<script setup lang="ts">
/**
 * Academy Admin - Courses Management
 * หน้าจัดการรายวิชาของสถาบัน
 */
import { Icon } from '@iconify/vue'
import CourseMarketCard from '~/components/academy/CourseMarketCard.vue'
import CourseMarketCardSkeleton from '~/components/academy/CourseMarketCardSkeleton.vue'
import CoursePurchaseModal from '~/components/academy/CoursePurchaseModal.vue'

definePageMeta({
  layout: 'main',
  middleware: 'auth',
  ssr: false,
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)

// Tabs State
const activeTab = ref('academy') // 'academy' | 'marketplace'

// Academy Courses State
const courses = ref<any[]>([])
const isLoading = ref(true)
const isLoadingMore = ref(false)
const searchQuery = ref('')
const selectedStatus = ref('all')
const currentPage = ref(1)
const totalPages = ref(1)
const perPage = ref(10)
const totalCourses = ref(0)
const academyId = ref<number | null>(null)

// Marketplace State
const marketCourses = ref<any[]>([])
const isMarketLoading = ref(false)
const isMarketLoadingMore = ref(false)
const marketSearchQuery = ref('')
const marketCurrentPage = ref(1)
const marketTotalPages = ref(1)
const marketTotalCourses = ref(0)

// Purchase State
const showPurchaseModal = ref(false)
const selectedCourse = ref<any>(null)

const hasMorePages = computed(() => currentPage.value < totalPages.value)
const marketHasMorePages = computed(() => marketCurrentPage.value < marketTotalPages.value)

const statuses = [
  { value: 'all', label: 'ทั้งหมด' },
  { value: 'published', label: 'เผยแพร่แล้ว' },
  { value: 'draft', label: 'ฉบับร่าง' },
  { value: 'archived', label: 'เก็บถาวร' },
]

const fetchCourses = async (append = false) => {
  if (!academyId.value) return

  if (append) {
    isLoadingMore.value = true
  } else {
    isLoading.value = true
    currentPage.value = 1
  }

  try {
    const params = new URLSearchParams({
      page: currentPage.value.toString(),
      per_page: perPage.value.toString(),
      ...(searchQuery.value && { search: searchQuery.value }),
      ...(selectedStatus.value !== 'all' && { status: selectedStatus.value }),
    })

    const response: any = await api.get(`/api/academies/${academyId.value}/courses?${params}`)


    const raw = response.courses
    const newCourses = Array.isArray(raw) ? raw : (raw?.data || [])

    if (append) {
      courses.value = [...courses.value, ...newCourses]
    } else {
      courses.value = newCourses
    }

    const pagination = response.pagination || raw?.pagination || {}
    totalPages.value = pagination.last_page || raw?.last_page || 1
    totalCourses.value = pagination.total || raw?.total || courses.value.length
  } catch (error) {
    console.error('Failed to fetch courses:', error)
  } finally {
    isLoading.value = false
    isLoadingMore.value = false
  }
}

const fetchMarketplace = async (append = false) => {
  if (append) {
    isMarketLoadingMore.value = true
  } else {
    isMarketLoading.value = true
    marketCurrentPage.value = 1
  }

  try {
    const params = new URLSearchParams({
      academy_id: academyId.value?.toString() || '',
      page: marketCurrentPage.value.toString(),
      ...(marketSearchQuery.value && { search: marketSearchQuery.value }),
    })

    // ใช้ endpoint /marketplace (คืน { data, meta } + owned_by_academy) ให้ตรงกับ parser ด้านล่าง
    const response: any = await api.get(`/api/courses/marketplace?${params}`)

    if (response.success) {
      const newCourses = response.data || []
      if (append) {
        marketCourses.value = [...marketCourses.value, ...newCourses]
      } else {
        marketCourses.value = newCourses
      }

      const meta = response.meta || {}
      marketTotalPages.value = meta.last_page || 1
      marketTotalCourses.value = meta.total || marketCourses.value.length
    }
  } catch (error) {
    console.error('Failed to fetch marketplace:', error)
  } finally {
    isMarketLoading.value = false
    isMarketLoadingMore.value = false
  }
}

const loadMore = () => {
  if (hasMorePages.value && !isLoadingMore.value) {
    currentPage.value++
    fetchCourses(true)
  }
}

const loadMoreMarket = () => {
  if (marketHasMorePages.value && !isMarketLoadingMore.value) {
    marketCurrentPage.value++
    fetchMarketplace(true)
  }
}

const handleSearch = () => {
  fetchCourses()
}

const handleMarketSearch = () => {
  fetchMarketplace()
}

const openPurchase = (course: any) => {
  selectedCourse.value = course
  showPurchaseModal.value = true
}

// ดูรายละเอียด Master Copy บนหน้า course จริง (แยกจากปุ่มซื้อ)
const viewCourse = (course: any) => {
  navigateTo(`/Learn/Courses/${course.id}`)
}

const purchaseBanner = ref<{ type: 'success' | 'queued'; text: string } | null>(null)

const onPurchaseSuccess = (result: any) => {
  // optimistic: ทำเครื่องหมายว่าโรงเรียนมีคอร์สนี้แล้ว ให้การ์ดในตลาดอัปเดตทันที
  if (selectedCourse.value) {
    const card = marketCourses.value.find((c) => c.id === selectedCourse.value.id)
    if (card) card.owned_by_academy = true
  }

  if (result.is_queued) {
    // คอร์สใหญ่ → clone แบบ async ยังไม่ขึ้นในคลังทันที
    purchaseBanner.value = {
      type: 'queued',
      text: 'ระบบกำลังคัดลอกรายวิชาเข้าคลังโรงเรียน จะแจ้งเตือนเมื่อเสร็จสิ้น แล้วรายวิชาจะปรากฏในคลัง',
    }
  } else {
    purchaseBanner.value = { type: 'success', text: 'เพิ่มรายวิชาเข้าคลังโรงเรียนแล้ว' }
    activeTab.value = 'academy'
    fetchCourses()
  }
}

const getStatusBadge = (status: string | number) => {
  const s = String(status)
  const badges: Record<string, string> = {
    '1': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    'published': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    '0': 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    'draft': 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    'archived': 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
  }
  return badges[s] || badges.draft
}

const getStatusLabel = (status: string | number) => {
  const s = String(status)
  const labels: Record<string, string> = {
    '1': 'เผยแพร่',
    'published': 'เผยแพร่',
    '0': 'ฉบับร่าง',
    'draft': 'ฉบับร่าง',
    'archived': 'เก็บถาวร',
  }
  return labels[s] || 'ไม่ทราบ'
}

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academyId.value = response.academy.id
      await fetchCourses()
    }
  } catch (err: any) {
    console.error('Failed to load academy:', err)
    isLoading.value = false
  }
})

watch(activeTab, (newTab) => {
  if (newTab === 'marketplace' && marketCourses.value.length === 0) {
    fetchMarketplace()
  }
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">จัดการรายวิชา</h1>
        <p class="mt-1 text-gray-500 dark:text-gray-400">
          บริหารจัดการรายวิชาในคลังของสถาบันและเลือกซื้อ Master Copy เพิ่มเติม
        </p>
      </div>
      <div class="flex flex-col gap-3 sm:flex-row">
        <button
          @click="activeTab = 'marketplace'"
          class="inline-flex items-center justify-center gap-2 rounded-xl bg-violet-100 px-4 py-2 text-violet-700 transition-colors hover:bg-violet-200 dark:bg-violet-900/30 dark:text-violet-200 dark:hover:bg-violet-900/50"
        >
          <Icon icon="mdi:store-search-outline" class="h-5 w-5" />
          ไปตลาดรายวิชา
        </button>
        <NuxtLink
          :to="`/academies/${academyName}/admin/courses/create`"
          class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-4 py-2 text-white transition-colors hover:bg-primary-700"
        >
          <Icon icon="fluent:add-24-regular" class="h-5 w-5" />
          สร้างรายวิชาใหม่
        </NuxtLink>
      </div>
    </div>

    <!-- Purchase result banner -->
    <div
      v-if="purchaseBanner"
      :class="[
        'flex items-start gap-3 rounded-2xl border p-4',
        purchaseBanner.type === 'queued'
          ? 'border-blue-100 bg-blue-50 text-blue-800 dark:border-blue-900/40 dark:bg-blue-900/10 dark:text-blue-200'
          : 'border-green-100 bg-green-50 text-green-800 dark:border-green-900/40 dark:bg-green-900/10 dark:text-green-200',
      ]"
    >
      <Icon :icon="purchaseBanner.type === 'queued' ? 'mdi:clock-fast' : 'mdi:check-circle'" class="mt-0.5 h-5 w-5 flex-shrink-0" />
      <p class="flex-1 text-sm font-medium">{{ purchaseBanner.text }}</p>
      <button @click="purchaseBanner = null" class="opacity-60 transition-opacity hover:opacity-100" title="ปิด">
        <Icon icon="fluent:dismiss-24-regular" class="h-5 w-5" />
      </button>
    </div>

    <!-- Tabs Navigation -->
    <div class="flex border-b border-gray-100 dark:border-gray-700">
      <button
        @click="activeTab = 'academy'"
        :class="[
          'relative px-6 py-3 text-sm font-bold transition-all',
          activeTab === 'academy' ? 'text-primary-600' : 'text-gray-400 hover:text-gray-600'
        ]"
      >
        <span>คลังรายวิชาโรงเรียน</span>
        <span v-if="totalCourses > 0" class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-[10px] dark:bg-gray-800">{{ totalCourses }}</span>
        <div v-if="activeTab === 'academy'" class="absolute bottom-0 left-0 h-0.5 w-full bg-primary-600"></div>
      </button>
      <button
        @click="activeTab = 'marketplace'"
        :class="[
          'relative px-6 py-3 text-sm font-bold transition-all',
          activeTab === 'marketplace' ? 'text-violet-600' : 'text-gray-400 hover:text-gray-600'
        ]"
      >
        <span>ตลาดรายวิชา Master Copy</span>
        <div v-if="activeTab === 'marketplace'" class="absolute bottom-0 left-0 h-0.5 w-full bg-violet-600"></div>
      </button>
    </div>

    <!-- Academy Courses Tab -->
    <div v-if="activeTab === 'academy'" class="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
      <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-col gap-4 sm:flex-row">
          <div class="relative flex-1">
            <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="ค้นหารายวิชาในคลัง..."
              class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pl-10 pr-4 text-gray-800 placeholder-gray-400 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              @keyup.enter="handleSearch"
            />
          </div>

          <select
            v-model="selectedStatus"
            class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-gray-800 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            @change="handleSearch"
          >
            <option v-for="status in statuses" :key="status.value" :value="status.value">
              {{ status.label }}
            </option>
          </select>

          <button
            @click="handleSearch"
            class="rounded-xl bg-primary-600 px-4 py-2.5 text-white transition-colors hover:bg-primary-700"
          >
            ค้นหา
          </button>
        </div>
      </div>

      <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div v-if="isLoading" class="p-8 text-center">
          <Icon icon="fluent:spinner-ios-20-regular" class="mx-auto h-8 w-8 animate-spin text-primary-600" />
          <p class="mt-2 text-gray-500">กำลังโหลดข้อมูล...</p>
        </div>

        <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
          <div
            v-for="course in courses"
            :key="course.id"
            class="p-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/30"
          >
            <div class="flex flex-col gap-4 sm:flex-row">
              <div class="h-24 w-full flex-shrink-0 overflow-hidden rounded-xl bg-gradient-to-br from-primary-100 to-purple-100 dark:from-primary-900/30 dark:to-purple-900/30 sm:w-40">
                <img
                  v-if="course.cover_url || course.cover_image"
                  :src="course.cover_url || course.cover_image"
                  :alt="course.title"
                  class="h-full w-full object-cover"
                />
                <div v-else class="flex h-full w-full items-center justify-center">
                  <Icon icon="fluent:hat-graduation-24-regular" class="h-10 w-10 text-primary-400 dark:text-primary-500" />
                </div>
              </div>

              <div class="min-w-0 flex-1">
                <div class="flex items-start justify-between gap-4">
                  <div>
                    <h3 class="line-clamp-1 font-semibold text-gray-800 dark:text-white">
                      {{ course.title || course.name }}
                    </h3>
                    <p class="mt-1 line-clamp-2 text-sm text-gray-500 dark:text-gray-400">
                      {{ course.description }}
                    </p>
                  </div>
                  <span :class="[getStatusBadge(course.status), 'whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-medium']">
                    {{ getStatusLabel(course.status) }}
                  </span>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                  <div class="flex items-center gap-1">
                    <Icon icon="fluent:person-24-regular" class="h-4 w-4" />
                    <span>{{ course.user?.name || course.instructor?.name || 'ไม่ระบุ' }}</span>
                  </div>
                  <div class="flex items-center gap-1">
                    <Icon icon="fluent:people-24-regular" class="h-4 w-4" />
                    <span>{{ course.members_count || course.enrolled_students || 0 }} ผู้เรียน</span>
                  </div>
                  <div v-if="course.source_course_id" class="flex items-center gap-1 text-violet-600 dark:text-violet-400">
                    <Icon icon="mdi:content-copy" class="h-4 w-4" />
                    <span>Master Copy</span>
                  </div>
                </div>
              </div>

              <div class="flex items-center gap-2 sm:flex-col sm:justify-center">
                <NuxtLink
                  :to="`/courses/${course.name || course.slug}`"
                  class="rounded-lg p-2 text-gray-500 transition-colors hover:bg-primary-50 hover:text-primary-600 dark:hover:bg-primary-900/30"
                  title="ดูรายวิชา"
                >
                  <Icon icon="fluent:eye-24-regular" class="h-5 w-5" />
                </NuxtLink>
                <NuxtLink
                  :to="`/academies/${academyName}/admin/courses/${course.id}/edit`"
                  class="rounded-lg p-2 text-gray-500 transition-colors hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-blue-900/30"
                  title="แก้ไข"
                >
                  <Icon icon="fluent:edit-24-regular" class="h-5 w-5" />
                </NuxtLink>
                <button
                  class="rounded-lg p-2 text-gray-500 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/30"
                  title="ลบ"
                >
                  <Icon icon="fluent:delete-24-regular" class="h-5 w-5" />
                </button>
              </div>
            </div>
          </div>

          <div v-if="courses.length === 0 && !isLoading" class="p-8 text-center">
            <Icon icon="fluent:hat-graduation-24-regular" class="mx-auto h-12 w-12 text-gray-300" />
            <p class="mt-2 text-gray-500">ไม่พบรายวิชาในคลัง</p>
            <div class="mt-4 flex flex-col items-center justify-center gap-3 sm:flex-row">
              <button
                @click="activeTab = 'marketplace'"
                class="inline-flex items-center gap-2 rounded-xl bg-violet-100 px-4 py-2 text-violet-700 transition-colors hover:bg-violet-200 dark:bg-violet-900/30 dark:text-violet-200 dark:hover:bg-violet-900/50"
              >
                <Icon icon="mdi:store-search-outline" class="h-5 w-5" />
                ไปตลาดรายวิชา
              </button>
              <NuxtLink
                :to="`/academies/${academyName}/admin/courses/create`"
                class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2 text-white transition-colors hover:bg-primary-700"
              >
                <Icon icon="fluent:add-24-regular" class="h-5 w-5" />
                สร้างรายวิชาใหม่
              </NuxtLink>
            </div>
          </div>

          <div v-if="hasMorePages" class="flex justify-center border-t border-gray-100 p-4 dark:border-gray-700">
            <button
              @click="loadMore"
              :disabled="isLoadingMore"
              class="flex items-center gap-2 rounded-xl bg-primary-50 px-6 py-2.5 font-medium text-primary-600 transition-colors disabled:opacity-50 hover:bg-primary-100 dark:bg-primary-900/30 dark:text-primary-400 dark:hover:bg-primary-900/50"
            >
              <Icon v-if="isLoadingMore" icon="fluent:spinner-ios-20-regular" class="h-5 w-5 animate-spin" />
              <Icon v-else icon="fluent:arrow-download-24-regular" class="h-5 w-5" />
              <span v-if="isLoadingMore">กำลังโหลด...</span>
              <span v-else>โหลดเพิ่มเติม ({{ courses.length }}/{{ totalCourses }})</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Marketplace Tab -->
    <div v-if="activeTab === 'marketplace'" class="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
      <div class="rounded-2xl border border-violet-100 bg-violet-50/80 p-5 text-violet-900 dark:border-violet-900/40 dark:bg-violet-900/10 dark:text-violet-100">
        <div class="flex items-start gap-4">
          <div class="rounded-xl bg-violet-600 p-2 text-white shadow-lg shadow-violet-500/30">
            <Icon icon="mdi:content-copy" class="h-6 w-6" />
          </div>
          <div>
            <h3 class="text-lg font-bold">ซื้อรายวิชา Master Copy เข้าคลังโรงเรียน</h3>
            <p class="mt-1 text-sm text-violet-700 dark:text-violet-200">
              เมื่อซื้อแล้ว ระบบจะทำการคัดลอกเนื้อหาทั้งหมด (บทเรียน, การบ้าน, ข้อสอบ) เข้ามาเป็นรายวิชาใหม่ของโรงเรียนคุณทันที เพื่อให้คุณนำไปปรับปรุงและเปิดสอนต่อได้
            </p>
          </div>
        </div>
      </div>

      <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="relative">
          <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
          <input
            v-model="marketSearchQuery"
            type="text"
            placeholder="ค้นหารายวิชาในตลาด..."
            class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pl-10 pr-4 text-gray-800 placeholder-gray-400 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-violet-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            @keyup.enter="handleMarketSearch"
          />
        </div>
      </div>

      <div v-if="isMarketLoading" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        <CourseMarketCardSkeleton v-for="i in 8" :key="i" />
      </div>

      <div v-else-if="marketCourses.length > 0">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          <CourseMarketCard
            v-for="course in marketCourses"
            :key="course.id"
            :course="course"
            embedded
            academy-admin-mode
            @purchase="openPurchase"
            @view="viewCourse"
          />
        </div>

        <div v-if="marketHasMorePages" class="mt-8 flex justify-center">
          <button
            @click="loadMoreMarket"
            :disabled="isMarketLoadingMore"
            class="flex items-center gap-2 rounded-xl bg-violet-50 px-8 py-3 font-bold text-violet-600 transition-all hover:bg-violet-100 dark:bg-violet-900/30 dark:text-violet-200"
          >
            <Icon v-if="isMarketLoadingMore" icon="fluent:spinner-ios-20-regular" class="h-5 w-5 animate-spin" />
            <Icon v-else icon="fluent:arrow-download-24-regular" class="h-5 w-5" />
            <span>{{ isMarketLoadingMore ? 'กำลังโหลด...' : 'ดูเพิ่มเติม' }}</span>
          </button>
        </div>
      </div>

      <div v-else class="rounded-2xl border border-dashed border-gray-200 p-12 text-center dark:border-gray-700">
        <Icon icon="mdi:store-off-outline" class="mx-auto h-16 w-16 text-gray-300" />
        <h3 class="mt-4 text-lg font-bold text-gray-800 dark:text-white">ไม่พบรายวิชาในตลาด</h3>
        <p class="mt-1 text-gray-500">ลองเปลี่ยนคำค้นหาหรือดูหมวดหมู่แกนกลางอื่น</p>
      </div>
    </div>

    <!-- Purchase Modal -->
    <CoursePurchaseModal
      v-if="selectedCourse"
      :visible="showPurchaseModal"
      :course="selectedCourse"
      :academy-id="academyId"
      @close="showPurchaseModal = false"
      @success="onPurchaseSuccess"
    />
  </div>
</template>
