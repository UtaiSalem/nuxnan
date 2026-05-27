import { ref, computed, onUnmounted } from 'vue'
import { useNuxtApp, useRoute } from '#app'
import { useAuthStore } from '~/stores/auth'

export interface RaceParticipant {
  user_id: number
  name: string
  avatar?: string
  progress: number    // 0-100%
  wpm: number
  score: number
  status: 'waiting' | 'racing' | 'finished' | 'left'
  rank?: number
}

export interface RaceRoom {
  room_code: string
  status: 'waiting' | 'countdown' | 'racing' | 'finished'
  host_user_id: number
  game_mode: string
  language: string
  difficulty: string
  time_limit: number
  content_ids: number[]
  participants: RaceParticipant[]
}

export function useClassroomRace() {
  const { $echo, $api } = useNuxtApp()
  const authStore = useAuthStore()

  const room = ref<RaceRoom | null>(null)
  const participants = ref<RaceParticipant[]>([])
  const raceStatus = ref<'idle' | 'lobby' | 'countdown' | 'racing' | 'finished'>('idle')
  const rankings = ref<any[]>([])
  const countdown = ref(3)
  const myProgress = ref(0)
  let channel: any = null
  let progressThrottle: ReturnType<typeof setTimeout> | null = null

  // Helper to call API
  async function call(method: string, url: string, data?: any) {
    const res = await ($api as any)[method.toLowerCase()](url, data)
    return res.data
  }

  // Create Room
  async function createRoom(config: {
    game_mode: string, language: string,
    difficulty: string, time_limit: number, academy_id?: number
  }) {
    const res = await call('POST', '/typing/race/rooms', config)
    room.value = res.room
    participants.value = res.room.participants ?? []
    raceStatus.value = 'lobby'
    subscribeToChannel(res.room_code)
    return res.room_code
  }

  // Join Room
  async function joinRoom(roomCode: string) {
    const res = await call('POST', `/typing/race/rooms/${roomCode}/join`)
    room.value = res.room
    participants.value = res.room.participants ?? []
    raceStatus.value = 'lobby'
    subscribeToChannel(roomCode)
    return res.room
  }

  // Start Race (Host only)
  async function startRace() {
    if (!room.value) return
    await call('POST', `/typing/race/rooms/${room.value.room_code}/start`)
  }

  // Subscribe to Presence Channel
  function subscribeToChannel(roomCode: string) {
    if (!$echo) {
      console.warn('Echo not found, real-time features disabled')
      return
    }

    channel = ($echo as any).join(`race.${roomCode}`)
      .here((users: any[]) => {
        users.forEach(user => {
          upsertParticipant(user.id, { 
            name: user.name, 
            avatar: user.avatar, 
            status: 'waiting',
            progress: 0,
            wpm: 0,
            score: 0
          })
        })
      })
      .joining((user: any) => {
        upsertParticipant(user.id, { 
          name: user.name, 
          avatar: user.avatar, 
          status: 'waiting',
          progress: 0,
          wpm: 0,
          score: 0
        })
      })
      .leaving((user: any) => {
        const p = participants.value.find(p => p.user_id === user.id)
        if (p) p.status = 'left'
      })
      .listen('.race.started', (data: any) => {
        raceStatus.value = 'countdown'
        startCountdown()
      })
      .listen('.race.finished', (data: any) => {
        raceStatus.value = 'finished'
        rankings.value = data.rankings
      })
      .listenForWhisper('progress', (data: { user_id: number; progress: number; wpm: number }) => {
        upsertParticipant(data.user_id, { progress: data.progress, wpm: data.wpm })
      })
      .listenForWhisper('finished', (data: { user_id: number; score: number; wpm: number }) => {
        upsertParticipant(data.user_id, { status: 'finished', score: data.score, wpm: data.wpm, progress: 100 })
      })
  }

  // Whisper Progress
  function broadcastProgress(progress: number, wpm: number) {
    myProgress.value = progress
    if (progressThrottle) return
    progressThrottle = setTimeout(() => {
      progressThrottle = null
      if (!channel) return
      channel.whisper('progress', {
        user_id: authStore.user?.id,
        progress: Math.round(progress),
        wpm: Math.round(wpm),
      })
    }, 500)
  }

  function broadcastFinished(score: number, wpm: number) {
    if (!channel) return
    channel.whisper('finished', { 
      user_id: authStore.user?.id, 
      score, 
      wpm 
    })
  }

  // Submit Final Result to Server
  async function submitResult(result: any) {
    if (!room.value) return
    broadcastFinished(result.score ?? 0, result.wpm ?? 0)
    return await call('POST', `/typing/race/rooms/${room.value.room_code}/submit`, result)
  }

  function startCountdown() {
    countdown.value = 3
    const interval = setInterval(() => {
      countdown.value--
      if (countdown.value <= 0) {
        clearInterval(interval)
        raceStatus.value = 'racing'
      }
    }, 1000)
  }

  function upsertParticipant(userId: number, updates: Partial<RaceParticipant>) {
    const idx = participants.value.findIndex(p => p.user_id === userId)
    if (idx >= 0) {
      participants.value[idx] = { ...participants.value[idx], ...updates }
    } else {
      participants.value.push({ 
        user_id: userId, 
        status: 'waiting', 
        progress: 0, 
        wpm: 0, 
        score: 0, 
        ...updates 
      } as RaceParticipant)
    }
  }

  async function leaveRoom() {
    if (room.value?.room_code) {
      try { await call('DELETE', `/typing/race/rooms/${room.value.room_code}/leave`) } catch (e) { /* ignore */ }
    }
    if (channel) {
      ;($echo as any).leave(`race.${room.value?.room_code}`)
      channel = null
    }
    if (progressThrottle) {
      clearTimeout(progressThrottle)
      progressThrottle = null
    }
    room.value = null
    raceStatus.value = 'idle'
    participants.value = []
  }

  onUnmounted(() => {
    leaveRoom()
  })

  return {
    room,
    participants,
    raceStatus,
    countdown,
    rankings,
    myProgress,
    createRoom,
    joinRoom,
    startRace,
    broadcastProgress,
    submitResult,
    leaveRoom
  }
}
