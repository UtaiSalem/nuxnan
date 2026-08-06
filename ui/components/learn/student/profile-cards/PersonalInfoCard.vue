<script setup lang="ts">
import { ref, computed } from 'vue'
import type { StudentProfile } from '~/composables/useStudentProfile'
import { useStudentEdit } from '~/composables/useStudentEdit'
import { useToast } from 'primevue/usetoast'

const props = withDefaults(defineProps<{
  student: StudentProfile
  accessLevel?: string
}>(), {
  accessLevel: ''
})

const emit = defineEmits<{
  (e: 'saved'): void
}>()

const toast = useToast()
const { updatePersonal, isSubmitting } = useStudentEdit(props.student.academy_id, props.student.id)

const isEditing = ref(false)

// Form state
const form = ref({
  title_prefix_th: props.student.title_prefix_th || '',
  first_name_th: props.student.first_name_th || '',
  last_name_th: props.student.last_name_th || '',
  title_prefix_en: props.student.title_prefix_en || '',
  first_name_en: props.student.first_name_en || '',
  last_name_en: props.student.last_name_en || '',
  nickname: props.student.nickname || '',
  gender: props.student.gender ?? '',
  date_of_birth: props.student.date_of_birth || '',
  nationality: props.student.nationality || '',
  religion: props.student.religion || ''
})

const startEditing = () => {
  form.value = {
    title_prefix_th: props.student.title_prefix_th || '',
    first_name_th: props.student.first_name_th || '',
    last_name_th: props.student.last_name_th || '',
    title_prefix_en: props.student.title_prefix_en || '',
    first_name_en: props.student.first_name_en || '',
    last_name_en: props.student.last_name_en || '',
    nickname: props.student.nickname || '',
    gender: props.student.gender ?? '',
    date_of_birth: props.student.date_of_birth || '',
    nationality: props.student.nationality || '',
    religion: props.student.religion || ''
  }
  isEditing.value = true
}

const cancelEditing = () => {
  isEditing.value = false
}

const save = async () => {
  try {
    const res = await updatePersonal(form.value)
    if (res?.success) {
      toast.add({
        severity: 'success',
        summary: 'สำเร็จ',
        detail: res.message || 'อัปเดตข้อมูลส่วนตัวสำเร็จแล้ว',
        life: 3000
      })
      isEditing.value = false
      emit('saved')
    } else {
      toast.add({
        severity: 'error',
        summary: 'เกิดข้อผิดพลาด',
        detail: res?.message || 'ไม่สามารถอัปเดตข้อมูลได้',
        life: 3000
      })
    }
  } catch (err: any) {
    toast.add({
      severity: 'error',
      summary: 'เกิดข้อผิดพลาด',
      detail: err?.message || 'เกิดข้อผิดพลาดในการส่งข้อมูล',
      life: 3000
    })
  }
}

const formatDate = (dateStr: string | null) => {
  if (!dateStr) return '-'
  try {
    return new Intl.DateTimeFormat('th-TH', { year: 'numeric', month: 'long', day: 'numeric' }).format(new Date(dateStr))
  } catch {
    return dateStr
  }
}

const formatCitizenId = (id: string | null) => {
  if (!id) return '-'
  const digits = id.replace(/\D/g, '')
  if (digits.length !== 13) return id
  return `${digits[0]}-${digits.substring(1, 5)}-${digits.substring(5, 10)}-${digits.substring(10, 12)}-${digits[12]}`
}

const genderText = computed(() => {
  if (props.student.gender === 1) return 'ชาย'
  if (props.student.gender === 0) return 'หญิง'
  return 'ไม่ระบุ'
})

const canEdit = computed(() => {
  return ['self', 'admin', 'homeroom'].includes(props.accessLevel)
})
</script>

<template>
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="bg-gradient-to-r from-emerald-500 to-teal-600 px-5 py-4 flex items-center justify-between">
      <div class="flex items-center">
        <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
        </div>
        <h3 class="ml-3 text-lg font-semibold text-white">ข้อมูลส่วนตัว</h3>
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
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">คำนำหน้า (ไทย)</label>
          <input v-model="form.title_prefix_th" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="เช่น นาย, นางสาว" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">ชื่อจริง (ไทย)</label>
          <input v-model="form.first_name_th" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">นามสกุล (ไทย)</label>
          <input v-model="form.last_name_th" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500" />
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">คำนำหน้า (อังกฤษ)</label>
          <input v-model="form.title_prefix_en" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="เช่น Mr., Miss" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">ชื่อจริง (อังกฤษ)</label>
          <input v-model="form.first_name_en" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">นามสกุล (อังกฤษ)</label>
          <input v-model="form.last_name_en" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500" />
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">ชื่อเล่น</label>
          <input v-model="form.nickname" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">เพศ</label>
          <select v-model="form.gender" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <option value="">ไม่ระบุ</option>
            <option :value="1">ชาย</option>
            <option :value="0">หญิง</option>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">วันเกิด</label>
          <input v-model="form.date_of_birth" type="date" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500" />
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">สัญชาติ</label>
          <input v-model="form.nationality" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">ศาสนา</label>
          <input v-model="form.religion" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500" />
        </div>
      </div>

      <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
        <button @click="cancelEditing" :disabled="isSubmitting"
                class="px-4 py-2 border border-gray-200 text-gray-600 rounded-xl text-sm font-semibold hover:bg-gray-50 transition-colors disabled:opacity-50">
          ยกเลิก
        </button>
        <button @click="save" :disabled="isSubmitting"
                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold transition-colors disabled:opacity-50 flex items-center gap-1.5">
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
          <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">วันที่เข้าเรียน</dt>
          <dd class="mt-1 text-sm font-medium text-gray-900">{{ formatDate(student.enrollment_date) }}</dd>
        </div>
      </dl>
    </div>
  </div>
</template>
