<script setup lang="ts">
/**
 * Course Gradebook — Score Audit
 * Teacher-facing audit of per-source score breakdown before grade publication.
 */
import { Icon } from '@iconify/vue'
import GradebookScoreTable from '~/components/learn/course/gradebook/GradebookScoreTable.vue'
import ResyncButton from '~/components/learn/course/gradebook/ResyncButton.vue'

const route = useRoute()
const api = useApi()
const courseId = computed(() => route.params.id as string)

const course = inject('course') as Ref<any>
const isCourseAdmin = inject('isCourseAdmin') as Ref<boolean>

// Filters / sort / pagination state
const members = ref<any[]>([])
const groups = ref<any[]>([])
const ungroupedCount = ref(0)
const isLoading = ref(true)
const courseTotalScore = ref(0)
const useLegacyGradebook = ref(false)

const searchQuery = ref('')
const activeGroup = ref<string | number>('all')
const sortBy = ref<'order_number' | 'name' | 'percentage' | 'grade' | 'last_synced_at'>('order_number')
const sortOrder = ref<'asc' | 'desc'>('asc')
const perPage = ref(20)

const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 20,
  from: 0,
  to: 0,
})

const stats = ref({
  total_members: 0,
  synced_members: 0,
  with_missing_sources_on_page: 0,
  last_full_sync_at: null as string | null,
})

const fetchData = async (page = 1) => {
  isLoading.value = true
  try {
    const params: Record<string, any> = {
      page,
      per_page: perPage.value,
      sort: sortBy.value,
      order: sortOrder.value,
    }
    if (activeGroup.value !== 'all') params.group_id = activeGroup.value
    if (searchQuery.value) params.search = searchQuery.value

    const res: any = await api.get(`/api/courses/${courseId.value}/score-breakdown`, { params })
    if (res?.success) {
      members.value = res.data || []
      groups.value = res.groups || []
      ungroupedCount.value = res.ungrouped_count || 0
      pagination.value = res.pagination || pagination.value
      stats.value = res.stats || stats.value
      courseTotalScore.value = res.course_total_score
      useLegacyGradebook.value = res.use_legacy_gradebook
    }
  } catch (err: any) {
    console.error('Failed to fetch score breakdown:', err)
    const swal = useSweetAlert()
    const message = err?.data?.message || err?.message || 'ไม่สามารถโหลดข้อมูลคะแนนได้'
    swal.error(message)
  } finally {
    isLoading.value = false
  }
}

// Debounced search
let searchTimeout: ReturnType<typeof setTimeout> | null = null
watch(searchQuery, () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => fetchData(1), 300)
})

watch([activeGroup, sortBy, sortOrder, perPage], () => fetchData(1))

const changePage = (page: number) => {
  if (page >= 1 && page <= pagination.value.last_page) fetchData(page)
}

const handleSort = (field: typeof sortBy.value) => {
  if (sortBy.value === field) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortBy.value = field
    sortOrder.value = 'asc'
  }
}

const handleResynced = () => fetchData(pagination.value.current_page)

const formatDate = (d?: string | null) => {
  if (!d) return '-'
  return new Date(d).toLocaleString('th-TH', { dateStyle: 'medium', timeStyle: 'short' })
}

onMounted(() => {
  if (courseId.value) fetchData()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
          <Icon icon="fluent:data-trending-24-regular" class="w-7 h-7 text-primary-500" />
          ตรวจสอบคะแนน
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1 text-sm">
          ตรวจสอบรายละเอียดคะแนนตามแหล่งที่มา ก่อนปิดเกรด — {{ course?.name }}
          <span v-if="useLegacyGradebook" class="ml-2 px-2 py-0.5 text-[10px] bg-amber-100 text-amber-800 rounded-full border border-amber-200">
            Legacy Mode
          </span>
        </p>
      </div>

      <div class="flex items-center gap-2 xl:pt-1">
        <ResyncButton :course-id="courseId" @resynced="handleResynced" />
      </div>
    </div>

    <!-- Legacy Warning -->
    <div v-if="useLegacyGradebook" class="p-4 bg-amber-50 border border-amber-200 rounded-xl flex gap-3 text-amber-800">
      <Icon icon="heroicons:information-circle" class="w-5 h-5 flex-shrink-0 mt-0.5" />
      <div class="text-sm">
        วิชานี้ใช้ระบบ <b>Legacy Gradebook</b> (ให้คะแนนด้วยมือ) ข้อมูลคะแนนในหน้านี้จะอิงตามสิ่งที่ครูบันทึกในหน้า "การประเมิน"
        <NuxtLink :to="`/Learn/Courses/${courseId}/gradebook/assessments`" class="font-bold underline ml-1">ไปหน้าการประเมิน</NuxtLink>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-3">
        <div class="text-xs text-gray-500">นักเรียนทั้งหมด</div>
        <div class="text-xl font-bold text-gray-900 dark:text-white">{{ stats.total_members }}</div>
      </div>
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-3">
        <div class="text-xs text-gray-500">ซิงค์คะแนนแล้ว</div>
        <div class="text-xl font-bold text-emerald-600">{{ stats.synced_members }}</div>
      </div>
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-3">
        <div class="text-xs text-gray-500">ขาดข้อมูล (หน้านี้)</div>
        <div class="text-xl font-bold text-amber-600">{{ stats.with_missing_sources_on_page }}</div>
      </div>
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-3">
        <div class="text-xs text-gray-500">ซิงค์ล่าสุด</div>
        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ formatDate(stats.last_full_sync_at) }}</div>
      </div>
    </div>

    <!-- Group Tabs -->
    <div v-if="groups.length > 0 || ungroupedCount > 0" class="flex flex-wrap gap-2">
      <button class="min-h-[44px] sm:min-h-0"
        @click="activeGroup = 'all'"
        :class="['px-3 py-1.5 text-sm rounded-lg border', activeGroup === 'all' ? 'bg-primary-500 text-white border-primary-500' : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700']"
      >
        ทั้งหมด ({{ stats.total_members }})
      </button>
      <button class="min-h-[44px] sm:min-h-0"
        v-for="g in groups"
        :key="g.id"
        @click="activeGroup = g.id"
        :class="['px-3 py-1.5 text-sm rounded-lg border', activeGroup === g.id ? 'bg-primary-500 text-white border-primary-500' : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700']"
      >
        {{ g.name }}
      </button>
      <button class="min-h-[44px] sm:min-h-0"
        v-if="ungroupedCount > 0"
        @click="activeGroup = 'ungrouped'"
        :class="['px-3 py-1.5 text-sm rounded-lg border', activeGroup === 'ungrouped' ? 'bg-primary-500 text-white border-primary-500' : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700']"
      >
        ยังไม่จัดกลุ่ม ({{ ungroupedCount }})
      </button>
    </div>

    <!-- Controls Row -->
    <div class="flex flex-col lg:flex-row lg:items-center gap-3">
      <div class="relative flex-1">
        <Icon icon="heroicons:magnifying-glass" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="ค้นหาชื่อ / username / รหัสนักเรียน"
          class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
        />
      </div>
      <div class="flex items-center gap-2 text-sm">
        <label class="text-gray-500">ต่อหน้า</label>
        <select v-model.number="perPage" class="border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-1.5 bg-white dark:bg-gray-800">
          <option :value="20">20</option>
          <option :value="50">50</option>
          <option :value="100">100</option>
        </select>
      </div>
    </div>

    <!-- Main Content -->
    <div v-if="isLoading" class="flex flex-col items-center justify-center py-20 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
      <p class="mt-4 text-gray-500 text-sm">กำลังคำนวณคะแนนและสรุปผล...</p>
    </div>

    <div v-else class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700">
      <div v-if="members.length === 0" class="p-12 text-center">
        <Icon icon="ph:users-four-bold" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
          {{ searchQuery ? 'ไม่พบนักเรียนที่ตรงกับการค้นหา' : 'ยังไม่มีนักเรียนในวิชา' }}
        </h3>
        <p class="text-gray-600 dark:text-gray-400">
          {{ searchQuery ? 'ลองค้นหาด้วยคำอื่น หรือเปลี่ยนตัวกรองกลุ่ม' : 'สรุปคะแนนจะแสดงเมื่อมีสมาชิกเข้าร่วมวิชาแล้ว' }}
        </p>
      </div>

      <GradebookScoreTable
        v-else
        :members="members"
        :course-id="courseId"
        :sort-by="sortBy"
        :sort-order="sortOrder"
        @sort="handleSort"
      />
    </div>

    <!-- Pagination -->
    <div v-if="members.length > 0" class="flex flex-col sm:flex-row items-center justify-between gap-4">
      <span class="text-sm text-gray-500 dark:text-gray-400">
        แสดง {{ pagination.from }}-{{ pagination.to }} จากทั้งหมด {{ pagination.total }} รายการ
      </span>
      <div class="flex gap-2">
        <button
          @click="changePage(pagination.current_page - 1)"
          :disabled="pagination.current_page === 1"
          class="min-h-[44px] sm:min-h-0 px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed dark:border-gray-600 dark:hover:bg-gray-700"
        >
          ก่อนหน้า
        </button>
        <div class="flex items-center gap-1">
          <template v-for="page in pagination.last_page" :key="page">
            <button class="min-h-[44px] sm:min-h-0"
              v-if="Math.abs(page - pagination.current_page) <= 2 || page === 1 || page === pagination.last_page"
              @click="changePage(page)"
              :class="['px-3 py-1 text-sm border rounded-lg', page === pagination.current_page ? 'bg-primary-500 text-white border-primary-500' : 'hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700']"
            >
              {{ page }}
            </button>
            <span v-else-if="Math.abs(page - pagination.current_page) === 3" class="px-2">...</span>
          </template>
        </div>
        <button
          @click="changePage(pagination.current_page + 1)"
          :disabled="pagination.current_page === pagination.last_page"
          class="min-h-[44px] sm:min-h-0 px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed dark:border-gray-600 dark:hover:bg-gray-700"
        >
          ถัดไป
        </button>
      </div>
    </div>

    <!-- Info Footer -->
    <div class="text-xs text-gray-500 flex flex-wrap items-center gap-4 px-2">
      <span class="flex items-center gap-1">
        <div class="w-2 h-2 rounded-full bg-primary-500"></div>
        คะแนนรวมรายวิชา: {{ courseTotalScore }} คะแนน
      </span>
      <span class="flex items-center gap-1">
        <Icon icon="heroicons:information-circle" class="w-3 h-3" />
        คะแนนของหน้าปัจจุบันจะถูก resync อัตโนมัติทุก 5 นาที — กดปุ่ม "Resync ทั้งคอร์ส" เพื่อบังคับซิงค์ทุกคน
      </span>
    </div>
  </div>
</template>
