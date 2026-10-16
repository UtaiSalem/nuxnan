/**
 * Soroban (ลูกคิดญี่ปุ่น) engine — โมดูลคำนวณแยกจาก UI ตามข้อแนะนำของแผนพัฒนา
 * เกมจินตคณิต: "กลไกคำนวณลูกคิดต้องเป็นโมดูลแยกจาก UI และมีชุดทดสอบทางคณิต
 * โดยเฉพาะ" เพื่อให้มั่นใจว่าโจทย์/คำตอบ/ขั้นตอนการเลื่อนเม็ดถูกต้อง
 *
 * โครงสร้างลูกคิด 1 หลัก (column):
 *   - heaven bead (เม็ดบน) 1 เม็ด = ค่า 5 เมื่อ "ชิดคาน" (active)
 *   - earth beads (เม็ดล่าง) 4 เม็ด = ค่า 1 ต่อเม็ด เมื่อชิดคาน
 *   ค่าของหลัก = (heaven ? 5 : 0) + earth  (0..9)
 *
 * index 0 = หลักหน่วย (units), index เพิ่ม = หลักสูงขึ้น (tens, hundreds, …)
 */

export interface Column {
  /** เม็ดบนชิดคานหรือไม่ (active = มีค่า 5) */
  heaven: boolean
  /** จำนวนเม็ดล่างที่ชิดคาน 0..4 */
  earth: number
}

export type Abacus = Column[]

/** กฎการบวก/ลบ 1 หลักตามแบบ soroban */
export type AddRule = 'direct' | 'little_friend' | 'big_friend'
export type SubRule = 'direct' | 'little_friend' | 'big_friend'

export interface AddDigitStep {
  rule: AddRule
  /** ตัวทดไปหลักถัดไป (0 หรือ 1) */
  carry: 0 | 1
  /** ค่าของหลักนี้หลังทำเสร็จ 0..9 */
  result: number
  /** คำอธิบายเชิงสอน เช่น "+7 = +10 − 3" */
  hint: string
}

export interface SubDigitStep {
  rule: SubRule
  /** การยืมจากหลักถัดไป (0 หรือ 1) */
  borrow: 0 | 1
  result: number
  hint: string
}

// ----------------------------------------------------------------------------
// การแทนค่า (representation)
// ----------------------------------------------------------------------------

export function makeColumn(heaven = false, earth = 0): Column {
  return { heaven, earth: clampEarth(earth) }
}

function clampEarth(n: number): number {
  if (n < 0) return 0
  if (n > 4) return 4
  return Math.floor(n)
}

/** ค่าของ 1 หลัก (0..9) */
export function columnValue(col: Column): number {
  return (col.heaven ? 5 : 0) + clampEarth(col.earth)
}

/** แปลงตัวเลข 0..9 เป็นสถานะหลัก */
export function digitToColumn(digit: number): Column {
  const d = Math.max(0, Math.min(9, Math.floor(digit)))
  return { heaven: d >= 5, earth: d % 5 }
}

/** สร้างลูกคิดว่างตามจำนวนหลัก */
export function createAbacus(width: number): Abacus {
  return Array.from({ length: Math.max(1, width) }, () => makeColumn())
}

/** ค่ารวมของลูกคิด (index 0 = หน่วย) */
export function abacusValue(abacus: Abacus): number {
  return abacus.reduce((sum, col, i) => sum + columnValue(col) * 10 ** i, 0)
}

/** แปลงตัวเลขเป็นสถานะลูกคิดกว้าง `width` หลัก (index 0 = หน่วย) */
export function setAbacusValue(width: number, value: number): Abacus {
  const w = Math.max(1, width)
  const v = Math.max(0, Math.floor(value))
  const cols = createAbacus(w)
  const digits = String(v).split('').reverse().map(Number) // [units, tens, ...]
  for (let i = 0; i < w; i++) {
    cols[i] = digitToColumn(digits[i] ?? 0)
  }
  return cols
}

/** เม็ดล่างกี่เม็ดที่ "ยกได้อีก" (ว่าง) ในหลักนี้ */
export function earthRoom(col: Column): number {
  return 4 - clampEarth(col.earth)
}

/** แตะเม็ดบน: สลับสถานะชิดคาน (±5) */
export function toggleHeaven(col: Column): Column {
  return makeColumn(!col.heaven, col.earth)
}

/**
 * แตะเม็ดล่างตำแหน่ง `p` (1..4 นับจากคานลงไป) ตามกฎ soroban:
 *   - แตะเม็ดที่ "ยังไม่ชิดคาน" → ดันเม็ดนั้น + เม็ดที่อยู่เหนือมันขึ้นชิดคาน (earth = p)
 *   - แตะเม็ดที่ "ชิดคานแล้ว"   → ถอนเม็ดนั้น + เม็ดที่อยู่ล่างมันลง (earth = p − 1)
 */
export function tapEarth(col: Column, p: number): Column {
  const pos = Math.max(1, Math.min(4, Math.floor(p)))
  const cur = clampEarth(col.earth)
  const newEarth = pos <= cur ? pos - 1 : pos
  return makeColumn(col.heaven, newEarth)
}

// ----------------------------------------------------------------------------
// กฎการบวก 1 หลัก (คู่ 5 / คู่ 10)
// ----------------------------------------------------------------------------

/**
 * วางแผนบวกเลข `addend` (1..9) เข้ากับหลักที่มีค่า `current` (0..9)
 * ตามกฎ soroban:
 *   - direct        : เลื่อนเม็ดเพิ่มได้ตรง ๆ (เม็ดล่างและ/หรือเม็ดบนพอ)
 *   - little_friend : คู่ 5 — เม็ดล่างไม่พอแต่ยังไม่ทด เช่น 4+1 = +5 − 4
 *   - big_friend    : คู่ 10 — ผลรวม ≥ 10 ต้องทด เช่น 8+7 = +10 − 3
 */
export function planAddDigit(current: number, addend: number): AddDigitStep {
  const c = clampDigit(current)
  const a = Math.max(1, Math.min(9, Math.floor(addend)))
  const sum = c + a

  if (sum >= 10) {
    // คู่ 10 (big friend): +a = +10 − (10 − a) และทด 1
    const sub = 10 - a
    return {
      rule: 'big_friend',
      carry: 1,
      result: sum - 10,
      hint: `+${a} = +10 − ${sub}`
    }
  }

  // ไม่ต้องทด — อยู่ในหลักเดียว
  const hDelta = (sum >= 5 ? 1 : 0) - (c >= 5 ? 1 : 0)
  const eDelta = (sum % 5) - (c % 5)

  if (hDelta >= 0 && eDelta >= 0) {
    return { rule: 'direct', carry: 0, result: sum, hint: `+${a}` }
  }

  // ต้องเติมเม็ดบนแล้วถอนเม็ดล่าง → คู่ 5
  return {
    rule: 'little_friend',
    carry: 0,
    result: sum,
    hint: `+${a} = +5 − ${5 - a}`
  }
}

/**
 * วางแผนลบเลข `sub` (1..9) ออกจากหลักที่มีค่า `current` (0..9)
 *   - direct        : ถอนเม็ดได้ตรง ๆ
 *   - little_friend : คู่ 5 — เช่น 5 − 1 = −5 + 4
 *   - big_friend    : คู่ 10 — ต้องยืมจากหลักถัดไป เช่น 13 − 4 → หลักหน่วย 3 − 4 = −10 + 6
 */
export function planSubDigit(current: number, sub: number): SubDigitStep {
  const c = clampDigit(current)
  const s = Math.max(1, Math.min(9, Math.floor(sub)))
  const diff = c - s

  if (diff < 0) {
    // คู่ 10 (big friend): −s = −10 + (10 − s) และยืม 1
    const add = 10 - s
    return {
      rule: 'big_friend',
      borrow: 1,
      result: c + add,
      hint: `−${s} = −10 + ${add}`
    }
  }

  const hDelta = (diff >= 5 ? 1 : 0) - (c >= 5 ? 1 : 0)
  const eDelta = (diff % 5) - (c % 5)

  if (hDelta <= 0 && eDelta <= 0) {
    return { rule: 'direct', borrow: 0, result: diff, hint: `−${s}` }
  }

  // ต้องถอนเม็ดบนแล้วเติมเม็ดล่าง → คู่ 5
  return {
    rule: 'little_friend',
    borrow: 0,
    result: diff,
    hint: `−${s} = −5 + ${5 - s}`
  }
}

function clampDigit(n: number): number {
  return Math.max(0, Math.min(9, Math.floor(n)))
}

// ----------------------------------------------------------------------------
// การคำนวณระดับค่า (ใช้ตรวจคำตอบ — คณิตจำนวนเต็มล้วน จึงถูกต้องเสมอ)
// ----------------------------------------------------------------------------

/** ผลลัพธ์จริงของ a op b (ใช้ตรวจคำตอบผู้เล่น) */
export function computeAnswer(a: number, op: '+' | '-' | '×' | '÷', b: number): number {
  switch (op) {
    case '+': return a + b
    case '-': return a - b
    case '×': return a * b
    case '÷': return b === 0 ? NaN : a / b
    default: return NaN
  }
}

/** ตรวจว่าค่าบนลูกคิดตรงกับคำตอบที่คาดหวังไหม */
export function isAbacusCorrect(abacus: Abacus, expected: number): boolean {
  return abacusValue(abacus) === expected
}

// ----------------------------------------------------------------------------
// การดำเนินการหลายหลัก (บวก/ลบ) พร้อมขั้นตอน — สำหรับโหมดเรียน/คำใบ้ (แผน §5.2)
// ----------------------------------------------------------------------------

export type Operation = '+' | '-'

export interface OperationStep {
  /** index หลัก (0 = หน่วย) */
  place: number
  /** จำนวนที่บวก/ลบที่หลักนี้ (รวมตัวทด/ยืมจากหลักก่อนหน้า) 1..10 */
  amount: number
  /** กฎที่ใช้ที่หลักนี้ */
  rule: AddRule | SubRule
  /** ทด (บวก) / ยืม (ลบ) ไปหลักถัดไป */
  carry: 0 | 1
  /** ค่าหลักนี้หลังทำ 0..9 */
  result: number
  /** คำอธิบายเชิงสอน */
  hint: string
}

export interface OperationResult {
  abacus: Abacus
  steps: OperationStep[]
  result: number
  /** ผลบวกล้นจำนวนหลัก */
  overflow: boolean
  /** ผลติดลบ (ลบเกิน) */
  underflow: boolean
}

const PLACE_NAMES = ['หน่วย', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน']
function placeName(i: number): string {
  return PLACE_NAMES[i] ?? `หลัก 10^${i}`
}

/**
 * บวก/ลบจำนวนเต็มไม่ติดลบเข้ากับลูกคิด เดินทีละหลัก (หน่วย→สูง) พร้อมตัวทด/ยืม
 * ใช้ planAddDigit/planSubDigit (ที่ทดสอบแล้ว) จำแนกกฎในแต่ละหลัก
 */
export function applyOperation(abacus: Abacus, op: Operation, operand: number): OperationResult {
  const width = abacus.length
  const next = abacus.map((c) => makeColumn(c.heaven, c.earth))
  const steps: OperationStep[] = []
  const digits = String(Math.max(0, Math.floor(operand))).split('').reverse().map(Number)
  let carry = 0

  for (let i = 0; i < width; i++) {
    const amount = (digits[i] ?? 0) + carry
    if (amount === 0) { carry = 0; continue }
    const cur = columnValue(next[i])

    if (amount === 10) {
      // +10 / −10 ล้วน: ค่าหลักไม่เปลี่ยน แค่ทด/ยืม 1
      next[i] = digitToColumn(cur)
      carry = 1
      steps.push({
        place: i,
        amount,
        rule: 'big_friend',
        carry: 1,
        result: cur,
        hint: `หลัก${placeName(i)}: ${op === '+' ? '+10 (ทด 1)' : '−10 (ยืม 1)'}`
      })
      continue
    }

    if (op === '+') {
      const step = planAddDigit(cur, amount)
      next[i] = digitToColumn(step.result)
      carry = step.carry
      steps.push({
        place: i, amount, rule: step.rule, carry: step.carry, result: step.result,
        hint: `หลัก${placeName(i)}: ${step.hint}${step.carry ? ' (ทด 1)' : ''}`
      })
    } else {
      const step = planSubDigit(cur, amount)
      next[i] = digitToColumn(step.result)
      carry = step.borrow
      steps.push({
        place: i, amount, rule: step.rule, carry: step.borrow, result: step.result,
        hint: `หลัก${placeName(i)}: ${step.hint}${step.borrow ? ' (ยืม 1)' : ''}`
      })
    }
  }

  const overflow = op === '+' && carry === 1
  const underflow = op === '-' && carry === 1
  // รักษาความถูกต้องปลายทางเมื่อ over/underflow (ไม่งั้น next = ค่าที่พันรอบ/ผิด)
  const finalAbacus = underflow ? createAbacus(width) : next
  return { abacus: finalAbacus, steps, result: abacusValue(finalAbacus), overflow, underflow }
}

/** คำใบ้ทีละขั้นของการบวก/ลบ (แผน §5.2 getHint) */
export function getHint(abacus: Abacus, op: Operation, operand: number): string[] {
  return applyOperation(abacus, op, operand).steps.map((s) => s.hint)
}

/** ตรวจว่าสถานะลูกคิดถูกกติกา (ไม่มีหลักที่เป็นไปไม่ได้) — แผน §5.2 validateState */
export function validateState(abacus: Abacus): boolean {
  return (
    Array.isArray(abacus) &&
    abacus.length > 0 &&
    abacus.every(
      (c) => typeof c.heaven === 'boolean' && Number.isInteger(c.earth) && c.earth >= 0 && c.earth <= 4
    )
  )
}
