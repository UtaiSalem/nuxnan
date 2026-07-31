export const useElections = () => {
  const api = useApi()
  const base = (academyId: number, electionId: number, stationId: number) =>
    `/api/academies/${academyId}/elections/${electionId}/stations/${stationId}`

  const openStation = (a: number, e: number, s: number) => api.call(`${base(a, e, s)}/open`, { method: 'POST' })
  const closeStation = (a: number, e: number, s: number) => api.call(`${base(a, e, s)}/close`, { method: 'POST' })
  const stationProgress = (a: number, e: number, s: number) => api.call(`${base(a, e, s)}/progress`)
  const lookupVoter = (a: number, e: number, s: number, identifier: string) => api.call(`${base(a, e, s)}/lookup`, { method: 'POST', body: { identifier } })
  const searchVoters = (a: number, e: number, s: number, q: string) => api.call(`${base(a, e, s)}/search?q=${encodeURIComponent(q)}`)
  const issueBallot = (a: number, e: number, s: number, userId: number) => api.call(`${base(a, e, s)}/issue`, { method: 'POST', body: { user_id: userId } })
  const voidBallot = (a: number, e: number, s: number, receiptId: number | string, reason: string) => api.call(`${base(a, e, s)}/void`, { method: 'POST', body: { receipt_id: receiptId, reason } })
  const castBallot = (a: number, e: number, data: { ballot_token: string; party_id: number | null }) => api.call(`/api/academies/${a}/elections/${e}/cast`, { method: 'POST', body: data })

  return { openStation, closeStation, stationProgress, lookupVoter, searchVoters, issueBallot, voidBallot, castBallot }
}
