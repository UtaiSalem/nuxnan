import { computed, type Ref } from 'vue'
import type { StudentCardRequestReason } from '~/types/studentCardRequest'

export interface PublicCardRequestPayload {
    student_id: number
    reason_code: StudentCardRequestReason
    reason?: string | null
    requester_name?: string | null
    requester_phone?: string | null
}

export interface PublicBulkCardRequestPayload {
    student_ids: number[]
    reason_code: StudentCardRequestReason
    reason?: string | null
    requester_name?: string | null
    requester_phone?: string | null
}

export interface PublicBulkCardRequestResult {
    success: boolean
    message: string
    results: { student_id: number; success: boolean; request_id?: number; message?: string }[]
}

export function usePublicCardRequest(level: Ref<string> | string, room: Ref<string> | string) {
    const config = useRuntimeConfig()
    const apiBase = config.public.apiBase

    const lvlVal = computed(() => typeof level === 'string' ? level : level.value)
    const roomVal = computed(() => typeof room === 'string' ? room : room.value)
    const base = computed(() => `${apiBase}/api/student-card/${lvlVal.value}/${roomVal.value}`)

    async function submitCardRequest(payload: PublicCardRequestPayload) {
        return await $fetch<{ success: boolean; message: string; request_id: number; status: string }>(
            `${base.value}/requests`,
            { method: 'POST', body: payload }
        )
    }

    async function submitBulkCardRequests(payload: PublicBulkCardRequestPayload) {
        return await $fetch<PublicBulkCardRequestResult>(
            `${base.value}/requests/bulk`,
            { method: 'POST', body: payload }
        )
    }

    async function cancelCardRequest(requestId: number, studentId: number) {
        return await $fetch<{ success: boolean; message: string; status: string }>(
            `${base.value}/requests/${requestId}/cancel`,
            { method: 'POST', body: { student_id: studentId } }
        )
    }

    return {
        submitCardRequest,
        submitBulkCardRequests,
        cancelCardRequest,
    }
}
