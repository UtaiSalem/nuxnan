<template>
  <div
    v-if="code"
    class="mx-2 mt-3 rounded-xl border p-3 transition-colors duration-300"
    :class="isDarkMode
      ? 'bg-vikinger-dark-600/60 border-vikinger-purple/30'
      : 'bg-purple-50 border-purple-200'"
  >
    <!-- หัวข้อ -->
    <p class="text-[10px] uppercase tracking-widest font-semibold mb-1.5 text-center"
       :class="isDarkMode ? 'text-gray-500' : 'text-gray-400'">
      รหัสผู้แนะนำของฉัน
    </p>

    <!-- รหัสใหญ่ -->
    <p class="text-xl font-black font-mono tracking-[0.25em] text-center mb-2"
       :class="isDarkMode ? 'text-vikinger-cyan' : 'text-vikinger-purple'">
      {{ formattedCode }}
    </p>

    <!-- คำอธิบาย -->
    <p class="text-[10px] text-center mb-2.5"
       :class="isDarkMode ? 'text-gray-500' : 'text-gray-400'">
      ให้ผู้สมัครกรอกรหัสนี้ตอนสมัครสมาชิก
    </p>

    <!-- ปุ่ม actions -->
    <div class="flex gap-2">
      <!-- คัดลอกรหัส -->
      <button
        @click="copyCode"
        class="flex-1 flex items-center justify-center gap-1.5 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200"
        :class="copied
          ? (isDarkMode ? 'bg-green-500/20 text-green-400' : 'bg-green-100 text-green-600')
          : (isDarkMode ? 'bg-vikinger-purple/20 text-vikinger-purple hover:bg-vikinger-purple/30' : 'bg-purple-100 text-vikinger-purple hover:bg-purple-200')"
      >
        <Icon :icon="copied ? 'fluent:checkmark-circle-24-filled' : 'fluent:copy-24-regular'" class="w-3.5 h-3.5" />
        {{ copied ? 'คัดลอกแล้ว' : 'คัดลอกรหัส' }}
      </button>

      <!-- คัดลอกลิงก์ -->
      <button
        @click="copyLink"
        class="flex-1 flex items-center justify-center gap-1.5 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200"
        :class="copiedLink
          ? (isDarkMode ? 'bg-green-500/20 text-green-400' : 'bg-green-100 text-green-600')
          : (isDarkMode ? 'bg-vikinger-cyan/10 text-vikinger-cyan hover:bg-vikinger-cyan/20' : 'bg-cyan-50 text-cyan-600 hover:bg-cyan-100')"
      >
        <Icon :icon="copiedLink ? 'fluent:checkmark-circle-24-filled' : 'fluent:link-24-regular'" class="w-3.5 h-3.5" />
        {{ copiedLink ? 'คัดลอกแล้ว' : 'คัดลอกลิงก์' }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'

const props = defineProps<{
  code: string
  isDarkMode: boolean
}>()

const copied = ref(false)
const copiedLink = ref(false)

const formattedCode = computed(() => {
  const c = props.code.replace(/\s/g, '')
  return c.length === 8 ? `${c.slice(0, 4)} ${c.slice(4)}` : c
})

const copyCode = async () => {
  try {
    await navigator.clipboard.writeText(props.code)
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
  } catch {}
}

const copyLink = async () => {
  try {
    const origin = typeof window !== 'undefined' ? window.location.origin : ''
    const url = `${origin}/auth?tab=register&ref=${props.code}`
    await navigator.clipboard.writeText(url)
    copiedLink.value = true
    setTimeout(() => { copiedLink.value = false }, 2000)
  } catch {}
}
</script>
