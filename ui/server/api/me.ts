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

    // Forward request to Laravel backend
    const response = await $fetch(
      `${config.public.apiBase}/api/auth/me`,
      {
        method: 'GET',
        headers: {
          Authorization: authHeader,
        },
      }
    )

    return response
  } catch (error: any) {
    console.error('Get user API error:', error)

    throw createError({
      statusCode: error.statusCode || error.response?.status || 401,
      statusMessage: error.data?.message || error.message || 'Failed to fetch user',
    })
  }
})
