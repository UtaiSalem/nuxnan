<script setup lang="ts">
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import RadialProgress from '~/components/Common/RadialProgress.vue';

const route = useRoute()
const courseId = route.params.id
const quizId = route.params.quizId
const api = useApi()

const isCourseAdmin = inject<Ref<boolean>>('isCourseAdmin')

// Fetch quiz details
const { data: quiz, refresh, pending } = await useAsyncData(
  `course-quiz-${quizId}`,
  async () => {
    const res = await api.get(`/api/courses/${courseId}/quizzes/${quizId}`)
    return res.quiz
  }
)

const startQuiz = () => {
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
    <div v-if="pending" class="flex justify-center p-12">
      <Icon icon="svg-spinners:3-dots-fade" class="w-10 h-10 text-gray-400" />
    </div>

    <div v-else-if="quiz">
      <!-- Header / Nav -->
      <div class="flex items-center justify-between mb-6">
        <button 
          @click="navigateTo(`/courses/${courseId}/quizzes`)"
          class="flex items-center gap-2 text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors"
        >
          <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
          <span>กลับไปหน้ารวมแบบทดสอบ</span>
        </button>

        <div v-if="isCourseAdmin" class="flex items-center gap-2">
          <button 
            @click="editQuiz"
            class="px-4 py-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400 dark:hover:bg-blue-900/30 transition-colors flex items-center gap-2"
          >
            <Icon icon="fluent:edit-24-regular" class="w-5 h-5" />
            แก้ไข
          </button>
          <button 
            @click="deleteQuiz"
            class="px-4 py-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/30 transition-colors flex items-center gap-2"
          >
            <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
            ลบ
          </button>
        </div>
      </div>

      <!-- Main Content -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        
        <!-- Cover / Banner Area -->
        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 p-8 text-white">
          <div class="flex items-start gap-4">
            <div class="w-16 h-16 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center border border-white/30">
              <Icon icon="fluent:quiz-new-24-filled" class="w-8 h-8 text-white" />
            </div>
            <div>
              <div class="flex items-center gap-2 mb-2">
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
          <div class="p-6 text-center">
            <div class="text-sm text-gray-500 mb-1">จำนวนข้อ</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ quiz.questions?.length || 0 }}
            </div>
          </div>
          <div class="p-6 text-center">
            <div class="text-sm text-gray-500 mb-1">คะแนนเต็ม</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ quiz.total_score || 0 }}
            </div>
          </div>
          <div class="p-6 text-center">
            <div class="text-sm text-gray-500 mb-1">เกณฑ์ผ่าน</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ quiz.passing_score }}%
            </div>
          </div>
        </div>

        <!-- Dates -->
        <div class="p-6 bg-gray-50 dark:bg-gray-700/30 border-b border-gray-200 dark:border-gray-700">
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
             <div v-if="quiz.current_result && quiz.current_result.completed_at" class="bg-gray-100 dark:bg-gray-700/50 rounded-xl p-6 mb-6">
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
            <div class="bg-gray-50 dark:bg-gray-700/30 rounded-xl p-6">
                <h3 class="text-lg font-semibold mb-4">ผลการสอบของนักเรียน</h3>
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
                            <tr v-for="result in quiz.student_results" :key="result.id" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
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
                            <tr v-if="!quiz.student_results || quiz.student_results.length === 0">
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                    ยังไม่มีใครทำแบบทดสอบนี้นะ
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="mt-4 flex justify-end gap-2">
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

  </div>
</template>
