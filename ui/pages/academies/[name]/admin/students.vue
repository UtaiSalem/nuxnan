<script setup lang="ts">
/**
 * Academy Admin - Students
 * หน้ารายการนักเรียน (redirect ไปยัง members และ filter เฉพาะนักเรียน)
 */
definePageMeta({
  layout: false
})

const route = useRoute()
const academyName = computed(() => route.params.name as string)

// On mount, redirect to members page with student filter pre-applied
onMounted(() => {
  // Store the filter preference to show only students
  const studentFilter = useCookie('academy-members-student-filter', {
    default: () => 'student',
    maxAge: 60 * 60 * 24 // 1 day
  })
  studentFilter.value = 'student'

  // Redirect to members page
  navigateTo(`/academies/${academyName.value}/admin/members`)
})
</script>

<template>
  <div class="flex items-center justify-center min-h-screen">
    <div class="text-center">
      <div class="animate-spin rounded-full h-12 w-12 border-4 border-primary-500 border-t-transparent mx-auto mb-4"></div>
      <p class="text-gray-600 dark:text-gray-400">กำลังโหลดรายการนักเรียน...</p>
    </div>
  </div>
</template>
