<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { Icon } from '@iconify/vue'
import { useObjectUrl } from '@vueuse/core'
import Swal from 'sweetalert2'
import { useAuthStore } from '~/stores/auth'
import { useApi } from '~/composables/useApi'
import { VueDatePicker } from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'

const authStore = useAuthStore()
const config = useRuntimeConfig()
const apiBase = config.public.apiBase
const api = useApi()
const router = useRouter()
const route = useRoute()

definePageMeta({
  layout: 'main',
  middleware: 'auth'
})

useHead({
  title: 'สร้างแคมเปญใหม่ | Nuxnan'
})

const isLoading = ref(false)
const isDataLoading = ref(true)

// Choices Data
const academies = ref([])
const courses = ref([])

// Form Selection
const campaignType = ref('advertisement') // 'advertisement' | 'support'
const scopeType = ref('public') // 'public' | 'academy' | 'course'
const selectedAcademy = ref(null)
const selectedCourse = ref(null)
const academySearch = ref('')
const courseSearch = ref('')
let targetSearchTimer
const inheritToAcademy = ref(false)

// Creative Fields (Advertisement)
const title = ref('')
const description = ref('')
const mediaLink = ref('')
const mediaImage = ref(null)
const inputMediaImage = ref(null)
const dragingMedia = ref(false)
const duration = ref(5) // 5, 10, 15, 30, 60
const totalViews = ref(1000) // min: 100

// Custom Budget (Support)
const customBudget = ref(100) // min: 1

// Payment Fields
const payWithWallet = ref(false)
const transferDate = ref(new Date())
const transferTime = ref({ hours: new Date().getHours(), minutes: new Date().getMinutes() })
const inputSlip = ref(null)
const slipImage = ref(null)
const dragingSlip = ref(false)

// Static Options
const viewsOptions = [100, 500, 1000, 2000, 5000, 10000]
const durationOptions = [5, 10, 15, 30, 60]

// Fetch user's academies and courses
async function loadUserData() {
  const userId = authStore.user?.id
  if (!userId) return
  isDataLoading.value = true
  const results = await Promise.allSettled([
    api.get('/api/campaigns/targets/academies'),
    api.get('/api/campaigns/targets/courses')
  ])
  const academyResult = results[0]
  const courseResult = results[1]
  if (academyResult.status === 'fulfilled') academies.value = academyResult.value.academies || []
  if (courseResult.status === 'fulfilled') courses.value = courseResult.value.courses || []
  isDataLoading.value = false
}

async function searchTargets(type, value) {
  const endpoint = type === 'academy' ? 'academies' : 'courses'
  const response = await api.get(`/api/campaigns/targets/${endpoint}`, { params: { q: value } })
  if (type === 'academy') academies.value = response.academies || []
  else courses.value = response.courses || []
}

function queueTargetSearch(type, value) {
  clearTimeout(targetSearchTimer)
  targetSearchTimer = setTimeout(() => searchTargets(type, value).catch(console.error), 300)
}

// Compute dynamic budget
const budgetAmount = computed(() => {
  if (campaignType.value === 'advertisement') {
    return totalViews.value * duration.value * 0.10
  } else {
    return parseFloat(customBudget.value) || 0
  }
})

// Points earned from support
const pointsEarned = computed(() => {
  const rate = 1080 // platform points rate
  return Math.round(budgetAmount.value * rate)
})

// Wallet state
const walletBalance = computed(() => parseFloat(authStore.user?.wallet) || 0)

// Interactive card preview
const previewCampaign = computed(() => {
  return {
    advertiser: authStore.user,
    title: campaignType.value === 'advertisement' ? (title.value || 'ชื่อแคมเปญโฆษณา') : 'สนับสนุนทุนการศึกษา',
    description: description.value || (campaignType.value === 'advertisement' ? 'รายละเอียดของโฆษณาของคุณจะแสดงตรงนี้...' : 'สนับสนุนการเรียนรู้บนแพลตฟอร์ม Nuxnan'),
    media_image: mediaImage.value?.url || null,
    media_link: mediaLink.value || '#',
    campaign_type: campaignType.value,
    scope_type: scopeType.value,
    budget_amount: budgetAmount.value,
    duration: campaignType.value === 'advertisement' ? duration.value : 0,
    total_views: campaignType.value === 'advertisement' ? totalViews.value : 0,
    points_earned: pointsEarned.value
  }
})

// Reset fields when scope or type changes
watch(scopeType, (newScope) => {
  selectedAcademy.value = null
  selectedCourse.value = null
  inheritToAcademy.value = false
})

watch(campaignType, () => {
  title.value = ''
  description.value = ''
  mediaLink.value = ''
  mediaImage.value = null
})

// Media Handlers
const browseInputMedia = () => inputMediaImage.value?.click()
const onInputMediaChange = (e) => {
  if (e.target.files && e.target.files[0]) {
    mediaImage.value = {
      file: e.target.files[0],
      url: URL.createObjectURL(e.target.files[0]),
      type: e.target.files[0].type
    }
  }
}
const onDropMediaFile = (e) => {
  dragingMedia.value = false
  let files = [...e.dataTransfer.items].filter((item) => item.kind === 'file').map((item) => item.getAsFile())
  if (files.length > 0) {
    mediaImage.value = {
      file: files[0],
      url: useObjectUrl(files[0]),
      type: files[0].type
    }
  }
}
const deleteMediaImage = () => {
  mediaImage.value = null
  if(inputMediaImage.value) inputMediaImage.value.value = ''
}

// Slip Handlers
const browseInputSlip = () => inputSlip.value?.click()
const onInputSlipChange = (e) => {
  if (e.target.files && e.target.files[0]) {
    slipImage.value = {
      file: e.target.files[0],
      url: URL.createObjectURL(e.target.files[0]),
    }
  }
}
const onDropSlipFile = (e) => {
  dragingSlip.value = false
  let files = [...e.dataTransfer.items].filter((item) => item.kind === 'file').map((item) => item.getAsFile())
  if (files.length > 0) {
    slipImage.value = {
      file: files[0],
      url: useObjectUrl(files[0]),
    }
  }
}
const deleteSlipImage = () => {
  slipImage.value = null
  if(inputSlip.value) inputSlip.value.value = ''
}

async function submitForm() {
  if (!authStore.isAuthenticated) {
    Swal.fire('ข้อผิดพลาด', 'กรุณาเข้าสู่ระบบก่อนดำเนินการ', 'error')
    return
  }

  // Validations
  if (campaignType.value === 'advertisement') {
    if (!title.value.trim()) {
      Swal.fire('แจ้งเตือน', 'กรุณาระบุหัวข้อโฆษณา', 'warning')
      return
    }
    if (!mediaImage.value) {
      Swal.fire('แจ้งเตือน', 'กรุณาอัปโหลดรูปภาพโฆษณา', 'warning')
      return
    }
    if (totalViews.value < 100) {
      Swal.fire('แจ้งเตือน', 'จำนวนแสดงผลขั้นต่ำ 100 วิว', 'warning')
      return
    }
  } else {
    if (customBudget.value < 1) {
      Swal.fire('แจ้งเตือน', 'งบประมาณสนับสนุนต้องอย่างน้อย 1 บาท', 'warning')
      return
    }
  }

  if (scopeType.value === 'academy' && !selectedAcademy.value) {
    Swal.fire('แจ้งเตือน', 'กรุณาเลือกโรงเรียน/สถาบัน', 'warning')
    return
  }

  if (scopeType.value === 'course' && !selectedCourse.value) {
    Swal.fire('แจ้งเตือน', 'กรุณาเลือกรายวิชา', 'warning')
    return
  }

  if (!payWithWallet.value && !slipImage.value) {
    Swal.fire('แจ้งเตือน', 'กรุณาอัปโหลดสลิปการโอนเงิน หรือชำระผ่าน Wallet', 'warning')
    return
  }

  if (payWithWallet.value && walletBalance.value < budgetAmount.value) {
    Swal.fire('แจ้งเตือน', 'ยอดเงินใน Wallet ไม่เพียงพอ', 'error')
    return
  }

  isLoading.value = true
  const formData = new FormData()
  formData.append('campaign_type', campaignType.value)
  formData.append('scope_type', scopeType.value)
  formData.append('budget_amount', budgetAmount.value.toFixed(2))
  formData.append('payment_method', payWithWallet.value ? 'wallet' : 'slip')

  if (description.value) formData.append('description', description.value)
  if (mediaLink.value) formData.append('media_link', mediaLink.value)

  if (scopeType.value === 'academy') {
    formData.append('academy_id', selectedAcademy.value)
  } else if (scopeType.value === 'course') {
    formData.append('course_id', selectedCourse.value)
    // Automatically grab academy_id if course belongs to an academy
    const courseObj = courses.value.find(c => c.id === selectedCourse.value)
    if (courseObj && courseObj.academy_id) {
      formData.append('academy_id', courseObj.academy_id)
    }
    formData.append('inherit_to_academy', inheritToAcademy.value ? '1' : '0')
  }

  if (campaignType.value === 'advertisement') {
    formData.append('title', title.value)
    formData.append('duration', duration.value)
    formData.append('total_views', totalViews.value)
    if (mediaImage.value) {
      formData.append('media_image', mediaImage.value.file)
    }
  }

  if (!payWithWallet.value) {
    if (slipImage.value) {
      formData.append('slip', slipImage.value.file)
    }
    const year = transferDate.value.getFullYear()
    const month = String(transferDate.value.getMonth() + 1).padStart(2, '0')
    const day = String(transferDate.value.getDate()).padStart(2, '0')
    formData.append('transfer_date', `${year}-${month}-${day}`)

    let hours = 0, minutes = 0
    if (transferTime.value) {
      if (typeof transferTime.value === 'string') {
        const parts = transferTime.value.split(':')
        hours = parts[0]
        minutes = parts[1]
      } else {
        hours = transferTime.value.hours ?? 0
        minutes = transferTime.value.minutes ?? 0
      }
    }
    formData.append('transfer_time', `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`)
  }

  try {
    const res = await $fetch(`${apiBase}/api/campaigns`, {
      method: 'POST',
      body: formData,
      headers: { Authorization: `Bearer ${authStore.token}` }
    })

    if (res.success) {
      Swal.fire({
        title: 'สำเร็จ!',
        text: 'สร้างแคมเปญและบันทึกข้อมูลแล้ว',
        icon: 'success',
        confirmButtonText: 'ตกลง',
        confirmButtonColor: '#10B981'
      }).then(() => {
        router.push('/earn/advertise/manage')
      })
    } else {
      throw new Error(res.message || 'เกิดข้อผิดพลาดจากระบบ')
    }
  } catch (error) {
    Swal.fire('ข้อผิดพลาด', error.message || 'ไม่สามารถทำรายการได้', 'error')
  } finally {
    isLoading.value = false
  }
}

// Prefill scope/target from query params (e.g. from the AdvertiseCtaWidget).
// Set the selection after nextTick so the scopeType reset-watcher (which nulls
// the selection) has already run before the prefilled value is applied.
async function applyQueryPrefill() {
  const scope = route.query.scope
  const academyId = Number(route.query.academy_id)
  const courseId = Number(route.query.course_id)
  if ((scope !== 'academy' && scope !== 'course') || (!academyId && !courseId)) return

  scopeType.value = scope
  await nextTick()

  if (scope === 'academy' && academyId) {
    selectedAcademy.value = academyId
    if (!academies.value.some(ac => ac.id === academyId)) {
      const res = await api.get('/api/campaigns/targets/academies', { params: { id: academyId } }).catch(() => null)
      if (res?.academies?.length) academies.value = [...res.academies, ...academies.value]
    }
  }

  if (scope === 'course' && courseId) {
    selectedCourse.value = courseId
    if (!courses.value.some(c => c.id === courseId)) {
      const res = await api.get('/api/campaigns/targets/courses', { params: { id: courseId } }).catch(() => null)
      if (res?.courses?.length) courses.value = [...res.courses, ...courses.value]
    }
  }
}

watch(() => authStore.user?.id, async (id) => {
  if (!id) return
  await loadUserData()
  await applyQueryPrefill()
}, { immediate: true })
</script>

<template>
  <div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-900 transition-colors duration-300">
    <div class="max-w-7xl mx-auto">
      
      <!-- Banner -->
      <div class="relative rounded-3xl overflow-hidden shadow-xl mb-8 bg-gradient-to-r from-blue-600 to-indigo-700 h-48 flex items-center px-8">
        <div class="absolute inset-0 opacity-20 bg-[url('/storage/images/banner/banner-bg.png')] bg-cover bg-center"></div>
        <div class="relative z-10 flex items-center gap-6">
          <div class="p-4 bg-white/10 backdrop-blur-md rounded-2xl border border-white/20">
            <Icon icon="mdi:bullhorn" class="w-12 h-12 text-white" />
          </div>
          <div>
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">สร้างแคมเปญโฆษณา / สนับสนุน</h1>
            <p class="text-blue-100 text-lg">ขยายการรับรู้ หรือสนับสนุนระบบ/ห้องเรียนผ่านแคมเปญของคุณ</p>
          </div>
        </div>
      </div>

      <div v-if="isDataLoading" class="flex flex-col items-center justify-center py-20 text-gray-500">
        <Icon icon="svg-spinners:ring-resize" class="w-12 h-12 text-blue-600 mb-3" />
        <p>กำลังโหลดข้อมูลผู้ใช้...</p>
      </div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        
        <!-- Left Forms -->
        <div class="lg:col-span-2 space-y-8">
          
          <!-- Campaign Type Selection -->
          <section class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 md:p-8">
            <div class="flex items-center gap-3 mb-6">
              <span class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold">1</span>
              <h2 class="text-xl font-bold text-gray-800 dark:text-white">เลือกประเภทแคมเปญ</h2>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <button 
                @click="campaignType = 'advertisement'" 
                :class="campaignType === 'advertisement' ? 'border-2 border-blue-500 bg-blue-50/50 dark:bg-blue-950/20' : 'border border-gray-200 dark:border-gray-700'"
                class="flex items-start gap-4 p-5 rounded-2xl text-left transition-all hover:shadow-md"
              >
                <div class="p-3 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 rounded-xl">
                  <Icon icon="solar:videocamera-record-bold-duotone" class="w-6 h-6" />
                </div>
                <div>
                  <h3 class="font-bold text-gray-900 dark:text-white mb-1">แคมเปญโฆษณา</h3>
                  <p class="text-xs text-gray-500 dark:text-gray-400">โปรโมทเว็บ/สินค้า มีรูปภาพ วิดีโอ ลิงก์ และคิดเงินต่อจำนวนวิวผู้เข้าชม</p>
                </div>
              </button>

              <button 
                @click="campaignType = 'support'" 
                :class="campaignType === 'support' ? 'border-2 border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/20' : 'border border-gray-200 dark:border-gray-700'"
                class="flex items-start gap-4 p-5 rounded-2xl text-left transition-all hover:shadow-md"
              >
                <div class="p-3 bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300 rounded-xl">
                  <Icon icon="solar:heart-bold-duotone" class="w-6 h-6" />
                </div>
                <div>
                  <h3 class="font-bold text-gray-900 dark:text-white mb-1">แคมเปญสนับสนุน</h3>
                  <p class="text-xs text-gray-500 dark:text-gray-400">สนับสนุนระบบหรือห้องเรียนโดยตรง โดยงบที่ลงจะแปลงเป็นแต้มเพื่อกระจายต่อ</p>
                </div>
              </button>
            </div>
          </section>

          <!-- Campaign Target Area (Scope) Selection -->
          <section class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 md:p-8">
            <div class="flex items-center gap-3 mb-6">
              <span class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">2</span>
              <h2 class="text-xl font-bold text-gray-800 dark:text-white">พื้นที่แสดงผล (Scope)</h2>
            </div>
            
            <div class="grid grid-cols-1 gap-2 mb-6 bg-gray-100 dark:bg-gray-900 p-1.5 rounded-2xl sm:grid-cols-3 sm:gap-3">
              <button 
                @click="scopeType = 'public'" 
                :class="scopeType === 'public' ? 'bg-white dark:bg-gray-800 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-500 hover:text-gray-800 dark:hover:text-white'"
                class="py-3 rounded-xl text-sm font-semibold transition-all"
              >
                สาธารณะ (Public)
              </button>
              <button 
                @click="scopeType = 'academy'" 
                :class="scopeType === 'academy' ? 'bg-white dark:bg-gray-800 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-500 hover:text-gray-800 dark:hover:text-white'"
                class="py-3 rounded-xl text-sm font-semibold transition-all"
              >
                โรงเรียน (Academy)
              </button>
              <button 
                @click="scopeType = 'course'" 
                :class="scopeType === 'course' ? 'bg-white dark:bg-gray-800 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-500 hover:text-gray-800 dark:hover:text-white'"
                class="py-3 rounded-xl text-sm font-semibold transition-all"
              >
                รายวิชา (Course)
              </button>
            </div>

            <!-- Scope Details -->
            <div class="space-y-6">
              <div v-if="scopeType === 'public'" class="p-4 bg-blue-50 dark:bg-blue-900/10 rounded-2xl text-blue-700 dark:text-blue-300 text-sm">
                <Icon icon="solar:info-circle-bold" class="inline w-5 h-5 mr-1" />
                โฆษณา/สนับสนุนนี้จะถูกเผยแพร่ในหน้าหลักของระบบและสตรีมสาธารณะที่ทุกคนเห็นได้
              </div>

              <!-- Academy Select -->
              <div v-if="scopeType === 'academy'">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เลือกโรงเรียน/สถาบัน <span class="text-red-500">*</span></label>
                <input v-model="academySearch" @input="queueTargetSearch('academy', academySearch)" type="search" placeholder="ค้นหาสถาบันที่ต้องการลงโฆษณา" class="mb-2 h-12 w-full rounded-2xl border-gray-300 px-4 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                <select 
                  v-model="selectedAcademy" 
                  class="w-full rounded-2xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 shadow-sm px-4 h-12"
                >
                  <option :value="null" disabled>-- เลือกสถาบันที่ต้องการลงโฆษณา --</option>
                  <option v-for="ac in academies" :key="ac.id" :value="ac.id">{{ ac.name }}</option>
                </select>
                <p v-if="!academies.length" class="text-red-500 text-xs mt-2">ไม่พบสถาบันที่ตรงกับคำค้นหา</p>
                <p class="text-xs text-gray-400 mt-2">โฆษณาจะแสดงหลังผ่านการอนุมัติจากผู้ดูแลสถาบันหรือผู้ดูแลระบบ</p>
              </div>

              <!-- Course Select -->
              <div v-if="scopeType === 'course'" class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เลือกรายวิชา <span class="text-red-500">*</span></label>
                  <input v-model="courseSearch" @input="queueTargetSearch('course', courseSearch)" type="search" placeholder="ค้นหารายวิชาที่ต้องการลงโฆษณา" class="mb-2 h-12 w-full rounded-2xl border-gray-300 px-4 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                  <select 
                    v-model="selectedCourse" 
                    class="w-full rounded-2xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 shadow-sm px-4 h-12"
                  >
                    <option :value="null" disabled>-- เลือกรายวิชาที่ต้องการลงโฆษณา --</option>
                    <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.name || c.title }}</option>
                  </select>
                  <p v-if="!courses.length" class="text-red-500 text-xs mt-2">ไม่พบรายวิชาที่ตรงกับคำค้นหา</p>
                  <p class="text-xs text-gray-400 mt-2">โฆษณาจะแสดงหลังผ่านการอนุมัติจากผู้ดูแลรายวิชาหรือผู้ดูแลระบบ</p>
                </div>

                <!-- Inherit Toggle -->
                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/30 rounded-2xl">
                  <div>
                    <span class="block text-sm font-semibold text-gray-800 dark:text-white">แสดงโฆษณาในโรงเรียนด้วย (Inherit to Academy)</span>
                    <span class="text-xs text-gray-500">หากเปิดโฆษณานี้จะถูกแชร์ขึ้นไปแสดงที่หน้าโรงเรียนที่รายวิชานี้สังกัดอยู่ด้วย</span>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" v-model="inheritToAcademy" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                  </label>
                </div>
              </div>
            </div>
          </section>

          <!-- Campaign Details -->
          <section class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 md:p-8">
            <div class="flex items-center gap-3 mb-6">
              <span class="w-8 h-8 rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold">3</span>
              <h2 class="text-xl font-bold text-gray-800 dark:text-white">รายละเอียดแคมเปญ</h2>
            </div>

            <!-- ADVERTISEMENT FORM -->
            <div v-if="campaignType === 'advertisement'" class="space-y-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หัวข้อโฆษณา / ชื่อสินค้า <span class="text-red-500">*</span></label>
                <input v-model="title" type="text" placeholder="เช่น โปรโมชั่นพิเศษต้อนรับเปิดเทอม..." class="w-full rounded-2xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 shadow-sm px-4 h-12" required />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รายละเอียด (คำโปรยสั้นๆ)</label>
                <textarea v-model="description" rows="3" placeholder="อธิบายจุดเด่นของแคมเปญหรือบริการของคุณ..." class="w-full rounded-2xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 shadow-sm p-4"></textarea>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รูปภาพหรือวิดีโอโฆษณา <span class="text-red-500">*</span></label>
                
                <div v-if="!mediaImage" 
                     @click="browseInputMedia"
                     @dragover.prevent="dragingMedia = true"
                     @dragleave.prevent="dragingMedia = false"
                     @drop.prevent="onDropMediaFile"
                     :class="{'border-blue-500 bg-blue-50 dark:bg-blue-950/20': dragingMedia}"
                     class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-3xl p-8 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer text-center group"
                >
                  <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                    <Icon icon="solar:cloud-upload-bold-duotone" class="w-8 h-8" />
                  </div>
                  <p class="text-gray-900 dark:text-white font-medium mb-1">คลิกเพื่อเลือกไฟล์ หรือลากมาวางตรงนี้</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">รองรับ JPG, PNG, GIF, MP4, WEBM (ขนาดไม่เกิน 20MB)</p>
                  <input type="file" ref="inputMediaImage" class="hidden" accept="image/*,video/*" @change="onInputMediaChange">
                </div>

                <div v-else class="relative rounded-2xl overflow-hidden shadow-md group">
                  <button @click.stop="deleteMediaImage" class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center absolute top-3 right-3 bg-red-500 hover:bg-red-600 text-white p-2.5 rounded-full shadow-lg z-20 transition-all hover:scale-110">
                    <Icon icon="mdi:trash-can" class="w-5 h-5" />
                  </button>
                  <div v-if="mediaImage.type?.startsWith('video/')" class="aspect-video bg-black flex items-center justify-center">
                    <video :src="mediaImage.url" controls class="max-h-[350px] w-full"></video>
                  </div>
                  <img v-else :src="mediaImage.url" class="w-full object-contain max-h-[350px] bg-gray-100 dark:bg-gray-900" />
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ลิงก์เว็บไซต์ปลายทาง (ถ้ามี)</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
                    <Icon icon="solar:link-bold" class="w-5 h-5" />
                  </div>
                  <input v-model="mediaLink" type="url" placeholder="https://example.com" class="pl-11 w-full rounded-2xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 shadow-sm h-12" />
                </div>
              </div>

              <div class="grid md:grid-cols-2 gap-6 pt-4 border-t border-gray-100 dark:border-gray-700">
                <!-- Quantity Views -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนการแสดงผล (Views)</label>
                  <div class="relative">
                    <input type="number" v-model.number="totalViews" class="w-full rounded-2xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 shadow-sm h-12 pr-12" placeholder="1000" min="100">
                    <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-gray-400 text-sm">ครั้ง</div>
                  </div>
                  <div class="mt-2 flex flex-wrap gap-2">
                    <button 
                      v-for="opt in viewsOptions" 
                      :key="opt" 
                      @click="totalViews = opt" 
                      :class="totalViews === opt ? 'bg-amber-100 border-amber-300 text-amber-700 dark:bg-amber-950 dark:border-amber-750 dark:text-amber-300' : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400'"
                      class="min-h-[44px] sm:min-h-0 text-xs px-2.5 py-1.5 rounded-lg border hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                      {{ opt.toLocaleString() }}
                    </button>
                  </div>
                </div>

                <!-- Duration per View -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เวลาต่อการชม 1 ครั้ง</label>
                  <select v-model.number="duration" class="w-full rounded-2xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 shadow-sm h-12 px-4">
                    <option v-for="opt in durationOptions" :key="opt" :value="opt">{{ opt }} วินาที</option>
                  </select>
                  <p class="text-xs text-gray-400 mt-2">ผู้ชมจะได้เหรียญตอบแทนตามความยาวของเวลาที่รับชม</p>
                </div>
              </div>
            </div>

            <!-- SUPPORT FORM -->
            <div v-else class="space-y-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">งบประมาณสนับสนุน (บาท) <span class="text-red-500">*</span></label>
                <div class="relative">
                  <input type="number" v-model.number="customBudget" class="w-full rounded-2xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 shadow-sm h-12 pr-12" placeholder="100.00" min="1" step="1">
                  <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-gray-400 text-sm font-bold">THB</div>
                </div>
                <div class="mt-3 p-4 bg-teal-50 dark:bg-teal-950/20 text-teal-800 dark:text-teal-300 rounded-2xl text-sm flex items-center gap-3">
                  <Icon icon="solar:database-bold-duotone" class="w-6 h-6 flex-shrink-0" />
                  <div>
                    งบประมาณนี้จะถูกเปลี่ยนเป็นแต้มสนับสนุนทั้งหมด 
                    <span class="font-bold underline text-lg">{{ pointsEarned.toLocaleString() }} PP</span>
                    เพื่อแจกจ่ายเป็นรางวัลแก่ผู้เรียน
                  </div>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ข้อความ / รายละเอียดสนับสนุน (ถ้ามี)</label>
                <textarea v-model="description" rows="3" placeholder="เขียนข้อความให้กำลังใจ หรือความจำนงในการสนับสนุน..." class="w-full rounded-2xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 shadow-sm p-4"></textarea>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ลิงก์เว็บไซต์ประชาสัมพันธ์สปอนเซอร์ (ถ้ามี)</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
                    <Icon icon="solar:link-bold" class="w-5 h-5" />
                  </div>
                  <input v-model="mediaLink" type="url" placeholder="https://mycompany.com" class="pl-11 w-full rounded-2xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 shadow-sm h-12" />
                </div>
              </div>
            </div>
          </section>

          <!-- Payment Section -->
          <section class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 md:p-8">
            <div class="flex items-center gap-3 mb-6">
              <span class="w-8 h-8 rounded-full bg-teal-100 dark:bg-teal-900/40 text-teal-600 dark:text-teal-400 flex items-center justify-center font-bold">4</span>
              <h2 class="text-xl font-bold text-gray-800 dark:text-white">ช่องทางการชำระเงิน</h2>
            </div>

            <!-- Payment Toggle -->
            <div class="flex p-1 bg-gray-100 dark:bg-gray-900 rounded-2xl mb-6">
              <button 
                @click="payWithWallet = false" 
                :class="!payWithWallet ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 hover:text-gray-800 dark:hover:text-white'"
                class="flex-1 py-3 rounded-xl text-sm font-semibold transition-all flex items-center justify-center gap-2"
              >
                <Icon icon="solar:card-transfer-bold-duotone" class="w-5 h-5" />
                โอนเงินธนาคาร
              </button>
              <button 
                @click="payWithWallet = true" 
                :class="payWithWallet ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 hover:text-gray-800 dark:hover:text-white'"
                class="flex-1 py-3 rounded-xl text-sm font-semibold transition-all flex items-center justify-center gap-2"
              >
                <Icon icon="solar:wallet-money-bold-duotone" class="w-5 h-5" />
                ชำระผ่าน Wallet
              </button>
            </div>

            <!-- Wallet Method -->
            <div v-if="payWithWallet" class="p-6 bg-teal-50 dark:bg-teal-950/20 border border-teal-200 dark:border-teal-900 rounded-2xl text-center">
              <Icon icon="solar:wallet-bold-duotone" class="w-12 h-12 text-teal-600 dark:text-teal-400 mx-auto mb-3" />
              <h3 class="font-bold text-teal-950 dark:text-teal-100 mb-1">ยอดเงินคงเหลือในกระเป๋า</h3>
              <p class="text-3xl font-extrabold text-teal-600 dark:text-teal-400 mb-3">{{ walletBalance.toLocaleString(undefined, {minimumFractionDigits: 2}) }} ฿</p>
              <p v-if="walletBalance < budgetAmount" class="text-red-500 text-sm">ยอดเงินสะสมของคุณไม่พอชำระเงิน กรุณาเลือกโอนธนาคารแทน</p>
              <p v-else class="text-xs text-teal-600 dark:text-teal-400">ระบบจะตัดเงินจากกระเป๋าและเปิดใช้งานโฆษณาทันทีหลังผ่านการรีวิว</p>
            </div>

            <!-- Slip Method -->
            <div v-else class="space-y-6">
              <div class="bg-gray-50 dark:bg-gray-700/30 p-5 rounded-2xl border border-gray-200 dark:border-gray-600">
                <p class="font-bold text-gray-900 dark:text-white mb-2">บัญชีสถาบันการชำระเงิน</p>
                <div class="text-sm space-y-1.5 text-gray-600 dark:text-gray-300">
                  <p>ธนาคาร: <span class="font-bold text-gray-800 dark:text-white">ธนาคารกสิกรไทย (K-Bank)</span></p>
                  <p>เลขที่บัญชี: <span class="font-mono font-bold text-lg select-all text-blue-600 dark:text-blue-400">123-4-56789-0</span></p>
                  <p>ชื่อบัญชี: <span class="font-semibold text-gray-800 dark:text-white">บจก. นุกแนน เลิร์นนิ่ง คอมมูนิตี้</span></p>
                </div>
              </div>

              <div class="grid md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">วันที่โอนเงิน</label>
                  <ClientOnly>
                    <VueDatePicker v-model="transferDate" :format="'dd/MM/yyyy'" :enable-time-picker="false" auto-apply />
                  </ClientOnly>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เวลาที่โอนเงิน</label>
                  <ClientOnly>
                    <VueDatePicker v-model="transferTime" time-picker :format="'HH:mm'" auto-apply>
                      <template #input-icon>
                        <Icon icon="solar:clock-circle-bold-duotone" class="w-5 h-5 text-gray-400 ml-2" />
                      </template>
                    </VueDatePicker>
                  </ClientOnly>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">อัปโหลดรูปภาพสลิปโอนเงิน <span class="text-red-500">*</span></label>
                <div v-if="!slipImage" 
                     @click="browseInputSlip"
                     @dragover.prevent="dragingSlip = true"
                     @dragleave.prevent="dragingSlip = false"
                     @drop.prevent="onDropSlipFile"
                     :class="{'border-teal-500 bg-teal-50 dark:bg-teal-950/20': dragingSlip}"
                     class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-2xl p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer text-center group"
                >
                  <Icon icon="solar:camera-add-bold-duotone" class="w-8 h-8 text-gray-400 group-hover:text-teal-500 mx-auto mb-2" />
                  <p class="text-sm font-medium text-gray-700 dark:text-gray-300">คลิกเพื่ออัปโหลดรูปภาพสลิป</p>
                  <input type="file" ref="inputSlip" class="hidden" accept="image/*" @change="onInputSlipChange">
                </div>
                
                <div v-else class="relative rounded-2xl overflow-hidden border border-gray-200 dark:border-gray-700 inline-block group">
                  <button @click.stop="deleteSlipImage" class="absolute top-2 right-2 bg-red-500 text-white p-1.5 rounded-full shadow-md z-10 transition-all hover:scale-105">
                    <Icon icon="solar:close-circle-bold" />
                  </button>
                  <img :src="slipImage.url" class="h-40 w-auto object-contain bg-gray-100 dark:bg-gray-900" />
                </div>
              </div>
            </div>
          </section>

        </div>

        <!-- Right Side sticky summary & preview -->
        <div class="lg:col-span-1">
          <div class="sticky top-24 space-y-6">
            
            <!-- Realtime preview card -->
            <div class="bg-gray-100 dark:bg-gray-800/40 rounded-3xl p-5 border border-dashed border-gray-300 dark:border-gray-700">
              <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 text-center">ตัวอย่างการแสดงผล</p>
              
              <!-- Advertisement Preview -->
              <div v-if="campaignType === 'advertisement'" class="bg-white dark:bg-gray-800 rounded-2xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
                <div class="aspect-video bg-gray-100 dark:bg-gray-900 relative">
                  <img v-if="mediaImage?.url" :src="mediaImage.url" class="w-full h-full object-cover" />
                  <div v-else class="w-full h-full flex flex-col items-center justify-center text-gray-400 p-4 text-center">
                    <Icon icon="solar:gallery-bold-duotone" class="w-12 h-12 mb-2" />
                    <p class="text-xs">อัปโหลดสิ่อภาพหรือวิดีโอ</p>
                  </div>
                  <!-- Duration Badge -->
                  <div class="absolute bottom-3 right-3 bg-black/60 backdrop-blur-sm text-white text-xs px-2.5 py-1 rounded-full flex items-center gap-1.5">
                    <Icon icon="solar:clock-circle-bold" class="w-3.5 h-3.5" />
                    <span>{{ duration }}s</span>
                  </div>
                  <!-- Scope Badge -->
                  <div class="absolute top-3 left-3 bg-blue-600 text-white text-[10px] uppercase font-extrabold px-2.5 py-1 rounded-full shadow-sm">
                    {{ scopeType }}
                  </div>
                </div>

                <div class="p-4">
                  <div class="flex items-center gap-2.5 mb-3">
                    <img :src="authStore.user?.profile_photo_url || '/storage/images/avatar/default.png'" class="w-7 h-7 rounded-full border" />
                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 truncate max-w-[120px]">{{ authStore.user?.name }}</span>
                    <span class="text-[10px] px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-500 rounded-full ml-auto">โฆษณา</span>
                  </div>
                  <h4 class="font-bold text-gray-900 dark:text-white text-sm line-clamp-1 mb-1">{{ title || 'หัวข้อโฆษณาของคุณ' }}</h4>
                  <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2">{{ description || 'คำบรรยายเพิ่มเติมเกี่ยวกับโฆษณา...' }}</p>
                </div>
              </div>

              <!-- Support Preview -->
              <div v-else class="bg-gradient-to-br from-indigo-500/10 to-purple-500/10 dark:from-indigo-950/20 dark:to-purple-950/20 rounded-2xl p-5 border border-indigo-200/50 dark:border-indigo-900/50 text-center">
                <div class="w-12 h-12 rounded-full bg-indigo-500 text-white flex items-center justify-center mx-auto mb-3 shadow-md shadow-indigo-500/20">
                  <Icon icon="solar:heart-bold" class="w-6 h-6" />
                </div>
                <div class="inline-block bg-indigo-600 text-white text-[10px] uppercase font-extrabold px-2.5 py-1 rounded-full mb-3 shadow-sm">
                  SUPPORT ({{ scopeType }})
                </div>
                <h4 class="font-bold text-gray-900 dark:text-white text-sm mb-1.5">ผู้สนับสนุน: {{ authStore.user?.name }}</h4>
                <p class="text-xs text-gray-600 dark:text-gray-400 line-clamp-3 px-2 mb-4">" {{ description || 'ขอสนับสนุนและมอบแต้มรางวัลเรียนรู้ให้กับระบบนี้...' }} "</p>
                <div class="py-2.5 px-4 bg-white dark:bg-gray-800 rounded-xl inline-flex flex-col border shadow-sm">
                  <span class="text-[10px] text-gray-400 font-bold uppercase">แต้มแจกรางวัลสะสม</span>
                  <span class="text-base font-extrabold text-teal-600 dark:text-teal-400">{{ pointsEarned.toLocaleString() }} PP</span>
                </div>
              </div>
            </div>

            <!-- Payment Summary -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
              <div class="p-6">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4">สรุปรายละเอียดงบประมาณ</h3>
                <div class="space-y-3.5 text-sm">
                  <div class="flex justify-between text-gray-600 dark:text-gray-400">
                    <span>ประเภท</span>
                    <span class="font-semibold text-gray-800 dark:text-white">{{ campaignType === 'advertisement' ? 'แคมเปญโฆษณา' : 'แคมเปญสนับสนุน' }}</span>
                  </div>
                  <div class="flex justify-between text-gray-600 dark:text-gray-400">
                    <span>ขอบเขตพื้นที่</span>
                    <span class="font-semibold text-gray-800 dark:text-white">{{ scopeType === 'public' ? 'สาธารณะ' : (scopeType === 'academy' ? 'เฉพาะในสถาบัน' : 'เฉพาะในรายวิชา') }}</span>
                  </div>
                  <div v-if="campaignType === 'advertisement'" class="flex justify-between text-gray-600 dark:text-gray-400">
                    <span>จำนวนแสดงผล</span>
                    <span class="font-semibold text-gray-800 dark:text-white">{{ totalViews.toLocaleString() }} วิว</span>
                  </div>
                  <div v-if="campaignType === 'advertisement'" class="flex justify-between text-gray-600 dark:text-gray-400">
                    <span>เวลาต่อวิว</span>
                    <span class="font-semibold text-gray-800 dark:text-white">{{ duration }} วินาที</span>
                  </div>
                  <div v-else class="flex justify-between text-gray-600 dark:text-gray-400">
                    <span>แต้มสะสมที่จะได้รับ</span>
                    <span class="font-semibold text-teal-600">{{ pointsEarned.toLocaleString() }} PP</span>
                  </div>

                  <div class="h-px bg-gray-100 dark:bg-gray-700 my-3"></div>

                  <div class="flex justify-between items-end">
                    <span class="font-bold text-gray-900 dark:text-white">ยอดสุทธิ</span>
                    <div class="text-right">
                      <div class="text-3xl font-extrabold text-blue-600 dark:text-blue-400">
                        {{ budgetAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}
                      </div>
                      <div class="text-[10px] text-gray-400 font-bold">บาท (THB)</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Action buttons -->
              <div class="p-5 bg-gray-50 dark:bg-gray-900/40 border-t border-gray-100 dark:border-gray-700 space-y-3">
                <button 
                  @click="submitForm" 
                  :disabled="isLoading" 
                  class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-2xl shadow-lg hover:shadow-blue-500/20 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                >
                  <Icon v-if="isLoading" icon="svg-spinners:ring-resize" class="w-5 h-5" />
                  <span v-else>ยืนยันสร้างแคมเปญ</span>
                </button>
                <button 
                  @click="router.back()" 
                  class="min-h-[44px] sm:min-h-0 w-full text-center text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white text-sm font-semibold py-2 transition-colors"
                >
                  ยกเลิก
                </button>
              </div>
            </div>

          </div>
        </div>

      </div>

    </div>
  </div>
</template>
