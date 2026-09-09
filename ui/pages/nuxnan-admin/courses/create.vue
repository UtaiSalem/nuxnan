<script setup lang="ts">
import { reactive, ref } from 'vue'
import { Icon } from '@iconify/vue'
import RichTextEditor from '~/components/Common/RichTextEditor.vue'

definePageMeta({
  layout: 'nuxnan-admin-layout',
  middleware: 'nuxnan-admin'
})

const config = useRuntimeConfig()
const apiBase = config.public.apiBase as string
const router = useRouter()

const isSubmitting = ref(false)
const errors = ref<Record<string, string>>({})
const successMessage = ref('')

// เฉพาะฟิลด์ที่ StoreCourseRequest รับจริง — ไม่งั้นถูก drop เงียบ ๆ
// (รูปหน้าปก/หมวดหมู่ตั้งได้ที่หน้า Course Settings หลังสร้าง)
const form = reactive({
  name: '',
  description: '',
  price: 0 as number,
  status: 'draft' as string,
  education_level: '' as string,
  education_year: null as number | null
})

// status ส่งเป็น string — backend (Course::setStatusAttribute) แปลงเป็น tinyint เอง
const statuses = [
  { value: 'published', label: 'เผยแพร่' },
  { value: 'draft', label: 'ฉบับร่าง' },
  { value: 'archived', label: 'เก็บถาวร' }
]

// ตรงกับ Rule::in ของ StoreCourseRequest.education_level
const educationLevels = ['ประถมศึกษา', 'มัธยมศึกษา', 'ปวช.', 'ปวส.', 'อุดมศึกษา', 'อื่นๆ']

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

    const payload: Record<string, any> = {
      name: form.name,
      description: form.description,
      price: Number(form.price) || 0,
      status: form.status
    }
    if (form.education_level) payload.education_level = form.education_level
    if (form.education_year != null && (form.education_year as any) !== '') {
      payload.education_year = Number(form.education_year)
    }

    const response = await $fetch<any>(`${apiBase}/api/admin/courses`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token.value}`,
        Accept: 'application/json'
      },
      body: payload
    })

    if (response?.success) {
      successMessage.value = 'สร้างรายวิชาสำเร็จ'
      setTimeout(() => {
        router.push('/nuxnan-admin/courses')
      }, 1200)
    }
  } catch (error: any) {
    if (error?.data?.errors) {
      const raw = error.data.errors as Record<string, string[]>
      errors.value = Object.fromEntries(Object.entries(raw).map(([k, v]) => [k, v?.[0] ?? '']))
    } else {
      errors.value.general = error?.data?.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง'
    }
  } finally {
    isSubmitting.value = false
  }
}
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
        <h1 class="text-xl sm:text-2xl font-bold text-slate-800 dark:text-white break-words">สร้างรายวิชาใหม่</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">กรอกข้อมูลเพื่อสร้างรายวิชา</p>
      </div>
    </div>

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

      <!-- Note about cover/category -->
      <p class="text-xs text-slate-400">
        รูปหน้าปก หมวดหมู่ และรายละเอียดอื่น ๆ ปรับได้ที่หน้าตั้งค่ารายวิชา (Course Settings) หลังสร้างเสร็จ
      </p>

      <!-- Actions -->
      <div class="flex flex-col sm:flex-row gap-3 pt-4">
        <button
          type="submit"
          :disabled="isSubmitting"
          class="flex-1 min-h-[44px] inline-flex justify-center items-center gap-2 px-6 py-3 bg-hopeui-primary-500 hover:bg-hopeui-primary-600 disabled:bg-hopeui-primary-300 rounded-xl text-white font-medium transition-colors"
        >
          <Icon v-if="isSubmitting" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
          <Icon v-else icon="fluent:save-24-regular" class="w-5 h-5" />
          {{ isSubmitting ? 'กำลังบันทึก...' : 'สร้างรายวิชา' }}
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
  </div>
</template>
