import { defineStore } from 'pinia'

export const useCourseStore = defineStore('course', () => {
  // State
  const currentCourse = ref<any>(null)
  const academy = ref<any>(null)
  const isCourseAdmin = ref(false)
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const lastFetchTime = ref<number | null>(null)
  const cacheDuration = 5 * 60 * 1000 // 5 minutes

  // Getters
  const isCacheValid = computed(() => {
    if (!lastFetchTime.value) return false
    return Date.now() - lastFetchTime.value < cacheDuration
  })

  // Actions
  const setCourse = (course: any) => {
    currentCourse.value = course
  }

  const setAcademy = (academyData: any) => {
    academy.value = academyData
  }

  const setIsCourseAdmin = (isAdmin: boolean) => {
    isCourseAdmin.value = isAdmin
  }

  const updateCourse = (updates: Partial<any>) => {
    if (currentCourse.value) {
      currentCourse.value = { ...currentCourse.value, ...updates }
    }
  }

  const clearCourse = () => {
    currentCourse.value = null
    academy.value = null
    isCourseAdmin.value = false
    error.value = null
    lastFetchTime.value = null
  }

  const fetchCourse = async (courseId: string | number, forceRefresh = false) => {
    // Return cached data if valid and not forcing refresh
    if (!forceRefresh && isCacheValid.value && currentCourse.value?.id == courseId) {
      return { success: true, course: currentCourse.value, academy: academy.value }
    }

    isLoading.value = true
    error.value = null
    
    try {
      const api = useApi()
      const response = await api.get(`/api/courses/${courseId}`)
      
      if (response.success) {
        setCourse(response.course)
        setAcademy(response.academy)
        setIsCourseAdmin(response.isCourseAdmin || false)
        lastFetchTime.value = Date.now()
        return response
      }
    } catch (err: any) {
      error.value = err.data?.msg || 'ไม่สามารถโหลดข้อมูลรายวิชาได้'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  return {
    // State
    currentCourse,
    academy,
    isCourseAdmin,
    isLoading,
    error,
    
    // Getters
    isCacheValid,
    
    // Actions
    setCourse,
    setAcademy,
    setIsCourseAdmin,
    updateCourse,
    clearCourse,
    fetchCourse
  }
})
