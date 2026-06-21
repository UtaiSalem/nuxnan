import { onMounted, onBeforeUnmount, type Ref } from 'vue'

interface SwipeOptions {
  threshold?: number
  onSwipeLeft?: () => void
  onSwipeRight?: () => void
  onSwipeUp?: () => void
  onSwipeDown?: () => void
}

export const useSwipe = (el: Ref<HTMLElement | null>, opts: SwipeOptions) => {
  const threshold = opts.threshold ?? 50
  let startX = 0
  let startY = 0

  const onStart = (e: TouchEvent) => {
    if (e.touches && e.touches.length > 0) {
      startX = e.touches[0].clientX
      startY = e.touches[0].clientY
    }
  }

  const onEnd = (e: TouchEvent) => {
    if (!e.changedTouches || e.changedTouches.length === 0) return
    const dx = e.changedTouches[0].clientX - startX
    const dy = e.changedTouches[0].clientY - startY
    
    // Ignore minor/accidental movements
    if (Math.abs(dx) < threshold && Math.abs(dy) < threshold) return

    // Calculate directions - check if swipe is mostly horizontal or vertical
    if (Math.abs(dx) > Math.abs(dy) * 1.5) {
      // Horizontal swipe
      if (dx > 0) {
        opts.onSwipeRight?.()
      } else {
        opts.onSwipeLeft?.()
      }
    } else if (Math.abs(dy) > Math.abs(dx) * 1.5) {
      // Vertical swipe
      if (dy > 0) {
        opts.onSwipeDown?.()
      } else {
        opts.onSwipeUp?.()
      }
    }
  }

  onMounted(() => {
    if (el.value) {
      el.value.addEventListener('touchstart', onStart, { passive: true })
      el.value.addEventListener('touchend', onEnd, { passive: true })
    }
  })

  onBeforeUnmount(() => {
    if (el.value) {
      el.value.removeEventListener('touchstart', onStart)
      el.value.removeEventListener('touchend', onEnd)
    }
  })
}
