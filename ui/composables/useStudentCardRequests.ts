import type { StudentCardRequestType } from '~/types/studentCardRequest'

export const useStudentCardRequests = (academyId: Ref<number | null>) => {
  const api = useApi()
  const base = computed(() => `/api/academies/${academyId.value}/student-card-requests`)

  const requireAcademy = () => {
    if (!academyId.value) throw new Error('Academy is not ready')
  }

  return {
    list: async (params = '') => { requireAcademy(); return await api.get(`${base.value}${params ? `?${params}` : ''}`) as any },
    counts: async () => { requireAcademy(); return await api.get(`${base.value}/counts`) as any },
    show: async (id: number) => { requireAcademy(); return await api.get(`${base.value}/${id}`) as any },
    myClassrooms: async () => { requireAcademy(); return await api.get(`${base.value}/my-classrooms`) as any },
    classroomStudents: async (id: number) => { requireAcademy(); return await api.get(`${base.value}/classrooms/${id}/students`) as any },
    submit: async (payload: { student_id: number; classroom_id: number; request_type: StudentCardRequestType; reason?: string; priority?: string }) => {
      requireAcademy(); return await api.post(base.value, payload) as any
    },
    transition: async (id: number, action: 'approve' | 'reject' | 'start' | 'complete' | 'cancel', payload: Record<string, any> = {}) => {
      requireAcademy(); return await api.patch(`${base.value}/${id}/${action}`, payload) as any
    },
  }
}
