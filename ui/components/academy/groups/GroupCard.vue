<script setup lang="ts">
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
import {
  getAcademyGroupTypeMeta,
  GROUP_TYPE_COLOR_CLASSES,
  type AcademyGroupTypeMeta,
} from '~/constants/academyGroupTypes'

interface AcademyGroup {
  id: number
  name: string
  description?: string | null
  type?: string | null
  members_count?: number | null
  posts_count?: number | null
}

interface Props {
  group: AcademyGroup
  /** Optional pre-resolved meta (skip lookup) — pass from a sectioned parent for perf */
  typeMeta?: AcademyGroupTypeMeta
  /** Show "จัดการ" button (Academy admin only) */
  canManage?: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{
  view: [group: AcademyGroup]
  manage: [group: AcademyGroup]
}>()

const meta = computed(() => props.typeMeta ?? getAcademyGroupTypeMeta(props.group.type))
const cls = computed(() => GROUP_TYPE_COLOR_CLASSES[meta.value.color])
</script>

<template>
  <div
    class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-shadow cursor-pointer flex flex-col"
    @click="emit('view', group)"
  >
    <!-- Gradient header strip -->
    <div :class="['h-14 bg-gradient-to-br', cls.gradient]"></div>

    <!-- Body -->
    <div class="p-4 flex flex-col gap-3 flex-1">
      <div class="flex items-start gap-3">
        <!-- Icon medallion -->
        <div
          :class="[
            'w-12 h-12 -mt-8 rounded-lg bg-gradient-to-br flex items-center justify-center border-2 border-white dark:border-vikinger-dark-200 shadow-md flex-shrink-0',
            cls.gradient,
          ]"
        >
          <Icon :icon="meta.icon" class="w-6 h-6 text-white" />
        </div>

        <!-- Title + type badge -->
        <div class="flex-1 pt-0 min-w-0">
          <div class="flex items-center gap-2 flex-wrap">
            <h4 class="font-semibold text-gray-900 dark:text-white truncate">
              {{ group.name }}
            </h4>
            <span
              :class="[
                'text-[10px] px-1.5 py-0.5 rounded-full font-bold flex-shrink-0',
                cls.badge,
              ]"
            >
              {{ meta.label }}
            </span>
          </div>
          <p
            v-if="group.description"
            class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2"
          >
            {{ group.description }}
          </p>
        </div>
      </div>

      <!-- Stats + actions -->
      <div class="mt-auto flex items-center justify-between text-sm">
        <div class="flex items-center gap-3 text-gray-500 dark:text-gray-400">
          <span class="inline-flex items-center gap-1">
            <Icon icon="fluent:people-24-regular" class="w-4 h-4" />
            {{ group.members_count || 0 }} สมาชิก
          </span>
          <span v-if="group.posts_count != null" class="inline-flex items-center gap-1">
            <Icon icon="fluent:chat-multiple-24-regular" class="w-4 h-4" />
            {{ group.posts_count }} โพสต์
          </span>
        </div>

        <div class="flex items-center gap-2">
          <button
            v-if="canManage"
            type="button"
            class="text-xs px-2 py-1 rounded font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
            @click.stop="emit('manage', group)"
          >
            จัดการ
          </button>
          <button
            type="button"
            class="text-vikinger-purple hover:text-vikinger-purple/80 font-medium text-sm"
            @click.stop="emit('view', group)"
          >
            ดูรายละเอียด
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
