import { defineStore } from 'pinia'
import type { Poll } from '~/composables/usePolls'

export const usePollStore = defineStore('poll', () => {
  // State
  const polls = ref<Poll[]>([])
  const currentPoll = ref<Poll | null>(null)
  const isLoading = ref(false)
  
  // Getters
  const getPollById = computed(() => (id: number) => {
    return polls.value.find(p => p.id === id)
  })
  
  const getActivePolls = computed(() => {
    return polls.value.filter(p => !p.is_ended)
  })
  
  const getEndedPolls = computed(() => {
    return polls.value.filter(p => p.is_ended)
  })
  
  // Actions
  const addPoll = (poll: Poll) => {
    polls.value.unshift(poll)
  }
  
  const updatePoll = (id: number, updates: Partial<Poll>) => {
    const index = polls.value.findIndex(p => p.id === id)
    if (index !== -1) {
      polls.value[index] = { ...polls.value[index], ...updates }
    }
    
    // Also update currentPoll if it's the same poll
    if (currentPoll.value?.id === id) {
      currentPoll.value = { ...currentPoll.value, ...updates }
    }
  }
  
  const removePoll = (id: number) => {
    polls.value = polls.value.filter(p => p.id !== id)
    
    if (currentPoll.value?.id === id) {
      currentPoll.value = null
    }
  }
  
  const setCurrentPoll = (poll: Poll | null) => {
    currentPoll.value = poll
  }
  
  const setPolls = (newPolls: Poll[]) => {
    polls.value = newPolls
  }
  
  const updatePollVote = (pollId: number, optionIds: number[]) => {
    const poll = polls.value.find(p => p.id === pollId)
    if (!poll) return
    
    // Update user vote status
    poll.user_voted = true
    poll.user_votes = optionIds
    
    // Update option votes
    poll.options = poll.options.map(option => ({
      ...option,
      votes: optionIds.includes(option.id) ? option.votes + 1 : option.votes,
      is_user_vote: optionIds.includes(option.id),
    }))
    
    // Recalculate percentages
    const totalVotes = poll.options.reduce((sum, opt) => sum + opt.votes, 0)
    poll.total_votes = totalVotes
    
    poll.options = poll.options.map(option => ({
      ...option,
      percentage: totalVotes > 0 ? Math.round((option.votes / totalVotes) * 100) : 0,
    }))
  }
  
  const closePollState = (pollId: number) => {
    updatePoll(pollId, { is_ended: true })
  }
  
  return {
    // State
    polls,
    currentPoll,
    isLoading,
    
    // Getters
    getPollById,
    getActivePolls,
    getEndedPolls,
    
    // Actions
    addPoll,
    updatePoll,
    removePoll,
    setCurrentPoll,
    setPolls,
    updatePollVote,
    closePollState,
  }
})
