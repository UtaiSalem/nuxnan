/**
 * Composable for fetching the current user's own student profile.
 *
 * Calls /api/academies/{academy}/students/me/profile
 * which resolves the student record from the authenticated user.
 *
 * Returns the exact same shape as useStudentProfile so the same
 * ProfileViewCards components can be reused.
 */
import { ref, computed, type Ref } from 'vue'
import { useApi } from './useApi'

// ============================================================================
// Types (re-exported from useStudentProfile for convenience)
// ============================================================================

export interface StudentProfile {
  id: number
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

export interface MyStudentProfileData {
  student: StudentProfile
  classroom: ClassroomInfo | null
  academic_info: AcademicInfo[]
  addresses: StudentAddress[]
  contacts: StudentContact[]
  guardians: StudentGuardian[]
  health_info: StudentHealthInfo | null
  access_level: string
  academy: AcademyInfo
}

export const STUDENT_NOT_LINKED_CODE = 'STUDENT_NOT_LINKED'

// ============================================================================
// Composable
// ============================================================================

export const useMyStudentProfile = (academyName: Ref<string> | string) => {
  const api = useApi()

  // State
  const profileData = ref<MyStudentProfileData | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  /** True when the backend returned STUDENT_NOT_LINKED */
  const isUnlinked = ref(false)

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
    const labels: Record<string, string> = {
      self: 'นักเรียน (ตัวเอง)',
      parent: 'ผู้ปกครอง',
      homeroom: 'ครูประจำชั้น',
      teacher: 'ครูผู้สอน',
      admin: 'ผู้บริหาร',
    }
    return labels[accessLevel.value] || accessLevel.value
  })

  // Actions
  const fetchProfile = async () => {
    const acadName = typeof academyName === 'string' ? academyName : academyName.value

    if (!acadName) {
      error.value = 'ข้อมูลไม่ครบถ้วน'
      return
    }

    isLoading.value = true
    error.value = null
    isUnlinked.value = false

    try {
      const response = await api.get(`/api/academies/${acadName}/students/me/profile`)

      if (response?.success && response?.data) {
        profileData.value = response.data as MyStudentProfileData
      } else {
        error.value = response?.message || 'ไม่สามารถโหลดข้อมูลได้'
      }
    } catch (err: any) {
      if (err?.data?.code === STUDENT_NOT_LINKED_CODE) {
        // User is authenticated but has no linked student record
        isUnlinked.value = true
        error.value = 'บัญชีของคุณยังไม่ได้เชื่อมกับข้อมูลนักเรียนในโรงเรียนนี้'
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
    isUnlinked,

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

    // Actions
    fetchProfile,
  }
}
