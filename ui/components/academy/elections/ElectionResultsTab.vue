<script setup lang="ts">
const props = defineProps<{
  academyId: number
  electionId: number
  election: any
  canManage: boolean
}>()
const { getTurnout, closeAndCount, publishResults, getResults } = useElections()
const turnout = ref<any>(null)
const results = ref<any[]>([])
const publishedError = ref(false)
const published = ref(false)
const load = async () => {
  turnout.value = ((await getTurnout(props.academyId, props.electionId)) as any)?.data
  published.value = props.election.status === 'published'
  if (published.value) {
    try {
      results.value = ((await getResults(props.academyId, props.electionId)) as any)?.data || []
    } catch {
      publishedError.value = true
    }
  }
}
const count = async () => {
  if (confirm('ยืนยันปิดหีบและนับคะแนน?')) {
    const response = (await closeAndCount(props.academyId, props.electionId)) as any
    results.value = response?.data?.results || []
  }
}
const publish = async () => {
  if (confirm('ยืนยันประกาศผล?')) {
    await publishResults(props.academyId, props.electionId)
    published.value = true
    results.value = ((await getResults(props.academyId, props.electionId)) as any)?.data || []
  }
}
watch(() => props.election.status, load, { immediate: true })
</script>
<template>
  <div class="space-y-4">
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
      <div v-for="key in ['voted', 'total', 'percentage']" :key="key" class="rounded-xl border p-4">
        <p class="text-xs text-gray-500">{{ key }}</p>
        <p class="text-2xl font-bold">
          {{ turnout?.[key] ?? '-' }}{{ key === 'percentage' ? '%' : '' }}
        </p>
      </div>
    </div>
    <button
      v-if="election.status === 'voting' && canManage"
      class="min-h-[44px] rounded-lg bg-primary-600 px-4 text-white"
      @click="count"
    >
      ปิดหีบและนับคะแนน
    </button>
    <button
      v-if="election.status === 'closed' && canManage && !published"
      class="min-h-[44px] rounded-lg bg-primary-600 px-4 text-white"
      @click="publish"
    >
      ประกาศผล
    </button>
    <p v-if="publishedError" class="rounded-lg bg-amber-50 p-4 text-amber-800">ยังไม่มีผลประกาศ</p>
    <div
      v-if="['closed', 'published'].includes(election.status)"
      class="overflow-x-auto rounded-xl border"
    >
      <table class="min-w-[560px] w-full text-left text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="p-3">อันดับ</th>
            <th class="p-3">พรรค</th>
            <th class="p-3">คะแนน</th>
            <th class="p-3">ผล</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="result in results"
            :key="result.party_id ?? 'abstain'"
            class="border-t"
            :class="{ 'bg-gray-50': result.party_id === null }"
          >
            <td class="p-3">{{ result.rank ?? '-' }}</td>
            <td class="p-3">
              {{ result.party_id === null ? 'ไม่ประสงค์ลงคะแนน' : result.party?.name }}
            </td>
            <td class="p-3">{{ result.votes }}</td>
            <td class="p-3">
              {{ result.party_id === null ? 'ไม่จัดอันดับ' : result.is_winner ? 'ผู้ชนะ' : '' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <p v-if="election.status === 'closed' && !results.length" class="text-sm text-gray-500">
      ระบบนับคะแนนแล้ว แต่ยังไม่มีข้อมูลผลลัพธ์ให้แสดง
    </p>
  </div>
</template>
