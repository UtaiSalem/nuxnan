<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch, provide } from 'vue'
import { Icon } from '@iconify/vue'
import { onBeforeRouteLeave } from 'vue-router'

const AccountInfo = defineAsyncComponent(() => import('~/components/settings/AccountInfo.vue'))
const ProfileInfo = defineAsyncComponent(() => import('~/components/settings/ProfileInfo.vue'))
const PrivacySettings = defineAsyncComponent(() => import('~/components/settings/PrivacySettings.vue'))
const Socials = defineAsyncComponent(() => import('~/components/settings/Socials.vue'))
const NotificationSettings = defineAsyncComponent(() => import('~/components/settings/NotificationSettings.vue'))

definePageMeta({
  middleware: 'auth',
})

useHead({
  title: 'ตั้งค่าโปรไฟล์'
})

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const api = useApi()

const routeId = computed(() => route.params.id as string)

const isOwnProfile = computed(() => {
  if (!authStore.user) return false
  return routeId.value === 'me' ||
    routeId.value === authStore.user.reference_code ||
    routeId.value === String(authStore.user.personal_code || '') ||
    routeId.value === String(authStore.user.id)
})

// Centralized Settings Data
const settingsData = ref<any>(null)
const isLoadingSettings = ref(false)

async function fetchSettings() {
  isLoadingSettings.value = true
  try {
    const res = await api.get<any>('/api/settings')
    if (res.success) {
      settingsData.value = res.data
    }
  } catch (e) {
    console.error('Fetch settings error', e)
  } finally {
    isLoadingSettings.value = false
  }
}

provide('settingsData', computed(() => settingsData.value))
provide('reloadSettings', fetchSettings)

// Unsaved changes tracking — child components call markDirty()/markClean()
const isDirty = ref(false)
provide('markDirty', () => { isDirty.value = true })
provide('markClean', () => { isDirty.value = false })

onBeforeRouteLeave((to, from, next) => {
  if (isDirty.value) {
    const confirmed = window.confirm('คุณมีการเปลี่ยนแปลงที่ยังไม่ได้บันทึก ต้องการออกจากหน้านี้หรือไม่?')
    confirmed ? next() : next(false)
  } else {
    next()
  }
})

const handleBeforeUnload = (e: BeforeUnloadEvent) => {
  if (isDirty.value) {
    e.preventDefault()
    e.returnValue = ''
  }
}

onMounted(() => {
  window.addEventListener('beforeunload', handleBeforeUnload)
  fetchSettings()
})

onBeforeUnmount(() => {
  window.removeEventListener('beforeunload', handleBeforeUnload)
})

onMounted(() => {
  if (!isOwnProfile.value) {
    router.replace(`/profile/${routeId.value}`)
    return
  }
  const targetId = authStore.user?.personal_code || authStore.user?.reference_code
  if (routeId.value === 'me' && targetId) {
    router.replace(`/profile/${targetId}/settings`)
    return
  }
})

watch(() => route.params.id, (newId) => {
  if (!newId) return
  if (newId === 'me' && (authStore.user?.personal_code || authStore.user?.reference_code)) {
    const targetId = authStore.user.personal_code || authStore.user.reference_code
    router.replace(`/profile/${targetId}/settings`)
  }
})

const settingsTabs = [
  { id: 'profile', label: 'โปรไฟล์', icon: 'fluent:contact-card-24-regular', description: 'แก้ไขข้อมูลโปรไฟล์' },
  { id: 'account', label: 'บัญชีและรหัสผ่าน', icon: 'fluent:person-info-24-regular', description: 'จัดการข้อมูลบัญชีและเปลี่ยนรหัสผ่าน' },
  { id: 'privacy', label: 'ความเป็นส่วนตัว', icon: 'fluent:shield-24-regular', description: 'ตั้งค่าความเป็นส่วนตัว' },
  { id: 'notifications', label: 'การแจ้งเตือน', icon: 'fluent:alert-24-regular', description: 'จัดการการแจ้งเตือน' },
  { id: 'socials', label: 'โซเชียล', icon: 'fluent:share-24-regular', description: 'เชื่อมต่อโซเชียลมีเดีย' },
]

const activeTab = ref('profile')

onMounted(() => {
  if (route.query.tab && typeof route.query.tab === 'string') {
    const validTab = settingsTabs.find(t => t.id === route.query.tab)
    if (validTab) {
      activeTab.value = route.query.tab
    }
  }
})

watch(activeTab, (newTab) => {
  router.replace({ query: { ...route.query, tab: newTab } })
})

watch(() => route.query.tab, (newTab) => {
  if (newTab && typeof newTab === 'string') {
    const validTab = settingsTabs.find(t => t.id === newTab)
    if (validTab) {
      activeTab.value = newTab
    }
  }
})

const goBackToProfile = () => {
  router.push(`/profile/${routeId.value}`)
}

const selectTab = (tabId: string) => {
  if (isDirty.value) {
    const confirmed = window.confirm('คุณมีการเปลี่ยนแปลงที่ยังไม่ได้บันทึก ต้องการเปลี่ยนแท็บหรือไม่?')
    if (!confirmed) return
    isDirty.value = false
  }
  activeTab.value = tabId
}
</script>

<template>
  <div class="w-full pb-20 md:pb-4">
    <!-- Header with back button -->
    <div class="mb-4 sm:mb-6">
      <button
        @click="goBackToProfile"
        class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors mb-3"
      >
        <Icon icon="fluent:arrow-left-24-regular" class="w-4 h-4" />
        <span>กลับไปยังโปรไฟล์</span>
      </button>

      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <Icon icon="fluent:settings-24-filled" class="w-6 h-6 text-blue-500" />
            ตั้งค่าโปรไฟล์
          </h2>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            แก้ไขข้อมูลส่วนตัว ความเป็นส่วนตัว และการตั้งค่าความปลอดภัย
          </p>
        </div>

        <!-- Unsaved changes indicator -->
        <div v-if="isDirty" class="hidden sm:flex items-center gap-2 text-sm text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 px-3 py-1.5 rounded-lg border border-amber-200 dark:border-amber-700">
          <Icon icon="fluent:warning-24-regular" class="w-4 h-4 flex-shrink-0" />
          <span>มีการเปลี่ยนแปลงที่ยังไม่บันทึก</span>
        </div>

      </div>
    </div>

    <!-- Mobile Horizontal Tab Scroller -->
    <div class="lg:hidden mb-4 -mx-4 px-4">
      <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
        <button
          v-for="tab in settingsTabs"
          :key="tab.id"
          @click="selectTab(tab.id)"
          class="min-h-[44px] sm:min-h-0 flex-shrink-0 flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium transition-all whitespace-nowrap"
          :class="activeTab === tab.id
            ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/30'
            : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'"
        >
          <Icon :icon="tab.icon" class="w-4 h-4" />
          {{ tab.label }}
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoadingSettings && !settingsData" class="py-20 flex flex-col items-center justify-center gap-4">
      <Icon icon="svg-spinners:ring-resize" class="w-10 h-10 text-blue-500" />
      <p class="text-gray-500 animate-pulse">กำลังโหลดข้อมูลการตั้งค่า...</p>
    </div>

    <div v-else class="flex flex-col lg:flex-row gap-6">
      <!-- Desktop Sidebar -->
      <div class="hidden lg:block lg:w-1/4 xl:w-1/5">
        <div class="sticky top-24">
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm overflow-hidden border border-gray-100 dark:border-gray-700">
            <div class="p-5 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20">
              <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <Icon icon="fluent:settings-24-filled" class="w-5 h-5 text-blue-500" />
                การตั้งค่า
              </h3>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">จัดการบัญชีของคุณ</p>
            </div>

            <div class="p-2">
              <button
                v-for="tab in settingsTabs"
                :key="tab.id"
                @click="selectTab(tab.id)"
                class="w-full flex items-start gap-3 px-4 py-3 rounded-xl transition-all duration-200 text-left group"
                :class="activeTab === tab.id
                  ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400 shadow-sm'
                  : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50'"
              >
                <div
                  class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 transition-all"
                  :class="activeTab === tab.id
                    ? 'bg-blue-100 dark:bg-blue-800/30'
                    : 'bg-gray-100 dark:bg-gray-700 group-hover:bg-gray-200 dark:group-hover:bg-gray-600'"
                >
                  <Icon :icon="tab.icon" class="w-5 h-5" />
                </div>
                <div class="flex-1 min-w-0">
                  <div class="font-medium text-sm">{{ tab.label }}</div>
                  <div v-if="tab.description" class="text-xs mt-0.5 opacity-60">{{ tab.description }}</div>
                </div>
                <Icon
                  v-if="activeTab === tab.id"
                  icon="fluent:chevron-right-24-regular"
                  class="w-4 h-4 flex-shrink-0 mt-2.5 opacity-50"
                />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Content Area -->
      <div class="flex-1 lg:w-3/4 xl:w-4/5 min-w-0">
        <!-- Mobile unsaved indicator -->
        <div v-if="isDirty" class="sm:hidden mb-3 flex items-center gap-2 text-sm text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 px-3 py-2 rounded-lg border border-amber-200 dark:border-amber-700">
          <Icon icon="fluent:warning-24-regular" class="w-4 h-4 flex-shrink-0" />
          <span>มีการเปลี่ยนแปลงที่ยังไม่บันทึก</span>
        </div>

        <Transition name="fade" mode="out-in">
          <div v-if="activeTab === 'profile'" key="profile">
            <ProfileInfo />
          </div>
          <div v-else-if="activeTab === 'account'" key="account">
            <AccountInfo />
          </div>
          <div v-else-if="activeTab === 'privacy'" key="privacy">
            <PrivacySettings />
          </div>
          <div v-else-if="activeTab === 'notifications'" key="notifications">
            <NotificationSettings />
          </div>
          <div v-else-if="activeTab === 'socials'" key="socials">
            <Socials />
          </div>
        </Transition>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.fade-enter-from {
  opacity: 0;
  transform: translateY(8px);
}

.fade-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
</style>
