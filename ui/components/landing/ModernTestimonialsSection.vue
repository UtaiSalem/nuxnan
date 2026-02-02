<template>
  <section id="testimonials" class="relative py-20 sm:py-32 px-4 sm:px-6 lg:px-8 xl:px-12 z-20 bg-vikinger-dark overflow-hidden">
    <!-- Section Header -->
    <div class="text-center max-w-3xl mx-auto mb-12 sm:mb-16 section-reveal" ref="sectionHeader">
      <span class="inline-block px-4 py-2 bg-vikinger-pink/10 border border-vikinger-pink/30 rounded-full text-xs sm:text-sm text-vikinger-pink font-medium mb-4 sm:mb-6">
        เสียงจากผู้ใช้งาน
      </span>
      <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-4 sm:mb-6">
        สิ่งที่ผู้เรียนพูด
        <span class="gradient-text">ถึงเรา</span>
      </h2>
      <p class="text-sm sm:text-base lg:text-lg text-gray-400 px-4">
        ฟังเสียงจากผู้เรียนที่ใช้แพลตฟอร์มของเรา
      </p>
    </div>

    <!-- Marquee Row 1 -->
    <div class="relative mb-4 sm:mb-6">
      <!-- Fade edges -->
      <div class="absolute left-0 top-0 bottom-0 w-16 sm:w-32 bg-gradient-to-r from-vikinger-dark to-transparent z-10 pointer-events-none"></div>
      <div class="absolute right-0 top-0 bottom-0 w-16 sm:w-32 bg-gradient-to-l from-vikinger-dark to-transparent z-10 pointer-events-none"></div>
      
      <div class="marquee-track" :class="{ 'paused': isPaused }" @mouseenter="isPaused = true" @mouseleave="isPaused = false">
        <TestimonialCard 
          v-for="(testimonial, index) in [...testimonials1, ...testimonials1]" 
          :key="`row1-${index}`"
          :testimonial="testimonial"
        />
      </div>
    </div>

    <!-- Marquee Row 2 - Reverse -->
    <div class="relative">
      <!-- Fade edges -->
      <div class="absolute left-0 top-0 bottom-0 w-16 sm:w-32 bg-gradient-to-r from-vikinger-dark to-transparent z-10 pointer-events-none"></div>
      <div class="absolute right-0 top-0 bottom-0 w-16 sm:w-32 bg-gradient-to-l from-vikinger-dark to-transparent z-10 pointer-events-none"></div>
      
      <div class="marquee-track marquee-reverse" :class="{ 'paused': isPaused }" @mouseenter="isPaused = true" @mouseleave="isPaused = false">
        <TestimonialCard 
          v-for="(testimonial, index) in [...testimonials2, ...testimonials2]" 
          :key="`row2-${index}`"
          :testimonial="testimonial"
        />
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
interface Testimonial {
  name: string
  role: string
  avatar: string
  content: string
  rating: number
  gradientFrom: string
  gradientTo: string
}

const isPaused = ref(false)
const sectionHeader = ref<HTMLElement | null>(null)

const testimonials1: Testimonial[] = [
  {
    name: 'คุณสมชาย ใจดี',
    role: 'นักเรียน ม.ปลาย',
    avatar: 'S',
    content: 'เรียนสนุกมากครับ ได้แต้มแลกของรางวัลด้วย ทำให้อยากเรียนทุกวันเลย',
    rating: 5,
    gradientFrom: 'from-vikinger-cyan',
    gradientTo: 'to-vikinger-purple'
  },
  {
    name: 'คุณสมหญิง รักเรียน',
    role: 'นักศึกษามหาวิทยาลัย',
    avatar: 'Y',
    content: 'ระบบ Quest ทำให้การเรียนไม่น่าเบื่อ ได้ Badge สวยๆ มาโชว์เพื่อนด้วย',
    rating: 5,
    gradientFrom: 'from-vikinger-green',
    gradientTo: 'to-vikinger-cyan'
  },
  {
    name: 'ครูวิชัย สอนดี',
    role: 'ครูผู้สอน',
    avatar: 'W',
    content: 'สร้างคอร์สง่าย มีรายได้เสริม นักเรียนก็สนุกกับการเรียน Win-Win ทุกฝ่าย',
    rating: 5,
    gradientFrom: 'from-vikinger-purple',
    gradientTo: 'to-vikinger-pink'
  }
]

const testimonials2: Testimonial[] = [
  {
    name: 'คุณมานี รักเรียน',
    role: 'ผู้ปกครอง',
    avatar: 'M',
    content: 'ลูกติดเกมน้อยลง เปลี่ยนมาติดเรียนแทน ขอบคุณ PlearnD มากค่ะ',
    rating: 5,
    gradientFrom: 'from-vikinger-orange',
    gradientTo: 'to-vikinger-yellow'
  },
  {
    name: 'คุณณัฐพล เก่งมาก',
    role: 'Freelancer',
    avatar: 'N',
    content: 'เรียน Skill ใหม่ๆ ได้ฟรี แถมได้แต้มแลกเงินด้วย คุ้มค่ามากครับ',
    rating: 5,
    gradientFrom: 'from-vikinger-pink',
    gradientTo: 'to-vikinger-purple'
  },
  {
    name: 'คุณกานต์ ตั้งใจ',
    role: 'เจ้าของธุรกิจ',
    avatar: 'K',
    content: 'ใช้ฝึกพนักงาน ได้ผลดีมาก พนักงานตั้งใจเรียนเพราะอยากได้รางวัล',
    rating: 5,
    gradientFrom: 'from-vikinger-cyan',
    gradientTo: 'to-vikinger-green'
  }
]

onMounted(() => {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('revealed')
        observer.unobserve(entry.target)
      }
    })
  }, { threshold: 0.1 })

  if (sectionHeader.value) observer.observe(sectionHeader.value)
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

.marquee-track {
  display: flex;
  gap: 1rem;
  animation: marquee 40s linear infinite;
}

.marquee-track.paused {
  animation-play-state: paused;
}

.marquee-reverse {
  animation-direction: reverse;
}

@keyframes marquee {
  0% { transform: translateX(0); }
  100% { transform: translateX(-50%); }
}

@media (min-width: 640px) {
  .marquee-track {
    gap: 1.5rem;
  }
}
</style>
