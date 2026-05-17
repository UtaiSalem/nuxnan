<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  course: any
  courseMemberOfAuth?: any
  isCourseAdmin?: boolean
  courseGroups?: any[]
  isEnrolling?: boolean
  isTogglingFavorite?: boolean
  isWishlisted?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  courseMemberOfAuth: null,
  isCourseAdmin: false,
  courseGroups: () => [],
  isEnrolling: false,
  isTogglingFavorite: false,
  isWishlisted: false
})

const emit = defineEmits<{
  'enroll': []
  'toggle-favorite': []
  'purchase': []
  'update:selectedGroupId': [id: number | null]
}>()

const selectedGroupId = ref<number | null>(null)

watch(selectedGroupId, (val) => {
  emit('update:selectedGroupId', val)
})

const config = useRuntimeConfig()

// Calculate course price
const coursePrice = computed(() => {
  const price = props.course?.tuition_fees ?? props.course?.price ?? 0
  const discount = props.course?.discount ?? 0
  if (price > 0 && discount > 0) {
    return price - (price * discount / 100)
  }
  return price
})

const isMember = computed(() => !!props.courseMemberOfAuth)
const hasGroups = computed(() => props.courseGroups.length > 0)

const getCoverUrl = (coverPath: string | null) => {
  if (!coverPath) return '/images/default-cover.jpg'
  if (coverPath.startsWith('http')) return coverPath
  return `${config.public.apiBase}/storage/images/courses/covers/${coverPath}`
}

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('th-TH', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price || 0)
}
</script>

<template>
  <div class="bg-white dark:bg-vikinger-dark-200 rounded-2xl shadow-xl border border-gray-100 dark:border-vikinger-dark-100 overflow-hidden sticky top-24">
    <!-- Video Preview / Cover -->
    <div class="relative aspect-video bg-gray-200 dark:bg-gray-700 overflow-hidden">
      <img
        :src="getCoverUrl(course.cover)"
        :alt="course.name"
        class="w-full h-full object-cover transition-transform duration-500 hover:scale-110"
      />
      <div class="absolute inset-0 flex items-center justify-center bg-black/30 group cursor-pointer">
        <div class="w-16 h-16 bg-vikinger-purple rounded-full flex items-center justify-center shadow-2xl transform transition-transform group-hover:scale-110">
          <Icon icon="fluent:play-24-filled" class="w-8 h-8 text-white ml-1" />
        </div>
      </div>
    </div>

    <div class="p-6">
      <!-- Price -->
      <div class="mb-6 text-center">
        <template v-if="coursePrice > 0">
          <div class="flex items-center justify-center gap-2">
            <span class="text-3xl font-black text-gray-900 dark:text-white">
              ฿{{ formatPrice(coursePrice) }}
            </span>
            <span v-if="course.discount > 0" class="text-sm text-gray-400 line-through">
              ฿{{ formatPrice(course.tuition_fees) }}
            </span>
          </div>
          <div v-if="course.discount > 0" class="mt-1">
            <span class="px-2 py-0.5 rounded-md bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400 text-[10px] font-bold uppercase">
              ลด {{ course.discount }}%
            </span>
          </div>
        </template>
        <div v-else class="text-3xl font-black text-emerald-500">
          ฟรี
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="space-y-3 mb-6">
        <template v-if="!isMember && !isCourseAdmin">
          <!-- Group Selector -->
          <div v-if="hasGroups" class="space-y-1.5">
            <label class="text-[10px] font-bold uppercase text-gray-400 px-1">เลือกกลุ่มเรียน</label>
            <select
              v-model="selectedGroupId"
              class="w-full px-4 py-2.5 border border-gray-100 dark:border-vikinger-dark-100 rounded-xl text-sm bg-gray-50 dark:bg-vikinger-dark-100 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-vikinger-purple/50 transition-all"
            >
              <option :value="null">-- ไม่ระบุกลุ่ม --</option>
              <option v-for="g in courseGroups" :key="g.id" :value="g.id">{{ g.name }}</option>
            </select>
          </div>
          
          <button
            @click="emit('enroll')"
            :disabled="isEnrolling"
            class="w-full py-3.5 bg-gradient-to-r from-vikinger-purple to-indigo-600 text-white rounded-xl font-black shadow-lg shadow-vikinger-purple/20 hover:shadow-vikinger-purple/40 hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2 disabled:opacity-50"
          >
            <Icon v-if="isEnrolling" icon="svg-spinners:ring-resize" class="w-5 h-5" />
            <Icon v-else icon="fluent:add-24-filled" class="w-5 h-5" />
            <span>{{ isEnrolling ? 'กำลังสมัคร...' : 'สมัครเรียนตอนนี้' }}</span>
          </button>
        </template>

        <NuxtLink
          v-else-if="isMember"
          :to="`/Learn/Courses/${course.id}/lessons`"
          class="w-full py-3.5 bg-emerald-500 text-white rounded-xl font-black shadow-lg shadow-emerald-500/20 hover:bg-emerald-600 hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2"
        >
          <Icon icon="fluent:play-24-filled" class="w-5 h-5" />
          <span>เข้าสู่บทเรียน</span>
        </NuxtLink>

        <button 
          @click="emit('toggle-favorite')"
          :disabled="isTogglingFavorite"
          :class="[
            'w-full py-3 rounded-xl font-bold flex items-center justify-center gap-2 transition-all disabled:opacity-50',
            isWishlisted 
              ? 'bg-red-50 dark:bg-red-900/20 text-red-500 border border-red-100 dark:border-red-900/30' 
              : 'bg-gray-100 dark:bg-vikinger-dark-100 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-vikinger-dark-50'
          ]"
        >
          <Icon v-if="isTogglingFavorite" icon="svg-spinners:ring-resize" class="w-5 h-5" />
          <Icon v-else :icon="isWishlisted ? 'fluent:heart-24-filled' : 'fluent:heart-24-regular'" class="w-5 h-5" />
          <span>{{ isWishlisted ? 'อยู่ในรายการโปรดแล้ว' : 'เพิ่มในรายการโปรด' }}</span>
        </button>
      </div>

      <!-- Course Stats List -->
      <div class="space-y-4 py-6 border-t border-gray-100 dark:border-vikinger-dark-100">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3 text-gray-500 dark:text-gray-400">
            <Icon icon="fluent:people-24-regular" class="w-5 h-5 text-indigo-500" />
            <span class="text-xs font-bold uppercase tracking-wider">ผู้เรียน</span>
          </div>
          <span class="text-sm font-black text-gray-900 dark:text-white">{{ course.enrolled_students || 0 }} คน</span>
        </div>
        
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3 text-gray-500 dark:text-gray-400">
            <Icon icon="fluent:book-24-regular" class="w-5 h-5 text-blue-500" />
            <span class="text-xs font-bold uppercase tracking-wider">บทเรียน</span>
          </div>
          <span class="text-sm font-black text-gray-900 dark:text-white">{{ course.course_lessons_count ?? course.lessons_count ?? 0 }} บท</span>
        </div>

        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3 text-gray-500 dark:text-gray-400">
            <Icon icon="fluent:clock-24-regular" class="w-5 h-5 text-emerald-500" />
            <span class="text-xs font-bold uppercase tracking-wider">ระยะเวลา</span>
          </div>
          <span class="text-sm font-black text-gray-900 dark:text-white">{{ course.hours ?? course.duration ?? 0 }} ชม.</span>
        </div>

        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3 text-gray-500 dark:text-gray-400">
            <Icon icon="fluent:calendar-24-regular" class="w-5 h-5 text-amber-500" />
            <span class="text-xs font-bold uppercase tracking-wider">เริ่มเรียน</span>
          </div>
          <span class="text-sm font-black text-gray-900 dark:text-white">{{ formatDate(course.start_date) }}</span>
        </div>
      </div>

      <!-- Features Checklist -->
      <div class="pt-6 border-t border-gray-100 dark:border-vikinger-dark-100">
        <h4 class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-4">สิ่งที่จะได้รับจากวิชานี้</h4>
        <ul class="space-y-3">
          <li v-for="feature in ['เข้าถึงเนื้อหาได้ตลอดชีพ', 'ใบประกาศนียบัตรรับรอง', 'รองรับการเรียนทุกอุปกรณ์', 'เอกสารประกอบการเรียน']" :key="feature" class="flex items-center gap-3">
            <Icon icon="fluent:checkmark-circle-24-filled" class="w-5 h-5 text-emerald-500 shrink-0" />
            <span class="text-xs font-bold text-gray-600 dark:text-gray-300">{{ feature }}</span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>
