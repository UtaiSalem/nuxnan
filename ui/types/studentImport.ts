export type ImportBatchStatus = 
  | 'uploaded'
  | 'validating'
  | 'validated'
  | 'processing'
  | 'completed'
  | 'partial'
  | 'failed'
  | 'cancelled'

export type ImportRowStatus =
  | 'pending'
  | 'valid'
  | 'warning'
  | 'invalid'
  | 'imported'
  | 'skipped'
  | 'failed'

export interface StudentImportBatch {
  id: number
  academy_id: number
  academic_year_id: number | null
  original_filename: string
  status: ImportBatchStatus
  total_rows: number
  valid_rows: number
  invalid_rows: number
  imported_rows: number
  skipped_rows: number
  column_mapping: Record<string, string> | null
  default_values: Record<string, any> | null
  created_at: string
  updated_at: string
  completed_at: string | null
}

export interface ImportRowError {
  field: string
  message: string
  code?: string
}

export interface StudentImportRow {
  id: number
  batch_id: number
  row_number: number
  raw_data: Record<string, any>
  normalized_data: Record<string, any> | null
  status: ImportRowStatus
  errors: ImportRowError[] | null
  warnings: ImportRowError[] | null
  student_id: number | null
  created_at: string
  updated_at: string
}

export interface PaginatedRowsResponse {
  data: StudentImportRow[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}
