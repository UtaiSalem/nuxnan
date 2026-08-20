import { useApi } from '../composables/useApi'
import type { ExternalScoreImportPreview, ExternalScoreImportRow } from '../types/externalScoreImport'

export const useExternalScoreImportService = () => {
  const api = useApi()

  const downloadTemplate = async (courseId: number, externalScoreId: number, groupId: number | null): Promise<void> => {
    let url = `/api/courses/${courseId}/external-scores/import/${externalScoreId}/template`
    if (groupId) {
      url += `/${groupId}`
    }
    const { blob, filename } = await api.getBlob(url)
    const blobUrl = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = blobUrl
    link.setAttribute('download', filename || `external-scores-${externalScoreId}.xlsx`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(blobUrl)
  }

  const previewImport = async (courseId: number, externalScoreId: number, file: File, groupId: number | null): Promise<ExternalScoreImportPreview> => {
    const formData = new FormData()
    formData.append('file', file)
    if (groupId) {
      formData.append('group_id', String(groupId))
    }
    const res: any = await api.post(`/api/courses/${courseId}/external-scores/import/${externalScoreId}/preview`, formData)
    return res?.data ?? res
  }

  const commitImport = async (courseId: number, externalScoreId: number, rows: ExternalScoreImportRow[]): Promise<any> => {
    const entries = rows
      .filter(r => r.errors.length === 0 && r.action !== 'skip' && r.course_member_id !== null)
      .map(r => r.action === 'clear'
        ? { course_member_id: r.course_member_id, score: null }
        : { course_member_id: r.course_member_id, score: r.new_score, note: r.note })
        
    const res: any = await api.post(`/api/courses/${courseId}/external-scores/${externalScoreId}/entries`, { entries })
    return res?.data ?? res
  }

  return { downloadTemplate, previewImport, commitImport }
}
