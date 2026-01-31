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
const permissions = ref<Record<string, any[]>>({})
const rolePermissions = ref<Record<number, string[]>>({})
const isLoading = ref(true)
const isSaving = ref(false)
const hasChanges = ref(false)

// Pending changes
const pendingChanges = ref<{ roleId: number; permission: string; action: 'add' | 'remove' }[]>([])

// Token helper
const getAuthToken = () => {
  const token = useCookie('admin_token')
  return token.value
}

// Fetch all data
const fetchData = async () => {
  isLoading.value = true
  try {
    // Fetch roles with their permissions
    const rolesResponse = await $fetch<any>(`${apiBase}/api/admin/roles?all=true`, {
      headers: {
        Authorization: `Bearer ${getAuthToken()}`
      }
    })

    // Fetch permissions grouped
    const permissionsResponse = await $fetch<any>(`${apiBase}/api/admin/permissions/groups`, {
      headers: {
        Authorization: `Bearer ${getAuthToken()}`
      }
    })

    if (rolesResponse.success) {
      roles.value = rolesResponse.data
      
      // Build role permissions map
      for (const role of roles.value) {
        const roleDetail = await $fetch<any>(`${apiBase}/api/admin/roles/${role.id}`, {
          headers: {
            Authorization: `Bearer ${getAuthToken()}`
          }
        })
        
        if (roleDetail.success && roleDetail.data.permissions) {
          rolePermissions.value[role.id] = roleDetail.data.permissions.map((p: any) => p.name)
        } else {
          rolePermissions.value[role.id] = []
        }
      }
    }

    if (permissionsResponse.success) {
      permissions.value = permissionsResponse.data
    }
  } catch (error) {
    console.error('Failed to fetch data:', error)
  } finally {
    isLoading.value = false
  }
}

// Check if role has permission
const hasPermission = (roleId: number, permissionName: string) => {
  // Check pending changes first
  const pending = pendingChanges.value.find(
    c => c.roleId === roleId && c.permission === permissionName
  )
  
  if (pending) {
    return pending.action === 'add'
  }
  
  return rolePermissions.value[roleId]?.includes(permissionName) || false
}

// Toggle permission
const togglePermission = (roleId: number, permissionName: string) => {
  // Super Admin has all permissions, cannot be modified
  const role = roles.value.find(r => r.id === roleId)
  if (role?.name === 'SUPER_ADMIN') return

  const currentlyHas = rolePermissions.value[roleId]?.includes(permissionName)
  
  // Remove any existing pending change for this permission
  const existingIndex = pendingChanges.value.findIndex(
    c => c.roleId === roleId && c.permission === permissionName
  )
  
  if (existingIndex > -1) {
    pendingChanges.value.splice(existingIndex, 1)
  }
  
  // Check if this is a new change or reverting to original
  const newState = !hasPermission(roleId, permissionName)
  
  if (newState !== currentlyHas) {
    pendingChanges.value.push({
      roleId,
      permission: permissionName,
      action: newState ? 'add' : 'remove'
    })
  }
  
  hasChanges.value = pendingChanges.value.length > 0
}

// Toggle all permissions for a role in a group
const toggleGroup = (roleId: number, groupPermissions: any[]) => {
  const role = roles.value.find(r => r.id === roleId)
  if (role?.name === 'SUPER_ADMIN') return

  const allSelected = groupPermissions.every(p => hasPermission(roleId, p.name))
  
  for (const permission of groupPermissions) {
    const currentlyHas = rolePermissions.value[roleId]?.includes(permission.name)
    const shouldHave = !allSelected
    
    // Remove any existing pending change
    const existingIndex = pendingChanges.value.findIndex(
      c => c.roleId === roleId && c.permission === permission.name
    )
    
    if (existingIndex > -1) {
      pendingChanges.value.splice(existingIndex, 1)
    }
    
    // Add change if different from original
    if (shouldHave !== currentlyHas) {
      pendingChanges.value.push({
        roleId,
        permission: permission.name,
        action: shouldHave ? 'add' : 'remove'
      })
    }
  }
  
  hasChanges.value = pendingChanges.value.length > 0
}

// Toggle all permissions for a role
const toggleAllForRole = (roleId: number) => {
  const role = roles.value.find(r => r.id === roleId)
  if (role?.name === 'SUPER_ADMIN') return

  const allPermissionNames = Object.values(permissions.value).flat().map(p => p.name)
  const allSelected = allPermissionNames.every(name => hasPermission(roleId, name))
  
  for (const name of allPermissionNames) {
    const currentlyHas = rolePermissions.value[roleId]?.includes(name)
    const shouldHave = !allSelected
    
    const existingIndex = pendingChanges.value.findIndex(
      c => c.roleId === roleId && c.permission === name
    )
    
    if (existingIndex > -1) {
      pendingChanges.value.splice(existingIndex, 1)
    }
    
    if (shouldHave !== currentlyHas) {
      pendingChanges.value.push({
        roleId,
        permission: name,
        action: shouldHave ? 'add' : 'remove'
      })
    }
  }
  
  hasChanges.value = pendingChanges.value.length > 0
}

// Save changes
const saveChanges = async () => {
  if (pendingChanges.value.length === 0) return
  
  isSaving.value = true
  
  try {
    // Group changes by role
    const changesByRole: Record<number, { add: string[]; remove: string[] }> = {}
    
    for (const change of pendingChanges.value) {
      if (!changesByRole[change.roleId]) {
        changesByRole[change.roleId] = { add: [], remove: [] }
      }
      changesByRole[change.roleId][change.action === 'add' ? 'add' : 'remove'].push(change.permission)
    }
    
    // Apply changes for each role
    for (const [roleId, changes] of Object.entries(changesByRole)) {
      const currentPermissions = rolePermissions.value[Number(roleId)] || []
      
      // Calculate new permissions
      let newPermissions = [...currentPermissions]
      
      // Add new permissions
      for (const p of changes.add) {
        if (!newPermissions.includes(p)) {
          newPermissions.push(p)
        }
      }
      
      // Remove permissions
      newPermissions = newPermissions.filter(p => !changes.remove.includes(p))
      
      // Update role
      await $fetch(`${apiBase}/api/admin/roles/${roleId}`, {
        method: 'PUT',
        headers: {
          Authorization: `Bearer ${getAuthToken()}`
        },
        body: {
          permissions: newPermissions
        }
      })
    }
    
    // Clear pending changes and reload
    pendingChanges.value = []
    hasChanges.value = false
    await fetchData()
  } catch (error) {
    console.error('Failed to save changes:', error)
  } finally {
    isSaving.value = false
  }
}

// Discard changes
const discardChanges = () => {
  pendingChanges.value = []
  hasChanges.value = false
}

// Get role badge color
const getRoleBadgeClass = (roleName: string) => {
  const badges: Record<string, string> = {
    'SUPER_ADMIN': 'bg-red-500 text-white',
    'ADMIN': 'bg-purple-500 text-white',
    'MODERATOR': 'bg-blue-500 text-white',
    'INSTRUCTOR': 'bg-green-500 text-white',
    'USER': 'bg-gray-500 text-white'
  }
  return badges[roleName] || 'bg-indigo-500 text-white'
}

// Count permissions for role
const countRolePermissions = (roleId: number) => {
  let count = 0
  const allPermissions = Object.values(permissions.value).flat()
  
  for (const p of allPermissions) {
    if (hasPermission(roleId, p.name)) {
      count++
    }
  }
  
  return count
}

// Total permissions count
const totalPermissions = computed(() => {
  return Object.values(permissions.value).flat().length
})

onMounted(() => {
  fetchData()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Permission Matrix</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">
          กำหนดสิทธิ์การใช้งานสำหรับแต่ละบทบาท
        </p>
      </div>
      
      <div v-if="hasChanges" class="flex items-center gap-3">
        <span class="text-sm text-amber-600 dark:text-amber-400">
          <Icon icon="fluent:warning-24-regular" class="w-4 h-4 inline mr-1" />
          มีการเปลี่ยนแปลง {{ pendingChanges.length }} รายการ
        </span>
        <button
          @click="discardChanges"
          class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors"
        >
          ยกเลิก
        </button>
        <button
          @click="saveChanges"
          :disabled="isSaving"
          class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition-colors disabled:opacity-50"
        >
          <Icon v-if="isSaving" icon="fluent:spinner-ios-20-regular" class="w-4 h-4 animate-spin inline mr-1" />
          {{ isSaving ? 'กำลังบันทึก...' : 'บันทึกการเปลี่ยนแปลง' }}
        </button>
      </div>
    </div>

    <!-- Permission Matrix -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
      <!-- Loading State -->
      <div v-if="isLoading" class="p-8 text-center">
        <Icon icon="fluent:spinner-ios-20-regular" class="w-8 h-8 text-indigo-600 animate-spin mx-auto" />
        <p class="text-gray-500 mt-2">กำลังโหลดข้อมูล...</p>
      </div>

      <!-- Matrix Table -->
      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 dark:bg-gray-700/50 sticky top-0 z-10">
            <tr>
              <th class="px-4 py-4 text-left text-sm font-semibold text-gray-700 dark:text-gray-300 min-w-[200px] sticky left-0 bg-gray-50 dark:bg-gray-700/50 z-20">
                สิทธิ์การใช้งาน
              </th>
              <th 
                v-for="role in roles" 
                :key="role.id" 
                class="px-3 py-4 text-center min-w-[120px]"
              >
                <div class="flex flex-col items-center gap-2">
                  <span 
                    class="px-2 py-1 rounded-lg text-xs font-medium" 
                    :class="getRoleBadgeClass(role.name)"
                  >
                    {{ role.display_name || role.name }}
                  </span>
                  <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ countRolePermissions(role.id) }}/{{ totalPermissions }}
                  </span>
                  <button
                    v-if="role.name !== 'SUPER_ADMIN'"
                    @click="toggleAllForRole(role.id)"
                    class="text-xs text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                  >
                    เลือกทั้งหมด
                  </button>
                </div>
              </th>
            </tr>
          </thead>
          <tbody>
            <!-- Group Headers and Permissions -->
            <template v-for="(groupPermissions, groupName) in permissions" :key="groupName">
              <!-- Group Header -->
              <tr class="bg-gray-100 dark:bg-gray-700">
                <td 
                  class="px-4 py-3 text-sm font-semibold text-gray-700 dark:text-gray-300 sticky left-0 bg-gray-100 dark:bg-gray-700"
                >
                  <div class="flex items-center gap-2">
                    <Icon icon="fluent:folder-24-regular" class="w-4 h-4" />
                    <span class="capitalize">{{ groupName }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">({{ groupPermissions.length }})</span>
                  </div>
                </td>
                <td 
                  v-for="role in roles" 
                  :key="`group-${groupName}-${role.id}`" 
                  class="px-3 py-3 text-center"
                >
                  <button
                    v-if="role.name !== 'SUPER_ADMIN'"
                    @click="toggleGroup(role.id, groupPermissions)"
                    class="text-xs text-gray-500 hover:text-indigo-600 dark:hover:text-indigo-400"
                  >
                    เลือกกลุ่ม
                  </button>
                  <span v-else class="text-xs text-gray-400">-</span>
                </td>
              </tr>
              
              <!-- Permission Rows -->
              <tr 
                v-for="permission in groupPermissions" 
                :key="permission.name" 
                class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
              >
                <td class="px-4 py-3 sticky left-0 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                  <div class="pl-6">
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                      {{ permission.display_name || permission.name }}
                    </div>
                    <div class="text-xs text-gray-400 font-mono">
                      {{ permission.name }}
                    </div>
                  </div>
                </td>
                <td 
                  v-for="role in roles" 
                  :key="`${permission.name}-${role.id}`" 
                  class="px-3 py-3 text-center"
                >
                  <!-- Super Admin always has all permissions -->
                  <div v-if="role.name === 'SUPER_ADMIN'" class="flex justify-center">
                    <div class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center">
                      <Icon icon="fluent:checkmark-16-filled" class="w-4 h-4 text-white" />
                    </div>
                  </div>
                  
                  <!-- Toggle Checkbox for other roles -->
                  <div v-else class="flex justify-center">
                    <button
                      @click="togglePermission(role.id, permission.name)"
                      :class="[
                        'w-6 h-6 rounded-full flex items-center justify-center transition-colors',
                        hasPermission(role.id, permission.name)
                          ? 'bg-green-500 hover:bg-green-600'
                          : 'bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500'
                      ]"
                    >
                      <Icon 
                        v-if="hasPermission(role.id, permission.name)" 
                        icon="fluent:checkmark-16-filled" 
                        class="w-4 h-4 text-white" 
                      />
                    </button>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Legend -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
      <div class="flex flex-wrap items-center gap-6">
        <div class="flex items-center gap-2">
          <div class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center">
            <Icon icon="fluent:checkmark-16-filled" class="w-4 h-4 text-white" />
          </div>
          <span class="text-sm text-gray-600 dark:text-gray-400">มีสิทธิ์</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="w-6 h-6 bg-gray-200 dark:bg-gray-600 rounded-full"></div>
          <span class="text-sm text-gray-600 dark:text-gray-400">ไม่มีสิทธิ์</span>
        </div>
        <div class="flex items-center gap-2">
          <span class="px-2 py-1 bg-red-500 text-white rounded-lg text-xs">SUPER_ADMIN</span>
          <span class="text-sm text-gray-600 dark:text-gray-400">มีสิทธิ์ทุกอย่างโดยอัตโนมัติ</span>
        </div>
      </div>
    </div>
  </div>
</template>
