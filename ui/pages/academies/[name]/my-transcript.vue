<script setup lang="ts">
/**
 * Student Transcript View
 * หน้าดูใบเกรดสำหรับนักเรียน
 */
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: false
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)

// State
const academy = ref<any>(null)
const isLoading = ref(true)

// Data
const transcripts = ref<any[]>([])
const selectedTranscript = ref<any>(null)

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academy.value = response.academy
      await fetchTranscripts()
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

const fetchTranscripts = async () => {
  try {
    const res: any = await api.get(`/api/students/me/transcripts`, {
      params: { academy_id: academy.value.id }
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

const downloadPDF = async (transcript: any) => {
  try {
    const res = await api.get(`/api/students/me/transcripts/${transcript.id}/pdf`, {
      responseType: 'blob'
    })
    const blob = new Blob([res as any], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `transcript-${transcript.academic_year_name}-${transcript.semester_number}.pdf`
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
</script>

<template>
  <NuxtLayout name="academy" :academy-name="academyName">
    <div class="max-w-5xl mx-auto px-0 sm:px-4 py-8">
      <div v-if="isLoading" class="flex items-center justify-center py-20">
        <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
      </div>

      <div v-else class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
              <Icon icon="fluent:document-text-24-filled" class="w-7 h-7 text-primary-500" />
              ใบแสดงผลการเรียน
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">ดูผลการเรียนและดาวน์โหลดใบเกรด</p>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="transcripts.length === 0" class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-12 text-center shadow-sm border border-gray-200 dark:border-gray-700">
          <Icon icon="fluent:document-text-24-regular" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">ยังไม่มีใบแสดงผลการเรียน</h3>
          <p class="text-gray-600 dark:text-gray-400">ใบแสดงผลการเรียนจะปรากฏเมื่อมีการประกาศผลเรียบร้อยแล้ว</p>
        </div>

        <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Transcript List -->
          <div class="lg:col-span-1 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">เลือกภาคเรียน</h2>
            <div class="space-y-2">
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
                    @click="downloadPDF(selectedTranscript)"
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
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">รหัสวิชา</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ชื่อวิชา</th>
                      <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">หน่วยกิต</th>
                      <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">เกรด</th>
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
                        <div v-if="item.subject_name_en" class="text-xs text-gray-500 dark:text-gray-400">{{ item.subject_name_en }}</div>
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

              <!-- Footer -->
              <div v-if="selectedTranscript.remarks" class="p-6 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  <span class="font-medium">หมายเหตุ:</span> {{ selectedTranscript.remarks }}
                </p>
              </div>
            </div>

            <!-- GPAX Card (if available) -->
            <div v-if="selectedTranscript.gpax" class="mt-4 bg-gradient-to-r from-yellow-400 to-orange-500 rounded-xl p-6 text-white">
              <div class="flex items-center justify-between">
                <div>
                  <h4 class="text-lg font-semibold">เกรดเฉลี่ยสะสม (GPAX)</h4>
                  <p class="text-white/80 text-sm">คำนวณจากทุกภาคเรียนที่ผ่านมา</p>
                </div>
                <div class="text-right">
                  <div class="text-4xl font-bold">{{ selectedTranscript.gpax?.toFixed(2) }}</div>
                  <div class="text-sm text-white/80">{{ selectedTranscript.total_accumulated_credits }} หน่วยกิตสะสม</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </NuxtLayout>
</template>
