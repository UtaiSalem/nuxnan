import { ref, computed } from 'vue'

export function useWpmCalc() {
  const elapsedSeconds  = ref(0)
  const _correctChars   = ref(0)
  let _interval: ReturnType<typeof setInterval> | null = null

  const wpm = computed(() => {
    const mins = elapsedSeconds.value / 60
    if (mins === 0) return 0
    return Math.round((_correctChars.value / 5) / mins)
  })

  function startTimer() {
    elapsedSeconds.value = 0
    if (_interval) clearInterval(_interval)
    _interval = setInterval(() => { elapsedSeconds.value++ }, 1000)
  }

  function stopTimer() {
    if (_interval) { clearInterval(_interval); _interval = null }
  }

  function updateCorrectChars(n: number) {
    _correctChars.value = n
  }

  function reset() {
    stopTimer()
    elapsedSeconds.value = 0
    _correctChars.value  = 0
  }

  return { wpm, elapsedSeconds, startTimer, stopTimer, updateCorrectChars, reset }
}
