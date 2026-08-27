<script setup lang="ts">
import { computed, ref } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

interface Props {
  group: any
}

const props = defineProps<Props>()
const emit = defineEmits<{
  deleted: [groupId: number]
  cancel: []
}>()

const { deleteGroup } = useAcademyGroups()

const confirmText = ref('')
const isDeleting = ref(false)

const canDelete = computed(() => confirmText.value.trim() === props.group?.name)

const handleDelete = async () => {
  if (!props.group?.id || !canDelete.value || isDeleting.value) return

  isDeleting.value = true
  try {
    await deleteGroup(props.group.id)
    emit('deleted', props.group.id)

    Swal.fire({
      icon: 'success',
      title: 'ลบส่วนงานแล้ว',
      timer: 1500,
      showConfirmButton: false,
    })
  } catch (error: any) {
    Swal.fire({
      icon: 'error',
      title: 'ลบไม่สำเร็จ',
      text: error?.data?.message || 'ไม่สามารถลบส่วนงานได้',
    })
  } finally {
    isDeleting.value = false
  }
}
</script>

<template>
  <div class="space-y-5 max-w-xl">
    <div class="rounded-xl border border-red-200 dark:border-red-800/60 bg-red-50 dark:bg-red-900/20 p-4">
      <div class="flex items-start gap-3">
        <Icon icon="heroicons:exclamation-triangle" class="w-6 h-6 text-red-500 flex-shrink-0 mt-0.5" />
        <div class="text-sm text-red-700 dark:text-red-300 space-y-1">
          <div class="font-semibold">การลบส่วนงานเป็นการดำเนินการถาวร</div>
          <div>สมาชิก หัวหน้า และการตั้งค่าสิทธิ์ของส่วนงานนี้จะถูกลบออก</div>
          <div>หากมีโพสต์ที่เชื่อมกับกลุ่มนี้ ควรตรวจสอบผลกระทบฝั่ง backend เพิ่มเติมก่อนลบจริง</div>
        </div>
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        พิมพ์ชื่อ <span class="font-bold">{{ group.name }}</span> เพื่อยืนยัน
      </label>
      <input
        v-model="confirmText"
        type="text"
        class="w-full px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-vikinger-dark-100 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500/40"
      />
    </div>

    <div class="flex justify-end gap-3 pt-3 border-t border-gray-100 dark:border-gray-700">
      <button
        type="button"
        class="min-h-[44px] sm:min-h-0 px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300"
        @click="emit('cancel')"
      >
        ยกเลิก
      </button>
      <button
        type="button"
        :disabled="!canDelete || isDeleting"
        class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-red-500 text-white rounded-lg font-medium hover:bg-red-600 disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-2"
        @click="handleDelete"
      >
        <Icon v-if="isDeleting" icon="svg-spinners:ring-resize" class="w-4 h-4" />
        <Icon v-else icon="heroicons:trash" class="w-4 h-4" />
        ลบส่วนงาน
      </button>
    </div>
  </div>
</template>
