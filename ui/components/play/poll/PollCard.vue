<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'
import { usePollStore } from '~/stores/poll'
import { usePolls } from '~/composables/usePolls'
import { useSweetAlert } from '~/composables/useSweetAlert'
import { useAvatar } from '~/composables/useAvatar'
import { useToast } from '~/composables/useToast'
import type { Poll } from '~/composables/usePolls'
import PollOption from './PollOption.vue'
import PollMenu from './PollMenu.vue'

interface Props {
  poll: Poll
  showActions?: boolean
  isNested?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showActions: true,
  isNested: false
})

const emit = defineEmits<{
  delete: []
  update: []
}>()

const authStore = useAuthStore()
const pollStore = usePollStore()
const { votePoll, calculateTimeRemaining } = usePolls()
const { getAvatarUrl } = useAvatar()
const swal = useSweetAlert()
const toast = useToast()

// Local state
const isVoting = ref(false)
const selectedOptions = ref<number[]>([])
const showResults = ref(false)
const showMenu = ref(false)
const localPoll = ref<Poll>({ ...props.poll })

// Computed
const isOwner = computed(() => {
  return authStore.user?.id === localPoll.value.author.id
})

const isEnded = computed(() => {
  return localPoll.value.is_ended
})

const hasVoted = computed(() => {
  return localPoll.value.user_voted
})

const canVote = computed(() => {
  return !isEnded.value && !hasVoted.value
})

const timeRemaining = computed(() => {
  if (isEnded.value) {
    return 'สิ้นสุดแล้ว'
  }
  return localPoll.value.time_remaining || calculateTimeRemaining(localPoll.value.ends_at)
})

const showVotingMode = computed(() => {
  return canVote.value && !showResults.value
})

const showResultsMode = computed(() => {
  return !canVote.value || showResults.value || hasVoted.value
})

const sortedOptions = computed(() => {
  return [...localPoll.value.options].sort((a, b) => b.votes - a.votes)
})

// Watch for poll updates
watch(() => props.poll, (newPoll) => {
  localPoll.value = { ...newPoll }
  
  // Reset local state if poll ended
  if (newPoll.is_ended) {
    showResults.value = true
  }
}, { deep: true })

// Update time remaining
onMounted(() => {
  if (!localPoll.value.time_remaining) {
    localPoll.value.time_remaining = calculateTimeRemaining(localPoll.value.ends_at)
  }
})

// Select option
const selectOption = (optionId: number) => {
  if (!canVote.value) return
  
  if (localPoll.value.is_multiple) {
    // Multiple choice: toggle selection
    const index = selectedOptions.value.indexOf(optionId)
    if (index === -1) {
      selectedOptions.value.push(optionId)
    } else {
      selectedOptions.value.splice(index, 1)
    }
  } else {
    // Single choice: replace selection
    selectedOptions.value = [optionId]
  }
}

// Submit vote
const submitVote = async () => {
  if (!canVote.value || selectedOptions.value.length === 0) {
    return
  }
  
  // Check points
  const pointsRequired = 12
  if (authStore.user && authStore.user.pp < pointsRequired) {
    swal.warning(`แต้มของคุณไม่พอสำหรับการโหวต\n\nต้องการ: ${pointsRequired} แต้ม\nมีอยู่: ${authStore.user.pp} แต้ม\nขาดอีก: ${pointsRequired - authStore.user.pp} แต้ม`, 'แต้มไม่พอ')
    return
  }
  
  isVoting.value = true
  
  try {
    const response = await votePoll(localPoll.value.id, selectedOptions.value)
    
    if (response.success) {
      // Update local state
      localPoll.value.user_voted = true
      localPoll.value.user_votes = [...selectedOptions.value]
      
      // Update option votes
      localPoll.value.options = localPoll.value.options.map(option => ({
        ...option,
        votes: selectedOptions.value.includes(option.id) ? option.votes + 1 : option.votes,
        is_user_vote: selectedOptions.value.includes(option.id),
      }))
      
      // Recalculate percentages
      const totalVotes = localPoll.value.options.reduce((sum, opt) => sum + opt.votes, 0)
      localPoll.value.total_votes = totalVotes
      
      localPoll.value.options = localPoll.value.options.map(option => ({
        ...option,
        percentage: totalVotes > 0 ? Math.round((option.votes / totalVotes) * 100) : 0,
      }))
      
      // Update store
      pollStore.updatePollVote(localPoll.value.id, selectedOptions.value)
      
      // Show results
      showResults.value = true
      selectedOptions.value = []
      
      toast.success('โหวตสำเร็จ!')
    } else {
      swal.error(response.message || 'ไม่สามารถโหวตได้')
    }
  } catch (error) {
    console.error('Error voting on poll:', error)
    const errorMsg = error?.data?.message || error?.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่'
    swal.error(errorMsg)
  } finally {
    isVoting.value = false
  }
}

// View results
const viewResults = () => {
  showResults.value = true
}

// Change vote
const changeVote = () => {
  showResults.value = false
  selectedOptions.value = []
}

// Handle menu actions
const handleEdit = () => {
  emit('update')
}

const handleClosePoll = async () => {
  try {
    const { closePoll } = usePolls()
    const response = await closePoll(localPoll.value.id)
    
    if (response.success) {
      localPoll.value.is_ended = true
      showResults.value = true
      pollStore.closePollState(localPoll.value.id)
      toast.success('ปิดโหวตสำเร็จ!')
    } else {
      swal.error(response.message || 'ไม่สามารถปิดโพลได้')
    }
  } catch (error) {
    console.error('Error closing poll:', error)
    swal.error('เกิดข้อผิดพลาด กรุณาลองใหม่')
  }
}

const handleDelete = async () => {
  try {
    const { deletePoll } = usePolls()
    const response = await deletePoll(localPoll.value.id)
    
    if (response.success) {
      pollStore.removePoll(localPoll.value.id)
      emit('delete')
      toast.success('ลบโพลสำเร็จ!')
    } else {
      swal.error(response.message || 'ไม่สามารถลบโพลได้')
    }
  } catch (error) {
    console.error('Error deleting poll:', error)
    swal.error('เกิดข้อผิดพลาด กรุณาลองใหม่')
  }
}

const handleShare = () => {
  // Implement share functionality
  toast.info('ฟีเจอร์แชร์จะเข้ามาเร็วๆ นี้!')
}
</script>

<template>
  <div 
    :class="[
      'poll-card',
      isNested ? 'poll-card-nested' : ''
    ]"
  >
    <!-- Header -->
    <div class="poll-header">
      <div class="flex items-center gap-3">
        <!-- Author Avatar -->
        <img 
          :src="getAvatarUrl(localPoll.author)" 
          class="poll-author-avatar"
        />
        
        <!-- Author Info -->
        <div class="flex-1">
          <div class="flex items-center gap-2 flex-wrap">
            <span class="poll-author-name">
              {{ localPoll.author.name }}
            </span>
            
            <!-- Poll Badge -->
            <span class="poll-badge">
              <Icon icon="fluent:poll-24-regular" class="w-3.5 h-3.5" />
              <span>โพล</span>
            </span>
          </div>
          
          <!-- Time Info -->
          <div class="poll-meta">
            <Icon icon="fluent:clock-24-regular" class="w-3.5 h-3.5" />
            <span>{{ timeRemaining }}</span>
          </div>
        </div>
      </div>
      
      <!-- Menu Button (only for owner) -->
      <div v-if="showActions && isOwner" class="relative">
        <button 
          @click="showMenu = !showMenu"
          class="poll-menu-trigger"
        >
          <Icon icon="fluent:more-horizontal-24-regular" class="w-5 h-5" />
        </button>
        
        <!-- Poll Menu -->
        <PollMenu
          :show="showMenu"
          :poll-id="localPoll.id"
          :is-owner="isOwner"
          :is-ended="isEnded"
          @close="showMenu = false"
          @edit="handleEdit"
          @close-poll="handleClosePoll"
          @delete="handleDelete"
          @share="handleShare"
        />
      </div>
    </div>
    
    <!-- Poll Question -->
    <h3 class="poll-question">
      <Icon icon="fluent:chart-multiple-24-regular" class="w-5 h-5 text-vikinger-cyan" />
      {{ localPoll.question }}
    </h3>
    
    <!-- Voting Mode -->
    <div v-if="showVotingMode" class="poll-options">
      <PollOption
        v-for="(option, index) in localPoll.options"
        :key="option.id"
        :option="option"
        :is-voting-mode="true"
        :is-selected="selectedOptions.includes(option.id)"
        :is-multiple="localPoll.is_multiple"
        :index="index"
        @click="selectOption(option.id)"
      />
      
      <!-- Submit Button -->
      <button
        v-if="selectedOptions.length > 0"
        @click="submitVote"
        :disabled="isVoting"
        class="poll-submit-btn"
      >
        <Icon v-if="isVoting" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
        <span>{{ isVoting ? 'กำลังส่งโหวต...' : 'ส่งโหวต' }}</span>
      </button>
      
      <!-- View Results Button -->
      <button
        v-else
        @click="viewResults"
        class="poll-view-results-btn"
      >
        <Icon icon="fluent:chart-multiple-24-regular" class="w-5 h-5" />
        <span>ดูผลลัพธ์</span>
      </button>
    </div>
    
    <!-- Results Mode -->
    <div v-else class="poll-options">
      <PollOption
        v-for="(option, index) in sortedOptions"
        :key="option.id"
        :option="option"
        :is-voting-mode="false"
        :is-selected="false"
        :is-multiple="false"
        :index="index"
        @click="changeVote"
      />
      
      <!-- Change Vote Button (if user voted) -->
      <button
        v-if="hasVoted && !isEnded"
        @click="changeVote"
        class="poll-change-vote-btn"
      >
        <Icon icon="fluent:arrow-clockwise-24-regular" class="w-5 h-5" />
        <span>เปลี่ยนโหวต</span>
      </button>
    </div>
    
    <!-- Footer Stats -->
    <div class="poll-footer">
      <div class="flex items-center gap-4">
        <span class="poll-stat-item">
          <Icon icon="fluent:thumb-like-24-regular" class="w-4 h-4" />
          <span>{{ localPoll.total_votes }} คนโหวต</span>
        </span>
        
        <span v-if="!isEnded" class="poll-stat-item">
          <Icon icon="fluent:clock-24-regular" class="w-4 h-4" />
          <span>{{ timeRemaining }}</span>
        </span>
        
        <span v-if="hasVoted" class="poll-stat-item voted">
          <Icon icon="fluent:checkmark-circle-24-filled" class="w-4 h-4" />
          <span>คุณโหวตแล้ว</span>
        </span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.poll-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.dark .poll-card {
  background: #2f3749;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
}

.poll-card-nested {
  border: 1px solid rgba(0, 0, 0, 0.1);
  border-radius: 12px;
  box-shadow: none;
}

.dark .poll-card-nested {
  border-color: rgba(255, 255, 255, 0.05);
}

/* Header */
.poll-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.dark .poll-header {
  border-bottom-color: rgba(255, 255, 255, 0.05);
}

.poll-author-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
}

.poll-author-name {
  font-size: 15px;
  font-weight: 600;
  color: #1f2937;
}

.dark .poll-author-name {
  color: #f9fafb;
}

.poll-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 10px;
  background: rgba(255, 215, 0, 0.1);
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
  color: #ffd700;
}

.poll-meta {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 13px;
  color: #6b7280;
  margin-top: 4px;
}

.dark .poll-meta {
  color: #9ca3af;
}

.poll-menu-trigger {
  padding: 8px;
  background: transparent;
  border: none;
  color: #6b7280;
  cursor: pointer;
  border-radius: 8px;
  transition: all 0.2s ease;
}

.dark .poll-menu-trigger {
  color: #9ca3af;
}

.poll-menu-trigger:hover {
  background: rgba(0, 0, 0, 0.05);
}

.dark .poll-menu-trigger:hover {
  background: rgba(255, 255, 255, 0.05);
}

/* Question */
.poll-question {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 16px 16px 0;
  font-size: 18px;
  font-weight: 600;
  color: #1f2937;
  line-height: 1.5;
}

.dark .poll-question {
  color: #f9fafb;
}

/* Options Container */
.poll-options {
  padding: 0 16px 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* Submit Button */
.poll-submit-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 24px;
  background: linear-gradient(90deg, #615dfa 0%, #23d2e2 100%);
  color: white;
  font-size: 15px;
  font-weight: 600;
  border: none;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s ease;
  margin-top: 8px;
}

.poll-submit-btn:hover:not(:disabled) {
  box-shadow: 0 8px 40px rgba(97, 93, 250, 0.35);
  transform: scale(1.02);
}

.poll-submit-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.poll-submit-btn:disabled:hover {
  transform: none;
  box-shadow: none;
}

/* View Results Button */
.poll-view-results-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 24px;
  background: rgba(35, 210, 226, 0.1);
  color: #23d2e2;
  font-size: 15px;
  font-weight: 600;
  border: none;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
  margin-top: 8px;
}

.poll-view-results-btn:hover {
  background: rgba(35, 210, 226, 0.15);
}

/* Change Vote Button */
.poll-change-vote-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 24px;
  background: rgba(97, 93, 250, 0.1);
  color: #615dfa;
  font-size: 14px;
  font-weight: 500;
  border: none;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
  margin-top: 8px;
}

.poll-change-vote-btn:hover {
  background: rgba(97, 93, 250, 0.15);
}

/* Footer */
.poll-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  border-top: 1px solid rgba(0, 0, 0, 0.1);
  font-size: 13px;
}

.dark .poll-footer {
  border-top-color: rgba(255, 255, 255, 0.05);
}

.poll-stat-item {
  display: flex;
  align-items: center;
  gap: 4px;
  color: #6b7280;
}

.dark .poll-stat-item {
  color: #9ca3af;
}

.poll-stat-item.voted {
  color: #00e676;
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .poll-submit-btn:hover:not(:disabled),
  .poll-view-results-btn:hover,
  .poll-change-vote-btn:hover {
    transform: none;
  }
}
</style>
