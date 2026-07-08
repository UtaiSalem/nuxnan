import { useApi } from '../composables/useApi'
import type { 
  StudentImportBatch, 
  PaginatedRowsResponse 
} from '../types/studentImport'

export const useStudentImportService = () => {
  const api = useApi()

  const listBatches = async (academyName: string, page = 1, perPage = 20) => {
    return api.get(`/api/academies/${academyName}/student-imports`, {
      params: { page, per_page: perPage }
    })
  }

  const uploadBatch = async (
    academyName: string, 
    file: File, 
    academicYearId?: number | null, 
    defaults?: Record<string, any>,
    importType?: string
  ): Promise<StudentImportBatch> => {
    const formData = new FormData()
    formData.append('file', file)
    if (academicYearId) {
      formData.append('academic_year_id', String(academicYearId))
    }
    if (importType) {
      formData.append('import_type', importType)
    }
    if (defaults) {
      Object.entries(defaults).forEach(([key, value]) => {
        formData.append(`defaults[${key}]`, String(value))
      })
    }

    const res: any = await api.post(`/api/academies/${academyName}/student-imports`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    return res?.data
  }

  const getBatch = async (academyName: string, batchId: number): Promise<StudentImportBatch> => {
    const res: any = await api.get(`/api/academies/${academyName}/student-imports/${batchId}`)
    return res?.data
  }

  const getRows = async (
    academyName: string, 
    batchId: number, 
    page = 1, 
    perPage = 50, 
    status?: string
  ): Promise<PaginatedRowsResponse> => {
    const params: Record<string, any> = { page, per_page: perPage }
    if (status) {
      params.status = status
    }
    return api.get(`/api/academies/${academyName}/student-imports/${batchId}/rows`, {
      params
    })
  }

  const confirmBatch = async (academyName: string, batchId: number): Promise<void> => {
    await api.post(`/api/academies/${academyName}/student-imports/${batchId}/confirm`)
  }

  const cancelBatch = async (academyName: string, batchId: number): Promise<void> => {
    await api.delete(`/api/academies/${academyName}/student-imports/${batchId}`)
  }

  const downloadTemplate = async (academyName: string): Promise<void> => {
    const res: any = await api.get(`/api/academies/${academyName}/student-imports/template`, {
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([res.data || res]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'student_import_template.csv')
    document.body.appendChild(link)
    link.click()
    link.remove()
  }

  const downloadErrorCsv = async (academyName: string, batchId: number): Promise<void> => {
    const res: any = await api.get(`/api/academies/${academyName}/student-imports/${batchId}/errors`, {
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([res.data || res]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `import_errors_${batchId}.csv`)
    document.body.appendChild(link)
    link.click()
    link.remove()
  }

  return {
    listBatches,
    uploadBatch,
    getBatch,
    getRows,
    confirmBatch,
    cancelBatch,
    downloadTemplate,
    downloadErrorCsv
  }
}
