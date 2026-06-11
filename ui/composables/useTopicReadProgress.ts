import { ref, computed, type Ref } from 'vue'

interface TopicProgressItem {
  topic_id: number
  status: 'not_started' | 'in_progress' | 'completed'
  started_at: string | null
  completed_at: string | null
  required_seconds: number
}

interface TopicProgressSummary {
  total_topics: number
  completed_topics: number
  progress_percentage: number
}

interface CompleteResult {
  success: boolean
  already_completed?: boolean
  lesson_completed?: boolean
  reward?: any
  message?: string
  code?: string
  required_seconds?: number
  remaining_seconds?: number
}

export function useTopicReadProgress(lessonId: Ref<number>) {
  const api = useApi()
  const topicProgress = ref<Record<number, TopicProgressItem>>({})
  const summary = ref<TopicProgressSummary>({
    total_topics: 0,
    completed_topics: 0,
    progress_percentage: 0
  })
  const isLoading = ref(false)

  const loadProgress = async () => {
    if (!lessonId.value) return
    
    isLoading.value = true
    try {
      const response = await api.get(`/api/lessons/${lessonId.value}/topics/reading-progress`)
      if (response.success) {
        const progressList: TopicProgressItem[] = response.progress
        topicProgress.value = progressList.reduce((acc, item) => {
          acc[item.topic_id] = item
          return acc
        }, {} as Record<number, TopicProgressItem>)
        summary.value = response.summary
      }
    } catch (error) {
      console.error('Failed to load topic progress:', error)
    } finally {
      isLoading.value = false
    }
  }

  const startReading = async (topicId: number) => {
    // If already completed or in progress, skip API call for optimization
    if (topicProgress.value[topicId]?.status === 'completed' || 
        topicProgress.value[topicId]?.status === 'in_progress') {
      return
    }

    try {
      const response = await api.post(`/api/topics/${topicId}/reading/start`)
      if (response.success) {
        topicProgress.value[topicId] = {
          ...topicProgress.value[topicId],
          ...response.progress,
          topic_id: topicId
        }
      }
    } catch (error) {
      console.error('Failed to start reading topic:', error)
    }
  }

  const completeReading = async (topicId: number): Promise<CompleteResult> => {
    try {
      const response = await api.post(`/api/topics/${topicId}/reading/complete`)
      if (response.success) {
        // Update local state
        topicProgress.value[topicId] = {
          ...topicProgress.value[topicId],
          ...response.progress,
          topic_id: topicId
        }
        
        // Recalculate summary locally or we can reload
        const items = Object.values(topicProgress.value)
        const completed = items.filter(i => i.status === 'completed').length
        summary.value = {
          total_topics: items.length,
          completed_topics: completed,
          progress_percentage: items.length > 0 ? Math.round((completed / items.length) * 100) : 0
        }

        return {
          success: true,
          already_completed: response.already_completed,
          lesson_completed: response.lesson_completed,
          reward: response.reward
        }
      }
      return { success: false }
    } catch (error: any) {
      if (error.data) {
        return {
          success: false,
          code: error.data.code,
          message: error.data.message,
          required_seconds: error.data.required_seconds,
          remaining_seconds: error.data.remaining_seconds
        }
      }
      return { success: false, message: 'เกิดข้อผิดพลาดในการบันทึกข้อมูล' }
    }
  }

  const isTopicCompleted = (topicId: number) => {
    return topicProgress.value[topicId]?.status === 'completed'
  }

  const getTopicStatus = (topicId: number) => {
    return topicProgress.value[topicId]?.status || 'not_started'
  }

  return {
    topicProgress,
    summary,
    isLoading,
    loadProgress,
    startReading,
    completeReading,
    isTopicCompleted,
    getTopicStatus
  }
}
