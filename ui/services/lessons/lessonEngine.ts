/**
 * LessonEngine — เส้นทางเรียนจินตคณิต + ประเมินความชำนาญ + กติกาปลดล็อกด่าน (แผน §4)
 * บริสุทธิ์ (ไม่มี side-effect) ทดสอบแยกได้ · การเก็บ/โหลดอยู่ที่ useLessonProgress
 *
 * กติกา: ด่านแรกเปิดเสมอ · ด่านถัดไปปลดเมื่อด่านก่อนหน้า "เชี่ยวชาญ"
 * (≥85% จากอย่างน้อย 8 ข้อ) — ไม่ปลดเพราะจำนวนข้อล้วน แต่ดูความแม่นยำ
 */

export type MasteryLevel = 'locked' | 'learning' | 'fluent' | 'mastered'

export interface StageStat {
  correct: number
  total: number
  bestStreak: number
}

export interface LessonStage {
  id: string
  order: number
  title: string
  route: string
}

/** เส้นทางเรียน (เรียงตามลำดับ) — ตรงกับการ์ดในหน้า games hub */
export const LESSON_STAGES: LessonStage[] = [
  { id: 'fingers', order: 1, title: 'ฝึกนิ้วมือ', route: '/practice/fingers' },
  { id: 'finger-math', order: 2, title: 'คิดเลขด้วยนิ้วมือ', route: '/practice/finger-math' },
  { id: 'bridge', order: 3, title: 'นิ้วมือ ↔ ลูกคิด', route: '/practice/bridge' },
  { id: 'abacus', order: 4, title: 'ฝึกลูกคิด', route: '/practice/abacus' },
  { id: 'anzan', order: 5, title: 'คิดในใจ (Anzan)', route: '/practice/anzan' }
]

/** จำนวนข้อขั้นต่ำก่อนประเมินความชำนาญ */
export const MIN_ATTEMPTS = 8
/** 60–84% = คล่อง */
export const FLUENT_ACCURACY = 0.6
/** ≥85% = เชี่ยวชาญ (ปลดล็อกด่านถัดไป) */
export const MASTER_ACCURACY = 0.85

export function emptyStat(): StageStat {
  return { correct: 0, total: 0, bestStreak: 0 }
}

export function accuracyOf(stat: StageStat): number {
  return stat.total === 0 ? 0 : stat.correct / stat.total
}

/** ระดับความชำนาญของด่าน (ยังไม่พิจารณาว่า locked) */
export function masteryOf(stat: StageStat): Exclude<MasteryLevel, 'locked'> {
  if (stat.total < MIN_ATTEMPTS) return 'learning'
  const acc = accuracyOf(stat)
  if (acc >= MASTER_ACCURACY) return 'mastered'
  if (acc >= FLUENT_ACCURACY) return 'fluent'
  return 'learning'
}

function statFor(id: string, stats: Record<string, StageStat>): StageStat {
  return stats[id] ?? emptyStat()
}

/** ด่านนี้ปลดล็อกไหม: ด่านแรกปลดเสมอ · ถัดไปปลดเมื่อด่านก่อนหน้า mastered */
export function isStageUnlocked(stageId: string, stats: Record<string, StageStat>): boolean {
  const stage = LESSON_STAGES.find((s) => s.id === stageId)
  if (!stage) return false
  if (stage.order <= 1) return true
  const prev = LESSON_STAGES.find((s) => s.order === stage.order - 1)
  if (!prev) return true
  return masteryOf(statFor(prev.id, stats)) === 'mastered'
}

/** ระดับรวม (รวม locked) ของด่าน */
export function stageLevel(stageId: string, stats: Record<string, StageStat>): MasteryLevel {
  if (!isStageUnlocked(stageId, stats)) return 'locked'
  return masteryOf(statFor(stageId, stats))
}

/** บันทึกผล 1 ข้อ คืน stat ใหม่ (immutable) */
export function recordAttempt(stat: StageStat, correct: boolean, currentStreak = 0): StageStat {
  return {
    correct: stat.correct + (correct ? 1 : 0),
    total: stat.total + 1,
    bestStreak: Math.max(stat.bestStreak, currentStreak)
  }
}
