/**
 * Adapter — API ชื่อตามแผนพัฒนา §5.1/§5.2 (AbacusColumn{upperActive,lowerActive},
 * createAbacusState/setNumber/readNumber/moveBead/applyOperation/validateState/getHint)
 * ครอบ engine เดิม (heaven/earth) โดยไม่แตะโครงเดิม — โค้ด/เทสต์ที่อิง engine ตรง ๆ ยังใช้ได้
 *
 * ใช้เมื่ออยากเขียนตามสัญญา (contract) ของแผน เช่น service บทเรียน/โจทย์ หรือ parity กับ backend
 */
import {
  type Abacus,
  type Operation,
  type OperationStep,
  createAbacus,
  setAbacusValue,
  abacusValue,
  columnValue,
  toggleHeaven,
  tapEarth,
  applyOperation as engineApplyOperation,
  getHint as engineGetHint,
  validateState as engineValidateState
} from './abacusEngine'

export type { Operation, OperationStep }

/** 1 หลักของลูกคิด (ชื่อฟิลด์ตามแผน §5.1) */
export interface AbacusColumn {
  /** เม็ดบนชิดคาน (มีค่า 5) */
  upperActive: boolean
  /** จำนวนเม็ดล่างที่ชิดคาน 0..4 */
  lowerActive: number
}

/** สถานะลูกคิดทั้งหมด (index 0 = หลักหน่วย) */
export interface AbacusState {
  columns: AbacusColumn[]
}

export type BeadKind = 'upper' | 'lower'

// --- converters ระหว่าง AbacusState (แผน) กับ Abacus (engine) ---
export function toEngine(state: AbacusState): Abacus {
  return state.columns.map((c) => ({ heaven: c.upperActive, earth: c.lowerActive }))
}
export function fromEngine(abacus: Abacus): AbacusState {
  return { columns: abacus.map((c) => ({ upperActive: c.heaven, lowerActive: c.earth })) }
}

/** สร้างสถานะลูกคิดว่าง `width` หลัก */
export function createAbacusState(width: number): AbacusState {
  return fromEngine(createAbacus(width))
}

/** แปลงตัวเลขเป็นสถานะลูกคิด `width` หลัก */
export function setNumber(width: number, value: number): AbacusState {
  return fromEngine(setAbacusValue(width, value))
}

/** อ่านค่าตัวเลขจากสถานะลูกคิด */
export function readNumber(state: AbacusState): number {
  return abacusValue(toEngine(state))
}

/** ค่าของ 1 หลัก (0..9) */
export function columnDigit(col: AbacusColumn): number {
  return columnValue({ heaven: col.upperActive, earth: col.lowerActive })
}

/** ตรวจสถานะว่าถูกกติกา */
export function validateState(state: AbacusState): boolean {
  return engineValidateState(toEngine(state))
}

/**
 * เลื่อนเม็ด: kind='upper' = สลับเม็ดบน · kind='lower' = แตะเม็ดล่างตำแหน่ง p (1..4)
 * คืนสถานะใหม่ (immutable); index นอกช่วงคืนสถานะเดิม
 */
export function moveBead(state: AbacusState, colIndex: number, kind: BeadKind, p = 1): AbacusState {
  const engine = toEngine(state)
  if (colIndex < 0 || colIndex >= engine.length) return state
  engine[colIndex] = kind === 'upper' ? toggleHeaven(engine[colIndex]) : tapEarth(engine[colIndex], p)
  return fromEngine(engine)
}

export interface AdapterOperationResult {
  state: AbacusState
  steps: OperationStep[]
  result: number
  overflow: boolean
  underflow: boolean
}

/** บวก/ลบจำนวนเต็มไม่ติดลบ คืนสถานะใหม่ + ขั้นตอน + ผลลัพธ์ */
export function applyOperation(state: AbacusState, op: Operation, operand: number): AdapterOperationResult {
  const r = engineApplyOperation(toEngine(state), op, operand)
  return { state: fromEngine(r.abacus), steps: r.steps, result: r.result, overflow: r.overflow, underflow: r.underflow }
}

/** คำใบ้ทีละขั้น */
export function getHint(state: AbacusState, op: Operation, operand: number): string[] {
  return engineGetHint(toEngine(state), op, operand)
}
