<script setup>
import { Icon } from '@iconify/vue'
import { ref, computed, watch } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { usePosts } from '~/composables/usePosts'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close', 'post-created'])

const { $apiFetch } = useNuxtApp()
const authStore = useAuthStore()
const swal = useSweetAlert()
const { getAvatarUrl } = useAvatar()
const { 
  feelings, 
  activityTypes, 
  backgrounds, 
  fetchPostOptions, 
  createPost: createPostApi 
} = usePosts()

// Current user avatar
const currentUserAvatar = computed(() => getAvatarUrl(authStore.user))

// Form state
const postText = ref('')
const isSubmitting = ref(false)
const selectedImages = ref([])
const imageInput = ref(null)

// Image preview URLs
const imagePreviews = computed(() => {
  if (typeof window === 'undefined') return []
  return selectedImages.value.map(file => ({
    file,
    url: URL.createObjectURL(file)
  }))
})

// Feature states
const selectedPrivacy = ref(3)
const locationInput = ref('')
const selectedFeeling = ref(null)
const selectedActivity = ref(null)
const activityText = ref('')
const selectedBackground = ref(null)
const taggedFriends = ref([])
const scheduledDate = ref('')
const commentsDisabled = ref(false)

// UI states
const showFeelingPicker = ref(false)
const showActivityPicker = ref(false)
const showBackgroundPicker = ref(false)
const showLocationInput = ref(false)
const showTagFriends = ref(false)
const showScheduler = ref(false)
const showPrivacyOptions = ref(false)
const friendSearchQuery = ref('')
const friendSearchResults = ref([])
const isSearchingFriends = ref(false)
const hasSearchedFriends = ref(false)

// Load options when modal opens
watch(() => props.show, (newVal) => {
  if (newVal) {
    fetchPostOptions()
  }
})

// Privacy options
const privacyOptions = [
  { value: 3, label: 'สาธารณะ', icon: 'mdi:earth', color: 'text-blue-500' },
  { value: 2, label: 'เพื่อน', icon: 'mdi:account-group', color: 'text-green-500' },
  { value: 1, label: 'เฉพาะฉัน', icon: 'mdi:lock', color: 'text-gray-500' }
]

const currentPrivacy = computed(() => {
  return privacyOptions.find(p => p.value === selectedPrivacy.value) || privacyOptions[0]
})

// Background style
const backgroundStyle = computed(() => {
  if (!selectedBackground.value) return {}
  const bg = selectedBackground.value
  const style = {}
  if (bg.background_gradient) {
    style.background = bg.background_gradient
  } else if (bg.background_color) {
    style.backgroundColor = bg.background_color
  }
  if (bg.text_color) {
    style.color = bg.text_color
  }
  return style
})

// Feeling/Activity display
const feelingActivityText = computed(() => {
  const parts = []
  if (selectedFeeling.value) {
    parts.push(`${selectedFeeling.value.icon} รู้สึก${selectedFeeling.value.name_th || selectedFeeling.value.name}`)
  }
  if (selectedActivity.value) {
    let actText = `${selectedActivity.value.icon} กำลัง${selectedActivity.value.name_th || selectedActivity.value.name}`
    if (activityText.value) actText += ` ${activityText.value}`
    parts.push(actText)
  }
  return parts.join(' — ')
})

// Group by category
const feelingsByCategory = computed(() => {
  const grouped = {}
  feelings.value.forEach(f => {
    if (!grouped[f.category]) grouped[f.category] = []
    grouped[f.category].push(f)
  })
  return grouped
})

const activitiesByCategory = computed(() => {
  const grouped = {}
  activityTypes.value.forEach(a => {
    if (!grouped[a.category]) grouped[a.category] = []
    grouped[a.category].push(a)
  })
  return grouped
})

// Close modal
const closeModal = () => {
  emit('close')
}

// Create post
const createPost = async () => {
  if (!postText.value.trim() && selectedImages.value.length === 0) return
  if (isSubmitting.value) return
  
  if (authStore.user && authStore.user.pp < 180) {
    swal.warning('คุณมีแต้มสะสมไม่พอสำหรับการโพสต์ กรุณาสะสมแต้มสะสมอย่างน้อย 180 แต้ม', 'แต้มไม่พอ')
    return
  }
  
  isSubmitting.value = true
  
  try {
    const options = {
      images: selectedImages.value,
      privacy_settings: selectedPrivacy.value,
      comments_disabled: commentsDisabled.value
    }
    
    if (locationInput.value) {
      options.location_name = locationInput.value
    }
    
    if (selectedFeeling.value) {
      options.feeling = selectedFeeling.value.name
      options.feeling_icon = selectedFeeling.value.icon
    }
    
    if (selectedActivity.value) {
      options.activity_type = selectedActivity.value.name
      if (activityText.value) {
        options.activity_text = activityText.value
      }
    }
    
    if (selectedBackground.value && selectedImages.value.length === 0) {
      options.background_color = selectedBackground.value.background_color
      options.background_gradient = selectedBackground.value.background_gradient
      options.text_color = selectedBackground.value.text_color
    }
    
    if (taggedFriends.value.length > 0) {
      options.tagged_users = taggedFriends.value.map(f => f.id)
    }
    
    if (scheduledDate.value) {
      options.scheduled_at = scheduledDate.value
    }
    
    const response = await createPostApi(postText.value, options)
    
    if (response.success) {
      emit('post-created', response.activity)
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
  locationInput.value = ''
  selectedFeeling.value = null
  selectedActivity.value = null
  activityText.value = ''
  selectedBackground.value = null
  taggedFriends.value = []
  scheduledDate.value = ''
  commentsDisabled.value = false
  showFeelingPicker.value = false
  showActivityPicker.value = false
  showBackgroundPicker.value = false
  showLocationInput.value = false
  showTagFriends.value = false
  showScheduler.value = false
  showPrivacyOptions.value = false
  closeModal()
}

// Image handling
const handleImageSelect = (event) => {
  const files = Array.from(event.target.files)
  selectedImages.value = [...selectedImages.value, ...files].slice(0, 20)
  if (files.length > 0) selectedBackground.value = null
  event.target.value = ''
}

const removeImage = (index) => {
  selectedImages.value.splice(index, 1)
}

const triggerImageInput = () => {
  imageInput.value?.click()
}

// Select helpers
const selectFeeling = (feeling) => {
  selectedFeeling.value = feeling
  showFeelingPicker.value = false
}

const selectActivity = (activity) => {
  selectedActivity.value = activity
  showActivityPicker.value = false
}

const selectBackground = (bg) => {
  if (selectedBackground.value?.id === bg.id) {
    selectedBackground.value = null
  } else {
    selectedBackground.value = bg
    selectedImages.value = []
  }
}

// Friends
let searchTimeout = null
const searchFriends = async () => {
  if (!friendSearchQuery.value.trim()) {
    friendSearchResults.value = []
    hasSearchedFriends.value = false
    return
  }
  
  // Debounce search
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(async () => {
    isSearchingFriends.value = true
    try {
      const response = await $apiFetch(`/api/friends/search?q=${encodeURIComponent(friendSearchQuery.value)}`)
      if (response.success) {
        friendSearchResults.value = response.data || []
      }
    } catch (error) {
      console.error('Error searching friends:', error)
      friendSearchResults.value = []
    } finally {
      isSearchingFriends.value = false
      hasSearchedFriends.value = true
    }
  }, 300)
}

const tagFriend = (friend) => {
  if (!taggedFriends.value.find(f => f.id === friend.id)) {
    taggedFriends.value.push(friend)
  }
  friendSearchQuery.value = ''
  friendSearchResults.value = []
}

const removeTaggedFriend = (friendId) => {
  taggedFriends.value = taggedFriends.value.filter(f => f.id !== friendId)
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
              <h2 class="text-xl font-bold text-gray-800 dark:text-white">สร้างโพสต์</h2>
              <button @click="closeModal" class="p-2 hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 rounded-full">
                <Icon icon="mdi:close" class="w-6 h-6 text-gray-500" />
              </button>
            </div>

            <!-- Body -->
            <div class="p-4 max-h-[70vh] overflow-y-auto">
              <!-- Hidden file input -->
              <input type="file" ref="imageInput" class="hidden" accept="image/*" multiple @change="handleImageSelect" />

                <!-- User & Privacy -->
                <div class="flex items-center gap-3 mb-4">
                  <img :src="currentUserAvatar" class="w-10 h-10 rounded-full object-cover" />
                  <div class="flex-1">
                    <div class="font-medium text-gray-800 dark:text-white">{{ authStore.user?.name }}</div>
                    <button @click="showPrivacyOptions = !showPrivacyOptions" class="flex items-center gap-1 text-xs text-gray-500 hover:text-gray-700">
                      <Icon :icon="currentPrivacy.icon" class="w-3 h-3" />
                      <span>{{ currentPrivacy.label }}</span>
                      <Icon icon="mdi:chevron-down" class="w-3 h-3" />
                    </button>
                  </div>
                </div>

                <!-- Privacy Options -->
                <div v-if="showPrivacyOptions" class="mb-4 p-3 bg-gray-50 dark:bg-vikinger-dark-200 rounded-lg">
                  <div class="space-y-2">
                    <button v-for="option in privacyOptions" :key="option.value" @click="selectedPrivacy = option.value; showPrivacyOptions = false" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white dark:hover:bg-vikinger-dark-100" :class="{ 'bg-white dark:bg-vikinger-dark-100': selectedPrivacy === option.value }">
                      <Icon :icon="option.icon" class="w-5 h-5" :class="option.color" />
                      <span class="text-sm text-gray-700 dark:text-gray-300">{{ option.label }}</span>
                    </button>
                  </div>
                  <label class="flex items-center gap-2 mt-3 pt-3 border-t border-gray-200 dark:border-vikinger-dark-50/30 cursor-pointer">
                    <input type="checkbox" v-model="commentsDisabled" class="rounded text-vikinger-purple" />
                    <span class="text-sm text-gray-600 dark:text-gray-400">ปิดการแสดงความคิดเห็น</span>
                  </label>
                </div>

                <!-- Status badges -->
                <div v-if="selectedFeeling || selectedActivity || locationInput || taggedFriends.length > 0" class="mb-3 flex flex-wrap gap-2">
                  <span v-if="feelingActivityText" class="inline-flex items-center gap-1 px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 rounded-full text-sm">
                    {{ feelingActivityText }}
                    <button @click="selectedFeeling = null; selectedActivity = null; activityText = ''" class="hover:text-red-500"><Icon icon="mdi:close" class="w-3 h-3" /></button>
                  </span>
                  <span v-if="locationInput" class="inline-flex items-center gap-1 px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-full text-sm">
                    <Icon icon="mdi:map-marker" class="w-3 h-3" /> {{ locationInput }}
                    <button @click="locationInput = ''; showLocationInput = false" class="hover:text-red-500"><Icon icon="mdi:close" class="w-3 h-3" /></button>
                  </span>
                  <span v-for="friend in taggedFriends" :key="friend.id" class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full text-sm">
                    {{ friend.name }}
                    <button @click="removeTaggedFriend(friend.id)" class="hover:text-red-500"><Icon icon="mdi:close" class="w-3 h-3" /></button>
                  </span>
                </div>

                <!-- Post Input -->
                <div class="rounded-lg mb-4 min-h-[120px] p-4 transition-all" :class="selectedBackground ? '' : 'bg-gray-50 dark:bg-vikinger-dark-200'" :style="backgroundStyle">
                  <textarea 
                    v-model="postText" 
                    placeholder="คุณกำลังคิดอะไรอยู่?" 
                    :rows="selectedBackground ? 5 : 3" 
                    class="w-full bg-transparent border-none outline-none resize-none placeholder-gray-400" 
                    :class="selectedBackground ? 'text-center text-lg font-medium' : 'text-gray-800 dark:text-white'" 
                    :style="selectedBackground ? { color: selectedBackground.text_color } : {}" 
                    @keydown.ctrl.enter="createPost" 
                    :disabled="isSubmitting" 
                  />
                </div>

                <!-- Images Preview -->
                <div v-if="imagePreviews.length > 0" class="mb-4">
                  <div class="flex flex-wrap gap-2">
                    <div v-for="(preview, index) in imagePreviews" :key="index" class="relative group">
                      <img :src="preview.url" class="w-32 h-32 object-cover rounded-lg border border-gray-200 dark:border-vikinger-dark-50/30" />
                      <button @click="removeImage(index)" class="absolute -top-1 -right-1 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center shadow-sm md:opacity-0 md:group-hover:opacity-100 transition-opacity">
                        <Icon icon="mdi:close" class="w-4 h-4" />
                      </button>
                    </div>
                    <button v-if="imagePreviews.length < 20" @click="triggerImageInput" class="w-32 h-32 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg flex items-center justify-center hover:border-vikinger-purple hover:bg-vikinger-purple/5 transition-all">
                      <Icon icon="mdi:plus" class="w-8 h-8 text-gray-400" />
                    </button>
                  </div>
                  <p class="text-xs text-gray-500 mt-1">{{ imagePreviews.length }}/20 รูป</p>
                </div>

                <!-- Background Picker -->
                <div v-if="showBackgroundPicker && imagePreviews.length === 0" class="mb-4 p-3 bg-gray-50 dark:bg-vikinger-dark-200 rounded-lg">
                  <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">เลือกพื้นหลัง</span>
                    <button @click="showBackgroundPicker = false"><Icon icon="mdi:close" class="w-5 h-5 text-gray-500" /></button>
                  </div>
                  <div class="flex flex-wrap gap-2">
                    <button @click="selectedBackground = null" class="w-10 h-10 rounded-lg border-2 flex items-center justify-center" :class="!selectedBackground ? 'border-vikinger-purple' : 'border-gray-300'">
                      <Icon icon="mdi:format-clear" class="w-5 h-5 text-gray-500" />
                    </button>
                    <button v-for="bg in backgrounds" :key="bg.id" @click="selectBackground(bg)" class="w-10 h-10 rounded-lg border-2 transition-all" :class="selectedBackground?.id === bg.id ? 'border-vikinger-purple scale-110' : 'border-transparent'" :style="{ background: bg.background_gradient || bg.background_color }" />
                  </div>
                </div>

                <!-- Feeling Picker -->
                <div v-if="showFeelingPicker" class="mb-4 p-3 bg-gray-50 dark:bg-vikinger-dark-200 rounded-lg max-h-48 overflow-y-auto">
                  <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">คุณรู้สึกอย่างไร?</span>
                    <button @click="showFeelingPicker = false"><Icon icon="mdi:close" class="w-5 h-5 text-gray-500" /></button>
                  </div>
                  <div v-for="(categoryFeelings, category) in feelingsByCategory" :key="category" class="mb-3">
                    <div class="text-xs text-gray-500 uppercase mb-2">{{ category }}</div>
                    <div class="grid grid-cols-5 gap-2">
                      <button v-for="feeling in categoryFeelings" :key="feeling.id" @click="selectFeeling(feeling)" class="flex flex-col items-center p-2 rounded-lg hover:bg-white dark:hover:bg-vikinger-dark-100" :class="{ 'bg-vikinger-purple/10 ring-1 ring-vikinger-purple': selectedFeeling?.id === feeling.id }">
                        <span class="text-xl">{{ feeling.icon }}</span>
                        <span class="text-xs text-gray-600 dark:text-gray-400 truncate w-full text-center">{{ feeling.name_th || feeling.name }}</span>
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Activity Picker -->
                <div v-if="showActivityPicker" class="mb-4 p-3 bg-gray-50 dark:bg-vikinger-dark-200 rounded-lg max-h-48 overflow-y-auto">
                  <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">คุณกำลังทำอะไร?</span>
                    <button @click="showActivityPicker = false"><Icon icon="mdi:close" class="w-5 h-5 text-gray-500" /></button>
                  </div>
                  <div v-for="(categoryActivities, category) in activitiesByCategory" :key="category" class="mb-3">
                    <div class="text-xs text-gray-500 uppercase mb-2">{{ category }}</div>
                    <div class="grid grid-cols-3 gap-2">
                      <button v-for="activity in categoryActivities" :key="activity.id" @click="selectActivity(activity)" class="flex items-center gap-2 p-2 rounded-lg hover:bg-white dark:hover:bg-vikinger-dark-100 text-left" :class="{ 'bg-vikinger-purple/10 ring-1 ring-vikinger-purple': selectedActivity?.id === activity.id }">
                        <span class="text-lg">{{ activity.icon }}</span>
                        <span class="text-xs text-gray-600 dark:text-gray-400 truncate">{{ activity.name_th || activity.name }}</span>
                      </button>
                    </div>
                  </div>
                  <div v-if="selectedActivity" class="mt-3">
                    <input v-model="activityText" type="text" :placeholder="`${selectedActivity.name_th || selectedActivity.name}...`" class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-vikinger-dark-50/30 bg-white dark:bg-vikinger-dark-100 text-gray-800 dark:text-white text-sm" />
                  </div>
                </div>

                <!-- Location Input -->
                <div v-if="showLocationInput" class="mb-4 p-3 bg-gray-50 dark:bg-vikinger-dark-200 rounded-lg">
                  <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">เพิ่มสถานที่</span>
                    <button @click="showLocationInput = false"><Icon icon="mdi:close" class="w-5 h-5 text-gray-500" /></button>
                  </div>
                  <div class="flex items-center gap-2">
                    <Icon icon="mdi:map-marker" class="w-5 h-5 text-red-500" />
                    <input v-model="locationInput" type="text" placeholder="ค้นหาหรือพิมพ์สถานที่..." class="flex-1 px-3 py-2 rounded-lg border border-gray-200 dark:border-vikinger-dark-50/30 bg-white dark:bg-vikinger-dark-100 text-gray-800 dark:text-white text-sm" />
                  </div>
                </div>

                <!-- Tag Friends -->
                <div v-if="showTagFriends" class="mb-4 p-3 bg-gray-50 dark:bg-vikinger-dark-200 rounded-lg">
                  <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">แท็กเพื่อน</span>
                    <button @click="showTagFriends = false"><Icon icon="mdi:close" class="w-5 h-5 text-gray-500" /></button>
                  </div>
                  
                  <!-- Tagged Friends Display -->
                  <div v-if="taggedFriends.length > 0" class="flex flex-wrap gap-2 mb-3">
                    <div v-for="friend in taggedFriends" :key="friend.id" class="flex items-center gap-1 px-2 py-1 bg-blue-100 dark:bg-blue-900/30 rounded-full">
                      <img :src="getAvatarUrl(friend)" class="w-5 h-5 rounded-full" />
                      <span class="text-xs text-blue-700 dark:text-blue-300">{{ friend.name }}</span>
                      <button @click="removeTaggedFriend(friend.id)" class="ml-1 text-blue-500 hover:text-red-500">
                        <Icon icon="mdi:close" class="w-3 h-3" />
                      </button>
                    </div>
                  </div>
                  
                  <!-- Search Input -->
                  <div class="relative">
                    <Icon icon="mdi:magnify" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input v-model="friendSearchQuery" @input="searchFriends" type="text" placeholder="พิมพ์ชื่อเพื่อนเพื่อค้นหา..." class="w-full pl-9 pr-10 py-2 rounded-lg border border-gray-200 dark:border-vikinger-dark-50/30 bg-white dark:bg-vikinger-dark-100 text-gray-800 dark:text-white text-sm focus:border-blue-400 focus:ring-1 focus:ring-blue-400 transition-all" />
                    <div v-if="isSearchingFriends" class="absolute right-2 top-1/2 -translate-y-1/2">
                      <div class="w-5 h-5 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                    </div>
                  </div>
                  
                  <!-- Loading State -->
                  <div v-if="isSearchingFriends" class="mt-2 p-4 text-center">
                    <div class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                      <div class="w-4 h-4 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                      <span>กำลังค้นหา...</span>
                    </div>
                  </div>
                  
                  <!-- Search Results -->
                  <div v-if="friendSearchResults.length > 0" class="mt-2 space-y-1 max-h-40 overflow-y-auto border border-gray-200 dark:border-vikinger-dark-50/30 rounded-lg bg-white dark:bg-vikinger-dark-100">
                    <button v-for="friend in friendSearchResults" :key="friend.id" @click="tagFriend(friend)" class="w-full flex items-center gap-3 p-2 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 transition-colors" :class="{ 'opacity-50 cursor-not-allowed': taggedFriends.find(f => f.id === friend.id) }" :disabled="!!taggedFriends.find(f => f.id === friend.id)">
                      <img :src="getAvatarUrl(friend)" class="w-8 h-8 rounded-full" />
                      <div class="flex-1 text-left">
                        <span class="text-sm text-gray-800 dark:text-white block">{{ friend.name }}</span>
                        <span v-if="friend.username" class="text-xs text-gray-500">@{{ friend.username }}</span>
                      </div>
                      <Icon v-if="taggedFriends.find(f => f.id === friend.id)" icon="mdi:check" class="w-5 h-5 text-green-500" />
                      <Icon v-else icon="mdi:plus" class="w-5 h-5 text-blue-500" />
                    </button>
                  </div>
                  
                  <!-- No Results -->
                  <div v-else-if="hasSearchedFriends && friendSearchQuery && !isSearchingFriends" class="mt-2 p-3 text-center text-sm text-gray-500 dark:text-gray-400 bg-white dark:bg-vikinger-dark-100 rounded-lg border border-gray-200 dark:border-vikinger-dark-50/30">
                    <Icon icon="mdi:account-search" class="w-8 h-8 mx-auto mb-1 text-gray-400" />
                    <p>ไม่พบเพื่อนที่ชื่อ "{{ friendSearchQuery }}"</p>
                  </div>
                  
                  <!-- Hint -->
                  <p v-if="!friendSearchQuery" class="mt-2 text-xs text-gray-500 dark:text-gray-400">พิมพ์อย่างน้อย 1 ตัวอักษรเพื่อค้นหา</p>
                </div>

                <!-- Schedule -->
                <div v-if="showScheduler" class="mb-4 p-3 bg-gray-50 dark:bg-vikinger-dark-200 rounded-lg">
                  <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">ตั้งเวลาโพสต์</span>
                    <button @click="showScheduler = false; scheduledDate = ''"><Icon icon="mdi:close" class="w-5 h-5 text-gray-500" /></button>
                  </div>
                  <input v-model="scheduledDate" type="datetime-local" class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-vikinger-dark-50/30 bg-white dark:bg-vikinger-dark-100 text-gray-800 dark:text-white text-sm" />
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-wrap gap-2 border-t border-gray-200 dark:border-vikinger-dark-50/30 pt-4">
                  <button @click="triggerImageInput" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-50/30 hover:border-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 transition-all" :disabled="isSubmitting">
                    <Icon icon="mdi:image-multiple" class="w-5 h-5 text-green-500" />
                    <span class="text-sm text-gray-700 dark:text-gray-300">รูปภาพ</span>
                  </button>
                  <button @click="showFeelingPicker = !showFeelingPicker; showActivityPicker = false" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-50/30 hover:border-yellow-400 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 transition-all" :class="{ 'border-yellow-400 bg-yellow-50 dark:bg-yellow-900/20': selectedFeeling }" :disabled="isSubmitting">
                    <Icon icon="mdi:emoticon-happy" class="w-5 h-5 text-yellow-500" />
                    <span class="text-sm text-gray-700 dark:text-gray-300">ความรู้สึก</span>
                  </button>
                  <button @click="showActivityPicker = !showActivityPicker; showFeelingPicker = false" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-50/30 hover:border-orange-400 hover:bg-orange-50 dark:hover:bg-orange-900/20 transition-all" :class="{ 'border-orange-400 bg-orange-50 dark:bg-orange-900/20': selectedActivity }" :disabled="isSubmitting">
                    <Icon icon="mdi:run" class="w-5 h-5 text-orange-500" />
                    <span class="text-sm text-gray-700 dark:text-gray-300">กิจกรรม</span>
                  </button>
                  <button @click="showLocationInput = !showLocationInput" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-50/30 hover:border-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all" :class="{ 'border-red-400 bg-red-50 dark:bg-red-900/20': locationInput }" :disabled="isSubmitting">
                    <Icon icon="mdi:map-marker" class="w-5 h-5 text-red-500" />
                    <span class="text-sm text-gray-700 dark:text-gray-300">สถานที่</span>
                  </button>
                  <button @click="showTagFriends = !showTagFriends" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-50/30 hover:border-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all" :class="{ 'border-blue-400 bg-blue-50 dark:bg-blue-900/20': taggedFriends.length > 0 }" :disabled="isSubmitting">
                    <Icon icon="mdi:account-multiple-plus" class="w-5 h-5 text-blue-500" />
                    <span class="text-sm text-gray-700 dark:text-gray-300">แท็กเพื่อน</span>
                  </button>
                  <button v-if="imagePreviews.length === 0" @click="showBackgroundPicker = !showBackgroundPicker" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-50/30 hover:border-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-all" :class="{ 'border-purple-400 bg-purple-50 dark:bg-purple-900/20': selectedBackground }" :disabled="isSubmitting">
                    <Icon icon="mdi:palette" class="w-5 h-5 text-purple-500" />
                    <span class="text-sm text-gray-700 dark:text-gray-300">พื้นหลัง</span>
                  </button>
                  <button @click="showScheduler = !showScheduler" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-50/30 hover:border-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-all" :class="{ 'border-indigo-400 bg-indigo-50 dark:bg-indigo-900/20': scheduledDate }" :disabled="isSubmitting">
                    <Icon icon="mdi:clock-outline" class="w-5 h-5 text-indigo-500" />
                    <span class="text-sm text-gray-700 dark:text-gray-300">ตั้งเวลา</span>
                  </button>
                </div>
              </div>

              <!-- Footer -->
              <div class="p-4 border-t border-gray-200 dark:border-vikinger-dark-50/30">
                <button @click="createPost" :disabled="isSubmitting || (!postText.trim() && imagePreviews.length === 0)" class="w-full py-3 px-4 bg-gradient-vikinger text-white font-semibold rounded-lg hover:shadow-vikinger transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                  <Icon v-if="isSubmitting" icon="mdi:loading" class="w-5 h-5 animate-spin" />
                  <Icon v-else-if="scheduledDate" icon="mdi:clock-outline" class="w-5 h-5" />
                  <span>{{ isSubmitting ? 'กำลังโพสต์...' : (scheduledDate ? 'ตั้งเวลาโพสต์' : 'โพสต์') }}</span>
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
