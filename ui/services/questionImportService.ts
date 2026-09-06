import { useApi } from '../composables/useApi'
import type { QuestionImportScope, QuestionImportPreview, QuestionImportRowData } from '../types/questionImport'

export const useQuestionImportService = () => {
  const api = useApi()

  // private helper
  const baseUrl = (scope: QuestionImportScope) =>
    scope.type === 'lesson'
      ? `/api/lessons/${scope.lessonId}/questions/import`
      : `/api/courses/${scope.courseId}/quizzes/${scope.quizId}/questions/import`

  const exportUrl = (scope: QuestionImportScope) =>
    scope.type === 'lesson'
      ? `/api/lessons/${scope.lessonId}/questions/export`
      : `/api/courses/${scope.courseId}/quizzes/${scope.quizId}/questions/export`

  const saveBlob = (blob: Blob, filename: string) => {
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  }

  const downloadTemplate = async (scope: QuestionImportScope): Promise<void> => {
    const { blob, filename } = await api.getBlob(`${baseUrl(scope)}/template`)
    saveBlob(blob, filename || 'question-import-template.xlsx')
  }

  const exportQuestions = async (scope: QuestionImportScope): Promise<void> => {
    const { blob, filename } = await api.getBlob(exportUrl(scope))
    saveBlob(blob, filename || 'questions.xlsx')
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

  return { downloadTemplate, exportQuestions, previewImport, commitImport }
}
