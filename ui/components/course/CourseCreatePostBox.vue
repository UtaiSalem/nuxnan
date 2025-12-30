<script setup>
/**
 * CourseCreatePostBox - Wrapper component for creating posts in a course
 * Usage: <CourseCreatePostBox :course-id="courseId" @post-created="handlePostCreated" />
 */
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'
import CourseCreatePostModal from './CourseCreatePostModal.vue'

const props = defineProps({
  courseId: {
    type: [String, Number],
    required: true
  }
})

const emit = defineEmits(['post-created'])

const { user } = useAuth()
const { getAvatarUrl } = useAvatar()
const showModal = ref(false)

// User avatar with fallback
const userAvatar = computed(() => getAvatarUrl(user.value))

const openModal = () => {
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
}

const handlePostCreated = (post) => {
  emit('post-created', post)
  closeModal()
}
</script>

<template>
  <div class="bg-white dark:bg-vikinger-dark-100 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-vikinger-dark-50/20">
    <div class="flex items-center gap-3 mb-3">
      <img 
        :src="userAvatar" 
        alt="Avatar" 
        class="w-10 h-10 rounded-full object-cover ring-2 ring-vikinger-purple/20"
      />
      <button
        @click="openModal"
        class="flex-1 text-left px-4 py-2.5 bg-gray-100 dark:bg-vikinger-dark-200/50 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-vikinger-dark-200 transition-colors text-sm"
      >
        โพสต์อะไรในรายวิชานี้...
      </button>
    </div>

    <div class="flex items-center justify-around pt-2 border-t border-gray-100 dark:border-vikinger-dark-50/20">
      <button 
        @click="openModal" 
        class="flex items-center gap-2 px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200/50 rounded-lg transition-colors"
      >
        <Icon icon="fluent:image-24-regular" class="w-5 h-5 text-green-500" />
        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">รูปภาพ</span>
      </button>
      <button 
        @click="openModal" 
        class="flex items-center gap-2 px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200/50 rounded-lg transition-colors"
      >
        <Icon icon="fluent:attach-24-regular" class="w-5 h-5 text-blue-500" />
        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">ไฟล์แนบ</span>
      </button>
      <button 
        @click="openModal" 
        class="flex items-center gap-2 px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200/50 rounded-lg transition-colors"
      >
        <Icon icon="fluent:poll-24-regular" class="w-5 h-5 text-amber-500" />
        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">โพล</span>
      </button>
    </div>
    
    <!-- Modal (Teleported to body) -->
    <CourseCreatePostModal 
      :show="showModal"
      :course-id="courseId"
      @close="closeModal" 
      @post-created="handlePostCreated" 
    />
  </div>
</template>
