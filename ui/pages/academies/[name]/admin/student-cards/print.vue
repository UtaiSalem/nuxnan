<script setup lang="ts">
/**
 * Academy Admin - Print Student Cards
 * หน้าพิมพ์บัตรนักเรียน (ด้านหน้า + ด้านหลัง)
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
const sections = ref<Record<string, string[]>>({})
const isLoading = ref(true)
const isPrinting = ref(false)

// Filters
const selectedLevel = ref('')
const selectedRoom = ref('')
const printBackSide = ref(true)

// Academy Role
const academyId = ref<number | null>(null)
const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()
      
      if (!isAdmin.value && !can('students.view')) {
        navigateTo(`/academies/${academyName.value}`)
        return
      }
      
      await fetchStatistics()
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

const fetchStatistics = async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/student-cards/statistics`)
    if (response.success) {
      levels.value = Object.keys(response.statistics.byLevel || {}).sort()
      sections.value = response.statistics.sectionsByLevel || {}
    }
  } catch (err) {
    // Fallback
    try {
      const response: any = await api.get(`/api/academies/${academyId.value}/student-cards/levels`)
      if (response.success) levels.value = response.levels || []
    } catch (legacyErr) {
      console.error('Failed to fetch levels:', legacyErr)
    }
  }
}

const fetchStudents = async () => {
  if (!selectedLevel.value || !selectedRoom.value) return
  
  isLoading.value = true
  try {
    const response: any = await api.get(
      `/api/academies/${academyId.value}/student-cards/${selectedLevel.value}/${selectedRoom.value}`
    )
    if (response.success) {
      students.value = response.students || []
      selectedStudents.value = new Set()
    }
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
  nextTick(() => {
    window.print()
    isPrinting.value = false
  })
}

const getProfileImage = (student: any) => {
  if (student.profile_image) {
    return `/storage/images/students/${student.class_level}/${student.class_section}/${student.profile_image}`
  }
  return '/images/default-avatar.png'
}

const roomsForLevel = computed(() => {
  if (!selectedLevel.value) return []
  return sections.value[selectedLevel.value] || ['1','2','3','4','5','6','7','8','9','10']
})

const studentsToPrint = computed(() => {
  if (selectedStudents.value.size === 0) return students.value
  return students.value.filter(s => selectedStudents.value.has(s.id))
})

const academyLogo = computed(() => {
  return academy.value?.logo ? `/storage/${academy.value.logo}` : '/images/default-school-logo.png'
})

const academyAddress = computed(() => academy.value?.address || '')
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
          v-if="studentsToPrint.length > 0"
          @click="printCards"
          :disabled="isPrinting"
          class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 flex items-center gap-2"
        >
          <Icon icon="fluent:print-24-regular" class="w-5 h-5" />
          <span>พิมพ์ ({{ studentsToPrint.length }} ใบ)</span>
        </button>
      </div>

      <!-- Filters (No Print) -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 print:hidden">
        <div class="flex flex-wrap gap-4 items-end">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ระดับชั้น</label>
            <select
              v-model="selectedLevel"
              @change="selectedRoom = ''; students = []"
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
              <option v-for="room in roomsForLevel" :key="room" :value="room">{{ room }}</option>
            </select>
          </div>
          
          <div class="flex items-center gap-2">
            <input id="print-back" v-model="printBackSide" type="checkbox" class="rounded border-gray-300" />
            <label for="print-back" class="text-sm text-gray-700 dark:text-gray-300">พิมพ์ด้านหลังด้วย</label>
          </div>

          <div v-if="students.length > 0">
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
        
        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-3 p-4">
          <div
            v-for="student in students"
            :key="student.id"
            @click="toggleStudent(student.id)"
            :class="[
              'cursor-pointer rounded-lg border-2 p-2 transition-all',
              selectedStudents.has(student.id) 
                ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' 
                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'
            ]"
          >
            <img :src="getProfileImage(student)" class="w-full aspect-[3/4] object-cover rounded-lg mb-1" />
            <p class="text-xs font-medium text-gray-900 dark:text-white truncate">{{ student.first_name_thai }}</p>
            <p class="text-[10px] text-gray-500 dark:text-gray-400">{{ student.student_number }}</p>
          </div>
        </div>
      </div>

      <!-- On-Screen Preview (No Print) -->
      <div v-if="studentsToPrint.length > 0" class="space-y-4 print:hidden">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ตัวอย่างบัตร</h3>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <div v-for="student in studentsToPrint.slice(0, 4)" :key="'preview-' + student.id">
            <LazyLearnStudentCardStudentCardFront
              :student="student"
              :academy-name="academy?.name"
              :academy-logo="academyLogo"
              :academy-address="academyAddress"
            />
          </div>
        </div>
        <p v-if="studentsToPrint.length > 4" class="text-sm text-gray-500 text-center">
          ... และอีก {{ studentsToPrint.length - 4 }} ใบ (จะแสดงในหน้าพิมพ์)
        </p>
      </div>

      <!-- Print Area (Hidden on Screen, Shown on Print) -->
      <div v-if="studentsToPrint.length > 0" class="hidden print:block">
        <div
          v-for="(student, idx) in studentsToPrint"
          :key="'print-' + student.id"
          class="print-card-wrapper"
        >
          <!-- Front Side -->
          <div class="print-card">
            <LazyLearnStudentCardStudentCardFront
              :student="student"
              :academy-name="academy?.name"
              :academy-logo="academyLogo"
              :academy-address="academyAddress"
              :show-qr-code="true"
              :card-id="'print-front-' + student.id"
            />
          </div>
          
          <!-- Back Side (if enabled) -->
          <div v-if="printBackSide" class="print-card">
            <LazyLearnStudentCardStudentCardBack
              :academy-name="academy?.name"
              :academy-logo="academyLogo"
              :academy-address="academyAddress"
              :card-id="'print-back-' + student.id"
            />
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
    size: A4 landscape;
    margin: 5mm;
  }
  
  .print\:hidden {
    display: none !important;
  }
  
  .print\:block {
    display: block !important;
  }
  
  .print-card-wrapper {
    display: flex;
    gap: 8mm;
    margin-bottom: 8mm;
    page-break-inside: avoid;
  }

  .print-card {
    width: 85.6mm;
    height: 54mm;
    flex-shrink: 0;
  }

  .print-card .student-card-front,
  .print-card .student-card-back {
    width: 100% !important;
    max-width: 100% !important;
    aspect-ratio: auto !important;
    height: 100%;
    border-radius: 8px !important;
  }
}
</style>
