<script setup>
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'game-layout'
})

// Game data
const games = [
  {
    title: 'เกมทายตัวเลข',
    description: 'ทายตัวเลขระหว่าง 1-100 ในจำนวนครั้งน้อยที่สุด',
    icon: 'fluent:number-symbol-24-regular',
    route: '/play/games/guessing-number-game',
    color: 'from-blue-500 to-blue-600'
  },
  {
    title: 'เกม XO',
    description: 'เกม tic-tac-toe แบบคลาสสิกกับเพื่อนหรือ AI',
    icon: 'fluent:grid-24-regular',
    route: '/play/games/xo-game',
    color: 'from-green-500 to-green-600'
  },
  {
    title: 'เกมงู',
    description: 'ควบคุมงูให้กินอาหารและหลีกเลี่ยงกำแพง',
    icon: 'fluent:animal-turtle-24-regular',
    route: '/play/games/snake-game',
    color: 'from-purple-500 to-purple-600'
  },
  {
    title: 'เกมจับคู่',
    description: 'จับคู่ภาพและคำเพื่อฝึกความจำและการคิด',
    icon: 'fluent:brain-circuit-24-regular',
    route: '/play/games/mental-match',
    color: 'from-orange-500 to-orange-600'
  }
]
</script>

<template>
  <div class="max-w-6xl mx-auto py-8">
    <!-- Header -->
    <div class="text-center mb-12">
      <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
        เกมส์สนุก ๆ
      </h1>
      <p class="text-lg text-gray-600 dark:text-gray-300">
        เลือกเกมที่คุณต้องการเล่นและท้าทายตัวเอง
      </p>
    </div>

    <!-- Games Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-8">
      <NuxtLink
        v-for="game in games"
        :key="game.route"
        :to="game.route"
        class="group relative overflow-hidden rounded-2xl bg-white dark:bg-vikinger-dark-100 shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 block border border-gray-100 dark:border-vikinger-dark-50"
      >
        <!-- Background Gradient on Hover -->
        <div class="absolute inset-0 bg-gradient-to-br opacity-0 group-hover:opacity-5 transition-opacity duration-300"
             :class="game.color"></div>
        
        <!-- Top Colored Line -->
        <div class="h-1.5 w-full bg-gradient-to-r" :class="game.color"></div>

        <!-- Content -->
        <div class="relative p-8">
          <div class="flex items-start justify-between mb-6">
              <!-- Icon Box -->
              <div class="w-14 h-14 rounded-xl bg-gradient-to-br flex items-center justify-center shadow-md transform group-hover:scale-110 transition-transform duration-300"
                   :class="game.color">
                <Icon :icon="game.icon" class="w-7 h-7 text-white" />
              </div>
              
              <!-- Arrow Icon -->
              <div class="p-2 rounded-full bg-gray-50 dark:bg-vikinger-dark-200 text-gray-400 group-hover:bg-vikinger-purple group-hover:text-white transition-colors duration-300">
                  <Icon icon="fluent:arrow-right-24-filled" class="w-5 h-5 -rotate-45 group-hover:rotate-0 transition-transform duration-300" />
              </div>
          </div>

          <!-- Title -->
          <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3 group-hover:text-vikinger-purple dark:group-hover:text-vikinger-cyan transition-colors">
            {{ game.title }}
          </h3>

          <!-- Description -->
          <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed mb-4">
            {{ game.description }}
          </p>
          
          <!-- Play Badge -->
          <div class="inline-flex items-center text-xs font-bold uppercase tracking-wider text-gray-400 group-hover:text-vikinger-purple dark:group-hover:text-vikinger-cyan transition-colors">
              Play Now
          </div>
        </div>
      </NuxtLink>
    </div>

    <!-- Coming Soon Section -->
    <div class="mt-16 text-center">
      <div class="inline-block p-8 px-12 rounded-2xl bg-gray-50 dark:bg-vikinger-dark-200/50 border-2 border-dashed border-gray-200 dark:border-vikinger-dark-50">
        <Icon icon="fluent:game-24-regular" class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
        <h3 class="text-lg font-semibold text-gray-500 dark:text-gray-400 mb-1">
          เกมใหม่กำลังมา
        </h3>
        <p class="text-sm text-gray-400 dark:text-gray-500">
          ติดตามอัปเดตเกมใหม่ๆ ได้เร็วๆ นี้
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Additional styles if needed */
</style>