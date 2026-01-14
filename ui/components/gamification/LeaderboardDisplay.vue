<template>
  <div class="leaderboard-display">
    <!-- Tabs -->
    <div class="tabs">
      <button 
        v-for="tab in tabs" 
        :key="tab.id"
        class="tab-btn" 
        :class="{ active: activeTab === tab.id }"
        @click="activeTab = tab.id"
      >
        <i :class="tab.icon"></i>
        {{ tab.label }}
      </button>
    </div>
    
    <!-- Leaderboard Content -->
    <div class="leaderboard-content" v-if="!isLoading">
      <!-- Points Leaderboard -->
      <div v-if="activeTab === 'points'" class="leaderboard-list">
        <div class="leaderboard-header">
          <span class="header-rank">อันดับ</span>
          <span class="header-user">ผู้ใช้</span>
          <span class="header-points">แต้ม</span>
          <span class="header-level">เลเวล</span>
        </div>
        
        <div 
          v-for="(item, index) in leaderboardData.leaderboard" 
          :key="item.user_id"
          class="leaderboard-item"
          :class="{ 'top-3': index < 3, 'current-user': item.user_id === currentUserId }"
        >
          <div class="item-rank">
            <span v-if="index === 0" class="rank-badge gold">🥇</span>
            <span v-else-if="index === 1" class="rank-badge silver">🥈</span>
            <span v-else-if="index === 2" class="rank-badge bronze">🥉</span>
            <span v-else class="rank-number">#{{ item.rank }}</span>
          </div>
          
          <div class="item-user">
            <img 
              :src="item.profile_photo_path || '/images/default-avatar.png'" 
              :alt="item.user_name"
              class="user-avatar"
            >
            <span class="user-name">{{ item.user_name }}</span>
          </div>
          
          <div class="item-points">{{ formatNumber(item.total_points) }} แต้ม</div>
          
          <div class="item-level">Lv. {{ item.level }}</div>
        </div>
        
        <!-- Empty State -->
        <div v-if="leaderboardData.leaderboard.length === 0" class="empty-state">
          <i class="lni lni-empty-file"></i>
          <p>ไม่มีข้อมูล</p>
        </div>
      </div>
      
      <!-- Streak Leaderboard -->
      <div v-if="activeTab === 'streak'" class="leaderboard-list">
        <div class="leaderboard-header">
          <span class="header-rank">อันดับ</span>
          <span class="header-user">ผู้ใช้</span>
          <span class="header-streak">Streak</span>
          <span class="header-level">ระดับ</span>
        </div>
        
        <div 
          v-for="(item, index) in leaderboardData.leaderboard" 
          :key="item.user_id"
          class="leaderboard-item"
          :class="{ 'top-3': index < 3, 'current-user': item.user_id === currentUserId }"
        >
          <div class="item-rank">
            <span v-if="index === 0" class="rank-badge gold">🥇</span>
            <span v-else-if="index === 1" class="rank-badge silver">🥈</span>
            <span v-else-if="index === 2" class="rank-badge bronze">🥉</span>
            <span v-else class="rank-number">#{{ item.rank }}</span>
          </div>
          
          <div class="item-user">
            <img 
              :src="item.profile_photo_path || '/images/default-avatar.png'" 
              :alt="item.user_name"
              class="user-avatar"
            >
            <span class="user-name">{{ item.user_name }}</span>
          </div>
          
          <div class="item-streak">
            <span class="streak-icon">{{ item.streak_icon }}</span>
            <span class="streak-days">{{ item.current_streak }} วัน</span>
          </div>
          
          <div class="item-level" :style="{ color: item.streak_level_color }">
            {{ item.streak_level }}
          </div>
        </div>
        
        <!-- Empty State -->
        <div v-if="leaderboardData.leaderboard.length === 0" class="empty-state">
          <i class="lni lni-empty-file"></i>
          <p>ไม่มีข้อมูล</p>
        </div>
      </div>
      
      <!-- Achievement Leaderboard -->
      <div v-if="activeTab === 'achievements'" class="leaderboard-list">
        <div class="leaderboard-header">
          <span class="header-rank">อันดับ</span>
          <span class="header-user">ผู้ใช้</span>
          <span class="header-achievements">ความ</span>
          <span class="header-level">เลเวล</span>
        </div>
        
        <div 
          v-for="(item, index) in leaderboardData.leaderboard" 
          :key="item.user_id"
          class="leaderboard-item"
          :class="{ 'top-3': index < 3, 'current-user': item.user_id === currentUserId }"
        >
          <div class="item-rank">
            <span v-if="index === 0" class="rank-badge gold">🥇</span>
            <span v-else-if="index === 1" class="rank-badge silver">🥈</span>
            <span v-else-if="index === 2" class="rank-badge bronze">🥉</span>
            <span v-else class="rank-number">#{{ item.rank }}</span>
          </div>
          
          <div class="item-user">
            <img 
              :src="item.profile_photo_path || '/images/default-avatar.png'" 
              :alt="item.user_name"
              class="user-avatar"
            >
            <span class="user-name">{{ item.user_name }}</span>
          </div>
          
          <div class="item-achievements">{{ formatNumber(item.achievement_count) }} ความ</div>
          
          <div class="item-level">Lv. {{ item.level }}</div>
        </div>
        
        <!-- Empty State -->
        <div v-if="leaderboardData.leaderboard.length === 0" class="empty-state">
          <i class="lni lni-empty-file"></i>
          <p>ไม่มีข้อมูล</p>
        </div>
      </div>
      
      <!-- Level Leaderboard -->
      <div v-if="activeTab === 'level'" class="leaderboard-list">
        <div class="leaderboard-header">
          <span class="header-rank">อันดับ</span>
          <span class="header-user">ผู้ใช้</span>
          <span class="header-level">เลเวล</span>
          <span class="header-points">แต้ม</span>
        </div>
        
        <div 
          v-for="(item, index) in leaderboardData.leaderboard" 
          :key="item.user_id"
          class="leaderboard-item"
          :class="{ 'top-3': index < 3, 'current-user': item.user_id === currentUserId }"
        >
          <div class="item-rank">
            <span v-if="index === 0" class="rank-badge gold">🥇</span>
            <span v-else-if="index === 1" class="rank-badge silver">🥈</span>
            <span v-else-if="index === 2" class="rank-badge bronze">🥉</span>
            <span v-else class="rank-number">#{{ item.rank }}</span>
          </div>
          
          <div class="item-user">
            <img 
              :src="item.profile_photo_path || '/images/default-avatar.png'" 
              :alt="item.user_name"
              class="user-avatar"
            >
            <span class="user-name">{{ item.user_name }}</span>
          </div>
          
          <div class="item-level">Lv. {{ item.level }}</div>
          
          <div class="item-points">{{ formatNumber(item.total_points) }} แต้ม</div>
        </div>
        
        <!-- Empty State -->
        <div v-if="leaderboardData.leaderboard.length === 0" class="empty-state">
          <i class="lni lni-empty-file"></i>
          <p>ไม่มีข้อมูล</p>
        </div>
      </div>
    </div>
    
    <!-- Pagination -->
    <div class="pagination" v-if="leaderboardData.total_pages > 1">
      <button 
        class="btn-pagination"
        @click="loadPage(leaderboardData.current_page - 1)"
        :disabled="leaderboardData.current_page <= 1"
      >
        <i class="lni lni-chevron-left"></i>
      </button>
      
      <span class="page-info">
        หน้า {{ leaderboardData.current_page }} / {{ leaderboardData.total_pages }}
      </span>
      
      <button 
        class="btn-pagination"
        @click="loadPage(leaderboardData.current_page + 1)"
        :disabled="leaderboardData.current_page >= leaderboardData.total_pages"
      >
        <i class="lni lni-chevron-right"></i>
      </button>
    </div>
    
    <!-- Loading State -->
    <div v-if="isLoading" class="loading-state">
      <i class="lni lni-spinner lni-spin"></i>
      <p>กำลังโหลด...</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useGamification } from '~/composables/useGamification'
import { useAuthStore } from '~/stores/auth'

const { 
  getPointsLeaderboard,
  getStreakLeaderboard,
  getAchievementLeaderboard,
  getLevelLeaderboard,
  getLeaderboardSummary
} = useGamification()

const authStore = useAuthStore()

// Reactive state
const activeTab = ref<'points' | 'streak' | 'achievements' | 'level'>('points')
const isLoading = ref(false)
const leaderboardData = ref({
  leaderboard: [],
  current_page: 1,
  total_pages: 1,
  total_users: 0,
  per_page: 10,
})

const currentUserId = computed(() => authStore.user?.id || 0)

// Tabs configuration
const tabs = [
  { id: 'points', label: 'แต้ม', icon: 'lni-star' },
  { id: 'streak', label: 'Streak', icon: 'lni-fire' },
  { id: 'achievements', label: 'ความ', icon: 'lni-trophy' },
  { id: 'level', label: 'เลเวล', icon: 'lni-medal' },
]

// Load leaderboard when tab changes
const loadLeaderboard = async () => {
  try {
    isLoading.value = true
    
    switch (activeTab.value) {
      case 'points':
        leaderboardData.value = await getPointsLeaderboard({ limit: 10, page: 1 })
        break
      case 'streak':
        leaderboardData.value = await getStreakLeaderboard({ limit: 10, page: 1 })
        break
      case 'achievements':
        leaderboardData.value = await getAchievementLeaderboard({ limit: 10, page: 1 })
        break
      case 'level':
        leaderboardData.value = await getLevelLeaderboard({ limit: 10, page: 1 })
        break
    }
  } catch (error) {
    console.error('Failed to load leaderboard:', error)
  } finally {
    isLoading.value = false
  }
}

// Load specific page
const loadPage = async (page: number) => {
  if (page < 1 || page > leaderboardData.value.total_pages) {
    return
  }

  try {
    isLoading.value = true
    
    switch (activeTab.value) {
      case 'points':
        leaderboardData.value = await getPointsLeaderboard({ limit: 10, page })
        break
      case 'streak':
        leaderboardData.value = await getStreakLeaderboard({ limit: 10, page })
        break
      case 'achievements':
        leaderboardData.value = await getAchievementLeaderboard({ limit: 10, page })
        break
      case 'level':
        leaderboardData.value = await getLevelLeaderboard({ limit: 10, page })
        break
    }
  } catch (error) {
    console.error('Failed to load page:', error)
  } finally {
    isLoading.value = false
  }
}

// Watch for tab changes
const { stop: stopWatchingTab } = watch(activeTab, () => {
  loadLeaderboard()
})

// Load leaderboard on mount
onMounted(() => {
  loadLeaderboard()
})
</script>

<style scoped>
.leaderboard-display {
  width: 100%;
}

.tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 24px;
  border-bottom: 2px solid #e5e7eb;
  padding-bottom: 2px;
}

.tab-btn {
  padding: 12px 20px;
  background: none;
  border: none;
  font-size: 14px;
  font-weight: 500;
  color: #6b7280;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
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

.leaderboard-content {
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.leaderboard-list {
  min-height: 400px;
}

.leaderboard-header {
  display: grid;
  grid-template-columns: 60px 1fr 120px 80px;
  gap: 16px;
  padding: 16px;
  background: #f9fafb;
  border-radius: 8px;
  margin-bottom: 16px;
  font-weight: 600;
  color: #4b5563;
}

.header-rank {
  text-align: center;
}

.header-user {
  text-align: left;
}

.header-points,
.header-streak,
.header-achievements,
.header-level {
  text-align: right;
}

.leaderboard-item {
  display: grid;
  grid-template-columns: 60px 1fr 120px 80px;
  gap: 16px;
  padding: 16px;
  border-bottom: 1px solid #e5e7eb;
  align-items: center;
  transition: background-color 0.2s;
}

.leaderboard-item:hover {
  background: #f3f4f6;
}

.leaderboard-item.top-3 {
  background: linear-gradient(135deg, #fef3c7 0%, #fbbf24 100%);
}

.leaderboard-item.current-user {
  background: #eff6ff;
  font-weight: 600;
}

.item-rank {
  text-align: center;
  display: flex;
  align-items: center;
  justify-content: center;
}

.rank-badge {
  font-size: 24px;
  display: block;
}

.rank-number {
  font-size: 18px;
  font-weight: 700;
  color: #1f2937;
}

.item-user {
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #e5e7eb;
}

.user-name {
  font-size: 14px;
  font-weight: 500;
  color: #1f2937;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.item-points,
.item-streak,
.item-achievements,
.item-level {
  text-align: right;
  font-size: 14px;
  font-weight: 600;
  color: #4b5563;
}

.streak-icon {
  font-size: 20px;
  margin-right: 4px;
}

.streak-days {
  font-size: 14px;
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

.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e5e7eb;
}

.btn-pagination {
  padding: 8px 12px;
  background: white;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-pagination:hover:not(:disabled) {
  background: #f3f4f6;
}

.btn-pagination:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-info {
  font-size: 14px;
  color: #6b7280;
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
