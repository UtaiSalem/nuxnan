<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'
import { usePolls } from '~/composables/usePolls'
import { useSweetAlert } from '~/composables/useSweetAlert'
import { useAvatar } from '~/composables/useAvatar'
import type { CreatePollOptions } from '~/composables/usePolls'

interface Props {
  show: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  close: []
  pollCreated: (poll: any) => void
}>()

const authStore = useAuthStore()
const { createPoll, calculateTimeRemaining } = usePolls()
const { getAvatarUrl } = useAvatar()
const swal = useSweetAlert()

// Current user avatar
const currentUserAvatar = computed(() => getAvatarUrl(authStore.user))

// Form state
const pollQuestion = ref('')
const pollOptions = ref(['', ''])
const allowMultiple = ref(false)
const pollDuration = ref(24) // hours
const isSubmitting = ref(false)

// UI states
const showDurationDropdown = ref(false)
const validationErrors = ref<Record<string, string>>({})

// Privacy options
const selectedPrivacy = ref(3)
const privacyOptions = [
  { value: 3, label: 'สาธารณะ', icon: 'mdi:earth' },
  { value: 2, label: 'เพื่อน', icon: 'mdi:account-group' },
  { value: 1, label: 'เฉพาะฉัน', icon: 'mdi:lock' },
]

const currentPrivacy = computed(() => {
  return privacyOptions.find(p => p.value === selectedPrivacy.value) || privacyOptions[0]
})

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
  } else {
    pollOptions.value.forEach((opt, index) => {
      if (!opt.trim() && index < 2) {
        errors[`option_${index}`] = 'กรุณาใส่ข้อความในแต่ละตัวเลือก'
      }
    })
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

// Create poll
const createNewPoll = async () => {
  if (!validateForm()) {
    return
  }
  
  if (isSubmitting.value) return
  
  // Check points
  if (authStore.user && authStore.user.pp < 180) {
    swal.warning('คุณมีแต้มสะสมไม่พอสำหรับการสร้างโพล กรุณาสะสมแต้มอย่างน้อย 180 แต้ม', 'แต้มไม่พอ')
    return
  }
  
  isSubmitting.value = true
  
  try {
    const validOptions = pollOptions.value.filter(opt => opt.trim())
    
    const pollData: CreatePollOptions = {
      question: pollQuestion.value.trim(),
      options: validOptions,
      duration: pollDuration.value,
      is_multiple: allowMultiple.value,
      privacy_settings: selectedPrivacy.value,
    }
    
    const response = await createPoll(pollData)
    
    if (response.success && response.poll) {
      emit('pollCreated', response.poll)
      resetForm()
      swal.toast('สร้างโพลสำเร็จ!', 'success')
    } else {
      swal.error(response.message || 'ไม่สามารถสร้างโพลได้')
    }
  } catch (error) {
    console.error('Error creating poll:', error)
    const errorMsg = error?.data?.message || error?.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง'
    swal.error(errorMsg)
  } finally {
    isSubmitting.value = false
  }
}

// Reset form
const resetForm = () => {
  pollQuestion.value = ''
  pollOptions.value = ['', '']
  allowMultiple.value = false
  pollDuration.value = 24
  selectedPrivacy.value = 3
  validationErrors.value = {}
  closeModal()
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
              <div class="w-10 h-10 rounded-full bg-gradient-vikinger flex items-center justify-center">
                <Icon icon="fluent:poll-24-regular" class="w-6 h-6 text-white" />
              </div>
              <h2 class="text-xl font-bold text-gray-800 dark:text-white">สร้างโพล</h2>
            </div>
            <button @click="closeModal" class="p-2 hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 rounded-full transition-colors">
              <Icon icon="mdi:close" class="w-6 h-6 text-gray-500" />
            </button>
          </div>
          
          <!-- Body -->
          <div class="p-4 max-h-[70vh] overflow-y-auto">
            <!-- User & Privacy -->
            <div class="flex items-center gap-3 mb-4">
              <img :src="currentUserAvatar" class="w-10 h-10 rounded-full object-cover" />
              <div class="flex-1">
                <div class="font-medium text-gray-800 dark:text-white">{{ authStore.user?.name }}</div>
                <button @click="showDurationDropdown = !showDurationDropdown" class="flex items-center gap-1 text-xs text-gray-500 hover:text-gray-700">
                  <Icon :icon="currentPrivacy.icon" class="w-3 h-3" />
                  <span>{{ currentPrivacy.label }}</span>
                  <Icon icon="mdi:chevron-down" class="w-3 h-3" />
                </button>
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
            
            <!-- Multiple Choice Toggle -->
            <div class="mb-4">
              <label class="flex items-center gap-3 cursor-pointer">
                <input
                  v-model="allowMultiple"
                  type="checkbox"
                  class="w-5 h-5 rounded text-vikinger-purple focus:ring-vikinger-purple"
                />
                <span class="text-sm text-gray-700 dark:text-gray-300">
                  อนุญาตให้ผู้ใช้เลือกหลายคำตอบ
                </span>
              </label>
            </div>
          </div>
          
          <!-- Footer -->
          <div class="p-4 border-t border-gray-200 dark:border-vikinger-dark-50/30">
            <button
              @click="createNewPoll"
              :disabled="isSubmitting || !isValidForm"
              class="poll-submit-btn"
            >
              <Icon v-if="isSubmitting" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
              <span>{{ isSubmitting ? 'กำลังสร้างโพล...' : 'สร้างโพล' }}</span>
            </button>
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

.poll-submit-btn {
  width: 100%;
  padding: 12px 16px;
  background: linear-gradient(90deg, #615dfa 0%, #23d2e2 100%);
  color: white;
  font-size: 16px;
  font-weight: 600;
  border-radius: 12px;
  border: none;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.poll-submit-btn:hover:not(:disabled) {
  box-shadow: 0 8px 40px rgba(97, 93, 250, 0.35);
  transform: scale(1.02);
}

.poll-submit-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.poll-submit-btn:disabled:hover {
  transform: none;
  box-shadow: none;
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

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .poll-submit-btn:hover:not(:disabled) {
    transform: none;
  }
}
</style>
