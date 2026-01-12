<script setup lang="ts">
import { Icon } from '@iconify/vue'
import BaseCard from '~/components/atoms/BaseCard.vue'
import type { UserProfile } from '~/composables/useProfile'

const props = defineProps<{
  profile: UserProfile
  isOwnProfile?: boolean
}>()

// Social media icons mapping
const socialIcons: Record<string, { icon: string; color: string; label: string }> = {
  facebook: { icon: 'ri:facebook-fill', color: '#1877f2', label: 'Facebook' },
  twitter: { icon: 'ri:twitter-x-fill', color: '#000000', label: 'Twitter / X' },
  instagram: { icon: 'ri:instagram-fill', color: '#e4405f', label: 'Instagram' },
  linkedin: { icon: 'ri:linkedin-fill', color: '#0077b5', label: 'LinkedIn' },
  youtube: { icon: 'ri:youtube-fill', color: '#ff0000', label: 'YouTube' },
  tiktok: { icon: 'ri:tiktok-fill', color: '#000000', label: 'TikTok' },
}

// Calculate age
const age = computed(() => {
  if (!props.profile.birthdate) return null
  
  const birth = new Date(props.profile.birthdate)
  const today = new Date()
  let age = today.getFullYear() - birth.getFullYear()
  const monthDiff = today.getMonth() - birth.getMonth()
  
  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
    age--
  }
  
  return age
})

// Format join date
const joinDate = computed(() => {
  if (!props.profile.join_date) return null
  return new Date(props.profile.join_date).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'long'
  })
})

// Gender display
const genderDisplay = computed(() => {
  const genderMap: Record<string, string> = {
    male: 'ชาย',
    female: 'หญิง',
    other: 'อื่นๆ',
    prefer_not_to_say: 'ไม่ระบุ'
  }
  return genderMap[props.profile.gender || ''] || props.profile.gender
})

// Has social links
const hasSocialLinks = computed(() => {
  if (!props.profile.social_media_links) return false
  return Object.values(props.profile.social_media_links).some(v => v)
})

// Grade display
const gradeDisplay = computed(() => {
  if (!props.profile.grade) return null
  return `ม.${props.profile.grade}`
})
</script>

<template>
  <div class="space-y-6">
    <!-- Main About Card -->
    <BaseCard class="bg-gray-800 border-gray-700 overflow-hidden">
      <div class="bg-gradient-to-r from-vikinger-purple/20 to-vikinger-cyan/20 p-6 border-b border-gray-700">
        <h3 class="text-xl font-bold text-white flex items-center gap-2">
          <Icon icon="fluent:person-info-24-filled" class="w-6 h-6 text-vikinger-cyan" />
          เกี่ยวกับ {{ profile.username || profile.full_name }}
        </h3>
      </div>

      <div class="p-6 space-y-6">
        <!-- Bio Section -->
        <div v-if="profile.bio">
          <h4 class="text-sm font-medium text-gray-400 uppercase tracking-wider mb-3">ประวัติย่อ</h4>
          <p class="text-white leading-relaxed">{{ profile.bio }}</p>
        </div>
        <div v-else-if="isOwnProfile" class="text-center py-4 bg-gray-700/30 rounded-xl">
          <Icon icon="fluent:edit-24-regular" class="w-8 h-8 text-gray-500 mx-auto mb-2" />
          <p class="text-gray-400 text-sm">เพิ่มประวัติย่อเพื่อให้คนอื่นรู้จักคุณมากขึ้น</p>
          <NuxtLink to="/profile/edit" class="text-vikinger-cyan text-sm hover:underline mt-1 inline-block">
            แก้ไขโปรไฟล์
          </NuxtLink>
        </div>

        <!-- Basic Info Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Gender -->
          <div v-if="profile.gender" class="flex items-center gap-3 p-4 bg-gray-700/30 rounded-xl">
            <div class="w-10 h-10 rounded-lg bg-pink-500/20 flex items-center justify-center">
              <Icon 
                :icon="profile.gender === 'male' ? 'fluent:person-24-filled' : profile.gender === 'female' ? 'fluent:person-24-filled' : 'fluent:people-24-filled'" 
                class="w-5 h-5 text-pink-400" 
              />
            </div>
            <div>
              <p class="text-xs text-gray-400">เพศ</p>
              <p class="text-white font-medium">{{ genderDisplay }}</p>
            </div>
          </div>

          <!-- Age / Birthday -->
          <div v-if="profile.birthdate" class="flex items-center gap-3 p-4 bg-gray-700/30 rounded-xl">
            <div class="w-10 h-10 rounded-lg bg-orange-500/20 flex items-center justify-center">
              <Icon icon="fluent:calendar-24-filled" class="w-5 h-5 text-orange-400" />
            </div>
            <div>
              <p class="text-xs text-gray-400">อายุ</p>
              <p class="text-white font-medium">{{ age }} ปี</p>
            </div>
          </div>

          <!-- Grade -->
          <div v-if="profile.grade" class="flex items-center gap-3 p-4 bg-gray-700/30 rounded-xl">
            <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
              <Icon icon="fluent:book-24-filled" class="w-5 h-5 text-blue-400" />
            </div>
            <div>
              <p class="text-xs text-gray-400">ระดับชั้น</p>
              <p class="text-white font-medium">{{ gradeDisplay }}</p>
            </div>
          </div>

          <!-- Location -->
          <div v-if="profile.location" class="flex items-center gap-3 p-4 bg-gray-700/30 rounded-xl">
            <div class="w-10 h-10 rounded-lg bg-green-500/20 flex items-center justify-center">
              <Icon icon="fluent:location-24-filled" class="w-5 h-5 text-green-400" />
            </div>
            <div>
              <p class="text-xs text-gray-400">ที่อยู่</p>
              <p class="text-white font-medium">{{ profile.location }}</p>
            </div>
          </div>

          <!-- Website -->
          <div v-if="profile.website" class="flex items-center gap-3 p-4 bg-gray-700/30 rounded-xl">
            <div class="w-10 h-10 rounded-lg bg-cyan-500/20 flex items-center justify-center">
              <Icon icon="fluent:globe-24-filled" class="w-5 h-5 text-cyan-400" />
            </div>
            <div>
              <p class="text-xs text-gray-400">เว็บไซต์</p>
              <a 
                :href="profile.website" 
                target="_blank" 
                rel="noopener noreferrer"
                class="text-vikinger-cyan font-medium hover:underline"
              >
                {{ profile.website.replace(/^https?:\/\//, '') }}
              </a>
            </div>
          </div>

          <!-- Joined Date -->
          <div v-if="joinDate" class="flex items-center gap-3 p-4 bg-gray-700/30 rounded-xl">
            <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
              <Icon icon="fluent:calendar-add-24-filled" class="w-5 h-5 text-purple-400" />
            </div>
            <div>
              <p class="text-xs text-gray-400">เข้าร่วมเมื่อ</p>
              <p class="text-white font-medium">{{ joinDate }}</p>
            </div>
          </div>
        </div>

        <!-- Interests -->
        <div v-if="profile.interests">
          <h4 class="text-sm font-medium text-gray-400 uppercase tracking-wider mb-3">ความสนใจ</h4>
          <div class="flex flex-wrap gap-2">
            <span 
              v-for="(interest, index) in profile.interests.split(',').map(i => i.trim()).filter(Boolean)"
              :key="index"
              class="px-3 py-1.5 bg-vikinger-purple/20 text-vikinger-purple rounded-full text-sm font-medium"
            >
              {{ interest }}
            </span>
          </div>
        </div>
      </div>
    </BaseCard>

    <!-- Social Media Card -->
    <BaseCard v-if="hasSocialLinks" class="bg-gray-800 border-gray-700 overflow-hidden">
      <div class="p-6 border-b border-gray-700">
        <h3 class="text-lg font-bold text-white flex items-center gap-2">
          <Icon icon="fluent:share-24-filled" class="w-5 h-5 text-vikinger-cyan" />
          โซเชียลมีเดีย
        </h3>
      </div>

      <div class="p-6 grid grid-cols-2 sm:grid-cols-3 gap-4">
        <a
          v-for="(url, platform) in profile.social_media_links"
          v-show="url"
          :key="platform"
          :href="url"
          target="_blank"
          rel="noopener noreferrer"
          class="flex items-center gap-3 p-4 bg-gray-700/30 rounded-xl hover:bg-gray-700/50 transition-colors group"
        >
          <div 
            class="w-10 h-10 rounded-lg flex items-center justify-center transition-transform group-hover:scale-110"
            :style="{ backgroundColor: socialIcons[platform]?.color + '20' }"
          >
            <Icon 
              :icon="socialIcons[platform]?.icon || 'fluent:link-24-regular'" 
              class="w-5 h-5"
              :style="{ color: socialIcons[platform]?.color }"
            />
          </div>
          <span class="text-gray-300 text-sm font-medium group-hover:text-white">
            {{ socialIcons[platform]?.label || platform }}
          </span>
        </a>
      </div>
    </BaseCard>

    <!-- Stats Overview Card -->
    <BaseCard class="bg-gray-800 border-gray-700 overflow-hidden">
      <div class="p-6 border-b border-gray-700">
        <h3 class="text-lg font-bold text-white flex items-center gap-2">
          <Icon icon="fluent:data-trending-24-filled" class="w-5 h-5 text-vikinger-cyan" />
          สถิติภาพรวม
        </h3>
      </div>

      <div class="p-6">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
          <div class="text-center p-4 bg-gradient-to-br from-violet-500/20 to-purple-500/20 rounded-xl">
            <Icon icon="fluent:document-24-filled" class="w-8 h-8 text-violet-400 mx-auto mb-2" />
            <p class="text-2xl font-bold text-white">{{ profile.posts_count || 0 }}</p>
            <p class="text-xs text-gray-400">โพสต์</p>
          </div>
          
          <div class="text-center p-4 bg-gradient-to-br from-blue-500/20 to-cyan-500/20 rounded-xl">
            <Icon icon="fluent:people-24-filled" class="w-8 h-8 text-blue-400 mx-auto mb-2" />
            <p class="text-2xl font-bold text-white">{{ profile.friends_count || profile.friends || 0 }}</p>
            <p class="text-xs text-gray-400">เพื่อน</p>
          </div>
          
          <div class="text-center p-4 bg-gradient-to-br from-amber-500/20 to-orange-500/20 rounded-xl">
            <Icon icon="fluent:star-24-filled" class="w-8 h-8 text-amber-400 mx-auto mb-2" />
            <p class="text-2xl font-bold text-white">{{ (profile.points || profile.pp || 0).toLocaleString() }}</p>
            <p class="text-xs text-gray-400">แต้ม</p>
          </div>
          
          <div class="text-center p-4 bg-gradient-to-br from-emerald-500/20 to-green-500/20 rounded-xl">
            <Icon icon="fluent:eye-24-filled" class="w-8 h-8 text-emerald-400 mx-auto mb-2" />
            <p class="text-2xl font-bold text-white">{{ profile.visits_count || 0 }}</p>
            <p class="text-xs text-gray-400">การเข้าชม</p>
          </div>
        </div>
      </div>
    </BaseCard>
  </div>
</template>
