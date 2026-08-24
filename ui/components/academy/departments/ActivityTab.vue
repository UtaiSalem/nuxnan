<template>
  <div class="relative flex flex-col bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700 rounded-2xl">
    <div class="p-4 sm:p-6 pb-0 sm:pb-0 mb-4">
      <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-1">ประวัติการใช้งานของฝ่าย</h4>
      <p class="text-sm text-gray-500">บันทึกการแก้ไขฝ่าย สมาชิก และสิทธิ์ (จากระบบประวัติกิจกรรมสมาชิก)</p>
    </div>
    
    <div class="p-4 sm:p-6 pt-0">
      <div v-if="isLoading && logs.length === 0" class="flex justify-center py-8">
        <div class="h-8 w-8 animate-spin rounded-full border-4 border-primary-500 border-t-transparent"></div>
      </div>
      
      <div v-else-if="logs.length === 0" class="flex flex-col items-center justify-center py-12 text-gray-500">
        <Icon icon="fluent:history-24-regular" class="mb-3 h-10 w-10 opacity-50" />
        <p>ยังไม่มีประวัติการใช้งานของฝ่ายนี้</p>
      </div>
      
      <div v-else class="flex flex-col gap-6">
        <div v-for="log in logs" :key="log.id" class="flex gap-3">
          <!-- Icon -->
          <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full" :class="getIconConfig(log.action).bgClass">
            <Icon :icon="getIconConfig(log.action).icon" class="h-5 w-5" :class="getIconConfig(log.action).textClass" />
          </div>
          
          <!-- Content -->
          <div class="min-w-0 flex-1 break-words pb-2">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 mb-1">
              <span class="font-medium text-gray-900 dark:text-white">{{ log.user?.name || 'ระบบ' }}</span>
              <span class="text-xs text-gray-500 flex-shrink-0" :title="formatFullDate(log.created_at)">{{ log.created_at_human || formatDate(log.created_at) }}</span>
            </div>
            
            <p class="text-sm text-gray-700 dark:text-gray-300">{{ log.description }}</p>
            
            <div v-if="log.action === 'department_permission_update' && log.new_values" class="mt-2 flex flex-wrap gap-1.5">
              <span v-for="p in log.new_values.turned_on" :key="'on-'+p" class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                + {{ p }}
              </span>
              <span v-for="p in log.new_values.turned_off" :key="'off-'+p" class="inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/50 dark:text-red-300">
                - {{ p }}
              </span>
            </div>
          </div>
        </div>
        
        <button v-if="pagination.current_page < pagination.last_page" type="button" class="mt-2 flex min-h-[44px] w-full items-center justify-center rounded-xl bg-gray-50 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:bg-gray-700/50 dark:text-gray-300 dark:hover:bg-gray-700" :disabled="isLoading" @click="fetchLogs(pagination.current_page + 1)">
          <span v-if="isLoading" class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-gray-500 border-t-transparent"></span>
          โหลดเพิ่ม
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps<{
  academyId: number | string
  departmentId: number
}>()

const api = useApi()

const logs = ref<any[]>([])
const isLoading = ref(false)
const pagination = ref({ current_page: 1, last_page: 1, per_page: 20, total: 0 })

const fetchLogs = async (page = 1) => {
  if (!props.academyId || !props.departmentId) return
  isLoading.value = true
  try {
    const response: any = await api.get(`/api/academies/${props.academyId}/activity-log`, {
      query: { department_id: props.departmentId, all: 1, per_page: 20, page }
    })
    
    if (response.success) {
      if (page === 1) {
        logs.value = response.logs || []
      } else {
        logs.value = [...logs.value, ...(response.logs || [])]
      }
      if (response.pagination) {
        pagination.value = response.pagination
      }
    }
  } catch (err) {
    console.error('Failed to fetch activity logs:', err)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchLogs(1)
})

watch(() => props.departmentId, () => {
  fetchLogs(1)
})

const getIconConfig = (action: string) => {
  const map: Record<string, { icon: string, bgClass: string, textClass: string }> = {
    department_create: { icon: 'fluent:add-24-regular', bgClass: 'bg-emerald-100 dark:bg-emerald-900/30', textClass: 'text-emerald-600 dark:text-emerald-400' },
    department_update: { icon: 'fluent:edit-24-regular', bgClass: 'bg-blue-100 dark:bg-blue-900/30', textClass: 'text-blue-600 dark:text-blue-400' },
    department_delete: { icon: 'fluent:delete-24-regular', bgClass: 'bg-red-100 dark:bg-red-900/30', textClass: 'text-red-600 dark:text-red-400' },
    department_setup: { icon: 'fluent:building-24-regular', bgClass: 'bg-indigo-100 dark:bg-indigo-900/30', textClass: 'text-indigo-600 dark:text-indigo-400' },
    department_member_add: { icon: 'fluent:person-add-24-regular', bgClass: 'bg-emerald-100 dark:bg-emerald-900/30', textClass: 'text-emerald-600 dark:text-emerald-400' },
    department_member_remove: { icon: 'fluent:person-delete-24-regular', bgClass: 'bg-red-100 dark:bg-red-900/30', textClass: 'text-red-600 dark:text-red-400' },
    department_member_role_change: { icon: 'fluent:person-swap-24-regular', bgClass: 'bg-amber-100 dark:bg-amber-900/30', textClass: 'text-amber-600 dark:text-amber-400' },
    department_permission_update: { icon: 'fluent:lock-closed-key-24-regular', bgClass: 'bg-purple-100 dark:bg-purple-900/30', textClass: 'text-purple-600 dark:text-purple-400' },
  }
  return map[action] || { icon: 'fluent:history-24-regular', bgClass: 'bg-gray-100 dark:bg-gray-700', textClass: 'text-gray-600 dark:text-gray-300' }
}

const formatDate = (dateStr: string) => {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('th-TH', { year: 'numeric', month: 'short', day: 'numeric' })
}

const formatFullDate = (dateStr: string) => {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleString('th-TH', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>
