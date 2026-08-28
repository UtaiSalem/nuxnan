<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { Icon } from '@iconify/vue'
import { getAcademyGroupTypeMeta, GROUP_TYPE_COLOR_CLASSES } from '~/constants/academyGroupTypes'
import { useAuthStore } from '~/stores/auth'
import { useAcademyGroups } from '~/composables/useAcademyGroups'

const mainContentRef = ref<HTMLElement | null>(null)

const switchToNextTab = () => {
  if (currentTab.value === 'feed') onTabChange('members')
  else if (currentTab.value === 'members') onTabChange('about')
}

const switchToPreviousTab = () => {
  if (currentTab.value === 'about') onTabChange('members')
  else if (currentTab.value === 'members') onTabChange('feed')
}

useSwipe(mainContentRef, {
  onSwipeLeft: switchToNextTab,
  onSwipeRight: switchToPreviousTab,
})

definePageMeta({
  layout: 'main',
  middleware: ['auth'],
})

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { user } = storeToRefs(authStore)
const { getGroup, listAdmins, listMembers, getGroupStats, muteGroup, unmuteGroup } = useAcademyGroups()
const api = useApi()

// ===== Route params =====
const academyName = computed(() => route.params.name as string)
const groupId = computed(() => Number(route.params.groupId))

// ===== State =====
const group = ref<any>(null)
const academy = ref<any>(null)
const admins = ref<any[]>([])
const members = ref<any[]>([])
const stats = ref<{ members_count: number; admins_count: number; posts_count: number; created_at?: string } | null>(null)
const isLoading = ref(true)
const error = ref<string | null>(null)
const currentTab = ref<'feed' | 'members' | 'about'>('feed')
const isMuted = ref(false)
const showManageModal = ref(false)
const manageModalActiveTab = ref<'info' | 'admins' | 'members' | 'permissions' | 'delete'>('info')

// ===== Membership status (derive from members/admins) =====
const isGroupAdmin = computed(() =>
  admins.value.some((a) => a.id === user.value?.id || (a.pivot && a.pivot.user_id === user.value?.id) || a.user_id === user.value?.id)
)
const isGroupMember = computed(() =>
  members.value.some((m) => m.id === user.value?.id || (m.pivot && m.pivot.user_id === user.value?.id) || m.user_id === user.value?.id) || isGroupAdmin.value
)
const isAcademyAdmin = computed(() => academy.value?.authIsAcademyAdmin === true)

// Derived: can the current user post in this group?
const groupPermissions = ref<string[]>([]) // array of enabled keys

const canPost = computed(() => {
  if (group.value?.type === 'department') {
    return isGroupMember.value && groupPermissions.value.includes('can_post')
  }
  // For other group types, permit members to post if they are in the group
  return isGroupMember.value
})
const canManage = computed(() => isAcademyAdmin.value)

// ===== Loaders =====
const loadGroup = async () => {
  isLoading.value = true
  error.value = null
  try {
    // 1. group + academy (parent)
    const gRes: any = await getGroup(groupId.value)
    if (!gRes?.success) throw new Error(gRes?.message || 'ไม่พบส่วนงาน')
    group.value = gRes.group
    academy.value = gRes.group.academy

    // 2. stats + admins + members in parallel
    const [statsRes, adminsRes, membersRes, permsRes]: any = await Promise.all([
      getGroupStats(groupId.value),
      listAdmins(groupId.value),
      listMembers(groupId.value),
      group.value.type === 'department'
        ? api.get(`/api/academies/${academy.value.id}/departments/${groupId.value}/permissions`).catch(() => ({ success: false, data: { enabled_keys: [] } }))
        : Promise.resolve({ success: true, data: { enabled_keys: [] } }),
    ])

    stats.value = statsRes?.data ?? null
    admins.value = adminsRes?.admins ?? []
    members.value = membersRes?.members ?? membersRes?.data ?? []
    groupPermissions.value = permsRes?.data?.enabled_keys ?? []
    
    // Check if group is muted
    // Assuming API has no direct check, we can check if user is in user_muted_groups or handle locally/via API if available
    // For now we set default or fallback
    isMuted.value = false
  } catch (e: any) {
    error.value = e?.message || 'โหลดข้อมูลไม่สำเร็จ'
  } finally {
    isLoading.value = false
  }
}

const onMute = async () => {
  try {
    const res: any = await muteGroup(groupId.value)
    if (res?.success) {
      isMuted.value = true
    }
  } catch (e) {
    console.error('Failed to mute group:', e)
  }
}

const onUnmute = async () => {
  try {
    const res: any = await unmuteGroup(groupId.value)
    if (res?.success) {
      isMuted.value = false
    }
  } catch (e) {
    console.error('Failed to unmute group:', e)
  }
}

const onTabChange = (tab: typeof currentTab.value) => {
  currentTab.value = tab
  // Sync URL hash so reload keeps current tab
  const newHash = `#${tab}`
  if (route.hash !== newHash) {
    history.replaceState(history.state, '', `${route.path}${newHash}`)
  }
}

const openManage = (tab: typeof manageModalActiveTab.value = 'info') => {
  manageModalActiveTab.value = tab
  showManageModal.value = true
}

const handleManageModalClose = () => {
  showManageModal.value = false
  void loadGroup()
}

const handleGroupUpdated = (updatedGroup: any) => {
  group.value = { ...group.value, ...updatedGroup }
  loadGroup() // reload relationships
}

const handleGroupDeleted = () => {
  router.push(`/academies/${academyName.value}#groups`)
}

// Pick up tab from URL hash on initial load
onMounted(() => {
  const hash = route.hash.replace('#', '')
  if (['feed', 'members', 'about'].includes(hash)) {
    currentTab.value = hash as any
  }
  loadGroup()
})

watch(groupId, loadGroup)

// ===== Meta for cover styling =====
const typeMeta = computed(() => group.value ? getAcademyGroupTypeMeta(group.value.type) : null)
const typeCls = computed(() => typeMeta.value ? GROUP_TYPE_COLOR_CLASSES[typeMeta.value.color] : null)
</script>

<template>
  <div class="min-h-screen bg-gray-100 dark:bg-vikinger-dark-300">
    <!-- Loading -->
    <div v-if="isLoading" class="flex items-center justify-center min-h-[50vh]">
      <div class="text-center">
        <Icon icon="svg-spinners:ring-resize" class="w-12 h-12 text-vikinger-purple mx-auto mb-4" />
        <p class="text-gray-600 dark:text-gray-400 font-medium">กำลังโหลดส่วนงาน...</p>
      </div>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="flex items-center justify-center min-h-[50vh]">
      <div class="text-center p-4 sm:p-8 max-w-md bg-white dark:bg-vikinger-dark-200 rounded-2xl shadow-sm">
        <Icon icon="heroicons:exclamation-triangle" class="w-16 h-16 text-amber-500 mx-auto mb-4" />
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ไม่พบส่วนงาน</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-6">{{ error }}</p>
        <NuxtLink
          :to="`/academies/${academyName}`"
          class="inline-flex items-center gap-2 px-6 py-3 bg-vikinger-purple text-white rounded-lg font-semibold hover:bg-vikinger-purple/90 transition-colors"
        >
          <Icon icon="heroicons:arrow-left" class="w-4 h-4" />
          กลับหน้าโรงเรียน
        </NuxtLink>
      </div>
    </div>

    <!-- Main content -->
    <div v-else-if="group" class="max-w-7xl mx-auto px-4 py-6">
      <!-- Breadcrumb -->
      <div class="flex items-center gap-2 mb-4 text-xs md:text-sm text-gray-500 dark:text-gray-400">
        <NuxtLink :to="`/academies/${academyName}`" class="hover:text-vikinger-purple font-medium">
          {{ academy?.name }}
        </NuxtLink>
        <Icon icon="heroicons:chevron-right" class="w-4 h-4" />
        <NuxtLink :to="`/academies/${academyName}#groups`" class="hover:text-vikinger-purple font-medium">
          ส่วนงาน
        </NuxtLink>
        <Icon icon="heroicons:chevron-right" class="w-4 h-4" />
        <span class="text-gray-700 dark:text-gray-200 font-semibold">{{ group.name }}</span>
      </div>

      <!-- Cover (Phase I.3) -->
      <AcademyGroupsGroupProfileCover
        :group="group"
        :stats="stats"
        :is-member="isGroupMember"
        :is-admin="isGroupAdmin"
        :can-manage="canManage"
        :is-muted="isMuted"
        @mute="onMute"
        @unmute="onUnmute"
        @manage="openManage('info')"
      />

      <!-- Tabs -->
      <div class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-vikinger-dark-200 rounded-b-xl shadow-sm">
        <div class="flex overflow-x-auto scrollbar-none">
          <button
            v-for="t in [
              { key: 'feed', label: 'ฟีด', icon: 'heroicons:home', count: stats?.posts_count },
              { key: 'members', label: 'สมาชิก', icon: 'heroicons:user-group', count: stats?.members_count },
              { key: 'about', label: 'เกี่ยวกับ', icon: 'heroicons:information-circle' },
            ]"
            :key="t.key"
            type="button"
            :class="[
              'flex items-center gap-2 px-6 py-4 text-sm font-semibold transition-colors whitespace-nowrap',
              currentTab === t.key
                ? 'text-vikinger-purple border-b-2 border-vikinger-purple'
                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
            ]"
            @click="onTabChange(t.key as any)"
          >
            <Icon :icon="t.icon" class="w-5 h-5" />
            <span>{{ t.label }}</span>
            <span
              v-if="t.count != null && t.count > 0"
              class="ml-1 text-[11px] px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 font-bold"
            >
              {{ t.count }}
            </span>
          </button>
        </div>
      </div>

      <!-- 2-col layout: main + sidebar -->
      <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_320px] gap-4 lg:gap-5 items-start mt-5">
        <main ref="mainContentRef" class="min-w-0">
          <AcademyGroupsGroupFeedTab
            v-if="currentTab === 'feed'"
            :group="group"
            :can-post="canPost"
            :is-academy-admin="isAcademyAdmin"
          />
          <AcademyGroupsGroupMembersTab
            v-else-if="currentTab === 'members'"
            :group="group"
            :admins="admins"
            :members="members"
            :can-manage="canManage"
            @open-manage="openManage"
          />
          <AcademyGroupsGroupAboutTab
            v-else-if="currentTab === 'about'"
            :group="group"
            :academy="academy"
            :stats="stats"
            :type-meta="typeMeta"
          />
        </main>

        <!-- Right sidebar: quick info + member preview -->
        <aside class="hidden lg:flex flex-col gap-4 sticky top-20 self-start">
          <!-- Quick stats card -->
          <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm p-4">
            <h4 class="text-sm font-bold text-gray-900 dark:text-white mb-3">สถิติด่วน</h4>
            <div class="grid grid-cols-3 gap-3 text-center">
              <div>
                <div class="text-lg font-bold text-gray-900 dark:text-white">{{ stats?.posts_count ?? 0 }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 font-semibold">โพสต์</div>
              </div>
              <div>
                <div class="text-lg font-bold text-gray-900 dark:text-white">{{ stats?.members_count ?? 0 }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 font-semibold">สมาชิก</div>
              </div>
              <div>
                <div class="text-lg font-bold text-gray-900 dark:text-white">{{ stats?.admins_count ?? 0 }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 font-semibold">หัวหน้า</div>
              </div>
            </div>
          </div>

          <!-- Admins preview (3 บนสุด) -->
          <div v-if="admins.length > 0" class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
              <span class="font-bold text-gray-900 dark:text-white text-sm">หัวหน้าส่วนงาน</span>
              <button
                class="text-xs font-bold text-vikinger-purple hover:underline"
                @click="onTabChange('members')"
              >
                ดูทั้งหมด
              </button>
            </div>
            <div class="p-4 space-y-3">
              <div
                v-for="a in admins.slice(0, 3)"
                :key="a.id"
                class="flex items-center gap-3"
              >
                <div class="w-9 h-9 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden flex-shrink-0">
                  <img v-if="a.profile_photo_path" :src="a.profile_photo_path" class="w-full h-full object-cover" />
                  <img v-else-if="a.user?.profile_photo_path" :src="a.user.profile_photo_path" class="w-full h-full object-cover" />
                  <Icon v-else icon="heroicons:user" class="w-full h-full p-1.5 text-gray-400" />
                </div>
                <div class="flex-1 min-w-0">
                  <div class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                    {{ a.name || a.user?.name }}
                  </div>
                  <div class="text-xs text-gray-500 font-medium">
                    {{ a.pivot?.role === 'admin' ? 'แอดมินกลุ่ม' : a.pivot?.role === 'teacher' ? 'ครูประจำกลุ่ม' : a.role || 'หัวหน้าส่วนงาน' }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </aside>
      </div>

      <!-- Manage Modal -->
      <AcademyGroupsGroupManageModal
        v-model:open="showManageModal"
        :group="group"
        :academy-id="academy?.id"
        :initial-tab="manageModalActiveTab"
        @close="handleManageModalClose"
        @updated="handleGroupUpdated"
        @deleted="handleGroupDeleted"
      />
    </div>
  </div>
</template>
