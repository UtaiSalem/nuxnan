<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'

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

// Refs สำหรับควบคุม scroll และแสดงผล
const tabContainer = ref<HTMLElement | null>(null)
const showLeftFade = ref(false)
const showRightFade = ref(false)

// Determine active tab based on current route
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

// Scroll active tab เข้ามาเห็นชัด
const scrollActiveTabIntoView = async () => {
  await nextTick()
  if (!tabContainer.value) return
  
  const activeEl = tabContainer.value.querySelector<HTMLElement>(
    `[data-tab-id="${activeTab.value}"]`
  )
  if (activeEl) {
    activeEl.scrollIntoView({
      behavior: 'smooth',
      block: 'nearest',
      inline: 'center'
    })
  }
}

// อัปเดตเงา fade ซ้าย-ขวา
const updateFadeIndicators = () => {
  if (!tabContainer.value) return
  const el = tabContainer.value
  showLeftFade.value = el.scrollLeft > 8
  showRightFade.value = el.scrollLeft < (el.scrollWidth - el.clientWidth - 8)
}

// ควบคุมการนำทางด้วยคีย์บอร์ด (ลูกศร ซ้าย-ขวา)
const handleKeyNavigation = (e: KeyboardEvent) => {
  if (!tabContainer.value?.contains(e.target as Node)) return
  
  const tabs = Array.from(tabContainer.value.querySelectorAll<HTMLElement>('[role="tab"]'))
  const currentIndex = tabs.findIndex(t => t === document.activeElement)
  if (currentIndex === -1) return
  
  let nextIndex = currentIndex
  if (e.key === 'ArrowRight') nextIndex = Math.min(currentIndex + 1, tabs.length - 1)
  if (e.key === 'ArrowLeft') nextIndex = Math.max(currentIndex - 1, 0)
  
  if (nextIndex !== currentIndex) {
    e.preventDefault()
    tabs[nextIndex].focus()
    tabs[nextIndex].click()
  }
}

// Save last accessed tab when tab changes
let isSavingTab = false
watch(activeTab, async (newTab, oldTab) => {
  scrollActiveTabIntoView()
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

onMounted(() => {
  scrollActiveTabIntoView()
  updateFadeIndicators()
  tabContainer.value?.addEventListener('scroll', updateFadeIndicators, { passive: true })
  window.addEventListener('resize', updateFadeIndicators)
  document.addEventListener('keydown', handleKeyNavigation)
})

onUnmounted(() => {
  tabContainer.value?.removeEventListener('scroll', updateFadeIndicators)
  window.removeEventListener('resize', updateFadeIndicators)
  document.removeEventListener('keydown', handleKeyNavigation)
})
</script>

<template>
  <div class="w-full mt-4 overflow-hidden bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-100 dark:border-gray-700">
    <div class="relative">
      <!-- Left fade -->
      <div 
        v-show="showLeftFade"
        class="pointer-events-none absolute left-0 top-0 bottom-0 w-8 z-10 bg-gradient-to-r from-white dark:from-gray-800 to-transparent transition-opacity"
      ></div>
      
      <!-- Right fade -->
      <div 
        v-show="showRightFade"
        class="pointer-events-none absolute right-0 top-0 bottom-0 w-8 z-10 bg-gradient-to-l from-white dark:from-gray-800 to-transparent transition-opacity"
      ></div>

      <div ref="tabContainer" 
           role="tablist"
           aria-label="เมนูรายวิชา"
           class="flex flex-nowrap overflow-x-auto scrollbar-hide relative scroll-smooth">
        
        <!-- ข้อมูลทั่วไป -->
        <NuxtLink :to="`/Learn/Courses/${courseId}`"
          role="tab"
          :aria-selected="activeTab === 12"
          data-tab-id="12"
          class="flex-shrink-0 min-w-[5rem] sm:min-w-0 sm:flex-1 text-center border-b-4 tab-item min-h-[60px] hover:border-gray-400 transition-all duration-300 ease-in-out"
          :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm': activeTab === 12, 'hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent': activeTab !== 12 }">
          <div class="flex flex-col items-center justify-center py-3 sm:py-3 px-2 text-slate-600/80 dark:text-gray-300 transition-all duration-300">
            <Icon icon="heroicons:information-circle" class="w-5 h-5 sm:w-6 sm:h-6 md:w-8 md:h-8 transition-all duration-300"
              :class="{ 'text-cyan-500 scale-110': activeTab === 12, 'hover:text-cyan-400': activeTab !== 12 }" />
            <span class="mt-1 text-[11px] sm:text-xs md:text-sm font-medium transition-all duration-300 whitespace-nowrap leading-tight" :class="{ 'text-cyan-500 font-semibold': activeTab === 12 }">ข้อมูล</span>
          </div>
        </NuxtLink>

        <!-- กระดาน -->
        <NuxtLink :to="`/Learn/Courses/${courseId}/feeds`"
          role="tab"
          :aria-selected="activeTab === 11"
          data-tab-id="11"
          class="flex-shrink-0 min-w-[5rem] sm:min-w-0 sm:flex-1 text-center border-b-4 tab-item min-h-[60px] hover:border-gray-400 transition-all duration-300 ease-in-out"
          :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm': activeTab === 11, 'hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent': activeTab !== 11 }">
          <div class="flex flex-col items-center justify-center py-3 sm:py-3 px-2 text-slate-600/80 dark:text-gray-300 transition-all duration-300">
            <Icon icon="codicon:feedback" class="w-5 h-5 sm:w-6 sm:h-6 md:w-8 md:h-8 transition-all duration-300"
              :class="{ 'text-cyan-500 scale-110': activeTab === 11, 'hover:text-cyan-400': activeTab !== 11 }" />
            <span class="mt-1 text-[11px] sm:text-xs md:text-sm font-medium transition-all duration-300 whitespace-nowrap leading-tight" :class="{ 'text-cyan-500 font-semibold': activeTab === 11 }">กระดาน</span>
          </div>
        </NuxtLink>

        <!-- การเข้าเรียน -->
        <NuxtLink v-if="isCourseAdmin || courseMemberOfAuth" :to="`/Learn/Courses/${courseId}/attendances`"
          role="tab"
          :aria-selected="activeTab === 7"
          data-tab-id="7"
          class="flex-shrink-0 min-w-[5rem] sm:min-w-0 sm:flex-1 text-center border-b-4 tab-item min-h-[60px] hover:border-gray-400 transition-all duration-300 ease-in-out"
          :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm': activeTab === 7, 'hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent': activeTab !== 7 }">
          <div class="flex flex-col items-center justify-center py-3 sm:py-3 px-2 text-slate-600/80 dark:text-gray-300 transition-all duration-300">
            <Icon icon="tabler:calendar-user" class="w-5 h-5 sm:w-6 sm:h-6 md:w-8 md:h-8 transition-all duration-300"
              :class="{ 'text-cyan-500 scale-110': activeTab === 7, 'hover:text-cyan-400': activeTab !== 7 }" />
            <span class="mt-1 text-[11px] sm:text-xs md:text-sm font-medium transition-all duration-300 whitespace-nowrap leading-tight" :class="{ 'text-cyan-500 font-semibold': activeTab === 7 }">เข้าเรียน</span>
          </div>
        </NuxtLink>

        <!-- บทเรียน -->
        <NuxtLink :to="`/Learn/Courses/${courseId}/lessons`"
          role="tab"
          :aria-selected="activeTab === 1"
          data-tab-id="1"
          class="flex-shrink-0 min-w-[5rem] sm:min-w-0 sm:flex-1 text-center border-b-4 tab-item min-h-[60px] hover:border-gray-400 transition-all duration-300 ease-in-out"
          :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm': activeTab === 1, 'hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent': activeTab !== 1 }">
          <div class="flex flex-col items-center justify-center py-3 sm:py-3 px-2 text-slate-600/80 dark:text-gray-300 transition-all duration-300">
            <Icon icon="icon-park-outline:view-grid-detail" class="w-5 h-5 sm:w-6 sm:h-6 md:w-8 md:h-8 transition-all duration-300"
              :class="{ 'text-cyan-500 scale-110': activeTab === 1, 'hover:text-cyan-400': activeTab !== 1 }" />
            <span class="mt-1 text-[11px] sm:text-xs md:text-sm font-medium transition-all duration-300 whitespace-nowrap leading-tight" :class="{ 'text-cyan-500 font-semibold': activeTab === 1 }">บทเรียน</span>
          </div>
        </NuxtLink>

        <!-- ภาระงาน -->
        <NuxtLink :to="`/Learn/Courses/${courseId}/assignments`"
          role="tab"
          :aria-selected="activeTab === 2"
          data-tab-id="2"
          class="flex-shrink-0 min-w-[5rem] sm:min-w-0 sm:flex-1 text-center border-b-4 tab-item min-h-[60px] hover:border-gray-400 transition-all duration-300 ease-in-out"
          :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm': activeTab === 2, 'hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent': activeTab !== 2 }">
          <div class="flex flex-col items-center justify-center py-3 sm:py-3 px-2 text-slate-600/80 dark:text-gray-300 transition-all duration-300">
            <Icon icon="material-symbols:assignment-add-outline" class="w-5 h-5 sm:w-6 sm:h-6 md:w-8 md:h-8 transition-all duration-300"
              :class="{ 'text-cyan-500 scale-110': activeTab === 2, 'hover:text-cyan-400': activeTab !== 2 }" />
            <span class="mt-1 text-[11px] sm:text-xs md:text-sm font-medium transition-all duration-300 whitespace-nowrap leading-tight" :class="{ 'text-cyan-500 font-semibold': activeTab === 2 }">ภาระงาน</span>
          </div>
        </NuxtLink>

        <!-- ทดสอบ -->
        <NuxtLink v-if="courseMemberOfAuth || isCourseAdmin" :to="`/Learn/Courses/${courseId}/quizzes`"
          role="tab"
          :aria-selected="activeTab === 3"
          data-tab-id="3"
          class="flex-shrink-0 min-w-[5rem] sm:min-w-0 sm:flex-1 text-center border-b-4 tab-item min-h-[60px] hover:border-gray-400 transition-all duration-300 ease-in-out"
          :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm': activeTab === 3, 'hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent': activeTab !== 3 }">
          <div class="flex flex-col items-center justify-center py-3 sm:py-3 px-2 text-slate-600/80 dark:text-gray-300 transition-all duration-300">
            <Icon icon="healthicons:i-exam-qualification-outline" class="w-5 h-5 sm:w-6 sm:h-6 md:w-8 md:h-8 transition-all duration-300"
              :class="{ 'text-cyan-500 scale-110': activeTab === 3, 'hover:text-cyan-400': activeTab !== 3 }" />
            <span class="mt-1 text-[11px] sm:text-xs md:text-sm font-medium transition-all duration-300 whitespace-nowrap leading-tight" :class="{ 'text-cyan-500 font-semibold': activeTab === 3 }">ทดสอบ</span>
          </div>
        </NuxtLink>

        <!-- บันทึกคะแนน (Admin) -->
        <NuxtLink v-if="isCourseAdmin" :to="`/Learn/Courses/${courseId}/external-scores`"
          role="tab"
          :aria-selected="activeTab === 14"
          data-tab-id="14"
          class="flex-shrink-0 min-w-[5rem] sm:min-w-0 sm:flex-1 text-center border-b-4 tab-item min-h-[60px] hover:border-gray-400 transition-all duration-300 ease-in-out"
          :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm': activeTab === 14, 'hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent': activeTab !== 14 }">
          <div class="flex flex-col items-center justify-center py-3 sm:py-3 px-2 text-slate-600/80 dark:text-gray-300 transition-all duration-300">
            <Icon icon="mdi:clipboard-text-outline" class="w-5 h-5 sm:w-6 sm:h-6 md:w-8 md:h-8 transition-all duration-300"
              :class="{ 'text-cyan-500 scale-110': activeTab === 14, 'hover:text-cyan-400': activeTab !== 14 }" />
            <span class="mt-1 text-[11px] sm:text-xs md:text-sm font-medium transition-all duration-300 whitespace-nowrap leading-tight" :class="{ 'text-cyan-500 font-semibold': activeTab === 14 }">บันทึกคะแนน</span>
          </div>
        </NuxtLink>

        <!-- กลุ่ม -->
        <NuxtLink :to="`/Learn/Courses/${courseId}/groups`"
          role="tab"
          :aria-selected="activeTab === 5"
          data-tab-id="5"
          class="flex-shrink-0 min-w-[5rem] sm:min-w-0 sm:flex-1 text-center border-b-4 tab-item min-h-[60px] hover:border-gray-400 transition-all duration-300 ease-in-out"
          :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm': activeTab === 5, 'hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent': activeTab !== 5 }">
          <div class="flex flex-col items-center justify-center py-3 sm:py-3 px-2 text-slate-600/80 dark:text-gray-300 transition-all duration-300">
            <Icon icon="heroicons-outline:user-group" class="w-5 h-5 sm:w-6 sm:h-6 md:w-8 md:h-8 transition-all duration-300"
              :class="{ 'text-cyan-500 scale-110': activeTab === 5, 'hover:text-cyan-400': activeTab !== 5 }" />
            <span class="mt-1 text-[11px] sm:text-xs md:text-sm font-medium transition-all duration-300 whitespace-nowrap leading-tight" :class="{ 'text-cyan-500 font-semibold': activeTab === 5 }">กลุ่ม</span>
          </div>
        </NuxtLink>

        <!-- สมาชิก -->
        <NuxtLink v-if="courseMemberOfAuth !== null" :to="`/Learn/Courses/${courseId}/members`"
          role="tab"
          :aria-selected="activeTab === 4"
          data-tab-id="4"
          class="flex-shrink-0 min-w-[5rem] sm:min-w-0 sm:flex-1 text-center border-b-4 tab-item min-h-[60px] hover:border-gray-400 transition-all duration-300 ease-in-out"
          :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm': activeTab === 4, 'hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent': activeTab !== 4 }">
          <div class="flex flex-col items-center justify-center py-3 sm:py-3 px-2 text-slate-600/80 dark:text-gray-300 transition-all duration-300">
            <Icon icon="ph:users-four" class="w-5 h-5 sm:w-6 sm:h-6 md:w-8 md:h-8 transition-all duration-300"
              :class="{ 'text-cyan-500 scale-110': activeTab === 4, 'hover:text-cyan-400': activeTab !== 4 }" />
            <span class="mt-1 text-[11px] sm:text-xs md:text-sm font-medium transition-all duration-300 whitespace-nowrap leading-tight" :class="{ 'text-cyan-500 font-semibold': activeTab === 4 }">สมาชิก</span>
          </div>
        </NuxtLink>

        <!-- ตั้งค่า (Admin) -->
        <NuxtLink v-if="isCourseAdmin" :to="`/Learn/Courses/${courseId}/settings`"
          role="tab"
          :aria-selected="activeTab === 8"
          data-tab-id="8"
          class="flex-shrink-0 min-w-[5rem] sm:min-w-0 sm:flex-1 text-center border-b-4 tab-item min-h-[60px] hover:border-gray-400 transition-all duration-300 ease-in-out"
          :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm': activeTab === 8, 'hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent': activeTab !== 8 }">
          <div class="flex flex-col items-center justify-center py-3 sm:py-3 px-2 text-slate-600/80 dark:text-gray-300 transition-all duration-300">
            <Icon icon="mdi-light:settings" class="w-5 h-5 sm:w-6 sm:h-6 md:w-8 md:h-8 transition-all duration-300"
              :class="{ 'text-cyan-500 scale-110': activeTab === 8, 'hover:text-cyan-400': activeTab !== 8 }" />
            <span class="mt-1 text-[11px] sm:text-xs md:text-sm font-medium transition-all duration-300 whitespace-nowrap leading-tight" :class="{ 'text-cyan-500 font-semibold': activeTab === 8 }">ตั้งค่า</span>
          </div>
        </NuxtLink>

        <!-- ผลการเรียน (Member) -->
        <NuxtLink v-if="!isCourseAdmin && courseMemberOfAuth" :to="`/Learn/Courses/${courseId}/my-progress`"
          role="tab"
          :aria-selected="activeTab === 9"
          data-tab-id="9"
          class="flex-shrink-0 min-w-[5rem] sm:min-w-0 sm:flex-1 text-center border-b-4 tab-item min-h-[60px] hover:border-gray-400 transition-all duration-300 ease-in-out"
          :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm': activeTab === 9, 'hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent': activeTab !== 9 }">
          <div class="flex flex-col items-center justify-center py-3 sm:py-3 px-2 text-slate-600/80 dark:text-gray-300 transition-all duration-300">
            <Icon icon="mdi:graph-box-plus-outline" class="w-5 h-5 sm:w-6 sm:h-6 md:w-8 md:h-8 transition-all duration-300"
              :class="{ 'text-cyan-500 scale-110': activeTab === 9, 'hover:text-cyan-400': activeTab !== 9 }" />
            <span class="mt-1 text-[11px] sm:text-xs md:text-sm font-medium transition-all duration-300 whitespace-nowrap leading-tight" :class="{ 'text-cyan-500 font-semibold': activeTab === 9 }">ผลเรียน</span>
          </div>
        </NuxtLink>

        <!-- ผลการเรียน (Admin) -->
        <NuxtLink v-if="isCourseAdmin" :to="`/Learn/Courses/${courseId}/progress`"
          role="tab"
          :aria-selected="activeTab === 10"
          data-tab-id="10"
          class="flex-shrink-0 min-w-[5rem] sm:min-w-0 sm:flex-1 text-center border-b-4 tab-item min-h-[60px] hover:border-gray-400 transition-all duration-300 ease-in-out"
          :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm': activeTab === 10, 'hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent': activeTab !== 10 }">
          <div class="flex flex-col items-center justify-center py-3 sm:py-3 px-2 text-slate-600/80 dark:text-gray-300 transition-all duration-300">
            <Icon icon="mdi:graph-box-plus-outline" class="w-5 h-5 sm:w-6 sm:h-6 md:w-8 md:h-8 transition-all duration-300"
              :class="{ 'text-cyan-500 scale-110': activeTab === 10, 'hover:text-cyan-400': activeTab !== 10 }" />
            <span class="mt-1 text-[11px] sm:text-xs md:text-sm font-medium transition-all duration-300 whitespace-nowrap leading-tight" :class="{ 'text-cyan-500 font-semibold': activeTab === 10 }">ผลเรียน</span>
          </div>
        </NuxtLink>

        <!-- Admin (ผู้ดูแล) -->
        <NuxtLink v-if="isCourseAdmin" :to="`/Learn/Courses/${courseId}/admin`"
          role="tab"
          :aria-selected="activeTab === 13"
          data-tab-id="13"
          class="flex-shrink-0 min-w-[5rem] sm:min-w-0 sm:flex-1 text-center border-b-4 tab-item min-h-[60px] hover:border-gray-400 transition-all duration-300 ease-in-out"
          :class="{ 'border-b-4 border-cyan-500 bg-gradient-to-t from-cyan-50 dark:from-cyan-900/20 to-white dark:to-gray-800 shadow-sm': activeTab === 13, 'hover:bg-gray-50 dark:hover:bg-gray-700/50 border-transparent': activeTab !== 13 }">
          <div class="flex flex-col items-center justify-center py-3 sm:py-3 px-2 text-slate-600/80 dark:text-gray-300 transition-all duration-300">
            <Icon icon="eos-icons:admin-outlined" class="w-5 h-5 sm:w-6 sm:h-6 md:w-8 md:h-8 transition-all duration-300"
              :class="{ 'text-cyan-500 scale-110': activeTab === 13, 'hover:text-cyan-400': activeTab !== 13 }" />
            <span class="mt-1 text-[11px] sm:text-xs md:text-sm font-medium transition-all duration-300 whitespace-nowrap leading-tight" :class="{ 'text-cyan-500 font-semibold': activeTab === 13 }">ผู้ดูแล</span>
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
</style>

