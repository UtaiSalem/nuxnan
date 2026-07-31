/**
 * School Management System Types
 */

export interface SchoolClassroom {
  id: number
  academy_id: number
  name: string
  level?: string
  room?: string
  homeroom_teacher_id?: number
  homeroom_teacher?: any // StaffProfile
  student_count?: number
  capacity?: number
  is_active: boolean
  created_at?: string
  updated_at?: string
}

export interface SchoolSubject {
  id: number
  academy_id: number
  code: string
  name: string
  description?: string
  credits?: number
  is_active: boolean
  created_at?: string
  updated_at?: string
}

export interface SchoolClassSchedule {
  id: number
  academy_id: number
  classroom_id: number
  subject_id: number
  teacher_id: number
  day_of_week: number // 1 = Monday, 7 = Sunday
  start_time: string // HH:mm:ss
  end_time: string // HH:mm:ss
  room_name?: string
  is_active: boolean
  
  // Relations
  classroom?: SchoolClassroom
  subject?: SchoolSubject
  teacher?: any
}

export interface SchoolFeeStructure {
  id: number
  academy_id: number
  name: string
  description?: string
  academic_year: string
  semester?: string
  level?: string
  total_amount: number
  due_date?: string
  is_active: boolean
  items?: SchoolFeeItem[]
  created_at?: string
}

export interface SchoolFeeItem {
  id: number
  fee_structure_id: number
  name: string
  amount: number
  is_mandatory: boolean
}

export interface SchoolAnnouncement {
  id: number
  academy_id: number
  created_by: number
  title: string
  content: string
  announcement_type: 'general' | 'academic' | 'financial' | 'event' | 'emergency'
  priority: 'low' | 'normal' | 'high' | 'urgent'
  target_audience?: Record<string, any>
  attachments?: any[]
  is_pinned: boolean
  is_published: boolean
  published_at?: string
  expires_at?: string
  view_count: number
  created_at?: string
  
  // Relations
  author?: any
}

export interface SchoolEvent {
  id: number
  academy_id: number
  title: string
  description?: string
  event_type: 'meeting' | 'holiday' | 'exam' | 'activity' | 'sports' | 'ceremony' | 'other'
  start_datetime: string
  end_datetime?: string
  location?: string
  location_type: 'onsite' | 'online' | 'hybrid'
  meeting_url?: string
  requires_registration: boolean
  registration_deadline?: string
  max_participants?: number
  status: 'draft' | 'published' | 'cancelled' | 'completed'
  cover_image?: string
}

export interface SchoolEmergencyAlert {
  id: number
  academy_id: number
  title: string
  message: string
  alert_type: string
  severity: 'low' | 'medium' | 'high' | 'critical'
  is_active: boolean
  expires_at?: string
  created_at?: string
}

export interface SchoolMeetingSlot {
  id: number
  academy_id: number
  teacher_id: number
  meeting_date: string
  start_time: string
  end_time: string
  slot_duration: number
  location?: string
  meeting_type: 'in_person' | 'video_call'
  meeting_url?: string
  is_available: boolean
  
  teacher?: any
}

export interface SchoolMeetingBooking {
  id: number
  slot_id: number
  parent_id: number
  student_id: number
  booked_time: string
  purpose?: string
  status: 'pending' | 'confirmed' | 'cancelled' | 'completed'
  teacher_notes?: string
  parent_notes?: string
  
  slot?: SchoolMeetingSlot
  parent?: any
  student?: any
}

export interface SchoolAttendanceSummary {
  present: number
  absent: number
  late: number
  leave: number
  total: number
  rate: number
}

// ===== School Attendance (Session-Based) =====

export interface SchoolAttendanceSession {
  id: number
  academy_id: number
  date: string              // YYYY-MM-DD
  title: string
  start_time?: string       // HH:mm
  late_minutes: number
  qr_token?: string
  qr_url?: string
  status: 'open' | 'closed'
  created_by: number
  closed_at?: string
  notes?: string
  records_count?: number
  creator?: {
    id: number
    name: string
  }
  records?: SchoolAttendanceRecord[]
  created_at?: string
  updated_at?: string
}

export interface SchoolAttendanceRecord {
  id: number
  attendance_id: number
  academy_id: number
  student_id: number
  status: 'present' | 'absent' | 'late' | 'leave'
  check_in_method: 'qr' | 'manual'
  checked_in_at?: string
  remarks?: string
  recorded_by?: number
  student?: {
    id: number
    name: string
    profile_photo_path?: string
  }
  created_at?: string
}

export interface SchoolAttendanceSummaryStats {
  total: number
  present: number
  absent: number
  late: number
  leave: number
}
