<script setup lang="ts">
import { Icon } from '@iconify/vue'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'

interface Props {
  academyId: number
}

const props = defineProps<Props>()

const { donations, isLoading, fetchDonations, approveDonation, rejectDonation, canManageRevenue } = useAcademyRevenue(props.academyId)

const selectedStatus = ref<string | null>(null)
const selectedType = ref<string | null>(null)
const rejectDialogVisible = ref(false)
const detailDialogVisible = ref(false)
const selectedDonation = ref<any>(null)
const rejectReason = ref('')

onMounted(async () => {
  await fetchDonations({ status: 'pending' })
})

const openReject = (donation: any) => {
  selectedDonation.value = donation
  rejectReason.value = ''
  rejectDialogVisible.value = true
}

const confirmReject = async () => {
  if (!selectedDonation.value || !rejectReason.value.trim()) return
  await rejectDonation(selectedDonation.value.id, rejectReason.value)
  rejectDialogVisible.value = false
  if (detailDialogVisible.value) {
    detailDialogVisible.value = false
  }
}

const applyStatusFilter = (status: string | null) => {
  selectedStatus.value = status
  fetchDonations({ status: status ?? undefined })
}

const applyTypeFilter = (type: string | null) => {
  selectedType.value = type
}

const filteredDonations = computed(() => {
  let result = donations.value
  if (selectedType.value) {
    result = result.filter((d: any) => d.donation_type === selectedType.value)
  }
  return result
})

const statusButtons = [
  { label: 'ทั้งหมด', value: null as string | null },
  { label: 'รอตรวจสอบ', value: 'pending' },
  { label: 'อนุมัติ', value: 'completed' },
  { label: 'ปฏิเสธ', value: 'rejected' },
]

const typeButtons = [
  { label: 'ทุกประเภท', value: null as string | null },
  { label: 'แต้ม', value: 'point' },
  { label: 'เงิน', value: 'cash' },
]

const openDetail = (donation: any) => {
  selectedDonation.value = donation
  detailDialogVisible.value = true
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <h3 class="text-lg font-bold text-gray-900 dark:text-white">รายการสนับสนุน</h3>
      <div class="flex flex-wrap gap-2">
        <Button
          v-for="btn in statusButtons"
          :key="btn.value || 'all'"
          :label="btn.label"
          :severity="selectedStatus === btn.value ? 'primary' : 'secondary'"
          size="small"
          @click="applyStatusFilter(btn.value)"
        />
      </div>
      <div class="flex flex-wrap gap-2">
        <Button
          v-for="btn in typeButtons"
          :key="btn.value || 'all-types'"
          :label="btn.label"
          :severity="selectedType === btn.value ? 'primary' : 'secondary'"
          size="small"
          @click="applyTypeFilter(btn.value)"
        />
      </div>
    </div>

    <div v-if="isLoading" class="space-y-3">
      <div v-for="i in 3" :key="i" class="h-16 animate-pulse rounded-xl bg-gray-100 dark:bg-gray-700" />
    </div>

    <div v-else-if="filteredDonations.length === 0" class="py-8 text-center text-gray-500 dark:text-gray-400">
      ไม่มีรายการ
    </div>

    <div v-else class="overflow-x-auto rounded-2xl border border-gray-100 dark:border-gray-700">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-800">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">ID</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">ประเภท</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">จำนวน</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">ผู้สนับสนุน</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">สถานะ</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">วันที่</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">จัดการ</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
          <tr v-for="donation in filteredDonations" :key="donation.id">
            <td class="px-4 py-3 text-gray-900 dark:text-white">#{{ donation.id }}</td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                :class="donation.donation_type === 'point' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200'">
                {{ donation.donation_type === 'point' ? 'แต้ม' : 'เงิน' }}
              </span>
            </td>
            <td class="px-4 py-3 text-gray-900 dark:text-white">
              {{ donation.donation_type === 'point' ? `${donation.points_amount?.toLocaleString()} แต้ม` : `${donation.cash_amount?.toLocaleString()} ${donation.currency}` }}
            </td>
            <td class="px-4 py-3 text-gray-900 dark:text-white">{{ donation.donor_display_name }}</td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                :class="{
                  'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': donation.status === 'pending',
                  'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': donation.status === 'approved' || donation.status === 'completed',
                  'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': donation.status === 'rejected',
                }">
                {{ donation.status === 'pending' ? 'รอตรวจสอบ' : donation.status === 'approved' || donation.status === 'completed' ? 'อนุมัติแล้ว' : 'ปฏิเสธ' }}
              </span>
            </td>
            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ new Date(donation.created_at).toLocaleDateString('th-TH') }}</td>
            <td class="px-4 py-3">
              <div class="flex gap-2">
                <Button icon="pi pi-eye" size="small" severity="info" @click="openDetail(donation)" />
                <Button v-if="donation.status === 'pending' && canManageRevenue" icon="pi pi-check" size="small" severity="success" @click="approveDonation(donation.id)" />
                <Button v-if="donation.status === 'pending' && canManageRevenue" icon="pi pi-times" size="small" severity="danger" @click="openReject(donation)" />
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <Dialog v-model:visible="rejectDialogVisible" header="ปฏิเสธการบริจาค" :style="{ width: '400px' }">
      <div class="space-y-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">เหตุผล</label>
        <Textarea v-model="rejectReason" rows="3" autoResize class="w-full" />
      </div>
      <template #footer>
        <Button label="ยกเลิก" severity="secondary" @click="rejectDialogVisible = false" />
        <Button label="ปฏิเสธ" severity="danger" :disabled="!rejectReason.trim()" @click="confirmReject" />
      </template>
    </Dialog>

    <Dialog v-model:visible="detailDialogVisible" header="รายละเอียดการบริจาค" :style="{ width: '500px' }">
      <div v-if="selectedDonation" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-xs text-gray-500 dark:text-gray-400">ID</p>
            <p class="text-sm font-medium text-gray-900 dark:text-white">#{{ selectedDonation.id }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 dark:text-gray-400">ประเภท</p>
            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ selectedDonation.donation_type === 'point' ? 'แต้ม' : 'เงิน' }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 dark:text-gray-400">จำนวน</p>
            <p class="text-sm font-medium text-gray-900 dark:text-white">
              {{ selectedDonation.donation_type === 'point' ? `${selectedDonation.points_amount?.toLocaleString()} แต้ม` : `${selectedDonation.cash_amount?.toLocaleString()} ${selectedDonation.currency}` }}
            </p>
          </div>
          <div>
            <p class="text-xs text-gray-500 dark:text-gray-400">ผู้สนับสนุน</p>
            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ selectedDonation.donor_display_name }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 dark:text-gray-400">สถานะ</p>
            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ selectedDonation.status }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 dark:text-gray-400">วันที่สร้าง</p>
            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ new Date(selectedDonation.created_at).toLocaleString('th-TH') }}</p>
          </div>
        </div>
        <div v-if="selectedDonation.purpose">
          <p class="text-xs text-gray-500 dark:text-gray-400">จุดประสงค์</p>
          <p class="text-sm text-gray-900 dark:text-white">{{ selectedDonation.purpose }}</p>
        </div>
        <div v-if="selectedDonation.slip_path">
          <p class="text-xs text-gray-500 dark:text-gray-400">สลิป</p>
          <img :src="selectedDonation.slip_path" alt="slip" class="mt-2 max-h-64 rounded-lg border border-gray-200 dark:border-gray-700" />
        </div>
      </div>
    </Dialog>
  </div>
</template>
