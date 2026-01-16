<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { Icon } from '@iconify/vue'
import SettingsSidebar from '~/components/settings/SettingsSidebar.vue'
import AccountInfo from '~/components/settings/AccountInfo.vue'
import ProfileInfo from '~/components/settings/ProfileInfo.vue'
import PrivacySettings from '~/components/settings/PrivacySettings.vue'
import Socials from '~/components/settings/Socials.vue'
import Security from '~/components/settings/Security.vue'

definePageMeta({
  layout: 'main',
  middleware: 'auth',
})

useHead({
  title: 'ตั้งค่าโปรไฟล์'
})

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

// Get reference_code from route params
const referenceCode = computed(() => route.params.reference_code as string)

// Check if user is viewing their own settings
const isOwnProfile = computed(() => {
  return authStore.user?.reference_code === referenceCode.value
})

// Check access on mount
onMounted(() => {
  // Redirect if trying to view someone else's settings
  if (!isOwnProfile.value) {
    router.replace(`/profile/${referenceCode.value}`)
  }
})

// Watch for route changes
watch(() => route.params.reference_code, (newCode) => {
  if (newCode && authStore.user?.reference_code !== newCode) {
    router.replace(`/profile/${newCode}`)
  }
})

const activeTab = ref('profile') // Default to Profile as it's most visual
</script>

<template>
  <div class="max-w-7xl mx-auto py-8 px-4">
    <!-- Settings Title -->
    <div class="mb-6">
      <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
        <Icon icon="fluent:settings-24-regular" class="w-5 h-5 text-blue-500" />
        จัดการข้อมูลและการตั้งค่าบัญชี
      </h2>
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
        แก้ไขข้อมูลส่วนตัว ความเป็นส่วนตัว และการตั้งค่าความปลอดภัย
      </p>
    </div>

    <div class="flex flex-col lg:flex-row gap-6">
      <!-- Sidebar -->
      <div class="lg:w-1/4">
        <div class="sticky top-24">
          <SettingsSidebar :active-tab="activeTab" @update:activeTab="activeTab = $event" />
        </div>
      </div>

      <!-- Content -->
      <div class="lg:w-3/4">
        <transition name="fade" mode="out-in">
          <div v-if="activeTab === 'account'" key="account">
            <AccountInfo />
          </div>
          <div v-else-if="activeTab === 'profile'" key="profile">
            <ProfileInfo />
          </div>
          <div v-else-if="activeTab === 'privacy'" key="privacy">
            <PrivacySettings />
          </div>
          <div v-else-if="activeTab === 'socials'" key="socials">
            <Socials />
          </div>
          <div v-else-if="activeTab === 'security'" key="security">
            <Security />
          </div>
        </transition>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
