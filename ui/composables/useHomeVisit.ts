import { computed, ref, type Ref } from 'vue'

export type HomeVisitStatus = 'scheduled' | 'completed' | 'cancelled'

export interface HomeVisitRecord {
  id: number
  visit_date: string
  visit_time: string | null
  visitor_name: string
  visitor_position: string | null
  visit_status: HomeVisitStatus
  zone_id: number | null
  observations: string | null
  recommendations: string | null
  follow_up: string | null
  next_visit: string | null
  created_at?: string | null
  updated_at?: string | null
}

export interface HomeVisitPayload {
  visit_date: string
  visit_time?: string | null
  visitor_name: string
  visitor_position: string
  visit_status: HomeVisitStatus
  zone_id?: number | null
  observations?: string | null
  recommendations?: string | null
  follow_up?: string | null
  next_visit?: string | null
}

interface PaginatedHomeVisits {
  current_page: number
  data: HomeVisitRecord[]
  last_page: number
  per_page: number
  total: number
}

export const useHomeVisit = (
  academyName: Ref<string> | string,
  studentId: Ref<number | string | null | undefined> | number | string | null | undefined,
) => {
  const api = useApi()
  const isLoading = ref(false)
  const isSubmitting = ref(false)
  const error = ref<string | null>(null)

  const academy = computed(() => typeof academyName === 'string' ? academyName : academyName.value)
  const student = computed(() => {
    if (typeof studentId === 'number' || typeof studentId === 'string' || studentId == null) return studentId
    return studentId.value
  })

  const endpoint = computed(() => `/api/academies/${encodeURIComponent(academy.value)}/students/${student.value}/home-visits`)

  const ensureContext = () => {
    if (!academy.value || !student.value) throw new Error('ข้อมูลโรงเรียนหรือนักเรียนไม่ครบถ้วน')
  }

  const fetchVisits = async (page = 1): Promise<PaginatedHomeVisits> => {
    ensureContext()
    isLoading.value = true
    error.value = null
    try {
      const response = await api.get<{ success: boolean, data: PaginatedHomeVisits }>(`${endpoint.value}?page=${page}`)
      return response.data
    } catch (err: any) {
      error.value = err?.data?.message || err?.message || 'ไม่สามารถโหลดข้อมูลการเยี่ยมบ้านได้'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  const submit = async <T>(operation: () => Promise<T>): Promise<T> => {
    ensureContext()
    isSubmitting.value = true
    error.value = null
    try {
      return await operation()
    } catch (err: any) {
      error.value = err?.data?.message || err?.message || 'ไม่สามารถบันทึกข้อมูลการเยี่ยมบ้านได้'
      throw err
    } finally {
      isSubmitting.value = false
    }
  }

  const createVisit = (payload: HomeVisitPayload) =>
    submit(() => api.post(endpoint.value, payload))

  const updateVisit = (visitId: number, payload: HomeVisitPayload) =>
    submit(() => api.put(`${endpoint.value}/${visitId}`, payload))

  const deleteVisit = (visitId: number) =>
    submit(() => api.delete(`${endpoint.value}/${visitId}`))

  const updateStatus = (visitId: number, visitStatus: HomeVisitStatus) =>
    submit(() => api.patch(`${endpoint.value}/${visitId}/status`, { visit_status: visitStatus }))

  return {
    isLoading,
    isSubmitting,
    error,
    fetchVisits,
    createVisit,
    updateVisit,
    deleteVisit,
    updateStatus,
  }
}
