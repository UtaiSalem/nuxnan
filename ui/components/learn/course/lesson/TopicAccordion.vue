<script setup lang="ts">
import { ref } from 'vue'
import { Icon } from '@iconify/vue'
import RichTextViewer from '~/components/RichTextViewer.vue'
import ImageGalleryModal from '~/components/ImageGalleryModal.vue'

interface Props {
  topic: any
  isCompleted?: boolean
  isAdmin?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isCompleted: false,
  isAdmin: false
})

const emit = defineEmits<{
  'toggleComplete': [topicId: number]
  'edit': [topic: any]
  'delete': [topic: any]
}>()

const isExpanded = ref(false)

// Image gallery
const showImageGallery = ref(false)
const galleryStartIndex = ref(0)
const openTopicImage = (index: number) => {
  galleryStartIndex.value = index
  showImageGallery.value = true
}

const toggleExpand = () => {
  isExpanded.value = !isExpanded.value
}

const handleCheckboxClick = (e: Event) => {
  e.stopPropagation()
  emit('toggleComplete', props.topic.id)
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
  </div>
</template>
