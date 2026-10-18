<script setup>
import { Icon } from '@iconify/vue'

// เส้นทางเรียนจินตคณิต (นิ้วมือ → ลูกคิด → คิดในใจ) — เรียงตามลำดับการเรียนรู้
const learningPath = [
  { step: 1, title: 'ฝึกนิ้วมือ', description: 'แทนจำนวน 0–99 ด้วยนิ้วสองมือ (หัวแม่มือ=5)', icon: 'fluent:hand-left-24-filled', route: '/practice/fingers', gradient: 'from-sky-400 to-blue-500' },
  { step: 2, title: 'คิดเลขด้วยนิ้วมือ', description: 'บวก/ลบ แล้วเลือกท่ามือที่เป็นคำตอบ', icon: 'fluent:hand-draw-24-filled', route: '/practice/finger-math', gradient: 'from-violet-500 to-purple-500' },
  { step: 3, title: 'นิ้วมือ ↔ ลูกคิด', description: 'เชื่อมจำนวนจากนิ้วมือสู่ตำแหน่งบนลูกคิด', icon: 'fluent:arrow-swap-24-filled', route: '/practice/bridge', gradient: 'from-amber-400 to-orange-500' },
  { step: 4, title: 'ฝึกลูกคิด', description: 'เลื่อนเม็ด soroban + โหมดซ่อนลูกคิดฝึกในใจ', icon: 'fluent:calculator-24-filled', route: '/practice/abacus', gradient: 'from-emerald-400 to-teal-500' },
  { step: 5, title: 'คิดในใจ (Anzan)', description: 'ตัวเลขโผล่ทีละตัว บวก/ลบในใจ แล้วตอบผลรวม', icon: 'fluent:brain-circuit-24-filled', route: '/practice/anzan', gradient: 'from-fuchsia-500 to-purple-600' }
]

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
  <div>
    <div class="flex items-center gap-2 mb-1 flex-wrap">
      <Icon icon="fluent:hat-graduation-24-filled" class="w-5 h-5 text-vikinger-purple dark:text-vikinger-cyan shrink-0" />
      <h2 class="font-heading text-xl sm:text-2xl font-bold text-gray-900 dark:text-white break-words">เรียนจินตคณิต</h2>
    </div>
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4 break-words">เส้นทางฝึกจากนิ้วมือ ไปสู่ลูกคิด และคิดในใจ</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
</template>
