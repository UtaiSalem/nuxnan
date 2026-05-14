/**
 * Shim สำหรับ @inertiajs/vue3 → Nuxt equivalents
 * ไฟล์นี้แทนที่ Inertia API ด้วย Nuxt API เพื่อให้ไฟล์เก่าทำงานได้โดยไม่ต้องแก้ทีละไฟล์
 */
import { defineComponent, h, reactive, ref } from 'vue'

// ── router ──────────────────────────────────────────────────────────────────
// Inertia router → useRouter() / navigateTo() ของ Nuxt

export const router = {
  get(url, _data, _options) {
    return navigateTo(url)
  },
  post(url, _data, _options) {
    return navigateTo(url)
  },
  put(url, _data, _options) {
    return navigateTo(url)
  },
  patch(url, _data, _options) {
    return navigateTo(url)
  },
  delete(url, _options) {
    return navigateTo(url)
  },
  visit(url, _options) {
    return navigateTo(url)
  },
  reload(_options) {
    if (typeof window !== 'undefined') window.location.reload()
  },
  on(_event, _callback) {},
  off(_event, _callback) {},
}

// ── Link ─────────────────────────────────────────────────────────────────────
// Inertia <Link> → <NuxtLink>
export const Link = defineComponent({
  name: 'InertiaLink',
  props: {
    href: String,
    method: { type: String, default: 'get' },
    as: { type: String, default: 'a' },
  },
  setup(props, { slots }) {
    return () => h(resolveComponent('NuxtLink'), { to: props.href }, slots)
  },
})

// ── Head ──────────────────────────────────────────────────────────────────────
// Inertia <Head> → useHead() (renderless wrapper)
export const Head = defineComponent({
  name: 'InertiaHead',
  props: { title: String },
  setup(props, { slots }) {
    if (props.title) useHead({ title: props.title })
    return () => slots.default?.()
  },
})

// ── usePage ───────────────────────────────────────────────────────────────────
// Inertia usePage() → ข้อมูลจาก auth store ของ Nuxt
export function usePage() {
  const authStore = useAuthStore()
  return {
    props: {
      auth: { user: authStore.user },
      flash: {},
      errors: {},
    },
    component: '',
    url: typeof window !== 'undefined' ? window.location.pathname : '',
  }
}

// ── useForm ───────────────────────────────────────────────────────────────────
// Inertia useForm() → reactive form object อย่างง่าย
export function useForm(initialData = {}) {
  const data = reactive({ ...initialData })
  const processing = ref(false)
  const errors = reactive({})
  const wasSuccessful = ref(false)
  const recentlySuccessful = ref(false)

  return {
    ...data,
    processing,
    errors,
    wasSuccessful,
    recentlySuccessful,
    data() { return { ...data } },
    reset(...fields) {
      if (fields.length === 0) Object.assign(data, initialData)
      else fields.forEach(f => { data[f] = initialData[f] })
    },
    clearErrors(...fields) {
      if (fields.length === 0) Object.keys(errors).forEach(k => delete errors[k])
      else fields.forEach(f => delete errors[f])
    },
    setError(key, value) { errors[key] = value },
    get(url, options = {}) {
      processing.value = true
      return navigateTo(url).finally(() => { processing.value = false })
    },
    post(url, options = {}) {
      processing.value = true
      return navigateTo(url).finally(() => { processing.value = false })
    },
    put(url, options = {}) {
      processing.value = true
      return navigateTo(url).finally(() => { processing.value = false })
    },
    delete(url, options = {}) {
      processing.value = true
      return navigateTo(url).finally(() => { processing.value = false })
    },
    transform(callback) { return this },
  }
}
