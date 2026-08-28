<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import type { StudentCardInfo } from '~/composables/useStudentProfile'
import { useStudentCard } from '~/composables/useStudentCard'
import StudentCardFront from '~/components/learn/student-card/StudentCardFront.vue'
import StudentCardBack from '~/components/learn/student-card/StudentCardBack.vue'
import { useApi } from '~/composables/useApi'

const props = defineProps<{
  studentCard: StudentCardInfo | null
  student: any | null
  academy: any | null
  accessLevel?: string
}>()

const emit = defineEmits<{
  (e: 'saved'): void
}>()

const api = useApi()
const toast = useToast()

const cardDetail = ref<any>(null)
const isLoadingDetail = ref(false)
const isFlipped = ref(false)
const isEditing = ref(false)

// Form fields for editing
const form = ref({
  student_number: '',
  card_issue_date: '',
  card_expiry_date: '',
})

// Initialize useStudentCard helper
const cardIdForComposables = computed(() => cardDetail.value?.id || props.studentCard?.id || null)
const academyIdOrName = computed(() => props.academy?.id || props.student?.academy_id || '')
const { isSubmitting, uploadPhoto, updateCard, deletePhoto } = useStudentCard(academyIdOrName, cardIdForComposables)

// Load full card detail if card exists
const loadCardDetail = async () => {
  if (!props.student?.id) return
  isLoadingDetail.value = true
  try {
    const res: any = await api.get(`/api/academies/${academyIdOrName.value}/student-cards/by-student/${props.student.id}`)
    if (res?.success && res?.student) {
      cardDetail.value = res.student
      // Sync form
      form.value = {
        student_number: res.student.student_number || '',
        card_issue_date: res.student.card_issue_date ? res.student.card_issue_date.split('T')[0] : '',
        card_expiry_date: res.student.card_expiry_date ? res.student.card_expiry_date.split('T')[0] : '',
      }
    }
  } catch (err) {
    console.error('Failed to load student card detail:', err)
  } finally {
    isLoadingDetail.value = false
  }
}

watch(() => props.student?.id, () => {
  loadCardDetail()
}, { immediate: true })

watch(() => props.studentCard, () => {
  loadCardDetail()
})

const academyDisplayName = computed(() => props.academy?.name || 'โรงเรียน')
const academyLogo = computed(() => props.academy?.logo || '/images/default-school-logo.png')
const academyAddress = computed(() => props.academy?.address || '')

// Map studentCard / student data to StudentCardFront props format
const cardStudentProps = computed(() => {
  const card = cardDetail.value
  const stu = props.student
  if (!card && !stu) return null
  return {
    id: card?.id || stu?.id,
    student_number: card?.student_number || stu?.student_id,
    title_name: card?.title_name || stu?.title_prefix_th,
    first_name_thai: card?.first_name_thai || stu?.first_name_th,
    last_name_thai: card?.last_name_thai || stu?.last_name_th,
    full_name_thai: card?.full_name_thai || '',
    first_name_english: card?.first_name_english || stu?.first_name_en,
    national_id: card?.national_id || stu?.citizen_id,
    class_level: card?.class_level || stu?.class_level || '',
    class_section: card?.class_section || stu?.class_section || '',
    birth_date: card?.birth_date || stu?.birth_date,
    card_expiry_date: card?.card_expiry_date,
    profile_image: card?.profile_image,
  }
})

const canAdmin = computed(() => {
  return props.accessLevel === 'admin' || props.accessLevel === 'homeroom'
})

const handleFileUpload = async (event: Event) => {
  const target = event.target as HTMLInputElement
  if (!target.files || target.files.length === 0) return
  
  const file = target.files[0]
  try {
    const res: any = await uploadPhoto(file)
    if (res?.success) {
      toast.add({
        severity: 'success',
        summary: 'สำเร็จ',
        detail: 'อัปโหลดรูปภาพประจำตัวสำเร็จแล้ว',
        life: 3000
      })
      await loadCardDetail()
      emit('saved')
    }
  } catch (err: any) {
    toast.add({
      severity: 'error',
      summary: 'เกิดข้อผิดพลาด',
      detail: err?.message || 'ไม่สามารถอัปโหลดรูปภาพได้',
      life: 3000
    })
  }
}

const handleDeletePhoto = async () => {
  if (!confirm('คุณแน่ใจหรือไม่ว่าต้องการลบรูปภาพประจำตัวของบัตรนี้?')) return
  try {
    const res: any = await deletePhoto()
    if (res?.success) {
      toast.add({
        severity: 'success',
        summary: 'สำเร็จ',
        detail: 'ลบรูปภาพประจำตัวสำเร็จแล้ว',
        life: 3000
      })
      await loadCardDetail()
      emit('saved')
    }
  } catch (err: any) {
    toast.add({
      severity: 'error',
      summary: 'เกิดข้อผิดพลาด',
      detail: err?.message || 'ไม่สามารถลบรูปภาพได้',
      life: 3000
    })
  }
}

const saveCardDetails = async () => {
  try {
    const res: any = await updateCard(form.value)
    if (res?.success) {
      toast.add({
        severity: 'success',
        summary: 'สำเร็จ',
        detail: 'อัปเดตข้อมูลบัตรนักเรียนสำเร็จแล้ว',
        life: 3000
      })
      isEditing.value = false
      await loadCardDetail()
      emit('saved')
    }
  } catch (err: any) {
    toast.add({
      severity: 'error',
      summary: 'เกิดข้อผิดพลาด',
      detail: err?.message || 'ไม่สามารถอัปเดตข้อมูลบัตรได้',
      life: 3000
    })
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Loading State -->
    <div v-if="isLoadingDetail" class="flex items-center justify-center py-12 bg-white rounded-2xl border border-gray-100 shadow-sm">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-blue-500 border-t-transparent"></div>
    </div>

    <!-- No Student Card Record -->
    <div v-else-if="!studentCard?.exists && !cardDetail"
         class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 sm:p-8 text-center">
      <div class="w-16 h-16 rounded-full bg-gray-50 flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0" />
        </svg>
      </div>
      <p class="text-base font-semibold text-gray-700">ยังไม่มีบัตรนักเรียนสำหรับนักเรียนท่านนี้</p>
      <p class="text-xs text-gray-400 mt-1 mb-6">คุณยังไม่ได้รับบัตรนักเรียน กรุณาติดต่อเจ้าหน้าที่</p>
      
      <!-- Admin Create Card CTA -->
      <NuxtLink
        v-if="canAdmin"
        :to="`/academies/${academy?.name || student?.academy?.name}/admin/student-cards?create=${student?.id}`"
        class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold transition-colors shadow-sm"
      >
        <Icon icon="fluent:card-ui-24-filled" class="w-5 h-5" />
        สร้างบัตรประจำตัวนักเรียน
      </NuxtLink>
    </div>

    <!-- Student Card Found -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      
      <!-- Visual Card Column -->
      <div class="lg:col-span-7 flex flex-col items-center space-y-4">
        <!-- Interactive Card with Flip Effect -->
        <div 
          class="relative w-full cursor-pointer perspective-1000"
          @click="isFlipped = !isFlipped"
        >
          <div 
            :class="[
              'w-full transition-transform duration-700 transform-style-preserve-3d',
              isFlipped ? 'rotate-y-180' : ''
            ]"
          >
            <!-- Front Face -->
            <div class="backface-hidden w-full">
              <StudentCardFront
                v-if="cardStudentProps"
                :student="cardStudentProps"
                :academy-name="academyDisplayName"
                :academy-logo="academyLogo"
                :academy-address="academyAddress"
                :show-qr-code="true"
                card-id="profile-student-card-front"
              />
            </div>

            <!-- Back Face -->
            <div class="absolute inset-0 backface-hidden rotate-y-180 w-full">
              <StudentCardBack
                :academy-name="academyDisplayName"
                :academy-logo="academyLogo"
                :academy-address="academyAddress"
                card-id="profile-student-card-back"
              />
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2 text-xs text-gray-500 font-medium bg-gray-50 px-3 py-1.5 rounded-full">
          <Icon icon="fluent:arrow-swap-24-regular" class="w-4 h-4 text-blue-500" />
          คลิกที่บัตรเพื่อหมุนพลิกดูหน้า-หลัง
        </div>
      </div>

      <!-- Settings / Admin Panel Column -->
      <div class="lg:col-span-5 space-y-4">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-4">
          <div class="flex items-center justify-between border-b border-gray-50 pb-3">
            <h3 class="text-sm font-bold text-gray-900 flex items-center gap-1.5">
              <Icon icon="fluent:settings-24-regular" class="w-5 h-5 text-gray-500" />
              รายละเอียดและการจัดการ
            </h3>
            <span :class="[
              'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold',
              studentCard?.photo_status === 'approved' ? 'bg-green-100 text-green-700' :
              studentCard?.photo_status === 'pending'  ? 'bg-yellow-100 text-yellow-700' :
                                                        'bg-gray-100 text-gray-500'
            ]">
              {{ studentCard?.photo_status === 'approved' ? 'รูปภาพเรียบร้อย' :
                 studentCard?.photo_status === 'pending'  ? 'รอตรวจสอบรูปภาพ' : 'ไม่มีรูปภาพประจำตัว' }}
            </span>
          </div>

          <!-- Card Details Read Mode -->
          <div v-if="!isEditing" class="space-y-4">
            <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
              <div>
                <dt class="text-xs font-semibold text-gray-400">เลขบัตรนักเรียน</dt>
                <dd class="font-semibold text-gray-900 mt-0.5">{{ cardDetail?.student_number || studentCard?.card_number || '—' }}</dd>
              </div>
              <div>
                <dt class="text-xs font-semibold text-gray-400">สถานะบัตร</dt>
                <dd class="font-semibold text-green-600 mt-0.5 flex items-center gap-1">
                  <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                  ใช้งานปกติ
                </dd>
              </div>
              <div>
                <dt class="text-xs font-semibold text-gray-400">วันออกบัตร</dt>
                <dd class="font-semibold text-gray-900 mt-0.5">{{ cardDetail?.card_issue_date ? new Date(cardDetail.card_issue_date).toLocaleDateString('th-TH') : '—' }}</dd>
              </div>
              <div>
                <dt class="text-xs font-semibold text-gray-400">วันหมดอายุ</dt>
                <dd class="font-semibold text-gray-900 mt-0.5">{{ cardDetail?.card_expiry_date ? new Date(cardDetail.card_expiry_date).toLocaleDateString('th-TH') : '—' }}</dd>
              </div>
            </dl>

            <!-- Admin Actions Block -->
            <div v-if="canAdmin" class="pt-4 border-t border-gray-100 space-y-3">
              <div class="flex gap-2">
                <button @click="isEditing = true"
                        class="min-h-[44px] sm:min-h-0 flex-1 px-4 py-2 border border-gray-200 text-gray-700 hover:bg-gray-50 rounded-xl text-xs font-bold transition-colors flex items-center justify-center gap-1">
                  <Icon icon="fluent:edit-24-regular" class="w-4 h-4" />
                  แก้ไขวันที่และเลขบัตร
                </button>
                <button v-if="cardDetail?.profile_image" @click="handleDeletePhoto"
                        class="min-h-[44px] sm:min-h-0 px-3 py-2 border border-red-200 text-red-600 hover:bg-red-50 rounded-xl text-xs font-bold transition-colors flex items-center justify-center gap-1">
                  <Icon icon="fluent:delete-24-regular" class="w-4 h-4" />
                  ลบรูป
                </button>
              </div>

              <!-- Photo Upload Field -->
              <div class="relative bg-gray-50 rounded-xl p-3 border border-dashed border-gray-200 flex items-center justify-between">
                <div>
                  <h4 class="text-xs font-bold text-gray-700">เปลี่ยนรูปภาพประจำตัว</h4>
                  <p class="text-[10px] text-gray-400 mt-0.5">ไฟล์ JPG, PNG ขนาดไม่เกิน 5MB</p>
                </div>
                <label class="cursor-pointer bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-colors shadow-sm flex items-center gap-1">
                  <Icon icon="fluent:camera-add-24-regular" class="w-4 h-4" />
                  เลือกรูป
                  <input type="file" accept="image/*" class="hidden" @change="handleFileUpload" :disabled="isSubmitting" />
                </label>
              </div>

              <NuxtLink
                :to="`/academies/${academy?.name || student?.academy?.name}/admin/student-cards`"
                class="block w-full text-center py-2 bg-gray-50 hover:bg-gray-100 text-gray-600 rounded-xl text-xs font-bold transition-colors border border-gray-100"
              >
                ไปหน้าจัดการบัตรสถาบัน
              </NuxtLink>
            </div>
          </div>

          <!-- Card Details Edit Mode -->
          <div v-else class="space-y-4">
            <div>
              <label class="block text-xs font-bold text-gray-400 mb-1">เลขรหัสบัตรนักเรียน</label>
              <input v-model="form.student_number" type="text"
                     class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-gray-400 mb-1">วันออกบัตร</label>
                <input v-model="form.card_issue_date" type="date"
                       class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
              </div>
              <div>
                <label class="block text-xs font-bold text-gray-400 mb-1">วันหมดอายุ</label>
                <input v-model="form.card_expiry_date" type="date"
                       class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
              </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-50">
              <button @click="isEditing = false" :disabled="isSubmitting"
                      class="min-h-[44px] sm:min-h-0 px-4 py-2 border border-gray-200 text-gray-600 rounded-xl text-xs font-bold hover:bg-gray-50 transition-colors">
                ยกเลิก
              </button>
              <button @click="saveCardDetails" :disabled="isSubmitting"
                      class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-colors shadow-sm flex items-center gap-1">
                <svg v-if="isSubmitting" class="w-3.5 h-3.5 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                บันทึก
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
.perspective-1000 {
  perspective: 1000px;
}

.transform-style-preserve-3d {
  transform-style: preserve-3d;
}

.backface-hidden {
  backface-visibility: hidden;
}

.rotate-y-180 {
  transform: rotateY(180deg);
}
</style>
