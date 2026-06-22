import { ref, onMounted, onBeforeUnmount, type Ref } from 'vue'

export const useIntersectionLoad = (el: Ref<HTMLElement | null>, onLoad: () => void) => {
  const loaded = ref(false)
  let observer: IntersectionObserver | null = null

  onMounted(() => {
    if (!el.value || !('IntersectionObserver' in window)) {
      // Fallback if not supported or missing element
      loaded.value = true
      onLoad()
      return
    }

    observer = new IntersectionObserver((entries) => {
      if (entries[0]?.isIntersecting && !loaded.value) {
        loaded.value = true
        onLoad()
        observer?.disconnect()
      }
    }, { rootMargin: '100px' }) // Start loading 100px before widget is scrolled into view

    observer.observe(el.value)
  })

  onBeforeUnmount(() => {
    if (observer) {
      observer.disconnect()
    }
  })

  return { loaded }
}
