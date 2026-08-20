export type ExternalScoreImportAction = 'set' | 'clear' | 'skip'

export interface ExternalScoreImportRow {
  row_number: number
  course_member_id: number | null
  order_number: number | string | null
  name: string
  group_name: string | null
  current_score: number | null
  new_score: number | null
  note: string | null
  action: ExternalScoreImportAction
  errors: string[]
  warnings: string[]
}

export interface ExternalScoreImportSummary {
  total: number
  set: number
  clear: number
  skip: number
  invalid: number
  missing: number
}

export interface ExternalScoreImportPreview {
  success: boolean
  external_score: { id: number; title: string; max_score: number }
  summary: ExternalScoreImportSummary
  rows: ExternalScoreImportRow[]
}

export interface ExternalScoreImportTopic {
  id: number
  title: string
  max_score: number
}
