/**
 * FingerMathService — โจทย์บวก/ลบที่ตอบด้วยท่ามือ (ด่าน 6, mock-first)
 * ผลลัพธ์ไม่ติดลบและอยู่ในช่วงของ stage (เพื่อแทนด้วยนิ้วมือได้)
 */
import type { Operation } from '~/components/games/abacus/abacusEngine'

export type FingerMathStage = 1 | 2 // 1 = คำตอบ 0–9 (มือเดียว) · 2 = คำตอบ 0–99 (สองมือ)

export interface FingerMathExercise {
  id: string
  stage: FingerMathStage
  op: Operation
  a: number
  b: number
  answer: number
  text: string
  choices: number[]
}

export interface FingerMathService {
  next(stage: FingerMathStage, ops: Operation[]): FingerMathExercise
  check(ex: FingerMathExercise, chosen: number): boolean
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

function makeChoices(answer: number, min: number, max: number): number[] {
  const set = new Set<number>([answer])
  const spread = Math.max(2, Math.min(9, Math.round((max - min) / 8) || 3))
  let guard = 0
  while (set.size < 4 && guard < 200) {
    guard++
    const delta = randInt(1, spread)
    let c = answer + (Math.random() < 0.5 ? -delta : delta)
    if (c < min || c > max) c = randInt(min, max)
    set.add(c)
  }
  while (set.size < 4) set.add(randInt(min, max))
  return shuffle([...set])
}

const symbol = (op: Operation) => (op === '+' ? '+' : '−')

export function createMockFingerMathService(): FingerMathService {
  let seq = 0
  return {
    next(stage, ops) {
      const max = stage === 2 ? 99 : 9
      const pool = ops.filter((o) => o === '+' || o === '-')
      const chosen = pool.length ? pool : (['+'] as Operation[])
      const op = chosen[randInt(0, chosen.length - 1)]

      let a: number
      let b: number
      let answer: number
      if (op === '+') {
        a = randInt(0, max)
        b = randInt(0, max - a) // ผลรวมไม่เกิน max
        answer = a + b
      } else {
        a = randInt(0, max)
        b = randInt(0, a) // ผลลบไม่ติดลบ
        answer = a - b
      }

      seq += 1
      return {
        id: `fm_${Date.now()}_${seq}`,
        stage,
        op,
        a,
        b,
        answer,
        text: `${a} ${symbol(op)} ${b}`,
        choices: makeChoices(answer, 0, max)
      }
    },
    check(ex, chosen) {
      return chosen === ex.answer
    }
  }
}
