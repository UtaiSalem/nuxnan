export default defineNuxtRouteMiddleware(async (to, from) => {
  const authStore = useAuthStore()

  if (!authStore.isAuthenticated) {
    return // ไม่มี token → แสดงหน้า login ตามปกติ
  }

  // มี token แต่ user ยังไม่โหลด (เช่น เปิดแท็บใหม่) → fetch ก่อน
  if (!authStore.user) {
    try {
      await authStore.fetchUser()
    } catch {
      return // token หมดอายุ → แสดงหน้า login
    }
  }

  // login อยู่แล้วและเป็น admin → ข้ามไป dashboard
  if (authStore.user?.is_plearnd_admin || authStore.user?.is_super_admin) {
    return navigateTo('/nuxnan-admin')
  }

  // login อยู่แต่ไม่ใช่ admin → กลับหน้าหลัก
  return navigateTo('/play/newsfeed')
})
