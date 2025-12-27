# Nuxni Pitnik - Modern Social Community App

โปรเจค **Nuxni Pitnik** เป็นการแปลง Template HTML เดิม (Pitnik) ให้เป็น Modern Web Application ด้วย **Nuxt.js 3**, **Vue 3 Composition API**, และ **Tailwind CSS**

## 🚀 เทคโนโลยีที่ใช้

- **Nuxt.js 3** - Full-stack Vue Framework
- **Vue 3** - Progressive JavaScript Framework (Composition API with `<script setup>`)
- **Tailwind CSS** - Utility-first CSS Framework
- **Nuxt Icon** - Icon System
- **Pinia** - State Management
- **TypeScript** - Type Safety

## 📁 โครงสร้างโปรเจค

```
nuxni-pitnik/
├── assets/
│   └── css/
│       └── main.css              # Tailwind CSS & Custom Styles
├── components/
│   ├── App/
│   │   ├── Header.vue            # Top Navigation Bar
│   │   ├── BottomNav.vue         # Bottom Navigation (Mobile)
│   │   └── Sidebar.vue           # Drawer Menu
│   ├── Post/
│   │   ├── Card.vue              # Post Card Component
│   │   └── CreateCard.vue        # Create Post Widget
│   ├── EventCard.vue             # Event Item Component
│   ├── GroupCard.vue             # Group Item Component
│   ├── ProductCard.vue           # Product Item Component
│   ├── StoriesSection.vue        # Stories Carousel
│   └── UserCard.vue              # User Profile Card
├── composables/
│   ├── usePosts.ts               # Posts Data & Logic
│   └── useUserProfile.ts         # User Profile Data
├── layouts/
│   ├── default.vue               # Main App Layout
│   └── auth.vue                  # Auth Pages Layout
├── pages/
│   ├── index.vue                 # Welcome/Landing Page
│   ├── login.vue                 # Login Page
│   ├── newsfeed.vue              # Main Feed (Home)
│   ├── timeline.vue              # User Profile/Timeline
│   ├── groups.vue                # Groups Listing
│   ├── events.vue                # Events Listing
│   └── marketplace.vue           # Marketplace Products
├── public/
│   └── images/                   # Static Images
├── .gitignore
├── app.vue                       # Root Component
├── nuxt.config.ts                # Nuxt Configuration
├── package.json                  # Dependencies
├── tailwind.config.ts            # Tailwind Configuration
└── README.md                     # This File
```

## 🛠️ การติดตั้งและรันโปรเจค

### 1. ติดตั้ง Dependencies

```bash
# ใช้ npm
npm install

# หรือ pnpm (แนะนำ)
pnpm install

# หรือ yarn
yarn install
```

### 2. รัน Development Server

```bash
npm run dev
```

เปิดเบราว์เซอร์ที่ `http://localhost:3000`

### 3. Build สำหรับ Production

```bash
npm run build
```

### 4. Preview Production Build

```bash
npm run preview
```

## 📄 หน้าเพจหลัก

| หน้า | เส้นทาง | คำอธิบาย |
|------|---------|----------|
| Welcome | `/` | หน้าต้นรับ (Onboarding) |
| Login | `/login` | หน้าเข้าสู่ระบบ |
| Newsfeed | `/newsfeed` | ฟีดโพสต์หลัก (หน้าแรกหลังล็อกอิน) |
| Timeline | `/timeline` | โปรไฟล์ผู้ใช้ |
| Groups | `/groups` | รายการกลุ่ม |
| Events | `/events` | รายการอีเวนต์ |
| Marketplace | `/marketplace` | ตลาดซื้อขายสินค้า |

## 🎨 การใช้งาน Tailwind CSS

โปรเจคนี้ใช้ Tailwind CSS สำหรับการออกแบบ UI ทั้งหมด พร้อม Custom Classes:

### Utility Classes

```vue
<button class="btn-primary">Primary Button</button>
<button class="btn-secondary">Secondary Button</button>
<div class="card">Card Container</div>
<input class="input-field" />
```

### Color Palette

- **Primary Colors**: `primary-50` ถึง `primary-900` (สีแดง)
- **Secondary Colors**: `secondary-50` ถึง `secondary-900` (สีเทา)

### ตัวอย่างการใช้งาน

```vue
<template>
  <div class="bg-white rounded-xl shadow-sm p-4">
    <h2 class="text-2xl font-bold text-secondary-900 mb-4">
      Title
    </h2>
    <button class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
      Click Me
    </button>
  </div>
</template>
```

## 🧩 Components

### การสร้าง Component ใหม่

```vue
<template>
  <div class="my-component">
    <!-- Your template -->
  </div>
</template>

<script setup lang="ts">
// Component logic with Composition API
interface Props {
  title: string
}

const props = defineProps<Props>()
const emit = defineEmits<{
  click: []
}>()
</script>
```

### การใช้ Icon

```vue
<Icon name="mdi:home" class="w-6 h-6" />
<Icon name="mdi:account" class="w-5 h-5 text-primary-600" />
```

## 📊 Mock Data

ข้อมูลตัวอย่างถูกจัดเก็บใน `composables/`:

### usePosts.ts
- ข้อมูลโพสต์
- ฟังก์ชัน Load More
- การจัดการ Like/Comment

### useUserProfile.ts
- ข้อมูลโปรไฟล์ผู้ใช้
- รูปภาพ
- เพื่อน
- สถิติ

### ตัวอย่างการใช้งาน

```vue
<script setup lang="ts">
const { posts, loadMore, hasMore } = usePosts()
const { profile, userPosts } = useUserProfile()
</script>
```

## 🎯 Features

### ✅ ที่ทำเสร็จแล้ว

- ✅ Welcome/Onboarding Screen
- ✅ Login Page
- ✅ Newsfeed (Main Feed)
- ✅ User Timeline/Profile
- ✅ Post Card with Like/Comment/Share
- ✅ Stories Section
- ✅ Create Post Widget
- ✅ Groups Listing
- ✅ Events Listing
- ✅ Marketplace Products
- ✅ Top Navigation Bar
- ✅ Bottom Navigation (Mobile-friendly)
- ✅ Sidebar Drawer Menu
- ✅ Responsive Design
- ✅ Dark Mode Support (via color-mode module)

### 🚧 ที่ยังต้องพัฒนาต่อ

- [ ] Messages/Chat System
- [ ] Notifications Page
- [ ] Settings Page
- [ ] Search Functionality
- [ ] Blog Posts Section
- [ ] Photo Gallery
- [ ] Video Gallery
- [ ] Nearby/Map Feature
- [ ] Product Detail Page
- [ ] Shopping Cart
- [ ] Authentication (Real API Integration)
- [ ] State Management with Pinia
- [ ] PWA Support

## 🔧 Configuration

### Nuxt Config (`nuxt.config.ts`)

```typescript
export default defineNuxtConfig({
  modules: [
    '@nuxtjs/tailwindcss',
    '@nuxt/icon',
    '@pinia/nuxt',
    '@nuxtjs/color-mode'
  ],
  css: ['~/assets/css/main.css'],
  // ... other configs
})
```

### Tailwind Config (`tailwind.config.ts`)

กำหนด Custom Colors, Animations, และ Plugins

## 🎨 Design System

### Typography
- Font: Roboto (เหมือนเดิม)
- Heading: `font-bold`
- Body: `font-normal`

### Spacing
- ใช้ Tailwind Spacing Scale: `p-4`, `m-6`, `gap-2`

### Shadows
- `shadow-sm`: Small Shadow
- `shadow-md`: Medium Shadow
- `shadow-lg`: Large Shadow

### Animations
- `animate-fade-in`: Fade In
- `animate-slide-up`: Slide Up
- `animate-slide-down`: Slide Down

## 📱 Responsive Design

โปรเจครองรับทุกขนาดหน้าจอ:

- **Mobile**: < 768px
- **Tablet**: 768px - 1024px
- **Desktop**: > 1024px

ใช้ Tailwind Responsive Prefixes: `md:`, `lg:`, `xl:`

```vue
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
  <!-- Responsive Grid -->
</div>
```

## 🌐 Navigation

### Top Bar
- Back Button
- Search
- Logo
- Settings
- New Post

### Bottom Nav (Mobile)
- Home
- Notifications
- Menu Toggle
- Messages
- Profile

### Sidebar Menu
- All Main Pages
- Settings
- Logout

## 💡 Tips & Best Practices

1. **ใช้ Composition API**: ใช้ `<script setup>` ทุกครั้ง
2. **TypeScript**: กำหนด Type สำหรับ Props และ Events
3. **Composables**: แยก Logic ที่ใช้ซ้ำไปยัง `composables/`
4. **Components**: สร้าง Reusable Components ขนาดเล็ก
5. **Tailwind**: ใช้ Utility Classes แทนการเขียน Custom CSS
6. **Auto-imports**: Nuxt จะ auto-import components และ composables

## 🔗 Links

- [Nuxt.js Documentation](https://nuxt.com/)
- [Vue.js Documentation](https://vuejs.org/)
- [Tailwind CSS Documentation](https://tailwindcss.com/)
- [Nuxt Icon](https://github.com/nuxt/icon)

## 📝 License

MIT License

## 👨‍💻 Author

Converted from Pitnik HTML Template to Modern Nuxt.js Application

---

**Happy Coding! 🚀**
