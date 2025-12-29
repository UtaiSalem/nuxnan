<template>
  <div class="relative inline-block text-left">
    <button
      @click="isOpen = !isOpen"
      type="button"
      class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
      :aria-expanded="isOpen"
      aria-haspopup="true"
    >
      <Icon name="heroicons:language" class="w-5 h-5" />
      <span>{{ currentLocaleName }}</span>
      <Icon
        name="heroicons:chevron-down"
        class="w-4 h-4 transition-transform"
        :class="{ 'rotate-180': isOpen }"
      />
    </button>

    <Transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="isOpen"
        class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-lg bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
        role="menu"
        aria-orientation="vertical"
      >
        <div class="py-1" role="none">
          <button
            v-for="locale in availableLocales"
            :key="locale.code"
            @click="switchLanguage(locale.code)"
            class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors flex items-center justify-between"
            :class="{
              'text-blue-600 dark:text-blue-400 font-semibold': locale.code === currentLocale,
              'text-gray-700 dark:text-gray-200': locale.code !== currentLocale,
            }"
            role="menuitem"
          >
            <span>{{ locale.name }}</span>
            <Icon
              v-if="locale.code === currentLocale"
              name="heroicons:check"
              class="w-5 h-5"
            />
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'

const { locale, locales, setLocale } = useI18n()
const isOpen = ref(false)

const currentLocale = computed(() => locale.value)

const availableLocales = computed(() => {
  return locales.value.map((loc: any) => ({
    code: loc.code,
    name: loc.name,
  }))
})

const currentLocaleName = computed(() => {
  const current = availableLocales.value.find((loc) => loc.code === currentLocale.value)
  return current?.name || 'Language'
})

const switchLanguage = async (code: string) => {
  await setLocale(code as 'th' | 'en')
  isOpen.value = false
}

// Close dropdown when clicking outside
if (process.client) {
  const handleClickOutside = (event: MouseEvent) => {
    const target = event.target as HTMLElement
    if (!target.closest('.relative')) {
      isOpen.value = false
    }
  }

  watch(isOpen, (newValue) => {
    if (newValue) {
      document.addEventListener('click', handleClickOutside)
    } else {
      document.removeEventListener('click', handleClickOutside)
    }
  })

  onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
  })
}
</script>
