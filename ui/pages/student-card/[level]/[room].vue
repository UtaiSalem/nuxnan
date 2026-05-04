<script setup lang="ts">
import { ref, computed } from 'vue'
import StudentCardItem from '~/components/student-card/StudentCardItem.vue'

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

onMounted(fetchStudents)
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
                />
            </div>
        </div>
    </div>
</template>
