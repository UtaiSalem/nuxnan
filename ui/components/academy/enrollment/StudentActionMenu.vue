<script setup lang="ts">
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import { Icon } from '@iconify/vue'
import type { ClassroomStudentDTO, EnrollmentAction, StudentSummaryDTO } from '~/types/enrollment'

interface Props {
  student: StudentSummaryDTO
  enrollment: ClassroomStudentDTO | null
}

defineProps<Props>()
const emit = defineEmits<{ select: [action: EnrollmentAction] }>()

interface MenuItemDef {
  action: EnrollmentAction
  label: string
  icon: string
  tone?: 'danger'
}

const items: MenuItemDef[] = [
  { action: 'transfer', label: 'ย้ายห้อง (ในปีนี้)', icon: 'mdi:arrow-right-bold' },
  { action: 'promote', label: 'เลื่อนชั้น (ปีถัดไป)', icon: 'mdi:arrow-up-bold' },
  { action: 'graduate', label: 'จบการศึกษา', icon: 'mdi:school' },
  { action: 'repeat', label: 'ซ้ำชั้น', icon: 'mdi:refresh' },
  { action: 'drop', label: 'ลาออก / พ้นสภาพ', icon: 'mdi:close-circle', tone: 'danger' },
]
</script>

<template>
  <Menu as="div" class="relative inline-block text-left">
    <MenuButton
      class="p-1.5 rounded-md text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200 transition focus:outline-none focus:ring-2 focus:ring-zinc-300 dark:focus:ring-zinc-700"
      aria-label="จัดการสถานะนักเรียน"
    >
      <Icon icon="mdi:dots-vertical" class="w-5 h-5" />
    </MenuButton>

    <transition
      enter-active-class="transition duration-100 ease-out"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition duration-75 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <MenuItems
        class="absolute right-0 z-30 mt-1 w-56 origin-top-right rounded-lg border border-zinc-200 bg-white py-1 shadow-lg focus:outline-none dark:border-zinc-700 dark:bg-zinc-900"
      >
        <MenuItem v-for="item in items" :key="item.action" v-slot="{ active }">
          <button
            type="button"
            :class="[
              'flex w-full items-center gap-2 px-3 py-2 text-sm transition',
              active ? 'bg-zinc-100 dark:bg-zinc-800' : '',
              item.tone === 'danger'
                ? 'text-rose-600 dark:text-rose-400'
                : 'text-zinc-700 dark:text-zinc-200',
            ]"
            @click="emit('select', item.action)"
          >
            <Icon :icon="item.icon" class="w-4 h-4 shrink-0" />
            <span>{{ item.label }}</span>
          </button>
        </MenuItem>
      </MenuItems>
    </transition>
  </Menu>
</template>
