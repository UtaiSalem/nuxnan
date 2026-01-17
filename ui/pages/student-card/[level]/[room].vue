<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import QRCodeVue3 from "qrcode-vue3"
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

definePageMeta({
  layout: false
})

const route = useRoute()
const config = useRuntimeConfig()
const apiBase = config.public.apiBase || ''

const level = computed(() => Number(route.params.level))
const room = computed(() => Number(route.params.room))

const levelName = computed(() => {
    const names = ['', 'ม.1', 'ม.2', 'ม.3', 'ม.4', 'ม.5', 'ม.6']
    return names[level.value] || ''
})

useHead({
  title: computed(() => `บัตรนักเรียน ${levelName.value}/${room.value}`)
})

// State
const students = ref<any[]>([])
const selectedStudent = ref<any>(null)
const isLoading = ref(true)
const isUploading = ref(false)

// Fetch students
const fetchStudents = async () => {
    isLoading.value = true
    try {
        const response = await $fetch(`${apiBase}/student-card/${level.value}/${room.value}`)
        students.value = (response as any).students || []
    } catch (error) {
        console.error('Error fetching students:', error)
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่สามารถโหลดข้อมูลนักเรียนได้'
        })
    } finally {
        isLoading.value = false
    }
}

// Upload photo
const uploadPhoto = async (studentId: number, file: File) => {
    isUploading.value = true
    const formData = new FormData()
    formData.append('photo', file)
    
    try {
        const response = await $fetch(`${apiBase}/student-card/admin/upload-photo/${studentId}`, {
            method: 'POST',
            body: formData
        })
        
        Swal.fire({
            icon: 'success',
            title: 'สำเร็จ',
            text: 'อัปโหลดรูปภาพเรียบร้อยแล้ว',
            timer: 1500
        })
        
        await fetchStudents()
    } catch (error) {
        console.error('Error uploading photo:', error)
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่สามารถอัปโหลดรูปภาพได้'
        })
    } finally {
        isUploading.value = false
    }
}

// Handle file input
const handleFileChange = (event: Event, studentId: number) => {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0]
    if (file) {
        uploadPhoto(studentId, file)
    }
}

// Get student image URL
const getStudentImageUrl = (student: any) => {
    if (student.profile_image) {
        return `/storage/images/students/${student.class_level}/${student.class_section}/${student.profile_image}`
    }
    return null
}

// Format national ID
const formatNationalId = (id: string) => {
    if (!id || id.length !== 13) return id
    return id.replace(/(\d)(\d{4})(\d{5})(\d{2})(\d{1})/, '$1-$2-$3-$4-$5')
}

// Select student for detail view
const selectStudent = (student: any) => {
    selectedStudent.value = student
}

// Close detail modal
const closeDetail = () => {
    selectedStudent.value = null
}

onMounted(() => {
    fetchStudents()
})
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-100 via-white to-blue-200">
        
        <!-- Header -->
        <div class="bg-white shadow-sm sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <NuxtLink 
                            to="/student-card" 
                            class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                        >
                            <Icon icon="heroicons:arrow-left" class="w-6 h-6 text-gray-600" />
                        </NuxtLink>
                        <div>
                            <h1 class="text-xl font-bold text-gray-800">บัตรนักเรียน {{ levelName }}/{{ room }}</h1>
                            <p class="text-sm text-gray-500">{{ students.length }} คน</p>
                        </div>
                    </div>
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

        <!-- Student Grid -->
        <div v-else class="max-w-7xl mx-auto px-4 py-6">
            <div v-if="students.length === 0" class="text-center py-20">
                <Icon icon="heroicons:user-group" class="w-16 h-16 text-gray-300 mx-auto mb-4" />
                <p class="text-gray-500">ไม่พบข้อมูลนักเรียน</p>
            </div>
            
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <div 
                    v-for="student in students" 
                    :key="student.id"
                    @click="selectStudent(student)"
                    class="bg-white rounded-xl shadow-md overflow-hidden cursor-pointer hover:shadow-lg transition-shadow"
                >
                    <!-- Student Card Preview -->
                    <div class="relative aspect-[1.6/1] bg-gradient-to-br from-blue-500 to-blue-600 p-4">
                        <div class="absolute top-2 right-2 bg-white/20 backdrop-blur px-2 py-1 rounded text-xs text-white">
                            {{ student.student_number }}
                        </div>
                        
                        <div class="flex items-center gap-3 h-full">
                            <!-- Photo -->
                            <div class="w-20 h-24 bg-white rounded-lg overflow-hidden flex-shrink-0">
                                <img 
                                    v-if="getStudentImageUrl(student)"
                                    :src="getStudentImageUrl(student)" 
                                    :alt="student.full_name_thai"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center bg-gray-100">
                                    <Icon icon="heroicons:user" class="w-8 h-8 text-gray-400" />
                                </div>
                            </div>
                            
                            <!-- Info -->
                            <div class="flex-1 min-w-0 text-white">
                                <p class="font-semibold text-sm truncate">{{ student.full_name_thai || `${student.title_name}${student.first_name_thai} ${student.last_name_thai}` }}</p>
                                <p class="text-xs opacity-80">{{ levelName }}/{{ room }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="p-3 border-t">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">เลขที่ {{ student.order_no }}</span>
                            <button class="text-blue-600 hover:text-blue-700 font-medium">
                                ดูรายละเอียด
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Student Detail Modal -->
        <Teleport to="body">
            <div 
                v-if="selectedStudent" 
                class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
                @click.self="closeDetail"
            >
                <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                    <!-- Modal Header -->
                    <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between">
                        <h2 class="text-lg font-bold text-gray-800">รายละเอียดนักเรียน</h2>
                        <button @click="closeDetail" class="p-2 hover:bg-gray-100 rounded-lg">
                            <Icon icon="heroicons:x-mark" class="w-6 h-6 text-gray-500" />
                        </button>
                    </div>
                    
                    <!-- Student Card Display -->
                    <div class="p-6">
                        <!-- Full Card -->
                        <div class="bg-gradient-to-br from-blue-500 via-blue-600 to-blue-700 rounded-2xl p-6 text-white mb-6 shadow-lg">
                            <div class="flex items-start gap-4">
                                <!-- Photo -->
                                <div class="w-28 h-36 bg-white rounded-xl overflow-hidden flex-shrink-0 shadow-md">
                                    <img 
                                        v-if="getStudentImageUrl(selectedStudent)"
                                        :src="getStudentImageUrl(selectedStudent)" 
                                        :alt="selectedStudent.full_name_thai"
                                        class="w-full h-full object-cover"
                                    />
                                    <div v-else class="w-full h-full flex items-center justify-center bg-gray-100">
                                        <Icon icon="heroicons:user" class="w-12 h-12 text-gray-400" />
                                    </div>
                                </div>
                                
                                <!-- Info -->
                                <div class="flex-1">
                                    <p class="text-xl font-bold mb-1">{{ selectedStudent.full_name_thai || `${selectedStudent.title_name}${selectedStudent.first_name_thai} ${selectedStudent.last_name_thai}` }}</p>
                                    <p class="text-blue-100 text-sm mb-3">{{ selectedStudent.first_name_english }} {{ selectedStudent.last_name_english }}</p>
                                    
                                    <div class="space-y-1 text-sm">
                                        <p><span class="opacity-70">รหัสนักเรียน:</span> {{ selectedStudent.student_number }}</p>
                                        <p><span class="opacity-70">ชั้น:</span> {{ levelName }}/{{ room }}</p>
                                        <p><span class="opacity-70">เลขที่:</span> {{ selectedStudent.order_no }}</p>
                                    </div>
                                </div>
                                
                                <!-- QR Code -->
                                <div class="bg-white p-2 rounded-xl">
                                    <QRCodeVue3
                                        :value="selectedStudent.student_number || ''"
                                        :width="80"
                                        :height="80"
                                    />
                                </div>
                            </div>
                        </div>
                        
                        <!-- Additional Info -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-gray-50 rounded-xl p-4">
                                <p class="text-sm text-gray-500 mb-1">เลขบัตรประชาชน</p>
                                <p class="font-mono font-semibold text-gray-800">{{ formatNationalId(selectedStudent.national_id) }}</p>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-4">
                                <p class="text-sm text-gray-500 mb-1">วันเกิด</p>
                                <p class="font-semibold text-gray-800">{{ selectedStudent.birth_date_string || selectedStudent.birth_date || '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
