<script setup lang="ts">
import { ref, computed } from 'vue'
import Swal from 'sweetalert2'
import { Icon } from '@iconify/vue'
import StudentCardItem from '~/components/student-card/StudentCardItem.vue'
import AddStudentModal from '~/components/student-card/AddStudentModal.vue'
import TransferStudentModal from '~/components/student-card/TransferStudentModal.vue'
import RemoveStudentModal from '~/components/student-card/RemoveStudentModal.vue'
import RequestCardModal from '~/components/student-card/RequestCardModal.vue'
import BulkRequestCardModal from '~/components/student-card/BulkRequestCardModal.vue'
import { usePublicCardRequest, type PublicBulkCardRequestResult } from '~/composables/usePublicCardRequest'

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

const { submitCardRequest, submitBulkCardRequests, cancelCardRequest } = usePublicCardRequest(level, room)

const showAddModal = ref(false)
const showTransferModal = ref(false)
const showRemoveModal = ref(false)
const showRequestModal = ref(false)
const showBulkRequestModal = ref(false)
const selectedStudent = ref<any | null>(null)

// โหมดส่งคำร้องหลายคน
const selectMode = ref(false)
const selectedIds = ref<Set<number>>(new Set())

// นักเรียนที่ยังส่งคำร้องได้ (มี student_id และไม่มีคำร้องค้างอยู่)
const eligibleStudents = computed(() =>
    students.value.filter(s => s.student_id && !s.active_card_request))

const selectedStudents = computed(() =>
    eligibleStudents.value.filter(s => selectedIds.value.has(s.student_id)))

const toggleSelectMode = () => {
    selectMode.value = !selectMode.value
    selectedIds.value = new Set()
}

const toggleSelect = (student: any) => {
    const next = new Set(selectedIds.value)
    if (next.has(student.student_id)) next.delete(student.student_id)
    else next.add(student.student_id)
    selectedIds.value = next
}

const selectAllEligible = () => {
    selectedIds.value = new Set(eligibleStudents.value.map(s => s.student_id))
}

const clearSelection = () => {
    selectedIds.value = new Set()
}

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

const openRequestModal = (student: any) => {
    selectedStudent.value = student
    showRequestModal.value = true
}

const cancelRequest = async (student: any) => {
    const request = student.active_card_request
    if (!request?.id || !student.student_id) return
    const confirmation = await Swal.fire({
        icon: 'warning',
        title: 'ยกเลิกคำร้องทำบัตรใหม่?',
        text: 'คำร้องนี้จะถูกยกเลิกและสามารถส่งคำร้องใหม่ได้ภายหลัง',
        showCancelButton: true,
        confirmButtonText: 'ยกเลิกคำร้อง',
        cancelButtonText: 'ปิด',
        confirmButtonColor: '#dc2626',
    })
    if (!confirmation.isConfirmed) return
    try {
        await cancelCardRequest(request.id, student.student_id)
        await fetchStudents()
        Swal.fire({ icon: 'success', title: 'ยกเลิกคำร้องแล้ว', timer: 1600, showConfirmButton: false })
    } catch (error: any) {
        Swal.fire({ icon: 'error', title: 'ยกเลิกคำร้องไม่สำเร็จ', text: error?.data?.message || 'กรุณาลองใหม่อีกครั้ง' })
    }
}

const handleRequestSubmitted = async () => {
    await fetchStudents() // refresh เพื่อให้การ์ดขึ้นสถานะ "ส่งคำร้องแล้ว" ทันที
    Swal.fire({ icon: 'success', title: 'ส่งคำร้องขอทำบัตรนักเรียนสำเร็จ', timer: 1800, showConfirmButton: false })
}

const handleBulkSubmitted = async (result: PublicBulkCardRequestResult) => {
    selectMode.value = false
    selectedIds.value = new Set()
    await fetchStudents()

    const failed = result.results.filter(r => !r.success)
    if (failed.length === 0) {
        Swal.fire({ icon: 'success', title: result.message, timer: 2200, showConfirmButton: false })
        return
    }

    const nameOf = (id: number) => {
        const s = students.value.find(st => st.student_id === id)
        return s?.full_name_thai || `รหัส ${id}`
    }
    Swal.fire({
        icon: 'warning',
        title: result.message,
        html: '<div style="text-align:left;font-size:0.875rem"><b>ส่งไม่สำเร็จ:</b><br>'
            + failed.map(f => `• ${nameOf(f.student_id)} — ${f.message || 'ไม่ทราบสาเหตุ'}`).join('<br>')
            + '</div>',
        confirmButtonText: 'ตกลง',
    })
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
                        <div class="flex flex-wrap items-center gap-3">
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
                            <div v-if="manageContext?.classroom_id" class="flex items-center gap-2 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-gray-600">
                                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                </svg>
                                <span class="font-medium">ครูประจำชั้น: <span class="text-gray-800">{{ manageContext.homeroom_teacher_name || 'ยังไม่ได้กำหนด' }}</span></span>
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

            <!-- Bulk request toolbar -->
            <div v-if="manageContext?.can_request && students.length" class="bg-white border border-blue-100 rounded-2xl p-4 mb-6 shadow-sm">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-2 text-gray-700">
                        <Icon icon="heroicons:credit-card" class="w-5 h-5 text-blue-600 flex-shrink-0" />
                        <div>
                            <div class="font-semibold text-sm">ส่งคำร้องทำบัตรหลายคนในคราวเดียว</div>
                            <div class="text-xs text-gray-500">
                                เหมาะสำหรับนักเรียนเข้าใหม่ทั้งห้อง — เหลือนักเรียนที่ยังส่งคำร้องได้ {{ eligibleStudents.length }} คน
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <template v-if="selectMode">
                            <button @click="selectAllEligible"
                                class="px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition">
                                เลือกทั้งหมด ({{ eligibleStudents.length }})
                            </button>
                            <button @click="clearSelection" :disabled="!selectedIds.size"
                                class="px-3 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition disabled:opacity-40">
                                ล้างที่เลือก
                            </button>
                            <button @click="toggleSelectMode"
                                class="px-3 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                                ยกเลิก
                            </button>
                        </template>
                        <button v-else @click="toggleSelectMode"
                            class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                            <Icon icon="heroicons:user-group" class="w-5 h-5" />
                            ส่งคำร้องหลายคน
                        </button>
                    </div>
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
                    :canRequest="!!manageContext?.can_request"
                    :selectMode="selectMode"
                    :selected="!!student.student_id && selectedIds.has(student.student_id)"
                    @transfer="openTransferModal"
                    @remove="openRemoveModal"
                    @request="openRequestModal"
                    @cancel-request="cancelRequest"
                    @toggle-select="toggleSelect"
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
        <RequestCardModal
            :open="showRequestModal"
            :student="selectedStudent"
            :defaultRequesterName="manageContext?.homeroom_teacher_name || null"
            :submitRequest="submitCardRequest"
            @close="showRequestModal = false"
            @submitted="handleRequestSubmitted"
        />
        <BulkRequestCardModal
            :open="showBulkRequestModal"
            :students="selectedStudents"
            :defaultRequesterName="manageContext?.homeroom_teacher_name || null"
            :submitBulk="submitBulkCardRequests"
            @close="showBulkRequestModal = false"
            @submitted="handleBulkSubmitted"
        />

        <!-- แถบสรุปลอยด้านล่างระหว่างโหมดเลือกหลายคน -->
        <Transition name="fade">
            <div v-if="selectMode" class="fixed bottom-4 left-1/2 -translate-x-1/2 z-40 w-[calc(100%-2rem)] max-w-xl">
                <div class="bg-gray-900/95 text-white rounded-2xl shadow-2xl px-5 py-3 flex items-center justify-between gap-3 backdrop-blur">
                    <div class="flex items-center gap-2 text-sm">
                        <Icon icon="heroicons:check-circle" class="w-5 h-5 text-blue-400" />
                        เลือกแล้ว <span class="font-bold text-blue-300">{{ selectedIds.size }}</span> คน
                    </div>
                    <button @click="showBulkRequestModal = true" :disabled="!selectedIds.size"
                        class="px-4 py-2 text-sm font-semibold bg-blue-600 rounded-xl hover:bg-blue-500 transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-1.5">
                        <Icon icon="heroicons:paper-airplane" class="w-4 h-4" />
                        ส่งคำร้อง
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translate(-50%, 8px);
}
</style>
