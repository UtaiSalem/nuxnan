<script setup lang="ts">
import { Icon } from '@iconify/vue'

const props = defineProps<{
  members: any[]
  courseId: string | number
}>()

const showMissingModal = ref(false)
const selectedMissingSources = ref({})

const openMissingModal = (sources: any) => {
  selectedMissingSources.value = sources
  showMissingModal.value = true
}

const formatDate = (dateString: string) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getGradeColor = (grade: any) => {
  const g = parseFloat(grade)
  if (isNaN(g)) return 'text-gray-500'
  if (g >= 3.5) return 'text-green-600 dark:text-green-400 font-bold'
  if (g >= 2.0) return 'text-blue-600 dark:text-blue-400 font-medium'
  if (g >= 1.0) return 'text-yellow-600 dark:text-yellow-400'
  return 'text-red-600 dark:text-red-400 font-bold'
}
</script>

<template>
  <div class="overflow-x-auto">
    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
      <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
        <tr>
          <th scope="col" class="px-4 py-3 sticky left-0 bg-gray-50 dark:bg-gray-700 z-10">นักเรียน</th>
          <th scope="col" class="px-4 py-3 text-center">Quiz</th>
          <th scope="col" class="px-4 py-3 text-center">Lesson Q</th>
          <th scope="col" class="px-4 py-3 text-center">Course Asg</th>
          <th scope="col" class="px-4 py-3 text-center">Lesson Asg</th>
          <th scope="col" class="px-4 py-3 text-center">External</th>
          <th scope="col" class="px-4 py-3 text-center">Bonus</th>
          <th scope="col" class="px-4 py-3 font-bold text-gray-900 dark:text-white text-center">รวม</th>
          <th scope="col" class="px-4 py-3 text-center">%</th>
          <th scope="col" class="px-4 py-3 text-center">เกรด</th>
          <th scope="col" class="px-4 py-3 text-center">สถานะ</th>
          <th scope="col" class="px-4 py-3">ซิงค์ล่าสุด</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in members" :key="item.member_id" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
          <td class="px-4 py-4 sticky left-0 bg-white dark:bg-gray-800 z-10 font-medium text-gray-900 dark:text-white flex items-center gap-2">
             <CircleAvatar :src="item.user.avatar" :name="item.user.name" size="xs" />
             <div class="flex flex-col">
               <span class="whitespace-nowrap">{{ item.user.name }}</span>
               <span class="text-[10px] text-gray-500">{{ item.user.username }}</span>
             </div>
          </td>
          
          <!-- Quiz -->
          <td class="px-4 py-4 text-center">
            {{ item.breakdown.course_quiz.earned }}/{{ item.breakdown.course_quiz.max }}
          </td>
          
          <!-- Lesson Q -->
          <td class="px-4 py-4 text-center">
            {{ item.breakdown.lesson_question.earned }}/{{ item.breakdown.lesson_question.max }}
          </td>

          <!-- Course Asg -->
          <td class="px-4 py-4 text-center">
            {{ item.breakdown.course_assignment.earned }}/{{ item.breakdown.course_assignment.max }}
          </td>

          <!-- Lesson Asg -->
          <td class="px-4 py-4 text-center">
            {{ item.breakdown.lesson_assignment.earned }}/{{ item.breakdown.lesson_assignment.max }}
          </td>

          <!-- External -->
          <td class="px-4 py-4 text-center">
            {{ item.breakdown.external.earned }}/{{ item.breakdown.external.max }}
          </td>

          <!-- Bonus -->
          <td class="px-4 py-4 text-center" :class="{ 'text-green-500': item.breakdown.bonus > 0, 'text-red-500': item.breakdown.bonus < 0 }">
            {{ item.breakdown.bonus > 0 ? '+' : '' }}{{ item.breakdown.bonus }}
          </td>

          <!-- Total -->
          <td class="px-4 py-4 text-center font-bold text-gray-900 dark:text-white">
            {{ item.breakdown.total_earned }}/{{ item.breakdown.total_max }}
          </td>

          <!-- Percentage -->
          <td class="px-4 py-4 text-center">
            <span class="px-2 py-1 rounded text-xs font-semibold" :class="item.breakdown.percentage >= 50 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
              {{ item.breakdown.percentage }}%
            </span>
          </td>

          <!-- Projected Grade -->
          <td class="px-4 py-4 text-center" :class="getGradeColor(item.grade_progress)">
            {{ item.grade_progress }}
          </td>

          <!-- Missing Sources / Action -->
          <td class="px-4 py-4 text-center">
            <button 
              v-if="item.breakdown.missing_sources && (item.breakdown.missing_sources.quiz_ids?.length || item.breakdown.missing_sources.course_assignment_ids?.length || item.breakdown.missing_sources.lesson_assignment_ids?.length)"
              @click="openMissingModal(item.breakdown.missing_sources)"
              class="text-xs text-amber-600 hover:text-amber-700 underline flex items-center justify-center gap-1 mx-auto"
            >
              <Icon icon="heroicons:exclamation-triangle" class="w-3 h-3" />
              ยังไม่ครบ
            </button>
            <span v-else class="text-xs text-green-600 flex items-center justify-center gap-1">
              <Icon icon="heroicons:check-circle" class="w-3 h-3" />
              ครบถ้วน
            </span>
          </td>

          <td class="px-4 py-4 text-xs">
            {{ formatDate(item.last_synced_at) }}
          </td>
        </tr>
      </tbody>
    </table>

    <MissingSourcesModal 
      :show="showMissingModal" 
      :missing-sources="selectedMissingSources"
      :course-id="courseId"
      @close="showMissingModal = false"
    />
  </div>
</template>
