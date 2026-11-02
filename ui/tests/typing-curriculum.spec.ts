import { describe, expect, it, vi, beforeEach } from 'vitest'
import { useTypingCurriculum } from '../composables/useTypingCurriculum'

const mockApi = {
  get: vi.fn(),
  post: vi.fn(),
}

vi.stubGlobal('useNuxtApp', () => ({
  $api: mockApi,
}))

describe('useTypingCurriculum', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('fetchCurriculum calls API with language and level', async () => {
    mockApi.get.mockResolvedValueOnce({
      data: {
        data: {
          language: 'th',
          level: 1,
          stages: [{ id: 1, stage_number: 1, title: 'แป้นเหย้า: ด และ ่' }],
          total_stages: 15,
          passed_stages: 0,
          total_stars: 0,
        },
      },
    })

    const { fetchCurriculum } = useTypingCurriculum()
    const result = await fetchCurriculum('th', 1)

    expect(mockApi.get).toHaveBeenCalledWith('/typing/curriculum', {
      params: { language: 'th', level: 1 },
    })
    expect(result.total_stages).toBe(15)
    expect(result.stages[0].stage_number).toBe(1)
  })

  it('fetchStage calls stage details endpoint', async () => {
    mockApi.get.mockResolvedValueOnce({
      data: {
        data: {
          stage: { id: 10, title: 'เอื้อมและกลับตำแหน่งเดิม' },
          progress: { is_unlocked: true, is_passed: false },
        },
      },
    })

    const { fetchStage } = useTypingCurriculum()
    const result = await fetchStage(10)

    expect(mockApi.get).toHaveBeenCalledWith('/typing/curriculum/stages/10')
    expect(result.stage.title).toBe('เอื้อมและกลับตำแหน่งเดิม')
    expect(result.progress.is_unlocked).toBe(true)
  })

  it('submitStageAttempt sends attempt data to submit endpoint', async () => {
    mockApi.post.mockResolvedValueOnce({
      data: {
        data: {
          stage_id: 1,
          attempt_passed: true,
          passed: true,
          stars: 3,
          accuracy: 98,
          wpm: 25,
          next_stage_unlocked: true,
          next_stage_id: 2,
        },
      },
    })

    const { submitStageAttempt } = useTypingCurriculum()
    const result = await submitStageAttempt(1, {
      wpm: 25,
      accuracy: 98,
      time_elapsed: 20,
      correct_chars: 98,
      total_chars: 100,
      mistakes: 2,
    })

    expect(mockApi.post).toHaveBeenCalledWith('/typing/curriculum/stages/1/submit', {
      wpm: 25,
      accuracy: 98,
      time_elapsed: 20,
      correct_chars: 98,
      total_chars: 100,
      mistakes: 2,
    })
    expect(result.passed).toBe(true)
    expect(result.stars).toBe(3)
    expect(result.next_stage_unlocked).toBe(true)
  })

  it('fetchWeakKeys requests weak keys by language', async () => {
    mockApi.get.mockResolvedValueOnce({
      data: {
        data: {
          language: 'th',
          weak_keys: ['ฟ', 'ว', 'เ'],
        },
      },
    })

    const { fetchWeakKeys } = useTypingCurriculum()
    const keys = await fetchWeakKeys('th')

    expect(mockApi.get).toHaveBeenCalledWith('/typing/curriculum/weak-keys', {
      params: { language: 'th' },
    })
    expect(keys).toEqual(['ฟ', 'ว', 'เ'])
  })
})
