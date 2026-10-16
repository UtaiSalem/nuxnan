/**
 * ExerciseService — บริการสร้าง/ตรวจโจทย์ลูกคิด (แผน §3 โมดูล Exercise, §9 mock-first)
 *
 * component/composable เรียกผ่าน interface นี้เท่านั้น เมื่อ backend พร้อม ให้สลับไปใช้
 * implementation ที่เรียก API (`/api/v1/game-sessions/.../attempts`) โดยไม่ต้องแก้ UI
 */
import type { Operation } from '~/components/games/abacus/abacusEngine'

export type AbacusLevel = 1 | 2 | 3

export interface AbacusExercise {
  id: string
  op: Operation
  a: number
  b: number
  answer: number
  /** จำนวนหลักของลูกคิดที่เหมาะกับโจทย์นี้ */
  columns: number
  /** ข้อความโจทย์ เช่น "47 + 38" */
  text: string
}

export interface AbacusExerciseService {
  next(level: AbacusLevel, ops: Operation[]): AbacusExercise
  check(ex: AbacusExercise, userValue: number): boolean
}

function randInt(min: number, max: number): number {
  return Math.floor(Math.random() * (max - min + 1)) + min
}

const LEVEL_RANGE: Record<AbacusLevel, { min: number; max: number; columns: number }> = {
  1: { min: 1, max: 9, columns: 2 }, // หลักเดียว (ผลบวกถึง 18)
  2: { min: 10, max: 99, columns: 3 }, // สองหลัก (ผลบวกถึง 198)
  3: { min: 100, max: 999, columns: 4 } // สามหลัก (ผลบวกถึง 1998)
}

const symbol = (op: Operation) => (op === '+' ? '+' : '−')

/** Mock service — สร้างโจทย์บวก/ลบจำนวนเต็มไม่ติดลบในเครื่อง (ยังไม่ต่อ backend) */
export function createMockExerciseService(): AbacusExerciseService {
  let seq = 0
  return {
    next(level, ops) {
      const cfg = LEVEL_RANGE[level] ?? LEVEL_RANGE[1]
      const pool = ops.filter((o) => o === '+' || o === '-')
      const chosen = pool.length ? pool : (['+'] as Operation[])
      const op = chosen[randInt(0, chosen.length - 1)]

      let a = randInt(cfg.min, cfg.max)
      let b = randInt(cfg.min, cfg.max)
      if (op === '-' && b > a) {
        const t = a
        a = b
        b = t
      }
      const answer = op === '+' ? a + b : a - b
      seq += 1
      return {
        id: `ex_${Date.now()}_${seq}`,
        op,
        a,
        b,
        answer,
        columns: cfg.columns,
        text: `${a} ${symbol(op)} ${b}`
      }
    },
    check(ex, userValue) {
      return userValue === ex.answer
    }
  }
}
