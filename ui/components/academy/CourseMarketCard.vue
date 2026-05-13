<template>
  <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm overflow-hidden border border-slate-200 dark:border-slate-700 hover:shadow-md transition-shadow flex flex-col">
    <!-- Cover -->
    <div class="relative h-40 overflow-hidden shrink-0">
      <img
        :src="course.cover || `${config.public.apiBase}/storage/images/courses/covers/default_cover.jpg`"
        :alt="course.name"
        class="w-full h-full object-cover transition-transform hover:scale-105 duration-500"
      />
      <div class="absolute top-2 right-2 flex flex-col gap-1">
        <span v-if="course.level" class="bg-black/50 backdrop-blur-md text-white text-[10px] px-2 py-0.5 rounded uppercase font-bold">
          {{ course.level }}
        </span>
        <span v-if="isOwned" class="bg-green-500 text-white text-[10px] px-2 py-0.5 rounded uppercase font-bold flex items-center gap-1">
          <Icon icon="mdi:check-circle" class="w-3 h-3" />
          โคลนแล้ว
        </span>
      </div>
    </div>

    <!-- Content -->
    <div class="p-4 flex flex-col flex-1">
      <!-- Instructor -->
      <div class="flex items-center gap-2 mb-2">
        <img
          :src="course.user?.profile_photo_url || '/images/avatar-placeholder.png'"
          class="w-5 h-5 rounded-full object-cover shrink-0"
        />
        <span class="text-xs text-slate-500 dark:text-slate-400 truncate">
          {{ course.user?.name || 'Unknown Instructor' }}
        </span>
      </div>

      <!-- Title -->
      <h3 class="font-bold text-slate-800 dark:text-white line-clamp-2 h-10 mb-2 text-sm leading-tight">
        {{ course.name }}
      </h3>

      <!-- Stats -->
      <div class="flex items-center gap-3 text-[10px] text-slate-500 dark:text-slate-400 mb-3">
        <div class="flex items-center gap-1">
          <Icon icon="mdi:book-open-variant" class="w-3 h-3" />
          {{ course.course_lessons_count || 0 }} บทเรียน
        </div>
        <div v-if="hasClone" class="flex items-center gap-1">
          <Icon icon="mdi:content-copy" class="w-3 h-3" />
          Clone แล้ว {{ course.total_sales || 0 }} ครั้ง
        </div>
      </div>

      <!-- Action section -->
      <div class="mt-auto border-t border-slate-100 dark:border-slate-700 pt-3">

        <!-- Both: clone + enroll -->
        <div v-if="hasClone && hasEnroll" class="grid grid-cols-2 divide-x divide-slate-100 dark:divide-slate-700">
          <!-- Clone column -->
          <div class="flex flex-col gap-1.5 pr-2">
            <span class="text-[10px] uppercase font-bold text-slate-400 flex items-center gap-1">
              <Icon icon="mdi:content-copy" class="w-3 h-3" /> โคลน
            </span>
            <div v-if="isOwned" class="bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 text-xs font-bold px-2 py-1.5 rounded-lg text-center">
              โคลนแล้ว
            </div>
            <template v-else>
              <div class="flex flex-col font-bold text-xs">
                <span v-if="course.price <= 0" class="text-green-600">FREE CLONE</span>
                <template v-else>
                  <span class="text-violet-600">฿{{ formatNumber(course.price) }}</span>
                  <span class="text-amber-600 flex items-center gap-0.5 text-[10px]">
                    <Icon icon="mdi:database" class="w-3 h-3" />{{ formatNumber(Math.ceil(course.price * 1200)) }} P
                  </span>
                </template>
              </div>
              <button @click="$emit('clone', course)" class="w-full bg-violet-100 dark:bg-violet-900/30 hover:bg-violet-600 hover:text-white text-violet-700 dark:text-violet-300 text-xs font-bold px-2 py-1.5 rounded-lg transition-colors flex items-center justify-center gap-1">
                <Icon icon="mdi:cart-plus" class="w-3.5 h-3.5" />ซื้อลิขสิทธิ์
              </button>
            </template>
          </div>

          <!-- Enroll column -->
          <div class="flex flex-col gap-1.5 pl-2">
            <span class="text-[10px] uppercase font-bold text-slate-400 flex items-center gap-1">
              <Icon icon="mdi:school" class="w-3 h-3" /> เรียน
            </span>
            <div v-if="isMember">
              <span v-if="memberStatus === 'active'" class="bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-xs font-bold px-2 py-1.5 rounded-lg block text-center">
                กำลังเรียน
              </span>
              <span v-else class="bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400 text-xs font-bold px-2 py-1.5 rounded-lg block text-center">
                รออนุมัติ
              </span>
            </div>
            <template v-else>
              <span class="text-xs font-bold" :class="enrollIsFree ? 'text-green-600' : 'text-blue-600'">
                {{ enrollIsFree ? 'FREE' : `฿${formatNumber(course.tuition_fees)}` }}
              </span>
              <button @click="$emit('enroll', course)" class="w-full bg-blue-100 dark:bg-blue-900/30 hover:bg-blue-600 hover:text-white text-blue-700 dark:text-blue-300 text-xs font-bold px-2 py-1.5 rounded-lg transition-colors flex items-center justify-center gap-1">
                <Icon icon="mdi:school" class="w-3.5 h-3.5" />สมัคร
              </button>
            </template>
          </div>
        </div>

        <!-- Clone only -->
        <div v-else-if="hasClone" class="flex items-center justify-between">
          <div v-if="isOwned" class="bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 text-xs font-bold px-3 py-2 rounded-lg">
            โคลนแล้ว
          </div>
          <template v-else>
            <div class="flex flex-col font-bold text-sm">
              <span v-if="course.price <= 0" class="text-green-600 text-xs">FREE CLONE</span>
              <template v-else>
                <span class="text-violet-600">฿{{ formatNumber(course.price) }}</span>
                <span class="text-amber-600 flex items-center gap-1 text-[10px]">
                  <Icon icon="mdi:database" class="w-3.5 h-3.5" />{{ formatNumber(Math.ceil(course.price * 1200)) }} P
                </span>
              </template>
            </div>
            <button @click="$emit('clone', course)" class="bg-slate-100 dark:bg-slate-700 hover:bg-violet-600 hover:text-white text-slate-700 dark:text-slate-200 p-2 rounded-lg transition-colors group">
              <Icon icon="mdi:cart-plus" class="w-5 h-5 group-hover:scale-110 transition-transform" />
            </button>
          </template>
        </div>

        <!-- Enroll only -->
        <div v-else-if="hasEnroll" class="flex items-center justify-between">
          <div v-if="isMember">
            <span v-if="memberStatus === 'active'" class="bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-xs font-bold px-3 py-2 rounded-lg block">
              กำลังเรียน
            </span>
            <span v-else class="bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400 text-xs font-bold px-3 py-2 rounded-lg block">
              รออนุมัติ
            </span>
          </div>
          <template v-else>
            <span class="text-sm font-bold" :class="enrollIsFree ? 'text-green-600' : 'text-blue-600'">
              {{ enrollIsFree ? 'FREE' : `฿${formatNumber(course.tuition_fees)}` }}
            </span>
            <button @click="$emit('enroll', course)" class="bg-slate-100 dark:bg-slate-700 hover:bg-blue-600 hover:text-white text-slate-700 dark:text-slate-200 p-2 rounded-lg transition-colors group">
              <Icon icon="mdi:school" class="w-5 h-5 group-hover:scale-110 transition-transform" />
            </button>
          </template>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps<{
  course: any
}>()

const config = useRuntimeConfig()

defineEmits(['clone', 'enroll'])

const hasClone = computed(() => props.course.is_for_marketplace)
const hasEnroll = computed(() => props.course.saleable)
const isOwned = computed(() => props.course.is_owned)
const enrollIsFree = computed(() => !props.course.tuition_fees || Number(props.course.tuition_fees) === 0)
const isMember = computed(() => props.course.enrollment_status?.is_member)
const memberStatus = computed(() => props.course.enrollment_status?.status)

const formatNumber = (num: number) => new Intl.NumberFormat().format(num || 0)
</script>
