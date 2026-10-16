import { describe, expect, it } from 'vitest'
import {
  columnValue,
  digitToColumn,
  abacusValue,
  setAbacusValue,
  makeColumn,
  createAbacus,
  planAddDigit,
  planSubDigit,
  computeAnswer,
  isAbacusCorrect,
  toggleHeaven,
  tapEarth,
  applyOperation,
  getHint,
  validateState
} from '../components/games/abacus/abacusEngine'
import {
  createAbacusState,
  setNumber as setNumberA,
  readNumber,
  moveBead,
  applyOperation as applyOperationA,
  validateState as validateStateA
} from '../components/games/abacus/abacusAdapter'

describe('abacus representation', () => {
  it('maps digits 0..9 to the right bead state', () => {
    for (let d = 0; d <= 9; d++) {
      const col = digitToColumn(d)
      expect(col.heaven).toBe(d >= 5)
      expect(col.earth).toBe(d % 5)
      expect(columnValue(col)).toBe(d)
    }
  })

  it('round-trips every value 0..9999 through the abacus', () => {
    for (let v = 0; v <= 9999; v++) {
      expect(abacusValue(setAbacusValue(4, v))).toBe(v)
    }
  })

  it('places digits by column (index 0 = units)', () => {
    const a = setAbacusValue(4, 1234)
    expect(columnValue(a[0])).toBe(4) // units
    expect(columnValue(a[1])).toBe(3) // tens
    expect(columnValue(a[2])).toBe(2)
    expect(columnValue(a[3])).toBe(1)
  })
})

describe('planAddDigit — soroban addition rules', () => {
  it('adds directly when beads are available', () => {
    expect(planAddDigit(2, 6)).toMatchObject({ rule: 'direct', carry: 0, result: 8 }) // +5+1
    expect(planAddDigit(1, 2)).toMatchObject({ rule: 'direct', carry: 0, result: 3 })
    expect(planAddDigit(5, 4)).toMatchObject({ rule: 'direct', carry: 0, result: 9 })
  })

  it('uses the little friend (คู่ 5) when earth beads run out', () => {
    expect(planAddDigit(4, 1)).toMatchObject({ rule: 'little_friend', carry: 0, result: 5 }) // +1 = +5 −4
    expect(planAddDigit(3, 4)).toMatchObject({ rule: 'little_friend', carry: 0, result: 7 }) // +4 = +5 −1
    expect(planAddDigit(4, 1).hint).toBe('+1 = +5 − 4')
  })

  it('uses the big friend (คู่ 10) and carries when the sum reaches 10', () => {
    expect(planAddDigit(8, 7)).toMatchObject({ rule: 'big_friend', carry: 1, result: 5 }) // +7 = +10 −3
    expect(planAddDigit(6, 7)).toMatchObject({ rule: 'big_friend', carry: 1, result: 3 })
    expect(planAddDigit(9, 1)).toMatchObject({ rule: 'big_friend', carry: 1, result: 0 })
    expect(planAddDigit(8, 7).hint).toBe('+7 = +10 − 3')
  })

  it('always reports a result that matches plain arithmetic (mod 10) and the right carry', () => {
    for (let c = 0; c <= 9; c++) {
      for (let a = 1; a <= 9; a++) {
        const step = planAddDigit(c, a)
        expect(step.result).toBe((c + a) % 10)
        expect(step.carry).toBe(c + a >= 10 ? 1 : 0)
      }
    }
  })
})

describe('planSubDigit — soroban subtraction rules', () => {
  it('subtracts directly when beads can be removed', () => {
    expect(planSubDigit(8, 3)).toMatchObject({ rule: 'direct', borrow: 0, result: 5 })
    expect(planSubDigit(9, 4)).toMatchObject({ rule: 'direct', borrow: 0, result: 5 })
  })

  it('uses the little friend (คู่ 5) when earth beads are not enough', () => {
    expect(planSubDigit(5, 1)).toMatchObject({ rule: 'little_friend', borrow: 0, result: 4 }) // −1 = −5 +4
    expect(planSubDigit(6, 3)).toMatchObject({ rule: 'little_friend', borrow: 0, result: 3 }) // −3 = −5 +2
    expect(planSubDigit(5, 1).hint).toBe('−1 = −5 + 4')
  })

  it('uses the big friend (คู่ 10) and borrows when the column goes negative', () => {
    expect(planSubDigit(3, 4)).toMatchObject({ rule: 'big_friend', borrow: 1, result: 9 }) // −4 = −10 +6
    expect(planSubDigit(2, 7)).toMatchObject({ rule: 'big_friend', borrow: 1, result: 5 })
    expect(planSubDigit(3, 4).hint).toBe('−4 = −10 + 6')
  })

  it('reports a result matching plain arithmetic (mod 10) and the right borrow', () => {
    for (let c = 0; c <= 9; c++) {
      for (let s = 1; s <= 9; s++) {
        const step = planSubDigit(c, s)
        expect(step.result).toBe(((c - s) % 10 + 10) % 10)
        expect(step.borrow).toBe(c - s < 0 ? 1 : 0)
      }
    }
  })
})

describe('bead tapping — ตรรกะการแตะเม็ด (ใช้ใน VirtualAbacus.vue)', () => {
  it('toggleHeaven สลับเม็ดบนโดยไม่แตะเม็ดล่าง', () => {
    expect(toggleHeaven(makeColumn(false, 3))).toEqual(makeColumn(true, 3))
    expect(toggleHeaven(makeColumn(true, 2))).toEqual(makeColumn(false, 2))
  })

  it('tapEarth เม็ดที่ยังไม่ชิดคาน → ดันเม็ดนั้น + ที่อยู่เหนือมันขึ้น (earth = p)', () => {
    expect(tapEarth(makeColumn(false, 0), 3)).toEqual(makeColumn(false, 3)) // แตะเม็ด 3 → 1,2,3 ชิดคาน
    expect(tapEarth(makeColumn(false, 2), 4)).toEqual(makeColumn(false, 4)) // แตะเม็ด 4 → ครบ 4
    expect(tapEarth(makeColumn(true, 1), 2)).toEqual(makeColumn(true, 2)) // ไม่แตะเม็ดบน
  })

  it('tapEarth เม็ดที่ชิดคานแล้ว → ถอนเม็ดนั้น + ที่อยู่ล่างมันลง (earth = p − 1)', () => {
    expect(tapEarth(makeColumn(false, 3), 3)).toEqual(makeColumn(false, 2)) // แตะเม็ด 3 ที่ active → เหลือ 2
    expect(tapEarth(makeColumn(false, 4), 1)).toEqual(makeColumn(false, 0)) // แตะเม็ดบนสุด → ถอนหมด
    expect(tapEarth(makeColumn(true, 2), 2)).toEqual(makeColumn(true, 1))
  })

  it('tapEarth clamp ตำแหน่งให้อยู่ 1..4 และไม่ทำให้ค่าหลักเกิน 0..9', () => {
    for (let e = 0; e <= 4; e++) {
      for (let p = 1; p <= 4; p++) {
        const next = tapEarth(makeColumn(false, e), p)
        expect(next.earth).toBeGreaterThanOrEqual(0)
        expect(next.earth).toBeLessThanOrEqual(4)
      }
    }
  })
})

describe('answer checking', () => {
  it('computes the four operations', () => {
    expect(computeAnswer(8, '+', 7)).toBe(15)
    expect(computeAnswer(12, '-', 5)).toBe(7)
    expect(computeAnswer(6, '×', 7)).toBe(42)
    expect(computeAnswer(12, '÷', 4)).toBe(3)
  })

  it('validates an abacus against an expected value', () => {
    expect(isAbacusCorrect(setAbacusValue(3, 15), 15)).toBe(true)
    expect(isAbacusCorrect(setAbacusValue(3, 15), 16)).toBe(false)
  })
})

describe('applyOperation — บวก/ลบหลายหลัก (แผน §5.4)', () => {
  const W = 4
  const run = (a: number, op: '+' | '-', b: number) =>
    applyOperation(setAbacusValue(W, a), op, b).result

  it('ผ่านชุดทดสอบมาตรฐาน §5.4', () => {
    expect(run(2, '+', 3)).toBe(5)
    expect(run(4, '+', 1)).toBe(5)
    expect(run(8, '+', 7)).toBe(15)
    expect(run(9, '+', 1)).toBe(10)
    expect(run(47, '+', 38)).toBe(85)
    expect(run(99, '+', 1)).toBe(100)
    expect(run(10, '-', 1)).toBe(9)
    expect(run(52, '-', 27)).toBe(25)
    expect(run(100, '-', 1)).toBe(99)
    expect(run(1000, '-', 999)).toBe(1)
  })

  it('ตรงกับคณิตปกติทุกคู่ที่ไล่ (บวก clamp ที่ 9999, ลบเฉพาะไม่ติดลบ)', () => {
    for (let a = 0; a <= 999; a += 37) {
      for (let b = 0; b <= 999; b += 53) {
        expect(applyOperation(setAbacusValue(4, a), '+', b).result).toBe(Math.min(9999, a + b))
        if (a >= b) expect(applyOperation(setAbacusValue(4, a), '-', b).result).toBe(a - b)
      }
    }
  })

  it('ตั้งธง overflow เมื่อผลบวกเกินจำนวนหลัก', () => {
    expect(applyOperation(setAbacusValue(2, 99), '+', 1).overflow).toBe(true)
  })

  it('ตั้งธง underflow + ผลเป็น 0 เมื่อลบเกิน', () => {
    const r = applyOperation(setAbacusValue(3, 5), '-', 9)
    expect(r.underflow).toBe(true)
    expect(r.result).toBe(0)
  })

  it('getHint คืนคำใบ้อย่างน้อย 1 ขั้นเมื่อมีการเปลี่ยนค่า', () => {
    expect(getHint(setAbacusValue(2, 8), '+', 7).length).toBeGreaterThan(0)
    expect(getHint(setAbacusValue(2, 5), '+', 0).length).toBe(0)
  })

  it('validateState จับสถานะผิดกติกา', () => {
    expect(validateState(createAbacus(4))).toBe(true)
    expect(validateState(setAbacusValue(3, 123))).toBe(true)
    expect(validateState([{ heaven: true, earth: 7 }] as never)).toBe(false)
  })
})

describe('abacusAdapter — API ชื่อตามแผน §5.2 (ครอบ engine เดิม)', () => {
  it('setNumber/readNumber round-trip', () => {
    expect(readNumber(setNumberA(4, 2580))).toBe(2580)
    expect(readNumber(createAbacusState(4))).toBe(0)
  })

  it('moveBead upper/lower แก้ค่าถูกตามกฎ soroban', () => {
    let s = createAbacusState(3)
    s = moveBead(s, 0, 'upper') // หน่วย +5
    s = moveBead(s, 0, 'lower', 1) // หน่วย +1 = 6
    expect(readNumber(s)).toBe(6)
    s = moveBead(s, 1, 'lower', 3) // สิบ = 3 → 36
    expect(readNumber(s)).toBe(36)
    s = moveBead(s, 0, 'upper') // ถอนเม็ดบนหน่วย → 31
    expect(readNumber(s)).toBe(31)
  })

  it('applyOperation (adapter) ให้ผลเท่า engine + state อ่านกลับได้', () => {
    const r = applyOperationA(setNumberA(4, 52), '-', 27)
    expect(r.result).toBe(25)
    expect(readNumber(r.state)).toBe(25)
  })

  it('validateState ครอบ engine', () => {
    expect(validateStateA(createAbacusState(4))).toBe(true)
  })
})
