<script setup>
import { ref, reactive, computed } from 'vue'
import QRCodeVue3 from "qrcode-vue3"
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue'
import { DEFAULT_STUDENT_CARD_SCHOOL } from '~/constants/studentCard'

const props = defineProps({
    studentInfo: { type: Object, required: true },
    canManage: { type: Boolean, default: false },
    canRequest: { type: Boolean, default: false },
    canEdit: { type: Boolean, default: true },
    selectMode: { type: Boolean, default: false },
    selected: { type: Boolean, default: false },
    school: { type: Object, default: () => ({}) },
    // ตัวเรียก API — ถ้าไม่ส่งมาจะใช้เส้นทางสาธารณะเดิม (หน้าชั่วคราว)
    updateCard: { type: Function, default: null },
    uploadPhoto: { type: Function, default: null },
    deletePhoto: { type: Function, default: null },
})

const emit = defineEmits(['transfer', 'remove', 'request', 'cancel-request', 'toggle-select', 'updated'])

const school = computed(() => ({ ...DEFAULT_STUDENT_CARD_SCHOOL, ...(props.school || {}) }))

// คำร้องทำบัตรที่ค้างอยู่ของนักเรียนคนนี้ (มาจาก active_card_request ใน API)
const activeRequest = computed(() => props.studentInfo.active_card_request || null)

const requestStatusLabel = computed(() => {
    switch (activeRequest.value?.status) {
        case 'pending': return 'ส่งคำร้องแล้ว • รอตรวจสอบ'
        case 'approved': return 'อนุมัติแล้ว • รอจัดทำบัตร'
        case 'in_progress': return 'กำลังจัดทำบัตร'
        default: return ''
    }
})

const requestStatusClass = computed(() => {
    switch (activeRequest.value?.status) {
        case 'pending': return 'bg-amber-50 text-amber-700 border-amber-200'
        case 'approved': return 'bg-blue-50 text-blue-700 border-blue-200'
        case 'in_progress': return 'bg-violet-50 text-violet-700 border-violet-200'
        default: return 'bg-gray-50 text-gray-600 border-gray-200'
    }
})

const requestedAtText = computed(() => {
    if (!activeRequest.value?.requested_at) return ''
    return new Date(activeRequest.value.requested_at).toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: 'numeric' })
})

const isActionMenuOpen = ref(false)

const config = useRuntimeConfig()
const apiBase = config.public.apiBase

const isEditStudentPhoto = ref(false)
const isDeletingStudentPhoto = ref(false)
const isSaving = ref(false)
const isEditModalOpen = ref(false)

const editForm = reactive({
    id: props.studentInfo.id,
    student_number: props.studentInfo.student_number,
    title_name: props.studentInfo.title_name || '',
    first_name_thai: props.studentInfo.first_name_thai || '',
    last_name_thai: props.studentInfo.last_name_thai || '',
    full_name_thai: props.studentInfo.full_name_thai || [props.studentInfo.title_name, props.studentInfo.first_name_thai, props.studentInfo.last_name_thai].filter(Boolean).join(' '),
    first_name_english: props.studentInfo.first_name_english || '',
    last_name_english: props.studentInfo.last_name_english || '',
    level: props.studentInfo.class_level < 4 ? 'มัธยมศึกษาตอนต้น' : 'มัธยมศึกษาตอนปลาย',
    birth_date: props.studentInfo.birth_date,
    national_id: props.studentInfo.national_id,
})

const formattedIdNumber = computed(() => {
    if (!editForm.national_id) return ''
    const s = String(editForm.national_id).replace(/\D/g, '')
    if (s.length !== 13) return s
    return s.replace(/(\d)(\d{4})(\d{5})(\d{2})(\d{1})/, '$1-$2-$3-$4-$5')
})

const displayFullNameThai = computed(() => {
    if (editForm.full_name_thai?.trim()) return editForm.full_name_thai.trim()
    return [editForm.title_name, editForm.first_name_thai, editForm.last_name_thai].filter(p => p?.trim()).join(' ')
})

const fileInput = ref(null)
const previewImage = ref(null)
const tempPhoto = ref(props.studentInfo.profile_image || null)

// รูปที่เพิ่งอัพโหลดในหน้านี้ — เก็บแยกแทนการเขียนทับ props.studentInfo
// (การแก้ props ตรง ๆ ทำให้ parent กับ component ไม่ตรงกันเงียบ ๆ)
const uploadedImageUrl = ref(null)

const studentImageUrl = computed(() => {
    if (previewImage.value) return previewImage.value
    return uploadedImageUrl.value || props.studentInfo.profile_image_url || null
})

const logoUrl = computed(() => school.value.logo_url || `${apiBase}/storage/jsm_logo.png`)

const cardBgStyle = computed(() => ({
    background: `url('${apiBase}/storage/images/std_card_bg2.png') center center / cover no-repeat`
}))

const handlePhotoUpload = (event) => {
    const file = event.target.files[0]
    if (!file) return
    const reader = new FileReader()
    reader.onload = (e) => {
        previewImage.value = e.target.result
        isEditStudentPhoto.value = true
        handlePhotoUploadToServer(props.studentInfo.id, props.studentInfo.student_number, file)
    }
    reader.readAsDataURL(file)
}

const handlePhotoUploadToServer = async (id, studentNumber, file) => {
    if (!file || !studentNumber) return
    const formData = new FormData()
    formData.append('photo', file)
    try {
        const response = props.uploadPhoto
            ? await props.uploadPhoto(formData, props.studentInfo)
            : await $fetch(`${apiBase}/api/student-card/admin/upload-photo/${id}`, {
                method: 'POST',
                body: formData,
            })
        if (response.success) {
            Swal.fire({ icon: 'success', title: 'อัพโหลดรูปภาพสำเร็จ', text: response.message, confirmButtonText: 'ตกลง' })
            if (response.photo) {
                tempPhoto.value = response.photo
            }
            if (response.path) {
                uploadedImageUrl.value = response.path
            }
            emit('updated', props.studentInfo)
        } else {
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: response.message || 'ไม่สามารถอัพโหลดรูปภาพได้', confirmButtonText: 'ตกลง' })
        }
    } catch {
        Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: 'ไม่สามารถอัพโหลดรูปภาพได้', confirmButtonText: 'ตกลง' })
    } finally {
        isEditStudentPhoto.value = false
        if (fileInput.value) fileInput.value.value = ''
    }
}

const triggerFileInput = () => fileInput.value?.click()

const handleSubmit = async () => {
    const payload = {
        national_id: editForm.national_id,
        student_number: editForm.student_number,
        title_name: editForm.title_name,
        first_name_thai: editForm.first_name_thai,
        last_name_thai: editForm.last_name_thai,
        first_name_english: editForm.first_name_english,
        last_name_english: editForm.last_name_english,
        birth_date: editForm.birth_date,
    }

    try {
        isSaving.value = true
        const response = props.updateCard
            ? await props.updateCard(payload, props.studentInfo)
            : await $fetch(`${apiBase}/api/student-card/public-update/${props.studentInfo.class_level}/${props.studentInfo.class_section}/${editForm.id}`, {
                method: 'PUT',
                body: payload,
            })
        if (response.success) {
            editForm.full_name_thai = [editForm.title_name, editForm.first_name_thai, editForm.last_name_thai].filter(p => p?.trim()).join(' ')
            Swal.fire({ title: 'บันทึกข้อมูลสำเร็จ', icon: 'success', confirmButtonText: 'ตกลง' })
            isEditModalOpen.value = false
            emit('updated', props.studentInfo)
        }
    } catch (error) {
        Swal.fire({
            title: 'เกิดข้อผิดพลาด',
            text: error?.data?.message || 'ไม่สามารถบันทึกข้อมูลได้',
            icon: 'error',
            confirmButtonText: 'ตกลง',
        })
    } finally {
        isSaving.value = false
    }
}

const handleDeletePhoto = async () => {
    const result = await Swal.fire({
        title: 'ลบรูปภาพ', text: 'คุณต้องการลบรูปภาพนี้หรือไม่?', icon: 'warning',
        showCancelButton: true, confirmButtonText: 'ลบ', cancelButtonText: 'ยกเลิก',
    })
    if (!result.isConfirmed) return
    try {
        isDeletingStudentPhoto.value = true
        const response = props.deletePhoto
            ? await props.deletePhoto(props.studentInfo)
            : await $fetch(`${apiBase}/api/student-card/${props.studentInfo.id}/photo`, { method: 'DELETE' })
        if (response.success) {
            previewImage.value = null
            tempPhoto.value = null
            uploadedImageUrl.value = null
            Swal.fire('ลบสำเร็จ', '', 'success')
            emit('updated', props.studentInfo)
        } else {
            Swal.fire('เกิดข้อผิดพลาด', response.message || '', 'error')
        }
    } catch (error) {
        Swal.fire('เกิดข้อผิดพลาด', error?.data?.message || 'ไม่สามารถลบรูปภาพได้', 'error')
    } finally {
        isDeletingStudentPhoto.value = false
    }
}

const studentPrefixName = (prefix) => {
    if (!prefix) return ''
    if (prefix === 'เด็กหญิง' || prefix === 'นางสาว') return 'Ms.'
    if (prefix === 'เด็กชาย' || prefix === 'นาย') return 'Mr.'
    return ''
}
</script>

<template>
    <div class="flex justify-center">
        <div class="w-full max-w-[680px] bg-white rounded-3xl border border-gray-200 shadow-sm hover:shadow-md hover:border-gray-300 transition-shadow p-3 sm:p-4">
            <div :style="cardBgStyle"
                class="w-full aspect-[1.95/1.20] relative overflow-hidden rounded-2xl shadow-lg border border-gray-300">

            <!-- Top Section -->
            <div class="h-[20%] relative" style="background: linear-gradient(135deg, transparent 40%, #4a90e2 0%);">
                <div class="absolute -left-2 md:-left-3 -top-[16px] sm:-top-[28px] md:-top-[22px] w-[22%] aspect-square rounded-full flex items-center justify-center">
                    <img :src="logoUrl" alt="School Logo" class="w-[56%] h-[56%] object-cover rounded-full">
                </div>
                <div class="absolute left-[16%] top-[10%] sm:top-2">
                    <div class="text-[3.8vw] md:text-[28px] font-semibold md:font-bold text-gray-800">{{ school.name_th }}</div>
                    <div class="text-[2.4vw] sm:text-[2.5vw] md:text-[16px] -mt-1 sm:-mt-2.5 md:-mt-2 text-gray-800 tracking-wider">{{ school.name_en }}</div>
                    <div class="text-[2.4vw] md:text-sm -mt-0.5 sm:-mt-2 md:-mt-1 text-gray-800">{{ school.address }}</div>
                </div>
                <div v-if="canEdit" @click="isEditModalOpen = true" class="absolute z-50 top-0 right-2 text-gray-700 bg-gray-200/60 p-2 rounded-full shadow-md cursor-pointer">
                    <Icon icon="dashicons:edit" width="20" height="20" />
                </div>
                <div v-if="canManage || canRequest || activeRequest?.status === 'pending'" class="absolute z-50 top-0 right-12">
                    <div @click="isActionMenuOpen = !isActionMenuOpen"
                        class="text-gray-700 bg-gray-200/60 p-2 rounded-full shadow-md cursor-pointer">
                        <Icon icon="heroicons:ellipsis-vertical" width="20" height="20" />
                    </div>
                    <div v-if="isActionMenuOpen"
                        class="absolute right-0 mt-1 w-44 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden">
                        <button v-if="canManage" @click="isActionMenuOpen = false; emit('transfer', studentInfo)"
                            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-blue-50 text-left">
                            <Icon icon="heroicons:arrow-right-circle" class="w-4 h-4 text-blue-600" />
                            ย้ายห้อง
                        </button>
                        <button v-if="canManage" @click="isActionMenuOpen = false; emit('remove', studentInfo)"
                            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 text-left">
                            <Icon icon="heroicons:user-minus" class="w-4 h-4" />
                            นำออกจากห้อง
                        </button>
                        <button v-if="canRequest && !activeRequest" @click="isActionMenuOpen = false; emit('request', studentInfo)"
                            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-blue-600 hover:bg-blue-50 text-left">
                            <Icon icon="heroicons:credit-card" class="w-4.5 h-4.5 text-blue-600" />
                            ขอทำบัตรใหม่
                        </button>
                        <div v-else-if="activeRequest"
                            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-amber-700 bg-amber-50/60">
                            <Icon icon="heroicons:clock" class="w-4 h-4" />
                            {{ requestStatusLabel }}
                            <button v-if="activeRequest.status === 'pending'" @click="isActionMenuOpen = false; emit('cancel-request', studentInfo)"
                                class="ml-auto text-xs text-red-600 hover:underline">ยกเลิกคำขอทำบัตร</button>
                        </div>
                    </div>
                </div>
                <div class="absolute z-10 top-6 md:top-[52px] right-2 text-white bg-blue-700 px-2 py-1 text-end rounded-md">
                    <div class="text-[1.8vw] sm:text-[14px] font-semibold">บัตรประจำตัวนักเรียน</div>
                    <div class="text-[1.4vw] sm:text-[10px] opacity-90">STUDENT CARD</div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="flex p-[3%] gap-[3%] h-[80%]">
                <!-- Photo -->
                <div class="w-[30%] h-[75%] rounded-xl overflow-hidden flex-shrink-0">
                    <input type="file" ref="fileInput" @change="handlePhotoUpload" accept="image/*" class="hidden" />
                    <div v-if="previewImage || tempPhoto" class="w-full h-full relative">
                        <img :src="studentImageUrl" alt="Student Photo" class="w-full h-full object-fill" />
                        <button v-if="canEdit" class="absolute bottom-2 right-2 bg-white p-1 rounded-full shadow-md cursor-pointer focus:outline-none" @click="triggerFileInput" aria-label="เปลี่ยนรูป">
                            <Icon :icon="isEditStudentPhoto ? 'eos-icons:bubble-loading' : 'heroicons:pencil-solid'" class="w-5 h-5 text-gray-600" />
                        </button>
                        <button v-if="canEdit" class="absolute bottom-2 left-2 bg-red-500 p-1 rounded-full shadow-md cursor-pointer focus:outline-none" @click="handleDeletePhoto" aria-label="ลบรูป">
                            <Icon :icon="isDeletingStudentPhoto ? 'eos-icons:bubble-loading' : 'heroicons:trash-solid'" class="w-5 h-5 text-white" />
                        </button>
                    </div>
                    <div v-else class="w-full h-full flex items-center justify-center bg-gray-300"
                        :class="canEdit ? 'cursor-pointer' : ''" @click="canEdit && triggerFileInput()">
                        <Icon icon="tabler:photo-plus" class="w-10 h-10 text-gray-600/60" />
                    </div>
                </div>

                <!-- Info -->
                <div class="flex-1 pt-0.5 relative">
                    <div class="flex items-baseline">
                        <span class="w-[25%] text-[2.2vw] sm:text-md md:text-lg font-medium text-gray-700">ชื่อ</span>
                        <span class="mx-[1%] text-[2.2vw] sm:text-sm md:text-lg text-gray-700">:</span>
                        <span class="flex-1 text-[2.4vw] sm:text-sm md:text-lg font-semibold text-gray-800">{{ displayFullNameThai }}</span>
                    </div>
                    <div class="flex items-baseline -mt-1">
                        <span class="w-[25%] text-[2vw] sm:text-xs font-normal text-gray-600">Name</span>
                    </div>
                    <div class="flex items-baseline -mt-3 sm:-mt-4 md:-mt-5">
                        <span class="w-[25%]"></span>
                        <span class="mx-[1%] text-transparent">:</span>
                        <span class="flex-1 text-[1.8vw] sm:text-sm font-normal text-gray-800">
                            {{ editForm.first_name_english ? studentPrefixName(editForm.title_name) : '' }} {{ [editForm.first_name_english, editForm.last_name_english].filter(Boolean).join(' ') }}
                        </span>
                    </div>
                    <div class="flex items-baseline mt-0.5">
                        <span class="w-[25%] text-[2.2vw] sm:text-sm md:text-lg font-medium text-gray-700">รหัสประจำตัว</span>
                        <span class="mx-[1%] text-[2.2vw] sm:text-sm md:text-lg text-gray-700">:</span>
                        <span class="flex-1 text-[2.4vw] sm:text-sm md:text-lg font-semibold text-gray-800">{{ editForm.student_number }}</span>
                    </div>
                    <div class="flex items-baseline -mt-1">
                        <span class="w-[25%] text-[2vw] sm:text-xs font-normal text-gray-600">Student ID</span>
                    </div>
                    <div class="flex items-baseline mt-0.5">
                        <span class="w-[25%] text-[2.2vw] sm:text-sm font-medium text-gray-700">เลขบัตรประชาชน</span>
                        <span class="mx-[1%] text-[2.2vw] sm:text-sm md:text-lg text-gray-700">:</span>
                        <span class="flex-1 text-[2.4vw] sm:text-sm md:text-lg font-semibold text-gray-800">{{ formattedIdNumber }}</span>
                    </div>
                    <div class="flex items-baseline -mt-1">
                        <span class="w-[25%] text-[2vw] sm:text-xs font-normal text-gray-600">ID Card No.</span>
                    </div>
                    <div class="flex items-baseline mt-0.5">
                        <span class="w-[25%] text-[2.2vw] sm:text-sm md:text-lg font-medium text-gray-700">ระดับ</span>
                        <span class="mx-[1%] text-[2.2vw] sm:text-sm md:text-lg text-gray-700">:</span>
                        <span class="flex-1 text-[2.4vw] sm:text-sm md:text-lg font-semibold text-gray-800">{{ editForm.level }}</span>
                    </div>
                    <div class="flex items-baseline -mt-1.5 sm:-mt-2 md:-mt-2.5">
                        <span class="w-[25%] text-[2vw] sm:text-xs font-normal text-gray-600">Level</span>
                        <span class="mx-[1%] text-transparent">:</span>
                        <span class="flex-1 text-[1.4vw] sm:text-[12px] font-normal text-gray-800">
                            {{ studentInfo.class_level < 4 ? 'LOWER SECONDARY' : 'UPPER SECONDARY' }}
                        </span>
                    </div>
                    <div class="flex items-baseline mt-0.5">
                        <span class="w-[25%] text-[2.2vw] sm:text-sm md:text-lg font-medium text-gray-700">วันเกิด</span>
                        <span class="mx-[1%] text-[2.2vw] sm:text-sm md:text-lg text-gray-700">:</span>
                        <!-- นักเรียนบางคนยังไม่มีวันเกิดในระบบ บอกให้ชัดว่าให้กดแก้ไขเติม ไม่ใช่แสดงขีดเปล่าๆ -->
                        <span v-if="editForm.birth_date"
                            class="flex-1 text-[2.4vw] sm:text-sm md:text-lg font-semibold text-gray-800">
                            {{ new Date(editForm.birth_date).toLocaleDateString('th-TH', { day: '2-digit', month: '2-digit', year: 'numeric' }) }}
                        </span>
                        <button v-else-if="canEdit" type="button" @click="isEditModalOpen = true"
                            class="flex-1 text-left text-[2.4vw] sm:text-sm md:text-lg font-semibold text-amber-600 underline decoration-dotted underline-offset-2 hover:text-amber-700">
                            ยังไม่ระบุ
                        </button>
                        <span v-else class="flex-1 text-[2.4vw] sm:text-sm md:text-lg font-semibold text-gray-400">ยังไม่ระบุ</span>
                    </div>
                    <div class="flex items-baseline -mt-1.5 sm:-mt-2 md:-mt-2.5">
                        <span class="w-[25%] text-[2vw] sm:text-xs font-normal text-gray-600">Date of Birth</span>
                        <span class="mx-[1%] text-transparent">:</span>
                        <span class="flex-1 text-[1.4vw] sm:text-[12px] font-normal text-gray-800">
                            {{ editForm.birth_date ? new Date(editForm.birth_date).toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: 'numeric' }) : '' }}
                        </span>
                    </div>
                    <div class="flex items-baseline mt-0.5">
                        <span class="w-[25%] text-[2.2vw] sm:text-sm md:text-lg font-medium text-gray-700">วันหมดอายุ</span>
                        <span class="mx-[1%] text-[2.2vw] sm:text-sm md:text-lg text-gray-700">:</span>
                        <span class="flex-1 text-[2.4vw] sm:text-sm md:text-lg font-semibold text-gray-800">
                            {{ studentInfo.card_expiry_date ? new Date(studentInfo.card_expiry_date).toLocaleDateString('th-TH', { day: '2-digit', month: '2-digit', year: 'numeric' }) : '-' }}
                        </span>
                    </div>
                    <div class="flex items-baseline -mt-1.5 sm:-mt-2 md:-mt-2.5">
                        <span class="w-[25%] text-[2vw] sm:text-xs font-normal text-gray-600">Expiry Date</span>
                        <span class="mx-[1%] text-transparent">:</span>
                        <span class="flex-1 text-[1.4vw] sm:text-[12px] font-normal text-gray-800">
                            {{ studentInfo.card_expiry_date ? new Date(studentInfo.card_expiry_date).toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: 'numeric' }) : '-' }}
                        </span>
                    </div>
                </div>

                <!-- QR Code -->
                <div class="absolute bottom-[5%] right-[3%] w-[15%] aspect-square">
                    <div class="w-full h-full bg-white flex items-center justify-center rounded-lg shadow-md">
                        <QRCodeVue3 :value="studentInfo.student_number || ''"
                            :cornersSquareOptions="{ type: 'extra-rounded', color: '#000' }"
                            :dotsOptions="{ type: 'dots', color: '#000' }"
                            :cornersDotOptions="{ type: 'square', color: '#000' }" />
                    </div>
                </div>

                <!-- Bottom bar -->
                <div class="absolute bottom-0 left-0 w-full h-[2.5%] rounded-b-2xl"
                    style="background: linear-gradient(135deg, #4a90e2 72%, transparent 0%);" />
            </div>

            <!-- Edit Modal -->
            <Dialog as="div" @close="isEditModalOpen = false" :open="isEditModalOpen" class="relative z-50">
                <div class="fixed inset-0 bg-black/30" aria-hidden="true" />
                <div class="fixed inset-0 flex items-center justify-center p-4">
                    <DialogPanel class="w-full max-w-md bg-white rounded-lg p-6 shadow-xl">
                        <DialogTitle class="text-lg font-medium text-gray-900 mb-4">แก้ไขข้อมูลนักเรียน</DialogTitle>
                        <form @submit.prevent="handleSubmit" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">รหัสนักเรียน</label>
                                <input type="text" v-model="editForm.student_number" class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">คำนำหน้าชื่อ</label>
                                <select id="student-title-name" v-model="editForm.title_name" class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                                    <option value="">ไม่ระบุ</option>
                                    <option value="เด็กชาย">เด็กชาย</option>
                                    <option value="เด็กหญิง">เด็กหญิง</option>
                                    <option value="นาย">นาย</option>
                                    <option value="นางสาว">นางสาว</option>
                                    <option value="นาง">นาง</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">ชื่อ</label>
                                <input type="text" v-model="editForm.first_name_thai" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">นามสกุล</label>
                                <input type="text" v-model="editForm.last_name_thai" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ (อังกฤษ)</label>
                                <input type="text" v-model="editForm.first_name_english" class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">นามสกุล (อังกฤษ)</label>
                                <input type="text" v-model="editForm.last_name_english" class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">เลขประจำตัวประชาชน</label>
                                <input type="text" v-model="editForm.national_id" class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">วันเกิด</label>
                                <input type="date" v-model="editForm.birth_date" class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" />
                            </div>
                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" @click="isEditModalOpen = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">ยกเลิก</button>
                                <button type="submit" :disabled="isSaving" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50">
                                    {{ isSaving ? 'กำลังบันทึก...' : 'บันทึก' }}
                                </button>
                            </div>
                        </form>
                    </DialogPanel>
                </div>
            </Dialog>
            </div>

            <!-- Action footer — ระบุชัดว่าปุ่มปฏิบัติการเป็นของบัตรใบนี้ -->
            <div v-if="canManage || canRequest || activeRequest?.status === 'pending'"
                class="mt-3 pt-3 border-t border-dashed border-gray-200 flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-1.5 text-xs text-gray-500 mr-auto min-w-0">
                    <Icon icon="heroicons:identification" class="w-4 h-4 text-gray-400 flex-shrink-0" />
                    <span class="truncate">จัดการบัตรของ <span class="font-semibold text-gray-700">{{ displayFullNameThai }}</span></span>
                </div>
                <!-- โหมดเลือกหลายคน: checkbox แทนปุ่มรายคน -->
                <template v-if="selectMode && canRequest">
                    <label v-if="!activeRequest"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg border cursor-pointer transition shadow-sm select-none"
                        :class="selected ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-blue-700 border-blue-200 hover:bg-blue-50'">
                        <input type="checkbox" class="sr-only" :checked="selected" @change="emit('toggle-select', studentInfo)" />
                        <Icon :icon="selected ? 'heroicons:check-circle-solid' : 'heroicons:plus-circle'" class="w-4 h-4" />
                        {{ selected ? 'เลือกแล้ว' : 'เลือกส่งคำร้อง' }}
                    </label>
                    <span v-else class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded-lg border"
                        :class="requestStatusClass">
                        <Icon icon="heroicons:clock" class="w-4 h-4" />
                        {{ requestStatusLabel }}
                    </span>
                </template>

                <template v-else>
                    <button v-if="canManage" @click="emit('transfer', studentInfo)"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-blue-700 bg-white border border-blue-200 rounded-lg hover:bg-blue-50 transition shadow-sm">
                        <Icon icon="heroicons:arrow-right-circle" class="w-4 h-4" />
                        ย้ายห้อง
                    </button>
                    <button v-if="canManage" @click="emit('remove', studentInfo)"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-red-600 bg-white border border-red-200 rounded-lg hover:bg-red-50 transition shadow-sm">
                        <Icon icon="heroicons:user-minus" class="w-4 h-4" />
                        นำออกจากห้อง
                    </button>
                    <button v-if="canRequest && !activeRequest" @click="emit('request', studentInfo)"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition shadow-sm">
                        <Icon icon="heroicons:credit-card" class="w-4 h-4" />
                        ขอทำบัตรใหม่
                    </button>
                    <span v-else-if="activeRequest"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded-lg border"
                        :class="requestStatusClass"
                        :title="requestedAtText ? `ส่งคำร้องเมื่อ ${requestedAtText}` : ''">
                        <Icon icon="heroicons:clock" class="w-4 h-4" />
                        {{ requestStatusLabel }}
                        <span v-if="requestedAtText" class="opacity-70">({{ requestedAtText }})</span>
                        <button v-if="activeRequest.status === 'pending'" @click="emit('cancel-request', studentInfo)"
                            class="ml-1 text-red-600 hover:underline">ยกเลิกคำขอทำบัตร</button>
                    </span>
                </template>
            </div>
        </div>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@100..900&display=swap');
* { font-family: "Noto Sans Thai", sans-serif; }
</style>
