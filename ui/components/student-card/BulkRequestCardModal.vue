<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Icon } from '@iconify/vue'
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue'
import { STUDENT_CARD_REQUEST_REASONS, type StudentCardRequestReason } from '~/types/studentCardRequest'
import type { PublicBulkCardRequestPayload, PublicBulkCardRequestResult } from '~/composables/usePublicCardRequest'

const props = defineProps<{
    open: boolean
    students: { student_id: number; full_name_thai: string }[]
    defaultRequesterName?: string | null
    submitBulk: (payload: PublicBulkCardRequestPayload) => Promise<PublicBulkCardRequestResult>
}>()

const emit = defineEmits<{
    close: []
    submitted: [result: PublicBulkCardRequestResult]
}>()

const reasonCode = ref<StudentCardRequestReason>('new_student')
const reasonDetail = ref('')
const requesterName = ref('')
const requesterPhone = ref('')
const isSubmitting = ref(false)
const errorMessage = ref('')
const showAllStudents = ref(false)

const detailRequired = computed(() => reasonCode.value === 'other')
const previewStudents = computed(() => showAllStudents.value ? props.students : props.students.slice(0, 5))

watch(() => props.open, (open) => {
    if (open) {
        // เคสหลักคือ ม.1 เข้าใหม่ทั้งห้อง → default เหตุผล "นักเรียนใหม่ยังไม่มีบัตร"
        reasonCode.value = 'new_student'
        reasonDetail.value = ''
        requesterName.value = props.defaultRequesterName || ''
        requesterPhone.value = ''
        errorMessage.value = ''
        isSubmitting.value = false
        showAllStudents.value = false
    }
})

const handleSubmit = async () => {
    if (!props.students.length) {
        errorMessage.value = 'ยังไม่ได้เลือกนักเรียน'
        return
    }
    if (detailRequired.value && !reasonDetail.value.trim()) {
        errorMessage.value = 'กรุณาระบุรายละเอียดเมื่อเลือกเหตุผล "อื่นๆ"'
        return
    }

    errorMessage.value = ''
    isSubmitting.value = true
    try {
        const result = await props.submitBulk({
            student_ids: props.students.map(s => s.student_id),
            reason_code: reasonCode.value,
            reason: reasonDetail.value.trim() || null,
            requester_name: requesterName.value.trim() || null,
            requester_phone: requesterPhone.value.trim() || null,
        })
        emit('submitted', result)
        emit('close')
    } catch (error: any) {
        errorMessage.value = error?.data?.message || 'ส่งคำร้องไม่สำเร็จ'
    } finally {
        isSubmitting.value = false
    }
}
</script>

<template>
    <Dialog as="div" :open="open" @close="!isSubmitting && emit('close')" class="relative z-50">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" aria-hidden="true" />
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <DialogPanel class="w-full max-w-lg bg-white rounded-2xl p-6 shadow-2xl border border-gray-100 overflow-hidden transform transition-all max-h-[90vh] overflow-y-auto">
                <div class="flex items-center gap-3 border-b border-gray-100 pb-4 mb-4">
                    <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
                        <Icon icon="heroicons:user-group" class="w-6 h-6" />
                    </div>
                    <div>
                        <DialogTitle class="text-lg font-bold text-gray-900">ส่งคำร้องทำบัตรหลายคน</DialogTitle>
                        <p class="text-xs text-gray-500">เหตุผลและผู้แจ้งชุดเดียวกันจะใช้กับนักเรียนทุกคนที่เลือก</p>
                    </div>
                </div>

                <!-- รายชื่อที่เลือก -->
                <div class="mb-4 p-3 bg-blue-50/50 rounded-xl border border-blue-100">
                    <div class="flex items-center justify-between">
                        <div class="text-xs text-blue-500 font-semibold uppercase tracking-wider">นักเรียนที่เลือก</div>
                        <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-blue-600 text-white">{{ students.length }} คน</span>
                    </div>
                    <ul class="mt-2 space-y-0.5 text-sm text-gray-700">
                        <li v-for="s in previewStudents" :key="s.student_id" class="flex items-center gap-1.5">
                            <Icon icon="heroicons:check-circle-solid" class="w-4 h-4 text-blue-500 flex-shrink-0" />
                            {{ s.full_name_thai }}
                        </li>
                    </ul>
                    <button v-if="students.length > 5" @click="showAllStudents = !showAllStudents"
                        class="mt-1.5 text-xs font-medium text-blue-600 hover:underline">
                        {{ showAllStudents ? 'ย่อรายชื่อ' : `ดูอีก ${students.length - 5} คน` }}
                    </button>
                </div>

                <div v-if="errorMessage" class="mb-4 px-3 py-2.5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg flex items-start gap-2">
                    <Icon icon="heroicons:exclamation-triangle" class="w-5 h-5 flex-shrink-0 mt-0.5" />
                    <span>{{ errorMessage }}</span>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-1.5">
                            เหตุผลการขอทำบัตร
                            <span class="px-1.5 py-0.5 text-[10px] font-medium rounded bg-red-50 text-red-600 border border-red-200">จำเป็น</span>
                        </label>
                        <select v-model="reasonCode"
                            class="w-full px-4 py-2.5 text-sm text-gray-700 bg-white border border-gray-300 rounded-xl outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30">
                            <option v-for="option in STUDENT_CARD_REQUEST_REASONS" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-1">
                            รายละเอียดเพิ่มเติม
                            <span v-if="detailRequired" class="px-1.5 py-0.5 text-[10px] font-medium rounded bg-red-50 text-red-600 border border-red-200">จำเป็น</span>
                            <span v-else class="px-1.5 py-0.5 text-[10px] font-medium rounded bg-gray-100 text-gray-500 border border-gray-200">ไม่จำเป็น</span>
                        </label>
                        <textarea v-model="reasonDetail" rows="2"
                            :placeholder="detailRequired ? 'โปรดระบุเหตุผล...' : 'เว้นว่างได้ ถ้าไม่มีรายละเอียดเพิ่มเติม'"
                            class="w-full px-3 py-2 border border-gray-300 rounded-xl outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30 text-sm"></textarea>
                    </div>

                    <div class="border-t border-gray-100 pt-4 space-y-3">
                        <div class="text-xs text-gray-400 font-semibold uppercase tracking-wider">ข้อมูลผู้แจ้งเรื่อง</div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="flex items-center gap-1.5 text-xs font-semibold text-gray-600 mb-1">
                                    ชื่อผู้แจ้ง
                                    <span class="px-1.5 py-0.5 text-[10px] font-medium rounded bg-gray-100 text-gray-500 border border-gray-200">ไม่จำเป็น</span>
                                </label>
                                <input type="text" v-model="requesterName" placeholder="ค่าเริ่มต้น: ครูประจำชั้น"
                                    class="w-full px-3 py-1.5 border border-gray-300 rounded-lg outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30 text-sm" />
                            </div>
                            <div>
                                <label class="flex items-center gap-1.5 text-xs font-semibold text-gray-600 mb-1">
                                    เบอร์โทรติดต่อ
                                    <span class="px-1.5 py-0.5 text-[10px] font-medium rounded bg-gray-100 text-gray-500 border border-gray-200">ไม่จำเป็น</span>
                                </label>
                                <input type="text" v-model="requesterPhone" placeholder="เว้นว่างได้"
                                    class="w-full px-3 py-1.5 border border-gray-300 rounded-lg outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30 text-sm" />
                            </div>
                        </div>
                        <p class="text-[11px] text-gray-400 flex items-center gap-1">
                            <Icon icon="heroicons:information-circle" class="w-3.5 h-3.5" />
                            ถ้าเว้นว่าง ระบบจะบันทึกชื่อครูประจำชั้นเป็นผู้แจ้งให้อัตโนมัติ
                        </p>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-6 mt-4 border-t border-gray-100">
                    <button @click="emit('close')" :disabled="isSubmitting"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-all disabled:opacity-50">
                        ยกเลิก
                    </button>
                    <button @click="handleSubmit" :disabled="isSubmitting"
                        class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-all disabled:opacity-50 flex items-center gap-1.5">
                        <Icon v-if="isSubmitting" icon="eos-icons:bubble-loading" class="w-4 h-4" />
                        <span>{{ isSubmitting ? 'กำลังส่ง...' : `ส่งคำร้อง ${students.length} คน` }}</span>
                    </button>
                </div>
            </DialogPanel>
        </div>
    </Dialog>
</template>
