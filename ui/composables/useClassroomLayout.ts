import { computed, onMounted, onUnmounted, ref } from 'vue'

/**
 * Responsive classroom zoning shared by the Phaser renderer and the Vue
 * fallback grid. Keeps both surfaces in lockstep so a viewport change shows
 * the same number of desks per row regardless of which renderer is active.
 *
 * Patterns are intentionally symmetric — uneven splits like 3:4:3 look off
 * once the room shrinks.
 */
export type ClassroomZoning = {
  zones: readonly number[]
  totalCols: number
  aisleCount: number
}

const HYSTERESIS = 24 // px — guard against pattern flicker while resizing

function pickPattern(width: number): ClassroomZoning {
  if (width < 480) return { zones: [1, 1], totalCols: 2, aisleCount: 1 }
  if (width < 640) return { zones: [2, 2], totalCols: 4, aisleCount: 1 }
  if (width < 1024) return { zones: [2, 2, 2], totalCols: 6, aisleCount: 2 }
  return { zones: [3, 3, 3], totalCols: 9, aisleCount: 2 }
}

export function useClassroomLayout() {
  const viewportWidth = ref(typeof window === 'undefined' ? 1024 : window.innerWidth)
  let lastSettledWidth = viewportWidth.value

  const onResize = () => {
    const w = window.innerWidth
    if (Math.abs(w - lastSettledWidth) < HYSTERESIS) return
    lastSettledWidth = w
    viewportWidth.value = w
  }

  onMounted(() => {
    window.addEventListener('resize', onResize, { passive: true })
  })

  onUnmounted(() => {
    window.removeEventListener('resize', onResize)
  })

  const zoning = computed<ClassroomZoning>(() => pickPattern(viewportWidth.value))

  return { zoning, viewportWidth }
}
