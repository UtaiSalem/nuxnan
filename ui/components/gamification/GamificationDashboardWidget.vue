<script setup lang="ts">
import { onMounted } from 'vue'
import { useGamificationProgress } from '~/composables/useGamificationProgress'
import LevelProgressBar from './LevelProgressBar.vue'
import DailyQuestCard from './DailyQuestCard.vue'

const { progress, dashboard, quests, isLoading, fetchAll, claimQuest } = useGamificationProgress()

const handleClaim = async (questId: number) => {
  try {
    const result = await claimQuest(questId)
    if (result.success) {
      // toast success
    }
  } catch (error) {
    console.error('Claim quest error:', error)
  }
}

onMounted(() => {
  fetchAll()
})
</script>

<template>
  <div class="gamification-dashboard-widget space-y-6">
    <!-- Level & XP Section -->
    <div v-if="progress" class="p-5 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white">ความคืบหน้าของฉัน</h3>
        <div v-if="progress.streak" class="flex items-center gap-1 text-orange-500 font-bold bg-orange-50 px-3 py-1 rounded-full text-sm">
          <span>🔥</span>
          <span>{{ progress.streak.count }} วัน</span>
        </div>
      </div>

      <LevelProgressBar 
        :level="progress.level"
        :current-xp="progress.current_xp"
        :xp-for-next-level="progress.xp_for_next_level"
        :progress-percentage="progress.progress_percentage"
      />

      <div v-if="dashboard" class="grid grid-cols-2 gap-4 mt-6 pt-6 border-t border-gray-100 dark:border-gray-700">
        <div class="text-center">
          <p class="text-xs text-gray-500 mb-1 uppercase tracking-wider font-bold">วันนี้ได้แต้ม</p>
          <p class="text-xl font-black text-green-600">+{{ dashboard.today.points }}</p>
        </div>
        <div class="text-center border-l border-gray-100 dark:border-gray-700">
          <p class="text-xs text-gray-500 mb-1 uppercase tracking-wider font-bold">วันนี้ได้ XP</p>
          <p class="text-xl font-black text-blue-600">+{{ dashboard.today.xp }}</p>
        </div>
      </div>
    </div>

    <!-- Daily Quests Section -->
    <div v-if="quests && quests.length > 0">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white">ภารกิจประจำวัน</h3>
        <span class="text-xs text-primary-600 font-bold hover:underline cursor-pointer">ดูทั้งหมด</span>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-1 gap-4">
        <DailyQuestCard 
          v-for="quest in quests" 
          :key="quest.id" 
          :quest="quest"
          @claim="handleClaim"
        />
      </div>
    </div>

    <div v-if="isLoading && !progress" class="animate-pulse space-y-4">
      <div class="h-40 bg-gray-200 dark:bg-gray-700 rounded-2xl"></div>
      <div class="h-24 bg-gray-200 dark:bg-gray-700 rounded-2xl"></div>
      <div class="h-24 bg-gray-200 dark:bg-gray-700 rounded-2xl"></div>
    </div>
  </div>
</template>
