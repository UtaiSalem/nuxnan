import { ref, computed } from 'vue'
import type { FriendshipStatus } from './useProfile'

// Types
export interface Friend {
  id: number
  user_id: number
  username: string
  name: string
  full_name: string
  avatar: string
  reference_code: string
  level: number
  is_online: boolean
  last_seen?: string
  mutual_friends_count?: number
  friendship_date?: string
}

export interface FriendRequest {
  id: number
  sender: Friend
  recipient: Friend
  status: 'pending' | 'accepted' | 'denied'
  created_at: string
}

// Re-export FriendshipStatus from useProfile for backward compatibility
export type { FriendshipStatus }

export const useFriends = () => {
  const api = useApi()
  const chatStore = useChatStore()
  
  // State
  const friends = ref<Friend[]>([])
  const friendRequests = ref<FriendRequest[]>([])
  const suggestions = ref<Friend[]>([])
  const isLoading = ref(false)
  const isLoadingRequests = ref(false)
  const isLoadingSuggestions = ref(false)
  const error = ref<string | null>(null)
  
  // Pagination
  const currentPage = ref(1)
  const lastPage = ref(1)
  const hasMore = computed(() => currentPage.value < lastPage.value)

  /**
   * Fetch user's friends list
   */
  const fetchFriends = async (userId?: string | number, page: number = 1): Promise<Friend[]> => {
    isLoading.value = true
    error.value = null
    
    try {
      const endpoint = userId 
        ? `/api/users/${userId}/friends?page=${page}`
        : `/api/friends?page=${page}`
      
      const response = await api.get(endpoint) as {
        success: boolean
        data?: Friend[]
        friends?: Friend[]
        meta?: { current_page: number; last_page: number }
      }
      
      if (response.success) {
        const friendsList = response.data || response.friends || []
        
        if (page === 1) {
          friends.value = friendsList
        } else {
          friends.value = [...friends.value, ...friendsList]
        }
        
        if (response.meta) {
          currentPage.value = response.meta.current_page
          lastPage.value = response.meta.last_page
        }
        
        // Update chat store for sidebar
        chatStore.setFriends(friends.value)
        
        return friendsList
      }
      return []
    } catch (err: any) {
      error.value = err.message || 'ไม่สามารถโหลดรายชื่อเพื่อนได้'
      console.error('Error fetching friends:', err)
      return []
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Fetch pending friend requests
   */
  const fetchPendingRequests = async (): Promise<FriendRequest[]> => {
    isLoadingRequests.value = true
    error.value = null
    
    try {
      const response = await api.get('/api/friends/pending') as {
        success: boolean
        requests?: FriendRequest[]
        data?: FriendRequest[]
      }
      
      if (response.success) {
        friendRequests.value = response.requests || response.data || []
        return friendRequests.value
      }
      return []
    } catch (err: any) {
      error.value = err.message || 'ไม่สามารถโหลดคำขอเป็นเพื่อนได้'
      console.error('Error fetching friend requests:', err)
      return []
    } finally {
      isLoadingRequests.value = false
    }
  }

  /**
   * Fetch friend suggestions
   */
  const fetchSuggestions = async (): Promise<Friend[]> => {
    isLoadingSuggestions.value = true
    error.value = null
    
    try {
      const response = await api.get('/api/friends/suggestions') as {
        success: boolean
        users?: Friend[]
        data?: Friend[]
      }
      
      if (response.success) {
        suggestions.value = response.users || response.data || []
        return suggestions.value
      }
      return []
    } catch (err: any) {
      error.value = err.message || 'ไม่สามารถโหลดคำแนะนำเพื่อนได้'
      console.error('Error fetching suggestions:', err)
      return []
    } finally {
      isLoadingSuggestions.value = false
    }
  }

  /**
   * Send friend request
   */
  const sendFriendRequest = async (userId: number): Promise<boolean> => {
    try {
      const response = await api.post(`/api/friends/${userId}`) as {
        success: boolean
        message?: string
      }
      
      if (response.success) {
        // Remove from suggestions if present
        suggestions.value = suggestions.value.filter(s => s.id !== userId)
        return true
      }
      return false
    } catch (err: any) {
      error.value = err.message || 'ไม่สามารถส่งคำขอเป็นเพื่อนได้'
      console.error('Error sending friend request:', err)
      return false
    }
  }

  /**
   * Accept friend request
   */
  const acceptFriendRequest = async (userId: number): Promise<boolean> => {
    try {
      const response = await api.patch(`/api/friends/${userId}/accept`) as {
        success: boolean
        message?: string
      }
      
      if (response.success) {
        // Remove from pending requests
        const request = friendRequests.value.find(r => r.sender.id === userId)
        friendRequests.value = friendRequests.value.filter(r => r.sender.id !== userId)
        
        // Add to friends list
        if (request) {
          friends.value.unshift(request.sender)
        }
        
        return true
      }
      return false
    } catch (err: any) {
      error.value = err.message || 'ไม่สามารถยอมรับคำขอได้'
      console.error('Error accepting friend request:', err)
      return false
    }
  }

  /**
   * Deny friend request
   */
  const denyFriendRequest = async (userId: number): Promise<boolean> => {
    try {
      const response = await api.post(`/api/friends/${userId}/deny`) as {
        success: boolean
        message?: string
      }
      
      if (response.success) {
        friendRequests.value = friendRequests.value.filter(r => r.sender.id !== userId)
        return true
      }
      return false
    } catch (err: any) {
      error.value = err.message || 'ไม่สามารถปฏิเสธคำขอได้'
      console.error('Error denying friend request:', err)
      return false
    }
  }

  /**
   * Cancel sent friend request
   */
  const cancelFriendRequest = async (userId: number): Promise<boolean> => {
    try {
      const response = await api.delete(`/api/friends/${userId}`) as {
        success: boolean
        message?: string
      }
      
      if (response.success) {
        return true
      }
      return false
    } catch (err: any) {
      error.value = err.message || 'ไม่สามารถยกเลิกคำขอได้'
      console.error('Error canceling friend request:', err)
      return false
    }
  }

  /**
   * Unfriend user
   */
  const unfriend = async (userId: number): Promise<boolean> => {
    try {
      const response = await api.post(`/api/friends/${userId}/unfriend`) as {
        success: boolean
        message?: string
      }
      
      if (response.success) {
        friends.value = friends.value.filter(f => f.id !== userId)
        return true
      }
      return false
    } catch (err: any) {
      error.value = err.message || 'ไม่สามารถลบเพื่อนได้'
      console.error('Error unfriending:', err)
      return false
    }
  }

  /**
   * Search friends
   */
  const searchFriends = async (query: string): Promise<Friend[]> => {
    if (!query.trim()) {
      return friends.value
    }
    
    try {
      const response = await api.get(`/api/friends/search?q=${encodeURIComponent(query)}`) as {
        success: boolean
        data?: Friend[]
        friends?: Friend[]
      }
      
      if (response.success) {
        return response.data || response.friends || []
      }
      return []
    } catch (err: any) {
      console.error('Error searching friends:', err)
      return friends.value.filter(f => 
        f.name.toLowerCase().includes(query.toLowerCase()) ||
        f.username?.toLowerCase().includes(query.toLowerCase())
      )
    }
  }

  /**
   * Get mutual friends count
   */
  const getMutualFriends = async (userId: number): Promise<{ count: number; friends: Friend[] }> => {
    try {
      const response = await api.get(`/api/users/${userId}/mutual-friends`) as {
        success: boolean
        count?: number
        friends?: Friend[]
        data?: { count: number; friends: Friend[] }
      }
      
      if (response.success) {
        return {
          count: response.count || response.data?.count || 0,
          friends: response.friends || response.data?.friends || []
        }
      }
      return { count: 0, friends: [] }
    } catch (err: any) {
      console.error('Error fetching mutual friends:', err)
      return { count: 0, friends: [] }
    }
  }

  /**
   * Load more friends
   */
  const loadMore = async (userId?: string | number): Promise<void> => {
    if (!hasMore.value || isLoading.value) return
    await fetchFriends(userId, currentPage.value + 1)
  }

  /**
   * Clear state
   */
  const clearState = () => {
    friends.value = []
    friendRequests.value = []
    suggestions.value = []
    currentPage.value = 1
    lastPage.value = 1
    error.value = null
  }

  return {
    // State
    friends,
    friendRequests,
    suggestions,
    isLoading,
    isLoadingRequests,
    isLoadingSuggestions,
    error,
    hasMore,
    
    // Methods
    fetchFriends,
    fetchPendingRequests,
    fetchSuggestions,
    sendFriendRequest,
    acceptFriendRequest,
    denyFriendRequest,
    cancelFriendRequest,
    unfriend,
    searchFriends,
    getMutualFriends,
    loadMore,
    clearState,
  }
}
