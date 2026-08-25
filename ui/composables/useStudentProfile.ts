/**
 * Composable for fetching and managing student profile data
 * Used in the student profile page to load student information
 */
import { ref, computed, type Ref } from 'vue'
import { useApi } from './useApi'

// ============================================================================
// Types
// ============================================================================

export interface StudentProfile {
  id: number
  /** Numeric academy id — required to build the sectional-edit endpoints */
  academy_id: number
  student_id: string
  citizen_id: string | null
  title_prefix_th: string | null
  first_name_th: string
  last_name_th: string
  middle_name_th: string | null
  title_prefix_en: string | null
  first_name_en: string | null
  last_name_en: string | null
  middle_name_en: string | null
  nickname: string | null
  date_of_birth: string | null
  age: number | null
  gender: number | null
  gender_text: string
  nationality: string | null
  religion: string | null
  profile_image: string | null
  blood_type: string | null
  status: string
  enrollment_date: string | null
  class_level: string | null
  class_section: string | null
}

export interface ClassroomInfo {
  id: number
  name: string | null
  grade_level: string | null
  section: string | null
  academic_year: string | null
  student_number: string | null
}

export interface AcademicInfo {
  id: number
  academic_year: string | null
  current_grade: string | null
  education_level: number | null
  current_class: string | null
  school_name: string | null
  study_status: string | null
  is_current: boolean
  enrollment_date: string | null
  graduation_date: string | null
}

export interface StudentAddress {
  id: number
  address_type: string
  house_number: string | null
  village_number: string | null
  village_name: string | null
  alley: string | null
  road: string | null
  subdistrict: string | null
  district: string | null
  province: string | null
  postal_code: string | null
  is_current: boolean
}

export interface StudentContact {
  id: number
  contact_type: string
  contact_value: string
  is_primary: boolean
}

export interface StudentGuardian {
  id: number
  guardian_type: string
  title_prefix: string | null
  first_name: string | null
  last_name: string | null
  relationship: string | null
  occupation: string | null
  is_primary_contact: boolean
  is_emergency_contact: boolean
  status: string | null
  citizen_id?: string | null
  monthly_income?: number | null
  workplace?: string | null
  link_id?: number | null
  appointed_by_role?: string | null
  verified_at?: string | null
  is_verified?: boolean
}

export interface StudentHealthInfo {
  height_cm: number | null
  weight_kg: number | null
  blood_type: string | null
  rh_factor: string | null
  allergies: string | null
  chronic_diseases: string | null
  medications: string | null
  last_checkup_date: string | null
}

export interface AcademyInfo {
  id: number
  name: string
  logo: string | null
}

export interface StudentCardInfo {
  exists: boolean
  id: number | null
  card_number: string | null
  issued_at: string | null
  expires_at: string | null
  photo_status: 'approved' | 'pending' | 'missing'
  preview_url: string | null
  match_strategy: string | null
}

export interface HomeVisitSummary {
  id: number
  visited_at: string | null
  status: string | null
  visitor_name: string | null
  observations?: string | null
}

export interface HomeVisitInfo {
  total_visits: number
  latest: HomeVisitSummary | null
  next_scheduled: { id: number; scheduled_at: string | null } | null
  recent: HomeVisitSummary[]
}

export interface SchoolActivityInfo {
  joined_at: string | null
  member_code: string | null
  last_active_at: string | null
}

export interface StudentProfileData {
  student: StudentProfile
  classroom: ClassroomInfo | null
  academic_info: AcademicInfo[]
  addresses: StudentAddress[]
  contacts: StudentContact[]
  guardians: StudentGuardian[]
  health_info: StudentHealthInfo | null
  access_level: string
  academy: AcademyInfo
  student_card: StudentCardInfo
  home_visit: HomeVisitInfo
  school_activity: SchoolActivityInfo
}

// Access level labels
export const ACCESS_LEVEL_LABELS: Record<string, string> = {
  self: 'นักเรียน (ตัวเอง)',
  parent: 'ผู้ปกครอง',
  homeroom: 'ครูประจำชั้น',
  teacher: 'ครูผู้สอน',
  admin: 'ผู้บริหาร',
}

// ============================================================================
// Composable
// ============================================================================

export const useStudentProfile = (academyName: Ref<string> | string, studentId: Ref<number | string> | number | string) => {
  const api = useApi()

  // State
  const profileData = ref<StudentProfileData | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  // Computed
  const student = computed(() => profileData.value?.student ?? null)
  const classroom = computed(() => profileData.value?.classroom ?? null)
  const academicInfo = computed(() => profileData.value?.academic_info ?? [])
  const addresses = computed(() => profileData.value?.addresses ?? [])
  const contacts = computed(() => profileData.value?.contacts ?? [])
  const guardians = computed(() => profileData.value?.guardians ?? [])
  const healthInfo = computed(() => profileData.value?.health_info ?? null)
  const accessLevel = computed(() => profileData.value?.access_level ?? null)
  const academy = computed(() => profileData.value?.academy ?? null)
  const studentCard = computed(() => profileData.value?.student_card ?? null)
  const homeVisit = computed(() => profileData.value?.home_visit ?? null)
  const schoolActivity = computed(() => profileData.value?.school_activity ?? null)

  const fullNameTh = computed(() => {
    if (!student.value) return ''
    return [
      student.value.title_prefix_th,
      student.value.first_name_th,
      student.value.last_name_th,
    ].filter(Boolean).join(' ')
  })

  const fullNameEn = computed(() => {
    if (!student.value) return ''
    return [
      student.value.title_prefix_en,
      student.value.first_name_en,
      student.value.last_name_en,
    ].filter(Boolean).join(' ')
  })

  const classDisplay = computed(() => {
    if (!student.value?.class_level) return ''
    return `ม.${student.value.class_level}/${student.value.class_section || '-'}`
  })

  const currentAcademicInfo = computed(() => {
    return academicInfo.value.find(a => a.is_current) || academicInfo.value[0] || null
  })

  const primaryAddress = computed(() => {
    return addresses.value.find(a => a.is_current) || addresses.value[0] || null
  })

  const primaryContact = computed(() => {
    return contacts.value.find(c => c.is_primary) || contacts.value[0] || null
  })

  const primaryGuardian = computed(() => {
    return guardians.value.find(g => g.is_primary_contact) || guardians.value[0] || null
  })

  const accessLevelLabel = computed(() => {
    if (!accessLevel.value) return ''
    return ACCESS_LEVEL_LABELS[accessLevel.value] || accessLevel.value
  })

  // Actions
  const fetchProfile = async () => {
    const acadName = typeof academyName === 'string' ? academyName : academyName.value
    const stuId = typeof studentId === 'number' || typeof studentId === 'string' ? studentId : studentId.value

    if (!acadName || !stuId) {
      error.value = 'ข้อมูลไม่ครบถ้วน'
      return
    }

    isLoading.value = true
    error.value = null

    try {
      const response = await api.get(`/api/academies/${acadName}/students/${stuId}/profile`)

      if (response?.success && response?.data) {
        profileData.value = response.data as StudentProfileData
      } else {
        error.value = response?.message || 'ไม่สามารถโหลดข้อมูลได้'
      }
    } catch (err: any) {
      if (err?.status === 403) {
        error.value = 'คุณไม่มีสิทธิ์เข้าถึงข้อมูลนักเรียนนี้'
      } else if (err?.status === 404) {
        error.value = 'ไม่พบข้อมูลนักเรียน'
      } else {
        error.value = err?.message || 'เกิดข้อผิดพลาดในการโหลดข้อมูล'
      }
    } finally {
      isLoading.value = false
    }
  }

  return {
    // State
    profileData,
    isLoading,
    error,

    // Computed
    student,
    classroom,
    academicInfo,
    addresses,
    contacts,
    guardians,
    healthInfo,
    accessLevel,
    accessLevelLabel,
    academy,
    fullNameTh,
    fullNameEn,
    classDisplay,
    currentAcademicInfo,
    primaryAddress,
    primaryContact,
    primaryGuardian,

    // New sections (Phase 3 additions)
    studentCard,
    homeVisit,
    schoolActivity,

    // Actions
    fetchProfile,
  }
}
