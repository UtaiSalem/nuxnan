<script setup lang="ts">
/**
 * Student Transcript Detail (Admin View)
 * หน้าดูรายละเอียดผลการเรียนนักเรียนสำหรับ Admin
 */
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'main'
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)
const studentId = computed(() => route.params.studentId as string)

// State
const academy = ref<any>(null)
const academyId = ref<number | null>(null)
const isLoading = ref(true)
const student = ref<any>(null)
const transcripts = ref<any[]>([])
const selectedTranscript = ref<any>(null)
const annualTranscripts = ref<any[]>([])
const viewMode = ref<'semester' | 'annual'>('semester')

const { isAdmin, fetchMyRole } = useAcademyRole(academyId)

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()
      
      if (!isAdmin.value) {
        navigateTo(`/academies/${academyName.value}`)
        return
      }
      
      await fetchData()
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

const fetchData = async () => {
  await Promise.all([
    fetchStudent(),
    fetchTranscripts(),
    fetchAnnualTranscripts(),
  ])
}

const fetchStudent = async () => {
  try {
    const res: any = await api.get(`/api/academies/${academyId.value}/students/${studentId.value}`)
    if (res.success) {
      student.value = res.student
    }
  } catch (err) {
    console.error('Failed to fetch student:', err)
  }
}

const fetchTranscripts = async () => {
  try {
    const res: any = await api.get(`/api/students/${studentId.value}/transcripts`, {
      params: { academy_id: academyId.value }
    })
    if (res.success) {
      transcripts.value = res.transcripts || []
      if (transcripts.value.length > 0) {
        selectedTranscript.value = transcripts.value[0]
      }
    }
  } catch (err) {
    console.error('Failed to fetch transcripts:', err)
  }
}

const fetchAnnualTranscripts = async () => {
  try {
    const res: any = await api.get(`/api/students/${studentId.value}/annual-transcripts`, {
      params: { academy_id: academyId.value }
    })
    if (res.success) {
      annualTranscripts.value = res.transcripts || []
    }
  } catch (err) {
    console.error('Failed to fetch annual transcripts:', err)
  }
}

const downloadPDF = async (transcript: any, type: 'semester' | 'annual' = 'semester') => {
  try {
    const endpoint = type === 'semester' 
      ? `/api/transcripts/${transcript.id}/pdf`
      : `/api/annual-transcripts/${transcript.id}/pdf`
    
    const res = await api.get(endpoint, { responseType: 'blob' })
    const blob = new Blob([res as any], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = type === 'semester'
      ? `transcript-${student.value?.student_number}-${transcript.academic_year_name}-sem${transcript.semester_number}.pdf`
      : `annual-transcript-${student.value?.student_number}-${transcript.academic_year_name}.pdf`
    link.click()
    window.URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Failed to download:', err)
    alert('ไม่สามารถดาวน์โหลดได้')
  }
}

const getGradeColor = (grade: string) => {
  const colors: Record<string, string> = {
    'A': 'text-green-600 dark:text-green-400',
    'B+': 'text-blue-600 dark:text-blue-400',
    'B': 'text-blue-600 dark:text-blue-400',
    'C+': 'text-yellow-600 dark:text-yellow-400',
    'C': 'text-yellow-600 dark:text-yellow-400',
    'D+': 'text-orange-600 dark:text-orange-400',
    'D': 'text-orange-600 dark:text-orange-400',
    'F': 'text-red-600 dark:text-red-400',
  }
  return colors[grade] || 'text-gray-600 dark:text-gray-400'
}

const getGPAColor = (gpa: number) => {
  if (gpa >= 3.5) return 'text-green-600 dark:text-green-400'
  if (gpa >= 3.0) return 'text-blue-600 dark:text-blue-400'
  if (gpa >= 2.5) return 'text-yellow-600 dark:text-yellow-400'
  if (gpa >= 2.0) return 'text-orange-600 dark:text-orange-400'
  return 'text-red-600 dark:text-red-400'
}

const getPromotionBadge = (status: string) => {
  const badges: Record<string, { label: string, color: string }> = {
    'promoted': { label: 'เลื่อนชั้น', color: 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-400' },
    'retained': { label: 'ซ้ำชั้น', color: 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-400' },
    'conditional': { label: 'มีเงื่อนไข', color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-400' },
    'pending': { label: 'รอพิจารณา', color: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' },
  }
  return badges[status] || { label: status, color: 'bg-gray-100 text-gray-800' }
}
</script>

<template>
  <div class="px-4 sm:px-0">
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <div class="flex items-center gap-3 mb-2">
            <NuxtLink :to="`/academies/${academyName}/admin/gradebook/students`" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
              <Icon icon="fluent:arrow-left-24-filled" class="w-5 h-5" />
            </NuxtLink>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
              <Icon icon="fluent:document-text-24-filled" class="w-7 h-7 text-primary-500" />
              ผลการเรียนนักเรียน
            </h1>
          </div>
        </div>
      </div>

      <!-- Student Info Card -->
      <div v-if="student" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center gap-4">
          <img
            v-if="student.user?.avatar"
            :src="student.user.avatar"
            :alt="student.user?.displayname"
            class="w-16 h-16 rounded-full object-cover"
          />
          <div v-else class="w-16 h-16 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
            <Icon icon="fluent:person-24-filled" class="w-8 h-8 text-gray-500 dark:text-gray-400" />
          </div>
          <div class="flex-1">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ student.user?.displayname }}</h2>
            <p class="text-gray-600 dark:text-gray-400">
              เลขประจำตัว: {{ student.student_number || '-' }} | 
              ห้อง: {{ student.classroom?.name || '-' }}
            </p>
          </div>
          <div class="text-right">
            <div class="text-sm text-gray-500 dark:text-gray-400">GPAX</div>
            <div :class="['text-3xl font-bold', getGPAColor(student.gpax || 0)]">
              {{ student.gpax?.toFixed(2) || '-' }}
            </div>
          </div>
        </div>
      </div>

      <!-- View Mode Tabs -->
      <div class="flex gap-2">
        <button class="min-h-[44px] sm:min-h-0"
          @click="viewMode = 'semester'"
          :class="[
            'px-4 py-2 rounded-lg font-medium transition-colors',
            viewMode === 'semester'
              ? 'bg-primary-500 text-white'
              : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
          ]"
        >
          <Icon icon="fluent:calendar-24-regular" class="w-5 h-5 inline mr-2" />
          รายภาคเรียน
        </button>
        <button class="min-h-[44px] sm:min-h-0"
          @click="viewMode = 'annual'"
          :class="[
            'px-4 py-2 rounded-lg font-medium transition-colors',
            viewMode === 'annual'
              ? 'bg-primary-500 text-white'
              : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
          ]"
        >
          <Icon icon="fluent:calendar-ltr-24-regular" class="w-5 h-5 inline mr-2" />
          รายปีการศึกษา
        </button>
      </div>

      <!-- Semester View -->
      <div v-if="viewMode === 'semester'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Transcript List -->
        <div class="lg:col-span-1 space-y-4">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">เลือกภาคเรียน</h2>
          
          <div v-if="transcripts.length === 0" class="bg-white dark:bg-gray-800 rounded-xl p-6 text-center border border-gray-200 dark:border-gray-700">
            <Icon icon="fluent:document-text-24-regular" class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
            <p class="text-gray-600 dark:text-gray-400">ยังไม่มีใบแสดงผลการเรียน</p>
          </div>
          
          <div v-else class="space-y-2">
            <button
              v-for="transcript in transcripts"
              :key="transcript.id"
              @click="selectedTranscript = transcript"
              :class="[
                'w-full p-4 rounded-xl text-left transition-all',
                selectedTranscript?.id === transcript.id
                  ? 'bg-primary-500 text-white shadow-lg'
                  : 'bg-white dark:bg-gray-800 hover:shadow-md border border-gray-200 dark:border-gray-700'
              ]"
            >
              <div class="flex items-center justify-between">
                <div>
                  <div :class="['font-semibold', selectedTranscript?.id === transcript.id ? 'text-white' : 'text-gray-900 dark:text-white']">
                    {{ transcript.academic_year_name }}
                  </div>
                  <div :class="['text-sm', selectedTranscript?.id === transcript.id ? 'text-white/80' : 'text-gray-600 dark:text-gray-400']">
                    ภาคเรียนที่ {{ transcript.semester_number }}
                  </div>
                </div>
                <div :class="['text-right', selectedTranscript?.id === transcript.id ? 'text-white' : '']">
                  <div :class="['text-lg font-bold', selectedTranscript?.id === transcript.id ? 'text-white' : getGPAColor(transcript.gpa)]">
                    {{ transcript.gpa?.toFixed(2) }}
                  </div>
                  <div :class="['text-xs', selectedTranscript?.id === transcript.id ? 'text-white/70' : 'text-gray-500 dark:text-gray-400']">GPA</div>
                </div>
              </div>
            </button>
          </div>
        </div>

        <!-- Transcript Detail -->
        <div v-if="selectedTranscript" class="lg:col-span-2">
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <!-- Header -->
            <div class="p-6 bg-gradient-to-r from-primary-500 to-primary-600 text-white">
              <div class="flex items-center justify-between">
                <div>
                  <h3 class="text-xl font-bold">{{ selectedTranscript.academic_year_name }}</h3>
                  <p class="text-white/80">ภาคเรียนที่ {{ selectedTranscript.semester_number }}</p>
                </div>
                <button
                  @click="downloadPDF(selectedTranscript, 'semester')"
                  class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg font-medium transition-colors flex items-center gap-2"
                >
                  <Icon icon="fluent:arrow-download-24-filled" class="w-5 h-5" />
                  ดาวน์โหลด PDF
                </button>
              </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-6 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
              <div class="text-center">
                <div class="text-2xl font-bold" :class="getGPAColor(selectedTranscript.gpa)">
                  {{ selectedTranscript.gpa?.toFixed(2) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">GPA</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                  {{ selectedTranscript.total_credits }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">หน่วยกิต</div>
              </div>
              <div v-if="selectedTranscript.class_rank" class="text-center">
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                  {{ selectedTranscript.class_rank }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">อันดับชั้น</div>
              </div>
              <div v-if="selectedTranscript.grade_rank" class="text-center">
                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                  {{ selectedTranscript.grade_rank }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">อันดับระดับชั้น</div>
              </div>
            </div>

            <!-- Grades Table -->
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">รหัสวิชา</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ชื่อวิชา</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">หน่วยกิต</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">เกรด</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                  <tr 
                    v-for="item in selectedTranscript.items" 
                    :key="item.id"
                    class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                  >
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                      {{ item.subject_code }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm font-medium text-gray-900 dark:text-white">{{ item.subject_name_th }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900 dark:text-white">
                      {{ item.credits }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                      <span :class="['text-lg font-bold', getGradeColor(item.letter_grade)]">
                        {{ item.letter_grade }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Annual View -->
      <div v-if="viewMode === 'annual'" class="space-y-4">
        <div v-if="annualTranscripts.length === 0" class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center border border-gray-200 dark:border-gray-700">
          <Icon icon="fluent:calendar-ltr-24-regular" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">ยังไม่มีผลการเรียนรายปี</h3>
          <p class="text-gray-600 dark:text-gray-400">ผลการเรียนรายปีจะปรากฏเมื่อสิ้นสุดปีการศึกษา</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="annual in annualTranscripts"
            :key="annual.id"
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
          >
            <div class="p-6 bg-gradient-to-r from-yellow-400 to-orange-500 text-white">
              <div class="flex items-center justify-between">
                <div>
                  <h3 class="text-lg font-bold">{{ annual.academic_year_name }}</h3>
                  <p class="text-white/80 text-sm">ระดับชั้น {{ annual.grade_level }}</p>
                </div>
                <button
                  @click="downloadPDF(annual, 'annual')"
                  class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 bg-white/20 hover:bg-white/30 rounded-lg transition-colors"
                  title="ดาวน์โหลด PDF"
                >
                  <Icon icon="fluent:arrow-download-24-filled" class="w-5 h-5" />
                </button>
              </div>
            </div>
            
            <div class="p-6 space-y-4">
              <div class="grid grid-cols-2 gap-4 text-center">
                <div>
                  <div :class="['text-2xl font-bold', getGPAColor(annual.gpax)]">{{ annual.gpax?.toFixed(2) }}</div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">GPAX</div>
                </div>
                <div>
                  <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ annual.total_credits }}</div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">หน่วยกิตสะสม</div>
                </div>
              </div>
              
              <div class="pt-4 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <span class="text-sm text-gray-600 dark:text-gray-400">สถานะ</span>
                <span :class="['px-2 py-1 text-xs font-medium rounded-full', getPromotionBadge(annual.promotion_status).color]">
                  {{ getPromotionBadge(annual.promotion_status).label }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
