<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import QRCodeVue3 from "qrcode-vue3";
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue'
// import html2canvas from 'html2canvas' // เพิ่มบรรทัดนี้
// import domtoimage from 'dom-to-image-more'
// Props definition
const props = defineProps({
    studentInfo: {
        type: Object,
        required: true,
    }
})

// Emit definition
const emit = defineEmits(['update', 'upload-image'])

// Reactive state
const isEditStudentPhoto = ref(false)
const isDeletingStudentPhoto = ref(false)
const isSaving = ref(false)
const isEditModalOpen = ref(false)
// const editForm = reactive({ ...props.studentInfo })
const editForm = reactive({
    id: props.studentInfo.id,
    student_number: props.studentInfo.student_number,
    title_name: props.studentInfo.title_name || '',
    first_name_thai: props.studentInfo.first_name_thai || '',
    last_name_thai: props.studentInfo.last_name_thai || '',
    full_name_thai: props.studentInfo.full_name_thai || ((props.studentInfo.title_name || '') + ' ' + (props.studentInfo.first_name_thai || '') + ' ' + (props.studentInfo.last_name_thai || '')).trim(),
    first_name_english: props.studentInfo.first_name_english || '',
    level: props.studentInfo.class_level < 4 ? "มัธยมศึกษาตอนต้น" : "มัธยมศึกษาตอนปลาย",
    birth_date: props.studentInfo.birth_date,
    national_id: props.studentInfo.national_id,
    card_expiry_date: props.studentInfo.card_expiry_date
})

const formattedIdNumber = computed(() => {
    if (!editForm.national_id) return '';
    // Convert to string and ensure it's a valid value
    const idString = String(editForm.national_id);
    if (idString.length !== 13) return idString;

    // Remove non-digits and format
    const id = idString.replace(/\D/g, '');
    return id.replace(/(\d)(\d{4})(\d{5})(\d{2})(\d{1})/, '$1-$2-$3-$4-$5');
})

const displayFullNameThai = computed(() => {
    if (editForm.full_name_thai && editForm.full_name_thai.trim()) {
        return editForm.full_name_thai.trim();
    }
    // สร้างชื่อเต็มจาก components
    const parts = [
        editForm.title_name?.trim(),
        editForm.first_name_thai?.trim(),
        editForm.last_name_thai?.trim()
    ].filter(part => part && part !== '');
    
    return parts.join(' ');
})

const fileInput = ref(null)
const previewImage = ref(null)
const tempPhoto = ref(props.studentInfo.profile_image || null)

// Computed properties
const studentImageUrl = computed(() => {
    if (previewImage.value) {
        return previewImage.value
    }
    if (tempPhoto.value) {
        return `/storage/images/students/${props.studentInfo.class_level}/${props.studentInfo.class_section}/${tempPhoto.value}`
    }
    return null
})


// Methods
const handlePhotoUpload = (event) => {
    const file = event.target.files[0]
    if (!file) return

    // Preview image
    const reader = new FileReader()
    reader.onload = (e) => {
        previewImage.value = e.target.result
        isEditStudentPhoto.value = true
        // upload photo to server
        handlePhotoUploadToServer(props.studentInfo.id, props.studentInfo.student_number, file)
        
    }
    reader.readAsDataURL(file)
}

const handlePhotoUploadToServer = async (id, studentId, file) => {
    if (!file || !studentId) return

    const formData = new FormData()
    formData.append('photo', file)
    formData.append('_method', 'patch')

    try {
        const response = await axios.post(`/student-card/admin/upload-photo/${id}`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })

        if (response.data.success) {
            Swal.fire({
                icon: 'success',
                title: 'อัพโหลดรูปภาพสำเร็จ',
                text: response.data.message,
                confirmButtonText: 'ตกลง'
            })
            // tempPhoto.value = response.data.photo // Update tempPhoto with the new photo
        } else {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: response.data.message || 'ไม่สามารถอัพโหลดรูปภาพได้',
                confirmButtonText: 'ตกลง'
            })
        }
    } catch (error) {
        console.error('Upload failed:', error)
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่สามารถอัพโหลดรูปภาพได้',
            confirmButtonText: 'ตกลง'
        })
    } finally {
        isEditStudentPhoto.value = false
        fileInput.value.value = '' // Clear the file input
    }
}

const triggerFileInput = () => {
    fileInput.value.click()
}

// Add method to handle form submission
const handleSubmit = async () => {
    try {
        isSaving.value = true

        const payload = {
            national_id: editForm.national_id,
            student_number: editForm.student_number,
            title_name: editForm.title_name,
            first_name_thai: editForm.first_name_thai,
            last_name_thai: editForm.last_name_thai,
            first_name_english: editForm.first_name_english,
            birth_date: editForm.birth_date // เพิ่มฟิลด์นี้
        }

        const response = await axios.put(`/student-card/update/${editForm.id}`, payload, {
            headers: {
                'Content-Type': 'application/json'
            }
        });

        if (response.data.success) {
            Swal.fire({
                title: 'บันทึกข้อมูลสำเร็จ',
                icon: 'success',
                confirmButtonText: 'ตกลง'
            })
            isEditModalOpen.value = false

        }
    } catch (error) {
        Swal.fire({
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่สามารถบันทึกข้อมูลได้',
            icon: 'error',
            confirmButtonText: 'ตกลง'
        })
    } finally {
        isSaving.value = false
    }
}

const formatDate = (dateString) => {
    try {
        const date = new Date(dateString);
        
        // Check if date is invalid
        if (isNaN(date.getTime())) {
            return '-';
        }

        return date.toLocaleDateString('th-TH', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    } catch (error) {
        console.error('Error formatting date:', error);
        return '-';
    }
}




</script>

<template>
    <div class="flex justify-center items-center font-sarabun">
        <div class="student-card-container w-full max-w-[640px] aspect-[1.95/1.20] relative overflow-hidden rounded-2xl shadow-lg border border-gray-300">
            <!-- Top Section -->
            <div class="h-[20%] bg-gradient-to-br from-gray-50 to-gray-200 relative"
                style="background: linear-gradient(135deg, transparent 40%, #4a90e2 0%);">
                <!-- School Logo - ใช้ % แทน px -->
                <div
                    class="absolute -left-2  md:-left-3 -top-[16px] sm:-top-[28px] md:-top-[22px] w-[22%] aspect-square rounded-full border-1 border-white flex items-center justify-center">
                    <img src="/storage/jsm_logo.png" alt="School Logo"
                        class="w-[56%] h-[56%] object-cover rounded-full">
                </div>

                <!-- School Information - ปรับ font size ให้ responsive -->
                <div class="absolute left-[16%] top-[10%] sm:top-2 ">
                    <div class="text-[3.8vw] md:text-[28px] font-semibold md:font-bold text-gray-800">
                        โรงเรียนจริยธรรมศึกษามูลนิธิ
                    </div>
                    <div class="text-[2.4vw] sm:text-[2.5vw] md:text-[16px] -mt-1 sm:-mt-2.5 md:-mt-2 font-base text-gray-800 tracking-wider">
                        CHARIYATHAMSUKSA FOUNDATION SCHOOL
                    </div>
                    <div class="text-[2.4vw] md:text-sm -mt-0.5 sm:-mt-2 md:-mt-1 text-gray-800">
                        148 ม.8 ต.สะกอม อ.จะนะ จ.สงขลา 90130 โทร.081-5412281
                    </div>
                </div>


                <!--Open Edit Modal Button -->
                <!-- <div @click="isEditModalOpen = true"
                    class="absolute z-50 top-0 right-2 text-white bg-gray-200/60 p-2 text-center rounded-full shadow-md cursor-pointer">
                    <Icon icon="dashicons:edit" width="20" height="20" />
                </div> -->
                <!-- Card Label - ปรับขนาดตาม % -->
                <div class="absolute z-10 top-6 md:top-[52px] right-2 text-white bg-blue-700 px-2 py-1 text-end rounded-md">
                    <div class="text-[1.8vw] sm:text-[14px] font-semibold">บัตรประจำตัวนักเรียน</div>
                    <div class="text-[1.4vw] sm:text-[10px] opacity-90">STUDENT CARD</div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="flex p-[3%] gap-[3%] h-[80%]">
                <!-- Photo Section -->
                <div class="w-[30%] h-[75%] rounded-xl overflow-hidden flex-shrink-0">

                    <!-- Hidden File Input -->
                    <input type="file" ref="fileInput" @change="handlePhotoUpload" accept="image/*" class="hidden" />

                    <!-- Photo Display -->
                    <div v-if="previewImage || tempPhoto" class="w-full h-full relative">
                        <img :src="studentImageUrl" alt="Student Photo" class="w-full h-full object-cover" />
                        <!-- <div class="absolute bottom-2 right-2 bg-white p-1 rounded-full shadow-md cursor-pointer"
                            @click="triggerFileInput">
                            <Icon icon="heroicons:pencil-solid" class="w-5 h-5 text-gray-600" />
                        </div> -->
   
                        <!-- <button
                            class="absolute bottom-2 right-2 bg-white p-1 rounded-full shadow-md cursor-pointer focus:outline-none focus:ring"
                            @click="triggerFileInput"
                            aria-label="เปลี่ยนรูป"
                        >
                            <Icon icon="eos-icons:bubble-loading" class="w-5 h-5 text-gray-600" v-if="isEditStudentPhoto" />
                            <Icon icon="heroicons:pencil-solid" class="w-5 h-5 text-gray-600" v-else />
                        </button>
                        <button
                            class="absolute bottom-2 left-2 bg-red-500 p-1 rounded-full shadow-md cursor-pointer focus:outline-none focus:ring"
                            @click="handleDeletePhoto"
                            aria-label="ลบรูป">
                
                            <Icon v-if="isDeletingStudentPhoto" icon="eos-icons:bubble-loading" class="w-5 h-5 text-white" />
                            <Icon v-if="!isDeletingStudentPhoto && studentImageUrl" icon="heroicons:trash-solid" class="w-5 h-5 text-white" />
                        </button> -->
                    </div>

                    <!-- Default Avatar -->
                    <div v-else class="w-full h-full flex items-center justify-center bg-gray-300 cursor-pointer">
                        <Icon icon="tabler:photo-plus" class="w-10 h-10 text-gray-600/60" @click="triggerFileInput" />
                    </div>
                </div>

                <!-- Information Section -->
                <div class="flex-1 pt-0.5 relative">
                    <!-- Name -->
                    <div class="flex items-baseline">
                        <span class="w-[25%] text-[2.2vw] sm:text-md md:text-lg font-medium text-gray-700">ชื่อ</span>
                        <span class="mx-[1%] text-[2.2vw] sm:text-sm md:text-lg text-gray-700">:</span>
                        <span class="flex-1 text-[2.4vw] sm:text-sm md:text-lg font-semibold text-gray-800">
                            {{ displayFullNameThai }}
                        </span>
                    </div>
                    <div class="flex items-baseline -mt-1">
                        <span class="w-[25%] text-[2vw] sm:text-xs font-normal text-gray-600">Name</span>
                    </div>
                    <div class="flex items-baseline -mt-3 sm:-mt-4 md:-mt-5">
                        <span class="w-[25%] text-[2.2vw] sm:text-sm md:text-lg font-medium text-gray-700"></span>
                        <span class="mx-[1%] text-[2.2vw] sm:text-sm md:text-lg text-gray-700 text-transparent">:</span>
                        <span class="flex-1 text-[1.8vw] sm:text-sm font-normal text-gray-800">
                            {{ editForm.first_name_english }}
                        </span>
                    </div>


                    <!-- Student Code -->
                    <div class="flex items-baseline mt-0.5">
                        <span
                            class="w-[25%] text-[2.2vw] sm:text-sm md:text-lg font-medium text-gray-700">รหัสประจำตัว</span>
                        <span class="mx-[1%] text-[2.2vw] sm:text-sm md:text-lg text-gray-700">:</span>
                        <span class="flex-1 text-[2.4vw] sm:text-sm md:text-lg font-semibold text-gray-800">
                            {{ editForm.student_number }}
                        </span>
                    </div>
                    <div class="flex items-baseline -mt-1">
                        <span class="w-[25%] text-[2vw] sm:text-xs font-normal text-gray-600">Student
                            ID</span>
                    </div>

                    <!-- ID Card Number -->
                    <div class="flex items-baseline mt-0.5">
                        <span
                            class="w-[25%] text-[2.2vw] sm:text-sm font-medium text-gray-700">เลขบัตรประชาชน</span>
                        <span class="mx-[1%] text-[2.2vw] sm:text-sm md:text-lg text-gray-700">:</span>
                        <span class="flex-1 text-[2.4vw] sm:text-sm md:text-lg font-semibold text-gray-800">
                            {{ formattedIdNumber }}
                        </span>
                    </div>
                    <div class="flex items-baseline -mt-1">
                        <span class="w-[25%] text-[2vw] sm:text-xs font-normal text-gray-600">ID Card
                            No.</span>
                    </div>

                    <!-- Level -->
                    <div class="flex items-baseline mt-0.5">
                        <span
                            class="w-[25%] text-[2.2vw] sm:text-sm md:text-lg font-medium text-gray-700">
                            ระดับ
                        </span>
                        <span class="mx-[1%] text-[2.2vw] sm:text-sm md:text-lg text-gray-700">:</span>
                        <span class="flex-1 text-[2.4vw] sm:text-sm md:text-lg font-semibold text-gray-800">
                            {{ editForm.level }}
                        </span>
                    </div>
                    <div class="flex items-baseline -mt-1.5 sm:-mt-2 md:-mt-2.5">
                        <span class="w-[25%] text-[2vw] sm:text-xs font-normal text-gray-600">Level</span>
                        <span class="mx-[1%] text-[2.2vw] sm:text-sm md:text-lg text-gray-700 text-transparent">:</span>
                        <span class="flex-1 text-[1.4vw] sm:text-[12px] font-normal text-gray-800">
                            {{ studentInfo.class_level < 4 ? 'LOWER SECONDARY' : 'UPPER SECONDARY' }}
                        </span>
                    </div>

                    <!-- Date of Birth -->
                    <div class="flex items-baseline mt-0.5">
                        <span
                            class="w-[25%] text-[2.2vw] sm:text-sm md:text-lg font-medium text-gray-700">วันเกิด</span>
                        <span class="mx-[1%] text-[2.2vw] sm:text-sm md:text-lg text-gray-700">:</span>
                        <span class="flex-1 text-[2.4vw] sm:text-sm md:text-lg font-semibold text-gray-800">
                            {{ new Date(editForm.birth_date).toLocaleDateString('th-TH', {
                                day: '2-digit',
                                month: '2-digit',
                                year: 'numeric'
                            }) }}
                        </span>
                        <!-- <span class="flex-1 text-[2.4vw] sm:text-sm md:text-lg font-semibold text-gray-800">
                            {{ formatDate(editForm.birth_date) }}
                        </span> -->
                    </div>
                    <div class="flex items-baseline -mt-1.5 sm:-mt-2 md:-mt-2.5">
                        <span class="w-[25%] text-[2vw] sm:text-xs font-normal text-gray-600">Date of Birth</span>
                        <span class="mx-[1%] text-[2.2vw] sm:text-sm md:text-lg text-gray-700 text-transparent">:</span>
                        <span class="flex-1 text-[1.4vw] sm:text-[12px] font-normal text-gray-800">
                            <!-- {{ editForm.birth_date }} --> 
                            {{ new Date(editForm.birth_date).toLocaleDateString('en-US', {
                                month: '2-digit',
                                day: '2-digit',
                                year: 'numeric'
                            }) }}
                        </span>
                    </div>

                    <!-- Expiry Date -->
                    <div class="flex items-baseline mt-0.5">
                        <span
                            class="w-[25%] text-[2.2vw] sm:text-sm md:text-lg font-medium text-gray-700">วันหมดอายุ</span>
                        <span class="mx-[1%] text-[2.2vw] sm:text-sm md:text-lg text-gray-700">:</span>
                        <span class="flex-1 text-[2.4vw] sm:text-sm md:text-lg font-semibold text-gray-800">
                            <!-- {{ new Date(props.studentInfo.card_expiry_date).toLocaleDateString('th-TH', {
                                day: '2-digit',
                                month: '2-digit',
                                year: 'numeric',
                            }) }} -->

                            {{ formatDate(props.studentInfo.card_expiry_date) }}
                        </span>
                    </div>
                    <div class="flex items-baseline -mt-1.5 sm:-mt-2 md:-mt-2.5">
                        <span class="w-[25%] text-[2vw] sm:text-xs font-normal text-gray-600">Expiry Date</span>
                        <span class="mx-[1%] text-[2.2vw] sm:text-sm md:text-lg text-gray-700 text-transparent">:</span>
                        <span class="flex-1 text-[1.4vw] sm:text-[12px] font-normal text-gray-800">
                            {{ '05/15/2027' }}
                        </span>
                    </div>
                </div>


                <!-- QR Code - ปรับขนาดตาม % -->
                <div class="absolute bottom-[5%] right-[3%] w-[15%] aspect-square">
                    <div class="w-full h-full bg-white flex items-center justify-center rounded-lg shadow-md">
                        <!-- <QRCodeVue3 
                            :value="`${props.studentInfo.student_number+props.studentInfo.first_name_english}`"
                            :cornersSquareOptions="{ type: 'extra-rounded', color: '#4a90e2' }" 
                            :dotsOptions="{
                                type: 'dots',
                                color: '#4a90e2',
                            }" 
                            :cornersDotOptions="{ type: 'square', color: '#4a90e2' }" 
                        /> -->
                        <QRCodeVue3 :value="`${props.studentInfo.student_number}`"
                            :cornersSquareOptions="{ type: 'extra-rounded', color: '#000' }" :dotsOptions="{
                                type: 'dots',
                                color: '#000',
                            }" :cornersDotOptions="{ type: 'square', color: '#000' }" />
                    </div>
                </div>

                <!-- Bottom Section -->
                <!-- เพิ่มแถบสีน้ำเงินที่ขอบบัตรด้านล่าง -->
                <div class="absolute bottom-0 left-0 w-full h-[2.5%] flex items-center justify-center rounded-b-2xl"
                    style="background: linear-gradient(135deg, #4a90e2 72%, transparent 0%);">
                    <span class="text-white text-[1vw] text-sm font-medium tracking-wider"></span>
                </div>
                <!-- Add Edit Button -->
            </div>

            <!-- Edit Modal -->
            <Dialog as="div" @close="isEditModalOpen = false" :open="isEditModalOpen" class="relative z-50">
                <div class="fixed inset-0 bg-black/30" aria-hidden="true" />

                <div class="fixed inset-0 flex items-center justify-center p-4">
                    <DialogPanel class="w-full max-w-md bg-white rounded-lg p-6 shadow-xl">
                        <DialogTitle class="text-lg font-medium text-gray-900 mb-4">แก้ไขข้อมูลนักเรียน</DialogTitle>

                        <form @submit.prevent="handleSubmit">
                            <!-- Student ID -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">รหัสนักเรียน</label>
                                <input type="text" v-model="editForm.student_number"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" />
                            </div>

                            <!-- Prefix Name -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">คำนำหน้าชื่อ</label>
                                <input type="text" v-model="editForm.title_name"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>

                            <!-- First Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">ชื่อ</label>
                                <input type="text" v-model="editForm.first_name_thai"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>

                            <!-- Last Name -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">นามสกุล</label>
                                                                <input type="text" v-model="editForm.last_name_thai"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>

                            <!-- English Name -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ-นามสกุล
                                    (อังกฤษ)</label>
                                <input type="text" v-model="editForm.first_name_english"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" />
                            </div>

                            <!-- ID Number -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">เลขประจำตัวประชาชน</label>
                                <input type="text" v-model="editForm.id_card_no"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" />
                            </div>

                            <!-- Birth Date -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">วันเกิด</label>
                                <input type="date" v-model="editForm.date_of_birth"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" />
                            </div>

                            <!-- Buttons -->
                            <div class="flex justify-end gap-2">
                                <button type="button" @click="isEditModalOpen = false"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                    ยกเลิก
                                </button>
                                <button type="submit" :disabled="isSaving"
                                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50">
                                    {{ isSaving ? 'กำลังบันทึก...' : 'บันทึก' }}
                                </button>
                            </div>
                        </form>
                    </DialogPanel>
                </div>
            </Dialog>
        </div>
        <!-- ปุ่มดาวน์โหลดบัตร -->
        <!-- <button
            @click="downloadCard"
            class="download-btn ml-4 px-4 py-2 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700 transition"
        >
            <Icon icon="mdi:download" class="inline-block mr-2" /> ดาวน์โหลดบัตร
        </button> -->
    </div>
</template>

<style scoped>
/* @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap'); */
@import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@100..900&display=swap');

* {
    /* font-family: 'Sarabun', sans-serif; */
    font-family: "Noto Sans Thai", sans-serif;
    /* font-weight: 500;
    font-style: normal; */
}

.student-card-container {
    background: repeating-linear-gradient(135deg,
            #a6d7ff 0%,
            #a6d7ff 20%,
            #ffffff 40%,
            #ffffff 60%,
            #a6d7ff 90%,
            #a6d7ff 100%);

    /* background: url('http://localhost:8000/storage/jsm_logo.png') center center/380px no-repeat ; */

}
</style>
