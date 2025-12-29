<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  courseId: string | number
  group?: any
}

const props = withDefaults(defineProps<Props>(), {
  group: null
})

const emit = defineEmits<{
  'saved': []
  'cancel': []
}>()

const api = useApi()
const courseGroupStore = useCourseGroupStore()

// Form data
const formData = ref({
  name: '',
  description: '',
  color: '#3B82F6', // Default blue
  max_members: null
})

// State
const isSaving = ref(false)
const errors = ref<any>({})

// Color options
const colorOptions = [
  { name: 'น้ำเงิน', value: '#3B82F6' },
  { name: 'ม่วง', value: '#8B5CF6' },
  { name: 'ชมพู', value: '#EC4899' },
  { name: 'แดง', value: '#EF4444' },
  { name: 'ส้ม', value: '#F97316' },
  { name: 'เหลือง', value: '#EAB308' },
  { name: 'เขียว', value: '#10B981' },
  { name: 'ฟ้า', value: '#06B6D4' },
  { name: 'เทา', value: '#6B7280' },
]

// Initialize form if editing
onMounted(() => {
  if (props.group) {
    formData.value = {
      name: props.group.name || '',
      description: props.group.description || '',
      color: props.group.color || '#3B82F6',
      max_members: props.group.max_members || null
    }
  }
})

// Validate form
const validateForm = () => {
  errors.value = {}
  
  if (!formData.value.name?.trim()) {
    errors.value.name = 'กรุณากรอกชื่อกลุ่ม'
  }
  
  if (formData.value.max_members && formData.value.max_members < 1) {
    errors.value.max_members = 'จำนวนสมาชิกต้องมากกว่า 0'
  }
  
  return Object.keys(errors.value).length === 0
}

// Save group
const saveGroup = async () => {
  if (!validateForm()) return
  
  isSaving.value = true
  try {
    if (props.group) {
      // Update existing group
      await courseGroupStore.updateGroupData(props.courseId, props.group.id, formData.value)
    } else {
      // Create new group
      await courseGroupStore.createGroup(props.courseId, formData.value)
    }
    
    emit('saved')
  } catch (error: any) {
    console.error('Failed to save group:', error)
    errors.value.general = error.data?.message || error.data?.msg || 'เกิดข้อผิดพลาดในการบันทึก'
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Error Message -->
    <div v-if="errors.general" class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
      <p class="text-sm text-red-600 dark:text-red-400 flex items-center gap-2">
        <Icon icon="heroicons:exclamation-circle" class="w-5 h-5" />
        {{ errors.general }}
      </p>
    </div>

    <!-- Group Name -->
    <div class="space-y-2">
      <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
        <Icon icon="heroicons:user-group-solid" class="w-5 h-5 text-teal-600 dark:text-teal-400" />
        <span>ชื่อกลุ่ม</span>
        <span class="text-red-500">*</span>
      </label>
      <input
        v-model="formData.name"
        type="text"
        placeholder="เช่น กลุ่ม A, กลุ่ม 1, เช้า"
        class="w-full px-4 py-3 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-xl font-medium text-base focus:outline-none focus:ring-4 focus:ring-teal-500/50 border-2 transition-all"
        :class="errors.name ? 'border-red-500' : 'border-gray-200 dark:border-gray-700 hover:border-teal-400 dark:hover:border-teal-600'"
      />
      <p v-if="errors.name" class="text-xs text-red-500 flex items-center gap-1">
        <Icon icon="heroicons:exclamation-circle" class="w-4 h-4" />
        {{ errors.name }}
      </p>
    </div>

    <!-- Description -->
    <div class="space-y-2">
      <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
        <Icon icon="heroicons:document-text" class="w-5 h-5 text-gray-600 dark:text-gray-400" />
        <span>คำอธิบาย</span>
        <span class="text-gray-400 text-xs">(ไม่บังคับ)</span>
      </label>
      <textarea
        v-model="formData.description"
        rows="3"
        placeholder="รายละเอียดเพิ่มเติมเกี่ยวกับกลุ่ม"
        class="w-full px-4 py-3 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-xl font-medium text-base focus:outline-none focus:ring-4 focus:ring-teal-500/50 border-2 border-gray-200 dark:border-gray-700 hover:border-teal-400 dark:hover:border-teal-600 transition-all resize-none"
      ></textarea>
    </div>

    <!-- Color Selection -->
    <div class="space-y-2">
      <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
        <Icon icon="heroicons:swatch" class="w-5 h-5 text-purple-600 dark:text-purple-400" />
        <span>สีของกลุ่ม</span>
      </label>
      <div class="grid grid-cols-9 gap-2">
        <button
          v-for="color in colorOptions"
          :key="color.value"
          type="button"
          @click="formData.color = color.value"
          :title="color.name"
          class="w-10 h-10 rounded-lg transition-all hover:scale-110"
          :class="formData.color === color.value ? 'ring-4 ring-offset-2 ring-gray-400 dark:ring-gray-600 scale-110' : ''"
          :style="{ backgroundColor: color.value }"
        ></button>
      </div>
    </div>

    <!-- Max Members -->
    <div class="space-y-2">
      <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
        <Icon icon="heroicons:users" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
        <span>จำนวนสมาชิกสูงสุด</span>
        <span class="text-gray-400 text-xs">(ไม่บังคับ - ไม่จำกัด)</span>
      </label>
      <input
        v-model.number="formData.max_members"
        type="number"
        min="1"
        placeholder="ไม่จำกัดจำนวน"
        class="w-full px-4 py-3 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-xl font-medium text-base focus:outline-none focus:ring-4 focus:ring-blue-500/50 border-2 transition-all"
        :class="errors.max_members ? 'border-red-500' : 'border-gray-200 dark:border-gray-700 hover:border-blue-400 dark:hover:border-blue-600'"
      />
      <p v-if="errors.max_members" class="text-xs text-red-500 flex items-center gap-1">
        <Icon icon="heroicons:exclamation-circle" class="w-4 h-4" />
        {{ errors.max_members }}
      </p>
    </div>

    <!-- Preview -->
    <div class="p-4 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 rounded-xl border-2 border-gray-200 dark:border-gray-700">
      <p class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2 flex items-center gap-1">
        <Icon icon="heroicons:eye" class="w-4 h-4" />
        ตัวอย่างการแสดงผล:
      </p>
      <div class="flex items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700">
        <div 
          class="w-12 h-12 rounded-lg flex items-center justify-center"
          :style="{ backgroundColor: formData.color }"
        >
          <Icon icon="heroicons:user-group" class="w-6 h-6 text-white" />
        </div>
        <div class="flex-1">
          <h4 class="font-bold text-gray-900 dark:text-white">{{ formData.name || 'ชื่อกลุ่ม' }}</h4>
          <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ formData.max_members ? `สูงสุด ${formData.max_members} คน` : 'ไม่จำกัดจำนวน' }}
          </p>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
      <button
        @click="emit('cancel')"
        :disabled="isSaving"
        class="px-6 py-2.5 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-all duration-300 hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        ยกเลิก
      </button>
      <button
        @click="saveGroup"
        :disabled="isSaving"
        class="flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-teal-500 to-cyan-600 hover:from-teal-600 hover:to-cyan-700 text-white font-bold rounded-xl transition-all duration-300 hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl"
      >
        <Icon v-if="isSaving" icon="svg-spinners:ring-resize" class="w-5 h-5" />
        <Icon v-else icon="fluent:save-24-filled" class="w-5 h-5" />
        <span>{{ isSaving ? 'กำลังบันทึก...' : (group ? 'บันทึกการแก้ไข' : 'สร้างกลุ่ม') }}</span>
      </button>
    </div>
  </div>
</template>
