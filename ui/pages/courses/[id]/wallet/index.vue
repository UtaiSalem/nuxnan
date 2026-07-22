<script setup lang="ts">
definePageMeta({ layout: 'course', middleware: ['auth'] })

const route = useRoute()
const id = computed(() => Number(route.params.id))
const store = useCourseStore()

onBeforeMount(async () => { if (!store.currentCourse || store.currentCourse.id !== id.value) await store.fetchCourse(id.value); if (!store.isCourseAdmin) return navigateTo(`/courses/${id.value}`) })

const courseId = computed(() => id.value)
const { account, fetchAccount } = useCoursePoints(courseId)

onMounted(() => fetchAccount())
</script>

<template>
  <div class="mx-auto max-w-7xl space-y-6 p-6">
    <div>
      <h1 class="text-2xl font-bold">กระเป๋าแต้ม</h1>
      <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">จัดการแต้มของรายวิชานี้</p>
    </div>

    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
      <section v-for="stat in [
        { label: 'ยอดคงเหลือ', value: account?.balance },
        { label: 'รอถอน/สงวน', value: account?.reserved_balance },
        { label: 'แจกไปแล้ว', value: account?.total_distributed },
        { label: 'ถอนสะสม', value: account?.total_withdrawn }
      ]" :key="stat.label" class="rounded-2xl bg-white p-6 shadow dark:bg-slate-800">
        <p class="text-sm text-slate-500 dark:text-slate-400">{{ stat.label }}</p>
        <p class="mt-2 text-2xl font-bold">{{ stat.value?.toLocaleString() || 0 }}</p>
        <p class="mt-1 text-xs text-slate-400">แต้ม</p>
      </section>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
      <NuxtLink :to="`/courses/${id.value}/wallet/campaigns`" class="group rounded-2xl bg-white p-6 shadow transition hover:-translate-y-0.5 hover:shadow-lg dark:bg-slate-800">
        <div class="flex items-start gap-4">
          <div class="rounded-xl bg-amber-100 p-3 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
            <Icon icon="mdi:star-circle" class="h-8 w-8" />
          </div>
          <div class="flex-1">
            <h2 class="text-lg font-semibold group-hover:text-primary-600">จัดการแคมเปญแต้ม</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">สร้างและจัดการแคมเปญให้นักเรียนกดรับแต้ม</p>
          </div>
          <Icon icon="mdi:chevron-right" class="h-6 w-6 text-slate-400" />
        </div>
      </NuxtLink>

      <NuxtLink :to="`/courses/${id.value}/wallet/withdraw`" class="group rounded-2xl bg-white p-6 shadow transition hover:-translate-y-0.5 hover:shadow-lg dark:bg-slate-800">
        <div class="flex items-start gap-4">
          <div class="rounded-xl bg-emerald-100 p-3 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
            <Icon icon="mdi:bank-transfer-out" class="h-8 w-8" />
          </div>
          <div class="flex-1">
            <h2 class="text-lg font-semibold group-hover:text-primary-600">ถอนแต้ม</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">ถอนแต้มจากกองทุนของรายวิชานี้</p>
          </div>
          <Icon icon="mdi:chevron-right" class="h-6 w-6 text-slate-400" />
        </div>
      </NuxtLink>
    </div>
  </div>
</template>
