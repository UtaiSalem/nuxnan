import { useApi } from '../composables/useApi'
import type { QuestionImportScope, QuestionImportPreview, QuestionImportRowData } from '../types/questionImport'

export const useQuestionImportService = () => {
  const api = useApi()

  // private helper
  const baseUrl = (scope: QuestionImportScope) =>
    scope.type === 'lesson'
      ? `/api/lessons/${scope.lessonId}/questions/import`
      : `/api/courses/${scope.courseId}/quizzes/${scope.quizId}/questions/import`

  const downloadTemplate = async (scope: QuestionImportScope): Promise<void> => {
    const { blob, filename } = await api.getBlob(`${baseUrl(scope)}/template`)
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', filename || 'question-import-template.xlsx')
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  }

  const previewImport = async (scope: QuestionImportScope, file: File): Promise<QuestionImportPreview> => {
    const formData = new FormData()
    formData.append('file', file)
    const res: any = await api.post(`${baseUrl(scope)}/preview`, formData)
    return res?.data ?? res
  }

  const commitImport = async (scope: QuestionImportScope, rows: QuestionImportRowData[]): Promise<{ success: boolean; imported: number }> => {
    const res: any = await api.post(baseUrl(scope), { rows })
    return res?.data ?? res
  }

  return { downloadTemplate, previewImport, commitImport }
}
