<script setup lang="ts">
import { computed, ref, onMounted, onBeforeUnmount, watch } from 'vue'
import { Icon } from '@iconify/vue'
import { getAcademyGroupTypeMeta, GROUP_TYPE_COLOR_CLASSES } from '~/constants/academyGroupTypes'

interface PostableGroup {
  id: number
  name: string
  type: string
  role?: 'admin' | 'member' | null
}

interface Props {
  academyId: number | string
  /** Currently selected group id, or null = post as self */
  modelValue?: number | null
  /** Hide group selector and lock to this group (used in group profile page) */
  lockedGroupId?: number | null
  /** Optional metadata for the locked group to render immediately without API load */
  lockedGroupMeta?: { id: number; name: string; type: string } | null
  /** Current user (for "self" label/avatar) */
  user: { id: number; name: string; profile_photo_path?: string | null } | null
  /** Variant style */
  variant?: 'compact' | 'full'
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
  lockedGroupId: null,
  lockedGroupMeta: null,
  variant: 'full',
})

const emit = defineEmits<{
  'update:modelValue': [value: number | null]
}>()

const { getPostableGroups } = useAcademyGroups()

const postable = ref<PostableGroup[]>([])
const isLoading = ref(false)
const isOpen = ref(false)
const triggerRef = ref<HTMLElement | null>(null)
const activeIndex = ref(-1)

const selectedGroup = computed<PostableGroup | null>(() => {
  if (props.modelValue == null) return null
  return postable.value.find((g) => g.id === props.modelValue) ?? null
})

const displayLockedGroup = computed(() => {
  if (props.lockedGroupMeta) return props.lockedGroupMeta
  if (props.lockedGroupId == null) return null
  return postable.value.find((g) => g.id === props.lockedGroupId) ?? null
})

const optionsList = computed(() => {
  return [
    { id: null, name: props.user?.name || 'ฉัน' },
    ...postable.value.map(g => ({ id: g.id, name: g.name }))
  ]
})

const load = async () => {
  if (props.lockedGroupId != null && props.lockedGroupMeta) {
    return
  }
  isLoading.value = true
  try {
    postable.value = await getPostableGroups(props.academyId)
  } finally {
    isLoading.value = false
  }
}

const select = (id: number | null) => {
  emit('update:modelValue', id)
  isOpen.value = false
}

const close = (e: MouseEvent) => {
  if (!triggerRef.value) return
  if (!triggerRef.value.contains(e.target as Node)) isOpen.value = false
}

const onKeydown = (e: KeyboardEvent) => {
  if (!isOpen.value) {
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter') {
      isOpen.value = true
      e.preventDefault()
    }
    return
  }
  switch (e.key) {
    case 'ArrowDown':
      e.preventDefault()
      activeIndex.value = Math.min(activeIndex.value + 1, optionsList.value.length - 1)
      break
    case 'ArrowUp':
      e.preventDefault()
      activeIndex.value = Math.max(activeIndex.value - 1, 0)
      break
    case 'Enter':
      e.preventDefault()
      if (activeIndex.value >= 0 && activeIndex.value < optionsList.value.length) {
        select(optionsList.value[activeIndex.value].id)
      }
      break
    case 'Escape':
      e.preventDefault()
      isOpen.value = false
      break
  }
}

watch(isOpen, (open) => {
  if (open) {
    const idx = optionsList.value.findIndex(o => o.id === props.modelValue)
    activeIndex.value = idx >= 0 ? idx : 0
  } else {
    activeIndex.value = -1
  }
})

onMounted(() => {
  load()
  document.addEventListener('click', close)
})
onBeforeUnmount(() => document.removeEventListener('click', close))

watch(() => props.academyId, () => {
  load()
})

const roleLabel: Record<string, string> = { admin: 'หัวหน้า', member: 'สมาชิก' }
</script>

<template>
  <div ref="triggerRef" class="relative inline-block" @keydown="onKeydown">
    <!-- Locked state: just show the chip, no toggle -->
    <div
      v-if="displayLockedGroup"
      class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold bg-vikinger-purple/10 text-vikinger-purple border border-vikinger-purple/20"
    >
      <Icon :icon="getAcademyGroupTypeMeta(displayLockedGroup.type).icon" class="w-3.5 h-3.5" />
      <span>โพสต์ในนาม: <b>{{ displayLockedGroup.name }}</b></span>
    </div>

    <!-- Selectable trigger -->
    <button
      v-else
      type="button"
      aria-haspopup="listbox"
      :aria-expanded="isOpen"
      :class="[
        'inline-flex items-center gap-2 rounded-full font-semibold transition-colors border',
        variant === 'compact' ? 'px-2.5 py-1 text-[11px]' : 'px-3 py-1.5 text-xs',
        selectedGroup
          ? 'bg-vikinger-purple/10 text-vikinger-purple border-vikinger-purple/20'
          : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-transparent hover:bg-gray-200 dark:hover:bg-gray-600',
      ]"
      @click.stop="isOpen = !isOpen"
    >
      <Icon
        v-if="selectedGroup"
        :icon="getAcademyGroupTypeMeta(selectedGroup.type).icon"
        class="w-3.5 h-3.5"
      />
      <Icon v-else icon="heroicons:user" class="w-3.5 h-3.5" />
      <span>
        โพสต์ในนาม:
        <b>{{ selectedGroup ? selectedGroup.name : (user?.name || 'ฉัน') }}</b>
      </span>
      <Icon icon="heroicons:chevron-down" class="w-3 h-3 opacity-70" />
    </button>

    <!-- Dropdown -->
    <div
      v-if="isOpen && !displayLockedGroup"
      role="listbox"
      class="absolute z-50 mt-2 w-72 bg-white dark:bg-vikinger-dark-100 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden left-0"
    >
      <div class="px-3 py-2 border-b border-gray-100 dark:border-gray-700 text-[11px] font-bold uppercase text-gray-400">
        เลือกผู้โพสต์
      </div>

      <!-- Self -->
      <button
        type="button"
        role="option"
        :aria-selected="activeIndex === 0"
        class="w-full flex items-center gap-3 px-3 py-2 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 text-left"
        :class="[
          modelValue === null && 'bg-vikinger-purple/5',
          activeIndex === 0 && 'bg-gray-100 dark:bg-vikinger-dark-200'
        ]"
        @click="select(null)"
      >
        <div class="w-9 h-9 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden flex-shrink-0">
          <img v-if="user?.profile_photo_path" :src="user.profile_photo_path" class="w-full h-full object-cover" />
          <Icon v-else icon="heroicons:user" class="w-full h-full p-2 text-gray-400" />
        </div>
        <div class="min-w-0 flex-1">
          <div class="text-sm font-semibold text-gray-900 dark:text-white truncate">
            {{ user?.name || 'ฉัน' }}
          </div>
          <div class="text-[11px] text-gray-500">โพสต์เป็นตัวเอง</div>
        </div>
        <Icon
          v-if="modelValue === null"
          icon="heroicons:check-circle-solid"
          class="w-5 h-5 text-vikinger-purple flex-shrink-0"
        />
      </button>

      <!-- Group options -->
      <div v-if="isLoading" class="px-3 py-3 text-center text-xs text-gray-500">
        กำลังโหลด...
      </div>
      <div v-else-if="postable.length === 0" class="px-3 py-3 text-center text-xs text-gray-500">
        คุณยังไม่มีส่วนงานที่โพสต์ได้
      </div>
      <button
        v-for="(g, index) in postable"
        :key="g.id"
        type="button"
        role="option"
        :aria-selected="activeIndex === index + 1"
        class="w-full flex items-center gap-3 px-3 py-2 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 text-left"
        :class="[
          modelValue === g.id && 'bg-vikinger-purple/5',
          activeIndex === index + 1 && 'bg-gray-100 dark:bg-vikinger-dark-200'
        ]"
        @click="select(g.id)"
      >
        <div
          :class="[
            'w-9 h-9 rounded-lg bg-gradient-to-br flex items-center justify-center flex-shrink-0',
            GROUP_TYPE_COLOR_CLASSES[getAcademyGroupTypeMeta(g.type).color].gradient,
          ]"
        >
          <Icon :icon="getAcademyGroupTypeMeta(g.type).icon" class="w-4 h-4 text-white" />
        </div>
        <div class="min-w-0 flex-1">
          <div class="text-sm font-semibold text-gray-900 dark:text-white truncate">
            {{ g.name }}
          </div>
          <div class="text-[11px] text-gray-500">
            {{ getAcademyGroupTypeMeta(g.type).label }}
            <span v-if="g.role" class="mx-1 text-gray-300">·</span>
            <span v-if="g.role">{{ roleLabel[g.role] }}</span>
          </div>
        </div>
        <Icon
          v-if="modelValue === g.id"
          icon="heroicons:check-circle-solid"
          class="w-5 h-5 text-vikinger-purple flex-shrink-0"
        />
      </button>
    </div>
  </div>
</template>
