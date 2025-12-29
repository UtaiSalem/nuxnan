<script setup lang="ts">
import { ref, computed, nextTick } from 'vue'
import { Icon } from '@iconify/vue'

const { $apiFetch } = useNuxtApp()

interface Props {
  status: number | null | undefined
  attendanceId: number
  memberId: number
  isCourseAdmin?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isCourseAdmin: false
})

const emit = defineEmits(['update-status'])

// Local state
const currentStatus = ref(props.status)
const showMenu = ref(false)
const isLoading = ref(false)
const menuRef = ref<HTMLElement | null>(null)

// Watch for prop changes
watch(() => props.status, (newVal) => {
  currentStatus.value = newVal
})

// Status options
const statusOptions = [
  { value: 1, label: 'มา', icon: 'heroicons:check-circle', color: 'green' },
  { value: 2, label: 'สาย', icon: 'heroicons:clock', color: 'amber' },
  { value: 3, label: 'ลา', icon: 'heroicons:document-text', color: 'blue' },
  { value: null, label: 'ขาด', icon: 'heroicons:x-circle', color: 'red' },
]

// Get current status config
const currentStatusConfig = computed(() => {
  const status = currentStatus.value
  
  if (status === 1) {
    return { 
      label: 'มา', 
      icon: 'heroicons:check-circle-20-solid', 
      color: 'green', 
      bgClass: 'bg-green-50 dark:bg-green-900/40', 
      textClass: 'text-green-800 dark:text-green-200',
      iconClass: 'text-green-600 dark:text-green-400'
    }
  }
  if (status === 2) {
    return { 
      label: 'สาย', 
      icon: 'heroicons:clock-20-solid', 
      color: 'amber', 
      bgClass: 'bg-amber-50 dark:bg-amber-900/40', 
      textClass: 'text-amber-800 dark:text-amber-200',
      iconClass: 'text-amber-600 dark:text-amber-400'
    }
  }
  if (status === 3) {
    return { 
      label: 'ลา', 
      icon: 'heroicons:document-text-20-solid', 
      color: 'blue', 
      bgClass: 'bg-blue-50 dark:bg-blue-900/40', 
      textClass: 'text-blue-800 dark:text-blue-200',
      iconClass: 'text-blue-600 dark:text-blue-400'
    }
  }
  // null, undefined, 0 = ขาด
  return { 
    label: 'ขาด', 
    icon: 'heroicons:x-circle-20-solid', 
    color: 'red', 
    bgClass: 'bg-red-50 dark:bg-red-900/40', 
    textClass: 'text-red-800 dark:text-red-200',
    iconClass: 'text-red-600 dark:text-red-400'
  }
})

// Toggle menu
const toggleMenu = () => {
  if (!props.isCourseAdmin) return
  showMenu.value = !showMenu.value
}

// Close menu
const closeMenu = () => {
  showMenu.value = false
}

// Handle status selection
const selectStatus = async (newStatus: number | null) => {
  if (isLoading.value) return
  if (currentStatus.value === newStatus || (currentStatus.value === null && newStatus === null)) {
    closeMenu()
    return
  }
  
  isLoading.value = true
  
  try {
    // Call API to update status
    const apiStatus = newStatus === null ? 0 : newStatus
    await $apiFetch(`/api/attendances/${props.attendanceId}/member/${props.memberId}/update-status`, {
      method: 'POST',
      body: { status: apiStatus }
    })
    
    // Update local state
    currentStatus.value = newStatus
    emit('update-status', { attendanceId: props.attendanceId, memberId: props.memberId, status: newStatus })
    
  } catch (error) {
    console.error('Failed to update status:', error)
  } finally {
    isLoading.value = false
    await nextTick()
    closeMenu()
  }
}

// Click outside handler
const onClickOutside = (event: Event) => {
  if (menuRef.value && !menuRef.value.contains(event.target as Node)) {
    closeMenu()
  }
}

// Setup click outside listener
onMounted(() => {
  document.addEventListener('click', onClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', onClickOutside)
})
</script>

<template>
  <div ref="menuRef" class="relative inline-flex items-center gap-1">
    <!-- Status Badge -->
    <div
      :class="[
        'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold transition-all duration-200 border',
        currentStatusConfig.bgClass,
        currentStatusConfig.textClass,
        currentStatusConfig.color === 'green' ? 'border-green-200 dark:border-green-800' : '',
        currentStatusConfig.color === 'amber' ? 'border-amber-200 dark:border-amber-800' : '',
        currentStatusConfig.color === 'blue' ? 'border-blue-200 dark:border-blue-800' : '',
        currentStatusConfig.color === 'red' ? 'border-red-200 dark:border-red-800' : '',
        isCourseAdmin ? 'cursor-pointer hover:shadow-sm active:scale-95' : ''
      ]"
      @click="toggleMenu"
      :title="isCourseAdmin ? 'คลิกเพื่อเปลี่ยนสถานะ' : currentStatusConfig.label"
    >
      <Icon 
        :icon="isLoading ? 'svg-spinners:ring-resize' : currentStatusConfig.icon" 
        class="w-4 h-4"
        :class="currentStatusConfig.iconClass"
      />
      <span>{{ currentStatusConfig.label }}</span>
      <Icon 
        v-if="isCourseAdmin && !isLoading" 
        icon="heroicons:chevron-down" 
        class="w-3 h-3 ml-0.5 transition-transform duration-200"
        :class="[{ 'rotate-180': showMenu }, currentStatusConfig.iconClass]"
      />
    </div>

    <!-- Dropdown Menu -->
    <Transition
      enter-active-class="transition ease-out duration-150"
      enter-from-class="opacity-0 scale-95 -translate-y-1"
      enter-to-class="opacity-100 scale-100 translate-y-0"
      leave-active-class="transition ease-in duration-100"
      leave-from-class="opacity-100 scale-100 translate-y-0"
      leave-to-class="opacity-0 scale-95 -translate-y-1"
    >
      <div
        v-if="showMenu && isCourseAdmin"
        class="absolute top-full left-1/2 -translate-x-1/2 mt-2 w-36 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden z-50"
      >
        <!-- Header -->
        <div class="px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600">
          <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">เลือกสถานะ</p>
        </div>

        <!-- Options -->
        <div class="py-1">
          <button
            v-for="option in statusOptions"
            :key="option.value ?? 'null'"
            @click.stop="selectStatus(option.value)"
            :disabled="isLoading"
            class="w-full flex items-center gap-2.5 px-3 py-2.5 text-sm font-medium transition-all duration-150 hover:bg-gray-100 dark:hover:bg-gray-700/50"
            :class="[
              (currentStatus === option.value || (currentStatus === null && option.value === null) || (currentStatus === undefined && option.value === null) || (currentStatus === 0 && option.value === null))
                ? `bg-${option.color}-50 dark:bg-${option.color}-900/20 text-${option.color}-700 dark:text-${option.color}-300`
                : 'text-gray-700 dark:text-gray-300'
            ]"
          >
            <div
              :class="[
                'w-7 h-7 rounded-full flex items-center justify-center',
                option.color === 'green' ? 'bg-green-100 dark:bg-green-900/30' : '',
                option.color === 'amber' ? 'bg-amber-100 dark:bg-amber-900/30' : '',
                option.color === 'blue' ? 'bg-blue-100 dark:bg-blue-900/30' : '',
                option.color === 'red' ? 'bg-red-100 dark:bg-red-900/30' : '',
              ]"
            >
              <Icon 
                :icon="option.icon" 
                class="w-4 h-4"
                :class="[
                  option.color === 'green' ? 'text-green-600 dark:text-green-400' : '',
                  option.color === 'amber' ? 'text-amber-600 dark:text-amber-400' : '',
                  option.color === 'blue' ? 'text-blue-600 dark:text-blue-400' : '',
                  option.color === 'red' ? 'text-red-600 dark:text-red-400' : '',
                ]"
              />
            </div>
            <span class="flex-1 text-left">{{ option.label }}</span>
            <Icon 
              v-if="currentStatus === option.value || (currentStatus === null && option.value === null) || (currentStatus === undefined && option.value === null) || (currentStatus === 0 && option.value === null)"
              icon="heroicons:check" 
              class="w-4 h-4"
              :class="[
                option.color === 'green' ? 'text-green-600' : '',
                option.color === 'amber' ? 'text-amber-600' : '',
                option.color === 'blue' ? 'text-blue-600' : '',
                option.color === 'red' ? 'text-red-600' : '',
              ]"
            />
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
/* Ensure dropdown is above other elements */
.z-50 {
  z-index: 50;
}
</style>
