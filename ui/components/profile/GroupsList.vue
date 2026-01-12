<script setup lang="ts">
import { Icon } from '@iconify/vue'
import BaseCard from '~/components/atoms/BaseCard.vue'

const props = defineProps<{
  userId?: string | number
  isOwnProfile?: boolean
}>()

const api = useApi()

// Types
interface Group {
  id: number
  name: string
  slug: string
  description?: string
  cover_image?: string
  avatar?: string
  members_count: number
  category: string
  privacy: 'public' | 'private' | 'secret'
  is_member: boolean
  is_admin: boolean
  joined_at?: string
}

// State
const groups = ref<Group[]>([])
const isLoading = ref(false)
const selectedFilter = ref<'all' | 'owned' | 'joined'>('all')

// Computed
const filteredGroups = computed(() => {
  if (selectedFilter.value === 'all') return groups.value
  if (selectedFilter.value === 'owned') return groups.value.filter(g => g.is_admin)
  return groups.value.filter(g => g.is_member && !g.is_admin)
})

// Fetch groups
const fetchGroups = async () => {
  isLoading.value = true
  
  try {
    const endpoint = props.userId 
      ? `/api/users/${props.userId}/groups`
      : `/api/profile/groups`
    
    const response = await api.get(endpoint) as {
      success: boolean
      data?: Group[]
      groups?: Group[]
    }
    
    if (response.success) {
      groups.value = response.data || response.groups || []
    } else {
      // Mock data for demonstration
      groups.value = getMockGroups()
    }
  } catch (error) {
    console.error('Error fetching groups:', error)
    groups.value = getMockGroups()
  } finally {
    isLoading.value = false
  }
}

// Mock data
const getMockGroups = (): Group[] => [
  {
    id: 1,
    name: 'นักพัฒนา Vue.js Thailand',
    slug: 'vuejs-thailand',
    description: 'ชุมชนนักพัฒนา Vue.js ในประเทศไทย',
    cover_image: 'https://picsum.photos/400/200?random=1',
    members_count: 1250,
    category: 'Technology',
    privacy: 'public',
    is_member: true,
    is_admin: true
  },
  {
    id: 2,
    name: 'หนังสืออ่านแล้วดี',
    slug: 'good-books',
    description: 'แชร์หนังสือที่อ่านแล้วรู้สึกดี',
    cover_image: 'https://picsum.photos/400/200?random=2',
    members_count: 856,
    category: 'Education',
    privacy: 'public',
    is_member: true,
    is_admin: false
  },
  {
    id: 3,
    name: 'Plearnd Developers',
    slug: 'plearnd-dev',
    description: 'กลุ่มสำหรับนักพัฒนา Plearnd',
    cover_image: 'https://picsum.photos/400/200?random=3',
    members_count: 324,
    category: 'Technology',
    privacy: 'private',
    is_member: true,
    is_admin: false
  }
]

// Privacy icons
const privacyConfig = {
  public: { icon: 'fluent:globe-24-regular', label: 'สาธารณะ', color: 'text-green-400' },
  private: { icon: 'fluent:lock-closed-24-regular', label: 'ส่วนตัว', color: 'text-amber-400' },
  secret: { icon: 'fluent:eye-off-24-regular', label: 'ลับ', color: 'text-red-400' }
}

// Navigate to group
const goToGroup = (group: Group) => {
  navigateTo(`/groups/${group.slug}`)
}

// Initialize
onMounted(() => {
  fetchGroups()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <BaseCard class="bg-gray-800 border-gray-700 p-5">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
        <div>
          <h3 class="text-xl font-bold text-white flex items-center gap-2">
            <Icon icon="fluent:people-community-24-filled" class="w-6 h-6 text-vikinger-cyan" />
            กลุ่ม
            <span class="text-vikinger-cyan font-normal ml-1">({{ groups.length }})</span>
          </h3>
          <p class="text-sm text-gray-400 mt-1">กลุ่มที่เข้าร่วม</p>
        </div>
        
        <!-- Create Group Button (Own Profile) -->
        <NuxtLink
          v-if="isOwnProfile"
          to="/groups/create"
          class="px-4 py-2 bg-vikinger-purple text-white rounded-lg hover:bg-vikinger-purple/80 transition-colors flex items-center gap-2"
        >
          <Icon icon="fluent:add-24-regular" class="w-5 h-5" />
          สร้างกลุ่มใหม่
        </NuxtLink>
      </div>

      <!-- Filter Tabs -->
      <div class="flex gap-2 border-b border-gray-700 pb-3">
        <button
          v-for="filter in [
            { key: 'all', label: 'ทั้งหมด' },
            { key: 'owned', label: 'ที่เป็นแอดมิน' },
            { key: 'joined', label: 'ที่เข้าร่วม' },
          ]"
          :key="filter.key"
          @click="selectedFilter = filter.key as typeof selectedFilter"
          :class="[
            'px-4 py-2 rounded-lg text-sm font-medium transition-all',
            selectedFilter === filter.key
              ? 'bg-vikinger-purple/20 text-vikinger-purple border border-vikinger-purple/30'
              : 'text-gray-400 hover:text-white hover:bg-gray-700'
          ]"
        >
          {{ filter.label }}
        </button>
      </div>
    </BaseCard>

    <!-- Loading State -->
    <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div v-for="i in 4" :key="i" class="animate-pulse">
        <BaseCard class="bg-gray-800 border-gray-700 overflow-hidden">
          <div class="h-24 bg-gray-700"></div>
          <div class="p-4 space-y-3">
            <div class="h-5 bg-gray-700 rounded w-3/4"></div>
            <div class="h-3 bg-gray-700 rounded w-1/2"></div>
          </div>
        </BaseCard>
      </div>
    </div>

    <!-- Groups Grid -->
    <div v-else-if="filteredGroups.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <BaseCard 
        v-for="group in filteredGroups" 
        :key="group.id"
        class="bg-gray-800 border-gray-700 overflow-hidden hover:border-vikinger-purple/50 transition-all cursor-pointer group"
        @click="goToGroup(group)"
      >
        <!-- Cover Image -->
        <div class="h-24 bg-gradient-to-r from-vikinger-purple/30 to-vikinger-cyan/30 relative overflow-hidden">
          <img 
            v-if="group.cover_image" 
            :src="group.cover_image" 
            :alt="group.name"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
          
          <!-- Privacy Badge -->
          <div 
            class="absolute top-2 right-2 px-2 py-1 bg-black/50 rounded-full flex items-center gap-1 text-xs"
            :class="privacyConfig[group.privacy].color"
          >
            <Icon :icon="privacyConfig[group.privacy].icon" class="w-3 h-3" />
            {{ privacyConfig[group.privacy].label }}
          </div>
          
          <!-- Admin Badge -->
          <div 
            v-if="group.is_admin"
            class="absolute top-2 left-2 px-2 py-1 bg-vikinger-purple text-white rounded-full text-xs font-medium"
          >
            แอดมิน
          </div>
        </div>

        <!-- Content -->
        <div class="p-4">
          <h4 class="font-semibold text-white truncate group-hover:text-vikinger-cyan transition-colors">
            {{ group.name }}
          </h4>
          <p v-if="group.description" class="text-sm text-gray-400 line-clamp-2 mt-1">
            {{ group.description }}
          </p>
          
          <div class="flex items-center gap-4 mt-3 text-sm text-gray-500">
            <span class="flex items-center gap-1">
              <Icon icon="fluent:people-24-regular" class="w-4 h-4" />
              {{ group.members_count.toLocaleString() }} สมาชิก
            </span>
            <span class="flex items-center gap-1">
              <Icon icon="fluent:tag-24-regular" class="w-4 h-4" />
              {{ group.category }}
            </span>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Empty State -->
    <BaseCard v-else class="bg-gray-800 border-gray-700 text-center py-12">
      <Icon icon="fluent:people-community-24-regular" class="w-16 h-16 text-gray-600 mx-auto mb-4" />
      <p class="text-gray-400">ยังไม่ได้เข้าร่วมกลุ่มใดๆ</p>
      <p v-if="isOwnProfile" class="text-sm text-gray-500 mt-2">
        เริ่มเข้าร่วมกลุ่มเพื่อพบปะผู้คนที่มีความสนใจเหมือนกัน!
      </p>
      <NuxtLink 
        v-if="isOwnProfile" 
        to="/groups"
        class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-vikinger-purple text-white rounded-lg hover:bg-vikinger-purple/80 transition-colors"
      >
        <Icon icon="fluent:search-24-regular" class="w-5 h-5" />
        ค้นหากลุ่ม
      </NuxtLink>
    </BaseCard>
  </div>
</template>
