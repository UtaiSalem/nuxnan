/**
 * Composable for performing student profile edits (sectional edit actions)
 */
import { ref, type Ref } from 'vue'
import { useApi } from './useApi'

export const useStudentEdit = (academyName: Ref<string> | string, studentId: Ref<number | string> | number | string) => {
  const api = useApi()
  const isSubmitting = ref(false)

  const getParams = () => {
    const acadName = typeof academyName === 'string' ? academyName : academyName.value
    const stuId = typeof studentId === 'number' || typeof studentId === 'string' ? studentId : studentId.value
    return { acadName, stuId }
  }

  const updatePersonal = async (payload: any) => {
    const { acadName, stuId } = getParams()
    isSubmitting.value = true
    try {
      const res = await api.patch(`/api/academies/${acadName}/students/${stuId}/personal`, payload)
      return res
    } finally {
      isSubmitting.value = false
    }
  }

  const addAddress = async (payload: any) => {
    const { acadName, stuId } = getParams()
    isSubmitting.value = true
    try {
      const res = await api.post(`/api/academies/${acadName}/students/${stuId}/addresses`, payload)
      return res
    } finally {
      isSubmitting.value = false
    }
  }

  const updateAddress = async (addressId: number, payload: any) => {
    const { acadName, stuId } = getParams()
    isSubmitting.value = true
    try {
      const res = await api.put(`/api/academies/${acadName}/students/${stuId}/addresses/${addressId}`, payload)
      return res
    } finally {
      isSubmitting.value = false
    }
  }

  const deleteAddress = async (addressId: number) => {
    const { acadName, stuId } = getParams()
    isSubmitting.value = true
    try {
      const res = await api.delete(`/api/academies/${acadName}/students/${stuId}/addresses/${addressId}`)
      return res
    } finally {
      isSubmitting.value = false
    }
  }

  const addContact = async (payload: any) => {
    const { acadName, stuId } = getParams()
    isSubmitting.value = true
    try {
      const res = await api.post(`/api/academies/${acadName}/students/${stuId}/contacts`, payload)
      return res
    } finally {
      isSubmitting.value = false
    }
  }

  const updateContact = async (contactId: number, payload: any) => {
    const { acadName, stuId } = getParams()
    isSubmitting.value = true
    try {
      const res = await api.put(`/api/academies/${acadName}/students/${stuId}/contacts/${contactId}`, payload)
      return res
    } finally {
      isSubmitting.value = false
    }
  }

  const deleteContact = async (contactId: number) => {
    const { acadName, stuId } = getParams()
    isSubmitting.value = true
    try {
      const res = await api.delete(`/api/academies/${acadName}/students/${stuId}/contacts/${contactId}`)
      return res
    } finally {
      isSubmitting.value = false
    }
  }

  const updateGuardian = async (payload: any) => {
    const { acadName, stuId } = getParams()
    isSubmitting.value = true
    try {
      const res = await api.put(`/api/academies/${acadName}/students/${stuId}/guardians`, payload)
      return res
    } finally {
      isSubmitting.value = false
    }
  }

  const updateHealth = async (healthId: number | null, payload: any) => {
    const { acadName, stuId } = getParams()
    isSubmitting.value = true
    try {
      if (healthId) {
        const res = await api.put(`/api/academies/${acadName}/students/${stuId}/health/${healthId}`, payload)
        return res
      } else {
        const res = await api.post(`/api/academies/${acadName}/students/${stuId}/health`, payload)
        return res
      }
    } finally {
      isSubmitting.value = false
    }
  }

  const updateAcademic = async (academicInfoId: number | null, payload: any) => {
    const { acadName, stuId } = getParams()
    isSubmitting.value = true
    try {
      if (academicInfoId) {
        const res = await api.put(`/api/academies/${acadName}/students/${stuId}/academic-info/${academicInfoId}`, payload)
        return res
      } else {
        const res = await api.post(`/api/academies/${acadName}/students/${stuId}/academic-info`, payload)
        return res
      }
    } finally {
      isSubmitting.value = false
    }
  }

  return {
    isSubmitting,
    updatePersonal,
    addAddress,
    updateAddress,
    deleteAddress,
    addContact,
    updateContact,
    deleteContact,
    updateGuardian,
    updateHealth,
    updateAcademic,
  }
}
