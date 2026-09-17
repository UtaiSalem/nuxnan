<script setup lang="ts">
import { ref, computed, nextTick, onMounted, onBeforeUnmount } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: null
  },
  options: {
    type: Array,
    default: () => []
  },
  optionValue: {
    type: String,
    default: 'id'
  },
  optionLabel: {
    type: [String, Function],
    default: 'name'
  },
  placeholder: {
    type: String,
    default: 'เลือกรายการ'
  },
  searchPlaceholder: {
    type: String,
    default: 'ค้นหา...'
  },
  disabled: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue'])

const isOpen = ref(false)
const searchQuery = ref('')
const searchInput = ref<HTMLInputElement | null>(null)
const containerRef = ref<HTMLDivElement | null>(null)

const getLabel = (option: any) => {
  if (typeof props.optionLabel === 'function') {
    return props.optionLabel(option)
  }
  return option[props.optionLabel]
}

const getValue = (option: any) => {
  return option[props.optionValue]
}

const selectedOption = computed(() => {
  return props.options.find(opt => getValue(opt) === props.modelValue)
})

const filteredOptions = computed(() => {
  if (!searchQuery.value) return props.options
  const lowerQuery = String(searchQuery.value).toLowerCase()
  return props.options.filter(opt => {
    return String(getLabel(opt)).toLowerCase().includes(lowerQuery)
  })
})

const toggleOpen = async () => {
  if (props.disabled) return
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    searchQuery.value = ''
    await nextTick()
    searchInput.value?.focus()
  }
}

const selectOption = (option: any) => {
  emit('update:modelValue', getValue(option))
  isOpen.value = false
  searchQuery.value = ''
}

const handleClickOutside = (event: MouseEvent) => {
  if (containerRef.value && !containerRef.value.contains(event.target as Node)) {
    isOpen.value = false
  }
}

const handleEscape = (event: KeyboardEvent) => {
  if (event.key === 'Escape') {
    isOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('mousedown', handleClickOutside)
  document.addEventListener('keydown', handleEscape)
})

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', handleClickOutside)
  document.removeEventListener('keydown', handleEscape)
})
</script>

<template>
  <div ref="containerRef" class="relative">
    <button
      type="button"
      @click="toggleOpen"
      :disabled="disabled"
      class="w-full px-4 py-2.5 min-h-[44px] bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white flex items-center gap-2"
      :class="{ 'opacity-50 cursor-not-allowed': disabled }"
    >
      <span class="min-w-0 flex-1 truncate text-left" :class="{ 'text-gray-400': !selectedOption }">
        {{ selectedOption ? getLabel(selectedOption) : placeholder }}
      </span>
      <Icon icon="fluent:chevron-down-24-regular" class="flex-shrink-0 w-5 h-5" />
    </button>

    <div
      v-if="isOpen"
      class="absolute z-30 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg overflow-hidden"
    >
      <input
        ref="searchInput"
        v-model="searchQuery"
        type="text"
        :placeholder="searchPlaceholder"
        class="w-full px-4 py-2.5 min-h-[44px] bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white outline-none"
      />
      <div class="max-h-60 overflow-y-auto">
        <template v-if="options.length === 0">
          <div class="px-4 py-3 text-center text-gray-500 dark:text-gray-400">
            ยังไม่มีรายการให้เลือก
          </div>
        </template>
        <template v-else-if="filteredOptions.length === 0">
          <div class="px-4 py-3 text-center text-gray-500 dark:text-gray-400">
            ไม่พบรายการที่ค้นหา
          </div>
        </template>
        <template v-else>
          <button
            v-for="option in filteredOptions"
            :key="getValue(option)"
            type="button"
            @click="selectOption(option)"
            class="w-full text-left px-4 py-2.5 min-h-[44px] hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-900 dark:text-white break-words"
            :class="{ 'bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 font-medium': getValue(option) === modelValue }"
          >
            {{ getLabel(option) }}
          </button>
        </template>
      </div>
    </div>
  </div>
</template>
