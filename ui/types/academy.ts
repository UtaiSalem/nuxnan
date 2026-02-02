/**
 * Academy Member System Types
 */

// Academy Member Status Constants
export const MEMBER_STATUS = {
  PENDING: 1,
  APPROVED: 2,
  REJECTED: 3,
  INVITED: 4,
  SUSPENDED: 5,
} as const

export type MemberStatus = typeof MEMBER_STATUS[keyof typeof MEMBER_STATUS]

// User Interface
export interface User {
  id: number
  name: string
  email: string
  profile_photo_url?: string
  reference_code?: string
}

// Student Interface
export interface Student {
  id: number
  student_id: string
  full_name_th?: string
  full_name_en?: string
  first_name_th?: string
  last_name_th?: string
  first_name_en?: string
  last_name_en?: string
  nickname?: string
  profile_image?: string
  class_level?: string
  class_section?: string
  current_classroom?: string
  date_of_birth?: string
  gender?: number
}

// Academy Role Interface
export interface AcademyRole {
  id: number
  academy_id?: number | null
  name: string
  display_name_th: string
  display_name_en?: string
  description?: string
  permissions: string[]
  color: string
  icon?: string
  is_system: boolean
  is_active: boolean
  sort_order: number
}

// Academy Member Interface
export interface AcademyMember {
  id: number
  academy_id: number
  user_id?: number | null
  student_id?: number | null
  member_code?: string | null
  role?: string | null
  status: MemberStatus
  status_text?: string
  member_name: string
  member_avatar: string
  role_display_name?: string
  enrollment_date?: string | null
  graduation_date?: string | null
  graduation_reason?: 'completed' | 'expelled' | 'dropped_out' | 'transferred' | 'other' | null
  note_comment?: string | null
  additional_info?: string | null
  invited_by?: number | null
  
  // Academy Role
  academy_role_id?: number | null
  academy_role?: AcademyRole | null
  
  // Role Checks
  is_admin?: boolean
  is_teacher?: boolean
  is_student?: boolean
  is_parent?: boolean
  
  // Relations
  user?: User | null
  student?: Student | null
  inviter?: User | null
  academy?: Academy | null
  
  // Timestamps
  created_at?: string
  updated_at?: string
}

// Academy Interface
export interface Academy {
  id: number
  name: string
  slogan?: string
  logo?: string
  cover?: string
  type?: string
  user_id: number
  total_students: number
  membership_fees_points?: number
  memberStatus?: MemberStatus | null
}

// Academy Settings Interface
export interface AcademySetting {
  id: number
  academy_id: number
  auto_accept_members: boolean
  allow_public_courses: boolean
  require_approval_for_enrollment: boolean
}

// Member Statistics Interface
export interface MemberStats {
  total: number
  approved: number
  pending: number
  invited: number
  rejected: number
  suspended: number
}

// Pagination Interface
export interface Pagination {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

// API Response Interfaces
export interface ApiResponse<T> {
  success: boolean
  message?: string
  data?: T
}

export interface MemberListResponse {
  success: boolean
  members: AcademyMember[]
  pagination: Pagination
}

export interface MemberStatsResponse {
  success: boolean
  stats: MemberStats
  role_distribution: Record<string, number>
}

export interface RoleListResponse {
  success: boolean
  roles: AcademyRole[]
}

// Permission Groups
export interface PermissionGroup {
  group: string
  group_th: string
  permissions: Permission[]
}

export interface Permission {
  key: string
  name: string
  name_th: string
  description?: string
}

// Academy Role Permission Categories
export const PERMISSION_CATEGORIES = {
  ACADEMY: 'academy',
  MEMBERS: 'members',
  COURSES: 'courses',
  STUDENTS: 'students',
  TEACHERS: 'teachers',
  FINANCE: 'finance',
  REPORTS: 'reports',
  ANNOUNCEMENTS: 'announcements',
  HOME_VISITS: 'home_visits',
  ATTENDANCE: 'attendance',
  GRADEBOOK: 'gradebook',
} as const

// Default System Role Names
export const SYSTEM_ROLES = [
  'owner',
  'director', 
  'admin',
  'teacher',
  'staff',
  'finance_staff',
  'student',
  'parent',
] as const

export type SystemRoleName = typeof SYSTEM_ROLES[number]

// Member Filters Interface
export interface MemberFilters {
  search?: string
  status?: MemberStatus | null
  role?: string | null
  academy_role_id?: number | null
  sort_by?: string
  sort_order?: 'asc' | 'desc'
  page?: number
  per_page?: number
}
