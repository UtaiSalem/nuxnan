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
import { ref, watch } from 'vue'
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
  },
  postedAsGroupId: {
    type: Number,
    default: null
  },
  lockedGroupId: {
    type: Number,
    default: null
  },
  isAcademyAdmin: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['post-created'])

const showModal = ref(false)
const initialTab = ref('status')
const internalPostedAsGroupId = ref(props.postedAsGroupId ?? props.lockedGroupId ?? null)

// Sync with props
watch(() => props.postedAsGroupId, (v) => {
  internalPostedAsGroupId.value = v ?? props.lockedGroupId ?? null
})

watch(() => props.lockedGroupId, (v) => {
  if (v != null) {
    internalPostedAsGroupId.value = v
  }
})

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
    <CreatePostTrigger 
      :context="context"
      :context-id="contextId"
      :locked-group-id="lockedGroupId"
      v-model:posted-as-group-id="internalPostedAsGroupId"
      @open-modal="openModal" 
    />
    
    <!-- Post/Poll Modal (Teleported to body) -->
    <CreatePostModal 
      :show="showModal" 
      :initial-tab="initialTab"
      :context="context"
      :context-id="contextId"
      :context-name="contextName"
      :posted-as-group-id="internalPostedAsGroupId"
      :locked-group-id="lockedGroupId"
      :is-academy-admin="isAcademyAdmin"
      @close="closeModal" 
      @post-created="handlePostCreated" 
    />
  </div>
</template>
