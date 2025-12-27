<script setup>
import StudentCard from './StudentCard.vue'
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
// import Swal from 'sweetalert2'
const props = defineProps({
    students: {
        type: Array,
        default: () => []
    },
    room: String,
    level: String,
})

const searchTerm = ref('')
const filteredStudents = computed(() => {
    if (!searchTerm.value) return props.students
    if (!props.students) return []
    
    return props.students.filter(student => {
        const searchTermLower = searchTerm.value.toLowerCase()
        return (
            (student.student_name_th && student.student_name_th.toLowerCase().includes(searchTermLower)) ||
            (student.student_id && student.student_id.toString().includes(searchTerm.value))
        )
    })
})

const handlePhotoUpload = async ({id, studentId, file }) => {

    // console.log('Uploading photo for student:', studentId, 'id:', id, 'File:', file)
    if (!file || !studentId) return
    // return;

    const formData = new FormData()
    formData.append('photo', file)
    formData.append('_method', 'patch')

    try {
        // ส่ง API request เพื่ออัพโหลดรูปภาพ
        const response = await axios.post('/student-card/admin/upload-photo/'+ id, formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })

    } catch (error) {
        console.error('Upload failed:', error)
    }
}

const handleUpdateStudent = async (updatedData) => {
    try {
        const response = await axios.put(`/student/${updatedData.id}`, updatedData)
        if (response.data.success) {
            // Update local state
            const index = props.students.findIndex(s => s.id === updatedData.id)
            if (index !== -1) {
                props.students[index] = { ...props.students[index], ...updatedData }
            }
            alert('บันทึกข้อมูลสำเร็จ')
        }
    } catch (error) {
        console.error('Update failed:', error)
        alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล')
    }
}

// Function to handle save student card as image
const handleSaveStudentCard = async (studentId) => {
    const student = props.students.find(s => s.id === studentId)
    if (!student) return

    try {
        // const response = await axios.get(`/student/${studentId}/card`, {
        //     responseType: 'blob'
        // })

        const blob = new Blob([response.data], { type: 'image/png' })
        const url = URL.createObjectURL(blob)

        const link = document.createElement('a')
        link.href = url
        link.download = `student_card_${student.student_id}.png`
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
    } catch (error) {
        console.error('Error saving student card:', error)
    }
}
</script>

<template>
    <Head :title="`ข้อมูลนักเรียน - ชั้น ม.${level}/${room}`" />
    <div class="min-h-screen">
        <!-- Header Section -->
        <div class="max-w-7xl bg-slate-200 mx-auto">
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
                            <input type="text" 
                                v-model="searchTerm"
                                placeholder="ค้นหาชื่อหรือรหัสนักเรียน..."
                                class="w-full px-4 py-2 pr-10 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none" />
                            <svg class="w-5 h-5 text-gray-400 absolute right-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <!-- Back Button -->
                        <div class="flex items-center w-full sm:w-auto">
                            <button @click="$inertia.visit('/student-card')" 
                                class="w-full sm:w-auto px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200">
                                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                <div v-for="student in filteredStudents" :key="student.id">
                    <StudentCard 
                        :studentInfo="student"
                        @update="handleUpdateStudent"
                        @upload-image="handlePhotoUpload" 
                    />
                </div>
            </div>
        </div>
    </div>
</template>


