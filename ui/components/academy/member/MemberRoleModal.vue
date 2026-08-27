<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Icon } from '@iconify/vue'

// Types defined locally to avoid import issues
interface AcademyRole {
  id: number
  name: string
  display_name_th: string
  display_name_en?: string
  color: string
  icon?: string
  permissions: string[]
}

interface AcademyMember {
  id: number
  member_name: string
  member_avatar: string
  member_code?: string
  academy_role_id?: number | null
  academy_role?: AcademyRole | null
  user?: { email?: string } | null
}

interface Props {
  modelValue: boolean
  member: AcademyMember | null
  academyId: number | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'role-assigned': [member: AcademyMember]
}>()

const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const { $axios } = useNuxtApp() as { $axios: any }

const loading = ref(false)
const selectedRoleId = ref<number | null>(null)
const roles = ref<AcademyRole[]>([])
const loadingRoles = ref(false)

// Fetch available roles when modal opens
watch(() => props.modelValue, async (isVisible) => {
  if (isVisible && props.academyId) {
    await fetchRoles()
    selectedRoleId.value = props.member?.academy_role_id || null
  }
})

async function fetchRoles() {
  loadingRoles.value = true
  try {
    const response = await $axios.get(`/academies/${props.academyId}/roles/available`)
    if (response.data.success) {
      roles.value = response.data.roles
    }
  } catch (error) {
    console.error('Error fetching roles:', error)
  } finally {
    loadingRoles.value = false
  }
}

async function assignRole() {
  if (!props.member || !selectedRoleId.value) return

  loading.value = true
  try {
    const response = await $axios.post(
      `/academies/${props.academyId}/members/${props.member.id}/role`,
      { role_id: selectedRoleId.value }
    )

    if (response.data.success) {
      emit('role-assigned', response.data.member)
      isOpen.value = false
    }
  } catch (error) {
    console.error('Error assigning role:', error)
  } finally {
    loading.value = false
  }
}

function getRoleColor(role: AcademyRole): string {
  const colorMap: Record<string, string> = {
    purple: 'bg-purple-100 text-purple-800 border-purple-300',
    indigo: 'bg-indigo-100 text-indigo-800 border-indigo-300',
    blue: 'bg-blue-100 text-blue-800 border-blue-300',
    green: 'bg-green-100 text-green-800 border-green-300',
    cyan: 'bg-cyan-100 text-cyan-800 border-cyan-300',
    amber: 'bg-amber-100 text-amber-800 border-amber-300',
    emerald: 'bg-emerald-100 text-emerald-800 border-emerald-300',
    rose: 'bg-rose-100 text-rose-800 border-rose-300',
  }
  return colorMap[role.color] || 'bg-gray-100 text-gray-800 border-gray-300'
}

function closeModal() {
  if (!loading.value) {
    isOpen.value = false
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
        v-if="isOpen"
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
            v-if="isOpen"
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md overflow-hidden"
          >
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                กำหนดบทบาท
              </h3>
              <button 
                @click="closeModal"
                :disabled="loading"
                class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors disabled:opacity-50"
              >
                <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5" />
              </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-4">
              <!-- Member Info -->
              <div v-if="member" class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <img
                  :src="member.member_avatar"
                  :alt="member.member_name"
                  class="w-12 h-12 rounded-full object-cover"
                />
                <div>
                  <p class="font-medium text-gray-900 dark:text-white">
                    {{ member.member_name }}
                  </p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ member.member_code || member.user?.email || '-' }}
                  </p>
                </div>
              </div>

              <!-- Current Role -->
              <div v-if="member?.academy_role" class="text-sm">
                <span class="text-gray-500 dark:text-gray-400">บทบาทปัจจุบัน:</span>
                <span 
                  class="ml-2 px-2 py-1 rounded text-xs font-medium inline-flex items-center"
                  :class="getRoleColor(member.academy_role)"
                >
                  <Icon v-if="member.academy_role.icon" :icon="member.academy_role.icon" class="w-3 h-3 mr-1" />
                  {{ member.academy_role.display_name_th }}
                </span>
              </div>

              <!-- Role Selection -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  เลือกบทบาทใหม่
                </label>
                
                <div v-if="loadingRoles" class="flex justify-center py-4">
                  <div class="w-6 h-6 border-4 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
                </div>
                
                <div v-else class="space-y-2 max-h-64 overflow-y-auto">
                  <label
                    v-for="role in roles"
                    :key="role.id"
                    class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-colors"
                    :class="[
                      selectedRoleId === role.id 
                        ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' 
                        : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800'
                    ]"
                  >
                    <input
                      v-model="selectedRoleId"
                      type="radio"
                      :value="role.id"
                      class="sr-only"
                    />
                    <div 
                      class="w-8 h-8 rounded-full flex items-center justify-center"
                      :class="getRoleColor(role)"
                    >
                      <Icon v-if="role.icon" :icon="role.icon" class="w-4 h-4" />
                    </div>
                    <div class="flex-1">
                      <p class="font-medium text-gray-900 dark:text-white">
                        {{ role.display_name_th }}
                      </p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ role.display_name_en }}
                      </p>
                    </div>
                    <Icon 
                      v-if="selectedRoleId === role.id"
                      icon="fluent:checkmark-circle-24-filled"
                      class="w-5 h-5 text-primary-500"
                    />
                  </label>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
              <button
                @click="closeModal"
                :disabled="loading"
                class="min-h-[44px] sm:min-h-0 px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors disabled:opacity-50"
              >
                ยกเลิก
              </button>
              <button
                @click="assignRole"
                :disabled="loading || !selectedRoleId"
                class="min-h-[44px] sm:min-h-0 flex items-center gap-2 px-5 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <div v-if="loading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                <Icon v-else icon="fluent:checkmark-24-filled" class="w-4 h-4" />
                <span>{{ loading ? 'กำลังบันทึก...' : 'บันทึก' }}</span>
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>
