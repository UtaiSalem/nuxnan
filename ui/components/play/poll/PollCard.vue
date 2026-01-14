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
const { likePoll, dislikePoll, commentOnPoll } = usePolls()
const isLiking = ref(false)
const isDisliking = ref(false)
const showComments = ref(false)
const newComment = ref('')
const isCommenting = ref(false)
const { deletePollComment } = usePolls()

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

const currentUserAvatar = computed(() => getAvatarUrl(authStore.user))

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

const maxPercentage = computed(() => {
  if (localPoll.value.options.length === 0) return 0
  return Math.max(...localPoll.value.options.map(o => o.percentage))
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
  
  isVoting.value = true
  
  try {
    // For now, our backend PollVoteController handles single option voting
    const optionId = selectedOptions.value[0]
    const response = await votePoll(localPoll.value.id, optionId) as any
    
    if (response.success) {
      // Update local state
      localPoll.value.user_voted = true
      localPoll.value.user_votes = [optionId]
      
      // Update option votes
      localPoll.value.options = localPoll.value.options.map(option => ({
        ...option,
        votes: option.id === optionId ? option.votes + 1 : option.votes,
        is_user_vote: option.id === optionId,
      }))
      
      // Recalculate percentages
      const totalVotes = localPoll.value.options.reduce((sum, opt) => sum + opt.votes, 0)
      localPoll.value.total_votes = totalVotes
      
      localPoll.value.options = localPoll.value.options.map(option => ({
        ...option,
        percentage: totalVotes > 0 ? Math.round((option.votes / totalVotes) * 100) : 0,
      }))
      
      // If points were earned, update auth store
      if (response.points_earned && response.points_earned > 0) {
        authStore.user.pp += response.points_earned
        swal.success(`โหวตสำเร็จ! คุณได้รับ ${response.points_earned} แต้ม`, 'โหวตสำเร็จ')
      } else {
        toast.success(response.message || 'โหวตสำเร็จ!')
      }
      
      // Show results
      showResults.value = true
      selectedOptions.value = []
    } else {
      swal.error(response.message || 'ไม่สามารถโหวตได้')
    }
  } catch (error) {
    console.error('Error voting on poll:', error)
    const errorMsg = error?.data?.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่'
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

const toggleLike = async () => {
  if (isLiking.value) return
  isLiking.value = true
  try {
    const response = await likePoll(localPoll.value.id)
    if (response.success) {
      localPoll.value.user_reactions.is_liked = !localPoll.value.user_reactions.is_liked
      if (localPoll.value.user_reactions.is_liked) {
          localPoll.value.reaction_counts.likes++
          if (localPoll.value.user_reactions.is_disliked) {
              localPoll.value.user_reactions.is_disliked = false
              localPoll.value.reaction_counts.dislikes--
          }
      } else {
          localPoll.value.reaction_counts.likes--
      }
    } else {
      swal.error(response.message || 'ไม่สามารถถูกใจได้')
    }
  } catch (error) {
    swal.error(error?.data?.message || 'เกิดข้อผิดพลาด')
  } finally {
    isLiking.value = false
  }
}

const toggleDislike = async () => {
  if (isDisliking.value) return
  isDisliking.value = true
  try {
    const response = await dislikePoll(localPoll.value.id)
    if (response.success) {
      localPoll.value.user_reactions.is_disliked = !localPoll.value.user_reactions.is_disliked
      if (localPoll.value.user_reactions.is_disliked) {
          localPoll.value.reaction_counts.dislikes++
          if (localPoll.value.user_reactions.is_liked) {
              localPoll.value.user_reactions.is_liked = false
              localPoll.value.reaction_counts.likes--
          }
      } else {
          localPoll.value.reaction_counts.dislikes--
      }
    } else {
      swal.error(response.message || 'ไม่สามารถไม่ถูกใจได้')
    }
  } catch (error) {
    swal.error(error?.data?.message || 'เกิดข้อผิดพลาด')
  } finally {
    isDisliking.value = false
  }
}

const submitComment = async () => {
  if (!newComment.value.trim() || isCommenting.value) return
  isCommenting.value = true
  try {
    const response = await commentOnPoll(localPoll.value.id, newComment.value)
    if (response.success && response.comment) {
      if (!localPoll.value.comments) localPoll.value.comments = []
      localPoll.value.comments.unshift(response.comment)
      localPoll.value.reaction_counts.comments++
      newComment.value = ''
      toast.success('แสดงความคิดเห็นสำเร็จ!')
    } else {
      swal.error(response.message || 'ไม่สามารถแสดงความคิดเห็นได้')
    }
  } catch (error) {
    swal.error(error?.data?.message || 'เกิดข้อผิดพลาด')
  } finally {
    isCommenting.value = false
  }
}

const handleDeleteComment = async (commentId: number) => {
  const result = await swal.confirm('คุณแน่ใจหรือไม่ว่าต้องการลบความคิดเห็นนี้?', 'ยืนยันการลบ')
  if (!result) return
  
  try {
    const response = await deletePollComment(commentId)
    if (response.success) {
      localPoll.value.comments = localPoll.value.comments?.filter(c => c.id !== commentId)
      localPoll.value.reaction_counts.comments--
      toast.success('ลบความคิดเห็นสำเร็จ!')
    } else {
      swal.error(response.message || 'ไม่สามารถลบความคิดเห็นได้')
    }
  } catch (error) {
    swal.error('เกิดข้อผิดพลาดในการลบความคิดเห็น')
  }
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
    <div v-if="!isNested || localPoll.points_pool > 0 || !isEnded" class="poll-header">
      <div v-if="!isNested" class="flex items-center gap-3">
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

      <!-- Nested Header (Poll-specific info only) -->
      <div v-else class="flex items-center gap-3">
        <!-- Poll Badge -->
        <span class="poll-badge">
          <Icon icon="fluent:poll-24-regular" class="w-3.5 h-3.5" />
          <span>โพล</span>
        </span>

        <!-- Time Info -->
        <div class="poll-meta !mt-0">
          <Icon icon="fluent:clock-24-regular" class="w-3.5 h-3.5" />
          <span>{{ timeRemaining }}</span>
        </div>
      </div>
      
      <!-- Reward Info -->
      <div v-if="localPoll.points_pool > 0" class="flex flex-col items-end">
        <div class="flex items-center gap-1.5 px-3 py-1 bg-vikinger-cyan/10 rounded-lg text-vikinger-cyan">
          <Icon icon="mdi:piggy-bank" class="w-4 h-4" />
          <span class="text-sm font-bold">{{ localPoll.points_pool - localPoll.points_distributed }} แต้มคงเหลือ</span>
        </div>
        <span class="text-[10px] text-gray-500 mt-1">รางวัลโหวตละ {{ localPoll.points_per_vote }} แต้ม</span>
      </div>
      
      <!-- Menu Button (only for owner) -->
      <div v-if="showActions && isOwner" class="relative">
        <button 
          @click.stop="showMenu = !showMenu"
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
      <div class="space-y-3">
        <PollOption
          v-for="(option, index) in localPoll.options"
          :key="option.id"
          :option="option"
          :is-voting-mode="true"
          :is-selected="selectedOptions.includes(option.id)"
          :is-multiple="!!localPoll.is_multiple"
          :max-percentage="maxPercentage"
          :index="index"
          @click="selectOption(option.id)"
        />
      </div>
      
      <!-- Voter Reward Notice -->
      <div v-if="localPoll.points_pool > 0 && (localPoll.points_pool - localPoll.points_distributed) > 0" class="flex items-center gap-2 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-700/30">
        <Icon icon="fluent:gift-24-filled" class="w-5 h-5 text-green-500 flex-shrink-0" />
        <span class="text-sm text-green-700 dark:text-green-300">
          🎁 ร่วมโหวตเพื่อรับ <strong>{{ localPoll.points_per_vote }}</strong> แต้มรางวัล!
        </span>
      </div>
      
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
      <div class="space-y-3">
        <PollOption
          v-for="(option, index) in sortedOptions"
          :key="option.id"
          :option="option"
          :is-voting-mode="false"
          :is-selected="false"
          :is-multiple="false"
          :max-percentage="maxPercentage"
          :index="index"
          @click="viewResults"
        />
      </div>
      
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
    <div v-if="!isNested" class="poll-footer">
      <div class="flex items-center gap-3">
        <button 
          @click="toggleLike" 
          class="poll-action-btn"
          :class="{ 'active text-vikinger-purple': localPoll.user_reactions.is_liked }"
        >
          <Icon :icon="localPoll.user_reactions.is_liked ? 'fluent:thumb-like-24-filled' : 'fluent:thumb-like-24-regular'" class="w-5 h-5" />
          <span>{{ localPoll.reaction_counts.likes }}</span>
        </button>

        <button 
          @click="toggleDislike" 
          class="poll-action-btn"
          :class="{ 'active text-red-500': localPoll.user_reactions.is_disliked }"
        >
          <Icon :icon="localPoll.user_reactions.is_disliked ? 'fluent:thumb-dislike-24-filled' : 'fluent:thumb-dislike-24-regular'" class="w-5 h-5" />
          <span>{{ localPoll.reaction_counts.dislikes }}</span>
        </button>

        <button 
          @click="showComments = !showComments" 
          class="poll-action-btn"
          :class="{ 'active text-vikinger-cyan': showComments }"
        >
          <Icon icon="fluent:comment-24-regular" class="w-5 h-5" />
          <span>{{ localPoll.reaction_counts.comments }}</span>
        </button>
      </div>

      <div class="flex items-center gap-4 text-xs text-gray-500">
        <span>{{ localPoll.total_votes }} คนโหวต</span>
        <span v-if="hasVoted" class="text-green-500 flex items-center gap-1">
          <Icon icon="fluent:checkmark-circle-24-filled" class="w-3.5 h-3.5" />
          โหวตแล้ว
        </span>
      </div>
    </div>

    <!-- Comment Section -->
    <div v-if="showComments && !isNested" class="poll-comment-section border-t border-gray-100 dark:border-vikinger-dark-50/10">
      <!-- Comment List -->
      <div v-if="localPoll.comments && localPoll.comments.length > 0" class="poll-comments-list p-4 space-y-4 max-h-[300px] overflow-y-auto">
        <div v-for="comment in localPoll.comments" :key="comment.id" class="flex gap-3 group">
          <img :src="getAvatarUrl(comment.user)" class="w-8 h-8 rounded-full object-cover flex-shrink-0" />
          <div class="flex-1">
            <div class="bg-gray-50 dark:bg-vikinger-dark-100 p-3 rounded-2xl rounded-tl-none relative">
              <div class="flex items-center justify-between mb-1">
                <span class="text-sm font-bold text-vikinger-dark-50 dark:text-white">{{ comment.user.name }}</span>
                <span class="text-[10px] text-gray-500">{{ comment.diff_humans_created_at }}</span>
              </div>
              <p class="text-sm text-gray-700 dark:text-gray-300">{{ comment.content }}</p>
              
              <!-- Delete Comment Button -->
              <button 
                v-if="comment.user.id === authStore.user?.id || isOwner"
                @click="handleDeleteComment(comment.id)"
                class="absolute -right-2 -top-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-sm"
              >
                <Icon icon="fluent:delete-24-regular" class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- New Comment Input -->
      <div class="p-4 border-t border-gray-50 dark:border-vikinger-dark-50/5">
        <div class="flex gap-3">
          <img :src="currentUserAvatar" class="w-8 h-8 rounded-full object-cover" />
          <div class="flex-1 relative">
            <input 
              v-model="newComment"
              @keydown.enter="submitComment"
              placeholder="เขียนความเห็น..."
              class="w-full pl-4 pr-10 py-2 bg-gray-50 dark:bg-vikinger-dark-100 border-none rounded-lg text-sm focus:ring-1 focus:ring-vikinger-purple outline-none"
            />
            <button 
              @click="submitComment"
              :disabled="!newComment.trim() || isCommenting"
              class="absolute right-2 top-1/2 -translate-y-1/2 text-vikinger-purple disabled:opacity-30"
            >
              <Icon icon="mdi:send" class="w-5 h-5" />
            </button>
          </div>
        </div>
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
  margin-bottom: 16px;
}

.poll-card-nested {
  margin-bottom: 0;
  border: 1px solid rgba(0, 0, 0, 0.05);
  box-shadow: none;
  background: rgba(0, 0, 0, 0.02);
}

.dark .poll-card-nested {
  background: rgba(255, 255, 255, 0.02);
  border-color: rgba(255, 255, 255, 0.05);
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

/* Action Buttons */
.poll-action-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: transparent;
  border: none;
  border-radius: 8px;
  color: #6b7280;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.dark .poll-action-btn {
  color: #9ca3af;
}

.poll-action-btn:hover {
  background: rgba(0, 0, 0, 0.05);
}

.dark .poll-action-btn:hover {
  background: rgba(255, 255, 255, 0.05);
}

.poll-action-btn.active {
  background: rgba(97, 93, 250, 0.1);
}

.dark .poll-action-btn.active {
  background: rgba(97, 93, 250, 0.2);
}

/* Comment Section */
.poll-comment-section {
  background: #fdfdfd;
}

.dark .poll-comment-section {
  background: rgba(0, 0, 0, 0.1);
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
