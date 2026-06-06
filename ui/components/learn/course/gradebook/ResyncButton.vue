<script setup lang="ts">
import { Icon } from '@iconify/vue'

const props = defineProps<{
  courseId: string | number
}>()

const emit = defineEmits(['resynced'])
const api = useApi()
const swal = useSwal()
const isLoading = ref(false)

const handleResync = async () => {
  isLoading.value = true
  try {
    const res: any = await api.post(`/api/courses/${props.courseId}/score-breakdown/resync`)
    if (res.success) {
      swal.toast(res.message || 'รีซิงค์ข้อมูลคะแนนเรียบร้อยแล้ว', 'success')
      emit('resynced')
    }
  } catch (error: any) {
    swal.error(error?.data?.message || 'ไม่สามารถรีซิงค์ข้อมูลได้')
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <button
    @click="handleResync"
    :disabled="isLoading"
    class="flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 disabled:bg-primary-400 text-white rounded-lg transition-colors text-sm font-medium shadow-sm"
  >
    <Icon
      icon="heroicons:arrow-path"
      class="w-4 h-4"
      :class="{ 'animate-spin': isLoading }"
    />
    {{ isLoading ? 'กำลังรีซิงค์...' : 'รีซิงค์คะแนนทั้งหมด' }}
  </button>
</template>
