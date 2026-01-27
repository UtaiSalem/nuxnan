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
  name: '',
  description: '',
  logo: null as File | null,
  is_active: true
})

// Handle file upload
const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    form.logo = target.files[0]
  }
}

// Validate form
const validateForm = () => {
  errors.value = {}
  
  if (!form.name) {
    errors.value.name = 'กรุณากรอกชื่อสถาบัน'
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
    
    const formData = new FormData()
    formData.append('name', form.name)
    formData.append('description', form.description)
    formData.append('is_active', form.is_active ? '1' : '0')
    if (form.logo) {
      formData.append('logo', form.logo)
    }
    
    const response = await $fetch(`${apiBase}/api/admin/academies`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token.value}`
      },
      body: formData
    })
    
    if (response.success) {
      successMessage.value = 'สร้างสถาบันสำเร็จ'
      setTimeout(() => {
        router.push('/nuxnan-admin/academies')
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
  <div class="space-y-6 max-w-2xl mx-auto">
    <!-- Page Header -->
    <div class="flex items-center gap-4">
      <NuxtLink
        to="/nuxnan-admin/academies"
        class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors"
      >
        <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
      </NuxtLink>
      <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">สร้างสถาบันใหม่</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">กรอกข้อมูลเพื่อสร้างสถาบัน</p>
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
      <!-- Name -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          ชื่อสถาบัน <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.name"
          type="text"
          class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
          :class="{ 'border-red-500': errors.name }"
          placeholder="เช่น สถาบัน ABC"
        />
        <p v-if="errors.name" class="mt-1 text-sm text-red-500">{{ errors.name }}</p>
      </div>

      <!-- Description -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          คำอธิบาย
        </label>
        <textarea
          v-model="form.description"
          rows="4"
          class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none"
          placeholder="รายละเอียดเกี่ยวกับสถาบัน..."
        ></textarea>
      </div>

      <!-- Logo -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          โลโก้
        </label>
        <div class="flex items-center gap-4">
          <label class="flex-1 flex flex-col items-center justify-center p-6 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl cursor-pointer hover:border-indigo-500 dark:hover:border-indigo-400 transition-colors">
            <Icon icon="fluent:image-add-24-regular" class="w-8 h-8 text-gray-400" />
            <span class="mt-2 text-sm text-gray-500">คลิกเพื่ออัพโหลด</span>
            <input type="file" accept="image/*" class="hidden" @change="handleFileChange" />
          </label>
          <div v-if="form.logo" class="text-sm text-gray-600 dark:text-gray-400">
            {{ form.logo.name }}
          </div>
        </div>
      </div>

      <!-- Active Status -->
      <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
        <label class="flex items-center gap-3 cursor-pointer">
          <input
            v-model="form.is_active"
            type="checkbox"
            class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
          />
          <span class="text-gray-700 dark:text-gray-300">เปิดใช้งานทันที</span>
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
          {{ isSubmitting ? 'กำลังบันทึก...' : 'สร้างสถาบัน' }}
        </button>
        
        <NuxtLink
          to="/nuxnan-admin/academies"
          class="flex-1 inline-flex justify-center items-center gap-2 px-6 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl text-gray-700 dark:text-gray-300 font-medium transition-colors"
        >
          <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5" />
          ยกเลิก
        </NuxtLink>
      </div>
    </form>
  </div>
</template>
