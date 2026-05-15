// https://nuxt.com/docs/api/configuration/nuxt-config
import { fileURLToPath } from 'url'

export default defineNuxtConfig({
  compatibilityDate: '2024-04-04',
  devtools: { enabled: false },

  vite: {
    resolve: {
      alias: {
        '@inertiajs/vue3': fileURLToPath(new URL('./shims/inertia-vue3', import.meta.url)),
      },
    },
    // Optimize Vite build for memory efficiency
    build: {
      chunkSizeWarningLimit: 2000,
      rollupOptions: {
        external: [/^\/storage\//],
        output: {
          manualChunks: {
            'vendor-vue': ['vue', 'vue-router'],
            'vendor-ui': ['@headlessui/vue', '@iconify/vue'],
            'vendor-editor': ['@tiptap/vue-3', '@tiptap/starter-kit'],
          },
        },
      },
    },
    optimizeDeps: {
      include: ['vue', 'pinia', 'highlight.js'],
    },
    ssr: {
      // Bundle ESM-only packages for SSR instead of treating them as externals.
      // All @tiptap/* v3, lowlight v3+, and v3-infinite-loading ship as pure
      // ESM ("type":"module"), which causes "Unexpected token 'export'" in
      // Nitro's CJS server bundle without this setting.
      noExternal: [
        'lowlight',
        'highlight.js',
        'v3-infinite-loading',
        'plyr',
        'vue-plyr',
        'laravel-echo',
        /^@tiptap\/.*/,
      ],
    },
    server: {
      hmr: {
        timeout: 60000,
      },
      watch: {
        usePolling: true,
        interval: 2000,
        ignored: ['**/.nuxt/**', '**/node_modules/**', '**/.git/**', '**/dist/**'],
      },
    },
  },

  // Vite Node options for better IPC stability on Windows
  viteNode: {
    maxRetryAttempts: 10,
    baseRetryDelay: 500,
    maxRetryDelay: 5000,
    requestTimeout: 120000,
  },

  experimental: {
    viteEnvironmentApi: true,
    asyncContext: true,
    incrementalSession: true,
  },

  // Configure Vue to recognize Vidstack custom elements
  vue: {
    compilerOptions: {
      isCustomElement: (tag: string) => tag.startsWith('media-'),
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
      reverbEnabled: process.env.NUXT_PUBLIC_REVERB_ENABLED === 'true',
      reverbAppKey: process.env.NUXT_PUBLIC_REVERB_APP_KEY || '',
      reverbHost: process.env.NUXT_PUBLIC_REVERB_HOST || '',
      reverbPort: Number(process.env.NUXT_PUBLIC_REVERB_PORT || 8080),
      reverbScheme: process.env.NUXT_PUBLIC_REVERB_SCHEME || 'http',
    },
  },

  googleFonts: {
    families: {
      Inter: [300, 400, 500, 600, 700],
      Prompt: [300, 400, 500, 600, 700],
      Outfit: [300, 400, 500, 600, 700],
      Audiowide: [400],
    },
    display: 'swap',
    prefetch: true,
    preconnect: true,
  },

  css: [
    // '~/assets/css/theme.css', // Theme variables for light/dark mode
    // '~/assets/css/main.min.css',
    // '~/assets/css/style.css',
    // '~/assets/css/color.css',
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
      title: 'Nuxnan - Online Learning Community App',
      bodyAttrs: {
        class: 'bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-100 font-sans',
      },
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
        { name: 'description', content: 'Nuxnan Social Learning E-commerce' },
        { name: 'format-detection', content: 'telephone=no' },
      ],
      link: [{ rel: 'icon', type: 'image/png', href: '/favicon.png' }],
      script: [],
    },
    // Enable page transition for smoother navigation
    pageTransition: { name: 'page', mode: 'out-in' },
  },

  build: {
    transpile: ['lowlight', 'highlight.js', 'v3-infinite-loading', 'plyr', 'vue-plyr', 'laravel-echo', /^@tiptap\/.*/],
  },
})
