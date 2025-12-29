// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2024-11-01',
  devtools: { enabled: true },

  vite: {
    resolve: {
      alias: {
        '@inertiajs/vue3': '/shims/inertia-vue3',
      },
    },
  },

  modules: ['@pinia/nuxt', '@nuxtjs/color-mode', '@nuxtjs/tailwindcss', '@nuxtjs/google-fonts', '@nuxtjs/i18n'],

  i18n: {
    locales: [
      {
        code: 'th',
        name: 'ภาษาไทย',
        file: 'th.json',
      },
      {
        code: 'en',
        name: 'English',
        file: 'en.json',
      },
    ],
    lazy: true,
    langDir: 'locales/',
    defaultLocale: 'th',
    strategy: 'no_prefix',
    detectBrowserLanguage: {
      useCookie: true,
      cookieKey: 'i18n_redirected',
      redirectOn: 'root',
      alwaysRedirect: false,
      fallbackLocale: 'th',
    },
  },


  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8000',
      siteUrl: process.env.NUXT_PUBLIC_SITE_URL || 'http://localhost:3000',
    },
  },

  googleFonts: {
    families: {
      Inter: [300, 400, 500, 600, 700],
      Prompt: [300, 400, 500, 600, 700],
      Outfit: [300, 400, 500, 600, 700],
    },
    display: 'swap',
    prefetch: true,
    preconnect: true,
  },

  css: [
    '~/assets/css/theme.css', // Theme variables for light/dark mode
    '~/assets/css/main.min.css',
    '~/assets/css/style.css',
    '~/assets/css/color.css',
    '~/assets/css/LineIcons.css',
    '~/assets/css/animate.min.css',
    '~/assets/css/icons.css', // Icon styles
    '~/assets/css/sweetalert-custom.css', // SweetAlert2 custom styles
    '~/assets/css/main.css', // Tailwind
  ],

  colorMode: {
    classSuffix: '',
    preference: 'system',
    fallback: 'light',
  },

  app: {
    head: {
      title: 'Nuxni - Online Learning Community App',
      bodyAttrs: {
        class: 'bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-100 font-sans',
      },
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
        { name: 'description', content: 'Pitnik Social Network' },
        { name: 'format-detection', content: 'telephone=no' },
      ],
      link: [{ rel: 'icon', type: 'image/png', href: '/favicon.png' }],
      script: [],
    },
    // Disable page transition to avoid warnings with multi-slot layouts
    pageTransition: false,
  },

})
