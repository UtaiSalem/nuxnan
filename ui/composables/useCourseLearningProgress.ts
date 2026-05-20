import { ref, computed } from 'vue'

export function useCourseLearningProgress(courseId: string | number, memberId: string | number | null) {
  const api = useApi()
  
  const lessons = ref<any[]>([])
  const assignments = ref<any[]>([])
  const quizzes = ref<any[]>([])
  const overallProgress = ref<any>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const fetchProgress = async () => {
    if (!memberId) {
      lessons.value = []
      assignments.value = []
      quizzes.value = []
      overallProgress.value = { progress_percentage: 0, status_label: 'ยังไม่ได้สมัครเรียน' }
      return
    }

    isLoading.value = true
    error.value = null

    try {
      const response = await api.get(`/api/courses/${courseId}/members/${memberId}/progress`)
      
      lessons.value = response.lessons || []
      assignments.value = response.assignments || []
      quizzes.value = response.quizzes || []
      overallProgress.value = response.overall_progress || { 
        progress_percentage: 0, 
        status_label: 'ยังไม่ได้เริ่ม' 
      }
    } catch (err: any) {
      console.error('Error fetching course progress:', err)
      error.value = 'ไม่สามารถโหลดข้อมูลความคืบหน้าได้'
    } finally {
      isLoading.value = false
    }
  }

  // Initial fetch
  if (process.client) {
    fetchProgress()
  }

  return {
    lessons,
    assignments,
    quizzes,
    overallProgress,
    isLoading,
    error,
    refreshProgress: fetchProgress
  }
}
