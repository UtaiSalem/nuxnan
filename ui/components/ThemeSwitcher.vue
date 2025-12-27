<script setup>
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
import { useUIStore } from '~/stores/ui'

const uiStore = useUIStore()

const isDarkMode = computed(() => uiStore.isDarkMode)

const toggleTheme = () => {
    uiStore.toggleTheme()
}

const themeLabel = computed(() => isDarkMode.value ? 'สลับไปโหมดสว่าง' : 'สลับไปโหมดมืด')
</script>

<template>
    <button
        @click="toggleTheme"
        :title="themeLabel"
        :aria-label="themeLabel"
        class="relative flex items-center justify-center w-10 h-10 text-gray-300 transition-all duration-300 rounded-lg hover:bg-slate-600 dark:hover:bg-slate-800 hover:text-white focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 focus:ring-offset-slate-700 dark:focus:ring-offset-slate-900"
    >
        <!-- Moon Icon (Show in Light Mode - click to go dark) -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 -rotate-90 scale-0"
            enter-to-class="opacity-100 rotate-0 scale-100"
            leave-active-class="transition-all duration-300 ease-in"
            leave-from-class="opacity-100 rotate-0 scale-100"
            leave-to-class="opacity-0 rotate-90 scale-0"
        >
            <Icon
                v-if="!isDarkMode"
                icon="solar:moon-stars-bold"
                class="absolute w-6 h-6 text-indigo-400"
            />
        </Transition>

        <!-- Sun Icon (Show in Dark Mode - click to go light) -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 rotate-90 scale-0"
            enter-to-class="opacity-100 rotate-0 scale-100"
            leave-active-class="transition-all duration-300 ease-in"
            leave-from-class="opacity-100 rotate-0 scale-100"
            leave-to-class="opacity-0 -rotate-90 scale-0"
        >
            <Icon
                v-if="isDarkMode"
                icon="solar:sun-bold"
                class="absolute w-6 h-6 text-yellow-400"
            />
        </Transition>
    </button>
</template>
