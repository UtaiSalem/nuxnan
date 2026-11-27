<script setup lang="ts">
/**
 * Academy Admin Layout
 * หน้า Layout หลักสำหรับ Admin Panel ของโรงเรียน
 */
import { Icon } from '@iconify/vue'
import AcademyAdminForbidden from '~/components/academy/admin/AcademyAdminForbidden.vue'
import { provideAcademyAdminContext } from '~/composables/useAcademyAdminContext'
import {
  adminRelativePath,
  canEnterAcademyAdmin,
  canViewSection,
  findAdminSection,
  visibleAdminGroups,
} from '~/utils/academyAdminNav'

definePageMeta({
  layout: 'main',
  ssr: false,
  middleware: ['auth']
})

const route = useRoute()
const api = useApi()

const academyName = computed(() => route.params.name as string)

// State
const academy = ref<any>(null)
const isLoading = ref(true)
const isSidebarOpen = ref(false) // Default to closed on mobile

// Academy Role
const academyId = ref<number | null>(null)
const academyRole = useAcademyRole(academyId)
const {
  myRole,
  roleDisplayName,
  roleColor,
  roleIcon,
  can,
  isOwner,
  isAdmin,
  fetchMyRole
} = academyRole

// Provide to child pages so they don't need to resolve academy themselves
provide('academyId', academyId)
provide('academyName', academyName)
provide('academy', academy)

// SA-3 — context เดียวที่มีชนิด (provide เดิม 3 ตัวด้านบนคงไว้ให้ 16 หน้าที่ inject อยู่)
provideAcademyAdminContext({
  academy,
  academyId,
  academyName,
  role: academyRole,
  can,
  isAdmin,
  refreshRole: academyRole.refreshRole,
})

// SA-1 — เมนูและด่านสิทธิ์มาจาก utils/academyAdminNav.ts ที่เดียว
const permissionCtx = computed(() => ({ isAdmin: isAdmin.value, can }))
const navGroups = computed(() => visibleAdminGroups(permissionCtx.value))
const sectionTo = (path: string) =>
  path ? `/academies/${academyName.value}/admin/${path}` : `/academies/${academyName.value}/admin`
const currentSection = computed(() => {
  const rel = adminRelativePath(route.path, academyName.value)
  return rel === null ? null : findAdminSection(rel)
})
// หน้าที่ไม่มีใน registry: ให้เฉพาะ isAdmin (fail closed — เทสต์ SA-1a กันไม่ให้มีหน้าตกหล่น)
const canViewCurrent = computed(() =>
  currentSection.value ? canViewSection(currentSection.value, permissionCtx.value) : isAdmin.value
)

// มือถือ: ปิดเมนูเองหลังเลือก ไม่งั้นเนื้อหาถูกดันลงใต้กริดเมนู
watch(() => route.fullPath, () => {
  isSidebarOpen.value = false
})

// Fetch data
onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()
      
      // SA-1 — ประตูเดียว: isAdmin หรือถือ admin.access
      if (!canEnterAcademyAdmin(permissionCtx.value)) {
        navigateTo(`/academies/${academyName.value}`)
        return
      }
    }
  } catch (err) {
    console.error('Failed to load academy:', err)
    navigateTo('/academies')
  } finally {
    isLoading.value = false
  }
})

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value
}
</script>

<template>
  <div class="w-full">
    <!-- Loading -->
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-12 w-12 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else>
      <!-- Academy Header Card -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
        <div class="p-4 flex items-center justify-between">
          <NuxtLink 
            :to="`/academies/${academyName}`"
            class="flex items-center gap-3 hover:opacity-80 transition-opacity"
          >
            <img 
              :src="academy?.logo || `https://ui-avatars.com/api/?name=${encodeURIComponent(academy?.name || 'Academy')}&background=6366f1&color=fff&size=96`" 
              :alt="academy?.name"
              class="w-12 h-12 rounded-lg object-cover flex-shrink-0"
            />
            <div>
              <h1 class="font-bold text-lg text-gray-900 dark:text-white">
                {{ academy?.name }}
              </h1>
              <p class="text-sm text-gray-500 dark:text-gray-400">Admin Panel</p>
            </div>
          </NuxtLink>
          
          <!-- Role Badge -->
          <div :class="['flex items-center gap-2 px-3 py-2 rounded-lg', roleColor]">
            <Icon :icon="roleIcon" class="w-5 h-5" />
            <span class="font-medium text-sm hidden sm:inline">{{ roleDisplayName }}</span>
          </div>
        </div>
      </div>

      <!-- Mobile Menu Toggle -->
      <button 
        @click="toggleSidebar"
        class="lg:hidden w-full mb-4 flex items-center justify-center gap-2 px-4 py-3 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700"
      >
        <Icon icon="fluent:navigation-24-regular" class="w-5 h-5" />
        <span>{{ isSidebarOpen ? 'ซ่อนเมนู' : 'แสดงเมนู' }}</span>
      </button>

      <div class="flex flex-col lg:flex-row gap-6">
      <!-- Sidebar Navigation -->
      <aside
        :class="[
          'bg-white dark:bg-gray-800 rounded-xl shadow-[0_0_30px_rgba(14,165,233,0.05)] overflow-hidden transition-all duration-300',
          isSidebarOpen ? 'block' : 'hidden lg:block',
          'lg:w-64 lg:flex-shrink-0'
        ]"
      >
        <!-- Navigation (HopeUI sidebar-default style) -->
        <nav class="p-4 space-y-4 max-h-[70vh] lg:max-h-none overflow-y-auto">
          <div v-for="group in navGroups" :key="group.id">
            <h3 class="mb-2 px-3 text-[13px] font-normal uppercase tracking-[.18rem] text-gray-900 dark:text-gray-100">
              {{ group.label }}
            </h3>
            <ul class="grid grid-cols-2 gap-1 sm:grid-cols-3 lg:grid-cols-1">
              <li v-for="section in group.sections" :key="section.id">
                <NuxtLink
                  :to="sectionTo(section.path)"
                  :class="[
                    'flex min-h-[44px] items-center gap-2 rounded-lg px-3 py-2.5 min-w-0 transition-all duration-300 ease-in-out lg:min-h-0',
                    currentSection?.id === section.id
                      ? 'bg-primary-500 text-white shadow-[0_10px_20px_-10px_rgba(14,165,233,0.6)]'
                      : 'text-gray-600 dark:text-gray-300 hover:bg-primary-500/10 hover:text-primary-600 dark:hover:text-primary-400'
                  ]"
                >
                  <Icon :icon="section.icon" class="w-5 h-5 flex-shrink-0" />
                  <span class="text-sm leading-tight break-words min-w-0">{{ section.label }}</span>
                </NuxtLink>
              </li>
            </ul>
          </div>
        </nav>

        <!-- Footer -->
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
          <NuxtLink
            :to="`/academies/${academyName}`"
            class="flex items-center gap-3 rounded-lg px-4 py-2.5 text-gray-600 dark:text-gray-300 transition-all duration-300 hover:bg-primary-500/10 hover:text-primary-600 dark:hover:text-primary-400"
          >
            <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
            <span class="text-sm">กลับหน้าโรงเรียน</span>
          </NuxtLink>
        </div>
      </aside>

      <!-- Main Content -->
      <main class="flex-1 min-w-0">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 sm:p-6">
          <AcademyAdminForbidden v-if="!canViewCurrent" :section="currentSection" :academy-name="academyName" />
          <NuxtPage v-else />
        </div>
      </main>
    </div>
  </div>
</div>
</template>

<style scoped>
/* Hide scrollbar for sidebar */
nav::-webkit-scrollbar {
  width: 4px;
}
nav::-webkit-scrollbar-track {
  background: transparent;
}
nav::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 2px;
}
.dark nav::-webkit-scrollbar-thumb {
  background: #475569;
}
</style>
