/**
 * Composable for managing a student card (updating, uploading photo, deleting photo)
 */
import { ref, type Ref } from 'vue'
import { useApi } from './useApi'

export const useStudentCard = (academyName: Ref<string | number> | string | number, cardId: Ref<number | string | null> | number | string | null) => {
  const api = useApi()
  const isSubmitting = ref(false)

  const getParams = () => {
    const acadName = typeof academyName === 'object' && 'value' in academyName ? academyName.value : academyName
    const cId = typeof cardId === 'object' && cardId !== null && 'value' in cardId ? cardId.value : cardId
    return { acadName, cId }
  }

  const uploadPhoto = async (file: File) => {
    const { acadName, cId } = getParams()
    if (!cId) throw new Error('Card ID is required to upload photo')
    
    isSubmitting.value = true
    const formData = new FormData()
    formData.append('photo', file)

    try {
      // POST /admin/upload-photo/{card}
      const res = await api.post(`/api/academies/${acadName}/student-cards/admin/upload-photo/${cId}`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })
      return res
    } finally {
      isSubmitting.value = false
    }
  }

  const updateCard = async (payload: any) => {
    const { acadName, cId } = getParams()
    if (!cId) throw new Error('Card ID is required to update')

    isSubmitting.value = true
    try {
      // PUT /{card}
      const res = await api.put(`/api/academies/${acadName}/student-cards/${cId}`, payload)
      return res
    } finally {
      isSubmitting.value = false
    }
  }

  const deletePhoto = async () => {
    const { acadName, cId } = getParams()
    if (!cId) throw new Error('Card ID is required to delete photo')

    isSubmitting.value = true
    try {
      // DELETE /{card}/photo
      const res = await api.delete(`/api/academies/${acadName}/student-cards/${cId}/photo`)
      return res
    } finally {
      isSubmitting.value = false
    }
  }

  return {
    isSubmitting,
    uploadPhoto,
    updateCard,
    deletePhoto,
  }
}
