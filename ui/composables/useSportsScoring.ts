/**
 * คะแนนกีฬาสี (S-S4 backend / S-S6 หน้าจอ)
 *
 * ทุก endpoint อยู่ใต้ครั้งที่จัด (`sports_editions`) ไม่ใช่ปีการศึกษา — หน่วยขอบเขตเดียวกับ
 * useHouseAssignments ดังนั้นหน้าจอต้องเลือก edition ก่อนเสมอ
 *
 * คะแนนมาจาก 2 ทางตามเกณฑ์จริง (§3 ของ .agents/school-admin/27-sports-day.md):
 *   - placing : บันทึกอันดับ แล้ว "ฝั่ง API" เป็นคนแปลงเป็นคะแนนจาก scoring_table
 *   - judged  : กรรมการให้คะแนนเป็นตัวเลขตรง ๆ (พาเหรด/กองเชียร์ เต็ม 100)
 *   - manual  : ให้/หักด้วยมือ ไม่ผูกกับรายการแข่ง (points ติดลบได้)
 *
 * ⚠️ pointsForPlacing() ในไฟล์นี้ใช้ "แสดงตัวอย่างก่อนกดบันทึก" เท่านั้น
 *    ค่าที่ถูกต้องคือค่าที่ API คืนกลับมาในแถว entry เสมอ ห้ามเอาค่าที่คำนวณฝั่งนี้ไปเขียนทับ
 */

export type SportsDisciplineType = 'team' | 'individual' | 'judged'
export type SportsScoreSource = 'placing' | 'judged' | 'manual'

export interface SportsDiscipline {
  id: number
  edition_id: number
  academy_id: number
  name: string
  type: SportsDisciplineType
  /** {"1":9,"2":8,...} — คีย์เป็น string เสมอเพราะมาจาก JSON */
  scoring_table: Record<string, number> | null
  max_score: string | number | null
  display_order: number
  created_at?: string
  updated_at?: string
}

export interface SportsScoreEntry {
  id: number
  edition_id: number
  academy_id: number
  house_group_id: number
  discipline_id: number | null
  source: SportsScoreSource
  placing: number | null
  /** decimal:2 จาก Laravel → มาเป็น string */
  points: string | number
  note: string | null
  ref_type: string | null
  ref_id: number | null
  awarded_by_user_id: number
  voided_at: string | null
  voided_by_user_id: number | null
  created_at: string
  discipline?: SportsDiscipline | null
}

export interface SportsHouseStanding {
  id: number
  edition_id: number
  house_group_id: number
  total_points: string | number
  gold_count: number
  silver_count: number
  bronze_count: number
  /** อันดับร่วมได้เลขเดียวกันแล้วข้ามอันดับถัดไป (1,1,3) — ฝั่ง API เป็นคนคิด */
  rank: number
  computed_at: string | null
  house_group?: { id: number; name: string; settings?: Record<string, any> | null } | null
}

export interface AwardEntryPayload {
  house_group_id: number
  source: SportsScoreSource
  discipline_id?: number | null
  placing?: number | null
  points?: number | null
  note?: string | null
}

/**
 * ค่าตั้งต้นของตารางคะแนนตามเกณฑ์โรงเรียน (§3) — เป็นแค่ค่าเริ่มต้นให้ผู้ใช้แก้ต่อได้
 * ทีม: ชนะเลิศ 9 ลดหลั่นถึง 2 · เดี่ยว: ชนะเลิศ 5 ลดหลั่นถึง 2
 */
export const DEFAULT_SCORING_TABLES: Record<'team' | 'individual', Record<string, number>> = {
  team: { 1: 9, 2: 8, 3: 7, 4: 6, 5: 5, 6: 4, 7: 3, 8: 2 },
  individual: { 1: 5, 2: 4, 3: 3, 4: 2 },
}

export const DEFAULT_JUDGED_MAX_SCORE = 100

export const useSportsScoring = () => {
  const api = useApi()

  const base = (academyId: number | string, editionId: number | string) =>
    `/api/academies/${academyId}/sports-editions/${editionId}`

  // ---- รายการแข่ง (disciplines) ----

  const listDisciplines = (academyId: number, editionId: number) =>
    api.get<SportsDiscipline[]>(`${base(academyId, editionId)}/disciplines`)

  const createDiscipline = (academyId: number, editionId: number, payload: Partial<SportsDiscipline>) =>
    api.post<SportsDiscipline>(`${base(academyId, editionId)}/disciplines`, payload)

  const updateDiscipline = (
    academyId: number,
    editionId: number,
    disciplineId: number,
    payload: Partial<SportsDiscipline>,
  ) => api.put<SportsDiscipline>(`${base(academyId, editionId)}/disciplines/${disciplineId}`, payload)

  const deleteDiscipline = (academyId: number, editionId: number, disciplineId: number) =>
    api.delete(`${base(academyId, editionId)}/disciplines/${disciplineId}`)

  // ---- event log ----

  /** คืนทุกแถวรวมแถวที่ void แล้ว (ยังไม่มีการแบ่งหน้าฝั่ง API) — หน้าจอต้องแสดงว่าถูกยกเลิก ไม่ใช่ซ่อน */
  const listEntries = (
    academyId: number,
    editionId: number,
    params?: { house_group_id?: number; discipline_id?: number },
  ) => api.get<SportsScoreEntry[]>(`${base(academyId, editionId)}/score-entries`, { params })

  const awardEntry = (academyId: number, editionId: number, payload: AwardEntryPayload) =>
    api.post<SportsScoreEntry>(`${base(academyId, editionId)}/score-entries`, payload)

  /** แก้คะแนนที่ลงผิด = void แล้วลงใหม่ ห้ามแก้ทับ (§10.2) */
  const voidEntry = (academyId: number, editionId: number, entryId: number) =>
    api.post<SportsScoreEntry>(`${base(academyId, editionId)}/score-entries/${entryId}/void`, {})

  // ---- ตารางคะแนนรวม ----

  const listStandings = (academyId: number, editionId: number) =>
    api.get<SportsHouseStanding[]>(`${base(academyId, editionId)}/standings`)

  const rebuildStandings = (academyId: number, editionId: number) =>
    api.post<SportsHouseStanding[]>(`${base(academyId, editionId)}/standings/rebuild`, {})

  // ---- helper ----

  /** มิเรอร์ของ SportsScoringService::pointsForPlacing() — อันดับที่เกินตารางได้ 0 ไม่ใช่ error */
  const pointsForPlacing = (discipline: SportsDiscipline | null | undefined, placing: number): number => {
    const table = discipline?.scoring_table
    if (!table) return 0
    const value = table[String(placing)]
    return value === undefined || value === null ? 0 : Number(value)
  }

  const defaultScoringTable = (type: SportsDisciplineType): Record<string, number> | null =>
    type === 'judged' ? null : { ...DEFAULT_SCORING_TABLES[type] }

  const num = (value: string | number | null | undefined): number => Number(value ?? 0)

  return {
    listDisciplines,
    createDiscipline,
    updateDiscipline,
    deleteDiscipline,
    listEntries,
    awardEntry,
    voidEntry,
    listStandings,
    rebuildStandings,
    pointsForPlacing,
    defaultScoringTable,
    num,
  }
}
