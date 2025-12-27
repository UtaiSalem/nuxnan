<template>
  <div class="auth-card relative group">
    <!-- Glow effect behind card -->
    <div class="absolute -inset-1 bg-gradient-to-r from-vikinger-purple via-vikinger-cyan to-vikinger-blue rounded-3xl blur-lg opacity-50 group-hover:opacity-75 transition-opacity duration-500"></div>
    
    <!-- Main card -->
    <div class="relative bg-white/95 backdrop-blur-xl rounded-3xl shadow-2xl p-6 lg:p-8 w-full max-w-2xl overflow-hidden flex flex-col justify-center">
      <!-- Decorative corner elements -->
      <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-vikinger-purple/20 to-transparent rounded-bl-full"></div>
      <div class="absolute bottom-0 left-0 w-24 h-24 bg-gradient-to-tr from-vikinger-cyan/20 to-transparent rounded-tr-full"></div>
      
      <!-- Header -->
      <div class="relative mb-6 text-center">
        <div class="inline-flex items-center justify-center w-12 h-12 mb-3 rounded-xl bg-gradient-to-br from-vikinger-purple to-vikinger-cyan shadow-lg transform group-hover:scale-110 transition-transform duration-300">
          <Icon 
            :icon="activeTab === 'login' ? 'fluent:person-24-filled' : 'fluent:person-add-24-filled'" 
            class="w-6 h-6 text-white"
          />
        </div>
        <h2 class="text-xl lg:text-2xl font-bold text-gray-800 mb-1">
          {{ activeTab === 'login' ? 'ยินดีต้อนรับกลับ!' : 'สร้างบัญชีใหม่' }}
        </h2>
        <p class="text-gray-500 text-sm">
          {{ activeTab === 'login' ? 'เข้าสู่ระบบเพื่อดำเนินการต่อ' : 'เริ่มต้นการเดินทางของคุณกับเรา' }}
        </p>
      </div>

      <!-- Form container -->
      <div class="relative w-full flex-1">
        <Transition name="slide-fade" mode="out-in">
          <div v-if="activeTab === 'login'" key="login" class="w-full">
            <LoginForm />
          </div>
          <div v-else key="register" class="w-full">
            <RegisterForm />
          </div>
        </Transition>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
import LoginForm from '../molecules/LoginForm.vue'
import RegisterForm from '../molecules/RegisterForm.vue'

const config = useRuntimeConfig()

withDefaults(
  defineProps<{
    activeTab?: 'login' | 'register'
  }>(),
  {
    activeTab: 'login',
  }
)

const loginWithGoogle = () => {
  window.location.href = `${config.public.apiBase}/auth/google`
}

const loginWithFacebook = () => {
  window.location.href = `${config.public.apiBase}/auth/facebook`
}
</script>

<style scoped>
.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: all 0.3s ease-out;
}

.slide-fade-enter-from {
  transform: translateX(20px);
  opacity: 0;
}

.slide-fade-leave-to {
  transform: translateX(-20px);
  opacity: 0;
}

/* Card hover effect */
.auth-card:hover .absolute.-inset-1 {
  animation: glow-pulse 2s ease-in-out infinite;
}

@keyframes glow-pulse {
  0%, 100% {
    opacity: 0.5;
  }
  50% {
    opacity: 0.75;
  }
}
</style>
