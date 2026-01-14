<template>
  <div class="achievements-display">
    <!-- Stats Overview -->
    <div class="stats-overview">
      <div class="stat-card">
        <div class="stat-icon">🏆</div>
        <div class="stat-info">
          <p class="stat-value">{{ stats.unlocked_achievements }}/{{ stats.total_achievements }}</p>
          <p class="stat-label">บรรลุความ</p>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">🔒</div>
        <div class="stat-info">
          <p class="stat-value">{{ stats.locked_achievements }}</p>
          <p class="stat-label">ค้างแลก</p>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">📊</div>
        <div class="stat-info">
          <p class="stat-value">{{ stats.completion_percentage }}%</p>
          <p class="stat-label">ความสำเร็จ</p>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">⭐</div>
        <div class="stat-info">
          <p class="stat-value">{{ stats.total_points_from_achievements }}</p>
          <p class="stat-label">แต้มจากความสำเร็จ</p>
        </div>
      </div>
    </div>
    
    <!-- Tabs -->
    <div class="tabs">
      <button 
        class="tab-btn" 
        :class="{ active: activeTab === 'unlocked' }"
        @click="activeTab = 'unlocked'"
      >
        บรรลุความ
      </button>
      <button 
        class="tab-btn" 
        :class="{ active: activeTab === 'available' }"
        @click="activeTab = 'available'"
      >
        ค้างแลก
      </button>
    </div>
    
    <!-- Achievements List -->
    <div class="achievements-list" v-if="!isLoading">
      <!-- Unlocked Achievements -->
      <div v-if="activeTab === 'unlocked'" class="achievements-grid">
        <div 
          v-for="achievement in unlockedAchievements" 
          :key="achievement.id"
          class="achievement-card unlocked"
        >
          <div class="achievement-badge">
            <img :src="achievement.icon || '/icons/achievement.png'" :alt="achievement.name">
          </div>
          <div class="achievement-details">
            <h4 class="achievement-name">{{ achievement.name }}</h4>
            <p class="achievement-description">{{ achievement.description }}</p>
            <div class="achievement-meta">
              <span class="achievement-type">{{ achievement.type_label }}</span>
              <span class="achievement-points" v-if="achievement.points_reward">+{{ achievement.points_reward }} แต้ม</span>
            </div>
            <p class="achievement-date" v-if="achievement.completed_at">
              บรรลุความเมื่อ {{ formatDate(achievement.completed_at) }}
            </p>
          </div>
        </div>
        
        <!-- Empty State -->
        <div v-if="unlockedAchievements.length === 0" class="empty-state">
          <i class="lni lni-empty-file"></i>
          <p>ยังไม่มีความสำเร็จ</p>
        </div>
      </div>
      
      <!-- Available Achievements -->
      <div v-if="activeTab === 'available'" class="achievements-grid">
        <div 
          v-for="achievement in availableAchievements" 
          :key="achievement.id"
          class="achievement-card locked"
        >
          <div class="achievement-badge">
            <img :src="achievement.icon || '/icons/achievement-locked.png'" :alt="achievement.name">
          </div>
          <div class="achievement-details">
            <h4 class="achievement-name">{{ achievement.name }}</h4>
            <p class="achievement-description">{{ achievement.description }}</p>
            <div class="achievement-meta">
              <span class="achievement-type">{{ achievement.type_label }}</span>
              <span class="achievement-points" v-if="achievement.points_reward">+{{ achievement.points_reward }} แต้ม</span>
            </div>
            <div class="achievement-criteria">
              <p class="criteria-label">เงื่อนไบรับ:</p>
              <ul class="criteria-list">
                <li v-for="(value, key) in achievement.criteria" :key="key">
                  {{ formatCriteria(key, value) }}
                </li>
              </ul>
            </div>
          </div>
        </div>
        
        <!-- Empty State -->
        <div v-if="availableAchievements.length === 0" class="empty-state">
          <i class="lni lni-checkmark-circle"></i>
          <p>บรรลุความทั้งหมด!</p>
        </div>
      </div>
    </div>
    
    <!-- Loading State -->
    <div v-if="isLoading" class="loading-state">
      <i class="lni lni-spinner lni-spin"></i>
      <p>กำลังโหลด...</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useGamification } from '~/composables/useGamification'

const { 
  getAchievements, 
  getAvailableAchievements, 
  getAchievementStats,
  showAchievementNotification 
} = useGamification()

// Reactive state
const activeTab = ref<'unlocked' | 'available'>('unlocked')
const isLoading = ref(false)
const unlockedAchievements = ref<any[]>([])
const availableAchievements = ref<any[]>([])
const stats = ref({
  total_achievements: 0,
  unlocked_achievements: 0,
  locked_achievements: 0,
  completion_percentage: 0,
  total_points_from_achievements: 0,
})

// Load achievements on mount
onMounted(async () => {
  await loadAchievements()
  await loadStats()
})

// Load achievements based on active tab
const loadAchievements = async () => {
  if (activeTab.value === 'unlocked') {
    await loadUnlockedAchievements()
  } else {
    await loadAvailableAchievements()
  }
}

// Load unlocked achievements
const loadUnlockedAchievements = async () => {
  try {
    isLoading.value = true
    unlockedAchievements.value = await getAchievements()
  } catch (error) {
    console.error('Failed to load achievements:', error)
  } finally {
    isLoading.value = false
  }
}

// Load available achievements
const loadAvailableAchievements = async () => {
  try {
    isLoading.value = true
    availableAchievements.value = await getAvailableAchievements()
  } catch (error) {
    console.error('Failed to load available achievements:', error)
  } finally {
    isLoading.value = false
  }
}

// Load stats
const loadStats = async () => {
  try {
    stats.value = await getAchievementStats()
  } catch (error) {
    console.error('Failed to load stats:', error)
  }
}

// Format date
const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

// Format criteria
const formatCriteria = (key: string, value: any): string => {
  const labels: Record<string, string> = {
    count: 'ทำสำเร็จ',
    points: 'สะสมแต้ม',
    streak: 'Streak',
    social_type: 'สังคม',
    learning_type: 'การเรียนรู้',
    action_type: 'การกระทำ',
  }
  
  if (key === 'count') {
    return `${labels[key]} ${value} ครั้ง`
  }
  
  return `${labels[key]}: ${value}`
}
</script>

<style scoped>
.achievements-display {
  width: 100%;
}

.stats-overview {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.stat-icon {
  font-size: 32px;
  width: 50px;
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f3f4f6;
  border-radius: 50%;
  flex-shrink: 0;
}

.stat-info {
  flex: 1;
}

.stat-value {
  font-size: 24px;
  font-weight: 700;
  margin: 0 0 4px 0;
  color: #1f2937;
}

.stat-label {
  font-size: 14px;
  margin: 0;
  color: #6b7280;
}

.tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 20px;
  border-bottom: 2px solid #e5e7eb;
}

.tab-btn {
  padding: 12px 24px;
  background: none;
  border: none;
  font-size: 16px;
  font-weight: 600;
  color: #6b7280;
  cursor: pointer;
  position: relative;
  transition: color 0.2s;
}

.tab-btn:hover {
  color: #4b5563;
}

.tab-btn.active {
  color: #4f46e5;
}

.tab-btn.active::after {
  content: '';
  position: absolute;
  bottom: -2px;
  left: 0;
  right: 0;
  height: 2px;
  background: #4f46e5;
}

.achievements-list {
  min-height: 400px;
}

.achievements-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 16px;
}

.achievement-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s, box-shadow 0.2s;
}

.achievement-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.achievement-card.unlocked {
  border-left: 4px solid #10b981;
}

.achievement-card.locked {
  border-left: 4px solid #9ca3af;
  opacity: 0.8;
}

.achievement-badge {
  width: 80px;
  height: 80px;
  margin: 0 auto 16px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.achievement-badge img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.achievement-details {
  text-align: center;
}

.achievement-name {
  font-size: 18px;
  font-weight: 600;
  margin: 0 0 8px 0;
  color: #1f2937;
}

.achievement-description {
  font-size: 14px;
  color: #6b7280;
  margin: 0 0 12px 0;
  line-height: 1.5;
}

.achievement-meta {
  display: flex;
  justify-content: center;
  gap: 12px;
  margin-bottom: 12px;
}

.achievement-type {
  background: #f3f4f6;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
  color: #4b5563;
}

.achievement-points {
  background: #ecfdf5;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  color: #10b981;
}

.achievement-date {
  font-size: 12px;
  color: #9ca3af;
  margin: 0;
}

.achievement-criteria {
  background: #f9fafb;
  border-radius: 8px;
  padding: 12px;
  margin-top: 12px;
  text-align: left;
}

.criteria-label {
  font-size: 12px;
  font-weight: 600;
  color: #4b5563;
  margin: 0 0 8px 0;
}

.criteria-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.criteria-list li {
  font-size: 13px;
  color: #6b7280;
  padding: 4px 0;
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #9ca3af;
}

.empty-state i {
  font-size: 48px;
  margin-bottom: 12px;
  display: block;
}

.empty-state p {
  margin: 0;
  font-size: 16px;
}

.loading-state {
  text-align: center;
  padding: 60px 20px;
  color: #6b7280;
}

.loading-state i {
  font-size: 32px;
  margin-bottom: 12px;
  display: block;
}

.loading-state p {
  margin: 0;
  font-size: 16px;
}

.lni-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
