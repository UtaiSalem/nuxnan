<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Icon } from '@iconify/vue'
import { usePolls } from '~/composables/usePolls'
import { useSweetAlert } from '~/composables/useSweetAlert'
import type { Poll, UpdatePollOptions } from '~/composables/usePolls'

interface Props {
  show: boolean
  poll: Poll
}

const props = defineProps<Props>()

const emit = defineEmits<{
  close: []
  pollUpdated: (poll: Poll) => void
}>()

const { updatePoll } = usePolls()
const swal = useSweetAlert()

// Form state
const pollQuestion = ref('')
const pollOptions = ref<string[]>([])
const pollDuration = ref(24) // hours
const isSubmitting = ref(false)

// UI states
const showDurationDropdown = ref(false)
const validationErrors = ref<Record<string, string>>({})

// Duration options
const durationOptions = [
  { value: 1, label: '1 ชั่วโมง' },
  { value: 6, label: '6 ชั่วโมง' },
  { value: 24, label: '24 ชั่วโมง' },
  { value: 72, label: '3 วัน' },
  { value: 168, label: '7 วัน' },
  { value: 720, label: '30 วัน' },
]

const selectedDurationLabel = computed(() => {
  const option = durationOptions.find(d => d.value === pollDuration.value)
  return option?.label || '24 ชั่วโมง'
})

// Validation
const validateForm = (): boolean => {
  const errors: Record<string, string> = {}
  
  // Validate question
  if (!pollQuestion.value.trim()) {
    errors.question = 'กรุณาใส่คำถาม'
  } else if (pollQuestion.value.trim().length < 5) {
    errors.question = 'กรุณาใส่คำถามอย่างน้อย 5 ตัวอักษร'
  }
  
  // Validate options
  const validOptions = pollOptions.value.filter(opt => opt.trim())
  if (validOptions.length < 2) {
    errors.options = 'กรุณาใส่ตัวเลือกอย่างน้อย 2 ข้อ'
  } else if (validOptions.length > 6) {
    errors.options = 'สามารถใส่ตัวเลือกได้สูงสุด 6 ข้อ'
  }
  
  // Validate duration
  if (!pollDuration.value || pollDuration.value < 1) {
    errors.duration = 'กรุณาระบุระยะเวลาโพล'
  }
  
  validationErrors.value = errors
  return Object.keys(errors).length === 0
}

const isValidForm = computed(() => {
  const hasQuestion = pollQuestion.value.trim().length >= 5
  const hasValidOptions = pollOptions.value.filter(opt => opt.trim()).length >= 2
  const hasDuration = pollDuration.value > 0
  return hasQuestion && hasValidOptions && hasDuration
})

// Initialize form with poll data
watch(() => props.show, (newVal) => {
  if (newVal && props.poll) {
    pollQuestion.value = props.poll.question
    pollOptions.value = props.poll.options.map(opt => opt.text)
    pollDuration.value = calculateDurationInHours(props.poll.ends_at)
  }
})

// Calculate duration in hours from ends_at
const calculateDurationInHours = (endsAt: string): number => {
  const now = new Date()
  const endsAtDate = new Date(endsAt)
  const diffMs = endsAtDate.getTime() - now.getTime()
  const diffHours = Math.floor(diffMs / (1000 * 60 * 60))
  
  // Find closest duration option
  const closestOption = durationOptions.reduce((prev, curr) => {
    return Math.abs(curr.value - diffHours) < Math.abs(prev.value - diffHours) ? curr : prev
  })
  
  return closestOption.value
}

// Add option
const addPollOption = () => {
  if (pollOptions.value.length < 6) {
    pollOptions.value.push('')
  }
}

// Remove option
const removePollOption = (index: number) => {
  if (pollOptions.value.length > 2) {
    pollOptions.value.splice(index, 1)
  }
}

// Update option
const updatePollOption = (index: number, value: string) => {
  pollOptions.value[index] = value
}

// Update poll
const updatePollData = async () => {
  if (!validateForm()) {
    return
  }
  
  if (isSubmitting.value) return
  
  isSubmitting.value = true
  
  try {
    const validOptions = pollOptions.value.filter(opt => opt.trim())
    
    const updateData: UpdatePollOptions = {
      question: pollQuestion.value.trim(),
      options: validOptions,
      duration: pollDuration.value,
    }
    
    const response = await updatePoll(props.poll.id, updateData)
    
    if (response.success && response.poll) {
      emit('pollUpdated', response.poll)
      closeModal()
      swal.toast('แก้ไขโพลสำเร็จ!', 'success')
    } else {
      swal.error(response.message || 'ไม่สามารถแก้ไขโพลได้')
    }
  } catch (error) {
    console.error('Error updating poll:', error)
    const errorMsg = error?.data?.message || error?.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่'
    swal.error(errorMsg)
  } finally {
    isSubmitting.value = false
  }
}

// Close modal
const closeModal = () => {
  emit('close')
}

// Handle escape key
const handleEscape = (e: KeyboardEvent) => {
  if (e.key === 'Escape' && props.show) {
    closeModal()
  }
}

// Add escape key listener
watch(() => props.show, (newVal) => {
  if (newVal) {
    document.addEventListener('keydown', handleEscape)
  } else {
    document.removeEventListener('keydown', handleEscape)
  }
})
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div 
        v-if="show" 
        class="fixed inset-0 z-50 flex items-start justify-center pt-10 md:pt-16 overflow-y-auto"
        @click.self="closeModal"
      >
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
        
        <!-- Modal Content -->
        <div class="relative w-full max-w-2xl mx-4 mb-10 bg-white dark:bg-vikinger-dark-100 rounded-xl shadow-vikinger-lg">
          <!-- Header -->
          <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-vikinger-dark-50/30">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-vikinger-purple flex items-center justify-center">
                <Icon icon="fluent:edit-24-regular" class="w-6 h-6 text-white" />
              </div>
              <h2 class="text-xl font-bold text-gray-800 dark:text-white">แก้ไขโพล</h2>
            </div>
            <button @click="closeModal" class="p-2 hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 rounded-full transition-colors">
              <Icon icon="mdi:close" class="w-6 h-6 text-gray-500" />
            </button>
          </div>
          
          <!-- Body -->
          <div class="p-4 max-h-[70vh] overflow-y-auto">
            <!-- Warning Banner -->
            <div class="poll-warning-banner mb-4">
              <div class="flex items-start gap-3">
                <Icon icon="fluent:warning-24-regular" class="w-6 h-6 text-vikinger-orange flex-shrink-0 mt-0.5" />
                <div>
                  <p class="poll-warning-text font-semibold mb-1">
                    ⚠️ การแก้ไขจะรีเซ็ตโหวตทั้งหมด
                  </p>
                  <p class="poll-warning-text text-sm">
                    เมื่อบันทึกการแก้ไข โหวตทั้งหมดจะถูกรีเซ็ตและเริ่มนับใหม่
                  </p>
                </div>
              </div>
            </div>
            
            <!-- Poll Question -->
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                คำถามโพล <span class="text-red-500">*</span>
              </label>
              <input
                v-model="pollQuestion"
                type="text"
                placeholder="คำถามโพล..."
                class="poll-input"
                :class="{ 'border-red-500': validationErrors.question }"
              />
              <p v-if="validationErrors.question" class="text-xs text-red-500 mt-1">
                {{ validationErrors.question }}
              </p>
            </div>
            
            <!-- Poll Options -->
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                ตัวเลือก <span class="text-red-500">*</span>
              </label>
              <div class="space-y-2">
                <div 
                  v-for="(option, index) in pollOptions" 
                  :key="index"
                  class="poll-option-input"
                >
                  <span class="text-sm text-gray-500 w-6">{{ index + 1 }}.</span>
                  <input
                    :model-value="option"
                    @input="updatePollOption(index, ($event.target as HTMLInputElement).value)"
                    type="text"
                    :placeholder="`ตัวเลือกที่ ${index + 1}`"
                    class="poll-input flex-1"
                    :class="{ 'border-red-500': validationErrors[`option_${index}`] }"
                  />
                  <button
                    v-if="pollOptions.length > 2"
                    @click="removePollOption(index)"
                    class="p-1 text-gray-400 hover:text-red-500 transition-colors"
                  >
                    <Icon icon="mdi:close" class="w-5 h-5" />
                  </button>
                </div>
              </div>
              
              <button
                v-if="pollOptions.length < 6"
                @click="addPollOption"
                class="flex items-center gap-1 text-sm text-vikinger-cyan hover:text-vikinger-purple mt-2"
              >
                <Icon icon="mdi:plus" class="w-4 h-4" />
                <span>เพิ่มตัวเลือก (สูงสุด 6 ตัวเลือก)</span>
              </button>
              
              <p v-if="validationErrors.options" class="text-xs text-red-500 mt-1">
                {{ validationErrors.options }}
              </p>
            </div>
            
            <!-- Poll Duration -->
            <div class="mb-4 relative">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                ระยะเวลาโพล <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <button
                  @click="showDurationDropdown = !showDurationDropdown"
                  class="poll-input flex items-center justify-between"
                  :class="{ 'border-red-500': validationErrors.duration }"
                >
                  <div class="flex items-center gap-2">
                    <Icon icon="fluent:clock-24-regular" class="w-5 h-5 text-vikinger-orange" />
                    <span>ระยะเวลาโพล:</span>
                    <span class="font-semibold">{{ selectedDurationLabel }}</span>
                  </div>
                  <Icon icon="mdi:chevron-down" class="w-5 h-5 text-gray-400" />
                </button>
                
                <!-- Duration Dropdown -->
                <Transition name="dropdown">
                  <div 
                    v-if="showDurationDropdown" 
                    class="poll-duration-dropdown"
                  >
                    <button
                      v-for="option in durationOptions"
                      :key="option.value"
                      @click="pollDuration = option.value; showDurationDropdown = false"
                      class="duration-option"
                      :class="{ selected: pollDuration === option.value }"
                    >
                      {{ option.label }}
                    </button>
                  </div>
                </Transition>
              </div>
              
              <p v-if="validationErrors.duration" class="text-xs text-red-500 mt-1">
                {{ validationErrors.duration }}
              </p>
            </div>
          </div>
          
          <!-- Footer -->
          <div class="p-4 border-t border-gray-200 dark:border-vikinger-dark-50/30">
            <div class="flex gap-3">
              <button
                @click="closeModal"
                class="flex-1 py-3 px-4 rounded-xl border border-gray-200 dark:border-vikinger-dark-50/30 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 transition-colors"
              >
                ยกเลิก
              </button>
              <button
                @click="updatePollData"
                :disabled="isSubmitting || !isValidForm"
                class="flex-1 py-3 px-4 rounded-xl bg-vikinger-purple text-white font-semibold hover:bg-vikinger-purple/90 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
              >
                <Icon v-if="isSubmitting" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
                <span>{{ isSubmitting ? 'กำลังบันทึก...' : 'บันทึกการแก้ไข' }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.poll-input {
  width: 100%;
  padding: 12px 16px;
  border-radius: 12px;
  border: 2px solid #e5e7eb;
  background: white;
  color: #1f2937;
  font-size: 15px;
  transition: all 0.2s ease;
}

.dark .poll-input {
  border-color: rgba(255, 255, 255, 0.1);
  background: #282f3f;
  color: #f9fafb;
}

.poll-input:focus {
  outline: none;
  border-color: #615dfa;
  box-shadow: 0 0 0 3px rgba(97, 93, 250, 0.1);
}

.poll-input::placeholder {
  color: #9ca3af;
}

.dark .poll-input::placeholder {
  color: #6b7280;
}

.poll-option-input {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border-radius: 12px;
  border: 2px solid #e5e7eb;
  background: white;
  transition: all 0.2s ease;
}

.dark .poll-option-input {
  border-color: rgba(255, 255, 255, 0.1);
  background: #282f3f;
}

.poll-option-input:focus-within {
  border-color: #23d2e2;
  box-shadow: 0 0 0 3px rgba(35, 210, 226, 0.1);
}

.poll-duration-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  margin-top: 8px;
  width: 100%;
  background: white;
  border-radius: 12px;
  box-shadow: 0 8px 40px rgba(0, 0, 0, 0.15);
  border: 1px solid rgba(0, 0, 0, 0.1);
  overflow: hidden;
  z-index: 10;
}

.dark .poll-duration-dropdown {
  background: #2f3749;
  border-color: rgba(255, 255, 255, 0.1);
}

.duration-option {
  width: 100%;
  padding: 12px 16px;
  background: transparent;
  border: none;
  color: #374151;
  font-size: 15px;
  font-weight: 500;
  text-align: left;
  cursor: pointer;
  transition: all 0.2s ease;
}

.dark .duration-option {
  color: #d1d5db;
}

.duration-option:hover {
  background: rgba(35, 210, 226, 0.05);
}

.duration-option.selected {
  background: rgba(35, 210, 226, 0.1);
  color: #23d2e2;
}

.poll-warning-banner {
  padding: 16px;
  border-radius: 12px;
  background: rgba(255, 149, 0, 0.1);
  border: 1px solid rgba(255, 149, 0, 0.2);
}

.dark .poll-warning-banner {
  background: rgba(255, 149, 0, 0.1);
  border-color: rgba(255, 149, 0, 0.2);
}

.poll-warning-text {
  color: #d97706;
}

.dark .poll-warning-text {
  color: #fbbf24;
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

.modal-enter-active > div:last-child {
  animation: modal-in 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.modal-leave-active > div:last-child {
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

/* Dropdown Animation */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
