<script setup lang="ts">
import { ref, computed } from 'vue'
import type { StudentHealthInfo } from '~/composables/useStudentProfile'
import { useStudentEdit } from '~/composables/useStudentEdit'
import { useToast } from 'primevue/usetoast'

const props = withDefaults(defineProps<{
  healthInfo?: StudentHealthInfo | null
  accessLevel?: string
  studentId?: number
  academyId?: number
}>(), {
  healthInfo: null,
  accessLevel: '',
  studentId: 0,
  academyId: 0
})

const emit = defineEmits<{
  (e: 'saved'): void
}>()

const toast = useToast()
// Note: health record might be on the student model directly or a linked ID.
// The backend supports GET/POST /health and PUT /health/{id}.
// We pass healthInfo ID or fallback to the student ID.
const { updateHealth, isSubmitting } = useStudentEdit(props.academyId, props.studentId)

const isEditing = ref(false)

// Form state
const form = ref({
  height: props.healthInfo?.height_cm || 0,
  weight: props.healthInfo?.weight_kg || 0,
  blood_type: props.healthInfo?.blood_type || '',
  rh_factor: props.healthInfo?.rh_factor || '',
  allergies: props.healthInfo?.allergies || '',
  chronic_diseases: props.healthInfo?.chronic_diseases || '',
  medications: props.healthInfo?.medications || ''
})

const startEditing = () => {
  form.value = {
    height: props.healthInfo?.height_cm || 0,
    weight: props.healthInfo?.weight_kg || 0,
    blood_type: props.healthInfo?.blood_type || '',
    rh_factor: props.healthInfo?.rh_factor || '',
    allergies: props.healthInfo?.allergies || '',
    chronic_diseases: props.healthInfo?.chronic_diseases || '',
    medications: props.healthInfo?.medications || ''
  }
  isEditing.value = true
}

const cancelEditing = () => {
  isEditing.value = false
}

const save = async () => {
  try {
    // Check if a health record exists by checking if there's height or weight, or linked table ID.
    // If not, we call POST /health (which updateHealth handles when healthId is null)
    // Wait, does healthInfo have an id? Let's check: yes, it has height_cm, weight_kg, etc.
    // We can pass healthInfo.id if it's there. Wait, useStudentProfile returns healthInfo as StudentHealthInfo.
    // Let's pass the healthInfo ID if it exists.
    const healthId = (props.healthInfo as any)?.id || null
    const res = await updateHealth(healthId, form.value)
    
    if (res?.status === 'success' || res?.success) {
      toast.add({
        severity: 'success',
        summary: 'สำเร็จ',
        detail: res.message || 'บันทึกข้อมูลสุขภาพเรียบร้อยแล้ว',
        life: 3000
      })
      isEditing.value = false
      emit('saved')
    } else {
      toast.add({
        severity: 'error',
        summary: 'เกิดข้อผิดพลาด',
        detail: res?.message || 'ไม่สามารถบันทึกข้อมูลได้',
        life: 3000
      })
    }
  } catch (err: any) {
    toast.add({
      severity: 'error',
      summary: 'เกิดข้อผิดพลาด',
      detail: err?.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล',
      life: 3000
    })
  }
}

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

const canEdit = computed(() => {
  return ['self', 'admin', 'homeroom'].includes(props.accessLevel)
})
</script>

<template>
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="bg-gradient-to-r from-rose-500 to-pink-600 px-5 py-4 flex items-center justify-between">
      <div class="flex items-center">
        <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
          </svg>
        </div>
        <h3 class="ml-3 text-lg font-semibold text-white">ข้อมูลสุขภาพ</h3>
      </div>
      <button v-if="canEdit && !isEditing" @click="startEditing"
              class="px-3 py-1.5 bg-white/20 hover:bg-white/30 text-white rounded-xl text-xs font-semibold backdrop-blur-sm transition-colors flex items-center gap-1">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
        </svg>
        {{ healthInfo ? 'แก้ไข' : 'เพิ่มข้อมูลสุขภาพ' }}
      </button>
    </div>

    <!-- Edit Form Mode -->
    <div v-if="isEditing" class="p-5 space-y-4">
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">ส่วนสูง (ซม.)</label>
          <input v-model="form.height" type="number" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-rose-500" required />
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">น้ำหนัก (กก.)</label>
          <input v-model="form.weight" type="number" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-rose-500" required />
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">หมู่เลือด</label>
          <input v-model="form.blood_type" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-rose-500" placeholder="A, B, O, AB" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">Rh Factor</label>
          <input v-model="form.rh_factor" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-rose-500" placeholder="Positive/Negative" />
        </div>
      </div>

      <div>
        <label class="block text-xs font-semibold text-gray-500 mb-1">ประวัติแพ้ยา/แพ้อาหาร</label>
        <textarea v-model="form.allergies" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-rose-500" placeholder="ไม่มี หรือระบุยา/อาหารที่แพ้"></textarea>
      </div>

      <div>
        <label class="block text-xs font-semibold text-gray-500 mb-1">โรคประจำตัว</label>
        <textarea v-model="form.chronic_diseases" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-rose-500" placeholder="ไม่มี หรือระบุโรคประจำตัว"></textarea>
      </div>

      <div>
        <label class="block text-xs font-semibold text-gray-500 mb-1">ยาที่ใช้ประจำ</label>
        <textarea v-model="form.medications" rows="2" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-rose-500" placeholder="ไม่มี หรือระบุชื่อยาและขนาดที่ทานประจำ"></textarea>
      </div>

      <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
        <button @click="cancelEditing" :disabled="isSubmitting"
                class="px-4 py-2 border border-gray-200 text-gray-600 rounded-xl text-sm font-semibold hover:bg-gray-50 transition-colors disabled:opacity-50">
          ยกเลิก
        </button>
        <button @click="save" :disabled="isSubmitting"
                class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-sm font-semibold transition-colors disabled:opacity-50 flex items-center gap-1.5">
          <svg v-if="isSubmitting" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
          </svg>
          บันทึก
        </button>
      </div>
    </div>

    <!-- Read Mode -->
    <div v-else class="p-5">
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
</template>
