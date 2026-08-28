<script setup lang="ts">
import { Icon } from '@iconify/vue'
import CourseSupportPanel from '~/components/learn/course/points/CourseSupportPanel.vue'
import AdvertiseCtaWidget from '~/components/widgets/AdvertiseCtaWidget.vue'
import CampaignWidget from '~/components/campaign/CampaignWidget.vue'

definePageMeta({ layout: 'main', middleware: 'auth' })
useHead({ title: 'รายได้ - Nuxnan' })

const course = inject<any>('course', ref(null))
const isCourseAdmin = inject<any>('isCourseAdmin', ref(false))

// เงินเข้าก่อน → พักในกองทุน → นักเรียนถึงกดรับได้ ลำดับของหน้าเดินตามลำดับนี้
const flowSteps = [
  { icon: 'mdi:cash-plus', title: 'ให้ทุน', description: 'ลงโฆษณาหรือสนับสนุนด้วยเงิน' },
  { icon: 'mdi:safe-square-outline', title: 'เข้ากองทุนรายวิชา', description: 'เก็บเป็นแต้มรอแจกจ่าย' },
  { icon: 'mdi:gift-outline', title: 'สมาชิกกดรับ', description: 'กดรับแต้มจากผู้สนับสนุน' },
]
</script>

<template>
  <div class="space-y-8">
    <section class="rounded-2xl border border-violet-100 bg-gradient-to-br from-violet-50 to-sky-50 px-0 py-6 sm:px-6 shadow-sm dark:border-violet-900/40 dark:from-slate-900 dark:to-indigo-950/40">
      <p class="text-sm font-semibold text-violet-500 dark:text-violet-300">Course Revenue</p>
      <h1 class="mt-1 text-2xl font-black text-gray-900 dark:text-white">รายได้</h1>
      <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">รายวิชานี้ได้รับทุนจากผู้สนับสนุน แล้วจ่ายกลับเป็นแต้มให้สมาชิกที่มากดรับ</p>

      <ol class="mt-5 grid gap-3 sm:grid-cols-3 sm:gap-6">
        <li
          v-for="(step, index) in flowSteps"
          :key="step.title"
          class="relative flex items-center gap-3 rounded-xl border border-violet-100 bg-white/70 p-3 dark:border-violet-900/40 dark:bg-gray-900/40"
        >
          <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-vikinger-purple/10 text-vikinger-purple dark:bg-vikinger-purple/20">
            <Icon :icon="step.icon" class="h-5 w-5" />
          </span>
          <div class="min-w-0">
            <p class="text-xs font-bold text-gray-900 dark:text-white">{{ index + 1 }}. {{ step.title }}</p>
            <p class="mt-0.5 text-[11px] leading-tight text-gray-500 dark:text-gray-400">{{ step.description }}</p>
          </div>
          <Icon
            v-if="index < flowSteps.length - 1"
            icon="mdi:arrow-right"
            class="absolute -right-5 top-1/2 hidden h-4 w-4 -translate-y-1/2 text-violet-400 dark:text-violet-600 sm:block"
          />
        </li>
      </ol>
    </section>

    <!-- ขั้นที่ 1 — ต้องมีเงินเข้าก่อน ถึงจะมีอะไรให้กดรับ -->
    <section>
      <div class="flex items-center gap-3">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-sm font-black text-white">1</span>
        <div class="min-w-0">
          <h2 class="text-lg font-black text-gray-900 dark:text-white">ให้ทุนรายวิชานี้</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400">ชำระผ่าน Wallet เพื่อโปรโมตหรือให้ทุนการเรียนรู้</p>
        </div>
      </div>
      <div class="mt-3">
        <AdvertiseCtaWidget
          v-if="course"
          scope-type="course"
          :target-id="course.id"
          :target-name="course.name"
          :academy-id="course.academy_id"
        />
      </div>
    </section>

    <!-- ขั้นที่ 2 — กองทุน สถิติ ประวัติ แล้วจบด้วยการ์ดกดรับล่างสุด -->
    <section>
      <div class="flex items-center gap-3">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-vikinger-purple text-sm font-black text-white">2</span>
        <div class="min-w-0">
          <h2 class="text-lg font-black text-gray-900 dark:text-white">กองทุน &amp; การกดรับ</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400">ดูผู้สนับสนุนและประวัติก่อน แล้วค่อยกดรับแต้มด้านล่าง</p>
        </div>
      </div>
      <div class="mt-3">
        <CourseSupportPanel v-if="course" :course="course" :is-course-admin="isCourseAdmin" />
      </div>
    </section>

    <section>
      <div class="flex items-center gap-3">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-300">
          <Icon icon="mdi:play-circle-outline" class="h-5 w-5" />
        </span>
        <div class="min-w-0">
          <h2 class="text-lg font-black text-gray-900 dark:text-white">ดูโฆษณาเพื่อรับรายได้</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400">อีกช่องทางรับรายได้ ดูแคมเปญและรับรางวัลตามเงื่อนไข</p>
        </div>
      </div>
      <div class="mt-3">
        <CampaignWidget
          v-if="course"
          scope="course"
          :academy-id="course.academy_id"
          :course-id="course.id"
          placement="course-revenue"
          :limit="4"
          hide-header
        />
      </div>
    </section>
  </div>
</template>
