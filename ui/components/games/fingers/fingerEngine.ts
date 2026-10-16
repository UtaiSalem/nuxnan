/**
 * FingerGestureEngine — ท่ามือแทนจำนวน (แผน "หลักสูตรนิ้วมือก่อนลูกคิด" §6)
 *
 * กติกา (ผู้ใช้ยืนยัน): มือซ้าย=หลักสิบ · มือขวา=หลักหน่วย · ต่อมือแทน 0–9 ด้วย
 * หัวแม่มือ=5 และนิ้วชี้..ก้อย=1–4 (เช่น 8 = 5 + 3) — โครงสร้างเดียวกับ soroban
 * (thumb ≈ เม็ดบน heaven, extended ≈ เม็ดล่าง earth)
 *
 * แยก "ค่าของมือ (0–9)" ออกจาก "ท่าทางที่แสดง (นิ้วไหนกาง)" ตามข้อแนะนำของแผน
 * เพื่อให้เปลี่ยน convention ได้โดยไม่ต้องแก้ตรรกะคำนวณ
 */

export type Finger = 'thumb' | 'index' | 'middle' | 'ring' | 'pinky'

/**
 * ท่ามือหนึ่งข้าง — ชั้น "ค่า"
 * ค่า = (thumb ? 5 : 0) + extended, โดย extended = จำนวนนิ้ว 1–4 ที่กาง (0..4)
 */
export interface HandGesture {
  thumb: boolean
  extended: number
}

/** สถานะสองมือ: tens=หลักสิบ (ซ้าย) · units=หลักหน่วย (ขวา) */
export interface HandsState {
  tens: HandGesture
  units: HandGesture
}

/** กติกาการกางนิ้ว — ชั้น "ท่าทาง" (config เปลี่ยนได้ตามหลักสูตร) */
export interface GestureConvention {
  /** ค่าของหัวแม่มือ (ต้นแบบ = 5) */
  thumbValue: 5
  /** ลำดับนิ้วที่กางเพื่อแทนค่า 1→4 */
  fingerOrder: [Finger, Finger, Finger, Finger]
}

/** กติกาต้นแบบ (ผู้ใช้ยืนยัน): หัวแม่มือ=5, นิ้วชี้→ก้อย = 1→4 */
export const DEFAULT_CONVENTION: GestureConvention = {
  thumbValue: 5,
  fingerOrder: ['index', 'middle', 'ring', 'pinky']
}

function clampExtended(n: number): number {
  if (n < 0) return 0
  if (n > 4) return 4
  return Math.floor(n)
}

export function makeHand(thumb = false, extended = 0): HandGesture {
  return { thumb, extended: clampExtended(extended) }
}

/** ค่าของมือเดียว 0..9 */
export function handValue(hand: HandGesture): number {
  return (hand.thumb ? 5 : 0) + clampExtended(hand.extended)
}

/** แปลงเลข 0..9 เป็นท่ามือ */
export function digitToHand(digit: number): HandGesture {
  const d = Math.max(0, Math.min(9, Math.floor(digit)))
  return { thumb: d >= 5, extended: d % 5 }
}

/** รายชื่อนิ้วที่ต้องกางสำหรับท่ามือนี้ (ชั้นแสดงผล — ใช้ convention) */
export function fingersUp(hand: HandGesture, convention: GestureConvention = DEFAULT_CONVENTION): Finger[] {
  const up: Finger[] = []
  if (hand.thumb) up.push('thumb')
  const n = clampExtended(hand.extended)
  for (let i = 0; i < n; i++) up.push(convention.fingerOrder[i])
  return up
}

/** ตรวจว่าท่ามือถูกกติกา (ไม่มีสถานะที่เป็นไปไม่ได้) */
export function validateHand(hand: HandGesture): boolean {
  return (
    !!hand &&
    typeof hand.thumb === 'boolean' &&
    Number.isInteger(hand.extended) &&
    hand.extended >= 0 &&
    hand.extended <= 4
  )
}

// ----------------------------------------------------------------------------
// PlaceValueEngine — รวมสองมือเป็นจำนวน 0..99
// ----------------------------------------------------------------------------

export function createHandsState(): HandsState {
  return { tens: makeHand(), units: makeHand() }
}

/** ค่ารวมสองมือ 0..99 (ซ้าย=สิบ ขวา=หน่วย) */
export function handsValue(state: HandsState): number {
  return handValue(state.tens) * 10 + handValue(state.units)
}

/** แปลงจำนวน 0..99 เป็นท่าสองมือ */
export function numberToHands(n: number): HandsState {
  const v = Math.max(0, Math.min(99, Math.floor(n)))
  return { tens: digitToHand(Math.floor(v / 10)), units: digitToHand(v % 10) }
}

export function validateHands(state: HandsState): boolean {
  return validateHand(state.tens) && validateHand(state.units)
}
