<template>
  <div>
    <!-- Page Header -->
    <section class="bg-gradient-to-r from-primary-500 to-primary-600 text-white py-6 mb-4">
      <div class="container mx-auto px-4">
        <h1 class="text-2xl font-bold mb-1">Groups</h1>
        <div class="flex items-center gap-2 text-sm text-primary-100">
          <NuxtLink to="/" class="hover:text-white transition-colors">Home</NuxtLink>
          <Icon icon="mdi:chevron-right" class="w-4 h-4" />
          <span>Groups</span>
        </div>
      </div>
    </section>

    <!-- Filters -->
    <div class="container mx-auto px-4 mb-6">
      <div class="flex gap-2 overflow-x-auto pb-2">
        <button
          v-for="filter in filters"
          :key="filter"
          @click="activeFilter = filter"
          class="px-4 py-2 rounded-full font-medium whitespace-nowrap transition-colors"
          :class="
            activeFilter === filter
              ? 'bg-primary-600 text-white'
              : 'bg-white text-secondary-700 hover:bg-gray-100'
          "
        >
          {{ filter }}
        </button>
      </div>
    </div>

    <!-- Groups Grid -->
    <div class="container mx-auto px-4 pb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <GroupCard 
          v-for="group in groups" 
          :key="group.id" 
          :group="group"
          course-id="general"
          @click="handleGroupClick"
          @join="handleGroupJoin"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'

const activeFilter = ref('All Groups')
const filters = ['All Groups', 'My Groups', 'Joined', 'Popular', 'New']

const groups = ref([
  {
    id: 1,
    name: 'Photography Lovers',
    description: 'Share your best shots and learn from others',
    cover: 'https://picsum.photos/800/400?random=40',
    members_count: 12345,
    posts_count: 234,
    visits_count: 5678,
    groupMemberOfAuth: true,
    members: [],
    image_url: null
  },
  {
    id: 2,
    name: 'Fitness & Wellness',
    description: 'Motivation and tips for a healthy lifestyle',
    cover: 'https://picsum.photos/800/400?random=41',
    members_count: 8765,
    posts_count: 567,
    visits_count: 3456,
    groupMemberOfAuth: false,
    members: [],
    image_url: null
  },
  {
    id: 3,
    name: 'Tech Enthusiasts',
    description: 'Latest in technology and innovation',
    cover: 'https://picsum.photos/800/400?random=42',
    members_count: 23456,
    posts_count: 1234,
    visits_count: 15000,
    groupMemberOfAuth: true,
    members: [],
    image_url: null
  },
  {
    id: 4,
    name: 'Travel Adventures',
    description: 'Explore the world together',
    cover: 'https://picsum.photos/800/400?random=43',
    members_count: 15678,
    posts_count: 890,
    visits_count: 8901,
    groupMemberOfAuth: false,
    members: [],
    image_url: null
  },
])

// Handle group click
const handleGroupClick = (group: any) => {
  navigateTo(`/groups/${group.id}`)
}

// Handle group join
const handleGroupJoin = async (groupId: number) => {
  // TODO: Implement API call to join group
  console.log('Joining group:', groupId)
  
  // Update local state
  const group = groups.value.find(g => g.id === groupId)
  if (group) {
    group.groupMemberOfAuth = true
    group.members_count++
  }
}

useHead({
  title: 'Groups',
})
</script>
