import { ref, computed, onMounted, onBeforeUnmount, watch, type Ref } from 'vue'

interface ReadTime {
  required_seconds: number
  spent_seconds: number
  remaining_seconds: number
  satisfied: boolean
}

export function useLessonReadTimer(lessonId: Ref<number>, enabled: Ref<boolean>) {
  const api = useApi()

  const targetEl = ref<HTMLElement | null>(null)
  const readTime = ref<ReadTime>({ required_seconds: 0, spent_seconds: 0, remaining_seconds: 0, satisfied: true })
  const isLoaded = ref(false)

  let observer: IntersectionObserver | null = null
  let ticker: ReturnType<typeof setInterval> | null = null
  let isOnScreen = false
  let pendingSeconds = 0
  const FLUSH_EVERY = 15

  // remaining_seconds <= 0 ต้องนับว่าครบด้วย ไม่งั้นตัวนับฝั่ง client เดินถึง 0 แล้ว
  // ปุ่มจะยังล็อกค้างโชว์ "อีก 0:00" จนกว่าจะถึงรอบ flush ถัดไป (นานได้ถึง 15 วินาที)
  const satisfied = computed(() =>
    readTime.value.required_seconds <= 0 ||
    readTime.value.satisfied ||
    readTime.value.remaining_seconds <= 0
  )
  const remainingSeconds = computed(() => Math.max(0, readTime.value.remaining_seconds))

  const loadReadTime = async () => {
    try {
      const response: any = await api.get(`/api/lessons/${lessonId.value}/progress`)
      if (response?.read_time) {
        readTime.value = response.read_time
      }
    } catch (e) {
      console.error('Failed to load lesson read time:', e)
    } finally {
      isLoaded.value = true
    }
  }

  const flush = async () => {
    if (pendingSeconds < 1) return
    const seconds = Math.min(pendingSeconds, 3600)
    pendingSeconds = 0
    try {
      const res: any = await api.post(`/api/lessons/${lessonId.value}/progress/time-spent`, { seconds })
      if (res?.read_time) readTime.value = res.read_time
    } catch (e) { 
      console.error('Failed to sync lesson read time:', e) 
    }
  }

  const tick = () => {
    if (!enabled.value || satisfied.value) return
    if (!isOnScreen) return
    if (typeof document !== 'undefined' && document.visibilityState !== 'visible') return
    pendingSeconds++
    readTime.value = {
      ...readTime.value,
      spent_seconds: readTime.value.spent_seconds + 1,
      remaining_seconds: Math.max(0, readTime.value.remaining_seconds - 1),
    }
    if (pendingSeconds >= FLUSH_EVERY) flush()
  }

  onMounted(async () => {
    if (!enabled.value) return
    await loadReadTime()
    if (satisfied.value) return
    if (typeof IntersectionObserver !== 'undefined' && targetEl.value) {
      observer = new IntersectionObserver(
        (entries) => { isOnScreen = entries.some(e => e.isIntersecting) },
        { threshold: 0.01 }
      )
      observer.observe(targetEl.value)
    } else {
      isOnScreen = true
    }
    ticker = setInterval(tick, 1000)
  })

  const stop = () => { 
    observer?.disconnect()
    observer = null
    if (ticker) clearInterval(ticker)
    ticker = null 
  }

  watch(satisfied, (ok) => { if (ok) { flush(); stop() } })

  onBeforeUnmount(() => { flush(); stop() })

  return { targetEl, readTime, satisfied, remainingSeconds, isLoaded, loadReadTime }
}
