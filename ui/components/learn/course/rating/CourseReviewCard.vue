<script setup lang="ts">
import { Icon } from '@iconify/vue'
import CourseRatingStars from './CourseRatingStars.vue'

interface Review {
  id: number
  user_id: number
  rating: number
  title: string | null
  content: string | null
  created_at_formatted: string
  user: {
    id: number
    name: string
    avatar: string | null
    reference_code: string
  }
  is_own: boolean
}

const props = defineProps<{
  review: Review
}>()

const emit = defineEmits<{
  (e: 'edit', review: Review): void
  (e: 'delete', reviewId: number): void
}>()

const isDeleting = ref(false)
const showDeleteConfirm = ref(false)

const getAvatarUrl = (avatar: string | null) => {
  if (!avatar) return '/images/default-avatar.png'
  if (avatar.startsWith('http')) return avatar
  return `${useRuntimeConfig().public.apiBase}/storage/${avatar}`
}

const handleEdit = () => {
  emit('edit', props.review)
}

const confirmDelete = () => {
  showDeleteConfirm.value = true
}

const cancelDelete = () => {
  showDeleteConfirm.value = false
}

const handleDelete = async () => {
  isDeleting.value = true
  emit('delete', props.review.id)
}
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
    <!-- Header -->
    <div class="flex items-start justify-between mb-3">
      <div class="flex items-center gap-3">
        <img
          :src="getAvatarUrl(review.user?.avatar)"
          :alt="review.user?.name"
          class="w-10 h-10 rounded-full object-cover border-2 border-gray-200 dark:border-gray-600"
        />
        <div>
          <p class="font-medium text-gray-900 dark:text-white">
            {{ review.user?.name || 'ผู้ใช้งาน' }}
          </p>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ review.created_at_formatted }}
          </p>
        </div>
      </div>
      
      <!-- Actions for review owner -->
      <div v-if="review.is_own" class="flex items-center gap-1">
        <button
          @click="handleEdit"
          class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 text-gray-500 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
          title="แก้ไข"
        >
          <Icon icon="fluent:edit-24-regular" class="w-4 h-4" />
        </button>
        <button
          @click="confirmDelete"
          class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 text-gray-500 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
          title="ลบ"
        >
          <Icon icon="fluent:delete-24-regular" class="w-4 h-4" />
        </button>
      </div>
    </div>
    
    <!-- Rating -->
    <div class="mb-3">
      <CourseRatingStars
        :rating="review.rating"
        size="sm"
      />
    </div>
    
    <!-- Title -->
    <h4 v-if="review.title" class="font-semibold text-gray-900 dark:text-white mb-2">
      {{ review.title }}
    </h4>
    
    <!-- Content -->
    <p v-if="review.content" class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
      {{ review.content }}
    </p>
    
    <!-- Delete Confirmation Modal -->
    <Teleport to="body">
      <div 
        v-if="showDeleteConfirm" 
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
        @click.self="cancelDelete"
      >
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-6 max-w-sm w-full shadow-xl">
          <div class="text-center mb-4">
            <Icon icon="fluent:warning-24-regular" class="w-12 h-12 text-yellow-500 mx-auto mb-3" />
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              ยืนยันการลบรีวิว?
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
              การดำเนินการนี้ไม่สามารถยกเลิกได้
            </p>
          </div>
          <div class="flex gap-3">
            <button
              @click="cancelDelete"
              :disabled="isDeleting"
              class="min-h-[44px] sm:min-h-0 flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors disabled:opacity-50"
            >
              ยกเลิก
            </button>
            <button
              @click="handleDelete"
              :disabled="isDeleting"
              class="min-h-[44px] sm:min-h-0 flex-1 px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
            >
              <Icon v-if="isDeleting" icon="svg-spinners:ring-resize" class="w-4 h-4" />
              ลบรีวิว
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
