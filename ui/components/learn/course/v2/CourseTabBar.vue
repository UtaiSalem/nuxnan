<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { computed, watch, onMounted, ref, nextTick } from 'vue'

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
    { id: 11, name: 'กระดาน', icon: 'codicon:feedback', href: `/Learn/Courses/${props.courseId}/feeds`, show: true },
    { id: 7, name: 'เข้าเรียน', icon: 'tabler:calendar-user', href: `/Learn/Courses/${props.courseId}/attendances`, show: props.isCourseAdmin || props.courseMemberOfAuth },
    { id: 1, name: 'บทเรียน', icon: 'icon-park-outline:view-grid-detail', href: `/Learn/Courses/${props.courseId}/lessons`, show: true },
    { id: 2, name: 'ภาระงาน', icon: 'material-symbols:assignment-add-outline', href: `/Learn/Courses/${props.courseId}/assignments`, show: true },
    { id: 3, name: 'ทดสอบ', icon: 'healthicons:i-exam-qualification-outline', href: `/Learn/Courses/${props.courseId}/quizzes`, show: props.courseMemberOfAuth || props.isCourseAdmin },
    { id: 14, name: 'บันทึกคะแนน', icon: 'mdi:clipboard-text-outline', href: `/Learn/Courses/${props.courseId}/external-scores`, show: props.isCourseAdmin },
    { id: 15, name: 'สมุดเกรด', icon: 'fluent:text-grammar-checkmark-24-filled', href: `/Learn/Courses/${props.courseId}/gradebook`, show: props.isCourseAdmin },
    { id: 5, name: 'กลุ่ม', icon: 'heroicons-outline:user-group', href: `/Learn/Courses/${props.courseId}/groups`, show: true },
    { id: 4, name: 'สมาชิก', icon: 'ph:users-four', href: `/Learn/Courses/${props.courseId}/members`, show: props.courseMemberOfAuth !== null },
    { id: 8, name: 'ตั้งค่า', icon: 'mdi-light:settings', href: `/Learn/Courses/${props.courseId}/settings`, show: props.isCourseAdmin },
    { id: 9, name: 'ผลเรียน', icon: 'mdi:graph-box-plus-outline', href: `/Learn/Courses/${props.courseId}/my-progress`, show: !props.isCourseAdmin && props.courseMemberOfAuth },
    { id: 10, name: 'ผลเรียน', icon: 'mdi:graph-box-plus-outline', href: `/Learn/Courses/${props.courseId}/progress`, show: props.isCourseAdmin },
    { id: 13, name: 'ผู้ดูแล', icon: 'eos-icons:admin-outlined', href: `/Learn/Courses/${props.courseId}/admin`, show: props.isCourseAdmin },
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
}

onMounted(() => {
  scrollActiveTabIntoView()
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
  <div class="w-full bg-white dark:bg-vikinger-dark-200 border-b border-gray-200 dark:border-vikinger-dark-50/30 shadow-sm overflow-hidden">
    <div 
      ref="scrollContainer"
      class="flex overflow-x-auto custom-scrollbar scroll-smooth p-1"
    >
      <div class="flex min-w-full">
        <NuxtLink
          v-for="tab in tabs"
          :key="tab.id"
          :to="tab.href"
          role="tab"
          :aria-selected="activeTab === tab.id"
          class="flex-shrink-0 flex items-center justify-center gap-2 min-w-[100px] sm:min-w-[120px] py-3.5 px-4 rounded-xl transition-all duration-300 relative group"
          :class="activeTab === tab.id 
            ? 'bg-vikinger-purple/10 text-vikinger-purple dark:bg-vikinger-cyan/10 dark:text-vikinger-cyan' 
            : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-vikinger-dark-100'"
        >
          <Icon 
            :icon="tab.icon" 
            class="w-5 h-5 transition-transform duration-300"
            :class="activeTab === tab.id ? 'scale-110' : 'group-hover:scale-110'" 
          />
          <span class="text-xs font-black whitespace-nowrap uppercase tracking-wider">
            {{ tab.name }}
          </span>
          
          <!-- Indicator for active tab -->
          <div 
            v-if="activeTab === tab.id"
            class="absolute bottom-1.5 left-4 right-4 h-0.5 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan rounded-full"
          ></div>
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  height: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
  background: #334155;
}
.custom-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: #e2e8f0 transparent;
}
.dark .custom-scrollbar {
  scrollbar-color: #334155 transparent;
}
</style>
