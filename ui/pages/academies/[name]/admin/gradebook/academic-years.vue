<script setup lang="ts">
/**
 * Academy Academic Years Management
 * หน้าจัดการปีการศึกษาและภาคเรียน
 */
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'main'
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)

// State
const academy = ref<any>(null)
const academyId = ref<number | null>(null)
const isLoading = ref(true)
const isSaving = ref(false)

// Data
const academicYears = ref<any[]>([])

// Modal
const showModal = ref(false)
const editingYear = ref<any>(null)
const form = ref({
  name: '',
  start_date: '',
  end_date: '',
  is_current: false,
  create_semesters: true,
})

const { isAdmin, fetchMyRole } = useAcademyRole(academyId)

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()
      
      if (!isAdmin.value) {
        navigateTo(`/academies/${academyName.value}`)
        return
      }
      
      await fetchData()
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

const fetchData = async () => {
  await fetchAcademicYears()
}

const fetchAcademicYears = async () => {
  try {
    const res: any = await api.get(`/api/academies/${academyId.value}/academic-years`)
    if (res.success) {
      academicYears.value = res.academicYears || []
    }
  } catch (err) {
    console.error('Failed to fetch academic years:', err)
  }
}

const openCreateModal = () => {
  editingYear.value = null
  
  // Auto-generate name based on current year
  const currentYear = new Date().getFullYear() + 543 // BE year
  form.value = {
    name: `${currentYear}`,
    start_date: `${new Date().getFullYear()}-05-16`, // May 16
    end_date: `${new Date().getFullYear() + 1}-03-31`, // March 31 next year
    is_current: academicYears.value.length === 0,
    create_semesters: true,
  }
  showModal.value = true
}

const openEditModal = (year: any) => {
  editingYear.value = year
  form.value = {
    name: year.name,
    start_date: year.start_date?.split('T')[0] || '',
    end_date: year.end_date?.split('T')[0] || '',
    is_current: year.is_current,
    create_semesters: false,
  }
  showModal.value = true
}

const saveAcademicYear = async () => {
  if (!form.value.name || !form.value.start_date || !form.value.end_date) {
    alert('กรุณากรอกข้อมูลให้ครบถ้วน')
    return
  }

  isSaving.value = true
  try {
    if (editingYear.value) {
      await api.put(`/api/academies/${academyId.value}/academic-years/${editingYear.value.id}`, form.value)
    } else {
      await api.post(`/api/academies/${academyId.value}/academic-years`, form.value)
    }
    
    showModal.value = false
    await fetchAcademicYears()
  } catch (err: any) {
    console.error('Failed to save:', err)
    alert(err.message || 'เกิดข้อผิดพลาด')
  } finally {
    isSaving.value = false
  }
}

const deleteAcademicYear = async (year: any) => {
  if (!confirm(`ต้องการลบปีการศึกษา "${year.name}" หรือไม่? ข้อมูลภาคเรียนทั้งหมดจะถูกลบด้วย`)) return
  
  try {
    await api.delete(`/api/academies/${academyId.value}/academic-years/${year.id}`)
    await fetchAcademicYears()
  } catch (err) {
    console.error('Failed to delete:', err)
    alert('ไม่สามารถลบได้')
  }
}

const setAsCurrent = async (year: any) => {
  try {
    await api.put(`/api/academies/${academyId.value}/academic-years/${year.id}`, {
      is_current: true,
    })
    await fetchAcademicYears()
  } catch (err) {
    console.error('Failed to set as current:', err)
  }
}

const setSemesterAsCurrent = async (yearId: number, semester: any) => {
  try {
    await api.put(`/api/academies/${academyId.value}/academic-years/${yearId}/semesters/${semester.id}`, {
      is_current: true,
    })
    await fetchAcademicYears()
  } catch (err) {
    console.error('Failed to set semester as current:', err)
  }
}

const formatDate = (dateStr: string) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  return date.toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: 'numeric' })
}
</script>

<template>
  <div>
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <div class="flex items-center gap-3 mb-2">
            <NuxtLink :to="`/academies/${academyName}/admin/gradebook`" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
              <Icon icon="fluent:arrow-left-24-filled" class="w-5 h-5" />
            </NuxtLink>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
              <Icon icon="fluent:calendar-24-filled" class="w-7 h-7 text-blue-500" />
              ปีการศึกษา & ภาคเรียน
            </h1>
          </div>
          <p class="text-gray-600 dark:text-gray-400">จัดการปีการศึกษาและภาคเรียน</p>
        </div>

        <button
          @click="openCreateModal"
          class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-medium transition-colors flex items-center gap-2"
        >
          <Icon icon="fluent:add-24-filled" class="w-5 h-5" />
          เพิ่มปีการศึกษา
        </button>
      </div>

      <!-- Academic Years List -->
      <div v-if="academicYears.length === 0" class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center shadow-sm border border-gray-200 dark:border-gray-700">
        <Icon icon="fluent:calendar-24-regular" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">ยังไม่มีปีการศึกษา</h3>
        <p class="text-gray-600 dark:text-gray-400 mb-6">เริ่มต้นด้วยการสร้างปีการศึกษาใหม่</p>
        <button
          @click="openCreateModal"
          class="inline-flex items-center gap-2 px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-medium transition-colors"
        >
          <Icon icon="fluent:add-24-filled" class="w-5 h-5" />
          สร้างปีการศึกษา
        </button>
      </div>

      <div v-else class="space-y-4">
        <div
          v-for="year in academicYears"
          :key="year.id"
          class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
        >
          <div class="p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
              <div class="flex items-center gap-4">
                <div :class="['w-14 h-14 rounded-xl flex items-center justify-center', year.is_current ? 'bg-primary-100 dark:bg-primary-900/50' : 'bg-gray-100 dark:bg-gray-700']">
                  <Icon icon="fluent:calendar-24-filled" :class="['w-7 h-7', year.is_current ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400']" />
                </div>
                <div>
                  <div class="flex items-center gap-3">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">ปีการศึกษา {{ year.name }}</h3>
                    <span v-if="year.is_current" class="px-2 py-0.5 text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900/50 dark:text-primary-400 rounded-full">
                      ปัจจุบัน
                    </span>
                  </div>
                  <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ formatDate(year.start_date) }} - {{ formatDate(year.end_date) }}
                  </p>
                </div>
              </div>
              
              <div class="flex items-center gap-2">
                <button
                  v-if="!year.is_current"
                  @click="setAsCurrent(year)"
                  class="px-3 py-1.5 text-sm text-primary-600 dark:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg transition-colors"
                >
                  ตั้งเป็นปัจจุบัน
                </button>
                <button
                  @click="openEditModal(year)"
                  class="p-2 text-gray-500 hover:text-primary-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                >
                  <Icon icon="fluent:edit-24-regular" class="w-5 h-5" />
                </button>
                <button
                  @click="deleteAcademicYear(year)"
                  class="p-2 text-gray-500 hover:text-red-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                >
                  <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
                </button>
              </div>
            </div>
          </div>
          
          <!-- Semesters -->
          <div class="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 p-4">
            <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-3">ภาคเรียน</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
              <div
                v-for="semester in year.semesters"
                :key="semester.id"
                :class="[
                  'p-4 rounded-lg border',
                  semester.is_current 
                    ? 'bg-primary-50 dark:bg-primary-900/20 border-primary-300 dark:border-primary-700'
                    : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-600'
                ]"
              >
                <div class="flex items-center justify-between mb-2">
                  <span :class="['font-medium', semester.is_current ? 'text-primary-700 dark:text-primary-300' : 'text-gray-900 dark:text-white']">
                    {{ semester.name }}
                  </span>
                  <span v-if="semester.is_current" class="px-2 py-0.5 text-xs font-medium bg-primary-500 text-white rounded-full">
                    ปัจจุบัน
                  </span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                  {{ formatDate(semester.start_date) }} - {{ formatDate(semester.end_date) }}
                </p>
                <button
                  v-if="!semester.is_current"
                  @click="setSemesterAsCurrent(year.id, semester)"
                  class="mt-2 text-xs text-primary-600 dark:text-primary-400 hover:underline"
                >
                  ตั้งเป็นภาคเรียนปัจจุบัน
                </button>
              </div>
              
              <div v-if="!year.semesters?.length" class="col-span-3 text-center py-4 text-gray-500 dark:text-gray-400 text-sm">
                ยังไม่มีภาคเรียน
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/50" @click="showModal = false"></div>
        
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
          <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
              {{ editingYear ? 'แก้ไขปีการศึกษา' : 'เพิ่มปีการศึกษาใหม่' }}
            </h2>
          </div>
          
          <form @submit.prevent="saveAcademicYear" class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อปีการศึกษา *</label>
              <input
                v-model="form.name"
                type="text"
                required
                placeholder="เช่น 2567"
                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
            </div>
            
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันเริ่มต้น *</label>
                <input
                  v-model="form.start_date"
                  type="date"
                  required
                  class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันสิ้นสุด *</label>
                <input
                  v-model="form.end_date"
                  type="date"
                  required
                  class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>
            </div>
            
            <div class="flex items-center gap-2">
              <input
                id="is-current"
                v-model="form.is_current"
                type="checkbox"
                class="w-4 h-4 text-primary-500 border-gray-300 rounded focus:ring-primary-500"
              />
              <label for="is-current" class="text-sm text-gray-700 dark:text-gray-300">
                ตั้งเป็นปีการศึกษาปัจจุบัน
              </label>
            </div>
            
            <div v-if="!editingYear" class="flex items-center gap-2">
              <input
                id="create-semesters"
                v-model="form.create_semesters"
                type="checkbox"
                class="w-4 h-4 text-primary-500 border-gray-300 rounded focus:ring-primary-500"
              />
              <label for="create-semesters" class="text-sm text-gray-700 dark:text-gray-300">
                สร้างภาคเรียนอัตโนมัติ (ภาคเรียนที่ 1, 2)
              </label>
            </div>
            
            <div class="flex items-center gap-3 pt-4">
              <button
                type="button"
                @click="showModal = false"
                class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
              >
                ยกเลิก
              </button>
              <button
                type="submit"
                :disabled="isSaving"
                class="flex-1 px-4 py-2 bg-primary-500 hover:bg-primary-600 disabled:bg-gray-400 text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2"
              >
                <Icon v-if="isSaving" icon="fluent:spinner-ios-20-filled" class="w-5 h-5 animate-spin" />
                {{ isSaving ? 'กำลังบันทึก...' : 'บันทึก' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
