<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { useApi } from '~/composables/useApi'
import ProgressCard from '~/components/course/ProgressCard.vue'

interface Props {
  courseId: string | number
  isCourseAdmin?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isCourseAdmin: false
})

const api = useApi()

// State
const members = ref<any[]>([])
const loading = ref(true)
const searchQuery = ref('')
const sortBy = ref<'name' | 'progress' | 'last_activity'>('progress')
const sortOrder = ref<'asc' | 'desc'>('desc')
const showDetailsModal = ref(false)
const selectedMember = ref<any>(null)
const memberDetails = ref<any>(null)

// Fetch progress data
const fetchProgress = async () => {
  loading.value = true
  try {
    const response = await api.get(`/courses/${props.courseId}/progress`)
    members.value = response.data.data || response.data
  } catch (error) {
    console.error('Error fetching progress:', error)
  } finally {
    loading.value = false
  }
}

// Filtered and sorted members
const filteredMembers = computed(() => {
  let result = [...members.value]
  
  // Filter by search
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(m => 
      m.user?.name?.toLowerCase().includes(query) ||
      m.user?.username?.toLowerCase().includes(query)
    )
  }
  
  // Sort
  result.sort((a, b) => {
    let comparison = 0
    
    if (sortBy.value === 'name') {
      comparison = (a.user?.name || '').localeCompare(b.user?.name || '')
    } else if (sortBy.value === 'progress') {
      const aProgress = calculateProgress(a)
      const bProgress = calculateProgress(b)
      comparison = aProgress - bProgress
    } else if (sortBy.value === 'last_activity') {
      const aDate = new Date(a.last_activity || 0).getTime()
      const bDate = new Date(b.last_activity || 0).getTime()
      comparison = aDate - bDate
    }
    
    return sortOrder.value === 'desc' ? -comparison : comparison
  })
  
  return result
})

// Calculate overall progress
const calculateProgress = (member: any) => {
  const lessons = member.lessons_progress || 0
  const assignments = member.assignments_progress || 0
  const quizzes = member.quizzes_progress || 0
  return Math.round((lessons * 40 + assignments * 30 + quizzes * 30) / 100)
}

// Class stats
const classStats = computed(() => {
  if (members.value.length === 0) return { avg: 0, min: 0, max: 0, completed: 0 }
  
  const progresses = members.value.map(m => calculateProgress(m))
  return {
    avg: Math.round(progresses.reduce((a, b) => a + b, 0) / progresses.length),
    min: Math.min(...progresses),
    max: Math.max(...progresses),
    completed: progresses.filter(p => p >= 100).length
  }
})

// View member details
const viewMemberDetails = async (member: any) => {
  selectedMember.value = member
  showDetailsModal.value = true
  
  try {
    const response = await api.get(`/courses/${props.courseId}/members/${member.id}/progress`)
    memberDetails.value = response.data
  } catch (error) {
    console.error('Error fetching member details:', error)
    memberDetails.value = null
  }
}

// Export progress
const exportProgress = async () => {
  try {
    const response = await api.get(`/courses/${props.courseId}/progress/export`, {
      responseType: 'blob'
    })
    
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `course-progress-${props.courseId}.xlsx`)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (error) {
    console.error('Error exporting progress:', error)
  }
}

// Toggle sort
const toggleSort = (field: 'name' | 'progress' | 'last_activity') => {
  if (sortBy.value === field) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortBy.value = field
    sortOrder.value = 'desc'
  }
}

// Init
onMounted(() => {
  fetchProgress()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          ความคืบหน้าของผู้เรียน
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">
          ทั้งหมด {{ members.length }} คน
        </p>
      </div>
      
      <button
        v-if="isCourseAdmin"
        @click="exportProgress"
        class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
      >
        <Icon icon="fluent:arrow-download-24-regular" class="w-5 h-5" />
        <span>ส่งออก Excel</span>
      </button>
    </div>
    
    <!-- Class Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 text-center shadow-sm">
        <Icon icon="fluent:data-trending-24-regular" class="w-8 h-8 mx-auto text-blue-500" />
        <div class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
          {{ classStats.avg }}%
        </div>
        <p class="text-sm text-gray-500">ค่าเฉลี่ยชั้นเรียน</p>
      </div>
      
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 text-center shadow-sm">
        <Icon icon="fluent:arrow-up-24-regular" class="w-8 h-8 mx-auto text-green-500" />
        <div class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
          {{ classStats.max }}%
        </div>
        <p class="text-sm text-gray-500">สูงสุด</p>
      </div>
      
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 text-center shadow-sm">
        <Icon icon="fluent:arrow-down-24-regular" class="w-8 h-8 mx-auto text-red-500" />
        <div class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
          {{ classStats.min }}%
        </div>
        <p class="text-sm text-gray-500">ต่ำสุด</p>
      </div>
      
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 text-center shadow-sm">
        <Icon icon="fluent:checkmark-circle-24-regular" class="w-8 h-8 mx-auto text-purple-500" />
        <div class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
          {{ classStats.completed }}
        </div>
        <p class="text-sm text-gray-500">เรียนจบแล้ว</p>
      </div>
    </div>
    
    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-3">
      <!-- Search -->
      <div class="relative flex-1">
        <Icon 
          icon="fluent:search-24-regular" 
          class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"
        />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="ค้นหาผู้เรียน..."
          class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        />
      </div>
      
      <!-- Sort buttons -->
      <div class="flex gap-2">
        <button
          @click="toggleSort('name')"
          :class="[
            'px-3 py-2 rounded-lg text-sm font-medium flex items-center gap-1 transition-colors',
            sortBy === 'name' 
              ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' 
              : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'
          ]"
        >
          ชื่อ
          <Icon 
            v-if="sortBy === 'name'"
            :icon="sortOrder === 'asc' ? 'fluent:arrow-up-24-regular' : 'fluent:arrow-down-24-regular'" 
            class="w-4 h-4"
          />
        </button>
        
        <button
          @click="toggleSort('progress')"
          :class="[
            'px-3 py-2 rounded-lg text-sm font-medium flex items-center gap-1 transition-colors',
            sortBy === 'progress' 
              ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' 
              : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'
          ]"
        >
          ความคืบหน้า
          <Icon 
            v-if="sortBy === 'progress'"
            :icon="sortOrder === 'asc' ? 'fluent:arrow-up-24-regular' : 'fluent:arrow-down-24-regular'" 
            class="w-4 h-4"
          />
        </button>
        
        <button
          @click="toggleSort('last_activity')"
          :class="[
            'px-3 py-2 rounded-lg text-sm font-medium flex items-center gap-1 transition-colors',
            sortBy === 'last_activity' 
              ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' 
              : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'
          ]"
        >
          ล่าสุด
          <Icon 
            v-if="sortBy === 'last_activity'"
            :icon="sortOrder === 'asc' ? 'fluent:arrow-up-24-regular' : 'fluent:arrow-down-24-regular'" 
            class="w-4 h-4"
          />
        </button>
      </div>
    </div>
    
    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>
    
    <!-- Progress Grid -->
    <div v-else-if="filteredMembers.length > 0" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <ProgressCard
        v-for="member in filteredMembers"
        :key="member.id"
        :member="member"
        :is-course-admin="isCourseAdmin"
        @view-details="viewMemberDetails"
      />
    </div>
    
    <!-- Empty State -->
    <div v-else class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl">
      <Icon icon="fluent:data-area-24-regular" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600" />
      <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
        {{ searchQuery ? 'ไม่พบผู้เรียน' : 'ยังไม่มีข้อมูลความคืบหน้า' }}
      </h3>
      <p class="mt-2 text-gray-500 dark:text-gray-400">
        {{ searchQuery ? 'ลองค้นหาด้วยคำอื่น' : 'ยังไม่มีผู้เรียนในคอร์สนี้' }}
      </p>
    </div>
    
    <!-- Member Details Modal -->
    <DialogModal :show="showDetailsModal" @close="showDetailsModal = false" max-width="2xl">
      <template #title>
        รายละเอียดความคืบหน้า - {{ selectedMember?.user?.name }}
      </template>
      
      <template #content>
        <div v-if="memberDetails" class="space-y-6 max-h-[60vh] overflow-y-auto">
          <!-- Lessons Progress -->
          <div>
            <h4 class="font-medium text-gray-900 dark:text-white flex items-center gap-2 mb-3">
              <Icon icon="fluent:book-24-regular" class="w-5 h-5 text-blue-500" />
              บทเรียน
            </h4>
            <div class="space-y-2">
              <div 
                v-for="lesson in memberDetails.lessons" 
                :key="lesson.id"
                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
              >
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ lesson.title }}</span>
                <span 
                  v-if="lesson.completed"
                  class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400"
                >
                  เรียนจบแล้ว
                </span>
                <span 
                  v-else
                  class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-500 dark:bg-gray-600 dark:text-gray-400"
                >
                  ยังไม่เริ่ม
                </span>
              </div>
            </div>
          </div>
          
          <!-- Assignments Progress -->
          <div>
            <h4 class="font-medium text-gray-900 dark:text-white flex items-center gap-2 mb-3">
              <Icon icon="fluent:document-text-24-regular" class="w-5 h-5 text-orange-500" />
              งานที่มอบหมาย
            </h4>
            <div class="space-y-2">
              <div 
                v-for="assignment in memberDetails.assignments" 
                :key="assignment.id"
                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
              >
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ assignment.title }}</span>
                <div class="flex items-center gap-2">
                  <span v-if="assignment.score !== null" class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ assignment.score }}/{{ assignment.max_score }}
                  </span>
                  <span 
                    :class="[
                      'text-xs px-2 py-1 rounded-full',
                      assignment.submitted 
                        ? 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400' 
                        : 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400'
                    ]"
                  >
                    {{ assignment.submitted ? 'ส่งแล้ว' : 'ยังไม่ส่ง' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Quizzes Progress -->
          <div>
            <h4 class="font-medium text-gray-900 dark:text-white flex items-center gap-2 mb-3">
              <Icon icon="fluent:quiz-new-24-regular" class="w-5 h-5 text-purple-500" />
              แบบทดสอบ
            </h4>
            <div class="space-y-2">
              <div 
                v-for="quiz in memberDetails.quizzes" 
                :key="quiz.id"
                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
              >
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ quiz.title }}</span>
                <div class="flex items-center gap-2">
                  <span v-if="quiz.score !== null" class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ quiz.score }}/{{ quiz.max_score }}
                  </span>
                  <span 
                    :class="[
                      'text-xs px-2 py-1 rounded-full',
                      quiz.completed 
                        ? 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400' 
                        : 'bg-gray-100 text-gray-500 dark:bg-gray-600 dark:text-gray-400'
                    ]"
                  >
                    {{ quiz.completed ? 'ทำแล้ว' : 'ยังไม่ได้ทำ' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div v-else class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>
      </template>
      
      <template #footer>
        <button
          @click="showDetailsModal = false"
          class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600"
        >
          ปิด
        </button>
      </template>
    </DialogModal>
  </div>
</template>
