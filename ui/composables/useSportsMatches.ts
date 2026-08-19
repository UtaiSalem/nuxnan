/**
 * จัดการตารางแข่งและผลการแข่งขันกีฬาสี (S-S6b)
 *
 * หมายเหตุสำคัญ:
 * - รอบชิงชนะเลิศ = แมตช์ที่ `round_order` สูงสุด และ `match_number === 1`
 *   คู่ชิงอันดับ 3 = `round_order` สูงสุดแต่ `match_number === 2`
 * - `POST confirm-placings` จะ void แถวคะแนน `source='placing'` ของรายการแข่งนั้นทั้งหมด
 *   รวมแถวที่ครูลงเอง แล้วลงใหม่ ⇒ หน้าจอต้องเตือนก่อนกด
 * - `POST generate-fixtures` จะ ลบคู่ที่ยังไม่แข่งทั้งหมดของรายการนั้นแล้วสร้างใหม่
 *   และจะ ตอบ 422 ถ้ามีแมตช์ที่ไม่ใช่ `scheduled` อยู่
 */

export type SportsMatchFormat = 'none' | 'knockout' | 'round_robin' | 'heats'
export type SportsMatchStatus = 'scheduled' | 'in_progress' | 'finished' | 'cancelled'
export type SportsParticipantStatus = 'ok' | 'dq' | 'dns' | 'dnf'

export interface SportsMatchParticipant {
  id: number
  match_id: number
  house_group_id: number
  slot: number
  /** decimal:2 จาก Laravel → มาเป็น string */
  score: string | number | null
  time_ms: number | null
  placing: number | null
  status: SportsParticipantStatus
}

export interface SportsMatch {
  id: number
  edition_id: number
  academy_id: number
  discipline_id: number
  round_label: string | null
  round_order: number
  match_number: number
  scheduled_at: string | null
  location: string | null
  status: SportsMatchStatus
  winner_house_group_id: number | null
  next_match_id: number | null
  next_match_slot: number | null
  note: string | null
  participants: SportsMatchParticipant[]
}

export interface SportsSuggestedPlacing {
  house_group_id: number
  placing: number
  reason: string
}

export interface SportsDisciplineResult {
  id: number
  edition_id: number
  discipline_id: number
  house_group_id: number
  placing: number
  source: 'suggested' | 'manual'
  score_entry_id: number | null
  confirmed_at: string | null
  confirmed_by_user_id: number | null
  score_entry?: { id: number; points: string | number; voided_at: string | null } | null
}

export interface GenerateFixturesPayload {
  format: SportsMatchFormat
  house_group_ids: number[]
  options?: { third_place?: boolean; lanes_per_heat?: number }
}

export interface RecordResultPayload {
  participants: Array<{
    house_group_id: number
    slot?: number | null
    score?: number | null
    time_ms?: number | null
    placing?: number | null
    status?: SportsParticipantStatus
  }>
  status?: SportsMatchStatus
}

export const useSportsMatches = () => {
  const api = useApi()

  const base = (academyId: number | string, editionId: number | string) =>
    `/api/academies/${academyId}/sports-editions/${editionId}`

  const listMatches = (academyId: number, editionId: number, params?: { discipline_id?: number }) =>
    api.get<SportsMatch[]>(`${base(academyId, editionId)}/matches`, { params })

  const createMatch = (academyId: number, editionId: number, payload: Partial<SportsMatch>) =>
    api.post<SportsMatch>(`${base(academyId, editionId)}/matches`, payload)

  const updateMatch = (academyId: number, editionId: number, matchId: number, payload: Partial<SportsMatch>) =>
    api.put<SportsMatch>(`${base(academyId, editionId)}/matches/${matchId}`, payload)

  const deleteMatch = (academyId: number, editionId: number, matchId: number) =>
    api.delete(`${base(academyId, editionId)}/matches/${matchId}`)

  const recordResult = (academyId: number, editionId: number, matchId: number, payload: RecordResultPayload) =>
    api.put<SportsMatch>(`${base(academyId, editionId)}/matches/${matchId}/result`, payload)

  const generateFixtures = (academyId: number, editionId: number, disciplineId: number, payload: GenerateFixturesPayload) =>
    api.post<SportsMatch[]>(`${base(academyId, editionId)}/disciplines/${disciplineId}/generate-fixtures`, payload)

  const suggestedPlacings = (academyId: number, editionId: number, disciplineId: number) =>
    api.get<{ format: SportsMatchFormat; placings: SportsSuggestedPlacing[] }>(`${base(academyId, editionId)}/disciplines/${disciplineId}/suggested-placings`)

  const confirmPlacings = (academyId: number, editionId: number, disciplineId: number, payload: { placings: { house_group_id: number; placing: number }[]; source?: 'suggested' | 'manual' }) =>
    api.post<SportsDisciplineResult[]>(`${base(academyId, editionId)}/disciplines/${disciplineId}/confirm-placings`, payload)

  /** จัดกลุ่มแมตช์เป็นรอบ เรียงจากรอบแรกไปรอบชิง */
  const groupByRound = (matches: SportsMatch[]) => {
    const roundsMap = new Map<number, SportsMatch[]>()
    for (const match of matches) {
      if (!roundsMap.has(match.round_order)) {
        roundsMap.set(match.round_order, [])
      }
      roundsMap.get(match.round_order)!.push(match)
    }
    
    return Array.from(roundsMap.entries())
      .sort((a, b) => a[0] - b[0])
      .map(([order, roundMatches]) => {
        // label = round_label ของแมตช์ใบแรกในรอบนั้น ถ้าไม่มีให้ใช้ `รอบที่ {round_order}`
        const label = roundMatches[0]?.round_label || `รอบที่ ${order}`
        return {
          round_order: order,
          label,
          matches: roundMatches.sort((a, b) => a.match_number - b.match_number),
        }
      })
  }

  /** ป้ายสถานะแมตช์เป็นภาษาไทย */
  const matchStatusText = (status: SportsMatchStatus): string => {
    switch (status) {
      case 'scheduled': return 'ยังไม่แข่ง'
      case 'in_progress': return 'กำลังแข่ง'
      case 'finished': return 'แข่งจบแล้ว'
      case 'cancelled': return 'ยกเลิก'
      default: return status
    }
  }

  /** ป้ายสถานะผู้เข้าแข่ง */
  const participantStatusText = (status: SportsParticipantStatus): string => {
    switch (status) {
      case 'ok': return 'ปกติ'
      case 'dq': return 'ถูกตัดสิทธิ์'
      case 'dns': return 'ไม่ลงแข่ง'
      case 'dnf': return 'แข่งไม่จบ'
      default: return status
    }
  }

  /** แปลง ms เป็นข้อความอ่านง่าย เช่น 12345 → '12.345 วินาที' · 75300 → '1:15.300 นาที' */
  const formatTimeMs = (ms: number | null | undefined): string => {
    if (ms == null) return ''
    if (ms < 60000) {
      return `${(ms / 1000).toFixed(3)} วินาที`
    }
    const mins = Math.floor(ms / 60000)
    const secs = ((ms % 60000) / 1000).toFixed(3)
    return `${mins}:${secs.padStart(6, '0')} นาที`
  }

  return {
    listMatches,
    createMatch,
    updateMatch,
    deleteMatch,
    recordResult,
    generateFixtures,
    suggestedPlacings,
    confirmPlacings,
    groupByRound,
    matchStatusText,
    participantStatusText,
    formatTimeMs,
  }
}
