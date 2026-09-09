<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import RichTextEditor from '~/components/Common/RichTextEditor.vue'

definePageMeta({
  layout: 'nuxnan-admin-layout',
  middleware: 'nuxnan-admin'
})

const config = useRuntimeConfig()
const apiBase = config.public.apiBase as string
const router = useRouter()
const route = useRoute()
const courseId = route.params.id as string

// State
const isLoading = ref(true)
const loadError = ref('')
const isSubmitting = ref(false)
const errors = ref<Record<string, string>>({})
const successMessage = ref('')
const coverUrl = ref<string | null>(null)

// Form — เฉพาะฟิลด์ที่ UpdateCourseRequest รับจริง (ปก/รูปแก้ที่หน้าตั้งค่ารายวิชา ไม่ใช่ที่นี่)
const form = reactive({
  name: '',
  description: '',
  status: 'draft' as string,
  price: 0 as number,
  education_level: '' as string,
  education_year: null as number | null,
  duration: null as number | null,
  capacity: null as number | null,
  language: '' as string
})

// ตรงกับ Rule::in ของ UpdateCourseRequest.education_level
const educationLevels = ['ประถมศึกษา', 'มัธยมศึกษา', 'ปวช.', 'ปวส.', 'อุดมศึกษา', 'อื่นๆ']

// ส่ง status เป็น string ให้ backend (Course::setStatusAttribute) แปลงเป็น int เอง
// courses.status เป็น tinyint: 1=published, 2=archived, else=draft (ตามหน้า Course Settings)
const statuses = [
  { value: 'published', label: 'เผยแพร่' },
  { value: 'draft', label: 'ฉบับร่าง' },
  { value: 'archived', label: 'เก็บถาวร' }
]

// status ที่โหลดมาจาก API เป็น int (tinyint) — map กลับเป็น string ให้ตรงกับ select
// 1=published, 2=draft, 3=archived
const normalizeStatus = (status: any): string => {
  const n = Number(status)
  if (n === 1) return 'published'
  if (n === 3) return 'archived'
  if (typeof status === 'string' && statuses.some(o => o.value === status)) return status
  return 'draft'
}

const fetchCourse = async () => {
  isLoading.value = true
  loadError.value = ''
  try {
    const token = useCookie('token')
    const res = await $fetch<any>(`${apiBase}/api/admin/courses/${courseId}`, {
      headers: { Authorization: `Bearer ${token.value}`, Accept: 'application/json' }
    })
    const c = res?.data ?? res
    if (!c || !c.id) {
      loadError.value = 'ไม่พบรายวิชานี้'
      return
    }
    // courses มีคอลัมน์ name เท่านั้น (ไม่มี title) — เผื่อ payload เก่าที่มี title ไว้ fallback
    form.name = c.name ?? c.title ?? ''
    form.description = c.description ?? ''
    form.status = normalizeStatus(c.status)
    form.price = Number(c.price) || 0
    form.education_level = c.education_level ?? ''
    form.education_year = c.education_year != null ? Number(c.education_year) : null
    form.duration = c.duration != null ? Number(c.duration) : null
    form.capacity = c.capacity != null ? Number(c.capacity) : null
    form.language = c.language ?? ''
    coverUrl.value = c.cover_url ?? null
  } catch (error: any) {
    if (error?.statusCode === 404 || error?.response?.status === 404) {
      loadError.value = 'ไม่พบรายวิชานี้'
    } else {
      loadError.value = error?.data?.message || 'โหลดข้อมูลรายวิชาไม่สำเร็จ'
    }
  } finally {
    isLoading.value = false
  }
}

const validateForm = () => {
  errors.value = {}
  if (!form.name) errors.value.name = 'กรุณากรอกชื่อรายวิชา'
  if (!form.description) errors.value.description = 'กรุณากรอกคำอธิบาย'
  return Object.keys(errors.value).length === 0
}

const handleSubmit = async () => {
  if (!validateForm()) return

  isSubmitting.value = true
  successMessage.value = ''
  errors.value = {}

  try {
    const token = useCookie('token')

    // ส่งเฉพาะฟิลด์ที่มีค่า — enum อย่าง education_level ถ้าส่ง '' จะไม่ผ่าน Rule::in
    const payload: Record<string, any> = {
      name: form.name,
      description: form.description,
      status: form.status,
      price: Number(form.price) || 0
    }
    if (form.education_level) payload.education_level = form.education_level
    if (form.education_year != null && form.education_year !== ('' as any)) payload.education_year = Number(form.education_year)
    if (form.duration != null && form.duration !== ('' as any)) payload.duration = Number(form.duration)
    if (form.capacity != null && form.capacity !== ('' as any)) payload.capacity = Number(form.capacity)
    if (form.language) payload.language = form.language

    const res = await $fetch<any>(`${apiBase}/api/admin/courses/${courseId}`, {
      method: 'PUT',
      headers: {
        Authorization: `Bearer ${token.value}`,
        Accept: 'application/json'
      },
      body: payload
    })

    if (res?.success) {
      successMessage.value = 'บันทึกการแก้ไขสำเร็จ'
      setTimeout(() => {
        router.push('/nuxnan-admin/courses')
      }, 1200)
    }
  } catch (error: any) {
    if (error?.data?.errors) {
      // Laravel validation errors → เก็บข้อความแรกของแต่ละฟิลด์
      const raw = error.data.errors as Record<string, string[]>
      errors.value = Object.fromEntries(Object.entries(raw).map(([k, v]) => [k, v?.[0] ?? '']))
    } else {
      errors.value.general = error?.data?.message || 'บันทึกไม่สำเร็จ กรุณาลองใหม่อีกครั้ง'
    }
  } finally {
    isSubmitting.value = false
  }
}

onMounted(fetchCourse)
</script>

<template>
  <div class="space-y-6 max-w-3xl mx-auto">
    <!-- Page Header -->
    <div class="flex items-center gap-3 sm:gap-4">
      <NuxtLink
        to="/nuxnan-admin/courses"
        class="shrink-0 min-h-[44px] min-w-[44px] inline-flex items-center justify-center p-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl transition-colors"
      >
        <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
      </NuxtLink>
      <div class="min-w-0">
        <h1 class="text-xl sm:text-2xl font-bold text-slate-800 dark:text-white break-words">แก้ไขรายวิชา</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">ปรับปรุงข้อมูลรายวิชา</p>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="flex items-center justify-center py-20 text-slate-500 dark:text-slate-400">
      <Icon icon="fluent:spinner-ios-20-regular" class="w-6 h-6 animate-spin" />
      <span class="ml-2">กำลังโหลด...</span>
    </div>

    <!-- Load Error / Not Found -->
    <div v-else-if="loadError" class="p-6 bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 text-center">
      <Icon icon="fluent:document-error-24-regular" class="w-12 h-12 text-slate-300 mx-auto mb-3" />
      <p class="text-slate-600 dark:text-slate-300 mb-4">{{ loadError }}</p>
      <NuxtLink
        to="/nuxnan-admin/courses"
        class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-xl text-slate-700 dark:text-slate-300 font-medium transition-colors"
      >
        <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
        กลับไปหน้ารายวิชา
      </NuxtLink>
    </div>

    <template v-else>
      <!-- Success Message -->
      <div v-if="successMessage" class="p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-xl">
        <div class="flex items-center gap-2 text-green-700 dark:text-green-400">
          <Icon icon="fluent:checkmark-circle-24-filled" class="w-5 h-5" />
          <span>{{ successMessage }}</span>
        </div>
      </div>

      <!-- Error Message -->
      <div v-if="errors.general" class="p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-xl">
        <div class="flex items-center gap-2 text-red-700 dark:text-red-400">
          <Icon icon="fluent:error-circle-24-filled" class="w-5 h-5" />
          <span>{{ errors.general }}</span>
        </div>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleSubmit" class="bg-white dark:bg-slate-800 rounded-2xl p-4 sm:p-6 shadow-hopeui border border-slate-100 dark:border-slate-700 space-y-6">
        <!-- Current cover (read-only) -->
        <div v-if="coverUrl" class="flex items-center gap-3">
          <img :src="coverUrl" alt="cover" class="w-20 h-20 rounded-xl object-cover border border-slate-200 dark:border-slate-600 shrink-0" />
          <p class="min-w-0 text-xs text-slate-400">แก้ไขรูปหน้าปกได้ที่หน้าตั้งค่ารายวิชา (Course Settings)</p>
        </div>

        <!-- Title -->
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
            ชื่อรายวิชา <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.name"
            type="text"
            class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500 focus:border-transparent"
            :class="{ 'border-red-500': errors.name }"
            placeholder="เช่น เรียน Python เบื้องต้น"
          />
          <p v-if="errors.name" class="mt-1 text-sm text-red-500">{{ errors.name }}</p>
        </div>

        <!-- Description -->
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
            คำอธิบาย <span class="text-red-500">*</span>
          </label>
          <!-- rich text — course.description แสดงผ่าน RichTextViewer จึงเก็บเป็น HTML -->
          <RichTextEditor
            v-model="form.description"
            placeholder="รายละเอียดรายวิชา..."
            class="w-full"
            :class="{ 'ring-2 ring-red-500 rounded-lg': errors.description }"
            min-height="150px"
          />
          <p v-if="errors.description" class="mt-1 text-sm text-red-500">{{ errors.description }}</p>
        </div>

        <!-- Status & Price -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">สถานะ</label>
            <select
              v-model="form.status"
              class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500"
            >
              <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">ราคา (บาท)</label>
            <input
              v-model.number="form.price"
              type="number"
              min="0"
              class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500 focus:border-transparent"
              :class="{ 'border-red-500': errors.price }"
              placeholder="0 = ฟรี"
            />
            <p v-if="errors.price" class="mt-1 text-sm text-red-500">{{ errors.price }}</p>
          </div>
        </div>

        <!-- Education level & year -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">ระดับการศึกษา</label>
            <select
              v-model="form.education_level"
              class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500"
              :class="{ 'border-red-500': errors.education_level }"
            >
              <option value="">ไม่ระบุ</option>
              <option v-for="lv in educationLevels" :key="lv" :value="lv">{{ lv }}</option>
            </select>
            <p v-if="errors.education_level" class="mt-1 text-sm text-red-500">{{ errors.education_level }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">ชั้นปี (1-6)</label>
            <input
              v-model.number="form.education_year"
              type="number"
              min="1"
              max="6"
              class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500 focus:border-transparent"
              :class="{ 'border-red-500': errors.education_year }"
              placeholder="ไม่ระบุ"
            />
            <p v-if="errors.education_year" class="mt-1 text-sm text-red-500">{{ errors.education_year }}</p>
          </div>
        </div>

        <!-- Duration & Capacity -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">ระยะเวลา (ชั่วโมง)</label>
            <input
              v-model.number="form.duration"
              type="number"
              min="0"
              class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500 focus:border-transparent"
              :class="{ 'border-red-500': errors.duration }"
              placeholder="ไม่ระบุ"
            />
            <p v-if="errors.duration" class="mt-1 text-sm text-red-500">{{ errors.duration }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">จำนวนที่รับ</label>
            <input
              v-model.number="form.capacity"
              type="number"
              min="0"
              class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500 focus:border-transparent"
              :class="{ 'border-red-500': errors.capacity }"
              placeholder="ไม่ระบุ"
            />
            <p v-if="errors.capacity" class="mt-1 text-sm text-red-500">{{ errors.capacity }}</p>
          </div>
        </div>

        <!-- Language -->
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">ภาษา</label>
          <input
            v-model="form.language"
            type="text"
            class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500 focus:border-transparent"
            placeholder="เช่น ไทย"
          />
        </div>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row gap-3 pt-4">
          <button
            type="submit"
            :disabled="isSubmitting"
            class="flex-1 min-h-[44px] inline-flex justify-center items-center gap-2 px-6 py-3 bg-hopeui-primary-500 hover:bg-hopeui-primary-600 disabled:bg-hopeui-primary-300 rounded-xl text-white font-medium transition-colors"
          >
            <Icon v-if="isSubmitting" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
            <Icon v-else icon="fluent:save-24-regular" class="w-5 h-5" />
            {{ isSubmitting ? 'กำลังบันทึก...' : 'บันทึกการแก้ไข' }}
          </button>

          <NuxtLink
            to="/nuxnan-admin/courses"
            class="flex-1 min-h-[44px] inline-flex justify-center items-center gap-2 px-4 sm:px-6 py-3 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-xl text-slate-700 dark:text-slate-300 font-medium transition-colors"
          >
            <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5" />
            ยกเลิก
          </NuxtLink>
        </div>
      </form>
    </template>
  </div>
</template>
