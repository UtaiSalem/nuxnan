<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue'
import type { ManageClassroomOption } from '~/composables/useClassroomManagement'

const props = defineProps<{
    open: boolean
    studentName: string
    currentClassroomName: string
    classrooms: ManageClassroomOption[]
}>()

const emit = defineEmits<{
    close: []
    confirm: [toClassroomId: number, reason: string | null]
}>()

const REASON_OPTIONS = [
    'ย้ายตามคำสั่งโรงเรียน',
    'ย้ายตามความประสงค์ผู้ปกครอง',
    'ปรับสมดุลจำนวนนักเรียน',
    'อื่นๆ',
]

const toClassroomId = ref<number | null>(null)
const reasonChoice = ref(REASON_OPTIONS[0])
const customReason = ref('')
const isSubmitting = ref(false)

watch(() => props.open, (open) => {
    if (open) {
        toClassroomId.value = null
        reasonChoice.value = REASON_OPTIONS[0]
        customReason.value = ''
        isSubmitting.value = false
    }
})

const finalReason = computed(() => {
    if (reasonChoice.value === 'อื่นๆ') return customReason.value.trim() || null
    return reasonChoice.value
})

const canSubmit = computed(() => toClassroomId.value !== null && !isSubmitting.value)

const handleConfirm = () => {
    if (!canSubmit.value || toClassroomId.value === null) return
    isSubmitting.value = true
    emit('confirm', toClassroomId.value, finalReason.value)
}
</script>

<template>
    <Dialog as="div" :open="open" @close="emit('close')" class="relative z-50">
        <div class="fixed inset-0 bg-black/30" aria-hidden="true" />
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <DialogPanel class="w-full max-w-md bg-white rounded-lg p-4 sm:p-6 shadow-xl">
                <DialogTitle class="text-lg font-medium text-gray-900 mb-4">ย้ายห้องเรียน</DialogTitle>

                <div class="mb-4 px-3 py-2 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800">
                    <span class="font-medium">{{ studentName }}</span> — ห้องปัจจุบัน {{ currentClassroomName }}
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ห้องปลายทาง</label>
                        <select v-model="toClassroomId"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                            <option :value="null" disabled>-- เลือกห้อง --</option>
                            <option v-for="classroom in classrooms" :key="classroom.id" :value="classroom.id">
                                {{ classroom.name }} ({{ classroom.student_count }} คน)
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">เหตุผล</label>
                        <select v-model="reasonChoice"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                            <option v-for="option in REASON_OPTIONS" :key="option" :value="option">{{ option }}</option>
                        </select>
                        <input v-if="reasonChoice === 'อื่นๆ'" type="text" v-model="customReason" placeholder="ระบุเหตุผล (ไม่บังคับ)"
                            class="mt-2 w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" />
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-5">
                    <button @click="emit('close')"
                        class="min-h-[44px] sm:min-h-0 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        ยกเลิก
                    </button>
                    <button @click="handleConfirm" :disabled="!canSubmit"
                        class="min-h-[44px] sm:min-h-0 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50">
                        {{ isSubmitting ? 'กำลังย้าย...' : 'ยืนยันย้ายห้อง' }}
                    </button>
                </div>
            </DialogPanel>
        </div>
    </Dialog>
</template>
