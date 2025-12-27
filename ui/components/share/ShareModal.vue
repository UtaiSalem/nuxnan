<script setup>
import { Icon } from '@iconify/vue'
import { ref, computed } from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    required: true
  },
  post: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'share'])

// Share form data
const shareComment = ref('')
const privacy = ref('public')
const taggedFriends = ref([])
const location = ref('')

// UI states
const showPrivacyMenu = ref(false)
const showFriendsPicker = ref(false)
const showLocationPicker = ref(false)

// Privacy options
const privacyOptions = [
  { value: 'public', label: 'สาธารณะ', icon: 'fluent:globe-24-regular', desc: 'ทุกคนสามารถเห็นได้' },
  { value: 'friends', label: 'เพื่อน', icon: 'fluent:people-24-regular', desc: 'เฉพาะเพื่อนเท่านั้น' },
  { value: 'private', label: 'ส่วนตัว', icon: 'fluent:lock-closed-24-regular', desc: 'เฉพาะคุณเท่านั้น' }
]

const selectedPrivacy = computed(() => {
  return privacyOptions.find(opt => opt.value === privacy.value) || privacyOptions[0]
})

// Handle share submission
const handleShare = () => {
  emit('share', {
    share_comment: shareComment.value.trim() || null,
    privacy: privacy.value,
    tagged_friends: taggedFriends.value,
    location: location.value || null
  })
  closeModal()
}

// Close and reset
const closeModal = () => {
  shareComment.value = ''
  privacy.value = 'public'
  taggedFriends.value = []
  location.value = ''
  emit('close')
}

// Post preview content
const previewContent = computed(() => {
  const content = props.post.content || props.post.description || ''
  return content.length > 200 ? content.substring(0, 200) + '...' : content
})

const previewImage = computed(() => {
  if (props.post.imagesResources && props.post.imagesResources.length) {
    return props.post.imagesResources[0].url
  }
  if (props.post.images && props.post.images.length) {
    return props.post.images[0].url || props.post.images[0]
  }
  return null
})
</script>

<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal"></div>
        
        <!-- Modal -->
        <div class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto bg-white dark:bg-vikinger-dark-100 rounded-2xl shadow-2xl">
          <!-- Header -->
          <div class="sticky top-0 z-10 flex items-center justify-between p-4 border-b border-gray-200 dark:border-vikinger-dark-50/30 bg-white dark:bg-vikinger-dark-100">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white">แชร์โพสต์</h3>
            <button @click="closeModal" class="p-2 hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 rounded-lg transition-colors">
              <Icon icon="fluent:dismiss-24-regular" class="w-6 h-6 text-gray-600 dark:text-gray-300" />
            </button>
          </div>

          <!-- Content -->
          <div class="p-4 space-y-4">
            <!-- Share Comment -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                เขียนบางอย่างเกี่ยวกับโพสต์นี้... (ไม่บังคับ)
              </label>
              <textarea
                v-model="shareComment"
                rows="3"
                placeholder="บอกเพื่อนๆ ว่าทำไมคุณถึงแชร์โพสต์นี้..."
                class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-vikinger-dark-50/30 bg-white dark:bg-vikinger-dark-200 text-gray-800 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-vikinger-purple/30 focus:border-vikinger-purple transition-all resize-none"
              ></textarea>
            </div>

            <!-- Options -->
            <div class="space-y-2">
              <!-- Privacy Setting -->
              <div class="relative">
                <button
                  @click="showPrivacyMenu = !showPrivacyMenu"
                  class="w-full flex items-center justify-between p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 transition-colors"
                >
                  <div class="flex items-center gap-3">
                    <Icon :icon="selectedPrivacy.icon" class="w-5 h-5 text-vikinger-purple" />
                    <div class="text-left">
                      <p class="text-sm font-medium text-gray-800 dark:text-white">{{ selectedPrivacy.label }}</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">{{ selectedPrivacy.desc }}</p>
                    </div>
                  </div>
                  <Icon icon="fluent:chevron-down-24-regular" class="w-5 h-5 text-gray-400" />
                </button>

                <!-- Privacy Menu -->
                <Transition name="dropdown">
                  <div v-if="showPrivacyMenu" class="absolute top-full left-0 right-0 mt-2 bg-white dark:bg-vikinger-dark-100 rounded-xl shadow-lg border border-gray-200 dark:border-vikinger-dark-50/30 overflow-hidden z-20">
                    <button
                      v-for="option in privacyOptions"
                      :key="option.value"
                      @click="privacy = option.value; showPrivacyMenu = false"
                      class="w-full flex items-center gap-3 p-3 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 transition-colors"
                      :class="{ 'bg-vikinger-purple/10': privacy === option.value }"
                    >
                      <Icon :icon="option.icon" class="w-5 h-5" :class="privacy === option.value ? 'text-vikinger-purple' : 'text-gray-500'" />
                      <div class="text-left">
                        <p class="text-sm font-medium text-gray-800 dark:text-white">{{ option.label }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ option.desc }}</p>
                      </div>
                      <Icon v-if="privacy === option.value" icon="fluent:checkmark-24-filled" class="w-5 h-5 text-vikinger-purple ml-auto" />
                    </button>
                  </div>
                </Transition>
              </div>

              <!-- Tag Friends (Placeholder) -->
              <button class="w-full flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 transition-colors opacity-50 cursor-not-allowed">
                <Icon icon="fluent:people-add-24-regular" class="w-5 h-5 text-gray-400" />
                <div class="text-left">
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400">แท็กเพื่อน</p>
                  <p class="text-xs text-gray-500 dark:text-gray-500">เร็วๆ นี้</p>
                </div>
              </button>

              <!-- Add Location (Placeholder) -->
              <button class="w-full flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 transition-colors opacity-50 cursor-not-allowed">
                <Icon icon="fluent:location-24-regular" class="w-5 h-5 text-gray-400" />
                <div class="text-left">
                  <p class="text-sm font-medium text-gray-600 dark:text-gray-400">เพิ่มสถานที่</p>
                  <p class="text-xs text-gray-500 dark:text-gray-500">เร็วๆ นี้</p>
                </div>
              </button>
            </div>

            <!-- Post Preview -->
            <div class="border border-gray-200 dark:border-vikinger-dark-50/30 rounded-xl p-3 bg-gray-50 dark:bg-vikinger-dark-200/50">
              <div class="flex gap-3">
                <!-- Author -->
                <img :src="post.author?.avatar || post.user?.avatar || 'https://i.pravatar.cc/150'" class="w-10 h-10 rounded-full object-cover flex-shrink-0" alt="" />
                <div class="flex-1 min-w-0">
                  <p class="font-semibold text-sm text-gray-800 dark:text-white">{{ post.author?.username || post.user?.username || 'ผู้ใช้' }}</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-3">{{ previewContent }}</p>
                </div>
                <!-- Preview Image -->
                <img v-if="previewImage" :src="previewImage" class="w-20 h-20 rounded-lg object-cover flex-shrink-0" alt="" />
              </div>
            </div>

            <!-- Points Info -->
            <div class="flex items-center gap-2 p-3 bg-gradient-to-r from-vikinger-purple/10 to-vikinger-cyan/10 rounded-xl">
              <Icon icon="fluent:info-24-regular" class="w-5 h-5 text-vikinger-purple flex-shrink-0" />
              <p class="text-sm text-gray-700 dark:text-gray-300">
                การแชร์จะใช้ <span class="font-bold text-vikinger-purple">36 แต้ม</span> 
                และเจ้าของโพสต์จะได้รับ <span class="font-bold text-vikinger-cyan">18 แต้ม</span>
              </p>
            </div>
          </div>

          <!-- Footer -->
          <div class="sticky bottom-0 flex items-center gap-3 p-4 border-t border-gray-200 dark:border-vikinger-dark-50/30 bg-white dark:bg-vikinger-dark-100">
            <button
              @click="closeModal"
              class="flex-1 px-6 py-3 rounded-xl border border-gray-300 dark:border-vikinger-dark-50/30 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 transition-all"
            >
              ยกเลิก
            </button>
            <button
              @click="handleShare"
              class="flex-1 px-6 py-3 rounded-xl bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white font-medium hover:shadow-lg transition-all duration-300 hover:scale-105"
            >
              <span class="flex items-center justify-center gap-2">
                <Icon icon="fluent:share-24-filled" class="w-5 h-5" />
                แชร์เลย - 36 แต้ม
              </span>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

.modal-fade-enter-active .relative,
.modal-fade-leave-active .relative {
  transition: transform 0.3s ease;
}

.modal-fade-enter-from .relative,
.modal-fade-leave-to .relative {
  transform: scale(0.95);
}

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
