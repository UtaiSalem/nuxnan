export interface QuestionImportRowData {
  text: string
  options: string[]
  correct: number
  points: number
  explanation: string | null
  pp_fine: number
}
export interface QuestionImportRow {
  row_number: number
  data: QuestionImportRowData
  errors: string[]
  warnings: string[]
}
export interface QuestionImportSummary { total: number; valid: number; invalid: number; warnings: number }
export interface QuestionImportPreview { success: boolean; summary: QuestionImportSummary; rows: QuestionImportRow[] }
export type QuestionImportScope =
  | { type: 'lesson'; lessonId: number | string }
  | { type: 'quiz'; courseId: number | string; quizId: number | string }
