<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { useCourseGroupStore } from '~/stores/courseGroup'

const props = defineProps<{
  courseId: number | string | undefined
  groupId: number | string | undefined
  groupName: string | undefined
  show: boolean
}>()

const emit = defineEmits(['close', 'synced'])

const groupStore = useCourseGroupStore()

const isLoading = ref(false)
const error = ref<string | null>(null)
const mode = ref<'A' | 'B'>('B') // A: Select classroom, B: Sync

// State for A
const academicYears = ref<any[]>([])
const classrooms = ref<any[]>([])
const selectedAcademicYearId = ref<number | null>(null)
const selectedClassroomIds = ref<number[]>([])
const classroomSearch = ref('')

// State for B
const linkedClassrooms = ref<any[]>([])
const classroomSyncedAt = ref<string | null>(null)
const syncResult = ref<any>(null)
const detachMemberIds = ref<number[]>([])

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

const checkSyncState = async () => {
  if (!props.courseId || !props.groupId) return
  isLoading.value = true
  error.value = null
  syncResult.value = null
  detachMemberIds.value = []
  
  try {
    const payload = { dry_run: true }
    const res = await groupStore.syncGroupClassroom(props.courseId, props.groupId, payload)
    linkedClassrooms.value = res.classrooms || []
    classroomSyncedAt.value = res.classroom_synced_at || null
    syncResult.value = res
    mode.value = 'B'
  } catch (err: any) {
    if (err.status === 422 || err.response?.status === 422 || err.data?.message?.includes('ยังไม่ได้ผูก')) {
      mode.value = 'A'
      fetchSources()
    } else {
      error.value = err.data?.message || 'ไม่สามารถตรวจสอบการซิงค์ได้'
    }
  } finally {
    isLoading.value = false
  }
}

watch(() => props.show, (newVal) => {
  if (newVal) {
    selectedClassroomIds.value = []
    checkSyncState()
  }
})

watch(selectedAcademicYearId, (newVal, oldVal) => {
  if (mode.value === 'A' && newVal && oldVal && newVal !== oldVal) {
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

const linkClassrooms = async () => {
  if (!props.courseId || !props.groupId) return
  isLoading.value = true
  error.value = null
  try {
    await groupStore.linkGroupClassrooms(props.courseId, props.groupId, selectedClassroomIds.value)
    await checkSyncState()
  } catch (err: any) {
    error.value = err.data?.message || 'ไม่สามารถผูกห้องเรียนได้'
    isLoading.value = false
  }
}

const confirmSync = async () => {
  if (!props.courseId || !props.groupId) return
  isLoading.value = true
  error.value = null
  try {
    const payload = {
      dry_run: false,
      detach_member_ids: detachMemberIds.value
    }
    await groupStore.syncGroupClassroom(props.courseId, props.groupId, payload)
    emit('synced')
    emit('close')
  } catch (err: any) {
    error.value = err.data?.message || 'ไม่สามารถซิงค์ข้อมูลได้'
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
          <Icon icon="fluent:arrow-sync-24-filled" class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
        </div>
        <div>
          <h3 class="text-xl font-bold text-gray-900 dark:text-white">ซิงค์ข้อมูลกับห้องเรียน</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400">กลุ่ม: {{ groupName }}</p>
        </div>
      </div>
    </template>

    <template #content>
      <div v-if="error" class="mb-4 p-3 bg-red-50 text-red-600 border border-red-200 rounded-lg text-sm">
        {{ error }}
      </div>

      <!-- MODE A: Select Classroom -->
      <div v-if="mode === 'A'" class="space-y-4">
        <div class="p-3 bg-blue-50 text-blue-700 rounded-xl text-sm flex items-start gap-2">
          <Icon icon="heroicons:information-circle" class="w-5 h-5 flex-shrink-0 mt-0.5" />
          <span>กลุ่มนี้ยังไม่ได้ผูกกับห้องเรียน กรุณาเลือกห้องเรียนที่ต้องการผูกข้อมูล</span>
        </div>

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
          ไม่พบห้องเรียน
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
                </div>
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- MODE B: Syncing -->
      <div v-else-if="mode === 'B' && syncResult" class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
          <div>
            <div class="flex flex-wrap items-center gap-2">
              <span class="text-sm text-gray-500">ผูกกับ:</span>
              <span v-for="c in linkedClassrooms" :key="c.id" class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded text-xs font-bold">{{ c.name }}</span>
            </div>
            <div class="text-xs text-gray-400 mt-1">
              ซิงค์ล่าสุด: {{ classroomSyncedAt ? new Date(classroomSyncedAt).toLocaleString('th-TH') : 'ยังไม่เคยซิงค์' }}
            </div>
          </div>
          <button @click="mode = 'A'; fetchSources()" class="text-xs text-blue-600 hover:underline min-h-[44px] px-2 whitespace-nowrap">
            เปลี่ยนห้องที่ผูก
          </button>
        </div>

        <div v-if="syncResult.to_add?.length === 0 && syncResult.missing?.length === 0" class="p-4 sm:p-8 text-center bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl">
          <Icon icon="fluent:checkmark-circle-24-filled" class="w-12 h-12 text-emerald-500 mx-auto mb-2" />
          <h4 class="font-bold text-emerald-700 dark:text-emerald-400">ตรงกับห้องเรียนอยู่แล้ว</h4>
          <p class="text-sm text-emerald-600/80 mt-1">จำนวนสมาชิก {{ syncResult.unchanged_count }} คน ไม่มีการเปลี่ยนแปลง</p>
        </div>

        <div v-else class="space-y-4">
          <div v-if="syncResult.no_user_account?.length > 0" class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl">
            <h4 class="font-bold text-yellow-800 dark:text-yellow-500 mb-2">มีนักเรียน {{ syncResult.no_user_account.length }} คนที่ยังไม่มีบัญชีผู้ใช้ในระบบ จะข้ามไปก่อน</h4>
            <div class="max-h-24 overflow-y-auto text-sm text-yellow-700 dark:text-yellow-600/80">
              <div v-for="student in syncResult.no_user_account" :key="student.student_id">
                - {{ student.name }}
              </div>
            </div>
          </div>

          <div v-if="syncResult.moving_from_other_group?.length > 0" class="p-4 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-xl">
            <h4 class="font-bold text-orange-800 dark:text-orange-500 mb-2">นักเรียน {{ syncResult.moving_from_other_group.length }} คนอยู่กลุ่มอื่นในรายวิชานี้อยู่แล้ว ระบบจะย้ายมากลุ่มใหม่ให้อัตโนมัติ</h4>
            <div class="max-h-24 overflow-y-auto text-sm text-orange-700 dark:text-orange-600/80">
              <div v-for="student in syncResult.moving_from_other_group" :key="student.user_id">
                - {{ student.name }} (จาก {{ student.from_group.name }})
              </div>
            </div>
          </div>

          <div class="flex flex-col md:flex-row gap-4">
            <!-- Left: To Add -->
            <div class="flex-1 border border-gray-200 dark:border-gray-700 rounded-xl p-4 flex flex-col">
              <h4 class="font-bold text-blue-600 mb-2 flex items-center gap-2">
                <Icon icon="fluent:person-add-24-filled" class="w-5 h-5" />
                จะเพิ่มเข้ากลุ่ม ({{ syncResult.to_add?.length || 0 }} คน)
              </h4>
              <div class="flex-1">
                <div v-if="syncResult.to_add?.length === 0" class="text-sm text-gray-500 italic py-2">ไม่มีสมาชิกใหม่</div>
                <div v-else class="flex flex-wrap gap-1">
                  <span
                    v-for="s in syncResult.to_add"
                    :key="s.user_id"
                    class="max-w-full truncate rounded-md bg-blue-50 px-1.5 py-0.5 text-[11px] text-blue-700 dark:bg-blue-900/30 dark:text-blue-300"
                  >{{ s.name }}</span>
                </div>
              </div>
            </div>
            
            <!-- Right: Missing (To Remove) -->
            <div class="flex-1 border border-gray-200 dark:border-gray-700 rounded-xl p-4 flex flex-col bg-red-50/50 dark:bg-red-900/10">
              <h4 class="font-bold text-red-600 mb-2 flex items-center gap-2">
                <Icon icon="fluent:person-delete-24-filled" class="w-5 h-5" />
                อยู่ในกลุ่มแต่ไม่อยู่ในห้องแล้ว ({{ syncResult.missing?.length || 0 }} คน)
              </h4>
              <p class="text-[11px] text-gray-500 mb-2">การถอดออกจากกลุ่มจะไม่ลบคะแนนหรือประวัติการเช็คชื่อ นักเรียนยังเป็นสมาชิกรายวิชาอยู่</p>
              
              <div class="flex-1">
                <div v-if="syncResult.missing?.length === 0" class="text-sm text-gray-500 italic py-2">ไม่มีสมาชิกที่หลุดจากห้อง</div>
                <label v-for="s in syncResult.missing" :key="s.course_member_id" class="flex items-center gap-2 py-1.5 border-b border-gray-100 dark:border-gray-800 last:border-0 cursor-pointer min-h-[44px]">
                  <input type="checkbox" :value="s.course_member_id" v-model="detachMemberIds" class="w-4 h-4 text-red-600 rounded border-gray-300 focus:ring-red-500 flex-shrink-0">
                  <div class="text-sm truncate min-w-0 flex-1 break-words">
                    <span class="text-gray-400 mr-2">{{ s.order_number || '-' }}</span> {{ s.name }}
                  </div>
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <template #footer>
      <div class="flex flex-col-reverse sm:flex-row sm:items-center justify-end gap-3 w-full">
        <button 
          @click="emit('close')" 
          class="min-h-[44px] px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 w-full sm:w-auto"
        >
          ยกเลิก
        </button>

        <button 
          v-if="mode === 'A'"
          @click="linkClassrooms"
          class="min-h-[44px] px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg flex items-center justify-center gap-2 disabled:opacity-50 w-full sm:w-auto"
          :disabled="selectedClassroomIds.length === 0 || isLoading"
        >
          <Icon v-if="isLoading" icon="svg-spinners:ring-resize" class="w-5 h-5" />
          บันทึกการผูกห้องเรียน
        </button>

        <button 
          v-else-if="mode === 'B'"
          @click="confirmSync"
          class="min-h-[44px] px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg flex items-center justify-center gap-2 disabled:opacity-50 w-full sm:w-auto"
          :disabled="isLoading"
        >
          <Icon v-if="isLoading" icon="svg-spinners:ring-resize" class="w-5 h-5" />
          ยืนยันซิงค์
        </button>
      </div>
    </template>
  </DialogModal>
</template>
