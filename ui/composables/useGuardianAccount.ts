/**
 * Composable for performing guardian account link actions
 */
import { ref, type Ref } from 'vue'
import { useApi } from './useApi'
import { errorStatus, errorMessage } from './useGuardianAppointment'

export const useGuardianAccount = (academyName: Ref<string | number> | string | number) => {
  const api = useApi()
  const isSearching = ref(false)
  const isSubmitting = ref(false)
  const isResponding = ref(false)

  const getParams = () => {
    const acadName = typeof academyName === 'string' || typeof academyName === 'number' ? academyName : academyName.value
    return { acadName }
  }

  const searchAccount = async (q: string) => {
    const { acadName } = getParams()
    isSearching.value = true
    try {
      const res = await api.get(`/api/academies/${acadName}/guardian-accounts/search?q=${encodeURIComponent(q)}`)
      return res
    } catch (err) {
      throw err
    } finally {
      isSearching.value = false
    }
  }

  const searchStudent = async (studentCode: string, lastName: string) => {
    const { acadName } = getParams()
    isSearching.value = true
    try {
      const res = await api.get(`/api/academies/${acadName}/guardian-accounts/student-search?student_code=${encodeURIComponent(studentCode)}&last_name=${encodeURIComponent(lastName)}`)
      return res
    } catch (err) {
      throw err
    } finally {
      isSearching.value = false
    }
  }

  const createAccountRequest = async (studentId: number, payload: { user_id: number, guardian_id?: number }) => {
    const { acadName } = getParams()
    isSubmitting.value = true
    try {
      const res = await api.post(`/api/academies/${acadName}/students/${studentId}/guardian-accounts`, payload)
      return res
    } catch (err) {
      throw err
    } finally {
      isSubmitting.value = false
    }
  }

  const fetchRequests = async (params?: Record<string, any>) => {
    const { acadName } = getParams()
    const searchParams = new URLSearchParams()
    if (params) {
      for (const [key, value] of Object.entries(params)) {
        if (value !== undefined && value !== null) {
          searchParams.append(key, String(value))
        }
      }
    }
    const queryStr = searchParams.toString()
    isSearching.value = true
    try {
      const res = await api.get(`/api/academies/${acadName}/guardian-account-requests${queryStr ? '?' + queryStr : ''}`)
      return res
    } catch (err) {
      throw err
    } finally {
      isSearching.value = false
    }
  }

  const acceptRequest = async (id: number) => {
    const { acadName } = getParams()
    isResponding.value = true
    try {
      const res = await api.post(`/api/academies/${acadName}/guardian-account-requests/${id}/accept`, {})
      return res
    } catch (err) {
      throw err
    } finally {
      isResponding.value = false
    }
  }

  const declineRequest = async (id: number, reason?: string) => {
    const { acadName } = getParams()
    isResponding.value = true
    try {
      const res = await api.post(`/api/academies/${acadName}/guardian-account-requests/${id}/decline`, { reason })
      return res
    } catch (err) {
      throw err
    } finally {
      isResponding.value = false
    }
  }

  const cancelRequest = async (id: number) => {
    const { acadName } = getParams()
    isResponding.value = true
    try {
      const res = await api.post(`/api/academies/${acadName}/guardian-account-requests/${id}/cancel`, {})
      return res
    } catch (err) {
      throw err
    } finally {
      isResponding.value = false
    }
  }

  const unlinkAccount = async (guardianId: number) => {
    const { acadName } = getParams()
    isSubmitting.value = true
    try {
      const res = await api.delete(`/api/academies/${acadName}/guardian-people/${guardianId}/account`)
      return res
    } catch (err) {
      throw err
    } finally {
      isSubmitting.value = false
    }
  }

  return {
    isSearching,
    isSubmitting,
    isResponding,
    searchAccount,
    searchStudent,
    createAccountRequest,
    fetchRequests,
    acceptRequest,
    declineRequest,
    cancelRequest,
    unlinkAccount,
  }
}
