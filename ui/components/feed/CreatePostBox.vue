<script setup>
/**
 * CreatePostBox - Wrapper component that combines trigger and modal
 * Usage: <CreatePostBox @post-created="handlePostCreated" />
 */
import { ref } from 'vue'
import CreatePostTrigger from './CreatePostTrigger.vue'
import CreatePostModal from './CreatePostModal.vue'

const emit = defineEmits(['post-created'])

const showModal = ref(false)

const openModal = () => {
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
    
    <!-- Modal (Teleported to body) -->
    <CreatePostModal 
      :show="showModal" 
      @close="closeModal" 
      @post-created="handlePostCreated" 
    />
  </div>
</template>
