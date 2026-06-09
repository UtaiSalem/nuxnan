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
  <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-xl p-4">
    <div class="flex items-center justify-between cursor-pointer select-none" :class="isOpen ? 'mb-3' : 'mb-0'"
      @click="isOpen = !isOpen">
      <span class="text-sm font-semibold text-white/80 flex items-center gap-1">
        <Icon icon="fluent:re-order-24-regular" class="w-4 h-4" />
        จัดลำดับกลุ่ม (Groups)
      </span>
      <div class="flex items-center gap-2">
        <div v-if="isDirty" class="flex gap-2" @click.stop>
          <button @click="cancel"
            class="px-3 py-1 text-xs text-white/60 hover:text-white border border-white/20 rounded-lg transition-colors">
            ยกเลิก
          </button>
          <button @click="save" :disabled="isSaving"
            class="px-3 py-1 text-xs bg-yellow-400 text-yellow-900 font-bold rounded-lg hover:bg-yellow-300 disabled:opacity-50 transition-colors flex items-center gap-1">
            <Icon :icon="isSaving ? 'svg-spinners:180-ring-with-bg' : 'fluent:save-24-filled'" class="w-3 h-3" />
            บันทึก
          </button>
        </div>
        <Icon :icon="isOpen ? 'fluent:chevron-up-24-regular' : 'fluent:chevron-down-24-regular'"
          class="w-4 h-4 text-white/50" />
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
          <div class="flex items-center gap-2 px-2 py-2 rounded-lg hover:bg-white/10 group transition-colors">
            <!-- drag handle -->
            <div class="drag-handle cursor-grab active:cursor-grabbing text-white/30 hover:text-white/70">
              <Icon icon="fluent:re-order-dots-vertical-24-regular" class="w-4 h-4" />
            </div>
            <!-- order number -->
            <span class="w-5 text-xs text-white/40 text-right shrink-0">{{ index + 1 }}</span>
            <!-- title -->
            <span class="flex-1 text-sm text-white/80 truncate">{{ group.name }}</span>
          </div>
        </template>
      </draggable>
    </ClientOnly>
  </div>
</template>
