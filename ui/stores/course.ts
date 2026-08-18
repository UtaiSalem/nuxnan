import { defineStore } from 'pinia'

export const useCourseStore = defineStore('course', () => {
  // State
  const currentCourse = ref<any>(null)
  const academy = ref<any>(null)
  const isCourseAdmin = ref(false)
  const courseMemberOfAuth = ref<any>(null)
  const courseGroups = ref<any[]>([])
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

  const setCourseMemberOfAuth = (member: any) => {
    courseMemberOfAuth.value = member
  }

  const setCourseGroups = (groups: any[]) => {
    courseGroups.value = groups
  }

  const updateCourse = (updates: Partial<any>) => {
    if (currentCourse.value) {
      currentCourse.value = { ...currentCourse.value, ...updates }
    }
  }

  // Drop the cache timestamp so the next fetchCourse() hits the API again.
  // Call this after any mutation that changes what /courses/{id}/feeds returns
  // (lessons, topics, ordering) — otherwise the course page keeps serving the
  // stale copy for up to `cacheDuration`.
  const invalidateCourse = () => {
    lastFetchTime.value = null
  }

  const clearCourse = () => {
    currentCourse.value = null
    academy.value = null
    isCourseAdmin.value = false
    courseMemberOfAuth.value = null
    courseGroups.value = []
    lessons.value = []
    error.value = null
    lastFetchTime.value = null
  }

  const fetchCourse = async (courseId: string | number, forceRefresh = false) => {
    const isSameCourse = currentCourse.value?.id == courseId
    
    // Return cached data if valid and not forcing refresh
    if (!forceRefresh && isCacheValid.value && isSameCourse) {
      return { 
        success: true, 
        course: currentCourse.value, 
        academy: academy.value, 
        isCourseAdmin: isCourseAdmin.value,
        courseMemberOfAuth: courseMemberOfAuth.value,
        courseMember: courseMemberOfAuth.value,
        courseGroups: courseGroups.value
      }
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
        setCourseMemberOfAuth(response.courseMemberOfAuth || response.courseMember || null)
        setCourseGroups(response.courseGroups || [])
        
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
    const normalizedCourseId = Number(courseId)
    const lessonsBelongToCourse = lessons.value.length > 0
      && lessons.value.every((lesson: any) => Number(lesson.course_id) === normalizedCourseId)

    if (!forceRefresh && lessonsBelongToCourse && currentCourse.value?.id == courseId) {
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

  const reorderLessons = async (courseId: number, lessonIds: number[]) => {
    try {
      const api = useApi()
      const response = await api.patch(`/api/courses/${courseId}/lessons/reorder`, {
        lessons: lessonIds
      })
      invalidateCourse()
      return response
    } catch (err: any) {
      console.error('Error reordering lessons in store:', err)
      throw err
    }
  }

  const reorderTopics = async (lessonId: number, topicIds: number[]) => {
    try {
      const api = useApi()
      const response = await api.patch(`/api/lessons/${lessonId}/topics/reorder`, {
        topics: topicIds
      })
      invalidateCourse()
      return response
    } catch (err: any) {
      console.error('Error reordering topics in store:', err)
      throw err
    }
  }

  const reorderGroups = async (courseId: number, groupIds: number[]) => {
    try {
      const api = useApi()
      const response = await api.patch(`/api/courses/${courseId}/groups/reorder`, {
        groups: groupIds
      })
      return response
    } catch (err: any) {
      console.error('Error reordering groups in store:', err)
      throw err
    }
  }

  return {
    // State
    currentCourse,
    academy,
    isCourseAdmin,
    courseMemberOfAuth,
    courseGroups,
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
    setCourseMemberOfAuth,
    setCourseGroups,
    updateCourse,
    invalidateCourse,
    clearCourse,
    fetchCourse,
    fetchLessons,
    reorderLessons,
    reorderTopics,
    reorderGroups
  }
})
