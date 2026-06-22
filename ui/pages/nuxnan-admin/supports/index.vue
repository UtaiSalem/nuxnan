<script setup>
import { ref, onMounted, watch } from 'vue'
import { Icon } from '@iconify/vue'
import ApproveDonateCard from '~/components/earn/donates/ApproveDonateCard.vue'
import Swal from 'sweetalert2'

definePageMeta({
  layout: 'nuxnan-admin-layout',
  middleware: 'nuxnan-admin'
})

const {
  donations, stats, page, lastPage, total, isLoading, status, search,
  fetch, receive, reject, update, destroy, bulkReview
} = useAdminDonations()

const viewMode = ref('card') // 'card', 'table'
const selectedSlip = ref(null)
const selectedDonation = ref(null)
const showEditModal = ref(false)
const selectedIds = ref([])
const processingId = ref(null)

const statusFilters = [
  { value: 'all', label: 'ทั้งหมด', color: 'bg-hopeui-primary-500' },
  { value: '0', label: 'รออนุมัติ', color: 'bg-yellow-500', statsKey: 'pending' },
  { value: '1', label: 'อนุมัติแล้ว', color: 'bg-green-500', statsKey: 'approved' },
  { value: '2', label: 'ปฏิเสธ', color: 'bg-red-500', statsKey: 'rejected' }
]

// Debounced search
let searchTimeout = null
watch(search, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetch(1)
  }, 500)
})

watch(status, () => {
  fetch(1)
})

const handleApprove = async (id) => {
  if (processingId.value) return
  try {
    processingId.value = id
    const response = await receive(id)
    if (response.success) {
      Swal.fire({
        title: 'สำเร็จ!',
        text: 'อนุมัติการสนับสนุนเรียบร้อยแล้ว',
        icon: 'success',
        timer: 1500,
        showConfirmButton: false
      })
    } else {
      Swal.fire('ไม่สำเร็จ', response.message || 'ไม่สามารถอนุมัติได้', 'warning')
    }
  } catch (err) {
    console.error(err)
    const msg = err.data?.message || err.message || 'ไม่สามารถอนุมัติได้'
    Swal.fire('เกิดข้อผิดพลาด', msg, 'error')
  } finally {
    processingId.value = null
  }
}

const handleReject = async (id) => {
  if (processingId.value) return
  const result = await Swal.fire({
    title: 'ยืนยันการปฏิเสธ?',
    text: "คุณแน่ใจหรือไม่ที่จะปฏิเสธการสนับสนุนนี้?",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#EF4444',
    confirmButtonText: 'ยืนยัน, ปฏิเสธ!',
    cancelButtonText: 'ยกเลิก'
  })

  if (result.isConfirmed) {
    try {
      processingId.value = id
      const response = await reject(id)
      if (response.success) {
        Swal.fire('เรียบร้อย', 'ปฏิเสธการสนับสนุนแล้ว', 'success')
      } else {
        Swal.fire('ไม่สำเร็จ', response.message || 'ไม่สามารถปฏิเสธได้', 'warning')
      }
    } catch (err) {
      console.error(err)
      const msg = err.data?.message || err.message || 'ไม่สามารถปฏิเสธได้'
      Swal.fire('เกิดข้อผิดพลาด', msg, 'error')
    } finally {
      processingId.value = null
    }
  }
}

const handleDelete = async (id) => {
  const result = await Swal.fire({
    title: 'ยืนยันการลบ?',
    text: "รายการจะถูกย้ายไปที่ถังขยะ สามารถกู้คืนได้ภายหลัง",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#EF4444',
    confirmButtonText: 'ยืนยันการลบ',
    cancelButtonText: 'ยกเลิก'
  })

  if (result.isConfirmed) {
    try {
      await destroy(id)
      Swal.fire('สำเร็จ', 'ลบรายการเรียบร้อยแล้ว', 'success')
    } catch (err) {
      Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถลบได้', 'error')
    }
  }
}

const handleBulkAction = async (action) => {
  if (selectedIds.value.length === 0) return

  const actionText = action === 'approve' ? 'อนุมัติ' : 'ปฏิเสธ'
  const result = await Swal.fire({
    title: `ยืนยัน${actionText}รายการที่เลือก?`,
    text: `จำนวน ${selectedIds.value.length} รายการ`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: `ยืนยัน, ${actionText}!`,
    cancelButtonText: 'ยกเลิก'
  })

  if (result.isConfirmed) {
    try {
      const response = await bulkReview(selectedIds.value, action)
      if (response.success) {
        Swal.fire('สำเร็จ', `${actionText}ทั้งหมด ${response.affected} รายการ`, 'success')
        selectedIds.value = []
      }
    } catch (err) {
      Swal.fire('เกิดข้อผิดพลาด', `ไม่สามารถ${actionText}ได้`, 'error')
    }
  }
}

const openEditModal = (donation) => {
  selectedDonation.value = { ...donation }
  showEditModal.value = true
}

const handleUpdate = async () => {
  try {
    const response = await update(selectedDonation.value.id, {
      donor_name: selectedDonation.value.donor_name,
      notes: selectedDonation.value.notes,
      review_note: selectedDonation.value.review_note
    })
    if (response.success) {
      showEditModal.value = false
      Swal.fire('สำเร็จ', 'อัปเดตข้อมูลเรียบร้อยแล้ว', 'success')
    }
  } catch (err) {
    Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถอัปเดตได้', 'error')
  }
}

const toggleSelectAll = () => {
  if (selectedIds.value.length === donations.value.length) {
    selectedIds.value = []
  } else {
    selectedIds.value = donations.value.map(d => d.id)
  }
}

const getStatusInfo = (s) => {
  switch (Number(s)) {
    case 0: return { text: 'รออนุมัติ', class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400', icon: 'fluent:clock-24-regular' }
    case 1: return { text: 'อนุมัติแล้ว', class: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400', icon: 'fluent:checkmark-circle-24-regular' }
    case 2: return { text: 'ปฏิเสธ', class: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400', icon: 'fluent:dismiss-circle-24-regular' }
    default: return { text: 'ไม่ทราบ', class: 'bg-slate-100 text-slate-800', icon: 'fluent:question-circle-24-regular' }
  }
}

onMounted(() => {
  fetch(1)
})
</script>

<template>
  <div class="space-y-6 max-w-[1600px] mx-auto pb-20">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-3xl font-black text-slate-800 dark:text-white flex items-center gap-3 tracking-tight">
          <div class="p-2 bg-gradient-to-br from-hopeui-primary-500 to-purple-600 rounded-2xl shadow-lg shadow-hopeui-primary-500/20">
            <Icon icon="fluent:heart-handshake-24-filled" class="w-8 h-8 text-white" />
          </div>
          จัดการการสนับสนุน
        </h1>
        <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium ml-14">ศูนย์กลางการตรวจสอบและอนุมัติยอดบริจาคเพื่อชุมชน</p>
      </div>

      <!-- View Toggle & Search -->
      <div class="flex items-center gap-3">
        <div class="relative group">
          <Icon icon="fluent:search-24-regular" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 group-focus-within:text-hopeui-primary-500 transition-colors" />
          <input
            v-model="search"
            type="text"
            placeholder="ค้นหาชื่อผู้บริจาค, ID, หรือรหัสธุรกรรม..."
            class="pl-11 pr-4 py-3 bg-white dark:bg-slate-800 border-none rounded-2xl shadow-hopeui-sm focus:ring-2 focus:ring-hopeui-primary-500 w-full md:w-80 transition-all text-sm font-medium"
          />
        </div>

        <div class="flex items-center bg-white dark:bg-slate-800 p-1 rounded-2xl shadow-hopeui-sm border border-slate-100 dark:border-slate-700">
          <button
            @click="viewMode = 'card'"
            :class="['p-2.5 rounded-xl transition-all', viewMode === 'card' ? 'bg-hopeui-primary-500 text-white shadow-lg shadow-hopeui-primary-500/30' : 'text-slate-400 hover:text-slate-600']"
          >
            <Icon icon="fluent:grid-24-regular" class="w-5 h-5" />
          </button>
          <button
            @click="viewMode = 'table'"
            :class="['p-2.5 rounded-xl transition-all', viewMode === 'table' ? 'bg-hopeui-primary-500 text-white shadow-lg shadow-hopeui-primary-500/30' : 'text-slate-400 hover:text-slate-600']"
          >
            <Icon icon="fluent:table-24-regular" class="w-5 h-5" />
          </button>
        </div>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="item in [
        { label: 'ทั้งหมด', value: stats.total, icon: 'fluent:list-24-regular', color: 'indigo' },
        { label: 'รออนุมัติ', value: stats.pending, icon: 'fluent:clock-24-regular', color: 'yellow' },
        { label: 'อนุมัติแล้ว', value: stats.approved, icon: 'fluent:checkmark-circle-24-regular', color: 'green' },
        { label: 'ปฏิเสธ', value: stats.rejected, icon: 'fluent:dismiss-circle-24-regular', color: 'rose' }
      ]" :key="item.label"
      class="bg-white dark:bg-slate-800 rounded-3xl p-5 shadow-hopeui-sm border border-slate-100 dark:border-slate-700 flex items-center gap-5 group hover:border-hopeui-primary-500/30 transition-all">
        <div :class="[`w-14 h-14 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform`, {
            'bg-hopeui-primary-100 dark:bg-hopeui-primary-900 text-hopeui-primary-600 dark:text-hopeui-primary-400': item.color === 'indigo',
            'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400': item.color === 'yellow',
            'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400': item.color === 'green',
            'bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400': item.color === 'rose',
        }]">
          <Icon :icon="item.icon" class="w-7 h-7" />
        </div>
        <div>
          <p class="text-3xl font-black text-slate-800 dark:text-white leading-none">{{ item.value }}</p>
          <p class="text-sm font-bold text-slate-400 uppercase tracking-wider mt-1">{{ item.label }}</p>
        </div>
      </div>
    </div>

    <!-- Toolbar: Filters & Bulk Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white/50 dark:bg-slate-800/50 backdrop-blur-xl p-3 rounded-[2rem] border border-white dark:border-slate-700 shadow-hopeui-lg">
      <div class="flex flex-wrap gap-2">
        <button
          v-for="filter in statusFilters"
          :key="filter.value"
          @click="status = filter.value"
          :class="[
            'px-5 py-2.5 rounded-2xl text-sm font-bold transition-all flex items-center gap-2',
            status.toString() === filter.value.toString()
              ? `${filter.color} text-white shadow-lg`
              : 'bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700'
          ]"
        >
          {{ filter.label }}
          <span v-if="filter.statsKey && stats[filter.statsKey] > 0" class="px-2 py-0.5 bg-white/20 rounded-full text-[10px]">
            {{ stats[filter.statsKey] }}
          </span>
        </button>
      </div>

      <!-- Bulk Actions -->
      <div v-if="selectedIds.length > 0" class="flex items-center gap-2 px-4 py-2 bg-hopeui-primary-100 dark:bg-hopeui-primary-900/20 rounded-2xl border border-hopeui-primary-100 dark:border-hopeui-primary-900">
        <span class="text-sm font-bold text-hopeui-primary-600 dark:text-hopeui-primary-400 mr-2">เลือกแล้ว {{ selectedIds.length }} รายการ:</span>
        <button @click="handleBulkAction('approve')" class="px-4 py-2 bg-green-500 text-white rounded-xl text-xs font-bold hover:bg-green-600 transition-colors">อนุมัติทั้งหมด</button>
        <button @click="handleBulkAction('reject')" class="px-4 py-2 bg-red-500 text-white rounded-xl text-xs font-bold hover:bg-red-600 transition-colors">ปฏิเสธทั้งหมด</button>
        <button @click="selectedIds = []" class="p-2 text-slate-400 hover:text-slate-600"><Icon icon="fluent:dismiss-24-regular" class="w-5 h-5" /></button>
      </div>
    </div>

    <!-- Content Area -->
    <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <div v-for="i in 8" :key="i" class="bg-white dark:bg-slate-800 h-96 rounded-3xl animate-pulse shadow-hopeui-sm border border-slate-100 dark:border-slate-700"></div>
    </div>

    <template v-else-if="donations.length > 0">
      <!-- Card Mode -->
      <div v-if="viewMode === 'card'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <ApproveDonateCard
          v-for="donate in donations"
          :key="donate.id"
          :donate="donate"
          :loading="processingId === donate.id"
          @approved="handleApprove(donate.id)"
          @rejected="handleReject(donate.id)"
          @edit="openEditModal"
          @delete="handleDelete"
          @view-slip="(url) => selectedSlip = url"
        />
      </div>

      <!-- Table Mode -->
      <div v-else class="bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-hopeui-lg overflow-hidden border border-slate-100 dark:border-slate-700">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50/50 dark:bg-slate-700/50">
                <th class="p-5 w-10">
                   <input type="checkbox" :checked="selectedIds.length === donations.length && donations.length > 0" @change="toggleSelectAll" class="rounded-lg text-hopeui-primary-500 focus:ring-hopeui-primary-500 w-5 h-5 cursor-pointer" />
                </th>
                <th class="p-5 text-[10px] font-black uppercase tracking-widest text-slate-400">ID / วันที่</th>
                <th class="p-5 text-[10px] font-black uppercase tracking-widest text-slate-400">ผู้บริจาค</th>
                <th class="p-5 text-[10px] font-black uppercase tracking-widest text-slate-400">ยอดเงิน / แต้ม</th>
                <th class="p-5 text-[10px] font-black uppercase tracking-widest text-slate-400">สลิป</th>
                <th class="p-5 text-[10px] font-black uppercase tracking-widest text-slate-400">สถานะ</th>
                <th class="p-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">จัดการ</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
              <tr v-for="donate in donations" :key="donate.id" class="hover:bg-hopeui-primary-50/30 dark:hover:bg-hopeui-primary-900/10 transition-colors group">
                <td class="p-5">
                   <input type="checkbox" v-model="selectedIds" :value="donate.id" class="rounded-lg text-hopeui-primary-500 focus:ring-hopeui-primary-500 w-5 h-5 cursor-pointer" />
                </td>
                <td class="p-5">
                  <div class="font-black text-slate-800 dark:text-white">#{{ donate.id }}</div>
                  <div class="text-[10px] font-bold text-slate-400 mt-1 uppercase">{{ donate.created_at }}</div>
                </td>
                <td class="p-5">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-slate-100 dark:bg-slate-700 overflow-hidden flex items-center justify-center border border-slate-100 dark:border-slate-600 shadow-hopeui-sm">
                      <img v-if="donate.donor?.avatar" :src="donate.donor.avatar" class="w-full h-full object-cover" />
                      <Icon v-else icon="fluent:person-24-regular" class="w-5 h-5 text-slate-400" />
                    </div>
                    <div>
                      <div class="font-bold text-slate-800 dark:text-white text-sm">{{ donate.donor_name }}</div>
                      <div class="text-[11px] font-medium text-slate-400">{{ donate.donor?.email || donate.donor_email || '-' }}</div>
                    </div>
                  </div>
                </td>
                <td class="p-5">
                  <div class="font-black text-blue-600 dark:text-blue-400 text-base leading-tight">{{ donate.amounts }}</div>
                  <div class="text-[11px] font-bold text-purple-500 mt-0.5 tracking-tight">+{{ donate.total_points?.toLocaleString() }} แต้ม</div>
                </td>
                <td class="p-5">
                  <button
                    v-if="donate.slip"
                    @click="selectedSlip = donate.slip.startsWith('http') ? donate.slip : useRuntimeConfig().public.apiBase + donate.slip"
                    class="w-12 h-16 rounded-xl overflow-hidden border-2 border-white dark:border-slate-700 shadow-lg hover:scale-110 transition-transform"
                  >
                    <img :src="donate.slip.startsWith('http') ? donate.slip : useRuntimeConfig().public.apiBase + donate.slip" class="w-full h-full object-cover" />
                  </button>
                  <span v-else class="text-slate-300 font-bold text-xs uppercase italic">No Slip</span>
                </td>
                <td class="p-5">
                  <div :class="[getStatusInfo(donate.status).class, 'px-3 py-1 rounded-full text-[10px] font-black inline-flex items-center gap-1.5 uppercase tracking-wide']">
                    <Icon :icon="getStatusInfo(donate.status).icon" class="w-3.5 h-3.5" />
                    {{ getStatusInfo(donate.status).text }}
                  </div>
                </td>
                <td class="p-5">
                  <div class="flex items-center justify-center gap-2">
                    <template v-if="donate.status === 0">
                      <button
                        @click="handleApprove(donate.id)"
                        :disabled="processingId === donate.id"
                        class="p-2.5 bg-green-100 text-green-600 rounded-xl hover:bg-green-500 hover:text-white disabled:opacity-50 transition-all shadow-hopeui-sm"
                        title="อนุมัติ"
                      >
                        <Icon v-if="processingId === donate.id" icon="fluent:spinner-24-regular" class="w-5 h-5 animate-spin" />
                        <Icon v-else icon="fluent:checkmark-24-regular" class="w-5 h-5" />
                      </button>
                      <button
                        @click="handleReject(donate.id)"
                        :disabled="processingId === donate.id"
                        class="p-2.5 bg-red-100 text-red-600 rounded-xl hover:bg-red-500 hover:text-white disabled:opacity-50 transition-all shadow-hopeui-sm"
                        title="ปฏิเสธ"
                      >
                        <Icon v-if="processingId === donate.id" icon="fluent:spinner-24-regular" class="w-5 h-5 animate-spin" />
                        <Icon v-else icon="fluent:dismiss-24-regular" class="w-5 h-5" />
                      </button>
                    </template>
                    <button @click="openEditModal(donate)" class="p-2.5 bg-slate-100 text-slate-600 rounded-xl hover:bg-hopeui-primary-500 hover:text-white transition-all shadow-hopeui-sm" title="แก้ไข">
                      <Icon icon="fluent:edit-24-regular" class="w-5 h-5" />
                    </button>
                    <button @click="handleDelete(donate.id)" class="p-2.5 bg-slate-100 text-slate-400 rounded-xl hover:bg-red-500 hover:text-white transition-all shadow-hopeui-sm opacity-0 group-hover:opacity-100" title="ลบ">
                      <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <div v-else class="text-center py-32 bg-white dark:bg-slate-800 rounded-[3rem] shadow-hopeui-lg border border-slate-100 dark:border-slate-700">
      <div class="w-24 h-24 bg-slate-50 dark:bg-slate-900 rounded-[2.5rem] flex items-center justify-center mx-auto mb-6">
        <Icon icon="fluent:box-search-24-regular" class="w-12 h-12 text-slate-200" />
      </div>
      <p class="text-xl font-black text-slate-800 dark:text-white tracking-tight">ไม่พบข้อมูลการสนับสนุน</p>
      <p class="text-slate-400 font-medium mt-1">ลองเปลี่ยนคำค้นหาหรือเลือกตัวกรองสถานะอื่น</p>
    </div>

    <!-- Pagination -->
    <div v-if="!isLoading && lastPage > 1" class="flex justify-center mt-12">
      <div class="flex items-center gap-1.5 bg-white dark:bg-slate-800 p-2 rounded-3xl shadow-hopeui-lg border border-slate-100 dark:border-slate-700">
        <button
          :disabled="page === 1"
          @click="fetch(page - 1)"
          class="p-3 bg-slate-50 dark:bg-slate-900 text-slate-400 hover:text-hopeui-primary-500 hover:bg-hopeui-primary-50 dark:hover:bg-hopeui-primary-900/30 rounded-2xl disabled:opacity-20 transition-all"
        >
          <Icon icon="fluent:chevron-left-24-regular" class="w-6 h-6" />
        </button>

        <div class="flex items-center gap-1 px-4">
          <span class="text-sm font-black text-slate-800 dark:text-white">{{ page }}</span>
          <span class="text-sm font-bold text-slate-300">/</span>
          <span class="text-sm font-bold text-slate-400">{{ lastPage }}</span>
        </div>

        <button
          :disabled="page === lastPage"
          @click="fetch(page + 1)"
          class="p-3 bg-slate-50 dark:bg-slate-900 text-slate-400 hover:text-hopeui-primary-500 hover:bg-hopeui-primary-50 dark:hover:bg-hopeui-primary-900/30 rounded-2xl disabled:opacity-20 transition-all"
        >
          <Icon icon="fluent:chevron-right-24-regular" class="w-6 h-6" />
        </button>
      </div>
    </div>

    <!-- Edit Modal -->
    <Teleport to="body">
        <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-2xl w-full max-w-lg overflow-hidden border border-white/20">
                <div class="p-8 pb-4 flex justify-between items-center border-b border-slate-100 dark:border-slate-700">
                    <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">แก้ไขข้อมูล #{{ selectedDonation.id }}</h2>
                    <button @click="showEditModal = false" class="p-2 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl transition-colors"><Icon icon="fluent:dismiss-24-regular" class="w-6 h-6" /></button>
                </div>
                <div class="p-8 space-y-5">
                    <div class="space-y-2">
                        <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">ชื่อผู้บริจาค (แสดงผล)</label>
                        <input v-model="selectedDonation.donor_name" type="text" class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-900 border-none rounded-2xl focus:ring-2 focus:ring-hopeui-primary-500 font-bold" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">หมายเหตุ (จากผู้บริจาค)</label>
                        <textarea v-model="selectedDonation.notes" rows="3" class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-900 border-none rounded-2xl focus:ring-2 focus:ring-hopeui-primary-500 font-medium text-sm"></textarea>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-black text-hopeui-primary-400 uppercase tracking-widest ml-1">หมายเหตุแอดมิน (Audit Note)</label>
                        <textarea v-model="selectedDonation.review_note" rows="3" placeholder="ระบุเหตุผลการอนุมัติหรือปฏิเสธเพื่อเป็นหลักฐาน..." class="w-full px-5 py-4 bg-hopeui-primary-50/30 dark:bg-hopeui-primary-900/10 border border-hopeui-primary-100/50 dark:border-hopeui-primary-800/50 rounded-2xl focus:ring-2 focus:ring-hopeui-primary-500 font-medium text-sm"></textarea>
                    </div>
                </div>
                <div class="p-8 pt-0 flex gap-3">
                    <button @click="showEditModal = false" class="flex-1 py-4 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-black rounded-2xl hover:bg-slate-200 dark:hover:bg-slate-600 transition-all">ยกเลิก</button>
                    <button @click="handleUpdate" class="flex-1 py-4 bg-gradient-to-r from-hopeui-primary-500 to-purple-600 text-white font-black rounded-2xl shadow-xl shadow-hopeui-primary-500/30 hover:scale-[1.02] transition-all">บันทึกการเปลี่ยนแปลง</button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Slip Modal (Teleported) -->
    <Teleport to="body">
      <div
        v-if="selectedSlip"
        class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/95 backdrop-blur-md p-4"
        @click="selectedSlip = null"
      >
        <div class="relative max-w-4xl max-h-[95vh]">
          <button
            @click="selectedSlip = null"
            class="absolute -top-12 right-0 p-3 bg-white/10 hover:bg-white/20 rounded-full text-white transition-all backdrop-blur-md border border-white/20"
          >
            <Icon icon="fluent:dismiss-24-regular" class="w-8 h-8" />
          </button>
          <img
            :src="selectedSlip"
            alt="สลิปโอนเงิน"
            class="max-w-full max-h-[85vh] object-contain rounded-[2rem] shadow-2xl border-4 border-white/10"
            @click.stop
          />
          <div class="mt-6 flex justify-center">
              <a :href="selectedSlip" download class="px-8 py-3 bg-white text-slate-900 rounded-2xl font-black text-sm hover:scale-105 transition-transform flex items-center gap-2">
                  <Icon icon="fluent:arrow-download-24-regular" class="w-5 h-5" />
                  ดาวน์โหลดสลิป
              </a>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
