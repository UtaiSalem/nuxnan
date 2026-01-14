<script setup lang="ts">
import { ref, watch, onBeforeUnmount } from 'vue'
import { Icon } from '@iconify/vue'
import { useSweetAlert } from '~/composables/useSweetAlert'

interface Props {
  show: boolean
  pollId: number
  isOwner: boolean
  isEnded: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  close: []
  edit: []
  closePoll: []
  delete: []
  share: []
}>()

const swal = useSweetAlert()
const showDeleteConfirm = ref(false)
const showCloseConfirm = ref(false)
const isDeleting = ref(false)
const isClosing = ref(false)

// Template ref for the menu element
const menuRef = ref<HTMLElement | null>(null)

// Close menu when clicking outside
const handleClickOutside = (event: MouseEvent) => {
  // Use the template ref instead of document.querySelector
  // This ensures we check the correct menu instance
  if (menuRef.value && !menuRef.value.contains(event.target as Node)) {
    // Check if clicked on a menu trigger button by looking for the class
    const target = event.target as HTMLElement
    const clickedOnTrigger = target.closest('.poll-menu-trigger')
    if (!clickedOnTrigger) {
      emit('close')
    }
  }
}

watch(() => props.show, (newValue) => {
  if (newValue) {
    // Use setTimeout to avoid immediately closing when the menu opens
    setTimeout(() => {
      document.addEventListener('click', handleClickOutside)
    }, 0)
  } else {
    document.removeEventListener('click', handleClickOutside)
  }
})

// Cleanup on unmount
onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})

const handleEdit = () => {
  emit('close')
  emit('edit')
}

const handleClosePoll = () => {
  emit('close')
  showCloseConfirm.value = true
}

const confirmClosePoll = async () => {
  isClosing.value = true
  
  try {
    emit('closePoll')
    showCloseConfirm.value = false
  } catch (error) {
    console.error('Error closing poll:', error)
    swal.error('ไม่สามารถปิดโพลได้')
  } finally {
    isClosing.value = false
  }
}

const handleDelete = () => {
  emit('close')
  showDeleteConfirm.value = true
}

const confirmDelete = async () => {
  const confirmed = await swal.confirmDelete('โพลนี้', 'การลบโพลจะลบโหวตและความคิดเห็นทั้งหมดด้วย')
  
  if (!confirmed) {
    return
  }
  
  isDeleting.value = true
  
  try {
    emit('delete')
    showDeleteConfirm.value = false
  } catch (error) {
    console.error('Error deleting poll:', error)
    swal.error('ไม่สามารถลบโพลได้')
  } finally {
    isDeleting.value = false
  }
}

const handleShare = () => {
  emit('close')
  emit('share')
}
</script>

<template>
  <!-- Menu -->
  <Transition name="dropdown">
    <div 
      v-if="show" 
      ref="menuRef"
      class="poll-menu"
    >
      <!-- Edit Poll (only for owner) -->
      <button
        v-if="isOwner"
        @click="handleEdit"
        class="poll-menu-item"
      >
        <Icon icon="fluent:edit-24-regular" class="w-5 h-5 text-vikinger-purple" />
        <span>แก้ไขโพล</span>
      </button>
      
      <!-- Close Poll (only for owner and not ended) -->
      <button
        v-if="isOwner && !isEnded"
        @click="handleClosePoll"
        class="poll-menu-item"
      >
        <Icon icon="fluent:pause-circle-24-regular" class="w-5 h-5 text-vikinger-orange" />
        <span>ปิดโหวต</span>
      </button>
      
      <!-- View Results -->
      <button
        v-if="!isEnded"
        @click="emit('close')"
        class="poll-menu-item"
      >
        <Icon icon="fluent:chart-multiple-24-regular" class="w-5 h-5 text-vikinger-cyan" />
        <span>ดูผลลัพธ์</span>
      </button>
      
      <!-- Share Poll -->
      <button
        @click="handleShare"
        class="poll-menu-item"
      >
        <Icon icon="fluent:share-24-regular" class="w-5 h-5 text-vikinger-green" />
        <span>แชร์โพล</span>
      </button>
      
      <!-- Divider -->
      <div v-if="isOwner" class="poll-menu-divider"></div>
      
      <!-- Delete Poll (only for owner) -->
      <button
        v-if="isOwner"
        @click="handleDelete"
        :disabled="isDeleting"
        class="poll-menu-item danger"
      >
        <Icon 
          :icon="isDeleting ? 'fluent:spinner-ios-20-regular' : 'fluent:delete-24-regular'" 
          class="w-5 h-5"
          :class="{ 'animate-spin': isDeleting }"
        />
        <span>{{ isDeleting ? 'กำลังลบ...' : 'ลบโพล' }}</span>
      </button>
    </div>
  </Transition>
  
  <!-- Close Poll Confirmation Modal -->
  <Teleport to="body">
    <Transition name="modal">
      <div 
        v-if="showCloseConfirm" 
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
        @click.self="showCloseConfirm = false"
      >
        <div class="bg-white dark:bg-vikinger-dark-100 rounded-xl shadow-vikinger-lg p-6 max-w-md mx-4">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 rounded-full bg-vikinger-orange/10 flex items-center justify-center">
              <Icon icon="fluent:pause-circle-24-regular" class="w-6 h-6 text-vikinger-orange" />
            </div>
            <h3 class="text-xl font-bold text-gray-800 dark:text-white">ปิดโหวต</h3>
          </div>
          
          <p class="text-gray-600 dark:text-gray-400 mb-4">
            คุณต้องการปิดโหวตนี้ใช่หรือไม่?
          </p>
          
          <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 mb-6 border border-yellow-200 dark:border-yellow-700/30">
            <p class="text-sm text-yellow-700 dark:text-yellow-300 mb-2 font-medium">เมื่อปิดโหวตแล้ว:</p>
            <ul class="text-sm text-yellow-700 dark:text-yellow-300 space-y-1">
              <li class="flex items-start gap-2">
                <Icon icon="fluent:checkmark-24-regular" class="w-4 h-4 mt-0.5 flex-shrink-0" />
                <span>ผู้ใช้จะไม่สามารถโหวตได้อีก</span>
              </li>
              <li class="flex items-start gap-2">
                <Icon icon="fluent:checkmark-24-regular" class="w-4 h-4 mt-0.5 flex-shrink-0" />
                <span>ผลลัพธ์จะแสดงทันที</span>
              </li>
              <li class="flex items-start gap-2">
                <Icon icon="fluent:checkmark-24-regular" class="w-4 h-4 mt-0.5 flex-shrink-0" />
                <span>ไม่สามารถเปิดโหวตใหม่ได้</span>
              </li>
            </ul>
          </div>
          
          <div class="flex gap-3">
            <button
              @click="showCloseConfirm = false"
              class="flex-1 py-3 px-4 rounded-xl border border-gray-200 dark:border-vikinger-dark-50/30 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 transition-colors"
            >
              ยกเลิก
            </button>
            <button
              @click="confirmClosePoll"
              :disabled="isClosing"
              class="flex-1 py-3 px-4 rounded-xl bg-vikinger-orange text-white font-semibold hover:bg-vikinger-orange/90 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
            >
              <Icon v-if="isClosing" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
              <span>{{ isClosing ? 'กำลังปิด...' : 'ยืนยันการปิดโหวต' }}</span>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
  
  <!-- Delete Poll Confirmation Modal -->
  <Teleport to="body">
    <Transition name="modal">
      <div 
        v-if="showDeleteConfirm" 
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
        @click.self="showDeleteConfirm = false"
      >
        <div class="bg-white dark:bg-vikinger-dark-100 rounded-xl shadow-vikinger-lg p-6 max-w-md mx-4">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 rounded-full bg-red-500/10 flex items-center justify-center">
              <Icon icon="fluent:delete-24-regular" class="w-6 h-6 text-red-500" />
            </div>
            <h3 class="text-xl font-bold text-gray-800 dark:text-white">ลบโพล</h3>
          </div>
          
          <p class="text-gray-600 dark:text-gray-400 mb-4">
            คุณต้องการลบโพลนี้ใช่หรือไม่?
          </p>
          
          <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 mb-6 border border-red-200 dark:border-red-700/30">
            <p class="text-sm text-red-700 dark:text-red-300 mb-3 flex items-center gap-2">
              <Icon icon="fluent:warning-24-regular" class="w-5 h-5" />
              <span class="font-medium">การดำเนินการนี้ไม่สามารถย้อนกลับได้</span>
            </p>
            <p class="text-sm text-red-700 dark:text-red-300 font-medium">เมื่อลบโพลแล้ว:</p>
            <ul class="text-sm text-red-700 dark:text-red-300 space-y-1 mt-2">
              <li class="flex items-start gap-2">
                <Icon icon="fluent:checkmark-24-regular" class="w-4 h-4 mt-0.5 flex-shrink-0" />
                <span>โหวตทั้งหมดจะหายไป</span>
              </li>
              <li class="flex items-start gap-2">
                <Icon icon="fluent:checkmark-24-regular" class="w-4 h-4 mt-0.5 flex-shrink-0" />
                <span>ความคิดเห็นทั้งหมดจะหายไป</span>
              </li>
              <li class="flex items-start gap-2">
                <Icon icon="fluent:checkmark-24-regular" class="w-4 h-4 mt-0.5 flex-shrink-0" />
                <span>ข้อมูลทั้งหมดจะถูกลบถาวร</span>
              </li>
            </ul>
          </div>
          
          <div class="flex gap-3">
            <button
              @click="showDeleteConfirm = false"
              class="flex-1 py-3 px-4 rounded-xl border border-gray-200 dark:border-vikinger-dark-50/30 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 transition-colors"
            >
              ยกเลิก
            </button>
            <button
              @click="confirmDelete"
              :disabled="isDeleting"
              class="flex-1 py-3 px-4 rounded-xl bg-red-500 text-white font-semibold hover:bg-red-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
            >
              <Icon v-if="isDeleting" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
              <span>{{ isDeleting ? 'กำลังลบ...' : 'ยืนยันการลบ' }}</span>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.poll-menu {
  position: absolute;
  right: 0;
  top: 100%;
  margin-top: 8px;
  width: 200px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 8px 40px rgba(0, 0, 0, 0.15);
  border: 1px solid rgba(0, 0, 0, 0.1);
  overflow: hidden;
  z-index: 50;
}

.dark .poll-menu {
  background: #2f3749;
  border-color: rgba(255, 255, 255, 0.1);
}

.poll-menu-item {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background: transparent;
  border: none;
  color: #374151;
  font-size: 14px;
  font-weight: 500;
  text-align: left;
  cursor: pointer;
  transition: all 0.2s ease;
}

.dark .poll-menu-item {
  color: #d1d5db;
}

.poll-menu-item:hover:not(:disabled) {
  background: #f3f4f6;
}

.dark .poll-menu-item:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.05);
}

.poll-menu-item:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.poll-menu-item.danger {
  color: #ef4444;
}

.dark .poll-menu-item.danger {
  color: #f87171;
}

.poll-menu-item.danger:hover:not(:disabled) {
  background: #fef2f2;
}

.dark .poll-menu-item.danger:hover:not(:disabled) {
  background: rgba(239, 68, 68, 0.1);
}

.poll-menu-divider {
  height: 1px;
  background: rgba(0, 0, 0, 0.1);
  margin: 4px 0;
}

.dark .poll-menu-divider {
  background: rgba(255, 255, 255, 0.1);
}

/* Dropdown Animation */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px) scale(0.95);
}

/* Modal Animation */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active > div {
  animation: modal-in 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.modal-leave-active > div {
  animation: modal-out 0.2s ease-in forwards;
}

@keyframes modal-in {
  from {
    opacity: 0;
    transform: scale(0.9) translateY(-20px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

@keyframes modal-out {
  from {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
  to {
    opacity: 0;
    transform: scale(0.95) translateY(10px);
  }
}
</style>
