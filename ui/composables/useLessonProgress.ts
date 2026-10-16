/**
 * useLessonProgress — ความก้าวหน้าเส้นทางเรียนจินตคณิต (เก็บใน localStorage)
 * เกมเรียก record() ทุกข้อ · hub เรียก levelOf/unlocked/accuracyPct เพื่อโชว์ล็อก/ความแม่นยำ
 * SSR-safe: โหลด localStorage เฉพาะฝั่ง client (onMounted)
 */
import { ref, onMounted } from 'vue'
import {
  LESSON_STAGES,
  emptyStat,
  recordAttempt,
  stageLevel,
  isStageUnlocked,
  accuracyOf,
  type StageStat,
  type MasteryLevel
} from '~/services/lessons/lessonEngine'

const STORAGE_KEY = 'mental_abacus_progress'

export function useLessonProgress() {
  const stats = ref<Record<string, StageStat>>({})

  function load(): Record<string, StageStat> {
    if (!import.meta.client) return {}
    try {
      const raw = localStorage.getItem(STORAGE_KEY)
      if (raw) return JSON.parse(raw)
    } catch (e) {
      /* noop */
    }
    return {}
  }

  function hydrate() {
    stats.value = load()
  }

  function persist() {
    if (!import.meta.client) return
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(stats.value))
    } catch (e) {
      /* noop */
    }
  }

  /** บันทึกผล 1 ข้อของด่าน */
  function record(stageId: string, correct: boolean, streak = 0) {
    const cur = stats.value[stageId] ?? emptyStat()
    stats.value = { ...stats.value, [stageId]: recordAttempt(cur, correct, streak) }
    persist()
  }

  const statOf = (id: string): StageStat => stats.value[id] ?? emptyStat()
  const levelOf = (id: string): MasteryLevel => stageLevel(id, stats.value)
  const unlocked = (id: string): boolean => isStageUnlocked(id, stats.value)
  const accuracyPct = (id: string): number => Math.round(accuracyOf(statOf(id)) * 100)

  onMounted(hydrate)

  return { stats, stages: LESSON_STAGES, record, statOf, levelOf, unlocked, accuracyPct, hydrate }
}
