export const useDarkMode = () => {
    const uiStore = useUIStore()
    const colorMode = useColorMode()

    const toggleDarkMode = () => {
        const newVal = colorMode.value === 'light' ? 'dark' : 'light'
        colorMode.preference = newVal
        uiStore.setDarkMode(newVal === 'dark')
    }

    return {
        toggleDarkMode,
        isDark: computed(() => colorMode.value === 'dark')
    }
}
