<script setup lang="ts">
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
import type { Poll, PollOption as PollOptionType } from '~/composables/usePolls'

interface Props {
  poll: Poll
  showChangeVote?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showChangeVote: false
})

const emit = defineEmits<{
  changeVote: []
}>()

// Computed
const sortedOptions = computed(() => {
  return [...props.poll.options].sort((a, b) => b.votes - a.votes)
})

const totalVotes = computed(() => props.poll.total_votes)

const participationRate = computed(() => {
  // Calculate based on some metric (e.g., followers, views)
  // For now, assume 100% for demonstration
  return 85
})

const getRankBadge = (percentage: number, index: number) => {
  if (percentage === 100) return '🏆'
  if (percentage >= 50 && index === 0) return '🥈'
  if (percentage >= 33 && index <= 1) return '🥉'
  return null
}

const getRankColor = (percentage: number) => {
  if (percentage >= 50) return 'text-vikinger-yellow'
  if (percentage >= 33) return 'text-vikinger-cyan'
  return 'text-vikinger-purple'
}
</script>

<template>
  <div class="poll-results">
    <!-- Results Header -->
    <div class="poll-results-header">
      <Icon icon="fluent:chart-multiple-24-regular" class="w-5 h-5 text-vikinger-cyan" />
      <h3 class="poll-results-title">ผลการโหวต</h3>
    </div>
    
    <!-- Options with Progress Bars -->
    <div class="poll-results-options">
      <div 
        v-for="(option, index) in sortedOptions" 
        :key="option.id"
        class="poll-result-item"
        :class="{ voted: option.is_user_vote }"
      >
        <!-- Progress Bar -->
        <div class="poll-result-progress">
          <!-- Background -->
          <div 
            class="poll-progress-background"
            :class="{ 'voted': option.is_user_vote }"
          ></div>
          
          <!-- Fill -->
          <div 
            class="poll-progress-fill"
            :class="{ 
              'voted': option.is_user_vote,
              'leading': option.percentage === Math.max(...sortedOptions.map(o => o.percentage))
            }"
            :style="{ width: `${option.percentage}%` }"
            role="progressbar"
            :aria-valuenow="option.percentage"
            :aria-valuemin="0"
            :aria-valuemax="100"
            :aria-label="`Option ${index + 1}: ${option.percentage}% of votes`"
          ></div>
          
          <!-- Content -->
          <div class="poll-result-content">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-1">
                <!-- Rank Badge -->
                <span 
                  v-if="getRankBadge(option.percentage, index)" 
                  class="poll-rank-badge"
                >
                  {{ getRankBadge(option.percentage, index) }}
                </span>
                
                <!-- Option Text -->
                <span 
                  class="poll-result-text"
                  :class="{ voted: option.is_user_vote }"
                >
                  {{ option.text }}
                  <Icon 
                    v-if="option.is_user_vote" 
                    icon="fluent:checkmark-24-filled" 
                    class="w-4 h-4 ml-1"
                  />
                </span>
              </div>
              
              <!-- Vote Count -->
              <div class="poll-result-votes">
                <Icon icon="fluent:thumb-like-24-regular" class="w-3.5 h-3.5" />
                <span>{{ option.votes }} คน</span>
              </div>
            </div>
            
            <!-- Percentage -->
            <div class="poll-result-percentage">
              {{ option.percentage }}%
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Statistics Card -->
    <div class="poll-stats-card">
      <div class="poll-stats-header">
        <Icon icon="fluent:chart-pie-24-regular" class="w-5 h-5 text-vikinger-purple" />
        <h4 class="poll-stats-title">สถิติโพล</h4>
      </div>
      
      <div class="poll-stats-content">
        <div class="poll-stat-item">
          <Icon icon="fluent:thumb-like-24-regular" class="poll-stat-icon" />
          <div class="poll-stat-info">
            <span class="poll-stat-label">โหวตทั้งหมด</span>
            <span class="poll-stat-value">{{ totalVotes }} คน</span>
          </div>
        </div>
        
        <div class="poll-stat-item">
          <Icon icon="fluent:people-24-regular" class="poll-stat-icon" />
          <div class="poll-stat-info">
            <span class="poll-stat-label">อัตราการมีส่วนร่วม</span>
            <span class="poll-stat-value">{{ participationRate }}%</span>
          </div>
        </div>
        
        <div v-if="!poll.is_ended" class="poll-stat-item">
          <Icon icon="fluent:clock-24-regular" class="poll-stat-icon" />
          <div class="poll-stat-info">
            <span class="poll-stat-label">เวลาที่เหลือ</span>
            <span class="poll-stat-value">{{ poll.time_remaining || 'คำนวณ' }}</span>
          </div>
        </div>
        
        <div v-else class="poll-stat-item">
          <Icon icon="fluent:checkmark-circle-24-filled" class="poll-stat-icon text-vikinger-green" />
          <div class="poll-stat-info">
            <span class="poll-stat-label">สถานะ</span>
            <span class="poll-stat-value text-vikinger-green">สิ้นสุดแล้ว</span>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Change Vote Button -->
    <button
      v-if="showChangeVote && !poll.is_ended"
      @click="emit('changeVote')"
      class="poll-change-vote-btn"
    >
      <Icon icon="fluent:arrow-clockwise-24-regular" class="w-5 h-5" />
      <span>เปลี่ยนโหวต</span>
    </button>
  </div>
</template>

<style scoped>
.poll-results {
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.dark .poll-results {
  background: #2f3749;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
}

/* Header */
.poll-results-header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 16px 20px 16px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.dark .poll-results-header {
  border-bottom-color: rgba(255, 255, 255, 0.05);
}

.poll-results-title {
  font-size: 18px;
  font-weight: 600;
  color: #1f2937;
}

.dark .poll-results-title {
  color: #f9fafb;
}

/* Options Container */
.poll-results-options {
  padding: 0 20px 20px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* Result Item */
.poll-result-item {
  position: relative;
  border-radius: 12px;
  overflow: hidden;
  transition: all 0.3s ease;
}

.poll-result-item.voted {
  border: 2px solid #23d2e2;
}

/* Progress Container */
.poll-result-progress {
  position: relative;
  height: 56px;
  border-radius: 12px;
  overflow: hidden;
}

.poll-progress-background {
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, 
    rgba(97, 93, 250, 0.1) 0%, 
    rgba(35, 210, 226, 0.1) 100%);
  border-radius: 12px;
  transition: opacity 0.3s ease;
}

.poll-progress-background.voted {
  background: linear-gradient(90deg, 
    rgba(97, 93, 250, 0.2) 0%, 
    rgba(35, 210, 226, 0.2) 100%);
}

.poll-progress-fill {
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, #615dfa 0%, #23d2e2 100%);
  border-radius: 12px;
  transition: width 0.5s ease-out;
  animation: progress-fill 0.5s ease-out;
}

.poll-progress-fill.voted {
  background: linear-gradient(90deg, 
    rgba(97, 93, 250, 0.3) 0%, 
    rgba(35, 210, 226, 0.3) 100%);
}

.poll-progress-fill.leading {
  box-shadow: 0 0 20px rgba(35, 210, 226, 0.4);
}

/* Content */
.poll-result-content {
  position: relative;
  z-index: 10;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 16px;
  gap: 16px;
}

.poll-rank-badge {
  font-size: 20px;
  line-height: 1;
}

.poll-result-text {
  font-size: 15px;
  font-weight: 500;
  color: #1f2937;
}

.dark .poll-result-text {
  color: #f9fafb;
}

.poll-result-text.voted {
  color: #23d2e2;
}

.dark .poll-result-text.voted {
  color: #23d2e2;
}

.poll-result-votes {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 13px;
  color: #6b7280;
}

.dark .poll-result-votes {
  color: #9ca3af;
}

.poll-result-percentage {
  font-size: 28px;
  font-weight: 700;
  background: linear-gradient(90deg, #615dfa 0%, #23d2e2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  flex-shrink: 0;
  min-width: 70px;
  text-align: right;
}

/* Stats Card */
.poll-stats-card {
  padding: 16px 20px;
  background: rgba(0, 0, 0, 0.03);
  border-top: 1px solid rgba(0, 0, 0, 0.1);
}

.dark .poll-stats-card {
  background: rgba(255, 255, 255, 0.03);
  border-top-color: rgba(255, 255, 255, 0.05);
}

.poll-stats-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 16px;
}

.poll-stats-title {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
}

.dark .poll-stats-title {
  color: #f9fafb;
}

.poll-stats-content {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.poll-stat-item {
  display: flex;
  align-items: center;
  gap: 12px;
}

.poll-stat-icon {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(97, 93, 250, 0.1);
  border-radius: 50%;
  color: #615dfa;
  flex-shrink: 0;
}

.dark .poll-stat-icon {
  background: rgba(97, 93, 250, 0.15);
}

.poll-stat-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.poll-stat-label {
  font-size: 13px;
  color: #6b7280;
}

.dark .poll-stat-label {
  color: #9ca3af;
}

.poll-stat-value {
  font-size: 15px;
  font-weight: 600;
  color: #1f2937;
}

.dark .poll-stat-value {
  color: #f9fafb;
}

/* Change Vote Button */
.poll-change-vote-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  padding: 14px 20px;
  background: rgba(97, 93, 250, 0.1);
  color: #615dfa;
  font-size: 15px;
  font-weight: 500;
  border: none;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
  margin-top: 16px;
}

.poll-change-vote-btn:hover {
  background: rgba(97, 93, 250, 0.15);
  transform: translateY(-2px);
}

/* Animations */
@keyframes progress-fill {
  0% {
    width: 0%;
  }
  100% {
    width: var(--progress-width);
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .poll-progress-fill {
    transition: none;
    animation: none;
  }
  
  .poll-change-vote-btn:hover {
    transform: none;
  }
}
</style>
