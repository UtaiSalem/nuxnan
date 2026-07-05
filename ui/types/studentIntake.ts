export interface StudentIntakeIdentity {
  student_id: string
  citizen_id: string | null
}

export interface StudentIntakePersonal {
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
  gender: 0 | 1 | null
  nationality: string | null
  religion: string | null
}

export interface StudentIntakeAdmission {
  academic_year_id: number | null
  classroom_id: number | null
  student_number: number | null
  enrollment_date: string | null
}

export interface StudentIntakePreviousSchool {
  name: string | null
  province: string | null
  grade_level: string | null
}

export interface GuardianContact {
  contact_type: 'mobile' | 'phone' | 'email' | 'line' | 'other'
  contact_value: string
  is_primary: boolean
}

export interface StudentIntakeGuardian {
  id?: string // for frontend state management
  guardian_type: 'father' | 'mother' | 'guardian' | 'relative' | 'other'
  citizen_id: string | null
  title_prefix: string | null
  first_name: string
  last_name: string
  occupation: string | null
  workplace: string | null
  monthly_income: number | null
  relationship: string | null
  status: 'alive' | 'deceased' | 'unknown' | null
  nationality: string | null
  is_primary_contact: boolean
  is_emergency_contact: boolean
  contacts: GuardianContact[]
}

export interface StudentIntakeAccount {
  mode: 'none' | 'pending_activation'
}

export interface StudentIntakePayload {
  identity: StudentIntakeIdentity
  personal: StudentIntakePersonal
  admission: StudentIntakeAdmission
  previous_school: StudentIntakePreviousSchool
  guardians: StudentIntakeGuardian[]
  account: StudentIntakeAccount
}

export interface DuplicateCheckMatch {
  id: number
  student_id: string
  citizen_id: string | null
  first_name_th: string
  last_name_th: string
  status: string
  matched_fields: string[]
}

export interface DuplicateCheckResult {
  success: boolean
  has_duplicates: boolean
  data: DuplicateCheckMatch[]
}

export interface StudentIntakeResult {
  success: boolean
  message: string
  data: any // Detailed response containing student, member, enrollment, etc.
}

export interface StudentListItem {
  id: number
  student_id: string
  first_name_th: string
  last_name_th: string
  title_prefix_th: string | null
  citizen_id: string | null
  status: string
  account_status: string | null
  created_at: string
  classroom_students: Array<{
    id: number
    classroom_id: number
    status: string
    classroom: {
      id: number
      name: string
      grade_level: string | null
      section: string | null
    } | null
  }>
}

export interface StudentListResponse {
  data: StudentListItem[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}
