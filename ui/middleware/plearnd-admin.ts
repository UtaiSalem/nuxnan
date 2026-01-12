export default defineNuxtRouteMiddleware((to, from) => {
  const authStore = useAuthStore()

  // Check if user is authenticated
  if (!authStore.isAuthenticated) {
    return navigateTo('/auth')
  }

  // Check if user is a Plearnd Admin
  if (!authStore.user?.is_plearnd_admin && !authStore.user?.is_super_admin) {
    // Redirect to home page with error
    return navigateTo('/?error=unauthorized')
  }
})
