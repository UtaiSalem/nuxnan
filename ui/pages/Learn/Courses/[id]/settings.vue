<script setup lang="ts">
import { Icon } from '@iconify/vue'
import RichTextEditor from '~/components/Common/RichTextEditor.vue'
import ResponsiveCard from '~/components/Common/ResponsiveCard.vue'
import CourseDangerAction from '~/components/learn/course/settings/CourseDangerAction.vue'
import CourseStatusOption from '~/components/learn/course/settings/CourseStatusOption.vue'
import CourseToggleSetting from '~/components/learn/course/settings/CourseToggleSetting.vue'
import CourseSettingField from '~/components/learn/course/settings/CourseSettingField.vue'

import Swal from 'sweetalert2'

const DEFAULT_DESCRIPTION_TEMPLATE = `<h2>📋 ภาพรวมรายวิชา</h2>
<p>ใส่คำอธิบายรายวิชาที่นี่...</p>

<h2>🎯 วัตถุประสงค์</h2>
<ul>
  <li>วัตถุประสงค์ข้อที่ 1</li>
  <li>วัตถุประสงค์ข้อที่ 2</li>
</ul>

<h2>📖 เนื้อหาที่จะได้เรียน</h2>
<ul>
  <li>หัวข้อที่ 1</li>
  <li>หัวข้อที่ 2</li>
</ul>`

// Inject course data from parent
const course = inject<Ref<any>>('course')
const isCourseAdmin = inject<Ref<boolean>>('isCourseAdmin')
const refreshCourse = inject<() => void>('refreshCourse')
const isLoadingParent = inject<Ref<boolean>>('isLoading')

// Helper for safe access
const route = useRoute()
const courseId = route.params.id

// Thai Date Formatter
const formatThaiDate = (date: any) => {
  if (!date) return ''
  const d = new Date(date)
  const day = d.getDate()
  const month = d.toLocaleDateString('th-TH', { month: 'long' })
  const year = d.getFullYear() + 543
  return `${day} ${month} ${year}`
}

// Security Check
onMounted(() => {
  // If loaded and not admin, kick them out
  if (!isLoadingParent?.value && isCourseAdmin?.value === false) {
    navigateTo(`/courses/${courseId}`)
  }
})

// Watch for loading state change to enforce security
watch(() => isLoadingParent?.value, (loading) => {
  if (!loading && isCourseAdmin?.value === false) {
    navigateTo(`/courses/${courseId}`)
  }
})

// State
const isSaving = ref(false)
const savingSection = ref<string | null>(null)
const isDuplicating = ref(false)
const api = useApi()

// Eligibility state
const eligibilitySummary = ref<any>(null)
const eligibilityMembers = ref<any[]>([])
const isLoadingEligibility = ref(false)
const isRefreshingEligibility = ref(false)
const eligibilityStatusFilter = ref('all')
const eligibilitySearch = ref('')
const showUnlockModal = ref(false)
const selectedMemberForUnlock = ref<any>(null)
const unlockReason = ref('')
const isUnlocking = ref(false)
const eligibilityPanelOpen = ref(true)

type CourseStatus = 'published' | 'draft' | 'archived'

// Section fields definition
const sectionFields: Record<string, string[]> = {
  general: ['name', 'code', 'description', 'category', 'education_level', 'education_year'],
  academic: ['semester', 'academic_year', 'credit_units', 'hours_per_week', 'start_date', 'end_date'],
  publishing: ['status', 'auto_accept_members'],
  management: ['saleable', 'tuition_fees', 'discount', 'discount_type'],
  marketplace: ['is_for_marketplace', 'price', 'price_points'],
  eligibility: [
    'max_absence_percent', 'min_sessions_for_eligibility_check', 'allow_unlock_by_appeal', 'allow_self_unlock',
    'allow_unlock_by_points', 'unlock_points_cost', 'allow_unlock_by_reading', 'unlock_reading_minutes',
  ],
}

const sectionLabels: Record<string, string> = {
  general: 'ข้อมูลทั่วไป',
  academic: 'ข้อมูลเชิงวิชาการ',
  publishing: 'การเผยแพร่',
  management: 'การจัดการ',
  marketplace: 'ตลาด Master Copy',
  eligibility: 'สิทธิ์สอบ / สอบแก้',
}

const isSavingSection = (section: string) => savingSection.value === section

// Form data
const form = ref({
  code: '',
  name: '',
  description: '',
  category: '',
  level: '',
  education_level: '',
  education_year: null,
  credit_units: 0,
  hours_per_week: 0,
  start_date: '',
  end_date: '',
  auto_accept_members: false,
  tuition_fees: 0,
  saleable: false,
  price: 0,
  discount: 0,
  discount_type: 'fixed',
  semester: '',
  academic_year: '',
  status: 'draft',
  is_for_marketplace: false,
  price_points: 0,
  price_type: 'free',
  max_absence_percent: 20,
  min_sessions_for_eligibility_check: 3,
  allow_unlock_by_appeal: true,
  allow_self_unlock: false,
  allow_unlock_by_points: false,
  unlock_points_cost: null as number | null,
  allow_unlock_by_reading: false,
  unlock_reading_minutes: null as number | null,
})

// Snapshot for dirty checking
const initialForm = ref<any>(null)

const normalizeValue = (value: any) => {
  if (value === undefined || value === null || value === '') return null
  if (typeof value === 'string') return value.trim()
  return value
}

const isSectionDirty = (section: string) => {
  if (!initialForm.value) return false
  const fields = sectionFields[section]
  if (!fields) return false
  
  return fields.some(field => {
    return normalizeValue((form.value as any)[field]) !== normalizeValue(initialForm.value[field])
  })
}

const isAnySectionDirty = computed(() => {
  return Object.keys(sectionFields).some(section => isSectionDirty(section))
})

const markSectionClean = (section: string) => {
  if (!initialForm.value) return
  const fields = sectionFields[section]
  fields.forEach(field => {
    initialForm.value[field] = JSON.parse(JSON.stringify((form.value as any)[field]))
  })
}

// Course categories
const courseCategories = [
  'ภาษาไทย',
  'คณิตศาสตร์',
  'วิทยาศาสตร์',
  'สังคมศึกษา ศาสนา และวัฒนธรรม',
  'สุขศึกษาและพลศึกษา',
  'ศิลปะ',
  'การงานอาชีพและเทคโนโลยี',
  'ภาษาต่างประเทศ',
  'อื่นๆ'
]

// Education level options
const educationLevelOptions = [
  { value: 'ประถมศึกษา', label: 'ประถมศึกษา', hasYear: true, maxYear: 6 },
  { value: 'มัธยมศึกษา', label: 'มัธยมศึกษา', hasYear: true, maxYear: 6 },
  { value: 'ปวช.', label: 'ปวช.', hasYear: true, maxYear: 3 },
  { value: 'ปวส.', label: 'ปวส.', hasYear: true, maxYear: 2 },
  { value: 'อุดมศึกษา', label: 'อุดมศึกษา', hasYear: false },
  { value: 'อื่นๆ', label: 'อื่นๆ', hasYear: false },
]
const selectedEducationLevelOption = computed(() =>
  educationLevelOptions.find(opt => opt.value === form.value.education_level)
)

// Initialize form with course data
watch(() => course?.value, (newCourse) => {
  if (newCourse) {
    // Map numeric status to string for UI
    let statusStr: CourseStatus = 'draft'
    if (newCourse.status === 1 || newCourse.status === 'published') statusStr = 'published'
    else if (newCourse.status === 2 || newCourse.status === 'archived') statusStr = 'archived'
    else statusStr = 'draft'

    const data = {
      code: newCourse.code || '',
      name: newCourse.name || '',
      description: newCourse.description || DEFAULT_DESCRIPTION_TEMPLATE,
      category: newCourse.category || '',
      level: newCourse.level || '',
      education_level: newCourse.education_level || '',
      education_year: newCourse.education_year || null,
      credit_units: newCourse.credit_units || 0,
      hours_per_week: newCourse.hours_per_week || 0,
      start_date: newCourse.start_date ? newCourse.start_date.split(/[T ]/)[0] : '',
      end_date: newCourse.end_date ? newCourse.end_date.split(/[T ]/)[0] : '',
      auto_accept_members: Boolean(newCourse.setting?.auto_accept_members),
      tuition_fees: newCourse.tuition_fees || 0,
      saleable: newCourse.saleable || false,
      price: newCourse.price || 0,
      discount: newCourse.discount || 0,
      discount_type: newCourse.discount_type || 'fixed',
      semester: newCourse.semester || '',
      academic_year: newCourse.academic_year || '',
      status: statusStr,
      is_for_marketplace: Boolean(newCourse.is_for_marketplace),
      price_points: newCourse.price_points || 0,
      price_type: newCourse.price_type || 'free',
      max_absence_percent: newCourse.max_absence_percent ?? 20,
      min_sessions_for_eligibility_check: newCourse.min_sessions_for_eligibility_check ?? 3,
      allow_unlock_by_appeal: newCourse.allow_unlock_by_appeal ?? true,
      allow_self_unlock: Boolean(newCourse.allow_self_unlock),
      allow_unlock_by_points: Boolean(newCourse.allow_unlock_by_points),
      unlock_points_cost: newCourse.unlock_points_cost ?? null,
      allow_unlock_by_reading: Boolean(newCourse.allow_unlock_by_reading),
      unlock_reading_minutes: newCourse.unlock_reading_minutes ?? null,
    }
    form.value = { ...data }
    initialForm.value = JSON.parse(JSON.stringify(data))
  }
}, { immediate: true })

// Net Price Calculation (ใช้ tuition_fees สำหรับค่าสมัครเรียน)
const netPrice = computed(() => {
    if (!form.value.saleable) return 0
    const price = Number(form.value.tuition_fees) || 0
    const discount = Number(form.value.discount) || 0
    
    if (form.value.discount_type === 'percent') {
        const discountAmount = (price * discount) / 100
        return Math.max(0, price - discountAmount)
    }
    
    return Math.max(0, price - discount)
})

// Save settings
const saveSettings = async () => {
  if (!course?.value) return

  isSaving.value = true
  try {
    // price_type กำหนดโดยระบบเสมอ — ผู้ซื้อใช้ wallet หรือ points ตามอัตราแลกเปลี่ยน
    const payload = {
      ...form.value,
      price_type: form.value.is_for_marketplace && form.value.price > 0 ? 'wallet' : 'free',
    }
    const response = await api.put(`/api/courses/${course.value.id}`, payload)
    if (response) {
       useToast().success('บันทึกการตั้งค่าทั้งหมดเรียบร้อยแล้ว')
       initialForm.value = JSON.parse(JSON.stringify(form.value))
       if (refreshCourse) refreshCourse()
    }
  } catch (err: any) {
    useToast().error(err.data?.msg || 'ไม่สามารถบันทึกได้')
  } finally {
    isSaving.value = false
  }
}

// Save specific section
const saveSettingsSection = async (section: string, fields: string[]) => {
  if (!course?.value) return

  // Validation
  if (section === 'general' && !form.value.name?.trim()) {
    useToast().error('กรุณาระบุชื่อรายวิชา')
    return
  }

  savingSection.value = section
  try {
    const payload: any = {}
    fields.forEach(field => {
      payload[field] = (form.value as any)[field]
    })

    let response
    if (section === 'marketplace') {
      payload.price_type = form.value.is_for_marketplace && form.value.price > 0 ? 'wallet' : 'free'
      response = await api.patch(`/api/courses/${course.value.id}/marketplace`, payload)
    } else {
      response = await api.put(`/api/courses/${course.value.id}`, payload)
    }
    if (response) {
      useToast().success(`บันทึก${sectionLabels[section]}เรียบร้อยแล้ว`)
      markSectionClean(section)
      if (refreshCourse) refreshCourse()
    }
  } catch (err: any) {
    useToast().error(err.data?.msg || `ไม่สามารถบันทึก${sectionLabels[section]}ได้`)
  } finally {
    savingSection.value = null
  }
}

// Duplicate course
const duplicateCourse = async () => {
  if (!course?.value || isDuplicating.value) return

  const result = await Swal.fire({
    title: 'คัดลอกรายวิชา?',
    html: `ระบบจะสร้างรายวิชาใหม่พร้อมเนื้อหาเหมือน <strong>${course.value.name}</strong> แล้วคุณสามารถแก้รายละเอียดภายหลังได้`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'คัดลอก',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#2563eb',
    cancelButtonColor: '#6b7280',
    reverseButtons: true
  })

  if (!result.isConfirmed) return

  isDuplicating.value = true
  try {
    const response = await api.post(`/api/courses/${course.value.id}/duplicate`, {})
    const newCourseId = response?.course?.id

    await Swal.fire({
      title: 'คัดลอกสำเร็จ',
      text: 'สร้างรายวิชาใหม่เรียบร้อยแล้ว',
      icon: 'success',
      timer: 1400,
      showConfirmButton: false
    })

    if (newCourseId) {
      navigateTo(`/Learn/Courses/${newCourseId}/settings`)
    }
  } catch (err: any) {
    Swal.fire('ผิดพลาด', err.data?.message || 'ไม่สามารถคัดลอกรายวิชาได้', 'error')
  } finally {
    isDuplicating.value = false
  }
}

// Delete course
const deleteCourse = async () => {
  if (!course?.value) return
  
  const result = await Swal.fire({
    title: 'ยืนยันการลบรายวิชา?',
    html: `คุณแน่ใจหรือไม่ที่จะลบรายวิชา<br><strong class="text-red-600">"${course.value.name}"</strong><br><br><span class="text-sm text-gray-500">การกระทำนี้ไม่สามารถย้อนกลับได้</span>`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'ใช่, ลบเลย',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#6b7280',
    reverseButtons: true
  })
  
  if (!result.isConfirmed) return
  
  try {
    const response = await api.delete(`/api/courses/${course.value.id}`)
    if (response) {
      await Swal.fire({
        title: 'ลบสำเร็จ',
        text: 'รายวิชาถูกลบเรียบร้อยแล้ว',
        icon: 'success',
        timer: 1500,
        showConfirmButton: false
      })
      navigateTo('/Learn/Courses')
    }
  } catch (err: any) {
    Swal.fire('ผิดพลาด', err.data?.msg || 'ไม่สามารถลบรายวิชาได้', 'error')
  }
}

const fetchEligibilitySummary = async () => {
  if (!course?.value) return
  isLoadingEligibility.value = true
  try {
    const res: any = await api.get(`/api/courses/${course.value.id}/eligibility/summary`)
    if (res.success) {
      eligibilitySummary.value = res.data
      eligibilityMembers.value = res.data.members || []
    }
  } finally { isLoadingEligibility.value = false }
}

const refreshEligibilityStatus = async () => {
  isRefreshingEligibility.value = true
  try {
    const res: any = await api.post(`/api/courses/${course.value.id}/eligibility/refresh`)
    if (res.success) { 
      await fetchEligibilitySummary()
      useToast().success('อัพเดทสถานะสิทธิ์สอบแล้ว') 
    }
  } catch { useToast().error('ไม่สามารถอัพเดทสถานะได้') }
  finally { isRefreshingEligibility.value = false }
}

const filteredEligibilityMembers = computed(() => {
  let r = [...eligibilityMembers.value]
  if (eligibilitySearch.value) {
    const q = eligibilitySearch.value.toLowerCase()
    r = r.filter(m => m.user?.name?.toLowerCase().includes(q) || m.member_code?.toLowerCase().includes(q))
  }
  if (eligibilityStatusFilter.value !== 'all')
    r = r.filter(m => m.stats?.eligibility_status === eligibilityStatusFilter.value)
  return r
})

const openUnlockModal = (member: any) => {
  selectedMemberForUnlock.value = member
  unlockReason.value = ''
  showUnlockModal.value = true
}

const confirmUnlock = async () => {
  if (!selectedMemberForUnlock.value || !unlockReason.value.trim()) return
  isUnlocking.value = true
  try {
    const res: any = await api.post(
      `/api/courses/${course.value.id}/eligibility/members/${selectedMemberForUnlock.value.id}/unlock`,
      { reason: unlockReason.value }
    )
    if (res.success) { 
      useToast().success('ปลดล็อคสิทธิ์สอบสำเร็จ')
      showUnlockModal.value = false
      await fetchEligibilitySummary() 
    }
  } catch { useToast().error('ไม่สามารถปลดล็อคได้') }
  finally { isUnlocking.value = false }
}

const getEligibilityStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    eligible: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    at_risk:  'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    ineligible:'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    unlocked: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
  }
  return colors[status] ?? 'bg-gray-100 text-gray-800'
}

const getEligibilityStatusLabel = (status: string) => {
  const labels: Record<string, string> = { 
    eligible: 'มีสิทธิ์สอบ', 
    at_risk: 'เสี่ยง', 
    ineligible: 'หมดสิทธิ์', 
    unlocked: 'ปลดล็อคแล้ว' 
  }
  return labels[status] ?? status
}

// Auto-null conditional fields when toggle is off
watch(() => form.value.allow_unlock_by_points, v => { if (!v) form.value.unlock_points_cost = null })
watch(() => form.value.allow_unlock_by_reading, v => { if (!v) form.value.unlock_reading_minutes = null })

// Load eligibility summary on mount (admin only)
watch(() => course?.value?.id, id => { if (id && isCourseAdmin?.value) fetchEligibilitySummary() }, { immediate: true })
</script>

<template>
  <div class="space-y-5 sm:space-y-8 max-w-7xl mx-auto px-3 sm:px-4 lg:px-6 pb-32 sm:pb-20">
    
    <!-- Header -->
    <div class="relative overflow-hidden rounded-xl sm:rounded-2xl bg-gradient-to-r from-cyan-600 to-blue-700 p-4 sm:p-6 lg:p-8 text-white shadow-lg sm:shadow-xl transition-all duration-300">
      <div class="absolute top-0 right-0 -mt-6 -mr-6 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
      <div class="absolute bottom-0 left-0 -mb-6 -ml-6 w-32 h-32 bg-black/10 rounded-full blur-2xl"></div>
      
      <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3 sm:gap-4 min-w-0">
          <div class="p-2 sm:p-3 bg-white/20 backdrop-blur-md rounded-lg sm:rounded-xl flex-shrink-0">
             <Icon icon="mdi-light:settings" class="w-5 h-5 sm:w-7 sm:h-7 lg:w-8 lg:h-8 text-white" />
          </div>
          <div class="min-w-0">
            <h1 class="text-lg sm:text-xl lg:text-2xl font-black truncate leading-tight">ตั้งค่ารายวิชา</h1>
            <p class="text-blue-100 opacity-90 text-[10px] sm:text-sm lg:text-base truncate">จัดการข้อมูลและสถานะของรายวิชา</p>
          </div>
        </div>
        
        <!-- Save Button (Desktop) -->
        <button
          v-if="isAnySectionDirty"
          @click="saveSettings"
          :disabled="isSaving"
          class="hidden md:flex items-center gap-2 px-6 py-2.5 bg-white text-blue-600 font-bold rounded-xl shadow-lg hover:bg-blue-50 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <Icon v-if="isSaving" icon="svg-spinners:ring-resize" class="w-5 h-5" />
          <Icon v-else icon="fluent:save-24-filled" class="w-5 h-5" />
          บันทึกการเปลี่ยนแปลงทั้งหมด
        </button>
      </div>
    </div>

    <!-- Main Form: Single Column Stack -->
    <form @submit.prevent="saveSettings" class="space-y-6 sm:space-y-8">
      
      <!-- General Information Card -->
      <ResponsiveCard title="ข้อมูลทั่วไป" icon="heroicons:information-circle" icon-color="text-cyan-500">
        <div class="space-y-5 sm:space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
            <CourseSettingField label="รหัสวิชา" icon="fluent:number-symbol-square-24-regular">
              <input
                v-model="form.code"
                type="text"
                placeholder="เช่น CS101"
                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-cyan-500/30 focus:border-cyan-500 transition-all dark:text-white text-base"
              />
            </CourseSettingField>

            <CourseSettingField label="ชื่อรายวิชา" icon="heroicons:book-open" required>
              <input
                v-model="form.name"
                type="text"
                required
                placeholder="ชื่อรายวิชา"
                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-cyan-500/30 focus:border-cyan-500 transition-all dark:text-white text-base"
              />
            </CourseSettingField>
          </div>

          <CourseSettingField label="คำอธิบายรายวิชา" icon="heroicons:document-text">
            <RichTextEditor
              v-model="form.description"
              placeholder="รายละเอียดเกี่ยวกับรายวิชา..."
              class="w-full"
              min-height="200px"
            />
          </CourseSettingField>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-5 sm:gap-6">
            <CourseSettingField label="หมวดหมู่" icon="heroicons:tag">
              <div class="relative">
                <select
                  v-model="form.category"
                  class="w-full pl-4 pr-10 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-cyan-500/30 focus:border-cyan-500 transition-all dark:text-white text-base appearance-none cursor-pointer"
                >
                  <option value="">เลือกหมวดหมู่</option>
                  <option v-for="cat in courseCategories" :key="cat" :value="cat">{{ cat }}</option>
                </select>
                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                  <Icon icon="heroicons:chevron-down" class="w-5 h-5" />
                </span>
              </div>
            </CourseSettingField>

            <CourseSettingField label="ระดับการศึกษา" icon="heroicons:academic-cap">
              <div class="relative">
                <select
                  v-model="form.education_level"
                  @change="!selectedEducationLevelOption?.hasYear && (form.education_year = null)"
                  class="w-full pl-4 pr-10 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-cyan-500/30 focus:border-cyan-500 transition-all dark:text-white text-base appearance-none cursor-pointer"
                >
                  <option value="">เลือกระดับการศึกษา</option>
                  <option v-for="opt in educationLevelOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                  <Icon icon="heroicons:chevron-down" class="w-5 h-5" />
                </span>
              </div>
            </CourseSettingField>

            <CourseSettingField v-if="selectedEducationLevelOption?.hasYear" label="ปีที่" icon="heroicons:hashtag">
              <div class="relative">
                <select
                  v-model="form.education_year"
                  class="w-full pl-4 pr-10 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-cyan-500/30 focus:border-cyan-500 transition-all dark:text-white text-base appearance-none cursor-pointer"
                >
                  <option :value="null">เลือกปีที่</option>
                  <option v-for="y in selectedEducationLevelOption.maxYear" :key="y" :value="y">ปีที่ {{ y }}</option>
                </select>
                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                  <Icon icon="heroicons:chevron-down" class="w-5 h-5" />
                </span>
              </div>
            </CourseSettingField>
          </div>
        </div>
        <template #footer v-if="isSectionDirty('general')">
          <div class="flex justify-end">
            <button
              type="button"
              @click="saveSettingsSection('general', sectionFields.general)"
              :disabled="isSavingSection('general')"
              class="flex items-center gap-2 px-4 py-2 bg-cyan-600 text-white font-bold rounded-xl shadow-lg hover:bg-cyan-700 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed text-sm"
            >
              <Icon v-if="isSavingSection('general')" icon="svg-spinners:ring-resize" class="w-4 h-4" />
              <Icon v-else icon="fluent:save-24-filled" class="w-4 h-4" />
              บันทึกข้อมูลทั่วไป
            </button>
          </div>
        </template>
      </ResponsiveCard>

      <!-- Mobile Save Button (Sticky Bottom) -->
      <div v-if="isAnySectionDirty"
           class="fixed bottom-16 left-0 right-0 px-4 pt-3 pb-3 bg-white/95 dark:bg-gray-900/95 backdrop-blur-md border-t border-gray-200 dark:border-gray-800 md:hidden z-40"
           style="padding-bottom: calc(0.75rem + env(safe-area-inset-bottom));">
        <button
          type="submit"
          :disabled="isSaving"
          class="w-full flex items-center justify-center gap-2 px-6 py-3.5 bg-blue-600 text-white font-black rounded-xl shadow-xl active:scale-95 transition-all disabled:opacity-50 min-h-[48px] text-base"
        >
          <Icon v-if="isSaving" icon="svg-spinners:ring-resize" class="w-5 h-5" />
          <Icon v-else icon="fluent:save-24-filled" class="w-5 h-5" />
          บันทึกการเปลี่ยนแปลงทั้งหมด
        </button>
      </div>

    </form>

    <!-- Left Sidebar: การเผยแพร่ + การจัดการ -->
    <Teleport to="#left-widgets-slot">
      <div class="space-y-4" style="order: -1">
        <!-- Status & Visibility -->
        <ResponsiveCard title="การเผยแพร่" icon="heroicons:globe-alt" icon-color="text-green-500">
          <div class="grid grid-cols-1 gap-3">
            <CourseStatusOption
              v-model="form.status"
              value="published"
              label="เผยแพร่"
              description="ทุกคนสามารถค้นหาและเห็นรายวิชานี้"
              icon="heroicons:globe-alt"
              color="green"
            />
            <CourseStatusOption
              v-model="form.status"
              value="draft"
              label="ฉบับร่าง"
              description="เฉพาะผู้ดูแลรายวิชาเท่านั้นที่เห็น"
              icon="heroicons:document-text"
              color="gray"
            />
            <CourseStatusOption
              v-model="form.status"
              value="archived"
              label="เก็บถาวร"
              description="ปิดรับสมาชิกและซ่อนจากการค้นหา"
              icon="heroicons:archive-box"
              color="orange"
            />
          </div>
          <template #footer v-if="isSectionDirty('publishing')">
            <div class="flex justify-end">
              <button
                type="button"
                @click="saveSettingsSection('publishing', sectionFields.publishing)"
                :disabled="isSavingSection('publishing')"
                class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white font-bold rounded-xl shadow-lg hover:bg-green-700 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed text-sm"
              >
                <Icon v-if="isSavingSection('publishing')" icon="svg-spinners:ring-resize" class="w-4 h-4" />
                <Icon v-else icon="fluent:save-24-filled" class="w-4 h-4" />
                บันทึกการเผยแพร่
              </button>
            </div>
          </template>
        </ResponsiveCard>

        <!-- Configuration -->
        <ResponsiveCard title="การจัดการ" icon="heroicons:cog-6-tooth" icon-color="text-orange-500">
          <div class="space-y-4">
            <div class="space-y-3">
              <CourseToggleSetting
                v-model="form.auto_accept_members"
                label="อนุมัติสมาชิกอัตโนมัติ"
                description="ไม่ต้องกดยืนยันคำขอ"
                variant="orange"
              />
              <CourseToggleSetting
                v-model="form.saleable"
                label="เปิดรับสมัครเรียน (แบบเก็บค่าธรรมเนียม)"
                description="เก็บค่าธรรมเนียมสำหรับผู้ที่ต้องการเข้าเรียน"
                variant="orange"
              />
            </div>
            <div v-if="form.saleable" class="space-y-3 pt-1 animate-fade-in-down">
              <CourseSettingField label="ค่าธรรมเนียมสมัครเรียน (บาท)" icon="heroicons:currency-dollar">
                <input
                  v-model.number="form.tuition_fees"
                  type="number"
                  min="0"
                  placeholder="0.00"
                  class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-orange-500/30 focus:border-orange-500 transition-all dark:text-white text-base"
                />
              </CourseSettingField>
              <CourseSettingField
                label="ส่วนลดค่าสมัคร"
                :icon="form.discount_type === 'fixed' ? 'heroicons:currency-dollar' : 'heroicons:receipt-percent'"
              >
                <div class="flex gap-2">
                  <input
                    v-model.number="form.discount"
                    type="number"
                    min="0"
                    :max="form.discount_type === 'percent' ? 100 : form.tuition_fees"
                    placeholder="0"
                    class="flex-1 px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-orange-500/30 focus:border-orange-500 transition-all dark:text-white text-base"
                  />
                  <select
                    v-model="form.discount_type"
                    class="flex-shrink-0 w-20 sm:w-24 bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-3 focus:ring-2 focus:ring-orange-500/30 focus:border-orange-500 text-sm font-bold dark:text-white appearance-none cursor-pointer text-center"
                  >
                    <option value="fixed">บาท</option>
                    <option value="percent">%</option>
                  </select>
                </div>
              </CourseSettingField>
              <div class="flex justify-between items-center text-lg font-black bg-emerald-50 dark:bg-emerald-900/20 p-3 rounded-xl border border-emerald-100 dark:border-emerald-800/30">
                <span class="text-gray-700 dark:text-gray-300 text-sm">ค่าสมัครสุทธิ:</span>
                <span class="text-emerald-600 dark:text-emerald-400">{{ netPrice.toLocaleString() }} บาท</span>
              </div>
            </div>
          </div>
          <template #footer v-if="isSectionDirty('management')">
            <div class="flex justify-end">
              <button
                type="button"
                @click="saveSettingsSection('management', sectionFields.management)"
                :disabled="isSavingSection('management')"
                class="flex items-center gap-2 px-4 py-2 bg-orange-600 text-white font-bold rounded-xl shadow-lg hover:bg-orange-700 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed text-sm"
              >
                <Icon v-if="isSavingSection('management')" icon="svg-spinners:ring-resize" class="w-4 h-4" />
                <Icon v-else icon="fluent:save-24-filled" class="w-4 h-4" />
                บันทึกค่าเรียน
              </button>
            </div>
          </template>
        </ResponsiveCard>

        <!-- Academic Details -->
        <ResponsiveCard title="ข้อมูลเชิงวิชาการ" icon="heroicons:academic-cap" icon-color="text-purple-500">
          <div class="space-y-3">
            <CourseSettingField label="ภาคเรียนที่" icon="heroicons:bookmark">
              <div class="relative">
                <select
                  v-model="form.semester"
                  class="w-full pl-4 pr-10 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 transition-all dark:text-white text-base appearance-none cursor-pointer"
                >
                  <option value="">เลือกภาคเรียน</option>
                  <option value="1">1</option>
                  <option value="2">2</option>
                  <option value="3">3</option>
                  <option value="summer">ฤดูร้อน</option>
                </select>
                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                  <Icon icon="heroicons:chevron-down" class="w-5 h-5" />
                </span>
              </div>
            </CourseSettingField>
            <CourseSettingField label="ปีการศึกษา" icon="heroicons:calendar">
              <input v-model="form.academic_year" type="text" placeholder="เช่น 2567" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 transition-all dark:text-white text-base" />
            </CourseSettingField>
            <CourseSettingField label="หน่วยกิต" icon="heroicons:star">
              <input v-model.number="form.credit_units" type="number" min="0" step="0.5" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 transition-all dark:text-white text-base" />
            </CourseSettingField>
            <CourseSettingField label="ชั่วโมง/สัปดาห์" icon="heroicons:clock">
              <input v-model.number="form.hours_per_week" type="number" min="0" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 transition-all dark:text-white text-base" />
            </CourseSettingField>
            <CourseSettingField label="วันเริ่มต้น" icon="heroicons:calendar-days">
              <ClientOnly>
                <VueDatePicker v-model="form.start_date" model-type="yyyy-MM-dd" :format="formatThaiDate" auto-apply :enable-time-picker="false" teleport="body" placeholder="เลือกวันเริ่มต้น" input-class-name="!bg-gray-50 dark:!bg-gray-900 !border-gray-200 dark:!border-gray-700 !rounded-xl dark:!text-white !py-3 !w-full !text-base" />
              </ClientOnly>
            </CourseSettingField>
            <CourseSettingField label="วันสิ้นสุด" icon="heroicons:calendar-days">
              <ClientOnly>
                <VueDatePicker v-model="form.end_date" model-type="yyyy-MM-dd" :format="formatThaiDate" auto-apply :enable-time-picker="false" teleport="body" placeholder="เลือกวันสิ้นสุด" input-class-name="!bg-gray-50 dark:!bg-gray-900 !border-gray-200 dark:!border-gray-700 !rounded-xl dark:!text-white !py-3 !w-full !text-base" />
              </ClientOnly>
            </CourseSettingField>
          </div>
          <template #footer v-if="isSectionDirty('academic')">
            <div class="flex justify-end">
              <button type="button" @click="saveSettingsSection('academic', sectionFields.academic)" :disabled="isSavingSection('academic')" class="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white font-bold rounded-xl shadow-lg hover:bg-purple-700 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed text-sm">
                <Icon v-if="isSavingSection('academic')" icon="svg-spinners:ring-resize" class="w-4 h-4" />
                <Icon v-else icon="fluent:save-24-filled" class="w-4 h-4" />
                บันทึกข้อมูลเชิงวิชาการ
              </button>
            </div>
          </template>
        </ResponsiveCard>
      </div>
    </Teleport>

    <!-- Right Sidebar: ตลาด Master Copy + Duplicate & Danger Zone -->
    <Teleport to="#right-widgets-slot">
      <div class="space-y-4" style="order: -1">
        <!-- Marketplace Settings -->
        <ResponsiveCard title="ตลาด Master Copy" icon="mdi:content-copy" icon-color="text-amber-500">
          <div class="space-y-4">
            <div class="flex gap-3 p-3 bg-amber-50 dark:bg-amber-900/10 rounded-xl border border-amber-200 dark:border-amber-800 text-xs text-amber-800 dark:text-amber-300">
              <Icon icon="mdi:information-outline" class="w-5 h-5 flex-shrink-0 mt-0.5" />
              <span>
                <strong>Master Copy</strong> คือต้นฉบับรายวิชาที่ครูหรือสถาบันอื่นสามารถซื้อไปเป็นฐานสำหรับการสอนของตัวเอง
              </span>
            </div>
            <CourseToggleSetting
              v-model="form.is_for_marketplace"
              label="เปิดขาย Master Copy"
              description="ครู/สถาบันอื่นสามารถซื้อต้นฉบับไปใช้สอนได้"
              variant="amber"
            />
            <div v-if="form.is_for_marketplace" class="space-y-3 animate-fade-in-down">
              <CourseSettingField label="ราคา Master Copy (บาท)" icon="heroicons:currency-dollar">
                <input
                  v-model.number="form.price"
                  type="number"
                  min="0"
                  placeholder="0.00"
                  class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-all dark:text-white text-base"
                />
              </CourseSettingField>
              <div class="p-3 bg-blue-50 dark:bg-blue-900/10 rounded-xl border border-blue-200 dark:border-blue-800 text-xs text-blue-700 dark:text-blue-300 flex gap-3">
                <Icon icon="mdi:information-outline" class="w-4 h-4 flex-shrink-0 mt-0.5" />
                <div>
                  ชำระด้วย <strong>Wallet</strong> หรือ <strong>แต้ม</strong> ได้
                  <br/>1 บาท = 1,200 แต้ม
                </div>
              </div>
              <div v-if="course?.total_sales > 0" class="p-3 bg-amber-50 dark:bg-amber-900/10 rounded-xl border border-amber-200 dark:border-amber-800">
                <div class="text-[10px] font-black text-amber-600 uppercase mb-2 tracking-wider text-center">สถิติการขาย</div>
                <div class="grid grid-cols-2 gap-3">
                  <div class="text-center">
                    <div class="text-lg font-black text-slate-800 dark:text-white">{{ course?.total_sales || 0 }}</div>
                    <div class="text-[10px] text-slate-500 font-bold">ครั้งที่ขายได้</div>
                  </div>
                  <div class="text-center border-l border-amber-200 dark:border-amber-800">
                    <div class="text-lg font-black text-slate-800 dark:text-white truncate">
                      ฿{{ (form.price * (course?.total_sales || 0)).toLocaleString() }}
                    </div>
                    <div class="text-[10px] text-slate-500 font-bold">รายได้รวม</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <template #footer v-if="isSectionDirty('marketplace')">
            <div class="flex justify-end">
              <button
                type="button"
                @click="saveSettingsSection('marketplace', sectionFields.marketplace)"
                :disabled="isSavingSection('marketplace')"
                class="flex items-center gap-2 px-4 py-2 bg-amber-600 text-white font-bold rounded-xl shadow-lg hover:bg-amber-700 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed text-sm"
              >
                <Icon v-if="isSavingSection('marketplace')" icon="svg-spinners:ring-resize" class="w-4 h-4" />
                <Icon v-else icon="fluent:save-24-filled" class="w-4 h-4" />
                บันทึกตลาด Master Copy
              </button>
            </div>
          </template>
        </ResponsiveCard>

        <!-- Duplicate & Danger Zone -->
        <ResponsiveCard title="คัดลอกรายวิชา" icon="heroicons:document-duplicate" icon-color="text-blue-500">
          <div class="space-y-4">
            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
              สร้างรายวิชาใหม่จากเนื้อหาเดิม เหมาะสำหรับเปิดรอบเรียนใหม่หรือปรับรายละเอียดบางส่วน
            </p>
            <button
              type="button"
              @click="duplicateCourse"
              :disabled="isDuplicating"
              class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition-all shadow-lg shadow-blue-500/20 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <Icon v-if="isDuplicating" icon="svg-spinners:ring-resize" class="w-5 h-5" />
              <Icon v-else icon="heroicons:document-duplicate-solid" class="w-5 h-5" />
              {{ isDuplicating ? 'กำลังคัดลอก...' : 'คัดลอกรายวิชา' }}
            </button>
          </div>
        </ResponsiveCard>

        <!-- Eligibility Settings -->
        <ResponsiveCard title="สิทธิ์สอบ / สอบแก้" icon="heroicons:academic-cap-solid" icon-color="text-indigo-500">
          <div class="space-y-4">
            <div class="space-y-3">
              <CourseSettingField label="ขาดเรียนได้ไม่เกิน (%)" icon="heroicons:user-minus" hint="หากขาดเกินเกณฑ์นี้ จะถูกระงับสิทธิ์สอบอัตโนมัติ">
                <div class="relative">
                  <input v-model.number="form.max_absence_percent" type="number" min="0" max="100" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all dark:text-white text-base" />
                  <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold">%</span>
                </div>
              </CourseSettingField>
              <CourseSettingField label="จำนวนคาบขั้นต่ำที่เริ่มตรวจ" icon="heroicons:list-bullet" hint="ระบบจะเริ่มคำนวณสิทธิ์สอบเมื่อมีการเช็คชื่อครบตามจำนวนคาบนี้">
                <input v-model.number="form.min_sessions_for_eligibility_check" type="number" min="1" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all dark:text-white text-base" />
              </CourseSettingField>
            </div>
            <div class="space-y-3">
              <CourseToggleSetting v-model="form.allow_unlock_by_appeal" label="อนุญาตให้ยื่นคำร้อง" description="นักเรียนสามารถส่งเหตุผลขอปลดล็อคได้" variant="neutral" />
              <CourseToggleSetting v-model="form.allow_self_unlock" label="ปลดล็อคอัตโนมัติ (Self Unlock)" description="นักเรียนสามารถกดปลดล็อคตัวเองได้ทันทีโดยไม่ต้องรออนุมัติ" variant="neutral" />
              <CourseToggleSetting v-model="form.allow_unlock_by_points" label="ปลดล็อคด้วยแต้มสะสม" description="ใช้แต้มแลกสิทธิ์สอบแก้" variant="neutral" />
              <div v-if="form.allow_unlock_by_points" class="pl-4 animate-fade-in-down">
                <CourseSettingField label="จำนวนแต้มที่ใช้">
                  <div class="relative">
                    <input v-model.number="form.unlock_points_cost" type="number" min="0" placeholder="เช่น 500" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-indigo-500/30 transition-all dark:text-white text-sm" />
                    <Icon icon="mdi:database" class="absolute right-3 top-1/2 -translate-y-1/2 text-amber-500 w-4 h-4" />
                  </div>
                </CourseSettingField>
              </div>
              <CourseToggleSetting v-model="form.allow_unlock_by_reading" label="ปลดล็อคด้วยการอ่าน" description="อ่านเนื้อหาให้ครบเวลาที่กำหนด" variant="neutral" />
              <div v-if="form.allow_unlock_by_reading" class="pl-4 animate-fade-in-down">
                <CourseSettingField label="เวลาที่ต้องอ่าน (นาที)">
                  <div class="relative">
                    <input v-model.number="form.unlock_reading_minutes" type="number" min="1" placeholder="เช่น 60" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-indigo-500/30 transition-all dark:text-white text-sm" />
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs">นาที</span>
                  </div>
                </CourseSettingField>
              </div>
            </div>
          </div>
          <template #footer v-if="isSectionDirty('eligibility')">
            <div class="flex justify-end">
              <button type="button" @click="saveSettingsSection('eligibility', sectionFields.eligibility)" :disabled="isSavingSection('eligibility')" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white font-bold rounded-xl shadow-lg hover:bg-indigo-700 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed text-sm">
                <Icon v-if="isSavingSection('eligibility')" icon="svg-spinners:ring-resize" class="w-4 h-4" />
                <Icon v-else icon="fluent:save-24-filled" class="w-4 h-4" />
                บันทึกเกณฑ์สิทธิ์สอบ
              </button>
            </div>
          </template>
        </ResponsiveCard>

        <CourseDangerAction
          label="โซนอันตราย"
          icon="heroicons:exclamation-triangle"
          description="การลบรายวิชาจะไม่สามารถกู้คืนได้ ข้อมูลทั้งหมดจะหายไปถาวร กรุณาตรวจสอบให้แน่ใจ"
          buttonLabel="ลบรายวิชาถาวร"
          variant="danger"
          :loading="false"
          @action="deleteCourse"
        />
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.animate-fade-in-down {
  animation: fadeInDown 0.3s ease-out;
}

@keyframes fadeInDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
