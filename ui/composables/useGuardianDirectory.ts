import { ref, type Ref } from 'vue'
import { useApi } from './useApi'
// errorStatus/errorMessage deliberately stay exported from useGuardianAppointment only.
// Re-exporting them here makes Nuxt auto-import the same name from two files and pick one at random.

export const useGuardianDirectory = (academyName: Ref<string | number> | string | number) => {
  const api = useApi()
  const isLoading = ref(false)
  const isLoadingStats = ref(false)
  const isSavingContact = ref(false)

  const getAcadName = () => {
    return typeof academyName === 'string' || typeof academyName === 'number' ? academyName : academyName.value
  }

  const fetchGuardians = async (params: { page?: number; per_page?: number; search?: string; type?: string }) => {
    isLoading.value = true
    try {
      const queryParams = new URLSearchParams()
      if (params.page) queryParams.append('page', params.page.toString())
      if (params.per_page) queryParams.append('per_page', params.per_page.toString())
      if (params.search) queryParams.append('search', params.search)
      if (params.type) queryParams.append('type', params.type)

      const res = await api.get(`/api/academies/${getAcadName()}/guardians?${queryParams.toString()}`)
      return res
    } finally {
      isLoading.value = false
    }
  }

  const fetchStatistics = async () => {
    isLoadingStats.value = true
    try {
      const res = await api.get(`/api/academies/${getAcadName()}/guardians/statistics`)
      return res
    } finally {
      isLoadingStats.value = false
    }
  }

  const fetchContacts = async (guardianId: number) => {
    isLoading.value = true
    try {
      const res = await api.get(`/api/academies/${getAcadName()}/guardian-people/${guardianId}/contacts`)
      return res
    } finally {
      isLoading.value = false
    }
  }

  const addContact = async (guardianId: number, payload: { contact_type: string; contact_value: string; is_primary?: boolean }) => {
    isSavingContact.value = true
    try {
      const res = await api.post(`/api/academies/${getAcadName()}/guardian-people/${guardianId}/contacts`, payload)
      return res
    } finally {
      isSavingContact.value = false
    }
  }

  const updateContact = async (guardianId: number, contactId: number, payload: Record<string, any>) => {
    isSavingContact.value = true
    try {
      const res = await api.patch(`/api/academies/${getAcadName()}/guardian-people/${guardianId}/contacts/${contactId}`, payload)
      return res
    } finally {
      isSavingContact.value = false
    }
  }

  const deleteContact = async (guardianId: number, contactId: number) => {
    isSavingContact.value = true
    try {
      const res = await api.delete(`/api/academies/${getAcadName()}/guardian-people/${guardianId}/contacts/${contactId}`)
      return res
    } finally {
      isSavingContact.value = false
    }
  }

  const setPrimaryContact = async (guardianId: number, contactId: number) => {
    isSavingContact.value = true
    try {
      const res = await api.patch(`/api/academies/${getAcadName()}/guardian-people/${guardianId}/contacts/${contactId}/set-primary`)
      return res
    } finally {
      isSavingContact.value = false
    }
  }

  return {
    isLoading,
    isLoadingStats,
    isSavingContact,
    fetchGuardians,
    fetchStatistics,
    fetchContacts,
    addContact,
    updateContact,
    deleteContact,
    setPrimaryContact,
  }
}
