<script setup lang="ts">
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

definePageMeta({ layout: 'main' })

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)

const isLoading = ref(true)
const academyId = ref<number | null>(null)
const periodSets = ref<any[]>([])
const classrooms = ref<any[]>([])
const uniqueGradeLevels = ref<string[]>([])

const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)

// Form state
const isFormOpen = ref(false)
const isSubmitting = ref(false)
const formMode = ref<'create'|'edit'>('create')
const formData = ref<any>({
  id: null,
  name: '',
  days: [],
  grade_levels: [],
  is_default: false,
  is_active: true,
  display_order: 0,
  periods: []
})
const formError = ref('')
const fieldErrors = ref<any>({})

const daysOfWeek = [
  { value: 1, label: 'จ' },
  { value: 2, label: 'อ' },
  { value: 3, label: 'พ' },
  { value: 4, label: 'พฤ' },
  { value: 5, label: 'ศ' },
  { value: 6, label: 'ส' },
  { value: 7, label: 'อา' },
]

const periodTypes = [
  { value: 'class', label: 'คาบเรียน' },
  { value: 'break', label: 'พักเบรก' },
  { value: 'lunch', label: 'พักกลางวัน' },
  { value: 'activity', label: 'กิจกรรม' },
]

onMounted(async () => {
  try {
    const academyRes: any = await api.get('/api/academies/' + academyName.value)
    if (academyRes?.success) {
      academyId.value = academyRes.academy.id
      await fetchMyRole()
      
      if (!isAdmin.value && !can('schedule.view') && !can('academy.view')) {
        navigateTo(`/academies/${academyName.value}`)
        return
      }

      await fetchClassrooms()
      await fetchPeriodSets()
    }
  } catch (error) {
    console.error(error)
  } finally {
    isLoading.value = false
  }
})

const fetchPeriodSets = async () => {
  if (!academyId.value) return
  try {
    const res: any = await api.get(`/api/academies/${academyId.value}/schedule-period-sets`)
    if (res?.success) {
      periodSets.value = res.data || []
    }
  } catch (error) {
    console.error(error)
  }
}

const fetchClassrooms = async () => {
  if (!academyId.value) return
  try {
    const res: any = await api.get(`/api/academies/${academyId.value}/classrooms`)
    const list = res?.classrooms || res?.data || []
    classrooms.value = list
    const grades = new Set(list.map((c: any) => c.grade_level).filter(Boolean))
    uniqueGradeLevels.value = Array.from(grades).sort() as string[]
  } catch (error) {
    console.error(error)
  }
}

// Actions
const canManage = computed(() => isAdmin.value || can('schedule.manage'))

const openCreateForm = () => {
  formMode.value = 'create'
  formData.value = {
    id: null,
    name: '',
    days: [],
    grade_levels: [],
    is_default: false,
    is_active: true,
    display_order: 0,
    periods: []
  }
  formError.value = ''
  fieldErrors.value = {}
  isFormOpen.value = true
}

const applyTemplate = () => {
  openCreateForm()
  formData.value.name = 'โครงคาบเรียนมัธยม'
  formData.value.periods = [
    { period_number: 1, name: 'คาบ 1', start_time: '08:30', end_time: '09:20', period_type: 'class' },
    { period_number: 2, name: 'คาบ 2', start_time: '09:20', end_time: '10:10', period_type: 'class' },
    { period_number: 3, name: 'คาบ 3', start_time: '10:10', end_time: '11:00', period_type: 'class' },
    { period_number: 4, name: 'คาบ 4', start_time: '11:00', end_time: '11:50', period_type: 'class' },
    { period_number: 5, name: 'พักกลางวัน', start_time: '11:50', end_time: '12:40', period_type: 'lunch' },
    { period_number: 6, name: 'คาบ 5', start_time: '12:40', end_time: '13:30', period_type: 'class' },
    { period_number: 7, name: 'คาบ 6', start_time: '13:30', end_time: '14:20', period_type: 'class' },
    { period_number: 8, name: 'คาบ 7', start_time: '14:20', end_time: '15:10', period_type: 'class' },
    { period_number: 9, name: 'คาบ 8', start_time: '15:10', end_time: '16:00', period_type: 'class' },
  ]
}

const openEditForm = (item: any) => {
  formMode.value = 'edit'
  formData.value = JSON.parse(JSON.stringify(item)) // deep copy
  if (!formData.value.days) formData.value.days = []
  if (!formData.value.grade_levels) formData.value.grade_levels = []
  // Sort periods by number or start time for editing just in case
  formData.value.periods.sort((a: any, b: any) => a.period_number - b.period_number)
  formError.value = ''
  fieldErrors.value = {}
  isFormOpen.value = true
}

const deleteSet = async (item: any) => {
  const result = await Swal.fire({
    title: 'ยืนยันการลบชุดโครงคาบ',
    html: `คุณต้องการลบชุด <b>${item.name}</b> ใช่หรือไม่?<br><br><span class="text-sm text-red-500">คาบที่ตารางเรียนอ้างถึงจะถูกปลดการอ้างอิง แต่คาบในตารางเรียนจะไม่ถูกลบ</span>`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'ลบข้อมูล',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#dc2626'
  })

  if (result.isConfirmed) {
    try {
      const res: any = await api.delete(`/api/academies/${academyId.value}/schedule-period-sets/${item.id}`)
      if (res?.success || res?.message) {
        Swal.fire({ title: 'ลบสำเร็จ', icon: 'success', timer: 1500, showConfirmButton: false })
        await fetchPeriodSets()
      }
    } catch (error: any) {
      Swal.fire('ข้อผิดพลาด', error?.data?.message || 'ไม่สามารถลบได้', 'error')
    }
  }
}

// Form helpers
const toggleDay = (day: number) => {
  const idx = formData.value.days.indexOf(day)
  if (idx === -1) {
    formData.value.days.push(day)
  } else {
    formData.value.days.splice(idx, 1)
  }
}

const toggleGradeLevel = (level: string) => {
  const idx = formData.value.grade_levels.indexOf(level)
  if (idx === -1) {
    formData.value.grade_levels.push(level)
  } else {
    formData.value.grade_levels.splice(idx, 1)
  }
}

const addPeriod = () => {
  let nextNumber = 1
  let startTime = '08:30'
  let endTime = '09:20'
  
  if (formData.value.periods.length > 0) {
    const last = formData.value.periods[formData.value.periods.length - 1]
    nextNumber = Math.max(...formData.value.periods.map((p:any) => p.period_number)) + 1
    if (last.end_time) {
      startTime = last.end_time.substring(0, 5) // "HH:MM"
      
      // Calculate end time (+50 mins)
      const [h, m] = startTime.split(':').map(Number)
      const totalMins = h * 60 + m + 50
      const newH = Math.floor(totalMins / 60) % 24
      const newM = totalMins % 60
      endTime = `${newH.toString().padStart(2, '0')}:${newM.toString().padStart(2, '0')}`
    }
  }

  formData.value.periods.push({
    period_number: nextNumber,
    name: `คาบ ${nextNumber}`,
    start_time: startTime,
    end_time: endTime,
    period_type: 'class'
  })
}

const removePeriod = (index: number) => {
  formData.value.periods.splice(index, 1)
}

const validateForm = () => {
  formError.value = ''
  if (!formData.value.name) {
    formError.value = 'กรุณากรอกชื่อชุดโครงคาบ'
    return false
  }
  if (!formData.value.periods || formData.value.periods.length === 0) {
    formError.value = 'ต้องมีอย่างน้อย 1 คาบ'
    return false
  }

  const numbers = new Set()
  for (let i = 0; i < formData.value.periods.length; i++) {
    const p = formData.value.periods[i]
    if (!p.period_number || !p.name || !p.start_time || !p.end_time) {
      formError.value = 'กรุณากรอกข้อมูลคาบให้ครบทุกช่อง'
      return false
    }
    
    if (numbers.has(p.period_number)) {
      formError.value = `เลขคาบ ${p.period_number} ซ้ำกันไม่ได้`
      return false
    }
    numbers.add(p.period_number)

    const startMinutes = timeToMins(p.start_time)
    const endMinutes = timeToMins(p.end_time)
    if (endMinutes <= startMinutes) {
      formError.value = `คาบ ${p.period_number}: เวลาจบต้องหลังเวลาเริ่ม`
      return false
    }
  }

  // Check overlap
  const sortedPeriods = [...formData.value.periods].sort((a, b) => timeToMins(a.start_time) - timeToMins(b.start_time))
  for (let i = 0; i < sortedPeriods.length - 1; i++) {
    if (timeToMins(sortedPeriods[i].end_time) > timeToMins(sortedPeriods[i+1].start_time)) {
      formError.value = 'ช่วงเวลาของคาบห้ามซ้อนทับกัน'
      return false
    }
  }

  return true
}

const timeToMins = (t: string) => {
  if (!t) return 0
  const [h, m] = t.split(':').map(Number)
  return h * 60 + m
}

const saveForm = async () => {
  if (!validateForm()) return

  isSubmitting.value = true
  fieldErrors.value = {}

  // format days and grade_levels to null if empty
  const payload = {
    ...formData.value,
    days: formData.value.days.length > 0 ? formData.value.days : null,
    grade_levels: formData.value.grade_levels.length > 0 ? formData.value.grade_levels : null,
  }
  // format time to HH:MM in case it has seconds from somewhere
  payload.periods = payload.periods.map((p: any) => ({
    ...p,
    start_time: p.start_time.substring(0, 5),
    end_time: p.end_time.substring(0, 5),
  }))

  try {
    let res: any
    if (formMode.value === 'create') {
      res = await api.post(`/api/academies/${academyId.value}/schedule-period-sets`, payload)
    } else {
      res = await api.patch(`/api/academies/${academyId.value}/schedule-period-sets/${payload.id}`, payload)
    }

    if (res?.success || res?.id || res?.message || res) { // Handle different successful responses
      Swal.fire({ title: 'บันทึกสำเร็จ', icon: 'success', timer: 1500, showConfirmButton: false })
      isFormOpen.value = false
      await fetchPeriodSets()
    }
  } catch (error: any) {
    // useApi โยน ApiError ที่มี body อยู่ที่ `.data` (ไม่ใช่ `.response._data` ของ ofetch ดิบ)
    const resData = error?.data
    if (resData?.errors) {
      fieldErrors.value = resData.errors
      formError.value = 'กรุณาตรวจสอบข้อมูลอีกครั้ง'
    } else {
      formError.value = resData?.message || 'เกิดข้อผิดพลาดในการบันทึก'
    }
  } finally {
    isSubmitting.value = false
  }
}

const formatDays = (days: any) => {
  if (!days || !days.length) return 'ทุกวัน'
  return days.map((d: number) => daysOfWeek.find(w => w.value === d)?.label).filter(Boolean).join('/')
}
const formatGradeLevels = (grades: any) => {
  if (!grades || !grades.length) return 'ทุกระดับชั้น'
  return grades.join(', ')
}
const getPeriodTypeName = (type: string) => {
  return periodTypes.find(t => t.value === type)?.label || type
}
</script>

<template>
  <div>
    <!-- Loading -->
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-12 w-12 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else>
      <!-- Header Card -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-6 shadow-sm border border-gray-100 dark:border-gray-700 mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <NuxtLink :to="`/academies/${academyName}/admin/schedule`" class="inline-flex items-center gap-1 text-sm text-primary-600 hover:text-primary-700 mb-1">
            <Icon icon="fluent:arrow-left-16-regular" /> ย้อนกลับไปตารางเรียน
          </NuxtLink>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">โครงคาบเรียน</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">จัดการชุดคาบเรียน เวลาเริ่มต้น/สิ้นสุด สำหรับใช้ในตารางเรียน</p>
        </div>
        <button v-if="canManage" @click="openCreateForm" class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-2 px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed w-full sm:w-auto justify-center">
          <Icon icon="fluent:add-24-regular" class="w-5 h-5" /> สร้างชุดโครงคาบ
        </button>
      </div>

      <!-- Empty State -->
      <div v-if="periodSets.length === 0" class="p-4 bg-orange-50 border border-orange-200 text-orange-800 rounded-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <Icon icon="fluent:warning-24-regular" class="w-6 h-6 flex-shrink-0" />
          <span>ยังไม่มีชุดโครงคาบเรียนในระบบ คุณต้องสร้างโครงคาบเรียนก่อนจัดตารางเรียน</span>
        </div>
        <button v-if="canManage" @click="applyTemplate" class="min-h-[44px] sm:min-h-0 whitespace-nowrap bg-white border border-orange-300 text-orange-700 px-4 py-2 rounded-xl font-medium hover:bg-orange-100 transition-colors">
          ใช้ตัวอย่างโครงคาบมัธยม
        </button>
      </div>

      <!-- List of Period Sets -->
      <div class="space-y-6">
        <div v-for="item in periodSets" :key="item.id" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
          <!-- Card Header -->
          <div class="p-4 sm:p-6 border-b border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-start justify-between gap-4 bg-gray-50 dark:bg-gray-800/50">
            <div>
              <div class="flex items-center gap-2 mb-2 flex-wrap">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ item.name }}</h3>
                <span v-if="item.is_default" class="bg-primary-100 text-primary-800 text-xs px-2 py-1 rounded-full font-medium border border-primary-200">ชุดเริ่มต้น</span>
                <span v-if="!item.is_active" class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full font-medium border border-gray-200">ปิดใช้งาน</span>
              </div>
              <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                <p><strong>วันที่ใช้:</strong> {{ formatDays(item.days) }}</p>
                <p><strong>ระดับชั้น:</strong> {{ formatGradeLevels(item.grade_levels) }}</p>
                <p><strong>จำนวน:</strong> {{ item.periods?.length || 0 }} คาบ ({{ item.periods?.[0]?.start_time?.substring(0,5) }} - {{ item.periods?.[item.periods?.length - 1]?.end_time?.substring(0,5) }})</p>
              </div>
            </div>
            
            <div v-if="canManage" class="flex items-center gap-2 self-end sm:self-start">
              <button @click="openEditForm(item)" class="min-h-[44px] sm:min-h-0 px-4 py-2 text-primary-600 bg-primary-50 hover:bg-primary-100 rounded-xl font-medium transition-colors">แก้ไข</button>
              <button @click="deleteSet(item)" class="min-h-[44px] sm:min-h-0 px-4 py-2 text-red-600 bg-red-50 hover:bg-red-100 rounded-xl font-medium transition-colors">ลบ</button>
            </div>
          </div>

          <!-- Card Content (Periods grid) -->
          <div class="p-4 sm:p-6">
            <h4 class="font-semibold text-gray-900 dark:text-white mb-4">รายการคาบ</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
              <div v-for="p in item.periods" :key="p.id" class="p-3 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 flex flex-col gap-1">
                <div class="flex items-center justify-between">
                  <span class="font-bold text-gray-900 dark:text-white">{{ p.name }}</span>
                  <span class="text-xs font-medium px-2 py-1 rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">
                    {{ getPeriodTypeName(p.period_type) }}
                  </span>
                </div>
                <div class="text-sm text-gray-500 flex justify-between items-center">
                  <span>คาบที่ {{ p.period_number }}</span>
                  <span class="font-medium text-primary-600 dark:text-primary-400">{{ p.start_time?.substring(0,5) }} - {{ p.end_time?.substring(0,5) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Form (using basic absolute/fixed overlay for mobile-first compatibility) -->
    <!-- z-[60]: แถบเมนูล่างของแอปบนมือถือเป็น fixed z-50 ถ้าโมดัลอยู่ z-50 เหมือนกัน ปุ่มบันทึกจะโดนแถบเมนูทับจนกดไม่ได้ -->
    <div v-if="isFormOpen" class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-0 sm:p-4 bg-gray-900/50 backdrop-blur-sm overflow-y-auto">
      <div class="bg-white dark:bg-gray-800 w-full max-w-4xl max-h-screen sm:max-h-[90vh] rounded-t-2xl sm:rounded-2xl shadow-xl flex flex-col mt-12 sm:mt-0">
        <!-- Modal Header -->
        <div class="flex items-center justify-between p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ formMode === 'create' ? 'สร้างชุดโครงคาบ' : 'แก้ไขชุดโครงคาบ' }}</h2>
          <button @click="isFormOpen = false" class="min-h-[44px] min-w-[44px] flex items-center justify-center text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-xl transition-colors">
            <Icon icon="fluent:dismiss-24-regular" class="w-6 h-6" />
          </button>
        </div>

        <!-- Modal Body -->
        <div class="p-4 sm:p-6 overflow-y-auto flex-1">
          <div class="space-y-6">
            <!-- Basic Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อชุด <span class="text-red-500">*</span></label>
                <input v-model="formData.name" type="text" placeholder="เช่น โครงปกติ จ-ศ" class="w-full px-4 py-2.5 min-h-[44px] bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white">
                <p v-if="fieldErrors.name" class="text-red-500 text-xs mt-1">{{ fieldErrors.name[0] }}</p>
              </div>
              <div class="flex flex-col gap-2 justify-center pt-2 sm:pt-6">
                <label class="flex items-center gap-2 min-h-[44px]">
                  <input type="checkbox" v-model="formData.is_default" class="w-5 h-5 rounded text-primary-600 focus:ring-primary-500">
                  <span class="text-sm text-gray-700 dark:text-gray-300">ตั้งเป็นชุดเริ่มต้น (จะปลดชุดอื่น)</span>
                </label>
                <label class="flex items-center gap-2 min-h-[44px]">
                  <input type="checkbox" v-model="formData.is_active" class="w-5 h-5 rounded text-primary-600 focus:ring-primary-500">
                  <span class="text-sm text-gray-700 dark:text-gray-300">เปิดใช้งาน</span>
                </label>
              </div>
            </div>

            <!-- Days -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">วันที่ใช้ (ไม่เลือก = ทุกวัน)</label>
              <div class="flex flex-wrap gap-2">
                <button v-for="d in daysOfWeek" :key="d.value" @click="toggleDay(d.value)" type="button"
                  :class="['min-h-[44px] px-4 rounded-xl font-medium transition-colors', 
                           formData.days.includes(d.value) ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200']">
                  {{ d.label }}
                </button>
              </div>
              <p class="text-sm text-gray-500 mt-2" v-if="formData.days.length === 0">เลือกใช้ทุกวัน</p>
            </div>

            <!-- Grade Levels -->
            <div v-if="uniqueGradeLevels.length > 0">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ระดับชั้นที่ใช้ (ไม่เลือก = ทุกระดับชั้น)</label>
              <div class="flex flex-wrap gap-2">
                <button v-for="g in uniqueGradeLevels" :key="g" @click="toggleGradeLevel(g)" type="button"
                  :class="['min-h-[44px] px-4 rounded-xl font-medium transition-colors', 
                           formData.grade_levels.includes(g) ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200']">
                  {{ g }}
                </button>
              </div>
              <p class="text-sm text-gray-500 mt-2" v-if="formData.grade_levels.length === 0">เลือกใช้ทุกระดับชั้น</p>
            </div>

            <!-- Periods section -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">คาบเรียน</h3>
                <button type="button" @click="addPeriod" class="min-h-[44px] px-4 bg-gray-100 hover:bg-gray-200 text-gray-900 rounded-xl font-medium flex items-center gap-2">
                  <Icon icon="fluent:add-24-regular" class="w-5 h-5"/> เพิ่มคาบ
                </button>
              </div>

              <!-- mobile periods card layout instead of table -->
              <div class="space-y-4">
                <div v-for="(p, index) in formData.periods" :key="index" class="bg-gray-50 dark:bg-gray-900 p-4 border border-gray-200 dark:border-gray-700 rounded-xl">
                  <div class="flex items-start sm:items-center justify-between gap-2 mb-3">
                    <h4 class="font-bold text-gray-700 dark:text-gray-300">คาบลำดับที่ {{ index + 1 }}</h4>
                    <button type="button" @click="removePeriod(index)" class="min-h-[44px] min-w-[44px] bg-red-100 text-red-600 hover:bg-red-200 rounded-xl flex items-center justify-center">
                      <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
                    </button>
                  </div>
                  
                  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    <div>
                      <label class="block text-xs text-gray-500 mb-1">เลขคาบ</label>
                      <input v-model.number="p.period_number" type="number" min="1" class="w-full px-4 py-2.5 min-h-[44px] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white">
                    </div>
                    <div>
                      <label class="block text-xs text-gray-500 mb-1">ชื่อคาบ</label>
                      <input v-model="p.name" type="text" class="w-full px-4 py-2.5 min-h-[44px] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white">
                    </div>
                    <div>
                      <label class="block text-xs text-gray-500 mb-1">เริ่ม</label>
                      <input v-model="p.start_time" type="time" class="w-full px-4 py-2.5 min-h-[44px] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white">
                    </div>
                    <div>
                      <label class="block text-xs text-gray-500 mb-1">จบ</label>
                      <input v-model="p.end_time" type="time" class="w-full px-4 py-2.5 min-h-[44px] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white">
                    </div>
                    <div>
                      <label class="block text-xs text-gray-500 mb-1">ชนิด</label>
                      <select v-model="p.period_type" class="w-full px-4 py-2.5 min-h-[44px] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white">
                        <option v-for="t in periodTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                      </select>
                    </div>
                  </div>
                </div>
                
                <div v-if="formData.periods.length === 0" class="text-center py-6 text-gray-500">
                  ไม่มีคาบเรียน กรุณากดปุ่ม "เพิ่มคาบ"
                </div>
              </div>

              <!-- Error message box -->
              <div v-if="formError" class="mt-4 p-3 bg-red-50 text-red-600 rounded-xl text-sm border border-red-200 flex items-center gap-2">
                <Icon icon="fluent:error-circle-24-regular" class="w-5 h-5 flex-shrink-0" /> {{ formError }}
              </div>
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-4 sm:p-6 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3 flex-shrink-0 bg-gray-50 dark:bg-gray-800/80 rounded-b-2xl">
          <button @click="isFormOpen = false" type="button" class="min-h-[44px] px-6 text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-xl font-medium transition-colors w-full sm:w-auto">
            ยกเลิก
          </button>
          <button @click="saveForm" :disabled="isSubmitting" type="button" class="min-h-[44px] px-6 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed w-full sm:w-auto flex items-center justify-center gap-2">
            <Icon v-if="isSubmitting" icon="fluent:spinner-ios-20-filled" class="animate-spin w-5 h-5" />
            บันทึกข้อมูล
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
