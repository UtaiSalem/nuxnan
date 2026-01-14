import { useAuthStore } from '~/stores/auth'

// API Response Type
interface ApiResponse<T = any> {
  success: boolean
  data?: T
  message?: string
  poll?: T
  comment?: T
  activity?: T
}

// Type definitions for poll
export interface PollComment {
  id: number
  poll_id: number
  content: string
  created_at: string
  diff_humans_created_at: string
  user: {
    id: number
    name: string
    avatar: string
  }
}

// Type definitions for poll
export interface PollOption {
  id: number
  text: string
  votes: number
  percentage: number
  is_user_vote?: boolean
}

export interface Poll {
  id: number
  question: string
  options: PollOption[]
  total_votes: number
  is_multiple: boolean
  is_ended: boolean
  ends_at: string
  created_at: string
  user_voted: boolean
  user_votes?: number[]
  points_pool?: number
  points_per_vote?: number
  points_distributed?: number
  author: {
    id: number
    name: string
    username: string
    avatar?: string
  }
  time_remaining?: string
  diff_humans_created_at?: string
  reaction_counts: {
    likes: number
    dislikes: number
    comments: number
  }
  user_reactions: {
    is_liked: boolean
    is_disliked: boolean
  }
  comments?: PollComment[]
}

export interface CreatePollOptions {
  question: string
  options: string[]
  duration: number // hours
  is_multiple: boolean
  points_pool?: number
  max_votes?: number
  privacy_settings?: number
  location_name?: string
  tagged_users?: number[]
}

export interface UpdatePollOptions {
  question?: string
  options?: string[]
  duration?: number
  is_multiple?: boolean
}

export const usePolls = () => {
  const { $apiFetch } = useNuxtApp()
  const authStore = useAuthStore()
  
  // Create a new poll
  const createPoll = async (options: CreatePollOptions) => {
    try {
      const formData = new FormData()
      formData.append('question', options.question)
      formData.append('duration', String(options.duration))
      formData.append('is_multiple', options.is_multiple ? '1' : '0')
      formData.append('privacy_settings', String(options.privacy_settings ?? 3))
      
      if (options.points_pool !== undefined) {
        formData.append('points_pool', String(options.points_pool))
      }

      if (options.max_votes !== undefined) {
        formData.append('max_votes', String(options.max_votes))
      }
      
      // Add options
      options.options.forEach((option, index) => {
        formData.append(`options[${index}]`, option)
      })
      
      // Add location if provided
      if (options.location_name) {
        formData.append('location_name', options.location_name)
      }
      
      // Add tagged users if provided
      if (options.tagged_users && options.tagged_users.length > 0) {
        options.tagged_users.forEach((userId, index) => {
          formData.append(`tagged_users[${index}]`, String(userId))
        })
      }
      
      const response = await $apiFetch('/api/polls', {
        method: 'POST',
        body: formData,
      }) as ApiResponse<Poll>
      
      return response
    } catch (error) {
      console.error('Error creating poll:', error)
      throw error
    }
  }
  
  // Get poll details
  const getPoll = async (pollId: number): Promise<Poll | null> => {
    try {
      const response = await $apiFetch(`/api/polls/${pollId}`) as ApiResponse<Poll>
      if (response.success && response.data) {
        return response.data
      }
      return null
    } catch (error) {
      console.error('Error fetching poll:', error)
      return null
    }
  }
  
  // Vote on a poll
  const votePoll = async (pollId: number, optionId: number) => {
    try {
      const response = await $apiFetch('/api/polls/vote', {
        method: 'POST',
        body: {
          poll_id: pollId,
          option_id: optionId,
        },
      }) as ApiResponse
      
      return response
    } catch (error) {
      console.error('Error voting on poll:', error)
      throw error
    }
  }
  
  // Update a poll
  const updatePoll = async (pollId: number, options: UpdatePollOptions) => {
    try {
      const formData = new FormData()
      formData.append('_method', 'PUT')
      
      if (options.question) {
        formData.append('question', options.question)
      }
      
      if (options.duration !== undefined) {
        formData.append('duration', String(options.duration))
      }
      
      if (options.is_multiple !== undefined) {
        formData.append('is_multiple', options.is_multiple ? '1' : '0')
      }
      
      if (options.options) {
        options.options.forEach((option, index) => {
          formData.append(`options[${index}]`, option)
        })
      }
      
      const response = await $apiFetch(`/api/polls/${pollId}`, {
        method: 'POST', // Using POST with _method override for FormData
        body: formData,
      }) as ApiResponse<Poll>
      
      return response
    } catch (error) {
      console.error('Error updating poll:', error)
      throw error
    }
  }
  
  // Close a poll
  const closePoll = async (pollId: number) => {
    try {
      const response = await $apiFetch(`/api/polls/${pollId}/close`, {
        method: 'POST',
      }) as ApiResponse<Poll>
      
      return response
    } catch (error) {
      console.error('Error closing poll:', error)
      throw error
    }
  }
  
  // Delete a poll
  const deletePoll = async (pollId: number) => {
    try {
      const response = await $apiFetch(`/api/polls/${pollId}`, {
        method: 'DELETE',
      }) as ApiResponse
      
      return response
    } catch (error) {
      console.error('Error deleting poll:', error)
      throw error
    }
  }
  
  // Get poll results
  const getPollResults = async (pollId: number) => {
    try {
      const response = await $apiFetch(`/api/polls/${pollId}/results`) as ApiResponse<Poll>
      if (response.success && response.data) {
        return response.data
      }
      return null
    } catch (error) {
      console.error('Error fetching poll results:', error)
      return null
    }
  }
  
  // Calculate time remaining
  const calculateTimeRemaining = (endsAt: string): string => {
    const now = new Date()
    const endsAtDate = new Date(endsAt)
    const diffMs = endsAtDate.getTime() - now.getTime()
    
    if (diffMs <= 0) {
      return 'สิ้นสุดแล้ว'
    }
    
    const diffHours = Math.floor(diffMs / (1000 * 60 * 60))
    const diffDays = Math.floor(diffHours / 24)
    
    if (diffDays > 0) {
      return `${diffDays} วัน`
    }
    
    if (diffHours > 0) {
      return `${diffHours} ชั่วโมง`
    }
    
    const diffMinutes = Math.floor(diffMs / (1000 * 60))
    return `${diffMinutes} นาที`
  }
  
  // Calculate percentage for each option
  const calculatePercentages = (options: PollOption[]): PollOption[] => {
    const totalVotes = options.reduce((sum, opt) => sum + opt.votes, 0)
    
    return options.map(option => ({
      ...option,
      percentage: totalVotes > 0 ? Math.round((option.votes / totalVotes) * 100) : 0,
    }))
  }
  
  // Like a poll
  const likePoll = async (pollId: number) => {
    try {
      const response = await $apiFetch(`/api/polls/${pollId}/like`, {
        method: 'POST',
      }) as ApiResponse
      return response
    } catch (error) {
      console.error('Error liking poll:', error)
      throw error
    }
  }

  // Dislike a poll
  const dislikePoll = async (pollId: number) => {
    try {
      const response = await $apiFetch(`/api/polls/${pollId}/dislike`, {
        method: 'POST',
      }) as ApiResponse
      return response
    } catch (error) {
      console.error('Error disliking poll:', error)
      throw error
    }
  }

  // Comment on a poll
  const commentOnPoll = async (pollId: number, content: string) => {
    try {
      const response = await $apiFetch(`/api/polls/${pollId}/comment`, {
        method: 'POST',
        body: { content },
      }) as ApiResponse<PollComment>
      return response
    } catch (error) {
      console.error('Error commenting on poll:', error)
      throw error
    }
  }

  // Delete a poll comment
  const deletePollComment = async (commentId: number) => {
    try {
      const response = await $apiFetch(`/api/poll_comments/${commentId}`, {
        method: 'DELETE',
      }) as ApiResponse
      return response
    } catch (error) {
      console.error('Error deleting poll comment:', error)
      throw error
    }
  }
  
  return {
    createPoll,
    getPoll,
    votePoll,
    updatePoll,
    closePoll,
    deletePoll,
    getPollResults,
    calculateTimeRemaining,
    calculatePercentages,
    likePoll,
    dislikePoll,
    commentOnPoll,
    deletePollComment,
  }
}
