import { computed, ref, type Ref } from 'vue'

export interface RosterClassroomOption {
    id: number
    name: string
    grade_level: string
    section: string | number
    student_count: number
}

export interface RosterContext {
    can_manage_roster: boolean
    can_edit_card: boolean
    can_request: boolean
    is_homeroom_teacher: boolean
    academy_id?: number
    academy_name?: string
    classroom_id?: number
    classroom_name?: string
    academic_year_id?: number
    academic_year_name?: string
    homeroom_teacher_name?: string | null
    student_count?: number
    capacity?: number | null
    available_classrooms?: RosterClassroomOption[]
}

export interface RosterAvailableStudent {
    id: number
    student_id_code: string
    full_name: string
    current_classroom: string | null
    current_classroom_id: number | null
    enrollment_status: 'unassigned' | 'already_in_room' | 'in_other_room'
}

/**
 * เส้นทางจัดการห้องเรียนของโรงเรียน (ต้องล็อกอินและมีสิทธิ์)
 * คู่กับ useClassroomManagement ที่เป็นเส้นทางสาธารณะชั่วคราว
 *
 * ต่างกันตรงที่ทุกคำขอผ่าน useApi (แนบ JWT + retry) และ backend ตรวจว่าเป็น
 * ผู้จัดการระดับโรงเรียนหรือครูประจำชั้นของห้องนี้
 */
export function useAcademyClassroomRoster(
    academyId: Ref<number | null>,
    level: Ref<string>,
    room: Ref<string>
) {
    const api = useApi()
    const base = computed(() => `/api/academies/${academyId.value}/student-cards/${level.value}/${room.value}`)

    const context = ref<RosterContext | null>(null)
    const isLoadingContext = ref(false)

    async function fetchContext(): Promise<void> {
        if (!academyId.value) return
        isLoadingContext.value = true
        try {
            context.value = await api.get(`${base.value}/context`) as RosterContext
        } catch {
            // ไม่มีสิทธิ์หรือหาห้องไม่เจอ — ถือว่าทำอะไรไม่ได้ ให้หน้าจอซ่อนปุ่มจัดการ
            context.value = {
                can_manage_roster: false,
                can_edit_card: false,
                can_request: false,
                is_homeroom_teacher: false,
            }
        } finally {
            isLoadingContext.value = false
        }
    }

    async function searchAvailableStudents(search: string): Promise<RosterAvailableStudent[]> {
        const response = await api.get(`${base.value}/available-students`, {
            params: { search },
        }) as { students: RosterAvailableStudent[] }

        return response.students || []
    }

    /**
     * confirmTransfer = true คือครูยืนยันแล้วว่าจะดึงนักเรียนออกจากห้องอื่นเข้ามา
     * ถ้าไม่ส่ง backend จะตอบ 422 in_other_room พร้อมชื่อห้องปัจจุบันให้เอาไปถาม
     */
    async function addStudent(studentId: number, studentNumber?: number | null, confirmTransfer = false) {
        return await api.post(`${base.value}/students`, {
            student_id: studentId,
            student_number: studentNumber || null,
            confirm_transfer: confirmTransfer,
        })
    }

    async function transferStudent(studentId: number, toClassroomId: number, reason: string | null) {
        return await api.post(`${base.value}/students/${studentId}/transfer`, {
            to_classroom_id: toClassroomId,
            reason,
        })
    }

    async function removeStudent(studentId: number, reason: string | null) {
        return await api.delete(`${base.value}/students/${studentId}`, {
            body: { reason },
        })
    }

    return {
        context,
        isLoadingContext,
        fetchContext,
        searchAvailableStudents,
        addStudent,
        transferStudent,
        removeStudent,
    }
}
