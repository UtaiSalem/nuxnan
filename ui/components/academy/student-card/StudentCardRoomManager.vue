<script setup lang="ts">
/**
 * มุมมอง "บัตรทั้งห้อง" ของหน้าจัดการบัตรนักเรียนฝั่งโรงเรียน
 *
 * ทำได้เท่ากับหน้าชั่วคราว /student-card/{level}/{room} — ดูบัตรใบใหญ่ แก้ข้อมูล
 * บนบัตร อัพโหลด/ลบรูป เรียงลำดับ กระโดดไปบัตรของใครก็ได้ จัดการรายชื่อในห้อง
 * และส่งคำร้องทำบัตร — แต่ทุกอย่างผ่านการตรวจสิทธิ์จริง (แอดมินโรงเรียน หรือ
 * ครูประจำชั้นของห้องนี้) โดย backend เป็นคนบอกผ่าน endpoint context ว่าทำอะไรได้
 */
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import StudentCardItem from '~/components/student-card/StudentCardItem.vue'
import AddStudentModal from '~/components/student-card/AddStudentModal.vue'
import TransferStudentModal from '~/components/student-card/TransferStudentModal.vue'
import RemoveStudentModal from '~/components/student-card/RemoveStudentModal.vue'
import RequestCardModal from '~/components/student-card/RequestCardModal.vue'
import BulkRequestCardModal from '~/components/student-card/BulkRequestCardModal.vue'

const props = defineProps<{
    academyId: number
    academy: any
    level: string
    room: string
}>()

const emit = defineEmits<{ back: [] }>()

const api = useApi()
const config = useRuntimeConfig()

const academyIdRef = computed(() => props.academyId)
const levelRef = computed(() => props.level)
const roomRef = computed(() => props.room)

const students = ref<any[]>([])
const isLoading = ref(true)
const loadError = ref<string | null>(null)

const {
    searchTerm,
    sortKey,
    sortOptions,
    currentIndex,
    railRef,
    sortedStudents,
    studentSortName,
    studentLabel,
    scrollToIndex,
    stepCard,
} = useStudentCardRoomView(students)

const {
    context,
    fetchContext,
    searchAvailableStudents,
    addStudent,
    transferStudent,
    removeStudent,
} = useAcademyClassroomRoster(academyIdRef, levelRef, roomRef)

const cardRequests = useStudentCardRequests(academyIdRef)

const canManageRoster = computed(() => !!context.value?.can_manage_roster)
const canEditCard = computed(() => !!context.value?.can_edit_card)
const canRequest = computed(() => !!context.value?.can_request)
const classroomName = computed(() => context.value?.classroom_name || `ม.${props.level}/${props.room}`)

// ตัวตนโรงเรียนบนหน้าบัตร — ต้องมาจาก academy ของ URL ไม่ใช่ค่าตายตัวในคอมโพเนนต์
const school = computed(() => ({
    name_th: props.academy?.name || '',
    name_en: props.academy?.name_en || props.academy?.english_name || '',
    address: props.academy?.address || '',
    logo_url: props.academy?.logo
        ? `${config.public.apiBase}/storage/${props.academy.logo}`
        : null,
}))

const fetchStudents = async () => {
    isLoading.value = true
    loadError.value = null
    try {
        const response: any = await api.get(
            `/api/academies/${props.academyId}/student-cards/${props.level}/${props.room}`
        )
        students.value = response.students || []
    } catch (error: any) {
        loadError.value = error?.data?.message || 'ไม่สามารถโหลดข้อมูลบัตรนักเรียนของห้องนี้ได้'
    } finally {
        isLoading.value = false
    }
}

onMounted(async () => {
    await Promise.all([fetchStudents(), fetchContext()])
})

// ── การแก้ไขบนบัตร ───────────────────────────────────────────────────
const updateCard = (payload: any, student: any) =>
    api.put(`/api/academies/${props.academyId}/student-cards/${student.id}`, payload)

const uploadPhoto = (formData: FormData, student: any) =>
    api.post(`/api/academies/${props.academyId}/student-cards/admin/upload-photo/${student.id}`, formData)

const deletePhoto = (student: any) =>
    api.delete(`/api/academies/${props.academyId}/student-cards/${student.id}/photo`)

// ── จัดการรายชื่อในห้อง ──────────────────────────────────────────────
const showAddModal = ref(false)
const showTransferModal = ref(false)
const showRemoveModal = ref(false)
const showRequestModal = ref(false)
const showBulkRequestModal = ref(false)
const selectedStudent = ref<any | null>(null)

const selectedStudentName = computed(() => selectedStudent.value?.full_name_thai
    || [selectedStudent.value?.first_name_thai, selectedStudent.value?.last_name_thai].filter(Boolean).join(' ')
    || '')

/**
 * นักเรียนที่ยังสังกัดห้องอื่นอยู่ — backend ตอบ 422 in_other_room มาก่อน
 * ถามครูให้ชัดว่ากำลังจะดึงออกจากห้องของใคร แล้วค่อยยิงซ้ำพร้อมคำยืนยัน
 */
const addStudentWithConfirm = async (studentId: number, studentNumber?: number | null) => {
    try {
        return await addStudent(studentId, studentNumber)
    } catch (error: any) {
        if (error?.data?.error !== 'in_other_room') throw error

        const currentRoom = error.data.current_classroom_name || 'ห้องอื่น'
        const confirmation = await Swal.fire({
            icon: 'warning',
            title: 'นักเรียนอยู่ห้องอื่นอยู่',
            html: `นักเรียนคนนี้อยู่ <b>${currentRoom}</b><br>ถ้ายืนยัน ระบบจะย้ายออกจากห้องนั้นเข้ามาห้อง <b>${classroomName.value}</b>`,
            showCancelButton: true,
            confirmButtonText: 'ยืนยันย้ายเข้าห้องนี้',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: '#2563eb',
        })

        if (!confirmation.isConfirmed) throw error

        const result: any = await addStudent(studentId, studentNumber, true)
        if (result?.message) {
            Swal.fire({ icon: 'success', title: result.message, timer: 2200, showConfirmButton: false })
        }

        return result
    }
}

const handleAdded = async () => {
    await Promise.all([fetchStudents(), fetchContext()])
}

const openTransferModal = (student: any) => {
    selectedStudent.value = student
    showTransferModal.value = true
}

const openRemoveModal = (student: any) => {
    selectedStudent.value = student
    showRemoveModal.value = true
}

const handleTransferConfirm = async (toClassroomId: number, reason: string | null) => {
    const student = selectedStudent.value
    if (!student?.student_id) return
    try {
        const response: any = await transferStudent(student.student_id, toClassroomId, reason)
        showTransferModal.value = false
        students.value = students.value.filter(s => s.uid !== student.uid)
        await fetchContext()
        Swal.fire({ icon: 'success', title: response.message || 'ย้ายห้องเรียบร้อย', timer: 1800, showConfirmButton: false })
    } catch (error: any) {
        showTransferModal.value = false
        Swal.fire({ icon: 'error', title: 'ย้ายห้องไม่สำเร็จ', text: error?.data?.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่' })
    }
}

const handleRemoveConfirm = async (reason: string | null) => {
    const student = selectedStudent.value
    if (!student?.student_id) return
    try {
        const response: any = await removeStudent(student.student_id, reason)
        showRemoveModal.value = false
        students.value = students.value.filter(s => s.uid !== student.uid)
        await fetchContext()
        Swal.fire({ icon: 'success', title: response.message || 'นำออกจากห้องเรียบร้อย', timer: 1800, showConfirmButton: false })
    } catch (error: any) {
        showRemoveModal.value = false
        Swal.fire({ icon: 'error', title: 'นำออกจากห้องไม่สำเร็จ', text: error?.data?.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่' })
    }
}

// ── คำร้องทำบัตร ─────────────────────────────────────────────────────
const selectMode = ref(false)
const selectedIds = ref<Set<number>>(new Set())

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

const openRequestModal = (student: any) => {
    selectedStudent.value = student
    showRequestModal.value = true
}

// โมดัลคำร้องถูกออกแบบไว้กับ payload ของเส้นทางสาธารณะ ฝั่งโรงเรียนต้องมี
// classroom_id เพิ่ม จึงห่อให้ตรงกันตรงนี้แทนการแก้โมดัลที่ใช้ร่วมกันสองหน้า
const submitCardRequest = async (payload: any) => {
    await cardRequests.submit({
        student_id: payload.student_id,
        classroom_id: context.value?.classroom_id as number,
        reason_code: payload.reason_code,
        reason: payload.reason || undefined,
    })

    return { success: true, message: 'ส่งคำร้องสำเร็จ', request_id: 0, status: 'pending' }
}

const submitBulkCardRequests = async (payload: any) => {
    const response = await cardRequests.submitBulk(
        payload.student_ids.map((studentId: number) => ({
            student_id: studentId,
            classroom_id: context.value?.classroom_id as number,
            reason_code: payload.reason_code,
            reason: payload.reason || undefined,
        }))
    )

    const results = response.data || []
    const failed = results.filter(r => !r.success)

    return {
        success: failed.length === 0,
        message: failed.length === 0
            ? `ส่งคำร้องสำเร็จ ${results.length} คน`
            : `ส่งคำร้องสำเร็จ ${results.length - failed.length} จาก ${results.length} คน`,
        results,
    }
}

const cancelRequest = async (student: any) => {
    const request = student.active_card_request
    if (!request?.id) return

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
        await cardRequests.transition(request.id, 'cancel')
        await fetchStudents()
        Swal.fire({ icon: 'success', title: 'ยกเลิกคำร้องแล้ว', timer: 1600, showConfirmButton: false })
    } catch (error: any) {
        Swal.fire({ icon: 'error', title: 'ยกเลิกคำร้องไม่สำเร็จ', text: error?.data?.message || 'กรุณาลองใหม่อีกครั้ง' })
    }
}

const handleRequestSubmitted = async () => {
    await fetchStudents()
    Swal.fire({ icon: 'success', title: 'ส่งคำร้องขอทำบัตรนักเรียนสำเร็จ', timer: 1800, showConfirmButton: false })
}

const handleBulkSubmitted = async (result: any) => {
    selectMode.value = false
    selectedIds.value = new Set()
    await fetchStudents()

    const failed = (result.results || []).filter((r: any) => !r.success)
    if (failed.length === 0) {
        Swal.fire({ icon: 'success', title: result.message, timer: 2200, showConfirmButton: false })

        return
    }

    const nameOf = (id: number) => students.value.find(s => s.student_id === id)?.full_name_thai || `รหัส ${id}`
    Swal.fire({
        icon: 'warning',
        title: result.message,
        html: '<div style="text-align:left;font-size:0.875rem"><b>ส่งไม่สำเร็จ:</b><br>'
            + failed.map((f: any) => `• ${nameOf(f.student_id)} — ${f.message || 'ไม่ทราบสาเหตุ'}`).join('<br>')
            + '</div>',
        confirmButtonText: 'ตกลง',
    })
}
</script>

<template>
    <div>
        <!-- หัวเรื่องห้อง + บริบทที่ backend ตอบมา -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 mb-4">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <button
                        @click="emit('back')"
                        class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition shrink-0"
                        aria-label="กลับไปเลือกห้อง"
                    >
                        <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5 dark:text-gray-300" />
                    </button>
                    <div class="min-w-0">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            ม.{{ level }}/{{ room }}
                        </h2>
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                            <span>{{ students.length }} คน</span>
                            <span v-if="context?.academic_year_name">ปีการศึกษา {{ context.academic_year_name }}</span>
                            <span v-if="context?.homeroom_teacher_name" class="inline-flex items-center gap-1">
                                <Icon icon="fluent:person-24-regular" class="w-4 h-4" />
                                ครูประจำชั้น: {{ context.homeroom_teacher_name }}
                            </span>
                            <span v-if="context?.is_homeroom_teacher"
                                class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-50 text-green-700 dark:bg-green-900/40 dark:text-green-300">
                                ห้องของคุณ
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-2 sm:items-center">
                    <div class="relative">
                        <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                        <input
                            v-model="searchTerm"
                            type="text"
                            placeholder="ค้นหาชื่อหรือรหัสนักเรียน..."
                            class="w-full sm:w-64 pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 outline-none"
                        />
                    </div>
                    <select
                        v-model="sortKey"
                        title="เรียงลำดับตาม"
                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white outline-none focus:ring-2 focus:ring-primary-500"
                    >
                        <option v-for="opt in sortOptions" :key="opt.key" :value="opt.key">
                            เรียงตาม{{ opt.label }}
                        </option>
                    </select>
                    <button
                        v-if="canManageRoster"
                        @click="showAddModal = true"
                        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition flex items-center justify-center gap-2"
                    >
                        <Icon icon="fluent:person-add-24-regular" class="w-5 h-5" />
                        <span>เพิ่มนักเรียน</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- แถบส่งคำร้องหลายคน -->
        <div v-if="canRequest && students.length"
            class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 mb-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                    <Icon icon="fluent:contact-card-24-regular" class="w-5 h-5 text-primary-600 shrink-0" />
                    <div>
                        <div class="font-semibold text-sm">ส่งคำร้องทำบัตรหลายคนในคราวเดียว</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            เหลือนักเรียนที่ยังส่งคำร้องได้ {{ eligibleStudents.length }} คน
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <template v-if="selectMode">
                        <button @click="selectAllEligible"
                            class="px-3 py-2 text-sm font-medium text-primary-700 bg-primary-50 dark:bg-primary-900/30 dark:text-primary-300 border border-primary-200 dark:border-primary-800 rounded-lg hover:bg-primary-100 transition">
                            เลือกทั้งหมด ({{ eligibleStudents.length }})
                        </button>
                        <button @click="clearSelection" :disabled="!selectedIds.size"
                            class="px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition disabled:opacity-40">
                            ล้างที่เลือก
                        </button>
                        <button @click="toggleSelectMode"
                            class="px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                            ยกเลิก
                        </button>
                    </template>
                    <button v-else @click="toggleSelectMode"
                        class="px-4 py-2 text-sm font-semibold text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition flex items-center gap-2">
                        <Icon icon="fluent:people-24-regular" class="w-5 h-5" />
                        ส่งคำร้องหลายคน
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="flex justify-center py-16">
            <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
        </div>

        <!-- Error -->
        <div v-else-if="loadError"
            class="p-8 text-center text-red-500 bg-red-50 dark:bg-red-900/10 rounded-xl border border-red-100 dark:border-red-900/30 max-w-md mx-auto my-6">
            <Icon icon="fluent:error-circle-24-regular" class="w-8 h-8 mx-auto mb-2" />
            <p class="text-sm">{{ loadError }}</p>
            <button @click="fetchStudents"
                class="mt-3 px-3 py-1.5 bg-red-100 hover:bg-red-200 dark:bg-red-900/50 dark:hover:bg-red-900 text-xs font-semibold rounded-lg transition">
                ลองใหม่อีกครั้ง
            </button>
        </div>

        <!-- Empty -->
        <div v-else-if="!students.length" class="text-center py-12">
            <Icon icon="fluent:people-24-regular" class="w-12 h-12 mx-auto text-gray-300 mb-3" />
            <p class="text-gray-500 dark:text-gray-400">ไม่พบข้อมูลนักเรียนประจำห้องเรียนนี้</p>
            <button v-if="canManageRoster" @click="showAddModal = true"
                class="mt-4 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition inline-flex items-center gap-2">
                <Icon icon="fluent:person-add-24-regular" class="w-5 h-5" />
                เพิ่มนักเรียนเข้าห้องนี้
            </button>
        </div>

        <!-- รายชื่อด้านซ้าย + บัตร -->
        <div v-else class="lg:flex lg:gap-6">
            <aside class="hidden shrink-0 lg:block lg:w-64 xl:w-72">
                <div class="rounded-xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm lg:sticky lg:top-4">
                    <div class="flex items-center gap-2 border-b border-gray-100 dark:border-gray-700 px-3 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-200">
                        <Icon icon="fluent:list-24-regular" class="h-4 w-4 text-primary-600" />
                        รายชื่อนักเรียน
                        <span class="ml-auto text-xs font-normal text-gray-400">{{ sortedStudents.length }} คน</span>
                    </div>

                    <div class="grid grid-cols-[2.5rem_3.5rem_1fr] gap-2 border-b border-gray-100 dark:border-gray-700 px-3 py-1.5 text-[11px] font-semibold text-gray-400">
                        <span>เลขที่</span>
                        <span>รหัส</span>
                        <span>ชื่อ-สกุล</span>
                    </div>

                    <div ref="railRef" class="relative max-h-[calc(100vh-11rem)] overflow-y-auto py-1">
                        <button v-for="(student, index) in sortedStudents" :key="student.uid"
                            :id="`row-${student.uid}`" type="button" @click="scrollToIndex(index)"
                            class="grid w-full grid-cols-[2.5rem_3.5rem_1fr] items-center gap-2 px-3 py-1.5 text-left text-xs transition"
                            :class="index === currentIndex
                                ? 'bg-primary-50 dark:bg-primary-900/30 font-semibold text-primary-700 dark:text-primary-300'
                                : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'">
                            <span class="tabular-nums">{{ student.order_no || '-' }}</span>
                            <span class="font-mono tabular-nums">{{ student.student_number || '-' }}</span>
                            <span class="truncate">{{ studentSortName(student) || 'ไม่ระบุชื่อ' }}</span>
                        </button>
                    </div>
                </div>
            </aside>

            <div class="min-w-0 lg:flex-1">
                <!-- แถบนำทาง — กระโดดไปบัตรของใครก็ได้โดยไม่ต้องเลื่อนหา -->
                <div class="sticky top-0 z-30 mb-4 rounded-xl border border-gray-100 dark:border-gray-700 bg-white/95 dark:bg-gray-800/95 px-3 py-2.5 shadow-sm backdrop-blur">
                    <div class="flex items-center gap-2">
                        <button @click="stepCard(-1)" :disabled="currentIndex <= 0"
                            class="shrink-0 rounded-lg border border-gray-200 dark:border-gray-600 p-2 text-gray-600 dark:text-gray-300 transition hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-40"
                            aria-label="บัตรก่อนหน้า">
                            <Icon icon="fluent:chevron-up-24-regular" class="h-4 w-4" />
                        </button>

                        <select :value="currentIndex"
                            @change="scrollToIndex(Number(($event.target as HTMLSelectElement).value))"
                            class="min-w-0 flex-1 truncate rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 outline-none focus:ring-2 focus:ring-primary-500"
                            aria-label="ไปที่บัตรนักเรียน">
                            <option v-for="(student, index) in sortedStudents" :key="student.uid" :value="index">
                                {{ studentLabel(student, index) }}
                            </option>
                        </select>

                        <button @click="stepCard(1)" :disabled="currentIndex >= sortedStudents.length - 1"
                            class="shrink-0 rounded-lg border border-gray-200 dark:border-gray-600 p-2 text-gray-600 dark:text-gray-300 transition hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-40"
                            aria-label="บัตรถัดไป">
                            <Icon icon="fluent:chevron-down-24-regular" class="h-4 w-4" />
                        </button>

                        <span class="shrink-0 whitespace-nowrap text-xs font-semibold text-gray-500 dark:text-gray-400 tabular-nums">
                            {{ currentIndex + 1 }}/{{ sortedStudents.length }}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 pb-6">
                    <div v-for="student in sortedStudents" :key="student.uid" :id="`card-${student.uid}`" class="scroll-mt-20">
                        <StudentCardItem
                            :studentInfo="student"
                            :school="school"
                            :canManage="canManageRoster"
                            :canRequest="canRequest"
                            :canEdit="canEditCard"
                            :selectMode="selectMode"
                            :selected="!!student.student_id && selectedIds.has(student.student_id)"
                            :updateCard="updateCard"
                            :uploadPhoto="uploadPhoto"
                            :deletePhoto="deletePhoto"
                            @transfer="openTransferModal"
                            @remove="openRemoveModal"
                            @request="openRequestModal"
                            @cancel-request="cancelRequest"
                            @toggle-select="toggleSelect"
                            @updated="fetchStudents"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <AddStudentModal
            :open="showAddModal"
            :classroomName="classroomName"
            :searchStudents="searchAvailableStudents"
            :addStudent="addStudentWithConfirm"
            @close="showAddModal = false"
            @added="handleAdded"
        />
        <TransferStudentModal
            :open="showTransferModal"
            :studentName="selectedStudentName"
            :currentClassroomName="classroomName"
            :classrooms="context?.available_classrooms || []"
            @close="showTransferModal = false"
            @confirm="handleTransferConfirm"
        />
        <RemoveStudentModal
            :open="showRemoveModal"
            :studentName="selectedStudentName"
            :classroomName="classroomName"
            @close="showRemoveModal = false"
            @confirm="handleRemoveConfirm"
        />
        <RequestCardModal
            :open="showRequestModal"
            :student="selectedStudent"
            :defaultRequesterName="context?.homeroom_teacher_name || null"
            :submitRequest="submitCardRequest"
            @close="showRequestModal = false"
            @submitted="handleRequestSubmitted"
        />
        <BulkRequestCardModal
            :open="showBulkRequestModal"
            :students="selectedStudents"
            :defaultRequesterName="context?.homeroom_teacher_name || null"
            :submitBulk="submitBulkCardRequests"
            @close="showBulkRequestModal = false"
            @submitted="handleBulkSubmitted"
        />

        <!-- แถบสรุปลอยด้านล่างระหว่างโหมดเลือกหลายคน -->
        <Transition name="fade">
            <div v-if="selectMode" class="fixed bottom-4 left-1/2 -translate-x-1/2 z-40 w-[calc(100%-2rem)] max-w-xl">
                <div class="bg-gray-900/95 text-white rounded-2xl shadow-2xl px-5 py-3 flex items-center justify-between gap-3 backdrop-blur">
                    <div class="flex items-center gap-2 text-sm">
                        <Icon icon="fluent:checkmark-circle-24-filled" class="w-5 h-5 text-primary-400" />
                        เลือกแล้ว <span class="font-bold text-primary-300">{{ selectedIds.size }}</span> คน
                    </div>
                    <button @click="showBulkRequestModal = true" :disabled="!selectedIds.size"
                        class="px-4 py-2 text-sm font-semibold bg-primary-600 rounded-xl hover:bg-primary-500 transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-1.5">
                        <Icon icon="fluent:send-24-regular" class="w-4 h-4" />
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
