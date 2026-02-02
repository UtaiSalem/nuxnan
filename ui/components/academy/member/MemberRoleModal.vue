<script setup lang="ts">
import { ref, computed, watch } from 'vue'

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
  academyId: number
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

function close() {
  isOpen.value = false
}
</script>

<template>
  <UModal v-model="isOpen" :ui="{ width: 'sm:max-w-md' }">
    <UCard>
      <template #header>
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            กำหนดบทบาท
          </h3>
          <UButton
            icon="i-heroicons-x-mark"
            color="gray"
            variant="ghost"
            size="sm"
            @click="close"
          />
        </div>
      </template>

      <div class="space-y-4">
        <!-- Member Info -->
        <div v-if="member" class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
          <UAvatar
            :src="member.member_avatar"
            :alt="member.member_name"
            size="md"
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
            class="ml-2 px-2 py-1 rounded text-xs font-medium"
            :class="getRoleColor(member.academy_role)"
          >
            <Icon v-if="member.academy_role.icon" :name="member.academy_role.icon" class="w-3 h-3 mr-1" />
            {{ member.academy_role.display_name_th }}
          </span>
        </div>

        <!-- Role Selection -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            เลือกบทบาทใหม่
          </label>
          
          <div v-if="loadingRoles" class="flex justify-center py-4">
            <UIcon name="i-heroicons-arrow-path" class="w-6 h-6 animate-spin text-gray-400" />
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
                <Icon v-if="role.icon" :name="role.icon" class="w-4 h-4" />
              </div>
              <div class="flex-1">
                <p class="font-medium text-gray-900 dark:text-white">
                  {{ role.display_name_th }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                  {{ role.display_name_en }}
                </p>
              </div>
              <UIcon 
                v-if="selectedRoleId === role.id"
                name="i-heroicons-check-circle-solid"
                class="w-5 h-5 text-primary-500"
              />
            </label>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end gap-3">
          <UButton
            color="gray"
            variant="ghost"
            @click="close"
          >
            ยกเลิก
          </UButton>
          <UButton
            color="primary"
            :loading="loading"
            :disabled="!selectedRoleId || loading"
            @click="assignRole"
          >
            บันทึก
          </UButton>
        </div>
      </template>
    </UCard>
  </UModal>
</template>
