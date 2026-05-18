<script setup lang="ts">
import { Icon } from '@iconify/vue'

const props = defineProps<{
  courseId: string | number
  isAdmin?: boolean
}>()

const courseStore = useCourseStore()
const route = useRoute()

const lessons = computed(() => courseStore.lessons)
const isLoading = ref(false)

const currentLessonId = computed(() => {
  const lessonId = route.params.lessonId
  return lessonId ? Number(lessonId) : null
})

const fetchLessons = async () => {
  if (lessons.value.length === 0) {
    isLoading.value = true
    await courseStore.fetchLessons(props.courseId)
    isLoading.value = false
  }
}

onMounted(() => {
  fetchLessons()
})

const getLessonStatusIcon = (lesson: any) => {
  if (lesson.status === 'draft') return 'fluent:draft-24-regular'
  if (lesson.is_locked) return 'fluent:lock-closed-24-regular'
  return 'fluent:checkmark-circle-24-regular'
}

const getLessonStatusColor = (lesson: any) => {
  if (lesson.status === 'draft') return 'text-gray-400'
  if (lesson.is_locked) return 'text-amber-500'
  return 'text-emerald-500'
}
</script>

<template>
  <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-vikinger-dark-100 dark:bg-vikinger-dark-200">
    <div class="flex items-center justify-between mb-4">
      <h4 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider">บทเรียน</h4>
      <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-vikinger-purple/10 text-vikinger-purple">
        {{ lessons.length }} บท
      </span>
    </div>

    <div v-if="isLoading && !lessons.length" class="space-y-3 animate-pulse">
      <div v-for="i in 3" :key="i" class="h-10 bg-gray-100 dark:bg-vikinger-dark-100 rounded-xl"></div>
    </div>

    <div v-else-if="!lessons.length" class="text-center py-4">
      <p class="text-xs font-bold text-gray-500 dark:text-gray-400">ยังไม่มีบทเรียน</p>
    </div>

    <div v-else class="space-y-1">
      <NuxtLink
        v-for="lesson in lessons"
        :key="lesson.id"
        :to="`/Learn/Courses/${courseId}/lessons/${lesson.id}`"
        class="flex items-center gap-3 p-2.5 rounded-xl transition-all duration-200 group relative overflow-hidden"
        :class="[
          currentLessonId === lesson.id
            ? 'bg-gradient-vikinger text-white shadow-vikinger'
            : 'hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 text-gray-700 dark:text-gray-300'
        ]"
      >
        <!-- Status Indicator -->
        <div 
          class="shrink-0 w-8 h-8 rounded-lg flex items-center justify-center transition-colors"
          :class="[
            currentLessonId === lesson.id
              ? 'bg-white/20'
              : 'bg-gray-100 dark:bg-vikinger-dark-50/30'
          ]"
        >
          <Icon 
            :icon="getLessonStatusIcon(lesson)" 
            class="w-4 h-4"
            :class="currentLessonId === lesson.id ? 'text-white' : getLessonStatusColor(lesson)"
          />
        </div>

        <!-- Lesson Info -->
        <div class="flex-1 min-w-0">
          <div class="text-xs font-bold truncate">
            {{ lesson.title }}
          </div>
          <div 
            class="text-[10px] font-medium"
            :class="currentLessonId === lesson.id ? 'text-white/70' : 'text-gray-500'"
          >
            {{ lesson.topics_count || 0 }} หัวข้อ
          </div>
        </div>

        <!-- Active Arrow -->
        <Icon 
          v-if="currentLessonId === lesson.id"
          icon="fluent:chevron-right-24-filled" 
          class="w-4 h-4 text-white animate-bounce-x"
        />
      </NuxtLink>
    </div>

    <!-- View All Link -->
    <NuxtLink 
      :to="`/Learn/Courses/${courseId}/lessons`"
      class="mt-4 flex items-center justify-center gap-2 py-2 text-[10px] font-black text-vikinger-purple dark:text-vikinger-cyan hover:underline"
    >
      ดูบทเรียนทั้งหมด
      <Icon icon="fluent:arrow-right-24-regular" class="w-3 h-3" />
    </NuxtLink>
  </div>
</template>

<style scoped>
@keyframes bounce-x {
  0%, 100% { transform: translateX(0); }
  50% { transform: translateX(3px); }
}
.animate-bounce-x {
  animation: bounce-x 1s infinite;
}
</style>
