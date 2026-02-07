<template>
  <span
    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium transition-all"
    :class="[sizeClasses, clickable ? 'cursor-pointer hover:opacity-80' : '']"
    :style="{ backgroundColor: tag.color + '20', color: tag.color, borderColor: tag.color }"
    @click="$emit('click', tag)"
  >
    <Icon v-if="showIcon" icon="mdi:tag" class="w-3 h-3" />
    <span>{{ tag.name }}</span>
    <button
      v-if="removable"
      type="button"
      class="ml-0.5 -mr-1 hover:bg-black/10 rounded-full p-0.5"
      @click.stop="$emit('remove', tag)"
    >
      <Icon icon="mdi:close" class="w-3 h-3" />
    </button>
  </span>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Tag {
  id: number
  name: string
  color: string
}

interface Props {
  tag: Tag
  size?: 'sm' | 'md' | 'lg'
  removable?: boolean
  clickable?: boolean
  showIcon?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
  removable: false,
  clickable: false,
  showIcon: false,
})

defineEmits<{
  (e: 'click', tag: Tag): void
  (e: 'remove', tag: Tag): void
}>()

const sizeClasses = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'text-[10px] px-1.5 py-0'
    case 'lg':
      return 'text-sm px-3 py-1'
    default:
      return 'text-xs px-2 py-0.5'
  }
})
</script>
