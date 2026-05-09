<script setup lang="ts">
import { Icon } from '@iconify/vue'
import RichTextEditor from '~/components/Common/RichTextEditor.vue'

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
  if (!confirm(`คุณแน่ใจหรือไม่ที่จะลบรายวิชา "${course.value.name}"? การกระทำนี้ไม่สามารถย้อนกลับได้`)) return
  
  try {
    const response = await api.delete(`/api/courses/${course.value.id}`)
    if (response) {
      navigateTo('/learn/courses')
    }
  } catch (err: any) {
    useToast().error(err.data?.msg || 'ไม่สามารถลบรายวิชาได้')
  }
}
</script>

<template>
  <div class="space-y-8 max-w-7xl mx-auto pb-20">
    
    <!-- Header -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-cyan-500 to-blue-600 p-4 sm:p-8 text-white shadow-lg">
      <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
      <div class="absolute bottom-0 left-0 -mb-10 -ml-10 w-40 h-40 bg-black/10 rounded-full blur-3xl"></div>
      
      <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-3 sm:gap-4">
          <div class="p-2 sm:p-3 bg-white/20 backdrop-blur-md rounded-xl">
             <Icon icon="mdi-light:settings" class="w-6 h-6 sm:w-8 sm:h-8 text-white" />
          </div>
          <div>
            <h1 class="text-lg sm:text-2xl font-bold">ตั้งค่ารายวิชา</h1>
            <p class="text-blue-100 opacity-90 text-sm sm:text-base">จัดการข้อมูลและสถานะของรายวิชา</p>
          </div>
        </div>
        
        <!-- Save Button (Top) -->
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
    <form @submit.prevent="saveSettings" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      
      <!-- Left Column: General Info (2 cols wide) -->
      <div class="lg:col-span-2 space-y-8">
        
        <!-- General Information Card -->
        <section class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
          <div class="p-6 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <Icon icon="heroicons:information-circle" class="w-5 h-5 text-cyan-500" />
              ข้อมูลทั่วไป
            </h2>
          </div>
          
          <div class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
               <div class="space-y-2">
                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">รหัสวิชา</label>
                <div class="relative">
                  <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                    <Icon icon="fluent:number-symbol-square-24-regular" class="w-5 h-5" />
                  </span>
                  <input
                    v-model="form.code"
                    type="text"
                    placeholder="เช่น CS101"
                    class="w-full pl-12 pr-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500 transition-all dark:text-white"
                  />
                </div>
              </div>
              
              <div class="space-y-2">
                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">ชื่อรายวิชา <span class="text-red-500">*</span></label>
                <div class="relative">
                  <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                    <Icon icon="heroicons:book-open" class="w-5 h-5" />
                  </span>
                  <input
                    v-model="form.name"
                    type="text"
                    required
                    placeholder="ชื่อรายวิชา"
                    class="w-full pl-12 pr-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500 transition-all dark:text-white"
                  />
                </div>
              </div>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">คำอธิบายรายวิชา</label>
              <RichTextEditor
                v-model="form.description"
                placeholder="รายละเอียดเกี่ยวกับรายวิชา..."
                class="w-full"
                min-height="200px"
              />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">หมวดหมู่</label>
                <div class="relative">
                   
                  <select
                    v-model="form.category"
                    class="w-full pl-4 pr-10 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500 transition-all dark:text-white appearance-none"
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
                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">ระดับชั้น</label>
                 <div class="relative">
                  <select
                    v-model="form.level"
                    class="w-full pl-4 pr-10 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500 transition-all dark:text-white appearance-none"
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
        </section>

        <!-- Academic Details Card -->
        <section class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
          <div class="p-6 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <Icon icon="heroicons:academic-cap" class="w-5 h-5 text-purple-500" />
              ข้อมูลเชิงวิชาการ
            </h2>
          </div>
          <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
             <div class="space-y-2">
                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">ภาคเรียนที่</label>
                <div class="relative">
                   <select
                    v-model="form.semester"
                    class="w-full pl-4 pr-10 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500 transition-all dark:text-white appearance-none"
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
                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">ปีการศึกษา</label>
                 <div class="relative">
                   <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                    <Icon icon="heroicons:calendar" class="w-5 h-5" />
                  </span>
                  <input
                    v-model="form.academic_year"
                    type="text"
                    placeholder="เช่น 2567"
                    class="w-full pl-12 pr-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500 transition-all dark:text-white"
                  />
                </div>
              </div>

             <div class="space-y-2">
                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">หน่วยกิต</label>
                <div class="relative">
                   <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                    <Icon icon="heroicons:star" class="w-5 h-5" />
                  </span>
                  <input
                    v-model.number="form.credit_units"
                    type="number"
                    min="0"
                    step="0.5"
                    class="w-full pl-12 pr-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500 transition-all dark:text-white"
                  />
                </div>
              </div>
              <div class="space-y-2">
                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">ชั่วโมง/สัปดาห์</label>
                 <div class="relative">
                   <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                    <Icon icon="heroicons:clock" class="w-5 h-5" />
                  </span>
                  <input
                    v-model.number="form.hours_per_week"
                    type="number"
                    min="0"
                    class="w-full pl-12 pr-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500 transition-all dark:text-white"
                  />
                </div>
              </div>
              <div class="space-y-2">
                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">วันเริ่มต้น</label>
                <ClientOnly>
                  <VueDatePicker
                    v-model="form.start_date"
                    model-type="yyyy-MM-dd"
                    :format="formatThaiDate"
                    auto-apply
                    :enable-time-picker="false"
                    teleport="body"
                    placeholder="เลือกวันเริ่มต้น"
                    input-class-name="!bg-gray-50 dark:!bg-gray-900 !border-gray-200 dark:!border-gray-700 !rounded-xl dark:!text-white !py-2.5"
                  />
                </ClientOnly>
              </div>
              <div class="space-y-2">
                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">วันสิ้นสุด</label>
                <ClientOnly>
                  <VueDatePicker
                    v-model="form.end_date"
                    model-type="yyyy-MM-dd"
                    :format="formatThaiDate"
                    auto-apply
                    :enable-time-picker="false"
                    teleport="body"
                    placeholder="เลือกวันสิ้นสุด"
                    input-class-name="!bg-gray-50 dark:!bg-gray-900 !border-gray-200 dark:!border-gray-700 !rounded-xl dark:!text-white !py-2.5"
                  />
                </ClientOnly>
              </div>
          </div>
          <!-- Card Footer with Save Button -->
           <div class="px-6 pb-6 pt-2 hidden lg:flex justify-end border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
             <button
              type="submit"
              :disabled="isSaving"
              class="mt-4 flex items-center gap-2 px-8 py-3 bg-blue-600 text-white font-bold rounded-xl shadow-lg hover:bg-blue-700 active:scale-95 transition-all disabled:opacity-50"
             >
              <Icon v-if="isSaving" icon="svg-spinners:ring-resize" class="w-5 h-5" />
              <Icon v-else icon="fluent:save-24-filled" class="w-5 h-5" />
              บันทึกการเปลี่ยนแปลง
             </button>
           </div>
        </section>



      </div>

      <!-- Right Column: Settings & Danger Zone (1 col wide) -->
      <div class="space-y-8">
        
        <!-- Status & Visibility -->
        <section class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
          <div class="p-6 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <Icon icon="heroicons:globe-alt" class="w-5 h-5 text-green-500" />
              การเผยแพร่
            </h2>
          </div>
          <div class="p-6 space-y-4">
             <div class="space-y-3">
              <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 dark:border-gray-700 cursor-pointer transition-all hover:bg-gray-50 dark:hover:bg-gray-700/50" :class="{'ring-2 ring-green-500 border-transparent': form.status === 'published'}">
                <div class="flex items-center justify-center w-5 h-5 rounded-full border border-gray-300 dark:border-gray-600">
                  <div class="w-3 h-3 rounded-full bg-green-500" v-if="form.status === 'published'"></div>
                </div>
                <input type="radio" v-model="form.status" value="published" class="hidden">
                 <div class="flex-1">
                   <div class="font-semibold text-gray-900 dark:text-white">เผยแพร่</div>
                   <div class="text-xs text-gray-500">ทุกคนสามารถค้นหาและเห็นรายวิชานี้</div>
                 </div>
              </label>

               <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 dark:border-gray-700 cursor-pointer transition-all hover:bg-gray-50 dark:hover:bg-gray-700/50" :class="{'ring-2 ring-gray-400 border-transparent': form.status === 'draft'}">
                <div class="flex items-center justify-center w-5 h-5 rounded-full border border-gray-300 dark:border-gray-600">
                  <div class="w-3 h-3 rounded-full bg-gray-400" v-if="form.status === 'draft'"></div>
                </div>
                <input type="radio" v-model="form.status" value="draft" class="hidden">
                <div class="flex-1">
                   <div class="font-semibold text-gray-900 dark:text-white">ฉบับร่าง</div>
                   <div class="text-xs text-gray-500">เฉพาะผู้ดูแลรายวิชาเท่านั้นที่เห็น</div>
                 </div>
              </label>

              <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 dark:border-gray-700 cursor-pointer transition-all hover:bg-gray-50 dark:hover:bg-gray-700/50" :class="{'ring-2 ring-orange-500 border-transparent': form.status === 'archived'}">
                <div class="flex items-center justify-center w-5 h-5 rounded-full border border-gray-300 dark:border-gray-600">
                  <div class="w-3 h-3 rounded-full bg-orange-500" v-if="form.status === 'archived'"></div>
                </div>
                <input type="radio" v-model="form.status" value="archived" class="hidden">
                <div class="flex-1">
                   <div class="font-semibold text-gray-900 dark:text-white">เก็บถาวร</div>
                   <div class="text-xs text-gray-500">ปิดรับสมาชิกและซ่อนจากการค้นหา</div>
                 </div>
              </label>
             </div>
          </div>
        </section>

        <!-- Configuration -->
        <section class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
          <div class="p-6 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <Icon icon="heroicons:cog-6-tooth" class="w-5 h-5 text-orange-500" />
              การจัดการ
            </h2>
          </div>
          <div class="p-6 space-y-6">
             <!-- Auto Accept -->
            <div class="flex items-center justify-between">
              <div>
                <div class="font-semibold text-gray-900 dark:text-white">อนุมัติสมาชิกอัตโนมัติ</div>
                <div class="text-xs text-gray-500">ไม่ต้องกดยืนยันคำขอ</div>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input v-model="form.auto_accept_members" type="checkbox" class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 dark:peer-focus:ring-orange-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-orange-500"></div>
              </label>
            </div>

            <hr class="border-gray-100 dark:border-gray-700">
            
            <!-- Saleable -->
            <div class="flex items-center justify-between">
              <div>
                <div class="font-semibold text-gray-900 dark:text-white">เปิดขายรายวิชา</div>
                <div class="text-xs text-gray-500">กำหนดราคาและขาย</div>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input v-model="form.saleable" type="checkbox" class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 dark:peer-focus:ring-orange-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-orange-500"></div>
              </label>
            </div>

            <!-- Price Input -->
            <div v-if="form.saleable" class="pt-2 animate-fade-in-down grid grid-cols-1 gap-4">
               
               <div class="space-y-2">
                  <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">ราคา (บาท)</label>
                  <div class="relative">
                      <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                        <Icon icon="heroicons:currency-dollar" class="w-5 h-5" />
                      </span>
                      <input
                        v-model.number="form.price"
                        type="number"
                        min="0"
                        placeholder="0.00"
                        class="w-full pl-12 pr-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-orange-500/50 focus:border-orange-500 transition-all dark:text-white"
                      />
                  </div>
               </div>


               <div class="space-y-2">
                  <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">ส่วนลด</label>
                  <div class="flex gap-2">
                    <div class="relative flex-1">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                           <Icon v-if="form.discount_type === 'fixed'" icon="heroicons:currency-dollar" class="w-5 h-5" />
                           <Icon v-else icon="heroicons:receipt-percent" class="w-5 h-5" />
                        </span>
                        <input
                          v-model.number="form.discount"
                          type="number"
                          min="0"
                          :max="form.discount_type === 'percent' ? 100 : form.price"
                          placeholder="0"
                          class="w-full pl-12 pr-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-orange-500/50 focus:border-orange-500 transition-all dark:text-white"
                        />
                    </div>
                    <select
                      v-model="form.discount_type"
                      class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-orange-500/50 focus:border-orange-500 text-sm font-medium dark:text-white appearance-none cursor-pointer"
                    >
                      <option value="fixed">บาท</option>
                      <option value="percent">%</option>
                    </select>
                  </div>
               </div>

               <div v-if="form.saleable" class="col-span-1 border-t pt-4 mt-2">
                 <div class="flex justify-between items-center text-lg font-bold">
                   <span class="text-gray-700 dark:text-gray-300">ราคาขายจริง (สุทธิ):</span>
                   <span class="text-green-600 dark:text-green-400">
                     {{ netPrice.toLocaleString() }} บาท
                   </span>
                 </div>
                 <p class="text-xs text-gray-500 text-right mt-1" v-if="form.discount > 0">
                   (จากราคาปกติ {{ form.price.toLocaleString() }} บาท ลด {{ form.discount_type === 'percent' ? form.discount + '%' : form.discount.toLocaleString() + ' บาท' }})
                 </p>
               </div>


            </div>

          </div>
        </section>

        <!-- Marketplace Settings -->
        <section class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
          <div class="p-6 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <Icon icon="heroicons:shopping-cart" class="w-5 h-5 text-amber-500" />
              ตั้งค่า Marketplace
            </h2>
          </div>
          <div class="p-6 space-y-6">
            <div class="flex items-center justify-between">
              <div>
                <div class="font-semibold text-gray-900 dark:text-white">วางขายใน Marketplace</div>
                <div class="text-xs text-gray-500">อนุญาตให้คนอื่นซื้อสำเนาวิชานี้ได้</div>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input v-model="form.is_for_marketplace" type="checkbox" class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 dark:peer-focus:ring-amber-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-amber-500"></div>
              </label>
            </div>

            <div v-if="form.is_for_marketplace" class="pt-2 animate-fade-in-down space-y-4">
              <div class="space-y-2">
                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">ประเภทการชำระเงิน</label>
                <select v-model="form.price_type" class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm font-medium dark:text-white">
                  <option value="free">ฟรี</option>
                  <option value="points">ใช้แต้ม (Points)</option>
                  <option value="wallet">ใช้เงิน (Wallet)</option>
                  <option value="both">ใช้ได้ทั้งสองอย่าง</option>
                </select>
              </div>

              <div v-if="form.price_type === 'points' || form.price_type === 'both'" class="space-y-2">
                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">ราคาเป็นแต้ม (Points)</label>
                <div class="relative">
                  <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                    <Icon icon="mdi:database" class="w-5 h-5" />
                  </span>
                  <input
                    v-model.number="form.price_points"
                    type="number"
                    min="0"
                    placeholder="ใส่จำนวนแต้ม..."
                    class="w-full pl-12 pr-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 transition-all dark:text-white"
                  />
                </div>
              </div>

              <div v-if="form.price_type === 'wallet' || form.price_type === 'both'" class="space-y-2">
                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">ราคาเป็นเงิน (บาท)</label>
                <div class="relative">
                  <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold">฿</span>
                  <input
                    v-model.number="form.price"
                    type="number"
                    min="0"
                    placeholder="0.00"
                    class="w-full pl-12 pr-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 transition-all dark:text-white"
                  />
                </div>
              </div>
            </div>

            <!-- Sales Stats (แสดงเฉพาะถ้าเคยขาย) -->
            <div v-if="form.is_for_marketplace && course?.total_sales > 0" 
              class="mt-4 p-4 bg-amber-50 dark:bg-amber-900/10 rounded-xl border border-amber-200 dark:border-amber-800">
              <div class="text-xs font-bold text-amber-600 uppercase mb-3">สถิติการขาย</div>
              <div class="grid grid-cols-2 gap-4">
                <div class="text-center">
                  <div class="text-2xl font-black text-slate-800 dark:text-white">{{ course?.total_sales || 0 }}</div>
                  <div class="text-xs text-slate-500">ยอดขายทั้งหมด</div>
                </div>
                <div class="text-center">
                  <div class="text-2xl font-black text-slate-800 dark:text-white">
                    {{ course?.price_type === 'points' ? (course?.price_points * course?.total_sales) + ' P' : '฿' + (course?.price * course?.total_sales) }}
                  </div>
                  <div class="text-xs text-slate-500">รายได้ทั้งหมด (ประมาณ)</div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Danger Zone -->
        <section class="bg-red-50 dark:bg-red-900/10 rounded-2xl shadow-sm border border-red-100 dark:border-red-900/30 overflow-hidden">
           <div class="p-6 border-b border-red-100 dark:border-red-900/30 bg-red-100/50 dark:bg-red-900/20">
            <h2 class="text-lg font-bold text-red-600 dark:text-red-400 flex items-center gap-2">
              <Icon icon="heroicons:exclamation-triangle" class="w-5 h-5" />
              โซนอันตราย
            </h2>
          </div>
          <div class="p-6 text-center">
            <p class="text-sm text-red-600/80 dark:text-red-400/80 mb-4">
              การลบรายวิชาจะไม่สามารถกู้คืนได้ ข้อมูลทั้งหมดจะหายไปถาวร
            </p>
             <button
              type="button"
              @click="deleteCourse"
              class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-red-500 hover:bg-red-600 text-white font-bold rounded-xl transition-colors shadow-lg shadow-red-500/20"
            >
              <Icon icon="fluent:delete-24-filled" class="w-5 h-5" />
              ลบรายวิชาถาวร
            </button>
          </div>
        </section>

      </div>



      <!-- Mobile Save Button (Sticky Bottom) -->
      <div class="fixed bottom-16 sm:bottom-0 left-0 right-0 p-4 bg-white/80 dark:bg-gray-900/80 backdrop-blur-lg border-t border-gray-200 dark:border-gray-800 lg:hidden z-50">
        <button
          type="submit"
          :disabled="isSaving"
          class="w-full flex items-center justify-center gap-2 px-6 py-3 bg-blue-600 text-white font-bold rounded-xl shadow-lg active:scale-95 transition-all disabled:opacity-50"
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
