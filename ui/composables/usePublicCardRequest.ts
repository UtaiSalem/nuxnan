import { computed, type Ref } from 'vue'

export function usePublicCardRequest(level: Ref<string> | string, room: Ref<string> | string) {
    const config = useRuntimeConfig()
    const apiBase = config.public.apiBase
    
    const lvlVal = computed(() => typeof level === 'string' ? level : level.value)
    const roomVal = computed(() => typeof room === 'string' ? room : room.value)
    const base = computed(() => `${apiBase}/api/student-card/${lvlVal.value}/${roomVal.value}`)

    async function submitCardRequest(
        studentId: number,
        requestType: string,
        reason?: string | null,
        requesterName?: string | null,
        requesterPhone?: string | null
    ) {
        return await $fetch<{ success: boolean; message: string; request_id: number; status: string }>(
            `${base.value}/requests`,
            {
                method: 'POST',
                body: {
                    student_id: studentId,
                    request_type: requestType,
                    reason,
                    requester_name: requesterName,
                    requester_phone: requesterPhone,
                },
            }
        )
    }

    return {
        submitCardRequest,
    }
}
