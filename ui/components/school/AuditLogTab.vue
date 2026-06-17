<script setup lang="ts">
/**
 * Audit Log Tab Component
 * ใช้สำหรับแสดงประวัติการแก้ไขของ Entity ต่างๆ
 */
import { Icon } from '@iconify/vue'

const props = defineProps<{
  academyId: number
  entityType: string
  entityId: number
}>()

const schoolApi = useSchoolManagement()
const logs = ref<any[]>([])
const isLoading = ref(true)

onMounted(async () => {
  await fetchLogs()
})

const fetchLogs = async () => {
  isLoading.value = true
  try {
    const response: any = await schoolApi.getEntityAuditLogs(
      props.academyId,
      props.entityType,
      props.entityId
    )
    if (response.success) {
      logs.value = response.data || []
    }
  } catch (err) {
    console.error('Failed to fetch audit logs:', err)
  } finally {
    isLoading.value = false
  }
}

const formatDate = (dateString: string) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getActionColor = (action: string) => {
  const colors: Record<string, string> = {
    created: 'text-green-600 bg-green-50 dark:bg-green-900/30',
    updated: 'text-blue-600 bg-blue-50 dark:bg-blue-900/30',
    deleted: 'text-red-600 bg-red-50 dark:bg-red-900/30',
    restored: 'text-indigo-600 bg-indigo-50 dark:bg-indigo-900/30'
  }
  return colors[action] || 'text-gray-600 bg-gray-50 dark:bg-gray-700'
}

const getActionLabel = (action: string) => {
  const labels: Record<string, string> = {
    created: 'สร้าง',
    updated: 'แก้ไข',
    deleted: 'ลบ',
    restored: 'กู้คืน'
  }
  return labels[action] || action
}
</script>

<template>
  <div class="space-y-4">
    <div v-if="isLoading" class="flex justify-center py-10">
      <div class="animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else-if="logs.length === 0" class="text-center py-10 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
      <Icon name="fluent:history-24-regular" class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-2" />
      <p class="text-gray-500 dark:text-gray-400">ไม่พบประวัติการแก้ไข</p>
    </div>

    <div v-else class="flow-root">
      <ul role="list" class="-mb-8">
        <li v-for="(log, logIdx) in logs" :key="log.id">
          <div class="relative pb-8">
            <span v-if="logIdx !== logs.length - 1" class="absolute left-5 top-5 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
            <div class="relative flex items-start space-x-3">
              <!-- Action Icon -->
              <div class="relative">
                <div :class="[
                  'h-10 w-10 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-gray-800',
                  getActionColor(log.action)
                ]">
                  <Icon 
                    :name="log.action === 'created' ? 'fluent:add-24-regular' : log.action === 'updated' ? 'fluent:edit-24-regular' : 'fluent:delete-24-regular'" 
                    class="w-5 h-5"
                  />
                </div>
              </div>

              <!-- Content -->
              <div class="min-w-0 flex-1 py-1.5">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                  <span class="font-medium text-gray-900 dark:text-white mr-2">
                    {{ log.user?.name || 'ระบบ' }}
                  </span>
                  <span :class="['inline-flex items-center px-2 py-0.5 rounded text-xs font-medium mr-2', getActionColor(log.action)]">
                    {{ getActionLabel(log.action) }}
                  </span>
                  <span class="whitespace-nowrap">{{ formatDate(log.created_at) }}</span>
                </div>
                
                <div class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                  <p>{{ log.description }}</p>
                  
                  <!-- Changed Fields -->
                  <div v-if="log.action === 'updated' && log.new_values" class="mt-2 space-y-1">
                    <div v-for="(val, field) in log.new_values" :key="field" class="flex flex-wrap gap-x-2 items-center">
                      <span class="font-medium text-gray-600 dark:text-gray-400">{{ field }}:</span>
                      <span class="text-red-500 line-through text-xs" v-if="log.old_values && log.old_values[field]">
                        {{ log.old_values[field] }}
                      </span>
                      <Icon name="fluent:arrow-right-12-regular" class="w-3 h-3 text-gray-400" v-if="log.old_values && log.old_values[field]" />
                      <span class="text-green-600 dark:text-green-400">{{ val }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>
