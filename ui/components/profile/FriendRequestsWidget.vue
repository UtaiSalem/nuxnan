<script setup lang="ts">
import { Icon } from '@iconify/vue'
import CircleAvatar from '~/components/CircleAvatar.vue'

const { 
  friendRequests, 
  suggestions,
  isLoadingRequests,
  isLoadingSuggestions,
  fetchPendingRequests,
  fetchSuggestions,
  acceptFriendRequest,
  denyFriendRequest,
  sendFriendRequest
} = useFriends()

const toast = useToast()

// State
const activeTab = ref<'requests' | 'suggestions'>('requests')
const processingIds = ref<Set<number>>(new Set())

// Methods
const handleAccept = async (userId: number) => {
  processingIds.value.add(userId)
  try {
    const success = await acceptFriendRequest(userId)
    if (success) {
      toast.success('เพิ่มเพื่อนสำเร็จ!')
    } else {
      toast.error('ไม่สามารถดำเนินการได้')
    }
  } finally {
    processingIds.value.delete(userId)
  }
}

const handleDeny = async (userId: number) => {
  processingIds.value.add(userId)
  try {
    const success = await denyFriendRequest(userId)
    if (success) {
      toast.success('ปฏิเสธคำขอแล้ว')
    }
  } finally {
    processingIds.value.delete(userId)
  }
}

const handleSendRequest = async (userId: number) => {
  processingIds.value.add(userId)
  try {
    const success = await sendFriendRequest(userId)
    if (success) {
      toast.success('ส่งคำขอเป็นเพื่อนแล้ว!')
    } else {
      toast.error('ไม่สามารถส่งคำขอได้')
    }
  } finally {
    processingIds.value.delete(userId)
  }
}

const goToProfile = (referenceCode: string | number) => {
  navigateTo(`/profile/${referenceCode}`)
}

// Initialize
onMounted(async () => {
  await Promise.all([
    fetchPendingRequests(),
    fetchSuggestions()
  ])
})
</script>

<template>
  <div class="vikinger-card vikinger-card-hover overflow-hidden !p-0">
    <!-- Header - Compact -->
    <div class="relative bg-gradient-to-r from-vikinger-purple to-vikinger-cyan px-3 py-2.5">
      <div class="absolute inset-0 bg-[url('/images/patterns/circuit.svg')] bg-repeat opacity-10"></div>
      <h3 class="relative text-sm font-bold text-white flex items-center gap-2">
        <div class="w-6 h-6 rounded-md bg-white/20 flex items-center justify-center">
          <Icon icon="fluent:people-add-24-filled" class="w-3.5 h-3.5" />
        </div>
        คำขอเป็นเพื่อน
        <span 
          v-if="friendRequests.length > 0"
          class="ml-auto px-2 py-0.5 text-[10px] bg-white/20 text-white rounded-full font-bold"
        >
          {{ friendRequests.length }} ใหม่
        </span>
      </h3>
    </div>

    <!-- Tabs - Compact -->
    <div class="flex border-b border-gray-200 dark:border-gray-700/50">
      <button
        @click="activeTab = 'requests'"
        :class="[
          'flex-1 py-2 text-xs font-bold transition-all relative flex items-center justify-center gap-1.5',
          activeTab === 'requests' 
            ? 'text-vikinger-purple dark:text-vikinger-cyan bg-vikinger-purple/5 dark:bg-vikinger-cyan/10' 
            : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/50'
        ]"
      >
        คำขอที่ได้รับ
        <span 
          v-if="friendRequests.length > 0"
          class="px-1.5 py-0.5 text-[10px] bg-red-500 text-white rounded-full font-bold"
        >
          {{ friendRequests.length }}
        </span>
        <div 
          v-if="activeTab === 'requests'"
          class="absolute bottom-0 left-0 right-0 h-0.5 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan"
        />
      </button>
      <button
        @click="activeTab = 'suggestions'"
        :class="[
          'flex-1 py-2 text-xs font-bold transition-all relative',
          activeTab === 'suggestions' 
            ? 'text-vikinger-purple dark:text-vikinger-cyan bg-vikinger-purple/5 dark:bg-vikinger-cyan/10' 
            : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/50'
        ]"
      >
        แนะนำ
        <div 
          v-if="activeTab === 'suggestions'"
          class="absolute bottom-0 left-0 right-0 h-0.5 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan"
        />
      </button>
    </div>

    <!-- Content - Compact -->
    <div class="max-h-72 overflow-y-auto">
      <!-- Friend Requests Tab -->
      <template v-if="activeTab === 'requests'">
        <!-- Loading -->
        <div v-if="isLoadingRequests" class="p-3 space-y-2">
          <div v-for="i in 3" :key="i" class="flex items-center gap-2 animate-pulse">
            <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700" />
            <div class="flex-1 space-y-1">
              <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-3/4" />
              <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded w-1/2" />
            </div>
          </div>
        </div>

        <!-- Requests List - Compact -->
        <div v-else-if="friendRequests.length > 0" class="divide-y divide-gray-100 dark:divide-gray-700/50">
          <div 
            v-for="request in friendRequests" 
            :key="request.id"
            class="p-2.5 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
          >
            <div class="flex items-center gap-2.5">
              <!-- Avatar - Smaller -->
              <div 
                class="cursor-pointer flex-shrink-0"
                @click="goToProfile(request.sender.reference_code || request.sender.id)"
              >
                <CircleAvatar
                  :src="request.sender.avatar || '/images/default-avatar.png'"
                  :alt="request.sender.name"
                  size="sm"
                />
              </div>
              
              <!-- Info - Full width for name -->
              <div class="flex-1 min-w-0">
                <h4 
                  class="font-bold text-xs text-gray-900 dark:text-white truncate hover:text-vikinger-purple dark:hover:text-vikinger-cyan cursor-pointer"
                  @click="goToProfile(request.sender.reference_code || request.sender.id)"
                >
                  {{ request.sender.name || request.sender.full_name }}
                </h4>
                <p class="text-[10px] text-gray-400 dark:text-gray-500">
                  {{ useDateFormatter().formatRelative(request.created_at) }}
                </p>
              </div>
            </div>
            
            <!-- Action Buttons - Below -->
            <div class="flex gap-1.5 mt-2">
              <button
                @click="handleAccept(request.sender.id)"
                :disabled="processingIds.has(request.sender.id)"
                class="flex-1 py-1.5 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white rounded-lg text-[10px] font-bold disabled:opacity-50 flex items-center justify-center gap-1 hover:opacity-90 transition-all"
              >
                <Icon 
                  v-if="processingIds.has(request.sender.id)" 
                  icon="fluent:spinner-ios-20-regular" 
                  class="w-3 h-3 animate-spin" 
                />
                <Icon v-else icon="fluent:checkmark-24-regular" class="w-3 h-3" />
                ยอมรับ
              </button>
              <button
                @click="handleDeny(request.sender.id)"
                :disabled="processingIds.has(request.sender.id)"
                class="flex-1 py-1.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-lg text-[10px] font-bold disabled:opacity-50 hover:bg-gray-200 dark:hover:bg-gray-700 transition-all"
              >
                ปฏิเสธ
              </button>
            </div>
          </div>
        </div>

        <!-- Empty State - Compact -->
        <div v-else class="p-6 text-center">
          <div class="w-12 h-12 mx-auto mb-2 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
            <Icon icon="fluent:people-24-regular" class="w-6 h-6 text-gray-400 dark:text-gray-500" />
          </div>
          <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">ไม่มีคำขอเป็นเพื่อน</p>
        </div>
      </template>

      <!-- Suggestions Tab -->
      <template v-if="activeTab === 'suggestions'">
        <!-- Loading -->
        <div v-if="isLoadingSuggestions" class="p-3 space-y-2">
          <div v-for="i in 3" :key="i" class="flex items-center gap-2 animate-pulse">
            <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700" />
            <div class="flex-1 space-y-1">
              <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-3/4" />
              <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded w-1/2" />
            </div>
          </div>
        </div>

        <!-- Suggestions List - Compact -->
        <div v-else-if="suggestions.length > 0" class="divide-y divide-gray-100 dark:divide-gray-700/50">
          <div 
            v-for="user in suggestions" 
            :key="user.id"
            class="p-2.5 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
          >
            <div class="flex items-center gap-2.5">
              <!-- Avatar - Smaller -->
              <div 
                class="cursor-pointer flex-shrink-0"
                @click="goToProfile(user.reference_code || user.id)"
              >
                <CircleAvatar
                  :src="user.avatar || '/images/default-avatar.png'"
                  :alt="user.name"
                  size="sm"
                />
              </div>
              
              <!-- Info - Compact -->
              <div class="flex-1 min-w-0">
                <h4 
                  class="font-bold text-xs text-gray-900 dark:text-white truncate hover:text-vikinger-purple dark:hover:text-vikinger-cyan cursor-pointer"
                  @click="goToProfile(user.reference_code || user.id)"
                >
                  {{ user.name || user.full_name }}
                </h4>
                <p v-if="user.mutual_friends_count" class="text-[10px] text-vikinger-purple dark:text-vikinger-cyan">
                  {{ user.mutual_friends_count }} เพื่อนร่วม
                </p>
              </div>
              
              <!-- Add Button - Compact -->
              <button
                @click="handleSendRequest(user.id)"
                :disabled="processingIds.has(user.id)"
                class="p-1.5 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white rounded-lg hover:opacity-90 hover:scale-105 transition-all disabled:opacity-50"
              >
                <Icon 
                  v-if="processingIds.has(user.id)" 
                  icon="fluent:spinner-ios-20-regular" 
                  class="w-4 h-4 animate-spin" 
                />
                <Icon v-else icon="fluent:person-add-24-regular" class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>

        <!-- Empty State - Compact -->
        <div v-else class="p-6 text-center">
          <div class="w-12 h-12 mx-auto mb-2 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
            <Icon icon="fluent:search-24-regular" class="w-6 h-6 text-gray-400 dark:text-gray-500" />
          </div>
          <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">ไม่มีคำแนะนำ</p>
        </div>
      </template>
    </div>

    <!-- Footer Link - Compact -->
    <div class="p-2.5 border-t border-gray-100 dark:border-gray-700/50 bg-gray-50 dark:bg-gray-800/50">
      <NuxtLink 
        to="/members" 
        class="flex items-center justify-center gap-1.5 py-1.5 px-3 bg-gradient-to-r from-vikinger-purple/10 to-vikinger-cyan/10 hover:from-vikinger-purple/20 hover:to-vikinger-cyan/20 text-vikinger-purple dark:text-vikinger-cyan rounded-lg text-xs font-bold transition-all"
      >
        <Icon icon="fluent:search-24-regular" class="w-3.5 h-3.5" />
        ค้นหาเพื่อนเพิ่มเติม
        <Icon icon="fluent:arrow-right-24-regular" class="w-3.5 h-3.5" />
      </NuxtLink>
    </div>
  </div>
</template>
