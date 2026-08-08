/**
 * คณะสี (sports houses) — แบ่งนักเรียนเข้าคณะสีของงานกีฬาสี "แต่ละครั้งที่จัด"
 *
 * หน่วยขอบเขตคือ sports_editions ไม่ใช่ปีการศึกษา — ปีหนึ่งจัดได้หลายครั้ง และแต่ละครั้ง
 * ใช้จำนวนคณะสีต่างกันได้ ปีการศึกษาเป็นคุณสมบัติของครั้งนั้น ไม่ใช่คีย์
 *
 * โหมดสุ่มกับโหมดนำเข้าสร้าง batch แบบเดียวกัน แล้วเดิน preview → commit → undo
 * เส้นเดียวกัน ฝั่ง UI จึงเรียกต่างกันแค่ขั้นสร้าง batch เท่านั้น
 */

export type HouseAssignmentMode = 'random' | 'import'
export type HouseAssignmentStatus = 'draft' | 'committed' | 'undone'
export type HouseRowStatus =
  | 'ok'
  | 'unmatched'
  | 'ambiguous'
  | 'unknown_house'
  | 'already_assigned'
  | 'skipped'

export interface HouseAssignmentSummary {
  total: number
  /** house_group_id => จำนวนคน */
  per_house: Record<string, number>
  /** house_group_id => ระดับชั้น => จำนวนคน */
  per_house_by_grade: Record<string, Record<string, number>>
  by_status?: Record<HouseRowStatus, number>
  skipped_count?: number
  moved_count?: number
}

export interface HouseAssignmentBatch {
  id: string
  academy_id: number
  edition_id: number
  mode: HouseAssignmentMode
  status: HouseAssignmentStatus
  options: Record<string, any>
  summary: HouseAssignmentSummary | null
  source_filename: string | null
  committed_at: string | null
  undone_at: string | null
  created_at: string
}

export interface HouseAssignmentRow {
  id: number
  row_number: number
  raw: Record<string, any> | null
  student_id: number | null
  house_group_id: number | null
  previous_house_group_id: number | null
  status: HouseRowStatus
  message: string | null
  student?: { id: number; student_id: string; first_name_th: string; last_name_th: string } | null
}

export interface RandomPreviewPayload {
  edition_id: number
  house_group_ids: number[]
  strategy?: 'stratified' | 'pure_random'
  balance_gender?: boolean
  scope?: 'unassigned_only' | 'all'
  seed?: number | null
}

export type SportsEditionStatus = 'draft' | 'active' | 'closed'

export interface SportsEditionHouse {
  id: number; edition_id: number; house_group_id: number; display_order: number
}

export interface SportsEdition {
  id: number
  academy_id: number
  academic_year_id: number
  school_event_id: number | null
  name: string
  sequence: number
  status: SportsEditionStatus
  starts_on: string | null
  ends_on: string | null
  houses?: SportsEditionHouse[]
  students_count?: number
  created_at: string
}

export const useHouseAssignments = () => {
  const api = useApi()

  const base = (academyId: number | string) => `/api/academies/${academyId}/house-assignments`

  const listBatches = <T = any>(academyId: number, params?: Record<string, any>) =>
    api.get<T>(base(academyId), { params })

  /**
   * ยอดคนต่อคณะสีที่ "มีผลจริง" ของครั้งนั้น — อ่านจาก house_memberships ไม่ใช่สมาชิกกลุ่ม
   * API เติมค่า 0 ให้ทุกคณะสีของครั้งนั้นด้วย เพื่อให้เห็นสีที่ยังไม่มีคน — อย่ากรองศูนย์ทิ้ง
   */
  const getCurrentCounts = <T = any>(academyId: number, editionId: number) =>
    api.get<T>(`${base(academyId)}/current`, { params: { edition_id: editionId } })

  const previewRandom = <T = any>(academyId: number, payload: RandomPreviewPayload) =>
    api.post<T>(`${base(academyId)}/preview-random`, payload)

  const previewImport = <T = any>(
    academyId: number,
    editionId: number,
    file: File,
    columnMapping: Record<string, string>,
    onConflict: 'skip' | 'overwrite' = 'skip',
  ) => {
    const form = new FormData()
    form.append('file', file)
    form.append('edition_id', String(editionId))
    form.append('on_conflict', onConflict)
    form.append('column_mapping', JSON.stringify(columnMapping))

    return api.post<T>(`${base(academyId)}/preview-import`, form)
  }

  const getBatch = <T = any>(academyId: number, batchId: string) =>
    api.get<T>(`${base(academyId)}/${batchId}`)

  const getRows = <T = any>(
    academyId: number,
    batchId: string,
    params?: { status?: HouseRowStatus; page?: number; per_page?: number },
  ) => api.get<T>(`${base(academyId)}/${batchId}/rows`, { params })

  const commitBatch = <T = any>(academyId: number, batchId: string) =>
    api.post<T>(`${base(academyId)}/${batchId}/commit`, {})

  const undoBatch = <T = any>(academyId: number, batchId: string) =>
    api.post<T>(`${base(academyId)}/${batchId}/undo`, {})

  const discardBatch = <T = any>(academyId: number, batchId: string) =>
    api.delete<T>(`${base(academyId)}/${batchId}`)

  /**
   * ต้องดึงเป็น blob ไม่ใช่ลิงก์ตรง — endpoint นี้มี academy.permission กำกับ และ API ใช้ JWT
   * ลิงก์ <a href> ธรรมดาไม่ได้แนบ Authorization header จึงโดน 401
   */
  const downloadTemplate = async (academyId: number) => {
    const { blob, filename } = await api.getBlob(`${base(academyId)}/template`)
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = filename || 'house-assignment-template.csv'
    document.body.appendChild(link)
    link.click()
    link.remove()
    URL.revokeObjectURL(url)
  }

  const listHouses = <T = any>(academyId: number) =>
    api.get<T>(`/api/academies/${academyId}/groups/type/house`)

  const listAcademicYears = <T = any>(academyId: number) =>
    api.get<T>(`/api/academies/${academyId}/academic-years`)

  const listEditions = <T = any>(academyId: number) =>
    api.get<T>(`/api/academies/${academyId}/sports-editions`)

  const createEdition = <T = any>(academyId: number, payload: Partial<SportsEdition> & { house_group_ids?: number[] }) =>
    api.post<T>(`/api/academies/${academyId}/sports-editions`, payload)

  const updateEdition = <T = any>(academyId: number, editionId: number, payload: Partial<SportsEdition> & { house_group_ids?: number[] }) =>
    api.put<T>(`/api/academies/${academyId}/sports-editions/${editionId}`, payload)

  const activateEdition = <T = any>(academyId: number, editionId: number) =>
    api.post<T>(`/api/academies/${academyId}/sports-editions/${editionId}/activate`, {})

  const closeEdition = <T = any>(academyId: number, editionId: number) =>
    api.post<T>(`/api/academies/${academyId}/sports-editions/${editionId}/close`, {})

  const deleteEdition = <T = any>(academyId: number, editionId: number) =>
    api.delete<T>(`/api/academies/${academyId}/sports-editions/${editionId}`)

  const editionHouseIds = (edition: SportsEdition | null): number[] => {
    if (!edition || !edition.houses) return []
    return [...edition.houses]
      .sort((a, b) => a.display_order - b.display_order)
      .map((h) => h.house_group_id)
  }

  /** batch ย้อนกลับได้ 24 ชม. หลัง commit — ตรงกับ HouseAssignmentBatch::isUndoable() ฝั่ง API */
  const isUndoable = (batch: HouseAssignmentBatch | null | undefined): boolean => {
    if (!batch || batch.status !== 'committed' || !batch.committed_at) return false
    return new Date(batch.committed_at).getTime() + 24 * 60 * 60 * 1000 > Date.now()
  }

  return {
    listBatches,
    getCurrentCounts,
    previewRandom,
    previewImport,
    getBatch,
    getRows,
    commitBatch,
    undoBatch,
    discardBatch,
    downloadTemplate,
    listHouses,
    listAcademicYears,
    isUndoable,
    listEditions,
    createEdition,
    updateEdition,
    activateEdition,
    closeEdition,
    deleteEdition,
    editionHouseIds,
  }
}
