<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import AssignmentGradingView from '~/components/learn/course/AssignmentGradingView.vue'
import AssignmentSubmissionForm from '~/components/learn/course/AssignmentSubmissionForm.vue'
import ContentLoader from '~/components/accessories/ContentLoader.vue'

const route = useRoute()
const api = useApi()
const swal = useSweetAlert()
const { getAvatarUrl } = useAvatar()

// Params
const courseId = route.params.id
const assignmentId = route.params.assignmentId

// State
const isLoading = ref(true)
const assignment = ref<any>(null)
const course = ref<any>(null)
const groups = ref<any[]>([])
const isCourseAdmin = ref(false)

// Student Answer State
const answerContent = ref('')
const answerFiles = ref<File[]>([])
const existingImages = ref<any[]>([])
const deletedImageIds = ref<number[]>([])
const isSubmitting = ref(false)
const isEditing = ref(false)

// Navigation
const goBack = () => {
  navigateTo(`/courses/${courseId}/assignments`)
}

// Fetch Assignment Data
const fetchAssignment = async () => {
  isLoading.value = true
  try {
    const response = await api.get(`/api/courses/${courseId}/assignments/${assignmentId}`)
    assignment.value = response.assignment
    course.value = response.course
    groups.value = response.groups || []
    isCourseAdmin.value = response.isCourseAdmin
    
    // Admin does NOT fetch answers automatically anymore (lazy load)
  } catch (error) {
    console.error('Failed to fetch assignment:', error)
    swal.toast('ไม่สามารถโหลดข้อมูลภาระงานได้', 'error')
  } finally {
    isLoading.value = false
  }
}

// Helper for late check
const isLate = (answer: any) => {
    if (!assignment.value.due_date) return false
    return new Date(answer.created_at) > new Date(assignment.value.due_date)
}

// Student: Fetch/Compute User Answer
const userAnswer = computed(() => {
  if (isCourseAdmin.value) return null
  // API returns answers array. For student, it contains only their answer (if exists)
  return assignment.value?.answers?.[0] || null
})

const assignmentStatus = computed(() => {
  if (!userAnswer.value) return 'not_started'
  if (userAnswer.value.graded_score !== null) return 'graded'
  return 'submitted'
})

const statusConfig = computed(() => {
  const configs: Record<string, any> = {
    not_started: {
      icon: 'fluent:circle-24-regular',
      text: 'ยังไม่ได้ทำ',
      color: 'text-gray-700 dark:text-gray-300',
      bgColor: 'bg-gray-50 dark:bg-gray-800 ring-1 ring-inset ring-gray-600/20 dark:ring-gray-500/30',
    },
    submitted: {
      icon: 'fluent:checkmark-circle-24-filled',
      text: 'ส่งแล้ว - รอตรวจ',
      color: 'text-blue-700 dark:text-blue-400',
      bgColor: 'bg-blue-50 dark:bg-blue-900/30 ring-1 ring-inset ring-blue-700/10 dark:ring-blue-500/30',
    },
    graded: {
      icon: 'fluent:trophy-24-filled',
      text: 'ตรวจแล้ว',
      color: 'text-amber-700 dark:text-amber-400',
      bgColor: 'bg-amber-50 dark:bg-amber-900/30 ring-1 ring-inset ring-amber-600/20 dark:ring-amber-500/30',
    },
  }
  return configs[assignmentStatus.value] || configs.not_started
})

// Student: Submission
const handleFileSelect = (event: Event) => {
  const input = event.target as HTMLInputElement
  if (input.files) {
    answerFiles.value = [...answerFiles.value, ...Array.from(input.files)]
  }
}

const removeFile = (index: number) => {
  answerFiles.value.splice(index, 1)
}

const removeExistingImage = (imageId: number) => {
  const index = existingImages.value.findIndex(img => img.id === imageId)
  if (index !== -1) {
    existingImages.value.splice(index, 1)
    deletedImageIds.value.push(imageId)
  }
}

const editAnswer = () => {
    if(!userAnswer.value) return
    answerContent.value = userAnswer.value.content || ''
    existingImages.value = [...(userAnswer.value.images || [])]
    deletedImageIds.value = []
    answerFiles.value = []
    isEditing.value = true
}

const cancelEdit = () => {
    isEditing.value = false
    answerContent.value = ''
    answerFiles.value = []
    existingImages.value = []
    deletedImageIds.value = []
}

const submitAnswer = async () => {
    if (!answerContent.value.trim() && answerFiles.value.length === 0 && existingImages.value.length === 0) return

    isSubmitting.value = true
    try {
        const formData = new FormData()
        formData.append('content', answerContent.value)
        formData.append('course_id', courseId as string) // Important for score calculation

        answerFiles.value.forEach((file, index) => {
            formData.append(`images[${index}]`, file)
        })

        if (deletedImageIds.value.length > 0) {
           deletedImageIds.value.forEach((id, index) => {
               formData.append(`deleted_images[${index}]`, id.toString())
           })
        }
        
        await api.post(`/api/assignments/${assignmentId}/answers`, formData) // Assuming this endpoint handles update if answer exists
        
        await fetchAssignment() // Refresh data
        isEditing.value = false
        answerContent.value = ''
        answerFiles.value = []
        deletedImageIds.value = []

        swal.toast('ส่งคำตอบเรียบร้อยแล้ว', 'success')
    } catch (error) {
        console.error('Submission error:', error)
        swal.toast('ส่งคำตอบไม่สำเร็จ', 'error')
    } finally {
        isSubmitting.value = false
    }
}

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleString('th-TH', {
    dateStyle: 'medium',
    timeStyle: 'short'
  })
}

const userAvatar = (user: any) => getAvatarUrl(user)

const getAnswerCardClass = (answer: any) => {
   if (answer.points === null) return 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700'
   const passing = assignment.value.passing_score ?? 0
   return answer.points >= passing 
      ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800'
      : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800'
}

onMounted(() => {
  fetchAssignment()
})
</script>

<template>
  <div class="max-w-5xl mx-auto px-4 py-8">
    <ContentLoader v-if="isLoading" />

    <!-- Back Button -->
    <button 
      v-if="!isLoading"
      @click="goBack"
      class="mb-6 flex items-center gap-2 text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors"
    >
      <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
      <span>กลับไปหน้ารวมภาระงาน</span>
    </button>

    <div v-if="!isLoading && assignment" class="space-y-8">
      <!-- Header Card -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-start justify-between mb-4">
          <div class="flex items-center gap-3">
             <div class="w-12 h-12 rounded-xl bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center text-orange-600 dark:text-orange-400">
                <Icon icon="fluent:clipboard-task-24-filled" class="w-6 h-6" />
             </div>
             <div>
               <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ assignment.title }}</h1>
               <div class="flex items-center gap-3 text-sm text-gray-500 mt-1">
                 <span v-if="assignment.points" class="flex items-center gap-1">
                   <Icon icon="fluent:star-20-filled" class="w-4 h-4 text-amber-500" />
                   {{ assignment.points }} คะแนน
                 </span>
                 <span v-if="assignment.due_date" class="flex items-center gap-1">
                   <Icon icon="fluent:calendar-ltr-20-regular" class="w-4 h-4" />
                   กำหนดส่ง: {{ formatDate(assignment.due_date) }}
                 </span>
               </div>
             </div>
          </div>
          
          <!-- Status Badge (Student Only) -->
          <div v-if="!isCourseAdmin">
             <div 
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold shadow-sm"
               :class="statusConfig.bgColor + ' ' + statusConfig.color"
             >
                <Icon :icon="statusConfig.icon" class="w-5 h-5" />
                {{ statusConfig.text }}
             </div>
          </div>
        </div>

        <div v-if="assignment.description" class="mt-6">
           <RichTextViewer :content="assignment.description" class="!min-h-0" />
        </div>
        
        <div v-if="assignment.images?.length" class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
           <img 
             v-for="img in assignment.images" 
             :key="img.id"
             :src="img.full_url || img.image_url" 
             class="w-full h-40 object-cover rounded-xl shadow-sm hover:opacity-95 transition-opacity cursor-pointer border border-gray-100 dark:border-gray-700" 
           />
        </div>
      </div>

       <!-- Instructor Grading Section -->
       <div v-if="isCourseAdmin" class="mt-8">
            <AssignmentGradingView 
                v-if="assignment"
                :assignment="assignment" 
                :courseId="courseId" 
            />
       </div>

      <!-- Student Submission Section -->
      <div v-else class="space-y-6">
        <!-- Existing Answer -->
        <div v-if="userAnswer && !isEditing" class="bg-blue-50 dark:bg-blue-900/10 rounded-2xl p-6 border border-blue-100 dark:border-blue-800">
           <div class="flex justify-between items-start mb-4">
              <h3 class="font-bold text-blue-900 dark:text-blue-300 flex items-center gap-2">
                 <Icon icon="fluent:document-checkmark-24-filled" class="w-6 h-6" />
                 คำตอบของคุณ
              </h3>
              <button 
                 v-if="assignmentStatus !== 'graded'"
                 @click="editAnswer"
                 class="text-sm font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1"
              >
                 <Icon icon="fluent:edit-16-filled" class="w-4 h-4" />
                 แก้ไข
              </button>
           </div>
           
           <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-blue-100 dark:border-blue-800/50 shadow-sm text-gray-800 dark:text-gray-200">
              <RichTextViewer :content="userAnswer.content" />
           </div>

           <div v-if="userAnswer.images?.length" class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-3">
              <img v-for="img in userAnswer.images" :key="img.id" :src="img.full_url || img.image_url" class="w-full h-32 object-cover rounded-lg border border-blue-100 dark:border-blue-800" />
           </div>

           <div v-if="userAnswer.graded_score !== null" class="mt-4 bg-amber-50 dark:bg-amber-900/20 p-4 rounded-xl border border-amber-100 dark:border-amber-800/50 flex items-center gap-3">
              <div class="p-2 bg-amber-100 dark:bg-amber-800/50 rounded-full text-amber-600 dark:text-amber-400">
                 <Icon icon="fluent:trophy-24-filled" class="w-6 h-6" />
              </div>
              <div>
                 <div class="font-bold text-amber-900 dark:text-amber-300">เข้าตรวจแล้ว</div>
                 <div class="text-amber-700 dark:text-amber-400">คุณได้คะแนน {{ userAnswer.graded_score }} / {{ assignment.points }}</div>
              </div>
           </div>
        </div>

        <!-- Submission Form -->
        <div v-if="!userAnswer || isEditing">
           <AssignmentSubmissionForm 
             :assignment="assignment"
             :courseId="courseId"
             :existingAnswer="isEditing ? userAnswer : null"
             :isEditing="isEditing"
             @submitted="fetchAssignment"
             @cancel="cancelEdit"
           />
        </div>
      </div>
    </div>
    
     <!-- Not Found -->
    <div v-else-if="!isLoading" class="text-center py-20 text-gray-500">
       <div class="text-6xl mb-4">😕</div>
       <h2 class="text-xl font-bold">ไม่พบข้อมูลภาระงาน</h2>
    </div>
  </div>
</template>
