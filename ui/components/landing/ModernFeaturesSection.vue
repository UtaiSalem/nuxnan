<template>
  <section id="features" class="relative py-20 sm:py-32 px-4 sm:px-6 lg:px-8 xl:px-12 z-20 bg-vikinger-dark">
    <!-- Section Header -->
    <div class="text-center max-w-3xl mx-auto mb-12 sm:mb-20 section-reveal" ref="sectionHeader">
      <span class="inline-block px-4 py-2 bg-vikinger-purple/10 border border-vikinger-purple/30 rounded-full text-xs sm:text-sm text-vikinger-cyan font-medium mb-4 sm:mb-6">
        ฟีเจอร์ครบครัน
      </span>
      <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-4 sm:mb-6">
        ทุกสิ่งที่คุณ
        <span class="gradient-text">ต้องการ</span>
      </h2>
      <p class="text-sm sm:text-base lg:text-lg text-gray-400 px-4">
        แพลตฟอร์มที่ออกแบบมาเพื่อให้การเรียนรู้สนุก และสร้างรายได้ไปพร้อมกัน
      </p>
    </div>

    <!-- Features Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 max-w-7xl mx-auto">
      <!-- Large Feature Card - Play -->
      <div class="lg:col-span-2 glass-card-hover p-6 sm:p-8 section-reveal" ref="feature1">
        <div class="flex flex-col lg:flex-row lg:items-start gap-4 sm:gap-6">
          <div class="flex-shrink-0">
            <div class="w-12 sm:w-16 h-12 sm:h-16 bg-vikinger-cyan/20 rounded-2xl flex items-center justify-center">
              <Icon name="heroicons:play" class="w-6 sm:w-8 h-6 sm:h-8 text-vikinger-cyan" />
            </div>
          </div>
          <div class="flex-1">
            <h3 class="text-lg sm:text-xl lg:text-2xl font-bold text-white mb-2 sm:mb-3">เรียนรู้ผ่านการเล่นเกม</h3>
            <p class="text-sm sm:text-base text-gray-400 mb-4 sm:mb-6 leading-relaxed">
              เปลี่ยนการเรียนที่น่าเบื่อให้กลายเป็นเกมที่สนุก ด้วยระบบ Quest, Achievement และ Leaderboard 
              ที่ทำให้คุณอยากเรียนรู้ทุกวัน
            </p>
            <div class="flex flex-wrap gap-2 sm:gap-3">
              <span class="px-2 sm:px-3 py-1 bg-white/5 rounded-full text-xs sm:text-sm text-gray-300">Quest System</span>
              <span class="px-2 sm:px-3 py-1 bg-white/5 rounded-full text-xs sm:text-sm text-gray-300">Leaderboard</span>
              <span class="px-2 sm:px-3 py-1 bg-white/5 rounded-full text-xs sm:text-sm text-gray-300">Achievements</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Feature Card - Learn -->
      <div class="glass-card-hover p-6 sm:p-8 section-reveal" ref="feature2">
        <div class="w-12 sm:w-14 h-12 sm:h-14 bg-vikinger-green/20 rounded-2xl flex items-center justify-center mb-4 sm:mb-6">
          <Icon name="heroicons:book-open" class="w-6 sm:w-7 h-6 sm:h-7 text-vikinger-green" />
        </div>
        <h3 class="text-lg sm:text-xl font-bold text-white mb-2 sm:mb-3">คอร์สเรียนคุณภาพ</h3>
        <p class="text-sm sm:text-base text-gray-400 leading-relaxed">
          เรียนรู้จากผู้เชี่ยวชาญ ด้วยเนื้อหาที่อัพเดตอยู่เสมอ พร้อมวิดีโอ แบบฝึกหัด และ Quiz
        </p>
      </div>

      <!-- Feature Card - Earn -->
      <div class="glass-card-hover p-6 sm:p-8 section-reveal" ref="feature3">
        <div class="w-12 sm:w-14 h-12 sm:h-14 bg-vikinger-orange/20 rounded-2xl flex items-center justify-center mb-4 sm:mb-6">
          <Icon name="heroicons:currency-dollar" class="w-6 sm:w-7 h-6 sm:h-7 text-vikinger-orange" />
        </div>
        <h3 class="text-lg sm:text-xl font-bold text-white mb-2 sm:mb-3">สร้างรายได้</h3>
        <p class="text-sm sm:text-base text-gray-400 leading-relaxed">
          เปลี่ยนแต้มเป็นเงินจริง หรือรับรายได้จากการสร้างคอร์สและเนื้อหาคุณภาพ
        </p>
      </div>

      <!-- Feature Card - Rewards -->
      <div class="glass-card-hover p-6 sm:p-8 section-reveal" ref="feature4">
        <div class="w-12 sm:w-14 h-12 sm:h-14 bg-vikinger-yellow/20 rounded-2xl flex items-center justify-center mb-4 sm:mb-6">
          <Icon name="heroicons:trophy" class="w-6 sm:w-7 h-6 sm:h-7 text-vikinger-yellow" />
        </div>
        <h3 class="text-lg sm:text-xl font-bold text-white mb-2 sm:mb-3">รางวัลและเหรียญ</h3>
        <p class="text-sm sm:text-base text-gray-400 leading-relaxed">
          รับเหรียญรางวัลจากการทำ Quest สะสม Badge และแลกของรางวัลพิเศษ
        </p>
      </div>

      <!-- Feature Card - Community -->
      <div class="glass-card-hover p-6 sm:p-8 section-reveal" ref="feature5">
        <div class="w-12 sm:w-14 h-12 sm:h-14 bg-vikinger-purple/20 rounded-2xl flex items-center justify-center mb-4 sm:mb-6">
          <Icon name="heroicons:users" class="w-6 sm:w-7 h-6 sm:h-7 text-vikinger-purple" />
        </div>
        <h3 class="text-lg sm:text-xl font-bold text-white mb-2 sm:mb-3">ชุมชนเรียนรู้</h3>
        <p class="text-sm sm:text-base text-gray-400 leading-relaxed">
          เข้าร่วมกลุ่มเรียน แลกเปลี่ยนความรู้ และสร้างเครือข่ายกับผู้เรียนทั่วประเทศ
        </p>
      </div>

      <!-- Large Feature Card - Security -->
      <div class="lg:col-span-2 glass-card-hover p-6 sm:p-8 section-reveal" ref="feature6">
        <div class="flex flex-col lg:flex-row lg:items-center gap-4 sm:gap-6">
          <div class="flex-1">
            <div class="w-12 sm:w-14 h-12 sm:h-14 bg-vikinger-pink/20 rounded-2xl flex items-center justify-center mb-4 sm:mb-6">
              <Icon name="heroicons:shield-check" class="w-6 sm:w-7 h-6 sm:h-7 text-vikinger-pink" />
            </div>
            <h3 class="text-lg sm:text-xl lg:text-2xl font-bold text-white mb-2 sm:mb-3">ความปลอดภัยระดับสูง</h3>
            <p class="text-sm sm:text-base text-gray-400 leading-relaxed">
              ระบบรักษาความปลอดภัยขั้นสูงด้วยการเข้ารหัส SSL, การยืนยันตัวตนแบบสองชั้น
              และการสำรองข้อมูลอัตโนมัติทุกวัน
            </p>
          </div>
          <div class="flex-shrink-0 flex items-center gap-3 sm:gap-4">
            <div class="text-center">
              <div class="w-12 sm:w-16 h-12 sm:h-16 bg-vikinger-green/20 rounded-full flex items-center justify-center mb-2">
                <Icon name="heroicons:lock-closed" class="w-6 sm:w-8 h-6 sm:h-8 text-vikinger-green" />
              </div>
              <span class="text-[10px] sm:text-xs text-gray-400">SSL</span>
            </div>
            <div class="text-center">
              <div class="w-12 sm:w-16 h-12 sm:h-16 bg-vikinger-cyan/20 rounded-full flex items-center justify-center mb-2">
                <Icon name="heroicons:finger-print" class="w-6 sm:w-8 h-6 sm:h-8 text-vikinger-cyan" />
              </div>
              <span class="text-[10px] sm:text-xs text-gray-400">2FA</span>
            </div>
            <div class="text-center">
              <div class="w-12 sm:w-16 h-12 sm:h-16 bg-vikinger-orange/20 rounded-full flex items-center justify-center mb-2">
                <Icon name="heroicons:server-stack" class="w-6 sm:w-8 h-6 sm:h-8 text-vikinger-orange" />
              </div>
              <span class="text-[10px] sm:text-xs text-gray-400">Backup</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const sectionHeader = ref<HTMLElement | null>(null)
const feature1 = ref<HTMLElement | null>(null)
const feature2 = ref<HTMLElement | null>(null)
const feature3 = ref<HTMLElement | null>(null)
const feature4 = ref<HTMLElement | null>(null)
const feature5 = ref<HTMLElement | null>(null)
const feature6 = ref<HTMLElement | null>(null)

onMounted(() => {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, index) => {
      if (entry.isIntersecting) {
        // Add staggered delay based on index
        setTimeout(() => {
          entry.target.classList.add('revealed')
        }, index * 100)
        observer.unobserve(entry.target)
      }
    })
  }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' })

  const elements = [sectionHeader, feature1, feature2, feature3, feature4, feature5, feature6]
  elements.forEach(el => {
    if (el.value) observer.observe(el.value)
  })
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
</style>
