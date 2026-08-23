<script setup lang="ts">
const props = defineProps<{
  academyId: number
  electionId: number
  canManage: boolean
  status: string
}>()
const { listParties, approveParty, rejectParty, withdrawParty } = useElections()
const parties = ref<any[]>([])
const loading = ref(false)
const notes = reactive<Record<number, string>>({})
const numbers = reactive<Record<number, string>>({})
const errors = reactive<Record<number, string>>({})
const load = async () => {
  loading.value = true
  const response: any = await listParties(props.academyId, props.electionId)
  parties.value = response?.data || []
  loading.value = false
}
const approve = async (party: any) => {
  errors[party.id] = ''
  try {
    await approveParty(props.academyId, props.electionId, party.id, numbers[party.id]?.trim() ? Number(numbers[party.id]) : null)
    await load()
  } catch (error: any) { errors[party.id] = error?.data?.message || error?.message || 'ไม่สามารถอนุมัติพรรคได้' }
}
const reject = async (party: any) => {
  if (!notes[party.id]?.trim()) return
  await rejectParty(props.academyId, props.electionId, party.id, notes[party.id])
  notes[party.id] = ''
  await load()
}
const withdraw = async (party: any) => {
  if (confirm('ยืนยันการถอนตัวของพรรคนี้หรือไม่')) {
    await withdrawParty(props.academyId, props.electionId, party.id)
    await load()
  }
}
watch(
  () => [props.academyId, props.electionId],
  () => {
    if (props.academyId) load()
  },
  { immediate: true }
)
</script>
<template>
  <div class="space-y-4">
    <div
      v-if="!parties.length"
      class="rounded-xl border border-dashed p-8 text-center text-gray-500"
    >
      ยังไม่มีพรรค
    </div>
    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
      <table class="min-w-[760px] w-full text-left text-sm">
        <thead class="bg-gray-50 dark:bg-gray-700">
          <tr>
            <th class="p-3">เบอร์</th>
            <th class="p-3">ชื่อพรรค</th>
            <th class="p-3">สมาชิก</th>
            <th class="p-3">สถานะ</th>
            <th class="p-3">การจัดการ</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="party in parties" :key="party.id" class="border-t dark:border-gray-700">
            <td class="p-3">{{ party.number ?? 'ว่าง' }}</td>
            <td class="p-3">{{ party.name }}</td>
            <td class="p-3">
              {{
                party.members
                  ?.map((member: any) => `${member.user?.name || '-'} (${member.role})`)
                  .join(', ') || '-'
              }}
            </td>
            <td class="p-3">{{ party.status }}</td>
            <td v-if="party.status === 'rejected'" class="p-3 text-sm text-red-700">
              <p>{{ party.review_note || '-' }}</p><p>{{ party.reviewed_at || '-' }}</p>
            </td>
            <td class="space-y-2 p-3">
              <div class="flex gap-2">
                <button
                  :disabled="
                    !canManage ||
                    !['nomination', 'campaign'].includes(status) ||
                    party.status !== 'pending'
                  "
                  class="min-h-[44px] rounded-lg bg-emerald-600 px-3 text-white disabled:opacity-50"
                  @click="approve(party)"
                >
                  อนุมัติ</button
                ><button
                  :disabled="
                    !canManage ||
                    !['nomination', 'campaign'].includes(status) ||
                    party.status !== 'pending'
                  "
                  class="min-h-[44px] rounded-lg bg-red-600 px-3 text-white disabled:opacity-50"
                  @click="reject(party)"
                >
                  ปฏิเสธ</button
                ><button
                  :disabled="!canManage || party.status !== 'approved'"
                  class="min-h-[44px] rounded-lg border px-3 disabled:opacity-50"
                  @click="withdraw(party)"
                >
                  ถอนตัว
                </button>
                <input v-if="party.status === 'pending'" v-model="numbers[party.id]" inputmode="numeric" class="min-h-[44px] w-24 rounded-lg border p-2" placeholder="เบอร์ (ว่างได้)" />
              </div>
              <input
                v-if="party.status === 'pending'"
                v-model="notes[party.id]"
                class="min-h-[44px] w-full rounded-lg border p-2"
                placeholder="เหตุผลปฏิเสธ (บังคับ)"
              />
              <p v-if="errors[party.id]" class="text-sm text-red-600">{{ errors[party.id] }}</p>
              <p v-if="party.status === 'rejected'" class="text-sm text-red-700">เหตุผล: {{ party.review_note || '-' }} · {{ party.reviewed_at || '-' }}</p>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
