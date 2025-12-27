import { computed, defineComponent, h, reactive, resolveComponent } from 'vue'
import { useNuxtApp, navigateTo } from '#app'
import { Head as UnheadHead } from '@unhead/vue/components'

// This is a minimal compatibility shim for legacy Inertia (Jetstream) code
// that still exists in this Nuxt app. It is intentionally incomplete: it aims
// to prevent build/SSR crashes, not to fully replicate Inertia.

export const Head = UnheadHead

export const Link = defineComponent({
  name: 'InertiaLinkShim',
  inheritAttrs: false,
  props: {
    href: { type: String, default: '' },
    as: { type: String, default: undefined },
    method: { type: String, default: undefined },
    data: { type: Object as () => Record<string, any>, default: undefined },
    replace: { type: Boolean, default: false },
    preserveScroll: { type: Boolean, default: false },
    preserveState: { type: Boolean, default: false },
  },
  setup(props, { slots, attrs }) {
    const NuxtLink = resolveComponent('NuxtLink') as any

    // If legacy code uses <Link as="button">, render a real <button>.
    if (props.as === 'button') {
      return () =>
        h(
          'button',
          {
            type: (attrs as any).type ?? 'button',
            ...attrs,
          },
          slots.default?.(),
        )
    }

    return () =>
      h(
        NuxtLink,
        {
          to: props.href,
          ...attrs,
        },
        slots.default?.(),
      )
  },
})

export function usePage<TProps extends Record<string, any> = any>() {
  const nuxtApp = useNuxtApp() as any
  // Provided by our plugin. Falls back to safe defaults.
  return (nuxtApp.$inertiaPage || { props: {} }) as { props: TProps; url?: string }
}

function asPath(input: any) {
  if (typeof input !== 'string') return '/'
  return input.startsWith('/') ? input : `/${input}`
}

export const router = {
  async visit(url: string) {
    await navigateTo(url)
  },
  async get(url: string) {
    await navigateTo(url)
  },
  async post(url: string, _data?: any) {
    // Best-effort: if an endpoint exists, callers should migrate to real API calls.
    // We still try to navigate after POST-like actions (logout, etc.).
    try {
      const nuxtApp = useNuxtApp() as any
      const $fetch = nuxtApp.$fetch || globalThis.$fetch
      if (typeof $fetch === 'function') {
        await $fetch(url, { method: 'POST', body: _data })
      }
    } catch {
      // ignore
    }
    await navigateTo('/')
  },
  async put(url: string, data?: any) {
    return this.post(url, data)
  },
  async patch(url: string, data?: any) {
    return this.post(url, data)
  },
  async delete(url: string, data?: any) {
    return this.post(url, data)
  },
}

export function useForm<T extends Record<string, any> = Record<string, any>>(initial: T = {} as T) {
  const form: any = reactive({
    ...initial,
    processing: false,
    errors: {},
    hasErrors: computed(() => Object.keys(form.errors || {}).length > 0),
    recentlySuccessful: false,
    reset: (...keys: string[]) => {
      if (!keys.length) {
        Object.keys(initial).forEach((k) => (form[k] = (initial as any)[k]))
        return
      }
      keys.forEach((k) => (form[k] = (initial as any)[k]))
    },
    clearErrors: () => {
      form.errors = {}
    },
    setError: (key: string, value: any) => {
      form.errors = { ...(form.errors || {}), [key]: value }
    },
    async post(url: string, options: any = {}) {
      form.processing = true
      try {
        const nuxtApp = useNuxtApp() as any
        const $fetch = nuxtApp.$fetch || globalThis.$fetch
        if (typeof $fetch === 'function') {
          await $fetch(asPath(url), { method: 'POST', body: form })
        }
        form.recentlySuccessful = true
        options?.onSuccess?.()
      } catch (e: any) {
        options?.onError?.(e)
      } finally {
        form.processing = false
      }
    },
  })

  return form as T & {
    processing: boolean
    errors: Record<string, any>
    hasErrors: boolean
    recentlySuccessful: boolean
    reset: (...keys: string[]) => void
    clearErrors: () => void
    setError: (key: string, value: any) => void
    post: (url: string, options?: any) => Promise<void>
  }
}
