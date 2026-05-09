<template>
  <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm overflow-hidden border border-slate-200 dark:border-slate-700 hover:shadow-md transition-shadow">
    <!-- Course Cover -->
    <div class="relative h-40 overflow-hidden">
      <img 
        :src="course.cover_url || '/images/course-placeholder.jpg'" 
        :alt="course.name"
        class="w-full h-full object-cover transition-transform hover:scale-105 duration-500"
      />
      <div class="absolute top-2 right-2 flex flex-col gap-1">
        <span v-if="course.level" class="bg-black/50 backdrop-blur-md text-white text-[10px] px-2 py-0.5 rounded uppercase font-bold">
          {{ course.level }}
        </span>
        <span v-if="course.is_owned" class="bg-green-500 text-white text-[10px] px-2 py-0.5 rounded uppercase font-bold flex items-center gap-1">
          <Icon icon="mdi:check-circle" class="w-3 h-3" />
          ซื้อแล้ว
        </span>
      </div>
    </div>

    <!-- Course Content -->
    <div class="p-4">
      <div class="flex items-center gap-2 mb-2">
        <img 
          :src="course.user?.profile_photo_url || '/images/avatar-placeholder.png'" 
          class="w-5 h-5 rounded-full object-cover"
        />
        <span class="text-xs text-slate-500 dark:text-slate-400 truncate">
          {{ course.user?.name || 'Unknown Instructor' }}
        </span>
      </div>

      <h3 class="font-bold text-slate-800 dark:text-white line-clamp-2 h-10 mb-2 text-sm leading-tight">
        {{ course.name }}
      </h3>

      <!-- Stats -->
      <div class="flex items-center gap-3 text-[10px] text-slate-500 dark:text-slate-400 mb-4">
        <div class="flex items-center gap-1">
          <Icon icon="mdi:book-open-variant" class="w-3 h-3" />
          {{ course.course_lessons_count || 0 }} บทเรียน
        </div>
        <div class="flex items-center gap-1">
          <Icon icon="mdi:cart" class="w-3 h-3" />
          {{ course.total_sales || 0 }} ยอดขาย
        </div>
      </div>

      <!-- Pricing & Actions -->
      <div class="flex items-center justify-between mt-auto">
        <div class="flex flex-col">
          <template v-if="course.price_type === 'free'">
            <span class="text-green-600 dark:text-green-400 font-bold text-lg">FREE</span>
          </template>
          <template v-else>
            <div v-if="course.price_type === 'points' || course.price_type === 'both'" class="flex items-center gap-1 text-amber-600 dark:text-amber-400 font-bold">
              <Icon icon="mdi:database" class="w-4 h-4" />
              <span>{{ formatNumber(course.price_points) }}</span>
            </div>
            <div v-if="course.price_type === 'wallet' || course.price_type === 'both'" class="flex items-center gap-1 text-primary-600 dark:text-primary-400 font-bold">
              <span class="text-xs">฿</span>
              <span>{{ formatNumber(course.price) }}</span>
            </div>
          </template>
        </div>

        <!-- ถ้าซื้อแล้วแสดง disabled badge แทนปุ่ม -->
        <div v-if="course.is_owned" 
          class="bg-green-100 dark:bg-green-900/30 text-green-600 text-xs font-bold px-3 py-2 rounded-lg">
          ซื้อแล้ว
        </div>
        <button 
          v-else
          @click="$emit('buy', course)"
          class="bg-slate-100 dark:bg-slate-700 hover:bg-primary-600 hover:text-white dark:hover:bg-primary-600 text-slate-700 dark:text-slate-200 p-2 rounded-lg transition-colors group"
        >
          <Icon icon="mdi:cart-plus" class="w-5 h-5 group-hover:scale-110 transition-transform" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'

defineProps<{
  course: any
}>()

defineEmits(['buy', 'view'])

const formatNumber = (num: number) => {
  return new Intl.NumberFormat().format(num)
}
</script>
