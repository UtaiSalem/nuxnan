/**
 * Fraud reporting (member-facing): submit a report against a fraudulent
 * account and list the reports the current member has filed.
 */

export type FraudCategory =
  | 'scam'
  | 'phishing'
  | 'point_fraud'
  | 'money_fraud'
  | 'fake_account'
  | 'other'

export interface FraudReportPayload {
  reported_user_id: number
  category: FraudCategory
  description: string
  related_transaction_type?: 'points' | 'wallet' | null
  related_transaction_id?: number | null
  evidence_note?: string | null
}

/** Category options with Thai labels, for building the report form select. */
export const FRAUD_CATEGORY_OPTIONS: { value: FraudCategory; label: string }[] = [
  { value: 'money_fraud', label: 'ฉ้อโกงเงิน / Wallet' },
  { value: 'point_fraud', label: 'ทุจริตแต้ม / คะแนน' },
  { value: 'scam', label: 'หลอกลวง / หลอกโอน' },
  { value: 'phishing', label: 'ฟิชชิง / ขโมยบัญชี' },
  { value: 'fake_account', label: 'บัญชีปลอม / สวมรอย' },
  { value: 'other', label: 'อื่น ๆ' },
]

export const useFraudReports = () => {
  const api = useApi()

  const submitReport = (payload: FraudReportPayload) =>
    api.post<{ success: boolean; message: string; data: any }>('/api/fraud-reports', payload)

  const getMyReports = (params: { page?: number; per_page?: number } = {}) =>
    api.get<{ success: boolean; data: any }>('/api/fraud-reports/mine', { params })

  return { submitReport, getMyReports, FRAUD_CATEGORY_OPTIONS }
}
