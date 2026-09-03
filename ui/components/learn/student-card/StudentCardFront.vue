<script setup lang="ts">
import { Icon } from '@iconify/vue'
/**
 * StudentCardFront - Reusable student card front face component
 * Used in: student card view, print, and list pages
 */
import QRCodeVue3 from 'qrcode-vue3'

interface StudentInfo {
  id: number
  student_number?: string
  qr_content?: string
  qr_url?: string
  title_name?: string
  first_name_thai?: string
  last_name_thai?: string
  full_name_thai?: string
  first_name_english?: string
  national_id?: string
  class_level?: string | number
  class_section?: string | number
  birth_date?: string
  card_expiry_date?: string
  profile_image?: string
}

interface Props {
  student: StudentInfo
  academyName?: string
  academyLogo?: string
  academyAddress?: string
  editable?: boolean
  showQrCode?: boolean
  cardId?: string
}

const props = withDefaults(defineProps<Props>(), {
  academyName: 'โรงเรียน',
  academyLogo: '/images/placeholders/academy-logo.svg',
  academyAddress: '',
  editable: false,
  showQrCode: true,
  cardId: '',
})

const emit = defineEmits<{
  (e: 'edit'): void
  (e: 'upload-photo'): void
}>()

// Computed: QR Value
const qrValue = computed(() => {
  return props.student.qr_content || props.student.student_number || ''
})

// Computed: Full name
const displayFullNameThai = computed(() => {
  if (props.student.full_name_thai?.trim()) {
    return props.student.full_name_thai.trim()
  }
  const parts = [
    props.student.title_name?.trim(),
    props.student.first_name_thai?.trim(),
    props.student.last_name_thai?.trim()
  ].filter(Boolean)
  return parts.join(' ')
})

// Computed: English prefix
const englishPrefix = computed(() => {
  const prefix = props.student.title_name
  if (!prefix) return ''
  if (prefix === 'เด็กหญิง' || prefix === 'นางสาว') return 'Ms.'
  if (prefix === 'เด็กชาย' || prefix === 'นาย') return 'Mr.'
  return ''
})

// Computed: Education level text
const levelText = computed(() => {
  const level = Number(props.student.class_level)
  return level < 4 ? 'มัธยมศึกษาตอนต้น' : 'มัธยมศึกษาตอนปลาย'
})

const levelTextEn = computed(() => {
  const level = Number(props.student.class_level)
  return level < 4 ? 'LOWER SECONDARY' : 'UPPER SECONDARY'
})

// Computed: Format national ID
const formattedIdNumber = computed(() => {
  const id = props.student.national_id
  if (!id) return ''
  const idStr = String(id).replace(/\D/g, '')
  if (idStr.length !== 13) return id
  return idStr.replace(/(\d)(\d{4})(\d{5})(\d{2})(\d{1})/, '$1-$2-$3-$4-$5')
})

// Computed: Profile image URL
const studentImageUrl = computed(() => {
  return props.student.profile_image_url || null
})

// Computed: Format dates
const formatDateTh = (dateStr?: string) => {
  if (!dateStr) return '-'
  try {
    return new Date(dateStr).toLocaleDateString('th-TH', {
      day: '2-digit', month: '2-digit', year: 'numeric'
    })
  } catch { return '-' }
}

const formatDateEn = (dateStr?: string) => {
  if (!dateStr) return '-'
  try {
    return new Date(dateStr).toLocaleDateString('en-US', {
      month: '2-digit', day: '2-digit', year: 'numeric'
    })
  } catch { return '-' }
}
</script>

<template>
  <div
    :id="cardId"
    class="student-card-front w-full max-w-[640px] aspect-[1.95/1.20] relative overflow-hidden rounded-2xl shadow-lg border border-gray-300 bg-white font-sarabun"
  >
    <!-- Top Section (School Header) -->
    <div
      class="h-[20%] bg-gradient-to-br from-gray-50 to-gray-200 relative"
      style="background: linear-gradient(135deg, transparent 40%, #4a90e2 0%)"
    >
      <!-- School Logo -->
      <div class="absolute -left-2 md:-left-3 -top-[16px] sm:-top-[28px] md:-top-[22px] w-[22%] aspect-square rounded-full border-1 border-white flex items-center justify-center">
        <img :src="academyLogo" alt="School Logo" class="w-[56%] h-[56%] object-cover rounded-full" />
      </div>

      <!-- School Information -->
      <div class="absolute left-[16%] top-[10%] sm:top-2">
        <div class="text-[3.8vw] md:text-[28px] font-semibold md:font-bold text-gray-800">
          {{ academyName }}
        </div>
        <div v-if="academyAddress" class="text-[2.4vw] md:text-sm -mt-0.5 sm:-mt-2 md:-mt-1 text-gray-800">
          {{ academyAddress }}
        </div>
      </div>

      <!-- Edit button (editable mode) -->
      <button
        v-if="editable"
        @click="emit('edit')"
        class="absolute z-50 top-0 right-2 text-white bg-gray-200/60 p-2 text-center rounded-full shadow-md cursor-pointer hover:bg-gray-300/80 transition-colors"
      >
        <Icon icon="dashicons:edit" width="20" height="20" />
      </button>

      <!-- Card Label -->
      <div class="absolute z-10 top-6 md:top-[52px] right-2 text-white bg-blue-700 px-2 py-1 text-end rounded-md">
        <div class="text-[1.8vw] sm:text-[14px] font-semibold">บัตรประจำตัวนักเรียน</div>
        <div class="text-[1.4vw] sm:text-[10px] opacity-90">STUDENT CARD</div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="flex p-[3%] gap-[3%] h-[80%]">
      <!-- Photo Section -->
      <div class="w-[30%] h-[75%] rounded-xl overflow-hidden flex-shrink-0 bg-gray-200">
        <img
          v-if="studentImageUrl"
          :src="studentImageUrl"
          alt="Student Photo"
          class="w-full h-full object-fill"
        />
        <div v-else class="w-full h-full flex items-center justify-center">
          <Icon icon="fluent:person-24-regular" class="w-12 h-12 text-gray-400" />
        </div>
      </div>

      <!-- Information Section -->
      <div class="flex-1 pt-0.5 relative">
        <!-- Name (Thai) -->
        <div class="flex items-baseline">
          <span class="w-[25%] text-[2.2vw] sm:text-md md:text-lg font-medium text-gray-700">ชื่อ</span>
          <span class="mx-[1%] text-[2.2vw] sm:text-sm md:text-lg text-gray-700">:</span>
          <span class="flex-1 text-[2.4vw] sm:text-sm md:text-lg font-semibold text-gray-800">
            {{ displayFullNameThai }}
          </span>
        </div>
        <div class="flex items-baseline -mt-1">
          <span class="w-[25%] text-[2vw] sm:text-xs font-normal text-gray-600">Name</span>
        </div>
        <!-- Name (English) -->
        <div class="flex items-baseline -mt-3 sm:-mt-4 md:-mt-5">
          <span class="w-[25%]"></span>
          <span class="mx-[1%] text-transparent">:</span>
          <span class="flex-1 text-[1.8vw] sm:text-sm font-normal text-gray-800">
            {{ student.first_name_english ? `${englishPrefix} ${student.first_name_english}` : '' }}
          </span>
        </div>

        <!-- Student Code -->
        <div class="flex items-baseline mt-0.5">
          <span class="w-[25%] text-[2.2vw] sm:text-sm md:text-lg font-medium text-gray-700">รหัสประจำตัว</span>
          <span class="mx-[1%] text-[2.2vw] sm:text-sm md:text-lg text-gray-700">:</span>
          <span class="flex-1 text-[2.4vw] sm:text-sm md:text-lg font-semibold text-gray-800">
            {{ student.student_number }}
          </span>
        </div>
        <div class="flex items-baseline -mt-1">
          <span class="w-[25%] text-[2vw] sm:text-xs font-normal text-gray-600">Student ID</span>
        </div>

        <!-- National ID -->
        <div class="flex items-baseline mt-0.5">
          <span class="w-[25%] text-[2.2vw] sm:text-sm font-medium text-gray-700">เลขบัตรประชาชน</span>
          <span class="mx-[1%] text-[2.2vw] sm:text-sm md:text-lg text-gray-700">:</span>
          <span class="flex-1 text-[2.4vw] sm:text-sm md:text-lg font-semibold text-gray-800">
            {{ formattedIdNumber }}
          </span>
        </div>
        <div class="flex items-baseline -mt-1">
          <span class="w-[25%] text-[2vw] sm:text-xs font-normal text-gray-600">ID Card No.</span>
        </div>

        <!-- Level -->
        <div class="flex items-baseline mt-0.5">
          <span class="w-[25%] text-[2.2vw] sm:text-sm md:text-lg font-medium text-gray-700">ระดับ</span>
          <span class="mx-[1%] text-[2.2vw] sm:text-sm md:text-lg text-gray-700">:</span>
          <span class="flex-1 text-[2.4vw] sm:text-sm md:text-lg font-semibold text-gray-800">
            {{ levelText }}
          </span>
        </div>
        <div class="flex items-baseline -mt-1.5 sm:-mt-2 md:-mt-2.5">
          <span class="w-[25%] text-[2vw] sm:text-xs font-normal text-gray-600">Level</span>
          <span class="mx-[1%] text-transparent">:</span>
          <span class="flex-1 text-[1.4vw] sm:text-[12px] font-normal text-gray-800">
            {{ levelTextEn }}
          </span>
        </div>

        <!-- Date of Birth -->
        <div class="flex items-baseline mt-0.5">
          <span class="w-[25%] text-[2.2vw] sm:text-sm md:text-lg font-medium text-gray-700">วันเกิด</span>
          <span class="mx-[1%] text-[2.2vw] sm:text-sm md:text-lg text-gray-700">:</span>
          <span class="flex-1 text-[2.4vw] sm:text-sm md:text-lg font-semibold text-gray-800">
            {{ formatDateTh(student.birth_date) }}
          </span>
        </div>
        <div class="flex items-baseline -mt-1.5 sm:-mt-2 md:-mt-2.5">
          <span class="w-[25%] text-[2vw] sm:text-xs font-normal text-gray-600">Date of Birth</span>
          <span class="mx-[1%] text-transparent">:</span>
          <span class="flex-1 text-[1.4vw] sm:text-[12px] font-normal text-gray-800">
            {{ formatDateEn(student.birth_date) }}
          </span>
        </div>

        <!-- Expiry Date -->
        <div class="flex items-baseline mt-0.5">
          <span class="w-[25%] text-[2.2vw] sm:text-sm md:text-lg font-medium text-gray-700">วันหมดอายุ</span>
          <span class="mx-[1%] text-[2.2vw] sm:text-sm md:text-lg text-gray-700">:</span>
          <span class="flex-1 text-[2.4vw] sm:text-sm md:text-lg font-semibold text-gray-800">
            {{ formatDateTh(student.card_expiry_date) }}
          </span>
        </div>
        <div class="flex items-baseline -mt-1.5 sm:-mt-2 md:-mt-2.5">
          <span class="w-[25%] text-[2vw] sm:text-xs font-normal text-gray-600">Expiry Date</span>
          <span class="mx-[1%] text-transparent">:</span>
          <span class="flex-1 text-[1.4vw] sm:text-[12px] font-normal text-gray-800">
            {{ formatDateEn(student.card_expiry_date) }}
          </span>
        </div>
      </div>

      <!-- QR Code -->
      <div v-if="showQrCode" class="absolute bottom-[5%] right-[3%] w-[15%] aspect-square">
        <div class="w-full h-full bg-white flex items-center justify-center rounded-lg shadow-md">
          <QRCodeVue3
            :value="qrValue"
            :cornersSquareOptions="{ type: 'extra-rounded', color: '#000' }"
            :dotsOptions="{ type: 'dots', color: '#000' }"
            :cornersDotOptions="{ type: 'square', color: '#000' }"
          />
        </div>
      </div>

      <!-- Bottom Bar -->
      <div
        class="absolute bottom-0 left-0 w-full h-[2.5%] rounded-b-2xl"
        style="background: linear-gradient(135deg, #4a90e2 72%, transparent 0%)"
      ></div>
    </div>
  </div>
</template>

<style scoped>
.font-sarabun {
  font-family: 'Sarabun', 'Noto Sans Thai', sans-serif;
}
</style>
