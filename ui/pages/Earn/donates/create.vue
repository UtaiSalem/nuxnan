<script setup lang="ts">
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

definePageMeta({
  layout: 'main',
})

useSeoMeta({
  title: 'สนับสนุนเว็บไซต์ - Plearnd',
})

import { useAuthStore } from '~/stores/auth'

const config = useRuntimeConfig()
const authStore = useAuthStore()

// Form state
const isLoading = ref(false)
const isLoadingDonor = ref(false)
const personalCode = ref('')
const donor = ref<any>(null)
const totalMoneySupport = ref(10)
const moneyIndexSelected = ref(0)
const slipImage = ref<{ file: File; url: string } | null>(null)
const now = new Date()
const transferDate = ref(now.toISOString().split('T')[0])
const transferTime = ref(now.toTimeString().slice(0, 5))
const dragging = ref(false)
const inputSlip = ref<HTMLInputElement | null>(null)
const errorMessage = ref('')
const isManualOverride = ref(false)

// Form Errors
const errors = ref({
  personalCode: '',
  slip: '',
  dateTime: '',
})

// Success modal
const showSuccessModal = ref(false)

// Money options
const generateOptions = (min: number, max: number, step: number) => {
  const options = []
  for (let i = min; i <= max; i += step) {
    options.push(i)
  }
  return options
}

const tempDonate2Digit = generateOptions(10, 100, 10)
const tempDonate3Digit = generateOptions(150, 500, 50)
const tempDonate4Digit = generateOptions(600, 1000, 100)
const totalMoneySupportOptions = [...tempDonate2Digit, ...tempDonate3Digit, ...tempDonate4Digit]

// Quick Select Options
const quickSelectOptions = [50, 100, 300, 500, 1000]

// Time selection
const hours = Array.from({ length: 24 }, (_, i) => i.toString().padStart(2, '0'))
const minutes = Array.from({ length: 60 }, (_, i) => i.toString().padStart(2, '0'))
const selectedHour = ref(now.getHours().toString().padStart(2, '0'))
const selectedMinute = ref(now.getMinutes().toString().padStart(2, '0'))

// Update transferTime when selectors change
watch([selectedHour, selectedMinute], ([h, m]) => {
  transferTime.value = `${h}:${m}`
}, { immediate: true })

// Computed points (1080x multiplier)
const estimatedPoints = computed(() => {
  return totalMoneySupport.value * 1080
})

// Handle money selection
const handleSelectedMoneyQuantity = () => {
  totalMoneySupport.value = totalMoneySupportOptions[moneyIndexSelected.value]
}

const selectQuickAmount = (amount: number) => {
  totalMoneySupport.value = amount
  const index = totalMoneySupportOptions.indexOf(amount)
  if (index !== -1) {
    moneyIndexSelected.value = index
  }
}

// Browse file
const browseInputSlip = () => {
  inputSlip.value?.click()
  errors.value.slip = '' // Clear error on interaction
}

// Validate File
const validateFile = (file: File): boolean => {
  const validTypes = ['image/jpeg', 'image/png', 'image/gif']
  if (!validTypes.includes(file.type)) {
    errors.value.slip = 'กรุณาอัพโหลดไฟล์รูปภาพเท่านั้น (JPG, PNG, GIF)'
    return false
  }
  if (file.size > 4 * 1024 * 1024) {
    errors.value.slip = 'ขนาดไฟล์ต้องไม่เกิน 4MB'
    return false
  }
  return true
}

// Handle file input
const onInputSlipChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  const file = target.files?.[0]
  if (file) {
    if (validateFile(file)) {
       slipImage.value = {
        file: file,
        url: URL.createObjectURL(file),
      }
      errors.value.slip = ''
    } else {
      target.value = '' // Reset input
    }
  }
}

// Handle drag and drop
const onDropFile = (e: DragEvent) => {
  dragging.value = false
  const items = e.dataTransfer?.items
  if (!items) return
  
  const files = [...items]
    .filter((item) => item.kind === 'file')
    .map((item) => item.getAsFile())
  
  if (files[0]) {
    if (validateFile(files[0])) {
      slipImage.value = {
        file: files[0],
        url: URL.createObjectURL(files[0]),
      }
      errors.value.slip = ''
    }
  }
}

// Delete image
const deleteImage = () => {
  slipImage.value = null
  if (inputSlip.value) inputSlip.value.value = ''
}

// Search donor by personal code
const handlePersonalcodeInput = async () => {
  isManualOverride.value = true
  errors.value.personalCode = ''
  if (personalCode.value.length === 8) {
    isLoadingDonor.value = true
    try {
      const response = await $fetch<any>(`${config.public.apiBase}/api/supports/donates/donor/${personalCode.value}`)
      if (response.success) {
        donor.value = response.donor
        errors.value.personalCode = ''
      } else {
         donor.value = null
         // If API returns success:false but no error thrown
         errors.value.personalCode = 'ไม่พบสมาชิกที่ระบุ'
      }
    } catch (error) {
      console.error('Donor not found:', error)
       donor.value = null
       errors.value.personalCode = 'ไม่พบสมาชิกที่ระบุ'
    } finally {
      isLoadingDonor.value = false
    }
  } else {
    donor.value = null
  }
}

// Clear donor
const handleEmptyDonor = () => {
  donor.value = null
  personalCode.value = ''
  errors.value.personalCode = ''
  isManualOverride.value = true
}

// Restore current user donor
const handleUseCurrentUser = async () => {
  if (authStore.user) {
    // If we have personal code, fetch full profile to get avatar
    if (authStore.user.personal_code) {
      isLoadingDonor.value = true
      try {
        const response = await $fetch<any>(`${config.public.apiBase}/api/supports/donates/donor/${authStore.user.personal_code}`)
        if (response.success) {
          donor.value = response.donor
        } else {
          donor.value = authStore.user
        }
      } catch (e) {
        donor.value = authStore.user
      } finally {
        isLoadingDonor.value = false
      }
    } else {
      donor.value = authStore.user
    }
    
    personalCode.value = authStore.user.personal_code || ''
    errors.value.personalCode = ''
    isManualOverride.value = false
  }
}

// Submit form
const submitForm = async () => {
  // Reset errors
  errors.value = { personalCode: '', slip: '', dateTime: '' }
  errorMessage.value = ''

  let hasError = false

  if (!slipImage.value) {
    errors.value.slip = 'กรุณาอัพโหลดสลิปการทำรายการ'
    hasError = true
  }
  
  if (!transferDate.value || !transferTime.value) {
    errors.value.dateTime = 'กรุณาระบุวันที่และเวลาที่โอน'
    hasError = true
  }

  if (hasError) return

  isLoading.value = true
  
  try {
    const formData = new FormData()
    // Use authStore.user.id for user_id (Creator) if logged in, otherwise empty (Controller defaults to System)
    formData.append('user_id', authStore.user?.id || '')
    formData.append('user_id', authStore.user?.id || '')
    formData.append('donor_id', donor.value?.id || '')
    formData.append('donor_name', donor.value?.name || donor.value?.username || 'ไม่ระบุนาม')
    formData.append('amounts', String(totalMoneySupport.value))
    formData.append('transfer_date', transferDate.value)
    formData.append('transfer_time', transferTime.value)
    formData.append('slip', slipImage.value!.file)

    const response = await $fetch<any>(`${config.public.apiBase}/api/supports/donates`, {
      method: 'POST',
      body: formData,
    })

    if (response.success) {
      showSuccessModal.value = true
      // Reset form
      slipImage.value = null
      transferDate.value = ''
      transferTime.value = ''
      // Reset donor to current user if available, otherwise null
      if (authStore.user) {
        donor.value = authStore.user
        personalCode.value = authStore.user.personal_code
      } else {
        donor.value = null
        personalCode.value = ''
      }
      moneyIndexSelected.value = 0
      totalMoneySupport.value = 10
      if (inputSlip.value) inputSlip.value.value = ''
    } else {
      Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด',
        text: response.message || 'เกิดข้อผิดพลาด',
        confirmButtonText: 'ตกลง',
        confirmButtonColor: '#d33',
      })
    }
  } catch (error: any) {
    console.error('Submit error:', error)
    Swal.fire({
      icon: 'error',
      title: 'ล้มเหลว',
      text: error.data?.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล',
      confirmButtonText: 'ตกลง',
      confirmButtonColor: '#d33',
    })
  } finally {
    isLoading.value = false
  }
}

// Get today's date in YYYY-MM-DD format
const today = new Date().toISOString().split('T')[0]

// Initialize donor with current user using watch to handle hydration/async fetch
watch(() => authStore.user, async (user) => {
  if (user && !donor.value && !isManualOverride.value) {
    // Fetch full donor profile to ensure we get the avatar field from UserResource
    if (user.personal_code) {
      isLoadingDonor.value = true
      try {
        const response = await $fetch<any>(`${config.public.apiBase}/api/supports/donates/donor/${user.personal_code}`)
        if (response.success) {
          donor.value = response.donor
        } else {
          donor.value = user
        }
      } catch (e) {
        donor.value = user
      } finally {
        isLoadingDonor.value = false
      }
    } else {
      donor.value = user
    }
    personalCode.value = user.personal_code || ''
  }
}, { immediate: true }) // Run immediately in case user is already loaded
</script>

<template>
  <div class="py-6 px-4">
    <!-- Header Banner -->
    <div class="mb-6 bg-gradient-to-r from-purple-600 via-pink-500 to-orange-400 rounded-2xl shadow-xl overflow-hidden">
      <div class="px-6 py-8 flex flex-col md:flex-row items-center justify-between">
        <div class="flex items-center gap-4 mb-4 md:mb-0">
          <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center">
            <Icon icon="mdi:hand-coin" class="w-10 h-10 text-white" />
          </div>
          <div>
            <h1 class="text-2xl md:text-3xl font-bold text-white">สนับสนุน เว็บไซต์ เพลินด์</h1>
            <p class="text-white/80 mt-1">ร่วมสนับสนุนเพื่อพัฒนาแพลตฟอร์มการเรียนรู้</p>
          </div>
        </div>
        <div class="flex gap-3">
          <NuxtLink to="/earn/donates" class="flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg text-white transition-colors">
            <Icon icon="mdi:arrow-left" class="w-5 h-5" />
            กลับหน้ารวม
          </NuxtLink>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-2xl mx-auto">
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
        <!-- Card Header -->
        <div class="bg-gradient-to-r from-teal-500 to-cyan-500 px-6 py-4">
          <h2 class="text-xl font-bold text-white flex items-center gap-2">
            <Icon icon="mdi:heart" class="w-6 h-6" />
            เพลินด์ขอขอบคุณผู้ให้การสนับสนุนทุกท่าน
          </h2>
          <p class="text-white/80 text-sm mt-1">การสนับสนุนจากท่านจะถูกจัดสรรให้กับสมาชิกผู้ใช้งาน</p>
        </div>

        <!-- Card Body -->
        <div class="p-6">
          <!-- Instructions -->
          <div class="mb-6 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
            <h3 class="font-semibold text-amber-800 dark:text-amber-400 mb-2 flex items-center gap-2">
              <Icon icon="mdi:information" class="w-5 h-5" />
              ขั้นตอนการสนับสนุน
            </h3>
            <ol class="text-amber-700 dark:text-amber-300 text-sm space-y-1 list-decimal list-inside">
              <li>โอนเงินเข้าบัญชี <span class="font-bold">677-7724-60-5</span> ธนาคารกรุงไทย</li>
              <li>ชื่อบัญชี <span class="font-bold">นายอุทัย สาเหล็ม</span></li>
              <li>กรอกข้อมูลให้ครบถ้วน</li>
              <li>บันทึกข้อมูล</li>
            </ol>
          </div>

          <form @submit.prevent="submitForm" class="space-y-6">
            <!-- Donor Section -->
            <div v-if="!donor" class="space-y-2">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                รหัสประจำตัวสมาชิก (ถ้ามี)
              </label>
              <div class="relative">
                <input
                  v-model="personalCode"
                  @input="handlePersonalcodeInput"
                  type="text"
                  maxlength="8"
                  placeholder="กรอกรหัส 8 หลัก"
                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-all"
                  :class="{ 'border-red-500 focus:ring-red-500': errors.personalCode }"
                />
                <Icon v-if="isLoadingDonor" icon="mdi:loading" class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-purple-500 animate-spin" />
              </div>
              
              <!-- Use My Info Button (only if logged in and in manual mode) -->
              <div v-if="authStore.user" class="flex justify-end mt-1">
                <button 
                  type="button"
                  @click="handleUseCurrentUser"
                  class="text-xs text-purple-600 dark:text-purple-400 hover:underline flex items-center gap-1"
                >
                  <Icon icon="mdi:account-circle" class="w-4 h-4" />
                  ใช้ข้อมูลของฉัน
                </button>
              </div>

              <p v-if="errors.personalCode" class="text-xs text-red-500 mt-1 flex items-center gap-1">
                 <Icon icon="mdi:alert-circle" class="w-4 h-4" /> {{ errors.personalCode }}
              </p>
              <p v-else class="text-xs text-gray-500 mt-2 flex items-center gap-1">
                <Icon icon="mdi:information-outline" class="w-3 h-3" />
                หากไม่ระบุรหัสสมาชิก ระบบจะบันทึกในชื่อ "ไม่ประสงค์ออกนาม"
              </p>
            </div>

            <!-- Donor Card -->
            <div v-if="isLoadingDonor && !donor" class="animate-pulse bg-gray-100 dark:bg-gray-700 rounded-xl p-4">
              <div class="flex items-center gap-4">
                <div class="w-16 h-16 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                <div class="flex-1 space-y-2">
                  <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-3/4"></div>
                  <div class="h-3 bg-gray-300 dark:bg-gray-600 rounded w-1/2"></div>
                </div>
              </div>
            </div>

            <div v-if="donor" class="relative bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 border border-purple-200 dark:border-purple-800 rounded-xl p-4 animate-fade-in-up">
              <button
                @click="handleEmptyDonor"
                type="button"
                class="absolute top-2 right-2 p-2 bg-white dark:bg-gray-700 rounded-full shadow hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                title="ไม่ประสงค์ออกนาม"
              >
                <Icon icon="mdi:close" class="w-4 h-4 text-red-500" />
              </button>
              
              <div class="flex items-center gap-4">
                <img
                  :src="donor.avatar || donor.profile_photo_url || '/storages/plearnd-logo.png'"
                  :alt="donor.name || donor.username"
                  class="w-16 h-16 rounded-full border-4 border-purple-500 shadow-lg object-cover"
                />
                <div>
                  <h4 class="text-lg font-bold text-gray-900 dark:text-white">{{ donor.name || donor.username }}</h4>
                  <p class="text-sm text-gray-600 dark:text-gray-400">
                    รหัสประจำตัว: <span class="font-semibold">{{ donor.personal_code }}</span>
                  </p>
                  <button 
                    @click="handleEmptyDonor"
                    type="button"
                    class="text-xs text-red-500 hover:text-red-700 hover:underline mt-1 flex items-center gap-1"
                  >
                    <Icon icon="mdi:incognito" class="w-3 h-3" />
                    ไม่ประสงค์ออกนาม
                  </button>
                </div>
              </div>
            </div>

            <!-- Money Amount -->
            <div class="space-y-3">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                จำนวนเงิน (บาท)
              </label>
              
              <!-- Quick Select Chips -->
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="amount in quickSelectOptions"
                  :key="amount"
                  type="button"
                  @click="selectQuickAmount(amount)"
                  :class="[
                    'px-3 py-1.5 rounded-lg text-sm font-medium transition-all border',
                    totalMoneySupport === amount
                      ? 'bg-purple-500 border-purple-500 text-white shadow-md'
                      : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:border-purple-400'
                  ]"
                >
                  {{ amount.toLocaleString() }}
                </button>
                 <button
                   v-if="!quickSelectOptions.includes(totalMoneySupport)"
                   type="button"
                   class="px-3 py-1.5 rounded-lg text-sm font-medium border bg-purple-500 border-purple-500 text-white shadow-md"
                >
                   {{ totalMoneySupport.toLocaleString() }}
                </button>
              </div>

              <select
                v-model="moneyIndexSelected"
                @change="handleSelectedMoneyQuantity"
                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent dark:bg-gray-700 dark:text-white text-lg font-semibold text-center transition-all"
              >
                <option v-for="(amount, idx) in totalMoneySupportOptions" :key="idx" :value="idx">
                  {{ amount.toLocaleString() }} บาท
                </option>
              </select>
              
              <!-- Points Preview -->
              <div class="flex items-center justify-center gap-2 py-3 bg-purple-50 dark:bg-purple-900/20 rounded-xl border border-purple-100 dark:border-purple-800/30">
                <Icon icon="mdi:star-circle" class="w-8 h-8 text-amber-500" />
                <span class="text-lg font-bold text-purple-600 dark:text-purple-400">
                  {{ estimatedPoints.toLocaleString() }} แต้ม
                </span>
              </div>
            </div>

            <!-- Slip Upload -->
            <div class="space-y-2">
              <div class="flex justify-between items-center">
                 <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  สลิปการโอนเงิน <span class="text-red-500">*</span>
                </label>
                <span v-if="errors.slip" class="text-xs text-red-500 flex items-center gap-1">
                   <Icon icon="mdi:alert-circle" class="w-3 h-3" /> {{ errors.slip }}
                </span>
              </div>
              
              <div v-if="slipImage" class="relative group">
                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors rounded-xl pointer-events-none"></div>
                <img
                  :src="slipImage.url"
                  alt="Slip"
                  class="w-full max-h-80 object-contain rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900"
                />
                <button
                  @click="deleteImage"
                  type="button"
                  class="absolute top-2 right-2 p-2 bg-white/90 dark:bg-gray-800/90 hover:bg-red-500 hover:text-white text-gray-700 rounded-full shadow-lg transition-all backdrop-blur-sm"
                >
                  <Icon icon="mdi:delete" class="w-5 h-5" />
                </button>
              </div>
              
              <div
                v-else
                @dragover.prevent="dragging = true"
                @dragleave.prevent="dragging = false"
                @drop.prevent="onDropFile"
                :class="[
                  'border-2 border-dashed rounded-xl p-8 text-center transition-all cursor-pointer relative overflow-hidden',
                  dragging
                    ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20 scale-[1.01]'
                    : 'border-gray-300 dark:border-gray-600 hover:border-purple-400 hover:bg-gray-50 dark:hover:bg-gray-700/50',
                  errors.slip ? 'border-red-300 bg-red-50 dark:border-red-800 dark:bg-red-900/10' : ''
                ]"
                @click="browseInputSlip"
              >
                <Icon icon="mdi:cloud-upload" class="w-12 h-12 mx-auto text-gray-400 mb-3" />
                <p class="text-gray-600 dark:text-gray-400 mb-2">
                  <span class="font-semibold text-purple-600 dark:text-purple-400">คลิกเพื่อเลือกไฟล์</span>
                  หรือลากไฟล์มาวางที่นี่
                </p>
                <p class="text-xs text-gray-500">PNG, JPG, GIF (ไม่เกิน 4MB)</p>
                <input
                  ref="inputSlip"
                  type="file"
                  accept="image/png, image/jpeg, image/gif"
                  class="hidden"
                  @change="onInputSlipChange"
                />
              </div>
            </div>

            <!-- Date & Time -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                 <div class="flex justify-between">
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    วันที่โอน <span class="text-red-500">*</span>
                  </label>
                   <span v-if="errors.dateTime" class="text-xs text-red-500 md:hidden">{{ errors.dateTime }}</span>
                 </div>
                <input
                  v-model="transferDate"
                  type="date"
                  :max="today"
                  class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent dark:bg-gray-700 dark:text-white transition-all"
                   :class="{ 'border-red-500': errors.dateTime }"
                />
              </div>
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  เวลาที่โอน <span class="text-red-500">*</span>
                </label>
                <div class="flex gap-2">
                  <div class="relative flex-1">
                    <select
                      v-model="selectedHour"
                      class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent dark:bg-gray-700 dark:text-white appearance-none"
                    >
                      <option v-for="h in hours" :key="h" :value="h">{{ h }}</option>
                    </select>
                    <Icon icon="mdi:chevron-down" class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500 pointer-events-none" />
                    <span class="absolute -top-6 left-0 text-xs text-gray-500">นาฬิกา</span>
                  </div>
                  <span class="flex items-center text-2xl font-bold text-gray-400">:</span>
                  <div class="relative flex-1">
                    <select
                      v-model="selectedMinute"
                      class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent dark:bg-gray-700 dark:text-white appearance-none"
                    >
                      <option v-for="m in minutes" :key="m" :value="m">{{ m }}</option>
                    </select>
                    <Icon icon="mdi:chevron-down" class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500 pointer-events-none" />
                    <span class="absolute -top-6 left-0 text-xs text-gray-500">นาที</span>
                  </div>
                </div>
              </div>
            </div>
             <p v-if="errors.dateTime" class="text-xs text-red-500 -mt-2 hidden md:block">
                 {{ errors.dateTime }}
             </p>


            <!-- Warning -->
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-3">
              <p class="text-sm text-red-600 dark:text-red-400 flex items-start gap-2">
                <Icon icon="mdi:alert" class="w-5 h-5 flex-shrink-0 mt-0.5" />
                <span>
                    กรุณาตรวจสอบวันที่และเวลาให้ตรงกับใบสลิปโอนเงิน <br class="hidden sm:block">
                    (หากเกิดข้อผิดพลาดจะไม่สามารถคืนเงินได้)
                </span>
              </p>
            </div>

            <!-- Buttons -->
            <div class="flex flex-col sm:flex-row gap-3 pt-4">
              <button
                type="submit"
                :disabled="isLoading"
                class="flex-1 flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-teal-500 to-cyan-500 hover:from-teal-600 hover:to-cyan-600 disabled:from-gray-400 disabled:to-gray-500 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all"
              >
                <Icon v-if="isLoading" icon="mdi:loading" class="w-5 h-5 animate-spin" />
                <Icon v-else icon="mdi:content-save" class="w-5 h-5" />
                บันทึกข้อมูล
              </button>
              <NuxtLink
                to="/earn/donates"
                class="flex items-center justify-center gap-2 px-6 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-all"
              >
                <Icon icon="mdi:close" class="w-5 h-5" />
                ยกเลิก
              </NuxtLink>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Success Modal -->
    <Teleport to="body">
      <div
        v-if="showSuccessModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm transition-all"
      >
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-8 text-center animate-bounce-in">
          <div class="w-20 h-20 mx-auto mb-4 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
            <Icon icon="mdi:check-circle" class="w-12 h-12 text-green-500" />
          </div>
          <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">เสร็จสมบูรณ์!</h3>
          <p class="text-gray-600 dark:text-gray-400 mb-6">
            ขอบคุณที่สนับสนุนเว็บไซต์เพลินด์ ทางทีมงานจะตรวจสอบข้อมูลและทำการอัพเดทข้อมูลให้เร็วที่สุด
          </p>
          <div class="flex gap-3">
            <button
              @click="showSuccessModal = false"
              class="flex-1 px-4 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-colors"
            >
              สนับสนุนเพิ่ม
            </button>
            <NuxtLink
              to="/earn/donates"
              class="flex-1 px-4 py-3 bg-gradient-to-r from-teal-500 to-cyan-500 hover:from-teal-600 hover:to-cyan-600 text-white font-semibold rounded-xl transition-all text-center"
              @click="showSuccessModal = false"
            >
              กลับหน้ารวม
            </NuxtLink>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
/* Keyframes for simple animations if Tailwind config doesn't have them */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translate3d(0, 20px, 0);
  }
  to {
    opacity: 1;
    transform: translate3d(0, 0, 0);
  }
}

.animate-fade-in-up {
  animation: fadeInUp 0.4s ease-out forwards;
}

@keyframes bounceIn {
  0% {
    opacity: 0;
    transform: scale(0.3);
  }
  50% {
    opacity: 1;
    transform: scale(1.05);
  }
  70% {
    transform: scale(0.9);
  }
  100% {
    transform: scale(1);
  }
}

.animate-bounce-in {
  animation: bounceIn 0.5s cubic-bezier(0.215, 0.610, 0.355, 1.000);
}
</style>
