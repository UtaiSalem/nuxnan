<script setup lang="ts">
import { Icon } from '@iconify/vue'
import BaseCard from '~/components/atoms/BaseCard.vue'
import BaseAvatar from '~/components/atoms/BaseAvatar.vue'
import type { ProfileUpdateData, SocialMediaLinks, UserProfile } from '~/composables/useProfile'

definePageMeta({
  layout: 'main',
  middleware: 'auth'
})

useHead({
  title: 'Edit Profile'
})

const router = useRouter()
const { fetchMyProfile, updateProfile, updateAvatar, updateCover } = useProfile()
const toast = useToast()

// State
const profile = ref<UserProfile | null>(null)
const isLoading = ref(true)
const isSaving = ref(false)
const activeSection = ref('basic')

// Form data
const form = ref<ProfileUpdateData>({
  first_name: '',
  last_name: '',
  bio: '',
  birthdate: '',
  gender: '',
  location: '',
  website: '',
  interests: '',
  social_media_links: {
    facebook: '',
    twitter: '',
    instagram: '',
    linkedin: '',
    youtube: '',
    tiktok: '',
  },
  privacy_settings: 'public',
})

// Image upload refs
const avatarInput = ref<HTMLInputElement | null>(null)
const coverInput = ref<HTMLInputElement | null>(null)
const avatarPreview = ref<string | null>(null)
const coverPreview = ref<string | null>(null)
const isUploadingAvatar = ref(false)
const isUploadingCover = ref(false)

// Gender options
const genderOptions = [
  { value: '', label: 'เลือกเพศ' },
  { value: 'male', label: 'ชาย' },
  { value: 'female', label: 'หญิง' },
  { value: 'other', label: 'อื่นๆ' },
  { value: 'prefer_not_to_say', label: 'ไม่ระบุ' },
]

// Privacy options
const privacyOptions = [
  { value: 'public', label: 'สาธารณะ', description: 'ทุกคนสามารถดูโปรไฟล์ได้', icon: 'fluent:globe-24-regular' },
  { value: 'friends', label: 'เพื่อนเท่านั้น', description: 'เฉพาะเพื่อนเท่านั้นที่ดูได้', icon: 'fluent:people-24-regular' },
  { value: 'private', label: 'ส่วนตัว', description: 'เฉพาะตัวเองเท่านั้น', icon: 'fluent:lock-closed-24-regular' },
]

// Sections
const sections = [
  { key: 'basic', label: 'ข้อมูลพื้นฐาน', icon: 'fluent:person-24-regular' },
  { key: 'about', label: 'เกี่ยวกับ', icon: 'fluent:info-24-regular' },
  { key: 'social', label: 'โซเชียลมีเดีย', icon: 'fluent:share-24-regular' },
  { key: 'privacy', label: 'ความเป็นส่วนตัว', icon: 'fluent:shield-24-regular' },
]

// Load profile data
const loadProfile = async () => {
  isLoading.value = true
  try {
    const data = await fetchMyProfile()
    if (data) {
      profile.value = data
      
      // Populate form
      form.value = {
        first_name: data.first_name || '',
        last_name: data.last_name || '',
        bio: data.bio || '',
        birthdate: data.birthdate || '',
        gender: data.gender || '',
        location: data.location || '',
        website: data.website || '',
        interests: data.interests || '',
        social_media_links: {
          facebook: data.social_media_links?.facebook || '',
          twitter: data.social_media_links?.twitter || '',
          instagram: data.social_media_links?.instagram || '',
          linkedin: data.social_media_links?.linkedin || '',
          youtube: data.social_media_links?.youtube || '',
          tiktok: data.social_media_links?.tiktok || '',
        },
        privacy_settings: data.privacy_settings || 'public',
      }
    }
  } catch (error) {
    console.error('Error loading profile:', error)
    toast.error('ไม่สามารถโหลดข้อมูลโปรไฟล์ได้')
  } finally {
    isLoading.value = false
  }
}

// Handle avatar upload
const handleAvatarChange = async (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  
  if (!file) return
  
  // Validate file
  if (!file.type.startsWith('image/')) {
    toast.error('กรุณาเลือกไฟล์รูปภาพ')
    return
  }
  
  if (file.size > 5 * 1024 * 1024) {
    toast.error('ไฟล์ต้องมีขนาดไม่เกิน 5MB')
    return
  }
  
  // Preview
  avatarPreview.value = URL.createObjectURL(file)
  
  // Upload
  isUploadingAvatar.value = true
  try {
    const newAvatar = await updateAvatar(file)
    if (newAvatar) {
      toast.success('อัพเดทรูปโปรไฟล์สำเร็จ')
    }
  } catch (error: any) {
    toast.error(error.message || 'ไม่สามารถอัพโหลดรูปได้')
    avatarPreview.value = null
  } finally {
    isUploadingAvatar.value = false
  }
}

// Handle cover upload
const handleCoverChange = async (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  
  if (!file) return
  
  // Validate file
  if (!file.type.startsWith('image/')) {
    toast.error('กรุณาเลือกไฟล์รูปภาพ')
    return
  }
  
  if (file.size > 10 * 1024 * 1024) {
    toast.error('ไฟล์ต้องมีขนาดไม่เกิน 10MB')
    return
  }
  
  // Preview
  coverPreview.value = URL.createObjectURL(file)
  
  // Upload
  isUploadingCover.value = true
  try {
    const newCover = await updateCover(file)
    if (newCover) {
      toast.success('อัพเดทรูปปกสำเร็จ')
    }
  } catch (error: any) {
    toast.error(error.message || 'ไม่สามารถอัพโหลดรูปได้')
    coverPreview.value = null
  } finally {
    isUploadingCover.value = false
  }
}

// Save profile
const handleSubmit = async () => {
  isSaving.value = true
  
  try {
    // Clean up empty social media links
    const socialLinks: SocialMediaLinks = {}
    Object.entries(form.value.social_media_links || {}).forEach(([key, value]) => {
      if (value) {
        socialLinks[key as keyof SocialMediaLinks] = value
      }
    })
    
    const dataToUpdate: ProfileUpdateData = {
      ...form.value,
      social_media_links: Object.keys(socialLinks).length > 0 ? socialLinks : undefined,
    }
    
    const updated = await updateProfile(dataToUpdate)
    if (updated) {
      toast.success('อัพเดทโปรไฟล์สำเร็จ')
      router.push('/profile/me')
    }
  } catch (error: any) {
    toast.error(error.message || 'ไม่สามารถบันทึกข้อมูลได้')
  } finally {
    isSaving.value = false
  }
}

// Initialize
onMounted(() => {
  loadProfile()
})

// Computed
const currentAvatar = computed(() => avatarPreview.value || profile.value?.avatar)
const currentCover = computed(() => coverPreview.value || profile.value?.cover_image)
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-4">
        <button 
          @click="router.back()"
          class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
        >
          <Icon icon="fluent:arrow-left-24-regular" class="w-6 h-6" />
        </button>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Profile</h1>
      </div>
      <button
        @click="handleSubmit"
        :disabled="isSaving"
        class="px-6 py-2 bg-vikinger-purple text-white rounded-vikinger hover:bg-vikinger-purple/80 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
      >
        <Icon v-if="isSaving" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
        <Icon v-else icon="fluent:save-24-regular" class="w-5 h-5" />
        {{ isSaving ? 'Saving...' : 'Save Changes' }}
      </button>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="flex items-center justify-center min-h-[400px]">
      <Icon icon="fluent:spinner-ios-20-regular" class="w-12 h-12 animate-spin text-vikinger-purple" />
    </div>

    <template v-else>
      <!-- Cover & Avatar -->
      <BaseCard no-padding class="mb-6 overflow-hidden">
        <!-- Cover -->
        <div class="h-48 md:h-64 w-full bg-gradient-to-r from-vikinger-purple to-blue-500 relative group">
          <img 
            v-if="currentCover" 
            :src="currentCover" 
            alt="Cover"
            class="w-full h-full object-cover"
          />
          
          <!-- Cover Upload Overlay -->
          <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
            <button 
              @click="coverInput?.click()"
              :disabled="isUploadingCover"
              class="px-6 py-3 bg-white text-gray-900 rounded-vikinger font-medium flex items-center gap-2"
            >
              <Icon v-if="isUploadingCover" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
              <Icon v-else icon="fluent:camera-24-regular" class="w-5 h-5" />
              {{ isUploadingCover ? 'Uploading...' : 'Change Cover' }}
            </button>
          </div>
          <input 
            ref="coverInput" 
            type="file" 
            accept="image/*" 
            class="hidden" 
            @change="handleCoverChange"
          />
        </div>
        
        <!-- Avatar -->
        <div class="px-6 pb-6">
          <div class="relative -mt-16 md:-mt-20 inline-block">
            <div class="relative group">
              <BaseAvatar 
                :src="currentAvatar || ''" 
                size="xl" 
                class="w-32 h-32 md:w-40 md:h-40 ring-4 ring-white dark:ring-gray-800 shadow-lg"
              />
              
              <!-- Avatar Upload Overlay -->
              <div 
                @click="avatarInput?.click()"
                class="absolute inset-0 rounded-full bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center cursor-pointer"
              >
                <Icon 
                  v-if="isUploadingAvatar" 
                  icon="fluent:spinner-ios-20-regular" 
                  class="w-8 h-8 text-white animate-spin" 
                />
                <Icon v-else icon="fluent:camera-24-filled" class="w-8 h-8 text-white" />
              </div>
              <input 
                ref="avatarInput" 
                type="file" 
                accept="image/*" 
                class="hidden" 
                @change="handleAvatarChange"
              />
            </div>
          </div>
          
          <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
            คลิกที่รูปโปรไฟล์หรือรูปปกเพื่อเปลี่ยน
          </p>
        </div>
      </BaseCard>

      <!-- Section Navigation -->
      <div class="flex gap-2 mb-6 overflow-x-auto pb-2">
        <button
          v-for="section in sections"
          :key="section.key"
          @click="activeSection = section.key"
          :class="[
            'px-4 py-2 rounded-vikinger font-medium text-sm flex items-center gap-2 whitespace-nowrap transition-colors',
            activeSection === section.key
              ? 'bg-vikinger-purple text-white'
              : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'
          ]"
        >
          <Icon :icon="section.icon" class="w-5 h-5" />
          {{ section.label }}
        </button>
      </div>

      <!-- Form Sections -->
      <BaseCard>
        <!-- Basic Info Section -->
        <div v-show="activeSection === 'basic'" class="space-y-6">
          <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <Icon icon="fluent:person-24-regular" class="w-6 h-6 text-vikinger-purple" />
            ข้อมูลพื้นฐาน
          </h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                ชื่อจริง
              </label>
              <input
                v-model="form.first_name"
                type="text"
                placeholder="ชื่อจริง"
                class="w-full px-4 py-3 rounded-vikinger border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-vikinger-purple focus:border-transparent"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                นามสกุล
              </label>
              <input
                v-model="form.last_name"
                type="text"
                placeholder="นามสกุล"
                class="w-full px-4 py-3 rounded-vikinger border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-vikinger-purple focus:border-transparent"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                วันเกิด
              </label>
              <input
                v-model="form.birthdate"
                type="date"
                class="w-full px-4 py-3 rounded-vikinger border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-vikinger-purple focus:border-transparent"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                เพศ
              </label>
              <select
                v-model="form.gender"
                class="w-full px-4 py-3 rounded-vikinger border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-vikinger-purple focus:border-transparent"
              >
                <option v-for="option in genderOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                ที่อยู่/ตำแหน่ง
              </label>
              <input
                v-model="form.location"
                type="text"
                placeholder="เช่น กรุงเทพมหานคร, ประเทศไทย"
                class="w-full px-4 py-3 rounded-vikinger border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-vikinger-purple focus:border-transparent"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                เว็บไซต์
              </label>
              <input
                v-model="form.website"
                type="url"
                placeholder="https://example.com"
                class="w-full px-4 py-3 rounded-vikinger border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-vikinger-purple focus:border-transparent"
              />
            </div>
          </div>
        </div>

        <!-- About Section -->
        <div v-show="activeSection === 'about'" class="space-y-6">
          <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <Icon icon="fluent:info-24-regular" class="w-6 h-6 text-vikinger-purple" />
            เกี่ยวกับคุณ
          </h3>
          
          <div class="space-y-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                ประวัติย่อ (Bio)
              </label>
              <textarea
                v-model="form.bio"
                rows="4"
                maxlength="500"
                placeholder="บอกเล่าเกี่ยวกับตัวคุณ..."
                class="w-full px-4 py-3 rounded-vikinger border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-vikinger-purple focus:border-transparent resize-none"
              ></textarea>
              <p class="mt-1 text-sm text-gray-500">
                {{ (form.bio || '').length }}/500 ตัวอักษร
              </p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                ความสนใจ
              </label>
              <textarea
                v-model="form.interests"
                rows="3"
                maxlength="1000"
                placeholder="เช่น การเดินทาง, ถ่ายภาพ, อ่านหนังสือ..."
                class="w-full px-4 py-3 rounded-vikinger border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-vikinger-purple focus:border-transparent resize-none"
              ></textarea>
            </div>
          </div>
        </div>

        <!-- Social Media Section -->
        <div v-show="activeSection === 'social'" class="space-y-6">
          <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <Icon icon="fluent:share-24-regular" class="w-6 h-6 text-vikinger-purple" />
            โซเชียลมีเดีย
          </h3>
          
          <div class="space-y-4">
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-full bg-blue-600 flex items-center justify-center flex-shrink-0">
                <Icon icon="logos:facebook" class="w-6 h-6" />
              </div>
              <input
                v-model="form.social_media_links!.facebook"
                type="url"
                placeholder="https://facebook.com/username"
                class="flex-1 px-4 py-3 rounded-vikinger border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-vikinger-purple focus:border-transparent"
              />
            </div>
            
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-full bg-sky-500 flex items-center justify-center flex-shrink-0">
                <Icon icon="logos:twitter" class="w-6 h-6" />
              </div>
              <input
                v-model="form.social_media_links!.twitter"
                type="url"
                placeholder="https://twitter.com/username"
                class="flex-1 px-4 py-3 rounded-vikinger border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-vikinger-purple focus:border-transparent"
              />
            </div>
            
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-full bg-gradient-to-tr from-yellow-400 via-red-500 to-purple-500 flex items-center justify-center flex-shrink-0">
                <Icon icon="mdi:instagram" class="w-6 h-6 text-white" />
              </div>
              <input
                v-model="form.social_media_links!.instagram"
                type="url"
                placeholder="https://instagram.com/username"
                class="flex-1 px-4 py-3 rounded-vikinger border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-vikinger-purple focus:border-transparent"
              />
            </div>
            
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-full bg-red-600 flex items-center justify-center flex-shrink-0">
                <Icon icon="logos:youtube-icon" class="w-6 h-6" />
              </div>
              <input
                v-model="form.social_media_links!.youtube"
                type="url"
                placeholder="https://youtube.com/@username"
                class="flex-1 px-4 py-3 rounded-vikinger border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-vikinger-purple focus:border-transparent"
              />
            </div>
            
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-full bg-blue-700 flex items-center justify-center flex-shrink-0">
                <Icon icon="logos:linkedin-icon" class="w-6 h-6" />
              </div>
              <input
                v-model="form.social_media_links!.linkedin"
                type="url"
                placeholder="https://linkedin.com/in/username"
                class="flex-1 px-4 py-3 rounded-vikinger border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-vikinger-purple focus:border-transparent"
              />
            </div>
            
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-full bg-black flex items-center justify-center flex-shrink-0">
                <Icon icon="logos:tiktok-icon" class="w-6 h-6" />
              </div>
              <input
                v-model="form.social_media_links!.tiktok"
                type="url"
                placeholder="https://tiktok.com/@username"
                class="flex-1 px-4 py-3 rounded-vikinger border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-vikinger-purple focus:border-transparent"
              />
            </div>
          </div>
        </div>

        <!-- Privacy Section -->
        <div v-show="activeSection === 'privacy'" class="space-y-6">
          <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <Icon icon="fluent:shield-24-regular" class="w-6 h-6 text-vikinger-purple" />
            ความเป็นส่วนตัว
          </h3>
          
          <div class="space-y-4">
            <p class="text-gray-600 dark:text-gray-400">
              ควบคุมว่าใครสามารถดูโปรไฟล์ของคุณได้
            </p>
            
            <div class="space-y-3">
              <label
                v-for="option in privacyOptions"
                :key="option.value"
                class="flex items-start gap-4 p-4 border rounded-vikinger cursor-pointer transition-colors"
                :class="[
                  form.privacy_settings === option.value 
                    ? 'border-vikinger-purple bg-vikinger-purple/5' 
                    : 'border-gray-200 dark:border-gray-700 hover:border-vikinger-purple/50'
                ]"
              >
                <input
                  v-model="form.privacy_settings"
                  type="radio"
                  :value="option.value"
                  class="mt-1 text-vikinger-purple focus:ring-vikinger-purple"
                />
                <div class="flex-1">
                  <div class="flex items-center gap-2">
                    <Icon :icon="option.icon" class="w-5 h-5 text-vikinger-purple" />
                    <span class="font-medium text-gray-900 dark:text-white">{{ option.label }}</span>
                  </div>
                  <p class="text-sm text-gray-500 mt-1">{{ option.description }}</p>
                </div>
              </label>
            </div>
          </div>
        </div>

        <!-- Save Button (Mobile) -->
        <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700 md:hidden">
          <button
            @click="handleSubmit"
            :disabled="isSaving"
            class="w-full py-3 bg-vikinger-purple text-white rounded-vikinger font-medium hover:bg-vikinger-purple/80 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
          >
            <Icon v-if="isSaving" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
            <Icon v-else icon="fluent:save-24-regular" class="w-5 h-5" />
            {{ isSaving ? 'กำลังบันทึก...' : 'บันทึกการเปลี่ยนแปลง' }}
          </button>
        </div>
      </BaseCard>
    </template>
  </div>
</template>
