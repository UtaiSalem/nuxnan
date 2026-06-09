<script setup lang="ts">
import { ref, watch, defineAsyncComponent } from 'vue'
import { Icon } from '@iconify/vue'
import { useCourseStore } from '~/stores/course'
import { useSweetAlert } from '~/composables/useSweetAlert'

const draggable = defineAsyncComponent(() => import('vuedraggable'))

const props = defineProps<{
  groups: any[]
  courseId: number
}>()

const emit = defineEmits<{
  saved: []
}>()

const courseStore = useCourseStore()
const swal = useSweetAlert()

const localList = ref<any[]>([])
const isDirty = ref(false)
const isSaving = ref(false)

// sync เมื่อ groups prop เปลี่ยน
watch(() => props.groups, (val) => {
  if (!isDirty.value) {
    localList.value = val ? [...val] : []
  }
}, { immediate: true })

const onDragEnd = () => { isDirty.value = true }

const cancel = () => {
  localList.value = [...props.groups]
  isDirty.value = false
}

const save = async () => {
  isSaving.value = true
  try {
    const groupIds = localList.value.map(g => g.id)
    await courseStore.reorderGroups(props.courseId, groupIds)
    isDirty.value = false
    swal.success('บันทึกลำดับกลุ่มสำเร็จ')
    emit('saved')
  } catch (err: any) {
    swal.error(err.data?.message || 'ไม่สามารถบันทึกลำดับได้')
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <div class="space-y-4">
    <!-- Action buttons when dirty -->
    <div v-if="isDirty" class="flex items-center justify-between bg-white dark:bg-gray-800/80 p-3 rounded-xl border border-yellow-200 dark:border-yellow-900/50 shadow-sm animate-in fade-in slide-in-from-top-2">
      <div class="flex items-center gap-2 text-yellow-700 dark:text-yellow-400">
        <Icon icon="fluent:info-24-regular" class="w-4 h-4" />
        <span class="text-xs font-bold">มีการเปลี่ยนแปลงลำดับกลุ่ม</span>
      </div>
      <div class="flex gap-2">
        <button @click="cancel"
          class="px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
          ยกเลิก
        </button>
        <button @click="save" :disabled="isSaving"
          class="px-4 py-1.5 text-xs bg-teal-600 text-white font-bold rounded-lg hover:bg-teal-700 disabled:opacity-50 transition-all shadow-md flex items-center gap-2">
          <Icon :icon="isSaving ? 'svg-spinners:180-ring-with-bg' : 'fluent:save-24-filled'" class="w-4 h-4" />
          {{ isSaving ? 'กำลังบันทึก...' : 'บันทึกลำดับ' }}
        </button>
      </div>
    </div>

    <!-- Draggable list -->
    <ClientOnly>
      <draggable
        v-model="localList"
        item-key="id"
        handle=".drag-handle"
        @end="onDragEnd"
        class="space-y-1.5"
      >
        <template #item="{ element: group, index }">
          <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-white dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700 hover:border-teal-200 dark:hover:border-teal-800 hover:bg-teal-50 dark:hover:bg-teal-900/20 group transition-all duration-200">
            <!-- drag handle with larger touch target -->
            <div class="drag-handle p-2 -m-2 cursor-grab active:cursor-grabbing text-gray-400 hover:text-teal-600 dark:text-gray-500 dark:hover:text-teal-400 transition-colors">
              <Icon icon="fluent:re-order-dots-vertical-24-regular" class="w-5 h-5" />
            </div>
            
            <!-- order number -->
            <span class="w-6 text-xs font-bold text-gray-400 dark:text-gray-500 text-right shrink-0">{{ index + 1 }}</span>
            
            <!-- title -->
            <span class="flex-1 text-sm font-medium text-gray-700 dark:text-gray-200 truncate">{{ group.name }}</span>
          </div>
        </template>
      </draggable>
    </ClientOnly>
  </div>
</template>
