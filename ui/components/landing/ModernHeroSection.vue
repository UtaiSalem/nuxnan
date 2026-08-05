<template>
  <section id="hero" class="relative min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8 xl:px-12 pt-20 pb-32 overflow-hidden bg-gradient-to-b from-vikinger-purple/20 via-vikinger-dark to-vikinger-dark">
    <!-- Particle Background Canvas -->
    <canvas ref="particleCanvas" class="absolute inset-0 w-full h-full pointer-events-none z-0 opacity-60"></canvas>
    
    <!-- Orbital Elements -->
    <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-10">
      <div class="relative w-[300px] h-[300px] sm:w-[400px] sm:h-[400px] lg:w-[600px] lg:h-[600px]" 
           style="animation: spin 60s linear infinite;">
        <!-- Orbit rings -->
        <div class="absolute inset-0 flex items-center justify-center">
          <div class="absolute w-[180px] h-[180px] sm:w-[250px] sm:h-[250px] lg:w-[350px] lg:h-[350px] border border-white/5 rounded-full"></div>
        </div>
        <div class="absolute inset-0 flex items-center justify-center">
          <div class="absolute w-[280px] h-[280px] sm:w-[380px] sm:h-[380px] lg:w-[550px] lg:h-[550px] border border-white/5 rounded-full"></div>
        </div>
        
        <!-- Orbital Cards -->
        <div class="absolute glass-card p-2 sm:p-3" style="left: 50%; top: 10%; transform: translate(-50%, -50%); animation: float 6s ease-in-out infinite;">
          <Icon name="heroicons:play" class="w-4 h-4 sm:w-5 sm:h-5 text-vikinger-cyan" />
          <span class="text-[10px] sm:text-xs text-white/80 mt-1 block">Play</span>
        </div>
        <div class="absolute glass-card p-2 sm:p-3" style="left: 85%; top: 35%; transform: translate(-50%, -50%); animation: float 6s ease-in-out infinite 1s;">
          <Icon name="heroicons:book-open" class="w-4 h-4 sm:w-5 sm:h-5 text-vikinger-green" />
          <span class="text-[10px] sm:text-xs text-white/80 mt-1 block">Learn</span>
        </div>
        <div class="absolute glass-card p-2 sm:p-3" style="left: 85%; top: 65%; transform: translate(-50%, -50%); animation: float 6s ease-in-out infinite 2s;">
          <Icon name="heroicons:currency-dollar" class="w-4 h-4 sm:w-5 sm:h-5 text-vikinger-orange" />
          <span class="text-[10px] sm:text-xs text-white/80 mt-1 block">Earn</span>
        </div>
        <div class="absolute glass-card p-2 sm:p-3" style="left: 50%; top: 90%; transform: translate(-50%, -50%); animation: float 6s ease-in-out infinite 3s;">
          <Icon name="heroicons:trophy" class="w-4 h-4 sm:w-5 sm:h-5 text-vikinger-yellow" />
          <span class="text-[10px] sm:text-xs text-white/80 mt-1 block">Reward</span>
        </div>
        <div class="absolute glass-card p-2 sm:p-3" style="left: 15%; top: 65%; transform: translate(-50%, -50%); animation: float 6s ease-in-out infinite 4s;">
          <Icon name="heroicons:users" class="w-4 h-4 sm:w-5 sm:h-5 text-vikinger-purple" />
          <span class="text-[10px] sm:text-xs text-white/80 mt-1 block">Community</span>
        </div>
        <div class="absolute glass-card p-2 sm:p-3" style="left: 15%; top: 35%; transform: translate(-50%, -50%); animation: float 6s ease-in-out infinite 5s;">
          <Icon name="heroicons:star" class="w-4 h-4 sm:w-5 sm:h-5 text-vikinger-pink" />
          <span class="text-[10px] sm:text-xs text-white/80 mt-1 block">Badge</span>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="relative z-20 text-center max-w-5xl mx-auto">
      <!-- Badge -->
      <div class="inline-flex items-center gap-2 px-4 py-2 bg-vikinger-purple/10 border border-vikinger-purple/30 rounded-full mb-6 sm:mb-8 animate-fade-in">
        <span class="w-2 h-2 bg-vikinger-green rounded-full animate-pulse"></span>
        <span class="text-xs sm:text-sm text-vikinger-cyan font-medium">แพลตฟอร์มการเรียนรู้แบบ Gamification</span>
      </div>

      <!-- Main Heading -->
      <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl xl:text-7xl font-bold text-white mb-4 sm:mb-6 leading-tight animate-fade-in-up">
        <span class="block">เรียนบ้าง เล่นบ้าง</span>
        <span class="block gradient-text">สร้างรายได้ด้วย PlearnD</span>
      </h1>

      <!-- Subtitle -->
      <p class="text-base sm:text-lg lg:text-xl text-gray-400 max-w-2xl mx-auto mb-8 sm:mb-10 leading-relaxed animate-fade-in-up animation-delay-200 px-4">
        เปลี่ยนการเรียนรู้ให้สนุกเหมือนเล่นเกม
        <span class="text-white font-medium">รับแต้ม รางวัล และสร้างรายได้</span>
        ไปพร้อมกัน
      </p>

      <!-- CTA Buttons -->
      <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4 animate-fade-in-up animation-delay-400 px-4">
        <NuxtLink to="/auth" class="w-full sm:w-auto btn-gradient inline-flex items-center justify-center gap-2 px-6 sm:px-8 py-3 sm:py-4">
          <span>เริ่มต้นใช้งาน</span>
          <Icon name="heroicons:arrow-right" class="w-5 h-5" />
        </NuxtLink>
        <button class="w-full sm:w-auto btn-gradient-outline inline-flex items-center justify-center gap-2 px-6 sm:px-8 py-3 sm:py-4">
          <Icon name="heroicons:play" class="w-5 h-5" />
          <span>ดูวิดีโอแนะนำ</span>
        </button>
      </div>

      <!-- Trust Badges -->
      <div class="mt-12 sm:mt-16 flex flex-wrap items-center justify-center gap-4 sm:gap-8 opacity-60 animate-fade-in-up animation-delay-600">
        <div class="flex items-center gap-2">
          <Icon name="heroicons:shield-check" class="w-4 h-4 sm:w-5 sm:h-5 text-vikinger-green" />
          <span class="text-xs sm:text-sm text-gray-400">ปลอดภัย 100%</span>
        </div>
        <div class="flex items-center gap-2">
          <Icon name="heroicons:bolt" class="w-4 h-4 sm:w-5 sm:h-5 text-vikinger-orange" />
          <span class="text-xs sm:text-sm text-gray-400">เร็วทันใจ</span>
        </div>
        <div class="flex items-center gap-2">
          <Icon name="heroicons:chat-bubble-left-right" class="w-4 h-4 sm:w-5 sm:h-5 text-vikinger-cyan" />
          <span class="text-xs sm:text-sm text-gray-400">สนับสนุน 24/7</span>
        </div>
      </div>
    </div>

    <!-- Dashboard Mockup Preview -->
    <div class="absolute bottom-0 left-1/2 -translate-x-1/2 translate-y-1/3 w-full max-w-4xl px-4 z-30 hidden lg:block" style="perspective: 1000px;">
      <div class="glass-card overflow-hidden shadow-2xl" style="transform: rotateX(5deg);">
        <!-- Browser Chrome -->
        <div class="flex items-center gap-2 px-4 py-3 bg-vikinger-dark-200/50 border-b border-white/5">
          <div class="flex gap-1.5">
            <div class="w-3 h-3 rounded-full bg-red-500"></div>
            <div class="w-3 h-3 rounded-full bg-amber-500"></div>
            <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
          </div>
          <div class="flex-1 flex justify-center">
            <div class="px-4 py-1 bg-vikinger-dark-300 rounded-lg text-xs text-gray-400">
              www.plearnd.com/dashboard
            </div>
          </div>
        </div>
        
        <!-- Dashboard Preview Content -->
        <div class="p-4 sm:p-6 grid grid-cols-3 gap-4">
          <div class="glass-card p-4">
            <div class="flex items-center justify-between mb-3">
              <span class="text-xs text-gray-400">แต้มสะสม</span>
              <Icon name="heroicons:star" class="w-5 h-5 text-vikinger-yellow" />
            </div>
            <div class="text-2xl font-bold text-white">12,500</div>
            <div class="flex items-center gap-1 mt-2 text-vikinger-green text-xs">
              <Icon name="heroicons:arrow-trending-up" class="w-4 h-4" />
              <span>+245 วันนี้</span>
            </div>
          </div>

          <div class="glass-card p-4">
            <div class="flex items-center justify-between mb-3">
              <span class="text-xs text-gray-400">คอร์สที่เรียน</span>
              <Icon name="heroicons:book-open" class="w-5 h-5 text-vikinger-cyan" />
            </div>
            <div class="text-2xl font-bold text-white">8</div>
            <div class="text-xs text-gray-400 mt-2">3 กำลังเรียน</div>
          </div>

          <div class="glass-card p-4">
            <div class="flex items-center justify-between mb-3">
              <span class="text-xs text-gray-400">เหรียญรางวัล</span>
              <Icon name="heroicons:trophy" class="w-5 h-5 text-vikinger-orange" />
            </div>
            <div class="text-2xl font-bold text-white">24</div>
            <div class="text-xs text-gray-400 mt-2">3 ใหม่</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Scroll Indicator -->
    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 animate-bounce z-40 hidden sm:block">
      <Icon name="heroicons:chevron-down" class="w-6 h-6 text-gray-400" />
    </div>
  </section>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
const particleCanvas = ref<HTMLCanvasElement | null>(null)
let particles: any[] = []
let animationId: number

class Particle {
  x: number
  y: number
  vx: number
  vy: number
  radius: number
  color: string
  canvas: HTMLCanvasElement

  constructor(canvas: HTMLCanvasElement) {
    this.canvas = canvas
    this.x = Math.random() * canvas.width
    this.y = Math.random() * canvas.height
    this.vx = (Math.random() - 0.5) * 0.5
    this.vy = (Math.random() - 0.5) * 0.5
    this.radius = Math.random() * 2 + 1
    this.color = Math.random() > 0.5 ? '#615dfa' : '#23d2e2'
  }

  update() {
    this.x += this.vx
    this.y += this.vy
    if (this.x < 0 || this.x > this.canvas.width) this.vx *= -1
    if (this.y < 0 || this.y > this.canvas.height) this.vy *= -1
  }

  draw(ctx: CanvasRenderingContext2D) {
    ctx.beginPath()
    ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2)
    ctx.fillStyle = this.color
    ctx.fill()
  }
}

function initParticles() {
  if (!particleCanvas.value) return
  const canvas = particleCanvas.value
  particles = []
  const count = Math.min(60, Math.floor((canvas.width * canvas.height) / 20000))
  for (let i = 0; i < count; i++) {
    particles.push(new Particle(canvas))
  }
}

function drawConnections(ctx: CanvasRenderingContext2D) {
  const maxDist = 100
  for (let i = 0; i < particles.length; i++) {
    for (let j = i + 1; j < particles.length; j++) {
      const dx = particles[i].x - particles[j].x
      const dy = particles[i].y - particles[j].y
      const dist = Math.sqrt(dx * dx + dy * dy)
      if (dist < maxDist) {
        const opacity = (1 - dist / maxDist) * 0.3
        ctx.beginPath()
        ctx.moveTo(particles[i].x, particles[i].y)
        ctx.lineTo(particles[j].x, particles[j].y)
        ctx.strokeStyle = `rgba(97, 93, 250, ${opacity})`
        ctx.lineWidth = 1
        ctx.stroke()
      }
    }
  }
}

function animate() {
  if (!particleCanvas.value) return
  const canvas = particleCanvas.value
  const ctx = canvas.getContext('2d')
  if (!ctx) return

  ctx.clearRect(0, 0, canvas.width, canvas.height)
  particles.forEach(p => {
    p.update()
    p.draw(ctx)
  })
  drawConnections(ctx)
  animationId = requestAnimationFrame(animate)
}

function resizeCanvas() {
  if (!particleCanvas.value) return
  particleCanvas.value.width = window.innerWidth
  particleCanvas.value.height = window.innerHeight
  initParticles()
}

onMounted(() => {
  resizeCanvas()
  animate()
  window.addEventListener('resize', resizeCanvas)
})

onUnmounted(() => {
  cancelAnimationFrame(animationId)
  window.removeEventListener('resize', resizeCanvas)
})
</script>

<style scoped>
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@keyframes float {
  0%, 100% { transform: translate(-50%, -50%) translateY(0); }
  50% { transform: translate(-50%, -50%) translateY(-15px); }
}

.animate-fade-in {
  animation: fadeIn 0.8s ease-out forwards;
}

.animate-fade-in-up {
  animation: fadeInUp 0.8s ease-out forwards;
  opacity: 0;
}

.animation-delay-200 {
  animation-delay: 0.2s;
}

.animation-delay-400 {
  animation-delay: 0.4s;
}

.animation-delay-600 {
  animation-delay: 0.6s;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
