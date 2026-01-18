<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'nuxnan-admin-layout',
  middleware: 'nuxnan-admin'
})

const config = useRuntimeConfig()
const apiBase = config.public.apiBase as string

// State
const users = ref([])
const isLoading = ref(true)
const searchQuery = ref('')
const selectedRole = ref('all')
const currentPage = ref(1)
const totalPages = ref(1)
const perPage = ref(10)
const totalUsers = ref(0)

// Delete modal
const showDeleteModal = ref(false)
const userToDelete = ref(null)

// Roles
const roles = [
  { value: 'all', label: 'ทั้งหมด' },
  { value: 'user', label: 'ผู้ใช้ทั่วไป' },
  { value: 'instructor', label: 'ผู้สอน' },
  { value: 'admin', label: 'ผู้ดูแล' }
]

// Fetch users
const fetchUsers = async () => {
  isLoading.value = true
  try {
    const token = useCookie('token')
    const params = new URLSearchParams({
      page: currentPage.value.toString(),
      per_page: perPage.value.toString(),
      ...(searchQuery.value && { search: searchQuery.value }),
      ...(selectedRole.value !== 'all' && { role: selectedRole.value })
    })

    const response = await $fetch(`${apiBase}/api/admin/users?${params}`, {
      headers: {
        Authorization: `Bearer ${token.value}`
      }
    })

    if (response.success) {
      users.value = response.data.data || response.data
      totalPages.value = response.data.last_page || 1
      totalUsers.value = response.data.total || users.value.length
    }
  } catch (error) {
    console.error('Failed to fetch users:', error)
  } finally {
    isLoading.value = false
  }
}

// Handle search
const handleSearch = () => {
  currentPage.value = 1
  fetchUsers()
}

// Handle role filter
const handleRoleFilter = () => {
  currentPage.value = 1
  fetchUsers()
}

// Handle pagination
const goToPage = (page: number) => {
  currentPage.value = page
  fetchUsers()
}

// Open delete modal
const openDeleteModal = (user: any) => {
  userToDelete.value = user
  showDeleteModal.value = true
}

// Confirm delete
const confirmDelete = async () => {
  if (!userToDelete.value) return

  try {
    const token = useCookie('token')
    await $fetch(`${apiBase}/api/admin/users/${userToDelete.value.id}`, {
      method: 'DELETE',
      headers: {
        Authorization: `Bearer ${token.value}`
      }
    })
    showDeleteModal.value = false
    userToDelete.value = null
    fetchUsers()
  } catch (error) {
    console.error('Failed to delete user:', error)
  }
}

// Get status badge class
const getStatusBadge = (status: string) => {
  const badges = {
    active: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    inactive: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    suspended: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
  }
  return badges[status] || badges.inactive
}

// Get role badge class
const getRoleBadge = (role: string) => {
  const badges = {
    admin: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    instructor: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    user: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
  }
  return badges[role] || badges.user
}

onMounted(() => {
  fetchUsers()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">จัดการผู้ใช้</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">
          ผู้ใช้งานทั้งหมด {{ totalUsers.toLocaleString() }} คน
        </p>
      </div>
      <NuxtLink
        to="/nuxnan-admin/users/create"
        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-white transition-colors"
      >
        <Icon icon="fluent:person-add-24-regular" class="w-5 h-5" />
        เพิ่มผู้ใช้ใหม่
      </NuxtLink>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
      <div class="flex flex-col sm:flex-row gap-4">
        <!-- Search -->
        <div class="flex-1 relative">
          <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="ค้นหาผู้ใช้..."
            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
            @keyup.enter="handleSearch"
          />
        </div>

        <!-- Role Filter -->
        <select
          v-model="selectedRole"
          class="px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
          @change="handleRoleFilter"
        >
          <option v-for="role in roles" :key="role.value" :value="role.value">
            {{ role.label }}
          </option>
        </select>

        <!-- Search Button -->
        <button
          @click="handleSearch"
          class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-white transition-colors"
        >
          ค้นหา
        </button>
      </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
      <!-- Loading State -->
      <div v-if="isLoading" class="p-8 text-center">
        <Icon icon="fluent:spinner-ios-20-regular" class="w-8 h-8 text-indigo-600 animate-spin mx-auto" />
        <p class="text-gray-500 mt-2">กำลังโหลดข้อมูล...</p>
      </div>

      <!-- Table -->
      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                ผู้ใช้
              </th>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                อีเมล
              </th>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                บทบาท
              </th>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                สถานะ
              </th>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                วันที่สมัคร
              </th>
              <th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                จัดการ
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <tr
              v-for="user in users"
              :key="user.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center gap-3">
                  <img
                    :src="user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=6366f1&color=fff`"
                    :alt="user.name"
                    class="w-10 h-10 rounded-full object-cover"
                  />
                  <div>
                    <p class="font-medium text-gray-800 dark:text-white">{{ user.name }}</p>
                    <p class="text-sm text-gray-500">ID: {{ user.id }}</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-gray-600 dark:text-gray-300">{{ user.email }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="[getRoleBadge(user.role), 'px-2.5 py-1 rounded-full text-xs font-medium']">
                  {{ user.role || 'user' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="[getStatusBadge(user.status || 'active'), 'px-2.5 py-1 rounded-full text-xs font-medium']">
                  {{ user.status || 'active' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-gray-600 dark:text-gray-300">
                  {{ new Date(user.created_at).toLocaleDateString('th-TH') }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right">
                <div class="flex items-center justify-end gap-2">
                  <NuxtLink
                    :to="`/nuxnan-admin/users/${user.id}`"
                    class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors"
                  >
                    <Icon icon="fluent:eye-24-regular" class="w-5 h-5" />
                  </NuxtLink>
                  <NuxtLink
                    :to="`/nuxnan-admin/users/${user.id}/edit`"
                    class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
                  >
                    <Icon icon="fluent:edit-24-regular" class="w-5 h-5" />
                  </NuxtLink>
                  <button
                    @click="openDeleteModal(user)"
                    class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                  >
                    <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty State -->
        <div v-if="users.length === 0" class="p-8 text-center">
          <Icon icon="fluent:people-24-regular" class="w-12 h-12 text-gray-300 mx-auto" />
          <p class="text-gray-500 mt-2">ไม่พบผู้ใช้งาน</p>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
        <p class="text-sm text-gray-500">
          แสดง {{ (currentPage - 1) * perPage + 1 }} - {{ Math.min(currentPage * perPage, totalUsers) }} จาก {{ totalUsers }} รายการ
        </p>
        <div class="flex items-center gap-2">
          <button
            :disabled="currentPage === 1"
            @click="goToPage(currentPage - 1)"
            class="p-2 rounded-lg border border-gray-200 dark:border-gray-600 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
          >
            <Icon icon="fluent:chevron-left-24-regular" class="w-5 h-5" />
          </button>
          <span class="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-300">
            หน้า {{ currentPage }} / {{ totalPages }}
          </span>
          <button
            :disabled="currentPage === totalPages"
            @click="goToPage(currentPage + 1)"
            class="p-2 rounded-lg border border-gray-200 dark:border-gray-600 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
          >
            <Icon icon="fluent:chevron-right-24-regular" class="w-5 h-5" />
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showDeleteModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
          <div class="absolute inset-0 bg-black/50" @click="showDeleteModal = false" />
          <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl">
            <div class="text-center">
              <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                <Icon icon="fluent:warning-24-regular" class="w-6 h-6 text-red-600 dark:text-red-400" />
              </div>
              <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-2">
                ยืนยันการลบผู้ใช้
              </h3>
              <p class="text-gray-500 dark:text-gray-400">
                คุณต้องการลบผู้ใช้ "{{ userToDelete?.name }}" ใช่หรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้
              </p>
            </div>
            <div class="flex gap-3 mt-6">
              <button
                @click="showDeleteModal = false"
                class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
              >
                ยกเลิก
              </button>
              <button
                @click="confirmDelete"
                class="flex-1 px-4 py-2.5 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-colors"
              >
                ลบผู้ใช้
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
