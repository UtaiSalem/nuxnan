<script setup lang="ts">
import { Icon } from '@iconify/vue'
definePageMeta({ layout: 'main' })
const route = useRoute()
const api = useApi()
const { listElections } = useElections()
const academyName = computed(() => route.params.name as string)
const academyId = inject<Ref<number | null>>('academyId', ref(null))
const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)
const loading = ref(true)
const error = ref('')
const elections = ref<any[]>([])
const total = ref(0)
const page = ref(1)
const showCreate = ref(false)
const selectedStatus = ref('')
const statuses = [
  { value: '', label: 'ทั้งหมด' },
  { value: 'draft', label: 'ร่าง' },
  { value: 'nomination', label: 'รับสมัคร' },
  { value: 'campaign', label: 'หาเสียง' },
  { value: 'voting', label: 'ลงคะแนน' },
  { value: 'closed', label: 'ปิดหีบ' },
  { value: 'published', label: 'ประกาศผลแล้ว' },
  { value: 'cancelled', label: 'ยกเลิก' },
]
const statusClass = (s: string) =>
  ({
    draft: 'bg-gray-100 text-gray-700',
    nomination: 'bg-blue-100 text-blue-700',
    campaign: 'bg-purple-100 text-purple-700',
    voting: 'bg-emerald-100 text-emerald-700',
    closed: 'bg-amber-100 text-amber-700',
    published: 'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-700',
  }[s] || 'bg-gray-100 text-gray-700')
const statusLabel = (s: string) => statuses.find((x) => x.value === s)?.label || s
const levelLabel = (v: any) =>
  v === null || v === undefined ? 'ทั้งโรงเรียน' : Number(v) === 1 ? 'ประถม' : 'มัธยม'
const load = async () => {
  if (!academyId.value) return
  loading.value = true
  error.value = ''
  try {
    const data: any = await listElections(academyId.value, {
      status: selectedStatus.value || undefined,
      page: page.value,
      per_page: 10,
    })
    elections.value = data?.data?.data || []
    total.value = data?.data?.total || 0
  } catch (e: any) {
    error.value = e?.data?.message || 'ไม่สามารถโหลดข้อมูลได้'
  } finally {
    loading.value = false
  }
}
const initialize = async () => {
  await fetchMyRole()
  if (!(isAdmin.value || can('elections.view') || can('elections.manage'))) {
    await navigateTo(`/academies/${academyName.value}`)
    return
  }
  await load()
}
watch([academyId, selectedStatus], () => {
  if (academyId.value) {
    page.value = 1
    load()
  }
})
watch(page, load)
watch(
  academyId,
  (value) => {
    if (value) initialize()
  },
  { immediate: true }
)
</script>
<template>
  <div class="space-y-5">
    <div v-if="loading" class="flex justify-center py-16">
      <div
        class="h-10 w-10 animate-spin rounded-full border-4 border-primary-500 border-t-transparent"
      />
    </div>
    <div v-else class="space-y-5">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
          <h1 class="break-words text-xl font-bold text-gray-900 dark:text-white">
            การเลือกตั้งสภานักเรียน
          </h1>
          <p class="text-sm text-gray-500">จัดการการเลือกตั้งของโรงเรียน</p>
        </div>
        <button
          v-if="can('elections.manage')"
          class="min-h-[44px] flex-shrink-0 rounded-lg bg-primary-600 px-4 py-2 font-medium text-white"
          @click="showCreate = true"
        >
          สร้างการเลือกตั้ง
        </button>
      </div>
      <div class="overflow-x-auto">
        <div class="flex min-w-max gap-2 pb-1">
          <button
            v-for="status in statuses"
            :key="status.value"
            class="min-h-[44px] rounded-lg px-3 py-2 text-sm"
            :class="
              selectedStatus === status.value
                ? 'bg-primary-600 text-white'
                : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200'
            "
            @click="selectedStatus = status.value"
          >
            {{ status.label }}
          </button>
        </div>
      </div>
      <p v-if="error" class="rounded-lg bg-red-50 p-3 text-red-700">{{ error }}</p>
      <div
        v-else-if="!elections.length"
        class="rounded-xl border border-dashed border-gray-300 p-8 text-center"
      >
        <Icon icon="fluent:vote-24-regular" class="mx-auto h-12 w-12 text-gray-400" />
        <p class="mt-3 text-gray-600 dark:text-gray-300">ยังไม่มีการเลือกตั้ง</p>
        <button
          v-if="can('elections.manage')"
          class="mt-4 min-h-[44px] rounded-lg bg-primary-600 px-4 py-2 text-white"
          @click="showCreate = true"
        >
          สร้างการเลือกตั้ง
        </button>
      </div>
      <div v-else class="space-y-3">
        <NuxtLink
          v-for="election in elections"
          :key="election.id"
          :to="`/academies/${academyName}/admin/elections/${election.id}`"
          class="block rounded-xl border border-gray-200 p-4 transition hover:border-primary-400 dark:border-gray-700"
          ><div class="flex flex-col gap-3 sm:flex-row sm:items-start">
            <div class="min-w-0 flex-1 break-words">
              <h2 class="font-semibold text-gray-900 dark:text-white">
                {{ election.name || election.title }}
              </h2>
              <div class="mt-2 flex flex-wrap gap-2 text-sm text-gray-500">
                <span>{{
                  election.academic_year?.name ||
                  election.academic_year_name ||
                  election.academic_year ||
                  '-'
                }}</span
                ><span>· {{ levelLabel(election.education_level) }}</span>
              </div>
            </div>
            <span
              class="flex-shrink-0 whitespace-nowrap rounded-full px-3 py-1 text-xs font-medium"
              :class="statusClass(election.status)"
              >{{ statusLabel(election.status) }}</span
            >
          </div>
          <div
            class="mt-4 grid grid-cols-1 gap-2 text-sm text-gray-600 sm:grid-cols-3 dark:text-gray-300"
          >
            <span>ผู้มีสิทธิ์: {{ election.voters_count ?? 0 }}</span
            ><span>พรรคอนุมัติ: {{ election.approved_parties_count ?? 0 }}</span
            ><span>ลงคะแนนแล้ว: {{ election.receipts_cast_count ?? 0 }}</span>
          </div></NuxtLink
        >
      </div>
      <div v-if="total > 10" class="flex items-center justify-between">
        <button
          :disabled="page <= 1"
          class="min-h-[44px] rounded-lg border px-4 disabled:opacity-50"
          @click="page--"
        >
          ก่อนหน้า</button
        ><span class="text-sm text-gray-500">หน้า {{ page }}</span
        ><button
          :disabled="page * 10 >= total"
          class="min-h-[44px] rounded-lg border px-4 disabled:opacity-50"
          @click="page++"
        >
          ถัดไป
        </button>
      </div>
    </div>
    <ElectionFormModal
      :open="showCreate"
      :academy-id="academyId || 0"
      @close="showCreate = false"
      @saved="load"
    />
  </div>
</template>
