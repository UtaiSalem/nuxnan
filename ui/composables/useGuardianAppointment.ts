/**
 * Composable for performing guardian appointment actions
 */
import { ref, type Ref } from 'vue'
import { useApi } from './useApi'

/** Pull the HTTP status out of whatever useApi threw, so callers can tell 409 from 500. */
export const errorStatus = (err: any): number | null =>
  err?.status ?? err?.statusCode ?? err?.response?.status ?? err?.data?.status ?? null

/** The Thai message the API sent, when it sent one. */
export const errorMessage = (err: any): string | null =>
  err?.data?.message ?? err?.response?._data?.message ?? err?.message ?? null

export const useGuardianAppointment = (academyName: Ref<string | number> | string | number, studentId: Ref<number | string> | number | string) => {
  const api = useApi()
  const isSearching = ref(false)
  const isSubmitting = ref(false)
  const isVerifying = ref(false)

  const getParams = () => {
    const acadName = typeof academyName === 'string' || typeof academyName === 'number' ? academyName : academyName.value
    const stuId = typeof studentId === 'number' || typeof studentId === 'string' ? studentId : studentId.value
    return { acadName, stuId }
  }

  const searchGuardians = async (q: string) => {
    const { acadName } = getParams()
    isSearching.value = true
    try {
      const res = await api.get(`/api/academies/${acadName}/guardians/search?q=${encodeURIComponent(q)}&per_page=20`)
      return res
    } catch (err) {
      throw err
    } finally {
      isSearching.value = false
    }
  }

  const matchGuardian = async (payload: { citizen_id: string; first_name: string; last_name: string }) => {
    const { acadName, stuId } = getParams()
    isSearching.value = true
    try {
      const res = await api.post(`/api/academies/${acadName}/students/${stuId}/guardians/match`, payload)
      return res
    } catch (err) {
      throw err
    } finally {
      isSearching.value = false
    }
  }

  const appointGuardian = async (payload: { guardian_id: number; guardian_type?: string; relationship?: string; is_primary_contact?: boolean; is_emergency_contact?: boolean }) => {
    const { acadName, stuId } = getParams()
    isSubmitting.value = true
    try {
      const res = await api.post(`/api/academies/${acadName}/students/${stuId}/guardians/appoint`, payload)
      return res
    } catch (err) {
      throw err
    } finally {
      isSubmitting.value = false
    }
  }

  const verifyAppointment = async (linkId: number) => {
    const { acadName, stuId } = getParams()
    isVerifying.value = true
    try {
      const res = await api.post(`/api/academies/${acadName}/students/${stuId}/guardians/links/${linkId}/verify`)
      return res
    } catch (err) {
      throw err
    } finally {
      isVerifying.value = false
    }
  }

  return {
    isSearching,
    isSubmitting,
    isVerifying,
    searchGuardians,
    matchGuardian,
    appointGuardian,
    verifyAppointment,
  }
}
