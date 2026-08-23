<script setup lang="ts">
const props = defineProps<{ election: any; turnout: any; canManage: boolean }>()
const emit = defineEmits<{ edit: []; transition: [string]; remove: [] }>()
const states = ['draft', 'nomination', 'campaign', 'voting', 'closed', 'published']
const labels: Record<string, string> = {
  draft: 'ร่าง',
  nomination: 'รับสมัคร',
  campaign: 'หาเสียง',
  voting: 'ลงคะแนน',
  closed: 'ปิดหีบ',
  published: 'ประกาศผล',
}
const nextState = (status: string) => states[states.indexOf(status) + 1]
const transitionReason = (state: string) =>
  state === 'voting' && !props.election.voter_roll_locked_at
    ? 'ต้องล็อกบัญชีผู้มีสิทธิ์ก่อน'
    : state === 'voting' && !props.election.approved_parties_count
    ? 'ต้องมีพรรคที่อนุมัติแล้วอย่างน้อย 1 พรรค'
    : ''
</script>
<template>
  <div class="space-y-4">
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
      <div
        v-for="item in [
          { label: 'ผู้มีสิทธิ์', value: election.voters_count },
          { label: 'พรรคอนุมัติ', value: election.approved_parties_count },
          { label: 'ลงคะแนนแล้ว', value: election.receipts_cast_count },
          { label: 'Turnout', value: `${turnout?.percentage ?? 0}%` },
        ]"
        :key="item.label"
        class="rounded-xl border border-gray-200 p-4 dark:border-gray-700"
      >
        <p class="text-xs text-gray-500">{{ item.label }}</p>
        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ item.value ?? 0 }}</p>
      </div>
    </div>
    <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
      <h2 class="font-semibold text-gray-900 dark:text-white">สถานะการเลือกตั้ง</h2>
      <div class="mt-4 overflow-x-auto">
        <div class="flex min-w-max items-start gap-2">
          <div v-for="state in states" :key="state" class="flex items-center gap-2">
            <div
              class="rounded-full px-3 py-2 text-sm"
              :class="
                state === election.status
                  ? 'bg-primary-600 text-white'
                  : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'
              "
            >
              {{ labels[state] }}
            </div>
            <span v-if="state !== states.at(-1)" class="text-gray-400">→</span>
          </div>
        </div>
      </div>
      <div class="mt-4 flex flex-col gap-2 sm:flex-row">
        <button
          v-if="nextState(election.status)"
          :disabled="!canManage || !!transitionReason(nextState(election.status))"
          class="min-h-[44px] rounded-lg bg-primary-600 px-4 py-2 text-white disabled:cursor-not-allowed disabled:opacity-50"
          @click="emit('transition', nextState(election.status))"
        >
          ไปยัง{{ labels[nextState(election.status)] }}
        </button>
        <p
          v-if="transitionReason(nextState(election.status))"
          class="self-center text-sm text-amber-700"
        >
          {{ transitionReason(nextState(election.status)) }}
        </p>
      </div>
    </div>
    <div class="flex flex-wrap gap-2">
      <button
        :disabled="!canManage"
        class="min-h-[44px] rounded-lg border px-4 py-2 disabled:opacity-50"
        @click="emit('edit')"
      >
        แก้ไขข้อมูล</button
      ><button
        v-if="election.status !== 'published' && election.status !== 'cancelled'"
        :disabled="!canManage"
        class="min-h-[44px] rounded-lg border border-red-200 px-4 py-2 text-red-600 disabled:opacity-50"
        @click="emit('transition', 'cancelled')"
      >
        ยกเลิกการเลือกตั้ง
      </button>
    </div>
  </div>
</template>
