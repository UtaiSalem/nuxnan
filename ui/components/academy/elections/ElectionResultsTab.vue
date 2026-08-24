<script setup lang="ts">
const props = defineProps<{
  academyId: number
  electionId: number
  election: any
  canManage: boolean
}>()
const route = useRoute()
const { getTurnout, closeAndCount, publishResults, getResults, formCouncil } = useElections()
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
const councilState = ref<{ status: 'success' | 'error' | null; message?: string; groupId?: number; groupName?: string }>({ status: null })
const createCouncil = async () => {
  if (confirm('ยืนยันตั้งสภานักเรียน?')) {
    councilState.value = { status: null }
    try {
      const response = (await formCouncil(props.academyId, props.electionId)) as any
      const group = response?.data || response
      councilState.value = { status: 'success', groupId: group.id, groupName: group.name }
    } catch (e: any) {
      const errData = e.data || e.response?.data || e.response?._data || e
      councilState.value = { 
        status: 'error', 
        message: errData.message || 'เกิดข้อผิดพลาด', 
        groupId: errData.group_id,
        groupName: errData.group_name
      }
    }
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
    
    <button
      v-if="election.status === 'published' && canManage"
      class="min-h-[44px] rounded-lg bg-primary-600 px-4 text-white"
      @click="createCouncil"
    >
      ตั้งสภานักเรียน
    </button>

    <div
      v-if="councilState.status === 'success'"
      class="flex flex-col gap-3 rounded-lg bg-green-50 p-3 text-green-800 sm:flex-row sm:items-center sm:justify-between sm:p-4"
    >
      <div class="min-w-0 flex-1 break-words">ตั้งสภานักเรียนสำเร็จ</div>
      <NuxtLink
        :to="`/academies/${route.params.name}/groups/${councilState.groupId}`"
        class="flex min-h-[44px] flex-shrink-0 items-center justify-center whitespace-nowrap rounded-lg bg-green-600 px-4 text-white"
      >
        ไปยังสภานักเรียน
      </NuxtLink>
    </div>
    
    <div
      v-else-if="councilState.status === 'error'"
      class="flex flex-col gap-3 rounded-lg bg-amber-50 p-3 text-amber-800 sm:flex-row sm:items-center sm:justify-between sm:p-4"
    >
      <div class="min-w-0 flex-1 break-words">{{ councilState.message }}</div>
      <NuxtLink
        v-if="councilState.groupId"
        :to="`/academies/${route.params.name}/groups/${councilState.groupId}`"
        class="flex min-h-[44px] flex-shrink-0 items-center justify-center whitespace-nowrap rounded-lg bg-amber-600 px-4 text-white"
      >
        ดูสภานักเรียน
      </NuxtLink>
    </div>

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
