/**
 * Composable for fetching the current user's own student profile.
 *
 * Calls /api/academies/{academy}/students/me/profile which resolves
 * the student record from the authenticated user.
 *
 * Returns the same shape as useStudentProfile so ProfileViewCards
 * components can be reused. Types live in useStudentProfile.ts —
 * this file imports them to avoid duplicate auto-imports.
 */
import { ref, computed, type Ref } from 'vue'
import { useApi } from './useApi'
import type { StudentProfileData } from './useStudentProfile'
import { ACCESS_LEVEL_LABELS } from './useStudentProfile'

export type MyStudentProfileData = StudentProfileData

export const STUDENT_NOT_LINKED_CODE = 'STUDENT_NOT_LINKED'

export const useMyStudentProfile = (academyName: Ref<string> | string) => {
  const api = useApi()

  const profileData = ref<MyStudentProfileData | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  /** True when the backend returned STUDENT_NOT_LINKED */
  const isUnlinked = ref(false)

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
    profileData,
    isLoading,
    error,
    isUnlinked,

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
    studentCard,
    homeVisit,
    schoolActivity,

    fetchProfile,
  }
}
