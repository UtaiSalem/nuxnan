/**
 * คะแนนข้อสอบเก็บเป็น decimal(8,2) ฝั่ง DB ⇒ API อาจคืนมาเป็น "1.00" หรือ 0.5
 * ผู้สอนตั้งคะแนนได้ทีละ 0.5 เท่านั้น การแสดงผลจึงตัดศูนย์ท้ายทิ้งให้อ่านง่าย
 * 1 -> "1" · "1.00" -> "1" · 0.5 -> "0.5" · 2.50 -> "2.5"
 */
export const SCORE_STEP = 0.5
export const SCORE_MIN = 0.5
export const SCORE_MAX = 1000

export function formatScore(value: unknown, fallback = '0'): string {
  const n = Number(value)
  if (!Number.isFinite(n)) return fallback

  return n.toLocaleString('th-TH', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
    useGrouping: false,
  })
}

/**
 * ปัดค่าที่ผู้ใช้พิมพ์ให้ลงล็อก 0.5 และอยู่ในช่วงที่ backend ยอมรับ
 * (backend: required|numeric|min:0.5|max:1000|multiple_of:0.5)
 */
export function normalizeScore(value: unknown): number {
  const n = Number(value)
  if (!Number.isFinite(n)) return SCORE_MIN

  const snapped = Math.round(n / SCORE_STEP) * SCORE_STEP

  return Math.min(SCORE_MAX, Math.max(SCORE_MIN, Number(snapped.toFixed(2))))
}

export function isValidScore(value: unknown): boolean {
  const n = Number(value)

  return Number.isFinite(n)
    && n >= SCORE_MIN
    && n <= SCORE_MAX
    && Math.abs(n / SCORE_STEP - Math.round(n / SCORE_STEP)) < 1e-9
}
