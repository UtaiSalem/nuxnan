<script setup lang="ts">
/**
 * Academy Transcripts Management
 * หน้าจัดการผลการเรียนและ Transcript
 */
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'main'
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)

// State
const academy = ref<any>(null)
const academyId = ref<number | null>(null)
const isLoading = ref(true)
const isGenerating = ref(false)
const isPublishing = ref(false)

// Data
const transcripts = ref<any[]>([])
const academicYears = ref<any[]>([])
const classrooms = ref<any[]>([])
const stats = ref<any>(null)

// Filters
const selectedAcademicYear = ref<number | null>(null)
const selectedSemester = ref<number | null>(null)
const selectedClassroom = ref<number | null>(null)
const searchQuery = ref('')

// Current Semester
const currentAcademicYear = ref<any>(null)
const semesters = computed(() => {
  if (!selectedAcademicYear.value) return []
  const year = academicYears.value.find((y: any) => y.id === selectedAcademicYear.value)
  return year?.semesters || []
})

const filteredTranscripts = computed(() => {
  let result = transcripts.value
  
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter((t: any) =>
      t.student?.student_id?.toLowerCase().includes(query) ||
      t.student?.first_name_th?.toLowerCase().includes(query) ||
      t.student?.last_name_th?.toLowerCase().includes(query)
    )
  }
  
  return result
})

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
    fetchAcademicYears(),
    fetchClassrooms(),
  ])
}

const fetchAcademicYears = async () => {
  try {
    const res: any = await api.get(`/api/academies/${academyId.value}/academic-years`)
    if (res.success) {
      academicYears.value = res.academicYears || []
      const current = academicYears.value.find((y: any) => y.is_current)
      if (current) {
        currentAcademicYear.value = current
        selectedAcademicYear.value = current.id
        const currentSem = current.semesters?.find((s: any) => s.is_current)
        if (currentSem) {
          selectedSemester.value = currentSem.id
        }
      }
    }
  } catch (err) {
    console.error('Failed to fetch academic years:', err)
  }
}

const fetchClassrooms = async () => {
  try {
    const params = new URLSearchParams()
    if (selectedAcademicYear.value) {
      params.append('academic_year_id', selectedAcademicYear.value.toString())
    }
    
    const res: any = await api.get(`/api/academies/${academyId.value}/classrooms?${params}`)
    if (res.success) {
      classrooms.value = res.classrooms || []
    }
  } catch (err) {
    console.error('Failed to fetch classrooms:', err)
  }
}

const fetchTranscripts = async () => {
  if (!selectedSemester.value) return
  
  try {
    const params = new URLSearchParams()
    params.append('semester_id', selectedSemester.value.toString())
    if (selectedClassroom.value) {
      params.append('classroom_id', selectedClassroom.value.toString())
    }
    
    const res: any = await api.get(`/api/academies/${academyId.value}/transcripts/overview?${params}`)
    if (res.success) {
      transcripts.value = res.transcripts || []
      stats.value = res.stats || null
    }
  } catch (err) {
    console.error('Failed to fetch transcripts:', err)
  }
}

watch([selectedAcademicYear], () => {
  fetchClassrooms()
  // Reset semester selection when year changes
  const year = academicYears.value.find((y: any) => y.id === selectedAcademicYear.value)
  const currentSem = year?.semesters?.find((s: any) => s.is_current)
  selectedSemester.value = currentSem?.id || null
})

watch([selectedSemester, selectedClassroom], () => {
  fetchTranscripts()
})

const generateTranscripts = async () => {
  if (!selectedSemester.value) {
    alert('กรุณาเลือกภาคเรียน')
    return
  }
  
  if (!confirm('ต้องการคำนวณผลการเรียนหรือไม่?')) return
  
  isGenerating.value = true
  try {
    await api.post(`/api/academies/${academyId.value}/transcripts/semester/generate`, {
      semester_id: selectedSemester.value,
      classroom_id: selectedClassroom.value,
    })
    
    await fetchTranscripts()
    alert('คำนวณผลการเรียนสำเร็จ')
  } catch (err: any) {
    console.error('Failed to generate:', err)
    alert(err.message || 'เกิดข้อผิดพลาด')
  } finally {
    isGenerating.value = false
  }
}

const publishTranscripts = async () => {
  if (!selectedSemester.value) {
    alert('กรุณาเลือกภาคเรียน')
    return
  }
  
  const draftCount = transcripts.value.filter((t: any) => t.status === 'draft').length
  if (draftCount === 0) {
    alert('ไม่มีผลการเรียนที่รอเผยแพร่')
    return
  }
  
  if (!confirm(`ต้องการเผยแพร่ผลการเรียน ${draftCount} รายการหรือไม่?`)) return
  
  isPublishing.value = true
  try {
    await api.post(`/api/academies/${academyId.value}/transcripts/semester/publish`, {
      semester_id: selectedSemester.value,
    })
    
    await fetchTranscripts()
    alert('เผยแพร่ผลการเรียนสำเร็จ')
  } catch (err: any) {
    console.error('Failed to publish:', err)
    alert(err.message || 'เกิดข้อผิดพลาด')
  } finally {
    isPublishing.value = false
  }
}

const downloadPdf = (transcript: any) => {
  window.open(`/api/students/${transcript.student_id}/transcripts/${transcript.id}/pdf`, '_blank')
}

const getGpaColor = (gpa: number) => {
  if (gpa >= 3.5) return 'text-green-600 dark:text-green-400'
  if (gpa >= 3.0) return 'text-blue-600 dark:text-blue-400'
  if (gpa >= 2.5) return 'text-amber-600 dark:text-amber-400'
  if (gpa >= 2.0) return 'text-orange-600 dark:text-orange-400'
  return 'text-red-600 dark:text-red-400'
}

const getStatusBadge = (status: string) => {
  switch (status) {
    case 'draft':
      return { text: 'ร่าง', class: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }
    case 'published':
      return { text: 'เผยแพร่แล้ว', class: 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-400' }
    case 'approved':
      return { text: 'อนุมัติแล้ว', class: 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-400' }
    default:
      return { text: status, class: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }
  }
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
            <NuxtLink :to="`/academies/${academyName}/admin/gradebook`" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
              <Icon icon="fluent:arrow-left-24-filled" class="w-5 h-5" />
            </NuxtLink>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
              <Icon icon="fluent:document-bullet-list-24-filled" class="w-7 h-7 text-cyan-500" />
              ผลการเรียนรายภาค
            </h1>
          </div>
          <p class="text-gray-600 dark:text-gray-400">สรุปผลการเรียนและออกใบ Transcript</p>
        </div>

        <div class="flex items-center gap-3">
          <button
            @click="generateTranscripts"
            :disabled="isGenerating || !selectedSemester"
            class="min-h-[44px] sm:min-h-0 px-4 py-2 border border-primary-500 text-primary-600 dark:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg font-medium transition-colors flex items-center gap-2 disabled:opacity-50"
          >
            <Icon :icon="isGenerating ? 'fluent:spinner-ios-20-filled' : 'fluent:calculator-24-filled'" :class="['w-5 h-5', isGenerating && 'animate-spin']" />
            คำนวณผลการเรียน
          </button>
          <button
            @click="publishTranscripts"
            :disabled="isPublishing || !selectedSemester"
            class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg font-medium transition-colors flex items-center gap-2 disabled:opacity-50"
          >
            <Icon :icon="isPublishing ? 'fluent:spinner-ios-20-filled' : 'fluent:send-24-filled'" :class="['w-5 h-5', isPublishing && 'animate-spin']" />
            เผยแพร่ทั้งหมด
          </button>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ปีการศึกษา</label>
            <select
              v-model="selectedAcademicYear"
              class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            >
              <option v-for="year in academicYears" :key="year.id" :value="year.id">
                {{ year.name }} {{ year.is_current ? '(ปัจจุบัน)' : '' }}
              </option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ภาคเรียน</label>
            <select
              v-model="selectedSemester"
              class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            >
              <option :value="null">เลือกภาคเรียน</option>
              <option v-for="semester in semesters" :key="semester.id" :value="semester.id">
                {{ semester.name }} {{ semester.is_current ? '(ปัจจุบัน)' : '' }}
              </option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ห้องเรียน</label>
            <select
              v-model="selectedClassroom"
              class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            >
              <option :value="null">ทุกห้องเรียน</option>
              <option v-for="classroom in classrooms" :key="classroom.id" :value="classroom.id">
                {{ classroom.name }}
              </option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ค้นหา</label>
            <div class="relative">
              <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
              <input
                v-model="searchQuery"
                type="text"
                placeholder="รหัส, ชื่อนักเรียน..."
                class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Stats -->
      <div v-if="stats" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-200 dark:border-gray-700">
          <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</div>
          <div class="text-sm text-gray-600 dark:text-gray-400">นักเรียนทั้งหมด</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-200 dark:border-gray-700">
          <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.published }}</div>
          <div class="text-sm text-gray-600 dark:text-gray-400">เผยแพร่แล้ว</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-200 dark:border-gray-700">
          <div class="text-2xl font-bold text-gray-600 dark:text-gray-400">{{ stats.draft }}</div>
          <div class="text-sm text-gray-600 dark:text-gray-400">ร่าง</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-200 dark:border-gray-700">
          <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ stats.avg_gpa?.toFixed(2) || '-' }}</div>
          <div class="text-sm text-gray-600 dark:text-gray-400">GPA เฉลี่ย</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-200 dark:border-gray-700">
          <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ stats.excellent }}</div>
          <div class="text-sm text-gray-600 dark:text-gray-400">เกียรตินิยม (≥3.5)</div>
        </div>
      </div>

      <!-- Transcripts Table -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div v-if="!selectedSemester" class="p-12 text-center">
          <Icon icon="fluent:calendar-24-regular" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">เลือกภาคเรียน</h3>
          <p class="text-gray-600 dark:text-gray-400">กรุณาเลือกภาคเรียนที่ต้องการดูผลการเรียน</p>
        </div>

        <div v-else-if="filteredTranscripts.length === 0" class="p-12 text-center">
          <Icon icon="fluent:document-bullet-list-24-regular" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">ยังไม่มีผลการเรียน</h3>
          <p class="text-gray-600 dark:text-gray-400 mb-6">คลิก "คำนวณผลการเรียน" เพื่อสร้างผลการเรียนรายภาค</p>
          <button
            @click="generateTranscripts"
            :disabled="isGenerating"
            class="inline-flex items-center gap-2 px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-medium transition-colors"
          >
            <Icon icon="fluent:calculator-24-filled" class="w-5 h-5" />
            คำนวณผลการเรียน
          </button>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  นักเรียน
                </th>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  ห้องเรียน
                </th>
                <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  หน่วยกิต
                </th>
                <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  GPA
                </th>
                <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  อันดับ
                </th>
                <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  สถานะ
                </th>
                <th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  จัดการ
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="transcript in filteredTranscripts" :key="transcript.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-900/50 flex items-center justify-center">
                      <span class="text-sm font-medium text-primary-600 dark:text-primary-400">
                        {{ transcript.student?.first_name_th?.[0] }}
                      </span>
                    </div>
                    <div>
                      <div class="font-medium text-gray-900 dark:text-white">
                        {{ transcript.student?.first_name_th }} {{ transcript.student?.last_name_th }}
                      </div>
                      <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ transcript.student?.student_id }}
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white">
                  {{ transcript.classroom?.name || '-' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center text-gray-900 dark:text-white">
                  {{ transcript.total_credits || 0 }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                  <span :class="['font-bold text-lg', getGpaColor(transcript.gpa)]">
                    {{ transcript.gpa?.toFixed(2) || '-' }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                  <span v-if="transcript.class_rank" class="text-gray-900 dark:text-white">
                    {{ transcript.class_rank }}/{{ transcript.class_total }}
                  </span>
                  <span v-else class="text-gray-400">-</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                  <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusBadge(transcript.status).class]">
                    {{ getStatusBadge(transcript.status).text }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right">
                  <div class="flex items-center justify-end gap-2">
                    <NuxtLink
                      :to="`/academies/${academyName}/admin/gradebook/transcripts/${transcript.id}`"
                      class="p-2 text-gray-500 hover:text-primary-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                      title="ดูรายละเอียด"
                    >
                      <Icon icon="fluent:eye-24-regular" class="w-5 h-5" />
                    </NuxtLink>
                    <button
                      @click="downloadPdf(transcript)"
                      class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 text-gray-500 hover:text-red-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                      title="ดาวน์โหลด PDF"
                    >
                      <Icon icon="fluent:document-pdf-24-regular" class="w-5 h-5" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
