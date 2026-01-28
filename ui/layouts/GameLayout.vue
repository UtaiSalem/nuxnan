<script setup>
import MainLayout from '~/layouts/main.vue'
import { Icon } from '@iconify/vue'

const isGamesMenuOpen = ref(true)

const games = [
  { 
      name: 'ทายตัวเลข', 
      path: '/play/games/guessing-number-game', 
      icon: 'fluent:number-symbol-24-regular',
      description: 'ทายตัวเลขปริศนาในใจ'
  },
  { 
      name: 'XO', 
      path: '/play/games/xo-game', 
      icon: 'fluent:grid-24-regular',
      description: 'เรียง 3 แถวเพื่อชนะ' 
  },
  { 
      name: 'งู', 
      path: '/play/games/snake-game', 
      icon: 'fluent:animal-turtle-24-regular',
      description: 'เลื้อยเก็บแต้มให้ยาวที่สุด' 
  },
  { 
      name: 'จับคู่', 
      path: '/play/games/mental-match', 
      icon: 'fluent:brain-circuit-24-regular',
      description: 'ทดสอบความจำจับคู่ภาพ' 
  },
]

// Formatting helper
const formatNumber = (num) => {
  return num >= 1000 ? (num / 1000).toFixed(1) + 'k' : num
}
</script>

<template>
  <MainLayout title="Games Zone">
    <template #leftWidgets>
      <div class="bg-white dark:bg-vikinger-dark-100 rounded-xl shadow-lg p-5 border border-gray-200 dark:border-vikinger-dark-50 sticky top-24">
        <!-- Header -->
        <div class="flex items-center gap-3 mb-6">
             <div class="w-10 h-10 rounded-lg bg-gradient-vikinger flex items-center justify-center text-white shadow-vikinger">
                 <Icon icon="fluent:games-24-filled" class="w-6 h-6" />
             </div>
             <div>
                 <h2 class="font-bold text-gray-900 dark:text-white text-lg">Game Center</h2>
                 <p class="text-xs text-gray-500 dark:text-gray-400">ศูนย์รวมความบันเทิง</p>
             </div>
        </div>
        
        <!-- Navigation -->
        <nav class="space-y-1">
           <NuxtLink to="/play/games" 
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group"
                active-class="bg-vikinger-purple/10 text-vikinger-purple dark:text-vikinger-cyan font-semibold ring-1 ring-vikinger-purple/20"
                :class="$route.path === '/play/games' ? '' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 hover:text-gray-900 dark:hover:text-gray-200'"
           >
                <Icon icon="fluent:home-24-regular" class="w-5 h-5 group-hover:scale-110 transition-transform" />
                <span>หน้าหลักเกม</span>
           </NuxtLink>

           <div class="my-4 border-t border-gray-100 dark:border-vikinger-dark-50/50"></div>
           
           <!-- Games Menu (Collapsible) -->
           <div>
              <button 
                  @click="isGamesMenuOpen = !isGamesMenuOpen"
                  class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg transition-all duration-200 group text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 font-medium"
              >
                  <div class="flex items-center gap-3">
                      <div class="p-1 rounded bg-orange-100 text-orange-500 dark:bg-orange-500/20 dark:text-orange-400">
                          <Icon icon="fluent:games-24-regular" class="w-5 h-5" />
                      </div>
                      <span>เกมทั้งหมด</span>
                  </div>
                  <Icon 
                      icon="fluent:chevron-down-24-regular" 
                      class="w-4 h-4 transition-transform duration-200 text-gray-400"
                      :class="{ 'rotate-180': isGamesMenuOpen }"
                  />
              </button>

              <div v-show="isGamesMenuOpen" class="mt-1 space-y-1 relative ml-3 pl-3 border-l-2 border-gray-100 dark:border-vikinger-dark-50">
                  <NuxtLink v-for="game in games" :key="game.path" :to="game.path"
                        class="block px-3 py-2 rounded-lg transition-all duration-200 group mb-1"
                        active-class="bg-vikinger-purple/5 text-vikinger-purple dark:text-vikinger-cyan font-semibold"
                        :class="$route.path === game.path ? '' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 hover:text-gray-900 dark:hover:text-gray-200'"
                  >
                        <div class="flex items-center gap-2 mb-0.5">
                            <Icon :icon="game.icon" class="w-4 h-4" :class="$route.path === game.path ? 'text-vikinger-purple dark:text-vikinger-cyan' : 'text-gray-400 group-hover:text-vikinger-purple dark:group-hover:text-vikinger-cyan'" />
                            <span class="text-sm">{{ game.name }}</span>
                        </div>
                        <p class="text-[10px] leading-tight opacity-70 pl-6">{{ game.description }}</p>
                  </NuxtLink>
              </div>
           </div>

        </nav>
        
        <!-- Stats Mini Widget (Decorator) -->
        <div class="mt-6 p-4 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white text-center shadow-lg relative overflow-hidden group hover:scale-[1.02] transition-transform cursor-pointer">
             <div class="absolute top-0 right-0 p-2 opacity-20">
                 <Icon icon="fluent:trophy-24-filled" class="w-16 h-16 transform rotate-12 group-hover:rotate-[20deg] transition-transform duration-500" />

             </div>
             <p class="text-xs font-medium text-white/80 mb-1">เล่นเกมสะสมแต้ม</p>
             <h3 class="text-xl font-bold mb-2">แลกของรางวัล!</h3>
             <div class="text-xs bg-white/20 hover:bg-white/30 backdrop-blur-sm px-3 py-1.5 rounded-lg transition-colors w-full inline-block">
                 ดูรางวัล
             </div>
        </div>
      </div>
    </template>

    <!-- Main Content Slot -->
    <div class="min-h-[500px] animate-fade-in-up">
        <slot />
    </div>

  </MainLayout>
</template>

<style scoped>
.animate-fade-in-up {
  animation: fadeInUp 0.5s ease-out;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
