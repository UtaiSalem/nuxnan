<script setup lang="ts">
/**
 * Academy Admin - Roles Management
 * หน้าจัดการบทบาทและสิทธิ์ของโรงเรียน
 */
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

definePageMeta({
  layout: 'main'
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)

// State
const academy = ref<any>(null)
const roles = ref<any[]>([])
const isLoading = ref(true)

// Modal
const showRoleModal = ref(false)
const editingRole = ref<any>(null)
const isSubmitting = ref(false)

// Form
const roleForm = ref({
  name: '',
  display_name_th: '',
  display_name_en: '',
  description: '',
  color: '#6366f1',
  icon: 'fluent:person-24-regular',
  permissions: [] as string[]
})

// Academy Role
const academyId = ref<number | null>(null)
const { can, isOwner, fetchMyRole } = useAcademyRole(academyId)

// Available permissions
const permissionGroups = [
  {
    name: 'สมาชิก',
    permissions: [
      { key: 'members.view', label: 'ดูรายชื่อสมาชิก' },
      { key: 'members.manage', label: 'จัดการสมาชิก (เชิญ/ลบ/ระงับ)' },
    ]
  },
  {
    name: 'บทบาท',
    permissions: [
      { key: 'roles.view', label: 'ดูบทบาท' },
      { key: 'roles.manage', label: 'จัดการบทบาท (สร้าง/แก้ไข/ลบ)' },
    ]
  },
  {
    name: 'คอร์สเรียน',
    permissions: [
      { key: 'courses.view', label: 'ดูคอร์ส' },
      { key: 'courses.create', label: 'สร้างคอร์ส' },
      { key: 'courses.edit', label: 'แก้ไขคอร์ส' },
      { key: 'courses.delete', label: 'ลบคอร์ส' },
    ]
  },
  {
    name: 'กลุ่มเรียน',
    permissions: [
      { key: 'groups.view', label: 'ดูกลุ่มเรียน' },
      { key: 'groups.manage', label: 'จัดการกลุ่มเรียน' },
    ]
  },
  {
    name: 'ข้อมูลนักเรียน',
    permissions: [
      { key: 'students.view', label: 'ดูข้อมูลนักเรียน' },
      { key: 'students.manage', label: 'จัดการข้อมูลนักเรียน' },
      { key: 'attendance.view', label: 'ดูข้อมูลการเข้าเรียน' },
      { key: 'attendance.manage', label: 'บันทึกการเข้าเรียน' },
      { key: 'grades.view', label: 'ดูผลการเรียน' },
      { key: 'grades.manage', label: 'บันทึกผลการเรียน' },
    ]
  },
  {
    name: 'การสื่อสาร',
    permissions: [
      { key: 'announcements.view', label: 'ดูประกาศ' },
      { key: 'announcements.manage', label: 'สร้าง/แก้ไขประกาศ' },
    ]
  },
  {
    name: 'รายงาน',
    permissions: [
      { key: 'reports.view', label: 'ดูรายงาน' },
    ]
  },
  {
    name: 'ตั้งค่า',
    permissions: [
      { key: 'settings.view', label: 'ดูการตั้งค่า' },
      { key: 'settings.manage', label: 'แก้ไขการตั้งค่า' },
    ]
  },
]

// System roles (cannot be edited/deleted)
const systemRoles = ['owner', 'director', 'admin', 'teacher', 'staff', 'finance_staff', 'student', 'parent']

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()
      
      if (!can('roles.view') && !isOwner.value) {
        navigateTo(`/academies/${academyName.value}/admin`)
        return
      }
      
      await fetchRoles()
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

const fetchRoles = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/roles`)
    if (response.success) {
      roles.value = response.roles || []
    }
  } catch (err) {
    console.error('Failed to fetch roles:', err)
  }
}

const openCreateModal = () => {
  editingRole.value = null
  roleForm.value = {
    name: '',
    display_name_th: '',
    display_name_en: '',
    description: '',
    color: '#6366f1',
    icon: 'fluent:person-24-regular',
    permissions: []
  }
  showRoleModal.value = true
}

const openEditModal = (role: any) => {
  if (isSystemRole(role.name)) {
    Swal.fire('ไม่สามารถแก้ไขได้', 'บทบาทระบบไม่สามารถแก้ไขได้', 'warning')
    return
  }
  
  editingRole.value = role
  roleForm.value = {
    name: role.name,
    display_name_th: role.display_name_th || '',
    display_name_en: role.display_name_en || '',
    description: role.description || '',
    color: role.color || '#6366f1',
    icon: role.icon || 'fluent:person-24-regular',
    permissions: role.permissions || []
  }
  showRoleModal.value = true
}

const saveRole = async () => {
  if (!roleForm.value.name || !roleForm.value.display_name_th) {
    Swal.fire('กรุณากรอกข้อมูล', 'ชื่อบทบาทจำเป็นต้องกรอก', 'warning')
    return
  }
  
  isSubmitting.value = true
  try {
    if (editingRole.value) {
      await api.put(`/api/academies/${academyId.value}/roles/${editingRole.value.id}`, roleForm.value)
      Swal.fire('สำเร็จ', 'แก้ไขบทบาทเรียบร้อยแล้ว', 'success')
    } else {
      await api.post(`/api/academies/${academyId.value}/roles`, roleForm.value)
      Swal.fire('สำเร็จ', 'สร้างบทบาทใหม่เรียบร้อยแล้ว', 'success')
    }
    showRoleModal.value = false
    await fetchRoles()
  } catch (err: any) {
    Swal.fire('เกิดข้อผิดพลาด', err.data?.message || 'ไม่สามารถบันทึกได้', 'error')
  } finally {
    isSubmitting.value = false
  }
}

const deleteRole = async (role: any) => {
  if (isSystemRole(role.name)) {
    Swal.fire('ไม่สามารถลบได้', 'บทบาทระบบไม่สามารถลบได้', 'warning')
    return
  }
  
  const result = await Swal.fire({
    title: 'ยืนยันการลบ',
    text: `ลบบทบาท "${role.display_name_th}"? สมาชิกที่มีบทบาทนี้จะถูกเปลี่ยนเป็นสมาชิกปกติ`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'ลบ',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#ef4444'
  })

  if (result.isConfirmed) {
    try {
      await api.delete(`/api/academies/${academyId.value}/roles/${role.id}`)
      await fetchRoles()
      Swal.fire('สำเร็จ', 'ลบบทบาทเรียบร้อยแล้ว', 'success')
    } catch (err: any) {
      Swal.fire('เกิดข้อผิดพลาด', err.data?.message || 'ไม่สามารถลบได้', 'error')
    }
  }
}

const isSystemRole = (name: string) => systemRoles.includes(name)

const togglePermission = (permission: string) => {
  const index = roleForm.value.permissions.indexOf(permission)
  if (index === -1) {
    roleForm.value.permissions.push(permission)
  } else {
    roleForm.value.permissions.splice(index, 1)
  }
}

const toggleAllInGroup = (group: typeof permissionGroups[0]) => {
  const allSelected = group.permissions.every(p => roleForm.value.permissions.includes(p.key))
  
  if (allSelected) {
    group.permissions.forEach(p => {
      const idx = roleForm.value.permissions.indexOf(p.key)
      if (idx !== -1) roleForm.value.permissions.splice(idx, 1)
    })
  } else {
    group.permissions.forEach(p => {
      if (!roleForm.value.permissions.includes(p.key)) {
        roleForm.value.permissions.push(p.key)
      }
    })
  }
}

const isGroupFullySelected = (group: typeof permissionGroups[0]) => {
  return group.permissions.every(p => roleForm.value.permissions.includes(p.key))
}

const isGroupPartiallySelected = (group: typeof permissionGroups[0]) => {
  const selected = group.permissions.filter(p => roleForm.value.permissions.includes(p.key)).length
  return selected > 0 && selected < group.permissions.length
}

const iconOptions = [
  'fluent:person-24-regular',
  'fluent:person-star-24-regular',
  'fluent:shield-person-24-regular',
  'fluent:hat-graduation-24-regular',
  'fluent:book-24-regular',
  'fluent:building-24-regular',
  'fluent:briefcase-24-regular',
  'fluent:heart-24-regular',
  'fluent:star-24-regular',
  'fluent:crown-24-regular',
]

const colorOptions = [
  '#6366f1', // indigo
  '#8b5cf6', // violet
  '#ec4899', // pink
  '#ef4444', // red
  '#f97316', // orange
  '#eab308', // yellow
  '#22c55e', // green
  '#14b8a6', // teal
  '#3b82f6', // blue
  '#64748b', // gray
]
</script>

<template>
  <div>
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">จัดการบทบาท</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">กำหนดบทบาทและสิทธิ์การเข้าถึงของสมาชิก</p>
        </div>
        <button 
          v-if="can('roles.manage') || isOwner"
          @click="openCreateModal"
          class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors flex items-center gap-2"
        >
          <Icon name="fluent:add-24-regular" class="w-5 h-5" />
          สร้างบทบาทใหม่
        </button>
      </div>

      <!-- System Roles Section -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
          <h2 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
            <Icon name="fluent:lock-closed-24-regular" class="w-5 h-5 text-gray-400" />
            บทบาทระบบ
          </h2>
          <p class="text-sm text-gray-500 mt-1">บทบาทเหล่านี้เป็นบทบาทมาตรฐานที่ไม่สามารถแก้ไขหรือลบได้</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 p-6">
          <div
            v-for="role in roles.filter(r => isSystemRole(r.name))"
            :key="role.id"
            class="p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600"
          >
            <div class="flex items-center gap-3 mb-3">
              <div 
                class="w-10 h-10 rounded-lg flex items-center justify-center"
                :style="{ backgroundColor: role.color + '20', color: role.color }"
              >
                <Icon :name="role.icon || 'fluent:person-24-regular'" class="w-5 h-5" />
              </div>
              <div>
                <p class="font-medium text-gray-900 dark:text-white">{{ role.display_name_th }}</p>
                <p class="text-xs text-gray-500">{{ role.name }}</p>
              </div>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
              {{ role.description || 'ไม่มีคำอธิบาย' }}
            </p>
          </div>
        </div>
      </div>

      <!-- Custom Roles Section -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
          <h2 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
            <Icon name="fluent:wrench-24-regular" class="w-5 h-5 text-gray-400" />
            บทบาทกำหนดเอง
          </h2>
          <p class="text-sm text-gray-500 mt-1">สร้างบทบาทเฉพาะสำหรับโรงเรียนของคุณ</p>
        </div>
        
        <div v-if="roles.filter(r => !isSystemRole(r.name)).length === 0" class="p-12 text-center">
          <Icon name="fluent:shield-add-24-regular" class="w-12 h-12 mx-auto text-gray-400 mb-4" />
          <p class="text-gray-500 dark:text-gray-400 mb-4">ยังไม่มีบทบาทกำหนดเอง</p>
          <button 
            @click="openCreateModal"
            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors inline-flex items-center gap-2"
          >
            <Icon name="fluent:add-24-regular" class="w-5 h-5" />
            สร้างบทบาทแรก
          </button>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-6">
          <div
            v-for="role in roles.filter(r => !isSystemRole(r.name))"
            :key="role.id"
            class="p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 hover:border-primary-300 dark:hover:border-primary-600 transition-colors"
          >
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-3">
                <div 
                  class="w-10 h-10 rounded-lg flex items-center justify-center"
                  :style="{ backgroundColor: role.color + '20', color: role.color }"
                >
                  <Icon :name="role.icon || 'fluent:person-24-regular'" class="w-5 h-5" />
                </div>
                <div>
                  <p class="font-medium text-gray-900 dark:text-white">{{ role.display_name_th }}</p>
                  <p class="text-xs text-gray-500">{{ role.name }}</p>
                </div>
              </div>
              <div class="flex items-center gap-1">
                <button 
                  @click="openEditModal(role)"
                  class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg"
                >
                  <Icon name="fluent:edit-24-regular" class="w-4 h-4" />
                </button>
                <button 
                  @click="deleteRole(role)"
                  class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg"
                >
                  <Icon name="fluent:delete-24-regular" class="w-4 h-4" />
                </button>
              </div>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-3">
              {{ role.description || 'ไม่มีคำอธิบาย' }}
            </p>
            <div class="flex items-center gap-2 text-xs text-gray-500">
              <Icon name="fluent:key-24-regular" class="w-4 h-4" />
              <span>{{ role.permissions?.length || 0 }} สิทธิ์</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Role Modal -->
    <Teleport to="body">
      <div 
        v-if="showRoleModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
      >
        <div class="fixed inset-0 bg-black/50" @click="showRoleModal = false" />
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
          <!-- Modal Header -->
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ editingRole ? 'แก้ไขบทบาท' : 'สร้างบทบาทใหม่' }}
            </h3>
            <button @click="showRoleModal = false" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
              <Icon name="fluent:dismiss-24-regular" class="w-5 h-5" />
            </button>
          </div>

          <!-- Modal Body -->
          <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)] space-y-6">
            <!-- Basic Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  ชื่อบทบาท (ภาษาไทย) *
                </label>
                <input
                  v-model="roleForm.display_name_th"
                  type="text"
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"
                  placeholder="เช่น ผู้ช่วยครู"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  ชื่อบทบาท (ภาษาอังกฤษ)
                </label>
                <input
                  v-model="roleForm.display_name_en"
                  type="text"
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"
                  placeholder="e.g. Assistant Teacher"
                />
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                รหัสบทบาท (slug) *
              </label>
              <input
                v-model="roleForm.name"
                type="text"
                :disabled="!!editingRole"
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white disabled:opacity-50"
                placeholder="e.g. assistant_teacher"
              />
              <p class="text-xs text-gray-500 mt-1">ใช้ตัวอักษรภาษาอังกฤษ ตัวเลข และขีดล่างเท่านั้น</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                คำอธิบาย
              </label>
              <textarea
                v-model="roleForm.description"
                rows="2"
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"
                placeholder="อธิบายหน้าที่ของบทบาทนี้"
              />
            </div>

            <!-- Icon & Color -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ไอคอน</label>
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="icon in iconOptions"
                    :key="icon"
                    @click="roleForm.icon = icon"
                    :class="[
                      'p-3 rounded-lg border-2 transition-colors',
                      roleForm.icon === icon 
                        ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/30'
                        : 'border-gray-200 dark:border-gray-600 hover:border-gray-300'
                    ]"
                  >
                    <Icon :name="icon" class="w-5 h-5 text-gray-700 dark:text-gray-300" />
                  </button>
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สี</label>
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="color in colorOptions"
                    :key="color"
                    @click="roleForm.color = color"
                    :class="[
                      'w-10 h-10 rounded-lg border-2 transition-all',
                      roleForm.color === color 
                        ? 'border-gray-900 dark:border-white ring-2 ring-offset-2 ring-primary-500'
                        : 'border-transparent'
                    ]"
                    :style="{ backgroundColor: color }"
                  />
                </div>
              </div>
            </div>

            <!-- Permissions -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">สิทธิ์การเข้าถึง</label>
              <div class="space-y-4">
                <div 
                  v-for="group in permissionGroups" 
                  :key="group.name"
                  class="border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden"
                >
                  <button
                    @click="toggleAllInGroup(group)"
                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700/50 flex items-center justify-between hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                  >
                    <span class="font-medium text-gray-900 dark:text-white">{{ group.name }}</span>
                    <div class="flex items-center gap-2">
                      <span class="text-xs text-gray-500">
                        {{ group.permissions.filter(p => roleForm.permissions.includes(p.key)).length }} / {{ group.permissions.length }}
                      </span>
                      <div 
                        :class="[
                          'w-5 h-5 rounded border-2 flex items-center justify-center transition-colors',
                          isGroupFullySelected(group)
                            ? 'bg-primary-500 border-primary-500'
                            : isGroupPartiallySelected(group)
                              ? 'bg-primary-200 border-primary-500'
                              : 'border-gray-300 dark:border-gray-500'
                        ]"
                      >
                        <Icon 
                          v-if="isGroupFullySelected(group) || isGroupPartiallySelected(group)"
                          name="fluent:checkmark-12-filled" 
                          class="w-3 h-3 text-white"
                        />
                      </div>
                    </div>
                  </button>
                  <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-2">
                    <label 
                      v-for="perm in group.permissions"
                      :key="perm.key"
                      class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer"
                    >
                      <input
                        type="checkbox"
                        :checked="roleForm.permissions.includes(perm.key)"
                        @change="togglePermission(perm.key)"
                        class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                      />
                      <span class="text-sm text-gray-700 dark:text-gray-300">{{ perm.label }}</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-end gap-3">
            <button 
              @click="showRoleModal = false"
              class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
            >
              ยกเลิก
            </button>
            <button 
              @click="saveRole"
              :disabled="isSubmitting"
              class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50 flex items-center gap-2"
            >
              <span v-if="isSubmitting" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
              {{ editingRole ? 'บันทึก' : 'สร้างบทบาท' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
