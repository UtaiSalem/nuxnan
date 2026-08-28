<script setup lang="ts">
/**
 * ทำไมหน้านี้ถึงเป็นแค่ redirect:
 * หน้านี้เคยเป็นเปลือกที่ไม่มีข้อมูลลูกเลย ส่วนแดชบอร์ดจริงคือ `parent/index.vue` ซึ่งใช้ครบทั้ง 7 endpoint 
 * `dashboard/index.vue` ยังชี้มาที่พาธนี้ตาม role
 * จึงคงไฟล์ไว้เป็นทางผ่าน ไม่ลบทิ้ง
 */
definePageMeta({
  layout: 'main',
  middleware: ['auth']
})

const route = useRoute()
const router = useRouter()

onMounted(() => {
  router.replace(`/academies/${route.params.name}/parent`)
})
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900">
    <div class="animate-spin rounded-full h-12 w-12 border-4 border-primary-500 border-t-transparent"></div>
  </div>
</template>
