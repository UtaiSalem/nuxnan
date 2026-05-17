export default defineNuxtRouteMiddleware(async (to, from) => {
  const authStore = useAuthStore()

  // Check if user is authenticated (has token)
  if (!authStore.isAuthenticated) {
    // Redirect to login page
    return navigateTo('/auth')
  }

  // If we have a token but no user data yet, fetch it
  if (!authStore.user) {
    try {
      await authStore.fetchUser()
    } catch (error) {
      // If fetching user fails (e.g. invalid/expired token), redirect to login
      console.error('Middleware auth fetchUser failed:', error)
      return navigateTo('/auth')
    }
  }
})
