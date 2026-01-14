<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { Icon } from '@iconify/vue'
import RichTextViewer from '~/components/RichTextViewer.vue'
import VideoModal from '~/components/media/VideoModal.vue'
import TopicAccordion from './TopicAccordion.vue'
import LessonInteractionTabs from './LessonInteractionTabs.vue'

interface Props {
  lesson: any
  isAdmin?: boolean
  currentUser?: any
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
}>()

// State
const showFullContent = ref(false)
const showTopics = ref(false)
const completedTopics = ref<number[]>([]) // Track completed topic IDs
const showVideoModal = ref(false) // Video modal state

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
const hasAssignments = computed(
  () => props.lesson.assignments && props.lesson.assignments.length > 0
)
const hasQuestions = computed(() => props.lesson.questions && props.lesson.questions.length > 0)

const progressPercentage = computed(() => {
  if (!hasTopics.value) return 0
  return Math.round((completedTopics.value.length / props.lesson.topics.length) * 100)
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
  if (props.currentIndex !== undefined && props.totalLessons) {
    return `${props.currentIndex + 1}/${props.totalLessons}`
  }
  return null
})

// Extract YouTube video ID from various URL formats
const getYoutubeVideoId = (url: string): string | null => {
  if (!url) return null

  // Already just a video ID (11 characters)
  if (/^[a-zA-Z0-9_-]{11}$/.test(url)) {
    return url
  }

  // Various YouTube URL patterns
  const patterns = [
    /(?:youtube\.com\/watch\?v=|youtube\.com\/watch\?.+&v=)([a-zA-Z0-9_-]{11})/, // youtube.com/watch?v=ID
    /youtu\.be\/([a-zA-Z0-9_-]{11})/, // youtu.be/ID
    /youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/, // youtube.com/embed/ID
    /youtube\.com\/v\/([a-zA-Z0-9_-]{11})/, // youtube.com/v/ID
    /youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/, // youtube.com/shorts/ID
  ]

  for (const pattern of patterns) {
    const match = url.match(pattern)
    if (match && match[1]) {
      return match[1]
    }
  }

  return null
}

// Computed YouTube embed URL
const youtubeVideoId = computed(() => getYoutubeVideoId(props.lesson.youtube_url))
const hasYoutubeVideo = computed(() => !!youtubeVideoId.value)
const youtubeEmbedUrl = computed(() => {
  if (!youtubeVideoId.value) return null
  return `https://www.youtube.com/embed/${youtubeVideoId.value}`
})

// YouTube thumbnail URL (high quality)
const youtubeThumbnailUrl = computed(() => {
  if (!youtubeVideoId.value) return null
  return `https://img.youtube.com/vi/${youtubeVideoId.value}/maxresdefault.jpg`
})

// Video modal methods
const openVideoModal = () => {
  showVideoModal.value = true
}

const closeVideoModal = () => {
  showVideoModal.value = false
}

// Methods
const toggleContent = () => {
  showFullContent.value = !showFullContent.value
}

const toggleTopics = () => {
  showTopics.value = !showTopics.value
}

const handleEdit = () => {
  emit('edit', props.lesson)
}

const handleDelete = () => {
  emit('delete', props.lesson.id)
}

const handleTopicComplete = (topicId: number) => {
  const index = completedTopics.value.indexOf(topicId)
  if (index > -1) {
    completedTopics.value.splice(index, 1)
  } else {
    completedTopics.value.push(topicId)
  }
}
</script>

<template>
  <article
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

      <!-- Badges with Glassmorphism -->
      <div class="absolute top-4 left-4 flex flex-wrap gap-2">
        <!-- Status Badge -->
        <span
          :class="statusColor"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-bold shadow-lg backdrop-blur-md bg-white/20 dark:bg-gray-900/30 ring-1 ring-white/20 transition-transform hover:scale-105"
        >
          <Icon
            v-if="lesson.status === 1"
            icon="fluent:checkmark-circle-24-filled"
            class="w-4 h-4"
          />
          <Icon v-else icon="fluent:draft-24-regular" class="w-4 h-4" />
          {{ statusText }}
        </span>

        <!-- Order Badge -->
        <span
          class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-500/90 backdrop-blur-md text-white text-sm font-bold shadow-lg ring-1 ring-white/20 transition-transform hover:scale-105"
        >
          <Icon icon="fluent:number-symbol-24-filled" class="w-4 h-4" />
          บทที่ {{ lesson.order }}
        </span>

        <!-- Reading Time Badge -->
        <span
          class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-purple-500/90 backdrop-blur-md text-white text-sm font-bold shadow-lg ring-1 ring-white/20 transition-transform hover:scale-105"
        >
          <Icon icon="fluent:clock-24-filled" class="w-4 h-4" />
          {{ estimatedReadingTime }} นาที
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

      <!-- Points Badge -->
      <div v-if="lesson.point_tuition_fee > 0" class="absolute top-4 right-4">
        <span
          class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-amber-500/90 backdrop-blur-md text-white text-sm font-bold shadow-lg ring-1 ring-white/20 transition-transform hover:scale-105"
        >
          <Icon icon="fluent:diamond-24-filled" class="w-4 h-4" />
          {{ lesson.point_tuition_fee }} พอยต์
        </span>
      </div>

      <!-- Admin Actions with better glassmorphism -->
      <div
        v-if="isAdmin"
        class="absolute top-4 right-4 flex gap-2"
        :class="{ 'top-16': lesson.point_tuition_fee > 0 }"
      >
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

    <!-- Content Section -->
    <div class="p-6 space-y-6">
      <!-- Title & Meta -->
      <div>
        <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4 leading-tight">
          {{ lesson.title }}
        </h2>

        <!-- Creator & Stats -->
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-3">
            <img
              :src="lesson.creater?.avatar || '/images/default-avatar.png'"
              :alt="lesson.creater?.name"
              class="w-10 h-10 rounded-full ring-2 ring-blue-500 object-cover"
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
          ความคืบหน้า: {{ progressPercentage }}% ({{ completedTopics.length }}/{{
            lesson.topics.length
          }}
          หัวข้อ)
        </p>
      </div>

      <!-- Description -->
      <div
        v-if="lesson.description"
        class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800"
      >
        <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
          {{ lesson.description }}
        </p>
      </div>

      <!-- Main Content -->
      <div class="relative">
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
      <div v-if="hasTopics">
        <button
          @click="toggleTopics"
          class="w-full flex items-center justify-between p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl border border-purple-200 dark:border-purple-800 hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors"
        >
          <div class="flex items-center gap-3">
            <Icon
              icon="fluent:book-open-24-filled"
              class="w-6 h-6 text-purple-600 dark:text-purple-400"
            />
            <div class="text-left">
              <h3 class="font-semibold text-gray-900 dark:text-white">หัวข้อย่อย</h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ lesson.topics.length }} หัวข้อ
              </p>
            </div>
          </div>
          <Icon
            :icon="showTopics ? 'fluent:chevron-up-24-filled' : 'fluent:chevron-down-24-filled'"
            class="w-5 h-5 text-gray-600 dark:text-gray-400"
          />
        </button>

        <div v-show="showTopics" class="mt-4 space-y-2">
          <TopicAccordion
            v-for="topic in lesson.topics"
            :key="topic.id"
            :topic="topic"
            :is-completed="completedTopics.includes(topic.id)"
            @toggle-complete="handleTopicComplete"
          />
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
      />

      <!-- Navigation Bar -->
      <div 
        v-if="hasNavigation" 
        class="flex items-center justify-between gap-4 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 rounded-b-2xl"
      >
        <!-- Previous Lesson -->
        <NuxtLink
          v-if="prevLesson"
          :to="`/courses/${lesson.course_id}/lessons/${prevLesson.id}`"
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
          :to="`/courses/${lesson.course_id}/lessons/${nextLesson.id}`"
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

    <!-- Video Modal -->
    <VideoModal
      v-if="showVideoModal"
      :youtube-url="lesson.youtube_url"
      :title="lesson.title"
      @close="closeVideoModal"
    />
  </article>
</template>
