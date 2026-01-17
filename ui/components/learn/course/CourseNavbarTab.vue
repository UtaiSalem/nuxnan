<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  courseId: string | number
  isCourseAdmin?: boolean
  courseMemberOfAuth?: any
}

const props = withDefaults(defineProps<Props>(), {
  isCourseAdmin: false,
  courseMemberOfAuth: null
})

const route = useRoute()
const api = useApi()
const courseMemberStore = useCourseMemberStore()

// Determine active tab based on current route
const activeTab = computed(() => {
  const path = route.path
  if (path.includes('/basic-info')) return 12
  if (path.includes('/feeds')) return 11
  if (path.includes('/attendances')) return 7
  if (path.includes('/lessons')) return 1
  if (path.includes('/assignments')) return 2
  if (path.includes('/quizzes')) return 3
  if (path.includes('/play/groups')) return 5
  if (path.includes('/members')) return 4
  if (path.includes('/settings')) return 8
  if (path.includes('/member-settings')) return 9
  if (path.includes('/my-progress')) return 9
  if (path.includes('/progress')) return 10
  if (path.includes('/admin')) return 13
  // Default to info tab for base course page
  if (path.endsWith(`/courses/${props.courseId}`) || path.endsWith(`/courses/${props.courseId}/`)) return 12
  return 12
})

// Save last accessed tab when tab changes
let isSavingTab = false
watch(activeTab, async (newTab, oldTab) => {
  // Only save if member exists and tab actually changed
  if (!props.courseMemberOfAuth?.id || newTab === oldTab || isSavingTab) return
  
  isSavingTab = true
  try {
    await api.post(`/api/courses/${props.courseId}/members/${props.courseMemberOfAuth.id}/set-active-tab`, {
      tab: newTab
    })
    // Update local store
    if (courseMemberStore.member) {
      courseMemberStore.member.last_accessed_tab = newTab
    }
  } catch (error) {
    console.error('Error saving last accessed tab:', error)
  } finally {
    isSavingTab = false
  }
})
</script>

<template>
  <div class="w-full mt-4 overflow-hidden bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-100 dark:border-gray-700">
    <div class="flex flex-row justify-around relative">
      
      <!-- ข้อมูลทั่วไป -->
      <NuxtLink :to="`/courses/${courseId}`"
        class="flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105"
        :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm': activeTab === 12, 'hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent': activeTab !== 12 }">
        <div class="flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300">
          <Icon icon="heroicons:information-circle" class="w-6 h-6 md:w-8 md:h-8 transition-all duration-300"
            :class="{ 'text-cyan-500 scale-110': activeTab === 12, 'hover:text-cyan-400': activeTab !== 12 }" />
          <span class="hidden md:block mt-1 text-sm font-medium transition-all duration-300" :class="{ 'text-cyan-500 font-semibold': activeTab === 12 }">ข้อมูลทั่วไป</span>
        </div>
      </NuxtLink>

      <!-- กระดาน -->
      <NuxtLink :to="`/courses/${courseId}/feeds`"
        class="flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105"
        :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm': activeTab === 11, 'hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent': activeTab !== 11 }">
        <div class="flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300">
          <Icon icon="codicon:feedback" class="w-6 h-6 md:w-8 md:h-8 transition-all duration-300"
            :class="{ 'text-cyan-500 scale-110': activeTab === 11, 'hover:text-cyan-400': activeTab !== 11 }" />
          <span class="hidden md:block mt-1 text-sm font-medium transition-all duration-300" :class="{ 'text-cyan-500 font-semibold': activeTab === 11 }">กระดาน</span>
        </div>
      </NuxtLink>

      <!-- การเข้าเรียน -->
      <NuxtLink v-if="isCourseAdmin || courseMemberOfAuth" :to="`/courses/${courseId}/attendances`"
        class="flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105"
        :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm': activeTab === 7, 'hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent': activeTab !== 7 }">
        <div class="flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300">
          <Icon icon="tabler:calendar-user" class="w-6 h-6 md:w-8 md:h-8 transition-all duration-300"
            :class="{ 'text-cyan-500 scale-110': activeTab === 7, 'hover:text-cyan-400': activeTab !== 7 }" />
          <span class="hidden md:block mt-1 text-sm font-medium transition-all duration-300" :class="{ 'text-cyan-500 font-semibold': activeTab === 7 }">การเข้าเรียน</span>
        </div>
      </NuxtLink>

      <!-- บทเรียน -->
      <NuxtLink :to="`/courses/${courseId}/lessons`"
        class="flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105"
        :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm': activeTab === 1, 'hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent': activeTab !== 1 }">
        <div class="flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300">
          <Icon icon="icon-park-outline:view-grid-detail" class="w-6 h-6 md:w-8 md:h-8 transition-all duration-300"
            :class="{ 'text-cyan-500 scale-110': activeTab === 1, 'hover:text-cyan-400': activeTab !== 1 }" />
          <span class="hidden md:block mt-1 text-sm font-medium transition-all duration-300" :class="{ 'text-cyan-500 font-semibold': activeTab === 1 }">บทเรียน</span>
        </div>
      </NuxtLink>

      <!-- ภาระงาน -->
      <NuxtLink :to="`/courses/${courseId}/assignments`"
        class="flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105"
        :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm': activeTab === 2, 'hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent': activeTab !== 2 }">
        <div class="flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300">
          <Icon icon="material-symbols:assignment-add-outline" class="w-6 h-6 md:w-8 md:h-8 transition-all duration-300"
            :class="{ 'text-cyan-500 scale-110': activeTab === 2, 'hover:text-cyan-400': activeTab !== 2 }" />
          <span class="hidden md:block mt-1 text-sm font-medium transition-all duration-300" :class="{ 'text-cyan-500 font-semibold': activeTab === 2 }">ภาระงาน</span>
        </div>
      </NuxtLink>

      <!-- ทดสอบ -->
      <NuxtLink v-if="courseMemberOfAuth || isCourseAdmin" :to="`/courses/${courseId}/quizzes`"
        class="flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105"
        :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm': activeTab === 3, 'hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent': activeTab !== 3 }">
        <div class="flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300">
          <Icon icon="healthicons:i-exam-qualification-outline" class="w-6 h-6 md:w-8 md:h-8 transition-all duration-300"
            :class="{ 'text-cyan-500 scale-110': activeTab === 3, 'hover:text-cyan-400': activeTab !== 3 }" />
          <span class="hidden md:block mt-1 text-sm font-medium transition-all duration-300" :class="{ 'text-cyan-500 font-semibold': activeTab === 3 }">ทดสอบ</span>
        </div>
      </NuxtLink>

      <!-- กลุ่ม -->
      <NuxtLink :to="`/courses/${courseId}/groups`"
        class="flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105"
        :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm': activeTab === 5, 'hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent': activeTab !== 5 }">
        <div class="flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300">
          <Icon icon="heroicons-outline:user-group" class="w-6 h-6 md:w-8 md:h-8 transition-all duration-300"
            :class="{ 'text-cyan-500 scale-110': activeTab === 5, 'hover:text-cyan-400': activeTab !== 5 }" />
          <span class="hidden md:block mt-1 text-sm font-medium transition-all duration-300" :class="{ 'text-cyan-500 font-semibold': activeTab === 5 }">กลุ่ม</span>
        </div>
      </NuxtLink>

      <!-- สมาชิก -->
      <NuxtLink v-if="courseMemberOfAuth !== null" :to="`/courses/${courseId}/members`"
        class="flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105"
        :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm': activeTab === 4, 'hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent': activeTab !== 4 }">
        <div class="flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300">
          <Icon icon="ph:users-four" class="w-6 h-6 md:w-8 md:h-8 transition-all duration-300"
            :class="{ 'text-cyan-500 scale-110': activeTab === 4, 'hover:text-cyan-400': activeTab !== 4 }" />
          <span class="hidden md:block mt-1 text-sm font-medium transition-all duration-300" :class="{ 'text-cyan-500 font-semibold': activeTab === 4 }">สมาชิก</span>
        </div>
      </NuxtLink>

      <!-- ตั้งค่า (Admin) -->
      <NuxtLink v-if="isCourseAdmin" :to="`/courses/${courseId}/settings`"
        class="flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105"
        :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm': activeTab === 8, 'hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent': activeTab !== 8 }">
        <div class="flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300">
          <Icon icon="mdi-light:settings" class="w-6 h-6 md:w-8 md:h-8 transition-all duration-300"
            :class="{ 'text-cyan-500 scale-110': activeTab === 8, 'hover:text-cyan-400': activeTab !== 8 }" />
          <span class="hidden md:block mt-1 text-sm font-medium transition-all duration-300" :class="{ 'text-cyan-500 font-semibold': activeTab === 8 }">ตั้งค่า</span>
        </div>
      </NuxtLink>

      <!-- ผลการเรียน (Member) -->
      <NuxtLink v-if="!isCourseAdmin && courseMemberOfAuth" :to="`/courses/${courseId}/my-progress`"
        class="flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105"
        :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm': activeTab === 9, 'hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent': activeTab !== 9 }">
        <div class="flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300">
          <Icon icon="mdi:graph-box-plus-outline" class="w-6 h-6 md:w-8 md:h-8 transition-all duration-300"
            :class="{ 'text-cyan-500 scale-110': activeTab === 9, 'hover:text-cyan-400': activeTab !== 9 }" />
          <span class="hidden md:block mt-1 text-sm font-medium transition-all duration-300" :class="{ 'text-cyan-500 font-semibold': activeTab === 9 }">ผลการเรียน</span>
        </div>
      </NuxtLink>

      <!-- ผลการเรียน (Admin) -->
      <NuxtLink v-if="isCourseAdmin" :to="`/courses/${courseId}/progress`"
        class="flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105"
        :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm': activeTab === 10, 'hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent': activeTab !== 10 }">
        <div class="flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300">
          <Icon icon="mdi:graph-box-plus-outline" class="w-6 h-6 md:w-8 md:h-8 transition-all duration-300"
            :class="{ 'text-cyan-500 scale-110': activeTab === 10, 'hover:text-cyan-400': activeTab !== 10 }" />
          <span class="hidden md:block mt-1 text-sm font-medium transition-all duration-300" :class="{ 'text-cyan-500 font-semibold': activeTab === 10 }">ผลการเรียน</span>
        </div>
      </NuxtLink>

      <!-- Admin (ผู้ดูแล) -->
      <NuxtLink v-if="isCourseAdmin" :to="`/courses/${courseId}/admin`"
        class="flex-row justify-center w-full text-center border-b-4 rounded-none tab-item hover:border-gray-400 transition-all duration-300 ease-in-out transform hover:scale-105"
        :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm': activeTab === 13, 'hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent': activeTab !== 13 }">
        <div class="flex flex-col items-center justify-center py-3 text-slate-600/80 dark:text-gray-300 transition-all duration-300">
          <Icon icon="eos-icons:admin-outlined" class="w-6 h-6 md:w-8 md:h-8 transition-all duration-300"
            :class="{ 'text-cyan-500 scale-110': activeTab === 13, 'hover:text-cyan-400': activeTab !== 13 }" />
          <span class="hidden md:block mt-1 text-sm font-medium transition-all duration-300" :class="{ 'text-cyan-500 font-semibold': activeTab === 13 }">ผู้ดูแล</span>
        </div>
      </NuxtLink>

    </div>
  </div>
</template>

<style scoped>
.tab-item {
  border-bottom-style: solid;
}
</style>
