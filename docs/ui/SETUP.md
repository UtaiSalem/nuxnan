# 🚀 Setup Guide - Nuxni Pitnik

## การติดตั้งโปรเจค Nuxt.js ใหม่จากศูนย์

### ขั้นตอนที่ 1: สร้างโปรเจค Nuxt.js

```bash
# วิธีที่ 1: ใช้ npx
npx nuxi@latest init nuxni-pitnik

# วิธีที่ 2: ใช้ pnpm (แนะนำ)
pnpm dlx nuxi@latest init nuxni-pitnik

# วิธีที่ 3: ใช้ yarn
yarn dlx nuxi@latest init nuxni-pitnik
```

### ขั้นตอนที่ 2: เข้าไปยังโฟลเดอร์โปรเจค

```bash
cd nuxni-pitnik
```

### ขั้นตอนที่ 3: ติดตั้ง Dependencies พื้นฐาน

```bash
# ติดตั้ง Tailwind CSS
npm install -D @nuxtjs/tailwindcss

# ติดตั้ง Nuxt Icon
npm install @nuxt/icon

# ติดตั้ง Pinia (State Management)
npm install @pinia/nuxt pinia

# ติดตั้ง Color Mode
npm install -D @nuxtjs/color-mode

# ติดตั้ง Tailwind Plugins
npm install -D @tailwindcss/forms @tailwindcss/typography
```

### ขั้นตอนที่ 4: แก้ไข `nuxt.config.ts`

```typescript
// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2024-11-01',
  devtools: { enabled: true },
  
  modules: [
    '@nuxtjs/tailwindcss',
    '@nuxt/icon',
    '@pinia/nuxt',
    '@nuxtjs/color-mode'
  ],

  css: ['~/assets/css/main.css'],

  tailwindcss: {
    cssPath: '~/assets/css/main.css',
    configPath: 'tailwind.config.ts',
    exposeConfig: false,
    viewer: true,
  },

  colorMode: {
    classSuffix: '',
    preference: 'light',
    fallback: 'light'
  },

  app: {
    head: {
      title: 'Pitnik - Social Community App',
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
        { name: 'description', content: 'Pitnik is a modern social community app' }
      ]
    },
    pageTransition: { name: 'page', mode: 'out-in' }
  }
})
```

### ขั้นตอนที่ 5: สร้าง `tailwind.config.ts`

```typescript
import type { Config } from 'tailwindcss'

export default <Partial<Config>>{
  content: [
    './components/**/*.{js,vue,ts}',
    './layouts/**/*.vue',
    './pages/**/*.vue',
    './plugins/**/*.{js,ts}',
    './app.vue',
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#fef2f2',
          100: '#fee2e2',
          200: '#fecaca',
          300: '#fca5a5',
          400: '#f87171',
          500: '#ef4444',
          600: '#dc2626',
          700: '#b91c1c',
          800: '#991b1b',
          900: '#7f1d1d',
        },
        secondary: {
          50: '#f8fafc',
          100: '#f1f5f9',
          200: '#e2e8f0',
          300: '#cbd5e1',
          400: '#94a3b8',
          500: '#64748b',
          600: '#475569',
          700: '#334155',
          800: '#1e293b',
          900: '#0f172a',
        }
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'),
  ],
}
```

### ขั้นตอนที่ 6: สร้าง `assets/css/main.css`

```css
@tailwind base;
@tailwind components;
@tailwind utilities;

@layer base {
  body {
    @apply font-sans text-secondary-700 bg-gray-50;
  }
}

@layer components {
  .btn-primary {
    @apply px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors;
  }
}
```

### ขั้นตอนที่ 7: สร้างโครงสร้างโฟลเดอร์

```bash
# Windows PowerShell
mkdir components, layouts, pages, composables, public\images

# หรือสร้างทีละโฟลเดอร์
mkdir components
mkdir layouts
mkdir pages
mkdir composables
mkdir public
mkdir public\images
```

### ขั้นตอนที่ 8: สร้าง `app.vue`

```vue
<template>
  <div>
    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
  </div>
</template>
```

### ขั้นตอนที่ 9: รันโปรเจค

```bash
npm run dev
```

เปิดเบราว์เซอร์ที่ `http://localhost:3000`

---

## การ Copy โครงสร้างจากโปรเจคนี้

หากต้องการใช้โครงสร้างที่สร้างไว้แล้ว:

### 1. Copy ทั้งโฟลเดอร์ `nuxni-pitnik`

```bash
# Copy ไปยังตำแหน่งที่ต้องการ
cp -r nuxni-pitnik /path/to/your/project
```

### 2. ติดตั้ง Dependencies

```bash
cd nuxni-pitnik
npm install
```

### 3. รันโปรเจค

```bash
npm run dev
```

---

## โครงสร้างไฟล์ที่สำคัญ

```
nuxni-pitnik/
├── app.vue                    # Root component
├── nuxt.config.ts             # Nuxt configuration
├── tailwind.config.ts         # Tailwind configuration
├── package.json               # Dependencies
├── assets/
│   └── css/
│       └── main.css           # Global styles
├── components/
│   ├── App/
│   │   ├── Header.vue         # Top navigation
│   │   ├── BottomNav.vue      # Bottom navigation
│   │   └── Sidebar.vue        # Sidebar menu
│   ├── Post/
│   │   ├── Card.vue           # Post card
│   │   └── CreateCard.vue     # Create post widget
│   └── ...                    # Other components
├── layouts/
│   ├── default.vue            # Default layout
│   └── auth.vue               # Auth layout
├── pages/
│   ├── index.vue              # Welcome page
│   ├── login.vue              # Login page
│   ├── newsfeed.vue           # Main feed
│   ├── timeline.vue           # User profile
│   ├── groups.vue             # Groups page
│   ├── events.vue             # Events page
│   ├── marketplace.vue        # Marketplace
│   ├── messages.vue           # Messages
│   ├── notifications.vue      # Notifications
│   └── settings.vue           # Settings
├── composables/
│   ├── usePosts.ts            # Posts composable
│   └── useUserProfile.ts      # User profile composable
└── public/
    └── images/                # Static images
```

---

## คำสั่ง npm ที่สำคัญ

```bash
# Development
npm run dev              # รัน dev server

# Production
npm run build            # Build สำหรับ production
npm run preview          # Preview production build

# Generate Static Site
npm run generate         # Generate static site (SSG)

# Other
npm run postinstall      # Prepare Nuxt
```

---

## การเพิ่มหน้าใหม่

### 1. สร้างไฟล์ใน `pages/`

```bash
# สร้างหน้า About
touch pages/about.vue
```

### 2. เขียน Component

```vue
<template>
  <div>
    <h1>About Page</h1>
  </div>
</template>

<script setup lang="ts">
useHead({
  title: 'About'
})
</script>
```

### 3. Routing อัตโนมัติ

Nuxt จะสร้าง route `/about` ให้อัตโนมัติ

---

## การเพิ่ม Component ใหม่

### 1. สร้างไฟล์ใน `components/`

```bash
# สร้าง component ใหม่
touch components/MyComponent.vue
```

### 2. เขียน Component

```vue
<template>
  <div class="my-component">
    {{ message }}
  </div>
</template>

<script setup lang="ts">
interface Props {
  message: string
}

defineProps<Props>()
</script>
```

### 3. ใช้งานใน Page

```vue
<template>
  <div>
    <MyComponent message="Hello World" />
  </div>
</template>
```

**หมายเหตุ**: Nuxt จะ auto-import components ให้อัตโนมัติ

---

## Tips & Troubleshooting

### ปัญหา: Module not found

```bash
# ลบ node_modules และติดตั้งใหม่
rm -rf node_modules
rm package-lock.json
npm install
```

### ปัญหา: Tailwind ไม่ทำงาน

1. ตรวจสอบว่า `@nuxtjs/tailwindcss` อยู่ใน `modules` ของ `nuxt.config.ts`
2. ตรวจสอบว่ามีไฟล์ `assets/css/main.css` และมี `@tailwind` directives
3. Restart dev server

### ปัญหา: Icons ไม่แสดง

```bash
# ติดตั้ง @nuxt/icon ใหม่
npm install @nuxt/icon
```

ตรวจสอบว่า `@nuxt/icon` อยู่ใน `modules` ของ `nuxt.config.ts`

---

## การ Deploy

### Vercel

```bash
# ติดตั้ง Vercel CLI
npm i -g vercel

# Deploy
vercel
```

### Netlify

```bash
# Build command
npm run build

# Publish directory
.output/public
```

### Static Hosting (GitHub Pages, etc.)

```bash
# Generate static site
npm run generate

# Deploy โฟลเดอร์ .output/public
```

---

## Resources

- [Nuxt.js Documentation](https://nuxt.com/)
- [Vue.js Documentation](https://vuejs.org/)
- [Tailwind CSS Documentation](https://tailwindcss.com/)
- [Nuxt Icon](https://github.com/nuxt/icon)
- [Pinia Documentation](https://pinia.vuejs.org/)

---

**Happy Coding! 🚀**
