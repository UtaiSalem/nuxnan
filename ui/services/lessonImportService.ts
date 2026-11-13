import { useApi } from '../composables/useApi'
import type { LessonImportPreview, LessonImportCommitPayload } from '../types/lessonImport'

export const useLessonImportService = () => {
  const api = useApi()

  const preview = async (courseId: string | number, html: string, filename: string): Promise<LessonImportPreview> => {
    const res: any = await api.post(`/api/courses/${courseId}/lessons/import/preview`, { html, filename })
    return res
  }

  const commit = async (
    courseId: string | number,
    payload: LessonImportCommitPayload,
  ): Promise<{ success: boolean; newLesson: { id: number }; topics_count: number; message?: string }> =>
    await api.post(`/api/courses/${courseId}/lessons/import`, payload)

  const uploadAttachments = async (lessonId: number, files: File[]): Promise<void> => {
    // แบ่งเป็นก้อนละ 5 ไฟล์ แล้ว POST ทีละก้อนตามลำดับ
    for (let i = 0; i < files.length; i += 5) {
      const fd = new FormData()
      files.slice(i, i + 5).forEach(f => fd.append('files[]', f))
      await api.post(`/api/lessons/${lessonId}/attachments`, fd)
    }
  }

  const errorMessage = (err: any, fallback: string): string => {
    const d = err?.data
    if (d?.errors) {
      const first = Object.values(d.errors)[0] as any
      if (Array.isArray(first) && first[0]) return String(first[0])
    }
    return d?.message || err?.message || fallback
  }

  return { preview, commit, uploadAttachments, errorMessage }
}
