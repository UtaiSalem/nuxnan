<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { computed, ref } from 'vue'
import RichTextViewer from '../../RichTextViewer.vue'
import RichTextEditor from '../../RichTextEditor.vue'
import AssignmentGradingView from './AssignmentGradingView.vue'
import AssignmentSubmissionForm from './AssignmentSubmissionForm.vue'

interface Props {
  assignment: any
  isCourseAdmin?: boolean
  courseId: string | number
}

const props = withDefaults(defineProps<Props>(), {
  isCourseAdmin: false
})

const emit = defineEmits<{
  'edit': [assignment: any]
  'delete': [assignmentId: number]
  'click': [assignment: any]
  'refresh': []
}>()

const showFullContent = ref(false)

// Format date
const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleString('th-TH', {
    dateStyle: 'medium',
    timeStyle: 'short'
  })
}

// Get status badge
const getStatusBadge = computed(() => {
  if (props.assignment.status === 1 || props.assignment.is_published) {
    return { 
      text: 'เผยแพร่',
      color: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
      icon: 'fluent:checkmark-circle-16-filled'
    }
  }
  return { 
    text: 'ร่าง',
    color: 'bg-gray-100 text-gray-700 dark:bg-gray-700/30 dark:text-gray-400',
    icon: 'fluent:drafts-16-regular'
  }
})

  const isOverdue = computed(() => {
  if (!props.assignment.due_date) return false
  return new Date(props.assignment.due_date) < new Date()
})

const currentAnswer = computed(() => {
  if (props.assignment.answers && props.assignment.answers.length > 0) {
    return props.assignment.answers[0]
  }
  return null
})

const hasAnswer = computed(() => !!currentAnswer.value)

const answerStatus = computed(() => {
  if (currentAnswer.value) return currentAnswer.value.status
  return props.assignment.answer_status
})

const answerContent = computed(() => currentAnswer.value?.content)
const answerImages = computed(() => currentAnswer.value?.images || [])

const isSubmitted = computed(() => {
    return answerStatus.value === 'submitted' || answerStatus.value === 'graded'
})

const viewImage = (img: any) => {
  window.open(img.full_url || img.image_url, '_blank')
}

const showGrading = ref(false)
const isEditingGraded = ref(false)

const toggleGrading = () => {
  showGrading.value = !showGrading.value
}

const handleGradedEditSubmit = () => {
  isEditingGraded.value = false
  emit('refresh')
}
</script>



<template>
  <article 
    class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden mb-6 border border-gray-200 dark:border-gray-700 group"
  >
    <!-- Header Section (Cover) -->
    <div class="relative">
       <!-- Gradient Cover -->
       <div class="relative h-32 bg-gradient-to-br from-violet-600 via-indigo-600 to-cyan-600 dark:from-violet-900 dark:via-indigo-900 dark:to-cyan-900 overflow-hidden rounded-t-2xl">
          <div class="absolute inset-0 bg-black/10"></div>
          
          <!-- Center Icon -->
          <div class="w-full h-full flex items-center justify-center">
            <Icon icon="fluent:clipboard-task-24-filled" class="w-16 h-16 text-white/20 animate-pulse" />
          </div>
       </div>

       <!-- Badges -->
       <div class="absolute top-4 left-4 flex flex-wrap gap-2">
          <!-- Status -->
          <span 
            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold shadow-lg backdrop-blur-md bg-white/90 dark:bg-gray-900/80 ring-1 ring-white/20 transition-transform hover:scale-105"
            :class="getStatusBadge.color"
          >
             <Icon :icon="getStatusBadge.icon" class="w-3.5 h-3.5" />
             {{ getStatusBadge.text }}
          </span>

          <!-- Points -->
          <span v-if="assignment.points" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-500/90 backdrop-blur-md text-white text-xs font-bold shadow-lg ring-1 ring-white/20 transition-transform hover:scale-105">
             <Icon icon="fluent:star-20-filled" class="w-3.5 h-3.5" />
             {{ assignment.points }} คะแนน
          </span>
       </div>

       <!-- Admin Actions -->
       <div v-if="isCourseAdmin" class="absolute top-4 right-4 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
          <button 
            @click.stop="emit('edit', assignment)"
            class="p-2 bg-white/90 dark:bg-gray-800/90 rounded-lg hover:bg-white dark:hover:bg-gray-800 text-blue-600 shadow-lg backdrop-blur hover:scale-105 transition-all"
            title="แก้ไข"
          >
            <Icon icon="fluent:edit-24-regular" class="w-5 h-5" />
          </button>
          <button 
            @click.stop="emit('delete', assignment.id)"
            class="p-2 bg-white/90 dark:bg-gray-800/90 rounded-lg hover:bg-white dark:hover:bg-gray-800 text-red-600 shadow-lg backdrop-blur hover:scale-105 transition-all"
            title="ลบ"
          >
            <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
          </button>
       </div>
    </div>

    <!-- Content Section -->
    <div class="p-6">
       <!-- Title & Meta -->
       <div class="mb-4">
          <button @click="emit('click', assignment)" class="text-left w-full group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
             <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2 leading-tight">
                {{ assignment.title || assignment.name }}
             </h2>
          </button>
          
          <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400 mt-2">
             <div v-if="assignment.due_date" class="flex items-center gap-1.5" :class="isOverdue ? 'text-red-500 font-medium' : ''">
                <Icon icon="fluent:calendar-clock-24-regular" class="w-4 h-4" />
                <span>กำหนดส่ง: {{ formatDate(assignment.due_date) }}</span>
                <span v-if="isOverdue" class="text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full ml-1">เกินกำหนด</span>
             </div>
          </div>
       </div>

       <!-- Description -->
       <div v-if="assignment.description" class="mb-6">
          <RichTextEditor :model-value="assignment.description" disabled class="!min-h-0" />
       </div>




          <!-- Student Answer Status -->
           <!-- Student Answer Status -->

       <!-- Read-only Answer for Graded Assignments -->
       <div v-if="!isCourseAdmin && answerStatus === 'graded' && hasAnswer" class="mb-6 p-5 bg-emerald-50 dark:bg-emerald-900/10 rounded-xl border border-emerald-100 dark:border-emerald-700/30">
           <div class="flex justify-between items-start mb-3">
               <h3 class="text-sm font-bold text-emerald-900 dark:text-emerald-100 flex items-center gap-2">
                  <Icon icon="fluent:person-feedback-24-regular" class="w-5 h-5 text-emerald-600" />
                  คำตอบของคุณ (ตรวจแล้ว):
               </h3>
               <!-- Edit Button for Graded Answer -->
               <button 
                  @click="isEditingGraded = !isEditingGraded"
                  class="text-xs font-bold text-emerald-600 hover:text-emerald-700 flex items-center gap-1 px-2 py-1 rounded-lg hover:bg-emerald-100 transition-colors"
               >
                  <Icon :icon="isEditingGraded ? 'fluent:dismiss-16-regular' : 'fluent:edit-16-regular'" class="w-4 h-4" />
                  {{ isEditingGraded ? 'ยกเลิก' : 'แก้ไข' }}
               </button>
           </div>
           
           <!-- Answer Content (Read-only) -->
           <div v-if="answerContent && !isEditingGraded" class="text-sm text-gray-700 dark:text-gray-300 mb-4 leading-relaxed font-sans bg-white dark:bg-gray-800 p-3 rounded-lg border border-gray-100 dark:border-gray-700">
               <RichTextViewer :content="answerContent" />
           </div>

           <div v-if="answerImages.length && !isEditingGraded" class="flex flex-wrap gap-3">
                 <div v-for="img in answerImages" :key="img.id" class="group relative w-24 h-24 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-600 shadow-sm hover:shadow-md transition-all">
                    <img :src="img.full_url || img.image_url" class="w-full h-full object-cover cursor-zoom-in group-hover:scale-110 transition-transform duration-500" @click="viewImage(img)" />
                 </div>
           </div>

           <!-- Edit Form for Graded Answer -->
           <div v-if="isEditingGraded" class="mt-4">
              <AssignmentSubmissionForm 
                 :assignment="assignment"
                 :courseId="courseId"
                 :is-editing="true"
                 :existing-answer="currentAnswer"
                 :show-cancel="true"
                 @submitted="handleGradedEditSubmit"
                 @cancel="isEditingGraded = false"
              />
           </div>
       </div>

        <!-- Action Footer & Admin View -->
        <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
           <!-- Status Badge Row (Only if it has status info) -->
           <div v-if="answerStatus || (!isCourseAdmin && hasAnswer)" class="flex items-center justify-between mb-4">
             <div v-if="answerStatus" class="flex items-center gap-2">
                <span class="text-sm font-medium text-gray-500">สถานะ:</span>
                <span 
                   class="px-3 py-1 rounded-full text-xs font-bold border flex items-center gap-1.5"
                   :class="answerStatus === 'submitted' || answerStatus === 'graded'
                       ? 'bg-emerald-50 text-emerald-600 border-emerald-100' 
                       : 'bg-orange-50 text-orange-500 border-orange-100'"
                >
                   <Icon :icon="answerStatus === 'submitted' || answerStatus === 'graded' ? 'fluent:checkmark-circle-16-filled' : 'fluent:circle-16-regular'" />
                   {{ (answerStatus === 'submitted' || answerStatus === 'graded') ? 'ส่งแล้ว' : 'ยังไม่ส่ง' }}
                </span>
             </div>
             <div v-else></div>

          </div>

          <!-- Score Progress Bar (Student - Graded Only) -->
          <div v-if="!isCourseAdmin && answerStatus === 'graded' && hasAnswer" class="mt-3">
             <div class="flex justify-between items-center mb-1.5">
                 <span class="text-xs font-medium text-gray-500 dark:text-gray-400">คะแนน</span>
                 <span class="text-sm font-bold" :class="(currentAnswer?.points || 0) >= (assignment.passing_score || 0) ? 'text-emerald-600' : 'text-red-500'">
                     {{ currentAnswer?.points || 0 }} / {{ assignment.points }}
                     <span v-if="assignment.passing_score" class="ml-1 text-xs font-normal">
                        <span v-if="(currentAnswer?.points || 0) >= assignment.passing_score" class="text-emerald-500">✓ ผ่าน</span>
                        <span v-else class="text-red-400">✗ ไม่ผ่าน</span>
                     </span>
                 </span>
             </div>
             <div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                 <div 
                     class="h-full rounded-full transition-all duration-500"
                     :class="(currentAnswer?.points || 0) >= (assignment.passing_score || 0) ? 'bg-gradient-to-r from-emerald-400 to-emerald-600' : 'bg-gradient-to-r from-red-400 to-red-600'"
                     :style="{ width: `${((currentAnswer?.points || 0) / (assignment.points || 1)) * 100}%` }"
                 ></div>
             </div>
          </div>
       
           <!-- Admin Grading View (Shared footer space) -->
           <div v-if="isCourseAdmin" class="mt-4">
               <AssignmentGradingView 
                 :assignment="assignment"
                 :courseId="courseId"
               />
           </div>

           <!-- Student Direct Submission Form / Edit Form -->
           <div v-if="!isCourseAdmin && (answerStatus !== 'graded')" class="mt-4">
              <AssignmentSubmissionForm 
                 :assignment="assignment"
                 :courseId="courseId"
                 :is-editing="hasAnswer"
                 :existing-answer="currentAnswer"
                 :show-cancel="false"
                 @submitted="emit('refresh')"
              />
           </div>
        </div>
    </div>
  </article>
</template>
