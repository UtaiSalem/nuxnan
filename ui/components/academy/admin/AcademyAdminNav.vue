<script setup lang="ts">
import { Icon } from '@iconify/vue'
import type { AdminGroup, AdminSection } from '~/utils/academyAdminNav'

const props = defineProps<{
  groups: Array<AdminGroup & { sections: AdminSection[] }>
  currentId: string | null
  sectionTo: (path: string) => string
  badges?: Record<string, number>
}>()

const search = ref('')
const collapsed = ref<Set<string>>(new Set())
const storageKey = 'academy-admin-nav-collapsed'
const query = computed(() => search.value.trim().toLocaleLowerCase())
const filteredGroups = computed(() => props.groups
  .map(group => ({
    ...group,
    sections: group.sections.filter(section =>
      group.label.toLocaleLowerCase().includes(query.value)
      || section.label.toLocaleLowerCase().includes(query.value)
    ),
  }))
  .filter(group => group.sections.length > 0))

const isExpanded = (group: AdminGroup & { sections: AdminSection[] }) =>
  Boolean(query.value)
  || group.sections.some(section => section.id === props.currentId)
  || !collapsed.value.has(group.id)

const toggleGroup = (group: AdminGroup & { sections: AdminSection[] }) => {
  if (query.value || group.sections.some(section => section.id === props.currentId)) return
  const next = new Set(collapsed.value)
  if (next.has(group.id)) next.delete(group.id)
  else next.add(group.id)
  collapsed.value = next
}

onMounted(() => {
  try {
    const stored: unknown = JSON.parse(localStorage.getItem(storageKey) ?? '[]')
    if (Array.isArray(stored)) {
      collapsed.value = new Set(stored.filter((id): id is string => typeof id === 'string'))
    }
  } catch {
    // Navigation remains usable when storage is unavailable or malformed.
  }
})

watch(collapsed, value => {
  try {
    localStorage.setItem(storageKey, JSON.stringify([...value]))
  } catch {
    // Remember collapsed groups only when storage is available.
  }
})
</script>

<template>
  <nav aria-label="เมนูจัดการโรงเรียน" class="space-y-4 text-gray-900 dark:text-gray-100">
    <div class="relative rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-800">
      <Icon
        icon="fluent:search-24-regular"
        aria-hidden="true"
        class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-500 dark:text-gray-400"
      />
      <input
        v-model="search"
        type="search"
        aria-label="ค้นหาเมนู"
        placeholder="ค้นหาเมนู…"
        class="w-full min-w-0 min-h-[44px] rounded-lg border-0 bg-white py-2.5 pl-10 pr-12 text-sm text-gray-900
          placeholder:text-gray-500 focus:ring-2 focus:ring-primary-500 sm:min-h-0
          dark:bg-gray-800 dark:text-gray-100
          dark:placeholder:text-gray-400 dark:focus:ring-primary-400
          [&::-webkit-search-cancel-button]:appearance-none"
      />
      <button
        v-if="search"
        type="button"
        aria-label="ล้างคำค้นหา"
        class="absolute right-0 top-1/2 flex min-h-[44px] min-w-[44px] -translate-y-1/2 items-center justify-center rounded-lg
          text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500
          dark:text-gray-400
          dark:hover:bg-gray-700 dark:hover:text-gray-100
          dark:focus-visible:ring-primary-400"
        @click="search = ''"
      >
        <span aria-hidden="true" class="text-xl">×</span>
      </button>
    </div>

    <p
      v-if="filteredGroups.length === 0"
      role="status"
      class="rounded-lg border border-dashed border-gray-200 bg-gray-50 px-3 py-10 text-center text-sm text-gray-500
        dark:border-gray-700
        dark:bg-gray-800/50
        dark:text-gray-400"
    >
      ไม่พบเมนูที่ค้นหา
    </p>

    <div v-for="group in filteredGroups" :key="group.id">
      <button
        type="button"
        :aria-expanded="isExpanded(group)"
        class="mb-2 flex min-h-[44px] w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-[13px] font-normal uppercase tracking-[.18rem]
          text-gray-900 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 sm:min-h-0
          dark:text-gray-100
          dark:hover:bg-gray-800
          dark:focus-visible:ring-primary-400"
        @click="toggleGroup(group)"
      >
        <span class="min-w-0 flex-1 break-words">{{ group.label }}</span>
        <Icon
          icon="fluent:chevron-down-20-regular"
          aria-hidden="true"
          :class="['h-4 w-4 flex-shrink-0 text-gray-500 dark:text-gray-400', { '-rotate-90': !isExpanded(group) }]"
        />
      </button>
      <ul v-if="isExpanded(group)" class="flex flex-col gap-1">
        <li v-for="section in group.sections" :key="section.id" class="min-w-0">
          <NuxtLink
            :to="sectionTo(section.path)"
            :aria-current="currentId === section.id ? 'page' : undefined"
            :class="[
              `flex min-h-[44px] items-center gap-3 rounded-lg px-4 py-2.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 lg:min-h-0
                dark:focus-visible:ring-primary-400`,
              currentId === section.id
                ? `bg-primary-500 text-white shadow-[0_10px_20px_-10px_rgba(14,165,233,0.6)]
                    dark:bg-primary-500 dark:text-white`
                : `text-gray-600 hover:bg-primary-500/10 hover:text-primary-600
                    dark:text-gray-300
                    dark:hover:bg-primary-500/10
                    dark:hover:text-primary-400`,
            ]"
          >
            <Icon :icon="section.icon" aria-hidden="true" class="w-5 h-5 flex-shrink-0" />
            <span class="min-w-0 flex-1 break-words text-sm leading-tight">{{ section.label }}</span>
            <span
              v-if="(badges?.[section.id] ?? 0) > 0"
              class="flex-shrink-0 whitespace-nowrap rounded-full bg-red-500 px-2 py-0.5 text-xs text-white
                dark:bg-red-500
                dark:text-white"
            >
              {{ (badges?.[section.id] ?? 0) > 99 ? '99+' : badges?.[section.id] }}
            </span>
          </NuxtLink>
        </li>
      </ul>
    </div>
  </nav>
</template>
