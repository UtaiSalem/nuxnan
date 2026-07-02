<script setup lang="ts">
import { ref, computed } from 'vue'
import type { AcademicInfo } from '~/composables/useStudentProfile'
import { useStudentEdit } from '~/composables/useStudentEdit'
import { useToast } from 'primevue/usetoast'

const props = withDefaults(defineProps<{
  academicInfo?: AcademicInfo[]
  accessLevel?: string
  studentId?: number
  academyId?: number
}>(), {
  academicInfo: () => [],
  accessLevel: '',
  studentId: 0,
  academyId: 0
})

const emit = defineEmits<{
  (e: 'saved'): void
}>()

const toast = useToast()
const { updateAcademic, isSubmitting } = useStudentEdit(props.academyId, props.studentId)

// Editing states
const isAdding = ref(false)
const editingRecordId = ref<number | null>(null)

// Form state
const form = ref({
  academic_year: '',
  semester: 1,
  current_grade: '',
  current_class: '',
  education_level: 2,
  school_name: '',
  study_status: 'studying',
  is_current: true,
  enrollment_date: '',
  graduation_date: '',
})

const startAdd = () => {
  form.value = {
    academic_year: new Date().getFullYear().toString(),
    semester: 1,
    current_grade: '',
    current_class: '',
    education_level: 2,
    school_name: '',
    study_status: 'studying',
    is_current: true,
    enrollment_date: '',
    graduation_date: '',
  }
  isAdding.value = true
  editingRecordId.value = null
}

const startEdit = (info: AcademicInfo) => {
  form.value = {
    academic_year: info.academic_year || '',
    semester: (info as any).semester || 1,
    current_grade: info.current_grade || '',
    current_class: info.current_class || '',
    education_level: info.education_level || 2,
    school_name: info.school_name || '',
    study_status: info.study_status || 'studying',
    is_current: info.is_current,
    enrollment_date: info.enrollment_date || '',
    graduation_date: info.graduation_date || '',
  }
  editingRecordId.value = info.id
  isAdding.value = false
}

const cancelForm = () => {
  isAdding.value = false
  editingRecordId.value = null
}

const save = async () => {
  try {
    const res = await updateAcademic(editingRecordId.value, form.value)
    if (res?.success || res?.status === 'success') {
      toast.add({
        severity: 'success',
        summary: 'สำเร็จ',
        detail: res.message || 'บันทึกประวัติการศึกษาเรียบร้อยแล้ว',
        life: 3000
      })
      cancelForm()
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

const educationLevelText = (level: number | null) => {
  const map: Record<number, string> = { 1: 'ประถมศึกษา', 2: 'มัธยมศึกษา', 3: 'อุดมศึกษา' }
  return level ? (map[level] || '-') : '-'
}

const statusText = (status: string | null) => {
  const map: Record<string, string> = {
    studying: 'กำลังศึกษา',
    graduated: 'สำเร็จการศึกษา',
    transferred: 'ย้ายสถานศึกษา',
    dropped: 'พ้นสภาพ',
  }
  return status ? (map[status] || status) : '-'
}

const statusColor = (status: string | null) => {
  const map: Record<string, string> = {
    studying: 'bg-green-100 text-green-800',
    graduated: 'bg-blue-100 text-blue-800',
    transferred: 'bg-yellow-100 text-yellow-800',
    dropped: 'bg-red-100 text-red-800',
  }
  return status ? (map[status] || 'bg-gray-100 text-gray-800') : 'bg-gray-100 text-gray-800'
}

const canEdit = computed(() => {
  return props.accessLevel === 'self' || props.accessLevel === 'admin'
})
</script>

<template>
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-5 py-4 flex items-center justify-between">
      <div class="flex items-center">
        <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
          </svg>
        </div>
        <h3 class="ml-3 text-lg font-semibold text-white">ประวัติการศึกษา</h3>
      </div>
      <button v-if="canEdit && !isAdding && !editingRecordId" @click="startAdd"
              class="px-3 py-1.5 bg-white/20 hover:bg-white/30 text-white rounded-xl text-xs font-semibold backdrop-blur-sm transition-colors flex items-center gap-1">
        + เพิ่มประวัติการศึกษา
      </button>
    </div>

    <!-- Edit Form Mode Inline -->
    <div v-if="isAdding || editingRecordId" class="p-5 space-y-4">
      <h4 class="text-sm font-semibold text-gray-700 mb-2">
        {{ editingRecordId ? 'แก้ไขข้อมูลประวัติการศึกษา' : 'เพิ่มข้อมูลประวัติการศึกษาใหม่' }}
      </h4>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">ปีการศึกษา</label>
          <input v-model="form.academic_year" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required />
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">ภาคเรียน</label>
          <select v-model="form.semester" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option :value="1">1</option>
            <option :value="2">2</option>
          </select>
        </div>
        <div class="flex items-center pt-5">
          <label class="inline-flex items-center cursor-pointer">
            <input type="checkbox" v-model="form.is_current" class="rounded text-blue-600 focus:ring-blue-500 w-4 h-4" />
            <span class="ml-2 text-sm text-gray-600 font-medium">ตั้งเป็นข้อมูลปัจจุบัน</span>
          </label>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">ระดับชั้น (ตัวเลข/อักษรย่อ)</label>
          <input v-model="form.current_grade" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="เช่น 1, 2, ม.1" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">ห้องเรียน</label>
          <input v-model="form.current_class" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="เช่น 1, 2" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">ระดับการศึกษา</label>
          <select v-model="form.education_level" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option :value="1">ประถมศึกษา</option>
            <option :value="2">มัธยมศึกษา</option>
            <option :value="3">อุดมศึกษา</option>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">ชื่อโรงเรียน/สถานศึกษา</label>
          <input v-model="form.school_name" type="text" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required />
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 mb-1">สถานะการศึกษา</label>
          <select v-model="form.study_status" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="studying">กำลังศึกษา</option>
            <option value="graduated">สำเร็จการศึกษา</option>
            <option value="transferred">ย้ายสถานศึกษา</option>
            <option value="dropped">พ้นสภาพ</option>
          </select>
        </div>
      </div>

      <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
        <button @click="cancelForm" :disabled="isSubmitting"
                class="px-4 py-2 border border-gray-200 text-gray-600 rounded-xl text-sm font-semibold hover:bg-gray-50 transition-colors disabled:opacity-50">
          ยกเลิก
        </button>
        <button @click="save" :disabled="isSubmitting"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold transition-colors disabled:opacity-50 flex items-center gap-1.5">
          <svg v-if="isSubmitting" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
          </svg>
          บันทึก
        </button>
      </div>
    </div>

    <!-- Read Mode List -->
    <div v-else class="p-5">
      <div v-if="!academicInfo || academicInfo.length === 0" class="text-center py-8">
        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
        </svg>
        <p class="text-sm text-gray-500">ยังไม่มีข้อมูลประวัติการศึกษา</p>
      </div>
      <div v-else class="space-y-4">
        <div v-for="info in academicInfo" :key="info.id"
             :class="['rounded-xl border p-4 transition-all relative group', info.is_current ? 'border-blue-200 bg-blue-50/50 ring-1 ring-blue-200' : 'border-gray-200 bg-white']">
          
          <!-- Actions Menu on Hover -->
          <div v-if="canEdit" class="absolute top-4 right-4 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity bg-white pl-2">
            <button @click="startEdit(info)"
                    title="แก้ไข"
                    class="p-1 hover:bg-gray-100 text-gray-600 rounded transition-colors">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
              </svg>
            </button>
          </div>

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
</template>
