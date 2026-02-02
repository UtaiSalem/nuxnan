/**
 * Academy Member Service
 * Provides API methods for managing academy members
 */

// Types defined locally to avoid module resolution issues
interface AcademyRole {
  id: number
  name: string
  display_name_th: string
  display_name_en?: string
  color: string
  icon?: string
  permissions: string[]
}

interface AcademyMember {
  id: number
  academy_id: number
  user_id?: number | null
  student_id?: number | null
  member_code?: string
  role?: string
  status: number
  member_name: string
  member_avatar: string
  academy_role?: AcademyRole | null
}

interface MemberFilters {
  search?: string
  status?: number | null
  role?: string | null
  academy_role_id?: number | null
  sort_by?: string
  sort_order?: 'asc' | 'desc'
  page?: number
  per_page?: number
}

interface MemberStats {
  total: number
  approved: number
  pending: number
  invited: number
  rejected: number
  suspended: number
}

interface Pagination {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export function useAcademyMemberService() {
  const { $axios } = useNuxtApp() as { $axios: any }

  /**
   * Get members with search, filter, and pagination
   */
  async function searchMembers(academyId: number, filters: MemberFilters = {}) {
    const params: Record<string, any> = {}
    
    if (filters.search) params.search = filters.search
    if (filters.status !== undefined && filters.status !== null) params.status = filters.status
    if (filters.role) params.role = filters.role
    if (filters.academy_role_id) params.academy_role_id = filters.academy_role_id
    if (filters.sort_by) params.sort_by = filters.sort_by
    if (filters.sort_order) params.sort_order = filters.sort_order
    if (filters.page) params.page = filters.page
    if (filters.per_page) params.per_page = filters.per_page

    const response = await $axios.get(`/academies/${academyId}/members/search`, { params })
    return {
      members: response.data.members as AcademyMember[],
      pagination: response.data.pagination as Pagination,
    }
  }

  /**
   * Get all members (simple list without pagination)
   */
  async function getMembers(academyId: number): Promise<AcademyMember[]> {
    const response = await $axios.get(`/academies/${academyId}/members`)
    return response.data.members
  }

  /**
   * Get member statistics
   */
  async function getMemberStats(academyId: number): Promise<{ stats: MemberStats; role_distribution: Record<string, number> }> {
    const response = await $axios.get(`/academies/${academyId}/members/stats`)
    return {
      stats: response.data.stats,
      role_distribution: response.data.role_distribution,
    }
  }

  /**
   * Get pending join requests
   */
  async function getPendingRequests(academyId: number): Promise<AcademyMember[]> {
    const response = await $axios.get(`/academies/${academyId}/pending-requests`)
    return response.data.pendingRequests
  }

  /**
   * Request to join an academy
   */
  async function requestMembership(academyId: number): Promise<{ success: boolean; memberStatus: number; totalStudents: number; message?: string }> {
    const response = await $axios.post(`/academies/${academyId}/members`)
    return response.data
  }

  /**
   * Leave an academy
   */
  async function leaveMembership(academyId: number): Promise<{ success: boolean; message: string }> {
    const response = await $axios.post(`/academies/${academyId}/unmembers`)
    return response.data
  }

  /**
   * Accept a membership request (admin)
   */
  async function acceptMember(academyId: number, memberId: number): Promise<{ success: boolean; memberStatus: number; totalStudents: number }> {
    const response = await $axios.post(`/academies/${academyId}/members/${memberId}/accept`)
    return response.data
  }

  /**
   * Reject a membership request (admin)
   */
  async function rejectMember(academyId: number, memberId: number): Promise<{ success: boolean; memberStatus: number; totalStudents: number }> {
    const response = await $axios.post(`/academies/${academyId}/members/${memberId}/reject`)
    return response.data
  }

  /**
   * Invite a user to join the academy
   */
  async function inviteMember(academyId: number, userId: number): Promise<{ success: boolean; message: string; invitation?: AcademyMember }> {
    const response = await $axios.post(`/academies/${academyId}/invite`, { user_id: userId })
    return response.data
  }

  /**
   * Accept an invitation
   */
  async function acceptInvitation(academyId: number): Promise<{ success: boolean; message: string; memberStatus: number; totalStudents: number }> {
    const response = await $axios.post(`/academies/${academyId}/accept-invite`)
    return response.data
  }

  /**
   * Decline an invitation
   */
  async function declineInvitation(academyId: number): Promise<{ success: boolean; message: string }> {
    const response = await $axios.post(`/academies/${academyId}/decline-invite`)
    return response.data
  }

  /**
   * Get my pending invitations
   */
  async function getMyInvitations(): Promise<AcademyMember[]> {
    const response = await $axios.get(`/academies/my-invitations`)
    return response.data.invitations
  }

  /**
   * Remove a member from the academy (admin)
   */
  async function removeMember(academyId: number, memberId: number): Promise<{ success: boolean; message: string; totalStudents: number }> {
    const response = await $axios.delete(`/academies/${academyId}/members/${memberId}`)
    return response.data
  }

  /**
   * Suspend a member (admin)
   */
  async function suspendMember(academyId: number, memberId: number, reason?: string): Promise<{ success: boolean; message: string; member: AcademyMember }> {
    const response = await $axios.post(`/academies/${academyId}/members/${memberId}/suspend`, { reason })
    return response.data
  }

  /**
   * Unsuspend a member (admin)
   */
  async function unsuspendMember(academyId: number, memberId: number): Promise<{ success: boolean; message: string; member: AcademyMember; totalStudents: number }> {
    const response = await $axios.post(`/academies/${academyId}/members/${memberId}/unsuspend`)
    return response.data
  }

  /**
   * Update member details (admin)
   */
  async function updateMember(academyId: number, memberId: number, data: Partial<AcademyMember>): Promise<{ success: boolean; message: string; member: AcademyMember }> {
    const response = await $axios.patch(`/academies/${academyId}/members/${memberId}`, data)
    return response.data
  }

  /**
   * Assign role to a member
   */
  async function assignRole(academyId: number, memberId: number, roleId: number): Promise<{ success: boolean; message: string; member: AcademyMember }> {
    const response = await $axios.post(`/academies/${academyId}/members/${memberId}/role`, { role_id: roleId })
    return response.data
  }

  /**
   * Bulk assign role to multiple members
   */
  async function bulkAssignRole(academyId: number, memberIds: number[], roleId: number): Promise<{ success: boolean; message: string; updated_count: number }> {
    const response = await $axios.post(`/academies/${academyId}/members/bulk-role`, { member_ids: memberIds, role_id: roleId })
    return response.data
  }

  /**
   * Get available roles for the academy
   */
  async function getAvailableRoles(academyId: number): Promise<AcademyRole[]> {
    const response = await $axios.get(`/academies/${academyId}/roles/available`)
    return response.data.roles
  }

  /**
   * Get current user's role in the academy
   */
  async function getMyRole(academyId: number): Promise<{
    role: AcademyRole | null;
    permissions: string[];
    is_owner: boolean;
    is_admin: boolean;
    is_member?: boolean;
    member_id?: number;
  }> {
    const response = await $axios.get(`/academies/${academyId}/my-role`)
    return response.data
  }

  return {
    // Search & List
    searchMembers,
    getMembers,
    getMemberStats,
    getPendingRequests,
    
    // Membership
    requestMembership,
    leaveMembership,
    acceptMember,
    rejectMember,
    
    // Invitations
    inviteMember,
    acceptInvitation,
    declineInvitation,
    getMyInvitations,
    
    // Member Management
    removeMember,
    suspendMember,
    unsuspendMember,
    updateMember,
    
    // Roles
    assignRole,
    bulkAssignRole,
    getAvailableRoles,
    getMyRole,
  }
}
