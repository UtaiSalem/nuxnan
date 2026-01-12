/**
 * Groups Management Composable
 * Provides functionality for fetching and managing groups
 */

export interface Group {
  id: number
  name: string
  slug: string
  description?: string
  cover_image?: string
  avatar?: string
  members_count: number
  posts_count?: number
  category: string
  privacy: 'public' | 'private' | 'secret'
  is_member: boolean
  is_admin: boolean
  is_moderator?: boolean
  joined_at?: string
  created_at?: string
  rules?: string[]
}

export interface GroupMember {
  id: number
  user_id: number
  name: string
  username?: string
  avatar?: string
  role: 'admin' | 'moderator' | 'member'
  joined_at: string
}

export interface GroupInvite {
  id: number
  group: Group
  invited_by: {
    id: number
    name: string
    avatar?: string
  }
  created_at: string
}

export function useGroups() {
  const api = useApi()

  /**
   * Fetch user's groups
   */
  const fetchUserGroups = async (userId?: string | number) => {
    try {
      const endpoint = userId 
        ? `/api/users/${userId}/groups`
        : `/api/profile/groups`
      
      const response = await api.get(endpoint) as {
        success: boolean
        data?: Group[]
        groups?: Group[]
      }
      
      if (response.success) {
        return response.data || response.groups || []
      }
      
      return []
    } catch (error) {
      console.error('Error fetching groups:', error)
      return []
    }
  }

  /**
   * Fetch all groups (for discovery)
   */
  const fetchAllGroups = async (page = 1, category?: string) => {
    try {
      const params: Record<string, any> = { page }
      if (category) params.category = category
      
      const response = await api.get('/api/groups', { params }) as {
        success: boolean
        data?: Group[]
        groups?: Group[]
        meta?: {
          current_page: number
          last_page: number
          total: number
        }
      }
      
      if (response.success) {
        return {
          groups: response.data || response.groups || [],
          meta: response.meta
        }
      }
      
      return { groups: [], meta: null }
    } catch (error) {
      console.error('Error fetching groups:', error)
      return { groups: [], meta: null }
    }
  }

  /**
   * Get group details
   */
  const getGroup = async (slugOrId: string | number) => {
    try {
      const response = await api.get(`/api/groups/${slugOrId}`) as {
        success: boolean
        group?: Group
        data?: Group
      }
      return response.success ? (response.group || response.data) : null
    } catch (error) {
      console.error('Error fetching group:', error)
      return null
    }
  }

  /**
   * Join a group
   */
  const joinGroup = async (groupId: number) => {
    try {
      const response = await api.post(`/api/groups/${groupId}/join`) as { 
        success: boolean
        message?: string
        status?: 'joined' | 'pending'
      }
      return response.success ? response : null
    } catch (error) {
      console.error('Error joining group:', error)
      return null
    }
  }

  /**
   * Leave a group
   */
  const leaveGroup = async (groupId: number) => {
    try {
      const response = await api.post(`/api/groups/${groupId}/leave`) as { success: boolean }
      return response.success
    } catch (error) {
      console.error('Error leaving group:', error)
      return false
    }
  }

  /**
   * Create a new group
   */
  const createGroup = async (data: {
    name: string
    description?: string
    category: string
    privacy: 'public' | 'private' | 'secret'
    cover_image?: File
    avatar?: File
    rules?: string[]
  }) => {
    try {
      const formData = new FormData()
      formData.append('name', data.name)
      formData.append('category', data.category)
      formData.append('privacy', data.privacy)
      
      if (data.description) formData.append('description', data.description)
      if (data.cover_image) formData.append('cover_image', data.cover_image)
      if (data.avatar) formData.append('avatar', data.avatar)
      if (data.rules) formData.append('rules', JSON.stringify(data.rules))

      const response = await api.post('/api/groups', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      }) as { success: boolean; group?: Group }

      return response.success ? response.group : null
    } catch (error) {
      console.error('Error creating group:', error)
      return null
    }
  }

  /**
   * Update group settings
   */
  const updateGroup = async (groupId: number, data: Partial<Group>) => {
    try {
      const response = await api.put(`/api/groups/${groupId}`, data) as {
        success: boolean
        group?: Group
      }
      return response.success ? response.group : null
    } catch (error) {
      console.error('Error updating group:', error)
      return null
    }
  }

  /**
   * Delete a group
   */
  const deleteGroup = async (groupId: number) => {
    try {
      const response = await api.delete(`/api/groups/${groupId}`) as { success: boolean }
      return response.success
    } catch (error) {
      console.error('Error deleting group:', error)
      return false
    }
  }

  /**
   * Fetch group members
   */
  const fetchGroupMembers = async (groupId: number, page = 1) => {
    try {
      const response = await api.get(`/api/groups/${groupId}/members`, {
        params: { page }
      }) as {
        success: boolean
        data?: GroupMember[]
        members?: GroupMember[]
        meta?: {
          current_page: number
          last_page: number
          total: number
        }
      }
      
      if (response.success) {
        return {
          members: response.data || response.members || [],
          meta: response.meta
        }
      }
      
      return { members: [], meta: null }
    } catch (error) {
      console.error('Error fetching group members:', error)
      return { members: [], meta: null }
    }
  }

  /**
   * Invite user to group
   */
  const inviteToGroup = async (groupId: number, userId: number) => {
    try {
      const response = await api.post(`/api/groups/${groupId}/invite`, { user_id: userId }) as { 
        success: boolean 
      }
      return response.success
    } catch (error) {
      console.error('Error inviting user:', error)
      return false
    }
  }

  /**
   * Accept group invitation
   */
  const acceptInvitation = async (invitationId: number) => {
    try {
      const response = await api.post(`/api/groups/invitations/${invitationId}/accept`) as { 
        success: boolean 
      }
      return response.success
    } catch (error) {
      console.error('Error accepting invitation:', error)
      return false
    }
  }

  /**
   * Decline group invitation
   */
  const declineInvitation = async (invitationId: number) => {
    try {
      const response = await api.post(`/api/groups/invitations/${invitationId}/decline`) as { 
        success: boolean 
      }
      return response.success
    } catch (error) {
      console.error('Error declining invitation:', error)
      return false
    }
  }

  /**
   * Fetch group invitations
   */
  const fetchInvitations = async () => {
    try {
      const response = await api.get('/api/groups/invitations') as {
        success: boolean
        data?: GroupInvite[]
        invitations?: GroupInvite[]
      }
      
      if (response.success) {
        return response.data || response.invitations || []
      }
      
      return []
    } catch (error) {
      console.error('Error fetching invitations:', error)
      return []
    }
  }

  /**
   * Search groups
   */
  const searchGroups = async (query: string, category?: string) => {
    try {
      const params: Record<string, string> = { q: query }
      if (category) params.category = category

      const response = await api.get('/api/groups/search', { params }) as {
        success: boolean
        data?: Group[]
        groups?: Group[]
      }
      
      if (response.success) {
        return response.data || response.groups || []
      }
      
      return []
    } catch (error) {
      console.error('Error searching groups:', error)
      return []
    }
  }

  return {
    fetchUserGroups,
    fetchAllGroups,
    getGroup,
    joinGroup,
    leaveGroup,
    createGroup,
    updateGroup,
    deleteGroup,
    fetchGroupMembers,
    inviteToGroup,
    acceptInvitation,
    declineInvitation,
    fetchInvitations,
    searchGroups
  }
}
