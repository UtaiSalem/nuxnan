<script setup lang="ts">
const props = defineProps<{ academyId: number; electionId: number }>()
const { getAuditLog, getActivityActions } = useElections()
const rows = ref<any[]>([])
const labels = ref<Record<string, string>>({})
const page = ref(1)
const lastPage = ref(1)
const loading = ref(false)
const load = async () => {
  loading.value = true
  try {
    const response: any = await getAuditLog(props.academyId, props.electionId, page.value)
    rows.value = response?.data?.data || []
    lastPage.value = response?.data?.last_page || 1
  } finally {
    loading.value = false
  }
}
const loadLabels = async () => {
  const response: any = await getActivityActions(props.academyId)
  for (const action of response?.actions || []) labels.value[action.value] = action.label
}
watch(
  () => [props.academyId, props.electionId],
  async () => {
    await loadLabels()
    await load()
  },
  { immediate: true }
)
watch(page, load)
</script>
<template>
  <div class="space-y-4">
    <div class="space-y-3">
      <article v-for="row in rows" :key="row.id" class="rounded-xl border p-4">
        <p class="font-semibold">{{ labels[row.action] || row.action }}</p>
        <p class="text-sm text-gray-500">{{ row.user?.name || '-' }} · {{ row.created_at }}</p>
        <p v-if="row.action === 'election_ballot_issue'" class="mt-2 text-sm text-gray-600">
          มีการออกบัตรเลือกตั้ง
        </p>
      </article>
    </div>
    <p
      v-if="!loading && !rows.length"
      class="rounded-xl border border-dashed p-6 text-center text-gray-500"
    >
      ยังไม่มีบันทึก
    </p>
    <div class="flex justify-between">
      <button class="min-h-[44px] rounded-lg border px-4" :disabled="page <= 1" @click="page--">
        ก่อนหน้า</button
      ><span class="self-center text-sm">{{ page }} / {{ lastPage }}</span
      ><button
        class="min-h-[44px] rounded-lg border px-4"
        :disabled="page >= lastPage"
        @click="page++"
      >
        ถัดไป
      </button>
    </div>
  </div>
</template>
