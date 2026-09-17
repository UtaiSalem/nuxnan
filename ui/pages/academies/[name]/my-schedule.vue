<script setup lang="ts">
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'main'
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)

const academy = ref<any>(null)
const isLoading = ref(true)
const isLoadingSchedule = ref(false)
const hasAccess = ref(true)

const periodSets = ref<any[]>([])
const academicYears = ref<any[]>([])
const myScheduleData = ref<any>({ contexts: [] })

const selectedSemester = ref<number | null>(null)
const viewContextIndex = ref<number>(0)

const contexts = computed(() => myScheduleData.value.contexts || [])
const currentContext = computed(() => contexts.value[viewContextIndex.value] || null)

const currentTimetable = computed(() => currentContext.value?.timetable || [])
const currentGradeLevel = computed(() => {
  const ctx = currentContext.value
  if (!ctx) return null
  if (ctx.type === 'classroom') return ctx.entity?.grade_level || null
  return null
})

const { gridDays, gridRows, getScheduleCell } = useScheduleGrid({
  periodSets,
  timetable: currentTimetable,
  gradeLevel: currentGradeLevel,
})

const fetchPeriodSets = async (academyId: number) => {
  try {
    const res: any = await api.get(`/api/academies/${academyId}/schedule-period-sets`)
    periodSets.value = (res.data || []).filter((s: any) => s.is_active)
  } catch (err) {
    console.error(err)
    periodSets.value = []
  }
}

const fetchAcademicYears = async (academyId: number) => {
  try {
    const res: any = await api.get(`/api/academies/${academyId}/academic-years`)
    if (res.success && res.academicYears) {
      academicYears.value = res.academicYears
    }
  } catch (err) {
    console.error(err)
  }
}

const fetchMySchedule = async (academyId: number) => {
  isLoadingSchedule.value = true
  try {
    const params = selectedSemester.value ? { semester_id: selectedSemester.value } : {}
    const res: any = await api.get(`/api/academies/${academyId}/schedules/my`, { params })
    if (res.success) {
      myScheduleData.value = res.data
      if (res.data.semester && !selectedSemester.value) {
        selectedSemester.value = res.data.semester.id
      }
    }
  } catch (err) {
    console.error(err)
    myScheduleData.value = { contexts: [] }
  } finally {
    isLoadingSchedule.value = false
  }
}

watch(selectedSemester, (newVal, oldVal) => {
  if (newVal && oldVal && academy.value?.id) {
    fetchMySchedule(academy.value.id)
  }
})

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academy.value = response.academy
      const academyId = response.academy.id
      
      await Promise.all([
        fetchPeriodSets(academyId),
        fetchAcademicYears(academyId),
        fetchMySchedule(academyId)
      ])
    } else {
      hasAccess.value = false
    }
  } catch (err: any) {
    hasAccess.value = false
    console.error(err)
  } finally {
    isLoading.value = false
  }
})

const getScheduleColor = (schedule: any) => {
  if (schedule.course?.id) {
    const colors = [
      'bg-blue-100 dark:bg-blue-900/50 border-blue-300 dark:border-blue-700',
      'bg-green-100 dark:bg-green-900/50 border-green-300 dark:border-green-700',
      'bg-purple-100 dark:bg-purple-900/50 border-purple-300 dark:border-purple-700',
      'bg-amber-100 dark:bg-amber-900/50 border-amber-300 dark:border-amber-700',
      'bg-pink-100 dark:bg-pink-900/50 border-pink-300 dark:border-pink-700',
      'bg-cyan-100 dark:bg-cyan-900/50 border-cyan-300 dark:border-cyan-700',
      'bg-rose-100 dark:bg-rose-900/50 border-rose-300 dark:border-rose-700',
      'bg-indigo-100 dark:bg-indigo-900/50 border-indigo-300 dark:border-indigo-700',
    ]
    return colors[schedule.course.id % colors.length]
  }
  
  if (schedule.entry_type === 'break') return 'bg-orange-50 dark:bg-orange-900/30 border-orange-200 dark:border-orange-800'
  if (schedule.entry_type === 'exam') return 'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-800'
  return 'bg-gray-100 dark:bg-gray-800 border-gray-300 dark:border-gray-600'
}

const availableSemesters = computed(() => {
  const semesters: any[] = []
  academicYears.value.forEach(y => {
    (y.semesters || []).forEach((s: any) => {
      semesters.push({ ...s, yearName: y.name })
    })
  })
  return semesters
})

const currentDayOfWeek = new Date().getDay() || 7

const isToday = (dayValue: number) => {
  return dayValue === currentDayOfWeek
}
</script>

<template>
  <div class="px-4 sm:px-0">
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else-if="!hasAccess" class="py-20 text-center">
      <h3 class="text-lg font-medium text-gray-900 dark:text-white">คุณยังไม่ได้เป็นสมาชิกของโรงเรียนนี้</h3>
    </div>

    <div v-else class="space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">ตารางของฉัน</h1>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-6 shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex flex-col sm:flex-row gap-4">
          <div class="flex-1 min-w-0">
            <select
              v-model="selectedSemester"
              class="w-full sm:w-48 px-4 py-2.5 min-h-[44px] bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white"
            >
              <option :value="null" disabled>เลือกภาคเรียน</option>
              <option v-for="sem in availableSemesters" :key="sem.id" :value="sem.id">
                {{ sem.name }} ({{ sem.yearName }})
              </option>
            </select>
          </div>
          
          <div v-if="contexts.length > 1" class="flex bg-gray-100 dark:bg-gray-700 rounded-lg p-1 flex-shrink-0">
            <button
              v-for="(ctx, idx) in contexts"
              :key="idx"
              class="min-h-[44px] sm:min-h-0 min-w-0 flex-1 break-words px-4 py-2 rounded-lg text-sm font-medium transition-colors"
              @click="viewContextIndex = idx"
              :class="viewContextIndex === idx ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm' : 'text-gray-600 dark:text-gray-400'"
            >
              <Icon :icon="ctx.type === 'teacher' ? 'fluent:person-24-regular' : 'fluent:building-24-regular'" class="w-4 h-4 inline mr-1" />
              {{ ctx.type === 'teacher' ? 'ตารางสอนของฉัน' : `ตารางเรียนของห้อง ${ctx.entity.name}` }}
            </button>
          </div>
          <div v-else-if="contexts.length === 1">
            <div class="min-h-[44px] inline-flex items-center px-4 bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300 rounded-lg font-medium">
              <Icon :icon="contexts[0].type === 'teacher' ? 'fluent:person-24-regular' : 'fluent:building-24-regular'" class="w-5 h-5 mr-2" />
              {{ contexts[0].type === 'teacher' ? 'ตารางสอนของฉัน' : `ตารางเรียนของห้อง ${contexts[0].entity.name}` }}
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div v-if="isLoadingSchedule" class="flex items-center justify-center py-20">
          <div class="animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent"></div>
        </div>
        
        <div v-else-if="contexts.length === 0" class="p-12 text-center">
          <Icon icon="fluent:calendar-person-24-regular" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">ยังไม่มีตารางของคุณ</h3>
          <p class="text-gray-500 dark:text-gray-400">ระบบจะแสดงตารางสอนให้ครู และตารางเรียนของห้องให้นักเรียนที่มีข้อมูลการเข้าชั้นเรียนแล้ว</p>
        </div>
        
        <div v-else-if="gridRows.length === 0" class="p-12 text-center">
          <Icon icon="fluent:calendar-empty-24-regular" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">ยังไม่มีคาบในภาคเรียนนี้</h3>
        </div>
        
        <div v-else class="overflow-x-auto">
          <table class="w-full min-w-[800px]">
            <thead>
              <tr class="bg-gray-50 dark:bg-gray-700/50">
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-20 whitespace-nowrap">
                  เวลา
                </th>
                <th
                  v-for="day in gridDays"
                  :key="day.value"
                  class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap"
                  :class="{'bg-primary-50/60 dark:bg-primary-900/10': isToday(day.value)}"
                >
                  {{ day.label }}
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
              <tr
                v-for="row in gridRows"
                :key="row.key"
                :class="{'bg-amber-50/60 dark:bg-amber-900/10': row.type === 'break' || row.type === 'lunch'}"
              >
                <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 align-top whitespace-nowrap">
                  <p class="text-sm font-medium" :class="row.type === 'outside' ? 'text-orange-600 dark:text-orange-400' : 'text-gray-700 dark:text-gray-300'">{{ row.label }}</p>
                  <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ row.start }} - {{ row.end }}</p>
                </td>
                <td
                  v-for="day in gridDays"
                  :key="`${day.value}-${row.key}`"
                  class="px-2 py-2 min-h-[80px] h-full align-top relative"
                  :class="{'bg-primary-50/60 dark:bg-primary-900/10': isToday(day.value)}"
                >
                  <div v-if="getScheduleCell(day.value, row).length > 0" class="flex flex-col gap-2 h-full">
                    <div
                      v-for="schedule in getScheduleCell(day.value, row)"
                      :key="schedule.id"
                      :class="[
                        'p-2 rounded-lg border flex flex-col justify-between h-full min-h-[80px]',
                        getScheduleColor(schedule)
                      ]"
                    >
                      <div>
                        <p class="text-xs font-semibold text-gray-900 dark:text-white break-words line-clamp-2">
                          {{ schedule.title || schedule.course?.name }}
                        </p>
                        <p v-if="schedule.course?.code" class="text-[10px] text-gray-700 dark:text-gray-300 truncate mt-0.5">
                          {{ schedule.course.code }}
                        </p>
                      </div>
                      
                      <div class="mt-2">
                        <p class="text-[10px] text-gray-600 dark:text-gray-400 truncate">
                          {{ schedule.start_time }} - {{ schedule.end_time }}
                        </p>
                        <p v-if="currentContext?.type === 'classroom' && schedule.teacher" class="text-[10px] text-gray-500 dark:text-gray-400 truncate mt-0.5">
                          <Icon icon="fluent:person-24-regular" class="w-3 h-3 inline align-middle mr-0.5" />
                          {{ schedule.teacher?.name }}
                        </p>
                        <p v-if="currentContext?.type === 'teacher' && schedule.classroom" class="text-[10px] text-gray-500 dark:text-gray-400 truncate mt-0.5">
                          <Icon icon="fluent:building-24-regular" class="w-3 h-3 inline align-middle mr-0.5" />
                          {{ schedule.classroom?.name }}
                        </p>
                        <p v-if="schedule.room" class="text-[10px] text-gray-500 dark:text-gray-400 truncate mt-0.5">
                          <Icon icon="fluent:location-24-regular" class="w-3 h-3 inline align-middle mr-0.5" />
                          {{ schedule.room }}
                        </p>
                      </div>
                    </div>
                  </div>
                  <div v-else class="h-full w-full min-h-[80px]"></div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
