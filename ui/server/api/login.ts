export default defineEventHandler(async (event) => {
  try {
    const body = await readBody(event)
    const config = useRuntimeConfig()

    // Forward login request to Laravel backend
    const response = await $fetch(`${config.public.apiBase}/api/login`, {
      method: 'POST',
      body: {
        login: body.login || body.email,
        password: body.password,
      },
    })

    return response
  } catch (error: any) {
    console.error('Login API error:', error)

    throw createError({
      statusCode: error.statusCode || error.response?.status || 401,
      statusMessage: error.data?.message || error.message || 'Login failed',
    })
  }
})
