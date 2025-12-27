import { reactive } from 'vue'
import { useAuthStore } from '~/stores/auth'

function createRouteHelper(getCurrentPath: () => string) {
  const resolveName = (name: string) => {
    // Minimal mapping for the most common legacy Jetstream routes.
    const map: Record<string, string> = {
      welcome: '/',
      newsfeed: '/newsfeed',
      dashboard: '/dashboard',
      'profile.show': '/profile',
      logout: '/logout',
    }

    if (name in map) return map[name]
    if (name.startsWith('/')) return name

    // Best-effort fallback: turn dotted route names into paths.
    // e.g. teams.show -> /teams/show
    return `/${name.replace(/\./g, '/')}`
  }

  const routeFn: any = (name?: string) => {
    if (!name) {
      return {
        current: (target: string) => {
          const currentPath = getCurrentPath()
          const expected = resolveName(target)
          return currentPath === expected || currentPath.startsWith(expected + '/')
        },
      }
    }

    return resolveName(name)
  }

  return routeFn
}

export default defineNuxtPlugin((nuxtApp) => {
  const authStore = useAuthStore()

  const page = reactive<any>({
    props: {
      auth: {
        user: authStore.user || null,
      },
      jetstream: {
        managesProfilePhotos: true,
      },
      // Provide safe nested shapes used by some legacy stores/components.
      course: { data: {} },
      courseMemberOfAuth: null,
    },
    url: '/',
  })

  const updateUrl = () => {
    const fullPath = (nuxtApp.$router as any)?.currentRoute?.value?.fullPath
    page.url = typeof fullPath === 'string' ? fullPath : '/'
    
    // Sync authStore and page props bidirectionally
    if (!authStore.user && page.props.auth.user) {
      // If authStore is empty but page has user, populate authStore
      authStore.user = page.props.auth.user
    } else if (authStore.user && !page.props.auth.user) {
      // If authStore has user but page doesn't, populate page
      page.props.auth.user = authStore.user
    }
    
    // Always keep page in sync with authStore for reactivity
    if (authStore.user) {
      page.props.auth.user = authStore.user
    }
  }

  updateUrl()
  nuxtApp.hook('page:finish', updateUrl)

  const route = createRouteHelper(() => page.url)

  nuxtApp.provide('inertiaPage', page)

  // Legacy templates expect these globals (Inertia / Ziggy style)
  nuxtApp.vueApp.config.globalProperties.$page = page
  nuxtApp.vueApp.config.globalProperties.route = route
})
