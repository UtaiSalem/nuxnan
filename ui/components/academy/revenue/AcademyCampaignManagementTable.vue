<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  academyId: number
}

const props = defineProps<Props>()

const { campaigns, isLoading, fetchCampaigns, createCampaign, updateCampaign, canManageRevenue } = useAcademyRevenue(props.academyId)

const campaignDialogVisible = ref(false)
const detailDialogVisible = ref(false)
const editingCampaign = ref<any>(null)
const selectedCampaign = ref<any>(null)
const form = ref({
  campaign_type: 'advertisement',
  title: '',
  description: '',
  budget_amount: 0,
  total_views: 100,
  duration: 10,
  payment_method: 'wallet',
  active_until: '',
})

onMounted(async () => {
  await fetchCampaigns()
})

const openCreate = () => {
  editingCampaign.value = null
  form.value = {
    campaign_type: 'advertisement',
    title: '',
    description: '',
    budget_amount: 0,
    total_views: 100,
    duration: 10,
    payment_method: 'wallet',
    active_until: '',
  }
  campaignDialogVisible.value = true
}

const openEdit = (campaign: any) => {
  editingCampaign.value = campaign
  form.value = {
    campaign_type: campaign.campaign_type,
    title: campaign.title,
    description: campaign.description || '',
    budget_amount: campaign.budget_amount,
    total_views: campaign.total_views,
    duration: campaign.duration,
    payment_method: 'wallet',
    active_until: campaign.active_until || '',
  }
  campaignDialogVisible.value = true
}

const openDetail = (campaign: any) => {
  selectedCampaign.value = campaign
  detailDialogVisible.value = true
}

const submit = async () => {
  if (editingCampaign.value) {
    await updateCampaign(editingCampaign.value.id, form.value)
  } else {
    await createCampaign(form.value)
  }
  campaignDialogVisible.value = false
}

const stopCampaign = async (campaign: any) => {
  await updateCampaign(campaign.id, { status: 2 })
}

const paymentStatusLabel = (status: string) => {
  switch (status) {
    case 'paid': return 'ชำระแล้ว'
    case 'unpaid': return 'ยังไม่ชำระ'
    case 'pending_slip': return 'รอตรวจสอบสลิป'
    case 'refunded': return 'เงินคืนแล้ว'
    default: return status
  }
}

const paymentStatusClass = (status: string) => {
  switch (status) {
    case 'paid': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
    case 'unpaid': return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
    case 'pending_slip': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
    case 'refunded': return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
    default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
  }
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h3 class="text-lg font-bold text-gray-900 dark:text-white">จัดการแคมเปญโฆษณา</h3>
      <Button v-if="canManageRevenue" label="สร้างแคมเปญ" icon="pi pi-plus" size="small" @click="openCreate" />
    </div>

    <div v-if="isLoading" class="space-y-3">
      <div v-for="i in 3" :key="i" class="h-16 animate-pulse rounded-xl bg-gray-100 dark:bg-gray-700" />
    </div>

    <div v-else-if="campaigns.length === 0" class="py-8 text-center text-gray-500 dark:text-gray-400">
      ยังไม่มีแคมเปญ
    </div>

    <div v-else class="overflow-x-auto rounded-2xl border border-gray-100 dark:border-gray-700">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-800">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">ID</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">ชื่อ</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">ประเภท</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">งบประมาณ</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">คงเหลือ</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">สถานะการตรวจสอบ</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">สถานะการชำระเงิน</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">จัดการ</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
          <tr v-for="campaign in campaigns" :key="campaign.id">
            <td class="px-4 py-3 text-gray-900 dark:text-white">#{{ campaign.id }}</td>
            <td class="px-4 py-3 text-gray-900 dark:text-white">{{ campaign.title }}</td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                :class="campaign.campaign_type === 'advertisement' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'">
                {{ campaign.campaign_type === 'advertisement' ? 'โฆษณา' : 'สนับสนุน' }}
              </span>
            </td>
            <td class="px-4 py-3 text-gray-900 dark:text-white">{{ campaign.budget_amount.toLocaleString() }}</td>
            <td class="px-4 py-3 text-gray-900 dark:text-white">{{ campaign.remaining_views.toLocaleString() }}</td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                :class="{
                  'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': campaign.review_status === 'pending',
                  'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': campaign.review_status === 'approved',
                  'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': campaign.review_status === 'rejected',
                }">
                {{ campaign.review_status === 'pending' ? 'รอตรวจสอบ' : campaign.review_status === 'approved' ? 'อนุมัติแล้ว' : 'ปฏิเสธ' }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium" :class="paymentStatusClass(campaign.payment_status)">
                {{ paymentStatusLabel(campaign.payment_status) }}
              </span>
            </td>
            <td class="px-4 py-3">
              <div class="flex gap-2">
                <Button icon="pi pi-eye" size="small" severity="info" @click="openDetail(campaign)" />
                <Button v-if="canManageRevenue" icon="pi pi-pencil" size="small" severity="info" @click="openEdit(campaign)" />
                <Button v-if="canManageRevenue && campaign.status !== 2" icon="pi pi-stop" size="small" severity="warning" @click="stopCampaign(campaign)" />
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <Dialog v-model:visible="detailDialogVisible" header="รายละเอียดแคมเปญ" :style="{ width: '500px' }">
      <div v-if="selectedCampaign" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-xs text-gray-500 dark:text-gray-400">ID</p>
            <p class="text-sm font-medium text-gray-900 dark:text-white">#{{ selectedCampaign.id }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 dark:text-gray-400">ชื่อ</p>
            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ selectedCampaign.title }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 dark:text-gray-400">ประเภท</p>
            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ selectedCampaign.campaign_type === 'advertisement' ? 'โฆษณา' : 'สนับสนุน' }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 dark:text-gray-400">งบประมาณ</p>
            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ selectedCampaign.budget_amount.toLocaleString() }} แต้ม</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 dark:text-gray-400">จำนวน Views ทั้งหมด</p>
            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ selectedCampaign.total_views.toLocaleString() }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 dark:text-gray-400">Views ที่เหลือ</p>
            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ selectedCampaign.remaining_views.toLocaleString() }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 dark:text-gray-400">สถานะการตรวจสอบ</p>
            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ selectedCampaign.review_status }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 dark:text-gray-400">สถานะการชำระเงิน</p>
            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ paymentStatusLabel(selectedCampaign.payment_status) }}</p>
          </div>
        </div>
        <div v-if="selectedCampaign.description">
          <p class="text-xs text-gray-500 dark:text-gray-400">รายละเอียด</p>
          <p class="text-sm text-gray-900 dark:text-white">{{ selectedCampaign.description }}</p>
        </div>
      </div>
    </Dialog>

    <Dialog v-model:visible="campaignDialogVisible" :header="editingCampaign ? 'แก้ไขแคมเปญ' : 'สร้างแคมเปญ'" :style="{ width: '500px' }">
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ชื่อแคมเปญ</label>
          <InputText v-model="form.title" class="mt-1 w-full" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">รายละเอียด</label>
          <Textarea v-model="form.description" rows="3" autoResize class="mt-1 w-full" />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">งบประมาณ</label>
            <InputNumber v-model="form.budget_amount" class="mt-1 w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">จำนวน Views</label>
            <InputNumber v-model="form.total_views" class="mt-1 w-full" />
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ระยะเวลา (วินาที)</label>
          <Select v-model="form.duration" :options="[5, 10, 15, 30, 60]" class="mt-1 w-full" />
        </div>
      </div>
      <template #footer>
        <Button label="ยกเลิก" severity="secondary" @click="campaignDialogVisible = false" />
        <Button label="บันทึก" @click="submit" />
      </template>
    </Dialog>
  </div>
</template>
