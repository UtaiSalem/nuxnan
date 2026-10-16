import { describe, expect, it } from 'vitest'
import {
  type Finger,
  makeHand,
  handValue,
  digitToHand,
  fingersUp,
  validateHand,
  createHandsState,
  handsValue,
  numberToHands,
  validateHands,
  DEFAULT_CONVENTION
} from '../components/games/fingers/fingerEngine'

describe('fingerEngine — มือเดียว 0..9 (หัวแม่มือ=5, นิ้ว 1–4)', () => {
  it('digitToHand/handValue ตรงทุกค่า 0..9', () => {
    for (let d = 0; d <= 9; d++) {
      const hand = digitToHand(d)
      expect(hand.thumb).toBe(d >= 5)
      expect(hand.extended).toBe(d % 5)
      expect(handValue(hand)).toBe(d)
    }
  })

  it('handValue clamp extended ให้อยู่ 0..4', () => {
    expect(handValue(makeHand(false, 9))).toBe(4)
    expect(handValue(makeHand(true, -2))).toBe(5)
  })

  it('fingersUp กางนิ้วถูกตามกติกาต้นแบบ', () => {
    expect(fingersUp(digitToHand(0))).toEqual([])
    expect(fingersUp(digitToHand(1))).toEqual<Finger[]>(['index'])
    expect(fingersUp(digitToHand(3))).toEqual<Finger[]>(['index', 'middle', 'ring'])
    expect(fingersUp(digitToHand(4))).toEqual<Finger[]>(['index', 'middle', 'ring', 'pinky'])
    expect(fingersUp(digitToHand(5))).toEqual<Finger[]>(['thumb'])
    expect(fingersUp(digitToHand(8))).toEqual<Finger[]>(['thumb', 'index', 'middle', 'ring'])
    expect(fingersUp(digitToHand(9))).toEqual<Finger[]>(['thumb', 'index', 'middle', 'ring', 'pinky'])
  })

  it('fingersUp เปลี่ยนตาม convention ที่กำหนดเองได้', () => {
    const custom = { thumbValue: 5 as const, fingerOrder: ['pinky', 'ring', 'middle', 'index'] as [Finger, Finger, Finger, Finger] }
    expect(fingersUp(digitToHand(2), custom)).toEqual<Finger[]>(['pinky', 'ring'])
    // default ยังเหมือนเดิม (ไม่ถูกแก้)
    expect(fingersUp(digitToHand(2))).toEqual<Finger[]>(['index', 'middle'])
    expect(DEFAULT_CONVENTION.fingerOrder[0]).toBe('index')
  })

  it('validateHand จับสถานะผิดกติกา', () => {
    expect(validateHand(digitToHand(7))).toBe(true)
    expect(validateHand({ thumb: true, extended: 5 })).toBe(false)
    expect(validateHand({ thumb: true, extended: -1 })).toBe(false)
  })
})

describe('fingerEngine — สองมือ 0..99 (ซ้าย=สิบ ขวา=หน่วย)', () => {
  it('round-trip ทุกจำนวน 0..99', () => {
    for (let v = 0; v <= 99; v++) {
      expect(handsValue(numberToHands(v))).toBe(v)
    }
  })

  it('แยกหลักสิบ/หน่วยถูก (47 = 4 สิบ + 7 หน่วย)', () => {
    const h = numberToHands(47)
    expect(handValue(h.tens)).toBe(4)
    expect(handValue(h.units)).toBe(7)
    expect(handsValue(h)).toBe(47)
  })

  it('ตัวอย่างจากแผน: 05, 30, 82, 99', () => {
    expect(handsValue(numberToHands(5))).toBe(5)
    expect(handsValue(numberToHands(30))).toBe(30)
    expect(handsValue(numberToHands(82))).toBe(82)
    expect(handsValue(numberToHands(99))).toBe(99)
  })

  it('createHandsState ว่าง = 0 และ validateHands ผ่าน', () => {
    const s = createHandsState()
    expect(handsValue(s)).toBe(0)
    expect(validateHands(s)).toBe(true)
  })
})
