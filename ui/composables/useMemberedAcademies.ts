import { ref, computed } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { useApi } from './useApi'

export interface MemberedAcademy {
  id: number
  name: string
  logo: string | null
  memberStatus: number | string | null
  memberRole: string | null
  [key: string]: any
}

export const useMemberedAcademies = () => {
  const api = useApi()
  const authStore = useAuthStore()

  const academies = ref<MemberedAcademy[]>([])
  const isLoading = ref(false)
  const isLoaded = ref(false)
  const error = ref<string | null>(null)

  const fetch = async () => {
    if (!authStore.user?.id) {
      error.value = 'User not authenticated'
      return
    }

    isLoading.value = true
    error.value = null

    try {
      const res: any = await api.get(
        `/api/academies/users/${authStore.user.id}/membered-academies`,
        { params: { per_page: 50 } }
      )

      if (res && res.success) {
        // Handle paginated response or flat array
        const list = res.academies?.data || res.academies || []
        academies.value = JSON.parse(JSON.stringify(list))
        isLoaded.value = true
      } else {
        error.value = res?.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล'
      }
    } catch (e: any) {
      error.value = e?.message || 'เกิดข้อผิดพลาดในการติดต่อระบบ'
    } finally {
      isLoading.value = false
    }
  }

  // Strictly filter approved status (status === 2 or status === 'approved' or status === 'accepted')
  const approved = computed(() => {
    return academies.value.filter(a => {
      const status = a.memberStatus
      return status === 2 || status === 'approved' || status === 'accepted'
    })
  })

  // Strictly filter pending status (status === 1 or status === 0 or status === 'pending')
  const pending = computed(() => {
    return academies.value.filter(a => {
      const status = a.memberStatus
      return status === 1 || status === 0 || status === 'pending'
    })
  })

  // Target destination mapping
  const quickActionTarget = computed(() => {
    if (isLoading.value || !isLoaded.value || error.value) {
      return '/academies'
    }
    if (approved.value.length === 1) {
      const name = encodeURIComponent(approved.value[0].name)
      return `/academies/${name}/dashboard`
    }
    if (approved.value.length >= 2) {
      return '/academies?view=my'
    }
    if (pending.value.length > 0) {
      return '/academies?view=my'
    }
    return '/academies?view=all'
  })

  return {
    academies,
    approved,
    pending,
    isLoading,
    isLoaded,
    error,
    fetch,
    quickActionTarget
  }
}
