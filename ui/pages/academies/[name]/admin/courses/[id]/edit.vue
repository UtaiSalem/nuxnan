<script setup lang="ts">
/**
 * Academy Admin - Edit Course (menu #12 CO-S4)
 * หน้าแก้ไขรายวิชา — academy admin แก้คอร์สของครูคนอื่นในโรงเรียนตัวเองได้ (authz ที่ backend update())
 * โหลดจาก GET /api/courses/{id}/basic-info · บันทึกด้วย PATCH /api/courses/{id} (POST + _method spoof รองรับไฟล์ปก)
 */
import { Icon } from '@iconify/vue'
import RichTextEditor from '~/components/Common/RichTextEditor.vue'

definePageMeta({
  layout: 'main',
  middleware: 'auth',
  ssr: false,
})

const route = useRoute()
const router = useRouter()
const api = useApi()
const academyName = computed(() => route.params.name as string)
const courseId = computed(() => route.params.id as string)

const isLoading = ref(true)
const isSubmitting = ref(false)
const loadError = ref('')
const errors = ref<Record<string, string>>({})
const successMessage = ref('')

// ชื่อฟิลด์ตรงกับ backend update(): name / description / level / status (string) / price / duration / cover (ไฟล์)
const form = reactive({
  name: '',
  description: '',
  price: 0,
  duration: '',
  level: 'beginner',
  status: 'draft',
  cover: null as File | null,
})
const existingCoverUrl = ref<string | null>(null)
// update() ตั้ง saleable จาก request แบบไม่มีเงื่อนไข — ต้องส่งค่าเดิมกลับไปด้วย ไม่งั้นถูกล้างเป็น null ทุกครั้งที่บันทึก
const existingSaleable = ref<number>(0)

const levels = [
  { value: 'beginner', label: 'เริ่มต้น' },
  { value: 'intermediate', label: 'ปานกลาง' },
  { value: 'advanced', label: 'ขั้นสูง' },
]

// ตรงกับ Course::STATUS_MAP (published/draft/archived)
const statuses = [
  { value: 'draft', label: 'ฉบับร่าง' },
  { value: 'published', label: 'เผยแพร่' },
  { value: 'archived', label: 'เก็บถาวร' },
]

// courses.status tinyint 1=published, 2=draft, 3=archived (0 = legacy draft)
const statusFromCode = (s: string | number | null | undefined) => {
  const n = Number(s)
  if (n === 1) return 'published'
  if (n === 3) return 'archived'
  return 'draft'
}

const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    form.cover = target.files[0]
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
  errors.value.general = ''

  try {
    const formData = new FormData()
    formData.append('_method', 'PATCH') // method spoof: POST multipart -> PATCH route (PHP ไม่ parse multipart บน PATCH ตรง ๆ)
    formData.append('name', form.name)
    formData.append('description', form.description ?? '')
    formData.append('level', form.level ?? '')
    formData.append('status', form.status)
    formData.append('price', String(form.price ?? 0))
    formData.append('duration', form.duration ?? '')
    formData.append('saleable', String(existingSaleable.value)) // preserve ค่าเดิม (ดูหมายเหตุที่ประกาศ)
    if (form.cover instanceof File) formData.append('cover', form.cover)

    const response: any = await api.post(`/api/courses/${courseId.value}`, formData)

    if (response.success) {
      successMessage.value = 'บันทึกการแก้ไขแล้ว'
      setTimeout(() => {
        router.push(`/academies/${academyName.value}/admin/courses`)
      }, 1200)
    }
  } catch (error: any) {
    if (error?.data?.errors) {
      errors.value = error.data.errors
    } else {
      errors.value.general = error?.data?.message || 'บันทึกไม่สำเร็จ โปรดลองใหม่'
    }
  } finally {
    isSubmitting.value = false
  }
}

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/courses/${courseId.value}/basic-info`)
    const course = response?.course || response?.data?.course
    if (!course) {
      loadError.value = 'ไม่พบรายวิชานี้'
      return
    }
    form.name = course.name || course.title || ''
    form.description = course.description || ''
    form.price = Number(course.price) || 0
    form.duration = course.duration || ''
    form.level = course.level || 'beginner'
    form.status = statusFromCode(course.status)
    existingCoverUrl.value = course.cover || course.cover_url || null
    existingSaleable.value = course.saleable ? 1 : 0
  } catch (err: any) {
    loadError.value = err?.data?.message || 'โหลดรายวิชาไม่สำเร็จ'
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-0">
    <!-- Header -->
    <div class="flex items-center gap-4">
      <NuxtLink
        :to="`/academies/${academyName}/admin/courses`"
        class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-xl text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
      >
        <Icon icon="fluent:arrow-left-24-regular" class="h-5 w-5" />
      </NuxtLink>
      <div class="min-w-0">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">แก้ไขรายวิชา</h1>
        <p class="mt-1 text-gray-500 dark:text-gray-400">แก้ไขข้อมูลหลักของรายวิชาในคลังโรงเรียน</p>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="rounded-2xl border border-gray-100 bg-white p-8 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
      <Icon icon="fluent:spinner-ios-20-regular" class="mx-auto h-8 w-8 animate-spin text-primary-600" />
      <p class="mt-2 text-gray-500">กำลังโหลดข้อมูลรายวิชา...</p>
    </div>

    <!-- Load error -->
    <div v-else-if="loadError" class="rounded-2xl border border-red-200 bg-red-50 p-6 text-center dark:border-red-700 dark:bg-red-900/30">
      <Icon icon="fluent:error-circle-24-filled" class="mx-auto h-10 w-10 text-red-500" />
      <p class="mt-2 text-red-700 dark:text-red-400">{{ loadError }}</p>
      <NuxtLink
        :to="`/academies/${academyName}/admin/courses`"
        class="mt-4 inline-flex min-h-[44px] items-center gap-2 rounded-xl bg-gray-100 px-4 py-2 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300"
      >
        กลับไปคลังรายวิชา
      </NuxtLink>
    </div>

    <template v-else>
      <!-- Success -->
      <div v-if="successMessage" class="rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-700 dark:bg-green-900/30">
        <div class="flex items-center gap-2 text-green-700 dark:text-green-400">
          <Icon icon="fluent:checkmark-circle-24-filled" class="h-5 w-5" />
          <span>{{ successMessage }}</span>
        </div>
      </div>

      <!-- Error -->
      <div v-if="errors.general" class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-700 dark:bg-red-900/30">
        <div class="flex items-center gap-2 text-red-700 dark:text-red-400">
          <Icon icon="fluent:error-circle-24-filled" class="h-5 w-5" />
          <span>{{ errors.general }}</span>
        </div>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleSubmit" class="space-y-6 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
        <!-- Name -->
        <div>
          <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
            ชื่อรายวิชา <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.name"
            type="text"
            class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-gray-800 placeholder-gray-400 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            :class="{ 'border-red-500': errors.name }"
            placeholder="เช่น วิทยาศาสตร์ ม.1"
          />
          <p v-if="errors.name" class="mt-1 text-sm text-red-500">{{ errors.name }}</p>
        </div>

        <!-- Description -->
        <div>
          <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
            คำอธิบาย <span class="text-red-500">*</span>
          </label>
          <RichTextEditor
            v-model="form.description"
            placeholder="รายละเอียดรายวิชา..."
            class="w-full"
            :class="{ 'rounded-lg ring-2 ring-red-500': errors.description }"
            min-height="150px"
          />
          <p v-if="errors.description" class="mt-1 text-sm text-red-500">{{ errors.description }}</p>
        </div>

        <!-- Level & Status -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">ระดับ</label>
            <select
              v-model="form.level"
              class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-gray-800 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            >
              <option v-for="level in levels" :key="level.value" :value="level.value">{{ level.label }}</option>
            </select>
          </div>
          <div>
            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">สถานะ</label>
            <select
              v-model="form.status"
              class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-gray-800 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            >
              <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
            </select>
          </div>
        </div>

        <!-- Price & Duration -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">ราคา (บาท)</label>
            <input
              v-model.number="form.price"
              type="number"
              min="0"
              class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-gray-800 placeholder-gray-400 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              placeholder="0 = ฟรี"
            />
          </div>
          <div>
            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">ระยะเวลา</label>
            <input
              v-model="form.duration"
              type="text"
              class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-gray-800 placeholder-gray-400 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              placeholder="เช่น 1 ภาคเรียน"
            />
          </div>
        </div>

        <!-- Cover -->
        <div>
          <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">รูปภาพหน้าปก</label>
          <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
            <div v-if="existingCoverUrl && !form.cover" class="h-24 w-full flex-shrink-0 overflow-hidden rounded-xl sm:w-40">
              <img :src="existingCoverUrl" alt="ปกปัจจุบัน" class="h-full w-full object-cover" />
            </div>
            <label class="flex flex-1 cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 p-6 transition-colors hover:border-primary-500 dark:border-gray-600 dark:hover:border-primary-400">
              <Icon icon="fluent:image-add-24-regular" class="h-8 w-8 text-gray-400" />
              <span class="mt-2 text-sm text-gray-500">{{ existingCoverUrl ? 'คลิกเพื่อเปลี่ยนปก' : 'คลิกเพื่ออัพโหลด' }}</span>
              <input type="file" accept="image/*" class="hidden" @change="handleFileChange" />
            </label>
            <div v-if="form.cover" class="min-w-0 break-words text-sm text-gray-600 dark:text-gray-400">{{ form.cover.name }}</div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-col gap-3 pt-4 sm:flex-row">
          <button
            type="submit"
            :disabled="isSubmitting"
            class="inline-flex min-h-[44px] flex-1 items-center justify-center gap-2 rounded-xl bg-primary-600 px-6 py-3 font-medium text-white transition-colors hover:bg-primary-700 disabled:bg-primary-400"
          >
            <Icon v-if="isSubmitting" icon="fluent:spinner-ios-20-regular" class="h-5 w-5 animate-spin" />
            <Icon v-else icon="fluent:save-24-regular" class="h-5 w-5" />
            {{ isSubmitting ? 'กำลังบันทึก...' : 'บันทึกการแก้ไข' }}
          </button>
          <NuxtLink
            :to="`/academies/${academyName}/admin/courses`"
            class="inline-flex min-h-[44px] flex-1 items-center justify-center gap-2 rounded-xl bg-gray-100 px-4 py-3 font-medium text-gray-700 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 sm:px-6"
          >
            <Icon icon="fluent:dismiss-24-regular" class="h-5 w-5" />
            ยกเลิก
          </NuxtLink>
        </div>
      </form>
    </template>
  </div>
</template>
