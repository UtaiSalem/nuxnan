export default defineNuxtRouteMiddleware((to, from) => {
  const authStore = useAuthStore()

  // If user is already authenticated and is an admin, redirect to admin dashboard
  if (authStore.isAuthenticated) {
    if (authStore.user?.is_nuxnan_admin || authStore.user?.is_super_admin) {
      return navigateTo('/nuxnan-admin')
    }
    // If authenticated but not admin, stay on login page to show error
    // or redirect to regular site
    return navigateTo('/play/newsfeed')
  }
})
