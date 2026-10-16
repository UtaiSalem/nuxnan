/**
 * FingerExerciseService — บริการสร้าง/ตรวจโจทย์เกมนิ้วมือ (mock-first)
 * component/composable เรียกผ่าน interface นี้ · เปลี่ยนเป็น API ภายหลังได้โดยไม่แก้ UI
 */
export type FingerStage = 1 | 2 // 1 = มือเดียว 0–9 · 2 = สองมือ 0–99
export type FingerMode = 'read' | 'make' // read = ดูมือตอบเลข · make = ดูเลขเลือกมือ

export interface FingerExercise {
  id: string
  stage: FingerStage
  mode: FingerMode
  value: number
  choices: number[]
}

export interface FingerExerciseService {
  next(stage: FingerStage, mode: FingerMode): FingerExercise
  check(ex: FingerExercise, chosen: number): boolean
}

function randInt(min: number, max: number): number {
  return Math.floor(Math.random() * (max - min + 1)) + min
}

function shuffle<T>(arr: T[]): T[] {
  const a = [...arr]
  for (let i = a.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1))
    ;[a[i], a[j]] = [a[j], a[i]]
  }
  return a
}

/** 4 ตัวเลือก = คำตอบถูก + ตัวลวงใกล้เคียง (ไม่ซ้ำ, อยู่ในช่วง, สับลำดับ) */
function makeChoices(value: number, min: number, max: number): number[] {
  const set = new Set<number>([value])
  const spread = Math.max(2, Math.min(9, Math.round((max - min) / 8) || 3))
  let guard = 0
  while (set.size < 4 && guard < 200) {
    guard++
    const delta = randInt(1, spread)
    let c = value + (Math.random() < 0.5 ? -delta : delta)
    if (c < min || c > max) c = randInt(min, max)
    set.add(c)
  }
  while (set.size < 4) set.add(randInt(min, max))
  return shuffle([...set])
}

export function createMockFingerExerciseService(): FingerExerciseService {
  let seq = 0
  return {
    next(stage, mode) {
      const max = stage === 2 ? 99 : 9
      const value = randInt(0, max)
      seq += 1
      return {
        id: `fx_${Date.now()}_${seq}`,
        stage,
        mode,
        value,
        choices: makeChoices(value, 0, max)
      }
    },
    check(ex, chosen) {
      return chosen === ex.value
    }
  }
}
