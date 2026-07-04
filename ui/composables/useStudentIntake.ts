import { ref, reactive } from 'vue'
import { v4 as uuidv4 } from 'uuid'
import type { 
  StudentIntakePayload, 
  StudentIntakeGuardian, 
  DuplicateCheckMatch 
} from '../types/studentIntake'
import { useStudentIntakeService } from '../services/studentIntakeService'

// --- Shared State (Module level) ---
const isCheckingDuplicate = ref(false)
const isSubmitting = ref(false)
const duplicateMatches = ref<DuplicateCheckMatch[]>([])

const payload = reactive<StudentIntakePayload>({
  identity: {
    student_id: '',
    citizen_id: null
  },
  personal: {
    title_prefix_th: null,
    first_name_th: '',
    last_name_th: '',
    middle_name_th: null,
    title_prefix_en: null,
    first_name_en: null,
    last_name_en: null,
    middle_name_en: null,
    nickname: null,
    date_of_birth: null,
    gender: null,
    nationality: 'ไทย',
    religion: 'พุทธ'
  },
  admission: {
    academic_year_id: null,
    classroom_id: null,
    student_number: null,
    enrollment_date: new Date().toISOString().split('T')[0] // Default today
  },
  previous_school: {
    name: null,
    province: null,
    grade_level: null
  },
  guardians: [],
  account: {
    mode: 'pending_activation'
  }
})
// -----------------------------------

export const useStudentIntake = (academyName?: string) => {
  const service = useStudentIntakeService()

  // Guardian Management
  const addGuardian = () => {
    if (payload.guardians.length >= 5) return
    
    payload.guardians.push({
      id: uuidv4(),
      guardian_type: 'parent', // Will be overridden by the form, just a fallback
      citizen_id: null,
      title_prefix: null,
      first_name: '',
      last_name: '',
      occupation: null,
      workplace: null,
      monthly_income: null,
      relationship: null,
      status: 'alive',
      nationality: 'ไทย',
      is_primary_contact: payload.guardians.length === 0, // First guardian is primary by default
      is_emergency_contact: payload.guardians.length === 0
    } as any)
  }

  const removeGuardian = (id: string) => {
    const index = payload.guardians.findIndex(g => g.id === id)
    if (index !== -1) {
      payload.guardians.splice(index, 1)
      
      // Reassign primary if needed
      if (payload.guardians.length > 0) {
        if (!payload.guardians.some(g => g.is_primary_contact)) {
          payload.guardians[0].is_primary_contact = true
        }
        if (!payload.guardians.some(g => g.is_emergency_contact)) {
          payload.guardians[0].is_emergency_contact = true
        }
      }
    }
  }

  const setPrimaryGuardian = (id: string) => {
    payload.guardians.forEach(g => {
      g.is_primary_contact = (g.id === id)
    })
  }
  
  const setEmergencyGuardian = (id: string) => {
    payload.guardians.forEach(g => {
      g.is_emergency_contact = (g.id === id)
    })
  }

  // API Methods
  const checkDuplicate = async (): Promise<boolean> => {
    if (!payload.identity.student_id) return false
    
    isCheckingDuplicate.value = true
    duplicateMatches.value = []
    
    try {
      const result = await service.checkDuplicate(
        academyName, 
        payload.identity.student_id, 
        payload.identity.citizen_id
      )
      
      if (result.has_duplicates) {
        duplicateMatches.value = result.data
        return true
      }
      return false
    } catch (error) {
      console.error('Duplicate check failed', error)
      throw error
    } finally {
      isCheckingDuplicate.value = false
    }
  }

  const submit = async () => {
    isSubmitting.value = true
    try {
      // Clean up payload before sending
      const cleanPayload = JSON.parse(JSON.stringify(payload))
      
      // Remove frontend-only IDs from guardians
      cleanPayload.guardians = cleanPayload.guardians.map((g: any) => {
        const { id, ...rest } = g
        return rest
      })
      
      const result = await service.submitIntake(academyName, cleanPayload)
      return result
    } catch (error) {
      console.error('Submission failed', error)
      throw error
    } finally {
      isSubmitting.value = false
    }
  }

  const resetForm = () => {
    // Reset to initial state
    payload.identity.student_id = ''
    payload.identity.citizen_id = null
    
    payload.personal.title_prefix_th = null
    payload.personal.first_name_th = ''
    payload.personal.last_name_th = ''
    payload.personal.middle_name_th = null
    payload.personal.title_prefix_en = null
    payload.personal.first_name_en = null
    payload.personal.last_name_en = null
    payload.personal.middle_name_en = null
    payload.personal.nickname = null
    payload.personal.date_of_birth = null
    payload.personal.gender = null
    payload.personal.nationality = 'ไทย'
    payload.personal.religion = 'พุทธ'
    
    payload.admission.academic_year_id = null
    payload.admission.classroom_id = null
    payload.admission.student_number = null
    payload.admission.enrollment_date = new Date().toISOString().split('T')[0]
    
    payload.previous_school.name = null
    payload.previous_school.province = null
    payload.previous_school.grade_level = null
    
    payload.guardians = []
    payload.account.mode = 'pending_activation'
    
    duplicateMatches.value = []
  }

  return {
    payload,
    isCheckingDuplicate,
    isSubmitting,
    duplicateMatches,
    addGuardian,
    removeGuardian,
    setPrimaryGuardian,
    setEmergencyGuardian,
    checkDuplicate,
    submit,
    resetForm
  }
}
