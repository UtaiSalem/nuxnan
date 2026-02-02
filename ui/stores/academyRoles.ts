/**
 * Academy Roles Management Store
 * จัดการบทบาทและสิทธิ์ทั้งหมดของโรงเรียน
 */

import { defineStore } from 'pinia'
import type { AcademyRole } from '~/composables/useAcademyRole'

interface PermissionGroup {
  [key: string]: {
    name: string
    display_name: string
  }[]
}

interface AcademyRolesState {
  roles: AcademyRole[]
  availableRoles: AcademyRole[]
  permissions: PermissionGroup
  isLoading: boolean
  error: string | null
}

export const useAcademyRolesStore = defineStore('academyRoles', {
  state: (): AcademyRolesState => ({
    roles: [],
    availableRoles: [],
    permissions: {},
    isLoading: false,
    error: null,
  }),

  getters: {
    systemRoles: (state) => state.roles.filter(r => r.is_system),
    customRoles: (state) => state.roles.filter(r => !r.is_system),
    activeRoles: (state) => state.roles.filter(r => r.is_active),
    getRoleById: (state) => (id: number) => state.roles.find(r => r.id === id),
    getRoleByName: (state) => (name: string) => state.roles.find(r => r.name === name),
  },

  actions: {
    /**
     * Fetch all roles for an academy
     */
    async fetchRoles(academyId: number) {
      const api = useApi()
      this.isLoading = true
      this.error = null

      try {
        const response: any = await api.get(`/api/academies/${academyId}/roles`)
        
        if (response.success) {
          this.roles = response.roles || []
        }
      } catch (err: any) {
        console.error('Failed to fetch roles:', err)
        this.error = err?.data?.message || 'ไม่สามารถโหลดบทบาทได้'
      } finally {
        this.isLoading = false
      }
    },

    /**
     * Fetch available roles for assignment
     */
    async fetchAvailableRoles(academyId: number) {
      const api = useApi()

      try {
        const response: any = await api.get(`/api/academies/${academyId}/roles/available`)
        
        if (response.success) {
          this.availableRoles = response.roles || []
        }
      } catch (err: any) {
        console.error('Failed to fetch available roles:', err)
      }
    },

    /**
     * Fetch all available permissions
     */
    async fetchPermissions() {
      const api = useApi()

      try {
        const response: any = await api.get('/api/academies/permissions/all')
        
        if (response.success) {
          this.permissions = response.permissions || {}
        }
      } catch (err: any) {
        console.error('Failed to fetch permissions:', err)
      }
    },

    /**
     * Create a new custom role
     */
    async createRole(academyId: number, roleData: Partial<AcademyRole>) {
      const api = useApi()
      this.isLoading = true

      try {
        const response: any = await api.post(`/api/academies/${academyId}/roles`, roleData)
        
        if (response.success) {
          this.roles.push(response.role)
          return { success: true, role: response.role }
        }
        return { success: false, message: response.message }
      } catch (err: any) {
        console.error('Failed to create role:', err)
        return { success: false, message: err?.data?.message || 'ไม่สามารถสร้างบทบาทได้' }
      } finally {
        this.isLoading = false
      }
    },

    /**
     * Update a role
     */
    async updateRole(academyId: number, roleId: number, roleData: Partial<AcademyRole>) {
      const api = useApi()
      this.isLoading = true

      try {
        const response: any = await api.put(`/api/academies/${academyId}/roles/${roleId}`, roleData)
        
        if (response.success) {
          const index = this.roles.findIndex(r => r.id === roleId)
          if (index !== -1) {
            this.roles[index] = response.role
          }
          return { success: true, role: response.role }
        }
        return { success: false, message: response.message }
      } catch (err: any) {
        console.error('Failed to update role:', err)
        return { success: false, message: err?.data?.message || 'ไม่สามารถอัพเดทบทบาทได้' }
      } finally {
        this.isLoading = false
      }
    },

    /**
     * Delete a role
     */
    async deleteRole(academyId: number, roleId: number) {
      const api = useApi()
      this.isLoading = true

      try {
        const response: any = await api.delete(`/api/academies/${academyId}/roles/${roleId}`)
        
        if (response.success) {
          this.roles = this.roles.filter(r => r.id !== roleId)
          return { success: true }
        }
        return { success: false, message: response.message }
      } catch (err: any) {
        console.error('Failed to delete role:', err)
        return { success: false, message: err?.data?.message || 'ไม่สามารถลบบทบาทได้' }
      } finally {
        this.isLoading = false
      }
    },

    /**
     * Assign role to a member
     */
    async assignRoleToMember(academyId: number, memberId: number, roleId: number) {
      const api = useApi()

      try {
        const response: any = await api.post(`/api/academies/${academyId}/members/${memberId}/role`, {
          role_id: roleId
        })
        
        return { success: response.success, message: response.message, member: response.member }
      } catch (err: any) {
        console.error('Failed to assign role:', err)
        return { success: false, message: err?.data?.message || 'ไม่สามารถกำหนดบทบาทได้' }
      }
    },

    /**
     * Bulk assign role to members
     */
    async bulkAssignRole(academyId: number, memberIds: number[], roleId: number) {
      const api = useApi()

      try {
        const response: any = await api.post(`/api/academies/${academyId}/members/bulk-role`, {
          member_ids: memberIds,
          role_id: roleId
        })
        
        return { success: response.success, message: response.message, count: response.updated_count }
      } catch (err: any) {
        console.error('Failed to bulk assign role:', err)
        return { success: false, message: err?.data?.message || 'ไม่สามารถกำหนดบทบาทได้' }
      }
    },

    /**
     * Clear store
     */
    clearStore() {
      this.roles = []
      this.availableRoles = []
      this.permissions = {}
      this.error = null
    }
  }
})
