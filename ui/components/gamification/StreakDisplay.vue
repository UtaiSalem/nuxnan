<template>
  <div class="streak-display">
    <div class="streak-card">
      <div class="streak-icon" :style="{ background: streakData.streak_level_color }">
        <span class="icon-emoji">{{ streakData.streak_icon }}</span>
      </div>
      
      <div class="streak-info">
        <h3 class="streak-title">Streak ปัจจุบัน</h3>
        <p class="streak-count">{{ streakData.current_streak }} วัน</p>
        <p class="streak-level">{{ streakData.streak_level }} Level</p>
      </div>
      
      <div class="streak-bonus">
        <p class="bonus-label">โบนัสถัดไป</p>
        <p class="bonus-amount">+{{ streakData.potential_bonus }} แต้ม</p>
        <p class="bonus-milestone">ที่ {{ streakData.next_milestone }} วัน</p>
        <p class="bonus-days">({{ streakData.days_until_next_bonus }} วัน)</p>
      </div>
    </div>
    
    <!-- Streak History -->
    <div class="streak-history" v-if="showHistory">
      <h4>ประวัติ Streak</h4>
      <div class="history-item">
        <span class="history-label">Streak ยาวนาย:</span>
        <span class="history-value">{{ streakData.longest_streak }} วัน</span>
      </div>
      <div class="history-item">
        <span class="history-label">แต้มโบนัสทั้งหมด:</span>
        <span class="history-value">{{ streakData.bonus_points_earned }} แต้ม</span>
      </div>
    </div>
    
    <!-- Toggle History Button -->
    <button class="btn-toggle-history" @click="showHistory = !showHistory">
      {{ showHistory ? 'ซ่อน' : 'แสดง' }} ประวัติ
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useGamification } from '~/composables/useGamification'

const { getStreakInfo, showStreakNotification } = useGamification()

// Reactive state
const streakData = ref<any>({
  current_streak: 0,
  longest_streak: 0,
  bonus_points_earned: 0,
  streak_level: 'Newbie',
  streak_icon: '🔥',
  streak_level_color: '#9ca3af',
  is_active_today: false,
  next_milestone: 5,
  days_until_next_bonus: 5,
  potential_bonus: 0,
})

const showHistory = ref(false)

// Load streak info on mount
onMounted(async () => {
  try {
    const data = await getStreakInfo()
    streakData.value = data
  } catch (error) {
    console.error('Failed to load streak info:', error)
  }
})
</script>

<style scoped>
.streak-display {
  width: 100%;
}

.streak-card {
  background: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%);
  border-radius: 16px;
  padding: 24px;
  color: white;
  display: flex;
  align-items: center;
  gap: 20px;
  box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
}

.streak-icon {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 40px;
  flex-shrink: 0;
}

.icon-emoji {
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

.streak-info {
  flex: 1;
}

.streak-title {
  font-size: 24px;
  font-weight: 700;
  margin: 0 0 8px 0;
}

.streak-count {
  font-size: 48px;
  font-weight: 700;
  margin: 0 0 8px 0;
  line-height: 1;
}

.streak-level {
  font-size: 18px;
  font-weight: 600;
  margin: 0;
  opacity: 0.9;
}

.streak-bonus {
  background: rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  padding: 16px;
  text-align: center;
  min-width: 150px;
}

.bonus-label {
  font-size: 14px;
  margin: 0 0 8px 0;
  opacity: 0.9;
}

.bonus-amount {
  font-size: 28px;
  font-weight: 700;
  margin: 0 0 8px 0;
  color: #ffd700;
}

.bonus-milestone {
  font-size: 16px;
  font-weight: 600;
  margin: 0 0 4px 0;
}

.bonus-days {
  font-size: 14px;
  margin: 0;
  opacity: 0.8;
}

.streak-history {
  background: white;
  border-radius: 12px;
  padding: 20px;
  margin-top: 20px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.streak-history h4 {
  margin: 0 0 16px 0;
  font-size: 18px;
  font-weight: 600;
  color: #1f2937;
}

.history-item {
  display: flex;
  justify-content: space-between;
  padding: 12px 0;
  border-bottom: 1px solid #e5e7eb;
}

.history-item:last-child {
  border-bottom: none;
}

.history-label {
  color: #6b7280;
  font-size: 14px;
}

.history-value {
  color: #1f2937;
  font-weight: 600;
  font-size: 14px;
}

.btn-toggle-history {
  width: 100%;
  padding: 12px;
  margin-top: 16px;
  background: #f3f4f6;
  border: none;
  border-radius: 8px;
  color: #4b5563;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-toggle-history:hover {
  background: #e5e7eb;
}
</style>
