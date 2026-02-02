<script setup lang="ts">
/**
 * Academy Admin - Members Management
 * หน้าจัดการสมาชิกของโรงเรียน
 */
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

definePageMeta({
  layout: false
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)

// State
const academy = ref<any>(null)
const members = ref<any[]>([])
const roles = ref<any[]>([])
const isLoading = ref(true)
const isLoadingMembers = ref(false)

// Filters
const searchQuery = ref('')
const selectedStatus = ref<number | null>(null)
const selectedRole = ref<string | null>(null)
const sortBy = ref('created_at')
const sortOrder = ref<'asc' | 'desc'>('desc')
const viewMode = ref<'card' | 'table'>('card')

// Persist view mode preference
const savedViewMode = useCookie<'card' | 'table'>('academy-members-view-mode')
onMounted(() => {
  if (savedViewMode.value) {
    viewMode.value = savedViewMode.value
  }
})
watch(viewMode, (val) => {
  savedViewMode.value = val
})

// Pagination
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0
})

// Modals
const showRoleModal = ref(false)
const showManageModal = ref(false)
const showInviteModal = ref(false)
const selectedMember = ref<any>(null)

// Academy Role
const academyId = ref<number | null>(null)
const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)

// Stats
const stats = ref({
  total: 0,
  approved: 0,
  pending: 0,
  invited: 0,
  rejected: 0,
  suspended: 0
})

const statusOptions = [
  { value: null, label: 'ทุกสถานะ' },
  { value: 2, label: 'สมาชิก' },
  { value: 1, label: 'รอการอนุมัติ' },
  { value: 4, label: 'ได้รับเชิญ' },
  { value: 5, label: 'ถูกระงับ' },
  { value: 3, label: 'ถูกปฏิเสธ' },
]

const roleOptions = computed(() => [
  { value: null, label: 'ทุกบทบาท' },
  ...roles.value.map(r => ({ value: r.name, label: r.display_name_th }))
])

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${encodeURIComponent(academyName.value)}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()
      
      if (!can('members.view')) {
        navigateTo(`/academies/${academyName.value}/admin`)
        return
      }
      
      await Promise.all([
        fetchMembers(),
        fetchRoles(),
        fetchStats(),
      ])
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

const fetchMembers = async (page = 1) => {
  if (!academyId.value) return
  
  isLoadingMembers.value = true
  try {
    const params = new URLSearchParams()
    params.append('page', String(page))
    params.append('per_page', String(pagination.value.per_page))
    if (searchQuery.value) params.append('search', searchQuery.value)
    if (selectedStatus.value !== null) params.append('status', String(selectedStatus.value))
    if (selectedRole.value) params.append('role', selectedRole.value)
    params.append('sort_by', sortBy.value)
    params.append('sort_order', sortOrder.value)

    const response: any = await api.get(`/api/academies/${academyId.value}/members/search?${params}`)
    if (response.success) {
      members.value = response.members || []
      pagination.value = response.pagination || pagination.value
    }
  } catch (err) {
    console.error('Failed to fetch members:', err)
  } finally {
    isLoadingMembers.value = false
  }
}

const fetchRoles = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/roles/available`)
    if (response.success) {
      roles.value = response.roles || []
    }
  } catch (err) {
    console.error('Failed to fetch roles:', err)
  }
}

const fetchStats = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/members/stats`)
    if (response.success) {
      stats.value = response.stats
    }
  } catch (err) {
    console.error('Failed to fetch stats:', err)
  }
}

// Actions
const openRoleModal = (member: any) => {
  selectedMember.value = member
  showRoleModal.value = true
}

const openManageModal = (member: any) => {
  selectedMember.value = member
  showManageModal.value = true
}

const acceptMember = async (member: any) => {
  const result = await Swal.fire({
    title: 'ยืนยันการอนุมัติ',
    text: `อนุมัติ ${member.member_name} เป็นสมาชิก?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'อนุมัติ',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#10b981'
  })

  if (result.isConfirmed) {
    try {
      await api.post(`/api/academies/${academyId.value}/members/${member.id}/accept`)
      await fetchMembers(pagination.value.current_page)
      await fetchStats()
      Swal.fire('สำเร็จ', 'อนุมัติสมาชิกเรียบร้อยแล้ว', 'success')
    } catch (err) {
      Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถอนุมัติได้', 'error')
    }
  }
}

const rejectMember = async (member: any) => {
  const result = await Swal.fire({
    title: 'ยืนยันการปฏิเสธ',
    text: `ปฏิเสธคำขอของ ${member.member_name}?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'ปฏิเสธ',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#ef4444'
  })

  if (result.isConfirmed) {
    try {
      await api.post(`/api/academies/${academyId.value}/members/${member.id}/reject`)
      await fetchMembers(pagination.value.current_page)
      await fetchStats()
      Swal.fire('สำเร็จ', 'ปฏิเสธคำขอเรียบร้อยแล้ว', 'success')
    } catch (err) {
      Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถปฏิเสธได้', 'error')
    }
  }
}

const removeMember = async (member: any) => {
  const result = await Swal.fire({
    title: 'ยืนยันการลบสมาชิก',
    text: `ลบ ${member.member_name} ออกจากโรงเรียน? การกระทำนี้ไม่สามารถย้อนกลับได้`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'ลบสมาชิก',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#ef4444'
  })

  if (result.isConfirmed) {
    try {
      await api.delete(`/api/academies/${academyId.value}/members/${member.id}`)
      await fetchMembers(pagination.value.current_page)
      await fetchStats()
      Swal.fire('สำเร็จ', 'ลบสมาชิกเรียบร้อยแล้ว', 'success')
    } catch (err: any) {
      Swal.fire('เกิดข้อผิดพลาด', err.data?.message || 'ไม่สามารถลบได้', 'error')
    }
  }
}

const onRoleAssigned = async () => {
  showRoleModal.value = false
  await fetchMembers(pagination.value.current_page)
}

const onMemberUpdated = async () => {
  showManageModal.value = false
  await fetchMembers(pagination.value.current_page)
  await fetchStats()
}

const onSearch = () => {
  pagination.value.current_page = 1
  fetchMembers(1)
}

const getStatusBadge = (status: number) => {
  const badges: Record<number, { label: string; class: string }> = {
    1: { label: 'รอการอนุมัติ', class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' },
    2: { label: 'สมาชิก', class: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' },
    3: { label: 'ถูกปฏิเสธ', class: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' },
    4: { label: 'ได้รับเชิญ', class: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' },
    5: { label: 'ถูกระงับ', class: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400' },
  }
  return badges[status] || { label: 'ไม่ทราบ', class: 'bg-gray-100 text-gray-800' }
}

const getRoleBadge = (member: any) => {
  if (member.academy_role) {
    return {
      label: member.academy_role.display_name_th,
      color: member.academy_role.color,
      icon: member.academy_role.icon
    }
  }
  return {
    label: member.role_display_name || 'สมาชิก',
    color: 'gray',
    icon: 'fluent:person-24-regular'
  }
}
</script>

<template>
  <NuxtLayout name="academy-admin" :academy-name="academyName">
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">จัดการสมาชิก</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">จัดการสมาชิกทั้งหมดของโรงเรียน</p>
        </div>
        <div class="flex items-center gap-3">
          <NuxtLink 
            :to="`/academies/${academyName}/admin/roles`"
            class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors flex items-center gap-2"
          >
            <Icon icon="fluent:shield-person-24-regular" class="w-5 h-5" />
            จัดการบทบาท
          </NuxtLink>
          <button 
            v-if="can('members.manage')"
            @click="showInviteModal = true"
            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors flex items-center gap-2"
          >
            <Icon icon="fluent:person-add-24-regular" class="w-5 h-5" />
            เชิญสมาชิก
          </button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div 
          @click="selectedStatus = null; onSearch()"
          class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 cursor-pointer hover:shadow-md transition-shadow"
          :class="{ 'ring-2 ring-primary-500': selectedStatus === null }"
        >
          <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
          <p class="text-sm text-gray-500">ทั้งหมด</p>
        </div>
        <div 
          @click="selectedStatus = 2; onSearch()"
          class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 cursor-pointer hover:shadow-md transition-shadow"
          :class="{ 'ring-2 ring-green-500': selectedStatus === 2 }"
        >
          <p class="text-2xl font-bold text-green-600">{{ stats.approved }}</p>
          <p class="text-sm text-gray-500">สมาชิก</p>
        </div>
        <div 
          @click="selectedStatus = 1; onSearch()"
          class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 cursor-pointer hover:shadow-md transition-shadow"
          :class="{ 'ring-2 ring-yellow-500': selectedStatus === 1 }"
        >
          <p class="text-2xl font-bold text-yellow-600">{{ stats.pending }}</p>
          <p class="text-sm text-gray-500">รอการอนุมัติ</p>
        </div>
        <div 
          @click="selectedStatus = 4; onSearch()"
          class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 cursor-pointer hover:shadow-md transition-shadow"
          :class="{ 'ring-2 ring-blue-500': selectedStatus === 4 }"
        >
          <p class="text-2xl font-bold text-blue-600">{{ stats.invited }}</p>
          <p class="text-sm text-gray-500">ได้รับเชิญ</p>
        </div>
        <div 
          @click="selectedStatus = 5; onSearch()"
          class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 cursor-pointer hover:shadow-md transition-shadow"
          :class="{ 'ring-2 ring-orange-500': selectedStatus === 5 }"
        >
          <p class="text-2xl font-bold text-orange-600">{{ stats.suspended }}</p>
          <p class="text-sm text-gray-500">ถูกระงับ</p>
        </div>
        <div 
          @click="selectedStatus = 3; onSearch()"
          class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 cursor-pointer hover:shadow-md transition-shadow"
          :class="{ 'ring-2 ring-red-500': selectedStatus === 3 }"
        >
          <p class="text-2xl font-bold text-red-600">{{ stats.rejected }}</p>
          <p class="text-sm text-gray-500">ถูกปฏิเสธ</p>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex flex-col md:flex-row gap-4">
          <div class="flex-1">
            <div class="relative">
              <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
              <input
                v-model="searchQuery"
                @input="onSearch"
                type="text"
                placeholder="ค้นหาชื่อ, อีเมล, รหัสสมาชิก..."
                class="w-full pl-10 pr-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
            </div>
          </div>
          <select
            v-model="selectedStatus"
            @change="onSearch"
            class="px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"
          >
            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
          <select
            v-model="selectedRole"
            @change="onSearch"
            class="px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"
          >
            <option v-for="opt in roleOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
          <!-- View Mode Toggle -->
          <div class="flex bg-gray-100 dark:bg-gray-700 p-1 rounded-lg">
            <button 
              @click="viewMode = 'card'"
              class="p-2 rounded-md transition-all"
              :class="viewMode === 'card' ? 'bg-white dark:bg-gray-600 text-primary-600 dark:text-primary-400 shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
              title="มุมมองการ์ด"
            >
              <Icon icon="fluent:grid-24-regular" class="w-5 h-5" />
            </button>
            <button 
              @click="viewMode = 'table'"
              class="p-2 rounded-md transition-all"
              :class="viewMode === 'table' ? 'bg-white dark:bg-gray-600 text-primary-600 dark:text-primary-400 shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
              title="มุมมองตาราง"
            >
              <Icon icon="fluent:table-24-regular" class="w-5 h-5" />
            </button>
          </div>
        </div>
      </div>

      <!-- Members List -->
      <div>
        <div v-if="isLoadingMembers" class="flex items-center justify-center py-16">
          <div class="animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent"></div>
        </div>

        <div v-else-if="members.length === 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 py-16 text-center">
          <Icon icon="fluent:people-24-regular" class="w-12 h-12 mx-auto text-gray-400 mb-4" />
          <p class="text-gray-500 dark:text-gray-400">ไม่พบสมาชิก</p>
        </div>

        <AcademyMemberMemberListView
          v-else
          :members="members"
          :view-mode="viewMode"
          :is-admin="can('members.manage')"
          @accept-member="acceptMember"
          @reject-member="rejectMember"
          @remove-member="removeMember"
          @edit-role="openRoleModal"
          @manage-member="openManageModal"
        />

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="flex items-center justify-between px-6 py-4 mt-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700">
          <p class="text-sm text-gray-500 dark:text-gray-400">
            แสดง {{ members.length }} จาก {{ pagination.total }} รายการ
          </p>
          <div class="flex items-center gap-2">
            <button
              @click="fetchMembers(pagination.current_page - 1)"
              :disabled="pagination.current_page === 1"
              class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              ก่อนหน้า
            </button>
            <span class="px-3 py-1.5 text-sm">
              หน้า {{ pagination.current_page }} / {{ pagination.last_page }}
            </span>
            <button
              @click="fetchMembers(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.last_page"
              class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              ถัดไป
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Role Modal -->
    <AcademyMemberMemberRoleModal
      v-model="showRoleModal"
      :member="selectedMember"
      :academy-id="academyId!"
      @role-assigned="onRoleAssigned"
    />

    <!-- Manage Modal -->
    <AcademyMemberMemberManageModal
      v-model="showManageModal"
      :member="selectedMember"
      :academy-id="academyId!"
      @member-updated="onMemberUpdated"
      @member-removed="onMemberUpdated"
      @member-suspended="onMemberUpdated"
    />

    <!-- Invite Modal -->
    <LazyInviteMemberModal
      v-model="showInviteModal"
      :academy-id="academyId!"
      @invited="fetchMembers(1); fetchStats()"
    />
  </NuxtLayout>
</template>
