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
</style>
