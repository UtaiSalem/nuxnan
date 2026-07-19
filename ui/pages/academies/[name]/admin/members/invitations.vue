<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

definePageMeta({ layout: 'academy-admin' })
const route = useRoute()
const api = useApi()
const academyName = String(route.params.name)
const academyId = ref<number | null>(null)
const { can, fetchMyRole } = useAcademyRole(academyId)
const rows = ref<any[]>([])
const q = ref('')
const action = ref('')
const from = ref('')
const to = ref('')
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })

const load = async (page = 1) => {
  const params = new URLSearchParams({ page: String(page) })
  if (q.value) params.set('q', q.value)
  if (action.value) params.set('action', action.value)
  if (from.value) params.set('from', from.value)
  if (to.value) params.set('to', to.value)
  const response: any = await api.get(`/api/academies/${academyId.value}/members/invitations?${params}`)
  rows.value = response.data || []
  pagination.value = response.pagination || pagination.value
}

onMounted(async () => {
  const academy: any = await api.get(`/api/academies/${academyName}`)
  academyId.value = academy.academy?.id
  await fetchMyRole()
  if (!academyId.value || !can('members.view')) return navigateTo(`/academies/${academyName}/admin/members`)
  await load()
})

const actionMeta: Record<string, { label: string; color: string; icon: string }> = {
  invite: { label: 'เชิญ', color: 'blue', icon: 'fluent:mail-24-regular' },
  accept_invite: { label: 'ตอบรับ', color: 'green', icon: 'fluent:checkmark-circle-24-regular' },
  decline_invite: { label: 'ปฏิเสธ', color: 'red', icon: 'fluent:dismiss-circle-24-regular' },
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
      <div><NuxtLink :to="`/academies/${academyName}/admin/members`" class="text-sm text-primary-600">← สมาชิก</NuxtLink><h1 class="text-2xl font-bold mt-2">ประวัติการเชิญสมาชิก</h1></div>
    </div>
    <div class="flex flex-wrap gap-3"><input v-model="q" @keyup.enter="load()" placeholder="ค้นหาผู้ทำหรือเป้าหมาย" class="border rounded-lg px-3 py-2" /><select v-model="action" @change="load()" class="border rounded-lg px-3 py-2"><option value="">ทุกการกระทำ</option><option value="invite">เชิญ</option><option value="accept_invite">ตอบรับ</option><option value="decline_invite">ปฏิเสธ</option></select><input v-model="from" @change="load()" type="date" class="border rounded-lg px-3 py-2" /><input v-model="to" @change="load()" type="date" class="border rounded-lg px-3 py-2" /></div>
    <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-xl border"><table class="min-w-full text-sm"><thead><tr class="text-left border-b"><th class="p-3">วันที่/เวลา</th><th class="p-3">การกระทำ</th><th class="p-3">ผู้ทำ</th><th class="p-3">เป้าหมาย</th><th class="p-3">รายละเอียด</th><th class="p-3">IP</th></tr></thead><tbody><tr v-for="row in rows" :key="row.id" class="border-b last:border-0"><td class="p-3">{{ row.created_at }}</td><td class="p-3"><span :class="`text-${actionMeta[row.action]?.color || 'gray'}-600 flex items-center gap-1`"><Icon :icon="actionMeta[row.action]?.icon || 'fluent:info-24-regular'" />{{ actionMeta[row.action]?.label || row.action }}</span></td><td class="p-3">{{ row.user?.name || row.user?.email || '-' }}</td><td class="p-3">{{ row.target_user?.name || row.target_user?.email || '-' }}</td><td class="p-3">{{ row.description }}</td><td class="p-3">{{ row.ip_address || '-' }}</td></tr><tr v-if="!rows.length"><td colspan="6" class="p-8 text-center text-gray-500">ไม่พบข้อมูล</td></tr></tbody></table></div>
    <div class="flex justify-between"><button v-if="pagination.current_page > 1" @click="load(pagination.current_page - 1)">ก่อนหน้า</button><span>หน้า {{ pagination.current_page }} / {{ pagination.last_page }}</span><button v-if="pagination.current_page < pagination.last_page" @click="load(pagination.current_page + 1)">ถัดไป</button></div>
  </div>
</template>
