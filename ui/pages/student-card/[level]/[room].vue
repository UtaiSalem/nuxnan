<script setup lang="ts">
import { ref, computed } from 'vue'
import Swal from 'sweetalert2'
import StudentCardItem from '~/components/student-card/StudentCardItem.vue'
import AddStudentModal from '~/components/student-card/AddStudentModal.vue'
import TransferStudentModal from '~/components/student-card/TransferStudentModal.vue'
import RemoveStudentModal from '~/components/student-card/RemoveStudentModal.vue'

definePageMeta({ layout: false })

const route = useRoute()
const config = useRuntimeConfig()
const apiBase = config.public.apiBase

const level = computed(() => route.params.level as string)
const room = computed(() => route.params.room as string)

useHead({ title: computed(() => `บัตรนักเรียน ม.${level.value}/${room.value}`) })

const students = ref<any[]>([])
const isLoading = ref(true)
const searchTerm = ref('')

const {
    manageContext,
    fetchManageContext,
    searchAvailableStudents,
    addStudent,
    transferStudent,
    removeStudent,
} = useClassroomManagement(level, room)

const showAddModal = ref(false)
const showTransferModal = ref(false)
const showRemoveModal = ref(false)
const selectedStudent = ref<any | null>(null)

const selectedStudentName = computed(() => selectedStudent.value?.full_name_thai
    || [selectedStudent.value?.first_name_thai, selectedStudent.value?.last_name_thai].filter(Boolean).join(' ')
    || '')

const openTransferModal = (student: any) => {
    selectedStudent.value = student
    showTransferModal.value = true
}

const openRemoveModal = (student: any) => {
    selectedStudent.value = student
    showRemoveModal.value = true
}

const handleAdded = async () => {
    await fetchStudents()
    await fetchManageContext()
    Swal.fire({ icon: 'success', title: 'เพิ่มนักเรียนเข้าห้องเรียบร้อย', timer: 1600, showConfirmButton: false })
}

const handleTransferConfirm = async (toClassroomId: number, reason: string | null) => {
    const student = selectedStudent.value
    if (!student) return
    try {
        const response = await transferStudent(student.student_id, toClassroomId, reason)
        showTransferModal.value = false
        students.value = students.value.filter(s => s.id !== student.id)
        await fetchManageContext()
        Swal.fire({ icon: 'success', title: response.message || 'ย้ายห้องเรียบร้อย', timer: 1800, showConfirmButton: false })
    } catch (error: any) {
        showTransferModal.value = false
        Swal.fire({ icon: 'error', title: 'ย้ายห้องไม่สำเร็จ', text: error?.data?.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่' })
    }
}

const handleRemoveConfirm = async (reason: string | null) => {
    const student = selectedStudent.value
    if (!student) return
    try {
        const response = await removeStudent(student.student_id, reason)
        showRemoveModal.value = false
        students.value = students.value.filter(s => s.id !== student.id)
        await fetchManageContext()
        Swal.fire({ icon: 'success', title: response.message || 'นำออกจากห้องเรียบร้อย', timer: 1800, showConfirmButton: false })
    } catch (error: any) {
        showRemoveModal.value = false
        Swal.fire({ icon: 'error', title: 'นำออกจากห้องไม่สำเร็จ', text: error?.data?.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่' })
    }
}

const filteredStudents = computed(() => {
    if (!searchTerm.value) return students.value
    const term = searchTerm.value.toLowerCase()
    return students.value.filter(s =>
        (s.full_name_thai && s.full_name_thai.toLowerCase().includes(term)) ||
        (s.first_name_thai && s.first_name_thai.toLowerCase().includes(term)) ||
        (s.student_number && s.student_number.toString().includes(term))
    )
})

const fetchStudents = async () => {
    isLoading.value = true
    try {
        const response = await $fetch<any>(`${apiBase}/api/student-card/${level.value}/${room.value}`)
        students.value = response.students || []
    } catch (error) {
        console.error('Error fetching students:', error)
    } finally {
        isLoading.value = false
    }
}

onMounted(() => {
    fetchStudents()
    fetchManageContext()
})
</script>

<template>
    <div class="min-h-screen bg-slate-100">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <!-- Header -->
            <div class="bg-white rounded-2xl shadow-xl p-6 mb-6">
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                    <div class="space-y-2">
                        <h1 class="text-2xl font-bold text-gray-800">ข้อมูลนักเรียน</h1>
                        <div class="flex gap-4">
                            <span class="px-3 py-2 bg-blue-100 text-blue-800 rounded-lg font-bold">
                                ชั้น ม.{{ level }}/{{ room }}
                            </span>
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <span class="font-medium">{{ students.length }} คน</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 w-full md:w-auto">
                        <div class="relative w-full sm:w-80">
                            <input type="text" v-model="searchTerm" placeholder="ค้นหาชื่อหรือรหัสนักเรียน..."
                                class="w-full px-4 py-2 pr-10 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none" />
                            <svg class="w-5 h-5 text-gray-400 absolute right-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <NuxtLink to="/student-card"
                            class="w-full sm:w-auto px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200 flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            กลับ
                        </NuxtLink>
                    </div>
                </div>
            </div>

            <!-- Management toolbar (temporary, gated by backend config) -->
            <div v-if="manageContext?.can_manage" class="bg-amber-50 border border-amber-300 rounded-2xl p-4 mb-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-2 text-amber-800">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                        <div>
                            <div class="font-semibold text-sm">ระบบจัดการชั่วคราว — ผู้ที่เข้าถึงหน้านี้สามารถแก้ไขข้อมูลห้องเรียนได้</div>
                            <div class="text-xs text-amber-700">{{ manageContext.academy_name }} · ปีการศึกษา {{ manageContext.academic_year_name }} · ห้อง {{ manageContext.classroom_name }}</div>
                        </div>
                    </div>
                    <button @click="showAddModal = true"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-200 flex items-center gap-2 text-sm font-medium">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        เพิ่มนักเรียน
                    </button>
                </div>
            </div>

            <!-- Loading -->
            <div v-if="isLoading" class="flex items-center justify-center py-20">
                <div class="text-center">
                    <div class="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
                    <p class="text-gray-600">กำลังโหลดข้อมูล...</p>
                </div>
            </div>

            <!-- Empty -->
            <div v-else-if="students.length === 0"
                class="flex flex-col items-center justify-center bg-white rounded-2xl shadow-xl p-12">
                <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <p class="mt-4 text-gray-500 text-lg">ไม่พบข้อมูลนักเรียน</p>
            </div>

            <!-- Cards -->
            <div v-else class="grid grid-cols-1 gap-6 pb-6">
                <StudentCardItem
                    v-for="student in filteredStudents"
                    :key="student.id"
                    :studentInfo="student"
                    :canManage="!!manageContext?.can_manage"
                    @transfer="openTransferModal"
                    @remove="openRemoveModal"
                />
            </div>
        </div>

        <!-- Management modals -->
        <AddStudentModal
            :open="showAddModal"
            :classroomName="manageContext?.classroom_name || `ม.${level}/${room}`"
            :searchStudents="searchAvailableStudents"
            :addStudent="addStudent"
            @close="showAddModal = false"
            @added="handleAdded"
        />
        <TransferStudentModal
            :open="showTransferModal"
            :studentName="selectedStudentName"
            :currentClassroomName="manageContext?.classroom_name || `ม.${level}/${room}`"
            :classrooms="manageContext?.available_classrooms || []"
            @close="showTransferModal = false"
            @confirm="handleTransferConfirm"
        />
        <RemoveStudentModal
            :open="showRemoveModal"
            :studentName="selectedStudentName"
            :classroomName="manageContext?.classroom_name || `ม.${level}/${room}`"
            @close="showRemoveModal = false"
            @confirm="handleRemoveConfirm"
        />
    </div>
</template>
