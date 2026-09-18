<script setup lang="ts">
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

definePageMeta({
  layout: false
})

// 🔴 หน้านี้ตั้งใจวางไว้นอก `admin/` — เพราะ `academies/[name]/admin.vue` เป็น "หน้าแม่"
// ที่วาดแถบเมนูฝ่ายจัดการกับหัวการ์ดโรงเรียนไว้รอบ ๆ ลูกทุกหน้า
// `layout: false` ตัดได้แค่ layout ของ Nuxt ตัดหน้าแม่ไม่ได้ ⇒ ถ้าวางไว้ใต้ admin/
// แถบเมนูจะติดไปในกระดาษที่พิมพ์ด้วย · ส่วน `academies/[name].vue` เป็นหน้าแม่ที่เรนเดอร์
// แค่ <NuxtPage /> สำหรับ child route จึงได้กระดาษเปล่าสะอาดตามต้องการ

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)
const academyId = ref<number | null>(null)

const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)

const isLoading = ref(true)
const isLoadingSchedules = ref(false)

const academicYears = ref<any[]>([])
const selectedAcademicYear = ref<number | null>(null)
const selectedSemester = ref<number | null>(null)

const classrooms = ref<any[]>([])
const teachers = ref<any[]>([])
const periodSets = ref<any[]>([])
const allSchedules = ref<any[]>([])

const scope = ref<'classroom' | 'grade' | 'all-classrooms' | 'teacher' | 'all-teachers'>('classroom')
const selectedClassroom = ref<number | null>(null)
const selectedGrade = ref<string | null>(null)
const selectedTeacher = ref<number | null>(null)

const availableGrades = computed(() => {
  const grades = classrooms.value.map((c: any) => c.grade_level).filter(Boolean)
  return [...new Set(grades)]
})

const availableSemesters = computed(() => {
  const year = academicYears.value.find((y: any) => y.id === selectedAcademicYear.value)
  return year?.semesters || []
})

const selectedSemesterName = computed(() => {
  const sem = availableSemesters.value.find((s: any) => s.id === selectedSemester.value)
  if (!sem) return ''
  const year = academicYears.value.find((y: any) => y.id === selectedAcademicYear.value)
  return `ภาคเรียนที่ ${sem.semester_number} ปีการศึกษา ${year?.name || ''}`
})

// ขอบเขตที่ต้องเลือกเป้าหมายก่อน — ถ้ายังไม่เลือก ต้องไม่พิมพ์อะไรเลย
// (ไม่งั้น scope='classroom' ที่ยังไม่เลือกห้องจะกลายเป็นพิมพ์ทั้ง 53 ห้อง)
const needsTarget = computed(() => {
  if (scope.value === 'classroom') return !selectedClassroom.value
  if (scope.value === 'grade') return !selectedGrade.value
  if (scope.value === 'teacher') return !selectedTeacher.value

  return false
})

const printSheets = computed(() => {
  if (isLoadingSchedules.value || needsTarget.value) return []

  const sheets: any[] = []

  if (scope.value === 'classroom' || scope.value === 'grade' || scope.value === 'all-classrooms') {
    let targetClassrooms = [...classrooms.value]
    if (scope.value === 'classroom' && selectedClassroom.value) {
      targetClassrooms = targetClassrooms.filter(c => c.id === selectedClassroom.value)
    } else if (scope.value === 'grade' && selectedGrade.value) {
      targetClassrooms = targetClassrooms.filter(c => c.grade_level === selectedGrade.value)
    }
    
    // เทียบแบบ numeric ไม่งั้นได้ ม.1/1, ม.1/10, ม.1/2 ... บนกระดาษที่พิมพ์ออกมา
    targetClassrooms.sort((a, b) => (a.name || '').localeCompare(b.name || '', 'th', { numeric: true }))
    
    for (const classroom of targetClassrooms) {
      const classroomSchedules = allSchedules.value.filter(s => s.classroom?.id === classroom.id)
      
      const timetableMap = new Map<number, any[]>()
      for (let day = 1; day <= 7; day++) {
        timetableMap.set(day, [])
      }
      
      for (const schedule of classroomSchedules) {
        const day = schedule.day_of_week
        if (timetableMap.has(day)) {
          timetableMap.get(day)!.push(schedule)
        }
      }
      
      const timetable = Array.from(timetableMap.entries()).map(([day, schedules]) => {
        schedules.sort((a, b) => (a.start_time || '').localeCompare(b.start_time || ''))
        return { day, schedules }
      })
      
      sheets.push({
        id: `classroom-${classroom.id}`,
        heading: `ตารางเรียน ชั้น ${classroom.name}`,
        subheading: selectedSemesterName.value,
        viewType: 'classroom',
        gradeLevel: classroom.grade_level,
        timetable
      })
    }
  } else if (scope.value === 'teacher' || scope.value === 'all-teachers') {
    let targetTeachers = [...teachers.value]
    if (scope.value === 'teacher' && selectedTeacher.value) {
      targetTeachers = targetTeachers.filter(t => t.user_id === selectedTeacher.value)
    } else if (scope.value === 'all-teachers') {
      const teacherIdsWithSchedules = new Set(allSchedules.value.filter(s => s.teacher).map(s => s.teacher.id))
      targetTeachers = targetTeachers.filter(t => teacherIdsWithSchedules.has(t.user_id))
    }
    
    targetTeachers.sort((a, b) => {
      const nameA = a.member_name || a.user?.name || ''
      const nameB = b.member_name || b.user?.name || ''
      return nameA.localeCompare(nameB, 'th', { numeric: true })
    })
    
    for (const teacher of targetTeachers) {
      const teacherSchedules = allSchedules.value.filter(s => s.teacher?.id === teacher.user_id)
      
      const timetableMap = new Map<number, any[]>()
      for (let day = 1; day <= 7; day++) {
        timetableMap.set(day, [])
      }
      
      for (const schedule of teacherSchedules) {
        const day = schedule.day_of_week
        if (timetableMap.has(day)) {
          timetableMap.get(day)!.push(schedule)
        }
      }
      
      const timetable = Array.from(timetableMap.entries()).map(([day, schedules]) => {
        schedules.sort((a, b) => (a.start_time || '').localeCompare(b.start_time || ''))
        return { day, schedules }
      })
      
      const teacherName = teacher.member_name || teacher.user?.name || '-'
      sheets.push({
        id: `teacher-${teacher.user_id}`,
        heading: `ตารางสอน ${teacherName}`,
        subheading: selectedSemesterName.value,
        viewType: 'teacher',
        gradeLevel: null,
        timetable
      })
    }
  }
  
  return sheets
})

const fetchSchedules = async () => {
  if (!academyId.value || !selectedSemester.value) return
  isLoadingSchedules.value = true
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/schedules`, {
      params: { semester_id: selectedSemester.value }
    })
    if (response.success) {
      allSchedules.value = response.data || []
    }
  } catch (err) {
    console.error('Failed to fetch schedules', err)
  } finally {
    isLoadingSchedules.value = false
  }
}

watch(selectedSemester, () => {
  fetchSchedules()
})

const fetchClassrooms = async (yearId: number) => {
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/classrooms`, {
      params: { academic_year_id: yearId }
    })
    if (response.success) {
      classrooms.value = response.classrooms || response.data || []
    }
  } catch (err) {
    console.error(err)
  }
}

watch(selectedAcademicYear, async (newVal) => {
  if (newVal && academyId.value) {
    await fetchClassrooms(newVal)
    const year = academicYears.value.find((y: any) => y.id === newVal)
    if (year && year.semesters?.length > 0) {
      const currentSem = year.semesters.find((s: any) => s.is_current) || year.semesters.sort((a: any, b: any) => a.semester_number - b.semester_number)[0]
      selectedSemester.value = currentSem.id
    } else {
      selectedSemester.value = null
    }
  }
})

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academyId.value = response.academy.id
      await fetchMyRole()
      
      if (!isAdmin.value && !can('schedule.view')) {
        navigateTo(`/academies/${academyName.value}`)
        return
      }
      
      const yearsRes: any = await api.get(`/api/academies/${academyId.value}/academic-years`)
      if (yearsRes.success && yearsRes.academicYears) {
        academicYears.value = yearsRes.academicYears
        const currentYear = academicYears.value.find((y: any) => y.is_current) || academicYears.value[0]
        if (currentYear) {
          selectedAcademicYear.value = currentYear.id
        }
      }
      
      const membersRes: any = await api.get(`/api/academies/${academyId.value}/members`, {
        params: { role: 'teacher', status: 2, per_page: 200 }
      })
      if (membersRes.success) {
        teachers.value = membersRes.members || []
      }
      
      const periodRes: any = await api.get(`/api/academies/${academyId.value}/schedule-period-sets`)
      if (periodRes.success) {
        periodSets.value = (periodRes.data || []).filter((s: any) => s.is_active)
      }
    }
  } catch (err) {
    console.error(err)
  } finally {
    isLoading.value = false
  }
})

const handlePrint = () => {
  window.print()
}

const isExporting = ref(false)
const handleExport = async () => {
  if (!academyId.value || !selectedSemester.value) return
  isExporting.value = true
  try {
    const params: any = { semester_id: selectedSemester.value }
    if (scope.value === 'classroom' && selectedClassroom.value) {
      params.classroom_id = selectedClassroom.value
    } else if (scope.value === 'teacher' && selectedTeacher.value) {
      params.teacher_id = selectedTeacher.value
    } else if (scope.value === 'grade' && selectedGrade.value) {
      params.grade_level = selectedGrade.value
    }
    
    // 🔴 `getBlob` ใช้ fetch ดิบ และอ่านเฉพาะ headers/timeout — **มันไม่สนใจ `params` เลย**
    // ถ้าส่งเป็น { params } query จะหายทั้งก้อน แล้วได้ไฟล์ของภาคเรียนปัจจุบันทั้งโรงเรียนแบบเงียบ ๆ
    // จึงต้องต่อ query string เองเหมือน admin/home-visits/export.vue
    const query = new URLSearchParams(
      Object.entries(params).map(([key, value]) => [key, String(value)])
    ).toString()

    const { blob, filename } = await api.getBlob(`/api/academies/${academyId.value}/schedules/export?${query}`)
    const objectUrl = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = objectUrl
    link.download = filename || `class-schedules.xlsx`
    document.body.appendChild(link)
    link.click()
    link.remove()
    URL.revokeObjectURL(objectUrl)
  } catch (err: any) {
    Swal.fire({
      icon: 'error',
      title: 'ไม่สามารถส่งออกได้',
      text: err.data?.message || 'ระบบส่งออกอาจจะยังไม่พร้อมใช้งาน'
    })
  } finally {
    isExporting.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
    <div class="print:hidden p-4 sm:p-6 bg-white dark:bg-gray-800 shadow-sm sticky top-0 z-10">
      <div class="max-w-7xl mx-auto space-y-4">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <NuxtLink :to="`/academies/${academyName}/admin/schedule`" class="min-h-[44px] sm:min-h-0 flex-shrink-0 inline-flex items-center justify-center p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
              <Icon icon="fluent:arrow-left-24-regular" class="w-6 h-6 text-gray-700 dark:text-gray-300" />
            </NuxtLink>
            <div class="min-w-0 flex-1 break-words">
              <h1 class="text-xl font-bold text-gray-900 dark:text-white truncate">พิมพ์ตารางเรียน</h1>
              <p class="text-sm text-gray-500 dark:text-gray-400 truncate">เลือกขอบเขตที่ต้องการพิมพ์หรือส่งออก Excel</p>
            </div>
          </div>
          
          <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto flex-shrink-0">
            <button @click="handlePrint" class="min-h-[44px] sm:min-h-0 min-w-0 flex-1 break-words sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium transition-colors">
              <Icon icon="fluent:print-24-regular" class="w-5 h-5 flex-shrink-0" />
              <span>พิมพ์</span>
            </button>
            <button @click="handleExport" :disabled="isExporting" class="min-h-[44px] sm:min-h-0 min-w-0 flex-1 break-words sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-medium transition-colors disabled:opacity-50">
              <Icon v-if="isExporting" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin flex-shrink-0" />
              <Icon v-else icon="fluent:document-excel-24-regular" class="w-5 h-5 flex-shrink-0" />
              <span>ส่งออก Excel</span>
            </button>
          </div>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <select v-model="selectedAcademicYear" class="min-h-[44px] w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white">
            <option :value="null" disabled>เลือกปีการศึกษา</option>
            <option v-for="year in academicYears" :key="year.id" :value="year.id">{{ year.name }}</option>
          </select>
          
          <select v-model="selectedSemester" :disabled="availableSemesters.length === 0" class="min-h-[44px] w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white">
            <option :value="null" disabled>เลือกภาคเรียน</option>
            <option v-for="sem in availableSemesters" :key="sem.id" :value="sem.id">{{ sem.name }}</option>
          </select>
          
          <select v-model="scope" class="min-h-[44px] w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white">
            <option value="classroom">ห้องเรียนที่เลือก</option>
            <option value="grade">ระดับชั้น</option>
            <option value="all-classrooms">ทุกห้องเรียน</option>
            <option value="teacher">ครูที่เลือก</option>
            <option value="all-teachers">ครูทุกคนที่มีคาบสอน</option>
          </select>
          
          <div v-if="scope === 'classroom'">
            <CommonSearchableSelect
              v-model="selectedClassroom"
              :options="classrooms"
              option-value="id"
              option-label="name"
              placeholder="เลือกห้องเรียน"
              search-placeholder="ค้นหาห้อง"
              class="w-full"
            />
          </div>
          <div v-else-if="scope === 'grade'">
            <select v-model="selectedGrade" class="min-h-[44px] w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white">
              <option :value="null" disabled>เลือกระดับชั้น</option>
              <option v-for="grade in availableGrades" :key="grade" :value="grade">{{ grade }}</option>
            </select>
          </div>
          <div v-else-if="scope === 'teacher'">
            <CommonSearchableSelect
              v-model="selectedTeacher"
              :options="teachers"
              option-value="user_id"
              :option-label="(teacher: any) => teacher.member_name || teacher.user?.name || '-'"
              placeholder="เลือกครู"
              search-placeholder="ค้นหาครู"
              class="w-full"
            />
          </div>
        </div>
        
        <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
          จะพิมพ์ {{ printSheets.length }} แผ่น
        </div>
      </div>
    </div>

    <div v-if="isLoading || isLoadingSchedules" class="flex justify-center items-center py-20 print:hidden">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="p-4 sm:p-8 max-w-[1000px] mx-auto print:p-0 print:max-w-none">
      <div v-if="needsTarget" class="text-center py-20 text-gray-500 dark:text-gray-400 print:hidden">
        เลือกห้องเรียน / ระดับชั้น / ครู ที่ต้องการพิมพ์ก่อน
      </div>
      <div v-else-if="printSheets.length === 0" class="text-center py-20 text-gray-500 dark:text-gray-400 print:hidden">
        ไม่มีข้อมูลตารางเรียนตามขอบเขตที่เลือก
      </div>
      
      <div v-for="sheet in printSheets" :key="sheet.id" class="print-sheet-wrap mb-8 print:mb-0 bg-white shadow-lg print:shadow-none overflow-x-auto print:overflow-visible rounded-xl print:rounded-none">
        <div class="min-w-[800px] print:min-w-0">
          <SchoolSchedulePrintSheet
            :academy-name="academyName"
            :heading="sheet.heading"
            :subheading="sheet.subheading"
            :view-type="sheet.viewType"
            :grade-level="sheet.gradeLevel"
            :period-sets="periodSets"
            :timetable="sheet.timetable"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<style>
@media print {
  @page { size: A4 landscape; margin: 8mm; }
  .print\:hidden { display: none !important; }

  /* 🔴 ตัวแบ่งหน้าต้องอยู่ที่ "กล่องห่อ" ไม่ใช่ที่ .print-sheet
     เพราะ .print-sheet เป็นลูกคนเดียวของกล่องห่อของตัวเอง ⇒ `.print-sheet:last-child`
     เป็นจริงกับ *ทุกแผ่น* ⇒ ได้ page-break-after: auto ทั้งหมด = ไม่ขึ้นหน้าใหม่เลยสักแผ่น
     กล่องห่อเป็นพี่น้องกันจริง `:last-child` จึงหมายถึงแผ่นสุดท้ายจริง ๆ */
  .print-sheet-wrap {
    page-break-after: always;
    break-after: page;
    width: 100%;
  }
  .print-sheet-wrap:last-child { page-break-after: auto; break-after: auto; }
  .print-sheet { width: 100%; }
  .print-sheet table { page-break-inside: avoid; }
}
</style>
