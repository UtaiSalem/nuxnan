<script setup lang="ts">
/**
 * Bulk Role Assignment Modal
 * กำหนดบทบาทให้หลายสมาชิกพร้อมกัน
 */
import { ref, computed, watch } from 'vue'
import { Icon } from '@iconify/vue'

interface AcademyRole {
  id: number
  name: string
  display_name_th: string
  display_name_en?: string
  color: string
  icon?: string
  is_system?: boolean
}

interface Props {
  modelValue: boolean
  academyId: number
  memberIds: number[]
  memberCount?: number
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'role-assigned': [roleId: number]
}>()

const api = useApi()
const isVisible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

// State
const roles = ref<AcademyRole[]>([])
const selectedRoleId = ref<number | null>(null)
const isLoading = ref(false)
const isSubmitting = ref(false)
const error = ref('')

// Fetch roles when modal opens
watch(() => props.modelValue, async (open) => {
  if (open) {
    await fetchRoles()
    selectedRoleId.value = null
    error.value = ''
  }
})

const fetchRoles = async () => {
  isLoading.value = true
  try {
    const response: any = await api.get(`/api/academies/${props.academyId}/roles/available`)
    if (response.success) {
      roles.value = response.roles || []
    }
  } catch (err) {
    console.error('Failed to fetch roles:', err)
    error.value = 'ไม่สามารถโหลดรายการบทบาทได้'
  } finally {
    isLoading.value = false
  }
}

const getRoleColorClass = (color: string) => {
  const colors: Record<string, string> = {
    red: 'border-red-400 bg-red-50 dark:bg-red-900/20',
    orange: 'border-orange-400 bg-orange-50 dark:bg-orange-900/20',
    amber: 'border-amber-400 bg-amber-50 dark:bg-amber-900/20',
    yellow: 'border-yellow-400 bg-yellow-50 dark:bg-yellow-900/20',
    green: 'border-green-400 bg-green-50 dark:bg-green-900/20',
    emerald: 'border-emerald-400 bg-emerald-50 dark:bg-emerald-900/20',
    teal: 'border-teal-400 bg-teal-50 dark:bg-teal-900/20',
    cyan: 'border-cyan-400 bg-cyan-50 dark:bg-cyan-900/20',
    blue: 'border-blue-400 bg-blue-50 dark:bg-blue-900/20',
    indigo: 'border-indigo-400 bg-indigo-50 dark:bg-indigo-900/20',
    violet: 'border-violet-400 bg-violet-50 dark:bg-violet-900/20',
    purple: 'border-purple-400 bg-purple-50 dark:bg-purple-900/20',
    pink: 'border-pink-400 bg-pink-50 dark:bg-pink-900/20',
    rose: 'border-rose-400 bg-rose-50 dark:bg-rose-900/20',
    gray: 'border-gray-400 bg-gray-50 dark:bg-gray-700/30',
  }
  return colors[color] || colors.gray
}

const handleSubmit = async () => {
  if (!selectedRoleId.value) {
    error.value = 'กรุณาเลือกบทบาท'
    return
  }

  isSubmitting.value = true
  error.value = ''

  try {
    const response: any = await api.post(`/api/academies/${props.academyId}/members/bulk-role`, {
      member_ids: props.memberIds,
      role_id: selectedRoleId.value
    })

    if (response.success) {
      emit('role-assigned', selectedRoleId.value)
      isVisible.value = false
    } else {
      error.value = response.message || 'เกิดข้อผิดพลาด'
    }
  } catch (err: any) {
    error.value = err.data?.message || 'ไม่สามารถกำหนดบทบาทได้'
  } finally {
    isSubmitting.value = false
  }
}

const closeModal = () => {
  if (!isSubmitting.value) {
    isVisible.value = false
  }
}
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div 
        v-if="isVisible"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @click.self="closeModal"
      >
        <Transition
          enter-active-class="transition-all duration-200"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition-all duration-200"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div 
            v-if="isVisible"
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg overflow-hidden"
          >
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
              <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                  กำหนดบทบาท
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                  เลือกบทบาทสำหรับ {{ memberIds.length }} สมาชิก
                </p>
              </div>
              <button 
                @click="closeModal"
                :disabled="isSubmitting"
                class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors disabled:opacity-50"
              >
                <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5" />
              </button>
            </div>

            <!-- Body -->
            <div class="p-6 max-h-96 overflow-y-auto">
              <!-- Loading -->
              <div v-if="isLoading" class="flex items-center justify-center py-8">
                <div class="w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
              </div>

              <!-- Error -->
              <div 
                v-else-if="error" 
                class="flex items-center gap-2 p-4 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 rounded-lg mb-4"
              >
                <Icon icon="fluent:warning-24-filled" class="w-5 h-5 shrink-0" />
                <span class="text-sm">{{ error }}</span>
              </div>

              <!-- Role Selection -->
              <div v-if="!isLoading" class="space-y-2">
                <label 
                  v-for="role in roles"
                  :key="role.id"
                  :class="[
                    'block p-4 rounded-xl border-2 cursor-pointer transition-all',
                    selectedRoleId === role.id 
                      ? 'ring-2 ring-primary-500 ' + getRoleColorClass(role.color)
                      : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                  ]"
                >
                  <div class="flex items-center gap-3">
                    <input
                      type="radio"
                      :value="role.id"
                      v-model="selectedRoleId"
                      class="sr-only"
                    />
                    <div 
                      :class="[
                        'flex items-center justify-center w-10 h-10 rounded-lg',
                        `bg-${role.color}-100 dark:bg-${role.color}-900/40`
                      ]"
                    >
                      <Icon 
                        :icon="role.icon || 'fluent:person-24-regular'" 
                        :class="[
                          'w-5 h-5',
                          `text-${role.color}-600 dark:text-${role.color}-400`
                        ]"
                      />
                    </div>
                    <div class="flex-1 min-w-0">
                      <h4 class="font-medium text-gray-900 dark:text-white">
                        {{ role.display_name_th }}
                      </h4>
                      <p v-if="role.display_name_en" class="text-xs text-gray-500 dark:text-gray-400">
                        {{ role.display_name_en }}
                      </p>
                    </div>
                    <div 
                      v-if="selectedRoleId === role.id"
                      class="flex items-center justify-center w-6 h-6 bg-primary-500 rounded-full"
                    >
                      <Icon icon="fluent:checkmark-12-filled" class="w-4 h-4 text-white" />
                    </div>
                  </div>
                </label>

                <div v-if="roles.length === 0" class="text-center py-8 text-gray-500">
                  <Icon icon="fluent:shield-person-24-regular" class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                  <p>ไม่พบบทบาทที่สามารถกำหนดได้</p>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
              <button
                @click="closeModal"
                :disabled="isSubmitting"
                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors disabled:opacity-50"
              >
                ยกเลิก
              </button>
              <button
                @click="handleSubmit"
                :disabled="isSubmitting || !selectedRoleId"
                class="flex items-center gap-2 px-5 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <div v-if="isSubmitting" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                <Icon v-else icon="fluent:checkmark-24-filled" class="w-4 h-4" />
                <span>{{ isSubmitting ? 'กำลังบันทึก...' : 'ยืนยัน' }}</span>
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>
