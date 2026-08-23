<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { useCourseGroupStore } from '~/stores/courseGroup'

const props = defineProps<{
  courseId: number | string | undefined
  show: boolean
}>()

const emit = defineEmits(['close', 'imported'])

const groupStore = useCourseGroupStore()

// State
const step = ref<1 | 2 | 3>(1)
const isLoading = ref(false)
const error = ref<string | null>(null)

// Step 1: Select Classrooms
const academicYears = ref<any[]>([])
const classrooms = ref<any[]>([])
const selectedAcademicYearId = ref<number | null>(null)
const selectedClassroomIds = ref<number[]>([])
const classroomSearch = ref('')

// Step 2: Mode
const mode = ref<'per_classroom' | 'single_group'>('per_classroom')
const singleGroupName = ref('')

// Step 3: Preview
const summary = ref<any>(null)
const previewItems = ref<any[]>([])

// Fetch sources
const fetchSources = async () => {
  if (!props.courseId) return
  isLoading.value = true
  error.value = null
  try {
    const res = await groupStore.fetchClassroomSources(props.courseId, selectedAcademicYearId.value || undefined)
    academicYears.value = res.academic_years || []
    classrooms.value = res.classrooms || []
    if (!selectedAcademicYearId.value) {
      selectedAcademicYearId.value = res.selected_academic_year_id || null
    }
  } catch (err: any) {
    error.value = err.data?.message || 'ไม่สามารถโหลดข้อมูลห้องเรียนได้'
  } finally {
    isLoading.value = false
  }
}

// Watch selected academic year
watch(selectedAcademicYearId, (newVal, oldVal) => {
  if (newVal && oldVal && newVal !== oldVal) {
    fetchSources()
  }
})

// Reset on show
watch(() => props.show, (newVal) => {
  if (newVal) {
    step.value = 1
    selectedClassroomIds.value = []
    classroomSearch.value = ''
    mode.value = 'per_classroom'
    singleGroupName.value = ''
    summary.value = null
    previewItems.value = []
    fetchSources()
  }
})

const filteredClassrooms = computed(() => {
  let list = classrooms.value
  if (classroomSearch.value.trim()) {
    const q = classroomSearch.value.toLowerCase()
    list = list.filter(c => c.name.toLowerCase().includes(q))
  }
  return list
})

const classroomsByGrade = computed(() => {
  const grouped: Record<string, any[]> = {}
  filteredClassrooms.value.forEach(c => {
    const grade = c.grade_level || 'ทั่วไป'
    if (!grouped[grade]) grouped[grade] = []
    grouped[grade].push(c)
  })
  return grouped
})

const selectAllByGrade = (grade: string, state: boolean) => {
  const gradeClassrooms = classroomsByGrade.value[grade] || []
  if (state) {
    gradeClassrooms.forEach(c => {
      if (!selectedClassroomIds.value.includes(c.id)) {
        selectedClassroomIds.value.push(c.id)
      }
    })
  } else {
    selectedClassroomIds.value = selectedClassroomIds.value.filter(id => !gradeClassrooms.some(c => c.id === id))
  }
}

const toggleClassroom = (id: number) => {
  const idx = selectedClassroomIds.value.indexOf(id)
  if (idx > -1) {
    selectedClassroomIds.value.splice(idx, 1)
  } else {
    selectedClassroomIds.value.push(id)
  }
}

// Go to Step 2
const goToStep2 = () => {
  if (selectedClassroomIds.value.length > 0) {
    step.value = 2
  }
}

// Go to Step 3 (Preview)
const goToStep3 = async () => {
  if (mode.value === 'single_group' && !singleGroupName.value.trim()) {
    alert('กรุณากรอกชื่อกลุ่ม')
    return
  }
  
  if (!props.courseId) return
  isLoading.value = true
  error.value = null
  try {
    const payload = {
      classroom_ids: selectedClassroomIds.value,
      mode: mode.value,
      group_name: mode.value === 'single_group' ? singleGroupName.value.trim() : undefined,
      dry_run: true
    }
    const res = await groupStore.importFromClassrooms(props.courseId, payload)
    summary.value = res.summary
    previewItems.value = res.items || []
    step.value = 3
  } catch (err: any) {
    error.value = err.data?.message || 'ไม่สามารถโหลดตัวอย่างผลลัพธ์ได้'
  } finally {
    isLoading.value = false
  }
}

// Confirm Import
const confirmImport = async () => {
  if (!props.courseId) return
  isLoading.value = true
  error.value = null
  try {
    const payload = {
      classroom_ids: selectedClassroomIds.value,
      mode: mode.value,
      group_name: mode.value === 'single_group' ? singleGroupName.value.trim() : undefined,
      dry_run: false
    }
    await groupStore.importFromClassrooms(props.courseId, payload)
    emit('imported')
    emit('close')
  } catch (err: any) {
    error.value = err.data?.message || 'ไม่สามารถดึงรายชื่อได้'
  } finally {
    isLoading.value = false
  }
}

</script>

<template>
  <DialogModal :show="show" @close="emit('close')" max-width="3xl">
    <template #title>
      <div class="flex items-center gap-3">
        <div class="flex items-center justify-center w-12 h-12 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl">
          <Icon icon="fluent:people-team-add-24-filled" class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
        </div>
        <div>
          <h3 class="text-xl font-bold text-gray-900 dark:text-white">ดึงรายชื่อจากห้องเรียน</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400">ซิงค์นักเรียนจากระบบโรงเรียนเข้าสู่รายวิชา</p>
        </div>
      </div>
    </template>

    <template #content>
      <!-- Stepper Header -->
      <div class="mb-4 flex items-stretch gap-1.5 sm:gap-2">
        <div
          v-for="s in [{ n: 1, label: 'เลือกห้องเรียน' }, { n: 2, label: 'เลือกโหมด' }, { n: 3, label: 'ตรวจสอบ' }]"
          :key="s.n"
          class="flex min-w-0 flex-1 items-center gap-2 rounded-xl px-2.5 py-2 sm:px-3"
          :class="step > s.n
            ? 'bg-emerald-500 text-white'
            : step === s.n
              ? 'bg-emerald-600 text-white shadow-sm'
              : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400'"
        >
          <span
            class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full text-xs font-bold sm:h-7 sm:w-7 sm:text-sm"
            :class="step >= s.n ? 'bg-white/25 text-white' : 'bg-white text-gray-500 dark:bg-gray-700 dark:text-gray-300'"
          >
            <Icon v-if="step > s.n" icon="heroicons:check-20-solid" class="h-4 w-4" />
            <template v-else>{{ s.n }}</template>
          </span>
          <span
            class="min-w-0 truncate text-xs font-semibold sm:text-sm"
            :class="step === s.n ? 'block' : 'hidden sm:block'"
          >{{ s.label }}</span>
        </div>
      </div>

      <div v-if="error" class="mb-4 p-3 bg-red-50 text-red-600 border border-red-200 rounded-lg text-sm">
        {{ error }}
      </div>

      <!-- STEP 1 -->
      <div v-if="step === 1" class="space-y-4">
        <div class="flex flex-col sm:flex-row gap-3">
          <select 
            v-model="selectedAcademicYearId" 
            class="min-h-[44px] px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg w-full sm:w-1/3"
            :disabled="isLoading"
          >
            <option v-for="y in academicYears" :key="y.id" :value="y.id">ปีการศึกษา {{ y.year }}</option>
          </select>
          <div class="relative flex-1">
            <Icon icon="heroicons:magnifying-glass" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
            <input 
              v-model="classroomSearch" 
              type="text" 
              placeholder="ค้นหาชื่อห้อง..." 
              class="w-full min-h-[44px] pl-9 pr-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg"
            >
          </div>
        </div>

        <div v-if="isLoading" class="flex justify-center py-8">
          <Icon icon="svg-spinners:ring-resize" class="w-8 h-8 text-emerald-500" />
        </div>
        
        <div v-else-if="filteredClassrooms.length === 0" class="py-8 text-center text-gray-500">
          ไม่พบห้องเรียนที่มีสิทธิ์เข้าถึงในปีนี้
        </div>

        <div v-else class="space-y-6 max-h-[50vh] overflow-y-auto pr-2">
          <div v-for="(gradeClassrooms, grade) in classroomsByGrade" :key="grade" class="space-y-2">
            <div class="flex items-center justify-between">
              <h4 class="font-bold text-gray-900 dark:text-white">{{ grade }}</h4>
              <div class="flex items-center gap-2">
                <button @click="selectAllByGrade(grade as string, true)" class="text-xs text-blue-600 hover:underline min-h-[44px] px-2">เลือกทั้ง {{ grade }}</button>
                <button @click="selectAllByGrade(grade as string, false)" class="text-xs text-gray-500 hover:underline min-h-[44px] px-2">ล้าง</button>
              </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
              <label 
                v-for="c in gradeClassrooms" 
                :key="c.id" 
                class="flex items-center gap-3 p-3 min-h-[44px] border rounded-lg cursor-pointer transition-colors"
                :class="selectedClassroomIds.includes(c.id) ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800'"
              >
                <input 
                  type="checkbox" 
                  :checked="selectedClassroomIds.includes(c.id)"
                  @change="toggleClassroom(c.id)"
                  class="w-4 h-4 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500 flex-shrink-0"
                >
                <div class="flex-1 min-w-0 break-words flex flex-col">
                  <span class="font-medium text-gray-900 dark:text-white">{{ c.name }}</span>
                  <div class="flex flex-wrap items-center gap-1.5 mt-1">
                    <span class="text-[11px] px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded">
                      {{ c.students_count }} คน
                    </span>
                    <span v-if="c.linked_group_id" class="text-[11px] px-1.5 py-0.5 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded">
                      ผูกกับกลุ่ม {{ c.linked_group_name }} แล้ว
                    </span>
                  </div>
                </div>
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- STEP 2 -->
      <div v-if="step === 2" class="space-y-4">
        <label 
          class="flex flex-col sm:flex-row gap-3 p-4 border rounded-xl cursor-pointer transition-colors"
          :class="mode === 'per_classroom' ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800'"
        >
          <div class="flex items-start gap-3 flex-1 min-w-0 break-words">
            <input type="radio" v-model="mode" value="per_classroom" class="mt-1 w-4 h-4 text-emerald-600 focus:ring-emerald-500 flex-shrink-0">
            <div>
              <div class="font-bold text-gray-900 dark:text-white">แยกกลุ่มตามห้องเรียน (1 ห้อง = 1 กลุ่ม)</div>
              <p class="text-sm text-gray-500 mt-1">ระบบจะสร้างหรืออัปเดตกลุ่มที่มีชื่อตรงกับชื่อห้องเรียน</p>
            </div>
          </div>
        </label>
        
        <label 
          class="flex flex-col sm:flex-row gap-3 p-4 border rounded-xl cursor-pointer transition-colors"
          :class="mode === 'single_group' ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800'"
        >
          <div class="flex items-start gap-3 flex-1 min-w-0 break-words">
            <input type="radio" v-model="mode" value="single_group" class="mt-1 w-4 h-4 text-emerald-600 focus:ring-emerald-500 flex-shrink-0">
            <div class="w-full">
              <div class="font-bold text-gray-900 dark:text-white">รวมทุกห้องเป็นกลุ่มเดียว</div>
              <p class="text-sm text-gray-500 mt-1">นักเรียนจากทุกห้องที่เลือกจะถูกดึงเข้ากลุ่มนี้</p>
              
              <div v-if="mode === 'single_group'" class="mt-3">
                <input 
                  v-model="singleGroupName" 
                  type="text" 
                  placeholder="ตั้งชื่อกลุ่มใหม่ หรือ กรอกชื่อกลุ่มที่มีอยู่แล้ว..." 
                  class="w-full min-h-[44px] px-3 py-2 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg"
                >
              </div>
            </div>
          </div>
        </label>
      </div>

      <!-- STEP 3 -->
      <div v-if="step === 3" class="space-y-4">
        <!-- Summary Strip -->
        <div v-if="summary" class="grid grid-cols-2 sm:grid-cols-4 gap-2">
          <div class="flex items-center gap-2 rounded-lg bg-blue-50 px-3 py-2 dark:bg-blue-900/30">
            <span class="text-lg font-bold text-blue-600 dark:text-blue-400 sm:text-xl">{{ summary.to_add }}</span>
            <span class="min-w-0 text-[11px] leading-tight text-gray-600 dark:text-gray-400 sm:text-xs">จะเพิ่มใหม่</span>
          </div>
          <div class="flex items-center gap-2 rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-800">
            <span class="text-lg font-bold text-gray-600 dark:text-gray-400 sm:text-xl">{{ summary.already_member }}</span>
            <span class="min-w-0 text-[11px] leading-tight text-gray-600 dark:text-gray-400 sm:text-xs">เป็นสมาชิกอยู่แล้ว</span>
          </div>
          <div class="flex items-center gap-2 rounded-lg bg-amber-50 px-3 py-2 dark:bg-amber-900/30">
            <span class="text-lg font-bold text-amber-600 dark:text-amber-400 sm:text-xl">{{ summary.moving_from_other_group }}</span>
            <span class="min-w-0 text-[11px] leading-tight text-gray-600 dark:text-gray-400 sm:text-xs">ย้ายกลุ่ม</span>
          </div>
          <div class="flex items-center gap-2 rounded-lg bg-red-50 px-3 py-2 dark:bg-red-900/30">
            <span class="text-lg font-bold text-red-600 dark:text-red-400 sm:text-xl">{{ summary.no_user_account }}</span>
            <span class="min-w-0 text-[11px] leading-tight text-gray-600 dark:text-gray-400 sm:text-xs">ไม่มีบัญชี</span>
          </div>
        </div>

        <div v-if="summary?.no_user_account > 0" class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl">
          <h4 class="font-bold text-yellow-800 dark:text-yellow-500 mb-2">มีนักเรียน {{ summary.no_user_account }} คนที่ยังไม่มีบัญชีผู้ใช้ในระบบ จะข้ามไปก่อน</h4>
          <div class="max-h-24 overflow-y-auto text-sm text-yellow-700 dark:text-yellow-600/80">
            <div v-for="item in previewItems" :key="item.classroom.id">
              <div v-for="student in item.no_user_account" :key="student.student_id">
                - {{ student.name }} ({{ item.classroom.name }})
              </div>
            </div>
          </div>
        </div>

        <div v-if="summary?.moving_from_other_group > 0" class="p-4 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-xl">
          <h4 class="font-bold text-orange-800 dark:text-orange-500 mb-2">นักเรียน {{ summary.moving_from_other_group }} คนอยู่กลุ่มอื่นในรายวิชานี้อยู่แล้ว ระบบจะย้ายมากลุ่มใหม่ให้อัตโนมัติ</h4>
          <div class="max-h-24 overflow-y-auto text-sm text-orange-700 dark:text-orange-600/80">
            <div v-for="item in previewItems" :key="item.classroom.id">
              <div v-for="student in item.moving_from_other_group" :key="student.user_id">
                - {{ student.name }} (จาก {{ student.from_group.name }})
              </div>
            </div>
          </div>
        </div>

        <div class="space-y-4 max-h-[40vh] overflow-y-auto pr-2">
          <div v-for="item in previewItems" :key="item.classroom.id" class="border border-gray-200 dark:border-gray-700 rounded-xl p-4">
            <div class="flex items-center gap-2 mb-3 border-b border-gray-100 dark:border-gray-700 pb-2">
              <Icon icon="fluent:class-24-regular" class="w-5 h-5 text-gray-500" />
              <span class="font-bold">{{ item.classroom.name }}</span>
              <Icon icon="heroicons:arrow-right" class="w-4 h-4 mx-1 text-gray-400" />
              <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ item.target_group.name }}</span>
              <span v-if="!item.target_group.exists" class="px-1.5 py-0.5 text-[10px] bg-emerald-100 text-emerald-700 rounded">กลุ่มใหม่</span>
            </div>
            
            <div class="flex flex-col gap-3 sm:flex-row">
              <div v-if="item.to_add.length > 0" class="min-w-0 flex-1">
                <div class="mb-1.5 text-xs font-bold text-blue-600 dark:text-blue-400">จะเพิ่มเข้ากลุ่ม ({{ item.to_add.length }})</div>
                <div class="flex flex-wrap gap-1">
                  <span
                    v-for="s in item.to_add"
                    :key="s.user_id"
                    class="max-w-full truncate rounded-md bg-blue-50 px-1.5 py-0.5 text-[11px] text-blue-700 dark:bg-blue-900/30 dark:text-blue-300"
                  >{{ s.name }}</span>
                </div>
              </div>
              <div v-if="item.already_member.length > 0" class="min-w-0 flex-1">
                <div class="mb-1.5 text-xs font-bold text-gray-500 dark:text-gray-400">อยู่ในกลุ่มอยู่แล้ว ({{ item.already_member.length }})</div>
                <div class="flex flex-wrap gap-1">
                  <span
                    v-for="s in item.already_member"
                    :key="s.user_id"
                    class="max-w-full truncate rounded-md bg-gray-100 px-1.5 py-0.5 text-[11px] text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                  >{{ s.name }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </template>

    <template #footer>
      <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-3 w-full">
        <button 
          v-if="step > 1"
          @click="step--" 
          class="min-h-[44px] px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 w-full sm:w-auto"
          :disabled="isLoading"
        >
          ย้อนกลับ
        </button>
        <button 
          v-else
          @click="emit('close')" 
          class="min-h-[44px] px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 w-full sm:w-auto"
        >
          ยกเลิก
        </button>

        <button 
          v-if="step === 1"
          @click="goToStep2"
          class="min-h-[44px] px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg disabled:opacity-50 w-full sm:w-auto"
          :disabled="selectedClassroomIds.length === 0"
        >
          ถัดไป
        </button>

        <button 
          v-if="step === 2"
          @click="goToStep3"
          class="min-h-[44px] px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg flex items-center justify-center gap-2 disabled:opacity-50 w-full sm:w-auto"
          :disabled="isLoading"
        >
          <Icon v-if="isLoading" icon="svg-spinners:ring-resize" class="w-5 h-5" />
          ดูตัวอย่างผลลัพธ์
        </button>

        <button 
          v-if="step === 3"
          @click="confirmImport"
          class="min-h-[44px] px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg flex items-center justify-center gap-2 disabled:opacity-50 w-full sm:w-auto"
          :disabled="isLoading"
        >
          <Icon v-if="isLoading" icon="svg-spinners:ring-resize" class="w-5 h-5" />
          ยืนยันดึงรายชื่อ
        </button>
      </div>
    </template>
  </DialogModal>
</template>
