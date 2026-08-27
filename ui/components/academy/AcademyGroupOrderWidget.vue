<script setup lang="ts">
import { ref, watch, defineAsyncComponent } from 'vue'
import { Icon } from '@iconify/vue'
import { useAcademyStore } from '~/stores/academy'
import { useSweetAlert } from '~/composables/useSweetAlert'

const draggable = defineAsyncComponent(() => import('vuedraggable'))

const props = defineProps<{
  groups: any[]
  academyId: number | string
}>()

const emit = defineEmits<{
  saved: []
}>()

const academyStore = useAcademyStore()
const swal = useSweetAlert()

const localList = ref<any[]>([])
const isDirty = ref(false)
const isSaving = ref(false)
const isOpen = ref(false)

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
    await academyStore.reorderGroups(props.academyId, groupIds)
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
  <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm mb-6">
    <div class="flex items-center justify-between cursor-pointer select-none" :class="isOpen ? 'mb-3' : 'mb-0'"
      @click="isOpen = !isOpen">
      <span class="text-sm font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-2">
        <Icon icon="fluent:re-order-24-regular" class="w-5 h-5 text-purple-500" />
        จัดลำดับกลุ่มในโรงเรียน
      </span>
      <div class="flex items-center gap-2">
        <div v-if="isDirty" class="flex gap-2" @click.stop>
          <button @click="cancel"
            class="min-h-[44px] sm:min-h-0 px-3 py-1 text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 border border-gray-200 dark:border-gray-600 rounded-lg transition-colors">
            ยกเลิก
          </button>
          <button @click="save" :disabled="isSaving"
            class="min-h-[44px] sm:min-h-0 px-3 py-1 text-xs bg-purple-500 text-white font-bold rounded-lg hover:bg-purple-600 disabled:opacity-50 transition-colors flex items-center gap-1 shadow-sm">
            <Icon :icon="isSaving ? 'svg-spinners:180-ring-with-bg' : 'fluent:save-24-filled'" class="w-3 h-3" />
            บันทึก
          </button>
        </div>
        <Icon :icon="isOpen ? 'fluent:chevron-up-24-regular' : 'fluent:chevron-down-24-regular'"
          class="w-4 h-4 text-gray-400" />
      </div>
    </div>

    <ClientOnly v-if="isOpen">
      <draggable
        v-model="localList"
        item-key="id"
        handle=".drag-handle"
        @end="onDragEnd"
        class="space-y-1 mt-3"
      >
        <template #item="{ element: group, index }">
          <div class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 group transition-colors border border-transparent hover:border-gray-100 dark:hover:border-gray-600">
            <!-- drag handle -->
            <div class="drag-handle cursor-grab active:cursor-grabbing text-gray-300 hover:text-gray-500 dark:text-gray-600 dark:hover:text-gray-400">
              <Icon icon="fluent:re-order-dots-vertical-24-regular" class="w-5 h-5" />
            </div>
            <!-- order number -->
            <span class="w-6 text-xs font-mono text-gray-400 text-right shrink-0">{{ index + 1 }}</span>
            <!-- icon by type -->
            <div class="shrink-0">
              <Icon v-if="group.type === 'department'" icon="heroicons:briefcase-solid" class="w-4 h-4 text-indigo-500" />
              <Icon v-else-if="group.type === 'classroom'" icon="heroicons:academic-cap-solid" class="w-4 h-4 text-cyan-500" />
              <Icon v-else icon="heroicons:star-solid" class="w-4 h-4 text-green-500" />
            </div>
            <!-- title -->
            <span class="flex-1 text-sm font-medium text-gray-700 dark:text-gray-200 truncate">{{ group.name }}</span>
            <!-- badge -->
            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 uppercase tracking-wider">
              {{ group.type }}
            </span>
          </div>
        </template>
      </draggable>
    </ClientOnly>
  </div>
</template>
