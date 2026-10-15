<script setup lang="ts">
/**
 * Academy Admin - School Management System
 * หน้าระบบบริหารจัดการโรงเรียน
 */
import { ref, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'main'
})

const route = useRoute()
const router = useRouter()
const api = useApi()
const academyName = computed(() => route.params.name as string)

// State
const academy = ref<any>(null)
const isLoading = ref(true)
const activeTab = computed({
  get: () => {
    const q = String(route.query.tab || 'academic')
    return tabs.some(t => t.id === q) ? q : 'academic'
  },
  set: (value: string) => router.replace({ query: { ...route.query, tab: value } }),
})

// Academy Role
const academyId = ref<number | null>(null)
const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)

// Tabs
const tabs = [
  { id: 'academic', name: 'วิชาการ', icon: 'fluent:hat-graduation-24-regular' },
  { id: 'finance', name: 'การเงิน', icon: 'fluent:money-24-regular' },
  { id: 'staff', name: 'บุคลากร', icon: 'fluent:people-team-24-regular' },
  { id: 'communication', name: 'สื่อสาร', icon: 'fluent:chat-24-regular' },
  { id: 'reports', name: 'รายงาน', icon: 'fluent:data-bar-horizontal-24-regular' },
]

// Quick Actions for Academic
const academicQuickActions = computed(() => [
  {
    title: 'หลักสูตร',
    description: 'จัดการหลักสูตรการเรียน',
    icon: 'fluent:book-globe-24-filled',
    to: `/academies/${academyName.value}/admin/curriculums`,
    color: 'from-indigo-500 to-indigo-600',
  },
  {
    title: 'ห้องเรียน',
    description: 'จัดการห้องเรียนและกลุ่ม',
    icon: 'fluent:people-team-24-filled',
    to: `/academies/${academyName.value}/admin/classrooms`,
    color: 'from-teal-500 to-teal-600',
  },
  {
    title: 'ฝ่าย/แผนก',
    description: 'จัดการโครงสร้างองค์กร',
    icon: 'fluent:organization-24-filled',
    to: `/academies/${academyName.value}/admin/departments`,
    color: 'from-orange-500 to-orange-600',
  },
  {
    title: 'ตารางเรียน',
    description: 'จัดตารางเรียนและกิจกรรม',
    icon: 'fluent:calendar-24-filled',
    to: `/academies/${academyName.value}/admin/schedule`,
    color: 'from-pink-500 to-pink-600',
  },
])

// Quick Actions for Students
const studentQuickActions = computed(() => [
  {
    title: 'ทะเบียนนักเรียน',
    description: 'ข้อมูลนักเรียนทั้งหมด',
    icon: 'fluent:hat-graduation-24-filled',
    to: `/academies/${academyName.value}/admin/students`,
    color: 'from-blue-500 to-blue-600',
  },
  {
    title: 'เยี่ยมบ้าน',
    description: 'บันทึกการเยี่ยมบ้าน',
    icon: 'fluent:home-person-24-filled',
    to: `/academies/${academyName.value}/admin/home-visits`,
    color: 'from-green-500 to-green-600',
  },
  {
    title: 'ร้านค้าโรงเรียน',
    description: 'จัดการร้านค้าและสินค้า',
    icon: 'fluent:store-microsoft-24-filled',
    to: `/academies/${academyName.value}/admin/store`,
    color: 'from-emerald-500 to-emerald-600',
  },
])

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()
      
      if (!isAdmin.value) {
        navigateTo(`/academies/${academyName.value}/admin`)
        return
      }
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="px-4 sm:px-0">
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">ระบบบริหารโรงเรียน</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">จัดการวิชาการ การเงิน บุคลากร สื่อสาร และรายงาน</p>
        </div>
      </div>

      <!-- Tab Navigation -->
      <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex flex-wrap gap-2 sm:gap-4" aria-label="Tabs">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              activeTab === tab.id
                ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
              'flex items-center gap-2 py-3 px-3 border-b-2 font-medium text-sm whitespace-nowrap transition-colors'
            ]"
          >
            <Icon :icon="tab.icon" class="h-5 w-5" />
            <span class="hidden sm:inline">{{ tab.name }}</span>
          </button>
        </nav>
      </div>

      <!-- Tab Content -->
      <div class="tab-content">
        <!-- Academic Tab -->
        <div v-if="activeTab === 'academic'" class="space-y-6">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <NuxtLink
              v-for="action in academicQuickActions"
              :key="action.to"
              :to="action.to"
              class="group bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-all hover:border-primary-300 dark:hover:border-primary-600"
            >
              <div class="flex items-start gap-4">
                <div :class="['p-3 rounded-xl bg-gradient-to-br', action.color]">
                  <Icon :icon="action.icon" class="w-6 h-6 text-white" />
                </div>
                <div class="flex-1 min-w-0">
                  <h4 class="font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                    {{ action.title }}
                  </h4>
                  <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ action.description }}</p>
                </div>
                <Icon icon="fluent:chevron-right-24-regular" class="w-5 h-5 text-gray-400 group-hover:text-primary-500 transition-colors" />
              </div>
            </NuxtLink>
          </div>
          
          <!-- Academic Tab Content -->
          <div v-if="academyId">
            <SchoolAcademicTab :academy-id="academyId" />
          </div>
        </div>

        <!-- Finance Tab -->
        <div v-else-if="activeTab === 'finance' && academyId">
          <SchoolFinanceTab :academy-id="academyId" />
        </div>

        <!-- Staff Tab -->
        <div v-else-if="activeTab === 'staff' && academyId">
          <SchoolStaffTab :academy-id="academyId" />
        </div>

        <!-- Communication Tab -->
        <div v-else-if="activeTab === 'communication' && academyId">
          <SchoolCommunicationTab :academy-id="academyId" />
        </div>

        <!-- Reports Tab -->
        <div v-else-if="activeTab === 'reports' && academyId">
          <SchoolReportsTab :academy-id="academyId" />
        </div>
      </div>
    </div>
  </div>
</template>
