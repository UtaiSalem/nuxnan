<script setup lang="ts">
import { ref, computed } from 'vue'
import type { StudentGuardian } from '~/composables/useStudentProfile'
import { useStudentEdit } from '~/composables/useStudentEdit'
import { useToast } from 'primevue/usetoast'

const props = withDefaults(defineProps<{
  guardians?: StudentGuardian[]
  accessLevel?: string
  studentId?: number
  academyId?: number
}>(), {
  guardians: () => [],
  accessLevel: '',
  studentId: 0,
  academyId: 0
})

const emit = defineEmits<{
  (e: 'saved'): void
}>()

const toast = useToast()
const { updateGuardian, isSubmitting } = useStudentEdit(props.academyId, props.studentId)

const isEditing = ref(false)

// Form state matching UpdateGuardianRequest nested structure
const form = ref({
  guardian: {
    guardian_type: 'father',
    title_prefix: '',
    first_name: '',
    last_name: '',
    citizen_id: '',
    occupation: '',
    workplace: '',
    monthly_income: 0,
    relationship: '',
    is_primary_contact: true,
    is_emergency_contact: false
  },
  contact: {
    contact_type: 'phone',
    contact_value: '',
    is_primary: true
  }
})

const startEditing = () => {
  const g = props.guardians?.[0]
  
  form.value = {
    guardian: {
      guardian_type: g?.guardian_type || 'father',
      title_prefix: g?.title_prefix || '',
      first_name: g?.first_name || '',
      last_name: g?.last_name || '',
      citizen_id: g?.citizen_id || '',
      occupation: g?.occupation || '',
      workplace: g?.workplace || '',
      monthly_income: g?.monthly_income ? Number(g.monthly_income) : 0,
      relationship: g?.relationship || '',
      is_primary_contact: g?.is_primary_contact ?? true,
      is_emergency_contact: g?.is_emergency_contact ?? false
    },
    contact: {
      // If guardian has contacts, get the first one
      contact_type: 'phone',
      contact_value: '',
      is_primary: true
    }
  }

  isEditing.value = true
}

const cancelEditing = () => {
  isEditing.value = false
}

const save = async () => {
  try {
    const res = await updateGuardian(form.value)
    if (res?.success) {
      toast.add({
        severity: 'success',
        summary: 'สำเร็จ',
        detail: res.message || 'บันทึกข้อมูลผู้ปกครองเรียบร้อยแล้ว',
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

const typeText = (type: string) => {
  const map: Record<string, string> = {
    father: 'บิดา',
    mother: 'มารดา',
    guardian: 'ผู้ปกครอง',
    relative: 'ญาติ',
    other: 'อื่นๆ',
  }
  return map[type] || type || '-'
}

const typeIcon = (type: string) => {
  const map: Record<string, string> = {
    father: '👨',
    mother: '👩',
    guardian: '🧑‍🦳',
    relative: '👥',
    other: '🧑',
  }
  return map[type] || '🧑'
}

const fullName = (g: StudentGuardian) => {
  return [g.title_prefix, g.first_name, g.last_name].filter(Boolean).join(' ') || '-'
}

const canEdit = computed(() => {
  return ['self', 'admin', 'homeroom'].includes(props.accessLevel)
})
</script>

<template>
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="bg-gradient-to-r from-purple-500 to-indigo-600 px-5 py-4 flex items-center justify-between">
      <div class="flex items-center">
        <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
        </div>
        <h3 class="ml-3 text-lg font-semibold text-white">ข้อมูลผู้ปกครอง</h3>
      </div>
      <button v-if="canEdit && !isEditing" @click="startEditing"
              class="px-3 py-1.5 bg-white/20 hover:bg-white/30 text-white rounded-xl text-xs font-semibold backdrop-blur-sm transition-colors flex items-center gap-1">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
        </svg>
        แก้ไข
      </button>
    </div>

    <!-- Edit Form Mode -->
    <div v-if="isEditing" class="p-5 space-y-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">ความสัมพันธ์/ประเภทผู้ปกครอง</label>
          <select v-model="form.guardian.guardian_type" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
            <option value="father">บิดา</option>
            <option value="mother">มารดา</option>
            <option value="guardian">ผู้ปกครอง</option>
            <option value="relative">ญาติ</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">เลขประจำตัวประชาชนผู้ปกครอง</label>
          <input v-model="form.guardian.citizen_id" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="เลข 13 หลัก" />
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">คำนำหน้า</label>
          <input v-model="form.guardian.title_prefix" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="นาย/นาง/นางสาว" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">ชื่อจริง</label>
          <input v-model="form.guardian.first_name" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" required />
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">นามสกุล</label>
          <input v-model="form.guardian.last_name" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" required />
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">อาชีพ</label>
          <input v-model="form.guardian.occupation" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">รายได้ต่อเดือน (บาท)</label>
          <input v-model="form.guardian.monthly_income" type="number" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" />
        </div>
      </div>

      <div>
        <label class="block text-xs font-semibold text-gray-500 mb-1">สถานที่ทำงาน</label>
        <input v-model="form.guardian.workplace" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" />
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">ช่องทางติดต่อผู้ปกครอง (เช่น เบอร์โทรศัพท์)</label>
          <input v-model="form.contact.contact_value" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="เช่น 089xxxxxxx" required />
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">ความสัมพันธ์ (เจาะลึก)</label>
          <input v-model="form.guardian.relationship" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="เช่น ปู่, ย่า, ลุง, น้า" />
        </div>
      </div>

      <div class="flex items-center gap-4">
        <label class="inline-flex items-center cursor-pointer">
          <input type="checkbox" v-model="form.guardian.is_primary_contact" class="rounded text-purple-600 focus:ring-purple-500 w-4 h-4" />
          <span class="ml-2 text-sm text-gray-600 font-medium">เป็นผู้ติดต่อหลัก</span>
        </label>
        <label class="inline-flex items-center cursor-pointer">
          <input type="checkbox" v-model="form.guardian.is_emergency_contact" class="rounded text-purple-600 focus:ring-purple-500 w-4 h-4" />
          <span class="ml-2 text-sm text-gray-600 font-medium">เป็นผู้ติดต่อกรณีฉุกเฉิน</span>
        </label>
      </div>

      <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
        <button @click="cancelEditing" :disabled="isSubmitting"
                class="px-4 py-2 border border-gray-200 text-gray-600 rounded-xl text-sm font-semibold hover:bg-gray-50 transition-colors disabled:opacity-50">
          ยกเลิก
        </button>
        <button @click="save" :disabled="isSubmitting"
                class="px-4 py-2 bg-purple-600 hover:bg-purple-755 text-white rounded-xl text-sm font-semibold transition-colors disabled:opacity-50 flex items-center gap-1.5">
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
      <div v-if="!guardians || guardians.length === 0" class="text-center py-8">
        <p class="text-sm text-gray-500">ยังไม่มีข้อมูลผู้ปกครอง</p>
      </div>
      <div v-else class="space-y-4">
        <div v-for="g in guardians" :key="g.id" class="rounded-xl border border-gray-100 p-4">
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
</template>
