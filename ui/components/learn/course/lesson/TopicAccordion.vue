<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Icon } from '@iconify/vue'
import RichTextViewer from '~/components/RichTextViewer.vue'
import ImageGalleryModal from '~/components/ImageGalleryModal.vue'
import VideoModal from '~/components/media/VideoModal.vue'
import LessonAttachmentList from './LessonAttachmentList.vue'
import { getYoutubeVideoId, getYoutubeThumbnailUrl } from '~/utils/youtube'

interface Props {
  topic: any
  status?: 'not_started' | 'in_progress' | 'completed'
  isAdmin?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  status: 'not_started',
  isAdmin: false
})

const emit = defineEmits<{
  'complete': [topicId: number]
  'expand': [topicId: number]
  'edit': [topic: any]
  'delete': [topic: any]
}>()

const isCompleted = computed(() => props.status === 'completed')
const isInProgress = computed(() => props.status === 'in_progress')

const isExpanded = ref(false)

// Video state and computation
const showVideoModal = ref(false)
const youtubeVideoId = computed(() => getYoutubeVideoId(props.topic.youtube_url))
const hasYoutubeVideo = computed(() => !!youtubeVideoId.value)
const isInvalidYoutubeUrl = computed(() => !!props.topic.youtube_url && !youtubeVideoId.value)

// Handle maxresdefault thumbnail error fallback to hqdefault
const youtubeThumbnailUrl = ref('')
watch(youtubeVideoId, (newId) => {
  if (newId) {
    youtubeThumbnailUrl.value = getYoutubeThumbnailUrl(newId) || ''
  } else {
    youtubeThumbnailUrl.value = ''
  }
}, { immediate: true })

const handleThumbnailError = () => {
  if (youtubeVideoId.value && youtubeThumbnailUrl.value.includes('maxresdefault')) {
    youtubeThumbnailUrl.value = `https://img.youtube.com/vi/${youtubeVideoId.value}/hqdefault.jpg`
  }
}

// Image gallery
const showImageGallery = ref(false)
const galleryStartIndex = ref(0)
const openTopicImage = (index: number) => {
  galleryStartIndex.value = index
  showImageGallery.value = true
}

const toggleExpand = () => {
  isExpanded.value = !isExpanded.value
  if (isExpanded.value) {
    emit('expand', props.topic.id)
  }
}

const handleCheckboxClick = (e: Event) => {
  e.stopPropagation()
  emit('complete', props.topic.id)
}

const handleEditClick = (e: Event) => {
  e.stopPropagation()
  emit('edit', props.topic)
}

const handleDeleteClick = (e: Event) => {
  e.stopPropagation()
  emit('delete', props.topic)
}

const handleImageError = (event: Event) => {
  const img = event.target as HTMLImageElement
  if (img.dataset.fallbackApplied) return
  img.dataset.fallbackApplied = 'true'
  img.src = '/images/default-avatar.png'
}
</script>

<template>
  <div 
    class="border rounded-xl overflow-hidden transition-all duration-300"
    :class="[
      isCompleted 
        ? 'border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/10' 
        : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800'
    ]"
  >
    <!-- Header -->
    <button
      @click="toggleExpand"
      class="w-full flex items-center justify-between p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
    >
      <div class="flex items-center gap-3 flex-1">
        <!-- Checkbox -->
        <div
          @click="handleCheckboxClick"
          class="flex-shrink-0 w-6 h-6 rounded-md border-2 flex items-center justify-center cursor-pointer transition-all"
          :class="[
            isCompleted
              ? 'bg-green-500 border-green-500'
              : 'border-gray-300 dark:border-gray-600 hover:border-green-500'
          ]"
        >
          <Icon
            v-if="isCompleted"
            icon="fluent:checkmark-24-filled"
            class="w-4 h-4 text-white"
          />
        </div>

        <!-- Topic Icon -->
        <Icon 
          icon="fluent:document-text-24-regular" 
          class="w-5 h-5 flex-shrink-0"
          :class="isCompleted ? 'text-green-600 dark:text-green-400' : 'text-gray-600 dark:text-gray-400'"
        />

        <!-- Title -->
        <h4 
          class="text-left font-medium transition-colors line-clamp-1"
          :class="[
            isCompleted 
              ? 'text-green-900 dark:text-green-100 line-through' 
              : 'text-gray-900 dark:text-white'
          ]"
        >
          {{ topic.title }}
        </h4>

        <!-- Requirement / Status Badge -->
        <div v-if="!isAdmin && topic.min_read > 0" class="hidden md:flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold whitespace-nowrap"
          :class="[
            isCompleted 
              ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' 
              : (isInProgress ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 animate-pulse' : 'bg-gray-100 text-gray-700 dark:bg-gray-700/30 dark:text-gray-400')
          ]"
        >
          <Icon :icon="isCompleted ? 'fluent:checkmark-12-filled' : 'fluent:clock-12-regular'" class="w-3 h-3" />
          {{ isCompleted ? 'อ่านจบแล้ว' : `อ่าน ${topic.min_read} น.` }}
        </div>

        <!-- Admin Actions -->
        <div v-if="isAdmin" class="flex items-center gap-1 ml-2" @click.stop>
            <button 
                @click="handleEditClick"
                class="p-1.5 text-blue-500 hover:text-blue-700 hover:bg-blue-50 rounded-full transition-colors"
                title="แก้ไข"
            >
                <Icon icon="fluent:edit-20-regular" class="w-4 h-4" />
            </button>
            <button 
                @click="handleDeleteClick"
                class="p-1.5 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-full transition-colors"
                title="ลบ"
            >
                <Icon icon="fluent:delete-20-regular" class="w-4 h-4" />
            </button>
        </div>
      </div>

      <!-- Expand Icon -->
      <Icon
        :icon="isExpanded ? 'fluent:chevron-up-24-filled' : 'fluent:chevron-down-24-filled'"
        class="w-5 h-5 text-gray-500 dark:text-gray-400 flex-shrink-0 ml-2"
      />
    </button>

    <!-- Content -->
    <div
      v-show="isExpanded"
      class="border-t border-gray-200 dark:border-gray-700"
    >
      <div class="p-4 space-y-4">
        <!-- Topic Content -->
        <div v-if="topic.content" class="prose prose-sm dark:prose-invert max-w-none">
          <RichTextViewer :content="topic.content" />
        </div>

        <!-- Video Section -->
        <div v-if="hasYoutubeVideo" class="relative rounded-xl overflow-hidden cursor-pointer group shadow border border-gray-200 dark:border-gray-700 aspect-video max-w-2xl mx-auto" @click="showVideoModal = true">
          <img
            :src="youtubeThumbnailUrl"
            :alt="topic.title"
            class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
            @error="handleThumbnailError"
          />
          <div class="absolute inset-0 flex items-center justify-center bg-black/40 group-hover:bg-black/50 transition-all">
            <div class="w-14 h-14 bg-red-600 rounded-full flex items-center justify-center shadow-lg transform transition-all group-hover:scale-110">
              <Icon icon="fluent:play-24-filled" class="w-7 h-7 text-white ml-0.5" />
            </div>
          </div>
          <div class="absolute bottom-0 left-0 right-0 p-3 bg-gradient-to-t from-black/80 to-transparent">
            <div class="flex items-center justify-between animate-fade-in">
              <span class="text-white text-sm font-medium truncate">{{ topic.title }}</span>
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-600 text-white text-xs font-semibold">
                <Icon icon="logos:youtube-icon" class="w-3 h-3" />
                YouTube
              </span>
            </div>
          </div>
        </div>

        <!-- Invalid YouTube URL Fallback -->
        <div v-else-if="isInvalidYoutubeUrl" class="p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg flex items-center justify-between gap-3 max-w-2xl mx-auto">
          <div class="flex items-center gap-2 text-yellow-800 dark:text-yellow-200 text-sm">
            <Icon icon="fluent:alert-24-regular" class="w-5 h-5 flex-shrink-0" />
            <span>รูปแบบลิงก์วิดีโอไม่ถูกต้อง</span>
          </div>
          <a
            :href="topic.youtube_url"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center gap-1.5 px-3 py-1 bg-yellow-600 hover:bg-yellow-700 text-white text-xs font-bold rounded transition-colors"
          >
            เปิดบน YouTube
            <Icon icon="fluent:open-24-regular" class="w-3.5 h-3.5" />
          </a>
        </div>

        <!-- Images Gallery -->
        <div v-if="topic.images && topic.images.length > 0" class="grid grid-cols-2 gap-2">
          <img
            v-for="(image, index) in topic.images"
            :key="image.id"
            :src="image.full_url"
            :alt="topic.title"
            class="rounded-lg object-cover w-full h-48 border border-gray-200 dark:border-gray-700 hover:scale-105 transition-transform cursor-pointer"
            @click="openTopicImage(index)"
            @error="handleImageError"
          />
        </div>

        <!-- Attachments Section -->
        <div v-if="topic.attachments && topic.attachments.length > 0" class="pt-4 border-t border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-2 mb-3">
            <Icon icon="fluent:folder-24-filled" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
            <h5 class="font-semibold text-gray-900 dark:text-white">เอกสารประกอบการเรียน</h5>
          </div>
          <LessonAttachmentList :attachments="topic.attachments" />
        </div>

        <!-- Assignments for this topic -->
        <div v-if="topic.assignments && topic.assignments.length > 0" class="pt-4 border-t border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-2 mb-3">
            <Icon icon="fluent:clipboard-task-24-filled" class="w-5 h-5 text-green-600 dark:text-green-400" />
            <h5 class="font-semibold text-gray-900 dark:text-white">แบบฝึกหัดในหัวข้อนี้</h5>
          </div>
          <div class="space-y-2">
            <div
              v-for="assignment in topic.assignments"
              :key="assignment.id"
              class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800"
            >
              <p class="text-sm font-medium text-gray-900 dark:text-white">
                {{ assignment.title }}
              </p>
            </div>
          </div>
        </div>

        <!-- Questions for this topic -->
        <div v-if="topic.questions && topic.questions.length > 0" class="pt-4 border-t border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-2 mb-3">
            <Icon icon="fluent:quiz-new-24-filled" class="w-5 h-5 text-orange-600 dark:text-orange-400" />
            <h5 class="font-semibold text-gray-900 dark:text-white">คำถามในหัวข้อนี้</h5>
          </div>
          <p class="text-sm text-gray-600 dark:text-gray-400">
            มี {{ topic.questions.length }} คำถาม
          </p>
        </div>
      </div>
    </div>

    <!-- Topic Image Gallery Modal -->
    <ImageGalleryModal
      :show="showImageGallery"
      :images="topic.images || []"
      :start-index="galleryStartIndex"
      :title="topic.title"
      @close="showImageGallery = false"
    />

    <!-- Topic Video Modal -->
    <VideoModal
      v-if="showVideoModal"
      :youtube-url="topic.youtube_url"
      :title="topic.title"
      @close="showVideoModal = false"
    />
  </div>
</template>
