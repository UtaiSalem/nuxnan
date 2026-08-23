<script setup lang="ts">
definePageMeta({ layout: 'main' })

const route = useRoute()
const router = useRouter()
const academyId = inject<Ref<number | null>>('academyId', ref(null))
const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)
const { getElection, getTurnout, transitionStatus, deleteElection } = useElections()
const showEdit = ref(false)
const electionId = computed(() => Number(route.params.id))
const election = ref<any>(null)
const turnout = ref<any>(null)
const loading = ref(true)
const error = ref('')
const tab = computed({
  get: () => String(route.query.tab || 'overview'),
  set: (value) => router.push({ query: { ...route.query, tab: value } }),
})
const tabs = [
  { key: 'overview', label: 'ภาพรวม' },
  { key: 'parties', label: 'พรรค' },
  { key: 'voters', label: 'บัญชีผู้มีสิทธิ์' },
  { key: 'stations', label: 'หน่วย' },
  { key: 'results', label: 'ผล' },
  { key: 'audit', label: 'บันทึก' },
]
const canManage = computed(() => isAdmin.value || can('elections.manage'))
const load = async () => {
  if (!academyId.value) return
  loading.value = true
  try {
    const response: any = await getElection(academyId.value, electionId.value)
    election.value = response?.data
    const turnoutResponse: any = await getTurnout(academyId.value, electionId.value)
    turnout.value = turnoutResponse?.data
  } catch (err: any) {
    error.value = err?.data?.message || 'ไม่สามารถโหลดข้อมูลได้'
  } finally {
    loading.value = false
  }
}
const transition = async (status: string) => {
  await transitionStatus(academyId.value!, electionId.value, status)
  await load()
}
const remove = async () => {
  if (confirm('ยืนยันการลบการเลือกตั้งหรือไม่')) {
    await deleteElection(academyId.value!, electionId.value)
    await navigateTo(`/academies/${route.params.name}/admin/elections`)
  }
}
const edit = () => {
  showEdit.value = true
}
watch(
  academyId,
  async (value) => {
    if (value) {
      await fetchMyRole()
      if (!(isAdmin.value || can('elections.view') || can('elections.manage'))) {
        await navigateTo(`/academies/${route.params.name}`)
        return
      }
      await load()
    }
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
    <p v-else-if="error" class="rounded-lg bg-red-50 p-4 text-red-700">{{ error }}</p>
    <template v-else-if="election">
      <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
          <h1 class="break-words text-xl font-bold text-gray-900 dark:text-white">
            {{ election.title }}
          </h1>
          <p class="text-sm text-gray-500">
            ปีการศึกษา {{ election.academic_year?.name || '-' }} · {{ election.status }}
          </p>
        </div>
      </div>
      <div class="overflow-x-auto border-b border-gray-200 dark:border-gray-700">
        <nav class="flex min-w-max gap-1" aria-label="แท็บการเลือกตั้ง">
          <button
            v-for="item in tabs"
            :key="item.key"
            class="min-h-[44px] flex-shrink-0 whitespace-nowrap rounded-t-lg px-4 py-3 text-sm"
            :class="
              tab === item.key
                ? 'border-b-2 border-primary-600 text-primary-600'
                : 'text-gray-500 hover:bg-gray-50'
            "
            @click="tab = item.key"
          >
            {{ item.label }}
          </button>
        </nav>
      </div>
      <ElectionOverviewTab
        v-if="tab === 'overview'"
        :election="election"
        :turnout="turnout"
        :can-manage="canManage"
        @transition="transition"
        @remove="remove"
        @edit="edit"
      />
      <ElectionPartiesTab
        v-else-if="tab === 'parties'"
        :academy-id="academyId!"
        :election-id="electionId"
        :can-manage="canManage"
        :status="election.status"
      />
      <ElectionVoterRollTab
        v-else-if="tab === 'voters'"
        :academy-id="academyId!"
        :election-id="electionId"
        :can-manage="canManage"
        :locked-at="election.voter_roll_locked_at"
      />
      <ElectionStationsTab
        v-else-if="tab === 'stations'"
        :academy-id="academyId!"
        :election-id="electionId"
        :academy-name="String(route.params.name)"
        :can-manage="canManage"
        :status="election.status"
      />
      <ElectionResultsTab
        v-else-if="tab === 'results'"
        :academy-id="academyId!"
        :election-id="electionId"
        :election="election"
        :can-manage="canManage"
      />
      <ElectionAuditTab
        v-else-if="tab === 'audit'"
        :academy-id="academyId!"
        :election-id="electionId"
      />
    </template>
    <ElectionFormModal
      :open="showEdit"
      :academy-id="academyId || 0"
      :election="election"
      @close="showEdit = false"
      @saved="showEdit = false; load()"
    />
  </div>
</template>
