interface GroupUpdatePayload {
  name?: string
  description?: string | null
  type?: string
  parent_id?: number | null
}

interface MemberSearchParams {
  search?: string
  status?: number
  per_page?: number
}

const postableCache = new Map<number | string, any[]>()

export const useAcademyGroups = () => {
  const api = useApi()

  const getGroup = <T = any>(groupId: number) =>
    api.get<T>(`/api/academies/groups/${groupId}`)

  const updateGroup = <T = any>(groupId: number, payload: GroupUpdatePayload) =>
    api.patch<T>(`/api/academies/groups/${groupId}`, payload)

  const deleteGroup = <T = any>(groupId: number) =>
    api.delete<T>(`/api/academies/groups/${groupId}`)

  const listMembers = <T = any>(groupId: number) =>
    api.get<T>(`/api/academies/groups/${groupId}/members`)

  const addMember = <T = any>(groupId: number, userId: number, role: 'student' | 'teacher' | 'admin' = 'student') =>
    api.post<T>(`/api/academies/groups/${groupId}/members`, { user_id: userId, role })

  const removeMember = <T = any>(groupId: number, userId: number) =>
    api.delete<T>(`/api/academies/groups/${groupId}/members`, { body: { user_id: userId } })

  const updateMemberRole = <T = any>(groupId: number, userId: number, role: 'student' | 'teacher' | 'admin') =>
    api.patch<T>(`/api/academies/groups/${groupId}/members/role`, { user_id: userId, role })

  const listAdmins = <T = any>(groupId: number) =>
    api.get<T>(`/api/academies/groups/${groupId}/admins`)

  const addAdmin = <T = any>(groupId: number, userId: number, role: 'leader' | 'co_leader' | 'advisor' = 'leader') =>
    api.post<T>(`/api/academies/groups/${groupId}/admins`, { user_id: userId, role })

  const removeAdmin = <T = any>(groupId: number, userId: number) =>
    api.delete<T>(`/api/academies/groups/${groupId}/admins`, { body: { user_id: userId } })

  const updateAdminRole = <T = any>(groupId: number, userId: number, role: 'leader' | 'co_leader' | 'advisor') =>
    api.patch<T>(`/api/academies/groups/${groupId}/admins/role`, { user_id: userId, role })

  const listPermissions = <T = any>(academyId: number | string, groupId: number) =>
    api.get<T>(`/api/academies/${academyId}/departments/${groupId}/permissions`)

  const updatePermissions = <T = any>(academyId: number | string, groupId: number, permissionKeys: string[]) =>
    api.put<T>(`/api/academies/${academyId}/departments/${groupId}/permissions`, {
      permission_keys: permissionKeys,
    })

  const searchAcademyMembers = <T = any>(academyId: number | string, params: MemberSearchParams = {}) =>
    api.get<T>(`/api/academies/${academyId}/members/search`, { params })

  const getPermissionOptions = <T = any>() =>
    api.get<T>('/api/academy-group-permissions')

  const muteGroup = <T = any>(groupId: number) =>
    api.post<T>(`/api/academies/groups/${groupId}/mute`, {})

  const unmuteGroup = <T = any>(groupId: number) =>
    api.delete<T>(`/api/academies/groups/${groupId}/mute`)

  const listGroupPosts = <T = any>(groupId: number, params?: { per_page?: number; page?: number }) =>
    api.get<T>(`/api/academies/groups/${groupId}/posts`, { params })

  const getGroupStats = <T = any>(groupId: number) =>
    api.get<T>(`/api/academies/groups/${groupId}/stats`)

  const getPostableGroups = async <T = any>(
    academyId: number | string,
    options?: { forceRefresh?: boolean },
  ): Promise<T[]> => {
    if (!options?.forceRefresh && postableCache.has(academyId)) {
      return postableCache.get(academyId) as T[]
    }
    try {
      const res: any = await api.get(`/api/academies/${academyId}/postable-groups`)
      const data = (res?.data ?? []) as T[]
      postableCache.set(academyId, data)
      return data
    } catch {
      return []
    }
  }

  const invalidatePostableCache = (academyId?: number | string) => {
    if (academyId != null) postableCache.delete(academyId)
    else postableCache.clear()
  }

  return {
    getGroup,
    updateGroup,
    deleteGroup,
    listMembers,
    addMember,
    removeMember,
    updateMemberRole,
    listAdmins,
    addAdmin,
    removeAdmin,
    updateAdminRole,
    listPermissions,
    updatePermissions,
    searchAcademyMembers,
    getPermissionOptions,
    muteGroup,
    unmuteGroup,
    listGroupPosts,
    getGroupStats,
    getPostableGroups,
    invalidatePostableCache,
  }
}

