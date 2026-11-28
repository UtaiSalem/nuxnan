/**
 * Academy Role Composable
 * จัดการบทบาทและสิทธิ์การเข้าถึงในโรงเรียน
 */

import { ref, computed, readonly, watch } from 'vue'
import type { Ref, ComputedRef } from 'vue'
import { useAuthStore } from '~/stores/auth'

// Types
export interface AcademyRole {
  id: number
  academy_id: number | null
  name: string
  display_name_th: string
  display_name_en: string | null
  description: string | null
  permissions: string[]
  is_system: boolean
  is_active: boolean
  sort_order: number
  color: string
  icon: string
}

export interface AcademyRoleState {
  role: AcademyRole | null
  permissions: string[]
  isOwner: boolean
  isAdmin: boolean
  isMember: boolean
  memberId: number | null
}

const ROLE_CACHE_TTL_MS = 60_000

interface RoleCacheEntry {
  state: AcademyRoleState
  fetchedAt: number
}

const roleCache = new Map<string, RoleCacheEntry>()
const inflight = new Map<string, Promise<AcademyRoleState | null>>()

/** ล้าง cache ทั้งหมด (ใช้ในเทสต์ หรือเมื่อสิทธิ์ของผู้ใช้ปัจจุบันเปลี่ยน) */
export const clearAcademyRoleCache = () => {
  roleCache.clear()
  inflight.clear()
}

// Role colors for UI
export const ROLE_COLORS: Record<string, string> = {
  purple: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
  indigo: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
  blue: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
  green: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
  cyan: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200',
  amber: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
  emerald: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200',
  rose: 'bg-rose-100 text-rose-800 dark:bg-rose-900 dark:text-rose-200',
  gray: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
}

export const useAcademyRole = (academyId: Ref<number | null>) => {
  const api = useApi()
  
  // State
  const roleState = ref<AcademyRoleState>({
    role: null,
    permissions: [],
    isOwner: false,
    isAdmin: false,
    isMember: false,
    memberId: null,
  })
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  // Computed
  const myRole = computed(() => roleState.value.role)
  const permissions = computed(() => roleState.value.permissions)
  const isOwner = computed(() => roleState.value.isOwner)
  const isAdmin = computed(() => roleState.value.isAdmin || roleState.value.isOwner)
  const isMember = computed(() => roleState.value.isMember)
  
  const isTeacher = computed(() => myRole.value?.name === 'teacher')
  const isStudent = computed(() => myRole.value?.name === 'student')
  const isParent = computed(() => myRole.value?.name === 'parent')
  const isStaff = computed(() => ['staff', 'finance_staff'].includes(myRole.value?.name || ''))
  const isGuest = computed(() => myRole.value?.name === 'guest' || !roleState.value.isMember)

  const roleName = computed(() => {
    if (!myRole.value) return 'guest'
    return myRole.value.name
  })

  const roleDisplayName = computed(() => {
    if (!myRole.value) return 'บุคคลทั่วไป'
    return myRole.value.display_name_th
  })

  const roleColor = computed(() => {
    if (!myRole.value?.color) return ROLE_COLORS.gray
    return ROLE_COLORS[myRole.value.color] || ROLE_COLORS.gray
  })

  const roleIcon = computed(() => {
    if (!myRole.value?.icon) return 'fluent:person-24-regular'
    return myRole.value.icon
  })

  // Methods
  
  /**
   * Fetch current user's role in the academy
   */
  const fetchMyRole = async (options: { force?: boolean } = {}) => {
    const id = academyId.value
    if (!id) return

    const userId = useAuthStore().user?.id
    const key = `${userId ?? 'anon'}:${id}`
    let request: Promise<AcademyRoleState | null> | undefined

    isLoading.value = true
    error.value = null

    try {
      const entry = roleCache.get(key)
      if (!options.force && entry && Date.now() - entry.fetchedAt < ROLE_CACHE_TTL_MS) {
        roleState.value = { ...entry.state, permissions: [...entry.state.permissions] }
        return
      }

      request = options.force ? undefined : inflight.get(key)
      if (!request) {
        request = (async (): Promise<AcademyRoleState | null> => {
          const response: any = await api.get(`/api/academies/${id}/my-role`)
          if (!response.success) return null

          const state: AcademyRoleState = {
            role: response.role,
            permissions: response.permissions || [],
            isOwner: response.is_owner || false,
            isAdmin: response.is_admin || false,
            isMember: response.is_member || false,
            memberId: response.member_id || null,
          }
          roleCache.set(key, { state, fetchedAt: Date.now() })
          return state
        })()
        inflight.set(key, request)
      }

      const state = await request
      if (state) {
        roleState.value = { ...state, permissions: [...state.permissions] }
      }
    } catch (err: any) {
      console.error('Failed to fetch role:', err)
      error.value = err?.data?.message || 'ไม่สามารถโหลดข้อมูลบทบาทได้'
    } finally {
      if (request && inflight.get(key) === request) {
        inflight.delete(key)
      }
      isLoading.value = false
    }
  }

  /**
   * Check if user has a specific permission
   */
  const can = (permission: string): boolean => {
    // Owner has all permissions
    if (roleState.value.isOwner) return true
    
    const perms = roleState.value.permissions
    
    // Wildcard check
    if (perms.includes('*')) return true
    
    // Direct permission check
    if (perms.includes(permission)) return true
    
    // Hierarchical check (e.g., 'courses.edit' grants 'courses.edit.own')
    for (const perm of perms) {
      if (permission.startsWith(perm + '.')) return true
    }
    
    return false
  }

  /**
   * Check if user has any of the given permissions
   */
  const canAny = (permissions: string[]): boolean => {
    return permissions.some(p => can(p))
  }

  /**
   * Check if user has all of the given permissions
   */
  const canAll = (permissions: string[]): boolean => {
    return permissions.every(p => can(p))
  }

  /**
   * Check if user can access admin features
   */
  const canAccessAdmin = computed(() => {
    return isAdmin.value || can('settings.view') || can('settings.manage')
  })

  /**
   * Check if user can manage members
   */
  const canManageMembers = computed(() => {
    return can('members.manage') || can('members.roles.manage')
  })

  /**
   * Check if user can manage courses
   */
  const canManageCourses = computed(() => {
    return can('courses.create') || can('courses.edit')
  })

  /**
   * Check if user can view finance
   */
  const canViewFinance = computed(() => {
    return can('finance.view') || can('finance.reports')
  })

  /**
   * Check if user can view reports
   */
  const canViewReports = computed(() => {
    return can('reports.view') || can('reports.export')
  })

  /**
   * Get the appropriate dashboard route based on role
   */
  const getDashboardRoute = (): string => {
    if (isAdmin.value) return 'admin'
    if (isTeacher.value) return 'teacher'
    if (isStudent.value) return 'student'
    if (isParent.value) return 'parent'
    if (isStaff.value) return 'staff'
    return 'guest'
  }

  /**
   * Reset role state
   */
  const resetRole = () => {
    roleState.value = {
      role: null,
      permissions: [],
      isOwner: false,
      isAdmin: false,
      isMember: false,
      memberId: null,
    }
  }

  // Watch for academy changes
  watch(academyId, (newId, oldId) => {
    if (newId !== oldId) {
      resetRole()
      if (newId) {
        fetchMyRole()
      }
    }
  })

  return {
    // State
    roleState: readonly(roleState),
    myRole,
    permissions,
    isLoading: readonly(isLoading),
    error: readonly(error),
    
    // Role checks
    isOwner,
    isAdmin,
    isMember,
    isTeacher,
    isStudent,
    isParent,
    isStaff,
    isGuest,
    
    // Display
    roleName,
    roleDisplayName,
    roleColor,
    roleIcon,
    
    // Permission checks
    can,
    canAny,
    canAll,
    canAccessAdmin,
    canManageMembers,
    canManageCourses,
    canViewFinance,
    canViewReports,
    
    // Actions
    fetchMyRole,
    refreshRole: () => fetchMyRole({ force: true }),
    getDashboardRoute,
    resetRole,
  }
}

// Export type for external use
export type UseAcademyRoleReturn = ReturnType<typeof useAcademyRole>
