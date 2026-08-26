export const ELECTION_STATUS_LABELS: Record<string, string> = {
  draft: 'ร่าง',
  nomination: 'รับสมัคร',
  campaign: 'หาเสียง',
  voting: 'ลงคะแนน',
  closed: 'ปิดหีบ',
  published: 'ประกาศผลแล้ว',
  cancelled: 'ยกเลิก',
}

export const PARTY_STATUS_LABELS: Record<string, string> = {
  pending: 'รอตรวจสอบ',
  approved: 'อนุมัติแล้ว',
  rejected: 'ถูกปฏิเสธ',
  withdrawn: 'ถอนตัวแล้ว',
}

export const PARTY_ROLE_LABELS: Record<string, string> = {
  leader: 'ประธาน',
  deputy: 'รองประธาน',
  secretary: 'เลขานุการ',
  treasurer: 'เหรัญญิก',
  member: 'สมาชิก',
}

export const electionStatusLabel = (status: string) => ELECTION_STATUS_LABELS[status] || status
export const partyStatusLabel = (status: string) => PARTY_STATUS_LABELS[status] || status
export const partyRoleLabel = (role: string) => PARTY_ROLE_LABELS[role] || role

export const formatThaiDateTime = (value: string | null | undefined) => {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return '-'
  return date.toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
