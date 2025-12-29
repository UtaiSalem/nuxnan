<script setup lang="ts">
import { Icon } from '@iconify/vue'

// Props from parent route
const props = defineProps<{
  course?: any
  academy?: any
  isCourseAdmin?: boolean
}>()

// Inject from parent if props not passed
const injectedCourse = inject<Ref<any>>('course')
const injectedAcademy = inject<Ref<any>>('academy')
const injectedIsCourseAdmin = inject<Ref<boolean>>('isCourseAdmin')
const refreshCourse = inject<() => void>('refreshCourse')

// Use course store
const courseStore = useCourseStore()

// Initialize store with course data
watch([() => props.course, injectedCourse], ([propsCourse, injected]) => {
  const courseData = propsCourse || injected
  if (courseData) {
    courseStore.setCourse(courseData)
  }
}, { immediate: true })

watch([() => props.academy, injectedAcademy], ([propsAcademy, injected]) => {
  const academyData = propsAcademy || injected
  if (academyData) {
    courseStore.setAcademy(academyData)
  }
}, { immediate: true })

watch([() => props.isCourseAdmin, injectedIsCourseAdmin], ([propsAdmin, injected]) => {
  const isAdmin = propsAdmin || injected || false
  courseStore.setIsCourseAdmin(isAdmin)
}, { immediate: true })

const course = computed(() => props.course || injectedCourse?.value || courseStore.currentCourse)
const academy = computed(() => props.academy || injectedAcademy?.value || courseStore.academy)
const isCourseAdmin = computed(() => props.isCourseAdmin || injectedIsCourseAdmin?.value || courseStore.isCourseAdmin)

const api = useApi()
const isEnrolling = ref(false)
const isWishlisted = ref(false)
const expandedSections = ref<number[]>([0])

// Description editing state
const isEditingDescription = ref(false)
const descriptionContent = ref('')
const isSavingDescription = ref(false)

// Initialize description content
watch(() => course.value?.description, (newVal) => {
  if (newVal && !isEditingDescription.value) {
    descriptionContent.value = newVal
  }
}, { immediate: true })

// Start editing description
const startEditDescription = () => {
  descriptionContent.value = course.value?.description || ''
  isEditingDescription.value = true
}

// Cancel editing
const cancelEditDescription = () => {
  descriptionContent.value = course.value?.description || ''
  isEditingDescription.value = false
}

// Save description
const saveDescription = async () => {
  if (!course.value) return
  
  isSavingDescription.value = true
  try {
    const response = await api.put(`/api/courses/${course.value.id}`, {
      description: descriptionContent.value
    })
    
    if (response.success) {
      isEditingDescription.value = false
      // Update store
      courseStore.updateCourse({ description: descriptionContent.value })
      // Refresh course data
      if (refreshCourse) {
        refreshCourse()
      }
    }
  } catch (err: any) {
    alert(err.data?.msg || 'ไม่สามารถบันทึกได้')
  } finally {
    isSavingDescription.value = false
  }
}

// Curriculum data from course lessons
const curriculum = computed(() => {
  if (!course.value?.lessons?.length) {
    return []
  }
  return course.value.lessons.map((lesson: any, index: number) => ({
    id: lesson.id,
    title: `${index + 1}. ${lesson.name}`,
    videos: lesson.topics_count || 0,
    items: lesson.topics?.map((topic: any) => ({
      id: topic.id,
      title: topic.name,
      duration: topic.duration || '15min',
      type: topic.is_preview ? 'video' : 'locked'
    })) || []
  }))
})

// Toggle section expand
const toggleSection = (index: number) => {
  const idx = expandedSections.value.indexOf(index)
  if (idx > -1) {
    expandedSections.value.splice(idx, 1)
  } else {
    expandedSections.value.push(index)
  }
}

// Enroll in course
const enrollCourse = async () => {
  if (!course.value) return
  
  isEnrolling.value = true
  try {
    const response = await api.post(`/api/courses/${course.value.id}/members`)
    if (response.success) {
      course.value.isMember = true
      course.value.member_status = response.memberStatus
      // Update store
      courseStore.updateCourse({ 
        isMember: true, 
        member_status: response.memberStatus 
      })
    }
  } catch (err: any) {
    alert(err.data?.msg || 'ไม่สามารถสมัครเรียนได้')
  } finally {
    isEnrolling.value = false
  }
}

// Toggle wishlist
const toggleWishlist = () => {
  isWishlisted.value = !isWishlisted.value
}

// Helper functions
const getCoverUrl = (coverPath: string | null) => {
  if (!coverPath) return `${useRuntimeConfig().public.apiBase}/storage/images/courses/covers/default_cover.jpg`
  if (coverPath.startsWith('http')) return coverPath
  return `${useRuntimeConfig().public.apiBase}/storage/images/courses/covers/${coverPath}`
}

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('th-TH', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price || 0)
}
</script>

<template>
  <div v-if="course" class="space-y-6">
    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Left Column - Course Details -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Description Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <Icon icon="fluent:text-description-24-regular" class="w-5 h-5 text-blue-500" />
              รายละเอียดรายวิชา
            </h3>
            <!-- Edit button for admin -->
            <button
              v-if="isCourseAdmin && !isEditingDescription"
              @click="startEditDescription"
              class="flex items-center gap-1 px-3 py-1.5 text-sm text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
            >
              <Icon icon="fluent:edit-24-regular" class="w-4 h-4" />
              แก้ไข
            </button>
          </div>
          
          <!-- View Mode -->
          <div v-if="!isEditingDescription" class="text-gray-600 dark:text-gray-400 leading-relaxed prose dark:prose-invert max-w-none">
            <div v-if="course.description" v-html="course.description"></div>
            <p v-else class="text-gray-400 italic">ไม่มีรายละเอียด</p>
          </div>
          
          <!-- Edit Mode -->
          <div v-else class="space-y-4">
            <CommonRichTextEditor
              v-model="descriptionContent"
              placeholder="เขียนรายละเอียดรายวิชาที่นี่..."
              min-height="250px"
            />
            <div class="flex items-center justify-end gap-3">
              <button
                @click="cancelEditDescription"
                :disabled="isSavingDescription"
                class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors disabled:opacity-50"
              >
                ยกเลิก
              </button>
              <button
                @click="saveDescription"
                :disabled="isSavingDescription"
                class="flex items-center gap-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors disabled:opacity-50"
              >
                <Icon v-if="isSavingDescription" icon="svg-spinners:ring-resize" class="w-4 h-4" />
                <Icon v-else icon="fluent:save-24-regular" class="w-4 h-4" />
                บันทึก
              </button>
            </div>
          </div>
        </div>

        <!-- Instructor Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <Icon icon="fluent:person-24-regular" class="w-5 h-5 text-blue-500" />
            ผู้สอน
          </h3>
          <div class="flex items-center gap-4">
            <img 
              :src="course.user?.avatar || '/images/default-avatar.png'" 
              :alt="course.user?.name"
              class="w-16 h-16 rounded-full object-cover border-2 border-gray-200 dark:border-gray-700"
            />
            <div class="flex-1">
              <p class="font-semibold text-gray-900 dark:text-white text-lg">
                {{ course.user?.name || 'ผู้สอน' }}
              </p>
              <p class="text-gray-500 dark:text-gray-400 text-sm">
                {{ course.user?.bio || 'ไม่มีข้อมูล' }}
              </p>
            </div>
            <button class="px-4 py-2 border border-blue-500 text-blue-500 rounded-lg font-medium hover:bg-blue-500 hover:text-white transition-colors">
              <Icon icon="fluent:person-add-24-regular" class="w-5 h-5" />
            </button>
          </div>
        </div>

        <!-- Curriculum Card -->
        <div v-if="curriculum.length > 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
          <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <Icon icon="fluent:book-24-regular" class="w-5 h-5 text-blue-500" />
            เนื้อหาบทเรียน
          </h3>
          
          <!-- Curriculum Accordion -->
          <div class="space-y-2">
            <div 
              v-for="(section, index) in curriculum" 
              :key="section.id" 
              class="border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden"
            >
              <!-- Section Header -->
              <button
                @click="toggleSection(index)"
                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700/50 flex items-center justify-between hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
              >
                <span class="font-medium text-gray-900 dark:text-white">
                  {{ section.title }}
                </span>
                <div class="flex items-center gap-3 text-gray-500 text-sm">
                  <span>{{ section.videos }} หัวข้อ</span>
                  <Icon 
                    :icon="expandedSections.includes(index) ? 'fluent:chevron-up-24-regular' : 'fluent:chevron-down-24-regular'" 
                    class="w-5 h-5" 
                  />
                </div>
              </button>

              <!-- Section Content -->
              <div v-if="expandedSections.includes(index) && section.items.length > 0" class="divide-y divide-gray-200 dark:divide-gray-600">
                <div 
                  v-for="item in section.items" 
                  :key="item.id"
                  class="px-4 py-3 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                >
                  <div class="flex items-center gap-3">
                    <Icon 
                      :icon="item.type === 'locked' ? 'fluent:lock-closed-24-regular' : 'fluent:play-circle-24-regular'" 
                      :class="[
                        'w-5 h-5',
                        item.type === 'locked' ? 'text-gray-400' : 'text-blue-500'
                      ]"
                    />
                    <span class="text-gray-700 dark:text-gray-300 text-sm">
                      {{ item.title }}
                    </span>
                  </div>
                  <span class="text-gray-500 text-sm">{{ item.duration }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column - Course Info Card -->
      <div class="space-y-6">
        <!-- Quick Info Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 sticky top-24">
          <!-- Video Preview -->
          <div class="relative aspect-video bg-gray-200 dark:bg-gray-700 rounded-lg overflow-hidden mb-4">
            <img
              :src="getCoverUrl(course.cover)"
              :alt="course.name"
              class="w-full h-full object-cover"
            />
            <div class="absolute inset-0 flex items-center justify-center bg-black/30">
              <button class="w-14 h-14 bg-blue-500 rounded-full flex items-center justify-center hover:bg-blue-600 transition-colors shadow-lg">
                <Icon icon="fluent:play-24-filled" class="w-7 h-7 text-white ml-1" />
              </button>
            </div>
          </div>

          <!-- Price -->
          <div v-if="course.price" class="text-center mb-4">
            <span class="text-3xl font-bold text-gray-900 dark:text-white">
              ฿{{ formatPrice(course.price) }}
            </span>
          </div>

          <!-- Action Buttons -->
          <div class="space-y-3 mb-6">
            <button
              v-if="!course.isMember"
              @click="enrollCourse"
              :disabled="isEnrolling"
              class="w-full py-3 bg-green-500 text-white rounded-lg font-bold hover:bg-green-600 transition-colors flex items-center justify-center gap-2 disabled:opacity-50"
            >
              <Icon icon="fluent:add-24-regular" class="w-5 h-5" />
              {{ isEnrolling ? 'กำลังสมัคร...' : 'สมัครเรียน' }}
            </button>
            <NuxtLink
              v-else
              :to="`/courses/${course.id}/lessons`"
              class="w-full py-3 bg-green-500 text-white rounded-lg font-bold hover:bg-green-600 transition-colors flex items-center justify-center gap-2"
            >
              <Icon icon="fluent:play-24-filled" class="w-5 h-5" />
              เข้าเรียน
            </NuxtLink>
            <button 
              @click="toggleWishlist"
              :class="[
                'w-full py-3 rounded-lg font-medium flex items-center justify-center gap-2 transition-colors',
                isWishlisted 
                  ? 'bg-red-500 text-white' 
                  : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
              ]"
            >
              <Icon :icon="isWishlisted ? 'fluent:heart-24-filled' : 'fluent:heart-24-regular'" class="w-5 h-5" />
              {{ isWishlisted ? 'เพิ่มในรายการโปรดแล้ว' : 'เพิ่มในรายการโปรด' }}
            </button>
          </div>

          <!-- Course Stats -->
          <div class="space-y-3 text-sm">
            <div class="flex items-center justify-between text-gray-600 dark:text-gray-400">
              <div class="flex items-center gap-2">
                <Icon icon="fluent:people-24-regular" class="w-5 h-5" />
                <span>ผู้เรียน</span>
              </div>
              <span class="font-medium text-gray-900 dark:text-white">{{ course.enrolled_students || 0 }} คน</span>
            </div>
            <div class="flex items-center justify-between text-gray-600 dark:text-gray-400">
              <div class="flex items-center gap-2">
                <Icon icon="fluent:book-24-regular" class="w-5 h-5" />
                <span>บทเรียน</span>
              </div>
              <span class="font-medium text-gray-900 dark:text-white">{{ course.lessons_count || curriculum.length }} บท</span>
            </div>
            <div v-if="course.duration" class="flex items-center justify-between text-gray-600 dark:text-gray-400">
              <div class="flex items-center gap-2">
                <Icon icon="fluent:clock-24-regular" class="w-5 h-5" />
                <span>ระยะเวลา</span>
              </div>
              <span class="font-medium text-gray-900 dark:text-white">{{ course.duration }}</span>
            </div>
            <div class="flex items-center justify-between text-gray-600 dark:text-gray-400">
              <div class="flex items-center gap-2">
                <Icon icon="fluent:calendar-24-regular" class="w-5 h-5" />
                <span>เริ่มเรียน</span>
              </div>
              <span class="font-medium text-gray-900 dark:text-white">{{ formatDate(course.start_date) }}</span>
            </div>
            <div class="flex items-center justify-between text-gray-600 dark:text-gray-400">
              <div class="flex items-center gap-2">
                <Icon icon="fluent:calendar-checkmark-24-regular" class="w-5 h-5" />
                <span>สิ้นสุด</span>
              </div>
              <span class="font-medium text-gray-900 dark:text-white">{{ formatDate(course.end_date) }}</span>
            </div>
          </div>

          <!-- Features -->
          <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
            <h4 class="font-medium text-gray-900 dark:text-white mb-3">สิ่งที่จะได้รับ</h4>
            <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
              <li class="flex items-center gap-2">
                <Icon icon="fluent:checkmark-circle-24-filled" class="w-5 h-5 text-green-500" />
                <span>เข้าถึงเนื้อหาได้ตลอดชีพ</span>
              </li>
              <li class="flex items-center gap-2">
                <Icon icon="fluent:checkmark-circle-24-filled" class="w-5 h-5 text-green-500" />
                <span>ใบประกาศนียบัตร</span>
              </li>
              <li class="flex items-center gap-2">
                <Icon icon="fluent:checkmark-circle-24-filled" class="w-5 h-5 text-green-500" />
                <span>รองรับทุกอุปกรณ์</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
