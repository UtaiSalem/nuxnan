<script setup lang="ts">
/**
 * SA-1 — แสดงในกรอบเปลือกแอดมินเมื่อผู้ใช้ไม่มีสิทธิ์เปิด section ปัจจุบัน (แทนการเด้งออกเงียบ ๆ)
 */
import { Icon } from '@iconify/vue'
import type { AdminSection } from '~/utils/academyAdminNav'

const props = defineProps<{
  section: AdminSection | null
  academyName: string
}>()

const dashboardTo = computed(() => `/academies/${props.academyName}/admin`)
</script>

<template>
  <div class="flex flex-col items-center gap-4 px-3 py-10 text-center sm:py-16">
    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-amber-500/10 text-amber-600 dark:bg-amber-400/10 dark:text-amber-400">
      <Icon icon="fluent:shield-lock-24-regular" class="h-7 w-7" />
    </div>
    <h2 class="text-lg font-semibold text-gray-900 dark:text-white sm:text-xl">ไม่มีสิทธิ์เปิดหน้านี้</h2>

    <p v-if="!section" class="max-w-md break-words text-sm text-gray-600 dark:text-gray-300 sm:text-base">
      หน้านี้ยังไม่ได้ลงทะเบียนสิทธิ์ เปิดได้เฉพาะเจ้าของหรือผู้ดูแลโรงเรียน
    </p>
    <p v-else-if="section.adminOnly" class="max-w-md break-words text-sm text-gray-600 dark:text-gray-300 sm:text-base">
      หน้า "{{ section.label }}" เปิดได้เฉพาะเจ้าของหรือผู้ดูแลโรงเรียน
    </p>
    <template v-else>
      <p class="max-w-md break-words text-sm text-gray-600 dark:text-gray-300 sm:text-base">
        หน้า "{{ section.label }}" ต้องมีสิทธิ์อย่างน้อยหนึ่งข้อต่อไปนี้
      </p>
      <ul class="flex max-w-md flex-wrap justify-center gap-2">
        <li
          v-for="key in section.view"
          :key="key"
          class="rounded-md bg-gray-100 px-2 py-1 font-mono text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-200"
        >
          {{ key }}
        </li>
      </ul>
    </template>

    <p class="max-w-md break-words text-xs text-gray-500 dark:text-gray-400 sm:text-sm">
      ติดต่อผู้ดูแลโรงเรียนเพื่อขอสิทธิ์ที่หน้า "บทบาทและสิทธิ์"
    </p>
    <NuxtLink
      :to="dashboardTo"
      class="inline-flex min-h-[44px] items-center gap-2 rounded-lg bg-primary-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-primary-600 dark:hover:bg-primary-400 sm:min-h-0"
    >
      <Icon icon="fluent:arrow-left-24-regular" class="h-5 w-5" />
      กลับแดชบอร์ด
    </NuxtLink>
  </div>
</template>
