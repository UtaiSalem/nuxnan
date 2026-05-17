<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { computed, watch, onMounted, ref } from 'vue'

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
const scrollContainer = ref<HTMLElement | null>(null)

/**
 * Determine active tab based on current route
 */
const activeTab = computed(() => {
  const path = route.path
  if (path.includes('/basic-info')) return 12
  if (path.includes('/feeds')) return 11
  if (path.includes('/attendances')) return 7
  if (path.includes('/lessons')) return 1
  if (path.includes('/assignments')) return 2
  if (path.includes('/quizzes')) return 3
  if (path.includes('/groups')) return 5
  if (path.includes('/members')) return 4
  if (path.includes('/settings')) return 8
  if (path.includes('/member-settings')) return 9
  if (path.includes('/my-progress')) return 9
  if (path.includes('/progress')) return 10
  if (path.includes('/external-scores')) return 14
  if (path.includes('/admin')) return 13
  // Default to info tab for base course page
  if (path.endsWith(`/Learn/Courses/${props.courseId}`) || path.endsWith(`/Learn/Courses/${props.courseId}/`)) return 12
  return 12
})

/**
 * Scroll the active tab into view
 */
const scrollActiveTabIntoView = () => {
  if (!scrollContainer.value) return
  
  setTimeout(() => {
    const activeElement = scrollContainer.value?.querySelector('[aria-selected="true"]') as HTMLElement
    if (activeElement && scrollContainer.value) {
      const containerWidth = scrollContainer.value.offsetWidth
      const elementOffset = activeElement.offsetLeft
      const elementWidth = activeElement.offsetWidth
      
      scrollContainer.value.scrollTo({
        left: elementOffset - (containerWidth / 2) + (elementWidth / 2),
        behavior: 'smooth'
      })
    }
  }, 100)
}

onMounted(() => {
  scrollActiveTabIntoView()
})

// Watch for route changes to scroll active tab into view
watch(() => route.path, () => {
  scrollActiveTabIntoView()
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
}, { immediate: false })
</script>

<template>
  <div class="w-full max-w-full min-w-0 mt-2 sm:mt-4 overflow-hidden bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700">
    <!-- Horizontal Scrollable Container -->
    <div 
      ref="scrollContainer"
      class="w-full max-w-full overflow-x-auto custom-scrollbar flex flex-nowrap relative min-w-0"
      style="-webkit-overflow-scrolling: touch; scroll-behavior: smooth;"
    >
      <div role="tablist"
           aria-label="เมนูรายวิชา"
           class="flex flex-nowrap min-w-0 w-full">
        
        <!-- ข้อมูลทั่วไป -->
        <NuxtLink :to="`/Learn/Courses/${courseId}`"
          role="tab"
          :aria-selected="activeTab === 12"
          class="flex-shrink-0 w-16 sm:w-24 md:w-28 text-center border-b-4 tab-item min-h-[52px] sm:min-h-[64px] hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-300"
          :class="{ 'border-cyan-500 bg-gradient-to-t from-cyan-50/50 dark:from-cyan-900/20 to-white dark:to-gray-800': activeTab === 12, 'border-transparent': activeTab !== 12 }">
          <div class="flex flex-col items-center justify-center py-1.5 sm:py-3 px-1 transition-all duration-300">
            <Icon icon="heroicons:information-circle" class="w-5 h-5 sm:w-6 sm:h-6 md:w-7 md:h-7"
              :class="{ 'text-cyan-500 scale-110': activeTab === 12, 'text-slate-400 dark:text-gray-500': activeTab !== 12 }" />
            <span class="mt-1 text-[9px] sm:text-xs md:text-sm font-medium whitespace-nowrap leading-tight" 
              :class="{ 'text-cyan-600 dark:text-cyan-400 font-bold': activeTab === 12, 'text-slate-500 dark:text-gray-400': activeTab !== 12 }">ข้อมูล</span>
          </div>
        </NuxtLink>

        <!-- กระดาน -->
        <NuxtLink :to="`/Learn/Courses/${courseId}/feeds`"
          role="tab"
          :aria-selected="activeTab === 11"
          class="flex-shrink-0 w-16 sm:w-24 md:w-28 text-center border-b-4 tab-item min-h-[52px] sm:min-h-[64px] hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-300"
          :class="{ 'border-cyan-500 bg-gradient-to-t from-cyan-50/50 dark:from-cyan-900/20 to-white dark:to-gray-800': activeTab === 11, 'border-transparent': activeTab !== 11 }">
          <div class="flex flex-col items-center justify-center py-1.5 sm:py-3 px-1 transition-all duration-300">
            <Icon icon="codicon:feedback" class="w-5 h-5 sm:w-6 sm:h-6 md:w-7 md:h-7"
              :class="{ 'text-cyan-500 scale-110': activeTab === 11, 'text-slate-400 dark:text-gray-500': activeTab !== 11 }" />
            <span class="mt-1 text-[9px] sm:text-xs md:text-sm font-medium whitespace-nowrap leading-tight" 
              :class="{ 'text-cyan-600 dark:text-cyan-400 font-bold': activeTab === 11, 'text-slate-500 dark:text-gray-400': activeTab !== 11 }">กระดาน</span>
          </div>
        </NuxtLink>

        <!-- การเข้าเรียน -->
        <NuxtLink v-if="isCourseAdmin || courseMemberOfAuth" :to="`/Learn/Courses/${courseId}/attendances`"
          role="tab"
          :aria-selected="activeTab === 7"
          class="flex-shrink-0 w-16 sm:w-24 md:w-28 text-center border-b-4 tab-item min-h-[52px] sm:min-h-[64px] hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-300"
          :class="{ 'border-cyan-500 bg-gradient-to-t from-cyan-50/50 dark:from-cyan-900/20 to-white dark:to-gray-800': activeTab === 7, 'border-transparent': activeTab !== 7 }">
          <div class="flex flex-col items-center justify-center py-1.5 sm:py-3 px-1 transition-all duration-300">
            <Icon icon="tabler:calendar-user" class="w-5 h-5 sm:w-6 sm:h-6 md:w-7 md:h-7"
              :class="{ 'text-cyan-500 scale-110': activeTab === 7, 'text-slate-400 dark:text-gray-500': activeTab !== 7 }" />
            <span class="mt-1 text-[9px] sm:text-xs md:text-sm font-medium whitespace-nowrap leading-tight" 
              :class="{ 'text-cyan-600 dark:text-cyan-400 font-bold': activeTab === 7, 'text-slate-500 dark:text-gray-400': activeTab !== 7 }">เข้าเรียน</span>
          </div>
        </NuxtLink>

        <!-- บทเรียน -->
        <NuxtLink :to="`/Learn/Courses/${courseId}/lessons`"
          role="tab"
          :aria-selected="activeTab === 1"
          class="flex-shrink-0 w-16 sm:w-24 md:w-28 text-center border-b-4 tab-item min-h-[52px] sm:min-h-[64px] hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-300"
          :class="{ 'border-cyan-500 bg-gradient-to-t from-cyan-50/50 dark:from-cyan-900/20 to-white dark:to-gray-800': activeTab === 1, 'border-transparent': activeTab !== 1 }">
          <div class="flex flex-col items-center justify-center py-1.5 sm:py-3 px-1 transition-all duration-300">
            <Icon icon="icon-park-outline:view-grid-detail" class="w-5 h-5 sm:w-6 sm:h-6 md:w-7 md:h-7"
              :class="{ 'text-cyan-500 scale-110': activeTab === 1, 'text-slate-400 dark:text-gray-500': activeTab !== 1 }" />
            <span class="mt-1 text-[9px] sm:text-xs md:text-sm font-medium whitespace-nowrap leading-tight" 
              :class="{ 'text-cyan-600 dark:text-cyan-400 font-bold': activeTab === 1, 'text-slate-500 dark:text-gray-400': activeTab !== 1 }">บทเรียน</span>
          </div>
        </NuxtLink>

        <!-- ภาระงาน -->
        <NuxtLink :to="`/Learn/Courses/${courseId}/assignments`"
          role="tab"
          :aria-selected="activeTab === 2"
          class="flex-shrink-0 w-16 sm:w-24 md:w-28 text-center border-b-4 tab-item min-h-[52px] sm:min-h-[64px] hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-300"
          :class="{ 'border-cyan-500 bg-gradient-to-t from-cyan-50/50 dark:from-cyan-900/20 to-white dark:to-gray-800': activeTab === 2, 'border-transparent': activeTab !== 2 }">
          <div class="flex flex-col items-center justify-center py-1.5 sm:py-3 px-1 transition-all duration-300">
            <Icon icon="material-symbols:assignment-add-outline" class="w-5 h-5 sm:w-6 sm:h-6 md:w-7 md:h-7"
              :class="{ 'text-cyan-500 scale-110': activeTab === 2, 'text-slate-400 dark:text-gray-500': activeTab !== 2 }" />
            <span class="mt-1 text-[9px] sm:text-xs md:text-sm font-medium whitespace-nowrap leading-tight" 
              :class="{ 'text-cyan-600 dark:text-cyan-400 font-bold': activeTab === 2, 'text-slate-500 dark:text-gray-400': activeTab !== 2 }">ภาระงาน</span>
          </div>
        </NuxtLink>

        <!-- ทดสอบ -->
        <NuxtLink v-if="courseMemberOfAuth || isCourseAdmin" :to="`/Learn/Courses/${courseId}/quizzes`"
          role="tab"
          :aria-selected="activeTab === 3"
          class="flex-shrink-0 w-16 sm:w-24 md:w-28 text-center border-b-4 tab-item min-h-[52px] sm:min-h-[64px] hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-300"
          :class="{ 'border-cyan-500 bg-gradient-to-t from-cyan-50/50 dark:from-cyan-900/20 to-white dark:to-gray-800': activeTab === 3, 'border-transparent': activeTab !== 3 }">
          <div class="flex flex-col items-center justify-center py-1.5 sm:py-3 px-1 transition-all duration-300">
            <Icon icon="healthicons:i-exam-qualification-outline" class="w-5 h-5 sm:w-6 sm:h-6 md:w-7 md:h-7"
              :class="{ 'text-cyan-500 scale-110': activeTab === 3, 'text-slate-400 dark:text-gray-500': activeTab !== 3 }" />
            <span class="mt-1 text-[9px] sm:text-xs md:text-sm font-medium whitespace-nowrap leading-tight" 
              :class="{ 'text-cyan-600 dark:text-cyan-400 font-bold': activeTab === 3, 'text-slate-500 dark:text-gray-400': activeTab !== 3 }">ทดสอบ</span>
          </div>
        </NuxtLink>

        <!-- บันทึกคะแนน (Admin) -->
        <NuxtLink v-if="isCourseAdmin" :to="`/Learn/Courses/${courseId}/external-scores`"
          role="tab"
          :aria-selected="activeTab === 14"
          class="flex-shrink-0 w-16 sm:w-24 md:w-28 text-center border-b-4 tab-item min-h-[52px] sm:min-h-[64px] hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-300"
          :class="{ 'border-cyan-500 bg-gradient-to-t from-cyan-50/50 dark:from-cyan-900/20 to-white dark:to-gray-800': activeTab === 14, 'border-transparent': activeTab !== 14 }">
          <div class="flex flex-col items-center justify-center py-1.5 sm:py-3 px-1 transition-all duration-300">
            <Icon icon="mdi:clipboard-text-outline" class="w-5 h-5 sm:w-6 sm:h-6 md:w-7 md:h-7"
              :class="{ 'text-cyan-500 scale-110': activeTab === 14, 'text-slate-400 dark:text-gray-500': activeTab !== 14 }" />
            <span class="mt-1 text-[9px] sm:text-xs md:text-sm font-medium whitespace-nowrap leading-tight" 
              :class="{ 'text-cyan-600 dark:text-cyan-400 font-bold': activeTab === 14, 'text-slate-500 dark:text-gray-400': activeTab !== 14 }">บันทึกคะแนน</span>
          </div>
        </NuxtLink>

        <!-- กลุ่ม -->
        <NuxtLink :to="`/Learn/Courses/${courseId}/groups`"
          role="tab"
          :aria-selected="activeTab === 5"
          class="flex-shrink-0 w-16 sm:w-24 md:w-28 text-center border-b-4 tab-item min-h-[52px] sm:min-h-[64px] hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-300"
          :class="{ 'border-cyan-500 bg-gradient-to-t from-cyan-50/50 dark:from-cyan-900/20 to-white dark:to-gray-800': activeTab === 5, 'border-transparent': activeTab !== 5 }">
          <div class="flex flex-col items-center justify-center py-1.5 sm:py-3 px-1 transition-all duration-300">
            <Icon icon="heroicons-outline:user-group" class="w-5 h-5 sm:w-6 sm:h-6 md:w-7 md:h-7"
              :class="{ 'text-cyan-500 scale-110': activeTab === 5, 'text-slate-400 dark:text-gray-500': activeTab !== 5 }" />
            <span class="mt-1 text-[9px] sm:text-xs md:text-sm font-medium whitespace-nowrap leading-tight" 
              :class="{ 'text-cyan-600 dark:text-cyan-400 font-bold': activeTab === 5, 'text-slate-500 dark:text-gray-400': activeTab !== 5 }">กลุ่ม</span>
          </div>
        </NuxtLink>

        <!-- สมาชิก -->
        <NuxtLink v-if="courseMemberOfAuth !== null" :to="`/Learn/Courses/${courseId}/members`"
          role="tab"
          :aria-selected="activeTab === 4"
          class="flex-shrink-0 w-16 sm:w-24 md:w-28 text-center border-b-4 tab-item min-h-[52px] sm:min-h-[64px] hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-300"
          :class="{ 'border-cyan-500 bg-gradient-to-t from-cyan-50/50 dark:from-cyan-900/20 to-white dark:to-gray-800': activeTab === 4, 'border-transparent': activeTab !== 4 }">
          <div class="flex flex-col items-center justify-center py-1.5 sm:py-3 px-1 transition-all duration-300">
            <Icon icon="ph:users-four" class="w-5 h-5 sm:w-6 sm:h-6 md:w-7 md:h-7"
              :class="{ 'text-cyan-500 scale-110': activeTab === 4, 'text-slate-400 dark:text-gray-500': activeTab !== 4 }" />
            <span class="mt-1 text-[9px] sm:text-xs md:text-sm font-medium whitespace-nowrap leading-tight" 
              :class="{ 'text-cyan-600 dark:text-cyan-400 font-bold': activeTab === 4, 'text-slate-500 dark:text-gray-400': activeTab !== 4 }">สมาชิก</span>
          </div>
        </NuxtLink>

        <!-- ตั้งค่า (Admin) -->
        <NuxtLink v-if="isCourseAdmin" :to="`/Learn/Courses/${courseId}/settings`"
          role="tab"
          :aria-selected="activeTab === 8"
          class="flex-shrink-0 w-16 sm:w-24 md:w-28 text-center border-b-4 tab-item min-h-[52px] sm:min-h-[64px] hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-300"
          :class="{ 'border-cyan-500 bg-gradient-to-t from-cyan-50/50 dark:from-cyan-900/20 to-white dark:to-gray-800': activeTab === 8, 'border-transparent': activeTab !== 8 }">
          <div class="flex flex-col items-center justify-center py-1.5 sm:py-3 px-1 transition-all duration-300">
            <Icon icon="mdi-light:settings" class="w-5 h-5 sm:w-6 sm:h-6 md:w-7 md:h-7"
              :class="{ 'text-cyan-500 scale-110': activeTab === 8, 'text-slate-400 dark:text-gray-500': activeTab !== 8 }" />
            <span class="mt-1 text-[9px] sm:text-xs md:text-sm font-medium whitespace-nowrap leading-tight" 
              :class="{ 'text-cyan-600 dark:text-cyan-400 font-bold': activeTab === 8, 'text-slate-500 dark:text-gray-400': activeTab !== 8 }">ตั้งค่า</span>
          </div>
        </NuxtLink>

        <!-- ผลการเรียน (Member) -->
        <NuxtLink v-if="!isCourseAdmin && courseMemberOfAuth" :to="`/Learn/Courses/${courseId}/my-progress`"
          role="tab"
          :aria-selected="activeTab === 9"
          class="flex-shrink-0 w-16 sm:w-24 md:w-28 text-center border-b-4 tab-item min-h-[52px] sm:min-h-[64px] hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-300"
          :class="{ 'border-cyan-500 bg-gradient-to-t from-cyan-50/50 dark:from-cyan-900/20 to-white dark:to-gray-800': activeTab === 9, 'border-transparent': activeTab !== 9 }">
          <div class="flex flex-col items-center justify-center py-1.5 sm:py-3 px-1 transition-all duration-300">
            <Icon icon="mdi:graph-box-plus-outline" class="w-5 h-5 sm:w-6 sm:h-6 md:w-7 md:h-7"
              :class="{ 'text-cyan-500 scale-110': activeTab === 9, 'text-slate-400 dark:text-gray-500': activeTab !== 9 }" />
            <span class="mt-1 text-[9px] sm:text-xs md:text-sm font-medium whitespace-nowrap leading-tight" 
              :class="{ 'text-cyan-600 dark:text-cyan-400 font-bold': activeTab === 9, 'text-slate-500 dark:text-gray-400': activeTab !== 9 }">ผลเรียน</span>
          </div>
        </NuxtLink>

        <!-- ผลการเรียน (Admin) -->
        <NuxtLink v-if="isCourseAdmin" :to="`/Learn/Courses/${courseId}/progress`"
          role="tab"
          :aria-selected="activeTab === 10"
          class="flex-shrink-0 w-16 sm:w-24 md:w-28 text-center border-b-4 tab-item min-h-[52px] sm:min-h-[64px] hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-300"
          :class="{ 'border-cyan-500 bg-gradient-to-t from-cyan-50/50 dark:from-cyan-900/20 to-white dark:to-gray-800': activeTab === 10, 'border-transparent': activeTab !== 10 }">
          <div class="flex flex-col items-center justify-center py-1.5 sm:py-3 px-1 transition-all duration-300">
            <Icon icon="mdi:graph-box-plus-outline" class="w-5 h-5 sm:w-6 sm:h-6 md:w-7 md:h-7"
              :class="{ 'text-cyan-500 scale-110': activeTab === 10, 'text-slate-400 dark:text-gray-500': activeTab !== 10 }" />
            <span class="mt-1 text-[9px] sm:text-xs md:text-sm font-medium whitespace-nowrap leading-tight" 
              :class="{ 'text-cyan-600 dark:text-cyan-400 font-bold': activeTab === 10, 'text-slate-500 dark:text-gray-400': activeTab !== 10 }">ผลเรียน</span>
          </div>
        </NuxtLink>

        <!-- Admin (ผู้ดูแล) -->
        <NuxtLink v-if="isCourseAdmin" :to="`/Learn/Courses/${courseId}/admin`"
          role="tab"
          :aria-selected="activeTab === 13"
          class="flex-shrink-0 w-16 sm:w-24 md:w-28 text-center border-b-4 tab-item min-h-[52px] sm:min-h-[64px] hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-300"
          :class="{ 'border-cyan-500 bg-gradient-to-t from-cyan-50/50 dark:from-cyan-900/20 to-white dark:to-gray-800': activeTab === 13, 'border-transparent': activeTab !== 13 }">
          <div class="flex flex-col items-center justify-center py-1.5 sm:py-3 px-1 transition-all duration-300">
            <Icon icon="eos-icons:admin-outlined" class="w-5 h-5 sm:w-6 sm:h-6 md:w-7 md:h-7"
              :class="{ 'text-cyan-500 scale-110': activeTab === 13, 'text-slate-400 dark:text-gray-500': activeTab !== 13 }" />
            <span class="mt-1 text-[9px] sm:text-xs md:text-sm font-medium whitespace-nowrap leading-tight" 
              :class="{ 'text-cyan-600 dark:text-cyan-400 font-bold': activeTab === 13, 'text-slate-500 dark:text-gray-400': activeTab !== 13 }">ผู้ดูแล</span>
          </div>
        </NuxtLink>

      </div>
    </div>
  </div>
</template>

<style scoped>
.tab-item {
  border-bottom-style: solid;
}

/* Custom Thin Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
  height: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(156, 163, 175, 0.3);
  border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(156, 163, 175, 0.5);
}

/* For Firefox */
.custom-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: rgba(156, 163, 175, 0.3) transparent;
}
</style>