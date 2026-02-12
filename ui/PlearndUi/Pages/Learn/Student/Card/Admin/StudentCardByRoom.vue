<script setup>

import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import QRCodeVue3 from "qrcode-vue3";
import Swal from 'sweetalert2'
import { Icon } from '@iconify/vue';
import html2canvas from 'html2canvas'

const props = defineProps({
    students: {
        type: Array,
        default: () => []
    },
    room: String,
    level: String,
})

const cardRef = ref([])
const searchTerm = ref('')
const filteredStudents = computed(() => {
    if (!searchTerm.value) return props.students
    if (!props.students) return []

    return props.students.filter(student => {
        const searchTermLower = searchTerm.value.toLowerCase()
        return (
            (student.first_name_thai && student.first_name_thai.toLowerCase().includes(searchTermLower)) ||
            (student.student_number && student.student_number.toString().includes(searchTerm.value))
        )
    })
})



// computed prefix name for students
const studentPrefixName = (idx) => {
    const student = props.students[idx]
    if (!student.first_name_english) return ''

    if (student.title_name == 'เด็กหญิง' || student.title_name == 'นางสาว') {
        return 'Ms.'
    } else if (student.title_name == 'เด็กชาย' || student.title_name == 'นาย') {
        return 'Mr.'
    } else {
        return ''
    }
}


// ฟังก์ชันสำหรับจัดรูปแบบเลขบัตรประชาชน
const formattedIdNumber = (idNumber) => {
    if (!idNumber) return ''
    const idString = String(idNumber);
    if (idString.length !== 13) return idString;

    // Remove non-digits and format
    const id = idString.replace(/\D/g, '');
    return id.replace(/(\d)(\d{4})(\d{5})(\d{2})(\d{1})/, '$1-$2-$3-$4-$5');
}

// ฟังก์ชันดาวน์โหลดบัตร
const downloadCard = async (cardIndex, studentId) => {
    const cardElement = document.getElementById('card-' + cardIndex)
    try {
        const canvas = await html2canvas(cardElement, {
            backgroundColor: null,
            scale: 6,
        })
        const imageData = canvas.toDataURL('image/png')
        const link = document.createElement('a')
        link.href = imageData
        link.download = `student_card_${props.level}_${props.room}_${studentId}.png`
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
    } catch (error) {
        console.error('Error downloading card:', error)
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่สามารถดาวน์โหลดบัตรนักเรียนได้',
        })
    }
}

//  download student card back side
const downloadCardBack = async () => {
    const cardElement = document.getElementById('student-card-back')
    try {
        const canvas = await html2canvas(cardElement, {
            backgroundColor: null,
            scale: 6,
        })
        const imageData = canvas.toDataURL('image/png')
        const link = document.createElement('a')
        link.href = imageData
        link.download = `student_card_back_.png`
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
    } catch (error) {
        console.error('Error downloading card back:', error)
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่สามารถดาวน์โหลดด้านหลังบัตรนักเรียนได้',
        })
    }
}

// ฟังก์ชันคำนวณคำนำหน้าชื่อภาษาไทย
const studentThaiPrefixName = (index) => {
    const student = props.students[index];
    if (!student?.first_name_thai) return { prefix: '', txtSize: 'text-[46px]' };

    const fullLength = (student.first_name_thai.length || 0) + (student.last_name_thai?.length || 0);
    const isGirl = student.title_name === 'เด็กหญิง';
    const isBoy = student.title_name === 'เด็กชาย';
    const isMiss = student.title_name === 'นางสาว';

    if (fullLength < 15) {
        return { prefix: student.title_name, txtSize: 'text-[46px]' };
    }
    if (fullLength > 20) {
        if (isGirl) return { prefix: 'ด.ญ.', txtSize: 'text-[42px]' };
        if (isBoy) return { prefix: 'ด.ช.', txtSize: 'text-[42px]' };
        if (isMiss) return { prefix: 'น.ส.', txtSize: 'text-[42px]' };
        return { prefix: '', txtSize: 'text-[42px]' };
    }

    if (isGirl) return { prefix: 'ด.ญ.', txtSize: 'text-[46px]' };
    if (isBoy) return { prefix: 'ด.ช.', txtSize: 'text-[46px]' };
    if (isMiss) return { prefix: 'น.ส.', txtSize: 'text-[46px]' };

    return { prefix: '', txtSize: 'text-[44px]' };
}
</script>

<template>

    <Head :title="`ข้อมูลนักเรียน - ชั้น ม.${level}/${room}`" />
    <div class="min-h-screen">
        <!-- Header Section -->
        <div class="max-w-7xl mx-auto">
            <div class="bg-white rounded-2xl shadow-xl p-6 mb-6">
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                    <!-- Header Section -->
                    <div class="space-y-2">
                        <h1 class="text-2xl font-bold text-gray-800">ข้อมูลนักเรียน</h1>
                        <div class="flex gap-4">
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-2 bg-blue-100 text-blue-800 rounded-lg font-bold">
                                    ชั้น ม.{{ level }}/{{ room }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <span class="font-medium">{{ students?.length || 0 }} คน</span>
                            </div>
                        </div>
                    </div>

                    <!-- Search and Back Button Container -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 w-full md:w-auto">
                        <!-- Search Box -->
                        <div class="relative w-full sm:w-80">
                            <input id="student-search-input" type="text" v-model="searchTerm"
                                placeholder="ค้นหาชื่อหรือรหัสนักเรียน..."
                                class="w-full px-4 py-2 pr-10 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none" />
                            <svg class="w-5 h-5 text-gray-400 absolute right-3 top-2.5" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <!-- Back Button -->
                        <div class="flex items-center w-full sm:w-auto">
                            <button @click="$inertia.visit('/student-card/admin')"
                                class="w-full sm:w-auto px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200">
                                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 19l-7-7 7-7" />
                                </svg>
                                กลับ
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Students Grid -->
            <div v-if="!students || students.length === 0"
                class="flex flex-col items-center justify-center bg-white rounded-2xl shadow-xl p-12">
                <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <p class="mt-4 text-gray-500 text-lg">ไม่พบข้อมูลนักเรียน</p>
            </div>

            <div v-else class="grid grid-cols-1 gap-4">

                <!-- Student Card Back Side -->
                <!-- <div id="student-card-back"
                    class="student-card-container w-full aspect-[1.95/1.20] bg-white shadow-lg relative overflow-hidden font-thai rounded-2xl border border-gray-300 mb-2 max-w-full"
                    style="max-width:100%;">


                    <div class="bg-blue-800 h-12 w-full absolute top-0 left-0"
                        style="background: linear-gradient(135deg, transparent 65%, #1c64f2 0%);"></div>

                    <div class="relative z-10 p-6 flex flex-col gap-3 h-full">

                        <div class="flex flex-col items-center justify-center mt-2">
                            <img src="/storage/jsm_logo.png" alt="logo" class="w-32 h-32" />
                            <h1
                                class="text-5xl font-bold text-gray-800 leading-tight text-center tracking-wide -mt-6 mb-0">
                                โรงเรียนจริยธรรมศึกษามูลนิธิ
                            </h1>
                            <div class="text-[27px] text-gray-700 leading-tight text-center tracking-wider">
                                CHARIYATHAMSUKSA FOUNDATION SCHOOL
                            </div>

                            <p class="text-[22px] text-gray-700 leading-tight text-center tracking-wider">
                                148 ม.8 ต.สะกอม อ.จะนะ จ.สงขลา 90130 โทร. 081-5412281
                            </p>
                        </div>


                        <div class="border-t-2 border-blue-200"></div>


                        <div class="-mt-1">
                            <div
                                class="bg-blue-600 text-white px-12 -ml-10 w-[380px] pb-8 -mt-1 text-4xl font-semibold text-center">
                                <span>เงื่อนไขการใช้บัตร</span>
                            </div>
                            <div class="pl-10">
                                <div class="flex items-center justify-start text-[34px] tracking-wider">
                                    <Icon icon="lucide:dot" class="w-16 h-16" />
                                    <p class="tracking-wider -mt-8">บัตรนี้ใช้เฉพาะผู้เป็นเจ้าของบัตรเท่านั้น</p>
                                </div>
                                <div class="flex items-center justify-start text-[34px] tracking-wider">
                                    <Icon icon="lucide:dot" class="w-16 h-16" />
                                    <p class="tracking-wider -mt-8">
                                        ให้พกพาบัตรนี้ตลอดเวลาที่อยู่ในโรงเรียนและนอกโรงเรียน</p>
                                </div>
                                <div class="flex items-center justify-start text-[34px] tracking-wider">
                                    <Icon icon="lucide:dot" class="w-16 h-16" />
                                    <p class="tracking-wider -mt-8">บัตรนี้เป็นเอกสารของทางโรงเรียน
                                        เพื่อแสดงสถานภาพการเป็นนักเรียน</p>
                                </div>
                                <div class="flex items-center justify-start text-[34px] tracking-wider">
                                    <Icon icon="lucide:dot" class="w-16 h-16 text-transparent" />
                                    <p class="tracking-wider -mt-8">ของโรงเรียนจริยธรรมศึกษามูลนิธิ</p>
                                </div>
                                <div class="flex items-center justify-start text-[34px] tracking-wider">
                                    <Icon icon="lucide:dot" class="w-16 h-16" />
                                    <p class="tracking-wider -mt-8">ให้ใช้บัตรนี้เป็นหลักฐานในการเข้าสอบทุกครั้ง</p>
                                </div>
                                <div class="flex items-center justify-start text-[34px] tracking-wider">
                                    <Icon icon="lucide:dot" class="w-16 h-16" />
                                    <p class="tracking-wider -mt-8">ผู้ใดเก็บบัตรนี้ได้ กรุณานำส่งคืนโรงเรียน</p>
                                </div>
                            </div>
                        </div>

                        <div class="absolute bottom-12 -right-10 flex justify-end items-end ">
                            <div class="text-right text-4xl text-gray-700 mr-32">
                                <div class="h-24 flex justify-center">
                                    <img src="/storage/images/jsm_director_signature.png" alt="signature"
                                        class="h-full mt-2" />
                                </div>
                                <div>(นางซารีนา สาเม๊าะ)</div>
                                <div class="">ผู้อำนวยการโรงเรียน</div>
                            </div>
                        </div>
                    </div>


                    <div class="absolute bottom-0 left-0 w-full h-8 bg-blue-800 rounded-b-2xl"
                        style="background: linear-gradient(135deg, #1c64f2 35%, transparent 0%);">
                    </div>
                </div> -->
                <!-- End Student Card Back Side -->

                <!-- download button -->
                <!-- <div class="flex justify-end ">
                    <button @click="downloadCardBack"
                        class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                        ดาวน์โหลดบัตรนักเรียน
                    </button>
                </div> -->

                <div v-for="(student, index) in filteredStudents" :key="student.id">

                    <div class="flex justify-center items-center font-sarabun">
                        <div :ref="`card-${cardRef[index]}`" :id="`card-${index}`"
                            class="student-card-container w-full  aspect-[1.95/1.20] relative overflow-hidden rounded-2xl shadow-lg border border-gray-300">
                            <!-- Top Section -->
                            <div class="h-[20%] -ml-8 flex items-center relative"
                                style="background: linear-gradient(135deg, transparent 45%, #4a90e2 0%);">

                                <!-- School Logo - ใช้ % แทน px -->
                                <div
                                    class=" w-[22%] aspect-square border-1 border-white flex items-center justify-center">
                                    <img src="/storage/jsm_logo.png" alt="School Logo"
                                        class="w-[56%] h-[56%] mt-10 object-cover rounded-full">
                                </div>

                                <!-- School Information - ปรับ font size ให้ responsive -->
                                <div class="-ml-10 -mt-2 ">
                                    <div class="text-6xl font-semibold md:font-bold text-gray-800">
                                        โรงเรียนจริยธรรมศึกษามูลนิธิ
                                    </div>
                                    <div class="text-[34px] mt-2 font-semibold text-gray-800 ">
                                        CHARIYATHAMSUKSA FOUNDATION SCHOOL
                                    </div>
                                    <div class="text-3xl -mt-1.5 text-gray-800">
                                        148 ม.8 ต.สะกอม อ.จะนะ จ.สงขลา 90130 โทร.081-5412281
                                    </div>
                                </div>

                                <!-- Card Label - ปรับขนาดตาม % -->
                                <div class="absolute -top-8 right-4 mt-[148px] ml-12  text-white bg-blue-700 px-4 pb-2 text-end rounded-md">
                                    <div class="text-3xl -mt-1.5 font-semibold">บัตรประจำตัวนักเรียน</div>
                                    <div class="text-2xl">STUDENT CARD</div>
                                </div>

                            </div>

                            <!-- Main Content -->
                            <div class="flex p-[2%] gap-[2%] h-[80%]">
                                <!-- Photo Section -->
                                <div class="w-[30%] h-[80%] rounded-xl overflow-hidden flex-shrink-0 mt-4">
                                    <!-- Photo Display -->
                                    <div v-if="student.profile_image" class="w-full h-full relative ">
                                        <img :src="`/storage/images/students/${student.class_level}/${student.class_section}/${student.profile_image}`"
                                            alt="Student Photo" class="w-full h-full object-fill rounded-xl" />
                                    </div>
                                    <!-- Default Avatar -->
                                    <div v-else
                                        class="w-full h-full flex items-center justify-center bg-gray-300 cursor-pointer">
                                        <Icon icon="tabler:photo-plus" class="w-10 h-10 text-gray-600/60" />
                                    </div>
                                </div>

                                <!-- Information Section -->
                                <div class="flex-1 relative">
                                    
                                    <!-- Row 1: Name -->
                                    <div class="">
                                        <div class="flex items-center">
                                            <div class="w-[284px] text-[38px] font-bold text-gray-600 leading-tight mt-2 ">ชื่อ</div>
                                            <div class="text-[42px] font-bold text-gray-800 leading-tight mr-3 mt-1">:</div>
                                            <div :class="studentThaiPrefixName(index).txtSize" class="font-bold text-gray-800 leading-tight -mt-1">
                                                {{ studentThaiPrefixName(index).prefix }}{{ student.first_name_thai }} {{ student.last_name_thai }}
                                            </div>
                                        </div>
                                        <div class="flex items-center -mt-3">
                                            <div class="w-[284px] text-[32px] font-base text-gray-700 leading-tight">Name</div>
                                            <div class="text-[42px] font-base text-gray-700 leading-tight text-transparent mr-4">:</div>
                                            <div class="text-[36px] font-base text-gray-700 leading-tight ">
                                                <!-- if student.first_name_english is not empty display prefixname and student.first_name_english otherwise display empty string -->
                                                <span v-if="student.first_name_english">
                                                    {{ studentPrefixName(index) }}{{ student.first_name_english }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Row 2: Student ID -->
                                    <div class="">
                                        <div class="flex items-center">
                                            <div class="w-[284px] text-[38px] font-bold text-gray-600 leading-tight ">รหัสประจำตัว</div>
                                            <div class="text-[42px] font-bold text-gray-700 leading-tight mr-3">:</div>
                                            <div class="text-[44px] font-bold text-gray-800 leading-tight ">
                                                {{ student.student_number }}
                                            </div>
                                        </div>
                                        <div class="flex items-center -mt-4">
                                            <div class="w-[284px] text-[32px] font-base text-gray-700 leading-tight">Student ID</div>
                                            <div class="text-[42px] font-base text-gray-700 leading-tight text-transparent mr-4">:</div>
                                        </div>
                                    </div>

                                    <!-- Row 3: ID Card Number -->
                                    <div class="">
                                        <div class="flex">
                                            <div class="w-[284px] text-[38px] font-bold text-gray-600 leading-tight ">เลขบัตรประชาชน</div>
                                            <div class="text-[42px] font-bold text-gray-700 leading-tight mr-3">:</div>
                                            <div class="text-[44px] font-bold text-gray-800 leading-tight ">
                                                {{ formattedIdNumber(student.national_id) }}
                                            </div>
                                        </div>
                                        <div class="flex -mt-4">
                                            <div class="w-[284px] text-[32px] font-base text-gray-700 leading-tight ">ID Card Number</div>
                                            <div class="text-[42px] font-base text-gray-700 leading-tight text-transparent mr-4">:</div>
                                        </div>
                                    </div>

                                    <!-- Row 4: Level -->
                                    <div class="">
                                        <div class="flex">
                                            <div class="w-[284px] text-[38px] font-bold text-gray-600 leading-tight ">ระดับ</div>
                                            <div class="text-[42px] font-bold text-gray-700 leading-tight mr-3">:</div>
                                            <div class="text-[44px] font-bold text-gray-800 leading-tight ">
                                                {{ student.class_level < 4 ? 'มัธยมศึกษาตอนต้น' : 'มัธยมศึกษาตอนปลาย' }} 
                                            </div>
                                        </div>

                                        <div class="flex -mt-3">
                                            <div class="w-[284px] text-[32px] font-base text-gray-700 leading-tight ">Level</div>
                                            <div class="text-[42px] font-base text-gray-700 leading-tight text-transparent mr-4">:</div>
                                            <div class="text-[36px] font-base text-gray-700 leading-tight ">
                                                {{ student.class_level < 4 ? 'Lower Secondary' : 'Upper Secondary' }} 
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Row 5: Date of Birth -->
                                    <div class="">
                                        <div class="flex">
                                            <div class="w-[284px] text-[38px] font-bold text-gray-600 leading-tight ">วัน/เดือน/ปี เกิด</div>
                                            <div class="text-[42px] font-bold text-gray-700 leading-tight mr-3">:</div>
                                            <div class="text-[44px] font-bold text-gray-800 leading-tight ">
                                                {{ 
                                                    new Date(student.birth_date).toLocaleDateString('th-TH', {
                                                        day: '2-digit',
                                                        month: '2-digit',
                                                        year: 'numeric'
                                                    }) 
                                                }}
                                            </div>
                                        </div>
                                        <div class="flex -mt-3">
                                            <div class="w-[284px] text-[32px] font-base text-gray-700 leading-tight ">Date of Birth</div>
                                            <div class="text-[42px] font-base text-gray-700 leading-tight text-transparent mr-4">:</div>
                                            <div class="text-[36px] font-base text-gray-700 leading-tight ">
                                                {{ 
                                                    new Date(student.birth_date).toLocaleDateString('en-US', {
                                                        day: '2-digit',
                                                        month: '2-digit',
                                                        year: 'numeric'
                                                    }) 
                                                }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Row 6: Expiry Date -->

                                    <div class="-mt-1">
                                        <div class="flex">
                                            <div class="w-[284px] text-[38px] font-bold text-gray-600 leading-tight ">วันหมดอายุบัตร</div>
                                            <div class="text-[42px] font-bold text-gray-700 leading-tight mr-3">:</div>
                                            <div class="text-[44px] font-bold text-gray-800 leading-tight ">
                                                {{ 
                                                    new Date(student.card_expiry_date).toLocaleDateString('th-TH', {
                                                        day: '2-digit',
                                                        month: '2-digit',
                                                        year: 'numeric'
                                                    }) 
                                                }}
                                            </div>
                                        </div>
                                        <div class="flex -mt-3">
                                            <div class="w-[284px] text-[32px] font-base text-gray-700 leading-tight ">Expiry Date</div>
                                            <div class="text-[42px] font-base text-gray-700 leading-tight text-transparent mr-4">:</div>
                                            <div class="text-[36px] font-base text-gray-700 leading-tight ">
                                                {{ 
                                                    new Date(student.card_expiry_date).toLocaleDateString('en-US', {
                                                        day: '2-digit',
                                                        month: '2-digit',
                                                        year: 'numeric'
                                                    }) 
                                                }}
                                            </div>
                                        </div>
                                    </div>

                                </div>


                            </div>

                            <!-- QR Code - ปรับขนาดตาม % -->
                            <div class="absolute bottom-10 right-10 w-[192px]">
                                <QRCodeVue3 :value="`${student.student_number}`"
                                    :cornersSquareOptions="{ type: 'extra-rounded', color: '#000' }"
                                    :dotsOptions="{
                                    type: 'dots',
                                    color: '#000',
                                }" :cornersDotOptions="{ type: 'square', color: '#000' }" />

                            </div>

                            <!-- Bottom Section -->
                            <!-- เพิ่มแถบสีน้ำเงินที่ขอบบัตรด้านล่าง -->
                            <div class="absolute bottom-0 left-0 w-full h-8 flex items-center justify-center rounded-b-2xl"
                                style="background: linear-gradient(135deg, #4a90e2 72%, transparent 0%);">
                                <span
                                    class="text-white text-[1vw] text-sm font-medium tracking-wider"></span>
                            </div>

                        </div>

                    </div>

                    <div class="flex justify-center items-center w-full mt-2">
                        <div class="w-full text-end">
                            <button @click="downloadCard(index, student.student_number)"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg">
                                ดาวน์โหลดบัตรนักเรียน
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap'); */
@import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@100..900&display=swap');

* {
    /* font-family: "Sarabun", sans-serif; */
    font-family: "Noto Sans Thai", sans-serif;
}

.student-card-container {
    background: url('/storage/images/std_card_bg2.png') center center/1400px no-repeat;
}
</style>
