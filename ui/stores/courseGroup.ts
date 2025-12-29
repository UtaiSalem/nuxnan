import { defineStore } from 'pinia'

export const useCourseGroupStore = defineStore('courseGroup', () => {
  // State
  const groups = ref<any[]>([])
  const currentGroup = ref<any>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const lastFetchTime = ref<number | null>(null)
  const lastCourseId = ref<string | number | null>(null)
  const cacheDuration = 5 * 60 * 1000 // 5 minutes

  // Getters
  const groupsCount = computed(() => groups.value.length)
  
  const getGroupById = computed(() => (groupId: number) => {
    return groups.value.find(g => g.id === groupId)
  })

  const hasGroups = computed(() => groups.value.length > 0)
  
  const isCacheValid = computed(() => (courseId?: string | number) => {
    if (!lastFetchTime.value) return false
    if (courseId && lastCourseId.value != courseId) return false
    return Date.now() - lastFetchTime.value < cacheDuration
  })

  // Actions
  const setGroups = (groupsData: any[], courseId?: string | number) => {
    groups.value = groupsData || []
    lastFetchTime.value = Date.now()
    if (courseId) {
      lastCourseId.value = courseId
    }
  }

  const setCurrentGroup = (group: any) => {
    currentGroup.value = group
  }

  const addGroup = (group: any) => {
    const exists = groups.value.find(g => g.id === group.id)
    if (!exists) {
      groups.value.push(group)
    }
  }

  const updateGroup = (groupId: number, updates: Partial<any>) => {
    const index = groups.value.findIndex(g => g.id === groupId)
    if (index !== -1) {
      groups.value[index] = { ...groups.value[index], ...updates }
    }
    // Update current group if it's the same
    if (currentGroup.value?.id === groupId) {
      currentGroup.value = { ...currentGroup.value, ...updates }
    }
  }

  const removeGroup = (groupId: number) => {
    groups.value = groups.value.filter(g => g.id !== groupId)
    if (currentGroup.value?.id === groupId) {
      currentGroup.value = null
    }
  }

  const clearGroups = () => {
    groups.value = []
    currentGroup.value = null
    error.value = null
    lastFetchTime.value = null
    lastCourseId.value = null
  }

  const fetchGroups = async (courseId: string | number, forceRefresh = false) => {
    // Return cached data if valid and for same course
    if (!forceRefresh && isCacheValid.value(courseId)) {
      return { success: true, groups: groups.value }
    }

    isLoading.value = true
    error.value = null
    
    try {
      const api = useApi()
      const response = await api.get(`/api/courses/${courseId}/groups`)
      
      if (response.success) {
        setGroups((response as any).groups || (response as any).data, courseId)
        return response
      }
    } catch (err: any) {
      error.value = err.data?.msg || 'ไม่สามารถโหลดข้อมูลกลุ่มได้'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  const fetchGroupById = async (courseId: string | number, groupId: string | number) => {
    isLoading.value = true
    error.value = null
    
    try {
      const api = useApi()
      const response = await api.get(`/api/courses/${courseId}/groups/${groupId}`)
      
      if (response.success) {
        setCurrentGroup(response.group || response.data)
        return response
      }
    } catch (err: any) {
      error.value = err.data?.msg || 'ไม่สามารถโหลดข้อมูลกลุ่มได้'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  const createGroup = async (courseId: string | number, data: any) => {
    isLoading.value = true
    error.value = null
    
    try {
      const api = useApi()
      const response = await api.post(`/api/courses/${courseId}/groups`, data)
      
      const group = (response as any).group || (response as any).data || response
      if (group) {
        addGroup(group)
      }
      return response
    } catch (err: any) {
      error.value = err.data?.msg || 'ไม่สามารถสร้างกลุ่มได้'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  const updateGroupData = async (courseId: string | number, groupId: string | number, data: any) => {
    isLoading.value = true
    error.value = null
    
    try {
      const api = useApi()
      const response = await api.patch(`/api/courses/${courseId}/groups/${groupId}`, data)
      
      const group = (response as any).group || (response as any).data || response
      if (group) {
        updateGroup(groupId as number, group)
      }
      return response
    } catch (err: any) {
      error.value = err.data?.msg || 'ไม่สามารถอัพเดทกลุ่มได้'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  const deleteGroup = async (courseId: string | number, groupId: string | number) => {
    isLoading.value = true
    error.value = null
    
    try {
      const api = useApi()
      const response = await api.delete(`/api/courses/${courseId}/groups/${groupId}`)
      
      if (response.success) {
        removeGroup(groupId as number)
        return response
      }
    } catch (err: any) {
      error.value = err.data?.msg || 'ไม่สามารถลบกลุ่มได้'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  return {
    // State
    groups,
    currentGroup,
    isLoading,
    error,
    
    // Getters
    groupsCount,
    getGroupById,
    hasGroups,
    isCacheValid,
    
    // Actions
    setGroups,
    setCurrentGroup,
    addGroup,
    updateGroup,
    removeGroup,
    clearGroups,
    fetchGroups,
    fetchGroupById,
    createGroup,
    updateGroupData,
    deleteGroup
  }
})
