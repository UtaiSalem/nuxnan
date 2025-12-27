export default defineEventHandler(async (event) => {
  try {
    // Get the authorization token from headers
    const authHeader = getHeader(event, 'authorization')

    if (!authHeader) {
      throw createError({
        statusCode: 401,
        statusMessage: 'Unauthorized - No token provided',
      })
    }

    // Forward request to Laravel backend
    const config = useRuntimeConfig()
    const apiBase = config.public.apiBase || 'http://localhost:8000'
    
    // Get query parameters (e.g., page for pagination)
    const query = getQuery(event)
    const queryString = new URLSearchParams(query as Record<string, string>).toString()
    const url = queryString ? `${apiBase}/api/newsfeed?${queryString}` : `${apiBase}/api/newsfeed`

    const response = await $fetch(url, {
      headers: {
        'Authorization': authHeader,
        'Accept': 'application/json',
      },
    })

    return response
  } catch (error: any) {
    console.error('Error in /api/newsfeed:', error)

    throw createError({
      statusCode: error.statusCode || error.status || 500,
      statusMessage: error.statusMessage || error.message || 'Internal Server Error',
    })
  }
})
