<script setup lang="ts">
import { ref, reactive } from 'vue'
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'nuxnan-admin-layout',
  middleware: 'nuxnan-admin'
})

const config = useRuntimeConfig()
const apiBase = config.public.apiBase as string
const router = useRouter()

// Form state
const isSubmitting = ref(false)
const errors = ref<Record<string, string>>({})
const successMessage = ref('')

const form = reactive({
  title: '',
  content: '',
  type: 'article',
  status: 'draft',
  is_featured: false
})

// Content types
const contentTypes = [
  { value: 'article', label: 'บทความ' },
  { value: 'announcement', label: 'ประกาศ' },
  { value: 'faq', label: 'คำถามที่พบบ่อย' },
  { value: 'page', label: 'หน้าเพจ' }
]

// Statuses
const statuses = [
  { value: 'draft', label: 'แบบร่าง' },
  { value: 'published', label: 'เผยแพร่' },
  { value: 'archived', label: 'เก็บถาวร' }
]

// Validate form
const validateForm = () => {
  errors.value = {}
  
  if (!form.title) {
    errors.value.title = 'กรุณากรอกหัวข้อ'
  }
  
  if (!form.content) {
    errors.value.content = 'กรุณากรอกเนื้อหา'
  }
  
  return Object.keys(errors.value).length === 0
}

// Submit form
const handleSubmit = async () => {
  if (!validateForm()) return
  
  isSubmitting.value = true
  successMessage.value = ''
  
  try {
    const token = useCookie('token')
    const response = await $fetch(`${apiBase}/api/admin/content`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token.value}`,
        'Content-Type': 'application/json'
      },
      body: form
    })
    
    if (response.success) {
      successMessage.value = 'สร้างเนื้อหาสำเร็จ'
      setTimeout(() => {
        router.push('/nuxnan-admin/content')
      }, 1500)
    }
  } catch (error: any) {
    if (error.data?.errors) {
      errors.value = error.data.errors
    } else {
      errors.value.general = error.data?.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง'
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="space-y-6 max-w-3xl mx-auto">
    <!-- Page Header -->
    <div class="flex items-center gap-4">
      <NuxtLink
        to="/nuxnan-admin/content"
        class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors"
      >
        <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
      </NuxtLink>
      <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">สร้างเนื้อหาใหม่</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">สร้างบทความ ประกาศ หรือเนื้อหาอื่นๆ</p>
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
    <form @submit.prevent="handleSubmit" class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 space-y-6">
      <!-- Title -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          หัวข้อ <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.title"
          type="text"
          class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
          :class="{ 'border-red-500': errors.title }"
          placeholder="หัวข้อเนื้อหา"
        />
        <p v-if="errors.title" class="mt-1 text-sm text-red-500">{{ errors.title }}</p>
      </div>

      <!-- Type & Status -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            ประเภท
          </label>
          <select
            v-model="form.type"
            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
          >
            <option v-for="type in contentTypes" :key="type.value" :value="type.value">
              {{ type.label }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            สถานะ
          </label>
          <select
            v-model="form.status"
            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
          >
            <option v-for="status in statuses" :key="status.value" :value="status.value">
              {{ status.label }}
            </option>
          </select>
        </div>
      </div>

      <!-- Content -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          เนื้อหา <span class="text-red-500">*</span>
        </label>
        <textarea
          v-model="form.content"
          rows="10"
          class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none"
          :class="{ 'border-red-500': errors.content }"
          placeholder="เนื้อหา..."
        ></textarea>
        <p v-if="errors.content" class="mt-1 text-sm text-red-500">{{ errors.content }}</p>
      </div>

      <!-- Featured -->
      <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
        <label class="flex items-center gap-3 cursor-pointer">
          <input
            v-model="form.is_featured"
            type="checkbox"
            class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
          />
          <span class="text-gray-700 dark:text-gray-300">แสดงในหน้าแรก (Featured)</span>
        </label>
      </div>

      <!-- Actions -->
      <div class="flex flex-col sm:flex-row gap-3 pt-4">
        <button
          type="submit"
          :disabled="isSubmitting"
          class="flex-1 inline-flex justify-center items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 rounded-xl text-white font-medium transition-colors"
        >
          <Icon v-if="isSubmitting" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
          <Icon v-else icon="fluent:save-24-regular" class="w-5 h-5" />
          {{ isSubmitting ? 'กำลังบันทึก...' : 'สร้างเนื้อหา' }}
        </button>
        
        <NuxtLink
          to="/nuxnan-admin/content"
          class="flex-1 inline-flex justify-center items-center gap-2 px-6 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl text-gray-700 dark:text-gray-300 font-medium transition-colors"
        >
          <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5" />
          ยกเลิก
        </NuxtLink>
      </div>
    </form>
  </div>
</template>
