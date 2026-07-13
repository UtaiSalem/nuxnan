<script setup lang="ts">
import { Icon } from '@iconify/vue'

definePageMeta({ layout: 'main' })

const route = useRoute()
const api = useApi()
const academyName = computed(() => String(route.params.name))
const departmentId = computed(() => Number(route.params.id))
const academy = ref<any>(null)
const department = ref<any>(null)
const members = ref<any[]>([])
const permissions = ref<any[]>([])
const activeTab = ref<'overview' | 'members' | 'permissions' | 'tasks' | 'files' | 'announcements' | 'reports' | 'audit'>('overview')
const search = ref('')
const isLoading = ref(true)
const isMembersLoading = ref(false)
const errorMessage = ref('')
const showAddMember = ref(false)
const availableMembers = ref<any[]>([])
const selectedMemberIds = ref<number[]>([])
const addRole = ref('member')

const tabs = [
  { key: 'overview', label: 'ภาพรวม', icon: 'fluent:apps-24-regular' },
  { key: 'members', label: 'สมาชิก', icon: 'fluent:people-24-regular' },
  { key: 'permissions', label: 'บทบาทและสิทธิ์', icon: 'fluent:lock-closed-key-24-regular' },
  { key: 'tasks', label: 'งานและเอกสาร', icon: 'fluent:clipboard-task-24-regular' },
  { key: 'announcements', label: 'ประกาศ', icon: 'fluent:megaphone-24-regular' },
  { key: 'reports', label: 'รายงาน', icon: 'fluent:document-table-24-regular' },
  { key: 'audit', label: 'ประวัติการใช้งาน', icon: 'fluent:history-24-regular' },
]

const filteredMembers = computed(() => {
  const query = search.value.trim().toLowerCase()
  if (!query) return members.value
  return members.value.filter((member: any) => {
    const user = member.user || member
    return [user.name, user.email].filter(Boolean).some((value: string) => value.toLowerCase().includes(query))
  })
})

const load = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const school: any = await api.get(`/api/academies/${academyName.value}`)
    if (!school.success) throw new Error('ไม่พบโรงเรียน')
    academy.value = school.academy
    const response: any = await api.get(`/api/academies/${academy.value.id}/departments/${departmentId.value}`)
    if (!response.success) throw new Error('ไม่พบฝ่ายงาน')
    department.value = response.data?.department || response.department
    await Promise.all([loadMembers(), loadPermissions()])
  } catch (error: any) {
    errorMessage.value = error?.message || 'ไม่สามารถโหลดข้อมูลฝ่ายได้'
  } finally {
    isLoading.value = false
  }
}

const loadMembers = async () => {
  if (!academy.value) return
  isMembersLoading.value = true
  try {
    const response: any = await api.get(`/api/academies/${academy.value.id}/departments/${departmentId.value}/members`)
    members.value = response.data?.members || response.members || []
  } finally {
    isMembersLoading.value = false
  }
}

const loadPermissions = async () => {
  if (!academy.value) return
  try {
    const response: any = await api.get(`/api/academies/${academy.value.id}/departments/${departmentId.value}/permissions`)
    permissions.value = response.data?.permissions || response.data?.enabled_keys || []
  } catch { permissions.value = [] }
}

const memberUser = (member: any) => member.user || member
const memberRole = (member: any) => member.pivot?.role || member.role || 'member'
const formatDate = (value: string | null) => value ? new Date(value).toLocaleDateString('th-TH', { year: 'numeric', month: 'short', day: 'numeric' }) : '-'

const updateRole = async (member: any, role: string) => {
  await api.patch(`/api/academies/${academy.value.id}/departments/${departmentId.value}/members/role`, { user_id: member.id || member.user_id, role })
  await loadMembers()
}

const removeMember = async (member: any) => {
  if (!confirm('ต้องการนำสมาชิกคนนี้ออกจากฝ่ายหรือไม่')) return
  await api.delete(`/api/academies/${academy.value.id}/departments/${departmentId.value}/members`, { body: { user_id: member.id || member.user_id } })
  await loadMembers()
}

const openAddMember = async () => {
  const response: any = await api.get(`/api/academies/${academy.value.id}/members`, { query: { status: 2, per_page: 100 } })
  const existing = new Set(members.value.map((member: any) => member.id || member.user_id))
  availableMembers.value = (response.members || response.data?.members || []).filter((member: any) => !existing.has(member.id || member.user_id))
  selectedMemberIds.value = []
  showAddMember.value = true
}

const addMembers = async () => {
  if (!selectedMemberIds.value.length) return
  await api.post(`/api/academies/${academy.value.id}/departments/${departmentId.value}/members/bulk`, { user_ids: selectedMemberIds.value, role: addRole.value })
  showAddMember.value = false
  await loadMembers()
}

onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <NuxtLink :to="`/academies/${academyName}/admin/departments`" class="inline-flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-primary-600">
      <Icon icon="fluent:arrow-left-24-regular" class="h-4 w-4" /> กลับรายการฝ่าย
    </NuxtLink>

    <div v-if="isLoading" class="flex justify-center py-20"><div class="h-10 w-10 animate-spin rounded-full border-4 border-primary-500 border-t-transparent" /></div>
    <div v-else-if="errorMessage" class="rounded-2xl bg-red-50 p-8 text-center text-red-700">{{ errorMessage }}</div>
    <template v-else-if="department">
      <section class="rounded-3xl bg-gradient-to-br from-primary-600 to-sky-500 p-6 text-white shadow-lg sm:p-8">
        <p class="text-sm text-white/75">{{ academy?.name }}</p>
        <div class="mt-2 flex flex-wrap items-start justify-between gap-4">
          <div><h1 class="text-3xl font-bold">{{ department.name }}</h1><p class="mt-2 max-w-2xl text-white/80">{{ department.description || 'จัดการข้อมูลและการสื่อสารภายในฝ่าย' }}</p></div>
          <span class="rounded-full bg-white/20 px-3 py-1 text-sm">ฝ่ายงาน</span>
        </div>
      </section>

      <nav class="flex gap-1 overflow-x-auto rounded-2xl border border-gray-100 bg-white p-2 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <button v-for="tab in tabs" :key="tab.key" class="flex shrink-0 items-center gap-2 rounded-xl px-4 py-3 text-sm font-medium transition" :class="activeTab === tab.key ? 'bg-primary-50 text-primary-600 dark:bg-primary-900/30 dark:text-primary-300' : 'text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700'" @click="activeTab = tab.key as any">
          <Icon :icon="tab.icon" class="h-5 w-5" /> {{ tab.label }}
        </button>
      </nav>

      <div v-if="activeTab === 'overview'" class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-3"><div v-for="item in [{label:'สมาชิกทั้งหมด',value:department.members_count || members.length},{label:'หัวหน้าฝ่าย',value:department.head?.name || 'ยังไม่กำหนด'},{label:'สร้างเมื่อ',value:formatDate(department.created_at)}]" :key="item.label" class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"><p class="text-sm text-gray-500">{{ item.label }}</p><p class="mt-2 truncate text-xl font-bold text-gray-900 dark:text-white">{{ item.value }}</p></div></div>
        <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"><h2 class="mb-4 font-bold text-gray-900 dark:text-white">แก้ไขข้อมูลฝ่าย</h2><AcademyGroupsManageTabInfo :group="department" @updated="department = $event" /></section>
      </div>

      <section v-else-if="activeTab === 'members'" class="rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center justify-between border-b border-gray-100 p-5 dark:border-gray-700"><div><h2 class="font-bold text-gray-900 dark:text-white">สมาชิกฝ่าย</h2><p class="text-sm text-gray-500">เพิ่ม ลบ และกำหนดบทบาทสมาชิก</p></div><button class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white" @click="openAddMember">เพิ่มสมาชิก</button></div>
        <div class="divide-y divide-gray-100 dark:divide-gray-700"><div v-for="member in filteredMembers" :key="member.id || member.user_id" class="flex flex-wrap items-center gap-3 p-4"><img :src="memberUser(member).profile_photo_url || '/images/default-avatar.png'" class="h-10 w-10 rounded-full object-cover" :alt="memberUser(member).name" /><div class="min-w-0 flex-1"><p class="truncate font-medium text-gray-900 dark:text-white">{{ memberUser(member).name || '-' }}</p><p class="truncate text-xs text-gray-500">{{ memberUser(member).email || '-' }}</p></div><select :value="memberRole(member)" class="rounded-lg border-gray-200 text-sm dark:border-gray-600 dark:bg-gray-700" @change="updateRole(member, ($event.target as HTMLSelectElement).value)"><option value="head">หัวหน้าฝ่าย</option><option value="staff">รองหัวหน้า</option><option value="member">สมาชิก</option></select><button class="rounded-lg p-2 text-red-500 hover:bg-red-50" title="นำออก" @click="removeMember(member)"><Icon icon="fluent:delete-24-regular" class="h-5 w-5" /></button></div></div>
        <div v-if="showAddMember" class="border-t border-gray-100 p-5 dark:border-gray-700"><div class="flex items-center justify-between"><h3 class="font-semibold text-gray-900 dark:text-white">เพิ่มสมาชิกหลายคน</h3><button class="text-gray-400" @click="showAddMember = false">ปิด</button></div><select v-model="selectedMemberIds" multiple class="mt-3 min-h-32 w-full rounded-xl border-gray-200 text-sm dark:border-gray-600 dark:bg-gray-700"><option v-for="member in availableMembers" :key="member.id || member.user_id" :value="member.id || member.user_id">{{ member.name }} — {{ member.email }}</option></select><div class="mt-3 flex items-center gap-3"><select v-model="addRole" class="rounded-xl border-gray-200 text-sm dark:border-gray-600 dark:bg-gray-700"><option value="head">หัวหน้าฝ่าย</option><option value="staff">รองหัวหน้า</option><option value="member">สมาชิก</option></select><button class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white" @click="addMembers">เพิ่มสมาชิกที่เลือก</button></div></div>
      </section>

      <section v-else-if="activeTab === 'permissions'" class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"><AcademyGroupsManageTabPermissions :academy-id="academy.id" :group="department" /></section>
      <AcademyScopesScopedWorkspace v-else-if="activeTab === 'tasks'" :academy-id="academy.id" scope-type="department" :scope-id="department.id" />
      <section v-else-if="activeTab === 'announcements' || activeTab === 'reports'" class="rounded-2xl border border-gray-100 bg-white p-8 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800"><p class="text-gray-500">{{ activeTab === 'announcements' ? 'ประกาศของฝ่ายจะใช้ scope เดียวกับฟีดและระบบประกาศกลาง' : 'รายงานของฝ่ายจะใช้ข้อมูลสมาชิก กิจกรรม งาน และประวัติการใช้งาน' }}</p><NuxtLink v-if="activeTab === 'reports'" :to="`/academies/${academyName}/admin/reports?scope_type=department&scope_id=${department.id}`" class="mt-4 inline-flex rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white">เปิดหน้ารายงาน</NuxtLink></section>
      <section v-else class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"><AuditLogTab v-if="academy?.id && department?.id" :academy-id="academy.id" entity-type="AcademyGroup" :entity-id="department.id" /></section>
    </template>
  </div>
</template>
