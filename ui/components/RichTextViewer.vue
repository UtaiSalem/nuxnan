<script setup lang="ts">
import { computed } from 'vue'
import { useRichText } from '~/composables/useRichText'

interface Props {
  content: string
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  class: ''
})

const { sanitizeHtml, wrapTablesForScroll } = useRichText()

/** ให้สคริปต์ในเอมเบดเข้าถึงหน้าหลักไม่ได้ (ค่าเดียวกับ Common/RichTextViewer) */
const IFRAME_SANDBOX = 'allow-forms allow-scripts allow-popups allow-popups-to-escape-sandbox allow-presentation'

// Sanitize and format HTML content
const sanitizedContent = computed(() => {
  if (!props.content) return ''

  let clean = props.content

  // Convert newlines to <br> if plain text
  if (!clean.includes('<p>') && !clean.includes('<div>')) {
    clean = clean.replace(/\n/g, '<br>')
  }

  // เนื้อหานี้ถูกยัดลงหน้าด้วย v-html ⇒ ต้องผ่าน DOMPurify ก่อนเสมอ
  // (ตัวเดียวกับที่ Common/RichTextViewer ใช้ — มีด่านสำรองตอน SSR ในตัวแล้ว)
  clean = sanitizeHtml(clean)

  // iframe ที่ยังไม่มี sandbox ให้ใส่ให้ (เลี่ยงแอตทริบิวต์ซ้ำ)
  clean = clean.replace(/<iframe(?![^>]*\ssandbox)/gi, `<iframe sandbox="${IFRAME_SANDBOX}"`)

  // ตารางกว้างต้องเลื่อนในกล่องตัวเอง ไม่ใช่ดันทั้งหน้า
  return wrapTablesForScroll(clean)
})
</script>

<template>
  <div
    v-if="content"
    :class="['prose prose-lg dark:prose-invert max-w-none break-words', props.class]"
    v-html="sanitizedContent"
  />
  <div v-else class="text-gray-500 dark:text-gray-400 italic">
    ไม่มีเนื้อหา
  </div>
</template>

<style scoped>
/* ---------------------------------------------------------------
   เนื้อหาที่กว้างเกินจอ (ตาราง / โค้ด / รูป / วิดีโอ)
   ต้องเลื่อนในกล่องของตัวเอง — ห้ามดันทั้งหน้าให้เลื่อนแนวนอน
   ใช้ :deep() เพราะ v-html สร้าง node ที่ไม่มี scope attribute
   --------------------------------------------------------------- */
:deep(.rtv-table-scroll) {
  @apply my-4 max-w-full overflow-x-auto;
  -webkit-overflow-scrolling: touch;
}

/* ตารางกว้างตามเนื้อหาจริง (ไม่โดนบีบจนหัวคอลัมน์ไทยแตกหลายบรรทัด)
   แต่ถ้าตารางแคบกว่ากล่องก็ยืดเต็มกล่องตามเดิม */
:deep(.rtv-table-scroll > table) {
  @apply m-0 border-collapse;
  width: max-content;
  min-width: 100%;
}

:deep(.rtv-table-scroll th),
:deep(.rtv-table-scroll td) {
  @apply border border-gray-300 px-3 py-2 dark:border-gray-600;
}

:deep(.rtv-table-scroll th) {
  @apply bg-gray-100 font-bold dark:bg-gray-800;
}

:deep(pre) {
  @apply max-w-full overflow-x-auto;
}

:deep(img) {
  @apply h-auto max-w-full;
}

:deep(iframe),
:deep(video) {
  @apply max-w-full;
}

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
