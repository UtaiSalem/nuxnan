<script setup lang="ts">
import { Icon } from '@iconify/vue'
import QRCode from 'qrcode'

const props = defineProps<{
  academyId: number
  attendanceId: number
}>()

const api = useApi()
const qrCanvas = ref<HTMLCanvasElement | null>(null)
const qrContent = ref('')
const isLoading = ref(false)
const isFullscreen = ref(false)

const renderQR = async () => {
  if (!qrContent.value || !qrCanvas.value) return
  await QRCode.toCanvas(qrCanvas.value, qrContent.value, {
    width: isFullscreen.value ? 600 : 300,
    margin: 2,
    color: { dark: '#000000', light: '#ffffff' }
  })
}

const refreshQR = async () => {
  isLoading.value = true
  try {
    const res = await api.post(
      `/api/academies/${props.academyId}/school-attendances/${props.attendanceId}/refresh-qr`,
      {}
    ) as any
    if (res.success) {
      qrContent.value = res.qr_content
      await nextTick()
      await renderQR()
    }
  } catch (err) {
    console.error('Failed to refresh QR:', err)
  } finally {
    isLoading.value = false
  }
}

const toggleFullscreen = () => {
  isFullscreen.value = !isFullscreen.value
  nextTick(renderQR)
}

onMounted(refreshQR)

watch(() => props.attendanceId, refreshQR)
</script>

<template>
  <div
    class="flex flex-col items-center justify-center p-6 bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 transition-all duration-300"
    :class="{ 'fixed inset-0 z-[100] rounded-none': isFullscreen }"
  >
    <!-- Header -->
    <div class="w-full flex justify-between items-center mb-6">
      <div class="flex items-center gap-3">
        <div class="p-2 bg-teal-100 dark:bg-teal-900 rounded-xl">
          <Icon icon="fluent:qr-code-24-filled" class="text-2xl text-teal-600 dark:text-teal-400" />
        </div>
        <div>
          <h3 class="text-xl font-bold text-gray-900 dark:text-white">สแกนเพื่อเช็คชื่อ</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400">ให้นักเรียนใช้แอป nuxnan สแกน QR นี้</p>
        </div>
      </div>

      <button
        @click="toggleFullscreen"
        class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors"
        title="ขยายเต็มจอ"
      >
        <Icon
          :icon="isFullscreen ? 'fluent:full-screen-minimize-24-regular' : 'fluent:full-screen-maximize-24-regular'"
          class="text-2xl text-gray-500"
        />
      </button>
    </div>

    <!-- QR Code Area -->
    <div class="relative group">
      <div
        v-if="isLoading"
        class="absolute inset-0 flex items-center justify-center bg-white/80 dark:bg-gray-800/80 z-10 rounded-2xl backdrop-blur-sm"
      >
        <Icon icon="eos-icons:loading" class="text-4xl text-teal-600" />
      </div>

      <div class="p-4 bg-white rounded-2xl shadow-inner border-4 border-teal-50 dark:border-gray-700">
        <canvas ref="qrCanvas" class="max-w-full h-auto"></canvas>
      </div>

      <!-- Session active indicator -->
      <div class="absolute -bottom-4 left-1/2 -translate-x-1/2 px-4 py-2 bg-gray-900 text-white text-xs font-bold rounded-full shadow-lg flex items-center gap-2 whitespace-nowrap">
        <span class="relative flex h-2 w-2">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-teal-400 opacity-75"></span>
          <span class="relative inline-flex rounded-full h-2 w-2 bg-teal-500"></span>
        </span>
        Session เปิดอยู่ — QR ใช้งานได้ตลอด
      </div>
    </div>

    <!-- Footer Actions -->
    <div class="mt-12 flex items-center gap-4">
      <button
        @click="refreshQR"
        :disabled="isLoading"
        class="flex items-center gap-2 px-6 py-3 bg-teal-600 hover:bg-teal-700 disabled:opacity-50 text-white font-bold rounded-2xl shadow-lg shadow-teal-200 dark:shadow-none transition-all active:scale-95"
      >
        <Icon icon="fluent:arrow-sync-24-filled" :class="{ 'animate-spin': isLoading }" />
        สร้าง QR ใหม่
      </button>

      <p v-if="isFullscreen" class="text-gray-500 dark:text-gray-400 text-sm">
        กดปุ่มขวามือบนเพื่อออกจากหน้าจอนี้
      </p>
    </div>
  </div>
</template>

<style scoped>
canvas {
  image-rendering: pixelated;
}
</style>
