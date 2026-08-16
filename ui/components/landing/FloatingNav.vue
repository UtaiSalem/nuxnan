<template>
  <nav 
    class="fixed bottom-4 sm:bottom-6 left-1/2 -translate-x-1/2 z-50 transition-all duration-500"
    :class="isVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10 pointer-events-none'"
  >
    <div class="flex items-center gap-1 px-2 py-2 bg-vikinger-dark-100/80 backdrop-blur-xl rounded-full border border-white/10 shadow-2xl">
      <!-- Home -->
      <a 
        href="#hero" 
        class="nav-item"
        :class="{ 'active': activeSection === 'hero' }"
        @click.prevent="scrollTo('hero')"
      >
        <Icon icon="heroicons:home" class="w-5 h-5" />
        <span class="nav-tooltip">หน้าแรก</span>
      </a>

      <!-- Features -->
      <a 
        href="#features" 
        class="nav-item"
        :class="{ 'active': activeSection === 'features' }"
        @click.prevent="scrollTo('features')"
      >
        <Icon icon="heroicons:squares-2x2" class="w-5 h-5" />
        <span class="nav-tooltip">ฟีเจอร์</span>
      </a>

      <!-- Stats -->
      <a 
        href="#stats" 
        class="nav-item"
        :class="{ 'active': activeSection === 'stats' }"
        @click.prevent="scrollTo('stats')"
      >
        <Icon icon="heroicons:chart-bar" class="w-5 h-5" />
        <span class="nav-tooltip">สถิติ</span>
      </a>

      <!-- Testimonials -->
      <a 
        href="#testimonials" 
        class="nav-item"
        :class="{ 'active': activeSection === 'testimonials' }"
        @click.prevent="scrollTo('testimonials')"
      >
        <Icon icon="heroicons:chat-bubble-left-ellipsis" class="w-5 h-5" />
        <span class="nav-tooltip">รีวิว</span>
      </a>

      <!-- Divider -->
      <div class="w-px h-6 bg-white/10 mx-1"></div>

      <!-- Login -->
      <NuxtLink 
        to="/auth" 
        class="nav-item nav-item-cta"
      >
        <Icon icon="heroicons:arrow-right-on-rectangle" class="w-5 h-5" />
        <span class="nav-tooltip">เข้าสู่ระบบ</span>
      </NuxtLink>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
const isVisible = ref(false)
const activeSection = ref('hero')

function scrollTo(sectionId: string) {
  const element = document.getElementById(sectionId)
  if (element) {
    element.scrollIntoView({ behavior: 'smooth' })
  }
}

function handleScroll() {
  // Show/hide nav based on scroll position
  isVisible.value = window.scrollY > 100

  // Determine active section
  const sections = ['hero', 'features', 'stats', 'testimonials']
  
  for (const sectionId of sections.reverse()) {
    const element = document.getElementById(sectionId)
    if (element) {
      const rect = element.getBoundingClientRect()
      if (rect.top <= window.innerHeight / 2) {
        activeSection.value = sectionId
        break
      }
    }
  }
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll, { passive: true })
  handleScroll() // Initial check
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})
</script>

<style scoped>
.nav-item {
  @apply relative flex items-center justify-center w-10 h-10 sm:w-12 sm:h-12 rounded-full transition-all duration-300 text-gray-400;
}

.nav-item:hover {
  @apply bg-white/10 text-vikinger-cyan;
}

.nav-item:hover .nav-tooltip {
  @apply opacity-100;
  transform: translateX(-50%) translateY(-8px);
}

.nav-item.active {
  @apply text-vikinger-cyan bg-vikinger-cyan/20;
}

.nav-item-cta {
  @apply bg-vikinger-purple/20 text-vikinger-purple;
}

.nav-item-cta:hover {
  @apply bg-vikinger-purple/30 text-vikinger-purple;
}

.nav-tooltip {
  @apply absolute bottom-full left-1/2 -translate-x-1/2 px-3 py-1.5 
         bg-vikinger-dark-100 text-white text-xs rounded-lg 
         whitespace-nowrap opacity-0 pointer-events-none 
         transition-all duration-300 mb-2 border border-white/10;
}

.nav-tooltip::after {
  content: '';
  position: absolute;
  top: 100%;
  left: 50%;
  transform: translateX(-50%);
  border-width: 4px;
  border-style: solid;
  border-color: rgba(29, 35, 51, 0.8) transparent transparent transparent;
}
</style>
