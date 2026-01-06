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

    // Forward logout request to Laravel backend
    const response = await $fetch(
      `${config.public.apiBase}/api/logout`,
      {
        method: 'POST',
        headers: {
          Authorization: authHeader,
        },
      }
    )

    return response
  } catch (error: any) {
    console.error('Logout API error:', error)

    // Return success even if backend fails
    return { message: 'Logged out' }
  }
})
