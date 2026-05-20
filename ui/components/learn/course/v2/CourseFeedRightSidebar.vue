<script setup lang="ts">
import { Icon } from '@iconify/vue'

const props = defineProps<{
  course: any
}>()

const { getAvatarUrl } = useAvatar()

const teacherAvatar = computed(() =>
  props.course?.user ? getAvatarUrl(props.course.user) : null
)
</script>

<template>
  <div class="space-y-4">
    <!-- Course Teacher Widget -->
    <div v-if="course?.user" class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm">
      <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
        <Icon icon="fluent:person-24-regular" class="w-4 h-4 text-blue-500" />
        ผู้สอน
      </h3>
      <div class="flex items-center gap-3">
        <img
          :src="teacherAvatar"
          :alt="course.user.name"
          class="w-12 h-12 rounded-full object-cover border-2 border-blue-500"
        />
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
            {{ course.user.name }}
          </p>
          <p class="text-xs text-gray-500 dark:text-gray-400">ผู้สร้างรายวิชา</p>
        </div>
      </div>
    </div>

    <!-- Recent Activity Widget -->
    <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm">
      <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
        <Icon icon="fluent:flash-24-regular" class="w-4 h-4 text-yellow-500" />
        กิจกรรมล่าสุด
      </h3>
      <div class="space-y-3">
        <div class="flex items-start gap-3 text-sm">
          <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
            <Icon icon="fluent:document-add-24-regular" class="w-4 h-4 text-blue-500" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-gray-600 dark:text-gray-300 text-xs">มีเอกสารใหม่ถูกเพิ่ม</p>
            <p class="text-xs text-gray-400">2 ชั่วโมงที่แล้ว</p>
          </div>
        </div>
        <div class="flex items-start gap-3 text-sm">
          <div class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center flex-shrink-0">
            <Icon icon="fluent:person-add-24-regular" class="w-4 h-4 text-green-500" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-gray-600 dark:text-gray-300 text-xs">สมาชิกใหม่เข้าร่วม</p>
            <p class="text-xs text-gray-400">5 ชั่วโมงที่แล้ว</p>
          </div>
        </div>
        <div class="flex items-start gap-3 text-sm">
          <div class="w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center flex-shrink-0">
            <Icon icon="fluent:chat-24-regular" class="w-4 h-4 text-purple-500" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-gray-600 dark:text-gray-300 text-xs">มีโพสต์ใหม่ในฟีด</p>
            <p class="text-xs text-gray-400">เมื่อวาน</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Announcements Widget -->
    <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm">
      <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
        <Icon icon="fluent:megaphone-24-regular" class="w-4 h-4 text-red-500" />
        ประกาศสำคัญ
      </h3>
      <div class="space-y-2">
        <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
          <p class="text-xs text-yellow-800 dark:text-yellow-200 font-medium flex items-center gap-1">
            <Icon icon="fluent:alert-24-regular" class="w-3 h-3" />
            แจ้งเตือน
          </p>
          <p class="text-xs text-yellow-600 dark:text-yellow-300 mt-1">
            ส่งงานภายในวันที่กำหนด
          </p>
        </div>
      </div>
    </div>

    <!-- Upcoming Events Widget -->
    <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm">
      <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
        <Icon icon="fluent:calendar-24-regular" class="w-4 h-4 text-indigo-500" />
        กำหนดการ
      </h3>
      <div class="space-y-2">
        <div class="flex items-center gap-3 p-2 bg-gray-50 dark:bg-vikinger-dark-100 rounded-lg">
          <div class="text-center bg-indigo-500 text-white rounded-lg px-2 py-1 min-w-[40px]">
            <p class="text-xs font-bold">31</p>
            <p class="text-[10px]">ธ.ค.</p>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs font-medium text-gray-900 dark:text-white truncate">
              ส่งงานชิ้นที่ 1
            </p>
            <p class="text-[10px] text-gray-500 dark:text-gray-400">
              23:59 น.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
