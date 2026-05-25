export function useTypingApi() {
  const { $api } = useNuxtApp()

  async function fetchWords(lang: string, difficulty: string, limit: number = 20) {
    const response = await $api.get('/typing/words', {
      params: { language: lang, difficulty, limit }
    })
    return response.data.data
  }

  async function fetchSentences(lang: string, difficulty: string, limit: number = 5) {
    const response = await $api.get('/typing/sentences', {
      params: { language: lang, difficulty, limit }
    })
    return response.data.data
  }

  async function submitSession(data: any) {
    const response = await $api.post('/typing/sessions', data)
    const session = response.data.data

    // If it's a challenge, try to complete it
    const route = useRoute()
    const challengeId = route.query.challenge
    
    if (challengeId && session.session_id) {
      try {
        await completeChallenge(challengeId as string, {
          session_id: session.session_id,
          score: session.score,
          wpm: session.wpm,
          accuracy: session.accuracy
        })
      } catch (e) {
        console.error('Failed to complete challenge', e)
      }
    }

    return session
  }

  async function completeChallenge(challengeId: string, data: any) {
    const response = await $api.post(`/typing/daily/${challengeId}/complete`, data)
    return response.data
  }

  async function fetchLeaderboard(mode: string, lang?: string, period: string = 'all') {
    const response = await $api.get('/typing/leaderboard', {
      params: { game_mode: mode, language: lang, period }
    })
    return response.data.data
  }

  async function fetchStats() {
    const response = await $api.get('/typing/sessions/stats')
    return response.data.data
  }

  async function fetchWordsByIds(ids: number[]) {
    const response = await $api.get('/typing/words', {
      params: { ids: ids.join(',') }
    })
    return response.data.data
  }

  async function fetchSentencesByIds(ids: number[]) {
    const response = await $api.get('/typing/sentences', {
      params: { ids: ids.join(',') }
    })
    return response.data.data
  }

  return {
    fetchWords,
    fetchSentences,
    fetchWordsByIds,
    fetchSentencesByIds,
    submitSession,
    completeChallenge,
    fetchLeaderboard,
    fetchStats
  }
}
