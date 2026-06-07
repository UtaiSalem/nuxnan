<script setup lang="ts">
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import RadialProgress from '~/components/Common/RadialProgress.vue';
import QuizDuplicateModal from '~/components/learn/course/quiz/QuizDuplicateModal.vue';
import ExamEligibilityPanel from '~/components/learn/course/ExamEligibilityPanel.vue';

const route = useRoute()
const courseId = route.params.id
const quizId = route.params.quizId
const api = useApi()

const isCourseAdmin = inject<Ref<boolean>>('isCourseAdmin')

// Duplicate Modal State
const showDuplicateModal = ref(false)

const openDuplicateModal = () => {
  showDuplicateModal.value = true
}

const handleDuplicated = (newQuiz: any) => {
  // If duplicated to same course, could navigate to new quiz
  if (newQuiz && newQuiz.course_id === Number(courseId)) {
    refresh()
  }
}

// Group filter state
const groups = ref<any[]>([])
const selectedGroupId = ref<number | null>(null)
const canTakeExam = ref(true)
const eligibility = ref<any>(null)
const remediationStatus = ref<any>(null)
const retakeStatus = ref<any>(null)
const remediationSessionsEnabled = false

// Fetch quiz details
const { data: quiz, refresh, pending } = await useAsyncData(
  `course-quiz-${quizId}`,
  async () => {
    const res = await api.get(`/api/courses/${courseId}/quizzes/${quizId}`)
    if (res.groups) {
      groups.value = res.groups
    }
    if (res.canTakeExam !== undefined) {
      canTakeExam.value = res.canTakeExam
      eligibility.value = res.eligibility
    }
    
    remediationStatus.value = res.remediation_status || null
    retakeStatus.value = res.retake_status || null
    
    return res.quiz
  }
)

// Filtered student results by group
const filteredStudentResults = computed(() => {
  if (!quiz.value?.student_results) return []
  if (!selectedGroupId.value) return quiz.value.student_results

  const group = groups.value.find((g: any) => g.id === selectedGroupId.value)
  if (!group) return quiz.value.student_results
  return quiz.value.student_results.filter((result: any) =>
    group.member_user_ids.includes(result.user_id)
  )
})

const startQuiz = async () => {
  if (!canTakeExam.value && eligibility.value) {
    Swal.fire('ไม่มีสิทธิ์สอบ', 'กรุณาดำเนินการคืนสิทธิ์สอบก่อนเริ่มทำแบบทดสอบ', 'warning')
    return
  }

  navigateTo(`/courses/${courseId}/quizzes/${quizId}/attempt`)
}

const editQuiz = () => {
  navigateTo(`/courses/${courseId}/quizzes/${quizId}/edit`)
}

const deleteQuiz = async () => {
  const result = await Swal.fire({
    title: 'ยืนยันการลบ?',
    text: "คุณต้องการลบแบบทดสอบนี้ใช่หรือไม่ การกระทำนี้ไม่สามารถยกเลิกได้",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'ใช่, ลบเลย',
    cancelButtonText: 'ยกเลิก'
  })

  if (result.isConfirmed) {
    try {
      await api.delete(`/api/courses/${courseId}/quizzes/${quizId}`)
      await Swal.fire('ลบสำเร็จ!', 'แบบทดสอบถูกลบแล้ว', 'success')
      navigateTo(`/courses/${courseId}/quizzes`)
    } catch (err) {
      Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถลบแบบทดสอบได้', 'error')
    }
  }
}


const recalculateScores = async () => {
  try {
    const result = await Swal.fire({
      title: 'คำนวณคะแนนใหม่?',
      text: "ระบบจะคำนวณผลคะแนนของนักเรียนทุกคนใหม่ตามเกณฑ์ปัจจุบัน หากมีการแก้ไขคะแนนหรือข้อสอบหลังจากนักเรียนสอบเสร็จ แนะนำให้กดปุ่มนี้",
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'ยืนยัน, คำนวณใหม่',
      confirmButtonColor: '#f97316',
      cancelButtonText: 'ยกเลิก'
    })
    
    if (result.isConfirmed) {
      Swal.fire({
        title: 'กำลังคำนวณ...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
      })
      
      const res = await api.post(`/api/courses/${courseId}/quizzes/${quizId}/recalculate`)
      
      await Swal.fire('เรียบร้อย', res.message, 'success')
      refresh()
    }
  } catch (err) {
    Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถคำนวณคะแนนได้', 'error')
  }
}

// Formatters
const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getStatusBadge = computed(() => {
  if (!quiz.value) return {}
  if (quiz.value.is_active) {
    return { text: 'เผยแพร่แล้ว', class: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' }
  }
  return { text: 'ฉบับร่าง', class: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' }
})

</script>

<template>
  <div class="container mx-auto px-4 py-6 max-w-4xl">
    
    <!-- Loading -->
    <div v-if="pending" class="flex justify-center p-8 sm:p-12">
      <Icon icon="svg-spinners:3-dots-fade" class="w-10 h-10 text-gray-400" />
    </div>

    <div v-else-if="quiz">
      <!-- Header / Nav -->
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
        <button 
          @click="navigateTo(`/courses/${courseId}/quizzes`)"
          class="flex items-center gap-2 text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors"
        >
          <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
          <span>กลับไปหน้ารวมแบบทดสอบ</span>
        </button>

        <div v-if="isCourseAdmin" class="flex items-center gap-2 flex-wrap">
          <button 
            @click="openDuplicateModal"
            class="px-4 py-2 rounded-lg bg-purple-50 text-purple-600 hover:bg-purple-100 dark:bg-purple-900/20 dark:text-purple-400 dark:hover:bg-purple-900/30 transition-colors flex items-center gap-2"
          >
            <Icon icon="fluent:copy-24-regular" class="w-5 h-5" />
            <span class="hidden sm:inline">คัดลอก</span>
          </button>
          <button 
            @click="editQuiz"
            class="px-4 py-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400 dark:hover:bg-blue-900/30 transition-colors flex items-center gap-2"
          >
            <Icon icon="fluent:edit-24-regular" class="w-5 h-5" />
            <span class="hidden sm:inline">แก้ไข</span>
          </button>
          <button 
            @click="deleteQuiz"
            class="px-4 py-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/30 transition-colors flex items-center gap-2"
          >
            <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
            <span class="hidden sm:inline">ลบ</span>
          </button>
        </div>
      </div>

      <!-- Main Content -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        
        <!-- Cover / Banner Area -->
        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 p-4 sm:p-6 sm:p-8 text-white">
          <div class="flex flex-col sm:flex-row items-center sm:items-start text-center sm:text-left gap-4">
            <div class="w-16 h-16 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center border border-white/30 shrink-0">
              <Icon icon="fluent:quiz-new-24-filled" class="w-8 h-8 text-white" />
            </div>
            <div>
              <div class="flex flex-wrap justify-center sm:justify-start items-center gap-2 mb-2">
                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-white/20 text-white border border-white/20">
                  {{ getStatusBadge.text }}
                </span>
                <span v-if="quiz.time_limit" class="flex items-center gap-1 text-xs bg-black/20 px-2 py-0.5 rounded-full">
                  <Icon icon="fluent:timer-24-filled" class="w-3 h-3" />
                  {{ quiz.time_limit }} นาที
                </span>
              </div>
              <h1 class="text-3xl font-bold mb-2">{{ quiz.title }}</h1>
              <p class="text-purple-100 text-lg opacity-90">{{ quiz.description || 'ไม่มีคำอธิบาย' }}</p>
            </div>
          </div>
        </div>

        <!-- Info Grid -->
        <div class="grid md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-gray-200 dark:divide-gray-700 border-b border-gray-200 dark:border-gray-700">
          <div class="p-4 sm:p-6 text-center">
            <div class="text-sm text-gray-500 mb-1">จำนวนข้อ</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ quiz.questions_count ?? quiz.questions?.length ?? 0 }}
            </div>
          </div>
          <div class="p-4 sm:p-6 text-center">
            <div class="text-sm text-gray-500 mb-1">คะแนนเต็ม</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ quiz.total_score || 0 }}
            </div>
          </div>
          <div class="p-4 sm:p-6 text-center">
            <div class="text-sm text-gray-500 mb-1">เกณฑ์ผ่าน</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ quiz.passing_score }}%
            </div>
          </div>
        </div>

        <!-- Dates -->
        <div class="p-4 sm:p-6 bg-gray-50 dark:bg-gray-700/30 border-b border-gray-200 dark:border-gray-700">
          <div class="grid md:grid-cols-2 gap-4 text-sm">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400">
                <Icon icon="fluent:calendar-ltr-24-regular" class="w-5 h-5" />
              </div>
              <div>
                <p class="text-gray-500">เริ่มทำได้ตั้งแต่</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ formatDate(quiz.start_date) }}</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center text-orange-600 dark:text-orange-400">
                <Icon icon="fluent:calendar-end-24-regular" class="w-5 h-5" />
              </div>
              <div>
                <p class="text-gray-500">สิ้นสุดเมื่อ</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ formatDate(quiz.end_date) }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Action Area -->
        <div class="p-8 text-center">
          <div v-if="!isCourseAdmin">
             <!-- Eligibility Panel / Retake Grant -->
             <div v-if="(!canTakeExam && eligibility) || retakeStatus?.can_retake" class="mb-8 text-left">
               <ExamEligibilityPanel 
                  :course-id="courseId" 
                  :can-take-exam="canTakeExam" 
                  :eligibility="eligibility"
                  :retake-status="retakeStatus"
                  @unlocked="refresh"
               />
             </div>

             <!-- Remediation Status Card -->
             <div v-if="remediationSessionsEnabled && remediationStatus" class="mb-6 p-4 rounded-xl border-2 transition-all"
                  :class="[
                    remediationStatus.enrollment?.status === 'passed' ? 'bg-green-50 border-green-200 dark:bg-green-900/10 dark:border-green-800' :
                    remediationStatus.enrollment?.status === 'failed' ? 'bg-red-50 border-red-200 dark:bg-red-900/10 dark:border-red-800' :
                    'bg-blue-50 border-blue-200 dark:bg-blue-900/10 dark:border-blue-800'
                  ]">
               <div class="flex items-start gap-3">
                 <div class="p-2 rounded-lg"
                      :class="[
                        remediationStatus.enrollment?.status === 'passed' ? 'bg-green-100 text-green-600' :
                        remediationStatus.enrollment?.status === 'failed' ? 'bg-red-100 text-red-600' :
                        'bg-blue-100 text-blue-600'
                      ]">
                   <Icon :icon="remediationStatus.enrollment?.status === 'passed' ? 'fluent:checkmark-circle-24-filled' : 
                               remediationStatus.enrollment?.status === 'failed' ? 'fluent:dismiss-circle-24-filled' : 
                               'fluent:info-24-filled'" class="w-6 h-6" />
                 </div>
                 <div class="flex-1 text-left">
                   <h4 class="font-bold text-gray-900 dark:text-white mb-1">
                     รอบแก้ตัว: {{ remediationStatus.session_title }}
                   </h4>
                   
                   <!-- Case 1: Not enrolled -->
                   <template v-if="!remediationStatus.enrollment">
                     <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">คุณมีสิทธิ์ลงทะเบียนสอบแก้ตัวสำหรับวิชานี้</p>
                     <NuxtLink :to="`/Learn/Courses/${courseId}/gradebook/remediation`" 
                               class="inline-flex items-center gap-1 text-sm font-bold text-blue-600 hover:underline">
                       ดูรายละเอียดและลงทะเบียน <Icon icon="fluent:arrow-right-24-regular" />
                     </NuxtLink>
                   </template>

                   <!-- Case 2: Enrolled/Confirmed -->
                   <template v-else-if="['enrolled', 'confirmed'].includes(remediationStatus.enrollment.status)">
                     <p class="text-sm text-gray-600 dark:text-gray-400">คุณลงทะเบียนรอบแก้ตัวไว้แล้ว กรุณารอผลการพิจารณาหรือเข้าสอบตามกำหนดการ</p>
                   </template>

                   <!-- Case 3: Passed -->
                   <template v-else-if="remediationStatus.enrollment.status === 'passed'">
                     <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">คุณผ่านการแก้ตัวแล้ว! คุณสามารถสอบใหม่เพื่อปรับปรุงคะแนนได้</p>
                     <p v-if="remediationStatus.enrollment.remediation_score" class="text-xs font-bold text-green-600">
                       คะแนนแก้ตัวที่ได้รับ: {{ remediationStatus.enrollment.remediation_score }}
                     </p>
                   </template>

                   <!-- Case 4: Failed -->
                   <template v-else-if="remediationStatus.enrollment.status === 'failed'">
                     <p class="text-sm text-gray-600 dark:text-gray-400">คุณไม่ผ่านการแก้ตัวในรอบนี้</p>
                   </template>
                 </div>
               </div>
             </div>

             <div v-if="quiz.current_result && quiz.current_result.completed_at" class="bg-gray-100 dark:bg-gray-700/50 rounded-xl p-4 sm:p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4 text-center">ผลการทดสอบของคุณ</h3>
                <div class="flex justify-center gap-12 text-center py-4">
                    <div class="flex flex-col items-center">
                        <RadialProgress 
                            :percentage="(quiz.current_result.score / quiz.total_score) * 100" 
                            :color="quiz.current_result.status === 3 ? (parseFloat(quiz.current_result.percentage) < quiz.passing_score + 10 ? 'text-orange-500' : 'text-green-600') : 'text-red-500'" 
                            :trackColor="'text-gray-100 dark:text-gray-700'"
                            :size="100" 
                            :strokeWidth="8"
                        >
                            <div class="flex flex-col items-center mt-1">
                                <span class="text-2xl font-bold" :class="quiz.current_result.status === 3 ? (parseFloat(quiz.current_result.percentage) < quiz.passing_score + 10 ? 'text-orange-600' : 'text-green-600') : 'text-red-600'">
                                    {{ quiz.current_result.score }}
                                </span>
                                <span class="text-xs text-gray-400 font-medium">/ {{ quiz.total_score }}</span>
                            </div>
                        </RadialProgress>
                        <div class="text-sm font-bold mt-3 text-gray-500">คะแนน</div>
                    </div>

                    <div class="flex flex-col items-center">
                         <RadialProgress 
                            :percentage="parseFloat(quiz.current_result.percentage)" 
                            :color="quiz.current_result.status === 3 ? (parseFloat(quiz.current_result.percentage) < quiz.passing_score + 10 ? 'text-orange-500' : 'text-green-600') : 'text-red-500'" 
                            :trackColor="'text-gray-100 dark:text-gray-700'"
                            :size="100" 
                            :strokeWidth="8"
                        >
                            <span class="text-xl font-bold" :class="quiz.current_result.status === 3 ? (parseFloat(quiz.current_result.percentage) < quiz.passing_score + 10 ? 'text-orange-600' : 'text-green-600') : 'text-red-600'">
                                {{ parseFloat(quiz.current_result.percentage).toFixed(0) }}%
                            </span>
                        </RadialProgress>
                        <div class="text-sm font-bold mt-3 text-gray-500">เปอร์เซ็นต์</div>
                    </div>
                </div>
                 <div class="mt-4 text-center">
                    <span class="px-3 py-1 rounded-full text-sm font-bold" 
                        :class="quiz.current_result.status === 3 ? (parseFloat(quiz.current_result.percentage) < quiz.passing_score + 10 ? 'bg-orange-100 text-orange-700' : 'bg-green-100 text-green-700') : 'bg-red-100 text-red-700'">
                         {{ quiz.current_result.status === 3 ? (parseFloat(quiz.current_result.percentage) < quiz.passing_score + 10 ? 'ผ่านเฉียดฉิว' : 'ผ่านฉลุย') : 'ไม่ผ่าน' }}
                    </span>
                 </div>
             </div>

            <button 
              @click="startQuiz"
              class="inline-flex items-center gap-3 px-8 py-4 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl font-bold text-lg hover:shadow-lg hover:scale-105 transition-all duration-300 shadow-purple-200 dark:shadow-none"
            >
              <Icon icon="fluent:play-circle-24-filled" class="w-8 h-8" />
              {{ quiz.current_result && quiz.current_result.completed_at ? 'ทำแบบทดสอบอีกครั้ง' : 'เริ่มทำแบบทดสอบ' }}
            </button>
            <p v-if="!quiz.current_result || !quiz.current_result.completed_at" class="mt-4 text-sm text-gray-500">
               เมื่อกดปุ่มเริ่มทำ เวลาจะนับถอยหลังทันที
            </p>
          </div>
          <div v-else>
            <!-- Group Filter -->
            <div v-if="groups.length > 0" class="mb-4">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                <Icon icon="fluent:people-team-24-regular" class="w-5 h-5 inline-block mr-1" />
                กรองตามกลุ่ม
              </label>
              <div class="flex flex-wrap gap-2">
                <button
                  @click="selectedGroupId = null"
                  :class="[
                    'px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 border',
                    !selectedGroupId
                      ? 'border-purple-500 bg-purple-50 text-purple-700 dark:bg-purple-900/20 dark:text-purple-400 dark:border-purple-500 shadow-sm'
                      : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-purple-300 dark:hover:border-purple-700'
                  ]"
                >
                  ทั้งหมด
                  <span class="ml-1 text-xs opacity-70">({{ quiz.student_results?.length || 0 }})</span>
                </button>
                <button
                  v-for="group in groups"
                  :key="group.id"
                  @click="selectedGroupId = group.id"
                  :class="[
                    'px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 border',
                    selectedGroupId === group.id
                      ? 'border-purple-500 bg-purple-50 text-purple-700 dark:bg-purple-900/20 dark:text-purple-400 dark:border-purple-500 shadow-sm'
                      : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-purple-300 dark:hover:border-purple-700'
                  ]"
                >
                  {{ group.name || `กลุ่ม ${group.id}` }}
                  <span class="ml-1 text-xs opacity-70">({{ group.member_count || 0 }})</span>
                </button>
              </div>
            </div>

            <div class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-4 sm:p-6">
                <h3 class="text-lg font-semibold mb-4">
                  ผลการสอบของนักเรียน
                  <span v-if="selectedGroupId" class="text-sm font-normal text-gray-500 ml-2">
                    ({{ filteredStudentResults.length }} คน)
                  </span>
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th class="px-6 py-3 rounded-l-lg">นักเรียน</th>
                                <th class="px-6 py-3">วันที่สอบ</th>
                                <th class="px-6 py-3">คะแนน</th>
                                <th class="px-6 py-3">เปอร์เซ็นต์</th>
                                <th class="px-6 py-3 rounded-r-lg">สถานะ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="result in filteredStudentResults" :key="result.id" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-gray-200 overflow-hidden">
                                        <img v-if="result.user?.avatar" :src="result.user.avatar" class="w-full h-full object-cover">
                                        <Icon v-else icon="fluent:person-24-filled" class="w-full h-full p-1 text-gray-400" />
                                    </div>
                                    {{ result.user?.name || 'Unknown' }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ result.completed_at ? formatDate(result.completed_at) : (result.started_at ? 'กำลังทำข้อสอบ' : '-') }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ result.score }} / {{ quiz.total_score }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ parseFloat(result.percentage).toFixed(1) }}%
                                </td>
                                <td class="px-6 py-4">
                                    <span v-if="result.completed_at" 
                                        class="px-2 py-1 rounded text-xs font-bold"
                                        :class="result.status === 3 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                    >
                                        {{ result.status === 3 ? 'ผ่าน' : 'ไม่ผ่าน' }}
                                    </span>
                                    <span v-else class="text-gray-500 italic">...</span>
                                </td>
                            </tr>
                            <tr v-if="filteredStudentResults.length === 0">
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                    {{ selectedGroupId ? 'ไม่มีนักเรียนในกลุ่มนี้ที่ทำแบบทดสอบ' : 'ยังไม่มีใครทำแบบทดสอบนี้นะ' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="mt-4 flex justify-end gap-2">
                 <button 
                  @click="recalculateScores"
                  class="px-4 py-2 rounded-lg bg-orange-50 text-orange-600 hover:bg-orange-100 dark:bg-orange-900/20 dark:text-orange-400 dark:hover:bg-orange-900/30 transition-colors flex items-center gap-2"
                >
                  <Icon icon="fluent:arrow-sync-24-filled" class="w-5 h-5" />
                  คำนวณคะแนนใหม่
                </button>
                 <button 
                  @click="editQuiz"
                  class="px-4 py-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400 dark:hover:bg-blue-900/30 transition-colors flex items-center gap-2"
                >
                  <Icon icon="fluent:edit-24-regular" class="w-5 h-5" />
                  แก้ไขแบบทดสอบ
                </button>
            </div>
          </div>
        </div>
      </div>

    </div>
    
    <!-- Not Found -->
    <div v-else class="text-center py-12">
      <Icon icon="fluent:error-circle-24-regular" class="w-16 h-16 text-gray-300 mx-auto mb-4" />
      <h3 class="text-xl font-semibold text-gray-900 dark:text-white">ไม่พบข้อมูลแบบทดสอบ</h3>
      <button 
        @click="navigateTo(`/courses/${courseId}/quizzes`)"
        class="mt-4 text-purple-600 hover:underline"
      >
        กลับไปหน้ารวม
      </button>
    </div>

    <!-- Duplicate Modal -->
    <QuizDuplicateModal
      :show="showDuplicateModal"
      :quiz="quiz"
      :current-course-id="courseId"
      @close="showDuplicateModal = false"
      @duplicated="handleDuplicated"
    />
  </div>
</template>
