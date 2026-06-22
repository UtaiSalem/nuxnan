<script setup lang="ts">
import { computed, ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { Icon } from '@iconify/vue'
import { getAcademyGroupTypeMeta } from '~/constants/academyGroupTypes'

interface Props {
  open: boolean
  group: any | null
  academyId: number | string
  initialTab?: TabKey
}

type TabKey = 'info' | 'admins' | 'members' | 'permissions' | 'delete'

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:open': [value: boolean]
  close: []
  updated: [group: any]
  deleted: [groupId: number]
}>()

const tabs: Array<{ key: TabKey; label: string; icon: string }> = [
  { key: 'info', label: 'ข้อมูล', icon: 'heroicons:information-circle' },
  { key: 'admins', label: 'หัวหน้า', icon: 'heroicons:star' },
  { key: 'members', label: 'สมาชิก', icon: 'heroicons:user-group' },
  { key: 'permissions', label: 'สิทธิ์', icon: 'heroicons:shield-check' },
  { key: 'delete', label: 'ลบ', icon: 'heroicons:trash' },
]

const activeTab = ref<TabKey>('info')
const containerRef = ref<HTMLElement | null>(null)

watch(() => props.open, (isOpen) => {
  if (isOpen) activeTab.value = props.initialTab ?? 'info'
})

const typeMeta = computed(() => props.group ? getAcademyGroupTypeMeta(props.group.type) : null)

useFocusTrap(containerRef, computed(() => props.open))

const handleKeydown = (e: KeyboardEvent) => {
  if (e.key === 'Escape' && props.open) {
    close()
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleKeydown)
})

const close = () => {
  emit('update:open', false)
  emit('close')
}

const handleUpdated = (group: any) => {
  emit('updated', group)
}

const handleDeleted = (groupId: number) => {
  emit('deleted', groupId)
  close()
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open && group"
      class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4"
      @click.self="close"
    >
      <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

      <div ref="containerRef" class="relative w-full max-w-4xl max-h-[92vh] overflow-hidden rounded-2xl bg-white dark:bg-vikinger-dark-200 shadow-2xl flex flex-col">
        <div class="px-5 sm:px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between gap-4">
          <div class="min-w-0 flex items-center gap-3">
            <span
              v-if="typeMeta"
              class="w-11 h-11 rounded-xl bg-vikinger-purple/10 text-vikinger-purple flex items-center justify-center flex-shrink-0"
            >
              <Icon :icon="typeMeta.icon" class="w-5 h-5" />
            </span>
            <div class="min-w-0">
              <h3 class="text-lg font-bold text-gray-900 dark:text-white truncate">
                จัดการ {{ group.name }}
              </h3>
              <p v-if="typeMeta" class="text-xs text-gray-500 dark:text-gray-400">{{ typeMeta.label }}</p>
            </div>
          </div>

          <button
            type="button"
            class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
            @click="close"
          >
            <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
          </button>
        </div>

        <div class="px-4 sm:px-6 pt-4 border-b border-gray-100 dark:border-gray-700 overflow-x-auto">
          <div class="flex gap-2 min-w-max pb-4">
            <button
              v-for="tab in tabs"
              :key="tab.key"
              type="button"
              :class="[
                'px-4 py-2 rounded-xl text-sm font-medium flex items-center gap-2 transition-colors',
                activeTab === tab.key
                  ? 'bg-vikinger-purple text-white'
                  : 'bg-gray-100 dark:bg-gray-700/60 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700',
              ]"
              @click="activeTab = tab.key"
            >
              <Icon :icon="tab.icon" class="w-4 h-4" />
              {{ tab.label }}
            </button>
          </div>
        </div>

        <div class="flex-1 overflow-y-auto px-4 sm:px-6 py-5">
          <AcademyGroupsManageTabInfo
            v-if="activeTab === 'info'"
            :group="group"
            @updated="handleUpdated"
          />

          <AcademyGroupsManageTabAdmins
            v-else-if="activeTab === 'admins'"
            :academy-id="academyId"
            :group="group"
          />

          <AcademyGroupsManageTabMembers
            v-else-if="activeTab === 'members'"
            :academy-id="academyId"
            :group="group"
          />

          <AcademyGroupsManageTabPermissions
            v-else-if="activeTab === 'permissions'"
            :academy-id="academyId"
            :group="group"
          />

          <AcademyGroupsManageTabDelete
            v-else
            :group="group"
            @cancel="close"
            @deleted="handleDeleted"
          />
        </div>
      </div>
    </div>
  </Teleport>
</template>
