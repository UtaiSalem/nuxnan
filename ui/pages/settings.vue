<script setup lang="ts">
import { ref } from 'vue'

import SettingsSidebar from '~/components/settings/SettingsSidebar.vue'
import AccountInfo from '~/components/settings/AccountInfo.vue'
import ProfileInfo from '~/components/settings/ProfileInfo.vue'
import Socials from '~/components/settings/Socials.vue'
import Security from '~/components/settings/Security.vue'

definePageMeta({
  layout: 'main',
  middleware: 'auth',
})

const activeTab = ref('profile') // Default to Profile as it's most visual
</script>

<template>
  <div class="max-w-7xl mx-auto py-8 px-4">
    <div class="flex flex-col lg:flex-row gap-6">
      
      <!-- Sidebar -->
      <div class="lg:w-1/4">
        <div class="sticky top-24">
          <SettingsSidebar v-model:activeTab="activeTab" />
        </div>
      </div>

      <!-- Content -->
      <div class="lg:w-3/4">
        <transition name="fade" mode="out-in">
          <div v-if="activeTab === 'account'">
            <AccountInfo />
          </div>
          <div v-else-if="activeTab === 'profile'">
            <ProfileInfo />
          </div>
          <div v-else-if="activeTab === 'socials'">
            <Socials />
          </div>
          <div v-else-if="activeTab === 'security'">
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
