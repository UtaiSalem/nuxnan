export default defineNuxtRouteMiddleware(async (to, from) => {
  const authStore = useAuthStore()

  if (!authStore.isAuthenticated) {
    return navigateTo('/nuxnan-admin/login')
  }

  // เปิดแท็บใหม่ → Pinia reset → user เป็น null แม้มี token → fetch ก่อนเช็ค
  if (!authStore.user) {
    try {
      await authStore.fetchUser()
    } catch {
      return navigateTo('/nuxnan-admin/login')
    }
  }

  if (!authStore.user?.is_plearnd_admin && !authStore.user?.is_super_admin) {
    return navigateTo('/nuxnan-admin/login?error=unauthorized')
  }
})
