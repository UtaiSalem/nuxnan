<template>
  <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
    <!-- Card 1 -->
    <div class="rounded-2xl border border-gray-100 bg-white p-3 shadow-sm sm:p-5 dark:border-gray-700 dark:bg-gray-800">
      <p class="break-words text-xs text-gray-500 sm:text-sm">สมาชิกในฝ่าย</p>
      <div class="mt-2 flex items-center justify-between gap-2">
        <div class="min-w-0 flex-1 truncate text-lg font-bold text-gray-900 sm:text-2xl dark:text-white">{{ membersCount || 0 }}</div>
        <div class="flex-shrink-0 rounded-xl bg-blue-100 p-2 text-blue-600 sm:p-3 dark:bg-blue-900/40 dark:text-blue-300">
          <Icon icon="fluent:people-24-filled" class="h-6 w-6" />
        </div>
      </div>
    </div>
    
    <!-- Card 2 -->
    <div class="rounded-2xl border border-gray-100 bg-white p-3 shadow-sm sm:p-5 dark:border-gray-700 dark:bg-gray-800">
      <p class="break-words text-xs text-gray-500 sm:text-sm">หัวหน้าฝ่าย</p>
      <div class="mt-2 flex items-center justify-between gap-2">
        <div :title="headName || 'ยังไม่กำหนด'" class="min-w-0 flex-1 truncate text-sm font-bold text-gray-900 sm:text-lg dark:text-white">{{ headName || 'ยังไม่กำหนด' }}</div>
        <div class="flex-shrink-0 rounded-xl bg-amber-100 p-2 text-amber-600 sm:p-3 dark:bg-amber-900/40 dark:text-amber-300">
          <Icon icon="fluent:person-star-24-filled" class="h-6 w-6" />
        </div>
      </div>
    </div>
    
    <!-- Card 3 -->
    <div class="rounded-2xl border border-gray-100 bg-white p-3 shadow-sm sm:p-5 dark:border-gray-700 dark:bg-gray-800">
      <p class="break-words text-xs text-gray-500 sm:text-sm">สิทธิ์ที่เปิดใช้</p>
      <div class="mt-2 flex items-center justify-between gap-2">
        <div class="min-w-0 flex-1 truncate text-lg font-bold text-gray-900 sm:text-2xl dark:text-white">{{ permissionCount || 0 }} รายการ</div>
        <div class="flex-shrink-0 rounded-xl bg-purple-100 p-2 text-purple-600 sm:p-3 dark:bg-purple-900/40 dark:text-purple-300">
          <Icon icon="fluent:lock-closed-key-24-filled" class="h-6 w-6" />
        </div>
      </div>
    </div>
    
    <!-- Card 4 -->
    <div class="rounded-2xl border border-gray-100 bg-white p-3 shadow-sm sm:p-5 dark:border-gray-700 dark:bg-gray-800">
      <p class="break-words text-xs text-gray-500 sm:text-sm">หน่วยงานย่อย</p>
      <div class="mt-2 flex items-center justify-between gap-2">
        <div class="min-w-0 flex-1 truncate text-lg font-bold text-gray-900 sm:text-2xl dark:text-white">{{ subUnitCount || 0 }}</div>
        <div class="flex-shrink-0 rounded-xl bg-emerald-100 p-2 text-emerald-600 sm:p-3 dark:bg-emerald-900/40 dark:text-emerald-300">
          <Icon icon="fluent:organization-24-filled" class="h-6 w-6" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'

defineProps<{
  membersCount: number
  headName: string | null
  permissionCount: number
  subUnitCount: number
  createdAt?: string | null
}>()
</script>
