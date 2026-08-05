<script setup lang="ts">
import { Icon } from '@iconify/vue'
import CourseSupportPanel from '~/components/learn/course/points/CourseSupportPanel.vue'
import AdvertiseCtaWidget from '~/components/widgets/AdvertiseCtaWidget.vue'
import CampaignWidget from '~/components/campaign/CampaignWidget.vue'

definePageMeta({ layout: 'main', middleware: 'auth' })
useHead({ title: 'รายได้ - Nuxnan' })

const course = inject<any>('course', ref(null))
const isCourseAdmin = inject<any>('isCourseAdmin', ref(false))
</script>

<template>
  <div class="space-y-6">
    <section class="rounded-2xl border border-violet-100 bg-gradient-to-br from-violet-50 to-sky-50 p-6 shadow-sm dark:border-violet-900/40 dark:from-slate-900 dark:to-indigo-950/40">
      <div>
        <p class="text-sm font-semibold text-violet-500 dark:text-violet-300">Course Revenue</p>
        <h1 class="mt-1 text-2xl font-black text-gray-900 dark:text-white">รายได้</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">รวมการสนับสนุนแต้มสะสมและรายได้จากการโฆษณาไว้ในหน้าเดียว</p>
      </div>
    </section>

    <CourseSupportPanel v-if="course" :course="course" :is-course-admin="isCourseAdmin" />

    <section class="grid gap-6 lg:grid-cols-2">
      <div class="rounded-2xl border border-indigo-100 bg-white p-5 shadow-sm dark:border-indigo-900/40 dark:bg-vikinger-dark-200">
        <div class="mb-4 flex items-center gap-3">
          <span class="rounded-xl bg-indigo-100 p-2 text-indigo-600 dark:bg-indigo-900/30"><Icon icon="mdi:bullhorn-outline" class="h-6 w-6" /></span>
          <div>
            <h2 class="font-black">ลงโฆษณา &amp; สนับสนุนด้วยเงิน</h2>
            <p class="text-sm text-slate-500">ชำระผ่าน Wallet เพื่อโปรโมตหรือให้ทุนรายวิชานี้</p>
          </div>
        </div>
        <AdvertiseCtaWidget v-if="course" scope-type="course" :target-id="course.id" :target-name="course.name" :academy-id="course.academy_id" />
      </div>
      <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm dark:border-emerald-900/40 dark:bg-vikinger-dark-200">
        <div class="mb-4 flex items-center gap-3">
          <span class="rounded-xl bg-emerald-100 p-2 text-emerald-600 dark:bg-emerald-900/30"><Icon icon="mdi:play-circle-outline" class="h-6 w-6" /></span>
          <div>
            <h2 class="font-black">ดูโฆษณาเพื่อรับรายได้</h2>
            <p class="text-sm text-slate-500">ดูแคมเปญและรับรางวัลตามเงื่อนไข</p>
          </div>
        </div>
        <CampaignWidget v-if="course" scope="course" :academy-id="course.academy_id" :course-id="course.id" placement="course-revenue" :limit="4" hide-header />
      </div>
    </section>
  </div>
</template>
