import { computed, type Ref } from 'vue'

export function useMemberProgress(member: Ref<any>, totalScore: Ref<number>) {

  // Total score including bonus points (matches backend getTotalAchievedScore)
  const totalAchievedScore = computed(() => {
    return (member.value?.achieved_score || 0) + (member.value?.bonus_points || 0)
  })

  const percentage = computed(() => {
    if (!totalScore.value || totalScore.value === 0) return 0
    const score = totalAchievedScore.value
    return Math.min(100, Math.round((score / totalScore.value) * 100))
  })

  // Determine status based on percentage
  // 0-49: Needs Improvement / Fair (Orange)
  // 50-79: Good (Yellow/Gold)
  // 80-100: Excellent (Green)
  const progressStatus = computed(() => {
    const p = percentage.value
    if (p >= 80) return 'excellent'
    if (p >= 50) return 'good'
    return 'fair'
  })

  const statusMessage = computed(() => {
    switch (progressStatus.value) {
      case 'excellent': return 'ยอดเยี่ยม'
      case 'good': return 'ดี'
      default: return 'พอใช้'
    }
  })
  
  const statusIcon = computed(() => {
    switch (progressStatus.value) {
        case 'excellent': return 'fluent:ribbon-star-20-filled'
        case 'good': return 'fluent:thumb-like-20-filled'
        default: return 'fluent:battery-3-20-filled'
    }
  })

  const progressColor = computed(() => {
    switch (progressStatus.value) {
      case 'excellent':
        return {
          ring: 'ring-green-500',
          bg: 'bg-green-500',
          text: 'text-green-600',
          gradient: 'from-green-400 to-green-600'
        }
      case 'good':
        return {
          ring: 'ring-yellow-400',
          bg: 'bg-yellow-400',
          text: 'text-yellow-600',
          gradient: 'from-yellow-300 to-yellow-500'
        }
      case 'fair':
      default:
        return {
          ring: 'ring-orange-500',
          bg: 'bg-orange-500',
          text: 'text-orange-600',
          gradient: 'from-orange-400 to-orange-600'
        }
      }
  })

  const remainingScore = computed(() => {
    const achieved = totalAchievedScore.value
    const total = totalScore.value || 0
    return Math.max(0, total - achieved)
  })
  
  const remainingPercentage = computed(() => {
      return Math.max(0, 100 - percentage.value)
  })

  const progressBarStyle = computed(() => {
      return {
          width: `${percentage.value}%`
      }
  })

  return {
    percentage,
    progressStatus,
    progressColor,
    statusMessage,
    statusIcon,
    remainingScore,
    remainingPercentage,
    progressBarStyle,
    totalAchievedScore
  }
}
