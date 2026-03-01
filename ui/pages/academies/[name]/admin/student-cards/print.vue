<script setup lang="ts">
/**
 * Academy Admin - Print Student Cards
 * หน้าพิมพ์บัตรนักเรียน
 */
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: false
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)

// State
const academy = ref<any>(null)
const students = ref<any[]>([])
const selectedStudents = ref<Set<number>>(new Set())
const levels = ref<string[]>([])
const isLoading = ref(true)
const isPrinting = ref(false)

// Filters
const selectedLevel = ref('')
const selectedRoom = ref('')

// Academy Role
const academyId = ref<number | null>(null)
const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${encodeURIComponent(academyName.value)}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()
      
      if (!isAdmin.value && !can('students.view')) {
        navigateTo(`/academies/${academyName.value}`)
        return
      }
      
      await fetchLevels()
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

const fetchLevels = async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/student-cards/levels`)
    if (response.success) {
      levels.value = response.levels || []
    }
  } catch (err) {
    console.error('Failed to fetch levels:', err)
  }
}

const fetchStudents = async () => {
  if (!selectedLevel.value || !selectedRoom.value) return
  
  isLoading.value = true
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/student-cards/by-room`, {
      params: { level: selectedLevel.value, section: selectedRoom.value }
    })
    students.value = response.students || []
    selectedStudents.value = new Set()
  } catch (err) {
    console.error('Failed to fetch students:', err)
  } finally {
    isLoading.value = false
  }
}

const toggleStudent = (id: number) => {
  if (selectedStudents.value.has(id)) {
    selectedStudents.value.delete(id)
  } else {
    selectedStudents.value.add(id)
  }
  selectedStudents.value = new Set(selectedStudents.value)
}

const selectAll = () => {
  if (selectedStudents.value.size === students.value.length) {
    selectedStudents.value = new Set()
  } else {
    selectedStudents.value = new Set(students.value.map(s => s.id))
  }
}

const printCards = () => {
  isPrinting.value = true
  setTimeout(() => {
    window.print()
    isPrinting.value = false
  }, 100)
}

const getProfileImage = (student: any) => {
  if (student.profile_image) {
    return `/storage/images/students/${student.class_level}/${student.class_section}/${student.profile_image}`
  }
  return '/images/default-avatar.png'
}

const rooms = computed(() => ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'])

const studentsToprint = computed(() => {
  if (selectedStudents.value.size === 0) return students.value
  return students.value.filter(s => selectedStudents.value.has(s.id))
})
</script>

<template>
  <div>
    <div class="space-y-6 print:space-y-0">
      <!-- Header (No Print) -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 print:hidden">
        <div class="flex items-center gap-4">
          <NuxtLink 
            :to="`/academies/${academyName}/admin/student-cards`"
            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
          >
            <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
          </NuxtLink>
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">พิมพ์บัตรนักเรียน</h1>
            <p class="text-gray-600 dark:text-gray-400">เลือกนักเรียนและพิมพ์บัตร</p>
          </div>
        </div>
        
        <button
          v-if="studentsToprint.length > 0"
          @click="printCards"
          :disabled="isPrinting"
          class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 flex items-center gap-2"
        >
          <Icon icon="fluent:print-24-regular" class="w-5 h-5" />
          <span>พิมพ์ ({{ studentsToprint.length }} ใบ)</span>
        </button>
      </div>

      <!-- Filters (No Print) -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 print:hidden">
        <div class="flex flex-wrap gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ระดับชั้น</label>
            <select
              v-model="selectedLevel"
              @change="selectedRoom = ''"
              class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            >
              <option value="">เลือกระดับชั้น</option>
              <option v-for="level in levels" :key="level" :value="level">ม.{{ level }}</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ห้อง</label>
            <select
              v-model="selectedRoom"
              @change="fetchStudents"
              class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            >
              <option value="">เลือกห้อง</option>
              <option v-for="room in rooms" :key="room" :value="room">{{ room }}</option>
            </select>
          </div>
          
          <div v-if="students.length > 0" class="flex items-end">
            <button
              @click="selectAll"
              class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              {{ selectedStudents.size === students.length ? 'ยกเลิกทั้งหมด' : 'เลือกทั้งหมด' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Student Selection (No Print) -->
      <div v-if="students.length > 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden print:hidden">
        <div class="p-4 border-b border-gray-100 dark:border-gray-700">
          <p class="text-sm text-gray-600 dark:text-gray-400">
            เลือกแล้ว {{ selectedStudents.size }} จาก {{ students.length }} คน
            <span v-if="selectedStudents.size === 0" class="text-yellow-600">(ถ้าไม่เลือก จะพิมพ์ทั้งหมด)</span>
          </p>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 p-4">
          <div
            v-for="student in students"
            :key="student.id"
            @click="toggleStudent(student.id)"
            :class="[
              'cursor-pointer rounded-lg border-2 p-3 transition-all',
              selectedStudents.has(student.id) 
                ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' 
                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'
            ]"
          >
            <img 
              :src="getProfileImage(student)"
              class="w-full aspect-[3/4] object-cover rounded-lg mb-2"
            />
            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
              {{ student.first_name_thai }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              {{ student.student_number }}
            </p>
          </div>
        </div>
      </div>

      <!-- Print Preview / Print Area -->
      <div v-if="studentsToprint.length > 0" class="hidden print:block">
        <div class="grid grid-cols-2 gap-4">
          <div
            v-for="student in studentsToprint"
            :key="student.id"
            class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-lg p-4 text-white text-sm"
            style="page-break-inside: avoid; height: 200px;"
          >
            <div class="text-center mb-2">
              <p class="font-bold">{{ academy?.name }}</p>
              <p class="text-xs opacity-80">บัตรประจำตัวนักเรียน</p>
            </div>
            
            <div class="flex gap-3">
              <div class="w-16 h-20 bg-white rounded overflow-hidden shrink-0">
                <img :src="getProfileImage(student)" class="w-full h-full object-cover" />
              </div>
              
              <div class="flex-1 space-y-1">
                <div>
                  <p class="text-[10px] opacity-70">ชื่อ-นามสกุล</p>
                  <p class="font-medium text-xs">{{ student.first_name_thai }} {{ student.last_name_thai }}</p>
                </div>
                <div>
                  <p class="text-[10px] opacity-70">รหัสนักเรียน</p>
                  <p class="font-mono">{{ student.student_number }}</p>
                </div>
                <div class="flex gap-2">
                  <div>
                    <p class="text-[10px] opacity-70">ชั้น</p>
                    <p>ม.{{ student.class_level }}</p>
                  </div>
                  <div>
                    <p class="text-[10px] opacity-70">ห้อง</p>
                    <p>{{ student.class_section }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="!isLoading && selectedLevel && selectedRoom" class="text-center py-12 print:hidden">
        <Icon icon="fluent:people-24-regular" class="w-16 h-16 mx-auto text-gray-300 mb-4" />
        <p class="text-gray-500 dark:text-gray-400">ไม่พบนักเรียนในห้องนี้</p>
      </div>
    </div>
  </div>
</template>

<style>
@media print {
  @page {
    size: A4;
    margin: 10mm;
  }
  
  body * {
    visibility: hidden;
  }
  
  .print\\:block,
  .print\\:block * {
    visibility: visible;
  }
}
</style>
