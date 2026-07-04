import type { DuplicateCheckResult, StudentIntakePayload, StudentIntakeResult, StudentListResponse } from '../types/studentIntake'

export const useStudentIntakeService = () => {
  const api = useApi()

  /**
   * Check for duplicate student records
   */
  const checkDuplicate = async (academyName: string, studentId: string, citizenId?: string | null): Promise<DuplicateCheckResult> => {
    try {
      const response = await api.get(`/api/academies/${academyName}/student-intakes/duplicate-check`, {
        params: {
          student_id: studentId,
          citizen_id: citizenId || undefined
        }
      })
      return response as any as DuplicateCheckResult
    } catch (error: any) {
      console.error('Failed to check duplicates:', error)
      throw error
    }
  }

  /**
   * Submit single student intake data
   */
  const submitIntake = async (academyName: string, payload: StudentIntakePayload): Promise<StudentIntakeResult> => {
    try {
      const response = await api.post(`/api/academies/${academyName}/student-intakes`, payload)
      return response as any as StudentIntakeResult
    } catch (error: any) {
      console.error('Failed to submit student intake:', error)
      throw error
    }
  }

  const listStudents = async (
    academyName: string,
    params: { page?: number; per_page?: number; search?: string; status?: string; classroom_id?: number; account_status?: string; sort_field?: string; sort_order?: string }
  ): Promise<StudentListResponse> => {
    const response = await api.get(`/api/academies/${academyName}/student-intakes/list`, { params })
    return response as any as StudentListResponse
  }

  return {
    checkDuplicate,
    submitIntake,
    listStudents
  }
}
