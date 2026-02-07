<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'

// Types defined locally
interface AcademyRole {
  id: number
  name: string
  display_name_th: string
  display_name_en?: string
  color: string
  icon?: string
}

interface AcademyMember {
  id: number
  member_name: string
  member_avatar: string
  member_code?: string
  role?: string
  status: number
  status_text?: string
  academy_role_id?: number | null
  academy_role?: AcademyRole | null
  enrollment_date?: string
  created_at?: string
  user?: { email?: string } | null
  student?: { student_id: string } | null
}

interface Props {
  academyId: number
  isAdmin?: boolean
}

const props = defineProps<Props>()

const { $axios } = useNuxtApp() as { $axios: any }

// State
const members = ref<AcademyMember[]>([])
const loading = ref(false)
const searchQuery = ref('')
const selectedStatus = ref<number | null>(null)
const selectedRole = ref<string | null>(null)
const roles = ref<AcademyRole[]>([])
const pagination = ref({
  currentPage: 1,
  lastPage: 1,
  perPage: 20,
  total: 0,
})

// Modals
const showRoleModal = ref(false)
const showManageModal = ref(false)
const showInviteModal = ref(false)
const showImportModal = ref(false)
const selectedMember = ref<AcademyMember | null>(null)

// Stats
const stats = ref({
  total: 0,
  approved: 0,
  pending: 0,
  invited: 0,
  rejected: 0,
  suspended: 0,
})

const statusOptions = [
  { label: 'ทั้งหมด', value: null },
  { label: 'สมาชิก', value: 2 },
  { label: 'รอการอนุมัติ', value: 1 },
  { label: 'ได้รับเชิญ', value: 4 },
  { label: 'ถูกระงับ', value: 5 },
  { label: 'ถูกปฏิเสธ', value: 3 },
]

// Fetch members
async function fetchMembers() {
  loading.value = true
  try {
    const params: Record<string, any> = {
      page: pagination.value.currentPage,
      per_page: pagination.value.perPage,
    }
    
    if (searchQuery.value) params.search = searchQuery.value
    if (selectedStatus.value !== null) params.status = selectedStatus.value
    if (selectedRole.value) params.role = selectedRole.value

    const response = await $axios.get(`/academies/${props.academyId}/members/search`, { params })
    
    if (response.data.success) {
      members.value = response.data.members
      pagination.value = {
        currentPage: response.data.pagination.current_page,
        lastPage: response.data.pagination.last_page,
        perPage: response.data.pagination.per_page,
        total: response.data.pagination.total,
      }
    }
  } catch (error) {
    console.error('Error fetching members:', error)
  } finally {
    loading.value = false
  }
}

// Fetch stats
async function fetchStats() {
  try {
    const response = await $axios.get(`/academies/${props.academyId}/members/stats`)
    if (response.data.success) {
      stats.value = response.data.stats
    }
  } catch (error) {
    console.error('Error fetching stats:', error)
  }
}

// Fetch roles
async function fetchRoles() {
  try {
    const response = await $axios.get(`/academies/${props.academyId}/roles/available`)
    if (response.data.success) {
      roles.value = response.data.roles
    }
  } catch (error) {
    console.error('Error fetching roles:', error)
  }
}

// Watchers for filtering
watch([searchQuery, selectedStatus, selectedRole], () => {
  pagination.value.currentPage = 1
  fetchMembers()
}, { debounce: 300 } as any)

// Modal handlers
function openRoleModal(member: AcademyMember) {
  selectedMember.value = member
  showRoleModal.value = true
}

function openManageModal(member: AcademyMember) {
  selectedMember.value = member
  showManageModal.value = true
}

function handleRoleAssigned(updatedMember: AcademyMember) {
  const index = members.value.findIndex(m => m.id === updatedMember.id)
  if (index !== -1) {
    members.value[index] = updatedMember
  }
}

function handleMemberUpdated(updatedMember: AcademyMember) {
  const index = members.value.findIndex(m => m.id === updatedMember.id)
  if (index !== -1) {
    members.value[index] = updatedMember
  }
  fetchStats()
}

function handleMemberRemoved(memberId: number) {
  members.value = members.value.filter(m => m.id !== memberId)
  fetchStats()
}

function handleMemberInvited() {
  fetchMembers()
  fetchStats()
}

function getStatusColor(status: number): string {
  const colors: Record<number, string> = {
    1: 'yellow',
    2: 'green',
    3: 'red',
    4: 'blue',
    5: 'orange',
  }
  return colors[status] || 'gray'
}

function getRoleColor(role?: AcademyRole): string {
  return role?.color || 'gray'
}

// Pagination
function changePage(page: number) {
  pagination.value.currentPage = page
  fetchMembers()
}

onMounted(() => {
  fetchMembers()
  fetchStats()
  fetchRoles()
})

// Export members
function exportMembers() {
  window.location.href = `/api/academies/${props.academyId}/members/export`
}
</script>

<template>
  <div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">ทั้งหมด</p>
      </div>
      <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.approved }}</p>
        <p class="text-sm text-green-600 dark:text-green-400">สมาชิก</p>
      </div>
      <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 border border-yellow-200 dark:border-yellow-800">
        <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ stats.pending }}</p>
        <p class="text-sm text-yellow-600 dark:text-yellow-400">รอการอนุมัติ</p>
      </div>
      <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ stats.invited }}</p>
        <p class="text-sm text-blue-600 dark:text-blue-400">ได้รับเชิญ</p>
      </div>
      <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4 border border-orange-200 dark:border-orange-800">
        <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ stats.suspended }}</p>
        <p class="text-sm text-orange-600 dark:text-orange-400">ถูกระงับ</p>
      </div>
      <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-800">
        <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ stats.rejected }}</p>
        <p class="text-sm text-red-600 dark:text-red-400">ถูกปฏิเสธ</p>
      </div>
    </div>

    <!-- Filters & Actions -->
    <div class="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
      <div class="flex flex-wrap gap-3">
        <UInput
          v-model="searchQuery"
          icon="i-heroicons-magnifying-glass"
          placeholder="ค้นหาชื่อ, อีเมล, รหัส..."
          class="w-64"
        />
        <USelect
          v-model="selectedStatus"
          :options="statusOptions"
          option-attribute="label"
          value-attribute="value"
          placeholder="สถานะ"
          class="w-40"
        />
        <USelect
          v-model="selectedRole"
          :options="[{ name: 'ทุกบทบาท', display_name_th: 'ทุกบทบาท' }, ...roles]"
          option-attribute="display_name_th"
          value-attribute="name"
          placeholder="บทบาท"
          class="w-40"
        />
      </div>

      <div v-if="isAdmin" class="flex gap-2">
        <UButton
          icon="i-heroicons-arrow-down-tray"
          color="gray"
          variant="soft"
          @click="exportMembers"
        >
          <span class="hidden sm:inline">ส่งออก</span>
        </UButton>
        <UButton
          icon="i-heroicons-arrow-up-tray"
          color="gray"
          variant="soft"
          @click="showImportModal = true"
        >
          <span class="hidden sm:inline">นำเข้า</span>
        </UButton>
        <UButton
          icon="i-heroicons-user-plus"
          color="primary"
          @click="showInviteModal = true"
        >
          เชิญสมาชิก
        </UButton>
      </div>
    </div>

    <!-- Members View -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div v-if="loading" class="flex justify-center items-center py-12">
        <UIcon name="i-heroicons-arrow-path" class="w-8 h-8 animate-spin text-primary-500" />
      </div>

      <template v-else>
        <!-- Mobile Card View -->
        <div class="block md:hidden divide-y divide-gray-200 dark:divide-gray-700">
          <div 
            v-for="member in members" 
            :key="'card-' + member.id"
            class="p-4 hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors"
          >
            <div class="flex items-start gap-3">
              <UAvatar
                :src="member.member_avatar"
                :alt="member.member_name"
                size="md"
              />
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2">
                  <p class="font-medium text-gray-900 dark:text-white truncate">
                    {{ member.member_name }}
                  </p>
                  <UBadge :color="getStatusColor(member.status)" size="xs">
                    {{ member.status_text }}
                  </UBadge>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">
                  {{ member.user?.email || member.member_code || '-' }}
                </p>
                <div class="flex items-center gap-2 mt-2">
                  <UBadge 
                    v-if="member.academy_role"
                    :color="getRoleColor(member.academy_role)"
                    size="xs"
                  >
                    {{ member.academy_role.display_name_th }}
                  </UBadge>
                  <span class="text-xs text-gray-400">
                    {{ member.enrollment_date || member.created_at?.split(' ')[0] || '' }}
                  </span>
                </div>
              </div>
              <!-- Mobile Actions -->
              <div v-if="isAdmin" class="flex flex-col gap-1">
                <UButton
                  icon="i-heroicons-user-circle"
                  color="gray"
                  variant="ghost"
                  size="xs"
                  @click="openRoleModal(member)"
                />
                <UButton
                  icon="i-heroicons-cog-6-tooth"
                  color="gray"
                  variant="ghost"
                  size="xs"
                  @click="openManageModal(member)"
                />
              </div>
            </div>
          </div>
          
          <div v-if="members.length === 0" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
            <UIcon name="i-heroicons-users" class="w-12 h-12 mx-auto mb-4 opacity-50" />
            <p>ไม่พบสมาชิก</p>
          </div>
        </div>

        <!-- Desktop Table View -->
        <table class="hidden md:table w-full">
          <thead class="bg-gray-50 dark:bg-gray-900">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                สมาชิก
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                รหัส
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                บทบาท
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                สถานะ
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                วันที่เข้าร่วม
              </th>
              <th v-if="isAdmin" class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                การจัดการ
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr 
              v-for="member in members" 
              :key="member.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors"
            >
              <td class="px-4 py-4">
                <div class="flex items-center gap-3">
                  <UAvatar
                    :src="member.member_avatar"
                    :alt="member.member_name"
                    size="sm"
                  />
                  <div>
                    <p class="font-medium text-gray-900 dark:text-white">
                      {{ member.member_name }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                      {{ member.user?.email || '-' }}
                    </p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">
                {{ member.member_code || member.student?.student_id || '-' }}
              </td>
              <td class="px-4 py-4">
                <UBadge 
                  v-if="member.academy_role"
                  :color="getRoleColor(member.academy_role)"
                  size="sm"
                >
                  {{ member.academy_role.display_name_th }}
                </UBadge>
                <span v-else class="text-sm text-gray-500">{{ member.role || '-' }}</span>
              </td>
              <td class="px-4 py-4">
                <UBadge :color="getStatusColor(member.status)" size="sm">
                  {{ member.status_text }}
                </UBadge>
              </td>
              <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">
                {{ member.enrollment_date || member.created_at?.split(' ')[0] || '-' }}
              </td>
              <td v-if="isAdmin" class="px-4 py-4">
                <div class="flex justify-end gap-2">
                  <UButton
                    icon="i-heroicons-user-circle"
                    color="gray"
                    variant="ghost"
                    size="xs"
                    title="กำหนดบทบาท"
                    @click="openRoleModal(member)"
                  />
                  <UButton
                    icon="i-heroicons-cog-6-tooth"
                    color="gray"
                    variant="ghost"
                    size="xs"
                    title="จัดการสมาชิก"
                    @click="openManageModal(member)"
                  />
                </div>
              </td>
            </tr>

            <tr v-if="members.length === 0">
              <td :colspan="isAdmin ? 6 : 5" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                <UIcon name="i-heroicons-users" class="w-12 h-12 mx-auto mb-4 opacity-50" />
                <p>ไม่พบสมาชิก</p>
              </td>
            </tr>
          </tbody>
        </table>
      </template>

      <!-- Pagination -->
      <div v-if="pagination.total > pagination.perPage" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
        <UPagination
          v-model="pagination.currentPage"
          :page-count="pagination.perPage"
          :total="pagination.total"
          @update:model-value="changePage"
        />
      </div>
    </div>

    <!-- Modals -->
    <AcademyMemberMemberRoleModal
      v-model="showRoleModal"
      :member="selectedMember"
      :academy-id="academyId"
      @role-assigned="handleRoleAssigned"
    />

    <AcademyMemberMemberManageModal
      v-model="showManageModal"
      :member="selectedMember"
      :academy-id="academyId"
      @member-updated="handleMemberUpdated"
      @member-removed="handleMemberRemoved"
      @member-suspended="handleMemberUpdated"
    />

    <AcademyInviteMemberModal
      v-model="showInviteModal"
      :academy-id="academyId"
      @member-invited="handleMemberInvited"
    />

    <AcademyMemberMemberImportModal
      v-model="showImportModal"
      :academy-id="academyId"
      @imported="() => { fetchMembers(); fetchStats(); }"
    />
  </div>
</template>
