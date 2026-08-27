<script setup lang="ts">
/**
 * Bulk Action Bar Component
 * แถบดำเนินการหลายรายการสำหรับสมาชิก
 */
import { Icon } from '@iconify/vue'

interface Props {
  count: number
  showApprove?: boolean
  showReject?: boolean
  showSuspend?: boolean
  showRemove?: boolean
  showAssignRole?: boolean
  showExport?: boolean
  isProcessing?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showApprove: true,
  showReject: true,
  showSuspend: true,
  showRemove: true,
  showAssignRole: true,
  showExport: true,
  isProcessing: false,
})

const emit = defineEmits<{
  'approve': []
  'reject': []
  'suspend': []
  'unsuspend': []
  'remove': []
  'assign-role': []
  'export': []
  'clear': []
}>()
</script>

<template>
  <Transition
    enter-active-class="transition-all duration-300 ease-out"
    enter-from-class="opacity-0 translate-y-4"
    enter-to-class="opacity-100 translate-y-0"
    leave-active-class="transition-all duration-200 ease-in"
    leave-from-class="opacity-100 translate-y-0"
    leave-to-class="opacity-0 translate-y-4"
  >
    <div 
      v-if="count > 0"
      class="fixed bottom-4 left-1/2 -translate-x-1/2 z-50 px-4 w-full max-w-3xl"
    >
      <div class="bg-gray-900 dark:bg-gray-800 text-white rounded-2xl shadow-2xl border border-gray-700">
        <div class="flex items-center justify-between px-4 py-3 gap-4">
          <!-- Selection Count -->
          <div class="flex items-center gap-3 shrink-0">
            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-primary-500 text-white font-bold text-sm">
              {{ count }}
            </div>
            <span class="text-sm font-medium hidden sm:inline">
              เลือกแล้ว {{ count }} รายการ
            </span>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-1 sm:gap-2 overflow-x-auto">
            <!-- Approve -->
            <button
              v-if="showApprove"
              @click="emit('approve')"
              :disabled="isProcessing"
              class="min-h-[44px] sm:min-h-0 flex items-center gap-1.5 px-3 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap"
            >
              <Icon icon="fluent:checkmark-24-filled" class="w-4 h-4" />
              <span class="hidden sm:inline">อนุมัติ</span>
            </button>

            <!-- Reject -->
            <button
              v-if="showReject"
              @click="emit('reject')"
              :disabled="isProcessing"
              class="min-h-[44px] sm:min-h-0 flex items-center gap-1.5 px-3 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap"
            >
              <Icon icon="fluent:dismiss-24-filled" class="w-4 h-4" />
              <span class="hidden sm:inline">ปฏิเสธ</span>
            </button>

            <!-- Suspend -->
            <button
              v-if="showSuspend"
              @click="emit('suspend')"
              :disabled="isProcessing"
              class="min-h-[44px] sm:min-h-0 flex items-center gap-1.5 px-3 py-2 rounded-lg bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap"
            >
              <Icon icon="fluent:person-prohibited-24-filled" class="w-4 h-4" />
              <span class="hidden sm:inline">ระงับ</span>
            </button>

            <!-- Assign Role -->
            <button
              v-if="showAssignRole"
              @click="emit('assign-role')"
              :disabled="isProcessing"
              class="min-h-[44px] sm:min-h-0 flex items-center gap-1.5 px-3 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap"
            >
              <Icon icon="fluent:person-tag-24-filled" class="w-4 h-4" />
              <span class="hidden sm:inline">กำหนดบทบาท</span>
            </button>

            <!-- Export -->
            <button
              v-if="showExport"
              @click="emit('export')"
              :disabled="isProcessing"
              class="min-h-[44px] sm:min-h-0 flex items-center gap-1.5 px-3 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap"
            >
              <Icon icon="fluent:arrow-download-24-filled" class="w-4 h-4" />
              <span class="hidden sm:inline">ส่งออก</span>
            </button>

            <!-- Remove -->
            <button
              v-if="showRemove"
              @click="emit('remove')"
              :disabled="isProcessing"
              class="min-h-[44px] sm:min-h-0 flex items-center gap-1.5 px-3 py-2 rounded-lg bg-gray-700 hover:bg-gray-600 text-white text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap"
            >
              <Icon icon="fluent:delete-24-filled" class="w-4 h-4" />
              <span class="hidden sm:inline">ลบ</span>
            </button>

            <!-- Divider -->
            <div class="w-px h-6 bg-gray-700 mx-1"></div>

            <!-- Clear Selection -->
            <button
              @click="emit('clear')"
              :disabled="isProcessing"
              class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 justify-center flex items-center gap-1.5 p-2 rounded-lg hover:bg-gray-700 text-gray-400 hover:text-white transition-colors disabled:opacity-50"
              title="ยกเลิกการเลือก"
            >
              <Icon icon="fluent:dismiss-circle-24-regular" class="w-5 h-5" />
            </button>
          </div>
        </div>

        <!-- Loading Overlay -->
        <div 
          v-if="isProcessing"
          class="absolute inset-0 bg-gray-900/80 rounded-2xl flex items-center justify-center"
        >
          <div class="flex items-center gap-2 text-white">
            <div class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
            <span class="text-sm">กำลังดำเนินการ...</span>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>
