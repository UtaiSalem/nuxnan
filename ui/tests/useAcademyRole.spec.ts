import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { effectScope, nextTick, ref } from 'vue'
import type { EffectScope, Ref } from 'vue'
import { clearAcademyRoleCache, useAcademyRole } from '~/composables/useAcademyRole'

const { authMock, getMock } = vi.hoisted(() => ({
  authMock: { user: { id: 1 } },
  getMock: vi.fn(),
}))

vi.mock('~/stores/auth', () => ({ useAuthStore: () => authMock }))

const roleResponse = () => ({
  success: true,
  role: {
    id: 3,
    academy_id: 5,
    name: 'staff',
    display_name_th: 'เจ้าหน้าที่',
    display_name_en: 'Staff',
    description: null,
    permissions: ['members.view'],
    is_system: true,
    is_active: true,
    sort_order: 1,
    color: 'blue',
    icon: 'fluent:person-24-regular',
  },
  permissions: ['members.view'],
  is_owner: false,
  is_admin: false,
  is_member: true,
  member_id: 10,
})

const scopes: EffectScope[] = []
const createRole = (academyId: Ref<number | null> = ref(5)) => {
  const scope = effectScope()
  scopes.push(scope)
  return scope.run(() => useAcademyRole(academyId))!
}

beforeEach(() => {
  clearAcademyRoleCache()
  getMock.mockClear()
  getMock.mockResolvedValue(roleResponse())
  authMock.user.id = 1
  vi.stubGlobal('useApi', () => ({ get: getMock }))
})

afterEach(() => {
  scopes.splice(0).forEach(scope => scope.stop())
  vi.useRealTimers()
  vi.unstubAllGlobals()
  vi.restoreAllMocks()
})

describe('useAcademyRole', () => {
  it('shares an in-flight request between instances', async () => {
    const first = createRole()
    const second = createRole()

    await Promise.all([first.fetchMyRole(), second.fetchMyRole()])

    expect(getMock).toHaveBeenCalledTimes(1)
    expect(getMock).toHaveBeenCalledWith('/api/academies/5/my-role')
    expect(first.can('members.view')).toBe(true)
    expect(second.can('members.view')).toBe(true)
    expect(first.isLoading.value).toBe(false)
    expect(second.isLoading.value).toBe(false)
  })

  it('deduplicates the academy watcher and an immediate explicit fetch', async () => {
    const academyId = ref<number | null>(null)
    const role = createRole(academyId)
    let resolveRequest!: (value: ReturnType<typeof roleResponse>) => void
    getMock.mockReturnValueOnce(new Promise(resolve => { resolveRequest = resolve }))

    academyId.value = 5
    const request = role.fetchMyRole()
    await nextTick()

    expect(getMock).toHaveBeenCalledTimes(1)
    expect(role.isLoading.value).toBe(true)
    resolveRequest(roleResponse())
    await request
    await nextTick()

    expect(getMock).toHaveBeenCalledTimes(1)
    expect(role.can('members.view')).toBe(true)
  })

  it('keeps fetching automatically when only the watcher is used', async () => {
    const academyId = ref<number | null>(null)
    const role = createRole(academyId)

    academyId.value = 5
    await nextTick()
    await nextTick()

    expect(getMock).toHaveBeenCalledTimes(1)
    expect(role.can('members.view')).toBe(true)
  })

  it('reuses cached roles within 60 seconds and fetches after the TTL', async () => {
    vi.useFakeTimers()
    const start = new Date('2026-11-28T00:00:00Z')
    vi.setSystemTime(start)
    const first = createRole()
    await first.fetchMyRole()

    vi.setSystemTime(start.getTime() + 59_999)
    await first.fetchMyRole()
    const second = createRole()
    await second.fetchMyRole()

    expect(getMock).toHaveBeenCalledTimes(1)
    expect(second.can('members.view')).toBe(true)

    vi.setSystemTime(start.getTime() + 60_001)
    await first.fetchMyRole()
    expect(getMock).toHaveBeenCalledTimes(2)
  })

  it('refreshes roles even while a cached entry is fresh', async () => {
    const role = createRole()
    await role.fetchMyRole()
    getMock.mockResolvedValueOnce({ ...roleResponse(), permissions: ['courses.view'] })

    await role.refreshRole()

    expect(getMock).toHaveBeenCalledTimes(2)
    expect(role.can('members.view')).toBe(false)
    expect(role.can('courses.view')).toBe(true)
    const second = createRole()
    await second.fetchMyRole()
    expect(getMock).toHaveBeenCalledTimes(2)
    expect(second.can('courses.view')).toBe(true)
  })

  it('separates cached roles by the current user ID', async () => {
    const role = createRole()
    await role.fetchMyRole()
    authMock.user.id = 2
    getMock.mockResolvedValueOnce({ ...roleResponse(), permissions: [] })

    await role.fetchMyRole()

    expect(getMock).toHaveBeenCalledTimes(2)
    expect(role.can('members.view')).toBe(false)
  })

  it('separates in-flight requests by the current user ID', async () => {
    const first = createRole()
    const firstRequest = first.fetchMyRole()
    authMock.user.id = 2
    getMock.mockResolvedValueOnce({ ...roleResponse(), permissions: [] })
    const second = createRole()

    await Promise.all([firstRequest, second.fetchMyRole()])

    expect(getMock).toHaveBeenCalledTimes(2)
    expect(first.can('members.view')).toBe(true)
    expect(second.can('members.view')).toBe(false)
  })

  it('separates cached roles by academy ID', async () => {
    const first = createRole()
    await first.fetchMyRole()
    const second = createRole(ref(6))
    await second.fetchMyRole()

    expect(getMock).toHaveBeenCalledTimes(2)
    expect(getMock).toHaveBeenLastCalledWith('/api/academies/6/my-role')
  })

  it('reports a failed request and retries without caching the failure', async () => {
    vi.spyOn(console, 'error').mockImplementation(() => {})
    getMock.mockRejectedValueOnce(new Error('Network error'))
    const role = createRole()

    await role.fetchMyRole()

    expect(role.error.value).toBe('ไม่สามารถโหลดข้อมูลบทบาทได้')
    expect(role.isLoading.value).toBe(false)
    await role.fetchMyRole()

    expect(getMock).toHaveBeenCalledTimes(2)
    expect(role.error.value).toBeNull()
    expect(role.can('members.view')).toBe(true)
  })

  it('reports API error messages to every instance awaiting a failed request', async () => {
    vi.spyOn(console, 'error').mockImplementation(() => {})
    getMock.mockRejectedValueOnce({ data: { message: 'ไม่มีสิทธิ์' } })
    const first = createRole()
    const second = createRole()

    await Promise.all([first.fetchMyRole(), second.fetchMyRole()])

    expect(getMock).toHaveBeenCalledTimes(1)
    for (const role of [first, second]) {
      expect(role.error.value).toBe('ไม่มีสิทธิ์')
      expect(role.isLoading.value).toBe(false)
    }
    await second.fetchMyRole()
    expect(getMock).toHaveBeenCalledTimes(2)
  })

  it('does not cache unsuccessful API responses', async () => {
    getMock.mockResolvedValueOnce({ success: false })
    const role = createRole()

    await role.fetchMyRole()
    expect(role.can('members.view')).toBe(false)
    await role.fetchMyRole()

    expect(getMock).toHaveBeenCalledTimes(2)
    expect(role.can('members.view')).toBe(true)
  })

  it('keeps state and permission arrays separate for cached and in-flight consumers', async () => {
    const first = createRole()
    const second = createRole()
    await Promise.all([first.fetchMyRole(), second.fetchMyRole()])
    const cached = createRole()
    await cached.fetchMyRole()

    expect(first.roleState.value).not.toBe(second.roleState.value)
    expect(first.permissions.value).not.toBe(second.permissions.value)
    expect(cached.roleState.value).not.toBe(first.roleState.value)
    expect(cached.permissions.value).not.toBe(first.permissions.value)
    first.permissions.value.push('courses.view')
    expect(second.can('courses.view')).toBe(false)
    expect(cached.can('courses.view')).toBe(false)
    await first.fetchMyRole()
    expect(first.can('courses.view')).toBe(false)
    expect(getMock).toHaveBeenCalledTimes(1)
  })

  it('clears cached roles on demand', async () => {
    const role = createRole()
    await role.fetchMyRole()
    clearAcademyRoleCache()
    await role.fetchMyRole()

    expect(getMock).toHaveBeenCalledTimes(2)
  })

  it('does not request a role without an academy ID', async () => {
    const role = createRole(ref(null))

    await role.fetchMyRole()
    await role.refreshRole()

    expect(getMock).not.toHaveBeenCalled()
    expect(role.isLoading.value).toBe(false)
    expect(role.error.value).toBeNull()
  })

  it('preserves every existing return name and adds refreshRole', () => {
    expect(Object.keys(createRole()).sort()).toEqual([
      'roleState', 'myRole', 'permissions', 'isLoading', 'error',
      'isOwner', 'isAdmin', 'isMember', 'isTeacher', 'isStudent', 'isParent',
      'isStaff', 'isGuest', 'roleName', 'roleDisplayName', 'roleColor', 'roleIcon',
      'can', 'canAny', 'canAll', 'canAccessAdmin', 'canManageMembers',
      'canManageCourses', 'canViewFinance', 'canViewReports', 'fetchMyRole',
      'getDashboardRoute', 'resetRole', 'refreshRole',
    ].sort())
  })
})
