import { defineStore } from 'pinia'

export const useCourseStore = defineStore('course', () => {
  // State
  const currentCourse = ref<any>(null)
  const academy = ref<any>(null)
  const isCourseAdmin = ref(false)
  const lessons = ref<any[]>([])
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

  const setLessons = (lessonsData: any[]) => {
    lessons.value = lessonsData
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
    lessons.value = []
    error.value = null
    lastFetchTime.value = null
  }

  const fetchCourse = async (courseId: string | number, forceRefresh = false) => {
    const isSameCourse = currentCourse.value?.id == courseId
    
    // Return cached data if valid and not forcing refresh
    if (!forceRefresh && isCacheValid.value && isSameCourse) {
      return { success: true, course: currentCourse.value, academy: academy.value, isCourseAdmin: isCourseAdmin.value }
    }

    // Only show loading if we don't have the course data yet or forcing a hard refresh
    const shouldShowLoading = !isSameCourse || forceRefresh
    if (shouldShowLoading) {
      isLoading.value = true
    }
    
    error.value = null

    try {
      const api = useApi()
      const response = await api.get(`/api/courses/${courseId}/feeds`)

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
      if (shouldShowLoading) {
        isLoading.value = false
      }
    }
  }

  const fetchLessons = async (courseId: string | number, forceRefresh = false) => {
    if (!forceRefresh && lessons.value.length > 0 && currentCourse.value?.id == courseId) {
      return { success: true, lessons: lessons.value }
    }

    try {
      const api = useApi()
      const response = await api.get(`/api/courses/${courseId}/lessons`)
      
      if (response.success || response.lessons || response.data) {
        const lessonPayload = response.lessons || response.data || response
        const normalizedLessons = Array.isArray(lessonPayload)
          ? lessonPayload
          : lessonPayload?.data || []
          
        setLessons(normalizedLessons)
        return { success: true, lessons: normalizedLessons }
      }
      return { success: false, lessons: [] }
    } catch (err: any) {
      console.error('Error fetching lessons in store:', err)
      return { success: false, lessons: [] }
    }
  }

  return {
    // State
    currentCourse,
    academy,
    isCourseAdmin,
    lessons,
    isLoading,
    error,
    
    // Getters
    isCacheValid,
    
    // Actions
    setCourse,
    setLessons,
    setAcademy,
    setIsCourseAdmin,
    updateCourse,
    clearCourse,
    fetchCourse,
    fetchLessons
  }
})
