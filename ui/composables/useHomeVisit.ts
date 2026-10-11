import { computed, ref, type Ref } from 'vue'

export type HomeVisitStatus = 'scheduled' | 'completed' | 'cancelled'

export interface HomeVisitParticipantInput {
  participant_name: string
  notes?: string | null
}

export interface HomeVisitParticipant extends HomeVisitParticipantInput {
  id: number
}

export interface HomeVisitImage {
  id: number
  image_path: string
  image_name: string | null
  image_url?: string | null
  description?: string | null
}

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
  participants?: HomeVisitParticipant[] | null
  images?: HomeVisitImage[] | null
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
  // HV-S5c — participants + รูปหลักฐาน (optional)
  participants?: HomeVisitParticipantInput[]
  images?: File[]
  images_to_delete?: number[]
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

  // HV-S5c — ใช้ multipart เฉพาะเมื่อมีรูป (เพิ่ม/ลบ) · ไม่งั้นส่ง JSON ตามเดิม
  // PHP แกะ multipart ได้เฉพาะ POST → แก้ไขที่มีรูปต้อง POST + _method=PUT (method spoof)
  const hasFiles = (p: HomeVisitPayload) =>
    (p.images?.length ?? 0) > 0 || (p.images_to_delete?.length ?? 0) > 0

  const jsonPayload = (p: HomeVisitPayload) => {
    const { images, images_to_delete, ...rest } = p
    return rest
  }

  const buildFormData = (p: HomeVisitPayload, method?: 'PUT'): FormData => {
    const fd = new FormData()
    if (method) fd.append('_method', method)

    const scalarKeys: Array<keyof HomeVisitPayload> = [
      'visit_date', 'visit_time', 'visitor_name', 'visitor_position',
      'visit_status', 'zone_id', 'observations', 'recommendations', 'follow_up', 'next_visit',
    ]
    for (const key of scalarKeys) {
      const value = p[key]
      if (value !== null && value !== undefined && value !== '') fd.append(key, String(value))
    }
    ;(p.participants ?? []).forEach((participant, index) => {
      fd.append(`participants[${index}][participant_name]`, participant.participant_name)
      if (participant.notes) fd.append(`participants[${index}][notes]`, participant.notes)
    })
    ;(p.images ?? []).forEach(file => fd.append('images[]', file))
    ;(p.images_to_delete ?? []).forEach(id => fd.append('images_to_delete[]', String(id)))
    return fd
  }

  const createVisit = (payload: HomeVisitPayload) =>
    submit(() => hasFiles(payload)
      ? api.post(endpoint.value, buildFormData(payload))
      : api.post(endpoint.value, jsonPayload(payload)))

  const updateVisit = (visitId: number, payload: HomeVisitPayload) =>
    submit(() => hasFiles(payload)
      ? api.post(`${endpoint.value}/${visitId}`, buildFormData(payload, 'PUT'))
      : api.put(`${endpoint.value}/${visitId}`, jsonPayload(payload)))

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
