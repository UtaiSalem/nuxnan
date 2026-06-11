import { ref } from 'vue'

export const useAdminDonations = () => {
  const api = useApi()
  const donations = ref<any[]>([])
  const stats = ref({ total: 0, pending: 0, approved: 0, rejected: 0 })
  const page = ref(1)
  const lastPage = ref(1)
  const total = ref(0)
  const isLoading = ref(false)
  const status = ref<string | number>('all')
  const search = ref('')

  const fetch = async (p = 1) => {
    try {
      isLoading.value = true
      page.value = p
      const params: any = { page: p }
      if (status.value !== 'all') params.status = status.value
      if (search.value) params.search = search.value

      const queryString = new URLSearchParams(params).toString()
      const response = await api.get(`/api/plearnd-admin/supports/donates?${queryString}`)
      
      if (response.donates) {
        donations.value = response.donates.data
        lastPage.value = response.donates.meta.last_page
        total.value = response.donates.meta.total
      }
      if (response.stats) {
        stats.value = response.stats
      }
    } catch (err) {
      console.error('Failed to fetch donations:', err)
    } finally {
      isLoading.value = false
    }
  }

  const receive = async (id: number) => {
    try {
      const response = await api.patch(`/api/plearnd-admin/supports/donates/${id}/receive`, {})
      if (response.success) {
        await fetch(page.value)
      }
      return response
    } catch (err: any) {
      console.error(`[useAdminDonations] Failed to receive donation ${id}:`, err)
      throw err
    }
  }

  const reject = async (id: number) => {
    try {
      const response = await api.patch(`/api/plearnd-admin/supports/donates/${id}/reject`, {})
      if (response.success) {
        await fetch(page.value)
      }
      return response
    } catch (err: any) {
      console.error(`[useAdminDonations] Failed to reject donation ${id}:`, err)
      throw err
    }
  }

  const update = async (id: number, payload: any) => {
    try {
      const response = await api.patch(`/api/plearnd-admin/supports/donates/${id}`, payload)
      if (response.success) {
        await fetch(page.value)
      }
      return response
    } catch (err) {
      console.error('Failed to update donation:', id, err)
      throw err
    }
  }

  const destroy = async (id: number) => {
    try {
      const response = await api.delete(`/api/plearnd-admin/supports/donates/${id}`)
      if (response.success) {
        await fetch(page.value)
      }
      return response
    } catch (err) {
      console.error('Failed to delete donation:', id, err)
      throw err
    }
  }

  const bulkReview = async (ids: number[], action: 'approve' | 'reject') => {
    try {
      const response = await api.post(`/api/plearnd-admin/supports/donates/bulk-review`, { ids, action })
      if (response.success) {
        await fetch(page.value)
      }
      return response
    } catch (err) {
      console.error('Failed bulk review:', err)
      throw err
    }
  }

  return { 
    donations, stats, page, lastPage, total, isLoading, status, search, 
    fetch, receive, reject, update, destroy, bulkReview 
  }
}
