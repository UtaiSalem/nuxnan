<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { computed, watch, onMounted, onUnmounted, ref, nextTick } from 'vue'

interface Props {
  courseId: string | number
  courseName?: string
  isCourseAdmin?: boolean
  courseMemberOfAuth?: any
}

const props = withDefaults(defineProps<Props>(), {
  courseName: '',
  isCourseAdmin: false,
  courseMemberOfAuth: null
})

const route = useRoute()
const api = useApi()
const courseMemberStore = useCourseMemberStore()
const scrollContainer = ref<HTMLElement | null>(null)

const canScrollLeft = ref(false)
const canScrollRight = ref(false)

const updateScrollState = () => {
  if (!scrollContainer.value) return
  const { scrollLeft, scrollWidth, clientWidth } = scrollContainer.value
  canScrollLeft.value = scrollLeft > 2
  canScrollRight.value = scrollLeft < scrollWidth - clientWidth - 2
}

const scrollBy = (dir: 'left' | 'right') => {
  scrollContainer.value?.scrollBy({
    left: dir === 'left' ? -160 : 160,
    behavior: 'smooth'
  })
}

/**
 * Determine active tab based on current route
 */
const activeTab = computed(() => {
  const path = route.path
  if (path.includes('/basic-info')) return 12
  if (path.includes('/feeds')) return 11
  if (path.includes('/support')) return 16
  if (path.includes('/attendances')) return 7
  if (path.includes('/lessons')) return 1
  if (path.includes('/assignments')) return 2
  if (path.includes('/quizzes')) return 3
  if (path.includes('/groups')) return 5
  if (path.includes('/members')) return 4
  if (path.includes('/settings')) return 8
  if (path.includes('/my-progress')) return 9
  if (path.includes('/progress')) return 10
  if (path.includes('/external-scores')) return 14
  if (path.includes('/gradebook')) return 15
  if (path.includes('/admin')) return 13
  // Default to info tab for base course page
  if (path.endsWith(`/Learn/Courses/${props.courseId}`) || path.endsWith(`/Learn/Courses/${props.courseId}/`)) return 12
  return 12
})

const tabs = computed(() => {
  const list = [
    { id: 12, name: 'ข้อมูล', icon: 'heroicons:information-circle', href: `/Learn/Courses/${props.courseId}`, show: true },
    { id: 8, name: 'ตั้งค่า', icon: 'mdi-light:settings', href: `/Learn/Courses/${props.courseId}/settings`, show: props.isCourseAdmin },
    { id: 11, name: 'กระดาน', icon: 'codicon:feedback', href: `/Learn/Courses/${props.courseId}/feeds`, show: true },
    { id: 7, name: 'เข้าเรียน', icon: 'tabler:calendar-user', href: `/Learn/Courses/${props.courseId}/attendances`, show: props.isCourseAdmin || !!props.courseMemberOfAuth },
    { id: 1, name: 'บทเรียน', icon: 'icon-park-outline:view-grid-detail', href: `/Learn/Courses/${props.courseId}/lessons`, show: true },
    { id: 2, name: 'ภาระงาน', icon: 'material-symbols:assignment-add-outline', href: `/Learn/Courses/${props.courseId}/assignments`, show: true },
    { id: 3, name: 'ทดสอบ', icon: 'healthicons:i-exam-qualification-outline', href: `/Learn/Courses/${props.courseId}/quizzes`, show: !!props.courseMemberOfAuth || props.isCourseAdmin },
    { id: 4, name: 'สมาชิก', icon: 'ph:users-four-bold', href: `/Learn/Courses/${props.courseId}/members`, show: props.isCourseAdmin || !!props.courseMemberOfAuth },
    { id: 13, name: 'ผู้ดูแล', icon: 'eos-icons:admin-outlined', href: `/Learn/Courses/${props.courseId}/admin`, show: props.isCourseAdmin },
    { id: 5, name: 'กลุ่ม', icon: 'heroicons-outline:user-group', href: `/Learn/Courses/${props.courseId}/groups`, show: true },
    { id: 9, name: 'ผลเรียน', icon: 'mdi:graph-box-plus-outline', href: `/Learn/Courses/${props.courseId}/my-progress`, show: !props.isCourseAdmin && !!props.courseMemberOfAuth },
    { id: 10, name: 'ผลเรียน', icon: 'mdi:graph-box-plus-outline', href: `/Learn/Courses/${props.courseId}/progress`, show: props.isCourseAdmin },
    { id: 14, name: 'คะแนนภายนอก', icon: 'mdi:clipboard-text-outline', href: `/Learn/Courses/${props.courseId}/external-scores`, show: props.isCourseAdmin },
    { id: 15, name: 'สรุปคะแนน', icon: 'fluent:text-grammar-checkmark-24-filled', href: `/Learn/Courses/${props.courseId}/gradebook`, show: props.isCourseAdmin },
    { id: 16, name: 'รายได้', icon: 'mdi:cash-multiple', href: `/Learn/Courses/${props.courseId}/support`, show: true },
  ]
  return list.filter(t => t.show)
})

/**
 * Scroll the active tab into view
 */
const scrollActiveTabIntoView = async () => {
  await nextTick()
  if (!scrollContainer.value) return
  
  const activeElement = scrollContainer.value.querySelector('[aria-selected="true"]') as HTMLElement
  if (activeElement) {
    const containerWidth = scrollContainer.value.offsetWidth
    const elementOffset = activeElement.offsetLeft
    const elementWidth = activeElement.offsetWidth
    
    scrollContainer.value.scrollTo({
      left: elementOffset - (containerWidth / 2) + (elementWidth / 2),
      behavior: 'smooth'
    })
  }
  updateScrollState()
}

onMounted(() => {
  scrollActiveTabIntoView()
  scrollContainer.value?.addEventListener('scroll', updateScrollState, { passive: true })
  window.addEventListener('resize', updateScrollState, { passive: true })
  updateScrollState()
})

onUnmounted(() => {
  scrollContainer.value?.removeEventListener('scroll', updateScrollState)
  window.removeEventListener('resize', updateScrollState)
})

watch(() => route.path, () => {
  scrollActiveTabIntoView()
})

let isSavingTab = false
let saveTabTimeout: any = null

watch(activeTab, (newTab, oldTab) => {
  if (!props.courseMemberOfAuth?.id || newTab === oldTab || isSavingTab) return
  
  // Debounce the API call
  if (saveTabTimeout) clearTimeout(saveTabTimeout)
  
  saveTabTimeout = setTimeout(async () => {
    isSavingTab = true
    try {
      await api.post(`/api/courses/${props.courseId}/members/${props.courseMemberOfAuth.id}/set-active-tab`, {
        tab: newTab
      })
      if (courseMemberStore.member) {
        courseMemberStore.member.last_accessed_tab = newTab
      }
    } catch (error) {
      console.error('Error saving last accessed tab:', error)
    } finally {
      isSavingTab = false
    }
  }, 1000) // 1 second debounce
}, { immediate: false })
</script>

<template>
  <div class="w-full py-2">
    <div
      role="tablist"
      aria-label="Course navigation"
      class="flex items-stretch h-[72px] sm:h-[80px] rounded-2xl bg-white dark:bg-vikinger-dark-200 border-2 border-gray-100 dark:border-vikinger-dark-50/20 shadow-lg backdrop-blur-xl overflow-hidden"
    >
      <!-- ปุ่มซ้าย -->
      <button
        @click="scrollBy('left')"
        :disabled="!canScrollLeft"
        aria-label="เลื่อนซ้าย"
        class="flex-shrink-0 flex items-center justify-center w-12 border-r border-gray-200/60 dark:border-vikinger-dark-50/30 transition-all duration-200"
        :class="
          canScrollLeft
            ? 'text-slate-600 hover:text-vikinger-cyan dark:text-slate-400 dark:hover:text-vikinger-cyan bg-gray-50/50 dark:bg-vikinger-dark-100/30'
            : 'opacity-10 text-slate-300 dark:text-slate-800 cursor-default'
        "
      >
        <Icon icon="heroicons:chevron-left" class="w-7 h-7" />
      </button>

      <!-- Scroll area -->
      <div
        ref="scrollContainer"
        class="relative flex flex-1 min-w-0 overflow-x-auto tab-scroll scroll-smooth pb-1"
      >
        <NuxtLink
          v-for="(tab, index) in tabs"
          :key="tab.id"
          :to="tab.href"
          role="tab"
          :aria-selected="activeTab === tab.id"
          :title="tab.name"
          :aria-label="tab.name"
            class="relative flex-shrink-0 flex-1 min-w-[82px] sm:min-w-[92px] flex flex-col items-center justify-center gap-1.5 sm:gap-2 transition-all duration-300 group px-2.5 sm:px-3 overflow-hidden"
          :class="[
            index > 0
              ? 'border-l border-gray-200/50 dark:border-vikinger-dark-50/20'
              : '',
            activeTab === tab.id
              ? 'text-vikinger-cyan bg-vikinger-cyan/15 dark:bg-vikinger-cyan/20'
              : 'text-slate-400 dark:text-slate-600 hover:text-slate-700 dark:hover:text-slate-300 hover:bg-slate-50 dark:hover:bg-vikinger-dark-100/40',
          ]"
        >
          <Icon
            :icon="tab.icon"
            class="w-6 h-6 sm:w-[26px] sm:h-[26px] transition-all duration-300"
            :class="activeTab === tab.id ? 'scale-110 drop-shadow-[0_0_8px_rgba(35,210,181,0.5)]' : 'group-hover:scale-110'"
          />
          <span
            class="text-[10px] xs:text-[11.5px] font-black whitespace-nowrap uppercase tracking-wider transition-all duration-300"
            :class="activeTab === tab.id ? 'scale-105' : ''"
          >
            {{ tab.name }}
          </span>

          <!-- Indicator -->
          <div
            v-if="activeTab === tab.id"
            class="absolute bottom-1 left-0 right-0 h-1.5 bg-vikinger-cyan shadow-[0_-2px_6px_rgba(35,210,181,0.4)]"
          />
        </NuxtLink>
      </div>

      <div
        v-if="canScrollLeft"
        aria-hidden="true"
        class="pointer-events-none absolute left-12 sm:left-14 top-0 bottom-0 w-6 bg-gradient-to-r from-white dark:from-vikinger-dark-200 to-transparent"
      />
      <div
        v-if="canScrollRight"
        aria-hidden="true"
        class="pointer-events-none absolute right-12 sm:right-14 top-0 bottom-0 w-8 bg-gradient-to-l from-white dark:from-vikinger-dark-200 to-transparent"
      />

      <!-- ปุ่มขวา -->
      <button
        @click="scrollBy('right')"
        :disabled="!canScrollRight"
        aria-label="เลื่อนขวา"
        class="flex-shrink-0 flex items-center justify-center w-12 border-l border-gray-200/60 dark:border-vikinger-dark-50/30 transition-all duration-200"
        :class="
          canScrollRight
            ? 'text-slate-600 hover:text-vikinger-cyan dark:text-slate-400 dark:hover:text-vikinger-cyan bg-gray-50/50 dark:bg-vikinger-dark-100/30'
            : 'opacity-10 text-slate-300 dark:text-slate-800 cursor-default'
        "
      >
        <Icon icon="heroicons:chevron-right" class="w-7 h-7" />
      </button>
    </div>
  </div>
</template>

<style scoped>
.tab-scroll::-webkit-scrollbar {
  height: 4px;
}
.tab-scroll::-webkit-scrollbar-track {
  background: transparent;
}
.tab-scroll::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 10px;
}
.dark .tab-scroll::-webkit-scrollbar-thumb {
  background: #334155;
}
.tab-scroll:hover::-webkit-scrollbar-thumb {
  background: #23d2b5;
}
.tab-scroll {
  scrollbar-width: thin;
  scrollbar-color: #e2e8f0 transparent;
  -webkit-overflow-scrolling: touch;
  overscroll-behavior-x: contain;
}
.dark .tab-scroll {
  scrollbar-color: #334155 transparent;
}
</style>
