<template>
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-12 items-center h-screen w-full relative py-4 overflow-hidden">
    <!-- Loading Overlay -->
    <Transition name="fade">
      <div
        v-if="authStore.isLoading"
        class="fixed inset-0 bg-black/60 backdrop-blur-md z-50 flex items-center justify-center"
      >
        <div class="bg-white/95 rounded-3xl p-10 shadow-2xl flex flex-col items-center space-y-6">
          <div class="relative w-20 h-20">
            <div class="absolute inset-0 border-4 border-vikinger-purple/30 rounded-full"></div>
            <div class="absolute inset-0 border-4 border-transparent border-t-vikinger-purple border-r-vikinger-cyan rounded-full animate-spin"></div>
            <div class="absolute inset-2 border-4 border-transparent border-b-vikinger-cyan rounded-full animate-spin" style="animation-direction: reverse; animation-duration: 1s;"></div>
          </div>
          <div class="text-center">
            <p class="text-gray-800 font-bold text-xl">กำลังดำเนินการ...</p>
            <p class="text-gray-500 text-sm mt-1">โปรดรอสักครู่</p>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Left Side: Branding & Welcome -->
    <div class="flex flex-col items-center lg:items-start text-center lg:text-left space-y-4 lg:space-y-6 relative px-4 lg:px-8">
      <!-- Logo with glow effect -->
      <div class="relative group">
        <div class="absolute -inset-4 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan rounded-full blur-xl opacity-50 group-hover:opacity-75 transition-opacity"></div>
        <div class="relative w-20 h-20 bg-gradient-to-br from-white to-gray-100 rounded-2xl flex items-center justify-center shadow-2xl transform group-hover:scale-110 transition-transform duration-300">
          <img src="/storage/images/plearnd-logo.png" alt="Nuxni Logo" class="w-12 h-12" />
        </div>
      </div>

      <!-- Title with gradient text -->
      <div class="space-y-2">
        <h1 class="text-5xl sm:text-6xl lg:text-7xl font-black tracking-tight">
          <span class="bg-clip-text text-transparent bg-gradient-to-r from-white via-vikinger-cyan to-vikinger-purple animate-gradient-x">
            NUXNI
          </span>
        </h1>
        <p class="text-gray-300 max-w-md mx-auto lg:mx-0 text-base lg:text-lg font-medium leading-relaxed">
          เรียนให้สนุก เล่นให้ได้ความรู้ สู่การสร้างรายได้
        </p>
      </div>

      <!-- Features badges -->
      <div class="flex flex-wrap gap-2 justify-center lg:justify-start">
        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/10 backdrop-blur-sm border border-white/20">
          <Icon icon="fluent:book-24-regular" class="w-4 h-4 text-vikinger-cyan" />
          <span class="text-white text-xs font-medium">เรียนออนไลน์</span>
        </div>
        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/10 backdrop-blur-sm border border-white/20">
          <Icon icon="fluent:games-24-regular" class="w-4 h-4 text-vikinger-purple" />
          <span class="text-white text-xs font-medium">เกมการเรียนรู้</span>
        </div>
        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/10 backdrop-blur-sm border border-white/20">
          <Icon icon="fluent:money-24-regular" class="w-4 h-4 text-yellow-400" />
          <span class="text-white text-xs font-medium">สร้างรายได้</span>
        </div>
      </div>

      <!-- Switch Buttons -->
      <div class="flex space-x-0 bg-white/10 backdrop-blur-md rounded-xl p-1 border border-white/20 shadow-xl">
        <button
          @click="setTab('login')"
          class="px-6 sm:px-10 py-2.5 rounded-lg font-bold transition-all duration-300 text-sm"
          :class="
            activeTab === 'login'
              ? 'bg-gradient-to-r from-vikinger-purple to-vikinger-blue text-white shadow-lg scale-105'
              : 'text-white/80 hover:bg-white/10 hover:text-white'
          "
        >
          <span class="relative z-10">เข้าสู่ระบบ</span>
        </button>
        <button
          @click="setTab('register')"
          class="px-6 sm:px-10 py-2.5 rounded-lg font-bold transition-all duration-300 text-sm"
          :class="
            activeTab === 'register'
              ? 'bg-gradient-to-r from-vikinger-cyan to-vikinger-purple text-white shadow-lg scale-105'
              : 'text-white/80 hover:bg-white/10 hover:text-white'
          "
        >
          <span class="relative z-10">สมัครสมาชิก</span>
        </button>
      </div>

      <!-- Stats -->
      <div class="hidden lg:grid grid-cols-3 gap-4 w-full max-w-sm">
        <div class="text-center">
          <div class="text-2xl font-black text-white">10K+</div>
          <div class="text-gray-400 text-xs">ผู้ใช้งาน</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-black text-white">500+</div>
          <div class="text-gray-400 text-xs">บทเรียน</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-black text-white">4.9</div>
          <div class="text-gray-400 text-xs">คะแนน</div>
        </div>
      </div>
    </div>

    <!-- Right Side: Auth Card -->
    <div class="flex justify-center lg:justify-end relative px-4 lg:px-8">
      <ClientOnly>
        <Transition name="card-transition" mode="out-in">
          <AuthCard :key="activeTab" :active-tab="activeTab" />
        </Transition>
      </ClientOnly>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Icon } from '@iconify/vue'
import AuthCard from '~/components/organisms/AuthCard.vue'

definePageMeta({
  layout: 'auth',
  middleware: 'guest',
})

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const activeTab = computed(() => (route.query.tab as string) || 'login')

const setTab = (tab: 'login' | 'register') => {
  router.push({ query: { ...route.query, tab } })
}

// Ensure valid tab on mount
onMounted(() => {
  if (!['login', 'register'].includes(activeTab.value)) {
    setTab('login')
  }
})
</script>

<style scoped>
/* Gradient animation for title */
@keyframes gradient-x {
  0%, 100% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
}

.animate-gradient-x {
  background-size: 200% 200%;
  animation: gradient-x 3s ease infinite;
}

/* Card transitions */
.card-transition-enter-active,
.card-transition-leave-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.card-transition-enter-from {
  opacity: 0;
  transform: translateX(40px) scale(0.95);
}

.card-transition-leave-to {
  opacity: 0;
  transform: translateX(-40px) scale(0.95);
}

/* Fade transitions */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
