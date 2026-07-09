<script setup lang="ts">
import { ref, watch } from 'vue'
import { Icon } from '@iconify/vue'
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue'

const props = defineProps<{
    open: boolean
    student: {
        student_id: number | null
        student_number: string
        full_name_thai: string
    } | null
    submitRequest: (
        studentId: number,
        requestType: string,
        reason?: string | null,
        requesterName?: string | null,
        requesterPhone?: string | null
    ) => Promise<{ success: boolean; message: string; request_id: number; status: string }>
}>()

const emit = defineEmits<{
    close: []
    submitted: []
}>()

const requestType = ref('replacement')
const reason = ref('')
const requesterName = ref('')
const requesterPhone = ref('')
const isSubmitting = ref(false)
const errorMessage = ref('')

watch(() => props.open, (open) => {
    if (open) {
        requestType.value = 'replacement'
        reason.value = ''
        requesterName.value = ''
        requesterPhone.value = ''
        errorMessage.value = ''
        isSubmitting.value = false
    }
})

const handleSubmit = async () => {
    if (!props.student || !props.student.student_id) {
        errorMessage.value = 'ข้อมูลนักเรียนไม่ถูกต้อง'
        return
    }

    errorMessage.value = ''
    isSubmitting.value = true
    try {
        await props.submitRequest(
            props.student.student_id,
            requestType.value,
            reason.value,
            requesterName.value,
            requesterPhone.value
        )
        emit('submitted')
        emit('close')
    } catch (error: any) {
        errorMessage.value = error?.data?.message || 'ส่งคำร้องไม่สำเร็จ'
    } finally {
        isSubmitting.value = false
    }
}
</script>

<template>
    <Dialog as="div" :open="open" @close="emit('close')" class="relative z-50">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" aria-hidden="true" />
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <DialogPanel class="w-full max-w-md bg-white rounded-2xl p-6 shadow-2xl border border-gray-100 overflow-hidden transform transition-all">
                <div class="flex items-center gap-3 border-b border-gray-100 pb-4 mb-4">
                    <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
                        <Icon icon="heroicons:credit-card" class="w-6 h-6" />
                    </div>
                    <div>
                        <DialogTitle class="text-lg font-bold text-gray-900">ยื่นคำร้องขอทำบัตรใหม่</DialogTitle>
                        <p class="text-xs text-gray-500">สำหรับส่งข้อมูลแจ้งเปลี่ยนหรือต่ออายุบัตรนักเรียน</p>
                    </div>
                </div>

                <div v-if="student" class="mb-4 p-3 bg-gray-50 rounded-xl border border-gray-150">
                    <div class="text-xs text-gray-400 font-semibold uppercase tracking-wider">นักเรียน</div>
                    <div class="text-base font-bold text-gray-800">{{ student.full_name_thai }}</div>
                    <div class="text-xs text-gray-500">รหัสประจำตัว: {{ student.student_number }}</div>
                </div>

                <div v-if="errorMessage" class="mb-4 px-3 py-2.5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg flex items-start gap-2">
                    <Icon icon="heroicons:exclamation-triangle" class="w-5 h-5 flex-shrink-0 mt-0.5" />
                    <span>{{ errorMessage }}</span>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">ประเภทคำร้อง</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="relative flex items-center justify-center p-3 border rounded-xl cursor-pointer transition-all select-none"
                                :class="requestType === 'replacement' ? 'border-blue-500 bg-blue-50/50 text-blue-700 font-medium' : 'border-gray-200 hover:bg-gray-50 text-gray-600'">
                                <input type="radio" v-model="requestType" value="replacement" class="sr-only" />
                                <span>บัตรชำรุด / สูญหาย</span>
                            </label>
                            <label class="relative flex items-center justify-center p-3 border rounded-xl cursor-pointer transition-all select-none"
                                :class="requestType === 'renewal' ? 'border-blue-500 bg-blue-50/50 text-blue-700 font-medium' : 'border-gray-200 hover:bg-gray-50 text-gray-600'">
                                <input type="radio" v-model="requestType" value="renewal" class="sr-only" />
                                <span>บัตรหมดอายุ</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">เหตุผลการขอทำบัตรใหม่</label>
                        <textarea v-model="reason" rows="2" placeholder="เช่น ทำบัตรหาย, แถบแม่เหล็กพังชำรุด..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-blue-500 text-sm"></textarea>
                    </div>

                    <div class="border-t border-gray-100 pt-4 space-y-3">
                        <div class="text-xs text-gray-400 font-semibold uppercase tracking-wider">ข้อมูลผู้แจ้งเรื่อง (ทางเลือก)</div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">ชื่อผู้แจ้ง</label>
                                <input type="text" v-model="requesterName" placeholder="เช่น ผู้ปกครอง, ครูประจำชั้น"
                                    class="w-full px-3 py-1.5 border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-blue-500 text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">เบอร์โทรติดต่อ</label>
                                <input type="text" v-model="requesterPhone" placeholder="เบอร์โทรผู้แจ้ง"
                                    class="w-full px-3 py-1.5 border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-blue-500 text-sm" />
                            </div>
                        </div>
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
                        <span>{{ isSubmitting ? 'กำลังส่ง...' : 'ส่งคำร้อง' }}</span>
                    </button>
                </div>
            </DialogPanel>
        </div>
    </Dialog>
</template>
