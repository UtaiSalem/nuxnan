<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps({
  course: { type: Object, required: true },
  owner: { type: Object, required: true },
})

const api = useApi()
const config = useRuntimeConfig()

const ownerName = computed(() => props.owner?.name || props.owner?.username || 'ไม่ระบุผู้สอน')
const ownerAvatar = computed(() => props.owner?.avatar || props.owner?.profile_photo_url || '/images/default-avatar.png')
const ownerPath = computed(() => `/profile/${props.owner?.reference_code || props.owner?.id}`)

const lessons = computed(() => {
  const courseLessons = props.course?.lessons
  if (Array.isArray(courseLessons)) {
    return courseLessons
  }
  return []
})
</script>

<template>
  <div class="rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-vikinger-dark-100 dark:bg-vikinger-dark-200 overflow-hidden">
    <!-- Instructor Header -->
    <div class="p-4 border-b border-gray-100 dark:border-vikinger-dark-100">
      <h3 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
        <Icon icon="fluent:person-teacher-24-filled" class="w-5 h-5 text-indigo-500" />
        ผู้สอน
      </h3>
    </div>

    <!-- Instructor Info -->
    <div class="p-5">
      <div class="flex items-center gap-4 mb-5">
        <NuxtLink :to="ownerPath" class="shrink-0">
          <div class="h-16 w-16 overflow-hidden rounded-2xl border-2 border-vikinger-purple shadow-lg transition-transform hover:scale-110">
            <img :src="ownerAvatar" :alt="ownerName" class="h-full w-full object-cover" />
          </div>
        </NuxtLink>
        
        <div class="min-w-0 flex-1">
          <NuxtLink :to="ownerPath" class="block truncate text-lg font-black text-gray-900 hover:text-vikinger-purple dark:text-white dark:hover:text-vikinger-cyan">
            {{ ownerName }}
          </NuxtLink>
          <div class="flex items-center gap-1.5 mt-0.5">
            <span class="px-2 py-0.5 rounded-md bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-[10px] font-bold uppercase tracking-wider">
              Instructor
            </span>
          </div>
        </div>
      </div>

      <NuxtLink 
        :to="ownerPath" 
        class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-vikinger-dark-100 dark:text-gray-300 dark:hover:bg-vikinger-dark-50 transition-all font-bold text-sm"
      >
        <span>ดูโปรไฟล์ผู้สอน</span>
        <Icon icon="fluent:arrow-right-24-regular" class="w-4 h-4" />
      </NuxtLink>
    </div>

    <!-- Course Lessons Menu -->
    <div class="border-t border-gray-100 dark:border-vikinger-dark-100">
      <div class="px-4 py-3 bg-gray-50/50 dark:bg-vikinger-dark-100/30 flex items-center justify-between">
        <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">
          เมนูบทเรียนของรายวิชา
        </span>
        <span v-if="lessons.length > 0" class="text-[10px] font-bold text-indigo-500">
          {{ lessons.length }} บทเรียน
        </span>
      </div>
      
      <div v-if="lessons.length > 0" class="divide-y divide-gray-100 dark:divide-vikinger-dark-100 max-h-[400px] overflow-y-auto custom-scrollbar">
        <NuxtLink 
          v-for="(lesson, index) in lessons" 
          :key="lesson.id" 
          :to="`/Learn/Courses/${props.course.id}/lessons#lesson-${lesson.id}`"
          class="flex items-center gap-3 p-4 hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-colors group"
        >
          <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-vikinger-dark-100 flex items-center justify-center flex-shrink-0 group-hover:bg-indigo-500 group-hover:text-white transition-colors">
            <Icon v-if="lesson.auth_progress?.status === 'completed'" icon="fluent:checkmark-circle-24-filled" class="w-5 h-5 text-green-500 group-hover:text-white" />
            <span v-else class="text-xs font-bold">{{ index + 1 }}</span>
          </div>
          <div class="flex-1 min-w-0">
            <h4 class="text-xs font-bold text-gray-800 dark:text-white line-clamp-2 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
              {{ lesson.title }}
            </h4>
          </div>
          <Icon icon="fluent:chevron-right-20-regular" class="w-4 h-4 text-gray-300 group-hover:text-indigo-500 transition-colors" />
        </NuxtLink>
      </div>

      <div v-else class="p-8 text-center">
        <Icon icon="fluent:document-error-24-regular" class="w-8 h-8 text-gray-300 mx-auto mb-2" />
        <p class="text-[10px] text-gray-400 font-medium">ยังไม่มีบทเรียนในรายวิชานี้</p>
      </div>
    </div>
  </div>
</template>
