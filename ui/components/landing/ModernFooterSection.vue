<template>
  <footer class="relative py-12 sm:py-16 px-4 sm:px-6 lg:px-8 xl:px-12 z-20 bg-vikinger-dark border-t border-white/5">
    <!-- Animated Top Border -->
    <div class="absolute top-0 left-0 right-0 h-px overflow-hidden">
      <div ref="footerBorder" class="h-full bg-gradient-to-r from-transparent via-vikinger-purple to-transparent" style="width: 100%; transform: translateX(-100%);"></div>
    </div>

    <div class="max-w-7xl mx-auto">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-8 lg:gap-12 mb-8 lg:mb-12">
        <!-- Brand Column -->
        <div class="sm:col-span-2 lg:col-span-2">
          <div class="flex items-center gap-3 mb-4 sm:mb-6">
            <div class="w-10 h-10 bg-gradient-vikinger rounded-xl flex items-center justify-center">
              <Icon icon="heroicons:play" class="w-6 h-6 text-white" />
            </div>
            <span class="text-lg sm:text-xl font-bold text-white">PlearnD</span>
          </div>
          <p class="text-sm sm:text-base text-gray-400 mb-4 sm:mb-6 max-w-sm leading-relaxed">
            แพลตฟอร์มการเรียนรู้แบบ Gamification ที่ทำให้การเรียนสนุกเหมือนเล่นเกม
            พร้อมสร้างรายได้ไปพร้อมกัน
          </p>
          <!-- Social Links -->
          <div class="flex gap-3">
            <a href="#" class="w-10 h-10 bg-white/5 hover:bg-vikinger-cyan/20 rounded-lg flex items-center justify-center transition-all duration-300 group">
              <Icon icon="bi:facebook" class="w-5 h-5 text-gray-400 group-hover:text-vikinger-cyan transition-colors" />
            </a>
            <a href="#" class="w-10 h-10 bg-white/5 hover:bg-vikinger-purple/20 rounded-lg flex items-center justify-center transition-all duration-300 group">
              <Icon icon="bi:twitter-x" class="w-5 h-5 text-gray-400 group-hover:text-vikinger-purple transition-colors" />
            </a>
            <a href="#" class="w-10 h-10 bg-white/5 hover:bg-vikinger-green/20 rounded-lg flex items-center justify-center transition-all duration-300 group">
              <Icon icon="bi:line" class="w-5 h-5 text-gray-400 group-hover:text-vikinger-green transition-colors" />
            </a>
            <a href="#" class="w-10 h-10 bg-white/5 hover:bg-vikinger-pink/20 rounded-lg flex items-center justify-center transition-all duration-300 group">
              <Icon icon="bi:youtube" class="w-5 h-5 text-gray-400 group-hover:text-vikinger-pink transition-colors" />
            </a>
          </div>
        </div>

        <!-- Quick Links -->
        <div>
          <h4 class="font-semibold text-white mb-3 sm:mb-4 text-sm sm:text-base">แพลตฟอร์ม</h4>
          <ul class="space-y-2 sm:space-y-3">
            <li>
              <NuxtLink to="/courses" class="text-sm text-gray-400 hover:text-white transition-colors link-underline">
                คอร์สเรียน
              </NuxtLink>
            </li>
            <li>
              <NuxtLink to="/play" class="text-sm text-gray-400 hover:text-white transition-colors link-underline">
                เล่นเกม
              </NuxtLink>
            </li>
            <li>
              <NuxtLink to="/earn" class="text-sm text-gray-400 hover:text-white transition-colors link-underline">
                สร้างรายได้
              </NuxtLink>
            </li>
            <li>
              <NuxtLink to="/badges" class="text-sm text-gray-400 hover:text-white transition-colors link-underline">
                รางวัลและเหรียญ
              </NuxtLink>
            </li>
          </ul>
        </div>

        <!-- Company -->
        <div>
          <h4 class="font-semibold text-white mb-3 sm:mb-4 text-sm sm:text-base">เกี่ยวกับเรา</h4>
          <ul class="space-y-2 sm:space-y-3">
            <li>
              <NuxtLink to="/about" class="text-sm text-gray-400 hover:text-white transition-colors link-underline">
                เกี่ยวกับ PlearnD
              </NuxtLink>
            </li>
            <li>
              <NuxtLink to="/teams" class="text-sm text-gray-400 hover:text-white transition-colors link-underline">
                ทีมงาน
              </NuxtLink>
            </li>
            <li>
              <a href="#" class="text-sm text-gray-400 hover:text-white transition-colors link-underline">
                ร่วมงานกับเรา
              </a>
            </li>
            <li>
              <a href="#" class="text-sm text-gray-400 hover:text-white transition-colors link-underline">
                ติดต่อ
              </a>
            </li>
          </ul>
        </div>

        <!-- Support -->
        <div>
          <h4 class="font-semibold text-white mb-3 sm:mb-4 text-sm sm:text-base">สนับสนุน</h4>
          <ul class="space-y-2 sm:space-y-3">
            <li>
              <a href="#" class="text-sm text-gray-400 hover:text-white transition-colors link-underline">
                ศูนย์ช่วยเหลือ
              </a>
            </li>
            <li>
              <a href="#" class="text-sm text-gray-400 hover:text-white transition-colors link-underline">
                คู่มือการใช้งาน
              </a>
            </li>
            <li>
              <a href="#" class="text-sm text-gray-400 hover:text-white transition-colors link-underline">
                FAQ
              </a>
            </li>
            <li>
              <NuxtLink to="/PrivacyPolicy" class="text-sm text-gray-400 hover:text-white transition-colors link-underline">
                นโยบายความเป็นส่วนตัว
              </NuxtLink>
            </li>
          </ul>
        </div>
      </div>

      <!-- Contact Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8 lg:mb-12">
        <div class="glass-card p-4 sm:p-6 text-center">
          <Icon icon="heroicons:phone" class="w-6 sm:w-8 h-6 sm:h-8 text-vikinger-cyan mx-auto mb-2 sm:mb-3" />
          <h4 class="font-semibold text-white mb-1 text-sm sm:text-base">โทรศัพท์</h4>
          <p class="text-gray-400 text-sm">02-123-4567</p>
        </div>
        <div class="glass-card p-4 sm:p-6 text-center">
          <Icon icon="heroicons:envelope" class="w-6 sm:w-8 h-6 sm:h-8 text-vikinger-green mx-auto mb-2 sm:mb-3" />
          <h4 class="font-semibold text-white mb-1 text-sm sm:text-base">อีเมล</h4>
          <p class="text-gray-400 text-sm">contact@plearnd.com</p>
        </div>
        <div class="glass-card p-4 sm:p-6 text-center">
          <Icon icon="bi:line" class="w-6 sm:w-8 h-6 sm:h-8 text-vikinger-orange mx-auto mb-2 sm:mb-3" />
          <h4 class="font-semibold text-white mb-1 text-sm sm:text-base">ไลน์</h4>
          <p class="text-gray-400 text-sm">@plearnd</p>
        </div>
      </div>

      <!-- Bottom Bar -->
      <div class="pt-6 sm:pt-8 border-t border-white/5 flex flex-col sm:flex-row items-center justify-between gap-4">
        <p class="text-gray-500 text-xs sm:text-sm text-center sm:text-left">
          © {{ currentYear }} PlearnD. All rights reserved.
        </p>
        <div class="flex flex-wrap justify-center gap-4 sm:gap-6">
          <NuxtLink to="/PrivacyPolicy" class="text-gray-500 hover:text-white text-xs sm:text-sm transition-colors">
            นโยบายความเป็นส่วนตัว
          </NuxtLink>
          <NuxtLink to="/TermsOfService" class="text-gray-500 hover:text-white text-xs sm:text-sm transition-colors">
            ข้อกำหนดการใช้งาน
          </NuxtLink>
          <a href="#" class="text-gray-500 hover:text-white text-xs sm:text-sm transition-colors">
            คุกกี้
          </a>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
const currentYear = computed(() => new Date().getFullYear())

const footerBorder = ref<HTMLDivElement | null>(null)

onMounted(() => {
  // Intersection Observer for border animation
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting && footerBorder.value) {
        footerBorder.value.style.transition = 'transform 1.5s ease-in-out'
        footerBorder.value.style.transform = 'translateX(100%)'
        observer.unobserve(entry.target)
      }
    })
  }, { threshold: 0.5 })

  if (footerBorder.value?.parentElement) {
    observer.observe(footerBorder.value.parentElement)
  }
})
</script>

<style scoped>
.link-underline {
  position: relative;
}

.link-underline::after {
  content: '';
  position: absolute;
  bottom: -2px;
  left: 0;
  width: 0;
  height: 1px;
  background: #615dfa;
  transition: width 0.3s ease;
}

.link-underline:hover::after {
  width: 100%;
}
</style>
