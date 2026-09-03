<script setup lang="ts">
import { Icon } from '@iconify/vue'
import CourseFeedsList from '~/components/learn/course/CourseFeedsList.vue'

definePageMeta({})

const injectedCourse = inject<Ref<any>>('course')
const injectedIsCourseAdmin = inject<Ref<boolean>>('isCourseAdmin')

const course = computed(() => injectedCourse?.value)
const isCourseAdmin = computed(() => injectedIsCourseAdmin?.value)

const showScrollButton = ref(false)

const scrollToTop = () => {
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const handleScroll = () => {
  showScrollButton.value = window.scrollY > 300
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll, { passive: true })
  handleScroll()
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})

useHead({
  title: computed(() => course.value?.name ? `ฟีด - ${course.value.name}` : 'ฟีดรายวิชา')
})
</script>

<template>
  <div class="space-y-6">
    <CourseFeedsList
      v-if="course?.id"
      :course-id="course.id"
      :is-course-admin="isCourseAdmin"
    />

    <div v-else class="bg-white dark:bg-vikinger-dark-200 rounded-xl px-0 py-8 sm:px-8 text-center border border-gray-100 dark:border-vikinger-dark-100 shadow-sm">
      <Icon icon="fluent:spinner-ios-20-regular" class="w-8 h-8 animate-spin mx-auto text-blue-500 mb-4" />
      <p class="text-gray-500 dark:text-gray-400">กำลังโหลดข้อมูลรายวิชา...</p>
    </div>

    <!-- Scroll to Top Button -->
    <transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="opacity-0 translate-y-10"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-300"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-10"
    >
      <button
        v-if="showScrollButton"
        @click="scrollToTop"
        class="fixed bottom-4 right-4 sm:bottom-8 sm:right-8 z-[999] p-3 min-h-[44px] min-w-[44px] flex items-center justify-center bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-full shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl hover:-translate-y-1 transition-all"
        title="เลื่อนขึ้นด้านบน"
        aria-label="เลื่อนขึ้นด้านบน"
      >
        <Icon icon="fluent:arrow-up-24-filled" class="w-6 h-6" />
      </button>
    </transition>
  </div>
</template>
