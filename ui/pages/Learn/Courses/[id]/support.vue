<script setup lang="ts">
import { Icon } from '@iconify/vue'
import CourseDonationModal from '~/components/donation/CourseDonationModal.vue'
import AdvertiseCtaWidget from '~/components/widgets/AdvertiseCtaWidget.vue'
import CampaignWidget from '~/components/campaign/CampaignWidget.vue'

definePageMeta({ layout: 'main', middleware: 'auth' })
useHead({ title: 'รายได้ - Nuxnan' })

const route = useRoute()
const authStore = useAuthStore()
const course = inject<any>('course', ref(null))
const isCourseAdmin = inject<any>('isCourseAdmin', ref(false))
const showDonationModal = ref(false)
const courseId = computed(() => String(route.params.id))
const { account, transactions, fetchAccount, fetchTransactions, isLoadingAccount } = useCoursePoints(courseId)
const { fetchCourseDonations } = useCourseDonations()
const donations = ref<any[]>([])

const load = async () => {
  await Promise.all([
    fetchAccount(),
    fetchTransactions(),
    fetchCourseDonations(Number(courseId.value), { per_page: 20 }).then((response: any) => {
      donations.value = response?.data || []
    })
  ])
}

onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <section class="rounded-2xl bg-gradient-to-br from-amber-500 via-orange-500 to-rose-500 p-6 text-white shadow-lg">
      <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <p class="text-sm font-bold text-white/80">Course Revenue</p>
          <h1 class="mt-1 text-2xl font-black">รายได้</h1>
          <p class="mt-2 text-sm text-white/85">รวมการสนับสนุนแต้มสะสมและรายได้จากการโฆษณาไว้ในหน้าเดียว</p>
        </div>
        <div class="flex flex-wrap gap-3">
          <button v-if="course?.donation_enabled !== false" type="button" class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-3 font-bold text-orange-600 shadow" @click="showDonationModal = true">
            <Icon icon="mdi:hand-heart" class="h-5 w-5" /> บริจาคแต้ม
          </button>
          <NuxtLink :to="`/earn/advertise/create?scope=course&course_id=${courseId}`" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 font-bold text-white shadow hover:bg-indigo-700">
            <Icon icon="mdi:bullhorn-outline" class="h-5 w-5" /> สร้างแคมเปญโฆษณา
          </NuxtLink>
          <NuxtLink to="/earn/advertise" class="inline-flex items-center gap-2 rounded-xl border border-white/50 px-5 py-3 font-bold text-white hover:bg-white/10">
            <Icon icon="mdi:play-circle-outline" class="h-5 w-5" /> ดูโฆษณาเพื่อรับรายได้
          </NuxtLink>
        </div>
      </div>
    </section>

    <div class="grid gap-4 sm:grid-cols-3">
      <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-vikinger-dark-200"><p class="text-sm text-slate-500">ยอดแต้มคงเหลือ</p><p class="mt-2 text-3xl font-black text-amber-500">{{ isLoadingAccount ? '—' : Number(account?.balance || 0).toLocaleString() }}</p></div>
      <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-vikinger-dark-200"><p class="text-sm text-slate-500">แต้มที่ได้รับทั้งหมด</p><p class="mt-2 text-3xl font-black text-emerald-500">{{ Number(account?.total_earned || 0).toLocaleString() }}</p></div>
      <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-vikinger-dark-200"><p class="text-sm text-slate-500">ผู้สนับสนุนล่าสุด</p><p class="mt-2 text-3xl font-black text-indigo-500">{{ donations.length }}</p></div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
      <section class="rounded-2xl bg-white p-5 shadow-sm dark:bg-vikinger-dark-200"><h2 class="mb-4 font-black">รายการบริจาค</h2><div v-if="!donations.length" class="py-8 text-center text-sm text-slate-500">ยังไม่มีรายการบริจาค</div><div v-for="donation in donations" :key="donation.id" class="flex items-center justify-between border-b border-slate-100 py-3 text-sm last:border-0 dark:border-slate-700"><span>{{ donation.donor_display_name || 'ผู้สนับสนุนไม่ประสงค์ออกนาม' }}</span><strong class="text-amber-600">{{ Number(donation.points_amount || 0).toLocaleString() }} แต้ม</strong></div></section>
      <section class="rounded-2xl bg-white p-5 shadow-sm dark:bg-vikinger-dark-200"><h2 class="mb-4 font-black">ประวัติการรับแต้ม</h2><div v-if="!transactions.length" class="py-8 text-center text-sm text-slate-500">ยังไม่มีประวัติรายการ</div><div v-for="transaction in transactions" :key="transaction.id" class="flex items-center justify-between border-b border-slate-100 py-3 text-sm last:border-0 dark:border-slate-700"><span class="truncate">{{ transaction.description || transaction.type || 'รายการแต้ม' }}</span><strong :class="Number(transaction.amount || transaction.points || 0) >= 0 ? 'text-emerald-600' : 'text-red-500'">{{ Number(transaction.amount || transaction.points || 0).toLocaleString() }}</strong></div></section>
    </div>

    <section class="grid gap-6 lg:grid-cols-2">
      <div class="rounded-2xl border border-indigo-100 bg-white p-5 shadow-sm dark:border-indigo-900/40 dark:bg-vikinger-dark-200"><div class="mb-4 flex items-center gap-3"><span class="rounded-xl bg-indigo-100 p-2 text-indigo-600 dark:bg-indigo-900/30"><Icon icon="mdi:bullhorn-outline" class="h-6 w-6" /></span><div><h2 class="font-black">การสร้างรายได้จากโฆษณา</h2><p class="text-sm text-slate-500">สร้างแคมเปญให้ผู้เรียนเห็นในรายวิชานี้</p></div></div><AdvertiseCtaWidget v-if="course" scope-type="course" :target-id="course.id" :target-name="course.name" /></div>
      <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm dark:border-emerald-900/40 dark:bg-vikinger-dark-200"><div class="mb-4 flex items-center gap-3"><span class="rounded-xl bg-emerald-100 p-2 text-emerald-600 dark:bg-emerald-900/30"><Icon icon="mdi:play-circle-outline" class="h-6 w-6" /></span><div><h2 class="font-black">ดูโฆษณาเพื่อรับรายได้</h2><p class="text-sm text-slate-500">ดูแคมเปญและรับรางวัลตามเงื่อนไข</p></div></div><CampaignWidget v-if="course" scope="course" :academy-id="course.academy_id" :course-id="course.id" placement="course-revenue" :limit="4" /></div>
    </section>

    <CourseDonationModal v-if="course" v-model:visible="showDonationModal" :course-id="Number(course.id)" :course-name="course.name" :course-owner-id="Number(course.user_id)" :balance="authStore.user?.pp" @update:visible="load" />
  </div>
</template>
