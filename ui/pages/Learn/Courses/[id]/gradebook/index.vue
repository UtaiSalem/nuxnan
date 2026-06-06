<script setup lang="ts">
/**
 * Course Gradebook - Score Breakdown Dashboard
 */
import { Icon } from '@iconify/vue'
import GradebookScoreTable from '~/components/learn/course/gradebook/GradebookScoreTable.vue'
import ResyncButton from '~/components/learn/course/gradebook/ResyncButton.vue'

const route = useRoute()
const api = useApi()
const courseId = computed(() => route.params.id as string)

// Inject from parent layout
const course = inject('course') as Ref<any>
const isCourseAdmin = inject('isCourseAdmin') as Ref<boolean>

// State
const members = ref<any[]>([])
const isLoading = ref(true)
const courseTotalScore = ref(0)
const useLegacyGradebook = ref(false)

const fetchData = async () => {
  isLoading.value = true
  try {
    const res: any = await api.get(`/api/courses/${courseId.value}/score-breakdown`)
    if (res.success) {
      members.value = res.data || []
      courseTotalScore.value = res.course_total_score
      useLegacyGradebook.value = res.use_legacy_gradebook
    }
  } catch (err) {
    console.error('Failed to fetch score breakdown:', err)
  } finally {
    isLoading.value = false
  }
}

onMounted(async () => {
  if (courseId.value) {
    await fetchData()
  }
})

const handleResynced = () => {
  fetchData()
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
          <Icon icon="fluent:data-trending-24-regular" class="w-7 h-7 text-primary-500" />
          สรุปคะแนนรายวิชา
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
          {{ course?.name }} 
          <span v-if="useLegacyGradebook" class="ml-2 px-2 py-0.5 text-[10px] bg-amber-100 text-amber-800 rounded-full border border-amber-200">
            Legacy Mode
          </span>
        </p>
      </div>

      <div class="flex items-center gap-2">
        <ResyncButton :course-id="courseId" @resynced="handleResynced" />
      </div>
    </div>

    <!-- Legacy Warning -->
    <div v-if="useLegacyGradebook" class="p-4 bg-amber-50 border border-amber-200 rounded-xl flex gap-3 text-amber-800">
       <Icon icon="heroicons:information-circle" class="w-5 h-5 flex-shrink-0 mt-0.5" />
       <div class="text-sm">
          วิชานี้ใช้ระบบ <b>Legacy Gradebook</b> (ให้คะแนนด้วยมือ) ข้อมูลคะแนนในหน้านี้จะอิงตามสิ่งที่ครูบันทึกในหน้า "การประเมิน" 
          <NuxtLink :to="`/Learn/Courses/${courseId}/gradebook/assessments`" class="font-bold underline ml-1">ไปหน้าการประเมิน</NuxtLink>
       </div>
    </div>

    <!-- Main Content -->
    <div v-if="isLoading" class="flex flex-col items-center justify-center py-20 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
      <p class="mt-4 text-gray-500 text-sm">กำลังคำนวณคะแนนและสรุปผล...</p>
    </div>

    <div v-else class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div v-if="members.length === 0" class="p-12 text-center">
        <Icon icon="ph:users-four-bold" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">ยังไม่มีนักเรียนในวิชา</h3>
        <p class="text-gray-600 dark:text-gray-400">สรุปคะแนนจะแสดงเมื่อมีสมาชิกเข้าร่วมวิชาแล้ว</p>
      </div>

      <GradebookScoreTable v-else :members="members" :course-id="courseId" />
    </div>

    <!-- Info Footer -->
    <div class="text-xs text-gray-500 flex items-center gap-4 px-2">
       <span class="flex items-center gap-1">
          <div class="w-2 h-2 rounded-full bg-primary-500"></div>
          คะแนนรวมรายวิชาปัจจุบัน: {{ courseTotalScore }} คะแนน
       </span>
       <span class="flex items-center gap-1">
          <Icon icon="heroicons:question-mark-circle" class="w-3 h-3" />
          ระบบจะอัปเดตคะแนนให้อัตโนมัติเมื่อนักเรียนส่งงานหรือครูให้คะแนน
       </span>
    </div>
  </div>
</template>
