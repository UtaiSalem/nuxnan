<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'nuxnan-admin-layout',
  middleware: 'nuxnan-admin'
})

const config = useRuntimeConfig()
const apiBase = config.public.apiBase as string

// ─── State ────────────────────────────────────────────────────
const users       = ref<any[]>([])
const isLoading   = ref(true)
const searchQuery = ref('')
const selectedRole  = ref('all')
const selectedScope = ref('active')   // active | deleted | all
const selectedStatus = ref('all')  // all | pending | verified
const currentPage = ref(1)
const totalPages  = ref(1)
const perPage     = ref(10)
const totalUsers  = ref(0)
const totalPendingUsers = ref(0)
const toast       = ref<{ type: 'success' | 'error'; message: string } | null>(null)

// ─── Delete modal state ───────────────────────────────────────
const showDeleteModal   = ref(false)
const userToDelete      = ref<any>(null)
const deleteReason      = ref('')
const isLoadingImpact   = ref(false)
const isDeleting        = ref(false)
const deleteImpact      = ref<any>(null)
const impactError       = ref('')
const warningAcknowledged = ref(false)

// ─── Verify modal state ───────────────────────────────────────
const showVerifyModal = ref(false)
const userToVerify    = ref<any>(null)
const verifyAction    = ref<'verify' | 'unverify'>('verify')
const verifyNote      = ref('')
const isVerifying     = ref(false)

// ─── Edit modal state ─────────────────────────────────────────
const showEditModal = ref(false)
const userToEdit = ref<any>(null)
const isLoadingEdit = ref(false)
const isSavingEdit = ref(false)
const editErrors = ref<Record<string, string>>({})
const originalEditUsername = ref('')

const editForm = reactive({
  username: '',
  name: '',
  email: '',
  phone_number: '',
  password: '',
  password_confirmation: '',
  role: 'student',
  is_super_admin: false,
  is_plearnd_admin: false,
  status: 'active',
})

// ─── Bulk Select state ────────────────────────────────────────
const selectedUserIds = ref<number[]>([])
const isAllSelected   = computed(() => users.value.length > 0 && selectedUserIds.value.length === users.value.length)

// ─── Computed ─────────────────────────────────────────────────
const hasBlockers = computed(() =>
  deleteImpact.value && deleteImpact.value.blockers?.length > 0
)
const hasWarnings = computed(() =>
  deleteImpact.value && deleteImpact.value.warnings?.length > 0
)
const canConfirmDelete = computed(() => {
  if (!deleteImpact.value || hasBlockers.value) return false
  if (deleteReason.value.trim().length < 5) return false
  if (hasWarnings.value && !warningAcknowledged.value) return false
  return true
})

const roles = [
  { value: 'all', label: 'ทั้งหมด' },
  { value: 'student', label: 'ผู้ใช้ทั่วไป' },
  { value: 'instructor', label: 'ผู้สอน' },
  { value: 'admin', label: 'ผู้ดูแล' },
]
const editableRoles = roles.filter(role => role.value !== 'all')
const scopes = [
  { value: 'active',  label: 'ใช้งานอยู่' },
  { value: 'deleted', label: 'ถูกปิดใช้งาน' },
  { value: 'all',     label: 'ทั้งหมด' },
]
const statusOptions = [
  { value: 'all',     label: 'ทุกสถานะ' },
  { value: 'pending', label: 'รอยืนยัน' },
  { value: 'verified', label: 'ยืนยันแล้ว' },
]
const editStatuses = [
  { value: 'active', label: 'เปิดใช้งาน' },
  { value: 'inactive', label: 'ยังไม่ยืนยัน' },
  { value: 'suspended', label: 'ระงับการใช้งาน' },
]

// ─── API helper ───────────────────────────────────────────────
function getHeaders() {
  const token = useCookie('token')
  return { Authorization: `Bearer ${token.value}` }
}

function showToast(type: 'success' | 'error', message: string) {
  toast.value = { type, message }
  setTimeout(() => { toast.value = null }, 4000)
}

const normalizeErrors = (validationErrors: Record<string, string | string[]>) => {
  return Object.fromEntries(
    Object.entries(validationErrors).map(([field, message]) => [
      field,
      Array.isArray(message) ? message[0] : message,
    ])
  ) as Record<string, string>
}

// ─── Fetch users ──────────────────────────────────────────────
const fetchUsers = async () => {
  isLoading.value = true
  try {
    const params = new URLSearchParams({
      page:     currentPage.value.toString(),
      per_page: perPage.value.toString(),
      scope:    selectedScope.value,
      status:   selectedStatus.value,
      ...(searchQuery.value && { search: searchQuery.value }),
      ...(selectedRole.value !== 'all' && { role: selectedRole.value }),
    })
    const response: any = await $fetch(`${apiBase}/api/admin/users?${params}`, {
      headers: getHeaders(),
    })
    if (response.success) {
      users.value      = response.data.data || response.data
      totalPages.value = response.meta?.last_page || response.data.last_page || 1
      totalUsers.value = response.meta?.total || response.data.total || users.value.length
      totalPendingUsers.value = response.meta?.total_pending || 0
    }
  } catch (e: any) {
    showToast('error', 'โหลดข้อมูลล้มเหลว: ' + (e?.data?.message || e.message))
  } finally {
    isLoading.value = false
  }
}

const handleSearch = () => { currentPage.value = 1; fetchUsers() }
const handleFilter = () => { currentPage.value = 1; fetchUsers() }
const goToPage = (p: number) => { currentPage.value = p; fetchUsers() }

// ─── Verify flow ──────────────────────────────────────────────
const openVerifyModal = (user: any) => {
  userToVerify.value = user
  verifyAction.value = 'verify'
  verifyNote.value = ''
  showVerifyModal.value = true
}

const openUnverifyModal = (user: any) => {
  userToVerify.value = user
  verifyAction.value = 'unverify'
  verifyNote.value = ''
  showVerifyModal.value = true
}

const confirmVerify = async () => {
  if (!userToVerify.value || isVerifying.value) return
  
  isVerifying.value = true
  const endpoint = verifyAction.value === 'verify' ? 'verify-email' : 'unverify-email'
  
  try {
    const res: any = await $fetch(`${apiBase}/api/admin/users/${userToVerify.value.id}/${endpoint}`, {
      method: 'POST',
      headers: getHeaders(),
    })
    showToast('success', res.message || 'ดำเนินการสำเร็จ')
    showVerifyModal.value = false
    fetchUsers()
  } catch (e: any) {
    showToast('error', e?.data?.message || 'เกิดข้อผิดพลาด')
  } finally {
    isVerifying.value = false
  }
}

const bulkVerify = async () => {
  if (!selectedUserIds.value.length) return
  if (!confirm(`ยืนยันบัญชี ${selectedUserIds.value.length} คน?`)) return
  try {
    const res: any = await $fetch(`${apiBase}/api/admin/users/bulk-verify`, {
      method: 'POST', 
      headers: getHeaders(),
      body: { user_ids: selectedUserIds.value }
    })
    showToast('success', res.message)
    selectedUserIds.value = []
    fetchUsers()
  } catch (e: any) {
    showToast('error', e?.data?.message || 'เกิดข้อผิดพลาด')
  }
}

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    selectedUserIds.value = []
  } else {
    selectedUserIds.value = users.value.map(u => u.id)
  }
}

const toggleSelectUser = (id: number) => {
  const index = selectedUserIds.value.indexOf(id)
  if (index === -1) {
    selectedUserIds.value.push(id)
  } else {
    selectedUserIds.value.splice(index, 1)
  }
}

// ─── Edit flow ────────────────────────────────────────────────
const resetEditForm = () => {
  originalEditUsername.value = ''
  editForm.username = ''
  editForm.name = ''
  editForm.email = ''
  editForm.phone_number = ''
  editForm.password = ''
  editForm.password_confirmation = ''
  editForm.role = 'student'
  editForm.is_super_admin = false
  editForm.is_plearnd_admin = false
  editForm.status = 'active'
  editErrors.value = {}
}

const normalizeEditStatus = (user: any) => {
  if (user.deleted_at || user.status === 'suspended') return 'suspended'
  if (user.status === 'inactive' || user.status === 'unverified' || user.is_verified === false) return 'inactive'
  return 'active'
}

const fillEditForm = (user: any) => {
  userToEdit.value = user
  originalEditUsername.value = user.username || ''
  editForm.username = originalEditUsername.value || user.name || ''
  editForm.name = user.name || ''
  editForm.email = user.email || ''
  editForm.phone_number = user.phone_number || ''
  editForm.role = user.role ?? (user.roles?.[0]?.name?.toLowerCase() ?? 'student')
  editForm.is_super_admin = Boolean(user.is_super_admin)
  editForm.is_plearnd_admin = Boolean(user.is_plearnd_admin)
  editForm.status = normalizeEditStatus(user)
}

const openEditModal = async (user: any) => {
  resetEditForm()
  showEditModal.value = true
  isLoadingEdit.value = true
  fillEditForm(user)

  try {
    const response: any = await $fetch(`${apiBase}/api/admin/users/${user.id}`, {
      headers: getHeaders(),
    })

    if (response.success) {
      fillEditForm(response.data)
    }
  } catch (e: any) {
    showToast('error', e?.data?.message || 'โหลดข้อมูลผู้ใช้ล้มเหลว')
  } finally {
    isLoadingEdit.value = false
  }
}

const closeEditModal = () => {
  if (isSavingEdit.value) return
  showEditModal.value = false
  userToEdit.value = null
  resetEditForm()
}

const validateEditForm = () => {
  editErrors.value = {}

  if (!editForm.username.trim()) {
    editErrors.value.username = 'กรุณากรอกชื่อผู้ใช้'
  }

  if (!editForm.name.trim()) {
    editErrors.value.name = 'กรุณากรอกชื่อ'
  }

  if (!editForm.email.trim()) {
    editErrors.value.email = 'กรุณากรอกอีเมล'
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(editForm.email)) {
    editErrors.value.email = 'รูปแบบอีเมลไม่ถูกต้อง'
  }

  if (editForm.password && editForm.password.length < 6) {
    editErrors.value.password = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร'
  }

  if (editForm.password && editForm.password !== editForm.password_confirmation) {
    editErrors.value.password_confirmation = 'รหัสผ่านไม่ตรงกัน'
  }

  return Object.keys(editErrors.value).length === 0
}

const saveEditUser = async () => {
  if (!userToEdit.value || isSavingEdit.value || !validateEditForm()) return

  isSavingEdit.value = true
  editErrors.value = {}

  const data: Record<string, any> = {
    name: editForm.name.trim(),
    email: editForm.email.trim(),
    phone_number: editForm.phone_number.trim(),
    role: editForm.role,
    is_super_admin: editForm.is_super_admin,
    is_plearnd_admin: editForm.is_plearnd_admin,
    status: editForm.status,
  }

  const nextUsername = editForm.username.trim()
  if (nextUsername !== originalEditUsername.value) {
    data.username = nextUsername
  }

  if (editForm.password) {
    data.password = editForm.password
    data.password_confirmation = editForm.password_confirmation
  }

  try {
    const response: any = await $fetch(`${apiBase}/api/admin/users/${userToEdit.value.id}`, {
      method: 'PUT',
      headers: getHeaders(),
      body: data,
    })

    showToast('success', response.message || 'อัปเดตผู้ใช้สำเร็จ')
    showEditModal.value = false
    userToEdit.value = null
    resetEditForm()
    fetchUsers()
  } catch (e: any) {
    if (e?.data?.errors) {
      editErrors.value = normalizeErrors(e.data.errors)
    } else {
      editErrors.value.general = e?.data?.message || 'บันทึกข้อมูลผู้ใช้ล้มเหลว'
    }
  } finally {
    isSavingEdit.value = false
  }
}

// ─── Delete flow ──────────────────────────────────────────────
const openDeleteModal = async (user: any) => {
  userToDelete.value      = user
  deleteReason.value      = ''
  deleteImpact.value      = null
  impactError.value       = ''
  warningAcknowledged.value = false
  showDeleteModal.value   = true

  // โหลด impact ทันทีที่เปิด modal
  isLoadingImpact.value = true
  try {
    const res: any = await $fetch(
      `${apiBase}/api/admin/users/${user.id}/delete-impact`,
      { headers: getHeaders() }
    )
    deleteImpact.value = res.data
  } catch (e: any) {
    impactError.value = 'โหลดข้อมูลผลกระทบล้มเหลว: ' + (e?.data?.message || e.message)
  } finally {
    isLoadingImpact.value = false
  }
}

const closeDeleteModal = () => {
  if (isDeleting.value) return   // ห้ามปิดระหว่าง request
  showDeleteModal.value = false
  userToDelete.value    = null
}

const confirmDelete = async () => {
  if (!canConfirmDelete.value || isDeleting.value) return

  isDeleting.value = true
  try {
    const res: any = await $fetch(
      `${apiBase}/api/admin/users/${userToDelete.value.id}`,
      {
        method:  'DELETE',
        headers: getHeaders(),
        body:    { reason: deleteReason.value },
      }
    )
    showDeleteModal.value = false
    showToast('success', res.message || 'ปิดใช้งานบัญชีสำเร็จ')
    fetchUsers()
  } catch (e: any) {
    const msg = e?.data?.message || 'เกิดข้อผิดพลาด'
    const blockers = e?.data?.blockers
    if (blockers) {
      // อัพเดท impact เพื่อแสดง blocker ใหม่ (กรณี race)
      impactError.value = msg
    } else {
      showToast('error', msg)
      closeDeleteModal()
    }
  } finally {
    isDeleting.value = false
  }
}

// ─── Restore ──────────────────────────────────────────────────
const restoreUser = async (user: any) => {
  if (!confirm(`กู้คืนบัญชี "${user.name}" ใช่หรือไม่?\n\nหมายเหตุ: email/ชื่ออาจถูก anonymize ไปแล้ว ต้องอัพเดทเองหลังกู้คืน`)) return
  try {
    const res: any = await $fetch(
      `${apiBase}/api/admin/users/${user.id}/restore`,
      { method: 'POST', headers: getHeaders() }
    )
    showToast('success', res.message)
    fetchUsers()
  } catch (e: any) {
    showToast('error', e?.data?.message || 'กู้คืนล้มเหลว')
  }
}

const getStatusLabel = (user: any) => {
  if (user.deleted_at) return 'ถูกปิดใช้งาน'
  if (!user.is_verified) return 'รอยืนยัน'
  return 'ยืนยันแล้ว'
}

const getStatusBadge = (user: any) => {
  if (user.deleted_at) return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
  if (!user.is_verified) return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'
  return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
}

const getRoleBadge = (role: string) => {
  const badges: Record<string, string> = {
    admin:      'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    instructor: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    student:    'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
  }
  return badges[role] ?? badges.student
}

const formatDate = (dateStr: string | null | undefined) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  if (Number.isNaN(date.getTime())) return '-'
  return date.toLocaleDateString('th-TH')
}

onMounted(fetchUsers)
</script>

<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <div class="flex items-center gap-3">
          <h1 class="text-2xl font-bold text-slate-800 dark:text-white">จัดการผู้ใช้</h1>
          <span v-if="totalPendingUsers > 0" class="px-2 py-0.5 bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 rounded-full text-xs font-medium">
            {{ totalPendingUsers }} รอยืนยัน
          </span>
        </div>
        <p class="text-slate-500 dark:text-slate-400 mt-1">
          ผู้ใช้งานทั้งหมด {{ totalUsers.toLocaleString() }} คน
        </p>
      </div>
      <NuxtLink
        to="/nuxnan-admin/users/create"
        class="inline-flex items-center gap-2 px-4 py-2 bg-hopeui-primary-500 hover:bg-hopeui-primary-600 rounded-xl text-white transition-colors"
      >
        <Icon icon="fluent:person-add-24-regular" class="w-5 h-5" />
        เพิ่มผู้ใช้ใหม่
      </NuxtLink>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-hopeui border border-slate-100 dark:border-slate-700">
      <div class="flex flex-col sm:flex-row gap-4">
        <!-- Search -->
        <div class="flex-1 relative">
          <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="ค้นหาผู้ใช้..."
            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500 focus:border-transparent"
            @keyup.enter="handleSearch"
          />
        </div>

        <!-- Status Filter -->
        <select
          v-model="selectedStatus"
          class="px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500"
          @change="handleFilter"
        >
          <option v-for="status in statusOptions" :key="status.value" :value="status.value">
            {{ status.label }}
          </option>
        </select>

        <!-- Role Filter -->
        <select
          v-model="selectedRole"
          class="px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500"
          @change="handleFilter"
        >
          <option v-for="role in roles" :key="role.value" :value="role.value">
            {{ role.label }}
          </option>
        </select>

        <!-- Scope Filter -->
        <select
          v-model="selectedScope"
          class="px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500"
          @change="handleFilter"
        >
          <option v-for="s in scopes" :key="s.value" :value="s.value">
            {{ s.label }}
          </option>
        </select>

        <!-- Search Button -->
        <button
          @click="handleSearch"
          class="min-h-[44px] sm:min-h-0 px-4 py-2.5 bg-hopeui-primary-500 hover:bg-hopeui-primary-600 rounded-xl text-white transition-colors"
        >
          ค้นหา
        </button>
      </div>
    </div>

    <!-- Bulk Actions -->
    <div v-if="selectedUserIds.length > 0" class="flex items-center justify-between gap-3 p-4 bg-hopeui-primary-100 dark:bg-hopeui-primary-900/20 rounded-2xl border border-hopeui-primary-100 dark:border-hopeui-primary-900 shadow-hopeui">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-hopeui-primary-100 dark:bg-hopeui-primary-900/40 rounded-full flex items-center justify-center">
          <Icon icon="fluent:select-all-24-regular" class="w-5 h-5 text-hopeui-primary-600 dark:text-hopeui-primary-500" />
        </div>
        <span class="text-sm font-medium text-hopeui-primary-600 dark:text-hopeui-primary-500">เลือก {{ selectedUserIds.length }} คน</span>
      </div>
      <div class="flex items-center gap-2">
        <button 
          @click="bulkVerify" 
          class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-xl transition-colors flex items-center gap-2"
        >
          <Icon icon="fluent:checkmark-circle-24-regular" class="w-4 h-4" />
          ยืนยันบัญชีที่เลือก
        </button>
        <button 
          @click="selectedUserIds = []" 
          class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-sm font-medium rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
        >
          ยกเลิก
        </button>
      </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-hopeui border border-slate-100 dark:border-slate-700 overflow-hidden">
      <!-- Loading State -->
      <div v-if="isLoading" class="p-8 text-center">
        <Icon icon="fluent:spinner-ios-20-regular" class="w-8 h-8 text-hopeui-primary-600 animate-spin mx-auto" />
        <p class="text-slate-500 mt-2">กำลังโหลดข้อมูล...</p>
      </div>

      <!-- Table -->
      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-slate-50 dark:bg-slate-700/50">
            <tr>
              <th class="px-6 py-4 text-left">
                <input
                  type="checkbox"
                  :checked="isAllSelected"
                  class="rounded border-slate-300 text-hopeui-primary-600 focus:ring-hopeui-primary-500"
                  @change="toggleSelectAll"
                />
              </th>
              <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                ผู้ใช้
              </th>
              <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                อีเมล
              </th>
              <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                บทบาท
              </th>
              <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                สถานะ
              </th>
              <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                วันที่สมัคร
              </th>
              <th class="px-6 py-4 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                จัดการ
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
            <tr
              v-for="user in users"
              :key="user.id"
              class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors"
              :class="{ 'bg-hopeui-primary-100/50 dark:bg-hopeui-primary-900/10': selectedUserIds.includes(user.id) }"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <input
                  type="checkbox"
                  :checked="selectedUserIds.includes(user.id)"
                  class="rounded border-slate-300 text-hopeui-primary-600 focus:ring-hopeui-primary-500"
                  @change="toggleSelectUser(user.id)"
                />
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center gap-3">
                  <img
                    :src="user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=3a57e8&color=fff`"
                    :alt="user.name"
                    class="w-10 h-10 rounded-full object-cover"
                  />
                  <div>
                    <p class="font-medium text-slate-800 dark:text-white">{{ user.name }}</p>
                    <p class="text-xs text-hopeui-primary-600 dark:text-hopeui-primary-500 font-semibold">@{{ user.username }}</p>
                    <p class="text-xs text-slate-500">ID: {{ user.id }}</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-slate-600 dark:text-slate-300">{{ user.email }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="[getRoleBadge(user.role), 'px-2.5 py-1 rounded-full text-xs font-medium']">
                  {{ user.role || 'user' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="[getStatusBadge(user), 'px-2.5 py-1 rounded-full text-xs font-medium']">
                  {{ getStatusLabel(user) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-slate-600 dark:text-slate-300">
                  {{ formatDate(user.created_at) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right">
                <div class="flex items-center justify-end gap-1">
                  <!-- Quick Verify -->
                  <button
                    v-if="!user.is_verified && !user.deleted_at"
                    @click="openVerifyModal(user)"
                    class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 text-slate-500 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/30 rounded-lg transition-colors"
                    title="ยืนยันบัญชี"
                  >
                    <Icon icon="fluent:checkmark-circle-24-regular" class="w-5 h-5" />
                  </button>
                  <button
                    v-if="user.is_verified && !user.deleted_at"
                    @click="openUnverifyModal(user)"
                    class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 text-slate-500 hover:text-yellow-600 hover:bg-yellow-50 dark:hover:bg-yellow-900/30 rounded-lg transition-colors"
                    title="ถอนการยืนยัน"
                  >
                    <Icon icon="fluent:dismiss-circle-24-regular" class="w-5 h-5" />
                  </button>

                  <div class="w-px h-4 bg-slate-200 dark:bg-slate-700 mx-1" />

                  <NuxtLink
                    :to="`/nuxnan-admin/users/${user.id}`"
                    class="p-2 text-slate-500 hover:text-hopeui-primary-600 hover:bg-hopeui-primary-100 dark:hover:bg-hopeui-primary-900/30 rounded-lg transition-colors"
                    title="ดูรายละเอียด"
                  >
                    <Icon icon="fluent:eye-24-regular" class="w-5 h-5" />
                  </NuxtLink>
                  <button
                    @click="openEditModal(user)"
                    class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 text-slate-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
                    title="แก้ไข"
                  >
                    <Icon icon="fluent:edit-24-regular" class="w-5 h-5" />
                  </button>
                  <button
                    @click="openDeleteModal(user)"
                    class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 text-slate-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                    title="ลบ"
                  >
                    <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
                  </button>
                  <button
                    v-if="user.deleted_at"
                    @click="restoreUser(user)"
                    class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 text-slate-500 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/30 rounded-lg transition-colors"
                    title="กู้คืนบัญชี"
                  >
                    <Icon icon="fluent:arrow-undo-24-regular" class="w-5 h-5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty State -->
        <div v-if="users.length === 0" class="p-8 text-center">
          <Icon icon="fluent:people-24-regular" class="w-12 h-12 text-slate-300 mx-auto" />
          <p class="text-slate-500 mt-2">ไม่พบผู้ใช้งาน</p>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="px-6 py-4 border-t border-slate-100 dark:border-slate-700 flex items-center justify-between">
        <p class="text-sm text-slate-500">
          แสดง {{ (currentPage - 1) * perPage + 1 }} - {{ Math.min(currentPage * perPage, totalUsers) }} จาก {{ totalUsers }} รายการ
        </p>
        <div class="flex items-center gap-2">
          <button
            :disabled="currentPage === 1"
            @click="goToPage(currentPage - 1)"
            class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 rounded-lg border border-slate-200 dark:border-slate-600 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
          >
            <Icon icon="fluent:chevron-left-24-regular" class="w-5 h-5" />
          </button>
          <span class="px-3 py-1.5 text-sm text-slate-600 dark:text-slate-300">
            หน้า {{ currentPage }} / {{ totalPages }}
          </span>
          <button
            :disabled="currentPage === totalPages"
            @click="goToPage(currentPage + 1)"
            class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 rounded-lg border border-slate-200 dark:border-slate-600 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
          >
            <Icon icon="fluent:chevron-right-24-regular" class="w-5 h-5" />
          </button>
        </div>
      </div>
    </div>

    <!-- Toast Notification -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="toast"
          class="fixed top-6 right-6 z-[100] flex items-center gap-3 px-5 py-3.5 rounded-xl shadow-lg text-white text-sm font-medium"
          :class="toast.type === 'success' ? 'bg-green-600' : 'bg-red-600'"
        >
          <Icon :icon="toast.type === 'success' ? 'fluent:checkmark-circle-24-filled' : 'fluent:dismiss-circle-24-filled'" class="w-5 h-5 flex-shrink-0" />
          {{ toast.message }}
        </div>
      </Transition>
    </Teleport>

    <!-- Edit User Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showEditModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
          <div class="absolute inset-0 bg-black/50" @click="closeEditModal" />
          <form
            class="relative bg-white dark:bg-slate-800 rounded-2xl w-full max-w-2xl shadow-xl flex flex-col max-h-[90vh]"
            @submit.prevent="saveEditUser"
          >
            <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex items-start justify-between gap-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                  <Icon icon="fluent:edit-24-regular" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                  <h3 class="text-base font-semibold text-slate-800 dark:text-white">แก้ไขผู้ใช้</h3>
                  <p class="text-sm text-slate-500">{{ userToEdit?.name || 'กำลังโหลดข้อมูล...' }}</p>
                </div>
              </div>
              <button
                type="button"
                class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                :disabled="isSavingEdit"
                @click="closeEditModal"
              >
                <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5" />
              </button>
            </div>

            <div class="overflow-y-auto p-6 space-y-5 flex-1">
              <div v-if="isLoadingEdit" class="flex items-center justify-center py-8 gap-3 text-slate-500">
                <Icon icon="fluent:spinner-ios-20-regular" class="w-6 h-6 animate-spin" />
                <span>กำลังโหลดข้อมูลผู้ใช้...</span>
              </div>

              <template v-else>
                <div v-if="editErrors.general" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl text-red-600 dark:text-red-400 text-sm">
                  {{ editErrors.general }}
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                  <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                      ชื่อผู้ใช้ (Username) <span class="text-red-500">*</span>
                    </label>
                    <input
                      v-model="editForm.username"
                      type="text"
                      class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500 focus:border-transparent"
                      :class="{ 'border-red-500': editErrors.username }"
                      placeholder="เช่น nuxnan_user"
                    />
                    <p v-if="editErrors.username" class="mt-1 text-sm text-red-500">{{ editErrors.username }}</p>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                      ชื่อแสดงผล <span class="text-red-500">*</span>
                    </label>
                    <input
                      v-model="editForm.name"
                      type="text"
                      class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500 focus:border-transparent"
                      :class="{ 'border-red-500': editErrors.name }"
                      placeholder="ชื่อที่ต้องการให้แสดง"
                    />
                    <p v-if="editErrors.name" class="mt-1 text-sm text-red-500">{{ editErrors.name }}</p>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                      อีเมล <span class="text-red-500">*</span>
                    </label>
                    <input
                      v-model="editForm.email"
                      type="email"
                      class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500 focus:border-transparent"
                      :class="{ 'border-red-500': editErrors.email }"
                      placeholder="email@example.com"
                    />
                    <p v-if="editErrors.email" class="mt-1 text-sm text-red-500">{{ editErrors.email }}</p>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">เบอร์โทรศัพท์</label>
                    <input
                      v-model="editForm.phone_number"
                      type="tel"
                      class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500 focus:border-transparent"
                      :class="{ 'border-red-500': editErrors.phone_number }"
                      placeholder="เช่น 0812345678"
                    />
                    <p v-if="editErrors.phone_number" class="mt-1 text-sm text-red-500">{{ editErrors.phone_number }}</p>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">บทบาทหลัก</label>
                    <select
                      v-model="editForm.role"
                      class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500"
                      :class="{ 'border-red-500': editErrors.role }"
                    >
                      <option v-for="role in editableRoles" :key="role.value" :value="role.value">
                        {{ role.label }}
                      </option>
                    </select>
                    <p v-if="editErrors.role" class="mt-1 text-sm text-red-500">{{ editErrors.role }}</p>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">สถานะบัญชี</label>
                    <select
                      v-model="editForm.status"
                      class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500"
                    >
                      <option v-for="status in editStatuses" :key="status.value" :value="status.value">
                        {{ status.label }}
                      </option>
                    </select>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">รหัสผ่านใหม่</label>
                    <input
                      v-model="editForm.password"
                      type="password"
                      class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500 focus:border-transparent"
                      :class="{ 'border-red-500': editErrors.password }"
                      placeholder="เว้นว่างไว้ถ้าไม่เปลี่ยน"
                    />
                    <p v-if="editErrors.password" class="mt-1 text-sm text-red-500">{{ editErrors.password }}</p>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">ยืนยันรหัสผ่านใหม่</label>
                    <input
                      v-model="editForm.password_confirmation"
                      type="password"
                      class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500 focus:border-transparent"
                      :class="{ 'border-red-500': editErrors.password_confirmation }"
                      placeholder="กรอกซ้ำเมื่อเปลี่ยนรหัสผ่าน"
                    />
                    <p v-if="editErrors.password_confirmation" class="mt-1 text-sm text-red-500">{{ editErrors.password_confirmation }}</p>
                  </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <label class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl cursor-pointer">
                    <input
                      v-model="editForm.is_super_admin"
                      type="checkbox"
                      class="rounded border-slate-300 dark:border-slate-600 text-hopeui-primary-600 focus:ring-hopeui-primary-500"
                    />
                    <span class="text-sm text-slate-700 dark:text-slate-300">ให้สิทธิ์ Super Admin</span>
                  </label>

                  <label class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl cursor-pointer">
                    <input
                      v-model="editForm.is_plearnd_admin"
                      type="checkbox"
                      class="rounded border-slate-300 dark:border-slate-600 text-hopeui-primary-600 focus:ring-hopeui-primary-500"
                    />
                    <span class="text-sm text-slate-700 dark:text-slate-300">ให้สิทธิ์ Plearnd Admin</span>
                  </label>
                </div>
              </template>
            </div>

            <div class="p-6 border-t border-slate-100 dark:border-slate-700 flex flex-col sm:flex-row gap-3">
              <button
                type="button"
                @click="closeEditModal"
                :disabled="isSavingEdit"
                class="min-h-[44px] sm:min-h-0 flex-1 px-4 py-2.5 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors disabled:opacity-50"
              >
                ยกเลิก
              </button>
              <button
                type="submit"
                :disabled="isLoadingEdit || isSavingEdit"
                class="min-h-[44px] sm:min-h-0 flex-1 px-4 py-2.5 bg-hopeui-primary-600 text-white rounded-xl hover:bg-hopeui-primary-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
              >
                <Icon v-if="isSavingEdit" icon="fluent:spinner-ios-20-regular" class="w-4 h-4 animate-spin" />
                <span>{{ isSavingEdit ? 'กำลังบันทึก...' : 'บันทึกการแก้ไข' }}</span>
              </button>
            </div>
          </form>
        </div>
      </Transition>
    </Teleport>

    <!-- Verify/Unverify Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showVerifyModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
          <div class="absolute inset-0 bg-black/50" @click="showVerifyModal = false" />
          <div class="relative bg-white dark:bg-slate-800 rounded-2xl w-full max-w-md shadow-xl p-6">
            <div class="flex items-center gap-4 mb-6">
              <div 
                class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0"
                :class="verifyAction === 'verify' ? 'bg-green-100 dark:bg-green-900/30' : 'bg-yellow-100 dark:bg-yellow-900/30'"
              >
                <Icon 
                  :icon="verifyAction === 'verify' ? 'fluent:checkmark-circle-24-regular' : 'fluent:dismiss-circle-24-regular'" 
                  class="w-6 h-6"
                  :class="verifyAction === 'verify' ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400'"
                />
              </div>
              <div>
                <h3 class="text-lg font-bold text-slate-800 dark:text-white">
                  {{ verifyAction === 'verify' ? 'ยืนยันบัญชีผู้ใช้' : 'ยกเลิกการยืนยันบัญชี' }}
                </h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                  {{ userToVerify?.name }} ({{ userToVerify?.email }})
                </p>
              </div>
            </div>

            <div class="space-y-4">
              <div v-if="verifyAction === 'verify'" class="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-xl text-sm text-blue-700 dark:text-blue-300">
                การยืนยันจะทำให้ผู้ใช้สามารถเข้าสู่ระบบและใช้งานฟีเจอร์ต่างๆ ได้ตามปกติ
              </div>
              
              <div class="space-y-1.5">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                  หมายเหตุ (ไม่บังคับ)
                </label>
                <textarea
                  v-model="verifyNote"
                  rows="2"
                  placeholder="ระบุข้อความสั้นๆ..."
                  class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500 resize-none"
                />
              </div>
            </div>

            <div class="flex gap-3 mt-8">
              <button
                @click="showVerifyModal = false"
                :disabled="isVerifying"
                class="min-h-[44px] sm:min-h-0 flex-1 px-4 py-2.5 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors disabled:opacity-50"
              >
                ยกเลิก
              </button>
              <button
                @click="confirmVerify"
                :disabled="isVerifying"
                class="min-h-[44px] sm:min-h-0 flex-1 px-4 py-2.5 text-white rounded-xl transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
                :class="verifyAction === 'verify' ? 'bg-green-600 hover:bg-green-700' : 'bg-yellow-600 hover:bg-yellow-700'"
              >
                <Icon v-if="isVerifying" icon="fluent:spinner-ios-20-regular" class="w-4 h-4 animate-spin" />
                <span>{{ isVerifying ? 'กำลังดำเนินการ...' : 'ยืนยัน' }}</span>
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Delete Modal with Impact Preview -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showDeleteModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
          <div class="absolute inset-0 bg-black/50" @click="closeDeleteModal" />
          <div class="relative bg-white dark:bg-slate-800 rounded-2xl w-full max-w-lg shadow-xl flex flex-col max-h-[90vh]">

            <!-- Header -->
            <div class="p-6 border-b border-slate-100 dark:border-slate-700">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                  <Icon icon="fluent:person-delete-24-regular" class="w-5 h-5 text-red-600 dark:text-red-400" />
                </div>
                <div>
                  <h3 class="text-base font-semibold text-slate-800 dark:text-white">ปิดใช้งานบัญชี</h3>
                  <p class="text-sm text-slate-500">{{ userToDelete?.name }}</p>
                </div>
              </div>
            </div>

            <!-- Body (scrollable) -->
            <div class="overflow-y-auto p-6 space-y-4 flex-1">

              <!-- Loading impact -->
              <div v-if="isLoadingImpact" class="flex items-center justify-center py-8 gap-3 text-slate-500">
                <Icon icon="fluent:spinner-ios-20-regular" class="w-6 h-6 animate-spin" />
                <span>กำลังตรวจสอบผลกระทบ...</span>
              </div>

              <!-- Impact error -->
              <div v-else-if="impactError" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl text-red-600 dark:text-red-400 text-sm">
                {{ impactError }}
              </div>

              <template v-else-if="deleteImpact">

                <!-- BLOCKERS -->
                <div v-if="hasBlockers" class="space-y-2">
                  <p class="text-sm font-semibold text-red-600 dark:text-red-400 flex items-center gap-1.5">
                    <Icon icon="fluent:error-circle-24-filled" class="w-4 h-4" />
                    ต้องจัดการก่อนลบ
                  </p>
                  <div
                    v-for="b in deleteImpact.blockers"
                    :key="b.type"
                    class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl text-sm"
                  >
                    <p class="font-medium text-red-700 dark:text-red-300">{{ b.message }}</p>
                    <ul v-if="b.items?.length" class="mt-1.5 space-y-0.5 text-red-600 dark:text-red-400">
                      <li v-for="item in b.items.slice(0, 5)" :key="item.id" class="flex items-center gap-1.5">
                        <Icon icon="fluent:chevron-right-12-regular" class="w-3 h-3 flex-shrink-0" />
                        {{ item.name }}
                      </li>
                      <li v-if="b.items.length > 5" class="text-xs opacity-70">และอีก {{ b.items.length - 5 }} รายการ</li>
                    </ul>
                    <ul v-if="b.tables?.length" class="mt-1.5 space-y-0.5 text-red-600 dark:text-red-400">
                      <li v-for="t in b.tables" :key="t.table" class="flex items-center gap-1.5 text-xs">
                        <Icon icon="fluent:table-24-regular" class="w-3 h-3 flex-shrink-0" />
                        {{ t.label }} ({{ t.count }} รายการ)
                      </li>
                    </ul>
                  </div>
                </div>

                <!-- WARNINGS -->
                <div v-if="hasWarnings" class="space-y-2">
                  <p class="text-sm font-semibold text-amber-600 dark:text-amber-400 flex items-center gap-1.5">
                    <Icon icon="fluent:warning-24-filled" class="w-4 h-4" />
                    ข้อมูลที่จะได้รับผลกระทบ
                  </p>
                  <div
                    v-for="w in deleteImpact.warnings"
                    :key="w.type"
                    class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl text-sm"
                  >
                    <p class="font-medium text-amber-700 dark:text-amber-300">{{ w.message }}</p>
                    <div v-if="w.counts" class="mt-1.5 flex flex-wrap gap-2">
                      <span
                        v-for="(count, table) in w.counts"
                        :key="table"
                        class="px-2 py-0.5 bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 rounded-full text-xs"
                      >
                        {{ table }}: {{ count }}
                      </span>
                    </div>
                  </div>

                  <!-- Warning acknowledge checkbox -->
                  <label class="flex items-start gap-2.5 cursor-pointer p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl">
                    <input
                      v-model="warningAcknowledged"
                      type="checkbox"
                      class="mt-0.5 rounded border-slate-300 dark:border-slate-600 text-red-600 focus:ring-red-500"
                    />
                    <span class="text-sm text-slate-700 dark:text-slate-300">
                      รับทราบว่าข้อมูลที่ระบุจะได้รับผลกระทบและไม่สามารถย้อนกลับได้
                    </span>
                  </label>
                </div>

                <!-- No issues — all clear -->
                <div v-if="!hasBlockers && !hasWarnings" class="p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl text-sm text-green-700 dark:text-green-300 flex items-center gap-2">
                  <Icon icon="fluent:checkmark-circle-24-filled" class="w-4 h-4 flex-shrink-0" />
                  ไม่พบข้อมูลที่อาจเป็นปัญหา สามารถปิดใช้งานได้เลย
                </div>

              </template>

              <!-- Reason textarea (แสดงเฉพาะเมื่อไม่มี blocker) -->
              <div v-if="deleteImpact && !hasBlockers" class="space-y-1.5">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                  เหตุผลที่ปิดใช้งาน <span class="text-red-500">*</span>
                </label>
                <textarea
                  v-model="deleteReason"
                  rows="3"
                  placeholder="ระบุเหตุผลอย่างน้อย 5 ตัวอักษร..."
                  class="w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-sm text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500 resize-none"
                />
                <p v-if="deleteReason.length > 0 && deleteReason.trim().length < 5" class="text-xs text-red-500">
                  กรุณาระบุเหตุผลอย่างน้อย 5 ตัวอักษร
                </p>
              </div>

            </div>

            <!-- Footer -->
            <div class="p-6 border-t border-slate-100 dark:border-slate-700 flex gap-3">
              <button
                @click="closeDeleteModal"
                :disabled="isDeleting"
                class="min-h-[44px] sm:min-h-0 flex-1 px-4 py-2.5 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors disabled:opacity-50"
              >
                ยกเลิก
              </button>
              <button
                v-if="deleteImpact && !hasBlockers"
                @click="confirmDelete"
                :disabled="!canConfirmDelete || isDeleting"
                class="min-h-[44px] sm:min-h-0 flex-1 px-4 py-2.5 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
              >
                <Icon v-if="isDeleting" icon="fluent:spinner-ios-20-regular" class="w-4 h-4 animate-spin" />
                <span>{{ isDeleting ? 'กำลังดำเนินการ...' : 'ยืนยันปิดใช้งาน' }}</span>
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: all 0.3s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
.modal-enter-from .relative,
.modal-leave-to .relative {
  transform: scale(0.95);
}
</style>
