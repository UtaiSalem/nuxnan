<template>
  <div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">ประวัติการนำเข้าข้อมูล</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">ประวัติการนำเข้าข้อมูลนักเรียนทั้งหมดของสถาบัน</p>
      </div>
      <NuxtLink :to="`/academies/${academyName}/admin/students`" class="btn-ghost flex items-center gap-2">
        <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
        กลับไปทะเบียนนักเรียน
      </NuxtLink>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
      <Icon icon="fluent:history-24-regular" class="w-16 h-16 mx-auto text-gray-400 mb-4" />
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">กำลังอยู่ระหว่างการพัฒนา</h3>
      <p class="text-gray-500 dark:text-gray-400 mt-2">หน้านี้จะแสดงประวัติการนำเข้าทั้งหมดในอนาคต</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'

definePageMeta({
  layout: false,
  middleware: ['auth', 'academy-role'],
})

const route = useRoute()
const academyName = computed(() => route.params.name as string)
</script>
