<script setup lang="ts">
import { ref, watch } from 'vue'
import { Icon } from '@iconify/vue'
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue'
import type { AvailableStudent } from '~/composables/useClassroomManagement'

const props = defineProps<{
    open: boolean
    classroomName: string
    searchStudents: (search: string) => Promise<AvailableStudent[]>
    addStudent: (studentId: number, studentNumber?: number | null) => Promise<{ success: boolean; message: string; student_count: number }>
}>()

const emit = defineEmits<{
    close: []
    added: []
}>()

const searchTerm = ref('')
const results = ref<AvailableStudent[]>([])
const isSearching = ref(false)
const addingId = ref<number | null>(null)
const errorMessage = ref('')
const studentNumbers = ref<Record<number, string>>({})

let debounceTimer: ReturnType<typeof setTimeout> | null = null

watch(searchTerm, (value) => {
    errorMessage.value = ''
    if (debounceTimer) clearTimeout(debounceTimer)
    if (value.trim().length < 2) {
        results.value = []
        return
    }
    debounceTimer = setTimeout(async () => {
        isSearching.value = true
        try {
            results.value = await props.searchStudents(value.trim())
        } catch {
            errorMessage.value = 'ค้นหาไม่สำเร็จ กรุณาลองใหม่'
        } finally {
            isSearching.value = false
        }
    }, 300)
})

watch(() => props.open, (open) => {
    if (open) {
        searchTerm.value = ''
        results.value = []
        errorMessage.value = ''
        studentNumbers.value = {}
    }
})

const handleAdd = async (student: AvailableStudent) => {
    errorMessage.value = ''
    addingId.value = student.id
    try {
        const rawNumber = studentNumbers.value[student.id]
        const studentNumber = rawNumber ? parseInt(rawNumber, 10) : null
        await props.addStudent(student.id, studentNumber)
        emit('added')
        results.value = results.value.filter(s => s.id !== student.id)
    } catch (error: any) {
        errorMessage.value = error?.data?.message || 'เพิ่มนักเรียนไม่สำเร็จ'
    } finally {
        addingId.value = null
    }
}
</script>

<template>
    <Dialog as="div" :open="open" @close="emit('close')" class="relative z-50">
        <div class="fixed inset-0 bg-black/30" aria-hidden="true" />
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <DialogPanel class="w-full max-w-lg bg-white rounded-lg p-6 shadow-xl">
                <DialogTitle class="text-lg font-medium text-gray-900 mb-1">เพิ่มนักเรียนเข้าห้อง {{ classroomName }}</DialogTitle>
                <p class="text-sm text-gray-500 mb-4">ค้นหาด้วยชื่อ นามสกุล หรือรหัสนักเรียน (อย่างน้อย 2 ตัวอักษร)</p>

                <div class="relative mb-4">
                    <input type="text" v-model="searchTerm" placeholder="ค้นหานักเรียน..."
                        class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none" />
                    <Icon :icon="isSearching ? 'eos-icons:bubble-loading' : 'heroicons:magnifying-glass'"
                        class="w-5 h-5 text-gray-400 absolute right-3 top-2.5" />
                </div>

                <div v-if="errorMessage" class="mb-3 px-3 py-2 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
                    {{ errorMessage }}
                </div>

                <div class="max-h-80 overflow-y-auto space-y-2">
                    <div v-if="!isSearching && searchTerm.trim().length >= 2 && results.length === 0"
                        class="text-center text-gray-400 py-6 text-sm">
                        ไม่พบนักเรียน
                    </div>

                    <div v-for="student in results" :key="student.id"
                        class="border rounded-lg p-3 flex items-center justify-between gap-3"
                        :class="{
                            'border-green-200 bg-green-50': student.enrollment_status === 'unassigned',
                            'border-amber-200 bg-amber-50': student.enrollment_status === 'in_other_room',
                            'border-gray-200 bg-gray-50': student.enrollment_status === 'already_in_room',
                        }">
                        <div class="min-w-0">
                            <div class="font-medium text-gray-800 truncate">{{ student.full_name }}</div>
                            <div class="text-xs text-gray-500">รหัส {{ student.student_id_code }}</div>
                            <div v-if="student.enrollment_status === 'in_other_room'" class="text-xs text-amber-700 mt-0.5">
                                อยู่ห้อง {{ student.current_classroom }} — ต้องใช้เมนู "ย้ายห้อง" จากห้องเดิม
                            </div>
                            <div v-else-if="student.enrollment_status === 'already_in_room'" class="text-xs text-gray-500 mt-0.5">
                                อยู่ในห้องนี้แล้ว
                            </div>
                        </div>
                        <div v-if="student.enrollment_status === 'unassigned'" class="flex items-center gap-2 flex-shrink-0">
                            <input type="number" min="1" v-model="studentNumbers[student.id]" placeholder="เลขที่"
                                class="w-16 px-2 py-1.5 text-sm border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-green-500" />
                            <button @click="handleAdd(student)" :disabled="addingId === student.id"
                                class="px-3 py-1.5 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 disabled:opacity-50">
                                {{ addingId === student.id ? 'กำลังเพิ่ม...' : 'เพิ่ม' }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-4">
                    <button @click="emit('close')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        ปิด
                    </button>
                </div>
            </DialogPanel>
        </div>
    </Dialog>
</template>
