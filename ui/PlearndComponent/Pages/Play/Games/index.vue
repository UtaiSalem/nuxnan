<script setup>
import { Icon } from '@iconify/vue'

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
  <div class="max-w-6xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="text-center mb-8">
      <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
        เกมส์สนุก ๆ
      </h1>
      <p class="text-lg text-gray-600 dark:text-gray-300">
        เลือกเกมที่คุณต้องการเล่นและท้าทายตัวเอง
      </p>
    </div>

    <!-- Games Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <NuxtLink
        v-for="game in games"
        :key="game.route"
        :to="game.route"
        class="group relative overflow-hidden rounded-xl bg-white dark:bg-vikinger-dark-100 shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105 border border-gray-200 dark:border-vikinger-dark-50"
      >
        <!-- Background Gradient -->
        <div class="absolute inset-0 bg-gradient-to-br opacity-10 group-hover:opacity-20 transition-opacity duration-300"
             :class="game.color"></div>

        <!-- Content -->
        <div class="relative p-6">
          <!-- Icon -->
          <div class="w-16 h-16 rounded-full bg-gradient-to-br flex items-center justify-center mb-4 shadow-lg"
               :class="game.color">
            <Icon :icon="game.icon" class="w-8 h-8 text-white" />
          </div>

          <!-- Title -->
          <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
            {{ game.title }}
          </h3>

          <!-- Description -->
          <p class="text-gray-600 dark:text-gray-300 text-sm leading-relaxed">
            {{ game.description }}
          </p>

          <!-- Play Button -->
          <div class="mt-4 flex items-center text-sm font-semibold text-vikinger-purple group-hover:text-vikinger-cyan transition-colors">
            <span>เล่นเลย</span>
            <Icon icon="fluent:arrow-right-24-regular" class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" />
          </div>
        </div>
      </NuxtLink>
    </div>

    <!-- Coming Soon Section -->
    <div class="mt-12 text-center">
      <div class="inline-block p-8 rounded-xl bg-gray-50 dark:bg-vikinger-dark-200 border-2 border-dashed border-gray-300 dark:border-vikinger-dark-50">
        <Icon icon="fluent:game-24-regular" class="w-12 h-12 text-gray-400 dark:text-gray-500 mx-auto mb-4" />
        <h3 class="text-lg font-semibold text-gray-600 dark:text-gray-400 mb-2">
          เกมใหม่กำลังมา
        </h3>
        <p class="text-gray-500 dark:text-gray-500">
          เรากำลังพัฒนาเกมใหม่ ๆ ให้คุณสนุกมากขึ้น
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Additional styles if needed */
</style>