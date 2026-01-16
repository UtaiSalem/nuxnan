<script setup>
/**
 * CreatePostBox - Wrapper component that combines trigger and modal
 * Usage: <CreatePostBox @post-created="handlePostCreated" />
 * 
 * Props:
 * - context: 'newsfeed' | 'academy' | 'course' - determines where the post will be created
 * - contextId: number - the ID of the academy/course if context is not 'newsfeed'
 * - contextName: string - the name of the context (e.g., academy name) for display
 */
import { ref } from 'vue'
import CreatePostTrigger from './CreatePostTrigger.vue'
import CreatePostModal from './CreatePostModal.vue'

const props = defineProps({
  context: {
    type: String,
    default: 'newsfeed', // 'newsfeed', 'academy', 'course'
    validator: (value) => ['newsfeed', 'academy', 'course'].includes(value)
  },
  contextId: {
    type: Number,
    default: null
  },
  contextName: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['post-created'])

const showModal = ref(false)
const initialTab = ref('status')

const openModal = (tab = 'status') => {
  initialTab.value = tab
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
}

const handlePostCreated = (activity) => {
  emit('post-created', activity)
  closeModal()
}
</script>

<template>
  <div class="contents">
    <!-- Trigger Box -->
    <CreatePostTrigger @open-modal="openModal" />
    
    <!-- Post/Poll Modal (Teleported to body) -->
    <CreatePostModal 
      :show="showModal" 
      :initial-tab="initialTab"
      :context="context"
      :context-id="contextId"
      :context-name="contextName"
      @close="closeModal" 
      @post-created="handlePostCreated" 
    />
  </div>
</template>
