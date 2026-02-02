<template>
  <div class="min-h-screen bg-vikinger-dark">
    <!-- Floating Navigation -->
    <LandingFloatingNav />

    <!-- Hero Section -->
    <LandingModernHeroSection />

    <!-- Features Section -->
    <LandingModernFeaturesSection />

    <!-- Stats Section -->
    <LandingModernStatsSection 
      :users-count="usersCount"
      :courses-count="coursesCount"
      :points-earned="5"
      :satisfaction="99"
    />

    <!-- Testimonials Section -->
    <LandingModernTestimonialsSection />

    <!-- CTA Section -->
    <section class="relative py-20 sm:py-32 px-4 sm:px-6 lg:px-8 xl:px-12 z-20 bg-vikinger-dark overflow-hidden">
      <!-- Starfield Canvas -->
      <canvas ref="starfieldCanvas" class="absolute inset-0 w-full h-full"></canvas>
      <div class="absolute inset-0 bg-gradient-to-b from-vikinger-dark via-vikinger-dark/90 to-vikinger-dark pointer-events-none"></div>

      <div class="relative z-10 text-center max-w-4xl mx-auto">
        <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl xl:text-6xl font-bold text-white mb-4 sm:mb-6">
          พร้อมที่จะเริ่มต้น<br />
          <span class="gradient-text">การเรียนรู้แบบใหม่</span>?
        </h2>
        <p class="text-base sm:text-lg lg:text-xl text-gray-400 mb-8 sm:mb-10 max-w-2xl mx-auto px-4">
          เริ่มต้นใช้งานฟรีวันนี้ ไม่ต้องใช้บัตรเครดิต
          พร้อมรับแต้มต้อนรับ 500 แต้ม!
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4 mb-8 sm:mb-12 px-4">
          <NuxtLink to="/auth" class="w-full sm:w-auto btn-gradient inline-flex items-center justify-center gap-2 px-8 sm:px-10 py-4 sm:py-5 text-base sm:text-lg">
            <span>สมัครใช้งานฟรี</span>
            <Icon name="heroicons:arrow-right" class="w-5 h-5" />
          </NuxtLink>
          <button class="w-full sm:w-auto btn-gradient-outline inline-flex items-center justify-center gap-2 px-8 sm:px-10 py-4 sm:py-5 text-base sm:text-lg">
            <Icon name="heroicons:phone" class="w-5 h-5" />
            <span>ติดต่อเรา</span>
          </button>
        </div>

        <div class="flex flex-wrap items-center justify-center gap-4 sm:gap-6 text-gray-400 px-4">
          <div class="flex items-center gap-2">
            <Icon name="heroicons:check-circle" class="w-4 sm:w-5 h-4 sm:h-5 text-vikinger-green" />
            <span class="text-xs sm:text-sm">ทดลองใช้ฟรี</span>
          </div>
          <div class="flex items-center gap-2">
            <Icon name="heroicons:check-circle" class="w-4 sm:w-5 h-4 sm:h-5 text-vikinger-green" />
            <span class="text-xs sm:text-sm">ไม่ต้องใช้บัตรเครดิต</span>
          </div>
          <div class="flex items-center gap-2">
            <Icon name="heroicons:check-circle" class="w-4 sm:w-5 h-4 sm:h-5 text-vikinger-green" />
            <span class="text-xs sm:text-sm">ยกเลิกได้ตลอดเวลา</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <LandingModernFooterSection />
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  layout: false,
})

// Fetch welcome data for stats
const { data: welcomeData } = await useFetch<{
  usersCount: number
  postsCount: number
  coursesCount: number
  lessonsCount: number
}>('/api/welcome', {
  baseURL: useRuntimeConfig().public.apiBase,
  default: () => ({
    usersCount: 10000,
    postsCount: 0,
    coursesCount: 500,
    lessonsCount: 0
  })
})

const usersCount = computed(() => welcomeData.value?.usersCount || 10000)
const coursesCount = computed(() => welcomeData.value?.coursesCount || 500)

// Starfield animation
const starfieldCanvas = ref<HTMLCanvasElement | null>(null)
let stars: any[] = []
let animationId: number

function initStars() {
  if (!starfieldCanvas.value) return
  const canvas = starfieldCanvas.value
  stars = []
  for (let i = 0; i < 150; i++) {
    stars.push({
      x: (Math.random() - 0.5) * canvas.width * 2,
      y: (Math.random() - 0.5) * canvas.height * 2,
      z: Math.random() * canvas.width,
      speed: Math.random() * 2 + 0.5
    })
  }
}

function animateStarfield() {
  if (!starfieldCanvas.value) return
  const canvas = starfieldCanvas.value
  const ctx = canvas.getContext('2d')
  if (!ctx) return

  ctx.fillStyle = 'rgba(29, 35, 51, 0.2)'
  ctx.fillRect(0, 0, canvas.width, canvas.height)

  const cx = canvas.width / 2
  const cy = canvas.height / 2

  stars.forEach(star => {
    star.z -= star.speed
    if (star.z <= 0) {
      star.z = canvas.width
      star.x = (Math.random() - 0.5) * canvas.width * 2
      star.y = (Math.random() - 0.5) * canvas.height * 2
    }

    const x = (star.x / star.z) * cx + cx
    const y = (star.y / star.z) * cy + cy
    const size = (1 - star.z / canvas.width) * 3
    const opacity = 1 - star.z / canvas.width

    if (x >= 0 && x <= canvas.width && y >= 0 && y <= canvas.height) {
      ctx.beginPath()
      ctx.arc(x, y, size, 0, Math.PI * 2)
      ctx.fillStyle = `rgba(255, 255, 255, ${opacity})`
      ctx.fill()
    }
  })

  animationId = requestAnimationFrame(animateStarfield)
}

function resizeCanvas() {
  if (!starfieldCanvas.value) return
  starfieldCanvas.value.width = starfieldCanvas.value.offsetWidth
  starfieldCanvas.value.height = starfieldCanvas.value.offsetHeight
  initStars()
}

onMounted(() => {
  resizeCanvas()
  animateStarfield()
  window.addEventListener('resize', resizeCanvas)
})

onUnmounted(() => {
  cancelAnimationFrame(animationId)
  window.removeEventListener('resize', resizeCanvas)
})

// SEO
useHead({
  title: 'PlearnD - เรียนบ้าง เล่นบ้าง สร้างรายได้',
  meta: [
    { name: 'description', content: 'แพลตฟอร์มการเรียนรู้แบบ Gamification ที่ทำให้การเรียนสนุกเหมือนเล่นเกม พร้อมสร้างรายได้ไปพร้อมกัน' },
    { name: 'keywords', content: 'การเรียนรู้, gamification, เรียนออนไลน์, สร้างรายได้, แต้มสะสม' }
  ]
})
</script>

<style>
/* Ensure smooth scrolling */
html {
  scroll-behavior: smooth;
}
</style>
