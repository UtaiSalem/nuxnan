import axios from 'axios'
import { useAuthStore } from '~/stores/auth'

export default defineNuxtPlugin(() => {
  const config = useRuntimeConfig()
  const apiBase = config.public.apiBase.endsWith('/') 
    ? config.public.apiBase.slice(0, -1) 
    : config.public.apiBase
    
  const baseURL = `${apiBase}/api`
  
  // ── 1. apiFetch ($fetch based) ────────────────────────────────────
  const apiFetch = $fetch.create({
    baseURL,
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
              baseURL,
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

  // ── 2. api (Axios based - for legacy/compat) ─────────────────────
  const api = axios.create({
    baseURL,
    withCredentials: true,
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    }
  })

  api.interceptors.request.use((config) => {
    const token = useCookie('token').value
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  })

  api.interceptors.response.use(
    (response) => response,
    async (error) => {
      const originalRequest = error.config
      if (error.response?.status === 401 && !originalRequest._retry) {
        originalRequest._retry = true
        const authStore = useAuthStore()
        const refreshed = await authStore.refreshToken()
        
        if (refreshed) {
          const newToken = useCookie('token').value
          originalRequest.headers.Authorization = `Bearer ${newToken}`
          return api(originalRequest)
        }
        
        await authStore.logout()
        navigateTo('/auth')
      }
      return Promise.reject(error)
    }
  )

  return {
    provide: {
      apiFetch,
      api,
      axios: api
    },
  }
})
