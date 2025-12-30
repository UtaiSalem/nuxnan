<script setup lang="ts">
import { ref } from 'vue'
import { Icon } from '@iconify/vue'
import RichTextViewer from '~/components/RichTextViewer.vue'

interface Props {
  topic: any
  isCompleted?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isCompleted: false
})

const emit = defineEmits<{
  'toggleComplete': [topicId: number]
}>()

const isExpanded = ref(false)

const toggleExpand = () => {
  isExpanded.value = !isExpanded.value
}

const handleCheckboxClick = (e: Event) => {
  e.stopPropagation()
  emit('toggleComplete', props.topic.id)
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
          class="text-left font-medium transition-colors"
          :class="[
            isCompleted 
              ? 'text-green-900 dark:text-green-100 line-through' 
              : 'text-gray-900 dark:text-white'
          ]"
        >
          {{ topic.title }}
        </h4>
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
            v-for="image in topic.images"
            :key="image.id"
            :src="image.full_url"
            :alt="topic.title"
            class="rounded-lg object-cover w-full h-48 border border-gray-200 dark:border-gray-700 hover:scale-105 transition-transform cursor-pointer"
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
  </div>
</template>
