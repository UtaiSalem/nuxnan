import { useAuthStore } from '~/stores/auth'

export default defineNuxtPlugin((nuxtApp) => {
  const authStore = useAuthStore()
  const token = useCookie('token')

  // Move user fetch logic to app:mounted hook to defer execution.
  // This prevents blocking the Nuxt module runner during cold start.
  nuxtApp.hook('app:mounted', async () => {
    if (token.value && !authStore.user) {
      try {
        await authStore.fetchUser()
      } catch (error: any) {
        if (error?.statusCode !== 401 && !error?.message?.includes('401')) {
          console.error('Failed to fetch user on mounted', error)
        }
        token.value = null
        authStore.user = null
      }
    }
  })

  // Dynamic token refresh: schedule the next refresh based on the token's
  // actual expires_in value (returned by login/refresh endpoints).
  // We refresh when 80% of the TTL has elapsed (e.g. at 48 min for a 60 min token).
  if (process.client && token.value) {
    let refreshTimer: ReturnType<typeof setTimeout> | null = null

    function scheduleRefresh() {
      if (refreshTimer) clearTimeout(refreshTimer)

      const ttlSeconds = authStore.tokenExpiresIn ?? 3600
      // Refresh at 80% of TTL to leave a comfortable margin
      const delayMs = Math.max(ttlSeconds * 0.8 * 1000, 30_000) // at least 30 s

      refreshTimer = setTimeout(async () => {
        if (!authStore.isAuthenticated) return

        const success = await authStore.refreshToken()
        if (success) {
          // Reschedule based on new TTL
          scheduleRefresh()
        } else {
          navigateTo('/auth')
        }
      }, delayMs)
    }

    scheduleRefresh()

    // Reschedule whenever tokenExpiresIn changes (e.g. after manual refreshToken call)
    watch(() => authStore.tokenExpiresIn, () => {
      if (authStore.isAuthenticated) scheduleRefresh()
    })

    nuxtApp.hook('app:unmount', () => {
      if (refreshTimer) clearTimeout(refreshTimer)
    })
  }
})
