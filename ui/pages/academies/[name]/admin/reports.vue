<script setup lang="ts">
import { Icon } from '@iconify/vue'

definePageMeta({ layout: false })

const academyId = inject<Ref<number | null>>('academyId', ref(null))
const academyName = inject<ComputedRef<string>>('academyName', computed(() => ''))
const api = useApi()

const isLoading = ref(true)
const stats = ref<any>(null)
const error = ref<string | null>(null)

const fetchOverview = async () => {
  if (!academyId.value) return
  isLoading.value = true
  error.value = null
  try {
    const [dashRes, memberRes] = await Promise.allSettled([
      api.get(`/api/academies/${academyId.value}/analytics/dashboard-stats`),
      api.get(`/api/academies/${academyId.value}/members/stats`),
    ])
    const dash = dashRes.status === 'fulfilled' ? (dashRes.value as any) : null
    const members = memberRes.status === 'fulfilled' ? (memberRes.value as any) : null
    stats.value = {
      total_students: dash?.data?.total_students ?? members?.stats?.approved ?? 0,
      total_staff: members?.stats?.by_role?.teacher ?? 0,
      total_classrooms: dash?.data?.classes_today ?? 0,
      total_courses: dash?.data?.total_courses ?? 0,
      pending_grading: dash?.data?.pending_grading ?? 0,
    }
  } catch (e: any) {
    error.value = 'ไม่สามารถโหลดข้อมูลรายงานได้'
    console.error('Failed to fetch analytics', e)
  } finally {
    isLoading.value = false
  }
}

watch(academyId, (id) => {
  if (id) fetchOverview()
}, { immediate: true })

const reportSections = computed(() => [
  {
    title: 'รายงานนักเรียน',
    icon: 'fluent:hat-graduation-24-regular',
    color: 'text-blue-600 bg-blue-50 dark:text-blue-400 dark:bg-blue-900/30',
    items: [
      { label: 'สรุปจำนวนนักเรียน', to: `/academies/${academyName.value}/admin/students` },
      { label: 'นักเรียนกลุ่มเสี่ยง', to: `/academies/${academyName.value}/admin/at-risk` },
      { label: 'สถิติการเข้าเรียน', to: `/academies/${academyName.value}/admin/school-attendance` },
    ]
  },
  {
    title: 'รายงานผลการเรียน',
    icon: 'fluent:star-24-regular',
    color: 'text-amber-600 bg-amber-50 dark:text-amber-400 dark:bg-amber-900/30',
    items: [
      { label: 'ผลการเรียนรวม', to: `/academies/${academyName.value}/admin/gradebook` },
      { label: 'ผลเรียนรายห้อง', to: `/academies/${academyName.value}/admin/gradebook/classrooms` },
      { label: 'ผลเรียนรายวิชา', to: `/academies/${academyName.value}/admin/gradebook/subjects` },
    ]
  },
  {
    title: 'รายงานบุคลากร',
    icon: 'fluent:person-board-24-regular',
    color: 'text-purple-600 bg-purple-50 dark:text-purple-400 dark:bg-purple-900/30',
    items: [
      { label: 'ข้อมูลบุคลากร', to: `/academies/${academyName.value}/admin/staff` },
      { label: 'ฝ่ายงาน/แผนก', to: `/academies/${academyName.value}/admin/departments` },
    ]
  },
  {
    title: 'รายงานกิจกรรม',
    icon: 'fluent:history-24-regular',
    color: 'text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-900/30',
    items: [
      { label: 'ประวัติกิจกรรมระบบ', to: `/academies/${academyName.value}/admin/activity-log` },
      { label: 'เยี่ยมบ้าน', to: `/academies/${academyName.value}/admin/home-visits` },
    ]
  },
])
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">รายงานและสถิติ</h1>
      <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">ภาพรวมข้อมูลและรายงานต่างๆ ของโรงเรียน</p>
    </div>

    <!-- Overview Stats -->
    <div v-if="isLoading" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="i in 4" :key="i" class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-5 animate-pulse">
        <div class="h-4 bg-gray-200 dark:bg-gray-600 rounded w-20 mb-3"></div>
        <div class="h-8 bg-gray-200 dark:bg-gray-600 rounded w-16"></div>
      </div>
    </div>

    <div v-else-if="error" class="rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
      <p class="text-red-700 dark:text-red-400 text-sm">{{ error }}</p>
    </div>

    <div v-else-if="stats" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-5 border border-blue-100 dark:border-blue-800">
        <p class="text-sm text-blue-600 dark:text-blue-400 font-medium">นักเรียนทั้งหมด</p>
        <p class="text-3xl font-bold text-blue-700 dark:text-blue-300 mt-2">{{ stats.total_students ?? stats.students_count ?? '-' }}</p>
      </div>
      <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-5 border border-green-100 dark:border-green-800">
        <p class="text-sm text-green-600 dark:text-green-400 font-medium">บุคลากร</p>
        <p class="text-3xl font-bold text-green-700 dark:text-green-300 mt-2">{{ stats.total_staff ?? stats.staff_count ?? '-' }}</p>
      </div>
      <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-5 border border-purple-100 dark:border-purple-800">
        <p class="text-sm text-purple-600 dark:text-purple-400 font-medium">ห้องเรียน</p>
        <p class="text-3xl font-bold text-purple-700 dark:text-purple-300 mt-2">{{ stats.total_classrooms ?? stats.classrooms_count ?? '-' }}</p>
      </div>
      <div class="bg-amber-50 dark:bg-amber-900/20 rounded-xl p-5 border border-amber-100 dark:border-amber-800">
        <p class="text-sm text-amber-600 dark:text-amber-400 font-medium">คอร์สเรียน</p>
        <p class="text-3xl font-bold text-amber-700 dark:text-amber-300 mt-2">{{ stats.total_courses ?? stats.courses_count ?? '-' }}</p>
      </div>
    </div>

    <!-- Report Sections -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div
        v-for="section in reportSections"
        :key="section.title"
        class="bg-white dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-700 p-5"
      >
        <div class="flex items-center gap-3 mb-4">
          <div :class="['w-10 h-10 rounded-lg flex items-center justify-center', section.color]">
            <Icon :icon="section.icon" class="w-5 h-5" />
          </div>
          <h3 class="font-semibold text-gray-900 dark:text-white">{{ section.title }}</h3>
        </div>
        <ul class="space-y-2">
          <li v-for="item in section.items" :key="item.label">
            <NuxtLink
              :to="item.to"
              class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
            >
              <Icon icon="fluent:chevron-right-24-regular" class="w-4 h-4 text-gray-400" />
              {{ item.label }}
            </NuxtLink>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>
