<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'nuxnan-admin-layout',
  middleware: 'nuxnan-admin'
})

const config = useRuntimeConfig()
const apiBase = config.public.apiBase as string

// State
const roles = ref<any[]>([])
const isLoading = ref(true)
const searchQuery = ref('')
const currentPage = ref(1)
const totalPages = ref(1)
const perPage = ref(10)
const totalRoles = ref(0)

// Modals
const showDeleteModal = ref(false)
const roleToDelete = ref<any>(null)
const showCreateModal = ref(false)
const showEditModal = ref(false)
const editingRole = ref<any>(null)

// Form
const formData = ref({
  name: '',
  display_name: '',
  description: '',
  permissions: [] as string[]
})
const formErrors = ref<Record<string, string>>({})
const isSubmitting = ref(false)

// Permissions
const availablePermissions = ref<Record<string, any[]>>({})

// Token helper
const getAuthToken = () => {
  const token = useCookie('admin_token')
  return token.value
}

// Fetch roles
const fetchRoles = async () => {
  isLoading.value = true
  try {
    const params = new URLSearchParams({
      page: currentPage.value.toString(),
      per_page: perPage.value.toString(),
      ...(searchQuery.value && { search: searchQuery.value })
    })

    const response = await $fetch<any>(`${apiBase}/api/admin/roles?${params}`, {
      headers: {
        Authorization: `Bearer ${getAuthToken()}`
      }
    })

    if (response.success) {
      roles.value = response.data
      totalPages.value = response.meta?.last_page || 1
      totalRoles.value = response.meta?.total || roles.value.length
    }
  } catch (error) {
    console.error('Failed to fetch roles:', error)
  } finally {
    isLoading.value = false
  }
}

// Fetch permissions for form
const fetchPermissions = async () => {
  try {
    const response = await $fetch<any>(`${apiBase}/api/admin/permissions/groups`, {
      headers: {
        Authorization: `Bearer ${getAuthToken()}`
      }
    })

    if (response.success) {
      availablePermissions.value = response.data
    }
  } catch (error) {
    console.error('Failed to fetch permissions:', error)
  }
}

// Handle search
const handleSearch = () => {
  currentPage.value = 1
  fetchRoles()
}

// Handle pagination
const goToPage = (page: number) => {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
  fetchRoles()
}

// Open create modal
const openCreateModal = () => {
  formData.value = { name: '', display_name: '', description: '', permissions: [] }
  formErrors.value = {}
  showCreateModal.value = true
  fetchPermissions()
}

// Open edit modal
const openEditModal = async (role: any) => {
  editingRole.value = role
  formErrors.value = {}
  
  try {
    const response = await $fetch<any>(`${apiBase}/api/admin/roles/${role.id}`, {
      headers: {
        Authorization: `Bearer ${getAuthToken()}`
      }
    })
    
    if (response.success) {
      const roleData = response.data
      formData.value = {
        name: roleData.name,
        display_name: roleData.display_name || '',
        description: roleData.description || '',
        permissions: roleData.permissions?.map((p: any) => p.name) || []
      }
      showEditModal.value = true
      fetchPermissions()
    }
  } catch (error) {
    console.error('Failed to fetch role details:', error)
  }
}

// Submit create form
const submitCreate = async () => {
  formErrors.value = {}
  isSubmitting.value = true
  
  try {
    const response = await $fetch<any>(`${apiBase}/api/admin/roles`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${getAuthToken()}`
      },
      body: formData.value
    })

    if (response.success) {
      showCreateModal.value = false
      fetchRoles()
    }
  } catch (error: any) {
    if (error.data?.errors) {
      formErrors.value = error.data.errors
    }
  } finally {
    isSubmitting.value = false
  }
}

// Submit edit form
const submitEdit = async () => {
  if (!editingRole.value) return
  
  formErrors.value = {}
  isSubmitting.value = true
  
  try {
    const response = await $fetch<any>(`${apiBase}/api/admin/roles/${editingRole.value.id}`, {
      method: 'PUT',
      headers: {
        Authorization: `Bearer ${getAuthToken()}`
      },
      body: formData.value
    })

    if (response.success) {
      showEditModal.value = false
      editingRole.value = null
      fetchRoles()
    }
  } catch (error: any) {
    if (error.data?.errors) {
      formErrors.value = error.data.errors
    }
  } finally {
    isSubmitting.value = false
  }
}

// Open delete modal
const openDeleteModal = (role: any) => {
  roleToDelete.value = role
  showDeleteModal.value = true
}

// Confirm delete
const confirmDelete = async () => {
  if (!roleToDelete.value) return

  try {
    await $fetch(`${apiBase}/api/admin/roles/${roleToDelete.value.id}`, {
      method: 'DELETE',
      headers: {
        Authorization: `Bearer ${getAuthToken()}`
      }
    })
    showDeleteModal.value = false
    roleToDelete.value = null
    fetchRoles()
  } catch (error) {
    console.error('Failed to delete role:', error)
  }
}

// Toggle permission
const togglePermission = (permissionName: string) => {
  const index = formData.value.permissions.indexOf(permissionName)
  if (index > -1) {
    formData.value.permissions.splice(index, 1)
  } else {
    formData.value.permissions.push(permissionName)
  }
}

// Toggle all permissions in a group
const toggleGroup = (groupPermissions: any[]) => {
  const permissionNames = groupPermissions.map(p => p.name)
  const allSelected = permissionNames.every(name => formData.value.permissions.includes(name))
  
  if (allSelected) {
    // Remove all
    formData.value.permissions = formData.value.permissions.filter(p => !permissionNames.includes(p))
  } else {
    // Add all
    const toAdd = permissionNames.filter(name => !formData.value.permissions.includes(name))
    formData.value.permissions.push(...toAdd)
  }
}

// Check if all permissions in group are selected
const isGroupSelected = (groupPermissions: any[]) => {
  return groupPermissions.every(p => formData.value.permissions.includes(p.name))
}

// Get role badge color
const getRoleBadgeClass = (roleName: string) => {
  const badges: Record<string, string> = {
    'SUPER_ADMIN': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    'ADMIN': 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    'MODERATOR': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    'INSTRUCTOR': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    'USER': 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
  }
  return badges[roleName] || 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400'
}

// Check if role is system role (cannot be deleted)
const isSystemRole = (roleName: string) => {
  return ['SUPER_ADMIN', 'ADMIN', 'USER'].includes(roleName)
}

// Pagination computed
const paginationPages = computed(() => {
  const pages = []
  const maxPages = 5
  let start = Math.max(1, currentPage.value - Math.floor(maxPages / 2))
  let end = Math.min(totalPages.value, start + maxPages - 1)
  
  if (end - start + 1 < maxPages) {
    start = Math.max(1, end - maxPages + 1)
  }
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  return pages
})

onMounted(() => {
  fetchRoles()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">จัดการบทบาท</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">
          บทบาททั้งหมด {{ totalRoles }} บทบาท
        </p>
      </div>
      <button
        @click="openCreateModal"
        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-white transition-colors"
      >
        <Icon icon="fluent:add-24-regular" class="w-5 h-5" />
        เพิ่มบทบาทใหม่
      </button>
    </div>

    <!-- Search Filter -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
      <div class="flex flex-col sm:flex-row gap-4">
        <div class="flex-1 relative">
          <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="ค้นหาบทบาท..."
            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
            @keyup.enter="handleSearch"
          />
        </div>
        <button
          @click="handleSearch"
          class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-white transition-colors"
        >
          ค้นหา
        </button>
      </div>
    </div>

    <!-- Roles Table -->
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
              <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">บทบาท</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">คำอธิบาย</th>
              <th class="px-6 py-4 text-center text-sm font-semibold text-gray-700 dark:text-gray-300">ผู้ใช้</th>
              <th class="px-6 py-4 text-center text-sm font-semibold text-gray-700 dark:text-gray-300">สิทธิ์</th>
              <th class="px-6 py-4 text-center text-sm font-semibold text-gray-700 dark:text-gray-300">สถานะ</th>
              <th class="px-6 py-4 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">จัดการ</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <tr v-for="role in roles" :key="role.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full flex items-center justify-center" :class="getRoleBadgeClass(role.name)">
                    <Icon icon="fluent:shield-24-regular" class="w-5 h-5" />
                  </div>
                  <div>
                    <div class="font-medium text-gray-800 dark:text-white">{{ role.display_name || role.name }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ role.name }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <p class="text-gray-600 dark:text-gray-400 text-sm truncate max-w-xs">
                  {{ role.description || '-' }}
                </p>
              </td>
              <td class="px-6 py-4 text-center">
                <span class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm text-gray-700 dark:text-gray-300">
                  <Icon icon="fluent:people-24-regular" class="w-4 h-4" />
                  {{ role.users_count || 0 }}
                </span>
              </td>
              <td class="px-6 py-4 text-center">
                <span class="inline-flex items-center gap-1 px-2 py-1 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg text-sm text-indigo-700 dark:text-indigo-400">
                  <Icon icon="fluent:key-24-regular" class="w-4 h-4" />
                  {{ role.permissions_count || 0 }}
                </span>
              </td>
              <td class="px-6 py-4 text-center">
                <span v-if="role.status" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                  ใช้งาน
                </span>
                <span v-else class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                  ปิดใช้งาน
                </span>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center justify-end gap-2">
                  <button
                    @click="openEditModal(role)"
                    class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                    title="แก้ไข"
                  >
                    <Icon icon="fluent:edit-24-regular" class="w-5 h-5" />
                  </button>
                  <button
                    v-if="!isSystemRole(role.name)"
                    @click="openDeleteModal(role)"
                    class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                    title="ลบ"
                  >
                    <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
                  </button>
                  <button
                    v-else
                    disabled
                    class="p-2 text-gray-400 cursor-not-allowed rounded-lg"
                    title="ไม่สามารถลบบทบาทระบบได้"
                  >
                    <Icon icon="fluent:lock-closed-24-regular" class="w-5 h-5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-if="!isLoading && roles.length === 0" class="p-8 text-center">
        <Icon icon="fluent:shield-error-24-regular" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto" />
        <h3 class="mt-4 text-lg font-medium text-gray-800 dark:text-white">ไม่พบบทบาท</h3>
        <p class="mt-1 text-gray-500">ลองค้นหาด้วยคำอื่น หรือเพิ่มบทบาทใหม่</p>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
        <div class="text-sm text-gray-500 dark:text-gray-400">
          แสดง {{ (currentPage - 1) * perPage + 1 }} - {{ Math.min(currentPage * perPage, totalRoles) }} จาก {{ totalRoles }} รายการ
        </div>
        <div class="flex items-center gap-1">
          <button
            @click="goToPage(currentPage - 1)"
            :disabled="currentPage === 1"
            class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <Icon icon="fluent:chevron-left-24-regular" class="w-5 h-5" />
          </button>
          <button
            v-for="page in paginationPages"
            :key="page"
            @click="goToPage(page)"
            :class="[
              'px-3 py-1 rounded-lg text-sm font-medium',
              page === currentPage 
                ? 'bg-indigo-600 text-white' 
                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
            ]"
          >
            {{ page }}
          </button>
          <button
            @click="goToPage(currentPage + 1)"
            :disabled="currentPage === totalPages"
            class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <Icon icon="fluent:chevron-right-24-regular" class="w-5 h-5" />
          </button>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <Teleport to="body">
      <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showCreateModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
          <div class="p-6 border-b border-gray-100 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white">เพิ่มบทบาทใหม่</h2>
          </div>
          
          <form @submit.prevent="submitCreate" class="p-6 space-y-4 overflow-y-auto max-h-[60vh]">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อบทบาท (ภาษาอังกฤษ)</label>
              <input
                v-model="formData.name"
                type="text"
                placeholder="เช่น CONTENT_MANAGER"
                class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500"
              />
              <p v-if="formErrors.name" class="text-red-500 text-sm mt-1">{{ formErrors.name }}</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อแสดง</label>
              <input
                v-model="formData.display_name"
                type="text"
                placeholder="เช่น ผู้จัดการเนื้อหา"
                class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
              <textarea
                v-model="formData.description"
                rows="2"
                placeholder="อธิบายหน้าที่ของบทบาทนี้..."
                class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500"
              ></textarea>
            </div>

            <!-- Permissions -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">สิทธิ์การใช้งาน</label>
              <div class="space-y-4">
                <div v-for="(permissions, group) in availablePermissions" :key="group" class="border border-gray-200 dark:border-gray-600 rounded-xl overflow-hidden">
                  <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                      <input
                        type="checkbox"
                        :checked="isGroupSelected(permissions)"
                        @change="toggleGroup(permissions)"
                        class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                      />
                      <span class="font-medium text-gray-700 dark:text-gray-300 capitalize">{{ group }}</span>
                    </div>
                    <span class="text-sm text-gray-500">{{ permissions.length }} สิทธิ์</span>
                  </div>
                  <div class="p-4 grid grid-cols-2 gap-2">
                    <label v-for="permission in permissions" :key="permission.name" class="flex items-center gap-2 cursor-pointer">
                      <input
                        type="checkbox"
                        :checked="formData.permissions.includes(permission.name)"
                        @change="togglePermission(permission.name)"
                        class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                      />
                      <span class="text-sm text-gray-600 dark:text-gray-400">{{ permission.display_name || permission.name }}</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </form>

          <div class="p-6 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-3">
            <button
              type="button"
              @click="showCreateModal = false"
              class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors"
            >
              ยกเลิก
            </button>
            <button
              @click="submitCreate"
              :disabled="isSubmitting"
              class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition-colors disabled:opacity-50"
            >
              {{ isSubmitting ? 'กำลังบันทึก...' : 'บันทึก' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Edit Modal -->
    <Teleport to="body">
      <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showEditModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
          <div class="p-6 border-b border-gray-100 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white">แก้ไขบทบาท</h2>
          </div>
          
          <form @submit.prevent="submitEdit" class="p-6 space-y-4 overflow-y-auto max-h-[60vh]">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อบทบาท</label>
              <input
                v-model="formData.name"
                type="text"
                :disabled="isSystemRole(editingRole?.name)"
                class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50"
              />
              <p v-if="isSystemRole(editingRole?.name)" class="text-sm text-gray-500 mt-1">ไม่สามารถเปลี่ยนชื่อบทบาทระบบได้</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อแสดง</label>
              <input
                v-model="formData.display_name"
                type="text"
                class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
              <textarea
                v-model="formData.description"
                rows="2"
                class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500"
              ></textarea>
            </div>

            <!-- Permissions -->
            <div v-if="editingRole?.name !== 'SUPER_ADMIN'">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">สิทธิ์การใช้งาน</label>
              <div class="space-y-4">
                <div v-for="(permissions, group) in availablePermissions" :key="group" class="border border-gray-200 dark:border-gray-600 rounded-xl overflow-hidden">
                  <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                      <input
                        type="checkbox"
                        :checked="isGroupSelected(permissions)"
                        @change="toggleGroup(permissions)"
                        class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                      />
                      <span class="font-medium text-gray-700 dark:text-gray-300 capitalize">{{ group }}</span>
                    </div>
                    <span class="text-sm text-gray-500">{{ permissions.length }} สิทธิ์</span>
                  </div>
                  <div class="p-4 grid grid-cols-2 gap-2">
                    <label v-for="permission in permissions" :key="permission.name" class="flex items-center gap-2 cursor-pointer">
                      <input
                        type="checkbox"
                        :checked="formData.permissions.includes(permission.name)"
                        @change="togglePermission(permission.name)"
                        class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                      />
                      <span class="text-sm text-gray-600 dark:text-gray-400">{{ permission.display_name || permission.name }}</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl">
              <p class="text-yellow-700 dark:text-yellow-400 text-sm">
                <Icon icon="fluent:warning-24-regular" class="w-4 h-4 inline mr-1" />
                Super Admin มีสิทธิ์ทุกอย่างโดยอัตโนมัติ ไม่สามารถแก้ไขได้
              </p>
            </div>
          </form>

          <div class="p-6 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-3">
            <button
              type="button"
              @click="showEditModal = false"
              class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors"
            >
              ยกเลิก
            </button>
            <button
              @click="submitEdit"
              :disabled="isSubmitting"
              class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition-colors disabled:opacity-50"
            >
              {{ isSubmitting ? 'กำลังบันทึก...' : 'บันทึก' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Delete Modal -->
    <Teleport to="body">
      <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showDeleteModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6">
          <div class="text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
              <Icon icon="fluent:delete-24-regular" class="w-8 h-8 text-red-600" />
            </div>
            <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2">ยืนยันการลบ</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-6">
              คุณต้องการลบบทบาท <span class="font-semibold text-gray-800 dark:text-white">{{ roleToDelete?.display_name || roleToDelete?.name }}</span> ใช่หรือไม่?
              <br /><span class="text-red-500">การกระทำนี้ไม่สามารถยกเลิกได้</span>
            </p>
            <div class="flex justify-center gap-3">
              <button
                @click="showDeleteModal = false"
                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors"
              >
                ยกเลิก
              </button>
              <button
                @click="confirmDelete"
                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl transition-colors"
              >
                ลบบทบาท
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
