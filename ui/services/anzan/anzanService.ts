/**
 * AnzanService — สร้างชุดตัวเลขสำหรับโหมดคิดในใจ (Anzan, mock-first)
 * ตัวแรกเป็นบวก · ที่เหลือบวก/ลบได้ แต่ผลรวมสะสมต้องไม่ติดลบทุกขั้น
 */
export type AnzanLevel = 'easy' | 'hard' // easy: ตัวเลข 1–9 · hard: 1–20

export interface AnzanRound {
  id: string
  /** พจน์ทีละตัว (ตัวแรกบวกเสมอ · ตัวถัดไปอาจเป็นลบ) */
  terms: number[]
  /** ผลรวมสุดท้าย (คำตอบ) */
  answer: number
}

export interface AnzanService {
  next(count: number, level: AnzanLevel): AnzanRound
  check(round: AnzanRound, value: number): boolean
}

function randInt(min: number, max: number): number {
  return Math.floor(Math.random() * (max - min + 1)) + min
}

export function createMockAnzanService(): AnzanService {
  let seq = 0
  return {
    next(count, level) {
      const max = level === 'hard' ? 20 : 9
      const n = Math.max(2, Math.min(9, Math.floor(count)))
      const terms: number[] = []
      let sum = 0
      for (let i = 0; i < n; i++) {
        if (i === 0) {
          const t = randInt(1, max)
          terms.push(t)
          sum += t
          continue
        }
        // ลบได้เฉพาะเมื่อผลรวมยังพอให้ลบโดยไม่ติดลบ
        const canSub = sum >= 2
        if (canSub && Math.random() < 0.4) {
          const t = randInt(1, Math.min(max, sum))
          terms.push(-t)
          sum -= t
        } else {
          const t = randInt(1, max)
          terms.push(t)
          sum += t
        }
      }
      seq += 1
      return { id: `az_${Date.now()}_${seq}`, terms, answer: sum }
    },
    check(round, value) {
      return value === round.answer
    }
  }
}
