import { computed, ref, unref } from 'vue'
import type { ApiError } from '~/composables/useApi'
import type {
  ClassroomStudentDTO,
  EnrollmentAction,
  EnrollmentActionPayload,
  EnrollmentApiErrorData,
  EnrollmentFieldErrors,
  MaybeEnrollmentAcademyId,
  StudentEnrollmentHistoryResponse,
  StudentEnrollmentLifecycleResponse,
} from '~/types/enrollment'

function normalizeEnrollmentFieldErrors(errorData?: EnrollmentApiErrorData | null): EnrollmentFieldErrors {
  const normalized: EnrollmentFieldErrors = {}

  if (!errorData?.errors) {
    return normalized
  }

  for (const [field, value] of Object.entries(errorData.errors)) {
    normalized[field] = Array.isArray(value) ? value[0] ?? '' : value
  }

  return normalized
}

export const useStudentEnrollmentActions = (academyId: MaybeEnrollmentAcademyId) => {
  const api = useApi()

  const isLoading = ref(false)
  const lastError = ref<ApiError | null>(null)
  const fieldErrors = ref<EnrollmentFieldErrors>({})

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
      throw new Error('Academy ID is required before calling enrollment actions')
    }

    return value
  }

  const buildStudentEndpoint = (studentId: number | string, action: EnrollmentAction): string => {
    return `/api/academies/${assertAcademyId()}/students/${studentId}/${action}`
  }

  const buildHistoryEndpoint = (studentId: number | string): string => {
    return `/api/academies/${assertAcademyId()}/students/${studentId}/enrollment-history-v2`
  }

  const resetErrors = () => {
    lastError.value = null
    fieldErrors.value = {}
  }

  const execute = async <TAction extends EnrollmentAction>(
    action: TAction,
    studentId: number | string,
    payload: EnrollmentActionPayload<TAction>,
  ): Promise<StudentEnrollmentLifecycleResponse> => {
    resetErrors()
    isLoading.value = true

    try {
      return await api.post<StudentEnrollmentLifecycleResponse>(
        buildStudentEndpoint(studentId, action),
        payload,
      )
    } catch (error: any) {
      lastError.value = error as ApiError
      fieldErrors.value = normalizeEnrollmentFieldErrors(error?.data)
      throw error
    } finally {
      isLoading.value = false
    }
  }

  const fetchHistory = async (studentId: number | string): Promise<ClassroomStudentDTO[]> => {
    resetErrors()
    isLoading.value = true

    try {
      const response = await api.get<StudentEnrollmentHistoryResponse>(buildHistoryEndpoint(studentId))
      return response.data ?? []
    } catch (error: any) {
      lastError.value = error as ApiError
      fieldErrors.value = normalizeEnrollmentFieldErrors(error?.data)
      throw error
    } finally {
      isLoading.value = false
    }
  }

  const getErrorMessage = (fallback: string = 'ไม่สามารถดำเนินการได้'): string => {
    return (
      lastError.value?.data?.message ??
      lastError.value?.message ??
      fallback
    )
  }

  return {
    execute,
    fetchHistory,
    getErrorMessage,
    resetErrors,
    isLoading,
    lastError,
    fieldErrors,
  }
}

export { normalizeEnrollmentFieldErrors }
