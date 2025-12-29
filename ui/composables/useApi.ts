/**
 * Custom composable for authenticated API calls
 * Automatically includes JWT token in headers
 */
export const useApi = () => {
  const authStore = useAuthStore()
  const config = useRuntimeConfig()

  /**
   * Make an authenticated API call
   * @param endpoint - API endpoint (e.g., '/api/newsfeed')
   * @param options - Fetch options
   */
  const call = async (endpoint: string, options: any = {}) => {
    const token = authStore.token
    const apiBase = config.public.apiBase as string

    if (!token) {
      throw new Error('No authentication token available')
    }

    // Build full URL - use apiBase for API calls
    const url = endpoint.startsWith('http') ? endpoint : `${apiBase}${endpoint}`

    try {
      const response = await $fetch(url, {
        ...options,
        headers: {
          ...options.headers,
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
        },
      })

      return response
    } catch (error: any) {
      // Log error details for debugging
      console.error('API Error:', {
        endpoint,
        url,
        status: error.statusCode || error.response?.status,
        data: error.data,
        message: error.message
      })
      // Handle 401 errors - try to refresh token
      if (error.statusCode === 401 || error.response?.status === 401) {
        const refreshed = await authStore.refreshToken()

        if (refreshed) {
          // Retry the original request with new token
          return await $fetch(url, {
            ...options,
            headers: {
              ...options.headers,
              Authorization: `Bearer ${authStore.token}`,
            },
          })
        } else {
          // Refresh failed, logout
          authStore.logout()
          throw new Error('Session expired. Please login again.')
        }
      }

      throw error
    }
  }

  /**
   * Make a GET request
   */
  const get = (endpoint: string, options: any = {}) => {
    return call(endpoint, { ...options, method: 'GET' })
  }

  /**
   * Make a POST request
   */
  const post = (endpoint: string, body: any, options: any = {}) => {
    return call(endpoint, { ...options, method: 'POST', body })
  }

  /**
   * Make a PUT request
   */
  const put = (endpoint: string, body: any, options: any = {}) => {
    return call(endpoint, { ...options, method: 'PUT', body })
  }

  /**
   * Make a PATCH request
   */
  const patch = (endpoint: string, body: any, options: any = {}) => {
    return call(endpoint, { ...options, method: 'PATCH', body })
  }

  /**
   * Make a DELETE request
   */
  const del = (endpoint: string, options: any = {}) => {
    return call(endpoint, { ...options, method: 'DELETE' })
  }

  return {
    call,
    get,
    post,
    put,
    patch,
    delete: del,
  }
}
