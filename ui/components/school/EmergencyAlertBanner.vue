<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps<{
  academyId: number | string
}>()

const api = useApi()
const activeAlerts = ref<any[]>([])
const isLoading = ref(true)
let pollInterval: any = null

const fetchAlerts = async () => {
  if (!props.academyId) return
  
  try {
    const response: any = await api.get(`/api/academies/${props.academyId}/emergency-alerts/active`)
    if (response.success && response.data) {
      activeAlerts.value = response.data
    }
  } catch (err) {
    console.error('Failed to fetch emergency alerts', err)
  } finally {
    isLoading.value = false
  }
}

const acknowledge = async (alertId: number, responseType: 'safe' | 'need_help') => {
  try {
    const res: any = await api.post(`/api/academies/${props.academyId}/emergency-alerts/${alertId}/acknowledge`, {
      response: responseType,
      channel: 'app'
    })
    
    if (res.success) {
      // Update local state
      const alert = activeAlerts.value.find(a => a.id === alertId)
      if (alert) {
        alert.is_acknowledged = true
      }
    }
  } catch (err) {
    console.error('Failed to acknowledge alert', err)
  }
}

onMounted(() => {
  fetchAlerts()
  // Poll every 60 seconds
  pollInterval = setInterval(fetchAlerts, 60000)
})

onUnmounted(() => {
  if (pollInterval) {
    clearInterval(pollInterval)
  }
})

const getSeverityColor = (severity: string) => {
  switch (severity) {
    case 'critical': return 'bg-red-600 text-white'
    case 'high': return 'bg-orange-500 text-white'
    case 'medium': return 'bg-amber-400 text-gray-900'
    default: return 'bg-blue-500 text-white'
  }
}

const getSeverityIcon = (severity: string) => {
  switch (severity) {
    case 'critical': return 'fluent:warning-24-filled'
    case 'high': return 'fluent:error-circle-24-filled'
    case 'medium': return 'fluent:info-24-filled'
    default: return 'fluent:info-16-regular'
  }
}
</script>

<template>
  <div v-if="!isLoading && activeAlerts.length > 0" class="w-full flex flex-col gap-2 mb-4">
    <div 
      v-for="alert in activeAlerts" 
      :key="alert.id"
      :class="['w-full rounded-lg shadow-md p-4 flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between transition-all', getSeverityColor(alert.severity)]"
    >
      <div class="flex items-start gap-3">
        <Icon :name="getSeverityIcon(alert.severity)" class="w-6 h-6 flex-shrink-0 mt-0.5" />
        <div>
          <h3 class="font-bold text-lg">{{ alert.title }}</h3>
          <p class="text-sm opacity-90 mt-1 whitespace-pre-wrap">{{ alert.message }}</p>
        </div>
      </div>
      
      <div v-if="!alert.is_acknowledged" class="flex items-center gap-2 w-full sm:w-auto mt-2 sm:mt-0">
        <button 
          @click="acknowledge(alert.id, 'safe')"
          class="flex-1 sm:flex-none px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg text-sm font-semibold backdrop-blur-sm transition-colors whitespace-nowrap"
        >
          <Icon name="fluent:checkmark-circle-24-regular" class="w-4 h-4 inline mr-1" />
          ปลอดภัยแล้ว
        </button>
        <button 
          @click="acknowledge(alert.id, 'need_help')"
          class="flex-1 sm:flex-none px-4 py-2 bg-red-900/40 hover:bg-red-900/60 rounded-lg text-sm font-semibold backdrop-blur-sm transition-colors whitespace-nowrap"
        >
          <Icon name="fluent:person-support-24-regular" class="w-4 h-4 inline mr-1" />
          ต้องการความช่วยเหลือ
        </button>
      </div>
      <div v-else class="px-4 py-2 bg-black/10 rounded-lg text-sm font-medium flex items-center gap-2 backdrop-blur-sm">
        <Icon name="fluent:checkmark-circle-24-filled" class="w-5 h-5" />
        รับทราบแล้ว
      </div>
    </div>
  </div>
</template>
