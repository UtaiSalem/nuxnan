<script setup>
/**
 * CourseCreatePostModal - Modal for creating posts in a course
 */
import { Icon } from '@iconify/vue'
import { ref, computed, watch } from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  courseId: {
    type: [String, Number],
    required: true
  },
  groupId: {
    type: [String, Number],
    default: null
  }
})

const emit = defineEmits(['close', 'post-created'])

const { user } = useAuth()
const api = useApi()
const swal = useSweetAlert()

// Form state
const postText = ref('')
const isSubmitting = ref(false)
const selectedImages = ref([])
const selectedFiles = ref([])
const imageInput = ref(null)
const fileInput = ref(null)

// Tabs
const activeTab = ref('status')
const tabs = [
  { id: 'status', label: 'สถานะ', icon: 'fluent:edit-24-regular' },
  { id: 'poll', label: 'โพล', icon: 'fluent:poll-24-regular' }
]

// Poll feature
const pollQuestion = ref('')
const pollOptions = ref(['', ''])
const pollDuration = ref(24) // hours
const pollPointsPool = ref(12000) // Default 12000 points
const maxVotes = ref(100) // Default max voters

// Privacy (for course posts, this might be limited)
const selectedPrivacy = ref('course') // 'course' = visible to course members only

// Image preview URLs
const imagePreviews = computed(() => {
  if (typeof window === 'undefined') return []
  return selectedImages.value.map(file => ({
    file,
    url: URL.createObjectURL(file)
  }))
})

// Close modal
const closeModal = () => {
  emit('close')
}

// Create post
const createPost = async () => {
  if (!postText.value.trim() && selectedImages.value.length === 0 && activeTab.value !== 'poll') return
  if (isSubmitting.value) return
  
  // Validate poll if enabled
  if (activeTab.value === 'poll') {
    if (!pollQuestion.value.trim()) {
      swal.warning('กรุณาใส่คำถามสำหรับโพล')
      return
    }
    const validOptions = pollOptions.value.filter(opt => opt.trim())
    if (validOptions.length < 2) {
      swal.warning('กรุณาใส่ตัวเลือกอย่างน้อย 2 ตัวเลือก')
      return
    }
  }
  
  isSubmitting.value = true
  
  try {
    const formData = new FormData()
    formData.append('content', postText.value)
    formData.append('privacy', selectedPrivacy.value)
    if (props.groupId) formData.append('group_id', props.groupId)
    
    // Add images
    selectedImages.value.forEach((file, index) => {
      formData.append(`images[${index}]`, file)
    })
    
    // Add files/attachments
    selectedFiles.value.forEach((file, index) => {
      formData.append(`attachments[${index}]`, file)
    })
    
    // Add poll data if enabled
    if (activeTab.value === 'poll' && pollQuestion.value.trim()) {
      formData.append('poll_question', pollQuestion.value)
      const validOptions = pollOptions.value.filter(opt => opt.trim())
      validOptions.forEach((option, index) => {
        formData.append(`poll_options[${index}]`, option)
      })
      formData.append('poll_duration', pollDuration.value)
      formData.append('poll_points_pool', pollPointsPool.value)
      formData.append('poll_max_votes', maxVotes.value)
    }
    
    const response = await api.post(`/api/courses/${props.courseId}/posts`, formData)
    
    if (response.success || response.data) {
      const newPost = response.data || response.post
      emit('post-created', newPost)
      resetForm()
      swal.toast('สร้างโพสต์สำเร็จ!', 'success')
    } else {
      swal.error(response.message || 'ไม่สามารถสร้างโพสต์ได้')
    }
  } catch (error) {
    console.error('Error creating post:', error)
    let errorMessage = 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง'
    if (error?.data?.message) errorMessage = error.data.message
    swal.error(errorMessage)
  } finally {
    isSubmitting.value = false
  }
}

// Reset form
const resetForm = () => {
  postText.value = ''
  selectedImages.value = []
  selectedFiles.value = []
  activeTab.value = 'status'
  pollQuestion.value = ''
  pollOptions.value = ['', '']
  pollDuration.value = 24
  pollPointsPool.value = 12000
  maxVotes.value = 100
  closeModal()
}

// Image handling
const handleImageSelect = (event) => {
  const files = Array.from(event.target.files)
  selectedImages.value = [...selectedImages.value, ...files].slice(0, 10)
  event.target.value = ''
}

const removeImage = (index) => {
  selectedImages.value.splice(index, 1)
}

const triggerImageInput = () => {
  imageInput.value?.click()
}

// File handling
const handleFileSelect = (event) => {
  const files = Array.from(event.target.files)
  selectedFiles.value = [...selectedFiles.value, ...files].slice(0, 5)
  event.target.value = ''
}

const removeFile = (index) => {
  selectedFiles.value.splice(index, 1)
}

const triggerFileInput = () => {
  fileInput.value?.click()
}

// Poll handling
const resetPoll = () => {
  pollQuestion.value = ''
  pollOptions.value = ['', '']
}

const addPollOption = () => {
  if (pollOptions.value.length < 10) {
    pollOptions.value.push('')
  }
}

const removePollOption = (index) => {
  if (pollOptions.value.length > 2) {
    pollOptions.value.splice(index, 1)
  }
}

// Format file size
const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div 
        v-if="show" 
        class="fixed inset-0 bg-black/50 z-50 flex items-start justify-center pt-10 md:pt-16 overflow-y-auto backdrop-blur-sm"
        @click.self="closeModal"
      >
        <div class="w-full max-w-2xl mx-4 mb-10 modal-content">
          <div class="bg-white dark:bg-vikinger-dark-300 rounded-xl shadow-2xl">
            <!-- Header -->
            <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-vikinger-dark-50/30">
              <div class="flex items-center gap-2">
                <Icon icon="fluent:book-24-regular" class="w-5 h-5 text-blue-500" />
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">โพสต์ในรายวิชา</h2>
              </div>
              <button @click="closeModal" class="p-2 hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 rounded-full">
                <Icon icon="fluent:dismiss-24-regular" class="w-6 h-6 text-gray-500" />
              </button>
            </div>

            <!-- Body -->
            <div class="p-4 max-h-[70vh] overflow-y-auto">
              <!-- Hidden file inputs -->
              <input type="file" ref="imageInput" class="hidden" accept="image/*" multiple @change="handleImageSelect" />
              <input type="file" ref="fileInput" class="hidden" multiple @change="handleFileSelect" />

              <!-- User Info -->
              <div class="flex items-center gap-3 mb-4">
                <img 
                  :src="user?.avatar || '/images/default-avatar.png'" 
                  class="w-10 h-10 rounded-full object-cover" 
                />
                <div class="flex-1">
                  <div class="font-medium text-gray-800 dark:text-white">{{ user?.name }}</div>
                  <div class="flex items-center gap-1 text-xs text-gray-500">
                    <Icon icon="fluent:people-community-24-regular" class="w-3 h-3" />
                    <span>โพสต์ในรายวิชา</span>
                  </div>
                </div>
              </div>

              <!-- Tabs -->
              <div class="flex border-b border-gray-200 dark:border-vikinger-dark-50/30 mb-4">
                <button 
                  v-for="tab in tabs" 
                  :key="tab.id"
                  @click="activeTab = tab.id"
                  class="flex-1 pb-3 text-sm font-medium flex items-center justify-center gap-2 border-b-2 transition-colors relative"
                  :class="activeTab === tab.id ? 'text-blue-600 border-blue-600 dark:text-blue-400 dark:border-blue-400' : 'text-gray-500 dark:text-gray-400 border-transparent hover:text-gray-700 dark:hover:text-gray-300'"
                >
                  <Icon :icon="tab.icon" class="w-5 h-5" />
                  <span>{{ tab.label }}</span>
                </button>
              </div>

              <!-- Status Tab -->
              <div v-show="activeTab === 'status'">
                <!-- Post Input -->
                <div class="rounded-lg mb-4 min-h-[120px] p-4 bg-gray-50 dark:bg-vikinger-dark-200">
                  <textarea 
                    v-model="postText" 
                    placeholder="แชร์ความรู้หรือถามคำถามในรายวิชานี้..." 
                    rows="4"
                    class="w-full bg-transparent border-none outline-none resize-none text-gray-800 dark:text-white placeholder-gray-400" 
                    @keydown.ctrl.enter="createPost" 
                    :disabled="isSubmitting" 
                  />
                </div>


              <!-- Images Preview -->
              <div v-if="imagePreviews.length > 0" class="mb-4">
                <div class="flex flex-wrap gap-2">
                  <div v-for="(preview, index) in imagePreviews" :key="index" class="relative group">
                    <img :src="preview.url" class="w-24 h-24 object-cover rounded-lg border border-gray-200 dark:border-vikinger-dark-50/30" />
                    <button 
                      @click="removeImage(index)" 
                      class="absolute -top-1 -right-1 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center shadow-sm md:opacity-0 md:group-hover:opacity-100 transition-opacity"
                    >
                      <Icon icon="fluent:dismiss-24-regular" class="w-4 h-4" />
                    </button>
                  </div>
                  <button 
                    v-if="imagePreviews.length < 10" 
                    @click="triggerImageInput" 
                    class="w-24 h-24 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg flex items-center justify-center hover:border-vikinger-purple hover:bg-vikinger-purple/5 transition-all"
                  >
                    <Icon icon="fluent:add-24-regular" class="w-8 h-8 text-gray-400" />
                  </button>
                </div>
                <p class="text-xs text-gray-500 mt-1">{{ imagePreviews.length }}/10 รูป</p>
              </div>

              <!-- Files Preview -->
              <div v-if="selectedFiles.length > 0" class="mb-4 space-y-2">
                <div 
                  v-for="(file, index) in selectedFiles" 
                  :key="index"
                  class="flex items-center justify-between p-3 bg-gray-50 dark:bg-vikinger-dark-200 rounded-lg"
                >
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                      <Icon icon="fluent:document-24-regular" class="w-5 h-5 text-blue-500" />
                    </div>
                    <div>
                      <p class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate max-w-[200px]">
                        {{ file.name }}
                      </p>
                      <p class="text-xs text-gray-500">{{ formatFileSize(file.size) }}</p>
                    </div>
                  </div>
                  <button 
                    @click="removeFile(index)"
                    class="p-1 hover:bg-gray-200 dark:hover:bg-vikinger-dark-100 rounded-full"
                  >
                    <Icon icon="fluent:dismiss-24-regular" class="w-4 h-4 text-gray-400 hover:text-red-500" />
                  </button>
                </div>
              </div>

              </div>

              <!-- Poll Tab -->
              <div v-show="activeTab === 'poll'" class="mb-4">
                <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-700/30">
                <div class="flex items-center justify-between mb-3">
                  <div class="flex items-center gap-2">
                    <Icon icon="fluent:poll-24-regular" class="w-5 h-5 text-amber-600" />
                    <span class="font-medium text-amber-700 dark:text-amber-300">รายละเอียดโพล</span>
                  </div>
                </div>
                
                <!-- Poll Question -->
                <input 
                  v-model="pollQuestion"
                  type="text"
                  placeholder="คำถามโพล..."
                  class="w-full px-3 py-2 mb-3 rounded-lg border border-amber-200 dark:border-amber-700/50 bg-white dark:bg-vikinger-dark-200 text-gray-800 dark:text-white"
                />
                
                <!-- Poll Options -->
                <div class="space-y-3 mb-4">
                  <div v-for="(option, index) in pollOptions" :key="index" class="flex items-center gap-3 p-3 bg-white dark:bg-vikinger-dark-100 rounded-xl border border-gray-200 dark:border-vikinger-dark-50/30">
                    <div class="w-7 h-7 rounded-full bg-vikinger-purple text-white flex items-center justify-center text-xs font-bold flex-shrink-0">
                      {{ index + 1 }}
                    </div>
                    <input 
                      v-model="pollOptions[index]"
                      type="text"
                      :placeholder="`ตัวเลือกที่ ${index + 1}`"
                      class="flex-1 bg-transparent border-none outline-none text-gray-800 dark:text-white text-sm"
                    />
                    <button 
                      v-if="pollOptions.length > 2"
                      @click="removePollOption(index)"
                      class="p-1 text-gray-400 hover:text-red-500 transition-colors"
                    >
                      <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5" />
                    </button>
                  </div>
                </div>
                
                <button 
                  v-if="pollOptions.length < 10"
                  @click="addPollOption"
                  class="w-full h-11 border-2 border-dashed border-gray-200 dark:border-vikinger-dark-50/20 rounded-xl flex items-center justify-center gap-2 text-sm text-gray-500 hover:border-vikinger-cyan hover:text-vikinger-cyan transition-all mb-4"
                >
                  <Icon icon="fluent:add-24-regular" class="w-5 h-5" />
                  <span>เพิ่มตัวเลือก (สูงสุด 10 ตัวเลือก)</span>
                </button>
                
                <!-- Poll Duration -->
                <div class="mt-3 pt-3 border-t border-amber-200 dark:border-amber-700/30">
                  <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <Icon icon="fluent:clock-24-regular" class="w-4 h-4" />
                    <span>ระยะเวลาโพล:</span>
                    <select 
                      v-model="pollDuration"
                      class="px-2 py-1 rounded border border-gray-200 dark:border-vikinger-dark-50/30 bg-white dark:bg-vikinger-dark-100 text-sm"
                    >
                      <option :value="1">1 ชั่วโมง</option>
                      <option :value="6">6 ชั่วโมง</option>
                      <option :value="12">12 ชั่วโมง</option>
                      <option :value="24">1 วัน</option>
                      <option :value="72">3 วัน</option>
                      <option :value="168">1 สัปดาห์</option>
                    </select>
                  </label>
                </div>
              </div>

                <!-- Settings Row (Points, Max Votes) -->
                <div class="grid grid-cols-2 gap-4 mt-6">
                  <!-- Points Pool -->
                  <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-wider">
                      แต้มรางวัลรวม
                    </label>
                    <div class="relative flex items-center">
                      <div class="absolute left-3 text-vikinger-cyan">
                        <Icon icon="mdi:piggy-bank" class="w-4 h-4" />
                      </div>
                      <input
                        v-model.number="pollPointsPool"
                        type="number"
                        min="0"
                        step="100"
                        class="w-full pl-9 pr-3 py-2.5 bg-gray-50 dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-50/10 rounded-xl text-sm focus:ring-1 focus:ring-vikinger-cyan outline-none"
                        placeholder="0"
                      />
                    </div>
                  </div>

                  <!-- Max Votes -->
                  <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-wider">
                      จำนวนคนโหวตสูงสุด
                    </label>
                    <div class="relative flex items-center">
                      <div class="absolute left-3 text-vikinger-orange">
                        <Icon icon="mdi:account-group" class="w-4 h-4" />
                      </div>
                      <input
                        v-model.number="maxVotes"
                        type="number"
                        min="1"
                        class="w-full pl-9 pr-3 py-2.5 bg-gray-50 dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-50/10 rounded-xl text-sm focus:ring-1 focus:ring-vikinger-cyan outline-none"
                        placeholder="100"
                      />
                    </div>
                  </div>
                </div>

                <!-- Points Summary Box -->
                <div class="mt-4 p-4 bg-vikinger-cyan/5 dark:bg-vikinger-cyan/10 border border-vikinger-cyan/20 rounded-xl">
                  <div class="flex items-start gap-3">
                    <Icon icon="fluent:info-24-regular" class="w-6 h-6 text-vikinger-cyan flex-shrink-0 mt-0.5" />
                    <div class="text-sm dark:text-gray-200 w-full">
                      <p class="font-bold text-vikinger-cyan mb-2">สรุปแต้มที่ต้องใช้:</p>
                      <ul class="space-y-1.5">
                        <li class="flex justify-between items-center text-gray-600 dark:text-gray-400">
                          <span>ค่าธรรมเนียมสร้างโพล</span>
                          <span class="font-semibold">180 แต้ม</span>
                        </li>
                        <li class="flex justify-between items-center text-gray-600 dark:text-gray-400">
                          <span>แต้มรางวัลสำหรับคนโหวต</span>
                          <span class="font-semibold">{{ pollPointsPool }} แต้ม</span>
                        </li>
                        <li class="flex justify-between items-center pt-2 border-t border-vikinger-cyan/20 font-bold text-gray-800 dark:text-white mt-1">
                          <span>รวมทั้งหมด</span>
                          <span>{{ 180 + pollPointsPool }} แต้ม</span>
                        </li>
                      </ul>
                      <div class="mt-3 flex items-center gap-2 text-xs text-green-600 dark:text-green-400 font-medium">
                        <Icon icon="fluent:gift-24-regular" class="w-4 h-4" />
                        <span>ผู้ร่วมโหวตจะได้รับแต้มรางวัลโดยอัตโนมัติ!</span>
                      </div>
                      <p v-if="user && user.pp < (180 + pollPointsPool)" class="text-xs text-red-500 font-bold mt-2">
                        ! คุณมีแต้มไม่เพียงพอ (ปัจจุบัน {{ user.pp }} แต้ม)
                      </p>
                    </div>
                  </div>
                </div>

              </div>

              <!-- Action Buttons (Only for Status Tab) -->
              <div v-if="activeTab === 'status'" class="flex flex-wrap gap-2 border-t border-gray-200 dark:border-vikinger-dark-50/30 pt-4">
                <button 
                  @click="triggerImageInput" 
                  class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-50/30 hover:border-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 transition-all" 
                  :disabled="isSubmitting"
                >
                  <Icon icon="fluent:image-24-regular" class="w-5 h-5 text-green-500" />
                  <span class="text-sm text-gray-700 dark:text-gray-300">รูปภาพ</span>
                </button>
                
                <button 
                  @click="triggerFileInput" 
                  class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-50/30 hover:border-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all" 
                  :disabled="isSubmitting"
                >
                  <Icon icon="fluent:attach-24-regular" class="w-5 h-5 text-blue-500" />
                  <span class="text-sm text-gray-700 dark:text-gray-300">ไฟล์แนบ</span>
                </button>
                
              </div>
            </div>

            <!-- Footer -->
            <div class="p-4 border-t border-gray-200 dark:border-vikinger-dark-50/30">
              <button 
                @click="createPost" 
                :disabled="isSubmitting || (activeTab === 'status' && !postText.trim() && imagePreviews.length === 0) || (activeTab === 'poll' && !pollQuestion.trim())" 
                class="w-full py-3 px-4 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
              >
                <Icon v-if="isSubmitting" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
                <span>{{ isSubmitting ? 'กำลังโพสต์...' : 'โพสต์' }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-enter-active {
  transition: opacity 0.3s ease;
}

.modal-leave-active {
  transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .modal-content {
  animation: modal-in 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.modal-leave-active .modal-content {
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
