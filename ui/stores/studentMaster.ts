import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useAuthStore } from './auth'

export const useStudentMasterStore = defineStore('studentMaster', () => {
  const authStore = useAuthStore()
  const config = useRuntimeConfig()
  const apiBase = config.public.apiBase as string

  // State
  const students = ref<any[]>([])
  const studentsPagination = ref<any>(null)
  const currentStudent = ref<any>(null)
  
  const changeRequests = ref<any[]>([])
  const requestsPagination = ref<any>(null)
  
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  // Auth Header
  const getHeaders = () => ({
    Authorization: `Bearer ${authStore.token}`
  })

  // Actions
  async function fetchMasterStudents(params = {}) {
    isLoading.value = true
    error.value = null
    try {
      const query = new URLSearchParams(params as Record<string, string>).toString()
      const response = await $fetch<any>(`${apiBase}/api/student/master?${query}`, {
        headers: getHeaders()
      })
      if (response && response.data) {
        students.value = response.data
        studentsPagination.value = response.meta || null
      }
    } catch (e: any) {
      error.value = e.message || 'Failed to fetch students'
    } finally {
      isLoading.value = false
    }
  }

  async function fetchMasterStudent(id: number | string) {
    isLoading.value = true
    error.value = null
    try {
      const response = await $fetch<any>(`${apiBase}/api/student/master/${id}`, {
        headers: getHeaders()
      })
      if (response && response.data) {
        currentStudent.value = response.data
      }
    } catch (e: any) {
      error.value = e.message || 'Failed to fetch student details'
    } finally {
      isLoading.value = false
    }
  }

  async function fetchMyProfile() {
    isLoading.value = true
    error.value = null
    try {
      const response = await $fetch<any>(`${apiBase}/api/student/me`, {
        headers: getHeaders()
      })
      if (response && response.data) {
        currentStudent.value = response.data
      }
    } catch (e: any) {
      error.value = e.message || 'Failed to fetch profile'
    } finally {
      isLoading.value = false
    }
  }

  async function updateProfile(data: any) {
    isLoading.value = true
    error.value = null
    try {
      const response = await $fetch<any>(`${apiBase}/api/student/update-info`, {
        method: 'POST',
        headers: getHeaders(),
        body: data
      })
      
      // Update local state if successful and not pending approval
      if (response.success && response.student) {
        currentStudent.value = response.student
      }
      
      return response
    } catch (e: any) {
      error.value = e.message || 'Failed to update profile'
      throw e
    } finally {
      isLoading.value = false
    }
  }

  async function fetchChangeRequests(params = {}) {
    isLoading.value = true
    error.value = null
    try {
      const query = new URLSearchParams(params as Record<string, string>).toString()
      const response = await $fetch<any>(`${apiBase}/api/student/requests?${query}`, {
        headers: getHeaders()
      })
      if (response && response.data && response.data.data) {
        changeRequests.value = response.data.data
        studentsPagination.value = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          total: response.data.total
        }
      }
    } catch (e: any) {
      error.value = e.message || 'Failed to fetch requests'
    } finally {
      isLoading.value = false
    }
  }

  async function approveRequest(id: number | string) {
    isLoading.value = true
    error.value = null
    try {
      const response = await $fetch<any>(`${apiBase}/api/student/requests/${id}/approve`, {
        method: 'PATCH',
        headers: getHeaders()
      })
      return response
    } catch (e: any) {
      error.value = e.message || 'Failed to approve request'
      throw e
    } finally {
      isLoading.value = false
    }
  }

  async function rejectRequest(id: number | string, reason: string) {
    isLoading.value = true
    error.value = null
    try {
      const response = await $fetch<any>(`${apiBase}/api/student/requests/${id}/reject`, {
        method: 'PATCH',
        headers: getHeaders(),
        body: { reason }
      })
      return response
    } catch (e: any) {
      error.value = e.message || 'Failed to reject request'
      throw e
    } finally {
      isLoading.value = false
    }
  }

  return {
    // State
    students,
    studentsPagination,
    currentStudent,
    changeRequests,
    requestsPagination,
    isLoading,
    error,
    
    // Actions
    fetchMasterStudents,
    fetchMasterStudent,
    fetchMyProfile,
    updateProfile,
    fetchChangeRequests,
    approveRequest,
    rejectRequest
  }
})
