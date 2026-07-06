<script setup lang="ts">
/**
 * Student Profile - Read-Only Profile View Components
 * 
 * Beautiful, reusable display components for student profile data.
 * These are view-only cards (unlike HomeVisit components which are editable).
 */

// ====== ProfileHeader ======
// Shows student photo, name, class, status badge
</script>

<script lang="ts">
import { defineComponent, computed, type PropType } from 'vue'
import type { StudentProfile, ClassroomInfo, AcademyInfo } from '../../../composables/useStudentProfile'

// ============================================================================
// ProfileHeader Component
// ============================================================================
export const ProfileHeader = defineComponent({
  name: 'ProfileHeader',
  props: {
    student: { type: Object as PropType<StudentProfile>, required: true },
    classroom: { type: Object as PropType<ClassroomInfo | null>, default: null },
    academy: { type: Object as PropType<AcademyInfo | null>, default: null },
    accessLevel: { type: String, default: '' },
    accessLevelLabel: { type: String, default: '' },
  },
  setup(props) {
    const fullNameTh = computed(() => {
      return [props.student.title_prefix_th, props.student.first_name_th, props.student.last_name_th]
        .filter(Boolean).join(' ')
    })
    const fullNameEn = computed(() => {
      return [props.student.title_prefix_en, props.student.first_name_en, props.student.last_name_en]
        .filter(Boolean).join(' ')
    })
    const classDisplay = computed(() => {
      if (!props.student.class_level) return '-'
      return `ม.${props.student.class_level}/${props.student.class_section || '-'}`
    })
    const userInitial = computed(() => {
      return props.student.first_name_th?.charAt(0) || props.student.first_name_en?.charAt(0) || 'S'
    })
    const statusConfig = computed(() => {
      const map: Record<string, { label: string; color: string }> = {
        active: { label: 'กำลังศึกษา', color: 'bg-green-100 text-green-800' },
        inactive: { label: 'ไม่เปิดใช้', color: 'bg-gray-100 text-gray-800' },
        graduated: { label: 'สำเร็จการศึกษา', color: 'bg-blue-100 text-blue-800' },
        transferred: { label: 'ย้าย', color: 'bg-yellow-100 text-yellow-800' },
      }
      return map[props.student.status] || { label: props.student.status, color: 'bg-gray-100 text-gray-800' }
    })
    const genderIcon = computed(() => {
      if (props.student.gender === 1) return '♂'
      if (props.student.gender === 0) return '♀'
      return ''
    })
    const profileImageUrl = computed(() => {
      return props.student.profile_image_url || null
    })

    return { fullNameTh, fullNameEn, classDisplay, userInitial, statusConfig, genderIcon, profileImageUrl }
  },
  template: `
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
      <!-- Cover Gradient -->
      <div class="h-32 sm:h-40 bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-700 relative">
        <div class="absolute inset-0 opacity-10">
          <div class="absolute top-4 right-8 w-20 h-20 bg-white rounded-full"></div>
          <div class="absolute bottom-2 left-12 w-16 h-16 bg-white rounded-full"></div>
        </div>
        <!-- Access Level Badge -->
        <div v-if="accessLevelLabel" class="absolute top-3 right-3 sm:top-4 sm:right-4">
          <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-white/20 text-white backdrop-blur-sm">
            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            {{ accessLevelLabel }}
          </span>
        </div>
        <!-- Academy Name -->
        <div v-if="academy" class="absolute top-3 left-3 sm:top-4 sm:left-4">
          <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-white/20 text-white backdrop-blur-sm">
            🏫 {{ academy.name }}
          </span>
        </div>
      </div>

      <!-- Profile Content -->
      <div class="relative px-4 sm:px-6 pb-6">
        <!-- Avatar -->
        <div class="flex flex-col sm:flex-row sm:items-end -mt-16 sm:-mt-20 mb-4">
          <div class="relative flex-shrink-0">
            <div class="w-28 h-28 sm:w-36 sm:h-36 rounded-2xl border-4 border-white shadow-lg overflow-hidden bg-gradient-to-br from-blue-100 to-indigo-100">
              <img v-if="profileImageUrl" :src="profileImageUrl" :alt="fullNameTh" class="w-full h-full object-cover" @error="$event.target.style.display='none'">
              <div v-if="!profileImageUrl" class="w-full h-full flex items-center justify-center">
                <span class="text-4xl sm:text-5xl font-bold text-blue-600">{{ userInitial }}</span>
              </div>
            </div>
            <!-- Gender Badge -->
            <span v-if="genderIcon" class="absolute -bottom-1 -right-1 w-8 h-8 rounded-full flex items-center justify-center text-lg shadow-md"
                  :class="student.gender === 1 ? 'bg-blue-100 text-blue-600' : 'bg-pink-100 text-pink-600'">
              {{ genderIcon }}
            </span>
          </div>

          <!-- Name & Info -->
          <div class="mt-4 sm:mt-0 sm:ml-5 sm:pb-2 flex-1 min-w-0">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
              <div class="min-w-0">
                <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 truncate">{{ fullNameTh }}</h1>
                <p v-if="fullNameEn" class="text-sm text-gray-500 truncate">{{ fullNameEn }}</p>
              </div>
              <span :class="['inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold whitespace-nowrap', statusConfig.color]">
                {{ statusConfig.label }}
              </span>
            </div>
          </div>
        </div>

        <!-- Quick Info Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-2">
          <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-3 text-center">
            <p class="text-xs text-blue-600 font-medium">รหัสนักเรียน</p>
            <p class="text-sm sm:text-base font-bold text-blue-900 mt-1">{{ student.student_id || '-' }}</p>
          </div>
          <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-3 text-center">
            <p class="text-xs text-purple-600 font-medium">ชั้นเรียน</p>
            <p class="text-sm sm:text-base font-bold text-purple-900 mt-1">{{ classDisplay }}</p>
          </div>
          <div class="bg-gradient-to-br from-amber-50 to-amber-100 rounded-xl p-3 text-center">
            <p class="text-xs text-amber-600 font-medium">ชื่อเล่น</p>
            <p class="text-sm sm:text-base font-bold text-amber-900 mt-1">{{ student.nickname || '-' }}</p>
          </div>
          <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 rounded-xl p-3 text-center">
            <p class="text-xs text-emerald-600 font-medium">อายุ</p>
            <p class="text-sm sm:text-base font-bold text-emerald-900 mt-1">{{ student.age ? student.age + ' ปี' : '-' }}</p>
          </div>
        </div>
      </div>
    </div>
  `
})

// ============================================================================
// PersonalInfoCard Component
// ============================================================================
export const PersonalInfoCard = defineComponent({
  name: 'PersonalInfoCard',
  props: {
    student: { type: Object as PropType<StudentProfile>, required: true },
  },
  setup(props) {
    const formatDate = (dateStr: string | null) => {
      if (!dateStr) return '-'
      try {
        return new Intl.DateTimeFormat('th-TH', { year: 'numeric', month: 'long', day: 'numeric' }).format(new Date(dateStr))
      } catch { return dateStr }
    }
    const formatCitizenId = (id: string | null) => {
      if (!id) return '-'
      const digits = id.replace(/\D/g, '')
      if (digits.length !== 13) return id
      return `${digits[0]}-${digits.substring(1,5)}-${digits.substring(5,10)}-${digits.substring(10,12)}-${digits[12]}`
    }
    const genderText = computed(() => {
      if (props.student.gender === 1) return 'ชาย'
      if (props.student.gender === 0) return 'หญิง'
      return 'ไม่ระบุ'
    })
    return { formatDate, formatCitizenId, genderText }
  },
  template: `
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-gradient-to-r from-emerald-500 to-teal-600 px-5 py-4">
        <div class="flex items-center">
          <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </div>
          <h3 class="ml-3 text-lg font-semibold text-white">ข้อมูลส่วนตัว</h3>
        </div>
      </div>
      <div class="p-5">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
          <div>
            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">เลขประจำตัวประชาชน</dt>
            <dd class="mt-1 text-sm font-medium text-gray-900">{{ formatCitizenId(student.citizen_id) }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">เพศ</dt>
            <dd class="mt-1 text-sm font-medium text-gray-900">{{ genderText }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">วันเกิด</dt>
            <dd class="mt-1 text-sm font-medium text-gray-900">{{ formatDate(student.date_of_birth) }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">สัญชาติ</dt>
            <dd class="mt-1 text-sm font-medium text-gray-900">{{ student.nationality || '-' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">ศาสนา</dt>
            <dd class="mt-1 text-sm font-medium text-gray-900">{{ student.religion || '-' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">กรุ๊ปเลือด</dt>
            <dd class="mt-1 text-sm font-medium text-gray-900">{{ student.blood_type || '-' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">วันที่เข้าเรียน</dt>
            <dd class="mt-1 text-sm font-medium text-gray-900">{{ formatDate(student.enrollment_date) }}</dd>
          </div>
        </dl>
      </div>
    </div>
  `
})

// ============================================================================
// AcademicInfoCard Component
// ============================================================================
export const AcademicInfoViewCard = defineComponent({
  name: 'AcademicInfoViewCard',
  props: {
    academicInfo: { type: Array as PropType<Array<any>>, default: () => [] },
  },
  setup(props) {
    const educationLevelText = (level: number | null) => {
      const map: Record<number, string> = { 1: 'ประถมศึกษา', 2: 'มัธยมศึกษา', 3: 'อุดมศึกษา' }
      return level ? (map[level] || '-') : '-'
    }
    const statusText = (status: string | null) => {
      const map: Record<string, string> = {
        studying: 'กำลังศึกษา', graduated: 'สำเร็จการศึกษา',
        transferred: 'ย้ายสถานศึกษา', dropped: 'พ้นสภาพ'
      }
      return status ? (map[status] || status) : '-'
    }
    const statusColor = (status: string | null) => {
      const map: Record<string, string> = {
        studying: 'bg-green-100 text-green-800', graduated: 'bg-blue-100 text-blue-800',
        transferred: 'bg-yellow-100 text-yellow-800', dropped: 'bg-red-100 text-red-800'
      }
      return status ? (map[status] || 'bg-gray-100 text-gray-800') : 'bg-gray-100 text-gray-800'
    }
    return { educationLevelText, statusText, statusColor }
  },
  template: `
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-5 py-4">
        <div class="flex items-center">
          <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
          </div>
          <h3 class="ml-3 text-lg font-semibold text-white">ประวัติการศึกษา</h3>
        </div>
      </div>
      <div class="p-5">
        <div v-if="academicInfo.length === 0" class="text-center py-8">
          <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
          </svg>
          <p class="text-sm text-gray-500">ยังไม่มีข้อมูลประวัติการศึกษา</p>
        </div>
        <div v-else class="space-y-4">
          <div v-for="info in academicInfo" :key="info.id"
               :class="['rounded-xl border p-4 transition-all', info.is_current ? 'border-blue-200 bg-blue-50/50 ring-1 ring-blue-200' : 'border-gray-200 bg-white']">
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2">
                <span v-if="info.is_current" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-600 text-white">
                  ปัจจุบัน
                </span>
                <span class="text-sm font-semibold text-gray-900">ปีการศึกษา {{ info.academic_year || '-' }}</span>
              </div>
              <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium', statusColor(info.study_status)]">
                {{ statusText(info.study_status) }}
              </span>
            </div>
            <dl class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm">
              <div>
                <dt class="text-gray-500 text-xs">ระดับชั้น</dt>
                <dd class="font-medium text-gray-900">{{ info.current_grade ? 'ม.' + info.current_grade : '-' }}</dd>
              </div>
              <div>
                <dt class="text-gray-500 text-xs">ห้อง</dt>
                <dd class="font-medium text-gray-900">{{ info.current_class || '-' }}</dd>
              </div>
              <div>
                <dt class="text-gray-500 text-xs">ระดับการศึกษา</dt>
                <dd class="font-medium text-gray-900">{{ educationLevelText(info.education_level) }}</dd>
              </div>
              <div class="col-span-2 sm:col-span-3">
                <dt class="text-gray-500 text-xs">สถานศึกษา</dt>
                <dd class="font-medium text-gray-900">{{ info.school_name || '-' }}</dd>
              </div>
            </dl>
          </div>
        </div>
      </div>
    </div>
  `
})

// ============================================================================
// AddressViewCard Component
// ============================================================================
export const AddressViewCard = defineComponent({
  name: 'AddressViewCard',
  props: {
    addresses: { type: Array as PropType<Array<any>>, default: () => [] },
  },
  setup() {
    const typeText = (type: string) => {
      const map: Record<string, string> = {
        current: 'ที่อยู่ปัจจุบัน', permanent: 'ที่อยู่ตามทะเบียนบ้าน', temporary: 'ที่อยู่ชั่วคราว'
      }
      return map[type] || type
    }
    const typeColor = (type: string) => {
      const map: Record<string, string> = {
        current: 'bg-green-100 text-green-800', permanent: 'bg-blue-100 text-blue-800', temporary: 'bg-amber-100 text-amber-800'
      }
      return map[type] || 'bg-gray-100 text-gray-800'
    }
    const formatAddress = (addr: any) => {
      const parts = [
        addr.house_number ? `บ้านเลขที่ ${addr.house_number}` : null,
        addr.village_number ? `หมู่ ${addr.village_number}` : null,
        addr.village_name ? `หมู่บ้าน${addr.village_name}` : null,
        addr.alley ? `ซอย ${addr.alley}` : null,
        addr.road ? `ถนน ${addr.road}` : null,
        addr.subdistrict ? `ต.${addr.subdistrict}` : null,
        addr.district ? `อ.${addr.district}` : null,
        addr.province ? `จ.${addr.province}` : null,
        addr.postal_code || null,
      ].filter(Boolean)
      return parts.join(' ') || '-'
    }
    return { typeText, typeColor, formatAddress }
  },
  template: `
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-gradient-to-r from-amber-500 to-orange-600 px-5 py-4">
        <div class="flex items-center">
          <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </div>
          <h3 class="ml-3 text-lg font-semibold text-white">ที่อยู่</h3>
        </div>
      </div>
      <div class="p-5">
        <div v-if="addresses.length === 0" class="text-center py-8">
          <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
          </svg>
          <p class="text-sm text-gray-500">ยังไม่มีข้อมูลที่อยู่</p>
        </div>
        <div v-else class="space-y-3">
          <div v-for="addr in addresses" :key="addr.id" class="rounded-xl border border-gray-200 p-4">
            <div class="flex items-center gap-2 mb-2">
              <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium', typeColor(addr.address_type)]">
                {{ typeText(addr.address_type) }}
              </span>
              <span v-if="addr.is_current" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-600 text-white">
                ปัจจุบัน
              </span>
            </div>
            <p class="text-sm text-gray-700 leading-relaxed">{{ formatAddress(addr) }}</p>
          </div>
        </div>
      </div>
    </div>
  `
})

// ============================================================================
// ContactViewCard Component
// ============================================================================
export const ContactViewCard = defineComponent({
  name: 'ContactViewCard',
  props: {
    contacts: { type: Array as PropType<Array<any>>, default: () => [] },
  },
  setup() {
    const typeText = (type: string) => {
      const map: Record<string, string> = {
        phone: 'โทรศัพท์', mobile: 'มือถือ', email: 'อีเมล', line: 'LINE', facebook: 'Facebook'
      }
      return map[type] || type
    }
    const typeIcon = (type: string) => {
      const map: Record<string, string> = {
        phone: '📞', mobile: '📱', email: '✉️', line: '💬', facebook: '👤'
      }
      return map[type] || '📋'
    }
    const typeColor = (type: string) => {
      const map: Record<string, string> = {
        phone: 'bg-blue-100 text-blue-800', mobile: 'bg-green-100 text-green-800',
        email: 'bg-purple-100 text-purple-800', line: 'bg-emerald-100 text-emerald-800',
        facebook: 'bg-indigo-100 text-indigo-800'
      }
      return map[type] || 'bg-gray-100 text-gray-800'
    }
    return { typeText, typeIcon, typeColor }
  },
  template: `
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-gradient-to-r from-teal-500 to-cyan-600 px-5 py-4">
        <div class="flex items-center">
          <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
            </svg>
          </div>
          <h3 class="ml-3 text-lg font-semibold text-white">ข้อมูลติดต่อ</h3>
        </div>
      </div>
      <div class="p-5">
        <div v-if="contacts.length === 0" class="text-center py-8">
          <p class="text-sm text-gray-500">ยังไม่มีข้อมูลติดต่อ</p>
        </div>
        <div v-else class="space-y-3">
          <div v-for="contact in contacts" :key="contact.id" class="flex items-center gap-3 rounded-xl border border-gray-200 p-3">
            <span class="text-xl flex-shrink-0">{{ typeIcon(contact.contact_type) }}</span>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium', typeColor(contact.contact_type)]">
                  {{ typeText(contact.contact_type) }}
                </span>
                <span v-if="contact.is_primary" class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-teal-600 text-white">หลัก</span>
              </div>
              <p class="text-sm font-medium text-gray-900 mt-1 truncate">{{ contact.contact_value }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  `
})

// ============================================================================
// GuardianViewCard Component
// ============================================================================
export const GuardianViewCard = defineComponent({
  name: 'GuardianViewCard',
  props: {
    guardians: { type: Array as PropType<Array<any>>, default: () => [] },
  },
  setup() {
    const typeText = (type: string) => {
      const map: Record<string, string> = {
        father: 'บิดา', mother: 'มารดา', guardian: 'ผู้ปกครอง', relative: 'ญาติ', other: 'อื่นๆ'
      }
      return map[type] || type || '-'
    }
    const typeIcon = (type: string) => {
      const map: Record<string, string> = {
        father: '👨', mother: '👩', guardian: '🧑‍🦳', relative: '👥', other: '🧑'
      }
      return map[type] || '🧑'
    }
    const fullName = (g: any) => {
      return [g.title_prefix, g.first_name, g.last_name].filter(Boolean).join(' ') || '-'
    }
    return { typeText, typeIcon, fullName }
  },
  template: `
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-gradient-to-r from-purple-500 to-indigo-600 px-5 py-4">
        <div class="flex items-center">
          <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
          <h3 class="ml-3 text-lg font-semibold text-white">ข้อมูลผู้ปกครอง</h3>
        </div>
      </div>
      <div class="p-5">
        <div v-if="guardians.length === 0" class="text-center py-8">
          <p class="text-sm text-gray-500">ยังไม่มีข้อมูลผู้ปกครอง</p>
        </div>
        <div v-else class="space-y-4">
          <div v-for="g in guardians" :key="g.id" class="rounded-xl border border-gray-200 p-4">
            <div class="flex items-start gap-3">
              <span class="text-2xl flex-shrink-0 mt-0.5">{{ typeIcon(g.guardian_type) }}</span>
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <h4 class="text-sm font-bold text-gray-900">{{ fullName(g) }}</h4>
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                    {{ typeText(g.guardian_type) }}
                  </span>
                  <span v-if="g.is_primary_contact" class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-purple-600 text-white">หลัก</span>
                  <span v-if="g.is_emergency_contact" class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">ฉุกเฉิน</span>
                </div>
                <dl class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                  <div v-if="g.relationship">
                    <dt class="text-gray-500 text-xs">ความสัมพันธ์</dt>
                    <dd class="text-gray-900">{{ g.relationship }}</dd>
                  </div>
                  <div v-if="g.occupation">
                    <dt class="text-gray-500 text-xs">อาชีพ</dt>
                    <dd class="text-gray-900">{{ g.occupation }}</dd>
                  </div>
                  <div v-if="g.workplace">
                    <dt class="text-gray-500 text-xs">สถานที่ทำงาน</dt>
                    <dd class="text-gray-900">{{ g.workplace }}</dd>
                  </div>
                  <div v-if="g.monthly_income">
                    <dt class="text-gray-500 text-xs">รายได้ต่อเดือน</dt>
                    <dd class="text-gray-900">{{ Number(g.monthly_income).toLocaleString() }} บาท</dd>
                  </div>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  `
})

// ============================================================================
// HealthInfoViewCard Component
// ============================================================================
export const HealthInfoViewCard = defineComponent({
  name: 'HealthInfoViewCard',
  props: {
    healthInfo: { type: Object as PropType<any | null>, default: null },
  },
  setup(props) {
    const bmi = computed(() => {
      if (!props.healthInfo?.height_cm || !props.healthInfo?.weight_kg) return null
      const h = props.healthInfo.height_cm / 100
      const val = props.healthInfo.weight_kg / (h * h)
      return val.toFixed(1)
    })
    const bmiStatus = computed(() => {
      if (!bmi.value) return null
      const v = parseFloat(bmi.value)
      if (v < 18.5) return { label: 'น้ำหนักต่ำ', color: 'text-yellow-600' }
      if (v < 25) return { label: 'ปกติ', color: 'text-green-600' }
      if (v < 30) return { label: 'น้ำหนักเกิน', color: 'text-orange-600' }
      return { label: 'อ้วน', color: 'text-red-600' }
    })
    return { bmi, bmiStatus }
  },
  template: `
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-gradient-to-r from-rose-500 to-pink-600 px-5 py-4">
        <div class="flex items-center">
          <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
          </div>
          <h3 class="ml-3 text-lg font-semibold text-white">ข้อมูลสุขภาพ</h3>
        </div>
      </div>
      <div class="p-5">
        <div v-if="!healthInfo" class="text-center py-8">
          <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
          </svg>
          <p class="text-sm text-gray-500">ยังไม่มีข้อมูลสุขภาพ</p>
        </div>
        <div v-else>
          <!-- Body Stats -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
            <div class="bg-gradient-to-br from-rose-50 to-rose-100 rounded-xl p-3 text-center">
              <p class="text-xs text-rose-600 font-medium">ส่วนสูง</p>
              <p class="text-lg font-bold text-rose-900">{{ healthInfo.height_cm || '-' }} <span class="text-xs font-normal">ซม.</span></p>
            </div>
            <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-xl p-3 text-center">
              <p class="text-xs text-pink-600 font-medium">น้ำหนัก</p>
              <p class="text-lg font-bold text-pink-900">{{ healthInfo.weight_kg || '-' }} <span class="text-xs font-normal">กก.</span></p>
            </div>
            <div class="bg-gradient-to-br from-fuchsia-50 to-fuchsia-100 rounded-xl p-3 text-center">
              <p class="text-xs text-fuchsia-600 font-medium">BMI</p>
              <p class="text-lg font-bold text-fuchsia-900">{{ bmi || '-' }}</p>
              <p v-if="bmiStatus" :class="['text-xs font-medium mt-0.5', bmiStatus.color]">{{ bmiStatus.label }}</p>
            </div>
            <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl p-3 text-center">
              <p class="text-xs text-red-600 font-medium">หมู่เลือด</p>
              <p class="text-lg font-bold text-red-900">{{ healthInfo.blood_type || '-' }}{{ healthInfo.rh_factor ? '(' + healthInfo.rh_factor + ')' : '' }}</p>
            </div>
          </div>
          <!-- Medical Info -->
          <dl class="space-y-3">
            <div v-if="healthInfo.allergies" class="rounded-xl bg-yellow-50 border border-yellow-200 p-3">
              <dt class="text-xs font-medium text-yellow-700 flex items-center">
                <span class="mr-1">⚠️</span> ประวัติแพ้ยา/อาหาร
              </dt>
              <dd class="mt-1 text-sm text-yellow-900">{{ healthInfo.allergies }}</dd>
            </div>
            <div v-if="healthInfo.chronic_diseases" class="rounded-xl bg-orange-50 border border-orange-200 p-3">
              <dt class="text-xs font-medium text-orange-700 flex items-center">
                <span class="mr-1">🏥</span> โรคประจำตัว
              </dt>
              <dd class="mt-1 text-sm text-orange-900">{{ healthInfo.chronic_diseases }}</dd>
            </div>
            <div v-if="healthInfo.medications" class="rounded-xl bg-blue-50 border border-blue-200 p-3">
              <dt class="text-xs font-medium text-blue-700 flex items-center">
                <span class="mr-1">💊</span> ยาที่ใช้ประจำ
              </dt>
              <dd class="mt-1 text-sm text-blue-900">{{ healthInfo.medications }}</dd>
            </div>
            <p v-if="!healthInfo.allergies && !healthInfo.chronic_diseases && !healthInfo.medications"
               class="text-sm text-gray-500 text-center py-2">
              ไม่มีข้อมูลทางการแพทย์ที่ต้องระวัง
            </p>
          </dl>
        </div>
      </div>
    </div>
  `
})
</script>
