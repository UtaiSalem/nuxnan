/**
 * Nuxt 3 Type Shims
 * These declarations help TypeScript understand Nuxt's auto-imports
 */

// Nuxt App
declare function useNuxtApp(): {
  $axios: import('axios').AxiosInstance
  $swal: {
    fire(title: string, text?: string, icon?: 'success' | 'error' | 'warning' | 'info' | 'question'): Promise<any>
    fire(options: any): Promise<any>
  }
}

// Vue
declare const ref: typeof import('vue')['ref']
declare const computed: typeof import('vue')['computed']
declare const watch: typeof import('vue')['watch']
declare const watchEffect: typeof import('vue')['watchEffect']
declare const onMounted: typeof import('vue')['onMounted']
declare const onUnmounted: typeof import('vue')['onUnmounted']
declare const reactive: typeof import('vue')['reactive']
declare const toRef: typeof import('vue')['toRef']
declare const toRefs: typeof import('vue')['toRefs']
declare const nextTick: typeof import('vue')['nextTick']

// Nuxt composables
declare function useRoute(): any
declare function useRouter(): any
declare function navigateTo(to: string, options?: any): Promise<void>
declare function useFetch<T>(url: string, options?: any): Promise<{ data: globalThis.Ref<T>; pending: globalThis.Ref<boolean>; error: globalThis.Ref<any> }>
declare function useAsyncData<T>(key: string, handler: () => Promise<T>): Promise<{ data: globalThis.Ref<T>; pending: globalThis.Ref<boolean>; error: globalThis.Ref<any> }>
declare function useState<T>(key: string, init?: () => T): globalThis.Ref<T>
declare function useCookie<T>(name: string): globalThis.Ref<T>
declare function useHead(head: any): void
declare function useRuntimeConfig(): any

// Pinia stores
declare function useAcademyRoleStore(): any
