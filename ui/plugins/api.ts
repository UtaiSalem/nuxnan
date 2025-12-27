import { useAuthStore } from '~/stores/auth'

export default defineNuxtPlugin(() => {
  // Create a custom $fetch instance with interceptors
  // Using baseURL to make direct requests to the Laravel API
  const apiFetch = $fetch.create({
    baseURL: useRuntimeConfig().public.apiBase,
    credentials: 'include', // Include cookies for CORS

    async onRequest({ options }) {
      // Access token cookie directly instead of through store
      // This ensures we get the actual cookie value on both client and server
      const tokenCookie = useCookie('token')
      const token = tokenCookie.value

      if (token) {
        ;(options as any).headers = {
          ...(options.headers || {}),
          Authorization: `Bearer ${token}`,
          Accept: 'application/json', // Ensure Laravel returns JSON instead of redirects
        }
      } else {
        // Ensure Accept header is set even without token
        ;(options as any).headers = {
          ...(options.headers || {}),
          Accept: 'application/json',
        }
      }
    },

    async onResponseError({ response }) {
      // Handle 401 errors (unauthorized)
      if (response.status === 401) {
        // Get fresh auth store instance
        const authStore = useAuthStore()
        const token = authStore.token?.value // Access .value since token is a cookie ref

        // Try to refresh token if we have one
        if (token && !authStore.isRefreshing) {
          const refreshed = await authStore.refreshToken()

          if (!refreshed) {
            // Refresh failed, redirect to login
            authStore.logout()
          }
        } else if (!token) {
          // No token, redirect to login
          navigateTo('/auth')
        }
      }
    },
  })

  return {
    provide: {
      apiFetch,
    },
  }
})
