export default defineEventHandler(async (event) => {
  try {
    const authHeader = getHeader(event, 'authorization')
    const config = useRuntimeConfig()

    if (!authHeader) {
      throw createError({
        statusCode: 401,
        statusMessage: 'No authorization token provided',
      })
    }

    // Forward token refresh request to Laravel backend
    const response = await $fetch(
      `${config.public.apiBase || 'http://localhost:8000'}/api/refresh`,
      {
        method: 'POST',
        headers: {
          Authorization: authHeader,
        },
      }
    )

    return response
  } catch (error: any) {
    console.error('Refresh token API error:', error)

    throw createError({
      statusCode: error.statusCode || error.response?.status || 401,
      statusMessage: error.data?.message || error.message || 'Token refresh failed',
    })
  }
})
