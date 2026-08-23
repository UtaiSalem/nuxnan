<script setup lang="ts">
const props = defineProps<{
  academyId: number
  electionId: number
  canManage: boolean
  lockedAt: string | null
}>()
const { lockVoterRoll, listVoters, setMemberEducationLevel } = useElections()
const rows = ref<any[]>([])
const counts = ref<any>(null)
const localLockedAt = ref(props.lockedAt)
const search = ref('')
const missing = ref('')
const page = ref(1)
const lastPage = ref(1)
const loading = ref(false)
let timer: ReturnType<typeof setTimeout> | undefined

const load = async () => {
  if (!props.academyId) return
  loading.value = true
  try {
    const voters: any = await listVoters(props.academyId, props.electionId, {
      page: page.value,
      per_page: 50,
      search: search.value,
      missing: missing.value,
    })
    rows.value = voters?.data?.data || []
    lastPage.value = voters?.data?.last_page || 1
  } finally {
    loading.value = false
  }
}
const lock = async () => {
  if (!confirm('ยืนยันการล็อกบัญชีผู้มีสิทธิ์เลือกตั้ง?')) return
  counts.value = ((await lockVoterRoll(props.academyId, props.electionId)) as any)?.data
  localLockedAt.value = new Date().toISOString()
  await load()
}
const setLevel = async (row: any, value: string) => {
  await setMemberEducationLevel(
    props.academyId,
    row.academy_member_id,
    value === '' ? null : Number(value)
  )
  await load()
}
const cards = computed(() =>
  counts.value
    ? [
        { label: 'ผู้มีสิทธิ์ทั้งหมด', value: counts.value.total },
        { label: 'นักเรียน', value: counts.value.students },
        { label: 'บุคลากร', value: counts.value.staff },
        { label: 'ไม่มีรหัสสมาชิก', value: counts.value.without_member_code },
        { label: 'ไม่มีบัตรนักเรียน', value: counts.value.without_student_card },
        { label: 'แถวสมาชิกซ้ำที่ถูกรวม', value: counts.value.duplicate_member_rows },
        { label: 'ข้ามเพราะไม่มีบัญชีผู้ใช้', value: counts.value.skipped_no_user_account },
        { label: 'ข้ามเพราะนักเรียนไม่ active', value: counts.value.skipped_inactive_student },
        { label: 'ข้ามเพราะอยู่คนละระดับ', value: counts.value.skipped_other_level },
        { label: 'บุคลากรไม่มีระดับ', value: counts.value.staff_without_level },
      ]
    : []
)
watch(() => [props.academyId, props.electionId], load, { immediate: true })
watch([missing], () => {
  page.value = 1
  load()
})
watch([search], () => {
  page.value = 1
  clearTimeout(timer)
  timer = setTimeout(load, 300)
})
watch(page, load)
</script>
<template>
  <div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
      <button
        class="min-h-[44px] rounded-lg bg-primary-600 px-4 py-2 text-white disabled:opacity-50"
        :disabled="!canManage"
        @click="lock"
      >
        {{ localLockedAt ? 'อัปเดตบัญชีผู้มีสิทธิ์' : 'ล็อกบัญชีผู้มีสิทธิ์' }}
      </button>
      <input
        v-model="search"
        class="min-h-[44px] rounded-lg border p-2"
        placeholder="ค้นหาชื่อหรือรหัสสมาชิก"
      />
      <select v-model="missing" class="min-h-[44px] rounded-lg border p-2">
        <option value="">ทั้งหมด</option>
        <option value="member_code">ไม่มีรหัสสมาชิก</option>
        <option value="student_card">ไม่มีบัตรนักเรียน</option>
      </select>
    </div>
    <p v-if="localLockedAt" class="text-sm text-gray-500">ล็อกล่าสุด: {{ localLockedAt }}</p>
    <div v-else class="rounded-xl border border-dashed p-4 text-sm text-gray-600">
      กดล็อกบัญชีผู้มีสิทธิ์เพื่อดูสรุปตัวเลขจากรอบล่าสุด
    </div>
    <div v-if="counts" class="rounded-xl bg-amber-50 p-4 text-sm text-amber-900">
      <p class="font-semibold">สรุปการล็อก: {{ counts.total }} คน</p>
      <p v-if="counts.staff_without_level">
        คำเตือน: หากเลือกตั้งแยกระดับ จะมีบุคลากร {{ counts.staff_without_level }} คนยังไม่ระบุระดับ
        จึงไม่มีสิทธิ์ลงคะแนน
      </p>
      <p v-if="counts.without_student_card">
        คำเตือน: นักเรียน {{ counts.without_student_card }} คนไม่มีบัตรนักเรียน
        ต้องใช้ช่องพิมพ์รหัสสมาชิกที่หน่วยเลือกตั้ง
      </p>
    </div>
    <div v-if="counts" class="grid grid-cols-2 gap-3 sm:grid-cols-5">
      <div v-for="item in cards" :key="item.label" class="rounded-xl border p-3">
        <p class="text-xs text-gray-500">{{ item.label }}</p>
        <p class="text-xl font-bold">{{ item.value }}</p>
      </div>
    </div>
    <div class="overflow-x-auto rounded-xl border">
      <table class="min-w-[760px] w-full text-left text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="p-3">ชื่อ</th>
            <th class="p-3">ประเภท</th>
            <th class="p-3">ระดับ</th>
            <th class="p-3">รหัสสมาชิก</th>
            <th class="p-3">ชั้นเรียน</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in rows" :key="row.id" class="border-t">
            <td class="p-3">{{ row.display_name }}</td>
            <td class="p-3">{{ row.voter_type }}</td>
            <td class="p-3">
              <select
                v-if="row.voter_type === 'staff'"
                :value="row.education_level ?? ''"
                class="min-h-[44px] rounded border p-2"
                @change="setLevel(row, ($event.target as HTMLSelectElement).value)"
              >
                <option value="">ไม่มีระดับ</option>
                <option value="1">ประถม</option>
                <option value="2">มัธยม</option></select
              ><span v-else>{{ row.grade_level || '-' }}</span>
            </td>
            <td class="p-3">{{ row.member_code || '-' }}</td>
            <td class="p-3">{{ row.classroom_name || '-' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="flex justify-between">
      <button
        class="min-h-[44px] rounded-lg border px-4 disabled:opacity-50"
        :disabled="page <= 1"
        @click="page--"
      >
        ก่อนหน้า</button
      ><button
        class="min-h-[44px] rounded-lg border px-4 disabled:opacity-50"
        :disabled="page >= lastPage"
        @click="page++"
      >
        ถัดไป
      </button>
    </div>
  </div>
</template>
