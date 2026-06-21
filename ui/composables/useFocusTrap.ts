import { onMounted, onBeforeUnmount, type Ref } from 'vue'

export const useFocusTrap = (containerRef: Ref<HTMLElement | null>, active: Ref<boolean>) => {
  let previousActive: HTMLElement | null = null

  const trap = (e: KeyboardEvent) => {
    if (!active.value || !containerRef.value) return
    if (e.key !== 'Tab') return

    const focusables = containerRef.value.querySelectorAll<HTMLElement>(
      'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
    )
    if (!focusables.length) return

    const first = focusables[0]
    const last = focusables[focusables.length - 1]
    const activeEl = document.activeElement as HTMLElement

    if (e.shiftKey && activeEl === first) {
      e.preventDefault()
      last.focus()
    } else if (!e.shiftKey && activeEl === last) {
      e.preventDefault()
      first.focus()
    }
  }

  onMounted(() => {
    previousActive = document.activeElement as HTMLElement
    document.addEventListener('keydown', trap)
    // Initial focus
    requestAnimationFrame(() => {
      const focusable = containerRef.value?.querySelector<HTMLElement>(
        'input:not([disabled]), button:not([disabled]), textarea:not([disabled])'
      )
      focusable?.focus()
    })
  })

  onBeforeUnmount(() => {
    document.removeEventListener('keydown', trap)
    if (previousActive && typeof previousActive.focus === 'function') {
      previousActive.focus()
    }
  })
}
