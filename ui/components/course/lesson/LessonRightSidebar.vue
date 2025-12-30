<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  stats?: any
  bookmarks?: any[]
  leaderboard?: any[]
  deadlines?: any[]
}

const props = withDefaults(defineProps<Props>(), {
  stats: () => ({}),
  bookmarks: () => [],
  leaderboard: () => [],
  deadlines: () => []
})
</script>

<template>
  <aside class="space-y-4 sticky top-4">
    
    <!-- Quick Stats Card -->
    <div class="bg-gradient-to-br from-purple-500 to-pink-500 rounded-2xl shadow-lg p-6 text-white">
      <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
        <Icon icon="fluent:chart-multiple-24-filled" class="w-5 h-5" />
        สถิติด่วน
      </h3>
      <div class="grid grid-cols-2 gap-3">
        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3">
          <p class="text-xs opacity-90 mb-1">บทเรียนทั้งหมด</p>
          <p class="text-2xl font-bold">{{ stats.total_lessons || 0 }}</p>
        </div>
        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3">
          <p class="text-xs opacity-90 mb-1">เวลาเฉลี่ย</p>
          <p class="text-2xl font-bold">{{ stats.avg_time || 0 }}<span class="text-sm">น.</span></p>
        </div>
        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3">
          <p class="text-xs opacity-90 mb-1">อันดับของคุณ</p>
          <p class="text-2xl font-bold">#{{ stats.your_rank || '-' }}</p>
        </div>
        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3">
          <p class="text-xs opacity-90 mb-1">พอยต์ที่มี</p>
          <p class="text-2xl font-bold">{{ stats.points_available || 0 }}</p>
        </div>
      </div>
    </div>

    <!-- Your Bookmarks -->
    <div v-if="bookmarks.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-4">
      <h3 class="flex items-center gap-2 font-semibold text-gray-900 dark:text-white mb-4">
        <Icon icon="fluent:bookmark-24-filled" class="w-5 h-5 text-amber-500" />
        บุ๊กมาร์กของคุณ
      </h3>
      <div class="space-y-2">
        <a
          v-for="bookmark in bookmarks.slice(0, 3)"
          :key="bookmark.id"
          :href="`/courses/${bookmark.course_id}/lessons/${bookmark.id}`"
          class="block p-3 bg-gray-50 dark:bg-gray-900 rounded-lg hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors group"
        >
          <p class="font-medium text-gray-900 dark:text-white text-sm line-clamp-1 group-hover:text-amber-600 dark:group-hover:text-amber-400">
            {{ bookmark.title }}
          </p>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            บทที่ {{ bookmark.order }}
          </p>
        </a>
      </div>
      <button class="w-full mt-3 px-4 py-2 text-sm text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg transition-colors font-medium">
        ดูทั้งหมด →
      </button>
    </div>

    <!-- Top Rated Lessons -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-4">
      <h3 class="flex items-center gap-2 font-semibold text-gray-900 dark:text-white mb-4">
        <Icon icon="fluent:star-24-filled" class="w-5 h-5 text-yellow-500" />
        บทเรียนยอดนิยม
      </h3>
      <div class="space-y-3">
        <div
          v-for="(lesson, index) in stats.top_lessons?.slice(0, 3) || []"
          :key="lesson.id"
          class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors cursor-pointer"
        >
          <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-yellow-400 to-orange-500 flex items-center justify-center text-white font-bold text-sm">
            {{ index + 1 }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="font-medium text-gray-900 dark:text-white text-sm line-clamp-1">
              {{ lesson.title }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              {{ lesson.like_count }} ❤️
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Upcoming Deadlines -->
    <div v-if="deadlines.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-4">
      <h3 class="flex items-center gap-2 font-semibold text-gray-900 dark:text-white mb-4">
        <Icon icon="fluent:calendar-clock-24-filled" class="w-5 h-5 text-red-500" />
        กำหนดส่งงาน
      </h3>
      <div class="space-y-2">
        <div
          v-for="deadline in deadlines.slice(0, 3)"
          :key="deadline.id"
          class="p-3 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 rounded-lg"
        >
          <p class="font-medium text-gray-900 dark:text-white text-sm">
            {{ deadline.title }}
          </p>
          <p class="text-xs text-red-600 dark:text-red-400 mt-1">
            ⏰ {{ deadline.due_date }}
          </p>
        </div>
      </div>
    </div>

    <!-- Leaderboard -->
    <div v-if="leaderboard.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-4">
      <h3 class="flex items-center gap-2 font-semibold text-gray-900 dark:text-white mb-4">
        <Icon icon="fluent:trophy-24-filled" class="w-5 h-5 text-amber-500" />
        กระดานผู้นำ
      </h3>
      <div class="space-y-2">
        <div
          v-for="(user, index) in leaderboard.slice(0, 5)"
          :key="user.id"
          class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
        >
          <!-- Rank Badge -->
          <div
            class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm"
            :class="[
              index === 0 ? 'bg-gradient-to-br from-yellow-400 to-orange-500 text-white' :
              index === 1 ? 'bg-gradient-to-br from-gray-300 to-gray-400 text-white' :
              index === 2 ? 'bg-gradient-to-br from-orange-400 to-orange-600 text-white' :
              'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400'
            ]"
          >
            {{ index + 1 }}
          </div>

          <!-- Avatar -->
          <img
            :src="user.avatar"
            :alt="user.name"
            class="w-8 h-8 rounded-full object-cover"
          >

          <!-- Info -->
          <div class="flex-1 min-w-0">
            <p class="font-medium text-gray-900 dark:text-white text-sm truncate">
              {{ user.name }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              {{ user.points }} คะแนน
            </p>
          </div>

          <!-- Medal Icon -->
          <Icon
            v-if="index < 3"
            icon="fluent:trophy-24-filled"
            class="w-5 h-5"
            :class="[
              index === 0 ? 'text-yellow-500' :
              index === 1 ? 'text-gray-400' :
              'text-orange-500'
            ]"
          />
        </div>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-4">
      <h3 class="flex items-center gap-2 font-semibold text-gray-900 dark:text-white mb-4">
        <Icon icon="fluent:history-24-filled" class="w-5 h-5 text-blue-500" />
        กิจกรรมล่าสุด
      </h3>
      <div class="space-y-3">
        <div
          v-for="activity in stats.recent_activities?.slice(0, 3) || []"
          :key="activity.id"
          class="flex items-start gap-3 text-sm"
        >
          <img
            :src="activity.user?.avatar"
            :alt="activity.user?.name"
            class="w-8 h-8 rounded-full object-cover flex-shrink-0"
          >
          <div class="flex-1 min-w-0">
            <p class="text-gray-900 dark:text-white">
              <span class="font-medium">{{ activity.user?.name }}</span>
              <span class="text-gray-600 dark:text-gray-400"> เรียนจบ</span>
            </p>
            <p class="text-gray-500 dark:text-gray-400 text-xs truncate">
              {{ activity.lesson?.title }}
            </p>
            <p class="text-gray-400 dark:text-gray-500 text-xs">
              {{ activity.time_ago }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </aside>
</template>
