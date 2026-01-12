<script setup lang="ts">
import { Icon } from '@iconify/vue'
import BaseCard from '~/components/atoms/BaseCard.vue'
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
  <BaseCard class="bg-gray-800 border-gray-700 overflow-hidden">
    <!-- Header -->
    <div class="p-4 border-b border-gray-700">
      <h3 class="text-lg font-bold text-white flex items-center gap-2">
        <Icon icon="fluent:people-add-24-filled" class="w-5 h-5 text-vikinger-cyan" />
        คำขอเป็นเพื่อน
      </h3>
    </div>

    <!-- Tabs -->
    <div class="flex border-b border-gray-700">
      <button
        @click="activeTab = 'requests'"
        :class="[
          'flex-1 py-3 text-sm font-medium transition-colors relative',
          activeTab === 'requests' 
            ? 'text-vikinger-cyan' 
            : 'text-gray-400 hover:text-white'
        ]"
      >
        คำขอที่ได้รับ
        <span 
          v-if="friendRequests.length > 0"
          class="ml-1 px-1.5 py-0.5 text-xs bg-red-500 text-white rounded-full"
        >
          {{ friendRequests.length }}
        </span>
        <div 
          v-if="activeTab === 'requests'"
          class="absolute bottom-0 left-0 right-0 h-0.5 bg-vikinger-cyan"
        />
      </button>
      <button
        @click="activeTab = 'suggestions'"
        :class="[
          'flex-1 py-3 text-sm font-medium transition-colors relative',
          activeTab === 'suggestions' 
            ? 'text-vikinger-cyan' 
            : 'text-gray-400 hover:text-white'
        ]"
      >
        แนะนำ
        <div 
          v-if="activeTab === 'suggestions'"
          class="absolute bottom-0 left-0 right-0 h-0.5 bg-vikinger-cyan"
        />
      </button>
    </div>

    <!-- Content -->
    <div class="max-h-96 overflow-y-auto">
      <!-- Friend Requests Tab -->
      <template v-if="activeTab === 'requests'">
        <!-- Loading -->
        <div v-if="isLoadingRequests" class="p-4 space-y-4">
          <div v-for="i in 3" :key="i" class="flex items-center gap-3 animate-pulse">
            <div class="w-12 h-12 rounded-full bg-gray-700" />
            <div class="flex-1 space-y-2">
              <div class="h-4 bg-gray-700 rounded w-3/4" />
              <div class="h-3 bg-gray-700 rounded w-1/2" />
            </div>
          </div>
        </div>

        <!-- Requests List -->
        <div v-else-if="friendRequests.length > 0" class="divide-y divide-gray-700">
          <div 
            v-for="request in friendRequests" 
            :key="request.id"
            class="p-4 hover:bg-gray-700/50 transition-colors"
          >
            <div class="flex items-center gap-3">
              <!-- Avatar -->
              <div 
                class="cursor-pointer"
                @click="goToProfile(request.sender.reference_code || request.sender.id)"
              >
                <CircleAvatar
                  :src="request.sender.avatar || '/images/default-avatar.png'"
                  :alt="request.sender.name"
                  size="md"
                />
              </div>
              
              <!-- Info -->
              <div class="flex-1 min-w-0">
                <h4 
                  class="font-semibold text-white truncate hover:text-vikinger-cyan cursor-pointer"
                  @click="goToProfile(request.sender.reference_code || request.sender.id)"
                >
                  {{ request.sender.name || request.sender.full_name }}
                </h4>
                <p v-if="request.sender.mutual_friends_count" class="text-xs text-gray-400">
                  {{ request.sender.mutual_friends_count }} เพื่อนร่วม
                </p>
                <p class="text-xs text-gray-500">
                  {{ useDateFormatter().formatRelative(request.created_at) }}
                </p>
              </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex gap-2 mt-3">
              <button
                @click="handleAccept(request.sender.id)"
                :disabled="processingIds.has(request.sender.id)"
                class="flex-1 py-2 bg-vikinger-purple text-white rounded-lg hover:bg-vikinger-purple/80 transition-colors text-sm font-medium disabled:opacity-50 flex items-center justify-center gap-1"
              >
                <Icon 
                  v-if="processingIds.has(request.sender.id)" 
                  icon="fluent:spinner-ios-20-regular" 
                  class="w-4 h-4 animate-spin" 
                />
                <Icon v-else icon="fluent:checkmark-24-regular" class="w-4 h-4" />
                ยอมรับ
              </button>
              <button
                @click="handleDeny(request.sender.id)"
                :disabled="processingIds.has(request.sender.id)"
                class="flex-1 py-2 bg-gray-700 text-gray-300 rounded-lg hover:bg-gray-600 transition-colors text-sm font-medium disabled:opacity-50"
              >
                ปฏิเสธ
              </button>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="p-8 text-center">
          <Icon icon="fluent:people-24-regular" class="w-12 h-12 text-gray-600 mx-auto mb-3" />
          <p class="text-gray-400">ไม่มีคำขอเป็นเพื่อน</p>
        </div>
      </template>

      <!-- Suggestions Tab -->
      <template v-if="activeTab === 'suggestions'">
        <!-- Loading -->
        <div v-if="isLoadingSuggestions" class="p-4 space-y-4">
          <div v-for="i in 3" :key="i" class="flex items-center gap-3 animate-pulse">
            <div class="w-12 h-12 rounded-full bg-gray-700" />
            <div class="flex-1 space-y-2">
              <div class="h-4 bg-gray-700 rounded w-3/4" />
              <div class="h-3 bg-gray-700 rounded w-1/2" />
            </div>
          </div>
        </div>

        <!-- Suggestions List -->
        <div v-else-if="suggestions.length > 0" class="divide-y divide-gray-700">
          <div 
            v-for="user in suggestions" 
            :key="user.id"
            class="p-4 hover:bg-gray-700/50 transition-colors"
          >
            <div class="flex items-center gap-3">
              <!-- Avatar -->
              <div 
                class="cursor-pointer"
                @click="goToProfile(user.reference_code || user.id)"
              >
                <CircleAvatar
                  :src="user.avatar || '/images/default-avatar.png'"
                  :alt="user.name"
                  size="md"
                />
              </div>
              
              <!-- Info -->
              <div class="flex-1 min-w-0">
                <h4 
                  class="font-semibold text-white truncate hover:text-vikinger-cyan cursor-pointer"
                  @click="goToProfile(user.reference_code || user.id)"
                >
                  {{ user.name || user.full_name }}
                </h4>
                <p v-if="user.mutual_friends_count" class="text-xs text-gray-400">
                  {{ user.mutual_friends_count }} เพื่อนร่วม
                </p>
              </div>
              
              <!-- Add Button -->
              <button
                @click="handleSendRequest(user.id)"
                :disabled="processingIds.has(user.id)"
                class="p-2 bg-vikinger-purple/20 text-vikinger-purple rounded-lg hover:bg-vikinger-purple hover:text-white transition-colors disabled:opacity-50"
              >
                <Icon 
                  v-if="processingIds.has(user.id)" 
                  icon="fluent:spinner-ios-20-regular" 
                  class="w-5 h-5 animate-spin" 
                />
                <Icon v-else icon="fluent:person-add-24-regular" class="w-5 h-5" />
              </button>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="p-8 text-center">
          <Icon icon="fluent:search-24-regular" class="w-12 h-12 text-gray-600 mx-auto mb-3" />
          <p class="text-gray-400">ไม่มีคำแนะนำในขณะนี้</p>
        </div>
      </template>
    </div>

    <!-- Footer Link -->
    <div class="p-3 border-t border-gray-700 text-center">
      <NuxtLink 
        to="/members" 
        class="text-vikinger-cyan hover:underline text-sm flex items-center justify-center gap-1"
      >
        ค้นหาเพื่อนเพิ่มเติม
        <Icon icon="fluent:arrow-right-24-regular" class="w-4 h-4" />
      </NuxtLink>
    </div>
  </BaseCard>
</template>
