<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRichText } from '~/composables/useRichText'
import { Icon } from '@iconify/vue'

interface Props {
  content: string | null | undefined
  collapsedHeight?: string // e.g., '200px', '12rem'
  canExpand?: boolean
  initialExpanded?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  content: '',
  collapsedHeight: '300px',
  canExpand: true,
  initialExpanded: false
})

const { convertPlainTextToHtml, sanitizeHtml } = useRichText()

const isExpanded = ref(props.initialExpanded)
const contentRef = ref<HTMLElement | null>(null)
const shouldShowReadMore = ref(false)

const sanitizedContent = computed(() => {
  const html = convertPlainTextToHtml(props.content)
  const sanitized = sanitizeHtml(html)
  
  // Add sandbox to iframes that don't already have it (avoids duplicate attribute)
  const sandboxValue = 'allow-forms allow-scripts allow-popups allow-popups-to-escape-sandbox allow-presentation'
  return sanitized.replace(/<iframe(?![^>]*\ssandbox)/gi, `<iframe sandbox="${sandboxValue}"`)
})

const checkHeight = () => {
  if (contentRef.value && props.canExpand) {
    const height = contentRef.value.scrollHeight
    const limit = parseInt(props.collapsedHeight) || 300
    shouldShowReadMore.value = height > limit
  }
}

onMounted(() => {
  setTimeout(checkHeight, 100) // Small delay to ensure rendering
})

watch(() => props.content, () => {
  setTimeout(checkHeight, 100)
})

const toggleExpand = () => {
  isExpanded.value = !isExpanded.value
}
</script>

<template>
  <div class="rich-text-viewer relative">
    <div 
      ref="contentRef"
      class="text-gray-600 dark:text-gray-400 leading-relaxed prose dark:prose-invert max-w-none break-words [overflow-wrap:anywhere] overflow-hidden transition-all duration-300
        [&_table]:block [&_table]:w-full [&_table]:overflow-x-auto [&_table]:border-collapse [&_table]:mb-4
        [&_thead]:bg-gray-50 [&_thead]:dark:bg-gray-800/50
        [&_th]:px-4 [&_th]:py-2 [&_th]:border [&_th]:border-gray-200 [&_th]:dark:border-gray-700 [&_th]:text-left
        [&_td]:px-4 [&_td]:py-2 [&_td]:border [&_td]:border-gray-200 [&_td]:dark:border-gray-700
        [&_img]:max-w-full [&_img]:h-auto [&_img]:rounded-xl [&_img]:mx-auto [&_img]:my-4
        [&_iframe]:max-w-full [&_iframe]:aspect-video [&_iframe]:rounded-xl [&_iframe]:w-full [&_iframe]:my-4
        [&_video]:max-w-full [&_video]:h-auto [&_video]:rounded-xl [&_video]:w-full [&_video]:my-4
        [&_p]:mb-4 [&_ul]:list-disc [&_ul]:ml-6 [&_ul]:mb-4 [&_ol]:list-decimal [&_ol]:ml-6 [&_ol]:mb-4
        [&_a]:text-blue-500 [&_a]:underline [&_a]:break-all"
      :style="shouldShowReadMore && !isExpanded ? { maxHeight: props.collapsedHeight } : {}"
    >
      <div v-if="sanitizedContent" v-html="sanitizedContent"></div>
      <p v-else class="text-gray-400 italic">ไม่มีรายละเอียด</p>
    </div>

    <!-- Read More Overlay -->
    <div 
      v-if="shouldShowReadMore && !isExpanded" 
      class="absolute bottom-0 left-0 right-0 h-24 bg-gradient-to-t from-white dark:from-gray-800 to-transparent pointer-events-none"
    ></div>

    <!-- Read More Button -->
    <div v-if="shouldShowReadMore" class="mt-4 flex justify-center">
      <button 
        @click="toggleExpand"
        class="flex items-center gap-2 px-4 py-2 text-sm font-semibold text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-full transition-colors border border-blue-200 dark:border-blue-800 shadow-sm"
      >
        <template v-if="isExpanded">
          <Icon icon="fluent:chevron-up-24-regular" class="w-4 h-4" />
          ย่อรายละเอียด
        </template>
        <template v-else>
          <Icon icon="fluent:chevron-down-24-regular" class="w-4 h-4" />
          ดูรายละเอียดเพิ่มเติม
        </template>
      </button>
    </div>
  </div>
</template>

<style scoped>
/* TipTap Specific Styles that might not be in standard prose */
:deep(ul[data-type="taskList"]) {
  list-style: none;
  padding: 0;
}

:deep(ul[data-type="taskList"] li) {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

:deep(ul[data-type="taskList"] li > label) {
  flex-shrink: 0;
  margin-top: 0.25rem;
}

:deep(ul[data-type="taskList"] li > label input[type="checkbox"]) {
  width: 1rem;
  height: 1rem;
  accent-color: #3b82f6;
  cursor: pointer;
}

:deep(ul[data-type="taskList"] li > div) {
  flex: 1;
}
</style>
