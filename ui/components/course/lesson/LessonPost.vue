<script setup lang="ts">
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'
import RichTextViewer from '~/components/RichTextViewer.vue'
import TopicAccordion from './TopicAccordion.vue'
import LessonInteractionBar from './LessonInteractionBar.vue'
import AssignmentPreview from './AssignmentPreview.vue'
import QuizPreview from './QuizPreview.vue'
import LessonCommentSection from './LessonCommentSection.vue'

interface Props {
  lesson: any
  isAdmin?: boolean
  currentUser?: any
}

const props = withDefaults(defineProps<Props>(), {
  isAdmin: false,
})

const emit = defineEmits<{
  edit: [lesson: any]
  delete: [lessonId: number]
  like: [lessonId: number]
  dislike: [lessonId: number]
  bookmark: [lessonId: number]
  share: [lesson: any]
  comment: [lessonId: number]
}>()

// State
const showFullContent = ref(false)
const showTopics = ref(false)
const completedTopics = ref<number[]>([]) // Track completed topic IDs
const showComments = ref(false) // Toggle comments section
const localCommentCount = ref(props.lesson.comment_count || 0)
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
      <!-- Cover Image/Video -->
      <div
        class="relative h-64 bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 dark:from-blue-900/20 dark:via-purple-900/20 dark:to-pink-900/20 overflow-hidden rounded-t-2xl"
      >
        <!-- If has image -->
        <img
          v-if="lesson.images && lesson.images[0]"
          :src="lesson.images[0].full_url"
          :alt="lesson.title"
          class="w-full h-full object-cover"
        />

        <!-- If has YouTube video -->
        <div v-else-if="lesson.youtube_url" class="w-full h-full">
          <iframe
            :src="`https://www.youtube.com/embed/${lesson.youtube_url}`"
            class="w-full h-full"
            frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen
          ></iframe>
        </div>

        <!-- Default gradient with animated icon -->
        <div
          v-else
          class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-100 via-purple-100 to-pink-100 dark:from-blue-900/30 dark:via-purple-900/30 dark:to-pink-900/30"
        >
          <Icon
            icon="fluent:book-24-filled"
            class="w-32 h-32 text-blue-400/40 dark:text-blue-300/30 animate-pulse"
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
          :class="[
            'prose prose-blue dark:prose-invert max-w-none transition-all duration-300',
            !showFullContent && 'max-h-96 overflow-hidden',
          ]"
        >
          <RichTextViewer :content="lesson.content" />
        </div>

        <!-- Read More/Less Button -->
        <div
          v-if="!showFullContent"
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

        <button
          v-if="showFullContent"
          @click="toggleContent"
          class="mt-4 w-full px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors font-medium"
        >
          ย่อเนื้อหา
          <Icon icon="fluent:chevron-up-24-regular" class="w-4 h-4 inline ml-1" />
        </button>
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

      <!-- Assignments Preview -->
      <div v-if="hasAssignments" class="space-y-3">
        <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white">
          <Icon
            icon="fluent:clipboard-task-24-filled"
            class="w-6 h-6 text-green-600 dark:text-green-400"
          />
          แบบฝึกหัด
        </h3>
        <AssignmentPreview
          v-for="assignment in lesson.assignments"
          :key="assignment.id"
          :assignment="assignment"
        />
      </div>

      <!-- Quizzes Preview -->
      <div v-if="hasQuestions" class="space-y-3">
        <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white">
          <Icon
            icon="fluent:quiz-new-24-filled"
            class="w-6 h-6 text-orange-600 dark:text-orange-400"
          />
          แบบทดสอบ
        </h3>
        <QuizPreview :lesson-id="lesson.id" :question-count="lesson.questions.length" />
      </div>

      <!-- Interaction Bar -->
      <LessonInteractionBar
        :lesson="lesson"
        @like="$emit('like', lesson.id)"
        @dislike="$emit('dislike', lesson.id)"
        @bookmark="$emit('bookmark', lesson.id)"
        @share="$emit('share', lesson)"
        @comment="showComments = !showComments"
      />

      <!-- Comments Section -->
      <LessonCommentSection
        v-if="showComments"
        :lessonId="lesson.id"
        :initialComments="lesson.comments || []"
        :commentCount="localCommentCount"
        @update:commentCount="localCommentCount = $event"
      />
    </div>
  </article>
</template>
