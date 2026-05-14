import { useAuthStore } from '~/stores/auth'

export default defineNuxtPlugin(() => {
  const apiFetch = $fetch.create({
    baseURL: useRuntimeConfig().public.apiBase,
    credentials: 'include',

    async onRequest({ options }) {
      const tokenCookie = useCookie('token')
      const token = tokenCookie.value

      ;(options as any).headers = {
        ...(options.headers || {}),
        Accept: 'application/json',
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      }
    },

    async onResponseError({ request, options, response }) {
      if (response.status === 401) {
        const authStore = useAuthStore()
        const token = authStore.token?.value

        if (token) {
          // Attempt to refresh (deduped inside the store)
          const refreshed = await authStore.refreshToken()

          if (refreshed) {
            // Retry the original request with the new token
            const newToken = useCookie('token').value
            const retryHeaders = {
              ...(options.headers || {}),
              Accept: 'application/json',
              Authorization: `Bearer ${newToken}`,
            }
            // Re-issue the same request — the caller will receive this result
            // We throw the retry so $fetch surfaces it correctly
            const retryResponse = await $fetch.raw(request as string, {
              ...options,
              headers: retryHeaders,
              baseURL: useRuntimeConfig().public.apiBase,
            })
            // Patch the original response object so the caller gets the retried data
            Object.assign(response, retryResponse)
            return
          }

          // Refresh failed — log out
          await authStore.logout()
          return
        } else {
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
