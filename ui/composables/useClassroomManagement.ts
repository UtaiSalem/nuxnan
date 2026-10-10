/**
 * สัญญา (type) ของการจัดการรายชื่อในห้อง — ใช้ร่วมกันโดยโมดัลเพิ่ม/ย้ายนักเรียน
 *
 * หมายเหตุ: เส้นทางสาธารณะเดิม (/api/student-card/{level}/{room}) ถูกปลดระวางแล้ว
 * 2026-10-11 · การจัดการจริงใช้ useAcademyClassroomRoster (ยิง academy-scoped
 * /api/academies/{academy}/student-cards/{level}/{room}) และส่งตัวจัดการเข้าโมดัลเป็น prop
 */
export interface ManageClassroomOption {
    id: number
    name: string
    grade_level: string
    section: string | number
    student_count: number
}

export interface ManageContext {
    can_manage: boolean
    academy_id?: number
    academy_name?: string
    classroom_id?: number
    classroom_name?: string
    academic_year_id?: number
    academic_year_name?: string
    homeroom_teacher_name?: string | null
    student_count?: number
    capacity?: number | null
    available_classrooms?: ManageClassroomOption[]
}

export interface AvailableStudent {
    id: number
    student_id_code: string
    full_name: string
    current_classroom: string | null
    current_classroom_id: number | null
    enrollment_status: 'unassigned' | 'already_in_room' | 'in_other_room'
}
