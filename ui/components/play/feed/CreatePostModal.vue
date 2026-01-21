<script setup>
import { Icon } from '@iconify/vue'
import { ref, computed, watch } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { usePosts } from '~/composables/usePosts'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  initialTab: {
    type: String,
    default: 'status'
  },
  context: {
    type: String,
    default: 'newsfeed', // 'newsfeed', 'academy', 'course'
    validator: (value) => ['newsfeed', 'academy', 'course'].includes(value)
  },
  contextId: {
    type: Number,
    default: null
  },
  contextName: {
    type: String,
    default: ''
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

// Modal title based on context
const modalTitle = computed(() => {
  if (props.context === 'academy' && props.contextName) {
    return `โพสต์ใน ${props.contextName}`
  }
  if (props.context === 'course' && props.contextName) {
    return `โพสต์ในรายวิชา ${props.contextName}`
  }
  return 'สร้างโพสต์'
})

// Create academy post
const createAcademyPost = async (content, options) => {
  const formData = new FormData()
  formData.append('content', content)
  
  if (options.images && options.images.length > 0) {
    options.images.forEach((image, index) => {
      formData.append(`images[${index}]`, image)
    })
  }
  
  if (options.privacy_settings !== undefined) {
    formData.append('privacy_settings', String(options.privacy_settings))
  }
  
  return await $apiFetch(`/api/academies/${props.contextId}/posts`, {
    method: 'POST',
    body: formData,
  })
}

// Create course post
const createCoursePost = async (content, options) => {
  const formData = new FormData()
  formData.append('content', content)
  
  if (options.images && options.images.length > 0) {
    options.images.forEach((image, index) => {
      formData.append(`images[${index}]`, image)
    })
  }
  
  return await $apiFetch(`/api/courses/${props.contextId}/posts`, {
    method: 'POST',
    body: formData,
  })
}

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

// Tabs
const activeTab = ref('status')
const tabs = [
  { id: 'status', label: 'สถานะ', icon: 'mdi:card-text-outline' },
  { id: 'poll', label: 'โพล', icon: 'mdi:poll' }
]

// Poll state
const pollQuestion = ref('')
const pollOptions = ref(['', ''])
const pollDuration = ref(24) // hours
const pollPointsPool = ref(12000)
const maxVotes = ref(100)
const pollEndDate = computed(() => {
  const date = new Date()
  date.setDate(date.getDate() + parseInt(pollDuration.value))
  return date.toISOString()
})

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

// Load options when modal opens
watch(() => props.show, (newVal) => {
  if (newVal) {
    activeTab.value = props.initialTab || 'status'
    fetchPostOptions()
  } else {
    resetForm()
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
  // For status tab, require text or images. For poll tab, we check pollQuestion later.
  if (activeTab.value === 'status' && !postText.value.trim() && selectedImages.value.length === 0) return
  if (isSubmitting.value) return
  
  // Skip point check for academy/course posts (handled differently)
  if (props.context === 'newsfeed' && authStore.user && authStore.user.pp < 180) {
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

    if (activeTab.value === 'poll') {
      if (!pollQuestion.value.trim()) {
        swal.warning('กรุณาระบุคำถามสำหรับโพล')
        isSubmitting.value = false
        return
      }
      if (pollOptions.value.some(o => !o.trim())) {
        swal.warning('กรุณาระบุตัวเลือกให้ครบถ้วน')
        isSubmitting.value = false
        return
      }
      
      options.is_poll = true
      options.poll_title = pollQuestion.value
      options.poll_options = pollOptions.value.filter(opt => opt.trim())
      options.poll_duration = pollDuration.value
      options.poll_points_pool = pollPointsPool.value
      options.poll_max_votes = maxVotes.value
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
    
    let response
    
    // Handle different contexts
    if (props.context === 'academy' && props.contextId) {
      response = await createAcademyPost(postText.value, options)
    } else if (props.context === 'course' && props.contextId) {
      response = await createCoursePost(postText.value, options)
    } else {
      response = await createPostApi(postText.value, options)
    }
    
    if (response.success) {
      emit('post-created', response.activity || response.post)
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
  
  // Reset poll
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
              <div class="flex items-center gap-2">
                <Icon :icon="context === 'academy' ? 'fluent:building-24-regular' : context === 'course' ? 'fluent:book-24-regular' : 'fluent:calendar-ltr-24-regular'" class="w-5 h-5 text-vikinger-purple" />
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">{{ modalTitle }}</h2>
              </div>
              <button @click="closeModal" class="p-2 hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 rounded-full">
                <Icon icon="fluent:dismiss-24-regular" class="w-6 h-6 text-gray-500" />
              </button>
            </div>

            <!-- Body -->
            <div class="p-4 max-h-[75vh] overflow-y-auto">
              
              <!-- User Info (matching Course style) -->
              <div class="flex items-center gap-3 mb-4">
                <img 
                  :src="currentUserAvatar" 
                  class="w-10 h-10 rounded-full object-cover ring-2 ring-vikinger-purple/20" 
                />
                <div class="flex-1">
                  <div class="font-bold text-gray-800 dark:text-white leading-tight">{{ authStore.user?.name }}</div>
                  <div class="flex items-center gap-1.5 mt-0.5">
                    <button @click="showPrivacyOptions = !showPrivacyOptions" class="flex items-center gap-1 text-[11px] font-bold text-gray-500 dark:text-gray-400 hover:text-vikinger-purple uppercase tracking-wider transition-colors">
                      <Icon :icon="currentPrivacy.icon" class="w-2.5 h-2.5" />
                      <span>{{ currentPrivacy.label }}</span>
                      <Icon icon="mdi:chevron-down" class="w-3 h-3" />
                    </button>
                    <span v-if="locationInput" class="text-gray-300 dark:text-gray-700 font-light text-xs">|</span>
                    <div v-if="locationInput" class="flex items-center gap-1 text-[11px] font-bold text-vikinger-cyan uppercase tracking-wider">
                      <Icon icon="mdi:map-marker" class="w-2.5 h-2.5" />
                      <span class="truncate max-w-[150px]">{{ locationInput }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Tab Navigation (50-50 split) -->
              <div class="flex border-b border-gray-200 dark:border-vikinger-dark-50/30 mb-4">
                <button
                  v-for="tab in tabs" 
                  :key="tab.id"
                  @click="activeTab = tab.id"
                  :class="[
                    'flex-1 pb-3 text-sm font-bold uppercase tracking-wider transition-all relative flex items-center justify-center gap-2',
                    activeTab === tab.id 
                      ? tab.id === 'status' ? 'text-blue-600' : 'text-amber-600' 
                      : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-200'
                  ]"
                >
                  <Icon :icon="tab.id === 'status' ? 'fluent:edit-24-regular' : 'fluent:poll-24-regular'" class="w-5 h-5" />
                  {{ tab.label }}
                  <div 
                    v-if="activeTab === tab.id" 
                    :class="[
                      'absolute bottom-0 left-0 w-full h-1 rounded-full',
                      tab.id === 'status' ? 'bg-blue-600' : 'bg-amber-600'
                    ]"
                  ></div>
                </button>
              </div>

              <!-- Status Tab Content -->
              <div v-show="activeTab === 'status'">
                <!-- Hidden file input -->
                <input type="file" ref="imageInput" class="hidden" accept="image/*" multiple @change="handleImageSelect" />

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
                </div>
                
                <div class="mt-4">
                  <!-- Search Results -->
                  <div v-if="isSearchingFriends" class="mb-2 p-4 text-center">
                    <div class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                      <div class="w-4 h-4 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                      <span>กำลังค้นหา...</span>
                    </div>
                  </div>
                  
                  <div v-if="friendSearchResults.length > 0" class="mb-2 space-y-1 max-h-40 overflow-y-auto border border-gray-200 dark:border-vikinger-dark-50/30 rounded-lg bg-white dark:bg-vikinger-dark-100">
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
                  
                  <div v-else-if="hasSearchedFriends && friendSearchQuery && !isSearchingFriends" class="mb-2 p-3 text-center text-sm text-gray-500 dark:text-gray-400 bg-white dark:bg-vikinger-dark-100 rounded-lg border border-gray-200 dark:border-vikinger-dark-50/30">
                    <Icon icon="mdi:account-search" class="w-8 h-8 mx-auto mb-1 text-gray-400" />
                    <p>ไม่พบเพื่อนที่ชื่อ "{{ friendSearchQuery }}"</p>
                  </div>
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

              <!-- Poll Tab Content -->
              <div v-show="activeTab === 'poll'" class="mb-4">
                <!-- Poll Card (matching CourseCreatePostModal style) -->
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
                  
                  <!-- Poll Duration (inside card) -->
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

                <!-- Settings Row (Points, Max Votes) - Outside card -->
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
                      <p v-if="authStore.user && authStore.user.pp < (180 + pollPointsPool)" class="text-xs text-red-500 font-bold mt-2">
                        ! คุณมีแต้มไม่เพียงพอ (ปัจจุบัน {{ authStore.user.pp }} แต้ม)
                      </p>
                    </div>
                  </div>
                </div>

              </div>

              <!-- Footer Buttons -->
              <div class="p-4 border-t border-gray-200 dark:border-vikinger-dark-50/30">
                <button 
                  @click="createPost" 
                  :disabled="isSubmitting || (activeTab === 'status' && !postText.trim() && imagePreviews.length === 0) || (activeTab === 'poll' && !pollQuestion.trim())" 
                  class="w-full py-3 px-4 bg-gradient-vikinger text-white font-semibold rounded-lg hover:shadow-vikinger transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                >
                  <Icon v-if="isSubmitting" icon="mdi:loading" class="w-5 h-5 animate-spin" />
                  <Icon v-else-if="activeTab === 'poll'" icon="mdi:poll" class="w-5 h-5" />
                  <Icon v-else-if="scheduledDate" icon="mdi:clock-outline" class="w-5 h-5" />
                  <span>{{ isSubmitting ? (activeTab === 'poll' ? 'กำลังสร้างโพล...' : 'กำลังโพสต์...') : (activeTab === 'poll' ? 'สร้างโพล' : (scheduledDate ? 'ตั้งเวลาโพสต์' : 'โพสต์')) }}</span>
                </button>
              </div>

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
