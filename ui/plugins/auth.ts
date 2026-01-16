import { useAuthStore } from '~/stores/auth'

export default defineNuxtPlugin(async (nuxtApp) => {
  const authStore = useAuthStore()
  const token = useCookie('token')

  if (token.value && !authStore.user) {
    try {
      // Fetch current user data
      await authStore.fetchUser()
    } catch (error: any) {
      // Only log if not a 401 (expected when token is invalid/expired)
      if (error?.statusCode !== 401 && !error?.message?.includes('401')) {
        console.error('Failed to fetch user on init', error)
      }
      // Clear token if it's invalid
      token.value = null
      authStore.user = null
    }
  }

  // Setup automatic token refresh (refresh 5 minutes before expiry)
  // JWT tokens typically last 1 hour, so refresh after 55 minutes
  // Only run on client-side
  if (process.client && token.value) {
    const refreshInterval = setInterval(async () => {
      if (authStore.isAuthenticated && !authStore.isRefreshing) {
        const success = await authStore.refreshToken()

        if (!success) {
          clearInterval(refreshInterval)
          // Redirect to login if refresh fails
          navigateTo('/auth')
        }
      } else {
        clearInterval(refreshInterval)
      }
    }, 55 * 60 * 1000) // 55 minutes

    // Clean up on app unmount
    nuxtApp.hook('app:unmount', () => {
      clearInterval(refreshInterval)
    })
  }
})
