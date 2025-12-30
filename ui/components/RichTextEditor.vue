<script setup lang="ts">
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { Icon } from '@iconify/vue'

interface Props {
  modelValue: string
  placeholder?: string
  disabled?: boolean
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'เริ่มเขียนเนื้อหา...',
  disabled: false,
  class: ''
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

// Editor state
const editorRef = ref<HTMLDivElement>()
const content = ref(props.modelValue || '')
const isBold = ref(false)
const isItalic = ref(false)
const isUnderline = ref(false)

// Update content when modelValue changes
watch(() => props.modelValue, (newValue) => {
  if (newValue !== content.value && editorRef.value) {
    content.value = newValue || ''
    editorRef.value.innerHTML = content.value
  }
})

// Initialize editor
onMounted(() => {
  if (editorRef.value) {
    editorRef.value.innerHTML = content.value
  }
})

// Handle input
const handleInput = () => {
  if (editorRef.value) {
    content.value = editorRef.value.innerHTML
    emit('update:modelValue', content.value)
  }
}

// Format commands
const execCommand = (command: string, value: string | undefined = undefined) => {
  document.execCommand(command, false, value)
  editorRef.value?.focus()
  updateButtonStates()
}

// Update button states based on current selection
const updateButtonStates = () => {
  isBold.value = document.queryCommandState('bold')
  isItalic.value = document.queryCommandState('italic')
  isUnderline.value = document.queryCommandState('underline')
}

// Handle selection change
const handleSelectionChange = () => {
  updateButtonStates()
}

onMounted(() => {
  document.addEventListener('selectionchange', handleSelectionChange)
})

onBeforeUnmount(() => {
  document.removeEventListener('selectionchange', handleSelectionChange)
})
</script>

<template>
  <div :class="['rich-text-editor', props.class]">
    <!-- Toolbar -->
    <div v-if="!disabled" class="toolbar bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-t-xl p-2 flex flex-wrap gap-1">
      <!-- Text Formatting -->
      <div class="flex gap-1 border-r border-gray-300 dark:border-gray-600 pr-2">
        <button
          type="button"
          @click="execCommand('bold')"
          :class="['toolbar-btn', isBold && 'active']"
          title="Bold (Ctrl+B)"
        >
          <Icon icon="fluent:text-bold-24-regular" class="w-4 h-4" />
        </button>
        <button
          type="button"
          @click="execCommand('italic')"
          :class="['toolbar-btn', isItalic && 'active']"
          title="Italic (Ctrl+I)"
        >
          <Icon icon="fluent:text-italic-24-regular" class="w-4 h-4" />
        </button>
        <button
          type="button"
          @click="execCommand('underline')"
          :class="['toolbar-btn', isUnderline && 'active']"
          title="Underline (Ctrl+U)"
        >
          <Icon icon="fluent:text-underline-24-regular" class="w-4 h-4" />
        </button>
        <button
          type="button"
          @click="execCommand('strikeThrough')"
          class="toolbar-btn"
          title="Strikethrough"
        >
          <Icon icon="fluent:text-strikethrough-24-regular" class="w-4 h-4" />
        </button>
      </div>

      <!-- Headings -->
      <div class="flex gap-1 border-r border-gray-300 dark:border-gray-600 pr-2">
        <button
          type="button"
          @click="execCommand('formatBlock', 'h1')"
          class="toolbar-btn"
          title="Heading 1"
        >
          H1
        </button>
        <button
          type="button"
          @click="execCommand('formatBlock', 'h2')"
          class="toolbar-btn"
          title="Heading 2"
        >
          H2
        </button>
        <button
          type="button"
          @click="execCommand('formatBlock', 'h3')"
          class="toolbar-btn"
          title="Heading 3"
        >
          H3
        </button>
        <button
          type="button"
          @click="execCommand('formatBlock', 'p')"
          class="toolbar-btn"
          title="Paragraph"
        >
          P
        </button>
      </div>

      <!-- Lists -->
      <div class="flex gap-1 border-r border-gray-300 dark:border-gray-600 pr-2">
        <button
          type="button"
          @click="execCommand('insertUnorderedList')"
          class="toolbar-btn"
          title="Bullet List"
        >
          <Icon icon="fluent:text-bullet-list-24-regular" class="w-4 h-4" />
        </button>
        <button
          type="button"
          @click="execCommand('insertOrderedList')"
          class="toolbar-btn"
          title="Numbered List"
        >
          <Icon icon="fluent:text-number-list-ltr-24-regular" class="w-4 h-4" />
        </button>
      </div>

      <!-- Alignment -->
      <div class="flex gap-1 border-r border-gray-300 dark:border-gray-600 pr-2">
        <button
          type="button"
          @click="execCommand('justifyLeft')"
          class="toolbar-btn"
          title="Align Left"
        >
          <Icon icon="fluent:text-align-left-24-regular" class="w-4 h-4" />
        </button>
        <button
          type="button"
          @click="execCommand('justifyCenter')"
          class="toolbar-btn"
          title="Align Center"
        >
          <Icon icon="fluent:text-align-center-24-regular" class="w-4 h-4" />
        </button>
        <button
          type="button"
          @click="execCommand('justifyRight')"
          class="toolbar-btn"
          title="Align Right"
        >
          <Icon icon="fluent:text-align-right-24-regular" class="w-4 h-4" />
        </button>
      </div>

      <!-- Insert -->
      <div class="flex gap-1">
        <button
          type="button"
          @click="execCommand('createLink', prompt('Enter URL:') || undefined)"
          class="toolbar-btn"
          title="Insert Link"
        >
          <Icon icon="fluent:link-24-regular" class="w-4 h-4" />
        </button>
        <button
          type="button"
          @click="execCommand('unlink')"
          class="toolbar-btn"
          title="Remove Link"
        >
          <Icon icon="fluent:link-dismiss-24-regular" class="w-4 h-4" />
        </button>
      </div>

      <!-- Clear Formatting -->
      <div class="flex gap-1 ml-auto">
        <button
          type="button"
          @click="execCommand('removeFormat')"
          class="toolbar-btn"
          title="Clear Formatting"
        >
          <Icon icon="fluent:text-clear-formatting-24-regular" class="w-4 h-4" />
        </button>
      </div>
    </div>

    <!-- Editor -->
    <div
      ref="editorRef"
      contenteditable="true"
      @input="handleInput"
      :class="[
        'editor-content',
        'min-h-[300px] max-h-[600px] overflow-y-auto',
        'p-4 bg-white dark:bg-gray-900',
        'border border-gray-300 dark:border-gray-600',
        disabled ? 'rounded-xl' : 'rounded-b-xl border-t-0',
        'focus:outline-none focus:ring-2 focus:ring-blue-500',
        'prose prose-lg dark:prose-invert max-w-none'
      ]"
      :data-placeholder="placeholder"
    />
  </div>
</template>

<style scoped>
.toolbar-btn {
  @apply px-2 py-1.5 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors font-medium text-sm;
}

.toolbar-btn.active {
  @apply bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400;
}

.editor-content:empty:before {
  content: attr(data-placeholder);
  @apply text-gray-400 dark:text-gray-500;
}

/* Prose styles for editor */
:deep(.editor-content) {
  @apply text-gray-800 dark:text-gray-200;
}

:deep(.editor-content h1) {
  @apply text-3xl font-bold text-gray-900 dark:text-white mb-4 mt-6;
}

:deep(.editor-content h2) {
  @apply text-2xl font-bold text-gray-900 dark:text-white mb-3 mt-5;
}

:deep(.editor-content h3) {
  @apply text-xl font-bold text-gray-900 dark:text-white mb-2 mt-4;
}

:deep(.editor-content p) {
  @apply mb-4 leading-relaxed;
}

:deep(.editor-content a) {
  @apply text-blue-600 dark:text-blue-400 underline hover:text-blue-700 dark:hover:text-blue-300;
}

:deep(.editor-content strong) {
  @apply font-bold;
}

:deep(.editor-content em) {
  @apply italic;
}

:deep(.editor-content u) {
  @apply underline;
}

:deep(.editor-content ul) {
  @apply list-disc list-inside mb-4 space-y-1;
}

:deep(.editor-content ol) {
  @apply list-decimal list-inside mb-4 space-y-1;
}

:deep(.editor-content li) {
  @apply ml-4;
}
</style>
