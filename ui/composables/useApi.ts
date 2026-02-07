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

    // Check if body is FormData - don't set Content-Type for FormData
    const isFormData = options.body instanceof FormData
    
    try {
      const response = await $fetch(url, {
        ...options,
        headers: {
          ...options.headers,
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
          // Don't set Content-Type for FormData - browser will set it with boundary
          ...(isFormData ? {} : { 'Content-Type': 'application/json' }),
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

  /**
   * Download a file as Blob (for PDF/Excel exports)
   */
  const getBlob = async (endpoint: string, options: any = {}) => {
    const token = authStore.token
    const config = useRuntimeConfig()
    const apiBase = config.public.apiBase as string

    if (!token) {
      throw new Error('No authentication token available')
    }

    const url = endpoint.startsWith('http') ? endpoint : `${apiBase}${endpoint}`

    try {
      const response = await fetch(url, {
        method: 'GET',
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/octet-stream, application/pdf, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
          ...options.headers,
        },
      })

      if (!response.ok) {
        // Try to get error message from response
        const contentType = response.headers.get('content-type')
        if (contentType && contentType.includes('application/json')) {
          const errorData = await response.json()
          throw new Error(errorData.message || 'Failed to download file')
        }
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const blob = await response.blob()
      
      // Get filename from Content-Disposition header if available
      const contentDisposition = response.headers.get('Content-Disposition')
      let filename = 'download'
      if (contentDisposition) {
        const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/)
        if (filenameMatch) {
          filename = filenameMatch[1].replace(/['"]/g, '')
        }
      }

      return { blob, filename }
    } catch (error: any) {
      console.error('Blob download error:', error)
      throw error
    }
  }

  return {
    call,
    get,
    post,
    put,
    patch,
    delete: del,
    getBlob,
  }
}
