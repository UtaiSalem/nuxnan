<script setup lang="ts">
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
import type { PollOption as PollOptionType } from '~/composables/usePolls'
import PollProgressBar from './PollProgressBar.vue'

interface Props {
  option: PollOptionType
  isVotingMode: boolean
  isSelected?: boolean
  isMultiple: boolean
  maxPercentage: number
  index: number
}

const props = withDefaults(defineProps<Props>(), {
  isSelected: false,
  isMultiple: false
})

const emit = defineEmits<{
  click: []
}>()

const optionLabel = computed(() => {
  return `${props.index + 1}.`
})

const rankBadge = computed(() => {
  if (props.option.percentage === 100) return '🏆'
  if (props.option.percentage >= 50) return '🥈'
  if (props.option.percentage >= 33) return '🥉'
  return null
})

const isLeading = computed(() => {
  return props.option.percentage > 0 && props.option.percentage === props.maxPercentage
})

const handleClick = () => {
  if (props.isVotingMode) {
    emit('click')
  }
}
</script>

<template>
  <!-- Voting Mode -->
  <button
    v-if="isVotingMode"
    @click="handleClick"
    class="poll-option-voting"
    :class="{ selected: isSelected }"
    role="radio"
    :aria-checked="isSelected"
    :aria-label="`Vote for option ${index + 1}: ${option.text}`"
  >
    <!-- Checkbox/Radio -->
    <div class="poll-option-checkbox">
      <Icon 
        v-if="isSelected" 
        icon="fluent:checkmark-circle-24-filled" 
        class="w-6 h-6 text-vikinger-cyan"
      />
      <Icon 
        v-else 
        icon="fluent:checkbox-circle-24-regular" 
        class="w-6 h-6 text-gray-400"
      />
    </div>
    
    <!-- Option Text -->
    <span class="poll-option-text">{{ option.text }}</span>
  </button>
  
  <!-- Results Mode -->
  <PollProgressBar
    v-else
    :percentage="option.percentage"
    :is-voted="option.is_user_vote"
    :is-leading="isLeading"
    :color="option.is_user_vote ? 'cyan' : 'purple'"
    @click="emit('click')"
  >
    <div class="poll-option-results-content">
      <!-- Left Side: Text and Rank -->
      <div class="flex-1">
        <div class="flex items-center gap-2">
          <!-- Rank Badge -->
          <span v-if="rankBadge" class="poll-rank-badge">
            {{ rankBadge }}
          </span>
          
          <!-- Option Text -->
          <span 
            class="poll-option-result-text"
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
        <div class="poll-option-votes">
          <Icon icon="fluent:thumb-like-24-regular" class="w-3.5 h-3.5" />
          <span>{{ option.votes }} คน</span>
        </div>
      </div>
      
      <!-- Right Side: Percentage -->
      <div class="poll-option-percentage">
        {{ option.percentage }}%
      </div>
    </div>
  </PollProgressBar>
</template>

<style scoped>
/* Voting Mode Styles */
.poll-option-voting {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  border-radius: 12px;
  border: 2px solid #e5e7eb;
  background: white;
  cursor: pointer;
  transition: all 0.2s ease;
  width: 100%;
  text-align: left;
}

.dark .poll-option-voting {
  border-color: rgba(255, 255, 255, 0.1);
  background: #282f3f;
}

.poll-option-voting:hover {
  border-color: #23d2e2;
  background: rgba(35, 210, 226, 0.05);
  transform: scale(1.01);
}

.dark .poll-option-voting:hover {
  background: rgba(35, 210, 226, 0.1);
}

.poll-option-voting.selected {
  border-color: #23d2e2;
  background: rgba(35, 210, 226, 0.1);
}

.dark .poll-option-voting.selected {
  background: rgba(35, 210, 226, 0.15);
}

.poll-option-checkbox {
  flex-shrink: 0;
}

.poll-option-text {
  font-size: 15px;
  font-weight: 500;
  color: #1f2937;
  flex: 1;
}

.dark .poll-option-text {
  color: #f9fafb;
}

/* Results Mode Styles */
.poll-option-results-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  gap: 16px;
}

.poll-rank-badge {
  font-size: 18px;
  line-height: 1;
}

.poll-option-result-text {
  font-size: 15px;
  font-weight: 500;
  color: #1f2937;
}

.dark .poll-option-result-text {
  color: #f9fafb;
}

.poll-option-result-text.voted {
  color: #23d2e2;
}

.dark .poll-option-result-text.voted {
  color: #23d2e2;
}

.poll-option-votes {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 13px;
  color: #6b7280;
  margin-top: 4px;
}

.dark .poll-option-votes {
  color: #9ca3af;
}

.poll-option-percentage {
  font-size: 24px;
  font-weight: 700;
  background: linear-gradient(90deg, #615dfa 0%, #23d2e2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  flex-shrink: 0;
  min-width: 60px;
  text-align: right;
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .poll-option-voting:hover {
    transform: none;
  }
}
</style>
