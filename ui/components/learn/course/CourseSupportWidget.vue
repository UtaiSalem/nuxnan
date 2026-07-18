<script setup lang="ts">
import { Icon } from '@iconify/vue'

const props = defineProps<{
  course: any
  isOwner?: boolean
}>()

const route = useRoute()
const authStore = useAuthStore()
const courseId = computed(() => String(props.course?.id || route.params.id))
const { account, fetchAccount, isLoadingAccount } = useCoursePoints(courseId)
const { fetchCourseDonations } = useCourseDonations()
const donations = ref<any[]>([])

const load = async () => {
  await Promise.all([
    fetchAccount(),
    fetchCourseDonations(Number(courseId.value), { per_page: 5 }).then((response: any) => {
      donations.value = response?.data || []
    }).catch(() => { donations.value = [] })
  ])
}

onMounted(load)
</script>

<template>
  <section class="overflow-hidden rounded-2xl border border-amber-100 bg-white shadow-sm dark:border-amber-900/40 dark:bg-vikinger-dark-200">
    <div class="bg-gradient-to-r from-amber-500 to-orange-500 px-4 py-4 text-white">
      <div class="flex items-center gap-2">
        <Icon icon="mdi:hand-heart" class="h-6 w-6" />
        <h3 class="font-black">สนับสนุนคอร์ส</h3>
      </div>
      <p class="mt-1 text-xs text-white/80">บริจาคแต้มเพื่อสนับสนุนผู้สอนและการเรียนรู้</p>
    </div>

    <div class="space-y-3 p-4">
      <div class="flex items-center justify-between rounded-xl bg-amber-50 px-3 py-2 text-sm dark:bg-amber-900/20">
        <span class="text-slate-600 dark:text-slate-300">แต้มในกองทุน</span>
        <strong v-if="!isLoadingAccount" class="text-amber-600">{{ Number(account?.balance || 0).toLocaleString() }}</strong>
        <span v-else class="h-4 w-16 animate-pulse rounded bg-amber-200 dark:bg-amber-800" />
      </div>

      <NuxtLink
        :to="`/Learn/Courses/${courseId}/support`"
        class="flex w-full items-center justify-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-amber-600"
      >
        <Icon icon="mdi:gift-outline" class="h-5 w-5" />
        จัดการบริจาคและรับแต้ม
      </NuxtLink>

      <div v-if="donations.length" class="border-t border-slate-100 pt-3 dark:border-slate-700">
        <p class="mb-2 text-xs font-bold text-slate-500">การสนับสนุนล่าสุด</p>
        <div v-for="donation in donations.slice(0, 3)" :key="donation.id" class="flex justify-between py-1 text-xs">
          <span class="truncate text-slate-600 dark:text-slate-300">{{ donation.donor_display_name || 'ผู้สนับสนุนไม่ประสงค์ออกนาม' }}</span>
          <strong class="text-amber-600">{{ Number(donation.points_amount || 0).toLocaleString() }} แต้ม</strong>
        </div>
      </div>
    </div>
  </section>
</template>
