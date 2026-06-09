import { defineStore } from 'pinia'

export const useAcademyStore = defineStore('academy', () => {
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const reorderGroups = async (academyId: number | string, groupIds: number[]) => {
    try {
      const api = useApi()
      const response = await api.patch(`/api/academies/${academyId}/groups/reorder`, {
        groups: groupIds
      })
      return response
    } catch (err: any) {
      console.error('Error reordering academy groups in store:', err)
      throw err
    }
  }

  return {
    isLoading,
    error,
    reorderGroups
  }
})
