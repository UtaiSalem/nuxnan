import { describe, expect, it } from 'vitest'
import {
  columnValue,
  digitToColumn,
  abacusValue,
  setAbacusValue,
  planAddDigit,
  planSubDigit,
  computeAnswer,
  isAbacusCorrect
} from '../components/games/abacus/abacusEngine'

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
