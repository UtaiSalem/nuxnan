<script setup lang="ts">
import { computed, ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { Icon } from '@iconify/vue'

interface MemberOption {
  userId: number
  name: string
  email: string
  avatar: string | null
  raw: any
}

interface Props {
  academyId: number | string
  placeholder?: string
  disabled?: boolean
  excludeIds?: number[]
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'ค้นหาสมาชิก...',
  disabled: false,
  excludeIds: () => [],
})

const emit = defineEmits<{
  selected: [member: MemberOption]
}>()

const { searchAcademyMembers } = useAcademyGroups()

const query = ref('')
const options = ref<MemberOption[]>([])
const isLoading = ref(false)
const isOpen = ref(false)
const activeIndex = ref(-1)
const triggerRef = ref<HTMLElement | null>(null)
const listboxId = `listbox-${Math.random().toString(36).substring(2, 9)}`

let searchTimer: ReturnType<typeof setTimeout> | null = null

const normalizedOptions = computed(() =>
  options.value.filter((item) => !props.excludeIds.includes(item.userId)),
)

const toOption = (item: any): MemberOption | null => {
  const user = item?.user ?? item ?? {}
  const userId = Number(item?.user_id ?? user?.id ?? item?.id ?? 0)
  if (!userId) return null

  return {
    userId,
    name: user?.name ?? item?.member_name ?? item?.name ?? `User #${userId}`,
    email: user?.email ?? item?.email ?? '',
    avatar: user?.profile_photo_url ?? user?.profile_photo_path ?? item?.member_avatar ?? item?.profile_photo_url ?? null,
    raw: item,
  }
}

const runSearch = async () => {
  const keyword = query.value.trim()

  if (!keyword || keyword.length < 2 || props.disabled) {
    options.value = []
    isOpen.value = false
    return
  }

  isLoading.value = true
  try {
    const response: any = await searchAcademyMembers(props.academyId, {
      search: keyword,
      status: 2,
      per_page: 8,
    })

    const items = response?.members?.data ?? response?.members ?? response?.data ?? []
    options.value = items
      .map(toOption)
      .filter(Boolean) as MemberOption[]
    isOpen.value = true
  } catch {
    options.value = []
    isOpen.value = false
  } finally {
    isLoading.value = false
  }
}

watch(query, () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(runSearch, 300)
})

watch(() => props.disabled, (disabled) => {
  if (disabled) {
    isOpen.value = false
    options.value = []
  }
})

const selectMember = (member: MemberOption) => {
  emit('selected', member)
  query.value = ''
  options.value = []
  isOpen.value = false
}

const onKeydown = (e: KeyboardEvent) => {
  if (!isOpen.value) {
    if ((e.key === 'ArrowDown' || e.key === 'ArrowUp') && normalizedOptions.value.length) {
      isOpen.value = true
      activeIndex.value = e.key === 'ArrowDown' ? 0 : normalizedOptions.value.length - 1
      e.preventDefault()
    }
    return
  }
  switch (e.key) {
    case 'ArrowDown':
      e.preventDefault()
      activeIndex.value = Math.min(activeIndex.value + 1, normalizedOptions.value.length - 1)
      break
    case 'ArrowUp':
      e.preventDefault()
      activeIndex.value = Math.max(activeIndex.value - 1, 0)
      break
    case 'Enter':
      e.preventDefault()
      if (activeIndex.value >= 0 && activeIndex.value < normalizedOptions.value.length) {
        selectMember(normalizedOptions.value[activeIndex.value])
      }
      break
    case 'Escape':
      e.preventDefault()
      isOpen.value = false
      break
  }
}

const closeDropdown = (e: MouseEvent) => {
  if (!triggerRef.value) return
  if (!triggerRef.value.contains(e.target as Node)) {
    isOpen.value = false
  }
}

watch([query, normalizedOptions], () => {
  activeIndex.value = -1
})

onMounted(() => {
  if (import.meta.client) {
    document.addEventListener('click', closeDropdown)
  }
})

onBeforeUnmount(() => {
  if (import.meta.client) {
    document.removeEventListener('click', closeDropdown)
  }
})
</script>

<template>
  <div ref="triggerRef" class="relative">
    <div
      class="relative"
      role="combobox"
      aria-haspopup="listbox"
      :aria-expanded="isOpen"
      :aria-controls="listboxId"
    >
      <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
      <input
        v-model="query"
        :disabled="disabled"
        type="text"
        :placeholder="placeholder"
        class="w-full pl-10 pr-10 py-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-vikinger-dark-100 text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-vikinger-purple/50 disabled:opacity-60 disabled:cursor-not-allowed"
        aria-autocomplete="list"
        :aria-controls="listboxId"
        :aria-activedescendant="activeIndex >= 0 ? `${listboxId}-option-${activeIndex}` : undefined"
        @focus="query.trim().length >= 2 && normalizedOptions.length ? (isOpen = true) : null"
        @keydown="onKeydown"
      />
      <Icon
        v-if="isLoading"
        icon="svg-spinners:ring-resize"
        class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-vikinger-purple"
      />
    </div>

    <div
      v-if="isOpen"
      :id="listboxId"
      role="listbox"
      class="absolute z-20 mt-2 w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-vikinger-dark-200 shadow-xl overflow-hidden"
    >
      <div v-if="normalizedOptions.length === 0" class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
        ไม่พบสมาชิกที่ตรงกับคำค้นหา
      </div>

      <button
        v-for="(member, index) in normalizedOptions"
        :key="member.userId"
        :id="`${listboxId}-option-${index}`"
        type="button"
        role="option"
        :aria-selected="activeIndex === index"
        class="w-full px-4 py-3 flex items-center gap-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700/60 transition-colors"
        :class="[
          activeIndex === index ? 'bg-gray-100 dark:bg-gray-700/80' : ''
        ]"
        @click="selectMember(member)"
      >
        <img
          v-if="member.avatar"
          :src="member.avatar"
          :alt="member.name"
          class="w-10 h-10 rounded-full object-cover"
        />
        <div
          v-else
          class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-300 flex items-center justify-center font-semibold"
        >
          {{ member.name.charAt(0) }}
        </div>
        <div class="min-w-0">
          <div class="font-medium text-gray-900 dark:text-white truncate">{{ member.name }}</div>
          <div v-if="member.email" class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ member.email }}</div>
        </div>
      </button>
    </div>
  </div>
</template>
