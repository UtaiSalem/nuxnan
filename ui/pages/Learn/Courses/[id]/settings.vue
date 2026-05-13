<script setup lang="ts">
import { Icon } from '@iconify/vue'
import RichTextEditor from '~/components/Common/RichTextEditor.vue'
import ResponsiveCard from '~/components/Common/ResponsiveCard.vue'

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
const api = useApi()

// Form data
const form = ref({
  code: '',
  name: '',
  description: '',
  category: '',
  level: '',
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
  price_type: 'free'
})

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

// Course levels
const courseLevels = [
  'ชั้นประถมศึกษาปีที่ 1',
  'ชั้นประถมศึกษาปีที่ 2',
  'ชั้นประถมศึกษาปีที่ 3',
  'ชั้นประถมศึกษาปีที่ 4',
  'ชั้นประถมศึกษาปีที่ 5',
  'ชั้นประถมศึกษาปีที่ 6',
  'ชั้นมัธยมศึกษาปีที่ 1',
  'ชั้นมัธยมศึกษาปีที่ 2',
  'ชั้นมัธยมศึกษาปีที่ 3',
  'ชั้นมัธยมศึกษาปีที่ 4',
  'ชั้นมัธยมศึกษาปีที่ 5',
  'ชั้นมัธยมศึกษาปีที่ 6',
  'อุดมศึกษา',
  'ทั่วไป'
]

// Initialize form with course data
watch(() => course?.value, (newCourse) => {
  if (newCourse) {
    form.value = {
      code: newCourse.code || '',
      name: newCourse.name || '',
      description: newCourse.description || DEFAULT_DESCRIPTION_TEMPLATE,
      category: newCourse.category || '',
      level: newCourse.level || '',
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
      status: newCourse.status || 'draft',
      is_for_marketplace: Boolean(newCourse.is_for_marketplace),
      price_points: newCourse.price_points || 0,
      price_type: newCourse.price_type || 'free'
    }
  }
}, { immediate: true })

// Net Price Calculation
const netPrice = computed(() => {
    if (!form.value.saleable) return 0
    const price = Number(form.value.price) || 0
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
    const response = await api.put(`/api/courses/${course.value.id}`, form.value)
    if (response) {
       useToast().success('บันทึกการตั้งค่าเรียบร้อยแล้ว')
       if (refreshCourse) refreshCourse()
    }
  } catch (err: any) {
    useToast().error(err.data?.msg || 'ไม่สามารถบันทึกได้')
  } finally {
    isSaving.value = false
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
          @click="saveSettings"
          :disabled="isSaving"
          class="hidden md:flex items-center gap-2 px-6 py-2.5 bg-white text-blue-600 font-bold rounded-xl shadow-lg hover:bg-blue-50 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <Icon v-if="isSaving" icon="svg-spinners:ring-resize" class="w-5 h-5" />
          <Icon v-else icon="fluent:save-24-filled" class="w-5 h-5" />
          บันทึกการเปลี่ยนแปลง
        </button>
      </div>
    </div>

    <!-- Main Form -->
    <form @submit.prevent="saveSettings" class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">
      
      <!-- Left Column: General Info (2 cols wide) -->
      <div class="lg:col-span-2 space-y-6 sm:space-y-8">
        
        <!-- General Information Card -->
        <ResponsiveCard title="ข้อมูลทั่วไป" icon="heroicons:information-circle" icon-color="text-cyan-500">
          <div class="space-y-5 sm:space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
               <div class="space-y-2">
                <label class="text-sm font-bold text-gray-700 dark:text-gray-300">รหัสวิชา</label>
                <div class="relative group">
                  <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-cyan-500 transition-colors">
                    <Icon icon="fluent:number-symbol-square-24-regular" class="w-5 h-5" />
                  </span>
                  <input
                    v-model="form.code"
                    type="text"
                    placeholder="เช่น CS101"
                    class="w-full pl-12 pr-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-cyan-500/30 focus:border-cyan-500 transition-all dark:text-white text-base"
                  />
                </div>
              </div>
              
              <div class="space-y-2">
                <label class="text-sm font-bold text-gray-700 dark:text-gray-300">ชื่อรายวิชา <span class="text-red-500">*</span></label>
                <div class="relative group">
                  <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-cyan-500 transition-colors">
                    <Icon icon="heroicons:book-open" class="w-5 h-5" />
                  </span>
                  <input
                    v-model="form.name"
                    type="text"
                    required
                    placeholder="ชื่อรายวิชา"
                    class="w-full pl-12 pr-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-cyan-500/30 focus:border-cyan-500 transition-all dark:text-white text-base"
                  />
                </div>
              </div>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-bold text-gray-700 dark:text-gray-300">คำอธิบายรายวิชา</label>
              <RichTextEditor
                v-model="form.description"
                placeholder="รายละเอียดเกี่ยวกับรายวิชา..."
                class="w-full"
                min-height="200px"
              />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
              <div class="space-y-2">
                <label class="text-sm font-bold text-gray-700 dark:text-gray-300">หมวดหมู่</label>
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
              </div>
              
               <div class="space-y-2">
                <label class="text-sm font-bold text-gray-700 dark:text-gray-300">ระดับชั้น</label>
                 <div class="relative">
                  <select
                    v-model="form.level"
                    class="w-full pl-4 pr-10 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-cyan-500/30 focus:border-cyan-500 transition-all dark:text-white text-base appearance-none cursor-pointer"
                  >
                    <option value="">เลือกระดับชั้น</option>
                    <option v-for="level in courseLevels" :key="level" :value="level">{{ level }}</option>
                  </select>
                  <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                    <Icon icon="heroicons:chevron-down" class="w-5 h-5" />
                  </span>
                </div>
              </div>
            </div>
          </div>
        </ResponsiveCard>

        <!-- Academic Details Card -->
        <ResponsiveCard title="ข้อมูลเชิงวิชาการ" icon="heroicons:academic-cap" icon-color="text-purple-500">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
             <div class="space-y-2">
                <label class="text-sm font-bold text-gray-700 dark:text-gray-300">ภาคเรียนที่</label>
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
              </div>
              <div class="space-y-2">
                <label class="text-sm font-bold text-gray-700 dark:text-gray-300">ปีการศึกษา</label>
                 <div class="relative group">
                   <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-purple-500 transition-colors">
                    <Icon icon="heroicons:calendar" class="w-5 h-5" />
                  </span>
                  <input
                    v-model="form.academic_year"
                    type="text"
                    placeholder="เช่น 2567"
                    class="w-full pl-12 pr-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 transition-all dark:text-white text-base"
                  />
                </div>
              </div>

             <div class="space-y-2">
                <label class="text-sm font-bold text-gray-700 dark:text-gray-300">หน่วยกิต</label>
                <div class="relative group">
                   <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-purple-500 transition-colors">
                    <Icon icon="heroicons:star" class="w-5 h-5" />
                  </span>
                  <input
                    v-model.number="form.credit_units"
                    type="number"
                    min="0"
                    step="0.5"
                    class="w-full pl-12 pr-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 transition-all dark:text-white text-base"
                  />
                </div>
              </div>
              <div class="space-y-2">
                <label class="text-sm font-bold text-gray-700 dark:text-gray-300">ชั่วโมง/สัปดาห์</label>
                 <div class="relative group">
                   <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-purple-500 transition-colors">
                    <Icon icon="heroicons:clock" class="w-5 h-5" />
                  </span>
                  <input
                    v-model.number="form.hours_per_week"
                    type="number"
                    min="0"
                    class="w-full pl-12 pr-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 transition-all dark:text-white text-base"
                  />
                </div>
              </div>
              <div class="space-y-2 min-w-0">
                <label class="text-sm font-bold text-gray-700 dark:text-gray-300">วันเริ่มต้น</label>
                <ClientOnly>
                  <VueDatePicker
                    v-model="form.start_date"
                    model-type="yyyy-MM-dd"
                    :format="formatThaiDate"
                    auto-apply
                    :enable-time-picker="false"
                    teleport="body"
                    placeholder="เลือกวันเริ่มต้น"
                    input-class-name="!bg-gray-50 dark:!bg-gray-900 !border-gray-200 dark:!border-gray-700 !rounded-xl dark:!text-white !py-3 !w-full !text-base"
                  />
                </ClientOnly>
              </div>
              <div class="space-y-2 min-w-0">
                <label class="text-sm font-bold text-gray-700 dark:text-gray-300">วันสิ้นสุด</label>
                <ClientOnly>
                  <VueDatePicker
                    v-model="form.end_date"
                    model-type="yyyy-MM-dd"
                    :format="formatThaiDate"
                    auto-apply
                    :enable-time-picker="false"
                    teleport="body"
                    placeholder="เลือกวันสิ้นสุด"
                    input-class-name="!bg-gray-50 dark:!bg-gray-900 !border-gray-200 dark:!border-gray-700 !rounded-xl dark:!text-white !py-3 !w-full !text-base"
                  />
                </ClientOnly>
              </div>
          </div>
        </ResponsiveCard>
      </div>

      <!-- Right Column: Settings & Danger Zone (1 col wide) -->
      <div class="space-y-6 sm:space-y-8">
        
        <!-- Status & Visibility -->
        <ResponsiveCard title="การเผยแพร่" icon="heroicons:globe-alt" icon-color="text-green-500">
          <div class="space-y-4">
             <div class="space-y-3">
              <label class="flex items-center gap-3 p-4 rounded-xl border-2 border-gray-100 dark:border-gray-800 cursor-pointer transition-all hover:bg-gray-50 dark:hover:bg-gray-700/50 min-h-[72px]" :class="{'border-green-500 bg-green-50/30 dark:bg-green-900/10': form.status === 'published'}">
                <div class="flex items-center justify-center w-6 h-6 rounded-full border-2 border-gray-300 dark:border-gray-600">
                  <div class="w-3.5 h-3.5 rounded-full bg-green-500" v-if="form.status === 'published'"></div>
                </div>
                <input type="radio" v-model="form.status" value="published" class="hidden">
                 <div class="flex-1">
                   <div class="font-bold text-gray-900 dark:text-white text-base">เผยแพร่</div>
                   <div class="text-[11px] text-gray-500 dark:text-gray-400">ทุกคนสามารถค้นหาและเห็นรายวิชานี้</div>
                 </div>
              </label>

               <label class="flex items-center gap-3 p-4 rounded-xl border-2 border-gray-100 dark:border-gray-800 cursor-pointer transition-all hover:bg-gray-50 dark:hover:bg-gray-700/50 min-h-[72px]" :class="{'border-gray-400 bg-gray-50 dark:bg-gray-700/30': form.status === 'draft'}">
                <div class="flex items-center justify-center w-6 h-6 rounded-full border-2 border-gray-300 dark:border-gray-600">
                  <div class="w-3.5 h-3.5 rounded-full bg-gray-400" v-if="form.status === 'draft'"></div>
                </div>
                <input type="radio" v-model="form.status" value="draft" class="hidden">
                <div class="flex-1">
                   <div class="font-bold text-gray-900 dark:text-white text-base">ฉบับร่าง</div>
                   <div class="text-[11px] text-gray-500 dark:text-gray-400">เฉพาะผู้ดูแลรายวิชาเท่านั้นที่เห็น</div>
                 </div>
              </label>

              <label class="flex items-center gap-3 p-4 rounded-xl border-2 border-gray-100 dark:border-gray-800 cursor-pointer transition-all hover:bg-gray-50 dark:hover:bg-gray-700/50 min-h-[72px]" :class="{'border-orange-500 bg-orange-50/30 dark:bg-orange-900/10': form.status === 'archived'}">
                <div class="flex items-center justify-center w-6 h-6 rounded-full border-2 border-gray-300 dark:border-gray-600">
                  <div class="w-3.5 h-3.5 rounded-full bg-orange-500" v-if="form.status === 'archived'"></div>
                </div>
                <input type="radio" v-model="form.status" value="archived" class="hidden">
                <div class="flex-1">
                   <div class="font-bold text-gray-900 dark:text-white text-base">เก็บถาวร</div>
                   <div class="text-[11px] text-gray-500 dark:text-gray-400">ปิดรับสมาชิกและซ่อนจากการค้นหา</div>
                 </div>
              </label>
             </div>
          </div>
        </ResponsiveCard>

        <!-- Configuration -->
        <ResponsiveCard title="การจัดการ" icon="heroicons:cog-6-tooth" icon-color="text-orange-500">
          <div class="space-y-6">
             <!-- Auto Accept -->
            <label class="flex items-center justify-between cursor-pointer min-h-[64px] gap-4">
              <div class="min-w-0">
                <div class="font-bold text-gray-900 dark:text-white text-base">อนุมัติสมาชิกอัตโนมัติ</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">ไม่ต้องกดยืนยันคำขอ</div>
              </div>
              <div class="relative inline-flex items-center flex-shrink-0">
                <input v-model="form.auto_accept_members" type="checkbox" class="sr-only peer">
                <div class="w-12 h-6.5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 dark:peer-focus:ring-orange-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-orange-500"></div>
              </div>
            </label>

            <hr class="border-gray-100 dark:border-gray-700">
            
            <!-- Saleable (Enrollment Fee) -->
            <label class="flex items-center justify-between cursor-pointer min-h-[64px] gap-4">
              <div class="min-w-0">
                <div class="font-bold text-gray-900 dark:text-white text-base">เปิดรับสมัครเรียน (แบบเก็บค่าธรรมเนียม)</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">เก็บค่าธรรมเนียมสำหรับผู้ที่ต้องการเข้าเรียน</div>
              </div>
              <div class="relative inline-flex items-center flex-shrink-0">
                <input v-model="form.saleable" type="checkbox" class="sr-only peer">
                <div class="w-12 h-6.5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 dark:peer-focus:ring-orange-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-orange-500"></div>
              </div>
            </label>

            <!-- Price Input -->
            <div v-if="form.saleable" class="pt-2 animate-fade-in-down grid grid-cols-1 gap-5">
               
               <div class="space-y-2">
                  <label class="text-sm font-bold text-gray-700 dark:text-gray-300">ค่าธรรมเนียมสมัครเรียน (บาท)</label>
                  <div class="relative group">
                      <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-orange-500 transition-colors">
                        <Icon icon="heroicons:currency-dollar" class="w-5 h-5" />
                      </span>
                      <input
                        v-model.number="form.price"
                        type="number"
                        min="0"
                        placeholder="0.00"
                        class="w-full pl-12 pr-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-orange-500/30 focus:border-orange-500 transition-all dark:text-white text-base"
                      />
                  </div>
               </div>


               <div class="space-y-2">
                  <label class="text-sm font-bold text-gray-700 dark:text-gray-300">ส่วนลดค่าสมัคร</label>
                  <div class="flex gap-2">
                    <div class="relative flex-1 group">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-orange-500 transition-colors">
                           <Icon v-if="form.discount_type === 'fixed'" icon="heroicons:currency-dollar" class="w-5 h-5" />
                           <Icon v-else icon="heroicons:receipt-percent" class="w-5 h-5" />
                        </span>
                        <input
                          v-model.number="form.discount"
                          type="number"
                          min="0"
                          :max="form.discount_type === 'percent' ? 100 : form.price"
                          placeholder="0"
                          class="w-full pl-12 pr-3 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-orange-500/30 focus:border-orange-500 transition-all dark:text-white text-base"
                        />
                    </div>
                    <select
                      v-model="form.discount_type"
                      class="flex-shrink-0 w-20 sm:w-24 bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-3 focus:ring-2 focus:ring-orange-500/30 focus:border-orange-500 text-sm font-bold dark:text-white appearance-none cursor-pointer text-center"
                    >
                      <option value="fixed">บาท</option>
                      <option value="percent">%</option>
                    </select>
                  </div>
               </div>

               <div class="col-span-1 border-t border-gray-100 dark:border-gray-700 pt-4 mt-1">
                 <div class="flex justify-between items-center text-lg font-black">
                   <span class="text-gray-700 dark:text-gray-300">ค่าสมัครสุทธิ:</span>
                   <span class="text-emerald-600 dark:text-emerald-400">
                     {{ netPrice.toLocaleString() }} บาท
                   </span>
                 </div>
                 <p class="text-[10px] text-gray-500 text-right mt-1" v-if="form.discount > 0">
                   (จากราคาปกติ {{ form.price.toLocaleString() }} บาท ลด {{ form.discount_type === 'percent' ? form.discount + '%' : form.discount.toLocaleString() + ' บาท' }})
                 </p>
               </div>
            </div>
          </div>
        </ResponsiveCard>

        <!-- Marketplace Settings -->
        <ResponsiveCard title="ตลาดรายวิชา (Marketplace)" icon="heroicons:shopping-cart" icon-color="text-amber-500">
          <div class="space-y-6">
            <label class="flex items-center justify-between cursor-pointer min-h-[64px] gap-4">
              <div class="min-w-0">
                <div class="font-bold text-gray-900 dark:text-white text-base">เปิดขายสำเนา (Clone)</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">ให้ครูท่านอื่นซื้อไปสอนต่อ</div>
              </div>
              <div class="relative inline-flex items-center flex-shrink-0">
                <input v-model="form.is_for_marketplace" type="checkbox" class="sr-only peer">
                <div class="w-12 h-6.5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 dark:peer-focus:ring-amber-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-amber-500"></div>
              </div>
            </label>

            <div v-if="form.is_for_marketplace" class="pt-2 animate-fade-in-down space-y-5">
              <div class="space-y-2">
                <label class="text-sm font-bold text-gray-700 dark:text-gray-300">ประเภทการชำระเงิน</label>
                <div class="relative">
                  <select v-model="form.price_type" class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-3 text-base font-bold dark:text-white appearance-none cursor-pointer">
                    <option value="free">เปิดให้ Clone ฟรี</option>
                    <option value="points">ใช้แต้ม (Points)</option>
                    <option value="wallet">ใช้เงิน (Wallet)</option>
                    <option value="both">ใช้ได้ทั้งสองอย่าง</option>
                  </select>
                  <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                    <Icon icon="heroicons:chevron-down" class="w-5 h-5" />
                  </span>
                </div>
              </div>

              <div v-if="form.price_type === 'both'" class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="space-y-2">
                  <label class="text-sm font-bold text-gray-700 dark:text-gray-300">ราคา (แต้ม)</label>
                  <div class="relative group">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-amber-500">
                      <Icon icon="mdi:database" class="w-5 h-5" />
                    </span>
                    <input
                      v-model.number="form.price_points"
                      type="number"
                      min="0"
                      placeholder="แต้ม..."
                      class="w-full pl-12 pr-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-all dark:text-white text-base"
                    />
                  </div>
                </div>

                <div class="space-y-2">
                  <label class="text-sm font-bold text-gray-700 dark:text-gray-300">ราคา (บาท)</label>
                  <div class="relative group">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-black group-focus-within:text-amber-500">฿</span>
                    <input
                      v-model.number="form.price"
                      type="number"
                      min="0"
                      placeholder="0.00"
                      class="w-full pl-12 pr-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-all dark:text-white text-base"
                    />
                  </div>
                </div>
              </div>

              <div v-else-if="form.price_type === 'points'" class="space-y-2">
                <label class="text-sm font-bold text-gray-700 dark:text-gray-300">ราคา (แต้ม)</label>
                <div class="relative group">
                  <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-amber-500">
                    <Icon icon="mdi:database" class="w-5 h-5" />
                  </span>
                  <input
                    v-model.number="form.price_points"
                    type="number"
                    min="0"
                    placeholder="ใส่จำนวนแต้ม..."
                    class="w-full pl-12 pr-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-all dark:text-white text-base"
                  />
                </div>
              </div>

              <div v-else-if="form.price_type === 'wallet'" class="space-y-2">
                <label class="text-sm font-bold text-gray-700 dark:text-gray-300">ราคา (บาท)</label>
                <div class="relative group">
                  <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-black group-focus-within:text-amber-500">฿</span>
                  <input
                    v-model.number="form.price"
                    type="number"
                    min="0"
                    placeholder="0.00"
                    class="w-full pl-12 pr-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-all dark:text-white text-base"
                  />
                </div>
              </div>
            </div>

            <!-- Sales Stats (แสดงเฉพาะถ้าเคยขาย) -->
            <div v-if="form.is_for_marketplace && course?.total_sales > 0" 
              class="mt-4 p-4 bg-amber-50 dark:bg-amber-900/10 rounded-xl border border-amber-200 dark:border-amber-800">
              <div class="text-[10px] font-black text-amber-600 uppercase mb-3 tracking-wider">สถิติลิขสิทธิ์รายวิชา</div>
              <div class="grid grid-cols-2 gap-4">
                <div class="text-center min-w-0">
                  <div class="text-xl sm:text-2xl font-black text-slate-800 dark:text-white truncate">{{ course?.total_sales || 0 }}</div>
                  <div class="text-[10px] text-slate-500 font-bold">ยอด Clone</div>
                </div>
                <div class="text-center min-w-0 border-l border-amber-200 dark:border-amber-800">
                  <div class="text-xl sm:text-2xl font-black text-slate-800 dark:text-white truncate">
                    {{ course?.price_type === 'points' ? (course?.price_points * course?.total_sales) + ' P' : '฿' + (course?.price * course?.total_sales).toLocaleString() }}
                  </div>
                  <div class="text-[10px] text-slate-500 font-bold">รายได้รวม</div>
                </div>
              </div>
            </div>
          </div>
        </ResponsiveCard>

        <!-- Danger Zone -->
        <ResponsiveCard title="โซนอันตราย" icon="heroicons:exclamation-triangle" icon-color="text-red-500" variant="danger">
          <div class="text-center py-2">
            <p class="text-xs text-red-600/80 dark:text-red-400/80 mb-5 leading-relaxed">
              การลบรายวิชาจะไม่สามารถกู้คืนได้ ข้อมูลทั้งหมดจะหายไปถาวร กรุณาตรวจสอบให้แน่ใจ
            </p>
             <button
              type="button"
              @click="deleteCourse"
              class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-red-500 hover:bg-red-600 text-white font-bold rounded-xl transition-all shadow-lg shadow-red-500/20 active:scale-95"
            >
              <Icon icon="fluent:delete-24-filled" class="w-5 h-5" />
              ลบรายวิชาถาวร
            </button>
          </div>
        </ResponsiveCard>

      </div>



      <!-- Mobile Save Button (Sticky Bottom) -->
      <div class="fixed bottom-16 left-0 right-0 px-4 pt-3 pb-3 bg-white/95 dark:bg-gray-900/95 backdrop-blur-md border-t border-gray-200 dark:border-gray-800 md:hidden z-40"
           style="padding-bottom: calc(0.75rem + env(safe-area-inset-bottom));">
        <button
          type="submit"
          :disabled="isSaving"
          class="w-full flex items-center justify-center gap-2 px-6 py-3.5 bg-blue-600 text-white font-black rounded-xl shadow-xl active:scale-95 transition-all disabled:opacity-50 min-h-[48px] text-base"
        >
          <Icon v-if="isSaving" icon="svg-spinners:ring-resize" class="w-5 h-5" />
          <Icon v-else icon="fluent:save-24-filled" class="w-5 h-5" />
          บันทึกการเปลี่ยนแปลง
        </button>
      </div>

    </form>
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
