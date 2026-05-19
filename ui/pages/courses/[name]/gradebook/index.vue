<script setup lang="ts">
/**
 * Course Gradebook - Score Entry
 * หน้าสมุดคะแนนสำหรับครู
 */
import { Icon } from '@iconify/vue'
import GradebookSubNav from '~/components/learn/course/gradebook/GradebookSubNav.vue'

definePageMeta({
  layout: false,
  pageTransition: false
})

const route = useRoute()
const api = useApi()
const courseName = computed(() => route.params.name as string)
const courseId = ref<number | null>(null)

// State
const course = ref<any>(null)
const gradebook = ref<any>(null)
const assessments = ref<any[]>([])
const students = ref<any[]>([])
const categories = ref<any[]>([])
const isLoading = ref(true)
const isSaving = ref(false)

// Selected assessment
const selectedAssessment = ref<any>(null)
const showScoreModal = ref(false)
const scoreForm = ref<any[]>([])

// Create assessment modal
const showCreateModal = ref(false)
const assessmentForm = ref({
  name: '',
  description: '',
  category_id: null as number | null,
  max_score: 100,
  weight: 10,
  due_date: '',
  is_included_in_grade: true,
})

onMounted(async () => {
  await fetchCourse()
})

const fetchCourse = async () => {
  try {
    const res: any = await api.get(`/api/courses/${courseName.value}`)
    if (res.success) {
      course.value = res.course
      courseId.value = res.course.id
      await fetchData()
    }
  } catch (err) {
    console.error('Failed to fetch course:', err)
  } finally {
    isLoading.value = false
  }
}

const fetchData = async () => {
  await Promise.all([
    fetchGradebook(),
    fetchCategories(),
  ])
}

const fetchGradebook = async () => {
  try {
    const res: any = await api.get(`/api/courses/${courseId.value}/gradebook`)
    if (res.success) {
      assessments.value = res.assessments || []
      students.value = res.students || []
      gradebook.value = res.gradebook || []
    }
  } catch (err) {
    console.error('Failed to fetch gradebook:', err)
  }
}

const fetchCategories = async () => {
  try {
    const academyId = course.value?.academy_id
    if (!academyId) return
    
    const res: any = await api.get(`/api/academies/${academyId}/assessment-categories`)
    if (res.success) {
      categories.value = res.categories || []
    }
  } catch (err) {
    console.error('Failed to fetch categories:', err)
  }
}

const openScoreModal = (assessment: any) => {
  selectedAssessment.value = assessment
  
  // Prepare score form
  scoreForm.value = students.value.map((student: any) => {
    const existingScore = assessment.scores?.find((s: any) => s.student_id === student.id)
    return {
      student_id: student.id,
      student_name: `${student.first_name_th} ${student.last_name_th}`,
      student_number: student.student_id,
      score: existingScore?.score ?? null,
      status: existingScore?.status || 'pending',
      feedback: existingScore?.feedback || '',
    }
  })
  
  showScoreModal.value = true
}

const saveScores = async () => {
  if (!selectedAssessment.value) return
  
  isSaving.value = true
  try {
    const scores = scoreForm.value.map((s: any) => ({
      student_id: s.student_id,
      score: s.score !== '' ? Number(s.score) : null,
      status: s.status,
      feedback: s.feedback,
    }))
    
    await api.put(`/api/courses/${courseId.value}/gradebook/assessments/${selectedAssessment.value.id}/scores`, {
      scores,
    })
    
    showScoreModal.value = false
    await fetchGradebook()
  } catch (err: any) {
    console.error('Failed to save scores:', err)
    alert(err.message || 'เกิดข้อผิดพลาด')
  } finally {
    isSaving.value = false
  }
}

const createAssessment = async () => {
  if (!assessmentForm.value.name || !assessmentForm.value.max_score) {
    alert('กรุณากรอกข้อมูลให้ครบถ้วน')
    return
  }
  
  isSaving.value = true
  try {
    await api.post(`/api/courses/${courseId.value}/gradebook/assessments`, assessmentForm.value)
    
    showCreateModal.value = false
    resetAssessmentForm()
    await fetchGradebook()
  } catch (err: any) {
    console.error('Failed to create assessment:', err)
    alert(err.message || 'เกิดข้อผิดพลาด')
  } finally {
    isSaving.value = false
  }
}

const deleteAssessment = async (assessment: any) => {
  if (!confirm(`ต้องการลบ "${assessment.name}" หรือไม่?`)) return
  
  try {
    await api.delete(`/api/courses/${courseId.value}/gradebook/assessments/${assessment.id}`)
    await fetchGradebook()
  } catch (err) {
    console.error('Failed to delete:', err)
    alert('ไม่สามารถลบได้')
  }
}

const resetAssessmentForm = () => {
  assessmentForm.value = {
    name: '',
    description: '',
    category_id: null,
    max_score: 100,
    weight: 10,
    due_date: '',
    is_included_in_grade: true,
  }
}

// Calculate stats
const getAssessmentStats = (assessment: any) => {
  const scores = assessment.scores?.filter((s: any) => s.status === 'graded') || []
  if (scores.length === 0) return { avg: '-', max: '-', min: '-', graded: 0, total: students.value.length }
  
  const values = scores.map((s: any) => s.score)
  return {
    avg: (values.reduce((a: number, b: number) => a + b, 0) / values.length).toFixed(1),
    max: Math.max(...values),
    min: Math.min(...values),
    graded: scores.length,
    total: students.value.length,
  }
}

const getStudentScore = (studentId: number, assessmentId: number) => {
  const row = gradebook.value.find((g: any) => g.student_id === studentId)
  if (!row) return null
  return row.scores[assessmentId]
}

const getGradeColor = (score: any, maxScore: number) => {
  if (!score || score.status !== 'graded') return ''
  const pct = (score.score / maxScore) * 100
  if (pct >= 80) return 'text-green-600 dark:text-green-400'
  if (pct >= 70) return 'text-blue-600 dark:text-blue-400'
  if (pct >= 60) return 'text-amber-600 dark:text-amber-400'
  if (pct >= 50) return 'text-orange-600 dark:text-orange-400'
  return 'text-red-600 dark:text-red-400'
}
</script>

<template>
  <NuxtLayout name="course">
    <div>
    <GradebookSubNav :course-name="courseName" />

    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
            <Icon icon="fluent:text-grammar-checkmark-24-filled" class="w-7 h-7 text-primary-500" />
            สมุดคะแนน
          </h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">{{ course?.name }}</p>
        </div>

        <button
          @click="showCreateModal = true"
          class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-medium transition-colors flex items-center gap-2"
        >
          <Icon icon="fluent:add-24-filled" class="w-5 h-5" />
          เพิ่มการประเมิน
        </button>
      </div>

      <!-- Stats Summary -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
              <Icon icon="fluent:clipboard-task-list-24-filled" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ assessments.length }}</div>
              <div class="text-sm text-gray-600 dark:text-gray-400">การประเมิน</div>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/50 flex items-center justify-center">
              <Icon icon="fluent:people-24-filled" class="w-5 h-5 text-green-600 dark:text-green-400" />
            </div>
            <div>
              <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ students.length }}</div>
              <div class="text-sm text-gray-600 dark:text-gray-400">นักเรียน</div>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center">
              <Icon icon="fluent:data-bar-vertical-24-filled" class="w-5 h-5 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
              <div class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ assessments.reduce((sum: number, a: any) => sum + (a.weight || 0), 0) }}%
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400">น้ำหนักรวม</div>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
              <Icon icon="fluent:checkmark-circle-24-filled" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
            </div>
            <div>
              <div class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ assessments.filter((a: any) => a.is_published).length }}
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400">เผยแพร่แล้ว</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Assessments List -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">รายการประเมิน</h2>
        </div>

        <div v-if="assessments.length === 0" class="p-12 text-center">
          <Icon icon="fluent:clipboard-task-list-24-regular" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">ยังไม่มีการประเมิน</h3>
          <p class="text-gray-600 dark:text-gray-400 mb-6">สร้างการประเมินเพื่อบันทึกคะแนน</p>
          <button
            @click="showCreateModal = true"
            class="inline-flex items-center gap-2 px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-medium transition-colors"
          >
            <Icon icon="fluent:add-24-filled" class="w-5 h-5" />
            สร้างการประเมิน
          </button>
        </div>

        <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
          <div
            v-for="assessment in assessments"
            :key="assessment.id"
            class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50"
          >
            <div class="flex flex-col md:flex-row md:items-center gap-4">
              <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                  <div :class="['w-10 h-10 rounded-lg flex items-center justify-center', assessment.category?.color ? `bg-${assessment.category.color}-100 dark:bg-${assessment.category.color}-900/50` : 'bg-gray-100 dark:bg-gray-700']">
                    <Icon :icon="assessment.category?.icon || 'fluent:clipboard-24-filled'" class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                  </div>
                  <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ assessment.name }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                      {{ assessment.category?.name || 'ทั่วไป' }} • คะแนนเต็ม {{ assessment.max_score }} • น้ำหนัก {{ assessment.weight }}%
                    </p>
                  </div>
                </div>

                <!-- Stats -->
                <div class="flex items-center gap-4 text-sm">
                  <span class="text-gray-600 dark:text-gray-400">
                    ให้คะแนนแล้ว {{ getAssessmentStats(assessment).graded }}/{{ getAssessmentStats(assessment).total }}
                  </span>
                  <span v-if="getAssessmentStats(assessment).avg !== '-'" class="text-primary-600 dark:text-primary-400">
                    เฉลี่ย {{ getAssessmentStats(assessment).avg }}
                  </span>
                </div>
              </div>

              <div class="flex items-center gap-2">
                <button
                  @click="openScoreModal(assessment)"
                  class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg text-sm font-medium transition-colors flex items-center gap-2"
                >
                  <Icon icon="fluent:edit-24-filled" class="w-4 h-4" />
                  ให้คะแนน
                </button>
                <button
                  @click="deleteAssessment(assessment)"
                  class="p-2 text-gray-500 hover:text-red-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                >
                  <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Gradebook Table -->
      <div v-if="assessments.length > 0 && students.length > 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">ตารางคะแนน</h2>
        </div>
        
        <div class="overflow-x-auto">
          <table class="w-full min-w-max">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider sticky left-0 bg-gray-50 dark:bg-gray-700 min-w-[200px]">
                  นักเรียน
                </th>
                <th
                  v-for="assessment in assessments"
                  :key="assessment.id"
                  class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider min-w-[100px]"
                >
                  <div class="truncate max-w-[100px]" :title="assessment.name">{{ assessment.name }}</div>
                  <div class="text-[10px] normal-case font-normal">({{ assessment.max_score }})</div>
                </th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider min-w-[80px]">
                  รวม
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="row in gradebook" :key="row.student_id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-4 py-3 whitespace-nowrap sticky left-0 bg-white dark:bg-gray-800">
                  <div class="flex items-center gap-2">
                    <span class="text-gray-500 dark:text-gray-400 text-sm">{{ row.student_number }}</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ row.student_name }}</span>
                  </div>
                </td>
                <td
                  v-for="assessment in assessments"
                  :key="assessment.id"
                  class="px-4 py-3 text-center"
                >
                  <span v-if="row.scores[assessment.id]?.status === 'graded'" :class="['font-medium', getGradeColor(row.scores[assessment.id], assessment.max_score)]">
                    {{ row.scores[assessment.id].score }}
                  </span>
                  <span v-else-if="row.scores[assessment.id]?.status === 'excused'" class="text-gray-400">ยกเว้น</span>
                  <span v-else-if="row.scores[assessment.id]?.status === 'missing'" class="text-red-500">ขาด</span>
                  <span v-else class="text-gray-300 dark:text-gray-600">-</span>
                </td>
                <td class="px-4 py-3 text-center font-semibold text-gray-900 dark:text-white">
                  {{ row.percentage }}%
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Create Assessment Modal -->
    <Teleport to="body">
      <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/50" @click="showCreateModal = false"></div>
        
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
          <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">สร้างการประเมินใหม่</h2>
          </div>
          
          <form @submit.prevent="createAssessment" class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อการประเมิน *</label>
              <input
                v-model="assessmentForm.name"
                type="text"
                required
                placeholder="เช่น แบบทดสอบครั้งที่ 1"
                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">หมวดหมู่</label>
              <select
                v-model="assessmentForm.category_id"
                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              >
                <option :value="null">ทั่วไป</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คะแนนเต็ม *</label>
                <input
                  v-model.number="assessmentForm.max_score"
                  type="number"
                  required
                  min="1"
                  class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">น้ำหนัก (%)</label>
                <input
                  v-model.number="assessmentForm.weight"
                  type="number"
                  min="0"
                  max="100"
                  class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">กำหนดส่ง</label>
              <input
                v-model="assessmentForm.due_date"
                type="date"
                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
              <textarea
                v-model="assessmentForm.description"
                rows="2"
                placeholder="รายละเอียดเพิ่มเติม..."
                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              ></textarea>
            </div>
            
            <div class="flex items-center gap-2">
              <input
                id="include-in-grade"
                v-model="assessmentForm.is_included_in_grade"
                type="checkbox"
                class="w-4 h-4 text-primary-500 border-gray-300 rounded focus:ring-primary-500"
              />
              <label for="include-in-grade" class="text-sm text-gray-700 dark:text-gray-300">
                รวมในการคำนวณเกรด
              </label>
            </div>
            
            <div class="flex items-center gap-3 pt-4">
              <button
                type="button"
                @click="showCreateModal = false"
                class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
              >
                ยกเลิก
              </button>
              <button
                type="submit"
                :disabled="isSaving"
                class="flex-1 px-4 py-2 bg-primary-500 hover:bg-primary-600 disabled:bg-gray-400 text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2"
              >
                <Icon v-if="isSaving" icon="fluent:spinner-ios-20-filled" class="w-5 h-5 animate-spin" />
                สร้างการประเมิน
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Score Entry Modal -->
    <Teleport to="body">
      <div v-if="showScoreModal" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/50" @click="showScoreModal = false"></div>
        
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-3xl mx-4 max-h-[90vh] flex flex-col">
          <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ selectedAssessment?.name }}</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
              คะแนนเต็ม {{ selectedAssessment?.max_score }} คะแนน
            </p>
          </div>
          
          <div class="flex-1 overflow-y-auto p-6">
            <table class="w-full">
              <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    นักเรียน
                  </th>
                  <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-32">
                    คะแนน
                  </th>
                  <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-32">
                    สถานะ
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr v-for="student in scoreForm" :key="student.student_id">
                  <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                      <span class="text-gray-500 dark:text-gray-400 text-sm">{{ student.student_number }}</span>
                      <span class="font-medium text-gray-900 dark:text-white">{{ student.student_name }}</span>
                    </div>
                  </td>
                  <td class="px-4 py-3">
                    <input
                      v-model.number="student.score"
                      type="number"
                      :min="0"
                      :max="selectedAssessment?.max_score"
                      :disabled="student.status === 'excused' || student.status === 'missing'"
                      class="w-full px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-center focus:ring-2 focus:ring-primary-500 disabled:bg-gray-100 dark:disabled:bg-gray-700"
                      @input="student.status = 'graded'"
                    />
                  </td>
                  <td class="px-4 py-3">
                    <select
                      v-model="student.status"
                      class="w-full px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500"
                    >
                      <option value="pending">รอให้คะแนน</option>
                      <option value="graded">ให้คะแนนแล้ว</option>
                      <option value="excused">ยกเว้น</option>
                      <option value="missing">ขาด/ไม่ส่ง</option>
                    </select>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <div class="p-6 border-t border-gray-200 dark:border-gray-700 flex items-center gap-3">
            <button
              @click="showScoreModal = false"
              class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              ยกเลิก
            </button>
            <button
              @click="saveScores"
              :disabled="isSaving"
              class="flex-1 px-4 py-2 bg-primary-500 hover:bg-primary-600 disabled:bg-gray-400 text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2"
            >
              <Icon v-if="isSaving" icon="fluent:spinner-ios-20-filled" class="w-5 h-5 animate-spin" />
              บันทึกคะแนน
            </button>
          </div>
        </div>
      </div>
    </Teleport>
    </div>
  </NuxtLayout>
</template>
