export interface TypingStageItem {
  id: number
  language: 'th' | 'en'
  level: number
  stage_number: number
  title: string
  description: string
  stage_type: 'drill' | 'words' | 'phrases' | 'review' | 'exam'
  target_keys: string[]
  min_accuracy: number
  min_wpm: number
  min_duration_sec: number
  pass_count_needed: number
  pass_window_size: number
  xp_reward: number
  is_unlocked: boolean
  is_passed: boolean
  stars: number
  best_accuracy: number
  best_wpm: number
  attempts_count: number
  recent_attempts: Array<{
    passed: boolean
    accuracy: number
    wpm: number
    cpm: number
    time_elapsed: number
    date: string
  }>
}

export interface CurriculumResponse {
  language: 'th' | 'en'
  level: number
  stages: TypingStageItem[]
  total_stages: number
  passed_stages: number
  total_stars: number
}

export interface StageDetailResponse {
  stage: {
    id: number
    language: 'th' | 'en'
    level: number
    stage_number: number
    title: string
    description: string
    stage_type: 'drill' | 'words' | 'phrases' | 'review' | 'exam'
    target_keys: string[]
    exercise_pool: string[]
    min_accuracy: number
    min_wpm: number
    min_duration_sec: number
    pass_count_needed: number
    pass_window_size: number
    xp_reward: number
  }
  progress: {
    is_unlocked: boolean
    is_passed: boolean
    stars: number
    best_accuracy: number
    best_wpm: number
    best_cpm: number
    attempts_count: number
    recent_attempts: any[]
  }
}

export interface SubmitStagePayload {
  session_token?: string
  wpm: number
  accuracy: number
  time_elapsed: number
  correct_chars: number
  total_chars: number
  mistakes: number
  max_combo?: number
  correct_words?: number
  total_words?: number
  score?: number
  weak_keys?: string[]
}

export interface StageSubmitResult {
  stage_id: number
  stage_number: number
  passed: boolean
  attempt_passed: boolean
  stars: number
  best_stars: number
  accuracy: number
  wpm: number
  cpm: number
  reasons: string[]
  next_stage_unlocked: boolean
  next_stage_id: number | null
  xp_earned: number
  recent_attempts: any[]
  exam_status: {
    needed: number
    window: number
    passes_count: number
  }
}

export function useTypingCurriculum() {
  const { $api } = useNuxtApp()

  async function fetchCurriculum(language: 'th' | 'en' = 'th', level: number = 1): Promise<CurriculumResponse> {
    const response = await $api.get('/typing/curriculum', {
      params: { language, level },
    })
    return response.data.data
  }

  async function fetchStage(stageId: number): Promise<StageDetailResponse> {
    const response = await $api.get(`/typing/curriculum/stages/${stageId}`)
    return response.data.data
  }

  async function submitStageAttempt(stageId: number, payload: SubmitStagePayload): Promise<StageSubmitResult> {
    const response = await $api.post(`/typing/curriculum/stages/${stageId}/submit`, payload)
    return response.data.data
  }

  async function fetchWeakKeys(language: 'th' | 'en' = 'th'): Promise<string[]> {
    const response = await $api.get('/typing/curriculum/weak-keys', {
      params: { language },
    })
    return response.data.data.weak_keys
  }

  return {
    fetchCurriculum,
    fetchStage,
    submitStageAttempt,
    fetchWeakKeys,
  }
}
