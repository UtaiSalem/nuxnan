import { ref, computed, type Ref } from 'vue'

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

export function useClassroomManagement(level: Ref<string>, room: Ref<string>) {
    const config = useRuntimeConfig()
    const apiBase = config.public.apiBase
    const base = computed(() => `${apiBase}/api/student-card/${level.value}/${room.value}`)

    const manageContext = ref<ManageContext | null>(null)

    async function fetchManageContext(): Promise<void> {
        try {
            manageContext.value = await $fetch<ManageContext>(`${base.value}/manage-context`)
        } catch {
            manageContext.value = { can_manage: false }
        }
    }

    async function searchAvailableStudents(search: string): Promise<AvailableStudent[]> {
        const response = await $fetch<{ students: AvailableStudent[] }>(
            `${base.value}/available-students`,
            { query: { search } }
        )
        return response.students || []
    }

    async function addStudent(studentId: number, studentNumber?: number | null) {
        return await $fetch<{ success: boolean; message: string; student_count: number }>(
            `${base.value}/students`,
            {
                method: 'POST',
                body: {
                    student_id: studentId,
                    student_number: studentNumber || null,
                },
            }
        )
    }

    async function transferStudent(studentId: number, toClassroomId: number, reason: string | null) {
        return await $fetch<{ success: boolean; message: string; student_count: number }>(
            `${base.value}/students/${studentId}/transfer`,
            {
                method: 'POST',
                body: {
                    to_classroom_id: toClassroomId,
                    reason,
                },
            }
        )
    }

    async function removeStudent(studentId: number, reason: string | null) {
        return await $fetch<{ success: boolean; message: string; student_count: number }>(
            `${base.value}/students/${studentId}`,
            {
                method: 'DELETE',
                body: { reason },
            }
        )
    }

    return {
        manageContext,
        fetchManageContext,
        searchAvailableStudents,
        addStudent,
        transferStudent,
        removeStudent,
    }
}
