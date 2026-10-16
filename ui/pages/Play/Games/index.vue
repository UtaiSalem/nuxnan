<script setup>
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'main'
})

// Game data — gradient = สีการ์ด (tailwind from/to), glow = สีเรืองตอน hover (rgba)
const games = [
  {
    title: 'Cross Math',
    description: 'ปริศนาตัวเลข เติมตัวเลขให้สมการถูกต้องทั้งแนวนอนและแนวตั้ง',
    icon: 'fluent:calculator-24-regular',
    route: '/play/games/cross-math-game',
    gradient: 'from-amber-400 to-orange-500',
    glow: 'rgba(255,149,0,0.35)'
  },
  {
    title: 'English Vocab Match',
    description: 'จับคู่คำศัพท์ภาษาอังกฤษกับความหมายภาษาไทย',
    icon: 'fluent:book-open-24-regular',
    route: '/play/games/english-vocab-game',
    gradient: 'from-emerald-400 to-green-500',
    glow: 'rgba(0,230,118,0.35)'
  },
  {
    title: 'เกมทายตัวเลข',
    description: 'ทายตัวเลขระหว่าง 1-100 ในจำนวนครั้งน้อยที่สุด',
    icon: 'fluent:number-symbol-24-regular',
    route: '/play/games/guessing-number-game',
    gradient: 'from-indigo-500 to-blue-500',
    glow: 'rgba(97,93,250,0.35)'
  },
  {
    title: 'เกม XO',
    description: 'เกม tic-tac-toe แบบคลาสสิกกับเพื่อนหรือ AI',
    icon: 'fluent:grid-24-regular',
    route: '/play/games/xo-game',
    gradient: 'from-cyan-400 to-sky-500',
    glow: 'rgba(35,210,226,0.35)'
  },
  {
    title: 'เกมงู',
    description: 'ควบคุมงูให้กินอาหารและหลีกเลี่ยงกำแพง',
    icon: 'fluent:animal-turtle-24-regular',
    route: '/play/games/snake-game',
    gradient: 'from-violet-500 to-fuchsia-500',
    glow: 'rgba(97,93,250,0.35)'
  },
  {
    title: 'Typing Master',
    description: 'ฝึกพิมพ์เร็วและแม่นยำ พร้อมโหมดท้าทายหลากหลาย',
    icon: 'fluent:keyboard-24-regular',
    route: '/play/games/typing',
    gradient: 'from-pink-500 to-rose-500',
    glow: 'rgba(255,107,129,0.35)'
  },
  {
    title: 'Mental Math',
    description: 'ฝึกคำนวณเลขในหัว คิดเร็ว ตอบแม่น แข่งกับเวลา',
    icon: 'fluent:brain-circuit-24-filled',
    route: '/play/games/mental-math-game',
    gradient: 'from-teal-400 to-cyan-500',
    glow: 'rgba(35,210,226,0.35)'
  },
  {
    title: 'เกมจับคู่',
    description: 'จับคู่ภาพและคำเพื่อฝึกความจำและการคิด',
    icon: 'fluent:brain-circuit-24-regular',
    route: '/play/games/mental-match',
    gradient: 'from-orange-400 to-pink-500',
    glow: 'rgba(255,149,0,0.35)'
  }
]

// เส้นทางเรียนจินตคณิต (นิ้วมือ → ลูกคิด → คิดในใจ) — เรียงตามลำดับการเรียนรู้
const learningPath = [
  {
    step: 1,
    title: 'ฝึกนิ้วมือ',
    description: 'แทนจำนวน 0–99 ด้วยนิ้วสองมือ (หัวแม่มือ=5)',
    icon: 'fluent:hand-left-24-filled',
    route: '/practice/fingers',
    gradient: 'from-sky-400 to-blue-500'
  },
  {
    step: 2,
    title: 'คิดเลขด้วยนิ้วมือ',
    description: 'บวก/ลบ แล้วเลือกท่ามือที่เป็นคำตอบ',
    icon: 'fluent:hand-draw-24-filled',
    route: '/practice/finger-math',
    gradient: 'from-violet-500 to-purple-500'
  },
  {
    step: 3,
    title: 'นิ้วมือ ↔ ลูกคิด',
    description: 'เชื่อมจำนวนจากนิ้วมือสู่ตำแหน่งบนลูกคิด',
    icon: 'fluent:arrow-swap-24-filled',
    route: '/practice/bridge',
    gradient: 'from-amber-400 to-orange-500'
  },
  {
    step: 4,
    title: 'ฝึกลูกคิด',
    description: 'เลื่อนเม็ด soroban + โหมดซ่อนลูกคิดฝึกในใจ',
    icon: 'fluent:calculator-24-filled',
    route: '/practice/abacus',
    gradient: 'from-emerald-400 to-teal-500'
  },
  {
    step: 5,
    title: 'คิดในใจ (Anzan)',
    description: 'ตัวเลขโผล่ทีละตัว บวก/ลบในใจ แล้วตอบผลรวม',
    icon: 'fluent:brain-circuit-24-filled',
    route: '/practice/anzan',
    gradient: 'from-fuchsia-500 to-purple-600'
  }
]

// ความก้าวหน้า + ปลดล็อกด่าน (stage id = ส่วนท้ายของ route)
const { levelOf, unlocked, accuracyPct, statOf } = useLessonProgress()
const stageId = (item) => item.route.split('/').pop()
// resolve จริง: `<component :is="'NuxtLink'">` แบบสตริงจะไม่นำทาง ต้องใช้ตัว component
const NuxtLinkComp = resolveComponent('NuxtLink')

const LEVEL_META = {
  locked: { label: 'ล็อก', icon: 'fluent:lock-closed-24-filled', cls: 'bg-gray-100 text-gray-400 dark:bg-vikinger-dark-200 dark:text-gray-500' },
  learning: { label: 'กำลังเรียน', icon: 'fluent:book-24-filled', cls: 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' },
  fluent: { label: 'คล่อง', icon: 'fluent:flash-24-filled', cls: 'bg-blue-100 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300' },
  mastered: { label: 'เชี่ยวชาญ', icon: 'fluent:trophy-24-filled', cls: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' }
}
</script>

<template>
  <div class="max-w-6xl mx-auto px-4 py-6 sm:py-10">
    <!-- Hero band (Vikinger dark + glow) -->
    <div class="relative overflow-hidden rounded-vikinger-xl bg-gradient-dark border border-vikinger-dark-50/40 p-6 sm:p-10 mb-6 sm:mb-10">
      <div class="pointer-events-none absolute -top-16 -right-10 w-56 h-56 sm:w-72 sm:h-72 bg-glow-purple rounded-full blur-2xl"></div>
      <div class="pointer-events-none absolute -bottom-20 -left-10 w-56 h-56 sm:w-72 sm:h-72 bg-glow-cyan rounded-full blur-2xl"></div>

      <div class="relative flex flex-col items-start gap-3 sm:gap-4">
        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/15 text-vikinger-cyan text-xs font-bold uppercase tracking-widest">
          <Icon icon="fluent:games-24-filled" class="w-4 h-4" />
          Play Zone
        </span>
        <h1 class="font-heading text-3xl sm:text-5xl font-extrabold text-white leading-tight break-words">
          เกมส์สนุก ๆ
        </h1>
        <p class="text-sm sm:text-lg text-gray-300 max-w-xl break-words">
          เลือกเกมที่คุณต้องการเล่น ฝึกสมอง แข่งกับเวลา และไต่อันดับผู้นำ
        </p>
        <div class="flex items-center gap-2 mt-1 text-xs sm:text-sm text-gray-400">
          <Icon icon="fluent:sparkle-24-filled" class="w-4 h-4 text-vikinger-yellow" />
          <span>{{ games.length }} เกมให้เลือกเล่น</span>
        </div>
      </div>
    </div>

    <!-- Learning path (จินตคณิต) -->
    <div class="mb-8 sm:mb-10">
      <div class="flex items-center gap-2 mb-1 flex-wrap">
        <Icon icon="fluent:hat-graduation-24-filled" class="w-5 h-5 text-vikinger-purple dark:text-vikinger-cyan shrink-0" />
        <h2 class="font-heading text-xl sm:text-2xl font-bold text-gray-900 dark:text-white break-words">เรียนจินตคณิต</h2>
      </div>
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-4 break-words">เส้นทางฝึกจากนิ้วมือ ไปสู่ลูกคิด และคิดในใจ</p>

      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <component
          :is="unlocked(stageId(item)) ? NuxtLinkComp : 'div'"
          v-for="item in learningPath"
          :key="item.route"
          :to="unlocked(stageId(item)) ? item.route : undefined"
          class="group relative overflow-hidden rounded-vikinger-lg bg-white dark:bg-vikinger-dark-100 border border-gray-100 dark:border-vikinger-dark-50/40 shadow-card dark:shadow-card-dark transition-all duration-300 block"
          :class="unlocked(stageId(item)) ? 'hover:shadow-vikinger-lg hover:-translate-y-1 cursor-pointer' : 'opacity-60 cursor-not-allowed'"
        >
          <div class="h-1.5 w-full bg-gradient-to-r" :class="item.gradient"></div>
          <div class="relative p-4 sm:p-5">
            <div class="flex items-center gap-2 mb-3">
              <div
                class="w-12 h-12 rounded-vikinger bg-gradient-to-br flex items-center justify-center shadow-lg flex-shrink-0 transition-transform duration-300"
                :class="[item.gradient, unlocked(stageId(item)) ? 'group-hover:scale-110' : 'grayscale']"
              >
                <Icon :icon="unlocked(stageId(item)) ? item.icon : 'fluent:lock-closed-24-filled'" class="w-6 h-6 text-white" />
              </div>
              <span
                class="ml-auto shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold whitespace-nowrap"
                :class="LEVEL_META[levelOf(stageId(item))].cls"
              >
                <Icon :icon="LEVEL_META[levelOf(stageId(item))].icon" class="w-3 h-3" />
                {{ LEVEL_META[levelOf(stageId(item))].label }}
              </span>
            </div>
            <h3
              class="font-heading text-base sm:text-lg font-bold text-gray-900 dark:text-white mb-1 break-words transition-colors"
              :class="unlocked(stageId(item)) ? 'group-hover:text-vikinger-purple dark:group-hover:text-vikinger-cyan' : ''"
            >
              {{ item.step }}. {{ item.title }}
            </h3>
            <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 leading-relaxed break-words min-h-[32px]">
              {{ item.description }}
            </p>
            <div v-if="statOf(stageId(item)).total > 0" class="mt-2.5">
              <div class="flex items-center justify-between text-[10px] font-medium text-gray-400 mb-1">
                <span>ความแม่นยำ</span>
                <span class="tabular-nums">{{ accuracyPct(stageId(item)) }}% · {{ statOf(stageId(item)).total }} ข้อ</span>
              </div>
              <div class="h-1.5 rounded-full bg-gray-100 dark:bg-vikinger-dark-200 overflow-hidden">
                <div class="h-full rounded-full bg-gradient-to-r transition-all" :class="item.gradient" :style="{ width: accuracyPct(stageId(item)) + '%' }"></div>
              </div>
            </div>
            <div v-else-if="!unlocked(stageId(item))" class="mt-2.5 flex items-center gap-1 text-[10px] text-gray-400">
              <Icon icon="fluent:lock-closed-24-regular" class="w-3 h-3 shrink-0" />
              ปลดล็อกเมื่อผ่านด่านก่อนหน้า (แม่นยำ ≥85%)
            </div>
          </div>
        </component>
      </div>
    </div>

    <!-- Games Grid -->
    <div class="flex items-center gap-2 mb-4">
      <Icon icon="fluent:games-24-filled" class="w-5 h-5 text-vikinger-purple dark:text-vikinger-cyan shrink-0" />
      <h2 class="font-heading text-xl sm:text-2xl font-bold text-gray-900 dark:text-white break-words">เกมทั้งหมด</h2>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-6">
      <NuxtLink
        v-for="game in games"
        :key="game.route"
        :to="game.route"
        class="group relative overflow-hidden rounded-vikinger-lg bg-white dark:bg-vikinger-dark-100 border border-gray-100 dark:border-vikinger-dark-50/40 shadow-card dark:shadow-card-dark hover:shadow-vikinger-lg transition-all duration-300 hover:-translate-y-1 block"
      >
        <!-- Top accent -->
        <div class="h-1.5 w-full bg-gradient-to-r" :class="game.gradient"></div>

        <!-- Hover glow -->
        <div
          class="pointer-events-none absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300"
          :style="{ background: `radial-gradient(circle at 80% 0%, ${game.glow} 0%, transparent 60%)` }"
        ></div>

        <div class="relative p-5 sm:p-6">
          <div class="flex items-start justify-between gap-3 mb-4 sm:mb-5">
            <div
              class="w-14 h-14 rounded-vikinger bg-gradient-to-br flex items-center justify-center shadow-lg flex-shrink-0 transform group-hover:scale-110 transition-transform duration-300"
              :class="game.gradient"
            >
              <Icon :icon="game.icon" class="w-7 h-7 text-white" />
            </div>
            <div class="w-9 h-9 flex items-center justify-center rounded-full bg-gray-50 dark:bg-vikinger-dark-200 text-gray-400 group-hover:bg-gradient-vikinger group-hover:text-white transition-all duration-300 flex-shrink-0">
              <Icon icon="fluent:arrow-right-24-filled" class="w-5 h-5 -rotate-45 group-hover:rotate-0 transition-transform duration-300" />
            </div>
          </div>

          <h3 class="font-heading text-lg sm:text-xl font-bold text-gray-900 dark:text-white mb-2 break-words group-hover:text-vikinger-purple dark:group-hover:text-vikinger-cyan transition-colors">
            {{ game.title }}
          </h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4 break-words min-h-[40px]">
            {{ game.description }}
          </p>

          <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-gray-400 group-hover:text-vikinger-purple dark:group-hover:text-vikinger-cyan transition-colors">
            เล่นเลย
            <Icon icon="fluent:play-24-filled" class="w-3.5 h-3.5" />
          </span>
        </div>
      </NuxtLink>
    </div>

    <!-- Coming Soon -->
    <div class="mt-10 sm:mt-14">
      <div class="relative overflow-hidden rounded-vikinger-lg border-2 border-dashed border-gray-200 dark:border-vikinger-dark-50/50 bg-gray-50/60 dark:bg-vikinger-dark-200/40 p-6 sm:p-10 text-center">
        <Icon icon="fluent:rocket-24-regular" class="w-12 h-12 text-gray-300 dark:text-vikinger-dark-50 mx-auto mb-3" />
        <h3 class="font-heading text-base sm:text-lg font-bold text-gray-500 dark:text-gray-300 mb-1">
          เกมใหม่กำลังมา
        </h3>
        <p class="text-sm text-gray-400 dark:text-gray-500">
          ติดตามอัปเดตเกมใหม่ ๆ ได้เร็ว ๆ นี้
        </p>
      </div>
    </div>
  </div>
</template>
