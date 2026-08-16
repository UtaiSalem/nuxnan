export const useCourseCreateGate = () => {
  const api = useApi()
  const { user } = useAuth()
  const authStore = useAuthStore()
  const threshold = useState<number>('courseCreateThreshold', () => 120000)
  const thresholdFetched = useState<boolean>('courseCreateThresholdFetched', () => false)

  const points = computed(() => {
    const value = Number(authStore.points)
    return Number.isFinite(value) ? value : 0
  })
  const canCreate = computed(() => points.value >= threshold.value)
  const remaining = computed(() => Math.max(0, threshold.value - points.value))

  const setThreshold = (value: number) => {
    if (Number.isFinite(value) && value > 0) {
      threshold.value = value
    }
  }

  const fetchThreshold = async () => {
    if (thresholdFetched.value || !user.value) return

    try {
      const response: any = await api.get(`/api/courses/users/${user.value.id}/my-courses`, {
        params: { per_page: 1 }
      })
      const value = Number(response?.create_course_threshold)
      if (Number.isFinite(value) && value > 0) {
        threshold.value = value
        thresholdFetched.value = true
      }
    } catch (error) {
      console.error('Failed to fetch course creation threshold', error)
    }
  }

  return { threshold, points, canCreate, remaining, setThreshold, fetchThreshold }
}
