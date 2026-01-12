<script setup lang="ts">
import { Icon } from '@iconify/vue'

defineProps<{
  activeTab: string
}>()

const emit = defineEmits(['update:activeTab'])

const menuItems = [
  { id: 'account', label: 'Account Info', icon: 'fluent:person-info-24-regular' },
  { id: 'profile', label: 'Profile Info', icon: 'fluent:contact-card-24-regular' },
  { id: 'socials', label: 'Social Networks', icon: 'fluent:share-24-regular' },
  { id: 'security', label: 'Security', icon: 'fluent:shield-keyhole-24-regular' },
  // { id: 'notifications', label: 'Notifications', icon: 'fluent:alert-24-regular' },
]

function selectTab(id: string) {
  emit('update:activeTab', id)
}
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden border border-gray-100 dark:border-gray-700">
    <div class="p-6 border-b border-gray-100 dark:border-gray-700">
      <h3 class="text-lg font-bold text-gray-900 dark:text-white">Settings</h3>
      <p class="text-sm text-gray-500 dark:text-gray-400">Manage your account preferences</p>
    </div>
    
    <div class="p-2">
      <button
        v-for="item in menuItems"
        :key="item.id"
        @click="selectTab(item.id)"
        class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 text-left"
        :class="activeTab === item.id 
          ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400 font-semibold' 
          : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50'"
      >
        <Icon :icon="item.icon" class="w-5 h-5" />
        <span>{{ item.label }}</span>
        <Icon v-if="activeTab === item.id" icon="fluent:chevron-right-24-regular" class="ml-auto w-4 h-4" />
      </button>
    </div>
  </div>
</template>
