<template>
  <article
    class="group relative bg-white dark:bg-gray-800 rounded-3xl shadow-xl hover:shadow-2xl hover:shadow-indigo-500/20 transition-all duration-300 overflow-hidden mb-8 border border-gray-100 dark:border-gray-700 isolate"
  >
    <!-- Background Decoration (Glassmorphism blobs) -->
    <div class="absolute -top-24 -right-24 w-64 h-64 bg-purple-500/10 rounded-full blur-3xl group-hover:bg-purple-500/20 transition-all duration-500"></div>
    <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl group-hover:bg-blue-500/20 transition-all duration-500"></div>

    <!-- Header Section -->
    <div class="relative h-48 overflow-hidden">
      <!-- Gradient Background -->
      <div class="absolute inset-0 bg-gradient-to-br from-indigo-600 via-purple-600 to-blue-600 transition-transform duration-700 group-hover:scale-105"></div>
      
      <!-- Pattern Overlay -->
      <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>
      
      <!-- Content Overlay -->
      <div class="absolute inset-0 flex items-center justify-center">
        <div class="relative z-10 p-6 text-center transform transition-transform duration-500 group-hover:-translate-y-2">
            <div class="w-16 h-16 mx-auto bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center mb-3 shadow-inner ring-1 ring-white/30">
                <Icon icon="healthicons:i-exam-qualification-outline" class="w-9 h-9 text-white drop-shadow-md" />
            </div>
            <h3 class="text-white font-bold text-lg opacity-90 tracking-wide uppercase">แบบทดสอบ</h3>
        </div>
      </div>

      <!-- Badges Group -->
      <div class="absolute top-4 left-4 z-20 flex flex-wrap gap-2">
        <span
          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-black/30 backdrop-blur-md text-white ring-1 ring-white/20 shadow-sm"
        >
          <Icon icon="fluent:number-symbol-24-filled" class="w-3.5 h-3.5" />
          ชุดที่ {{ quizIndex }}
        </span>
        <span
          v-if="timeLimit"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-blue-500/20 backdrop-blur-md text-blue-50 ring-1 ring-blue-400/30 shadow-sm"
        >
          <Icon icon="fluent:timer-24-filled" class="w-3.5 h-3.5" />
          {{ timeLimit }} นาที
        </span>
      </div>

      <!-- Status Badge (Top Right) -->
      <div class="absolute top-4 right-4 z-20">
        <span
          :class="status.color"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold shadow-lg backdrop-blur-md ring-1 ring-white/20"
        >
          <Icon :icon="status.icon" class="w-3.5 h-3.5" />
          {{ status.text }}
        </span>
      </div>
    
      <!-- Admin Actions (Floating) -->
      <div
        v-if="isAdmin"
        class="absolute bottom-4 right-4 z-20 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300 translate-y-2 group-hover:translate-y-0"
      >
        <button
          @click.stop="handleDuplicate"
          class="p-2.5 bg-white text-gray-700 rounded-xl hover:bg-purple-50 hover:text-purple-600 transition-colors shadow-lg"
          title="คัดลอก"
        >
          <Icon icon="fluent:copy-24-filled" class="w-5 h-5" />
        </button>
        <button
          @click.stop="handleEdit"
          class="p-2.5 bg-white text-gray-700 rounded-xl hover:bg-gray-50 hover:text-blue-600 transition-colors shadow-lg"
          title="แก้ไข"
        >
          <Icon icon="fluent:edit-24-filled" class="w-5 h-5" />
        </button>
        <button
          @click.stop="handleDelete"
          class="p-2.5 bg-white text-gray-700 rounded-xl hover:bg-red-50 hover:text-red-600 transition-colors shadow-lg"
          title="ลบ"
        >
          <Icon icon="fluent:delete-24-filled" class="w-5 h-5" />
        </button>
      </div>
    </div>

    <!-- Body Content -->
    <div class="p-6 md:p-8 relative z-10">
      <div class="flex justify-between items-start mb-6">
          <div class="flex-1 pr-4">
              <h2 class="text-2xl md:text-3xl font-extrabold text-gray-800 dark:text-white leading-tight mb-2 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                {{ quiz.title || quiz.name }}
              </h2>
              <p v-if="quiz.description" class="text-gray-500 dark:text-gray-400 text-sm line-clamp-2 leading-relaxed">
                  {{ quiz.description }}
              </p>
          </div>
          <!-- Score Bubble -->
          <div class="flex flex-col items-center justify-center w-16 h-16 rounded-2xl bg-amber-50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-700/30 text-amber-600 dark:text-amber-500 shadow-sm flex-shrink-0">
             <span class="text-xl font-black">{{ totalScore }}</span>
             <span class="text-[10px] font-bold uppercase tracking-wide">คะแนน</span>
          </div>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-2 gap-4 mb-6">
          <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-gray-700/30 border border-gray-100 dark:border-gray-700">
             <div class="w-10 h-10 rounded-lg bg-white dark:bg-gray-800 flex items-center justify-center text-indigo-500 shadow-sm">
                <Icon icon="fluent:document-text-24-filled" class="w-5 h-5" />
             </div>
             <div>
                <p class="text-xs text-gray-400 font-medium uppercase">จำนวนข้อ</p>
                <p class="font-bold text-gray-700 dark:text-gray-200">{{ questionsCount }} ข้อ</p>
             </div>
          </div>
          <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-gray-700/30 border border-gray-100 dark:border-gray-700">
             <div class="w-10 h-10 rounded-lg bg-white dark:bg-gray-800 flex items-center justify-center text-emerald-500 shadow-sm">
                <Icon icon="fluent:target-arrow-24-filled" class="w-5 h-5" />
             </div>
             <div>
                <p class="text-xs text-gray-400 font-medium uppercase">เกณฑ์ผ่าน</p>
                <p class="font-bold text-gray-700 dark:text-gray-200">{{ passingScore }}%</p>
             </div>
          </div>
      </div>

      <!-- Dates (Conditional) -->
      <div v-if="quiz.start_date || quiz.end_date" class="flex flex-wrap gap-y-2 gap-x-6 text-sm text-gray-500 dark:text-gray-400 mb-6 px-1">
          <div v-if="quiz.start_date" class="flex items-center gap-1.5">
             <Icon icon="fluent:calendar-ltr-24-regular" class="w-4 h-4 text-gray-400" />
             <span>เริ่ม: {{ formatDate(quiz.start_date) }}</span>
          </div>
          <div v-if="quiz.end_date" class="flex items-center gap-1.5">
             <Icon icon="fluent:calendar-end-24-regular" class="w-4 h-4 text-gray-400" />
             <span>สิ้นสุด: {{ formatDate(quiz.end_date) }}</span>
          </div>
      </div>

      <!-- Divider -->
      <div class="h-px w-full bg-gradient-to-r from-transparent via-gray-200 dark:via-gray-700 to-transparent mb-6"></div>

      <!-- Result Card (Premium Style) -->
      <div v-if="hasAttempted && !isAdmin" class="mb-6">
         <div class="relative overflow-hidden rounded-2xl border transition-all duration-300"
              :class="isPassed 
                 ? (isBarelyPassed 
                    ? 'bg-gradient-to-r from-orange-50 to-amber-50 border-orange-200 dark:from-orange-900/20 dark:to-amber-900/20 dark:border-orange-800' 
                    : 'bg-gradient-to-r from-emerald-50 to-teal-50 border-emerald-200 dark:from-emerald-900/20 dark:to-teal-900/20 dark:border-emerald-800') 
                 : 'bg-gradient-to-r from-red-50 to-orange-50 border-red-200 dark:from-red-900/20 dark:to-orange-900/20 dark:border-red-800'">
            
             <div class="p-4 flex items-center justify-between relative z-10">
                 <div class="flex items-center gap-4">
                     <div class="relative">
                        <RadialProgress 
                            :percentage="parseFloat(attemptPercentage)" 
                            :color="isPassed ? (isBarelyPassed ? 'text-orange-500' : 'text-emerald-500') : 'text-red-500'"
                            :size="52"
                            :strokeWidth="4"
                            class="bg-white rounded-full shadow-sm"
                        >
                            <Icon :icon="isPassed ? 'fluent:trophy-24-filled' : 'fluent:dismiss-circle-24-filled'" class="w-5 h-5" :class="isPassed ? (isBarelyPassed ? 'text-orange-500' : 'text-emerald-500') : 'text-red-500'" />
                        </RadialProgress>
                     </div>
                     <div>
                         <p class="text-xs font-bold uppercase opacity-70" :class="isPassed ? (isBarelyPassed ? 'text-orange-800 dark:text-orange-300' : 'text-emerald-800 dark:text-emerald-300') : 'text-red-800 dark:text-red-300'">ผลการทดสอบ</p>
                         <p class="text-lg font-black tracking-tight" :class="isPassed ? (isBarelyPassed ? 'text-orange-700 dark:text-orange-400' : 'text-emerald-700 dark:text-emerald-400') : 'text-red-700 dark:text-red-400'">
                             {{ isPassed ? (isBarelyPassed ? 'ผ่านแบบเฉียดฉิว' : 'ผ่านการทดสอบ') : 'ไม่ผ่านเกณฑ์' }}
                         </p>
                     </div>
                 </div>
                 <div class="text-right">
                     <div class="text-2xl font-black tabular-nums" :class="isPassed ? 'text-emerald-600' : 'text-red-600'">
                         {{ attemptScore }}<span class="text-sm font-medium text-gray-500">/{{ totalScore }}</span>
                     </div>
                 </div>
             </div>
             
             <!-- Decorative elements -->
             <div class="absolute -right-6 -bottom-6 w-24 h-24 rounded-full opacity-50 blur-2xl" :class="isPassed ? 'bg-emerald-400' : 'bg-red-400'"></div>
         </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center gap-3">
          <button 
             @click="handleView"
             class="flex-1 py-3 px-4 rounded-xl font-bold text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors flex items-center justify-center gap-2"
          >
              <Icon icon="fluent:eye-24-filled" class="w-5 h-5" />
              รายละเอียด
          </button>
          
          <button
              v-if="!isAdmin || (isAdmin && quiz.questions_count > 0)"
              @click="handleStart"
              class="flex-[2] py-3 px-6 rounded-xl font-bold text-white shadow-lg hover:shadow-indigo-500/30 transform hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2 relative overflow-hidden group/btn"
              :class="isAdmin ? 'bg-gray-900 dark:bg-gray-700' : (hasAttempted ? 'bg-gradient-to-r from-blue-600 to-cyan-600' : 'bg-gradient-to-r from-indigo-600 to-purple-600')"
          >
              <div class="absolute inset-0 bg-white/20 translate-y-full group-hover/btn:translate-y-0 transition-transform duration-300"></div>
              <Icon :icon="hasAttempted ? 'fluent:arrow-rotate-clockwise-24-filled' : 'fluent:play-circle-24-filled'" class="w-6 h-6" />
              <span>{{ isAdmin ? 'ทดสอบ (Admin)' : (hasAttempted ? 'ทำแบบทดสอบอีกครั้ง' : 'เริ่มทำแบบทดสอบ') }}</span>
          </button>
          
          <div v-if="!isAdmin && !isAvailable" class="flex-[2] py-3 px-6 rounded-xl font-bold bg-gray-100 text-gray-400 flex items-center justify-center gap-2 cursor-not-allowed">
              <Icon icon="fluent:lock-closed-24-filled" />
              <span>ยังไม่เปิด</span>
          </div>
      </div>

    </div>
  </article>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { computed } from 'vue';
import RadialProgress from '~/components/Common/RadialProgress.vue';

interface Props {
  quiz: any
  isAdmin?: boolean
  courseId: string | number
  quizIndex?: number
}

const props = withDefaults(defineProps<Props>(), {
  isAdmin: false,
  quizIndex: 1
})

const emit = defineEmits<{
  edit: [quiz: any]
  delete: [quizId: number]
  start: [quiz: any]
  view: [quiz: any]
  duplicate: [quiz: any]
}>()

// Computed Status
const status = computed(() => {
  const isActive = props.quiz.is_active || props.quiz.status === 1;
  return isActive 
    ? { 
        text: 'เผยแพร่แล้ว', 
        color: 'bg-emerald-500/90 text-white shadow-emerald-500/30', 
        icon: 'fluent:checkmark-circle-24-filled' 
      }
    : { 
        text: 'ฉบับร่าง', 
        color: 'bg-gray-500/90 text-white', 
        icon: 'fluent:draft-24-regular' 
      };
});

const questionsCount = computed(() => props.quiz.questions_count || props.quiz.questions?.length || 0)
const totalScore = computed(() => props.quiz.total_score || 0)
const timeLimit = computed(() => props.quiz.time_limit || 0)
const passingScore = computed(() => props.quiz.passing_score || 50)

// Check if quiz is available now
const isAvailable = computed(() => {
  const now = new Date()
  if (props.quiz.start_date && new Date(props.quiz.start_date) > now) return false
  if (props.quiz.end_date && new Date(props.quiz.end_date) < now) return false
  return true
})

// Format date
const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('th-TH', {
    day: 'numeric',
    month: 'short',
    year: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Has user attempted
const hasAttempted = computed(() => {
  return props.quiz.current_result && props.quiz.current_result.completed_at
})

const attemptScore = computed(() => {
  if (!hasAttempted.value) return null
  return props.quiz.current_result?.score || 0
})

const attemptPercentage = computed(() => {
  if (!hasAttempted.value) return null
  return parseFloat(props.quiz.current_result?.percentage || 0).toFixed(1)
})

const isPassed = computed(() => {
  return props.quiz.current_result?.status === 'Pass' || props.quiz.current_result?.status === 'passed' || props.quiz.current_result?.status === 3
})

const isBarelyPassed = computed(() => {
    if (!isPassed.value) return false
    const percentage = parseFloat(props.quiz.current_result?.percentage || 0)
    const passing = props.quiz.passing_score || 50
    return percentage < passing + 10
})

// Methods
const handleEdit = () => emit('edit', props.quiz)
const handleDelete = () => emit('delete', props.quiz.id)
const handleStart = () => emit('start', props.quiz)
const handleView = () => emit('view', props.quiz)
const handleDuplicate = () => emit('duplicate', props.quiz)
</script>
