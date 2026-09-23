<script setup lang="ts">
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

definePageMeta({
  layout: 'main'
})

const route = useRoute()
const router = useRouter()
const api = useApi()
const academyName = computed(() => route.params.name as string)

// useAcademyRole ต้องได้ ref ของ academyId และไม่มี `academy` ในตัว — โหลด academy เองแบบเดียวกับ admin/schedule.vue
const academy = ref<any>(null)
const academyId = ref<number | null>(null)
const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)

const canView = computed(() => isAdmin.value || can('schedule.view') || can('academy.view'))
const canManage = computed(() => isAdmin.value || can('schedule.manage'))

const pad = (n: number) => (n < 10 ? '0' + n : n)
const todayObj = new Date()
const todayYMD = `${todayObj.getFullYear()}-${pad(todayObj.getMonth() + 1)}-${pad(todayObj.getDate())}`

const currentTab = ref<'daily' | 'history'>('daily')

// --- Tab 1: Daily ---
const dailyDate = ref(todayYMD)
const dailyTeacherId = ref<number | null>(null)
const dailySearch = ref('')
const dailySchedules = ref<any[]>([])
const allTeachers = ref<any[]>([])
const loadingDaily = ref(false)

const loadAllTeachers = async () => {
  if (!academy.value?.id) return
  try {
    const res = await api.get(`/api/academies/${academy.value.id}/members`, {
      params: { role: 'teacher', status: 2, per_page: 200 }
    })
    allTeachers.value = res.members || []
  } catch (err) {
    console.error('Load teachers error', err)
  }
}

const loadDailySchedules = async () => {
  if (!academy.value?.id || !dailyDate.value) return
  loadingDaily.value = true
  try {
    const params: Record<string, any> = { date: dailyDate.value }
    if (dailyTeacherId.value) params.teacher_id = dailyTeacherId.value
    
    const res = await api.get(`/api/academies/${academy.value.id}/schedules/today`, { params })
    dailySchedules.value = res.data?.schedules || []
  } catch (err) {
    console.error('Load schedules error', err)
  } finally {
    loadingDaily.value = false
  }
}

const prevDay = () => {
  const d = new Date(`${dailyDate.value}T00:00:00`)
  d.setDate(d.getDate() - 1)
  dailyDate.value = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
}

const nextDay = () => {
  const d = new Date(`${dailyDate.value}T00:00:00`)
  d.setDate(d.getDate() + 1)
  dailyDate.value = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
}

const setToday = () => {
  dailyDate.value = todayYMD
}

watch([dailyDate, dailyTeacherId], () => {
  loadDailySchedules()
})

const filteredDailySchedules = computed(() => {
  if (!dailySearch.value.trim()) return dailySchedules.value
  const q = dailySearch.value.toLowerCase()
  return dailySchedules.value.filter(s => {
    return (s.display_title || '').toLowerCase().includes(q) ||
           (s.classroom?.name || '').toLowerCase().includes(q) ||
           (s.teacher?.name || '').toLowerCase().includes(q)
  })
})

const dailyDateThai = computed(() => {
  if (!dailyDate.value) return ''
  return new Date(`${dailyDate.value}T00:00:00`).toLocaleDateString('th-TH', {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
  })
})

const isWeekend = computed(() => {
  if (!dailyDate.value) return false
  const day = new Date(`${dailyDate.value}T00:00:00`).getDay()
  return day === 0 || day === 6
})

// Modal State
const modalShow = ref(false)
const modalSchedule = ref<any>(null)
const modalInitialType = ref<'cancelled' | 'substitute' | 'room_change'>('cancelled')
const modalDate = ref('')

const openModal = (schedule: any, type: 'cancelled' | 'substitute' | 'room_change' = 'cancelled') => {
  modalSchedule.value = schedule
  modalInitialType.value = type
  modalDate.value = dailyDate.value
  modalShow.value = true
}

const closeModal = () => {
  modalShow.value = false
  modalSchedule.value = null
}

const onModalSaved = () => {
  closeModal()
  loadDailySchedules()
  if (currentTab.value === 'history') {
    loadHistory()
  }
}

const cancelException = async (exceptionId: number) => {
  const result = await Swal.fire({
    title: 'ยกเลิกรายการนี้?',
    text: 'ตารางเรียนจะกลับมาเป็นปกติ',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'ยืนยันยกเลิก',
    cancelButtonText: 'ปิด'
  })
  
  if (!result.isConfirmed) return
  
  try {
    await api.delete(`/api/academies/${academy.value.id}/schedules/exceptions/${exceptionId}`)
    Swal.fire({
      icon: 'success',
      title: 'ยกเลิกแล้ว',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000
    })
    loadDailySchedules()
    if (currentTab.value === 'history') loadHistory()
  } catch (err: any) {
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: err.data?.message || 'ไม่สามารถทำรายการได้'
    })
  }
}

const escapeHtml = (text: string) =>
  text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;')

const cancelAllForTeacher = async () => {
  if (!dailyTeacherId.value) return
  const schedulesToCancel = dailySchedules.value.filter(s => s.teacher?.id === dailyTeacherId.value && !s.exception)
  if (schedulesToCancel.length === 0) {
    Swal.fire({ icon: 'info', title: 'ไม่มีคาบปกติที่สามารถงดได้ในวันนี้' })
    return
  }
  
  const result = await Swal.fire({
    title: 'งดทุกคาบของครูคนนี้?',
    text: `จำนวน ${schedulesToCancel.length} คาบ ในวันนี้`,
    input: 'text',
    inputPlaceholder: 'เหตุผล (ไม่บังคับ)',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'ยืนยันงดคาบ',
    cancelButtonText: 'ยกเลิก',
  })
  
  if (!result.isConfirmed) return
  
  const reason = result.value || ''
  let successCount = 0
  let failCount = 0
  const failMessages: string[] = []
  
  for (const s of schedulesToCancel) {
    try {
      await api.post(`/api/academies/${academy.value.id}/schedules/exceptions`, {
        class_schedule_id: s.id,
        date: dailyDate.value,
        type: 'cancelled',
        reason
      })
      successCount++
    } catch (err: any) {
      failCount++
      // ข้อความนี้ไปลง Swal `html` — ต้อง escape ชื่อคาบ/ข้อความจาก backend ก่อน
      failMessages.push(escapeHtml(`คาบ ${s.display_title || ''}: ${err.data?.errors?.date?.[0] || err.data?.message || 'เกิดข้อผิดพลาด'}`))
    }
  }
  
  let summary = `บันทึกสำเร็จ ${successCount} คาบ · ไม่สำเร็จ ${failCount} คาบ`
  if (failCount > 0) {
    summary += `<br><br><div class="text-left text-sm text-red-500">${failMessages.join('<br>')}</div>`
  }
  
  Swal.fire({
    icon: failCount === 0 ? 'success' : 'warning',
    title: 'สรุปการทำรายการ',
    html: summary
  })
  
  loadDailySchedules()
}


// --- Tab 2: History ---
const getMonday = (d: Date) => {
  const day = d.getDay()
  const diff = d.getDate() - day + (day === 0 ? -6 : 1)
  return new Date(d.setDate(diff))
}
const mondayDate = getMonday(new Date())
const mondayYMD = `${mondayDate.getFullYear()}-${pad(mondayDate.getMonth() + 1)}-${pad(mondayDate.getDate())}`
const add13Days = new Date(mondayDate)
add13Days.setDate(add13Days.getDate() + 13)
const toYMD = `${add13Days.getFullYear()}-${pad(add13Days.getMonth() + 1)}-${pad(add13Days.getDate())}`

const historyFrom = ref(mondayYMD)
const historyTo = ref(toYMD)
const historyType = ref('')
const historyData = ref<any[]>([])
const loadingHistory = ref(false)
const historyError = ref('')

const loadHistory = async () => {
  if (!academy.value?.id) return
  loadingHistory.value = true
  historyError.value = ''
  try {
    const params: Record<string, any> = { from: historyFrom.value, to: historyTo.value }
    if (historyType.value) params.type = historyType.value
    
    const res = await api.get(`/api/academies/${academy.value.id}/schedules/exceptions`, { params })
    historyData.value = res.data || []
  } catch (err: any) {
    if (err.status === 422 && err.data?.errors?.to) {
      historyError.value = err.data.errors.to[0]
    } else {
      console.error('Load history error', err)
    }
  } finally {
    loadingHistory.value = false
  }
}

watch([historyFrom, historyTo, historyType, currentTab], () => {
  if (currentTab.value === 'history') {
    loadHistory()
  }
})

const groupedHistory = computed(() => {
  const groups: Record<string, any[]> = {}
  for (const item of historyData.value) {
    if (!groups[item.date]) groups[item.date] = []
    groups[item.date].push(item)
  }
  // Sort dates desc
  return Object.keys(groups).sort((a, b) => b.localeCompare(a)).map(date => ({
    date,
    dateStr: new Date(`${date}T00:00:00`).toLocaleDateString('th-TH', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }),
    items: groups[date]
  }))
})


onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (!response.success) return

    academyId.value = response.academy.id
    await fetchMyRole()

    if (!canView.value) {
      router.push(`/academies/${academyName.value}`)
      return
    }

    // ตั้ง academy หลังตรวจสิทธิ์ — template แสดงผลเมื่อ academy มีค่าเท่านั้น
    academy.value = response.academy
    await loadAllTeachers()
    await loadDailySchedules()
  } catch (err) {
    console.error('Failed to load:', err)
  }
})

</script>

<template>
  <div v-if="academy && canView" class="max-w-7xl mx-auto px-3 sm:px-6 py-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 mb-6">
      <NuxtLink :to="`/academies/${academyName}/admin/schedule`" class="w-11 h-11 min-h-[44px] flex items-center justify-center bg-white dark:bg-gray-800 text-gray-500 hover:text-gray-900 dark:hover:text-white rounded-full shadow-sm border border-gray-100 dark:border-gray-700 shrink-0">
        <Icon icon="fluent:arrow-left-24-regular" class="w-6 h-6" />
      </NuxtLink>
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">สอนแทน / งดคาบ</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">บันทึกรายวันที่ — ตารางเรียนหลักไม่เปลี่ยน</p>
      </div>
    </div>
    
    <!-- Tabs -->
    <div class="flex flex-wrap gap-2 mb-6">
      <button type="button" @click="currentTab = 'daily'" class="px-4 min-h-[44px] rounded-lg font-medium transition-colors"
        :class="currentTab === 'daily' ? 'bg-primary-600 text-white shadow' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 shadow-sm'">
        จัดการรายวัน
      </button>
      <button type="button" @click="currentTab = 'history'" class="px-4 min-h-[44px] rounded-lg font-medium transition-colors"
        :class="currentTab === 'history' ? 'bg-primary-600 text-white shadow' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 shadow-sm'">
        รายการที่บันทึกแล้ว
      </button>
    </div>
    
    <!-- Tab 1: Daily -->
    <div v-if="currentTab === 'daily'">
      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 mb-6 flex flex-col sm:flex-row flex-wrap gap-3">
        <!-- Date Selector -->
        <div class="flex items-center">
          <button type="button" @click="prevDay" class="w-11 h-11 min-h-[44px] flex items-center justify-center border border-gray-300 dark:border-gray-600 rounded-l-lg hover:bg-gray-50 dark:hover:bg-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 shrink-0">
            <Icon icon="fluent:chevron-left-24-regular" class="w-5 h-5" />
          </button>
          <input type="date" v-model="dailyDate" class="min-h-[44px] border-y border-gray-300 dark:border-gray-600 px-3 bg-white dark:bg-gray-800 text-gray-900 dark:text-white w-36 text-center focus:outline-none">
          <button type="button" @click="nextDay" class="w-11 h-11 min-h-[44px] flex items-center justify-center border border-gray-300 dark:border-gray-600 rounded-r-lg hover:bg-gray-50 dark:hover:bg-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 shrink-0">
            <Icon icon="fluent:chevron-right-24-regular" class="w-5 h-5" />
          </button>
        </div>
        
        <button type="button" @click="setToday" class="min-h-[44px] px-4 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 shrink-0">
          วันนี้
        </button>
        
        <!-- Teacher Select -->
        <div class="flex items-center gap-2 min-w-[200px] flex-1">
          <div class="flex-1">
            <CommonSearchableSelect
              v-model="dailyTeacherId"
              :options="allTeachers"
              option-value="user_id"
              :option-label="(t: any) => t.member_name || t.user?.name || '-'"
              placeholder="ทุกครู"
              class="min-h-[44px] w-full"
            />
          </div>
          <button v-if="dailyTeacherId" type="button" @click="dailyTeacherId = null" class="min-h-[44px] px-3 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800/50 shrink-0">
            ล้างครู
          </button>
        </div>
        
        <!-- Search text -->
        <input type="text" v-model="dailySearch" placeholder="ค้นหาชื่อคาบ / ห้อง / ครู..." class="min-h-[44px] flex-1 min-w-[200px] px-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
      </div>
      
      <!-- Quick Action if teacher selected -->
      <div v-if="dailyTeacherId && canManage" class="mb-4">
        <button type="button" @click="cancelAllForTeacher" class="min-h-[44px] px-4 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 font-medium w-full sm:w-auto">
          <Icon icon="fluent:dismiss-circle-24-regular" class="inline-block w-5 h-5 mr-1 align-text-bottom" />
          งดทุกคาบของครูคนนี้ในวันนี้
        </button>
      </div>
      
      <!-- Header List -->
      <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
        {{ dailyDateThai }} · {{ filteredDailySchedules.length }} คาบ
      </h2>
      <div v-if="filteredDailySchedules.length === 0 && isWeekend" class="text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/50 p-6 rounded-xl text-center border border-gray-100 dark:border-gray-700">
        วันนี้ไม่มีคาบเรียน
      </div>
      <div v-else-if="filteredDailySchedules.length === 0" class="text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/50 p-6 rounded-xl text-center border border-gray-100 dark:border-gray-700">
        ไม่พบรายการ
      </div>
      
      <!-- Cards List -->
      <div v-else class="flex flex-col gap-3">
        <div v-for="s in filteredDailySchedules" :key="s.id" class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col gap-3 transition-opacity" :class="s.exception?.type === 'cancelled' ? 'opacity-70' : ''">
          
          <div class="flex gap-3">
            <div class="flex-shrink-0 whitespace-nowrap font-bold text-gray-900 dark:text-white">
              {{ s.start_time }} – {{ s.end_time }}
            </div>
            <div class="min-w-0 flex-1 break-words">
              <div class="font-medium text-gray-900 dark:text-white" :class="s.exception?.type === 'cancelled' ? 'line-through text-gray-500 dark:text-gray-400' : ''">
                {{ s.display_title || '-' }}
              </div>
              <div class="text-sm text-gray-500 dark:text-gray-400 break-words mt-1">
                ห้อง {{ s.classroom?.name || '-' }} · ครู {{ s.teacher?.name || '-' }} · {{ s.room || '-' }}
              </div>
              
              <!-- Exception Tags -->
              <div v-if="s.exception" class="mt-2 flex flex-wrap gap-2 items-center">
                <span v-if="s.exception.type === 'cancelled'" class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                  งดคาบ
                </span>
                <span v-else-if="s.exception.type === 'substitute'" class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                  สอนแทน: {{ s.exception.substitute_teacher?.name || '-' }}
                </span>
                <span v-else-if="s.exception.type === 'room_change'" class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                  ย้ายไป {{ s.exception.room }}
                </span>
                
                <span v-if="s.exception.type === 'substitute' && s.exception.room && s.exception.room !== s.room" class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                  ย้ายไป {{ s.exception.room }}
                </span>
                
                <div v-if="s.exception.reason" class="w-full text-xs text-gray-500 dark:text-gray-400 mt-1">
                  เหตุผล: {{ s.exception.reason }}
                </div>
              </div>
            </div>
          </div>
          
          <!-- Actions -->
          <div v-if="canManage" class="flex flex-wrap gap-2 border-t border-gray-100 dark:border-gray-700 pt-3 mt-1">
            <template v-if="!s.exception">
              <button type="button" @click="openModal(s, 'cancelled')" class="min-h-[44px] sm:min-h-0 w-full sm:w-auto sm:py-1.5 px-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 text-sm font-medium transition-colors">
                งดคาบ
              </button>
              <button type="button" @click="openModal(s, 'substitute')" class="min-h-[44px] sm:min-h-0 w-full sm:w-auto sm:py-1.5 px-3 bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/40 text-sm font-medium transition-colors">
                หาครูสอนแทน
              </button>
              <button type="button" @click="openModal(s, 'room_change')" class="min-h-[44px] sm:min-h-0 w-full sm:w-auto sm:py-1.5 px-3 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 text-sm font-medium transition-colors">
                ย้ายห้อง
              </button>
            </template>
            <template v-else>
              <button type="button" @click="openModal(s, s.exception.type)" class="min-h-[44px] sm:min-h-0 w-full sm:w-auto sm:py-1.5 px-3 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 border border-primary-200 dark:border-primary-800 rounded-lg hover:bg-primary-100 dark:hover:bg-primary-900/40 text-sm font-medium transition-colors">
                แก้ไข
              </button>
              <button type="button" @click="cancelException(s.exception.id)" class="min-h-[44px] sm:min-h-0 w-full sm:w-auto sm:py-1.5 px-3 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 text-sm font-medium transition-colors">
                ยกเลิกรายการนี้
              </button>
            </template>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Tab 2: History -->
    <div v-else-if="currentTab === 'history'">
      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 mb-6 flex flex-col sm:flex-row flex-wrap gap-3">
        <div class="flex items-center gap-2 flex-1 min-w-[280px]">
          <input type="date" v-model="historyFrom" class="w-full min-h-[44px] px-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
          <span class="text-gray-500">ถึง</span>
          <input type="date" v-model="historyTo" class="w-full min-h-[44px] px-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
        </div>
        <select v-model="historyType" class="min-h-[44px] flex-1 min-w-[150px] px-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
          <option value="">ทุกประเภท</option>
          <option value="cancelled">งดคาบ</option>
          <option value="substitute">สอนแทน</option>
          <option value="room_change">ย้ายห้อง</option>
        </select>
      </div>
      
      <div v-if="historyError" class="mb-4 text-sm text-red-500 bg-red-50 dark:bg-red-900/20 p-4 rounded-xl border border-red-200 dark:border-red-800">
        {{ historyError }}
      </div>
      
      <div v-if="historyData.length === 0 && !loadingHistory" class="text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/50 p-6 rounded-xl text-center border border-gray-100 dark:border-gray-700">
        ยังไม่มีรายการในช่วงนี้
      </div>
      
      <div v-else class="flex flex-col gap-6">
        <div v-for="group in groupedHistory" :key="group.date">
          <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3">{{ group.dateStr }}</h3>
          <div class="flex flex-col gap-3">
            <div v-for="item in group.items" :key="item.id" class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div class="flex gap-3">
                <div class="flex-shrink-0 whitespace-nowrap font-bold text-gray-900 dark:text-white">
                  {{ item.schedule?.start_time }} – {{ item.schedule?.end_time }}
                </div>
                <div class="min-w-0 flex-1 break-words">
                  <div class="font-medium text-gray-900 dark:text-white">{{ item.schedule?.display_title || '-' }}</div>
                  <div class="text-sm text-gray-500 dark:text-gray-400 mt-1 break-words">
                    ห้อง {{ item.schedule?.classroom?.name || '-' }} · ครูประจำคาบ {{ item.schedule?.teacher?.name || '-' }}
                  </div>
                  
                  <div class="mt-2 flex flex-wrap gap-2 items-center">
                    <span v-if="item.type === 'cancelled'" class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                      งดคาบ
                    </span>
                    <span v-else-if="item.type === 'substitute'" class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                      สอนแทน: {{ item.substitute_teacher?.name || '-' }}
                    </span>
                    <span v-else-if="item.type === 'room_change'" class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                      ย้ายไป {{ item.room }}
                    </span>
                    
                    <span v-if="item.type === 'substitute' && item.room && item.room !== item.schedule?.room" class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                      ย้ายไป {{ item.room }}
                    </span>
                    
                    <div v-if="item.reason" class="w-full text-xs text-gray-500 dark:text-gray-400 mt-1">
                      เหตุผล: {{ item.reason }}
                    </div>
                  </div>
                </div>
              </div>
              
              <div v-if="canManage" class="flex-shrink-0 mt-2 sm:mt-0">
                <button type="button" @click="cancelException(item.id)" class="min-h-[44px] sm:min-h-0 w-full sm:w-auto sm:py-1.5 px-3 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 text-sm font-medium transition-colors">
                  ลบ
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Modal -->
    <SchoolScheduleExceptionModal
      :show="modalShow"
      :academy-id="academy.id"
      :schedule="modalSchedule"
      :date="modalDate"
      :initial-type="modalInitialType"
      @close="closeModal"
      @saved="onModalSaved"
    />
  </div>
</template>
