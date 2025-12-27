import { defineStore } from 'pinia'

export const useUIStore = defineStore('ui', () => {
    const isSidebarOpen = ref(true)
    const colorMode = useColorMode()

    // Computed property to get current theme
    const isDarkMode = computed(() => colorMode.value === 'dark')

    function toggleSidebar() {
        isSidebarOpen.value = !isSidebarOpen.value
    }

    function toggleTheme() {
        colorMode.preference = colorMode.value === 'dark' ? 'light' : 'dark'
    }

    function setTheme(theme: 'light' | 'dark' | 'system') {
        colorMode.preference = theme
    }

    return {
        isSidebarOpen,
        isDarkMode,
        colorMode,
        toggleSidebar,
        toggleTheme,
        setTheme
    }
})
