<script setup lang="ts">
import { ref, computed } from 'vue'

interface Props {
  modelValue: boolean
  academyId: number
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'imported'): void
}>()

const { $axios } = useNuxtApp() as { $axios: any }

const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

// State
const file = ref<File | null>(null)
const defaultRole = ref<string>('student')
const autoApprove = ref(false)
const loading = ref(false)
const result = ref<{
  success: boolean
  message: string
  imported_count: number
  skipped_count: number
  errors: string[]
} | null>(null)

const roleOptions = [
  { label: 'นักเรียน', value: 'student' },
  { label: 'ผู้ปกครอง', value: 'parent' },
  { label: 'ครู/อาจารย์', value: 'teacher' },
  { label: 'เจ้าหน้าที่', value: 'staff' },
  { label: 'ผู้ดูแล', value: 'admin' },
]

const fileInputRef = ref<HTMLInputElement | null>(null)

function handleFileChange(event: Event) {
  const target = event.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    file.value = target.files[0]
  }
}

function selectFile() {
  fileInputRef.value?.click()
}

function clearFile() {
  file.value = null
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

async function importMembers() {
  if (!file.value) return

  loading.value = true
  result.value = null

  try {
    const formData = new FormData()
    formData.append('file', file.value)
    formData.append('default_role', defaultRole.value)
    formData.append('auto_approve', autoApprove.value ? '1' : '0')

    const response = await $axios.post(
      `/academies/${props.academyId}/members/import`,
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      }
    )

    result.value = response.data

    if (response.data.success) {
      emit('imported')
    }
  } catch (error: any) {
    result.value = {
      success: false,
      message: error.response?.data?.message || 'เกิดข้อผิดพลาดในการนำเข้า',
      imported_count: 0,
      skipped_count: 0,
      errors: [error.message],
    }
  } finally {
    loading.value = false
  }
}

function closeModal() {
  isOpen.value = false
  // Reset state
  file.value = null
  result.value = null
  autoApprove.value = false
  defaultRole.value = 'student'
}

function downloadTemplate() {
  const csvContent = 'email,reference_code,role,member_code\nexample@email.com,ABC123XYZ1,student,STD001\n'
  const blob = new Blob([csvContent], { type: 'text/csv' })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = 'import_template.csv'
  a.click()
  window.URL.revokeObjectURL(url)
}
</script>

<template>
  <UModal v-model="isOpen" :ui="{ width: 'max-w-lg' }">
    <div class="p-6">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          นำเข้าสมาชิก
        </h3>
        <UButton
          icon="i-heroicons-x-mark"
          color="gray"
          variant="ghost"
          size="xs"
          @click="closeModal"
        />
      </div>

      <!-- Result Display -->
      <template v-if="result">
        <div
          :class="[
            'p-4 rounded-lg mb-4',
            result.success
              ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800'
              : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800'
          ]"
        >
          <p
            :class="[
              'font-medium',
              result.success ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'
            ]"
          >
            {{ result.message }}
          </p>
          <div v-if="result.imported_count > 0 || result.skipped_count > 0" class="mt-2 text-sm">
            <p class="text-gray-600 dark:text-gray-300">
              นำเข้าสำเร็จ: {{ result.imported_count }} คน
            </p>
            <p class="text-gray-600 dark:text-gray-300">
              ข้าม: {{ result.skipped_count }} คน
            </p>
          </div>
          <div v-if="result.errors.length > 0" class="mt-2">
            <p class="text-sm font-medium text-red-600 dark:text-red-400 mb-1">รายละเอียด:</p>
            <ul class="text-xs text-red-600 dark:text-red-400 space-y-0.5 max-h-32 overflow-y-auto">
              <li v-for="(error, index) in result.errors.slice(0, 10)" :key="index">
                • {{ error }}
              </li>
              <li v-if="result.errors.length > 10" class="text-gray-500">
                ... และอีก {{ result.errors.length - 10 }} รายการ
              </li>
            </ul>
          </div>
        </div>

        <div class="flex justify-end">
          <UButton @click="closeModal">
            ปิด
          </UButton>
        </div>
      </template>

      <!-- Import Form -->
      <template v-else>
        <div class="space-y-4">
          <!-- File Upload -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              ไฟล์ CSV
            </label>
            <input
              ref="fileInputRef"
              type="file"
              accept=".csv,.txt"
              class="hidden"
              @change="handleFileChange"
            />
            <div
              class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center cursor-pointer hover:border-primary-500 transition-colors"
              @click="selectFile"
            >
              <template v-if="file">
                <div class="flex items-center justify-center gap-2">
                  <UIcon name="i-heroicons-document-text" class="w-5 h-5 text-primary-500" />
                  <span class="text-sm text-gray-700 dark:text-gray-300">{{ file.name }}</span>
                  <UButton
                    icon="i-heroicons-x-mark"
                    color="gray"
                    variant="ghost"
                    size="xs"
                    @click.stop="clearFile"
                  />
                </div>
              </template>
              <template v-else>
                <UIcon name="i-heroicons-cloud-arrow-up" class="w-8 h-8 mx-auto mb-2 text-gray-400" />
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  คลิกเพื่อเลือกไฟล์ CSV
                </p>
                <p class="text-xs text-gray-400 mt-1">
                  ต้องมีคอลัมน์ email หรือ reference_code
                </p>
              </template>
            </div>
          </div>

          <!-- Download Template -->
          <button
            type="button"
            class="text-sm text-primary-600 hover:text-primary-700 underline"
            @click="downloadTemplate"
          >
            ดาวน์โหลดไฟล์ตัวอย่าง
          </button>

          <!-- Default Role -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              บทบาทเริ่มต้น
            </label>
            <USelect
              v-model="defaultRole"
              :options="roleOptions"
              option-attribute="label"
              value-attribute="value"
            />
          </div>

          <!-- Auto Approve -->
          <div class="flex items-center gap-3">
            <UToggle v-model="autoApprove" />
            <div>
              <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                อนุมัติสมาชิกอัตโนมัติ
              </p>
              <p class="text-xs text-gray-500 dark:text-gray-400">
                หากเปิด สมาชิกจะถูกเพิ่มทันที หากปิด จะส่งคำเชิญแทน
              </p>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
          <UButton color="gray" variant="ghost" @click="closeModal">
            ยกเลิก
          </UButton>
          <UButton
            :loading="loading"
            :disabled="!file"
            @click="importMembers"
          >
            นำเข้า
          </UButton>
        </div>
      </template>
    </div>
  </UModal>
</template>
