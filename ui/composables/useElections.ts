export const useElections = () => {
  const api = useApi()
  const electionsBase = (academyId: number) => `/api/academies/${academyId}/elections`
  const base = (academyId: number, electionId: number, stationId: number) =>
    `/api/academies/${academyId}/elections/${electionId}/stations/${stationId}`

  const openStation = (a: number, e: number, s: number) => api.call(`${base(a, e, s)}/open`, { method: 'POST' })
  const closeStation = (a: number, e: number, s: number) => api.call(`${base(a, e, s)}/close`, { method: 'POST' })
  const stationProgress = (a: number, e: number, s: number) => api.call(`${base(a, e, s)}/progress`)
  const lookupVoter = (a: number, e: number, s: number, identifiers: string | { user_id?: number; member_code?: string }) => api.call(`${base(a, e, s)}/lookup`, { method: 'POST', body: typeof identifiers === 'string' ? { identifier: identifiers } : identifiers })
  const searchVoters = (a: number, e: number, s: number, q: string) => api.call(`${base(a, e, s)}/search?q=${encodeURIComponent(q)}`)
  const issueBallot = (a: number, e: number, s: number, userId: number) => api.call(`${base(a, e, s)}/issue`, { method: 'POST', body: { user_id: userId } })
  const voidBallot = (a: number, e: number, s: number, receiptId: number | string, reason: string) => api.call(`${base(a, e, s)}/void`, { method: 'POST', body: { receipt_id: receiptId, reason } })
  const castBallot = (a: number, e: number, data: { ballot_token: string; party_id: number | null }) => api.call(`/api/academies/${a}/elections/${e}/cast`, { method: 'POST', body: data })

  const listElections = (a: number, params: { status?: string; academic_year_id?: number | string; per_page?: number; page?: number } = {}) => {
    const query = new URLSearchParams()
    Object.entries(params).forEach(([key, value]) => { if (value !== undefined && value !== '') query.set(key, String(value)) })
    return api.call(`${electionsBase(a)}${query.toString() ? `?${query}` : ''}`)
  }
  const getElection = (a: number, e: number) => api.call(`${electionsBase(a)}/${e}`)
  const createElection = (a: number, payload: Record<string, any>) => api.call(electionsBase(a), { method: 'POST', body: payload })
  const updateElection = (a: number, e: number, payload: Record<string, any>) => api.call(`${electionsBase(a)}/${e}`, { method: 'PUT', body: payload })
  const deleteElection = (a: number, e: number) => api.call(`${electionsBase(a)}/${e}`, { method: 'DELETE' })
  const transitionStatus = (a: number, e: number, status: string) => api.call(`${electionsBase(a)}/${e}/status`, { method: 'POST', body: { status } })
  const getTurnout = (a: number, e: number) => api.call(`${electionsBase(a)}/${e}/turnout`)
  const listParties = (a: number, e: number) => api.call(`${electionsBase(a)}/${e}/parties`)
  const approveParty = (a: number, e: number, p: number, number?: number | null) => api.call(`${electionsBase(a)}/${e}/parties/${p}/approve`, { method: 'POST', body: number === undefined ? {} : { number } })
  const rejectParty = (a: number, e: number, p: number, review_note: string) => api.call(`${electionsBase(a)}/${e}/parties/${p}/reject`, { method: 'POST', body: { review_note } })
  const withdrawParty = (a: number, e: number, p: number) => api.call(`${electionsBase(a)}/${e}/parties/${p}/withdraw`, { method: 'POST' })
  const lockVoterRoll = (a: number, e: number) => api.call(`${electionsBase(a)}/${e}/voter-roll/lock`, { method: 'POST' })
  const listVoters = (a: number, e: number, params: Record<string, string | number | undefined> = {}) => { const query = new URLSearchParams(); Object.entries(params).forEach(([key, value]) => { if (value !== undefined && value !== '') query.set(key, String(value)) }); return api.call(`${electionsBase(a)}/${e}/voter-roll${query.toString() ? `?${query}` : ''}`) }
  const voterRollStats = (a: number, e: number) => api.call(`${electionsBase(a)}/${e}/voter-roll/stats`)
  const setMemberEducationLevel = (a: number, member: number, level: number | null) => api.call(`/api/academies/${a}/members/${member}/education-level`, { method: 'PUT', body: { education_level: level } })

  return { openStation, closeStation, stationProgress, lookupVoter, searchVoters, issueBallot, voidBallot, castBallot, listElections, getElection, createElection, updateElection, deleteElection, transitionStatus, getTurnout, listParties, approveParty, rejectParty, withdrawParty, lockVoterRoll, listVoters, voterRollStats, setMemberEducationLevel }
}
