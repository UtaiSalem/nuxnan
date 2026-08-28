<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 pb-12">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 dark:from-blue-800 dark:to-indigo-950 text-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
          <div class="flex items-center space-x-4">
            <div class="flex-shrink-0 w-16 h-16 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
              <Icon icon="fluent:person-add-24-filled" class="w-10 h-10" />
            </div>
            <div class="min-w-0">
              <h1 class="text-2xl font-bold break-words">คำขอผูกบัญชีผู้ปกครอง</h1>
              <p class="text-blue-100">จัดการคำขอและเพิ่มข้อมูลนักเรียน</p>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <NuxtLink 
              :to="`/academies/${academyName}/parent`"
              class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-white text-blue-700 hover:bg-blue-50 rounded-xl font-semibold shadow-sm transition-colors flex items-center justify-center gap-2"
            >
              <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
              แดชบอร์ดผู้ปกครอง
            </NuxtLink>
          </div>
        </div>
      </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
      <!-- Search Panel -->
      <section class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 sm:p-6">
        <h2 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2 mb-4">
          <Icon icon="fluent:search-24-regular" class="w-6 h-6 text-blue-600" />
          เพิ่มบุตรหลาน
        </h2>
        
        <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
          กรุณากรอกข้อมูลให้ตรงกับที่โรงเรียนมีอยู่ทั้งสองช่อง เพื่อส่งคำขอให้นักเรียนยืนยัน
          (หลังจากส่งแล้ว <strong class="text-purple-600 dark:text-purple-400">นักเรียนต้องกดยอมรับ</strong> คุณถึงจะเห็นข้อมูล)
        </p>

        <div class="flex flex-col sm:flex-row gap-4 mb-4">
          <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">รหัสประจำตัวนักเรียน</label>
            <input v-model="searchForm.student_code" type="text" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-gray-900 dark:text-white" placeholder="เช่น 12345" />
          </div>
          <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">นามสกุลนักเรียน</label>
            <input v-model="searchForm.last_name" type="text" class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-gray-900 dark:text-white" placeholder="นามสกุลภาษาไทย" @keyup.enter="doSearch" />
          </div>
        </div>

        <button @click="doSearch" :disabled="!canSearch || isSearching" class="w-full min-h-[44px] rounded-xl bg-blue-600 px-4 text-sm font-semibold text-white disabled:opacity-50 flex items-center justify-center gap-2">
          <Icon v-if="isSearching" icon="mdi:loading" class="w-5 h-5 animate-spin" />
          ค้นหานักเรียน
        </button>

        <div v-if="hasSearched" class="mt-6 border-t border-gray-100 dark:border-gray-700 pt-6">
          <div v-if="searchResult" class="flex flex-col sm:flex-row items-center justify-between p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-900/50 gap-4">
            <div class="flex items-center gap-4 w-full sm:w-auto">
              <div class="flex-shrink-0 w-12 h-12 bg-blue-100 dark:bg-blue-800 rounded-full flex items-center justify-center text-blue-600 dark:text-blue-300 font-bold">
                <Icon icon="fluent:person-24-filled" class="w-6 h-6" />
              </div>
              <div class="min-w-0 flex-1">
                <h4 class="font-bold text-gray-900 dark:text-white truncate">{{ searchResult.full_name }}</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ searchResult.classroom }}</p>
              </div>
            </div>
            <button @click="sendRequest" :disabled="isSubmitting" class="w-full sm:w-auto min-h-[44px] flex-shrink-0 whitespace-nowrap rounded-xl bg-purple-600 px-4 text-sm font-semibold text-white hover:bg-purple-700 disabled:opacity-50">
              <Icon v-if="isSubmitting" icon="mdi:loading" class="w-4 h-4 mr-1 animate-spin inline" />
              ส่งคำขอเป็นผู้ปกครอง
            </button>
          </div>
          <div v-else class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">
            ไม่พบนักเรียนที่ตรงทั้งรหัสและนามสกุล
          </div>
        </div>
      </section>

      <!-- Requests Area -->
      <section class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <!-- Tabs -->
        <div class="flex border-b border-gray-200 dark:border-gray-700">
          <button 
            @click="activeTab = 'incoming'"
            :class="[
              'flex-1 min-h-[44px] py-3 text-sm font-bold text-center border-b-2 transition-colors relative',
              activeTab === 'incoming' 
                ? 'border-blue-600 text-blue-600 dark:text-blue-400' 
                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
            ]"
          >
            คำขอที่รอฉันตอบ
            <span v-if="incomingCount > 0" class="ml-2 inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white bg-red-500 rounded-full">{{ incomingCount }}</span>
          </button>
          <button 
            @click="activeTab = 'outgoing'"
            :class="[
              'flex-1 min-h-[44px] py-3 text-sm font-bold text-center border-b-2 transition-colors relative',
              activeTab === 'outgoing' 
                ? 'border-blue-600 text-blue-600 dark:text-blue-400' 
                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
            ]"
          >
            คำขอที่ฉันส่ง
            <span v-if="outgoingCount > 0" class="ml-2 inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white bg-gray-500 rounded-full">{{ outgoingCount }}</span>
          </button>
        </div>

        <div class="p-4 sm:p-6 pt-2 divide-y divide-gray-200 dark:divide-gray-700">
          <div v-if="isLoadingRequests" class="py-8 space-y-4">
            <div v-for="i in 3" :key="i" class="flex items-center gap-4 py-4 animate-pulse">
              <div class="flex-shrink-0 w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
              <div class="flex-1 space-y-2">
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/3"></div>
                <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/4"></div>
              </div>
            </div>
          </div>

          <template v-else-if="activeTab === 'incoming'">
            <div v-if="incomingRequests.length === 0" class="py-12 text-center">
              <div class="w-20 h-20 bg-gray-50 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                <Icon icon="fluent:mail-inbox-24-regular" class="w-10 h-10 text-gray-400" />
              </div>
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ไม่มีคำขอเข้าใหม่</h3>
              <p class="text-gray-500 dark:text-gray-400 mt-1 max-w-xs mx-auto">ขณะนี้ยังไม่มีนักเรียนหรือครูส่งคำขอผูกบัญชีมาถึงคุณ</p>
            </div>
            <div v-for="req in incomingRequests" :key="req.id" class="flex flex-col md:flex-row items-center gap-4 py-4 justify-between">
              <div class="flex items-center gap-4 w-full md:w-auto">
                <div class="flex-shrink-0 w-12 h-12 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full flex items-center justify-center font-bold text-lg">
                  {{ (req.student_name || req.guardian_name || '?').charAt(0) }}
                </div>
                <div class="min-w-0 flex-1 break-words">
                  <h5 class="text-base font-semibold text-gray-900 dark:text-white mb-0.5">
                    นักเรียน: {{ req.student_name }}
                  </h5>
                  <h6 class="text-sm text-gray-500 dark:text-gray-400">
                    จาก: {{ req.user_name || req.guardian_name }} • {{ formatDate(req.created_at) }}
                  </h6>
                </div>
              </div>
              <div class="flex items-center gap-2 w-full md:w-auto mt-2 md:mt-0 justify-end">
                <template v-if="req.status === 'pending'">
                  <button @click="doAccept(req.id)" :disabled="isResponding" class="flex-1 md:flex-none min-h-[44px] md:min-h-0 md:py-2 rounded-xl bg-green-600 px-4 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-50">
                    ยอมรับ
                  </button>
                  <button @click="openDecline(req.id)" :disabled="isResponding" class="flex-1 md:flex-none min-h-[44px] md:min-h-0 md:py-2 rounded-xl border border-gray-300 dark:border-gray-600 px-4 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50">
                    ปฏิเสธ
                  </button>
                </template>
                <template v-else>
                  <span :class="['px-3 py-1 rounded-full text-xs font-semibold', statusClass(req.status)]">
                    {{ statusLabel(req.status) }}
                  </span>
                </template>
              </div>
            </div>
          </template>

          <template v-else>
            <div v-if="outgoingRequests.length === 0" class="py-12 text-center">
              <div class="w-20 h-20 bg-gray-50 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                <Icon icon="fluent:send-24-regular" class="w-10 h-10 text-gray-400" />
              </div>
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ไม่มีคำขอที่กำลังรอ</h3>
              <p class="text-gray-500 dark:text-gray-400 mt-1 max-w-xs mx-auto">คุณยังไม่ได้ส่งคำขอผูกบัญชี หรือคำขอได้รับการตอบรับแล้ว</p>
            </div>
            <div v-for="req in outgoingRequests" :key="req.id" class="flex flex-col md:flex-row items-center gap-4 py-4 justify-between">
              <div class="flex items-center gap-4 w-full md:w-auto">
                <div class="flex-shrink-0 w-12 h-12 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-full flex items-center justify-center font-bold text-lg">
                  {{ (req.student_name || req.guardian_name || '?').charAt(0) }}
                </div>
                <div class="min-w-0 flex-1 break-words">
                  <h5 class="text-base font-semibold text-gray-900 dark:text-white mb-0.5">
                    นักเรียน: {{ req.student_name }}
                  </h5>
                  <h6 class="text-sm text-gray-500 dark:text-gray-400">
                    ส่งถึง: {{ req.user_name || req.guardian_name || 'บัญชีของนักเรียน' }} • {{ formatDate(req.created_at) }}
                  </h6>
                </div>
              </div>
              <div class="flex items-center gap-2 w-full md:w-auto mt-2 md:mt-0 justify-end">
                <template v-if="req.status === 'pending'">
                  <button @click="doCancel(req.id)" :disabled="isResponding" class="flex-1 md:flex-none min-h-[44px] md:min-h-0 md:py-2 rounded-xl border border-red-300 dark:border-red-800 px-4 text-sm font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 disabled:opacity-50">
                    ยกเลิกคำขอ
                  </button>
                </template>
                <template v-else>
                  <span :class="['px-3 py-1 rounded-full text-xs font-semibold', statusClass(req.status)]">
                    {{ statusLabel(req.status) }}
                  </span>
                </template>
              </div>
            </div>
          </template>
        </div>
      </section>
    </div>

    <!-- Decline Modal -->
    <div v-if="declineModalId" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="closeDecline"></div>
      <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-sm bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ปฏิเสธคำขอ</h3>
          <textarea v-model="declineReason" rows="3" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none mb-4" placeholder="เหตุผล (ไม่บังคับ)"></textarea>
          <div class="flex gap-3">
            <button @click="closeDecline" class="flex-1 min-h-[44px] py-2 rounded-xl border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 font-semibold hover:bg-gray-50 dark:hover:bg-gray-700">ยกเลิก</button>
            <button @click="confirmDecline" :disabled="isResponding" class="flex-1 min-h-[44px] py-2 rounded-xl bg-red-600 text-white font-semibold hover:bg-red-700 disabled:opacity-50">ยืนยันปฏิเสธ</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import { useGuardianAccount } from '~/composables/useGuardianAccount'
import { errorStatus, errorMessage } from '~/composables/useGuardianAppointment'
import { useToast } from '#imports'

definePageMeta({
  layout: 'main',
  middleware: ['auth']
})

const route = useRoute()
const academyName = computed(() => route.params.name as string)
const auth = useNuxtApp().$auth
const toast = useToast()

const { isSearching, isSubmitting, isResponding, searchStudent, createAccountRequest, fetchRequests, acceptRequest, declineRequest, cancelRequest } = useGuardianAccount(academyName)

const searchForm = ref({
  student_code: '',
  last_name: ''
})
const hasSearched = ref(false)
const searchResult = ref<any>(null)

const canSearch = computed(() => searchForm.value.student_code.trim().length > 0 && searchForm.value.last_name.trim().length > 0)

const doSearch = async () => {
  if (!canSearch.value) return
  hasSearched.value = true
  searchResult.value = null
  try {
    const res: any = await searchStudent(searchForm.value.student_code.trim(), searchForm.value.last_name.trim())
    searchResult.value = res.data
  } catch (err) {
    // If fails, we just don't show result
  }
}

const sendRequest = async () => {
  if (!searchResult.value || !auth?.user?.id) return
  try {
    await createAccountRequest(searchResult.value.id, { user_id: auth.user.id })
    toast.add({ severity: 'success', summary: 'สำเร็จ', detail: 'ส่งคำขอสำเร็จ', life: 3000 })
    searchForm.value.student_code = ''
    searchForm.value.last_name = ''
    hasSearched.value = false
    searchResult.value = null
    loadRequests()
  } catch (err: any) {
    const status = errorStatus(err)
    if (status === 409) {
      toast.add({ severity: 'warn', summary: 'ข้อมูลซ้ำ', detail: 'คุณเคยส่งคำขอให้บัญชีนี้แล้ว หรือผูกบัญชีไปแล้ว', life: 3000 })
    } else if (status === 403) {
      toast.add({ severity: 'error', summary: 'ข้อผิดพลาด', detail: 'ไม่มีสิทธิ์ดำเนินการ', life: 3000 })
    } else {
      toast.add({ severity: 'error', summary: 'ข้อผิดพลาด', detail: errorMessage(err) || 'เกิดข้อผิดพลาด', life: 3000 })
    }
  }
}

const activeTab = ref<'incoming'|'outgoing'>('incoming')
const incomingRequests = ref<any[]>([])
const outgoingRequests = ref<any[]>([])
const isLoadingRequests = ref(false)

const incomingCount = computed(() => incomingRequests.value.filter(r => r.status === 'pending').length)
const outgoingCount = computed(() => outgoingRequests.value.filter(r => r.status === 'pending').length)

const loadRequests = async () => {
  isLoadingRequests.value = true
  try {
    const res: any = await fetchRequests()
    incomingRequests.value = res.incoming || []
    outgoingRequests.value = res.outgoing || []
  } catch (err) {
    console.error('Error fetching requests', err)
  } finally {
    isLoadingRequests.value = false
  }
}

const declineModalId = ref<number | null>(null)
const declineReason = ref('')

const doAccept = async (id: number) => {
  try {
    await acceptRequest(id)
    toast.add({ severity: 'success', summary: 'สำเร็จ', detail: 'ยอมรับคำขอแล้ว', life: 3000 })
    loadRequests()
  } catch (err: any) {
    toast.add({ severity: 'error', summary: 'ข้อผิดพลาด', detail: errorMessage(err) || 'ไม่สามารถยอมรับคำขอได้', life: 3000 })
  }
}

const openDecline = (id: number) => {
  declineModalId.value = id
  declineReason.value = ''
}

const closeDecline = () => {
  declineModalId.value = null
}

const confirmDecline = async () => {
  if (!declineModalId.value) return
  try {
    await declineRequest(declineModalId.value, declineReason.value)
    toast.add({ severity: 'success', summary: 'สำเร็จ', detail: 'ปฏิเสธคำขอแล้ว', life: 3000 })
    closeDecline()
    loadRequests()
  } catch (err: any) {
    toast.add({ severity: 'error', summary: 'ข้อผิดพลาด', detail: errorMessage(err) || 'ไม่สามารถปฏิเสธคำขอได้', life: 3000 })
  }
}

const doCancel = async (id: number) => {
  try {
    await cancelRequest(id)
    toast.add({ severity: 'success', summary: 'สำเร็จ', detail: 'ยกเลิกคำขอแล้ว', life: 3000 })
    loadRequests()
  } catch (err: any) {
    toast.add({ severity: 'error', summary: 'ข้อผิดพลาด', detail: errorMessage(err) || 'ไม่สามารถยกเลิกคำขอได้', life: 3000 })
  }
}

const statusClass = (status: string) => {
  if (status === 'accepted') return 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'
  if (status === 'declined') return 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300'
  if (status === 'cancelled') return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
  return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300'
}

const statusLabel = (status: string) => {
  if (status === 'accepted') return 'อนุมัติแล้ว'
  if (status === 'declined') return 'ถูกปฏิเสธ'
  if (status === 'cancelled') return 'ยกเลิก'
  return 'รอการตอบรับ'
}

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

onMounted(() => {
  loadRequests()
})
</script>
