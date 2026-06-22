# Phase N — Polish + A11y + Mobile UX (Detailed DIY Plan)

อ้างอิง: Phases A-M [✅ ครบ]
Target: ยกระดับ UX — skeletons / empty states / a11y / mobile drawer / touch / animation polish
วันที่: 2026-06-21

---

## 📌 Pain Points ที่ Phase A-M เปิดทิ้งไว้

| ปัญหาที่พบใน UX | กระทบที่ไหน |
|---|---|
| ⏳ ใช้ `svg-spinners:ring-resize` ทุก loading | feed / tabs / sidebar / modal — ไม่บอกขนาด content จริง |
| 📭 Empty state แค่ icon + ข้อความสั้น | groups / events / leaderboard ก่อนมีข้อมูล |
| ❌ Error → SweetAlert popup เกิน — บางที่ควรเป็น inline retry | API call ล้มใน widget |
| ⌨️ Modal/dropdown ไม่มี **focus trap** + escape handler | GroupManageModal / CreatePostModal / PostAsSelector / NotificationBell |
| 📱 Sidebar `hidden lg:flex` ซ่อนทั้งหมดบน mobile → quick menu/stats/level ไม่เข้าถึง | Phase A/B/M widgets |
| 🤚 ไม่มี **touch gesture** สำหรับเปลี่ยน tab / dismiss modal | mobile UX |
| 🎬 hover state OK แต่ขาด **motion-reduce** support | accessibility (prefers-reduced-motion) |
| 🏷️ ขาด **ARIA roles + labels** ใน custom components | screen reader |
| 📏 input/textarea ไม่มี **validation feedback** ใน real-time | CreatePostModal / GroupCreateModal / ManageTabInfo |

---

# 🗺️ Phase N Sub-phases Overview

| # | งาน | Est. |
|---|---|---|
| **N.0** | Skeleton component system (5 reusable) | 1.5 hr |
| **N.1** | Empty state system + illustrations | 1 hr |
| **N.2** | Error/Retry inline pattern | 1 hr |
| **N.3** | Mobile sidebar drawer (left + right slide-in) | 2 hr |
| **N.4** | Modal a11y: focus trap + ARIA + escape | 1.5 hr |
| **N.5** | Dropdown a11y: keyboard nav (PostAsSelector / NotificationBell / type pickers) | 1 hr |
| **N.6** | Form realtime validation + inline error | 1.5 hr |
| **N.7** | Animation polish + `motion-reduce` | 1 hr |
| **N.8** | Touch gestures (swipe tabs, drag-to-dismiss) | 1.5 hr |
| **N.9** | Performance: image lazy load + skeleton intersection | 1 hr |
| **N.10** | QA checklist + Lighthouse pass | 1 hr |
| **รวม** | | **~13 ชม.** |

---

# N.0 — Skeleton component system

**Goal:** แทน spinner ด้วย placeholder shape ที่ตรงกับ content จริง — ลด CLS (cumulative layout shift)

## N.0.1 — `SkeletonBox` (atom)

**File:** `ui/components/Common/SkeletonBox.vue` (NEW)

```vue
<script setup lang="ts">
interface Props {
  width?: string
  height?: string
  rounded?: 'sm' | 'md' | 'lg' | 'xl' | 'full'
  shimmer?: boolean
}
withDefaults(defineProps<Props>(), { rounded: 'md', shimmer: true })
</script>

<template>
  <div
    :class="[
      'bg-gray-200 dark:bg-gray-700 inline-block',
      `rounded-${rounded}`,
      shimmer && 'animate-pulse motion-reduce:animate-none',
    ]"
    :style="{ width, height }"
    aria-hidden="true"
  />
</template>
```

## N.0.2 — Domain skeletons (5 ตัว)

| Skeleton | สำหรับ | Layout |
|---|---|---|
| `FeedPostSkeleton.vue` | FeedPost loading | avatar + 2 lines + 4-line content + actions row |
| `GroupCardSkeleton.vue` | GroupCard grid | header gradient + medallion + name + stats line |
| `MemberRowSkeleton.vue` | Member/Admin list | avatar + name + role |
| `StatGridSkeleton.vue` | SchoolStatGrid | 4 cards 2×2 |
| `UpcomingEventsSkeleton.vue` | SchoolUpcomingEvents | 3 rows date-chip + title |

**ตัวอย่าง:** `ui/components/play/feed/FeedPostSkeleton.vue`

```vue
<template>
  <article class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm p-4">
    <div class="flex gap-3 mb-3">
      <CommonSkeletonBox width="40px" height="40px" rounded="full" />
      <div class="flex-1 flex flex-col gap-1.5">
        <CommonSkeletonBox width="120px" height="14px" />
        <CommonSkeletonBox width="80px" height="11px" />
      </div>
    </div>
    <div class="space-y-2">
      <CommonSkeletonBox width="100%" height="14px" />
      <CommonSkeletonBox width="92%" height="14px" />
      <CommonSkeletonBox width="78%" height="14px" />
    </div>
    <div class="flex gap-4 mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
      <CommonSkeletonBox width="40px" height="20px" />
      <CommonSkeletonBox width="40px" height="20px" />
      <CommonSkeletonBox width="40px" height="20px" />
    </div>
  </article>
</template>
```

## N.0.3 — Replace spinners

หาทุกที่ที่ใช้ `svg-spinners:ring-resize` แล้วเปลี่ยน:

**Pattern:**
```vue
<!-- Before -->
<div v-if="isLoading" class="py-8 text-center">
  <Icon icon="svg-spinners:ring-resize" class="w-8 h-8 text-vikinger-purple mx-auto" />
</div>
<div v-else>...</div>

<!-- After -->
<div v-if="isLoading" class="space-y-3">
  <PlayFeedFeedPostSkeleton v-for="i in 3" :key="i" />
</div>
<div v-else>...</div>
```

ใช้กับ:
- Feed tab in `[name].vue:1196`
- Members tab list
- Groups grid (use `GroupCardSkeleton`)
- Group profile feed/members tabs
- StatGrid initial mount

---

# N.1 — Empty state system

## N.1.1 — `EmptyState` component

**File:** `ui/components/Common/EmptyState.vue` (NEW)

```vue
<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  icon?: string
  title: string
  description?: string
  /** Primary CTA */
  ctaLabel?: string
  ctaIcon?: string
  /** Compact = smaller padding */
  compact?: boolean
}
withDefaults(defineProps<Props>(), {
  icon: 'heroicons:inbox',
  compact: false,
})
const emit = defineEmits<{ action: [] }>()
</script>

<template>
  <div
    :class="[
      'flex flex-col items-center justify-center text-center bg-white dark:bg-vikinger-dark-200 rounded-xl',
      compact ? 'py-6 px-4' : 'py-12 px-6',
    ]"
    role="status"
  >
    <div
      :class="[
        'rounded-full bg-gradient-to-br from-vikinger-purple/10 to-vikinger-cyan/10 flex items-center justify-center mb-4',
        compact ? 'w-12 h-12' : 'w-20 h-20',
      ]"
    >
      <Icon
        :icon="icon"
        :class="['text-vikinger-purple', compact ? 'w-6 h-6' : 'w-10 h-10']"
      />
    </div>
    <h3 :class="['font-bold text-gray-900 dark:text-white', compact ? 'text-sm' : 'text-base']">
      {{ title }}
    </h3>
    <p
      v-if="description"
      :class="['text-gray-500 dark:text-gray-400 mt-1', compact ? 'text-xs' : 'text-sm max-w-md']"
    >
      {{ description }}
    </p>
    <button
      v-if="ctaLabel"
      type="button"
      class="mt-4 px-4 py-2 bg-vikinger-purple text-white rounded-lg font-medium hover:bg-vikinger-purple/90 inline-flex items-center gap-2"
      @click="emit('action')"
    >
      <Icon v-if="ctaIcon" :icon="ctaIcon" class="w-4 h-4" />
      {{ ctaLabel }}
    </button>
  </div>
</template>
```

## N.1.2 — แทน inline empty states

**Pattern:**
```vue
<!-- Before -->
<div v-if="groups.length === 0" class="bg-white p-8 text-center">
  <Icon icon="..." class="..." />
  <p>ยังไม่มีส่วนงาน</p>
</div>

<!-- After -->
<CommonEmptyState
  v-if="groups.length === 0"
  icon="heroicons:building-office"
  title="ยังไม่มีส่วนงาน"
  description="เริ่มสร้างฝ่าย กลุ่มสาระ หรือชมรมใหม่ของโรงเรียน"
  :cta-label="academy.authIsAcademyAdmin ? 'เปิดส่วนงานใหม่' : undefined"
  cta-icon="fluent:add-24-regular"
  @action="showCreateGroupModal = true"
/>
```

ใช้ทุกที่ที่มี empty state:
- Groups tab
- Members tab
- Classrooms tab
- Group profile feed/members
- Pinned announcements (no pinned)
- Upcoming events
- Notification dropdown

---

# N.2 — Error/Retry inline pattern

## N.2.1 — `ErrorRetry` component

**File:** `ui/components/Common/ErrorRetry.vue` (NEW)

```vue
<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  message?: string
  /** Show as inline (compact) or block */
  variant?: 'inline' | 'block'
}
withDefaults(defineProps<Props>(), {
  message: 'โหลดข้อมูลไม่สำเร็จ',
  variant: 'block',
})
const emit = defineEmits<{ retry: [] }>()
</script>

<template>
  <div
    :class="[
      variant === 'block'
        ? 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/50 rounded-xl py-6 px-4 text-center'
        : 'text-xs text-red-600 dark:text-red-400 py-2 text-center'
    ]"
    role="alert"
  >
    <Icon
      icon="heroicons:exclamation-triangle"
      :class="variant === 'block' ? 'w-8 h-8 text-red-500 mx-auto mb-2' : 'w-4 h-4 inline mr-1'"
    />
    <p :class="variant === 'block' ? 'text-sm text-red-700 dark:text-red-300 mb-3' : 'inline'">
      {{ message }}
    </p>
    <button
      type="button"
      :class="[
        'inline-flex items-center gap-1.5 font-semibold',
        variant === 'block'
          ? 'px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 text-sm'
          : 'underline text-red-600 dark:text-red-400 ml-2 text-xs'
      ]"
      @click="emit('retry')"
    >
      <Icon icon="heroicons:arrow-path" class="w-4 h-4" />
      ลองอีกครั้ง
    </button>
  </div>
</template>
```

## N.2.2 — ใช้กับ widget calls

ปรับ pattern ใน `SchoolUpcomingEvents`, `SchoolPinnedAnnouncement`, `SchoolClassroomLeaderboard`:

```vue
<CommonErrorRetry
  v-if="error"
  :message="error"
  variant="inline"
  @retry="load"
/>
```

---

# N.3 — Mobile sidebar drawer

**Goal:** บน mobile/tablet (< xl) sidebar widget ตอนนี้หายหมด → เพิ่ม drawer slide-in

## N.3.1 — `SidebarDrawer` component

**File:** `ui/components/Common/SidebarDrawer.vue` (NEW)

```vue
<script setup lang="ts">
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { Icon } from '@iconify/vue'

interface Props {
  open: boolean
  side?: 'left' | 'right'
  title?: string
}
const props = withDefaults(defineProps<Props>(), { side: 'left' })
const emit = defineEmits<{ 'update:open': [v: boolean]; close: [] }>()

const close = () => {
  emit('update:open', false)
  emit('close')
}

// Lock body scroll
watch(() => props.open, (open) => {
  if (import.meta.client) {
    document.body.style.overflow = open ? 'hidden' : ''
  }
})

// Escape key
const onEsc = (e: KeyboardEvent) => {
  if (e.key === 'Escape' && props.open) close()
}
onMounted(() => document.addEventListener('keydown', onEsc))
onBeforeUnmount(() => {
  document.removeEventListener('keydown', onEsc)
  if (import.meta.client) document.body.style.overflow = ''
})
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      leave-active-class="transition-opacity duration-150"
      leave-to-class="opacity-0"
    >
      <div
        v-if="open"
        class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm"
        @click="close"
        aria-hidden="true"
      />
    </Transition>

    <Transition
      :enter-active-class="`transition-transform duration-300 motion-reduce:duration-0`"
      :enter-from-class="side === 'left' ? '-translate-x-full' : 'translate-x-full'"
      :leave-active-class="`transition-transform duration-200 motion-reduce:duration-0`"
      :leave-to-class="side === 'left' ? '-translate-x-full' : 'translate-x-full'"
    >
      <aside
        v-if="open"
        :class="[
          'fixed top-0 bottom-0 z-50 w-[85vw] max-w-sm bg-white dark:bg-vikinger-dark-100 shadow-2xl overflow-y-auto',
          side === 'left' ? 'left-0' : 'right-0',
        ]"
        role="dialog"
        :aria-label="title || 'sidebar'"
        aria-modal="true"
      >
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700 sticky top-0 bg-white dark:bg-vikinger-dark-100">
          <span class="font-bold text-gray-900 dark:text-white">{{ title }}</span>
          <button
            type="button"
            class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
            aria-label="ปิด"
            @click="close"
          >
            <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5" />
          </button>
        </div>
        <div class="p-4">
          <slot />
        </div>
      </aside>
    </Transition>
  </Teleport>
</template>
```

## N.3.2 — Mount + trigger buttons ใน `[name].vue`

เพิ่ม state + buttons:
```ts
const showMobileLeftDrawer = ref(false)
const showMobileRightDrawer = ref(false)
```

ใน toolbar (เหนือ tabs หรือใน cover area) แสดงเฉพาะบน mobile:
```vue
<div class="flex gap-2 mt-3 lg:hidden">
  <button
    type="button"
    class="flex-1 px-3 py-2 rounded-lg border text-xs font-semibold flex items-center justify-center gap-1.5"
    @click="showMobileLeftDrawer = true"
  >
    <Icon icon="heroicons:bars-3" class="w-4 h-4" />
    เมนูลัด
  </button>
  <button
    type="button"
    class="flex-1 px-3 py-2 rounded-lg border text-xs font-semibold flex items-center justify-center gap-1.5"
    @click="showMobileRightDrawer = true"
  >
    <Icon icon="heroicons:chart-bar" class="w-4 h-4" />
    สถิติ
  </button>
</div>
```

Mount drawer (ใต้ modals):
```vue
<CommonSidebarDrawer v-model:open="showMobileLeftDrawer" side="left" title="เมนูลัด">
  <SchoolQuickMenu :academy="academy" @navigate="(t) => { switchTab(t); showMobileLeftDrawer = false; }" />
  <div class="mt-4">
    <SchoolLevelCard v-if="academy.level" ... />
  </div>
</CommonSidebarDrawer>

<CommonSidebarDrawer v-model:open="showMobileRightDrawer" side="right" title="สถิติและกิจกรรม">
  <div class="space-y-4">
    <SchoolStatGrid :academy="{ ...academy, total_classrooms: classrooms.length }" />
    <SchoolUpcomingEvents :academy-id="academy.id" @view-all="switchTab('events')" />
    <SchoolClassroomLeaderboard :academy-id="academy.id" cycle="month" />
  </div>
</CommonSidebarDrawer>
```

---

# N.4 — Modal a11y: focus trap + ARIA + escape

## N.4.1 — `useFocusTrap` composable

**File:** `ui/composables/useFocusTrap.ts` (NEW)

```ts
import { onMounted, onBeforeUnmount, ref, Ref } from 'vue'

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
        'input:not([disabled]), button:not([disabled])'
      )
      focusable?.focus()
    })
  })

  onBeforeUnmount(() => {
    document.removeEventListener('keydown', trap)
    previousActive?.focus()
  })
}
```

## N.4.2 — ใช้ใน modal

`GroupManageModal.vue`, `GroupCreateModal.vue`, `CreatePostModal.vue`:

```ts
const dialogRef = ref<HTMLElement | null>(null)
useFocusTrap(dialogRef, computed(() => props.open))
```

```vue
<div ref="dialogRef" role="dialog" aria-modal="true" :aria-labelledby="`${id}-title`">
  <h3 :id="`${id}-title`">เปิดส่วนงานใหม่</h3>
  ...
</div>
```

## N.4.3 — Escape handler (already in N.3)

ทำ pattern เดียวกัน:
```ts
const onEsc = (e: KeyboardEvent) => {
  if (e.key === 'Escape' && props.open) close()
}
onMounted(() => document.addEventListener('keydown', onEsc))
onBeforeUnmount(() => document.removeEventListener('keydown', onEsc))
```

---

# N.5 — Dropdown a11y: keyboard nav

## N.5.1 — Arrow key navigation

ทุก dropdown (`PostAsSelector`, `NotificationBell`, post type picker, search autocomplete):

```ts
const activeIndex = ref(-1)

const onKeydown = (e: KeyboardEvent) => {
  if (!isOpen.value) return
  switch (e.key) {
    case 'ArrowDown':
      e.preventDefault()
      activeIndex.value = Math.min(activeIndex.value + 1, options.value.length - 1)
      break
    case 'ArrowUp':
      e.preventDefault()
      activeIndex.value = Math.max(activeIndex.value - 1, 0)
      break
    case 'Enter':
      e.preventDefault()
      if (activeIndex.value >= 0) select(options.value[activeIndex.value])
      break
    case 'Escape':
      isOpen.value = false
      break
  }
}
```

```vue
<button
  v-for="(opt, i) in options"
  :key="opt.id"
  :class="[
    activeIndex === i && 'bg-vikinger-purple/10'
  ]"
  role="option"
  :aria-selected="activeIndex === i"
  @click="select(opt)"
>...</button>
```

## N.5.2 — Container ARIA

```vue
<div role="combobox" aria-haspopup="listbox" :aria-expanded="isOpen">
  <button :aria-expanded="isOpen" :aria-controls="`${id}-listbox`" @click="toggle">...</button>
  <div v-if="isOpen" :id="`${id}-listbox`" role="listbox">
    ...
  </div>
</div>
```

---

# N.6 — Form realtime validation

## N.6.1 — Validation helper

**File:** `ui/composables/useFieldValidation.ts` (NEW)

```ts
import { computed, Ref } from 'vue'

export const useFieldValidation = (
  value: Ref<string>,
  rules: Array<(v: string) => string | true>,
) => {
  const error = computed(() => {
    for (const rule of rules) {
      const result = rule(value.value)
      if (result !== true) return result
    }
    return ''
  })
  const isValid = computed(() => !error.value)
  return { error, isValid }
}

// Common rules
export const rules = {
  required: (msg = 'จำเป็น') => (v: string) => v.trim() ? true : msg,
  minLen: (n: number) => (v: string) => v.length >= n ? true : `ต้องอย่างน้อย ${n} ตัวอักษร`,
  maxLen: (n: number) => (v: string) => v.length <= n ? true : `ไม่เกิน ${n} ตัวอักษร`,
}
```

## N.6.2 — `FormField` wrapper

**File:** `ui/components/Common/FormField.vue` (NEW)

```vue
<script setup lang="ts">
interface Props {
  label: string
  error?: string
  required?: boolean
  hint?: string
}
defineProps<Props>()
const id = useId()
</script>

<template>
  <div>
    <label :for="id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
      {{ label }}
      <span v-if="required" class="text-red-500" aria-label="จำเป็น">*</span>
    </label>
    <slot :id="id" :describedBy="error ? `${id}-error` : (hint ? `${id}-hint` : undefined)" />
    <p v-if="error" :id="`${id}-error`" class="mt-1 text-xs text-red-500" role="alert">
      {{ error }}
    </p>
    <p v-else-if="hint" :id="`${id}-hint`" class="mt-1 text-xs text-gray-400">
      {{ hint }}
    </p>
  </div>
</template>
```

## N.6.3 — Apply ใน `GroupCreateModal`

```vue
<CommonFormField label="ชื่อส่วนงาน" :error="nameError" required hint="เช่น ฝ่ายวิชาการ, ชมรมหุ่นยนต์">
  <template #default="{ id, describedBy }">
    <input
      :id="id"
      v-model="form.name"
      type="text"
      :aria-describedby="describedBy"
      :aria-invalid="!!nameError"
      class="w-full px-4 py-3 rounded-lg border ..."
    />
  </template>
</CommonFormField>
```

---

# N.7 — Animation polish + motion-reduce

## N.7.1 — Tailwind config

**File:** `ui/tailwind.config.ts`
ตรวจให้รองรับ `motion-reduce:` variant (Tailwind 3+ มี built-in)

## N.7.2 — เพิ่มทุก animation

```vue
<!-- Before -->
<div class="transition-transform duration-300 hover:scale-105">

<!-- After (a11y-friendly) -->
<div class="transition-transform duration-300 hover:scale-105 motion-reduce:transition-none motion-reduce:hover:scale-100">
```

## N.7.3 — Page transition (Nuxt)

**File:** `ui/app.vue` หรือ layout

```ts
definePageMeta({
  pageTransition: {
    name: 'page-fade',
    mode: 'out-in',
    onBeforeEnter: () => {
      // disable if motion-reduce
      const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches
      return !reduce
    },
  },
})
```

## N.7.4 — Micro-interactions

- ปุ่ม submit ตอน loading → ripple disable + cursor wait
- โพสต์ใหม่เพิ่มเข้า feed → fade-in slide-down
- Like animation → heart bounce

---

# N.8 — Touch gestures

## N.8.1 — `useSwipe` composable

**File:** `ui/composables/useSwipe.ts` (NEW)

```ts
import { ref, Ref } from 'vue'

interface SwipeOptions {
  threshold?: number
  onSwipeLeft?: () => void
  onSwipeRight?: () => void
  onSwipeUp?: () => void
  onSwipeDown?: () => void
}

export const useSwipe = (el: Ref<HTMLElement | null>, opts: SwipeOptions) => {
  const threshold = opts.threshold ?? 50
  let startX = 0, startY = 0

  const onStart = (e: TouchEvent) => {
    startX = e.touches[0].clientX
    startY = e.touches[0].clientY
  }

  const onEnd = (e: TouchEvent) => {
    const dx = e.changedTouches[0].clientX - startX
    const dy = e.changedTouches[0].clientY - startY
    if (Math.abs(dx) < threshold && Math.abs(dy) < threshold) return
    if (Math.abs(dx) > Math.abs(dy)) {
      dx > 0 ? opts.onSwipeRight?.() : opts.onSwipeLeft?.()
    } else {
      dy > 0 ? opts.onSwipeDown?.() : opts.onSwipeUp?.()
    }
  }

  onMounted(() => {
    el.value?.addEventListener('touchstart', onStart, { passive: true })
    el.value?.addEventListener('touchend', onEnd, { passive: true })
  })
  onBeforeUnmount(() => {
    el.value?.removeEventListener('touchstart', onStart)
    el.value?.removeEventListener('touchend', onEnd)
  })
}
```

## N.8.2 — Swipe tabs ใน `[name].vue`

```ts
const tabsRef = ref<HTMLElement | null>(null)
useSwipe(tabsRef, {
  onSwipeLeft: () => switchToNextTab(),
  onSwipeRight: () => switchToPreviousTab(),
})

const switchToNextTab = () => {
  const i = tabs.findIndex(t => t.id === currentTab.value)
  if (i < tabs.length - 1) switchTab(tabs[i + 1].id)
}
```

## N.8.3 — Drag-to-dismiss modal (optional)

ผูก `useSwipe(modalRef, { onSwipeDown: close, threshold: 100 })` ใน mobile

---

# N.9 — Performance: lazy load + intersection

## N.9.1 — Image lazy load

ทุก `<img>` ใหญ่ใช้ `loading="lazy" decoding="async"`:

```vue
<img
  :src="logoUrl"
  :alt="academy.name"
  loading="lazy"
  decoding="async"
  width="112"
  height="112"
/>
```

ใส่ `width`/`height` ป้องกัน CLS

## N.9.2 — Intersection-based widget load

**File:** `ui/composables/useIntersectionLoad.ts` (NEW)

```ts
import { ref, onMounted, onBeforeUnmount, Ref } from 'vue'

export const useIntersectionLoad = (el: Ref<HTMLElement | null>, onLoad: () => void) => {
  const loaded = ref(false)
  let observer: IntersectionObserver | null = null

  onMounted(() => {
    if (!el.value) return
    observer = new IntersectionObserver((entries) => {
      if (entries[0]?.isIntersecting && !loaded.value) {
        loaded.value = true
        onLoad()
        observer?.disconnect()
      }
    })
    observer.observe(el.value)
  })
  onBeforeUnmount(() => observer?.disconnect())

  return { loaded }
}
```

ใช้กับ widget ที่ไม่อยู่ above-the-fold (เช่น leaderboard ใน right sidebar) → ไม่โหลด API จนกว่า scroll ถึง

## N.9.3 — Defer FeedPost media

ใน `FeedPost.vue` ถ้ามี image gallery → ใช้ `<img loading="lazy">` + skeleton placeholder

---

# N.10 — QA checklist + Lighthouse pass

## N.10.1 — Manual QA matrix

| Page | Mobile (375) | Tablet (768) | Desktop (1440) |
|---|---|---|---|
| `/academies/{name}` (feed) | ✓ no horizontal scroll, drawer ใช้งานได้ | ✓ 2-col | ✓ 3-col |
| `/academies/{name}` (groups tab) | ✓ cards 1-col stacked | ✓ 2-col | ✓ 2-col sections grouped |
| `/academies/{name}/groups/{id}` | ✓ cover + tabs | ✓ | ✓ |
| GroupManageModal | ✓ full-screen mobile | ✓ md | ✓ max-w-3xl |
| CreatePostModal | ✓ | ✓ | ✓ |
| NotificationBell | ✓ dropdown fit screen | ✓ | ✓ |

## N.10.2 — A11y checklist

- [ ] ทุก interactive element มี aria-label หรือ text content
- [ ] Modal มี `role="dialog"` + `aria-modal="true"` + labelledby
- [ ] Form input มี `<label for=>` หรือ aria-label
- [ ] Error message มี `role="alert"`
- [ ] Image มี alt text
- [ ] Color contrast WCAG AA (4.5:1 สำหรับ text)
- [ ] Focus visible (default ring) ไม่ถูก override เป็น 0
- [ ] Keyboard-only navigation ใช้งานได้ทุกหน้า

## N.10.3 — Lighthouse target

```bash
npm run build
npx lighthouse http://localhost:3000/academies/{name} \
  --only-categories=performance,accessibility,best-practices \
  --output=html
```

**Target scores:**
- Performance: > 80
- Accessibility: > 95
- Best Practices: > 90

## N.10.4 — Reduced motion test

```js
// In Chrome DevTools console
window.matchMedia('(prefers-reduced-motion: reduce)').matches
// Then toggle "Emulate CSS prefers-reduced-motion" in Rendering panel
```

ทดสอบ:
- ทุก animation หยุด / instant
- Skeleton shimmer หยุด

---

# 📋 Phase N — Files Summary

## ✨ New files (~14)

### Atoms / Common (5)
```
ui/components/Common/SkeletonBox.vue
ui/components/Common/EmptyState.vue
ui/components/Common/ErrorRetry.vue
ui/components/Common/SidebarDrawer.vue
ui/components/Common/FormField.vue
```

### Skeleton variants (5)
```
ui/components/play/feed/FeedPostSkeleton.vue
ui/components/academy/groups/GroupCardSkeleton.vue
ui/components/academy/groups/MemberRowSkeleton.vue
ui/components/school/StatGridSkeleton.vue
ui/components/school/UpcomingEventsSkeleton.vue
```

### Composables (4)
```
ui/composables/useFocusTrap.ts
ui/composables/useFieldValidation.ts
ui/composables/useSwipe.ts
ui/composables/useIntersectionLoad.ts
```

## 🔧 Modified files (~12)
```
ui/pages/academies/[name].vue                                    (drawer + swipe + skeletons)
ui/pages/academies/[name]/groups/[groupId].vue                    (skeletons + a11y)
ui/components/academy/groups/GroupManageModal.vue                 (focus trap + ARIA)
ui/components/academy/groups/GroupCreateModal.vue                 (FormField + validation)
ui/components/academy/groups/PostAsSelector.vue                   (keyboard nav + ARIA)
ui/components/academy/groups/ManageTabInfo.vue                    (FormField)
ui/components/academy/groups/MemberAutocompleteInput.vue          (arrow keys + ARIA)
ui/components/play/feed/CreatePostModal.vue                       (focus trap + skeleton + FormField)
ui/components/play/feed/FeedPost.vue                              (lazy image + motion-reduce)
ui/components/notifications/NotificationBell.vue                  (keyboard nav + ARIA)
ui/components/school/SchoolStatGrid.vue                           (intersection load)
ui/components/school/SchoolClassroomLeaderboard.vue               (intersection + skeleton + error retry)
ui/components/school/SchoolUpcomingEvents.vue                     (intersection + skeleton + error retry)
ui/components/school/SchoolPinnedAnnouncement.vue                 (motion-reduce on ring pulse)
```

---

# 🛣️ Commit plan (6 commits)

```
1. feat(ui): skeleton component system (5 variants)               (N.0)
2. feat(ui): empty state + error retry components                 (N.1 + N.2)
3. feat(ui): mobile sidebar drawer (left + right)                 (N.3)
4. feat(ui): a11y — focus trap, keyboard nav, ARIA in modals     (N.4 + N.5)
5. feat(ui): form realtime validation + motion-reduce            (N.6 + N.7)
6. feat(ui): touch gestures + intersection lazy load             (N.8 + N.9)
```

---

# ✅ Phase N — Test Checklist

## Visual / Layout
- [ ] Skeleton แสดง 1-2 วินาทีก่อน content โหลด (ไม่กระตุก)
- [ ] Empty state มี CTA — admin เห็น "เปิดส่วนงานใหม่"
- [ ] Error widget → กดลองอีกครั้ง → re-fetch สำเร็จ
- [ ] Mobile drawer slide-in สวย + body scroll lock + ESC ปิด

## A11y
- [ ] Tab key → focus วน element ใน modal เท่านั้น
- [ ] Esc → ปิด modal + dropdown
- [ ] Arrow keys → เลือก option ใน autocomplete/dropdown
- [ ] Enter → select option
- [ ] Screen reader (NVDA/VoiceOver) → อ่าน label + role ถูก
- [ ] Lighthouse a11y > 95

## Form
- [ ] พิมพ์ครบ → error หาย ทันที (debounce 200ms)
- [ ] required field ว่าง → error "จำเป็น"
- [ ] aria-invalid ทำงาน
- [ ] error message มี role="alert"

## Mobile
- [ ] 375px width → no horizontal scroll
- [ ] Swipe left ใน tabs → tab ถัดไป
- [ ] Drawer button มองเห็น
- [ ] Touch target ≥ 44×44 px

## Motion
- [ ] เปิด `prefers-reduced-motion` → animation ทั้งหมดหยุด/instant
- [ ] Page transition ทำงาน (เว้น reduce mode)

## Performance
- [ ] Lighthouse Performance > 80
- [ ] Image lazy load ทำงาน (Network tab)
- [ ] Widget ที่ off-screen ไม่ fetch จนกว่า scroll

---

# ⚠️ Pitfalls & Notes

## 1. Skeleton ขนาดต้องตรงกับ content
- ถ้า skeleton สูงกว่า/ต่ำกว่า content จริง → CLS เกิด
- วัดขนาด content จริงก่อนใส่ width/height ของ skeleton

## 2. Focus trap with Teleport
- `Teleport` ทำให้ DOM ของ modal อยู่ที่ body — ระวัง `containerRef` ต้องชี้ใน Teleport
- ใช้ `template ref` แทน `getElementById`

## 3. iOS Safari body scroll lock
- `document.body.style.overflow = 'hidden'` ไม่พอบน iOS
- ต้องเพิ่ม `position: fixed` + restore scrollY:
  ```ts
  const scrollY = window.scrollY
  document.body.style.position = 'fixed'
  document.body.style.top = `-${scrollY}px`
  // on close: restore
  window.scrollTo(0, scrollY)
  ```

## 4. Keyboard nav กับ Teleport
- Escape handler ต้องอยู่ที่ document level — ไม่ใช่ container
- ถ้ามี modal ซ้อน → ปิดทีละชั้น (track stack)

## 5. Swipe vs scroll conflict
- ถ้า tabsRef อยู่ใน scrollable container → swipe vertical อาจตี trigger
- ใช้ threshold + ตรวจ `Math.abs(dx) > Math.abs(dy) * 1.5` ก่อน trigger

## 6. Intersection observer SSR
- `IntersectionObserver` ไม่มีบน server
- guard ด้วย `if (import.meta.client)` หรือ `onMounted` (ที่ client only)

## 7. motion-reduce coverage
- Tailwind `motion-reduce:` ใช้ได้กับ class ปกติ
- แต่ JS animation (Vue Transition) ต้องเช็ค `matchMedia` เอง

## 8. ARIA over-engineering
- ไม่ต้องใส่ role ทุก div — ใช้ semantic HTML ก่อน (`<button>` แทน `<div onclick>`)
- ARIA แค่เสริมเมื่อ semantic ไม่ครอบคลุม

## 9. FormField + v-model ผ่าน slot
- ใช้ scoped slot ส่ง `id`, `describedBy` ลง input
- v-model ยังผูกกับ form data ของ parent

## 10. Lighthouse mobile vs desktop
- Run ทั้งสอง profile — score ต่างกัน
- Mobile ใช้ throttled CPU + slow 4G

---

# 🎯 ลำดับงานแนะนำ

```
1. N.0 + N.1 + N.2 (3.5h) → component system พื้นฐาน
2. N.6 (1.5h) → form validation (เห็นผลทันทีใน modal)
3. N.4 + N.5 (2.5h) → a11y core (focus trap + keyboard)
4. N.3 (2h) → mobile drawer (ปลดล็อกการใช้งาน mobile)
5. N.7 (1h) → animation polish
6. N.8 (1.5h) → touch (mobile feel native)
7. N.9 (1h) → performance
8. N.10 (1h) → QA + Lighthouse
```

หลัง N เสร็จ → ระบบ **production-grade** ครบทั้ง UX + A11y + Mobile

---

# 🏁 ก้าวต่อไปหลัง Phase N (สรุป roadmap ที่เหลือ)

| Phase | Focus | Time |
|---|---|---|
| **O** | Realtime — Reverb websocket (live feed, live leaderboard, live notification) | ~6 hr |
| **P** | Analytics dashboard (admin) — XP velocity, engagement metrics | ~6 hr |
| **Q** | QA + Documentation — comprehensive test suite + user guide + dev docs | ~8 hr |
| **R** | Production deployment — env setup, CI/CD, monitoring | ~4 hr |

หรือ — ระบบพร้อม ship จริงๆ หลัง Phase N + Q (QA + docs) — Phase O และ P เป็น optional enhancement

ติดตรงไหนตอนทำ Phase N ถามได้เลยครับ 🙌
