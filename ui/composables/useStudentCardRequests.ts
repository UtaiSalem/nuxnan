import type { StudentCardRequest, ClassroomSummary, StudentCardRequestType, StudentCardRequestReason } from '~/types/studentCardRequest'

export interface SubmitCardRequestPayload {
  student_id: number
  classroom_id: number
  request_type?: StudentCardRequestType
  reason_code?: StudentCardRequestReason
  reason?: string
  priority?: string
}

export interface BulkSubmitResultItem {
  student_id: number
  success: boolean
  request_id?: number
  message?: string
}

export const useStudentCardRequests = (academyId: Ref<number | null>) => {
  const api = useApi()
  const base = computed(() => `/api/academies/${academyId.value}/student-card-requests`)

  const requireAcademy = () => {
    if (!academyId.value) throw new Error('Academy is not ready')
  }

  return {
    list: async (params = ''): Promise<{ data: StudentCardRequest[] }> => {
      requireAcademy()
      return await api.get(`${base.value}${params ? `?${params}` : ''}`) as { data: StudentCardRequest[] }
    },
    counts: async (): Promise<{ data: Record<string, number> }> => {
      requireAcademy()
      return await api.get(`${base.value}/counts`) as { data: Record<string, number> }
    },
    show: async (id: number): Promise<{ data: StudentCardRequest }> => {
      requireAcademy()
      return await api.get(`${base.value}/${id}`) as { data: StudentCardRequest }
    },
    myClassrooms: async (): Promise<{ data: ClassroomSummary[] }> => {
      requireAcademy()
      return await api.get(`${base.value}/my-classrooms`) as { data: ClassroomSummary[] }
    },
    classroomStudents: async (id: number): Promise<{ data: { classroom: ClassroomSummary; students: any[] } }> => {
      requireAcademy()
      return await api.get(`${base.value}/classrooms/${id}/students`) as { data: { classroom: ClassroomSummary; students: any[] } }
    },
    submit: async (payload: SubmitCardRequestPayload): Promise<{ data: StudentCardRequest }> => {
      requireAcademy()
      return await api.post(base.value, payload) as { data: StudentCardRequest }
    },
    submitBulk: async (requests: SubmitCardRequestPayload[]): Promise<{ data: BulkSubmitResultItem[] }> => {
      requireAcademy()
      return await api.post(`${base.value}/bulk`, { requests }) as { data: BulkSubmitResultItem[] }
    },
    transition: async (id: number, action: 'approve' | 'reject' | 'start' | 'complete' | 'cancel', payload: Record<string, any> = {}): Promise<{ data: StudentCardRequest }> => {
      requireAcademy()
      return await api.patch(`${base.value}/${id}/${action}`, payload) as { data: StudentCardRequest }
    },
  }
}
