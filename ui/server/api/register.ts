export default defineEventHandler(async (event) => {
  try {
    const body = await readBody(event)
    const config = useRuntimeConfig()

    // Forward registration request to Laravel backend
    const response = await $fetch(
      `${config.public.apiBase || 'http://localhost:8000'}/api/register`,
      {
        method: 'POST',
        body: body,
      }
    )

    return response
  } catch (error: any) {
    console.error('Register API error:', error)

    throw createError({
      statusCode: error.statusCode || error.response?.status || 422,
      statusMessage: error.data?.message || error.message || 'Registration failed',
    })
  }
})
