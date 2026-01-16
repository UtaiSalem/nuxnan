<script setup lang="ts">
import { Icon } from '@iconify/vue'

defineProps<{
  activeTab: string
}>()

const emit = defineEmits(['update:activeTab'])

const menuItems = [
  { id: 'account', label: 'ข้อมูลบัญชี', icon: 'fluent:person-info-24-regular', description: 'จัดการข้อมูลบัญชี' },
  { id: 'profile', label: 'ข้อมูลโปรไฟล์', icon: 'fluent:contact-card-24-regular', description: 'แก้ไขข้อมูลโปรไฟล์' },
  { id: 'privacy', label: 'ความเป็นส่วนตัว', icon: 'fluent:shield-24-regular', description: 'ตั้งค่าความเป็นส่วนตัว' },
  { id: 'socials', label: 'โซเชียลมีเดีย', icon: 'fluent:share-24-regular', description: 'เชื่อมต่อโซเชียลมีเดีย' },
  { id: 'security', label: 'ความปลอดภัย', icon: 'fluent:shield-keyhole-24-regular', description: 'จัดการรหัสผ่าน' },
  // { id: 'notifications', label: 'Notifications', icon: 'fluent:alert-24-regular' },
]

function selectTab(id: string) {
  emit('update:activeTab', id)
}
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden border border-gray-100 dark:border-gray-700">
    <div class="p-6 border-b border-gray-100 dark:border-gray-700">
      <h3 class="text-lg font-bold text-gray-900 dark:text-white">การตั้งค่า</h3>
      <p class="text-sm text-gray-500 dark:text-gray-400">จัดการการตั้งค่าบัญชีของคุณ</p>
    </div>
    
    <div class="p-2">
      <button
        v-for="item in menuItems"
        :key="item.id"
        @click="selectTab(item.id)"
        class="w-full flex items-start gap-3 px-4 py-3 rounded-lg transition-all duration-200 text-left"
        :class="activeTab === item.id 
          ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400' 
          : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50'"
      >
        <Icon :icon="item.icon" class="w-5 h-5 mt-0.5 flex-shrink-0" />
        <div class="flex-1 min-w-0">
          <div class="font-medium">{{ item.label }}</div>
          <div v-if="item.description" class="text-xs mt-0.5 opacity-75">{{ item.description }}</div>
        </div>
        <Icon v-if="activeTab === item.id" icon="fluent:chevron-right-24-regular" class="w-4 h-4 flex-shrink-0 mt-1" />
      </button>
    </div>
  </div>
</template>
