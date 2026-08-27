<script setup lang="ts">
import { Icon } from '@iconify/vue'
import CourseRatingStars from './CourseRatingStars.vue'

const props = withDefaults(defineProps<{
  courseId: number
  existingReview?: {
    id: number
    rating: number
    title: string | null
    content: string | null
  } | null
}>(), {
  existingReview: null
})

const emit = defineEmits<{
  (e: 'submitted', review: any): void
  (e: 'cancel'): void
}>()

const api = useApi()

const rating = ref(props.existingReview?.rating || 0)
const title = ref(props.existingReview?.title || '')
const content = ref(props.existingReview?.content || '')
const isSubmitting = ref(false)
const errorMessage = ref('')

const isEditing = computed(() => !!props.existingReview)

const canSubmit = computed(() => rating.value >= 1 && rating.value <= 5)

const handleRating = (value: number) => {
  rating.value = value
}

const submitReview = async () => {
  if (!canSubmit.value) return
  
  isSubmitting.value = true
  errorMessage.value = ''
  
  try {
    const response = await api.post(`/api/courses/${props.courseId}/reviews`, {
      rating: rating.value,
      title: title.value || null,
      content: content.value || null,
    }) as { success: boolean; review?: any; message?: string }
    
    if (response.success) {
      emit('submitted', response.review)
    } else {
      errorMessage.value = response.message || 'ไม่สามารถบันทึกรีวิวได้'
    }
  } catch (err: any) {
    errorMessage.value = err.data?.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่'
  } finally {
    isSubmitting.value = false
  }
}

const cancel = () => {
  emit('cancel')
}
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
      {{ isEditing ? 'แก้ไขรีวิว' : 'เขียนรีวิว' }}
    </h4>
    
    <!-- Rating Selection -->
    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        ให้คะแนน <span class="text-red-500">*</span>
      </label>
      <div class="flex items-center gap-2">
        <CourseRatingStars
          :rating="rating"
          size="lg"
          :interactive="true"
          @rate="handleRating"
        />
        <span v-if="rating > 0" class="text-lg font-semibold text-gray-700 dark:text-gray-300 ml-2">
          {{ rating }}/5
        </span>
      </div>
    </div>
    
    <!-- Title -->
    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        หัวข้อรีวิว (ไม่บังคับ)
      </label>
      <input
        v-model="title"
        type="text"
        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        placeholder="สรุปความคิดเห็นของคุณ..."
        maxlength="255"
      />
    </div>
    
    <!-- Content -->
    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        รายละเอียด (ไม่บังคับ)
      </label>
      <textarea
        v-model="content"
        rows="4"
        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
        placeholder="แชร์ประสบการณ์การเรียนของคุณ..."
        maxlength="2000"
      ></textarea>
      <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
        {{ content.length }}/2000
      </p>
    </div>
    
    <!-- Error Message -->
    <div v-if="errorMessage" class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
      <p class="text-sm text-red-600 dark:text-red-400">{{ errorMessage }}</p>
    </div>
    
    <!-- Actions -->
    <div class="flex items-center justify-end gap-3">
      <button
        type="button"
        @click="cancel"
        :disabled="isSubmitting"
        class="min-h-[44px] sm:min-h-0 px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors disabled:opacity-50"
      >
        ยกเลิก
      </button>
      <button
        type="button"
        @click="submitReview"
        :disabled="!canSubmit || isSubmitting"
        class="min-h-[44px] sm:min-h-0 px-6 py-2 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
      >
        <Icon v-if="isSubmitting" icon="svg-spinners:ring-resize" class="w-4 h-4" />
        <Icon v-else icon="fluent:send-24-regular" class="w-4 h-4" />
        {{ isEditing ? 'อัปเดตรีวิว' : 'ส่งรีวิว' }}
      </button>
    </div>
  </div>
</template>
