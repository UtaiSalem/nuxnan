<script setup>
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'
import MainLayout from '~/layouts/main.vue'
import { useAuthStore } from '~/stores/auth'

defineProps({
  coursePageTitle: String,
})

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const authUser = computed(() => authStore.user)
const isLoadingPage = ref(false)

const handleLoadingPage = (option) => {
  isLoadingPage.value = true
  switch (option) {
    case 1:
      router.push(`/Learn/Course/UserCourses`)
      break
    case 2:
      router.push(`/Learn/Course/AuthMemberCourses`)
      break
    case 3:
      router.push(`/Learn/Courses`)
      break
    case 4:
      router.push(`/Learn/Courses/create`)
      break
    default:
      isLoadingPage.value = false
  }
}

// Active state checks
const isMyCoursesActive = computed(() => route.path === '/Learn/Course/UserCourses' || route.path === '/learn/course/usercourses')
const isMemberCoursesActive = computed(() => route.path === '/Learn/Course/AuthMemberCourses' || route.path === '/learn/course/authmembercourses')
const isAllCoursesActive = computed(() => route.path === '/Learn/Courses' || route.path === '/learn/courses' || route.path === '/Learn/Courses/' || route.path === '/learn/courses/')
const isCreateCourseActive = computed(() => route.path === '/Learn/Courses/create' || route.path === '/learn/courses/create')

</script>

<template>
  <MainLayout :title="coursePageTitle">
    <template #coverProfileCard>
      <div
        v-if="isLoadingPage"
        class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm"
      >
        <div class="flex flex-col items-center gap-4">
          <Icon icon="svg-spinners:bars-rotate-fade" class="w-16 h-16 text-white" />
          <span class="text-white font-medium animate-pulse">กำลังเปลี่ยนหน้า...</span>
        </div>
      </div>

      <div
        class="hidden md:flex items-center max-w-7xl mx-auto mt-2 mb-4 shadow-lg bg-cover bg-no-repeat rounded-lg"
        :style="{ backgroundImage: 'url(/storage/images/banner/banner-bg.png)' }"
      >
        <img
          class="section-banner-icon"
          :src="'/storage/images/banner/forums-icon.png'"
          alt="forums-icon"
        />
        <p class="text-xl lg:text-4xl font-bold text-white">รวมรายวิชา</p>
      </div>

      <div class="w-full mx-auto mt-4 overflow-hidden bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-7xl">
        <div class="flex flex-row justify-around">
          <button
            type="button"
            @click="handleLoadingPage(1)"
            class="flex-row justify-center w-full text-center border-b-4 rounded-none hover:border-gray-400 transition-all duration-300"
            :class="{
              'border-b-4 border-cyan-500 bg-cyan-100/50 dark:bg-cyan-900/20': isMyCoursesActive,
              'border-transparent': !isMyCoursesActive
            }"
          >
            <div class="flex flex-col items-center justify-center py-2 text-slate-700 dark:text-gray-300">
              <Icon
                icon="lucide:layout-list"
                class="w-8 h-8"
                :class="{
                  'text-cyan-500 scale-110': isMyCoursesActive,
                  'text-gray-400': !isMyCoursesActive
                }"
              />
              <span
                :class="{
                  'text-cyan-500 font-bold': isMyCoursesActive,
                }"
                >รายวิชาของฉัน</span
              >
            </div>
          </button>

          <button
            type="button"
            @click="handleLoadingPage(2)"
            class="flex-row justify-center w-full text-center border-b-4 rounded-none hover:border-gray-400 transition-all duration-300"
            :class="{
              'border-b-4 border-cyan-500 bg-cyan-100/50 dark:bg-cyan-900/20': isMemberCoursesActive,
              'border-transparent': !isMemberCoursesActive
            }"
          >
            <div class="flex flex-col items-center justify-center py-2 text-slate-700 dark:text-gray-300">
              <Icon
                icon="lucide:layout-list"
                class="w-8 h-8"
                :class="{
                  'text-cyan-500 scale-110': isMemberCoursesActive,
                  'text-gray-400': !isMemberCoursesActive
                }"
              />
              <span
                :class="{
                  'text-cyan-500 font-bold': isMemberCoursesActive,
                }"
                >รายวิชาที่ฉันเป็นสมาชิก</span
              >
            </div>
          </button>

          <button
            type="button"
            @click="handleLoadingPage(3)"
            class="flex-row justify-center w-full text-center border-b-4 rounded-none hover:border-gray-400 transition-all duration-300"
            :class="{ 
              'border-b-4 border-cyan-500 bg-cyan-100/50 dark:bg-cyan-900/20': isAllCoursesActive,
              'border-transparent': !isAllCoursesActive
            }"
          >
            <div class="flex flex-col items-center justify-center py-2 text-slate-700 dark:text-gray-300">
              <Icon
                icon="lucide:layout-list"
                class="w-8 h-8"
                :class="{ 
                  'text-cyan-500 scale-110': isAllCoursesActive,
                  'text-gray-400': !isAllCoursesActive
                }"
              />
              <span :class="{ 'text-cyan-500 font-bold': isAllCoursesActive }">รวมรายวิชาทั้งหมด</span>
            </div>
          </button>

          <button
            type="button"
            @click="handleLoadingPage(4)"
            v-if="authUser && authUser.pp > 120000"
            class="flex-row justify-center w-full text-center border-b-4 rounded-none hover:border-gray-400 transition-all duration-300"
            :class="{ 
              'border-b-4 border-cyan-500 bg-cyan-100/50 dark:bg-cyan-900/20': isCreateCourseActive,
              'border-transparent': !isCreateCourseActive
            }"
          >
            <div class="flex flex-col items-center justify-center py-2 text-slate-700 dark:text-gray-300">
              <Icon
                icon="lucide:layout-list"
                class="w-8 h-8"
                :class="{ 
                  'text-cyan-500 scale-110': isCreateCourseActive,
                  'text-gray-400': !isCreateCourseActive
                }"
              />
              <span :class="{ 'text-cyan-500 font-bold': isCreateCourseActive }"
                >เพิ่มรายวิชาใหม่</span
              >
            </div>
          </button>
        </div>
      </div>
    </template>

    <template #leftSideWidget>
      <slot name="coursesLeftWidget"></slot>
    </template>

    <template #default>
      <slot name="coursesMainContent"></slot>
    </template>
  </MainLayout>
</template>

