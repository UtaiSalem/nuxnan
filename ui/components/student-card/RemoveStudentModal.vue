<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Icon } from '@iconify/vue'
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue'

const props = defineProps<{
    open: boolean
    studentName: string
    classroomName: string
}>()

const emit = defineEmits<{
    close: []
    confirm: [reason: string | null]
}>()

const REASON_OPTIONS = [
    'จัดห้องใหม่',
    'ข้อมูลผิดพลาด',
    'ย้ายโรงเรียน',
    'อื่นๆ',
]

const reasonChoice = ref(REASON_OPTIONS[0])
const customReason = ref('')
const isSubmitting = ref(false)

watch(() => props.open, (open) => {
    if (open) {
        reasonChoice.value = REASON_OPTIONS[0]
        customReason.value = ''
        isSubmitting.value = false
    }
})

const finalReason = computed(() => {
    if (reasonChoice.value === 'อื่นๆ') return customReason.value.trim() || null
    return reasonChoice.value
})

const handleConfirm = () => {
    if (isSubmitting.value) return
    isSubmitting.value = true
    emit('confirm', finalReason.value)
}
</script>

<template>
    <Dialog as="div" :open="open" @close="emit('close')" class="relative z-50">
        <div class="fixed inset-0 bg-black/30" aria-hidden="true" />
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <DialogPanel class="w-full max-w-md bg-white rounded-lg p-6 shadow-xl">
                <DialogTitle class="text-lg font-medium text-gray-900 mb-4 flex items-center gap-2">
                    <Icon icon="heroicons:exclamation-triangle" class="w-6 h-6 text-red-500" />
                    นำนักเรียนออกจากห้อง
                </DialogTitle>

                <div class="mb-4 px-3 py-2 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
                    นำ <span class="font-medium">{{ studentName }}</span> ออกจากห้อง {{ classroomName }}
                </div>
                <p class="text-sm text-gray-600 mb-4">
                    นักเรียนจะยังคงสถานะ <span class="font-medium text-green-700">active</span>
                    แต่จะไม่สังกัดห้องใดๆ ประวัติการลงทะเบียนจะถูกเก็บไว้ ไม่ถูกลบ
                </p>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">เหตุผล</label>
                    <select v-model="reasonChoice"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-red-500 outline-none">
                        <option v-for="option in REASON_OPTIONS" :key="option" :value="option">{{ option }}</option>
                    </select>
                    <input v-if="reasonChoice === 'อื่นๆ'" type="text" v-model="customReason" placeholder="ระบุเหตุผล (ไม่บังคับ)"
                        class="mt-2 w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-red-500 outline-none" />
                </div>

                <div class="flex justify-end gap-2 pt-5">
                    <button @click="emit('close')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        ยกเลิก
                    </button>
                    <button @click="handleConfirm" :disabled="isSubmitting"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 disabled:opacity-50">
                        {{ isSubmitting ? 'กำลังนำออก...' : 'ยืนยันนำออก' }}
                    </button>
                </div>
            </DialogPanel>
        </div>
    </Dialog>
</template>
