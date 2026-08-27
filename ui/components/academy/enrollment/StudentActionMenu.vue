<script setup lang="ts">
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import { Icon } from '@iconify/vue'
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue'
import type { ClassroomStudentDTO, StudentMenuAction, StudentSummaryDTO } from '~/types/enrollment'

interface Props {
  student: StudentSummaryDTO
  enrollment: ClassroomStudentDTO | null
}

const props = defineProps<Props>()
const emit = defineEmits<{ select: [action: StudentMenuAction] }>()

interface MenuItemDef {
  action: StudentMenuAction
  label: string
  icon: string
  tone?: 'danger'
}

const lifecycleItems: MenuItemDef[] = [
  { action: 'transfer', label: 'ย้ายห้อง (ในปีนี้)', icon: 'mdi:arrow-right-bold' },
  { action: 'promote', label: 'เลื่อนชั้น (ปีถัดไป)', icon: 'mdi:arrow-up-bold' },
  { action: 'graduate', label: 'จบการศึกษา', icon: 'mdi:school' },
  { action: 'repeat', label: 'ซ้ำชั้น', icon: 'mdi:refresh' },
  { action: 'drop', label: 'ลาออก / พ้นสภาพ', icon: 'mdi:close-circle', tone: 'danger' },
]
const items = computed<MenuItemDef[]>(() => {
  if (props.enrollment) return lifecycleItems
  return [
    { action: 'assign', label: 'ลงห้องเรียน', icon: 'mdi:door-open' },
    { action: 'drop', label: 'ลาออก / พ้นสภาพ', icon: 'mdi:close-circle', tone: 'danger' },
  ]
})

const buttonRef = ref<any>(null)
const menuPanelRef = ref<any>(null)
const menuPosition = ref({ top: 0, left: 0 })
const hasPositioned = ref(false)

const updateMenuPosition = () => {
  const button = buttonRef.value?.$el ?? buttonRef.value
  const panel = menuPanelRef.value?.$el ?? menuPanelRef.value
  if (!button || !panel) return

  const rect = button.getBoundingClientRect()
  const panelHeight = panel.offsetHeight
  const panelWidth = panel.offsetWidth
  const left = Math.max(8, rect.right - panelWidth)
  const hasRoomBelow = rect.bottom + panelHeight <= window.innerHeight - 8
  const top = hasRoomBelow ? rect.bottom + 4 : rect.top - panelHeight - 4

  menuPosition.value = {
    top: Math.max(8, top),
    left,
  }
  hasPositioned.value = true
}

const scheduleMenuPosition = () => {
  hasPositioned.value = false
  nextTick(updateMenuPosition)
}

onMounted(() => {
  window.addEventListener('scroll', updateMenuPosition, true)
  window.addEventListener('resize', updateMenuPosition)
})

onUnmounted(() => {
  window.removeEventListener('scroll', updateMenuPosition, true)
  window.removeEventListener('resize', updateMenuPosition)
})
</script>

<template>
  <Menu as="div" class="relative inline-block text-left">
    <MenuButton
      ref="buttonRef"
      class="p-1.5 rounded-md text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200 transition focus:outline-none focus:ring-2 focus:ring-zinc-300 dark:focus:ring-zinc-700"
      aria-label="จัดการสถานะนักเรียน"
      @click="scheduleMenuPosition"
      @keydown="scheduleMenuPosition"
    >
      <Icon icon="mdi:dots-vertical" class="w-5 h-5" />
    </MenuButton>

    <Teleport to="body">
      <transition
        enter-active-class="transition duration-100 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-75 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
      >
        <MenuItems
          ref="menuPanelRef"
          :style="{ top: `${menuPosition.top}px`, left: `${menuPosition.left}px`, visibility: hasPositioned ? 'visible' : 'hidden' }"
          class="fixed z-50 w-56 rounded-lg border border-zinc-200 bg-white py-1 shadow-lg focus:outline-none dark:border-zinc-700 dark:bg-zinc-900"
        >
          <div class="border-b border-zinc-200 px-3 py-2 text-xs text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
            ห้องปัจจุบัน: {{ enrollment?.classroom?.display_name ?? 'ยังไม่มีห้องเรียน' }}
          </div>
          <MenuItem v-for="item in items" :key="item.action" v-slot="{ active }">
            <button class="min-h-[44px] sm:min-h-0"
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
    </Teleport>
  </Menu>
</template>
