<script setup>
import { ref, computed } from 'vue'
import QRCodeVue3 from "qrcode-vue3"
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

definePageMeta({ layout: false, middleware: ['auth'] })

const route = useRoute()
const config = useRuntimeConfig()
const apiBase = config.public.apiBase
const api = useApi()

const level = computed(() => route.params.level)
const room = computed(() => route.params.room)

useHead({ title: computed(() => `ข้อมูลนักเรียน - ชั้น ม.${level.value}/${room.value}`) })

const students = ref([])
const isLoading = ref(true)
const searchTerm = ref('')

const filteredStudents = computed(() => {
    if (!searchTerm.value) return students.value
    const term = searchTerm.value.toLowerCase()
    return students.value.filter(s =>
        (s.first_name_thai && s.first_name_thai.toLowerCase().includes(term)) ||
        (s.student_number && s.student_number.toString().includes(term))
    )
})

const fetchStudents = async () => {
    isLoading.value = true
    try {
        const response = await api.get(`/api/student-card/admin/students/${level.value}/${room.value}`)
        students.value = response.students || []
    } catch (error) {
        console.error('Error fetching students:', error)
        Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: 'ไม่สามารถโหลดข้อมูลนักเรียนได้' })
    } finally {
        isLoading.value = false
    }
}

onMounted(fetchStudents)

const cardBgStyle = computed(() => ({
    background: `url('${apiBase}/storage/images/std_card_bg2.png') center center / 1400px no-repeat`
}))

const formattedIdNumber = (idNumber) => {
    if (!idNumber) return ''
    const s = String(idNumber).replace(/\D/g, '')
    if (s.length !== 13) return s
    return s.replace(/(\d)(\d{4})(\d{5})(\d{2})(\d{1})/, '$1-$2-$3-$4-$5')
}

const studentPrefixName = (student) => {
    if (!student.first_name_english) return ''
    if (student.title_name === 'เด็กหญิง' || student.title_name === 'นางสาว') return 'Ms.'
    if (student.title_name === 'เด็กชาย' || student.title_name === 'นาย') return 'Mr.'
    return ''
}

const studentThaiPrefixName = (student) => {
    if (!student?.first_name_thai) return { prefix: '', txtSize: 'text-[46px]' }
    const fullLength = (student.first_name_thai?.length || 0) + (student.last_name_thai?.length || 0)
    const isGirl = student.title_name === 'เด็กหญิง'
    const isBoy = student.title_name === 'เด็กชาย'
    const isMiss = student.title_name === 'นางสาว'

    if (fullLength < 15) return { prefix: student.title_name, txtSize: 'text-[46px]' }
    if (fullLength > 20) {
        if (isGirl) return { prefix: 'ด.ญ.', txtSize: 'text-[42px]' }
        if (isBoy) return { prefix: 'ด.ช.', txtSize: 'text-[42px]' }
        if (isMiss) return { prefix: 'น.ส.', txtSize: 'text-[42px]' }
        return { prefix: '', txtSize: 'text-[42px]' }
    }
    if (isGirl) return { prefix: 'ด.ญ.', txtSize: 'text-[46px]' }
    if (isBoy) return { prefix: 'ด.ช.', txtSize: 'text-[46px]' }
    if (isMiss) return { prefix: 'น.ส.', txtSize: 'text-[46px]' }
    return { prefix: '', txtSize: 'text-[44px]' }
}

const formatDate = (dateStr, locale) => {
    if (!dateStr) return '-'
    try {
        const d = new Date(dateStr)
        if (isNaN(d.getTime())) return '-'
        return d.toLocaleDateString(locale, { day: '2-digit', month: '2-digit', year: 'numeric' })
    } catch {
        return '-'
    }
}

const downloadCard = async (index, studentNumber) => {
    if (process.server) return
    const { default: html2canvas } = await import('html2canvas')
    const el = document.getElementById(`card-${index}`)
    if (!el) return
    try {
        const canvas = await html2canvas(el, { backgroundColor: null, scale: 6 })
        const link = document.createElement('a')
        link.href = canvas.toDataURL('image/png')
        link.download = `student_card_${level.value}_${room.value}_${studentNumber}.png`
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
    } catch {
        Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: 'ไม่สามารถดาวน์โหลดบัตรนักเรียนได้' })
    }
}
</script>

<template>
    <div class="min-h-screen bg-gray-100">
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
                        <NuxtLink to="/student-card/admin"
                            class="w-full sm:w-auto px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200 flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            กลับ
                        </NuxtLink>
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
            <div v-else-if="students.length === 0" class="flex flex-col items-center justify-center bg-white rounded-2xl shadow-xl p-12">
                <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <p class="mt-4 text-gray-500 text-lg">ไม่พบข้อมูลนักเรียน</p>
            </div>

            <!-- Cards -->
            <div v-else class="grid grid-cols-1 gap-4">
                <div v-for="(student, index) in filteredStudents" :key="student.id">
                    <div class="flex justify-center items-center">
                        <div :id="`card-${index}`" :style="cardBgStyle"
                            class="w-full aspect-[1.95/1.20] relative overflow-hidden rounded-2xl shadow-lg border border-gray-300">

                            <!-- Top Section -->
                            <div class="h-[20%] -ml-8 flex items-center relative"
                                style="background: linear-gradient(135deg, transparent 45%, #4a90e2 0%);">
                                <div class="w-[22%] aspect-square flex items-center justify-center">
                                    <img :src="`${apiBase}/storage/jsm_logo.png`" alt="School Logo"
                                        class="w-[56%] h-[56%] mt-10 object-cover rounded-full">
                                </div>
                                <div class="-ml-10 -mt-2">
                                    <div class="text-6xl font-semibold text-gray-800">โรงเรียนจริยธรรมศึกษามูลนิธิ</div>
                                    <div class="text-[34px] mt-2 font-semibold text-gray-800">CHARIYATHAMSUKSA FOUNDATION SCHOOL</div>
                                    <div class="text-3xl -mt-1.5 text-gray-800">148 ม.8 ต.สะกอม อ.จะนะ จ.สงขลา 90130 โทร.081-5412281</div>
                                </div>
                                <div class="absolute -top-8 right-4 mt-[148px] text-white bg-blue-700 px-4 pb-2 text-end rounded-md">
                                    <div class="text-3xl -mt-1.5 font-semibold">บัตรประจำตัวนักเรียน</div>
                                    <div class="text-2xl">STUDENT CARD</div>
                                </div>
                            </div>

                            <!-- Main Content -->
                            <div class="flex p-[2%] gap-[2%] h-[80%]">
                                <!-- Photo -->
                                <div class="w-[30%] h-[80%] rounded-xl overflow-hidden flex-shrink-0 mt-4">
                                    <img v-if="student.profile_image_url"
                                        :src="student.profile_image_url"
                                        alt="Student Photo" class="w-full h-full object-fill rounded-xl" />
                                    <div v-else class="w-full h-full flex items-center justify-center bg-gray-300">
                                        <Icon icon="tabler:photo-plus" class="w-10 h-10 text-gray-600/60" />
                                    </div>
                                </div>

                                <!-- Info -->
                                <div class="flex-1 relative">
                                    <!-- Name -->
                                    <div>
                                        <div class="flex items-center">
                                            <div class="w-[284px] text-[38px] font-bold text-gray-600 leading-tight mt-2">ชื่อ</div>
                                            <div class="text-[42px] font-bold text-gray-800 leading-tight mr-3 mt-1">:</div>
                                            <div :class="studentThaiPrefixName(student).txtSize" class="font-bold text-gray-800 leading-tight -mt-1">
                                                {{ studentThaiPrefixName(student).prefix }}{{ student.first_name_thai }} {{ student.last_name_thai }}
                                            </div>
                                        </div>
                                        <div class="flex items-center -mt-3">
                                            <div class="w-[284px] text-[32px] text-gray-700 leading-tight">Name</div>
                                            <div class="text-[42px] text-transparent leading-tight mr-4">:</div>
                                            <div class="text-[36px] text-gray-700 leading-tight">
                                                <span v-if="student.first_name_english">
                                                    {{ studentPrefixName(student) }}{{ student.full_name_english || student.first_name_english }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Student ID -->
                                    <div>
                                        <div class="flex items-center">
                                            <div class="w-[284px] text-[38px] font-bold text-gray-600 leading-tight">รหัสประจำตัว</div>
                                            <div class="text-[42px] font-bold text-gray-700 leading-tight mr-3">:</div>
                                            <div class="text-[44px] font-bold text-gray-800 leading-tight">{{ student.student_number }}</div>
                                        </div>
                                        <div class="flex items-center -mt-4">
                                            <div class="w-[284px] text-[32px] text-gray-700 leading-tight">Student ID</div>
                                            <div class="text-[42px] text-transparent leading-tight mr-4">:</div>
                                        </div>
                                    </div>
                                    <!-- ID Card -->
                                    <div>
                                        <div class="flex">
                                            <div class="w-[284px] text-[38px] font-bold text-gray-600 leading-tight">เลขบัตรประชาชน</div>
                                            <div class="text-[42px] font-bold text-gray-700 leading-tight mr-3">:</div>
                                            <div class="text-[44px] font-bold text-gray-800 leading-tight">{{ formattedIdNumber(student.national_id) }}</div>
                                        </div>
                                        <div class="flex -mt-4">
                                            <div class="w-[284px] text-[32px] text-gray-700 leading-tight">ID Card Number</div>
                                            <div class="text-[42px] text-transparent leading-tight mr-4">:</div>
                                        </div>
                                    </div>
                                    <!-- Level -->
                                    <div>
                                        <div class="flex">
                                            <div class="w-[284px] text-[38px] font-bold text-gray-600 leading-tight">ระดับ</div>
                                            <div class="text-[42px] font-bold text-gray-700 leading-tight mr-3">:</div>
                                            <div class="text-[44px] font-bold text-gray-800 leading-tight">
                                                {{ student.class_level < 4 ? 'มัธยมศึกษาตอนต้น' : 'มัธยมศึกษาตอนปลาย' }}
                                            </div>
                                        </div>
                                        <div class="flex -mt-3">
                                            <div class="w-[284px] text-[32px] text-gray-700 leading-tight">Level</div>
                                            <div class="text-[42px] text-transparent leading-tight mr-4">:</div>
                                            <div class="text-[36px] text-gray-700 leading-tight">
                                                {{ student.class_level < 4 ? 'Lower Secondary' : 'Upper Secondary' }}
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Date of Birth -->
                                    <div>
                                        <div class="flex">
                                            <div class="w-[284px] text-[38px] font-bold text-gray-600 leading-tight">วัน/เดือน/ปี เกิด</div>
                                            <div class="text-[42px] font-bold text-gray-700 leading-tight mr-3">:</div>
                                            <div class="text-[44px] font-bold text-gray-800 leading-tight">{{ formatDate(student.birth_date, 'th-TH') }}</div>
                                        </div>
                                        <div class="flex -mt-3">
                                            <div class="w-[284px] text-[32px] text-gray-700 leading-tight">Date of Birth</div>
                                            <div class="text-[42px] text-transparent leading-tight mr-4">:</div>
                                            <div class="text-[36px] text-gray-700 leading-tight">{{ formatDate(student.birth_date, 'en-US') }}</div>
                                        </div>
                                    </div>
                                    <!-- Expiry Date -->
                                    <div class="-mt-1">
                                        <div class="flex">
                                            <div class="w-[284px] text-[38px] font-bold text-gray-600 leading-tight">วันหมดอายุบัตร</div>
                                            <div class="text-[42px] font-bold text-gray-700 leading-tight mr-3">:</div>
                                            <div class="text-[44px] font-bold text-gray-800 leading-tight">{{ formatDate(student.card_expiry_date, 'th-TH') }}</div>
                                        </div>
                                        <div class="flex -mt-3">
                                            <div class="w-[284px] text-[32px] text-gray-700 leading-tight">Expiry Date</div>
                                            <div class="text-[42px] text-transparent leading-tight mr-4">:</div>
                                            <div class="text-[36px] text-gray-700 leading-tight">{{ formatDate(student.card_expiry_date, 'en-US') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- QR Code -->
                            <div class="absolute bottom-10 right-10 w-[192px]">
                                <QRCodeVue3 :value="student.student_number || ''"
                                    :cornersSquareOptions="{ type: 'extra-rounded', color: '#000' }"
                                    :dotsOptions="{ type: 'dots', color: '#000' }"
                                    :cornersDotOptions="{ type: 'square', color: '#000' }" />
                            </div>

                            <!-- Bottom bar -->
                            <div class="absolute bottom-0 left-0 w-full h-8 rounded-b-2xl"
                                style="background: linear-gradient(135deg, #4a90e2 72%, transparent 0%);" />
                        </div>
                    </div>

                    <!-- Download Button -->
                    <div class="flex justify-center w-full mt-2">
                        <div class="w-full text-end">
                            <button @click="downloadCard(index, student.student_number)"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
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
@import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@100..900&display=swap');
* { font-family: "Noto Sans Thai", sans-serif; }
</style>
