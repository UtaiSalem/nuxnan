<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { ADMIN_ACCESS_KEY, hasPermission } from '~/utils/academyAdminNav'
/**
 * Academy Dashboard Router
 * หน้านี้จะ redirect ไปยัง dashboard ที่เหมาะสมตามบทบาทของผู้ใช้
 */

definePageMeta({
  layout: 'main',
  middleware: ['auth']
})

const route = useRoute()
const router = useRouter()
const api = useApi()

const academyName = computed(() => route.params.name as string)
const academyId = ref<number | null>(null)
const isLoading = ref(true)
const error = ref<string | null>(null)

// Fetch academy and role, then redirect
onMounted(async () => {
  try {
    // First get academy info
    const academyResponse: any = await api.get(`/api/academies/${academyName.value}`)
    
    if (!academyResponse.success) {
      error.value = 'ไม่พบโรงเรียน'
      return
    }
    
    academyId.value = academyResponse.academy.id
    
    // Then get user's role
    const roleResponse: any = await api.get(`/api/academies/${academyId.value}/my-role`)
    
    if (!roleResponse.success) {
      // Not a member, redirect to guest view
      router.replace(`/academies/${academyName.value}`)
      return
    }

    // SA-1 — แอดมิน/บทบาทฝั่งบุคลากรไปหน้าแอดมิน · ครู/ผู้ปกครอง/นักเรียนไปแดชบอร์ดของตัว
    // (เดิมส่ง staff ไปแดชบอร์ดที่ไม่มีหน้า และส่งบทบาทอื่นไปแดชบอร์ดนักเรียน)
    const base = `/academies/${academyName.value}`
    const roleName = roleResponse.role?.name
    const permissions: string[] = roleResponse.permissions || []
    let target = base

    if (roleResponse.is_owner || roleResponse.is_admin) {
      target = `${base}/admin`
    } else if (roleName === 'teacher') {
      target = `${base}/dashboard/teacher`
    } else if (roleName === 'parent') {
      target = `${base}/dashboard/parent`
    } else if (roleName === 'student') {
      target = `${base}/dashboard/student`
    } else if (hasPermission(permissions, ADMIN_ACCESS_KEY)) {
      target = `${base}/admin`
    } else if (roleResponse.is_member) {
      target = `${base}/dashboard/student`
    }

    router.replace(target)
    
  } catch (err: any) {
    console.error('Dashboard routing error:', err)
    error.value = 'เกิดข้อผิดพลาด'
    // Fallback to main academy page
    router.replace(`/academies/${academyName.value}`)
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900">
    <div class="text-center">
      <!-- Loading -->
      <div v-if="isLoading" class="flex flex-col items-center gap-4">
        <div class="animate-spin rounded-full h-12 w-12 border-4 border-primary-500 border-t-transparent"></div>
        <p class="text-gray-600 dark:text-gray-400">กำลังโหลด Dashboard...</p>
      </div>
      
      <!-- Error -->
      <div v-else-if="error" class="flex flex-col items-center gap-4">
        <Icon icon="fluent:error-circle-24-regular" class="w-12 h-12 text-red-500" />
        <p class="text-red-500">{{ error }}</p>
        <NuxtLink 
          :to="`/academies/${academyName}`"
          class="text-primary-500 hover:underline"
        >
          กลับไปหน้าโรงเรียน
        </NuxtLink>
      </div>
    </div>
  </div>
</template>
