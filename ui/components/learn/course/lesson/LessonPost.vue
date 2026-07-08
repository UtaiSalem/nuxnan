<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick, watch, reactive } from 'vue'
import { Icon } from '@iconify/vue'
import RichTextViewer from '~/components/RichTextViewer.vue'
import VideoModal from '~/components/media/VideoModal.vue'
import LessonRewardBadge from '~/components/learn/course/points/LessonRewardBadge.vue'
import TopicAccordion from './TopicAccordion.vue'
import TopicFormModal from './TopicFormModal.vue'
import TopicOrderWidget from './TopicOrderWidget.vue'
import LessonInteractionTabs from './LessonInteractionTabs.vue'
import Swal from 'sweetalert2'
import ImageGalleryModal from '~/components/ImageGalleryModal.vue'
import { getYoutubeVideoId, getYoutubeThumbnailUrl, getYoutubeEmbedUrl } from '~/utils/youtube'

interface Props {
  lesson: any
  isAdmin?: boolean
  currentUser?: any
  // Balance สำหรับ unlock modal
  userPP?: number
  userWallet?: number
  // Navigation props
  prevLesson?: any
  nextLesson?: any
  currentIndex?: number
  totalLessons?: number
  // Feature flags
  showNavigation?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isAdmin: false,
  showNavigation: true,
  userPP: 0,
  userWallet: 0
})

const emit = defineEmits<{
  edit: [lesson: any]
  delete: [lessonId: number]
  like: [lessonId: number]
  dislike: [lessonId: number]
  bookmark: [lessonId: number]
  share: [lesson: any]
  comment: [lessonId: number]
  navigate: [lessonId: number]
  refresh: []
  'topic-created': [topic: any]
  'topic-updated': [topic: any]
  'topic-deleted': [topicId: number]
}>()

const api = useApi()
const swal = useSweetAlert()
const route = useRoute()

// Locked state logic
const isLocked = computed(() => props.lesson?.is_locked === true)
const accessType = computed(() => props.lesson?.access_type ?? 'free')
const priceLabel = computed(() => props.lesson?.price_label ?? '')
const isUnlocking = ref(false)

const handleUnlock = async () => {
  // สร้างข้อความ confirm แสดงราคา + ยอดคงเหลือ
  const isPoints = accessType.value === 'points'
  const price = isPoints ? props.lesson.price_points : props.lesson.price_money
  const balance = isPoints ? (props.userPP ?? null) : (props.userWallet ?? null)
  const unit = isPoints ? 'แต้ม' : 'บาท'

  let bodyText = `ราคา: <strong>${priceLabel.value}</strong>`
  if (balance !== null) {
    const after = balance - (price ?? 0)
    bodyText += `<br>ยอดคงเหลือปัจจุบัน: <strong>${balance.toLocaleString()} ${unit}</strong>`
    bodyText += `<br>ยอดหลังชำระ: <strong>${after.toLocaleString()} ${unit}</strong>`
    if (after < 0) {
      swal.error(`${isPoints ? 'แต้ม' : 'เงิน'}ไม่เพียงพอ ขาดอีก ${Math.abs(after).toLocaleString()} ${unit}`)
      return
    }
  }

  const confirmed = await swal.confirm(bodyText, 'ยืนยันการปลดล็อก')
  if (!confirmed) return

  isUnlocking.value = true
  try {
    const courseId = route.params.id
    await api.post(`/api/courses/${courseId}/lessons/${props.lesson.id}/unlock`)
    swal.toast('ปลดล็อกบทเรียนสำเร็จ!', 'success')
    emit('refresh') // ให้ parent reload lesson
  } catch (err: any) {
    // ตรวจ 402 จาก backend = แต้ม/เงินไม่พอ
    if (err?.status === 402 || err?.data?.shortage) {
      const shortage = err.data?.shortage ?? ''
      const unit = isPoints ? 'แต้ม' : 'บาท'
      swal.error(`${isPoints ? 'แต้ม' : 'เงิน'}ไม่เพียงพอ ขาดอีก ${shortage} ${unit}`)
    } else {
      swal.error(err?.data?.message || 'เกิดข้อผิดพลาดในการปลดล็อก')
    }
  } finally {
    isUnlocking.value = false
  }
}

// State
const showFullContent = ref(false)
const showVideoModal = ref(false) // Video modal state
const showImagePreview = ref(false) // Image preview modal state
const previewIndex = ref(0) // Current preview image index

// Reading Progress
const lessonIdRef = computed(() => props.lesson.id)
const { 
  summary, 
  loadProgress, 
  startReading, 
  completeReading, 
  isTopicCompleted, 
  getTopicStatus 
} = useTopicReadProgress(lessonIdRef)

onMounted(async () => {
  if (hasTopics.value && !props.isAdmin) {
    await loadProgress()
  }
})

// Topic Management
const showTopicModal = ref(false)
const topicModalRef = ref<any>(null)
const editingTopic = ref(null)
const isSubmittingTopic = ref(false)

const openCreateTopicModal = () => {
    editingTopic.value = null
    showTopicModal.value = true
}

const editTopic = (topic: any) => {
    editingTopic.value = topic
    showTopicModal.value = true
}

const handleTopicSubmit = async (formData: any) => {
    isSubmittingTopic.value = true
    
    // Prepare FormData for file upload
    const payload = new FormData()
    payload.append('title', formData.title)
    if (formData.content) payload.append('content', formData.content)
    if(formData.youtube_url) payload.append('youtube_url', formData.youtube_url)
    payload.append('min_read', String(formData.min_read || 0))

    // Append images
    if (formData.images && formData.images.length > 0) {
        formData.images.forEach((file: File, index: number) => {
            payload.append(`images[${index}]`, file)
        })
    }

    // Append _method for PUT if editing (Laravel quirk for FormData)
    if (editingTopic.value) {
        payload.append('_method', 'PUT')
    }

    try {
        if (editingTopic.value) {
            // Updating
            const response = await api.post(`/api/lessons/${props.lesson.id}/topics/${editingTopic.value.id}`, payload) as any
            swal.toast('แก้ไขข้อมูลเรียบร้อย', 'success')
            // Update topic in local state immediately
            if (response.topic) {
                emit('topic-updated', response.topic)
            } else {
                emit('refresh')
            }
        } else {
            // Creating
            const response = await api.post(`/api/lessons/${props.lesson.id}/topics`, payload) as any
            swal.toast('เพิ่มหัวข้อเรียบร้อย', 'success')
            // Add new topic to local state immediately
            const createdTopic = response.topic || response.newTopic
            if (createdTopic) {
                emit('topic-created', createdTopic)
            } else {
                emit('refresh')
            }
        }
        showTopicModal.value = false
    } catch (err: any) {
        console.error(err)
        if (err.status === 422 && err.data?.errors) {
            // Show field-specific errors in modal
            topicModalRef.value?.setErrors(err.data.errors)
            // Show toast summary of the first error
            const firstError = Object.values(err.data.errors).flat()[0]
            swal.error(firstError as string || 'กรุณาตรวจสอบข้อมูลที่กรอก')
        } else {
            swal.error(err.data?.message || 'ไม่สามารถบันทึกข้อมูลได้')
        }
    } finally {
        isSubmittingTopic.value = false
    }
}

const deleteTopicImage = async (imageId: number) => {
    try {
        const result = await swal.confirm('คุณต้องการลบรูปภาพนี้ใช่หรือไม่?', 'ยืนยันการลบ')
        if (!result) return

        if (!editingTopic.value) return

        await api.delete(`/api/topics/${editingTopic.value.id}/images/${imageId}`)
        swal.toast('ลบรูปภาพเรียบร้อย', 'success')

        // Sync local state: ลบรูปออกจาก topic object ทันที
        if (editingTopic.value.images) {
            editingTopic.value.images = editingTopic.value.images.filter(
                (img: any) => img.id !== imageId
            )
        }

        // Notify parent to update list
        emit('topic-updated', editingTopic.value)
    } catch (err: any) {
        console.error(err)
        swal.error(err.data?.message || 'ไม่สามารถลบรูปภาพได้')
    }
}

const deleteTopic = async (topic: any) => {
    const result = await Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "การกระทำนี้ไม่สามารถยกเลิกได้",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ลบเลย'
    })

    if (result.isConfirmed) {
        try {
            await api.delete(`/api/lessons/${props.lesson.id}/topics/${topic.id}`)
            swal.toast('ลบหัวข้อเรียบร้อย', 'success')
            emit('topic-deleted', topic.id)
        } catch (err) {
             swal.error('ไม่สามารถลบหัวข้อได้')
        }
    }
}


// Content overflow detection
const contentRef = ref<HTMLElement | null>(null)
const isContentOverflowing = ref(false)
const MAX_CONTENT_HEIGHT = 384 // 96 * 4 = 384px (max-h-96)

// Check if content overflows the max height
const checkContentOverflow = () => {
  if (contentRef.value) {
    const scrollHeight = contentRef.value.scrollHeight
    isContentOverflowing.value = scrollHeight > MAX_CONTENT_HEIGHT
  }
}

// Watch for content changes and recheck overflow
let resizeObserver: ResizeObserver | null = null

onMounted(() => {
  nextTick(() => {
    checkContentOverflow()

    // Use ResizeObserver to detect content size changes
    if (contentRef.value && typeof ResizeObserver !== 'undefined') {
      resizeObserver = new ResizeObserver(() => {
        if (!showFullContent.value) {
          checkContentOverflow()
        }
      })
      resizeObserver.observe(contentRef.value)
    }
  })
})

onUnmounted(() => {
  if (resizeObserver) {
    resizeObserver.disconnect()
  }
})

// Re-check when lesson content changes
watch(
  () => props.lesson.content,
  () => {
    nextTick(() => {
      checkContentOverflow()
    })
  }
)

const statusColor = computed(() => {
  return props.lesson.status === 1
    ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
    : 'bg-gray-100 text-gray-700 dark:bg-gray-700/30 dark:text-gray-400'
})

const statusText = computed(() => {
  return props.lesson.status === 1 ? 'เผยแพร่แล้ว' : 'แบบร่าง'
})

const hasTopics = computed(() => props.lesson.topics && props.lesson.topics.length > 0)
const hasImages = computed(() => props.lesson.images && props.lesson.images.length > 0)
const hasAssignments = computed(
  () => props.lesson.assignments && props.lesson.assignments.length > 0
)
const hasQuestions = computed(() => props.lesson.questions && props.lesson.questions.length > 0)

const progressPercentage = computed(() => {
  if (props.isAdmin) return 0
  return summary.value.progress_percentage
})

// Estimated reading time (words per minute = 200)
const estimatedReadingTime = computed(() => {
  const content = props.lesson.content || ''
  const description = props.lesson.description || ''
  const text = content.replace(/<[^>]*>/g, '') + ' ' + description
  const wordCount = text.trim().split(/\s+/).filter(Boolean).length
  const minutes = Math.ceil(wordCount / 200)
  return minutes > 0 ? minutes : 1
})

// Has navigation
const hasNavigation = computed(() => props.showNavigation && (props.prevLesson || props.nextLesson))
const lessonPosition = computed(() => {
  if (props.currentIndex !== undefined && props.currentIndex >= 0 && props.totalLessons) {
    return `${props.currentIndex + 1}/${props.totalLessons}`
  }
  return null
})

const displayOrder = computed(() => {
  // List view: ใช้ index จาก parent (แม่นยำที่สุดสำหรับทั้ง admin และ student)
  if (props.currentIndex !== undefined && props.currentIndex >= 0) {
    return props.currentIndex + 1
  }
  // Single lesson view (student): ใช้ display_order จาก backend (เฉพาะ published, ไม่มี gap)
  if (!props.isAdmin && props.lesson?.display_order) {
    return props.lesson.display_order
  }
  // Admin single view: ใช้ canonical order จาก DB
  return props.lesson?.order ?? 1
})

// Computed YouTube embed URL
const youtubeVideoId = computed(() => getYoutubeVideoId(props.lesson.youtube_url))
const hasYoutubeVideo = computed(() => !!youtubeVideoId.value)
const youtubeEmbedUrl = computed(() => getYoutubeEmbedUrl(youtubeVideoId.value))
const youtubeThumbnailUrl = computed(() => getYoutubeThumbnailUrl(youtubeVideoId.value))

// Video modal methods
const openVideoModal = () => {
  showVideoModal.value = true
}

const closeVideoModal = () => {
  showVideoModal.value = false
}

// Image preview methods
const openImagePreview = (index: number) => {
  previewIndex.value = index
  showImagePreview.value = true
}

const closeImagePreview = () => {
  showImagePreview.value = false
}

const handleImageError = (event: Event, index: number) => {
  const img = event.target as HTMLImageElement
  // Prevent infinite loop if fallback image also fails
  if (img.dataset.fallbackApplied) return
  img.dataset.fallbackApplied = 'true'
  
  img.src = '/images/default-avatar.png'
  img.alt = `รูปภาพ ${index + 1} ไม่สามารถโหลดได้`
}

// Methods
const toggleContent = () => {
  showFullContent.value = !showFullContent.value
}

const handleEdit = () => {
  emit('edit', props.lesson)
}

const handleDelete = () => {
  emit('delete', props.lesson.id)
}

// Handle topic expand → start reading
const handleTopicExpand = async (topicId: number) => {
  if (props.isAdmin) return
  await startReading(topicId)
}

const handleTopicComplete = async (topicId: number) => {
  if (props.isAdmin) return
  
  const result = await completeReading(topicId)
  
  if (result.code === 'topic_read_too_fast') {
    swal.error(`${result.message} (เหลืออีก ${result.remaining_seconds} วินาที)`)
    return
  }
  
  if (!result.success) {
    swal.error(result.message || 'ไม่สามารถบันทึกข้อมูลได้')
    return
  }

  if (result.lesson_completed) {
    emit('refresh')
    if (result.reward?.rewarded) {
      rewardResult.value = result.reward
      showRewardToast.value = true
      setTimeout(() => { showRewardToast.value = false }, 4000)
    } else {
      swal.toast('อ่านครบทุกหัวข้อ บทเรียนนี้เสร็จสมบูรณ์แล้ว!', 'success')
    }
  }
}

// Reward Toast State
const rewardResult = ref<any>(null)
const showRewardToast = ref(false)

const handleComplete = (response: any) => {
  if (response.reward?.rewarded) {
    rewardResult.value = response.reward
    showRewardToast.value = true
    setTimeout(() => {
      showRewardToast.value = false
    }, 4000)
  }
  emit('refresh')
}

// Publication Status Helpers
const publicationStatusText = computed(() => {
  switch (props.lesson.publication_status) {
    case 'draft': return 'ฉบับร่าง'
    case 'archived': return 'เก็บถาวร'
    case 'published': return 'เผยแพร่แล้ว'
    default: return 'ไม่ระบุ'
  }
})

const publicationStatusIcon = computed(() => {
  switch (props.lesson.publication_status) {
    case 'draft': return 'fluent:draft-24-regular'
    case 'archived': return 'fluent:archive-24-regular'
    case 'published': return 'fluent:checkmark-circle-24-filled'
    default: return 'fluent:question-24-regular'
  }
})

const publicationStatusColor = computed(() => {
  switch (props.lesson.publication_status) {
    case 'draft': return 'bg-gray-500/90 text-white'
    case 'archived': return 'bg-orange-500/90 text-white'
    case 'published': return 'bg-green-500/90 text-white'
    default: return 'bg-gray-400/90 text-white'
  }
})
</script>

<template>
  <article
    :id="`lesson-${lesson.id}`"
    class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden mb-6 border border-gray-200 dark:border-gray-700"
  >
    <!-- Header Section -->
    <div class="relative">
      <!-- Cover with gradient and icon -->
      <div
        class="relative h-40 bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 dark:from-blue-900/20 dark:via-purple-900/20 dark:to-pink-900/20 overflow-hidden rounded-t-2xl"
      >
        <!-- Default gradient with animated icon -->
        <div
          class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-100 via-purple-100 to-pink-100 dark:from-blue-900/30 dark:via-purple-900/30 dark:to-pink-900/30"
        >
          <Icon
            icon="fluent:book-24-filled"
            class="w-20 h-20 text-blue-400/40 dark:text-blue-300/30 animate-pulse"
          />
        </div>

        <!-- Lighter Gradient Overlay -->
        <div
          class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent"
        ></div>
      </div>

      <!-- Unified overlay bar: badges left, access + admin right — no overlap possible -->
      <div class="absolute top-4 left-4 right-4 flex items-start justify-between gap-3">

        <!-- Left group: publication status, order, reading time, position -->
        <div class="flex flex-wrap gap-2">
          <!-- Publication Status Badge (admin only, or non-published) -->
          <span
            v-if="isAdmin || lesson.publication_status !== 'published'"
            :class="publicationStatusColor"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-bold shadow-lg backdrop-blur-md ring-1 ring-white/20 transition-transform hover:scale-105"
          >
            <Icon :icon="publicationStatusIcon" class="w-4 h-4" />
            {{ publicationStatusText }}
          </span>

          <!-- Order Badge -->
          <span
            class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-500/90 backdrop-blur-md text-white text-sm font-bold shadow-lg ring-1 ring-white/20 transition-transform hover:scale-105"
          >
            <Icon icon="fluent:number-symbol-24-filled" class="w-4 h-4" />
            บทที่ {{ displayOrder }}
          </span>

          <!-- Reading Time Badge -->
          <span
            class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-purple-500/90 backdrop-blur-md text-white text-sm font-bold shadow-lg ring-1 ring-white/20 transition-transform hover:scale-105"
          >
            <Icon icon="fluent:clock-24-filled" class="w-4 h-4" />
            {{ lesson.min_read }} นาที
          </span>

          <!-- Lesson Position Badge (if navigation enabled) -->
          <span
            v-if="lessonPosition"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-cyan-500/90 backdrop-blur-md text-white text-sm font-bold shadow-lg ring-1 ring-white/20 transition-transform hover:scale-105"
          >
            <Icon icon="fluent:list-24-filled" class="w-4 h-4" />
            {{ lessonPosition }}
          </span>
        </div>

        <!-- Right group: access badge + admin actions (stacked, never overlaps left) -->
        <div class="flex flex-col items-end gap-2 shrink-0">
          <!-- Access Badge -->
          <span
            v-if="lesson.access_type === 'points'"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-amber-500/90 backdrop-blur-md text-white text-sm font-bold shadow-lg ring-1 ring-white/20 transition-transform hover:scale-105"
          >
            <Icon icon="fluent:star-24-filled" class="w-4 h-4" />
            {{ lesson.price_label || `${lesson.point_tuition_fee} พอยต์` }}
          </span>
          <span
            v-else-if="lesson.access_type === 'money'"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-green-500/90 backdrop-blur-md text-white text-sm font-bold shadow-lg ring-1 ring-white/20 transition-transform hover:scale-105"
          >
            <Icon icon="fluent:wallet-24-filled" class="w-4 h-4" />
            {{ lesson.price_label || `฿${lesson.money_tuition_fee}` }}
          </span>
          <span
            v-else
            class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-500/90 backdrop-blur-md text-white text-sm font-bold shadow-lg ring-1 ring-white/20 transition-transform hover:scale-105"
          >
            <Icon icon="fluent:lock-open-24-filled" class="w-4 h-4" />
            ฟรี
          </span>

          <!-- Admin Action Buttons (below access badge, right-aligned) -->
          <div v-if="isAdmin" class="flex gap-2">
            <button
              @click="handleEdit"
              class="p-3 bg-white/95 dark:bg-gray-800/95 rounded-xl hover:bg-white dark:hover:bg-gray-800 transition-all duration-200 backdrop-blur-md shadow-lg ring-1 ring-gray-200/50 dark:ring-gray-700/50 hover:scale-105"
              title="แก้ไข"
            >
              <Icon icon="fluent:edit-24-regular" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
            </button>
            <button
              @click="handleDelete"
              class="p-3 bg-white/95 dark:bg-gray-800/95 rounded-xl hover:bg-white dark:hover:bg-gray-800 transition-all duration-200 backdrop-blur-md shadow-lg ring-1 ring-gray-200/50 dark:ring-gray-700/50 hover:scale-105"
              title="ลบ"
            >
              <Icon icon="fluent:delete-24-regular" class="w-5 h-5 text-red-600 dark:text-red-400" />
            </button>
          </div>
        </div>

      </div>
    </div>

    <!-- Content Section -->
    <div class="p-6 space-y-6">
      <!-- Title & Meta -->
      <div>
        <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4 leading-tight flex items-center gap-3">
          {{ lesson.title }}
          <LessonRewardBadge
            v-if="!isAdmin"
            :reward="lesson?.reward ?? null"
            size="md"
          />
        </h2>

        <!-- Creator & Stats -->
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-3">
            <img
              :src="lesson.creater?.avatar || '/images/default-avatar.png'"
              :alt="lesson.creater?.name"
              class="w-10 h-10 rounded-full ring-2 ring-blue-500 object-cover"
              @error="(e) => (e.target as HTMLImageElement).src = '/images/default-avatar.png'"
            />
            <div>
              <p class="font-medium text-gray-900 dark:text-white">
                {{ lesson.creater?.name }}
              </p>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ lesson.created_at_for_humans }}
              </p>
            </div>
          </div>

          <!-- Quick Stats -->
          <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
            <div class="flex items-center gap-1">
              <Icon icon="fluent:clock-24-regular" class="w-4 h-4" />
              <span>{{ lesson.min_read }} นาที</span>
            </div>
            <div v-if="lesson.view_count" class="flex items-center gap-1">
              <Icon icon="fluent:eye-24-regular" class="w-4 h-4" />
              <span>{{ lesson.view_count }}</span>
            </div>
          </div>
        </div>

        <!-- Progress Bar (if has topics) -->
        <div
          v-if="hasTopics && !isAdmin"
          class="bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden"
        >
          <div
            class="bg-gradient-to-r from-blue-500 to-purple-500 h-full transition-all duration-500"
            :style="{ width: `${progressPercentage}%` }"
          ></div>
        </div>
        <p v-if="hasTopics && !isAdmin" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
          ความคืบหน้า: {{ summary.progress_percentage }}% ({{ summary.completed_topics }}/{{
            summary.total_topics
          }}
          หัวข้อ)
        </p>
      </div>

      <!-- Description -->
      <!-- Description -->
      <div v-if="lesson.description" class="mb-8">
        <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white mb-3">
          <Icon icon="fluent:text-description-24-filled" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
          คำอธิบาย
        </h3>
        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
          <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
            {{ lesson.description }}
          </p>
        </div>
      </div>

      <!-- Locked Overlay -->
      <div v-if="isLocked"
        class="my-6 rounded-2xl border-2 border-dashed border-yellow-400/50 bg-yellow-500/10 p-8 text-center transition-all animate-in fade-in zoom-in duration-300">
        <Icon icon="fluent:lock-closed-24-filled" class="mx-auto mb-3 h-16 w-16 text-yellow-400" />
        <h3 class="mb-1 text-2xl font-bold text-yellow-500">บทเรียนนี้ถูกล็อกอยู่</h3>
        <p class="mb-6 text-gray-600 dark:text-gray-400">
          <template v-if="accessType === 'points'">
            ต้องใช้ <span class="font-bold text-yellow-600 dark:text-yellow-400 text-lg">{{ priceLabel }}</span> เพื่อปลดล็อกเนื้อหาทั้งหมด
          </template>
          <template v-else-if="accessType === 'money'">
            ต้องชำระ <span class="font-bold text-green-600 dark:text-green-400 text-lg">{{ priceLabel }}</span> เพื่อเข้าชมบทเรียนนี้
          </template>
        </p>
        <button
          @click="handleUnlock"
          :disabled="isUnlocking"
          class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-yellow-500 to-orange-500 px-8 py-4 text-lg font-bold text-white shadow-lg hover:from-yellow-600 hover:to-orange-600 hover:shadow-xl transition-all disabled:opacity-50 transform hover:scale-105 active:scale-95"
        >
          <Icon v-if="isUnlocking" icon="fluent:spinner-ios-20-regular" class="h-6 w-6 animate-spin" />
          <template v-else>
            <Icon :icon="accessType === 'points' ? 'fluent:star-24-filled' : 'fluent:wallet-24-filled'" class="h-6 w-6" />
            {{ accessType === 'points' ? `ปลดล็อกด้วย ${priceLabel}` : `ชำระเงิน ${priceLabel}` }}
          </template>
        </button>
      </div>

      <!-- Sensitive Content Container -->
      <template v-if="!isLocked">
        <!-- Main Content -->
        <div class="relative">
          <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white mb-4">
            <Icon icon="fluent:document-text-24-filled" class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
            เนื้อหาบทเรียน
          </h3>
          <div
            ref="contentRef"
            :class="[
              'prose prose-blue dark:prose-invert max-w-none transition-all duration-300',
              !showFullContent && isContentOverflowing && 'max-h-96 overflow-hidden',
            ]"
          >
            <RichTextViewer :content="lesson.content" />
          </div>

          <!-- Read More Button - Only show when content overflows -->
          <div
            v-if="isContentOverflowing && !showFullContent"
            class="absolute bottom-0 inset-x-0 h-24 bg-gradient-to-t from-white dark:from-gray-800 to-transparent flex items-end justify-center pb-2"
          >
            <button
              @click="toggleContent"
              class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors font-medium shadow-lg"
            >
              อ่านต่อ
              <Icon icon="fluent:chevron-down-24-regular" class="w-4 h-4 inline ml-1" />
            </button>
          </div>

          <!-- Collapse Button - Only show when expanded and content was overflowing -->
          <button
            v-if="isContentOverflowing && showFullContent"
            @click="toggleContent"
            class="mt-4 w-full px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors font-medium"
          >
            ย่อเนื้อหา
            <Icon icon="fluent:chevron-up-24-regular" class="w-4 h-4 inline ml-1" />
          </button>
        </div>

        <!-- Image Gallery Section -->
        <div v-if="hasImages" class="mt-6">
          <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white mb-4">
            <Icon
              icon="fluent:image-multiple-24-filled"
              class="w-6 h-6 text-indigo-600 dark:text-indigo-400"
            />
            รูปภาพประกอบบทเรียน
          </h3>
          
          <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <div
              v-for="(image, index) in lesson.images"
              :key="image.id"
              class="relative aspect-square rounded-xl overflow-hidden cursor-pointer group shadow-md border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-300"
              @click="openImagePreview(index)"
            >
              <img
                :src="image.full_url || image.url"
                :alt="`รูปที่ ${index + 1}`"
                class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                @error="handleImageError($event, index)"
              />
              <!-- Hover Overlay -->
              <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-all duration-300 flex items-center justify-center opacity-0 group-hover:opacity-100">
                <div class="w-12 h-12 bg-white/90 rounded-full flex items-center justify-center shadow-lg">
                  <Icon icon="fluent:zoom-in-24-filled" class="w-6 h-6 text-gray-700" />
                </div>
              </div>
              <!-- Image Number Badge -->
              <div class="absolute bottom-2 right-2 px-2 py-1 bg-black/60 rounded-md text-xs text-white font-medium">
                {{ index + 1 }}/{{ lesson.images.length }}
              </div>
            </div>
          </div>
        </div>

        <!-- Video Section - Show YouTube video thumbnail below content -->
        <div v-if="hasYoutubeVideo" class="mt-6">
          <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white mb-4">
            <Icon
              icon="fluent:video-24-filled"
              class="w-6 h-6 text-red-600 dark:text-red-400"
            />
            วิดีโอประกอบบทเรียน
          </h3>
          
          <div
            class="relative rounded-2xl overflow-hidden cursor-pointer group shadow-lg border border-gray-200 dark:border-gray-700"
            @click="openVideoModal"
          >
            <!-- YouTube Thumbnail -->
            <div class="aspect-video">
              <img
                :src="youtubeThumbnailUrl"
                :alt="lesson.title"
                class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
              />
            </div>
            
            <!-- Play Button Overlay -->
            <div
              class="absolute inset-0 flex items-center justify-center bg-gradient-to-t from-black/60 via-black/20 to-transparent group-hover:from-black/70 group-hover:via-black/30 transition-all duration-300"
            >
              <div
                class="w-20 h-20 bg-red-600 rounded-full flex items-center justify-center shadow-2xl transform transition-all duration-300 group-hover:scale-110 group-hover:shadow-red-500/50"
              >
                <Icon icon="fluent:play-24-filled" class="w-10 h-10 text-white ml-1" />
              </div>
            </div>
            
            <!-- Video Info Label -->
            <div class="absolute bottom-0 left-0 right-0 p-4 bg-gradient-to-t from-black/80 to-transparent">
              <div class="flex items-center justify-between">
                <span class="text-white font-medium truncate">{{ lesson.title }}</span>
                <span
                  class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-600/90 text-white text-sm font-medium"
                >
                  <Icon icon="logos:youtube-icon" class="w-4 h-4" />
                  YouTube
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Topics Section -->
        <div v-if="hasTopics || isAdmin" class="mt-8">
          <div class="flex items-center justify-between mb-4">
              <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white">
                  <Icon icon="fluent:book-open-24-filled" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                  หัวข้อย่อย
                  <span v-if="hasTopics" class="text-sm font-normal text-gray-500 dark:text-gray-400 ml-2">
                      ({{ lesson.topics.length }} หัวข้อ)
                  </span>
              </h3>
              
               <button 
                  v-if="isAdmin"
                  @click="openCreateTopicModal"
                  class="px-3 py-1.5 bg-purple-600 text-white text-sm rounded-lg hover:bg-purple-700 flex items-center gap-2 shadow-md transition-all hover:scale-105"
              >
                  <Icon icon="fluent:add-24-filled" class="w-4 h-4" />
                  เพิ่มหัวข้อ
              </button>
          </div>

          <!-- Reorder Topics (Admin Only) -->
          <div v-if="isAdmin && hasTopics" class="mb-6">
            <details class="group bg-purple-50 dark:bg-purple-900/20 rounded-xl border border-purple-200 dark:border-purple-800 transition-all duration-300">
              <summary class="flex items-center justify-between p-3 cursor-pointer list-none font-bold text-purple-700 dark:text-purple-300 hover:bg-purple-100 dark:hover:bg-purple-900/40 rounded-xl transition-colors">
                <div class="flex items-center gap-3">
                  <div class="p-2 bg-purple-600 text-white rounded-lg shadow-md group-open:scale-90 transition-transform">
                    <Icon icon="fluent:reorder-24-filled" class="w-5 h-5" />
                  </div>
                  <div class="flex flex-col">
                    <span class="text-sm">จัดลำดับหัวข้อ</span>
                    <span class="text-[10px] opacity-60 font-normal">ทั้งหมด {{ lesson.topics.length }} หัวข้อ</span>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <span class="text-xs font-normal opacity-0 group-hover:opacity-100 transition-opacity hidden md:inline">คลิกเพื่อเปิดส่วนจัดการ</span>
                  <Icon 
                    icon="fluent:chevron-down-24-regular" 
                    class="w-5 h-5 transition-transform duration-300 group-open:rotate-180" 
                  />
                </div>
              </summary>
              <div class="p-4 pt-2 border-t border-purple-100 dark:border-purple-800/50">
                <TopicOrderWidget 
                  :topics="lesson.topics" 
                  :lesson-id="lesson.id" 
                  @saved="emit('refresh')"
                />
              </div>
            </details>
          </div>

          <div v-if="hasTopics">
              <div class="space-y-2">
                <TopicAccordion
                  v-for="topic in lesson.topics"
                  :key="topic.id"
                  :topic="topic"
                  :status="getTopicStatus(topic.id)"
                  :isAdmin="isAdmin"
                  @expand="handleTopicExpand"
                  @complete="handleTopicComplete"
                  @edit="editTopic"
                  @delete="deleteTopic"
                />
              </div>
          </div>
          
          <div v-else class="p-8 text-center border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-800/50">
               <p class="text-gray-500 dark:text-gray-400 mb-2">ยังไม่มีหัวข้อย่อยในบทเรียนนี้</p>
               <button v-if="isAdmin" @click="openCreateTopicModal" class="text-purple-600 hover:text-purple-700 font-medium hover:underline">เพิ่มหัวข้อแรกเลย</button>
          </div>
        </div>

        <!-- Interaction Tabs (Reaction / Assignment / Quiz) -->
        <LessonInteractionTabs
          :lesson="lesson"
          :isAdmin="isAdmin"
          @like="$emit('like', lesson.id)"
          @dislike="$emit('dislike', lesson.id)"
          @bookmark="$emit('bookmark', lesson.id)"
          @share="$emit('share', lesson)"
          @complete="handleComplete"
        />
      </template>

      <!-- Reward Toast Overlay -->
      <Transition
        enter-active-class="transition-all duration-500"
        enter-from-class="opacity-0 translate-y-4"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition-all duration-300"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="showRewardToast && rewardResult"
          class="fixed bottom-24 left-1/2 -translate-x-1/2 z-50
                 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-2xl
                 shadow-2xl px-6 py-4 flex items-center gap-4
                 min-w-[280px] border border-white/20 backdrop-blur-md"
        >
          <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center shrink-0 shadow-inner">
            <Icon icon="mdi:star" class="w-7 h-7 text-amber-300 animate-bounce" />
          </div>
          <div>
            <p class="text-xs font-bold uppercase tracking-wider opacity-80">รางวัลการอ่าน</p>
            <p class="text-xl font-black">+{{ rewardResult.points_received }} แต้ม!</p>
          </div>
        </div>
      </Transition>

      <!-- Navigation Bar -->
      <div 
        v-if="hasNavigation" 
        class="flex items-center justify-between gap-4 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 rounded-b-2xl"
      >
        <!-- Previous Lesson -->
        <NuxtLink
          v-if="prevLesson"
          :to="`/Learn/Courses/${lesson.course_id}/lessons/${prevLesson.id}`"
          class="flex items-center gap-3 px-4 py-3 rounded-xl bg-white dark:bg-gray-700 shadow-md hover:shadow-lg transition-all group hover:-translate-x-1"
        >
          <Icon icon="fluent:chevron-left-24-filled" class="w-5 h-5 text-gray-400 group-hover:text-blue-500 transition-colors" />
          <div class="text-left">
            <p class="text-xs text-gray-400 uppercase tracking-wide">บทก่อนหน้า</p>
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 line-clamp-1 max-w-[150px] md:max-w-[200px]">
              {{ prevLesson.title }}
            </p>
          </div>
        </NuxtLink>
        <div v-else class="flex-1"></div>

        <!-- Center: Position indicator -->
        <div 
          v-if="lessonPosition"
          class="hidden md:flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-700 rounded-full shadow"
        >
          <Icon icon="fluent:book-open-24-regular" class="w-4 h-4 text-blue-500" />
          <span class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ lessonPosition }}</span>
        </div>

        <!-- Next Lesson -->
        <NuxtLink
          v-if="nextLesson"
          :to="`/Learn/Courses/${lesson.course_id}/lessons/${nextLesson.id}`"
          class="flex items-center gap-3 px-4 py-3 rounded-xl bg-white dark:bg-gray-700 shadow-md hover:shadow-lg transition-all group hover:translate-x-1"
        >
          <div class="text-right">
            <p class="text-xs text-gray-400 uppercase tracking-wide">บทถัดไป</p>
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 line-clamp-1 max-w-[150px] md:max-w-[200px]">
              {{ nextLesson.title }}
            </p>
          </div>
          <Icon icon="fluent:chevron-right-24-filled" class="w-5 h-5 text-gray-400 group-hover:text-blue-500 transition-colors" />
        </NuxtLink>
        <div v-else class="flex-1"></div>
      </div>
    </div>

    <!-- Topic Form Modal -->
    <TopicFormModal
        ref="topicModalRef"
        :show="showTopicModal"
        :topic="editingTopic"
        :is-submitting="isSubmittingTopic"
        @close="showTopicModal = false"
        @submit="handleTopicSubmit"
        @delete-image="deleteTopicImage"
    />

    <!-- Video Modal -->
    <VideoModal
      v-if="showVideoModal"
      :youtube-url="lesson.youtube_url"
      :title="lesson.title"
      @close="closeVideoModal"
    />

    <!-- Lesson Image Gallery Modal -->
    <ImageGalleryModal
      :show="showImagePreview"
      :images="lesson.images || []"
      :start-index="previewIndex"
      title="รูปภาพประกอบบทเรียน"
      @close="closeImagePreview"
    />
  </article>
</template>
