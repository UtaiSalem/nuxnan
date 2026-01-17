<script setup lang="ts">
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

interface Props {
  show: boolean
  quiz: any
  currentCourseId: string | number
}

const props = defineProps<Props>()

const emit = defineEmits<{
  close: []
  duplicated: [newQuiz: any]
}>()

const api = useApi()

// Form state
const form = reactive({
  title: '',
  copyMode: 'select_course' as 'same_course' | 'select_course',
  targetCourseId: null as number | null
})

const isLoading = ref(false)
const isSearching = ref(false)
const searchQuery = ref('')
const searchResults = ref<any[]>([])
const selectedCourse = ref<any>(null)

// Watch for modal show
watch(() => props.show, (show) => {
  if (show && props.quiz) {
    form.title = `${props.quiz.title} (สำเนา)`
    form.copyMode = 'select_course'
    form.targetCourseId = null
    selectedCourse.value = null
    searchQuery.value = ''
    searchResults.value = []
  }
})

// Search courses
const searchCourses = async () => {
  if (searchQuery.value.length < 2) {
    searchResults.value = []
    return
  }

  isSearching.value = true
  try {
    const res = await api.get(`/api/courses/search?q=${encodeURIComponent(searchQuery.value)}`)
    // Filter out current course
    searchResults.value = (res.courses || res.data || []).filter(
      (c: any) => c.id !== Number(props.currentCourseId)
    )
  } catch (err) {
    console.error('Error searching courses:', err)
    searchResults.value = []
  } finally {
    isSearching.value = false
  }
}

// Debounced search
let searchTimeout: NodeJS.Timeout
watch(searchQuery, (val) => {
  clearTimeout(searchTimeout)
  if (val.length >= 2) {
    searchTimeout = setTimeout(searchCourses, 300)
  } else {
    searchResults.value = []
  }
})

// Select course
const selectCourse = (course: any) => {
  selectedCourse.value = course
  form.targetCourseId = course.id
  searchQuery.value = ''
  searchResults.value = []
}

// Clear selected course
const clearSelectedCourse = () => {
  selectedCourse.value = null
  form.targetCourseId = null
}

// Handle duplicate
const handleDuplicate = async () => {
  if (!form.title.trim()) {
    Swal.fire({
      icon: 'warning',
      title: 'กรุณาระบุชื่อ',
      text: 'กรุณาระบุชื่อแบบทดสอบใหม่'
    })
    return
  }

  // Determine target course
  let targetCourseId: number
  if (form.copyMode === 'same_course') {
    targetCourseId = Number(props.currentCourseId)
  } else {
    if (!form.targetCourseId) {
      Swal.fire({
        icon: 'warning',
        title: 'กรุณาเลือกคอร์ส',
        text: 'กรุณาเลือกคอร์สปลายทาง'
      })
      return
    }
    targetCourseId = form.targetCourseId
  }

  isLoading.value = true
  try {
    const res = await api.post(`/api/quizzes/${props.quiz.id}/duplicate`, {
      course_id: targetCourseId,
      title: form.title.trim()
    })

    if (res.success || res.quiz) {
      await Swal.fire({
        icon: 'success',
        title: 'คัดลอกสำเร็จ!',
        text: `แบบทดสอบ "${form.title}" ถูกสร้างเรียบร้อยแล้ว`,
        showConfirmButton: true,
        confirmButtonText: 'ตกลง'
      })
      
      emit('duplicated', res.quiz)
      emit('close')
    }
  } catch (err: any) {
    console.error('Error duplicating quiz:', err)
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: err?.data?.message || 'ไม่สามารถคัดลอกแบบทดสอบได้'
    })
  } finally {
    isLoading.value = false
  }
}

// Close modal
const closeModal = () => {
  if (!isLoading.value) {
    emit('close')
  }
}
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
        @click.self="closeModal"
      >
        <Transition
          enter-active-class="transition ease-out duration-200"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition ease-in duration-150"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div
            v-if="show"
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden"
          >
            <!-- Header -->
            <div class="bg-gradient-to-r from-purple-600 to-indigo-600 p-6">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                    <Icon icon="fluent:copy-24-filled" class="w-6 h-6 text-white" />
                  </div>
                  <div>
                    <h3 class="text-xl font-bold text-white">คัดลอกแบบทดสอบ</h3>
                    <p class="text-purple-100 text-sm">{{ quiz?.title }}</p>
                  </div>
                </div>
                <button
                  @click="closeModal"
                  :disabled="isLoading"
                  class="p-2 text-white/80 hover:text-white hover:bg-white/20 rounded-lg transition-colors"
                >
                  <Icon icon="fluent:dismiss-24-regular" class="w-6 h-6" />
                </button>
              </div>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-6">
              <!-- Title Input -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  ชื่อแบบทดสอบใหม่ <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.title"
                  type="text"
                  class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-shadow"
                  placeholder="ระบุชื่อแบบทดสอบ..."
                />
              </div>

              <!-- Copy Mode Selection -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                  คัดลอกไปยัง
                </label>
                <div class="grid grid-cols-2 gap-3">
                  <button
                    type="button"
                    @click="form.copyMode = 'select_course'"
                    :class="[
                      'p-4 rounded-xl border-2 flex flex-col items-center gap-2 transition-all',
                      form.copyMode === 'select_course'
                        ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300'
                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 text-gray-600 dark:text-gray-400'
                    ]"
                  >
                    <Icon icon="fluent:arrow-export-24-regular" class="w-6 h-6" />
                    <span class="text-sm font-medium">เลือกคอร์ส</span>
                  </button>
                  <button
                    type="button"
                    @click="form.copyMode = 'same_course'; clearSelectedCourse()"
                    :class="[
                      'p-4 rounded-xl border-2 flex flex-col items-center gap-2 transition-all',
                      form.copyMode === 'same_course'
                        ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300'
                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 text-gray-600 dark:text-gray-400'
                    ]"
                  >
                    <Icon icon="fluent:arrow-sync-24-regular" class="w-6 h-6" />
                    <span class="text-sm font-medium">คอร์สเดิม</span>
                  </button>
                </div>
              </div>

              <!-- Course Search (when select_course mode) -->
              <div v-if="form.copyMode === 'select_course'">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  เลือกคอร์สปลายทาง <span class="text-red-500">*</span>
                </label>

                <!-- Selected Course Display -->
                <div
                  v-if="selectedCourse"
                  class="flex items-center gap-3 p-4 rounded-xl bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 mb-3"
                >
                  <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-800 flex items-center justify-center">
                    <Icon icon="fluent:book-24-filled" class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 dark:text-white truncate">
                      {{ selectedCourse.name || selectedCourse.title }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                      {{ selectedCourse.code || '' }}
                    </p>
                  </div>
                  <button
                    type="button"
                    @click="clearSelectedCourse"
                    class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                  >
                    <Icon icon="fluent:dismiss-16-regular" class="w-4 h-4" />
                  </button>
                </div>

                <!-- Search Input -->
                <div v-else class="relative">
                  <Icon
                    icon="fluent:search-24-regular"
                    class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"
                  />
                  <input
                    v-model="searchQuery"
                    type="text"
                    class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-shadow"
                    placeholder="ค้นหาคอร์สด้วยชื่อหรือรหัส..."
                  />
                  <Icon
                    v-if="isSearching"
                    icon="svg-spinners:ring-resize"
                    class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-purple-500"
                  />
                </div>

                <!-- Search Results -->
                <div
                  v-if="searchResults.length > 0 && !selectedCourse"
                  class="mt-2 max-h-48 overflow-y-auto rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg"
                >
                  <button
                    v-for="course in searchResults"
                    :key="course.id"
                    type="button"
                    @click="selectCourse(course)"
                    class="w-full flex items-center gap-3 p-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-left border-b border-gray-100 dark:border-gray-700 last:border-b-0"
                  >
                    <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center overflow-hidden">
                      <img
                        v-if="course.cover_image || course.image"
                        :src="course.cover_image || course.image"
                        :alt="course.name || course.title"
                        class="w-full h-full object-cover"
                      />
                      <Icon v-else icon="fluent:book-24-regular" class="w-5 h-5 text-gray-400" />
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="font-medium text-gray-900 dark:text-white truncate">
                        {{ course.name || course.title }}
                      </p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ course.code || '' }}
                      </p>
                    </div>
                  </button>
                </div>

                <!-- No Results -->
                <div
                  v-else-if="searchQuery.length >= 2 && !isSearching && searchResults.length === 0 && !selectedCourse"
                  class="mt-2 p-4 text-center text-gray-500 dark:text-gray-400 rounded-xl border border-gray-200 dark:border-gray-700"
                >
                  <Icon icon="fluent:search-24-regular" class="w-8 h-8 mx-auto mb-2 opacity-50" />
                  <p class="text-sm">ไม่พบคอร์สที่ค้นหา</p>
                </div>

                <!-- Hint -->
                <p
                  v-if="!selectedCourse && searchQuery.length < 2"
                  class="mt-2 text-xs text-gray-500 dark:text-gray-400"
                >
                  พิมพ์อย่างน้อย 2 ตัวอักษรเพื่อค้นหา
                </p>
              </div>

              <!-- Info Box -->
              <div class="p-4 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800">
                <div class="flex items-start gap-3">
                  <Icon icon="fluent:info-24-regular" class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" />
                  <div class="text-sm text-blue-700 dark:text-blue-300">
                    <p class="font-medium mb-1">สิ่งที่จะถูกคัดลอก:</p>
                    <ul class="list-disc list-inside space-y-0.5 text-xs opacity-90">
                      <li>ข้อมูลแบบทดสอบทั้งหมด (ชื่อ, คำอธิบาย, การตั้งค่า)</li>
                      <li>คำถามและตัวเลือกทั้งหมด</li>
                      <li>รูปภาพประกอบทั้งหมด</li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="p-6 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
              <button
                type="button"
                @click="closeModal"
                :disabled="isLoading"
                class="px-6 py-2.5 rounded-xl font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors disabled:opacity-50"
              >
                ยกเลิก
              </button>
              <button
                type="button"
                @click="handleDuplicate"
                :disabled="isLoading || !form.title.trim() || (form.copyMode === 'select_course' && !form.targetCourseId)"
                class="px-6 py-2.5 rounded-xl font-bold text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center gap-2"
              >
                <Icon
                  :icon="isLoading ? 'svg-spinners:ring-resize' : 'fluent:copy-24-filled'"
                  class="w-5 h-5"
                />
                {{ isLoading ? 'กำลังคัดลอก...' : 'คัดลอกแบบทดสอบ' }}
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>
