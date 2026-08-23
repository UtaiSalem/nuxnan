<script setup lang="ts">
const props = defineProps<{
  academyId: number
  electionId: number
  academyName: string
  canManage: boolean
  status: string
}>()
const { listStations, createStation, updateStation, deleteStation, openStation, closeStation } =
  useElections()
const stations = ref<any[]>([])
const form = reactive({ name: '', location: '' })
const editing = ref<number | null>(null)
const loading = ref(false)
const message = ref('')
const load = async () => {
  loading.value = true
  try {
    stations.value = ((await listStations(props.academyId, props.electionId)) as any)?.data || []
  } finally {
    loading.value = false
  }
}
const save = async () => {
  if (!form.name.trim()) return
  if (editing.value)
    await updateStation(props.academyId, props.electionId, editing.value, { ...form })
  else await createStation(props.academyId, props.electionId, { ...form })
  editing.value = null
  form.name = ''
  form.location = ''
  await load()
}
const edit = (station: any) => {
  editing.value = station.id
  form.name = station.name
  form.location = station.location || ''
}
const remove = async (station: any) => {
  if (confirm('ยืนยันการลบหน่วยเลือกตั้ง?')) {
    await deleteStation(props.academyId, props.electionId, station.id)
    await load()
  }
}
const toggle = async (station: any) => {
  if (station.is_open) await closeStation(props.academyId, props.electionId, station.id)
  else if (props.status === 'voting')
    await openStation(props.academyId, props.electionId, station.id)
  else message.value = 'เปิดหน่วยได้เฉพาะเมื่อการเลือกตั้งอยู่ในสถานะกำลังลงคะแนน'
  await load()
}
const linkFor = (station: any) =>
  `/academies/${props.academyName}/elections/${props.electionId}/station?station=${station.id}`
const copy = async (station: any) => {
  await navigator.clipboard.writeText(`${window.location.origin}${linkFor(station)}`)
  message.value = 'คัดลอกลิงก์แล้ว'
}
watch(() => [props.academyId, props.electionId], load, { immediate: true })
</script>
<template>
  <div class="space-y-4">
    <form
      v-if="canManage"
      class="grid gap-3 rounded-xl border p-4 sm:grid-cols-[1fr_1fr_auto]"
      @submit.prevent="save"
    >
      <input
        v-model="form.name"
        required
        class="min-h-[44px] rounded-lg border p-2"
        placeholder="ชื่อหน่วย"
      />
      <input
        v-model="form.location"
        class="min-h-[44px] rounded-lg border p-2"
        placeholder="สถานที่"
      />
      <button class="min-h-[44px] rounded-lg bg-primary-600 px-4 text-white">
        {{ editing ? 'บันทึก' : 'เพิ่มหน่วย' }}
      </button>
    </form>
    <p v-if="message" class="rounded-lg bg-blue-50 p-3 text-sm text-blue-800">{{ message }}</p>
    <div class="overflow-x-auto rounded-xl border">
      <table class="min-w-[760px] w-full text-left text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="p-3">หน่วย</th>
            <th class="p-3">สถานที่</th>
            <th class="p-3">สถานะ</th>
            <th class="p-3">ออกบัตร</th>
            <th class="p-3">ใช้สิทธิ์</th>
            <th class="p-3">จัดการ</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="station in stations" :key="station.id" class="border-t">
            <td class="p-3">{{ station.name }}</td>
            <td class="p-3">{{ station.location || '-' }}</td>
            <td class="p-3">{{ station.is_open ? 'เปิด' : 'ปิด' }}</td>
            <td class="p-3">{{ station.issued_count }}</td>
            <td class="p-3">{{ station.cast_count }}</td>
            <td class="p-3">
              <div class="flex flex-wrap gap-2">
                <button
                  class="min-h-[44px] rounded border px-3"
                    :disabled="!canManage || (!station.is_open && status !== 'voting')"
                    :title="!station.is_open && status !== 'voting' ? 'เปิดหน่วยได้เฉพาะเมื่อการเลือกตั้งอยู่ในสถานะกำลังลงคะแนน' : ''"
                  @click="toggle(station)"
                >
                  {{ station.is_open ? 'ปิดหน่วย' : 'เปิดหน่วย' }}</button
                ><button class="min-h-[44px] rounded border px-3" @click="copy(station)">
                  คัดลอกลิงก์</button
                ><button
                  v-if="canManage"
                  class="min-h-[44px] rounded border px-3"
                  @click="edit(station)"
                >
                  แก้ไข</button
                ><button
                  v-if="canManage"
                  class="min-h-[44px] rounded border px-3 text-red-600"
                  @click="remove(station)"
                >
                  ลบ
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <p
      v-if="!loading && !stations.length"
      class="rounded-xl border border-dashed p-6 text-center text-gray-500"
    >
      ยังไม่มีหน่วยเลือกตั้ง
    </p>
  </div>
</template>
