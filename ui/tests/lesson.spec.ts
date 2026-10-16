import { describe, expect, it } from 'vitest'
import {
  type StageStat,
  LESSON_STAGES,
  MIN_ATTEMPTS,
  emptyStat,
  accuracyOf,
  masteryOf,
  isStageUnlocked,
  stageLevel,
  recordAttempt
} from '../services/lessons/lessonEngine'

const stat = (correct: number, total: number, bestStreak = 0): StageStat => ({ correct, total, bestStreak })

describe('lessonEngine — ความชำนาญ', () => {
  it('accuracyOf กัน 0 หาร', () => {
    expect(accuracyOf(emptyStat())).toBe(0)
    expect(accuracyOf(stat(9, 10))).toBeCloseTo(0.9)
  })

  it('masteryOf: ต่ำกว่าขั้นต่ำข้อ = learning เสมอ', () => {
    expect(masteryOf(stat(7, 7))).toBe('learning') // 100% แต่ < 8 ข้อ
    expect(masteryOf(stat(MIN_ATTEMPTS, MIN_ATTEMPTS))).toBe('mastered')
  })

  it('masteryOf: เกณฑ์ 60/85%', () => {
    expect(masteryOf(stat(5, 10))).toBe('learning') // 50%
    expect(masteryOf(stat(6, 10))).toBe('fluent') // 60%
    expect(masteryOf(stat(8, 10))).toBe('fluent') // 80%
    expect(masteryOf(stat(9, 10))).toBe('mastered') // 90%
  })
})

describe('lessonEngine — ปลดล็อกด่าน', () => {
  it('ด่านแรก (fingers) ปลดล็อกเสมอ', () => {
    expect(isStageUnlocked('fingers', {})).toBe(true)
    expect(stageLevel('fingers', {})).toBe('learning')
  })

  it('ด่านถัดไปล็อกจนกว่าด่านก่อนหน้า mastered', () => {
    expect(isStageUnlocked('finger-math', {})).toBe(false)
    expect(stageLevel('finger-math', {})).toBe('locked')

    // fingers คล่องแต่ยังไม่เชี่ยวชาญ → ยังล็อก
    expect(isStageUnlocked('finger-math', { fingers: stat(7, 10) })).toBe(false)
    // fingers เชี่ยวชาญ → ปลด finger-math
    expect(isStageUnlocked('finger-math', { fingers: stat(9, 10) })).toBe(true)
    // แต่ bridge (ด่าน 3) ยังล็อกเพราะ finger-math ยังไม่ mastered
    expect(isStageUnlocked('bridge', { fingers: stat(9, 10) })).toBe(false)
  })

  it('ปลดล็อกไล่ต่อเนื่องจนด่านสุดท้าย (anzan)', () => {
    const upToAbacus = {
      fingers: stat(9, 10),
      'finger-math': stat(9, 10),
      bridge: stat(9, 10)
    }
    expect(isStageUnlocked('abacus', upToAbacus)).toBe(true)
    expect(stageLevel('abacus', upToAbacus)).toBe('learning')
    // anzan ยังล็อกจนกว่า abacus mastered
    expect(isStageUnlocked('anzan', upToAbacus)).toBe(false)
    expect(isStageUnlocked('anzan', { ...upToAbacus, abacus: stat(9, 10) })).toBe(true)
  })

  it('stageId ที่ไม่มีจริง = ไม่ปลดล็อก', () => {
    expect(isStageUnlocked('nope', {})).toBe(false)
  })
})

describe('lessonEngine — recordAttempt', () => {
  it('นับถูก/รวม + bestStreak', () => {
    let s = emptyStat()
    s = recordAttempt(s, true, 1)
    s = recordAttempt(s, false, 0)
    s = recordAttempt(s, true, 5)
    expect(s).toEqual({ correct: 2, total: 3, bestStreak: 5 })
  })

  it('เส้นทางมี 5 ด่านเรียงลำดับ', () => {
    expect(LESSON_STAGES.map((s) => s.id)).toEqual(['fingers', 'finger-math', 'bridge', 'abacus', 'anzan'])
  })
})
