import { ref, onMounted, onBeforeUnmount } from 'vue'

export interface SidebarOptions {
  lgBreakpoint?: number
  xlBreakpoint?: number
  defaultCollapsed?: boolean
}

export function useResponsiveSidebar(options: SidebarOptions = {}) {
  const lg = options.lgBreakpoint || 1024
  const xl = options.xlBreakpoint || 1280
  
  const isCollapsed = ref(options.defaultCollapsed ?? false)
  const isMobileOpen = ref(false)
  const isDesktop = ref(false)
  const isWideDesktop = ref(false)

  const syncSidebarWithViewport = () => {
    if (typeof window === 'undefined') return
    
    const width = window.innerWidth
    isDesktop.value = width >= lg
    isWideDesktop.value = width >= xl

    if (width < lg) {
      // Mobile mode
      // We don't force isCollapsed here because the desktop sidebar is hidden anyway
      // But for consistency when switching to desktop, we might want a default
      isCollapsed.value = true
    } else if (width < xl) {
      // Tablet/Small Desktop (lg to xl)
      isCollapsed.value = true
      isMobileOpen.value = false
    } else {
      // Wide Desktop (>= xl)
      isCollapsed.value = false
      isMobileOpen.value = false
    }
  }

  const toggleSidebar = () => {
    if (typeof window === 'undefined') return
    if (window.innerWidth < lg) {
      isMobileOpen.value = !isMobileOpen.value
    } else {
      isCollapsed.value = !isCollapsed.value
    }
  }

  onMounted(() => {
    syncSidebarWithViewport()
    window.addEventListener('resize', syncSidebarWithViewport)
  })

  onBeforeUnmount(() => {
    window.removeEventListener('resize', syncSidebarWithViewport)
  })

  return {
    isCollapsed,
    isMobileOpen,
    isDesktop,
    isWideDesktop,
    toggleSidebar,
    syncSidebarWithViewport
  }
}
