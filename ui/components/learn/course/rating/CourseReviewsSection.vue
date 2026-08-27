<script setup lang="ts">
import { Icon } from '@iconify/vue'
import CourseRatingStars from './CourseRatingStars.vue'

interface Review {
  id: number
  user_id: number
  rating: number
  title: string | null
  content: string | null
  created_at_formatted: string
  user: {
    id: number
    name: string
    avatar: string | null
    reference_code: string
  }
  is_own: boolean
}

interface ReviewSummary {
  average_rating: number
  total_reviews: number
  distribution: Record<number, number>
}

const props = defineProps<{
  courseId: number
  isMember?: boolean
}>()

const api = useApi()

const reviews = ref<Review[]>([])
const summary = ref<ReviewSummary>({
  average_rating: 0,
  total_reviews: 0,
  distribution: { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 }
})
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0
})

const myReview = ref<Review | null>(null)
const canReview = ref(false)
const isLoading = ref(true)
const isLoadingMore = ref(false)
const showReviewForm = ref(false)
const editingReview = ref<Review | null>(null)

// Fetch reviews
const fetchReviews = async (page = 1, append = false) => {
  if (page === 1) {
    isLoading.value = true
  } else {
    isLoadingMore.value = true
  }
  
  try {
    const response = await api.get(`/api/courses/${props.courseId}/reviews`, {
      params: { page, per_page: 10 }
    }) as { success: boolean; reviews?: Review[]; summary?: ReviewSummary; pagination?: typeof pagination.value }
    
    if (response.success) {
      if (append) {
        reviews.value = [...reviews.value, ...(response.reviews || [])]
      } else {
        reviews.value = response.reviews || []
      }
      summary.value = response.summary || summary.value
      pagination.value = response.pagination || pagination.value
    }
  } catch (err) {
    console.error('Failed to fetch reviews:', err)
  } finally {
    isLoading.value = false
    isLoadingMore.value = false
  }
}

// Fetch my review
const fetchMyReview = async () => {
  if (!props.isMember) return
  
  try {
    const response = await api.get(`/api/courses/${props.courseId}/reviews/my-review`) as { success: boolean; review?: Review | null; can_review?: boolean }
    if (response.success) {
      myReview.value = response.review || null
      canReview.value = response.can_review || false
    }
  } catch (err) {
    console.error('Failed to fetch my review:', err)
  }
}

// Delete review
const deleteReview = async (reviewId: number) => {
  try {
    const response = await api.delete(`/api/courses/${props.courseId}/reviews/${reviewId}`) as { success: boolean }
    if (response.success) {
      // Remove from list
      reviews.value = reviews.value.filter(r => r.id !== reviewId)
      // Clear my review if it was deleted
      if (myReview.value?.id === reviewId) {
        myReview.value = null
      }
      // Refresh summary
      fetchReviews(1)
    }
  } catch (err) {
    console.error('Failed to delete review:', err)
  }
}

// Handle review submitted
const handleReviewSubmitted = (review: Review) => {
  myReview.value = review
  showReviewForm.value = false
  editingReview.value = null
  // Refresh reviews list
  fetchReviews(1)
}

// Handle edit review
const handleEditReview = (review: Review) => {
  editingReview.value = review
  showReviewForm.value = true
}

// Cancel form
const handleCancelForm = () => {
  showReviewForm.value = false
  editingReview.value = null
}

// Load more reviews
const loadMore = () => {
  if (pagination.value.current_page < pagination.value.last_page) {
    fetchReviews(pagination.value.current_page + 1, true)
  }
}

// Calculate distribution percentage
const getDistributionPercent = (rating: number) => {
  if (summary.value.total_reviews === 0) return 0
  return ((summary.value.distribution[rating] || 0) / summary.value.total_reviews) * 100
}

// Initialize
onMounted(() => {
  fetchReviews()
  fetchMyReview()
})

// Watch for course changes
watch(() => props.courseId, () => {
  fetchReviews()
  fetchMyReview()
})
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
      <Icon icon="fluent:star-24-regular" class="w-5 h-5 text-yellow-500" />
      รีวิวและคะแนน
    </h3>
    
    <!-- Loading State -->
    <div v-if="isLoading" class="flex items-center justify-center py-12">
      <Icon icon="svg-spinners:ring-resize" class="w-8 h-8 text-blue-500" />
    </div>
    
    <template v-else>
      <!-- Rating Summary -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Average Rating -->
        <div class="text-center md:text-left">
          <div class="flex items-center justify-center md:justify-start gap-3 mb-2">
            <span class="text-5xl font-bold text-gray-900 dark:text-white">
              {{ summary.average_rating > 0 ? summary.average_rating.toFixed(1) : '-' }}
            </span>
            <div>
              <CourseRatingStars
                :rating="summary.average_rating"
                size="md"
              />
              <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ summary.total_reviews }} รีวิว
              </p>
            </div>
          </div>
        </div>
        
        <!-- Rating Distribution -->
        <div class="space-y-2">
          <div 
            v-for="rating in [5, 4, 3, 2, 1]" 
            :key="rating"
            class="flex items-center gap-2"
          >
            <span class="text-sm text-gray-600 dark:text-gray-400 w-3">{{ rating }}</span>
            <Icon icon="fluent:star-24-filled" class="w-4 h-4 text-yellow-400" />
            <div class="flex-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
              <div 
                class="h-full bg-yellow-400 rounded-full transition-all duration-300"
                :style="{ width: `${getDistributionPercent(rating)}%` }"
              ></div>
            </div>
            <span class="text-xs text-gray-500 dark:text-gray-400 w-8 text-right">
              {{ summary.distribution[rating] || 0 }}
            </span>
          </div>
        </div>
      </div>
      
      <!-- Write Review Button / Form -->
      <div v-if="isMember && canReview && !myReview" class="mb-6">
        <button
          v-if="!showReviewForm"
          @click="showReviewForm = true"
          class="w-full py-3 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl text-gray-600 dark:text-gray-400 hover:border-blue-500 hover:text-blue-500 transition-colors flex items-center justify-center gap-2"
        >
          <Icon icon="fluent:edit-24-regular" class="w-5 h-5" />
          เขียนรีวิว
        </button>
        
        <LearnCourseRatingCourseReviewForm
          v-else
          :course-id="courseId"
          :existing-review="editingReview"
          @submitted="handleReviewSubmitted"
          @cancel="handleCancelForm"
        />
      </div>
      
      <!-- Edit Own Review -->
      <div v-else-if="isMember && myReview && showReviewForm" class="mb-6">
        <LearnCourseRatingCourseReviewForm
          :course-id="courseId"
          :existing-review="{ id: myReview.id, rating: myReview.rating, title: myReview.title, content: myReview.content }"
          @submitted="handleReviewSubmitted"
          @cancel="handleCancelForm"
        />
      </div>
      
      <!-- Non-member message -->
      <div v-else-if="!isMember" class="mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl text-center">
        <p class="text-sm text-gray-600 dark:text-gray-400">
          <Icon icon="fluent:info-24-regular" class="w-4 h-4 inline mr-1" />
          สมัครเรียนเพื่อเขียนรีวิว
        </p>
      </div>
      
      <!-- Reviews List -->
      <div v-if="reviews.length > 0" class="space-y-4">
        <LearnCourseRatingCourseReviewCard
          v-for="review in reviews"
          :key="review.id"
          :review="review"
          @edit="handleEditReview"
          @delete="deleteReview"
        />
        
        <!-- Load More Button -->
        <div v-if="pagination.current_page < pagination.last_page" class="text-center pt-4">
          <button
            @click="loadMore"
            :disabled="isLoadingMore"
            class="min-h-[44px] sm:min-h-0 px-6 py-2 text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors flex items-center gap-2 mx-auto"
          >
            <Icon v-if="isLoadingMore" icon="svg-spinners:ring-resize" class="w-4 h-4" />
            <span>โหลดเพิ่มเติม</span>
          </button>
        </div>
      </div>
      
      <!-- Empty State -->
      <div v-else class="text-center py-8">
        <Icon icon="fluent:chat-empty-24-regular" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
        <p class="text-gray-500 dark:text-gray-400">ยังไม่มีรีวิว</p>
        <p v-if="isMember && canReview" class="text-sm text-gray-400 dark:text-gray-500 mt-1">
          เป็นคนแรกที่รีวิวรายวิชานี้!
        </p>
      </div>
    </template>
  </div>
</template>
