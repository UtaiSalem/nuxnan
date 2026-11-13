export interface LessonImportSection {
  title: string
  content: string
  youtube_url: string | null
  min_read: number
}

export interface LessonImportLesson extends LessonImportSection {
  description: string | null
}

export interface LessonImportPreview {
  import_id: string
  lesson: LessonImportLesson
  topics: LessonImportSection[]
  images: number
  warnings: string[]
}

export type LessonPublicationStatus = 'draft' | 'published'

export interface LessonImportCommitPayload {
  import_id: string
  publication_status: LessonPublicationStatus
  lesson: LessonImportLesson
  topics: LessonImportSection[]
}

export interface DocxConversionResult {
  html: string
  messages: string[]
}
