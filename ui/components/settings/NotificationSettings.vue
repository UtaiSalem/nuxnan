<script setup lang="ts">
import { ref, onMounted, inject, watch } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

const api = useApi()

const settingsData = inject<any>('settingsData')
const reloadSettings = inject<() => Promise<void>>('reloadSettings', async () => {})
const markDirty = inject<() => void>('markDirty', () => {})
const markClean = inject<() => void>('markClean', () => {})

const isLoading = ref(false)
const isFetching = ref(false) // No longer needed for fetching, but kept for UI if needed

const notificationForm = ref({
  email_notifications: true,
  push_notifications: true,
  // Social
  notify_friend_requests: true,
  notify_messages: true,
  notify_mentions: true,
  notify_comments: true,
  notify_likes: true,
  // Learning
  notify_course_updates: true,
  notify_assignment_due: true,
  notify_grade_posted: true,
  notify_certificate: true,
  // System
  notify_system_updates: true,
  notify_security_alerts: true,
})

let watcherActive = false
watch(notificationForm, () => {
  if (watcherActive) markDirty()
}, { deep: true })

function hydrateForm(data: any) {
  if (!data || !data.profile) return
  if (data.profile.notification_settings) {
    const settings = typeof data.profile.notification_settings === 'string'
      ? JSON.parse(data.profile.notification_settings)
      : data.profile.notification_settings
    notificationForm.value = { ...notificationForm.value, ...settings }
  }
  setTimeout(() => {
    watcherActive = true
  }, 100)
}

onMounted(() => {
  if (settingsData?.value) {
    hydrateForm(settingsData.value)
  }
})

watch(() => settingsData?.value, (newData) => {
  if (newData && !watcherActive) {
    hydrateForm(newData)
  }
}, { immediate: true })

const notificationGroups = [
  {
    title: 'ช่องทางการแจ้งเตือน',
    description: 'เลือกช่องทางที่ต้องการรับการแจ้งเตือน',
    icon: 'fluent:channel-24-regular',
    color: 'blue',
    items: [
      { key: 'email_notifications', label: 'การแจ้งเตือนทางอีเมล', description: 'รับการแจ้งเตือนผ่านอีเมล', icon: 'fluent:mail-24-regular' },
      { key: 'push_notifications', label: 'การแจ้งเตือนแบบ Push', description: 'รับการแจ้งเตือนบนหน้าจอ', icon: 'fluent:alert-24-regular' },
    ]
  },
  {
    title: 'โซเชียล',
    description: 'การแจ้งเตือนเกี่ยวกับกิจกรรมทางสังคม',
    icon: 'fluent:people-24-regular',
    color: 'purple',
    items: [
      { key: 'notify_friend_requests', label: 'คำขอเป็นเพื่อน', description: 'เมื่อมีคนส่งคำขอเป็นเพื่อน', icon: 'fluent:person-add-24-regular' },
      { key: 'notify_messages', label: 'ข้อความ', description: 'เมื่อได้รับข้อความใหม่', icon: 'fluent:chat-24-regular' },
      { key: 'notify_mentions', label: 'การกล่าวถึง', description: 'เมื่อถูกกล่าวถึงในโพสต์หรือความคิดเห็น', icon: 'fluent:mention-24-regular' },
      { key: 'notify_comments', label: 'ความคิดเห็น', description: 'เมื่อมีคนแสดงความคิดเห็นในโพสต์ของคุณ', icon: 'fluent:comment-24-regular' },
      { key: 'notify_likes', label: 'ถูกใจ', description: 'เมื่อมีคนกดถูกใจโพสต์ของคุณ', icon: 'fluent:thumb-like-24-regular' },
    ]
  },
  {
    title: 'การเรียน',
    description: 'การแจ้งเตือนเกี่ยวกับคอร์สและการเรียน',
    icon: 'fluent:book-open-24-regular',
    color: 'emerald',
    items: [
      { key: 'notify_course_updates', label: 'อัปเดตคอร์ส', description: 'เมื่อมีเนื้อหาใหม่ในคอร์สที่ลงทะเบียน', icon: 'fluent:book-add-24-regular' },
      { key: 'notify_assignment_due', label: 'กำหนดส่งงาน', description: 'แจ้งเตือนก่อนถึงกำหนดส่งงาน', icon: 'fluent:calendar-clock-24-regular' },
      { key: 'notify_grade_posted', label: 'ประกาศเกรด', description: 'เมื่อมีการประกาศเกรดหรือคะแนน', icon: 'fluent:document-checkmark-24-regular' },
      { key: 'notify_certificate', label: 'ใบประกาศนียบัตร', description: 'เมื่อได้รับใบประกาศนียบัตร', icon: 'fluent:certificate-24-regular' },
    ]
  },
  {
    title: 'ระบบ',
    description: 'การแจ้งเตือนเกี่ยวกับระบบและความปลอดภัย',
    icon: 'fluent:settings-24-regular',
    color: 'amber',
    items: [
      { key: 'notify_system_updates', label: 'อัปเดตระบบ', description: 'ข่าวสารและอัปเดตจากระบบ', icon: 'fluent:megaphone-24-regular' },
      { key: 'notify_security_alerts', label: 'แจ้งเตือนความปลอดภัย', description: 'การเข้าสู่ระบบผิดปกติหรือการเปลี่ยนรหัสผ่าน', icon: 'fluent:shield-error-24-regular' },
    ]
  },
]

async function saveNotificationSettings() {
  isLoading.value = true
  try {
    const res = await api.post<any>('/api/settings/profile', {
        notification_settings: notificationForm.value
    })
    
    if (res.success) {
      markClean()
      await reloadSettings()
      Swal.fire({
        icon: 'success',
        title: 'บันทึกสำเร็จ!',
        text: 'การตั้งค่าการแจ้งเตือนถูกอัปเดตแล้ว',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000
      })
    }
  } catch (error: any) {
    Swal.fire('ผิดพลาด', error.data?.message || 'ไม่สามารถบันทึกได้', 'error')
  } finally {
    isLoading.value = false
  }
}

// Toggle all in a group
function toggleGroup(group: typeof notificationGroups[0], enable: boolean) {
  for (const item of group.items) {
    (notificationForm.value as any)[item.key] = enable
  }
}

// Check if all items in a group are enabled
function isGroupAllEnabled(group: typeof notificationGroups[0]): boolean {
  return group.items.every(item => (notificationForm.value as any)[item.key])
}

// Helper to get/set notification value for v-model
function getNotifValue(key: string): boolean {
  return (notificationForm.value as Record<string, boolean>)[key] ?? false
}

function setNotifValue(key: string, val: boolean) {
  (notificationForm.value as Record<string, boolean>)[key] = val
}
</script>

<template>
<div class="space-y-6">
  <!-- Notification Settings Groups -->
  <div 
    v-for="group in notificationGroups" 
    :key="group.title"
    class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden"
  >
    <!-- Group Header -->
    <div class="p-4 sm:p-6 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/10">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div 
            class="w-10 h-10 rounded-xl flex items-center justify-center"
            :class="`bg-${group.color}-100 dark:bg-${group.color}-900/20`"
          >
            <Icon :icon="group.icon" class="w-5 h-5" :class="`text-${group.color}-500`" />
          </div>
          <div>
            <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ group.title }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ group.description }}</p>
          </div>
        </div>
        
        <!-- Toggle All -->
        <button 
          @click="toggleGroup(group, !isGroupAllEnabled(group))"
          class="min-h-[44px] sm:min-h-0 text-xs font-medium px-3 py-1.5 rounded-lg transition-colors"
          :class="isGroupAllEnabled(group) 
            ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400 hover:bg-blue-100' 
            : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400 hover:bg-gray-200'"
        >
          {{ isGroupAllEnabled(group) ? 'ปิดทั้งหมด' : 'เปิดทั้งหมด' }}
        </button>
      </div>
    </div>

    <!-- Group Items -->
    <div class="divide-y divide-gray-50 dark:divide-gray-700/50">
      <div 
        v-for="item in group.items" 
        :key="item.key"
        class="flex items-center justify-between p-4 sm:px-6 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
      >
        <div class="flex items-center gap-3 flex-1 min-w-0">
          <Icon :icon="item.icon" class="w-5 h-5 text-gray-400 flex-shrink-0" />
          <div class="min-w-0">
            <p class="font-medium text-sm text-gray-900 dark:text-white">{{ item.label }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 hidden sm:block">{{ item.description }}</p>
          </div>
        </div>
        
        <!-- Toggle Switch -->
        <label class="relative inline-flex items-center cursor-pointer flex-shrink-0 ml-3">
          <input 
            type="checkbox" 
            :checked="getNotifValue(item.key)"
            @change="setNotifValue(item.key, ($event.target as HTMLInputElement).checked)"
            class="sr-only peer" 
          />
          <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
        </label>
      </div>
    </div>
  </div>

  <!-- Save Button -->
  <div class="flex justify-end sticky bottom-20 md:bottom-4">
    <button 
      @click="saveNotificationSettings" 
      :disabled="isLoading"
      class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium shadow-lg hover:shadow-blue-500/30 transition-all flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
    >
      <Icon v-if="isLoading" icon="svg-spinners:ring-resize" class="w-5 h-5" />
      <Icon v-else icon="fluent:save-24-regular" class="w-5 h-5" />
      <span>บันทึกการตั้งค่าการแจ้งเตือน</span>
    </button>
  </div>
</div>
</template>
