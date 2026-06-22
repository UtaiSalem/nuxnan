<script setup lang="ts">
import { Icon } from '@iconify/vue'
import CourseLifecycleBadge from '~/components/learn/course/CourseLifecycleBadge.vue'

const props = defineProps<{
  course: any
  index?: number
  to?: string
}>()

const emit = defineEmits(['loading-page'])
const config = useRuntimeConfig()

const getCoverUrl = (course: any) => {
  if (course.cover) {
    if (course.cover.startsWith('http')) {
      return course.cover
    }
    return `${config.public.apiBase}/storage/images/courses/covers/${course.cover}`
  }
  return `${config.public.apiBase}/storage/images/courses/covers/default_cover.jpg`
}

const getInstructorAvatar = (course: any) => {
  return course.user?.avatar || '/images/default-avatar.png'
}

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('th-TH').format(price || 0)
}

const getBadgeType = (course: any, index: number) => {
  if (index === undefined) return null
  if (course.enrolled_students > 50) return 'bestseller'
  if (index < 3) return 'trending'
  return null
}

const goToCourse = () => {
  emit('loading-page')

  if (props.to) {
    navigateTo(props.to)
  }
}
</script>

<template>
  <div
    class="bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-all cursor-pointer group h-full flex flex-col"
    @click="goToCourse"
  >
    <!-- Cover -->
    <div class="relative h-44 overflow-hidden bg-gray-100 dark:bg-gray-700 flex-shrink-0">
      <img
        :src="getCoverUrl(course)"
        :alt="course.name"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
      />

      <!-- Badge (Best Seller / Trending) -->
      <div
        v-if="getBadgeType(course, index ?? 0)"
        :class="[
          'absolute top-3 left-3 px-3 py-1 text-white text-xs font-bold rounded shadow-lg',
          getBadgeType(course, index ?? 0) === 'bestseller' ? 'bg-blue-500' : 'bg-orange-500',
        ]"
      >
        {{ getBadgeType(course, index ?? 0) === 'bestseller' ? 'Best Seller' : 'Trending' }}
      </div>

      <!-- Lifecycle badge (top-right) — only shown when not in default Active state -->
      <CourseLifecycleBadge
        :course="course"
        hide-when-active
        size="sm"
        class="absolute top-3 right-3 shadow-md"
      />

      <!-- Rating Badge -->
      <div
        v-if="course.rating"
        class="absolute bottom-3 left-3 px-2 py-1 bg-yellow-500 text-gray-900 rounded text-xs font-bold flex items-center gap-1"
      >
        <Icon icon="fluent:star-16-filled" class="w-3 h-3" />
        <span>{{ typeof course.rating === 'number' ? course.rating.toFixed(1) : course.rating }}</span>
        <span v-if="course.reviews_count" class="text-gray-700">({{ course.reviews_count }})</span>
      </div>
    </div>

    <!-- Content -->
    <div class="p-4 flex flex-col flex-grow">
      <!-- Instructor & Price Row -->
      <div class="flex items-center justify-between mb-3">
        <div v-if="course.user" class="flex items-center gap-2">
          <img
            :src="getInstructorAvatar(course)"
            :alt="course.user.name"
            class="w-8 h-8 rounded-full object-cover border-2 border-gray-200 dark:border-gray-600"
          />
          <div class="min-w-0">
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[100px]">
              By: {{ course.user.name }}
            </p>
            <p class="text-xs text-blue-500 truncate max-w-[100px]">
              {{ course.category || 'General' }}
            </p>
          </div>
        </div>

        <!-- Price -->
        <div class="flex items-center gap-3 flex-wrap">
          <!-- Tuition Fees -->
          <template v-if="course.tuition_fees > 0">
            <div class="flex items-center gap-1 text-gray-700 dark:text-gray-300 font-bold">
              <Icon icon="mdi:currency-thb" class="w-5 h-5 text-blue-500" />
              <span>ค่าเรียน {{ formatPrice(course.tuition_fees) }}</span>
            </div>
          </template>
          <!-- Price -->
          <template v-if="course.price > 0">
            <div class="flex items-center gap-1 text-gray-700 dark:text-gray-300 font-bold">
              <Icon icon="ri:bit-coin-line" class="w-5 h-5 text-yellow-500" />
              <span>{{ formatPrice(course.price) }}</span>
            </div>
          </template>
          <!-- Free badge -->
          <template v-if="!course.tuition_fees && !course.price">
            <span class="px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-[10px] font-bold rounded-full">
              ฟรี
            </span>
          </template>
        </div>
      </div>

      <!-- Title -->
      <h3
        class="text-gray-800 dark:text-white font-bold mb-2 line-clamp-2 group-hover:text-blue-500 transition-colors"
      >
        {{ course.name }}
      </h3>

      <!-- Semester & Academic Year Badges -->
      <div v-if="course.semester || course.academic_year" class="flex flex-wrap gap-2 mb-3">
        <div 
          v-if="course.semester" 
          class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 text-[10px] font-medium rounded border border-blue-100 dark:border-blue-800"
        >
          <Icon icon="fluent:calendar-16-regular" class="w-3 h-3" />
          <span>ภาคเรียนที่ {{ course.semester }}</span>
        </div>
        <div 
          v-if="course.academic_year" 
          class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-50 dark:bg-gray-700/30 text-gray-600 dark:text-gray-400 text-[10px] font-medium rounded border border-gray-100 dark:border-gray-700"
        >
          <Icon icon="fluent:hat-graduation-16-regular" class="w-3 h-3" />
          <span>ปีการศึกษา {{ course.academic_year }}</span>
        </div>
      </div>

      <!-- Stats Row -->
      <div
        class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 pt-3 border-t border-gray-100 dark:border-gray-700 mt-auto"
      >
        <div class="flex items-center gap-1">
          <Icon icon="fluent:book-open-16-regular" class="w-4 h-4" />
          <span>{{ course.course_lessons_count ?? course.lessons_count ?? course.lessons ?? 0 }} Lectures</span>
        </div>
        <div class="flex items-center gap-1">
          <Icon icon="fluent:clock-16-regular" class="w-4 h-4" />
          <span>{{ course.hours ?? course.duration ?? course.hours_per_week ?? 0 }}Hrs</span>
        </div>
      </div>

      <!-- Member Status / CTA -->
      <div v-if="course.isMember" class="mt-3">
        <!-- Student -->
        <div class="space-y-2">
          <div class="flex items-center justify-between text-xs font-medium">
             <span class="text-green-600 dark:text-green-400 flex items-center gap-1">
                <Icon icon="fluent:hat-graduation-16-filled" />
                Student
             </span>
             <span class="text-gray-500">{{ Math.round(course.auth_progress || 0) }}%</span>
          </div>
          <!-- Progress Bar -->
          <div class="h-1.5 w-full bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
            <div 
              class="h-full bg-green-500 rounded-full transition-all duration-500"
              :style="{ width: `${course.auth_progress || 0}%` }"
            ></div>
          </div>
        </div>
      </div>

      <!-- Non-Member CTA -->
      <button 
        v-else
        class="mt-3 w-full py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center justify-center gap-2 group-hover:bg-blue-700"
      >
        View Details
        <Icon icon="fluent:arrow-right-16-regular" class="w-4 h-4" />
      </button>
    </div>
  </div>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
