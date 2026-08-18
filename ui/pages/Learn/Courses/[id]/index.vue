<script setup lang="ts">
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import CourseSupportPanel from '~/components/learn/course/points/CourseSupportPanel.vue'

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
const injectedCourseMemberOfAuth = inject<Ref<any>>('courseMemberOfAuth')
const refreshCourse = inject<(force?: boolean) => void>('refreshCourse')

// Unsaved changes guard
onBeforeRouteLeave((to, from, next) => {
  if (isEditingDescription.value) {
    Swal.fire({
      title: 'มีการแก้ไขที่ยังไม่บันทึก',
      text: 'คุณต้องการออกจากหน้านี้หรือไม่?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'ใช่, ออกโดยไม่บันทึก',
      cancelButtonText: 'ยกเลิก',
      confirmButtonColor: '#ef4444',
    }).then((result) => {
      if (result.isConfirmed) {
        next()
      } else {
        next(false)
      }
    })
  } else {
    next()
  }
})

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

const courseGroupStore = useCourseGroupStore()
const api = useApi()
const router = useRouter()
const isEnrolling = ref(false)
const isWishlisted = ref(false)
const isTogglingFavorite = ref(false)
const expandedSections = ref<number[]>([0])
const showPurchaseModal = ref(false)
const selectedGroupId = ref<number | null>(null)

// Membership state from parent layout (source of truth)
const isMember = computed(() => !!injectedCourseMemberOfAuth?.value)
const courseGroups = computed(() => courseGroupStore.groups || [])
const hasGroups = computed(() => courseGroups.value.length > 0)

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
    title: `${index + 1}. ${lesson.title || 'ไม่มีชื่อบทเรียน'}`,
    videos: lesson.topics_count || 0,
    items: (lesson.topics || []).map((topic: any) => ({
      id: topic.id,
      title: topic.title || 'ไม่มีชื่อหัวข้อ',
      duration: topic.min_read ? `${topic.min_read} นาที` : '—',
      type: topic.is_preview ? 'video' : 'locked'
    }))
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

// Calculate course price
const coursePrice = computed(() => {
  const price = course.value?.tuition_fees ?? course.value?.price ?? 0
  const discount = course.value?.discount ?? 0
  if (price > 0 && discount > 0) {
    return price - (price * discount / 100)
  }
  return price
})

// Enroll in course - shows purchase modal for paid courses
const enrollCourse = async () => {
  if (!course.value) return
  
  // Show purchase confirmation for paid courses
  if (coursePrice.value > 0) {
    showPurchaseModal.value = true
    return
  }
  
  // Free courses - enroll directly
  await processEnrollment()
}

// Process the actual enrollment
const processEnrollment = async () => {
  if (!course.value) return

  isEnrolling.value = true
  try {
    const payload: Record<string, any> = {}
    if (selectedGroupId.value) payload.group_id = selectedGroupId.value

    const response = await api.post(`/api/courses/${course.value.id}/members`, payload)
    if (response.success) {
      // Refresh parent layout state so the hero section updates too
      if (refreshCourse) refreshCourse(true)

      // Show success message for paid enrollment
      if (response.paid) {
        Swal.fire({
          icon: 'success',
          title: 'สมัครเรียนสำเร็จ!',
          text: `หักเงินจำนวน ฿${response.amount_paid} เรียบร้อยแล้ว`,
          timer: 3000,
          showConfirmButton: false
        })
      } else {
        Swal.fire({
          icon: 'success',
          title: 'สมัครเรียนสำเร็จ!',
          timer: 2000,
          showConfirmButton: false
        })
      }
    }
  } catch (err: any) {
    Swal.fire({
      icon: 'error',
      title: 'ไม่สามารถสมัครเรียนได้',
      text: err.data?.msg || 'กรุณาลองใหม่อีกครั้ง'
    })
  } finally {
    isEnrolling.value = false
  }
}

// Handle purchase confirmation from modal
const onPurchaseConfirm = async () => {
  showPurchaseModal.value = false
  await processEnrollment()
}

// Redirect to wallet topup
const goToTopup = () => {
  router.push('/Earn/Wallet')
}

// Toggle wishlist
const toggleWishlist = async () => {
  if (!course.value || isTogglingFavorite.value) return
  
  isTogglingFavorite.value = true
  try {
    const response = await api.post(`/api/courses/${course.value.id}/favorite`, {}) as { 
      success: boolean
      is_favorited?: boolean
      message?: string 
    }
    
    if (response.success) {
      isWishlisted.value = response.is_favorited ?? !isWishlisted.value
    }
  } catch (err: any) {
    console.error('Failed to toggle favorite:', err)
  } finally {
    isTogglingFavorite.value = false
  }
}

// Initialize wishlist state from course data
watch(() => course.value?.is_favorited, (newVal) => {
  if (newVal !== undefined) {
    isWishlisted.value = newVal
  }
}, { immediate: true })

// Helper functions
const getCoverUrl = (coverPath: string | null) => {
  if (!coverPath) return '/images/default-cover.jpg'
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

// The course payload is cached in the store, so lessons created/edited from
// another tab would otherwise show up stale here. Ask the parent to re-read it
// on every visit — fetchCourse() still serves the cache when it is untouched.
onMounted(() => {
  refreshCourse?.()
})

const pendingInvitation = computed(() => {
  return course.value?.pending_invitation
})

const respondToInvitation = async (accept: boolean) => {
  if (!pendingInvitation.value) return
  
  try {
    const action = accept ? 'accept' : 'decline'
    const res: any = await api.post(`/api/courses/${course.value.id}/admins/invitations/${pendingInvitation.value.id}/${action}`, {})
    if (res.success) {
      if (accept) {
        // Reload page to refresh permissions
        window.location.reload() 
      } else {
        // Just remove the invitation data locally
        if (course.value) {
            course.value.pending_invitation = null
        }
        // Also update store
        courseStore.updateCourse({ pending_invitation: null })
        Swal.fire('ปฏิเสธคำเชิญแล้ว', '', 'success')
      }
    }
  } catch (error: any) {
    Swal.fire('ข้อผิดพลาด', error.response?.data?.message || 'ทำรายการไม่สำเร็จ', 'error')
  }
}
</script>

<template>
  <div v-if="course" class="flex flex-col gap-6">
    <!-- Invitation Alert Card -->
    <div v-if="pendingInvitation" class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6 relative overflow-hidden">
      <div class="absolute top-0 right-0 p-4 opacity-10">
        <Icon icon="fluent:mail-read-24-filled" class="w-24 h-24 text-blue-600" />
      </div>
      <div class="relative z-10">
        <div class="flex items-center gap-3 mb-2">
          <div class="p-2 bg-blue-100 dark:bg-blue-800 rounded-full">
            <Icon icon="fluent:person-key-20-filled" class="w-6 h-6 text-blue-600 dark:text-blue-300" />
          </div>
          <div>
            <h3 class="font-bold text-lg text-gray-900 dark:text-white">คำเชิญเป็นผู้ดูแลรายวิชา</h3>
            <p class="text-blue-700 dark:text-blue-300">
              คุณได้รับคำเชิญให้เข้าร่วมเป็น <span class="font-semibold underline">{{ pendingInvitation.role === 4 ? 'ผู้ดูแลระบบ (Admin)' : 'ผู้ช่วยสอน (TA)' }}</span>
            </p>
          </div>
        </div>
        
        <p class="text-gray-600 dark:text-gray-400 mt-2 mb-4 max-w-xl">
          ผู้เชิญ: {{ pendingInvitation.inviter_id }} (ตรวจสอบโดยระบบ)
          <br>
          เมื่อคุณตอบรับ คุณจะได้รับสิทธิ์ในการจัดการรายวิชานี้ตามบทบาทที่ได้รับมอบหมายทันที
        </p>

        <div class="flex gap-3">
          <button @click="respondToInvitation(true)" class="px-6 py-2 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition-all shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
            ตอบรับคำเชิญ
          </button>
          <button @click="respondToInvitation(false)" class="px-6 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            ปฏิเสธ
          </button>
        </div>
      </div>
    </div>

    <!-- Description Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 sm:p-6 overflow-hidden">
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
      <div v-if="!isEditingDescription">
        <CommonRichTextViewer :content="course.description" collapsed-height="250px" />
      </div>
      
      <!-- Edit Mode -->
      <div v-else class="flex flex-col gap-4">
        <CommonRichTextEditor
          v-model="descriptionContent"
          :id="`course-desc-${course.id}`"
          placeholder="เขียนรายละเอียดรายวิชาที่นี่..."
          min-height="250px"
          @save="saveDescription"
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

    <!-- Curriculum Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
      <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
        <Icon icon="fluent:book-24-regular" class="w-5 h-5 text-blue-500" />
        เนื้อหาบทเรียน
      </h3>
      
      <!-- Curriculum Accordion -->
      <div v-if="curriculum.length > 0" class="flex flex-col gap-2">
        <div 
          v-for="(section, index) in curriculum" 
          :key="section.id" 
          class="border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden"
        >
          <!-- Section Header -->
          <div
            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700/50 flex items-center justify-between transition-colors"
          >
            <NuxtLink 
              :to="`/Learn/Courses/${course.id}/lessons/${section.id}`"
              class="font-medium text-gray-900 dark:text-white hover:text-blue-500 transition-colors"
            >
              {{ section.title }}
            </NuxtLink>
            <div class="flex items-center gap-3 text-gray-500 text-sm">
              <span v-if="section.videos > 0">{{ section.videos }} หัวข้อ</span>
              <span v-else class="opacity-50 italic">ยังไม่มีหัวข้อ</span>
              <button 
                @click="toggleSection(index)"
                class="p-1 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-md transition-colors"
              >
                <Icon 
                  :icon="expandedSections.includes(index) ? 'fluent:chevron-up-24-regular' : 'fluent:chevron-down-24-regular'" 
                  class="w-5 h-5" 
                />
              </button>
            </div>
          </div>

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

      <!-- Empty State -->
      <div v-else class="text-center py-10 px-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-dashed border-gray-300 dark:border-gray-700">
        <Icon icon="fluent:document-question-mark-24-regular" class="w-16 h-16 text-gray-400 mx-auto mb-3" />
        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-2">ยังไม่มีบทเรียนในรายวิชานี้</h4>
        <p class="text-gray-500 dark:text-gray-400 text-sm mb-6 max-w-xs mx-auto">
          อาจารย์ยังไม่ได้เพิ่มเนื้อหาบทเรียน กรุณากลับมาตรวจสอบใหม่อีกครั้งในภายหลัง
        </p>
        <div class="flex items-center justify-center gap-3">
          <NuxtLink 
            :to="`/Learn/Courses/${course.id}/lessons`"
            class="px-5 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
          >
            ไปหน้าบทเรียน
          </NuxtLink>
          <NuxtLink 
            v-if="isCourseAdmin"
            :to="`/Learn/Courses/${course.id}/lessons/create`"
            class="px-5 py-2 bg-blue-500 text-white rounded-lg text-sm font-bold hover:bg-blue-600 shadow-md transition-all"
          >
            เพิ่มบทเรียน
          </NuxtLink>
        </div>
      </div>
    </div>

    <!-- Reviews Section -->
    <LearnCourseRatingCourseReviewsSection
      v-if="course"
      :course-id="course.id"
      :is-member="isMember"
    />

    <!-- Course points claim section (student-facing; widget hides itself when no campaigns are available) -->
    <CourseSupportPanel
      v-if="course"
      :course="course"
      :is-course-admin="isCourseAdmin"
    />

    <!-- Purchase Modal -->
    <LearnCoursePurchaseModal
      v-model="showPurchaseModal"
      :course="course"
      @confirm="onPurchaseConfirm"
      @topup="goToTopup"
    />
  </div>
</template>
