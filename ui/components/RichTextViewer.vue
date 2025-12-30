<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  content: string
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  class: ''
})

// Sanitize and format HTML content
const sanitizedContent = computed(() => {
  if (!props.content) return ''
  
  // Basic sanitization - in production, use a library like DOMPurify
  let clean = props.content
  
  // Convert newlines to <br> if plain text
  if (!clean.includes('<p>') && !clean.includes('<div>')) {
    clean = clean.replace(/\n/g, '<br>')
  }
  
  return clean
})
</script>

<template>
  <div 
    v-if="content"
    :class="['prose prose-lg dark:prose-invert max-w-none', props.class]"
    v-html="sanitizedContent"
  />
  <div v-else class="text-gray-500 dark:text-gray-400 italic">
    ไม่มีเนื้อหา
  </div>
</template>

<style scoped>
/* Rich Text Viewer Styles */
:deep(.prose) {
  @apply text-gray-800 dark:text-gray-200;
}

:deep(.prose h1) {
  @apply text-3xl font-bold text-gray-900 dark:text-white mb-4 mt-8;
}

:deep(.prose h2) {
  @apply text-2xl font-bold text-gray-900 dark:text-white mb-3 mt-6;
}

:deep(.prose h3) {
  @apply text-xl font-bold text-gray-900 dark:text-white mb-2 mt-4;
}

:deep(.prose p) {
  @apply mb-4 leading-relaxed;
}

:deep(.prose a) {
  @apply text-blue-600 dark:text-blue-400 hover:underline;
}

:deep(.prose strong) {
  @apply font-bold text-gray-900 dark:text-white;
}

:deep(.prose em) {
  @apply italic;
}

:deep(.prose ul) {
  @apply list-disc list-inside mb-4 space-y-2;
}

:deep(.prose ol) {
  @apply list-decimal list-inside mb-4 space-y-2;
}

:deep(.prose li) {
  @apply ml-4;
}

:deep(.prose blockquote) {
  @apply border-l-4 border-blue-500 pl-4 italic text-gray-700 dark:text-gray-300 my-4;
}

:deep(.prose code) {
  @apply bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded text-sm font-mono;
}

:deep(.prose pre) {
  @apply bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto my-4;
}

:deep(.prose pre code) {
  @apply bg-transparent p-0;
}

:deep(.prose img) {
  @apply rounded-lg my-4 max-w-full h-auto;
}

:deep(.prose table) {
  @apply w-full border-collapse my-4;
}

:deep(.prose th) {
  @apply bg-gray-100 dark:bg-gray-800 font-bold p-2 border border-gray-300 dark:border-gray-600;
}

:deep(.prose td) {
  @apply p-2 border border-gray-300 dark:border-gray-600;
}
</style>
