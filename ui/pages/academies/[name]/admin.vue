<script setup lang="ts">
/**
 * Academy Admin Layout
 * หน้า Layout หลักสำหรับหน้าจัดการโรงเรียน
 */
import { Icon } from '@iconify/vue'
import AcademyAdminForbidden from '~/components/academy/admin/AcademyAdminForbidden.vue'
import AcademyAdminNav from '~/components/academy/admin/AcademyAdminNav.vue'
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
const isDrawerOpen = ref(false)
const badges = ref<Record<string, number>>({})
const drawerElement = ref<HTMLElement | null>(null)

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
const currentGroupLabel = computed(() => navGroups.value.find(g => g.sections.some(s => s.id === currentSection.value?.id))?.label ?? null)
// หน้าที่ไม่มีใน registry: ให้เฉพาะ isAdmin (fail closed — เทสต์ SA-1a กันไม่ให้มีหน้าตกหล่น)
const canViewCurrent = computed(() =>
  currentSection.value ? canViewSection(currentSection.value, permissionCtx.value) : isAdmin.value
)

// มือถือ: ปิด drawer เองหลังเลือกเมนู
watch(() => route.fullPath, () => {
  isDrawerOpen.value = false
})

let previousBodyOverflow: string | null = null
let drawerOpener: HTMLElement | null = null
const restoreBodyOverflow = () => {
  if (previousBodyOverflow !== null) {
    document.body.style.overflow = previousBodyOverflow
    previousBodyOverflow = null
  }
}

watch(isDrawerOpen, async open => {
  if (!import.meta.client) return
  if (open) {
    previousBodyOverflow = document.body.style.overflow
    drawerOpener = document.activeElement instanceof HTMLElement ? document.activeElement : null
    document.body.style.overflow = 'hidden'
    await nextTick()
    if (isDrawerOpen.value) drawerElement.value?.querySelector<HTMLButtonElement>('button')?.focus()
  } else {
    restoreBodyOverflow()
    drawerOpener?.focus()
    drawerOpener = null
  }
})

const handleDrawerKeydown = (event: KeyboardEvent) => {
  if (!isDrawerOpen.value) return
  if (event.key === 'Escape') {
    event.preventDefault()
    isDrawerOpen.value = false
  } else if (event.key === 'Tab') {
    const controls = drawerElement.value?.querySelectorAll<HTMLElement>('a[href], button:not([disabled]), input:not([disabled])')
    const first = controls?.[0]
    const last = controls?.[controls.length - 1]
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault()
      last?.focus()
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault()
      first?.focus()
    }
  }
}

const closeDesktopDrawer = () => {
  if (window.matchMedia('(min-width: 1024px)').matches) isDrawerOpen.value = false
}

onMounted(() => {
  document.addEventListener('keydown', handleDrawerKeydown)
  window.addEventListener('resize', closeDesktopDrawer)
})

onBeforeUnmount(() => {
  document.removeEventListener('keydown', handleDrawerKeydown)
  window.removeEventListener('resize', closeDesktopDrawer)
  restoreBodyOverflow()
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

</script>

<template>
  <div class="w-full">
    <!-- Loading -->
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-12 w-12 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else>
      <!-- Academy Header Card -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-4 sm:mb-6">
        <div class="p-3 sm:p-4 flex items-center gap-3">
          <NuxtLink 
            :to="`/academies/${academyName}`"
            class="flex min-h-[44px] min-w-0 flex-1 items-center gap-3 hover:opacity-80 transition-opacity"
          >
            <img 
              :src="academy?.logo || `https://ui-avatars.com/api/?name=${encodeURIComponent(academy?.name || 'Academy')}&background=6366f1&color=fff&size=96`" 
              :alt="academy?.name"
              class="h-10 w-10 sm:h-12 sm:w-12 rounded-lg object-cover flex-shrink-0"
            />
            <div class="min-w-0 flex-1">
              <h1 class="break-words font-bold text-base sm:text-lg text-gray-900 dark:text-white">
                {{ academy?.name }}
              </h1>
              <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">หน้าจัดการโรงเรียน</p>
            </div>
          </NuxtLink>
          
          <!-- Role Badge -->
          <div :class="['flex flex-shrink-0 items-center gap-2 px-2 sm:px-3 py-2 rounded-lg', roleColor]">
            <Icon :icon="roleIcon" aria-hidden="true" class="w-5 h-5 flex-shrink-0" />
            <span class="whitespace-nowrap font-medium text-xs sm:text-sm">{{ roleDisplayName }}</span>
          </div>
        </div>
      </div>

      <!-- Mobile menu and breadcrumb -->
      <div class="lg:hidden mb-4 flex items-center gap-2">
        <button
          type="button"
          aria-controls="academy-admin-drawer"
          :aria-expanded="isDrawerOpen"
          class="flex flex-shrink-0 min-h-[44px] items-center gap-2 whitespace-nowrap px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
          @click="isDrawerOpen = true"
        >
          <Icon icon="fluent:navigation-24-regular" aria-hidden="true" class="w-5 h-5" />
          <span>เมนู</span>
        </button>
        <div class="min-w-0 flex-1">
          <nav aria-label="breadcrumb" class="overflow-x-auto text-gray-500 dark:text-gray-400">
            <ol class="flex items-center gap-1 whitespace-nowrap text-xs sm:text-sm">
              <li>
                <NuxtLink :to="sectionTo('')" :aria-current="currentSection?.id === 'dashboard' ? 'page' : undefined" class="inline-flex min-h-[44px] items-center hover:text-primary-600 dark:hover:text-primary-400">จัดการโรงเรียน</NuxtLink>
              </li>
              <template v-if="currentSection && currentSection.id !== 'dashboard'">
                <li v-if="currentGroupLabel && currentSection.group !== 'overview'" class="flex items-center gap-1">
                  <Icon icon="fluent:chevron-right-16-regular" aria-hidden="true" class="h-4 w-4 flex-shrink-0" />
                  <span>{{ currentGroupLabel }}</span>
                </li>
                <li class="flex items-center gap-1">
                  <Icon icon="fluent:chevron-right-16-regular" aria-hidden="true" class="h-4 w-4 flex-shrink-0" />
                  <span aria-current="page" class="font-bold text-gray-900 dark:text-gray-100">{{ currentSection.label }}</span>
                </li>
              </template>
            </ol>
          </nav>
        </div>
      </div>

      <Teleport to="body">
        <div
          v-if="isDrawerOpen"
          id="academy-admin-drawer"
          ref="drawerElement"
          role="dialog"
          aria-modal="true"
          aria-label="เมนูจัดการโรงเรียน"
          class="lg:hidden fixed inset-0 z-50 flex flex-col bg-white dark:bg-gray-900"
        >
          <div class="flex flex-shrink-0 items-center gap-3 border-b border-gray-200 p-3 dark:border-gray-700">
            <span class="min-w-0 flex-1 break-words font-semibold text-gray-900 dark:text-white">{{ academy?.name }}</span>
            <button
              type="button"
              aria-label="ปิดเมนู"
              class="flex min-h-[44px] min-w-[44px] flex-shrink-0 items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
              @click="isDrawerOpen = false"
            >
              <Icon icon="fluent:dismiss-24-regular" aria-hidden="true" class="h-6 w-6" />
            </button>
          </div>
          <div class="min-h-0 flex-1 overflow-y-auto p-3">
            <AcademyAdminNav :groups="navGroups" :current-id="currentSection?.id ?? null" :section-to="sectionTo" :badges="badges" />
          </div>
          <div class="flex-shrink-0 border-t border-gray-200 px-3 pt-3 pb-[env(safe-area-inset-bottom)] dark:border-gray-700">
            <NuxtLink
              :to="`/academies/${academyName}`"
              class="mb-3 flex min-h-[44px] items-center gap-3 rounded-lg px-4 py-2.5 text-gray-600 dark:text-gray-300 hover:bg-primary-500/10 hover:text-primary-600 dark:hover:text-primary-400"
            >
              <Icon icon="fluent:arrow-left-24-regular" aria-hidden="true" class="w-5 h-5 flex-shrink-0" />
              <span class="min-w-0 flex-1 break-words text-sm">กลับหน้าโรงเรียน</span>
            </NuxtLink>
          </div>
        </div>
      </Teleport>

      <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
        <!-- Desktop sidebar -->
        <aside class="hidden lg:block lg:w-64 lg:flex-shrink-0">
          <div
            class="lg:sticky lg:top-20 lg:max-h-[calc(100vh-6rem)] lg:overflow-y-auto rounded-xl bg-white p-4 shadow-[0_0_30px_rgba(14,165,233,0.05)] dark:bg-gray-800"
          >
            <AcademyAdminNav :groups="navGroups" :current-id="currentSection?.id ?? null" :section-to="sectionTo" :badges="badges" />
            <div class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
              <NuxtLink
                :to="`/academies/${academyName}`"
                class="flex min-h-[44px] items-center gap-3 rounded-lg px-4 py-2.5 text-gray-600 dark:text-gray-300 hover:bg-primary-500/10 hover:text-primary-600 dark:hover:text-primary-400"
              >
                <Icon icon="fluent:arrow-left-24-regular" aria-hidden="true" class="w-5 h-5 flex-shrink-0" />
                <span class="min-w-0 flex-1 break-words text-sm">กลับหน้าโรงเรียน</span>
              </NuxtLink>
            </div>
          </div>
        </aside>

        <!-- Main content -->
        <main class="min-w-0 flex-1">
          <div class="hidden lg:block mb-3">
            <nav aria-label="breadcrumb" class="overflow-x-auto text-gray-500 dark:text-gray-400">
              <ol class="flex items-center gap-1 whitespace-nowrap text-xs sm:text-sm">
                <li>
                  <NuxtLink :to="sectionTo('')" :aria-current="currentSection?.id === 'dashboard' ? 'page' : undefined" class="inline-flex min-h-[44px] items-center hover:text-primary-600 dark:hover:text-primary-400">จัดการโรงเรียน</NuxtLink>
                </li>
                <template v-if="currentSection && currentSection.id !== 'dashboard'">
                  <li v-if="currentGroupLabel && currentSection.group !== 'overview'" class="flex items-center gap-1">
                    <Icon icon="fluent:chevron-right-16-regular" aria-hidden="true" class="h-4 w-4 flex-shrink-0" />
                    <span>{{ currentGroupLabel }}</span>
                  </li>
                  <li class="flex items-center gap-1">
                    <Icon icon="fluent:chevron-right-16-regular" aria-hidden="true" class="h-4 w-4 flex-shrink-0" />
                    <span aria-current="page" class="font-bold text-gray-900 dark:text-gray-100">{{ currentSection.label }}</span>
                  </li>
                </template>
              </ol>
            </nav>
          </div>
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-3 sm:p-6">
            <AcademyAdminForbidden v-if="!canViewCurrent" :section="currentSection" :academy-name="academyName" />
            <NuxtPage v-else />
          </div>
        </main>
      </div>
    </div>
  </div>
</template>
