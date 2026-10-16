import { describe, expect, it } from 'vitest'
import { createMockAnzanService } from '../services/anzan/anzanService'

describe('anzanService — ชุดตัวเลขคิดในใจ', () => {
  const svc = createMockAnzanService()

  it('terms รวมได้ answer · ผลรวมสะสมไม่ติดลบ · ตัวแรกบวก · count ถูก', () => {
    for (let i = 0; i < 300; i++) {
      for (const count of [3, 5, 7]) {
        for (const level of ['easy', 'hard'] as const) {
          const r = svc.next(count, level)
          expect(r.terms.length).toBe(count)
          expect(r.terms[0]).toBeGreaterThan(0)
          let run = 0
          for (const t of r.terms) {
            run += t
            expect(run).toBeGreaterThanOrEqual(0)
          }
          expect(run).toBe(r.answer)
          const max = level === 'hard' ? 20 : 9
          for (const t of r.terms) expect(Math.abs(t)).toBeLessThanOrEqual(max)
        }
      }
    }
  })

  it('check ถูกเมื่อตรง answer เท่านั้น', () => {
    const r = svc.next(5, 'easy')
    expect(svc.check(r, r.answer)).toBe(true)
    expect(svc.check(r, r.answer + 1)).toBe(false)
  })
})
