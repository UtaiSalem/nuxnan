<template>
  <section id="stats" class="relative py-20 sm:py-32 px-4 sm:px-6 lg:px-8 xl:px-12 z-20 bg-vikinger-dark overflow-hidden">
    <!-- Background Glow -->
    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
      <div class="w-[400px] sm:w-[600px] h-[400px] sm:h-[600px] bg-vikinger-purple/10 rounded-full blur-[150px]"></div>
    </div>

    <!-- Section Header -->
    <div class="text-center max-w-3xl mx-auto mb-12 sm:mb-20 relative section-reveal" ref="sectionHeader">
      <span class="inline-block px-4 py-2 bg-vikinger-orange/10 border border-vikinger-orange/30 rounded-full text-xs sm:text-sm text-vikinger-orange font-medium mb-4 sm:mb-6">
        ความน่าเชื่อถือ
      </span>
      <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-4 sm:mb-6">
        ตัวเลขที่พิสูจน์
        <span class="gradient-text">ความสำเร็จ</span>
      </h2>
      <p class="text-sm sm:text-base lg:text-lg text-gray-400 px-4">
        ผู้เรียนจากทั่วประเทศไว้วางใจใช้แพลตฟอร์มของเรา
      </p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 max-w-6xl mx-auto relative">
      <!-- Stat Card 1 - Users -->
      <div class="stat-card glass-card p-4 sm:p-6 lg:p-8 text-center section-reveal" ref="stat1">
        <div class="relative mb-4 sm:mb-6">
          <div class="w-16 sm:w-20 lg:w-24 h-16 sm:h-20 lg:h-24 mx-auto rounded-full border-4 border-white/10 flex items-center justify-center overflow-hidden relative">
            <div class="liquid-fill absolute bottom-0 left-0 right-0" 
                 :style="{ height: animated ? '85%' : '0%' }"></div>
            <Icon icon="heroicons:users" 
                  class="w-6 sm:w-8 lg:w-10 h-6 sm:h-8 lg:h-10 relative z-10 transition-colors duration-500"
                  :class="animated ? 'text-white' : 'text-gray-400'" />
          </div>
          <div class="absolute inset-0 w-16 sm:w-20 lg:w-24 h-16 sm:h-20 lg:h-24 mx-auto rounded-full blur-xl transition-opacity duration-500"
               :class="animated ? 'opacity-50' : 'opacity-0'"
               style="background-color: #615dfa;"></div>
        </div>
        <div class="text-2xl sm:text-3xl lg:text-4xl xl:text-5xl font-bold text-white mb-1 sm:mb-2">
          <span ref="statNumber1">0</span>+
        </div>
        <div class="text-xs sm:text-sm text-gray-400">ผู้ใช้งาน</div>
      </div>

      <!-- Stat Card 2 - Courses -->
      <div class="stat-card glass-card p-4 sm:p-6 lg:p-8 text-center section-reveal" ref="stat2">
        <div class="relative mb-4 sm:mb-6">
          <div class="w-16 sm:w-20 lg:w-24 h-16 sm:h-20 lg:h-24 mx-auto rounded-full border-4 border-white/10 flex items-center justify-center overflow-hidden relative">
            <div class="liquid-fill absolute bottom-0 left-0 right-0" 
                 style="background: linear-gradient(to top, #059669, #10B981);"
                 :style="{ height: animated ? '78%' : '0%' }"></div>
            <Icon icon="heroicons:book-open" 
                  class="w-6 sm:w-8 lg:w-10 h-6 sm:h-8 lg:h-10 relative z-10 transition-colors duration-500"
                  :class="animated ? 'text-white' : 'text-gray-400'" />
          </div>
          <div class="absolute inset-0 w-16 sm:w-20 lg:w-24 h-16 sm:h-20 lg:h-24 mx-auto rounded-full blur-xl transition-opacity duration-500"
               :class="animated ? 'opacity-50' : 'opacity-0'"
               style="background-color: #10B981;"></div>
        </div>
        <div class="text-2xl sm:text-3xl lg:text-4xl xl:text-5xl font-bold text-white mb-1 sm:mb-2">
          <span ref="statNumber2">0</span>+
        </div>
        <div class="text-xs sm:text-sm text-gray-400">คอร์สเรียน</div>
      </div>

      <!-- Stat Card 3 - Points Earned -->
      <div class="stat-card glass-card p-4 sm:p-6 lg:p-8 text-center section-reveal" ref="stat3">
        <div class="relative mb-4 sm:mb-6">
          <div class="w-16 sm:w-20 lg:w-24 h-16 sm:h-20 lg:h-24 mx-auto rounded-full border-4 border-white/10 flex items-center justify-center overflow-hidden relative">
            <div class="liquid-fill absolute bottom-0 left-0 right-0" 
                 style="background: linear-gradient(to top, #D97706, #F59E0B);"
                 :style="{ height: animated ? '92%' : '0%' }"></div>
            <Icon icon="heroicons:star" 
                  class="w-6 sm:w-8 lg:w-10 h-6 sm:h-8 lg:h-10 relative z-10 transition-colors duration-500"
                  :class="animated ? 'text-white' : 'text-gray-400'" />
          </div>
          <div class="absolute inset-0 w-16 sm:w-20 lg:w-24 h-16 sm:h-20 lg:h-24 mx-auto rounded-full blur-xl transition-opacity duration-500"
               :class="animated ? 'opacity-50' : 'opacity-0'"
               style="background-color: #F59E0B;"></div>
        </div>
        <div class="text-2xl sm:text-3xl lg:text-4xl xl:text-5xl font-bold text-white mb-1 sm:mb-2">
          <span ref="statNumber3">0</span>M+
        </div>
        <div class="text-xs sm:text-sm text-gray-400">แต้มที่แจก</div>
      </div>

      <!-- Stat Card 4 - Satisfaction -->
      <div class="stat-card glass-card p-4 sm:p-6 lg:p-8 text-center section-reveal" ref="stat4">
        <div class="relative mb-4 sm:mb-6">
          <div class="w-16 sm:w-20 lg:w-24 h-16 sm:h-20 lg:h-24 mx-auto rounded-full border-4 border-white/10 flex items-center justify-center overflow-hidden relative">
            <div class="liquid-fill absolute bottom-0 left-0 right-0" 
                 style="background: linear-gradient(to top, #DB2777, #EC4899);"
                 :style="{ height: animated ? '99%' : '0%' }"></div>
            <Icon icon="heroicons:heart" 
                  class="w-6 sm:w-8 lg:w-10 h-6 sm:h-8 lg:h-10 relative z-10 transition-colors duration-500"
                  :class="animated ? 'text-white' : 'text-gray-400'" />
          </div>
          <div class="absolute inset-0 w-16 sm:w-20 lg:w-24 h-16 sm:h-20 lg:h-24 mx-auto rounded-full blur-xl transition-opacity duration-500"
               :class="animated ? 'opacity-50' : 'opacity-0'"
               style="background-color: #EC4899;"></div>
        </div>
        <div class="text-2xl sm:text-3xl lg:text-4xl xl:text-5xl font-bold text-white mb-1 sm:mb-2">
          <span ref="statNumber4">0</span>%
        </div>
        <div class="text-xs sm:text-sm text-gray-400">ความพึงพอใจ</div>
      </div>
    </div>

    <!-- Trust Badges -->
    <div class="mt-12 sm:mt-20 flex flex-wrap items-center justify-center gap-4 sm:gap-6 opacity-50 section-reveal" ref="trustBadges">
      <div class="flex items-center gap-2">
        <Icon icon="heroicons:trophy" class="w-4 sm:w-5 h-4 sm:h-5 text-vikinger-yellow" />
        <span class="text-xs sm:text-sm text-gray-400">EdTech Award 2024</span>
      </div>
      <div class="flex items-center gap-2">
        <Icon icon="heroicons:star" class="w-4 sm:w-5 h-4 sm:h-5 text-vikinger-yellow" />
        <span class="text-xs sm:text-sm text-gray-400">คะแนนรีวิว 4.9/5.0</span>
      </div>
      <div class="flex items-center gap-2">
        <Icon icon="heroicons:check-badge" class="w-4 sm:w-5 h-4 sm:h-5 text-vikinger-green" />
        <span class="text-xs sm:text-sm text-gray-400">รับรองคุณภาพ</span>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
interface Props {
  usersCount?: number
  coursesCount?: number
  pointsEarned?: number
  satisfaction?: number
}

const props = withDefaults(defineProps<Props>(), {
  usersCount: 10000,
  coursesCount: 500,
  pointsEarned: 5,
  satisfaction: 99
})

const animated = ref(false)
const sectionHeader = ref<HTMLElement | null>(null)
const stat1 = ref<HTMLElement | null>(null)
const stat2 = ref<HTMLElement | null>(null)
const stat3 = ref<HTMLElement | null>(null)
const stat4 = ref<HTMLElement | null>(null)
const trustBadges = ref<HTMLElement | null>(null)
const statNumber1 = ref<HTMLElement | null>(null)
const statNumber2 = ref<HTMLElement | null>(null)
const statNumber3 = ref<HTMLElement | null>(null)
const statNumber4 = ref<HTMLElement | null>(null)

function animateNumber(element: HTMLElement | null, target: number, duration: number = 2000, isDecimal: boolean = false) {
  if (!element) return
  
  const start = performance.now()
  
  function updateNumber(currentTime: number) {
    const elapsed = currentTime - start
    const progress = Math.min(elapsed / duration, 1)
    const easeProgress = 1 - Math.pow(1 - progress, 3)
    const current = target * easeProgress
    
    if (isDecimal) {
      element.textContent = current.toFixed(1)
    } else if (target >= 1000) {
      element.textContent = Math.floor(current).toLocaleString()
    } else {
      element.textContent = Math.floor(current).toString()
    }
    
    if (progress < 1) {
      requestAnimationFrame(updateNumber)
    }
  }
  
  requestAnimationFrame(updateNumber)
}

onMounted(() => {
  // Intersection Observer for reveal animations
  const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('revealed')
        revealObserver.unobserve(entry.target)
      }
    })
  }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' })

  // Stats animation observer
  const statsObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        animated.value = true
        
        // Animate numbers
        animateNumber(statNumber1.value, props.usersCount)
        animateNumber(statNumber2.value, props.coursesCount)
        animateNumber(statNumber3.value, props.pointsEarned)
        animateNumber(statNumber4.value, props.satisfaction)
        
        statsObserver.unobserve(entry.target)
      }
    })
  }, { threshold: 0.5 })

  // Observe elements
  const revealElements = [sectionHeader, stat1, stat2, stat3, stat4, trustBadges]
  revealElements.forEach(el => {
    if (el.value) revealObserver.observe(el.value)
  })

  if (stat1.value) statsObserver.observe(stat1.value)
})
</script>

<style scoped>
.section-reveal {
  opacity: 0;
  transform: translateY(30px);
  transition: all 0.7s ease-out;
}

.section-reveal.revealed {
  opacity: 1;
  transform: translateY(0);
}

.liquid-fill {
  background: linear-gradient(to top, #615dfa, #23d2e2);
  transition: height 2s ease-out;
}

.liquid-fill::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: rgba(255, 255, 255, 0.3);
}
</style>
