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
  title: computed(() => `จัดการบัตรนักเรียน ${levelName.value}/${room.value}`)
})

// State
const students = ref<any[]>([])
const selectedStudent = ref<any>(null)
const isLoading = ref(true)
const isUploading = ref(false)
const isSaving = ref(false)

// Edit form
const editForm = ref({
    student_number: '',
    title_name: '',
    first_name_thai: '',
    last_name_thai: '',
    first_name_english: '',
    last_name_english: ''
})

// Fetch students
const fetchStudents = async () => {
    isLoading.value = true
    try {
        const response = await $fetch(`${apiBase}/student-card/admin/students/${level.value}/${room.value}`)
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
        await $fetch(`${apiBase}/student-card/admin/upload-photo/${studentId}`, {
            method: 'POST',
            body: formData
        })
        
        Swal.fire({
            icon: 'success',
            title: 'สำเร็จ',
            text: 'อัปโหลดรูปภาพเรียบร้อยแล้ว',
            timer: 1500,
            showConfirmButton: false
        })
        
        await fetchStudents()
        
        // Update selected student if viewing
        if (selectedStudent.value?.id === studentId) {
            selectedStudent.value = students.value.find(s => s.id === studentId)
        }
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

// Update student name (Thai)
const updateStudentNameTh = async (studentId: number) => {
    isSaving.value = true
    try {
        await $fetch(`${apiBase}/student-card/admin/update-name-th/${studentId}`, {
            method: 'PATCH',
            body: {
                title_name: editForm.value.title_name,
                first_name_thai: editForm.value.first_name_thai,
                last_name_thai: editForm.value.last_name_thai
            }
        })
        
        Swal.fire({
            icon: 'success',
            title: 'สำเร็จ',
            text: 'บันทึกข้อมูลเรียบร้อยแล้ว',
            timer: 1500,
            showConfirmButton: false
        })
        
        await fetchStudents()
        closeDetail()
    } catch (error) {
        console.error('Error updating student:', error)
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่สามารถบันทึกข้อมูลได้'
        })
    } finally {
        isSaving.value = false
    }
}

// Update student ID
const updateStudentID = async (studentId: number) => {
    isSaving.value = true
    try {
        await $fetch(`${apiBase}/student-card/admin/update-code/${studentId}`, {
            method: 'PATCH',
            body: {
                student_number: editForm.value.student_number
            }
        })
        
        Swal.fire({
            icon: 'success',
            title: 'สำเร็จ',
            text: 'บันทึกรหัสนักเรียนเรียบร้อยแล้ว',
            timer: 1500,
            showConfirmButton: false
        })
        
        await fetchStudents()
        closeDetail()
    } catch (error) {
        console.error('Error updating student ID:', error)
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่สามารถบันทึกรหัสนักเรียนได้'
        })
    } finally {
        isSaving.value = false
    }
}

// Handle file input
const handleFileChange = (event: Event, studentId: number) => {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0]
    if (file) {
        uploadPhoto(studentId, file)
    }
    target.value = ''
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

// Select student for editing
const selectStudent = (student: any) => {
    selectedStudent.value = student
    editForm.value = {
        student_number: student.student_number || '',
        title_name: student.title_name || '',
        first_name_thai: student.first_name_thai || '',
        last_name_thai: student.last_name_thai || '',
        first_name_english: student.first_name_english || '',
        last_name_english: student.last_name_english || ''
    }
}

// Close detail modal
const closeDetail = () => {
    selectedStudent.value = null
}

// File input ref
const fileInputRef = ref<HTMLInputElement | null>(null)

const triggerFileInput = () => {
    fileInputRef.value?.click()
}

onMounted(() => {
    fetchStudents()
})
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-purple-100 via-white to-purple-200">
        
        <!-- Header -->
        <div class="bg-white shadow-sm sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <NuxtLink 
                            to="/student-card/admin" 
                            class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                        >
                            <Icon icon="heroicons:arrow-left" class="w-6 h-6 text-gray-600" />
                        </NuxtLink>
                        <div>
                            <div class="flex items-center gap-2">
                                <h1 class="text-xl font-bold text-gray-800">จัดการบัตรนักเรียน {{ levelName }}/{{ room }}</h1>
                                <span class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded text-xs font-medium">Admin</span>
                            </div>
                            <p class="text-sm text-gray-500">{{ students.length }} คน</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="flex items-center justify-center py-20">
            <div class="text-center">
                <div class="w-12 h-12 border-4 border-purple-500 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
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
                    class="bg-white rounded-xl shadow-md overflow-hidden"
                >
                    <!-- Student Card Preview -->
                    <div class="relative aspect-[1.6/1] bg-gradient-to-br from-purple-500 to-purple-600 p-4">
                        <div class="absolute top-2 right-2 bg-white/20 backdrop-blur px-2 py-1 rounded text-xs text-white">
                            {{ student.student_number }}
                        </div>
                        
                        <div class="flex items-center gap-3 h-full">
                            <!-- Photo -->
                            <div class="w-20 h-24 bg-white rounded-lg overflow-hidden flex-shrink-0 relative group">
                                <img 
                                    v-if="getStudentImageUrl(student)"
                                    :src="getStudentImageUrl(student)" 
                                    :alt="student.full_name_thai"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center bg-gray-100">
                                    <Icon icon="heroicons:user" class="w-8 h-8 text-gray-400" />
                                </div>
                                
                                <!-- Upload overlay -->
                                <label class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                                    <Icon icon="heroicons:camera" class="w-6 h-6 text-white" />
                                    <input 
                                        type="file" 
                                        accept="image/*" 
                                        class="hidden"
                                        @change="handleFileChange($event, student.id)"
                                    />
                                </label>
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
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">เลขที่ {{ student.order_no }}</span>
                            <button 
                                @click="selectStudent(student)"
                                class="px-3 py-1.5 bg-purple-100 text-purple-700 rounded-lg text-sm font-medium hover:bg-purple-200 transition-colors"
                            >
                                <Icon icon="heroicons:pencil-square" class="w-4 h-4 inline mr-1" />
                                แก้ไข
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <Teleport to="body">
            <div 
                v-if="selectedStudent" 
                class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
                @click.self="closeDetail"
            >
                <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                    <!-- Modal Header -->
                    <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between">
                        <h2 class="text-lg font-bold text-gray-800">แก้ไขข้อมูลนักเรียน</h2>
                        <button @click="closeDetail" class="p-2 hover:bg-gray-100 rounded-lg">
                            <Icon icon="heroicons:x-mark" class="w-6 h-6 text-gray-500" />
                        </button>
                    </div>
                    
                    <div class="p-6">
                        <!-- Photo Section -->
                        <div class="flex items-center gap-6 mb-6">
                            <div class="relative">
                                <div class="w-28 h-36 bg-gray-100 rounded-xl overflow-hidden">
                                    <img 
                                        v-if="getStudentImageUrl(selectedStudent)"
                                        :src="getStudentImageUrl(selectedStudent)" 
                                        :alt="selectedStudent.full_name_thai"
                                        class="w-full h-full object-cover"
                                    />
                                    <div v-else class="w-full h-full flex items-center justify-center">
                                        <Icon icon="heroicons:user" class="w-12 h-12 text-gray-400" />
                                    </div>
                                </div>
                                <label class="absolute -bottom-2 -right-2 p-2 bg-purple-500 text-white rounded-full cursor-pointer hover:bg-purple-600 transition-colors shadow-lg">
                                    <Icon icon="heroicons:camera" class="w-5 h-5" />
                                    <input 
                                        type="file" 
                                        accept="image/*" 
                                        class="hidden"
                                        @change="handleFileChange($event, selectedStudent.id)"
                                    />
                                </label>
                            </div>
                            
                            <div>
                                <p class="text-lg font-bold text-gray-800">{{ selectedStudent.full_name_thai }}</p>
                                <p class="text-sm text-gray-500">{{ levelName }}/{{ room }} เลขที่ {{ selectedStudent.order_no }}</p>
                            </div>
                        </div>
                        
                        <!-- Edit Form -->
                        <div class="space-y-4">
                            <!-- Student ID -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">รหัสนักเรียน</label>
                                <div class="flex gap-2">
                                    <input 
                                        v-model="editForm.student_number"
                                        type="text"
                                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                    />
                                    <button 
                                        @click="updateStudentID(selectedStudent.id)"
                                        :disabled="isSaving"
                                        class="px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 disabled:opacity-50 transition-colors"
                                    >
                                        บันทึก
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Thai Name -->
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">คำนำหน้า</label>
                                    <select 
                                        v-model="editForm.title_name"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                    >
                                        <option value="">เลือก</option>
                                        <option value="นางสาว">นางสาว</option>
                                        <option value="นาย">นาย</option>
                                        <option value="เด็กหญิง">เด็กหญิง</option>
                                        <option value="เด็กชาย">เด็กชาย</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ (ไทย)</label>
                                    <input 
                                        v-model="editForm.first_name_thai"
                                        type="text"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">นามสกุล (ไทย)</label>
                                    <input 
                                        v-model="editForm.last_name_thai"
                                        type="text"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                    />
                                </div>
                            </div>
                            
                            <div class="flex justify-end">
                                <button 
                                    @click="updateStudentNameTh(selectedStudent.id)"
                                    :disabled="isSaving"
                                    class="px-6 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 disabled:opacity-50 transition-colors"
                                >
                                    <Icon v-if="isSaving" icon="heroicons:arrow-path" class="w-5 h-5 animate-spin inline mr-2" />
                                    บันทึกชื่อ-นามสกุล
                                </button>
                            </div>
                            
                            <!-- National ID (Read only) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">เลขบัตรประชาชน</label>
                                <input 
                                    :value="formatNationalId(selectedStudent.national_id)"
                                    type="text"
                                    disabled
                                    class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 font-mono"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
        
        <!-- Upload indicator -->
        <div v-if="isUploading" class="fixed inset-0 bg-black/50 z-[60] flex items-center justify-center">
            <div class="bg-white rounded-xl p-6 text-center">
                <div class="w-12 h-12 border-4 border-purple-500 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
                <p class="text-gray-600">กำลังอัปโหลดรูปภาพ...</p>
            </div>
        </div>
    </div>
</template>
