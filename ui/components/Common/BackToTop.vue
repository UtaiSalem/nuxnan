<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { Icon } from '@iconify/vue'

/**
 * ปุ่มเลื่อนกลับขึ้นบนสุด — mount ครั้งเดียวที่ app.vue จึงขึ้นทุกหน้า
 * หน้าที่เนื้อหาสั้นจะไม่เลื่อนเกิน threshold ปุ่มก็จะไม่โผล่เอง
 *
 * ตำแหน่ง: บนมือถือต้องอยู่เหนือ LayoutBottomNav (fixed bottom-0 h-16 lg:hidden)
 * จึงใช้ bottom-20 แล้วค่อยลดเป็น lg:bottom-8 ตอน nav ถูกซ่อน
 * z-30 ตั้งใจให้ต่ำกว่า drawer (z-50), overlay (z-40) และแถบ action ที่ sticky อยู่
 * ปุ่มจะได้ไม่ลอยทับของพวกนั้น
 */
const threshold = 300
const isVisible = ref(false)
let ticking = false

const update = () => {
  isVisible.value = window.scrollY > threshold
  ticking = false
}

const onScroll = () => {
  if (ticking) return
  ticking = true
  requestAnimationFrame(update)
}

const scrollToTop = () => {
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

onMounted(() => {
  window.addEventListener('scroll', onScroll, { passive: true })
  update()
})

onUnmounted(() => {
  window.removeEventListener('scroll', onScroll)
})
</script>

<template>
  <transition
    enter-active-class="transition ease-out duration-300"
    enter-from-class="opacity-0 translate-y-10"
    enter-to-class="opacity-100 translate-y-0"
    leave-active-class="transition ease-in duration-300"
    leave-from-class="opacity-100 translate-y-0"
    leave-to-class="opacity-0 translate-y-10"
  >
    <button
      v-if="isVisible"
      type="button"
      @click="scrollToTop"
      class="fixed bottom-20 right-4 lg:bottom-8 lg:right-8 z-30 p-3 min-h-[44px] min-w-[44px] flex items-center justify-center bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-full shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl hover:-translate-y-1 transition-all"
      title="เลื่อนขึ้นด้านบน"
      aria-label="เลื่อนขึ้นด้านบน"
    >
      <Icon icon="fluent:arrow-up-24-filled" class="w-6 h-6" />
    </button>
  </transition>
</template>
