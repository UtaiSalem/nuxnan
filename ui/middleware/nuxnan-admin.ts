export default defineNuxtRouteMiddleware((to, from) => {
  const authStore = useAuthStore()

  // Check if user is authenticated
  if (!authStore.isAuthenticated) {
    return navigateTo('/nuxnan-admin/login')
  }

  // Check if user is a Nuxnan Admin or Super Admin
  if (!authStore.user?.is_nuxnan_admin && !authStore.user?.is_super_admin) {
    // Redirect to login with error
    return navigateTo('/nuxnan-admin/login?error=unauthorized')
  }
})
