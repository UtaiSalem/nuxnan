import { computed, ref, unref } from 'vue'
import type { ApiError } from '~/composables/useApi'
import type {
  AcademicYearDTO,
  ClassroomOptionDTO,
  MaybeEnrollmentAcademyId,
  RolloverBatchResponse,
  RolloverPaginatedResponse,
  RolloverPlanRequestEntry,
  RolloverPlanResponse,
  RolloverPreviewResponse,
} from '~/types/enrollment'

export const useRolloverActions = (academyId: MaybeEnrollmentAcademyId) => {
  const api = useApi()

  const isLoading = ref(false)
  const lastError = ref<ApiError | null>(null)

  const resolvedAcademyId = computed(() => {
    const value = unref(academyId)
    if (value === null || value === undefined || value === '') {
      return null
    }

    return String(value)
  })

  const assertAcademyId = (): string => {
    const value = resolvedAcademyId.value
    if (!value) {
      throw new Error('Academy ID is required before calling rollover actions')
    }

    return value
  }

  const resetError = () => {
    lastError.value = null
  }

  const withLoading = async <T>(runner: () => Promise<T>): Promise<T> => {
    resetError()
    isLoading.value = true

    try {
      return await runner()
    } catch (error: any) {
      lastError.value = error as ApiError
      throw error
    } finally {
      isLoading.value = false
    }
  }

  const preview = async (fromYearId: number, toYearId: number): Promise<RolloverPreviewResponse> => {
    return withLoading(() => api.post<RolloverPreviewResponse>(
      `/api/academies/${assertAcademyId()}/rollover/preview`,
      {
        from_year_id: fromYearId,
        to_year_id: toYearId,
      },
    ))
  }

  const plan = async (
    fromYearId: number,
    toYearId: number,
    mapping: RolloverPlanRequestEntry[],
  ): Promise<RolloverPlanResponse> => {
    return withLoading(() => api.post<RolloverPlanResponse>(
      `/api/academies/${assertAcademyId()}/rollover/plan`,
      {
        from_year_id: fromYearId,
        to_year_id: toYearId,
        mapping,
      },
    ))
  }

  const commit = async (planId: string, confirmText: string): Promise<RolloverBatchResponse> => {
    return withLoading(() => api.post<RolloverBatchResponse>(
      `/api/academies/${assertAcademyId()}/rollover/commit`,
      {
        plan_id: planId,
        confirm_text: confirmText,
      },
      {
        timeout: 120000,
      },
    ))
  }

  const undo = async (batchId: string): Promise<RolloverBatchResponse> => {
    return withLoading(() => api.post<RolloverBatchResponse>(
      `/api/academies/${assertAcademyId()}/rollover/batches/${batchId}/undo`,
      {},
    ))
  }

  const closeUndo = async (batchId: string): Promise<RolloverBatchResponse> => {
    return withLoading(() => api.post<RolloverBatchResponse>(
      `/api/academies/${assertAcademyId()}/rollover/batches/${batchId}/close-undo`,
      {},
    ))
  }

  const listBatches = async (page: number = 1, perPage: number = 20): Promise<RolloverPaginatedResponse> => {
    return withLoading(() => api.get<RolloverPaginatedResponse>(
      `/api/academies/${assertAcademyId()}/rollover/batches?page=${page}&per_page=${perPage}`,
    ))
  }

  const fetchBatch = async (batchId: string): Promise<RolloverBatchResponse> => {
    return withLoading(() => api.get<RolloverBatchResponse>(
      `/api/academies/${assertAcademyId()}/rollover/batches/${batchId}`,
    ))
  }

  const fetchAcademicYears = async (): Promise<AcademicYearDTO[]> => {
    const response = await withLoading(() => api.get<any>(
      `/api/academies/${assertAcademyId()}/academic-years`,
    ))

    return response?.academicYears ?? response?.data ?? []
  }

  const createAcademicYear = async (payload: {
    name: string
    start_date: string
    end_date: string
    is_current?: boolean
    create_semesters?: boolean
  }): Promise<AcademicYearDTO> => {
    const response = await withLoading(() => api.post<any>(
      `/api/academies/${assertAcademyId()}/academic-years`,
      payload,
    ))

    return response?.academicYear ?? response?.data ?? response
  }

  const fetchClassroomsByYear = async (yearId: number): Promise<ClassroomOptionDTO[]> => {
    const response = await withLoading(() => api.get<any>(
      `/api/academies/${assertAcademyId()}/classrooms?academic_year_id=${yearId}`,
    ))

    const classrooms = response?.classrooms ?? response?.data ?? []

    return classrooms.map((c: any): ClassroomOptionDTO => ({
      id: c.id,
      display_name: c.display_name ?? c.name ?? `${c.grade_level}/${c.section}`,
      grade_level: c.grade_level ?? null,
      section: c.section ?? null,
      academic_year_id: c.academic_year_id ?? null,
      academic_year_name: c.academic_year_info?.name ?? c.academic_year?.name ?? c.academic_year ?? null,
    }))
  }

  const createClassroom = async (payload: {
    academic_year_id: number
    grade_level: string
    section: string
    name?: string
    capacity?: number
  }): Promise<ClassroomOptionDTO> => {
    const response = await withLoading(() => api.post<any>(
      `/api/academies/${assertAcademyId()}/classrooms`,
      {
        ...payload,
        name: payload.name ?? `${payload.grade_level}/${payload.section}`,
      },
    ))

    const classroom = response?.classroom ?? response?.data ?? response

    return {
      id: classroom.id,
      display_name: classroom.display_name ?? classroom.name ?? `${classroom.grade_level}/${classroom.section}`,
      grade_level: classroom.grade_level ?? null,
      section: classroom.section ?? null,
      academic_year_id: classroom.academic_year_id ?? null,
      academic_year_name: classroom.academic_year_info?.name ?? classroom.academic_year?.name ?? classroom.academic_year ?? null,
    }
  }

  const getErrorMessage = (fallback: string = 'ไม่สามารถดำเนินการเลื่อนชั้นได้'): string => {
    return (
      lastError.value?.data?.message ??
      lastError.value?.message ??
      fallback
    )
  }

  return {
    preview,
    plan,
    commit,
    undo,
    closeUndo,
    listBatches,
    fetchBatch,
    fetchAcademicYears,
    createAcademicYear,
    fetchClassroomsByYear,
    createClassroom,
    getErrorMessage,
    resetError,
    isLoading,
    lastError,
  }
}
