<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { computed, inject, onMounted, ref, watch, type Ref } from 'vue'
import Swal from 'sweetalert2'
import { useAcademyAllocations, type AcademyAllocation } from '~/composables/useAcademyAllocations'
import { useAcademyRevenue } from '~/composables/useAcademyRevenue'
import AllocationBalanceCard from '~/components/academy/allocations/AllocationBalanceCard.vue'
import AllocationForm from '~/components/academy/allocations/AllocationForm.vue'
import AllocationHistory from '~/components/academy/allocations/AllocationHistory.vue'
import AllocationCourseFilters from '~/components/academy/allocations/AllocationCourseFilters.vue'
import type { AvailableFilters, CourseFilters } from '~/types/allocation'

definePageMeta({ layout: 'main', middleware: 'auth' })

const route = useRoute()
const api = useApi()
const { allocateToCourse, fetchAllocations } = useAcademyAllocations()

// admin.vue (parent) provides the resolved academy id — fall back to resolving by name
const injectedAcademyId = inject<Ref<number | null>>('academyId', ref(null))
const academyId = ref<number | null>(injectedAcademyId?.value ?? null)

const { supportSummary, fetchSupportSummary } = useAcademyRevenue(academyId)

const courses = ref<any[]>([])
const availableFilters = ref<AvailableFilters | null>(null)
const courseFilters = ref<CourseFilters>({
  education_level: '',
  education_year: '',
  semester: '',
  academic_year: '',
  search: '',
})
// ครั้งแรกให้ backend เลือกเทอมปัจจุบันให้ แล้วค่อยยึดค่าที่ผู้ใช้ตั้งเอง
const filtersInitialized = ref(false)

const rows = ref<AcademyAllocation[]>([])
const meta = ref<any>({})
const pendingAmount = ref(0)

const isLoadingCourses = ref(true)
const isLoadingHistory = ref(true)
const isLoadingBalance = ref(true)
const isSubmitting = ref(false)

const formRef = ref<InstanceType<typeof AllocationForm> | null>(null)

const academyName = computed(() => route.params.name as string)

const availablePoints = computed(() => supportSummary.value?.available_point_balance ?? 0)
const pointBalance = computed(() => supportSummary.value?.point_balance ?? 0)
const totalDistributed = computed(() => supportSummary.value?.total_distributed ?? 0)

const loadHistory = async (page = 1) => {
  if (!academyId.value) return
  isLoadingHistory.value = true
  try {
    const res: any = await fetchAllocations(academyId.value, { page, per_page: 10 })
    rows.value = res?.data || []
    meta.value = res?.meta || {}
  } catch (err) {
    console.error('Failed to load allocations:', err)
  } finally {
    isLoadingHistory.value = false
  }
}

const loadCourses = async () => {
  if (!academyId.value) return
  isLoadingCourses.value = true
  try {
    const params = new URLSearchParams({ per_page: '100' })

    // ครั้งแรกปล่อยให้ backend เติมเทอมปัจจุบันให้เอง
    if (!filtersInitialized.value) params.set('use_current_term', '1')

    const f = courseFilters.value
    if (f.education_level) params.set('education_level', f.education_level)
    if (f.education_year) params.set('education_year', f.education_year)
    if (f.semester) params.set('semester', f.semester)
    if (f.academic_year) params.set('academic_year', f.academic_year)
    if (f.search.trim()) params.set('search', f.search.trim())

    const res: any = await api.get(`/api/academies/${academyId.value}/courses?${params.toString()}`)
    courses.value = res?.courses?.data || res?.courses || res?.data || []
    availableFilters.value = res?.available_filters || availableFilters.value

    // รับค่าเทอมปัจจุบันที่ backend เลือกให้ มาเป็นสถานะเริ่มต้นของตัวกรอง
    if (!filtersInitialized.value) {
      const term = availableFilters.value?.current_term
      if (term?.academic_year) courseFilters.value.academic_year = term.academic_year
      if (term?.semester) courseFilters.value.semester = term.semester
      filtersInitialized.value = true
    }
  } catch (err) {
    console.error('Failed to load courses:', err)
  } finally {
    isLoadingCourses.value = false
  }
}

const loadBalance = async () => {
  isLoadingBalance.value = true
  try {
    await fetchSupportSummary()
  } finally {
    isLoadingBalance.value = false
  }
}

const resolveAcademyId = async () => {
  if (academyId.value) return
  const res: any = await api.get(`/api/academies/${academyName.value}`)
  academyId.value = res?.academy?.id || res?.data?.id || res?.id || null
}

const handleSubmit = async (payload: { course_id: number; amount: number; purpose?: string }) => {
  if (!academyId.value || isSubmitting.value) return

  isSubmitting.value = true
  try {
    await allocateToCourse(academyId.value, payload)

    const courseName = courses.value.find(c => c.id === payload.course_id)?.name || 'คอร์สเรียน'
    await Swal.fire({
      icon: 'success',
      title: 'โอนแต้มสำเร็จ',
      text: `โอน ${payload.amount.toLocaleString('th-TH')} แต้ม ไปยัง ${courseName} แล้ว`,
      timer: 2400,
      showConfirmButton: false,
    })

    formRef.value?.reset()
    pendingAmount.value = 0
    await Promise.all([loadBalance(), loadHistory(1)])
  } catch (err: any) {
    Swal.fire({
      icon: 'error',
      title: 'โอนแต้มไม่สำเร็จ',
      text: err?.data?.message || 'เกิดข้อผิดพลาดในการโอนแต้ม กรุณาลองใหม่อีกครั้ง',
    })
  } finally {
    isSubmitting.value = false
  }
}

// Parent resolves the academy asynchronously; pick it up as soon as it lands
watch(injectedAcademyId, (id) => {
  if (id && !academyId.value) academyId.value = id
})

onMounted(async () => {
  await resolveAcademyId()
  if (!academyId.value) {
    isLoadingCourses.value = false
    isLoadingHistory.value = false
    isLoadingBalance.value = false
    return
  }
  await Promise.all([loadCourses(), loadHistory(), loadBalance()])
})
</script>

<template>
  <div class="space-y-6 px-4 sm:px-0">
    <!-- หัวข้อหน้า -->
    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-200 pb-5 dark:border-gray-700">
      <div class="flex items-start gap-3">
        <span class="rounded-xl bg-gradient-to-br from-indigo-500 to-violet-500 p-2.5 text-white shadow-sm">
          <Icon icon="fluent:arrow-swap-24-regular" class="h-6 w-6" />
        </span>
        <div>
          <h1 class="text-xl font-bold text-gray-900 sm:text-2xl dark:text-white">จัดสรรแต้มให้คอร์สเรียน</h1>
          <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
            โอนแต้มจากกระเป๋าโรงเรียนไปยังคอร์สเรียน เพื่อให้ครูนำไปใช้เป็นทุน รางวัล หรือสนับสนุนกิจกรรมในคอร์สนั้น
          </p>
        </div>
      </div>
      <NuxtLink
        :to="`/academies/${academyName}/admin/revenue`"
        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-600 transition hover:border-indigo-300 hover:text-indigo-600 dark:border-gray-700 dark:text-gray-300"
      >
        <Icon icon="fluent:money-24-regular" class="h-4 w-4" />
        ดูรายได้โรงเรียน
      </NuxtLink>
    </header>

    <!-- ยอดแต้มคงเหลือ -->
    <AllocationBalanceCard
      :available="availablePoints"
      :balance="pointBalance"
      :distributed="totalDistributed"
      :pending-amount="pendingAmount"
      :loading="isLoadingBalance"
    />

    <!-- แจ้งเตือนเมื่อไม่มีแต้ม -->
    <div
      v-if="!isLoadingBalance && availablePoints <= 0"
      class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-950/30"
    >
      <Icon icon="fluent:info-24-regular" class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-500" />
      <div class="text-sm">
        <p class="font-semibold text-amber-800 dark:text-amber-200">กระเป๋าโรงเรียนยังไม่มีแต้มที่โอนได้</p>
        <p class="mt-0.5 text-amber-700 dark:text-amber-300">
          แต้มจะเข้ากระเป๋าเมื่อมีผู้สนับสนุนบริจาคให้โรงเรียน หรือมีรายได้จากแคมเปญโฆษณา
        </p>
      </div>
    </div>

    <!-- ฟอร์ม + ประวัติ -->
    <div class="grid gap-6 xl:grid-cols-12">
      <section class="xl:col-span-7">
        <div class="rounded-2xl border border-gray-200 p-5 sm:p-6 dark:border-gray-700">
          <AllocationForm
            ref="formRef"
            :courses="courses"
            :available="availablePoints"
            :submitting="isSubmitting"
            :loading-courses="isLoadingCourses"
            @submit="handleSubmit"
            @update:amount="pendingAmount = $event"
          >
            <template #filters>
              <AllocationCourseFilters
                v-model="courseFilters"
                :available-filters="availableFilters"
                :result-count="courses.length"
                :loading="isLoadingCourses"
                @change="loadCourses"
              />
            </template>
          </AllocationForm>
        </div>
      </section>

      <section class="xl:col-span-5">
        <div class="h-full rounded-2xl border border-gray-200 p-5 sm:p-6 dark:border-gray-700">
          <AllocationHistory
            :rows="rows"
            :meta="meta"
            :loading="isLoadingHistory"
            @page="loadHistory"
          />
        </div>
      </section>
    </div>
  </div>
</template>
