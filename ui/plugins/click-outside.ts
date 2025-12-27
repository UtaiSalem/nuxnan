/**
 * v-click-outside directive plugin
 * Detects clicks outside of an element
 */
export default defineNuxtPlugin((nuxtApp) => {
  nuxtApp.vueApp.directive('click-outside', {
    mounted(el, binding) {
      el._clickOutsideHandler = (event: MouseEvent) => {
        // Check if clicked outside the element
        if (!(el === event.target || el.contains(event.target as Node))) {
          binding.value(event)
        }
      }
      document.addEventListener('click', el._clickOutsideHandler)
    },
    beforeUnmount(el) {
      document.removeEventListener('click', el._clickOutsideHandler)
      delete el._clickOutsideHandler
    }
  })
})
