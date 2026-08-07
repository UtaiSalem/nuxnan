<script setup lang="ts">
/**
 * Academy Admin - Classroom Detail Management Page
 * จัดการห้องเรียนเชิงลึก: ภาพรวม, นักเรียน, ครูและสมาชิก, การเข้าเรียน, วิชาและผลการเรียน, ประกาศ, รายงาน
 */
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import * as XLSX from 'xlsx'
import AssignHomeroomTeacherModal from '~/components/academy/AssignHomeroomTeacherModal.vue'

definePageMeta({
  layout: 'main',
})

const route = useRoute()
const router = useRouter()
const api = useApi()
const schoolApi = useSchoolManagement()

const academyName = computed(() => route.params.name as string)
const classroomId = computed(() => Number(route.params.id))

// Core State
const academy = ref<any>(null)
const classroom = ref<any>(null)
const students = ref<any[]>([])
const members = ref<any[]>([])
const isLoading = ref(true)
const errorMessage = ref('')

// Tabs config
const activeTab = ref<'overview' | 'students' | 'members' | 'attendance' | 'grades' | 'announcements' | 'reports'>('overview')
const tabs = [
  { key: 'overview', label: 'ภาพรวม', icon: 'fluent:apps-24-regular' },
  { key: 'students', label: 'นักเรียน', icon: 'fluent:people-24-regular' },
  { key: 'members', label: 'ครูและสมาชิก', icon: 'fluent:person-board-24-regular' },
  { key: 'attendance', label: 'การเข้าเรียน', icon: 'fluent:calendar-checkmark-24-regular' },
  { key: 'grades', label: 'วิชาและผลการเรียน', icon: 'fluent:hat-graduation-24-regular' },
  { key: 'announcements', label: 'ประกาศ', icon: 'fluent:megaphone-24-regular' },
  { key: 'reports', label: 'รายงาน', icon: 'fluent:document-text-24-regular' },
]

// Students tab state
const studentSearch = ref('')
const selectedStatusFilter = ref('active')
const selectedGenderFilter = ref('all')
const studentSortKey = ref<'student_number' | 'student_id' | 'name'>('student_number')
const studentSortDir = ref<'asc' | 'desc'>('asc')
const showAddStudentModal = ref(false)
const searchQueryAddStudent = ref('')
const availableStudents = ref<any[]>([])
const isLoadingAvailableStudents = ref(false)
const availableStudentsError = ref('')
const availableStudentsTotal = ref(0)
let availableStudentsSearchTimer: ReturnType<typeof setTimeout> | null = null
let availableStudentsRequestId = 0
const showTransferStudentModal = ref(false)
const selectedStudentForTransfer = ref<any>(null)
const transferToClassroomId = ref<number | null>(null)
const transferReason = ref('ปรับสมดุลจำนวนนักเรียน')
const otherClassrooms = ref<any[]>([])
const editingStudentNumberId = ref<number | null>(null)
const editingStudentNumberValue = ref<number | null>(null)
const showProfileDrawer = ref(false)
const selectedStudentForProfile = ref<any>(null)
const isLoadingProfile = ref(false)
const showAssignHomeroomModal = ref(false)
const showRenumberModal = ref(false)
const isLoadingRenumberPreview = ref(false)
const isApplyingRenumber = ref(false)
const renumberPreview = ref<any[]>([])
const renumberChangedCount = ref(0)
const renumberTotal = ref(0)

// Members tab state
const showAddMemberModal = ref(false)
const searchQueryMember = ref('')
const availableUsers = ref<any[]>([])
const isLoadingAvailableUsers = ref(false)
const selectedMemberRole = ref<'teacher' | 'co_teacher' | 'observer'>('co_teacher')

// Attendance tab state
const attendanceDate = ref(new Date().toISOString().split('T')[0])
const activeSession = ref<any>(null)
const isSessionLoading = ref(false)
const isSavingAttendance = ref(false)
const isClosingSession = ref(false)
const attendanceStatuses = ref<Record<number, { status: string; remark: string }>>({})

// Mock / Local Data for Grades & Subjects (Phase 2 preview but fully interactive)
const subjectsList = ref([
  { id: 1, code: 'MA101', name: 'คณิตศาสตร์พื้นฐาน', teacher: 'ครูสมเจตน์ ใจดี', credit: 1.5 },
  { id: 2, code: 'SC101', name: 'วิทยาศาสตร์ทั่วไป', teacher: 'ครูพรทิพย์ สวยงาม', credit: 1.5 },
  { id: 3, code: 'EN101', name: 'ภาษาอังกฤษเพื่อการสื่อสาร', teacher: 'Teacher John Doe', credit: 1.0 },
  { id: 4, code: 'TH101', name: 'ภาษาไทยเพื่อการสร้างสรรค์', teacher: 'ครูสุดา รักไทย', credit: 1.0 },
])

const studentGrades = ref<Record<number, Record<number, { score: number; grade: string }>>>({})
const mockGPAData = ref<Record<number, { gpa: number; gpax: number }>>({})

// Mock announcements (Local state for interactivity)
const announcements = ref([
  {
    id: 1,
    title: 'เตรียมความพร้อมสอบกลางภาคเรียนที่ 1',
    content: 'ขอให้นักเรียนทุกคนทบทวนบทเรียนวิชาคณิตศาสตร์และวิทยาศาสตร์ สำหรับการสอบกลางภาคในสัปดาห์หน้า ระหว่างวันที่ 20-22 กรกฎาคมนี้',
    date: '2026-07-12T09:00:00Z',
    author: 'ครูสมศรี มีสุข',
  },
  {
    id: 2,
    title: 'การส่งสมุดแบบฝึกหัดภาษาอังกฤษ',
    content: 'ให้นักเรียนรวบรวมสมุดแบบฝึกหัดบทที่ 3 ส่งที่โต๊ะ Teacher John ภายในวันศุกร์นี้ก่อนเวลา 16.00 น.',
    date: '2026-07-10T14:30:00Z',
    author: 'Teacher John Doe',
  },
])
const showAddAnnouncementModal = ref(false)
const newAnnouncementTitle = ref('')
const newAnnouncementContent = ref('')

// Helpers
const classroomName = computed(() => classroom.value?.name || 'ห้องเรียน')
const studentCount = computed(() => students.value.length || 0)
const capacity = computed(() => classroom.value?.capacity || 40)
const occupancy = computed(() => Math.min(Math.round((studentCount.value / capacity.value) * 100), 100))

// Resolve strictly from homeroom_teacher_id. Falling back to "any member with
// the teacher role" would keep showing someone after the post is cleared.
const homeroomTeacher = computed(() => {
  const teacherId = classroom.value?.homeroom_teacher_id
  if (!teacherId) return null

  return classroom.value?.homeroom_teacher
    || members.value.find((m: any) => (m.user_id || m.user?.id) === teacherId)
    || null
})

const clearHomeroomTeacher = async () => {
  const result = await Swal.fire({ title: 'ล้างครูประจำชั้น?', text: 'ห้องนี้จะยังไม่มีครูประจำชั้นหลัก', icon: 'warning', showCancelButton: true, confirmButtonText: 'ล้าง', cancelButtonText: 'ยกเลิก' })
  if (!result.isConfirmed) return
  try {
    await api.patch(`/api/academies/${academy.value.id}/classrooms/${classroomId.value}`, { homeroom_teacher_id: null })
    await loadClassroom()
    await Swal.fire({ icon: 'success', title: 'ล้างครูประจำชั้นแล้ว', timer: 1200, showConfirmButton: false })
  } catch (error: any) {
    await Swal.fire({ icon: 'error', title: 'ไม่สามารถล้างครูประจำชั้นได้', text: error?.data?.message || '' })
  }
}

const assignTeacherFromMember = async (member: any) => {
  try {
    await api.patch(`/api/academies/${academy.value.id}/classrooms/${classroomId.value}`, { homeroom_teacher_id: member.user_id || member.user?.id })
    await loadClassroom()
  } catch (error: any) {
    await Swal.fire({ icon: 'error', title: 'ไม่สามารถแต่งตั้งครูได้', text: error?.data?.message || '' })
  }
}

const classroomMembersList = computed(() => {
  return members.value.filter((m: any) => m.role !== 'student')
})

const studentName = (student: any) => {
  if (student.user?.name) return student.user.name
  return [student.title_prefix_th, student.first_name_th, student.last_name_th].filter(Boolean).join(' ') || student.name || '-'
}

// เรียงตามชื่อจริง-นามสกุล โดยไม่เอาคำนำหน้ามาคิด ไม่งั้น "เด็กชาย" ทั้งหมดจะถูกจับกองไว้ด้วยกัน
const studentSortName = (student: any) => {
  const parts = [student.first_name_th, student.last_name_th].filter(Boolean).join(' ').trim()
  return parts || studentName(student)
}

// Attendance summary computes
const currentSessionSummary = computed(() => {
  if (!activeSession.value) return { total: 0, present: 0, late: 0, leave: 0, absent: 0 }
  const counts = { total: students.value.length, present: 0, late: 0, leave: 0, absent: 0 }
  
  students.value.forEach((s) => {
    const state = attendanceStatuses.value[s.id || s.user_id]
    if (state) {
      if (state.status === 'present') counts.present++
      else if (state.status === 'late') counts.late++
      else if (state.status === 'leave') counts.leave++
      else if (state.status === 'absent') counts.absent++
    } else {
      counts.absent++ // Default absent if not checked
    }
  })
  return counts
})

// Load Classroom Data
const loadClassroom = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const academyResponse: any = await api.get(`/api/academies/${academyName.value}`)
    if (!academyResponse.success) throw new Error('ไม่พบโรงเรียน')
    academy.value = academyResponse.academy

    const response: any = await api.get(`/api/academies/${academy.value.id}/classrooms/${classroomId.value}`)
    if (!response.success || !response.classroom) throw new Error('ไม่พบห้องเรียน')
    
    classroom.value = response.classroom
    students.value = response.classroom.students || []
    members.value = response.classroom.members || []
    
    // Generate realistic grades and GPAs for interactive view
    generateMockGrades()
  } catch (error: any) {
    errorMessage.value = error?.message || 'ไม่สามารถโหลดข้อมูลห้องเรียนได้'
  } finally {
    isLoading.value = false
  }
}

// Generate Mock Academic Data for demonstration
const generateMockGrades = () => {
  students.value.forEach((s) => {
    const stdId = s.id
    studentGrades.value[stdId] = {}
    subjectsList.value.forEach((subj) => {
      // Deterministic score based on student ID to keep it consistent
      const score = 55 + ((stdId * subj.id) % 41)
      let grade = 'F'
      if (score >= 80) grade = 'A'
      else if (score >= 75) grade = 'B+'
      else if (score >= 70) grade = 'B'
      else if (score >= 65) grade = 'C+'
      else if (score >= 60) grade = 'C'
      else if (score >= 55) grade = 'D+'
      else if (score >= 50) grade = 'D'
      
      studentGrades.value[stdId][subj.id] = { score, grade }
    })
    
    // Calculate GPA
    const sumGrades = subjectsList.value.reduce((acc, subj) => {
      const g = studentGrades.value[stdId][subj.id].grade
      let val = 0
      if (g === 'A') val = 4.0
      else if (g === 'B+') val = 3.5
      else if (g === 'B') val = 3.0
      else if (g === 'C+') val = 2.5
      else if (g === 'C') val = 2.0
      else if (g === 'D+') val = 1.5
      else if (g === 'D') val = 1.0
      return acc + (val * subj.credit)
    }, 0)
    
    const totalCredits = subjectsList.value.reduce((acc, s) => acc + s.credit, 0)
    const gpa = Math.round((sumGrades / totalCredits) * 100) / 100
    const gpax = Math.round((gpa - 0.1 + (stdId % 3) * 0.1) * 100) / 100
    mockGPAData.value[stdId] = { gpa, gpax }
  })
}

// Roster Filtered Students
const filteredStudents = computed(() => {
  return students.value.filter((s) => {
    const name = studentName(s).toLowerCase()
    const code = (s.student_id || '').toLowerCase()
    const nick = (s.nickname || '').toLowerCase()
    const query = studentSearch.value.toLowerCase()
    const matchesSearch = name.includes(query) || code.includes(query) || nick.includes(query)
    
    const matchesStatus = selectedStatusFilter.value === 'all' || s.status === selectedStatusFilter.value
    
    let matchesGender = true
    if (selectedGenderFilter.value === 'male') matchesGender = s.gender === 1
    else if (selectedGenderFilter.value === 'female') matchesGender = s.gender === 0
    
    return matchesSearch && matchesStatus && matchesGender
  })
})

// gender: 1 = ชาย, 0 = หญิง, null = ยังไม่ระบุ. The old inline
// `gender === 1 ? 'ชาย' : 'หญิง'` labelled every unspecified student as หญิง.
const genderLabel = (gender: number | null | undefined) => {
  if (gender === 1) return 'ชาย'
  if (gender === 0) return 'หญิง'
  return 'ไม่ระบุ'
}

const genderBadgeClass = (gender: number | null | undefined) => {
  if (gender === 1) return 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300'
  if (gender === 0) return 'bg-pink-100 text-pink-800 dark:bg-pink-950 dark:text-pink-300'
  return 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'
}

// Roster sorting — เลขที่ / เลขประจำตัว / ชื่อ-สกุล
// Thai names need localeCompare('th'); student codes are numeric strings so
// they need `numeric: true` or "10" would sort before "9".
const sortedStudents = computed(() => {
  const dir = studentSortDir.value === 'asc' ? 1 : -1

  return [...filteredStudents.value].sort((a, b) => {
    if (studentSortKey.value === 'student_number') {
      // Students without a เลขที่ always sink to the bottom, either direction.
      const na = Number(a.student_number)
      const nb = Number(b.student_number)
      const aMissing = !Number.isFinite(na)
      const bMissing = !Number.isFinite(nb)
      if (aMissing || bMissing) return aMissing && bMissing ? 0 : aMissing ? 1 : -1
      return (na - nb) * dir
    }

    if (studentSortKey.value === 'student_id') {
      return String(a.student_id || '').localeCompare(String(b.student_id || ''), 'th', { numeric: true }) * dir
    }

    return studentSortName(a).localeCompare(studentSortName(b), 'th') * dir
  })
})

const toggleStudentSort = (key: 'student_number' | 'student_id' | 'name') => {
  if (studentSortKey.value === key) {
    studentSortDir.value = studentSortDir.value === 'asc' ? 'desc' : 'asc'
    return
  }
  studentSortKey.value = key
  studentSortDir.value = 'asc'
}

const studentSortIcon = (key: 'student_number' | 'student_id' | 'name') => {
  if (studentSortKey.value !== key) return 'fluent:arrow-sort-24-regular'
  return studentSortDir.value === 'asc' ? 'fluent:arrow-sort-up-24-filled' : 'fluent:arrow-sort-down-24-filled'
}

// Edit student number
const startEditStudentNumber = (student: any) => {
  editingStudentNumberId.value = student.id
  editingStudentNumberValue.value = student.student_number || null
}

const cancelEditStudentNumber = () => {
  editingStudentNumberId.value = null
  editingStudentNumberValue.value = null
}

const saveStudentNumber = async (studentId: number) => {
  if (editingStudentNumberValue.value === null || editingStudentNumberValue.value < 1) return
  try {
    const res: any = await api.patch(
      `/api/academies/${academy.value.id}/classrooms/${classroomId.value}/students/${studentId}/number`,
      { student_number: editingStudentNumberValue.value }
    )
    if (res.success) {
      const idx = students.value.findIndex((s) => s.id === studentId)
      if (idx !== -1) {
        students.value[idx].student_number = editingStudentNumberValue.value
        students.value.sort((a, b) => (a.student_number || 999) - (b.student_number || 999))
      }
      Swal.fire({ icon: 'success', title: 'อัปเดตเลขที่สำเร็จ', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 })
    }
  } catch (err: any) {
    Swal.fire('ข้อผิดพลาด', err.message || 'ไม่สามารถบันทึกเลขที่ได้', 'error')
  } finally {
    cancelEditStudentNumber()
  }
}

// Student Profile Drawer
const openStudentProfile = async (student: any) => {
  selectedStudentForProfile.value = student
  showProfileDrawer.value = true
  isLoadingProfile.value = true
  try {
    const res: any = await api.get(`/api/academies/${academy.value.id}/students/${student.id}`)
    if (res.success && res.student) {
      selectedStudentForProfile.value = res.student
    }
  } catch (err) {
    console.error('Failed to load student details:', err)
  } finally {
    isLoadingProfile.value = false
  }
}

// Remove Student
const removeStudent = async (student: any) => {
  const result = await Swal.fire({
    title: 'ยืนยันการคัดนักเรียนออก?',
    text: `คุณแน่ใจว่าต้องการคัด ${studentName(student)} ออกจากห้องเรียนนี้ใช่หรือไม่?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'คัดออก',
    cancelButtonText: 'ยกเลิก',
  })

  if (result.isConfirmed) {
    try {
      const res: any = await api.delete(`/api/academies/${academy.value.id}/classrooms/${classroomId.value}/students/${student.id}`)
      if (res.success) {
        students.value = students.value.filter((s) => s.id !== student.id)
        Swal.fire('สำเร็จ', 'คัดนักเรียนออกจากห้องแล้ว', 'success')
      }
    } catch (err: any) {
      Swal.fire('เกิดข้อผิดพลาด', err.message || 'ไม่สามารถนำนักเรียนออกได้', 'error')
    }
  }
}

// Add Student Modal Logic
const openAddStudentModal = async () => {
  showAddStudentModal.value = true
  searchQueryAddStudent.value = ''
  availableStudents.value = []
  availableStudentsError.value = ''
  availableStudentsTotal.value = 0
  // ให้ watcher ของ searchQueryAddStudent ทำงานก่อน แล้วค่อยยกเลิก timer ที่มันตั้งไว้
  // ไม่งั้นจะยิง request ซ้ำอีกรอบหลังเปิด modal 350ms
  await nextTick()
  if (availableStudentsSearchTimer) clearTimeout(availableStudentsSearchTimer)
  await fetchAvailableStudents()
}

const fetchAvailableStudents = async () => {
  isLoadingAvailableStudents.value = true
  availableStudentsError.value = ''
  // กันผลลัพธ์ของคำค้นเก่ามาทับคำค้นล่าสุด (out-of-order response)
  const requestId = ++availableStudentsRequestId

  try {
    const res: any = await api.get(`/api/academies/${academy.value.id}/classrooms/students`, {
      query: {
        per_page: 50,
        search: searchQueryAddStudent.value.trim() || undefined,
      }
    })
    if (requestId !== availableStudentsRequestId) return
    if (res.success) {
      // Filter out students who are already in this classroom
      const classroomStudentIds = new Set(students.value.map(s => s.id))
      availableStudents.value = (res.students || []).filter((s: any) => !classroomStudentIds.has(s.id))
      availableStudentsTotal.value = res.pagination?.total ?? availableStudents.value.length
    }
  } catch (err: any) {
    if (requestId !== availableStudentsRequestId) return
    console.error('Failed to fetch available students:', err)
    availableStudents.value = []
    availableStudentsTotal.value = 0
    availableStudentsError.value = err?.data?.message || err?.message || 'ไม่สามารถดึงรายชื่อนักเรียนได้'
  } finally {
    if (requestId === availableStudentsRequestId) {
      isLoadingAvailableStudents.value = false
    }
  }
}

// ค้นหาฝั่ง server — สถาบันมีนักเรียนหลักพัน การกรองใน 50 รายการที่โหลดมาจะหาไม่เจอ
watch(searchQueryAddStudent, () => {
  if (!showAddStudentModal.value) return
  if (availableStudentsSearchTimer) clearTimeout(availableStudentsSearchTimer)
  availableStudentsSearchTimer = setTimeout(() => {
    fetchAvailableStudents()
  }, 350)
})

// จำนวนที่ค้นเจอทั้งหมดยังเกินที่โหลดมาแสดงหรือไม่ (ให้ผู้ใช้รู้ว่าต้องพิมพ์ค้นให้แคบลง)
const hasMoreAvailableStudents = computed(
  () => availableStudentsTotal.value > availableStudents.value.length
)

const escapeHtml = (value: string) =>
  value.replace(/[&<>"']/g, (ch) =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch] as string
  )

const handleAddStudent = async (student: any) => {
  // นักเรียนที่มีห้องอยู่แล้วจะถูกย้าย (ปิดทะเบียนห้องเดิม) ไม่ใช่เพิ่มเฉยๆ — ต้องให้ผู้ใช้ยืนยันก่อน
  const currentClassroom = student.currentEnrollment?.classroom
  if (currentClassroom) {
    const confirmed = await Swal.fire({
      icon: 'warning',
      title: 'นักเรียนมีห้องเรียนอยู่แล้ว',
      html: `<b>${escapeHtml(studentName(student))}</b> อยู่ห้อง <b>${escapeHtml(currentClassroom.name || '-')}</b><br>`
        + `การเพิ่มเข้าห้อง <b>${escapeHtml(classroomName.value)}</b> จะปิดทะเบียนห้องเดิมและย้ายมาห้องนี้`,
      showCancelButton: true,
      confirmButtonText: 'ย้ายมาห้องนี้',
      cancelButtonText: 'ยกเลิก',
      confirmButtonColor: '#d97706',
    })
    if (!confirmed.isConfirmed) return
  }

  try {
    const res: any = await api.post(`/api/academies/${academy.value.id}/classrooms/${classroomId.value}/students`, {
      student_ids: [student.id]
    })
    if (res.success) {
      await loadClassroom()
      availableStudents.value = availableStudents.value.filter((s) => s.id !== student.id)
      Swal.fire({
        icon: 'success',
        title: res.message || 'เพิ่มนักเรียนเข้าห้องสำเร็จ',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2500,
      })
    }
  } catch (err: any) {
    Swal.fire('ข้อผิดพลาด', err.message || 'ไม่สามารถเพิ่มนักเรียนได้', 'error')
  }
}

// Transfer Student Modal Logic
const openTransferStudentModal = (student: any) => {
  selectedStudentForTransfer.value = student
  transferToClassroomId.value = null
  showTransferStudentModal.value = true
  fetchOtherClassrooms()
}

const fetchOtherClassrooms = async () => {
  try {
    const res: any = await api.get(`/api/academies/${academy.value.id}/classrooms`)
    if (res.success) {
      otherClassrooms.value = (res.classrooms || []).filter((c: any) => c.id !== classroomId.value)
    }
  } catch (err) {
    console.error('Failed to fetch other classrooms:', err)
  }
}

const handleTransferStudentSubmit = async () => {
  if (!transferToClassroomId.value) return
  try {
    const res: any = await api.post(`/api/academies/${academy.value.id}/classrooms/transfer-student`, {
      student_id: selectedStudentForTransfer.value.id,
      from_classroom_id: classroomId.value,
      to_classroom_id: transferToClassroomId.value,
      reason: transferReason.value
    })
    if (res.success) {
      showTransferStudentModal.value = false
      await loadClassroom()
      Swal.fire('สำเร็จ', 'ย้ายห้องเรียนให้นักเรียนแล้ว', 'success')
    }
  } catch (err: any) {
    Swal.fire('ข้อผิดพลาด', err.message || 'ไม่สามารถย้ายห้องเรียนได้', 'error')
  }
}

// Members Management (Add/Remove)
const openAddMemberModal = () => {
  showAddMemberModal.value = true
  searchQueryMember.value = ''
  availableUsers.value = []
  selectedMemberRole.value = 'co_teacher'
  fetchAvailableUsers()
}

const fetchAvailableUsers = async () => {
  isLoadingAvailableUsers.value = true
  try {
    // Search users in academy
    const res: any = await api.get(`/api/academies/${academy.value.id}/members/search`, {
      params: { search: searchQueryMember.value || undefined, status: 2, per_page: 50 }
    })
    if (res.success) {
      const allMembers: any[] = res.members?.data ?? res.members ?? res.data ?? []
      const existingMemberUserIds = new Set(members.value.map((m: any) => m.user_id || m.user?.id))
      availableUsers.value = allMembers
        .filter((u: any) => {
          const uid = u.user_id || u.user?.id
          return uid && u.role !== 'student' && u.role !== 'parent' && !existingMemberUserIds.has(uid)
        })
        .map((u: any) => ({
          id: u.user_id || u.user?.id,
          name: u.member_name || u.user?.name || u.name || '-',
          email: u.user?.email || u.email || '',
          avatar: u.member_avatar || u.user?.profile_photo_url || '',
        }))
    }
  } catch (err) {
    console.error('Failed to fetch users:', err)
  } finally {
    isLoadingAvailableUsers.value = false
  }
}

watch(searchQueryMember, () => {
  fetchAvailableUsers()
})

const handleAddMember = async (user: any) => {
  try {
    const res: any = await api.post(`/api/academies/${academy.value.id}/classrooms/${classroomId.value}/members`, {
      user_id: user.id || user.user_id,
      role: selectedMemberRole.value
    })
    if (res.success) {
      await loadClassroom()
      showAddMemberModal.value = false
      Swal.fire({ icon: 'success', title: 'เพิ่มสมาชิกสำเร็จ', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 })
    }
  } catch (err: any) {
    Swal.fire('ข้อผิดพลาด', err.message || 'ไม่สามารถเพิ่มสมาชิกได้', 'error')
  }
}

const handleRemoveMember = async (member: any) => {
  const result = await Swal.fire({
    title: 'ลบสมาชิกออก?',
    text: `คุณต้องการนำ ${member.name || member.user?.name} ออกจากห้องเรียนนี้ใช่หรือไม่?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    confirmButtonText: 'ลบออก',
    cancelButtonText: 'ยกเลิก',
  })

  if (result.isConfirmed) {
    try {
      const res: any = await api.delete(`/api/academies/${academy.value.id}/classrooms/${classroomId.value}/members/${member.id}`)
      if (res.success) {
        await loadClassroom()
        Swal.fire('สำเร็จ', 'ลบสมาชิกแล้ว', 'success')
      }
    } catch (err: any) {
      Swal.fire('เกิดข้อผิดพลาด', err.message || 'ไม่สามารถลบสมาชิกได้', 'error')
    }
  }
}

// Attendance Management Logic
const loadAttendanceForDate = async () => {
  if (!academy.value) return
  isSessionLoading.value = true
  activeSession.value = null
  attendanceStatuses.value = {}
  try {
    const res: any = await schoolApi.getSchoolAttendances(academy.value.id, { date: attendanceDate.value })
    const sessions = res?.data?.data || []
    
    // Find daily classroom session or fall back to general session
    if (sessions.length > 0) {
      const session = sessions[0]
      // Load details
      const detailRes: any = await schoolApi.getSchoolAttendance(academy.value.id, session.id)
      if (detailRes?.success) {
        activeSession.value = detailRes.data
        // Map records
        const records = detailRes.data.records || []
        
        // Initialize attendance state for classroom students
        students.value.forEach((s) => {
          const matched = records.find((r: any) => r.student_id === (s.user_id || s.user?.id || s.id))
          attendanceStatuses.value[s.id || s.user_id] = {
            status: matched?.status || 'present',
            remark: matched?.remarks || ''
          }
        })
      }
    }
  } catch (err) {
    console.error('Failed to load attendance:', err)
  } finally {
    isSessionLoading.value = false
  }
}

watch(attendanceDate, loadAttendanceForDate)

const handleCreateAttendanceSession = async () => {
  if (!academy.value) return
  isSavingAttendance.value = true
  try {
    const res: any = await schoolApi.createSchoolAttendance(academy.value.id, {
      date: attendanceDate.value,
      title: `เช็คชื่อมาโรงเรียน ห้อง ${classroomName.value}`,
      start_time: '08:00',
      late_minutes: 15,
      notes: `เช็คชื่อรายวันสำหรับชั้นเรียน ${classroomName.value}`
    })
    if (res?.success) {
      await loadAttendanceForDate()
      Swal.fire('สำเร็จ', 'เปิดเซสชันเช็คชื่อแล้ว', 'success')
    }
  } catch (err: any) {
    Swal.fire('ข้อผิดพลาด', err.message || 'ไม่สามารถเปิดเซสชันเช็คชื่อได้', 'error')
  } finally {
    isSavingAttendance.value = false
  }
}

const saveAttendanceRecords = async () => {
  if (!academy.value || !activeSession.value) return
  isSavingAttendance.value = true
  try {
    const records = students.value.map((s) => {
      const state = attendanceStatuses.value[s.id || s.user_id]
      return {
        student_id: s.user_id || s.user?.id || s.id,
        status: state?.status || 'present',
        remarks: state?.remark || undefined
      }
    })
    const res: any = await schoolApi.recordSchoolAttendances(academy.value.id, activeSession.value.id, records)
    if (res?.success) {
      await loadAttendanceForDate()
      Swal.fire({ icon: 'success', title: 'บันทึกรายชื่อการเข้าเรียนสำเร็จ', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 })
    }
  } catch (err: any) {
    Swal.fire('เกิดข้อผิดพลาด', err.message || 'ไม่สามารถบันทึกการเข้าเรียนได้', 'error')
  } finally {
    isSavingAttendance.value = false
  }
}

const handleCloseAttendanceSession = async () => {
  if (!academy.value || !activeSession.value) return
  const result = await Swal.fire({
    title: 'ปิดเซสชันเช็คชื่อ?',
    text: 'เมื่อปิดเซสชันแล้ว นักเรียนจะไม่สามารถสแกนบัตรเช็คชื่อด้วยตนเองได้อีก',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    confirmButtonText: 'ปิดเช็คชื่อ',
    cancelButtonText: 'ยกเลิก',
  })

  if (result.isConfirmed) {
    isClosingSession.value = true
    try {
      const res: any = await schoolApi.closeSchoolAttendance(academy.value.id, activeSession.value.id)
      if (res?.success) {
        await loadAttendanceForDate()
        Swal.fire('สำเร็จ', 'ปิดการเช็คชื่อสำหรับวันนี้แล้ว', 'success')
      }
    } catch (err: any) {
      Swal.fire('ข้อผิดพลาด', err.message || 'ไม่สามารถปิดเซสชันได้', 'error')
    } finally {
      isClosingSession.value = false
    }
  }
}

// Announcements Logic
const handleAddAnnouncement = () => {
  if (!newAnnouncementTitle.value.trim() || !newAnnouncementContent.value.trim()) return
  announcements.value.unshift({
    id: announcements.value.length + 1,
    title: newAnnouncementTitle.value,
    content: newAnnouncementContent.value,
    date: new Date().toISOString(),
    author: homeroomTeacher.value?.name || homeroomTeacher.value?.user?.name || 'ครูประจำชั้น'
  })
  newAnnouncementTitle.value = ''
  newAnnouncementContent.value = ''
  showAddAnnouncementModal.value = false
  Swal.fire({ icon: 'success', title: 'ประกาศเรียบร้อยแล้ว', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 })
}

// Reports Tab Excel Exports (using XLSX / SheetJS)
const exportRosterReport = () => {
  const reportData = students.value.map((s, index) => ({
    'เลขที่': s.student_number || index + 1,
    'รหัสประจำตัวนักเรียน': s.student_id || '-',
    'ชื่อ-นามสกุล (ไทย)': studentName(s),
    'ชื่อเล่น': s.nickname || '-',
    'เพศ': genderLabel(s.gender),
    'สถานะ': s.status === 'active' ? 'กำลังเรียน' : s.status,
    'ผู้ปกครอง': s.guardians?.[0]?.guardian_name || '-',
    'เบอร์ติดต่อผู้ปกครอง': s.guardians?.[0]?.phone || '-'
  }))

  const ws = XLSX.utils.json_to_sheet(reportData)
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'รายชื่อนักเรียน')
  XLSX.writeFile(wb, `รายชื่อนักเรียน_ห้อง_${classroomName.value}_ปี_${classroom.value?.academic_year || '-'}.xlsx`)
}

const exportAttendanceReport = () => {
  const reportData = students.value.map((s, index) => {
    const state = attendanceStatuses.value[s.id || s.user_id]
    const statusText = state?.status === 'present' ? 'มา' : state?.status === 'late' ? 'สาย' : state?.status === 'leave' ? 'ลา' : state?.status === 'absent' ? 'ขาด' : '-'
    return {
      'เลขที่': s.student_number || index + 1,
      'รหัสประจำตัว': s.student_id || '-',
      'ชื่อ-นามสกุล': studentName(s),
      'สถานะการมาเรียนวันนี้': statusText,
      'หมายเหตุ/ลาเนื่องจาก': state?.remark || '-'
    }
  })

  const ws = XLSX.utils.json_to_sheet(reportData)
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'เช็คชื่อมาเรียน')
  XLSX.writeFile(wb, `เช็คชื่อเข้าเรียน_ห้อง_${classroomName.value}_วันที่_${attendanceDate.value}.xlsx`)
}

const exportAcademicReport = () => {
  const reportData = students.value.map((s, index) => {
    const grades = studentGrades.value[s.id] || {}
    const gpaInfo = mockGPAData.value[s.id] || { gpa: 0, gpax: 0 }
    
    const row: any = {
      'เลขที่': s.student_number || index + 1,
      'รหัสประจำตัว': s.student_id || '-',
      'ชื่อ-นามสกุล': studentName(s),
    }
    
    subjectsList.value.forEach((subj) => {
      row[subj.name] = grades[subj.id] ? `${grades[subj.id].score} (${grades[subj.id].grade})` : '-'
    })
    
    row['เกรดเฉลี่ย (GPA)'] = gpaInfo.gpa
    row['เกรดเฉลี่ยสะสม (GPAX)'] = gpaInfo.gpax
    
    return row
  })

  const ws = XLSX.utils.json_to_sheet(reportData)
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'ผลการเรียน')
  XLSX.writeFile(wb, `ผลสัมฤทธิ์ทางการเรียน_ห้อง_${classroomName.value}_ปี_${classroom.value?.academic_year || '-'}.xlsx`)
}

const openRenumberPreview = async () => {
  if (students.value.length === 0) return
  isLoadingRenumberPreview.value = true
  try {
    const res: any = await api.post(`/api/academies/${academy.value.id}/classrooms/${classroomId.value}/renumber`, {
      sort_by: 'student_id',
      dry_run: true
    })
    
    if (res.changed_count === 0) {
      Swal.fire({ icon: 'info', title: 'เลขที่เรียงถูกต้องอยู่แล้ว', toast: true, position: 'top-end', showConfirmButton: false, timer: 2500 })
      return
    }
    
    renumberPreview.value = res.preview || []
    renumberChangedCount.value = res.changed_count || 0
    renumberTotal.value = res.total || 0
    showRenumberModal.value = true
  } catch (err: any) {
    Swal.fire('ข้อผิดพลาด', err.message || 'ไม่สามารถดึงข้อมูลพรีวิวได้', 'error')
  } finally {
    isLoadingRenumberPreview.value = false
  }
}

const applyRenumber = async () => {
  isApplyingRenumber.value = true
  try {
    const res: any = await api.post(`/api/academies/${academy.value.id}/classrooms/${classroomId.value}/renumber`, {
      sort_by: 'student_id',
      dry_run: false
    })
    if (res.success) {
      showRenumberModal.value = false
      await loadClassroom()
      Swal.fire({ icon: 'success', title: res.message || 'จัดเรียงเลขที่สำเร็จ', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 })
    }
  } catch (err: any) {
    Swal.fire('ข้อผิดพลาด', err.message || 'ไม่สามารถจัดเรียงเลขที่ได้', 'error')
  } finally {
    isApplyingRenumber.value = false
  }
}

onMounted(async () => {
  await loadClassroom()
  await loadAttendanceForDate()
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900 pb-12">
    <!-- Top Nav Back -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
      <NuxtLink
        :to="`/academies/${academyName}/admin/classrooms`"
        class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-primary-600 dark:text-slate-400 dark:hover:text-primary-400 transition-colors"
      >
        <Icon icon="fluent:arrow-left-24-regular" class="h-4 w-4" />
        กลับหน้ารายการห้องเรียน
      </NuxtLink>
    </div>

    <!-- Main Container -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
      <div v-if="isLoading" class="flex flex-col items-center justify-center py-32 gap-3">
        <div class="h-12 w-12 animate-spin rounded-full border-4 border-primary-500 border-t-transparent"></div>
        <p class="text-slate-500 dark:text-slate-400 text-sm font-medium animate-pulse">กำลังโหลดข้อมูลห้องเรียน...</p>
      </div>

      <div v-else-if="errorMessage" class="rounded-2xl border border-red-100 bg-red-50 p-8 text-center dark:border-red-950 dark:bg-red-950/20">
        <Icon icon="fluent:error-circle-24-regular" class="mx-auto mb-3 h-12 w-12 text-red-500" />
        <p class="text-red-700 dark:text-red-300 font-semibold">{{ errorMessage }}</p>
      </div>

      <div v-else-if="classroom" class="space-y-6">
        <!-- HopeUI Signature Wave Hero Banner -->
        <div class="relative isolate overflow-hidden rounded-2xl bg-gradient-to-br from-primary-600 via-primary-500 to-sky-500 px-6 py-10 text-white shadow-lg md:px-10">
          <div class="absolute inset-x-0 top-0 -z-10 h-full w-full overflow-hidden rounded-2xl">
            <img
              src="/images/hopeui/top-header.png"
              alt="HopeUI Wave Hero"
              class="h-full w-full object-cover rounded-2xl opacity-45 scale-100 transition-transform duration-1000 select-none pointer-events-none"
              style="animation: scaleLoop 30s ease-in-out infinite alternate"
            />
          </div>

          <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-2">
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-white/20 text-white border border-white/30 backdrop-blur-sm">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse" />
                ปีการศึกษา {{ classroom.academic_year || '-' }}
              </span>
              <h1 class="text-3xl font-extrabold tracking-tight font-heading md:text-4xl text-white drop-shadow-md">
                ห้องเรียน: {{ classroomName }}
              </h1>
              <p class="text-sm text-sky-100 font-medium md:text-base">
                ระดับ {{ classroom.grade_level || '-' }} / Section {{ classroom.section || '-' }} · {{ academy?.name }}
              </p>
            </div>
            
            <div class="flex gap-2">
              <NuxtLink
                :to="`/academies/${academyName}/admin/gradebook/classrooms/${classroomId}`"
                class="flex items-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-bold text-primary-600 hover:bg-slate-50 transition-all shadow-md active:scale-95"
              >
                <Icon icon="fluent:hat-graduation-24-filled" class="h-5 w-5" />
                เปิด Gradebook
              </NuxtLink>
            </div>
          </div>
        </div>

        <!-- Custom Navigation Tabs -->
        <div class="border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 rounded-2xl shadow-sm px-5">
          <nav class="flex space-x-6 overflow-x-auto" aria-label="Tabs">
            <button
              v-for="tab in tabs"
              :key="tab.key"
              @click="activeTab = tab.key"
              class="border-b-2 py-4 px-1 text-sm font-semibold whitespace-nowrap transition-colors flex items-center gap-2 -mb-px"
              :class="activeTab === tab.key 
                ? 'border-primary-500 text-primary-600 dark:text-primary-400 dark:border-primary-400' 
                : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'"
            >
              <Icon :icon="tab.icon" class="h-5 w-5" />
              {{ tab.label }}
            </button>
          </nav>
        </div>

        <!-- Tab Contents -->
        <!-- TAB 1: OVERVIEW -->
        <div v-if="activeTab === 'overview'" class="space-y-6">
          <!-- Overview Stats Cards -->
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Capacity card -->
            <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-800 hover:shadow-md transition-shadow">
              <div class="flex items-center justify-between">
                <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">อัตราความจุห้องเรียน</p>
                <Icon icon="fluent:people-community-24-regular" class="h-6 w-6 text-primary-500" />
              </div>
              <p class="mt-2 text-3xl font-extrabold text-slate-900 dark:text-white">{{ studentCount }} / {{ capacity }}</p>
              <div class="mt-4 h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700">
                <div 
                  class="h-full rounded-full bg-gradient-to-r from-primary-500 to-sky-400 transition-all duration-500" 
                  :style="{ width: `${occupancy}%` }"
                ></div>
              </div>
              <p class="mt-2 text-xs font-semibold text-slate-500 dark:text-slate-400">ความจุห้อง {{ occupancy }}%</p>
            </div>

            <!-- Homeroom Teacher card -->
            <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-800 hover:shadow-md transition-shadow">
              <div class="flex items-center justify-between">
                <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">ครูประจำชั้น</p>
                <Icon icon="fluent:person-board-24-regular" class="h-6 w-6 text-indigo-500" />
              </div>
              <div class="mt-3 flex items-center gap-3">
                <div class="h-10 w-10 shrink-0 rounded-full bg-indigo-100 flex items-center justify-center font-bold text-indigo-600 dark:bg-indigo-950 dark:text-indigo-400">
                  {{ homeroomTeacher?.name ? homeroomTeacher.name.charAt(0) : 'T' }}
                </div>
                <div class="min-w-0 flex-1">
                  <p class="truncate text-base font-bold text-slate-900 dark:text-white">{{ homeroomTeacher?.name || 'ยังไม่ได้กำหนด' }}</p>
                  <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ homeroomTeacher?.email || '—' }}</p>
                </div>
              </div>
              <div class="mt-4 flex items-center justify-end gap-2">
                <button
                  class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-bold text-primary-600 transition-colors hover:bg-primary-50 dark:border-slate-700 dark:hover:bg-slate-700"
                  @click="showAssignHomeroomModal = true"
                >
                  {{ homeroomTeacher ? 'เปลี่ยน' : 'แต่งตั้ง' }}
                </button>
                <button
                  v-if="homeroomTeacher"
                  class="rounded-lg border border-red-200 px-2.5 py-1 text-xs font-bold text-red-600 transition-colors hover:bg-red-50 dark:border-red-900 dark:text-red-400 dark:hover:bg-red-950/40"
                  @click="clearHomeroomTeacher"
                >
                  เอาออก
                </button>
              </div>
            </div>

            <!-- Attendance Rate card -->
            <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-800 hover:shadow-md transition-shadow">
              <div class="flex items-center justify-between">
                <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">อัตราการเข้าเรียนเฉลี่ย</p>
                <Icon icon="fluent:calendar-checkmark-24-regular" class="h-6 w-6 text-emerald-500" />
              </div>
              <p class="mt-2 text-3xl font-extrabold text-emerald-600 dark:text-emerald-400">96.5%</p>
              <div class="mt-4 flex items-center gap-1 text-xs font-semibold text-emerald-600">
                <Icon icon="fluent:arrow-trending-lines-24-regular" class="h-4 w-4" />
                <span>เพิ่มขึ้น +1.2% สัปดาห์นี้</span>
              </div>
            </div>

            <!-- GPA card -->
            <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-800 hover:shadow-md transition-shadow">
              <div class="flex items-center justify-between">
                <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">เกรดเฉลี่ยห้องเรียน (GPA)</p>
                <Icon icon="fluent:hat-graduation-24-regular" class="h-6 w-6 text-amber-500" />
              </div>
              <p class="mt-2 text-3xl font-extrabold text-amber-600 dark:text-amber-400">3.25</p>
              <div class="mt-4 flex items-center gap-1 text-xs font-semibold text-slate-500">
                <span class="rounded bg-amber-100 px-1.5 py-0.5 text-amber-800 dark:bg-amber-950 dark:text-amber-300">กลุ่มวิชาหลัก 4 วิชา</span>
              </div>
            </div>
          </div>

          <!-- Quick Actions & Details -->
          <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Left Info Columns -->
            <div class="space-y-6 lg:col-span-2">
              <!-- Latest Announcements -->
              <div class="rounded-2xl border border-slate-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-800 p-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-700">
                  <h3 class="font-bold text-slate-900 dark:text-white flex items-center gap-2 text-lg">
                    <Icon icon="fluent:megaphone-24-regular" class="h-5 w-5 text-primary-500" />
                    ประกาศล่าสุดของห้องเรียน
                  </h3>
                  <button @click="activeTab = 'announcements'" class="text-sm font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-400">
                    ดูประกาศทั้งหมด
                  </button>
                </div>
                
                <div class="mt-4 divide-y divide-slate-100 dark:divide-slate-700">
                  <div v-for="ann in announcements.slice(0, 2)" :key="ann.id" class="py-4 first:pt-0 last:pb-0 space-y-2">
                    <div class="flex items-center justify-between">
                      <h4 class="font-bold text-slate-800 dark:text-slate-100">{{ ann.title }}</h4>
                      <span class="text-xs text-slate-400">{{ new Date(ann.date).toLocaleDateString('th-TH') }}</span>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400 line-clamp-2 leading-relaxed">
                      {{ ann.content }}
                    </p>
                    <div class="flex items-center gap-1.5 text-xs text-slate-400">
                      <Icon icon="fluent:person-24-regular" class="h-3.5 w-3.5" />
                      <span>เขียนโดย {{ ann.author }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Quick Schedule Calendar -->
              <div class="rounded-2xl border border-slate-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-800 p-6">
                <h3 class="font-bold text-slate-900 dark:text-white flex items-center gap-2 text-lg border-b border-slate-100 pb-4 dark:border-slate-700">
                  <Icon icon="fluent:calendar-ltr-24-regular" class="h-5 w-5 text-indigo-500" />
                  ตารางสอนวันพรุ่งนี้
                </h3>
                
                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                  <div v-for="subj in subjectsList" :key="subj.id" class="rounded-xl bg-slate-50 p-4 dark:bg-slate-700/30 flex items-center justify-between">
                    <div>
                      <p class="text-xs font-mono font-bold text-slate-400 dark:text-slate-500">{{ subj.code }}</p>
                      <h4 class="font-bold text-slate-800 dark:text-slate-100 mt-0.5 text-sm">{{ subj.name }}</h4>
                      <p class="text-xs text-slate-500 mt-1">{{ subj.teacher }}</p>
                    </div>
                    <div class="rounded-lg bg-white px-2.5 py-1.5 text-center shadow-sm dark:bg-slate-800 border dark:border-slate-700">
                      <p class="text-xs text-slate-400 font-semibold">เวลา</p>
                      <p class="text-xs font-bold text-primary-500 mt-0.5">08:30 น.</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right Sidebar Columns -->
            <div class="space-y-6">
              <!-- Top Performing Subjects -->
              <div class="rounded-2xl border border-slate-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-800 p-6">
                <h3 class="font-bold text-slate-900 dark:text-white flex items-center gap-2 text-lg border-b border-slate-100 pb-4 dark:border-slate-700">
                  <Icon icon="fluent:arrow-trending-lines-24-regular" class="h-5 w-5 text-amber-500" />
                  รายวิชาผลสัมฤทธิ์ดีเด่น
                </h3>
                
                <div class="mt-4 space-y-4">
                  <div class="space-y-1">
                    <div class="flex justify-between text-sm">
                      <span class="font-semibold text-slate-800 dark:text-slate-200">คณิตศาสตร์ (MA101)</span>
                      <span class="font-bold text-primary-600 dark:text-primary-400">82.4% A/B</span>
                    </div>
                    <div class="h-1.5 w-full bg-slate-100 rounded-full dark:bg-slate-700">
                      <div class="h-full bg-primary-500 rounded-full" style="width: 82%"></div>
                    </div>
                  </div>
                  <div class="space-y-1">
                    <div class="flex justify-between text-sm">
                      <span class="font-semibold text-slate-800 dark:text-slate-200">วิทยาศาสตร์ (SC101)</span>
                      <span class="font-bold text-amber-600 dark:text-amber-400">78.5% A/B</span>
                    </div>
                    <div class="h-1.5 w-full bg-slate-100 rounded-full dark:bg-slate-700">
                      <div class="h-full bg-amber-500 rounded-full" style="width: 78%"></div>
                    </div>
                  </div>
                  <div class="space-y-1">
                    <div class="flex justify-between text-sm">
                      <span class="font-semibold text-slate-800 dark:text-slate-200">ภาษาอังกฤษ (EN101)</span>
                      <span class="font-bold text-indigo-600 dark:text-indigo-400">75.0% A/B</span>
                    </div>
                    <div class="h-1.5 w-full bg-slate-100 rounded-full dark:bg-slate-700">
                      <div class="h-full bg-indigo-500 rounded-full" style="width: 75%"></div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- At risk Students -->
              <div class="rounded-2xl border border-slate-100 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-800 p-6">
                <h3 class="font-bold text-slate-900 dark:text-white flex items-center gap-2 text-lg border-b border-slate-100 pb-4 dark:border-slate-700">
                  <Icon icon="fluent:warning-24-regular" class="h-5 w-5 text-red-500" />
                  กลุ่มความเสี่ยงการเข้าเรียน
                </h3>
                
                <div v-if="students.length === 0" class="mt-4 text-center py-6 text-sm text-slate-400">
                  ไม่มีรายชื่อนักเรียนในขณะนี้
                </div>
                
                <div v-else class="mt-4 space-y-3">
                  <div class="flex items-center justify-between rounded-xl bg-red-50 p-3 dark:bg-red-950/20 border border-red-100 dark:border-red-900/40">
                    <div class="flex items-center gap-2.5">
                      <div class="h-8 w-8 rounded-full bg-red-100 flex items-center justify-center font-bold text-red-700 text-sm dark:bg-red-900/50 dark:text-red-300">
                        {{ studentName(students[0]).charAt(0) }}
                      </div>
                      <div>
                        <h4 class="font-bold text-slate-800 dark:text-slate-100 text-xs">{{ studentName(students[0]) }}</h4>
                        <p class="text-[10px] text-red-600 dark:text-red-400">มาเรียนสะสมต่ำกว่า 80%</p>
                      </div>
                    </div>
                    <span class="rounded bg-red-600 px-2 py-0.5 text-[10px] font-bold text-white shadow-sm">76.8%</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 2: STUDENTS -->
        <div v-else-if="activeTab === 'students'" class="space-y-6">
          <!-- Filters & Action Bar -->
          <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between bg-white dark:bg-slate-800 p-4 rounded-2xl border dark:border-slate-700">
            <div class="flex flex-wrap gap-2 flex-1">
              <!-- Search query -->
              <div class="relative w-full max-w-xs">
                <input
                  v-model="studentSearch"
                  type="text"
                  placeholder="ค้นหาชื่อ เลขประจำตัว หรือชื่อเล่น..."
                  class="w-full pl-9 pr-4 py-2 border border-slate-300 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-900 dark:text-white placeholder-slate-400 text-sm focus:ring-2 focus:ring-primary-500 outline-none"
                />
                <Icon icon="fluent:search-24-regular" class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
              </div>

              <!-- Status filter -->
              <select
                v-model="selectedStatusFilter"
                class="border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500 outline-none"
              >
                <option value="all">สถานะทั้งหมด</option>
                <option value="active">กำลังเรียน</option>
                <option value="inactive">ไม่ได้อยู่ในห้อง</option>
              </select>

              <!-- Gender Filter -->
              <select
                v-model="selectedGenderFilter"
                class="border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500 outline-none"
              >
                <option value="all">เพศทั้งหมด</option>
                <option value="male">ชาย</option>
                <option value="female">หญิง</option>
              </select>
            </div>

            <!-- Action buttons -->
            <div class="flex gap-2 shrink-0">
              <button
                @click="openRenumberPreview"
                :disabled="students.length === 0 || isLoadingRenumberPreview"
                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-700 px-4 py-2 text-sm font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50 active:scale-95 transition-all disabled:opacity-50"
                title="เรียงเลขที่ใหม่ตามลำดับเลขประจำตัวนักเรียน"
              >
                <Icon v-if="isLoadingRenumberPreview" icon="fluent:spinner-ios-20-filled" class="h-4 w-4 animate-spin" />
                <Icon v-else icon="fluent:arrow-sort-24-regular" class="h-4 w-4" />
                จัดเรียงเลขที่ใหม่
              </button>
              <button
                @click="openAddStudentModal"
                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-primary-600 to-sky-500 px-4 py-2 text-sm font-bold text-white hover:opacity-95 shadow-md active:scale-95 transition-all"
              >
                <Icon icon="fluent:person-add-24-filled" class="h-4 w-4" />
                เพิ่มนักเรียน
              </button>
            </div>
          </div>

          <!-- Roster List Table -->
          <div class="overflow-hidden bg-white dark:bg-slate-800 rounded-2xl border dark:border-slate-700 shadow-sm">
            <div class="overflow-x-auto">
              <!-- Column widths include the cell padding, so px-4 (not px-5) is
                   what keeps the narrow badge columns from wrapping mid-word. -->
              <table class="w-full min-w-[1040px] text-left border-collapse">
                <thead>
                  <tr class="bg-slate-50 dark:bg-slate-900/50 border-b dark:border-slate-700 text-xs font-semibold text-slate-500 dark:text-slate-400 whitespace-nowrap">
                    <th class="px-4 py-4 w-20">
                      <button
                        @click="toggleStudentSort('student_number')"
                        class="inline-flex items-center gap-1 font-semibold transition-colors hover:text-primary-600 dark:hover:text-primary-400"
                        :class="studentSortKey === 'student_number' ? 'text-primary-600 dark:text-primary-400' : ''"
                      >
                        เลขที่
                        <Icon :icon="studentSortIcon('student_number')" class="h-3.5 w-3.5" />
                      </button>
                    </th>
                    <th class="px-4 py-4 w-28">
                      <button
                        @click="toggleStudentSort('student_id')"
                        class="inline-flex items-center gap-1 font-semibold transition-colors hover:text-primary-600 dark:hover:text-primary-400"
                        :class="studentSortKey === 'student_id' ? 'text-primary-600 dark:text-primary-400' : ''"
                      >
                        เลขประจำตัว
                        <Icon :icon="studentSortIcon('student_id')" class="h-3.5 w-3.5" />
                      </button>
                    </th>
                    <th class="px-4 py-4 min-w-[200px]">
                      <button
                        @click="toggleStudentSort('name')"
                        class="inline-flex items-center gap-1 font-semibold transition-colors hover:text-primary-600 dark:hover:text-primary-400"
                        :class="studentSortKey === 'name' ? 'text-primary-600 dark:text-primary-400' : ''"
                      >
                        รูปภาพ &amp; ชื่อ-นามสกุล
                        <Icon :icon="studentSortIcon('name')" class="h-3.5 w-3.5" />
                      </button>
                    </th>
                    <th class="px-4 py-4 w-24">ชื่อเล่น</th>
                    <th class="px-4 py-4 w-24">เพศ</th>
                    <th class="px-4 py-4 w-36">สถานะ</th>
                    <th class="px-4 py-4 w-44">ผู้ปกครองหลัก</th>
                    <th class="px-4 py-4 text-right w-36">การจัดการ</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                  <tr v-if="sortedStudents.length === 0">
                    <td colspan="8" class="text-center py-10 text-slate-400 dark:text-slate-500 text-sm">ไม่พบรายชื่อนักเรียน</td>
                  </tr>

                  <tr
                    v-for="(student, index) in sortedStudents"
                    :key="student.id"
                    class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-colors text-sm text-slate-800 dark:text-slate-200"
                  >
                    <!-- student_number -->
                    <td class="px-4 py-3.5 font-medium">
                      <div v-if="editingStudentNumberId === student.id" class="flex items-center gap-1.5">
                        <input
                          v-model="editingStudentNumberValue"
                          type="number"
                          class="w-14 px-2 py-1 text-xs border rounded bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
                          min="1"
                        />
                        <button @click="saveStudentNumber(student.id)" class="text-green-500 hover:text-green-600">
                          <Icon icon="fluent:checkmark-12-filled" class="h-4 w-4" />
                        </button>
                        <button @click="cancelEditStudentNumber" class="text-red-500 hover:text-red-600">
                          <Icon icon="fluent:dismiss-12-filled" class="h-4 w-4" />
                        </button>
                      </div>
                      <div v-else class="flex items-center gap-2 group">
                        <span class="font-semibold">{{ student.student_number || index + 1 }}</span>
                        <button 
                          @click="startEditStudentNumber(student)" 
                          class="opacity-0 group-hover:opacity-100 text-slate-400 hover:text-primary-500 transition-opacity"
                        >
                          <Icon icon="fluent:edit-16-regular" class="h-3.5 w-3.5" />
                        </button>
                      </div>
                    </td>
                    
                    <!-- student_id_code -->
                    <td class="px-4 py-3.5 font-mono text-xs text-slate-500 dark:text-slate-400">{{ student.student_id || '-' }}</td>

                    <!-- Photo & Name -->
                    <td class="px-4 py-3.5">
                      <div class="flex items-center gap-3">
                        <img
                          v-if="student.profile_image_url || student.profile_image"
                          :src="student.profile_image_url || student.profile_image"
                          class="h-9 w-9 rounded-full object-cover shadow-inner"
                          alt="avatar"
                        />
                        <div v-else class="h-9 w-9 rounded-full bg-primary-100 flex items-center justify-center font-bold text-primary-600 dark:bg-primary-950 dark:text-primary-300">
                          {{ studentName(student).charAt(0) }}
                        </div>
                        <div class="min-w-0">
                          <p class="font-semibold text-slate-950 dark:text-white truncate">{{ studentName(student) }}</p>
                          <p class="text-[10px] text-slate-400">{{ student.citizen_id || '-' }}</p>
                        </div>
                      </div>
                    </td>

                    <!-- Nickname -->
                    <td class="px-4 py-3.5 font-semibold truncate">{{ student.nickname || '-' }}</td>

                    <!-- Gender -->
                    <td class="px-4 py-3.5">
                      <span class="inline-block whitespace-nowrap text-xs font-semibold px-2 py-0.5 rounded-full" :class="genderBadgeClass(student.gender)">
                        {{ genderLabel(student.gender) }}
                      </span>
                    </td>

                    <!-- Status -->
                    <td class="px-4 py-3.5">
                      <span class="inline-flex items-center gap-1.5 whitespace-nowrap px-2.5 py-0.5 rounded-full text-xs font-semibold" :class="student.status === 'active' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-slate-100 text-slate-800 dark:bg-slate-950 dark:text-slate-300'">
                        <span class="w-1.5 h-1.5 shrink-0 rounded-full" :class="student.status === 'active' ? 'bg-emerald-400' : 'bg-slate-400'"></span>
                        {{ student.status === 'active' ? 'กำลังเรียน' : student.status }}
                      </span>
                    </td>

                    <!-- Parent / Guardian -->
                    <td class="px-4 py-3.5">
                      <div class="text-xs">
                        <p class="truncate font-semibold text-slate-950 dark:text-white">{{ student.guardians?.[0]?.guardian_name || 'ไม่ระบุ' }}</p>
                        <p class="text-slate-400 font-mono mt-0.5">{{ student.guardians?.[0]?.phone || '-' }}</p>
                      </div>
                    </td>

                    <!-- Actions -->
                    <td class="px-4 py-3.5 text-right space-x-1 whitespace-nowrap">
                      <!-- View Profile -->
                      <button
                        @click="openStudentProfile(student)"
                        class="p-1.5 text-slate-500 hover:text-primary-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700/50 rounded-lg transition-all"
                        title="ดูโปรไฟล์"
                      >
                        <Icon icon="fluent:person-info-24-regular" class="h-4.5 w-4.5" />
                      </button>
                      
                      <!-- Transfer -->
                      <button
                        @click="openTransferStudentModal(student)"
                        class="p-1.5 text-slate-500 hover:text-amber-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700/50 rounded-lg transition-all"
                        title="ย้ายห้องเรียน"
                      >
                        <Icon icon="fluent:share-ios-24-regular" class="h-4.5 w-4.5" />
                      </button>

                      <!-- Remove -->
                      <button
                        @click="removeStudent(student)"
                        class="p-1.5 text-slate-500 hover:text-red-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700/50 rounded-lg transition-all"
                        title="คัดออก"
                      >
                        <Icon icon="fluent:person-delete-24-regular" class="h-4.5 w-4.5" />
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- TAB 3: MEMBERS -->
        <div v-else-if="activeTab === 'members'" class="space-y-6">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-lg font-bold text-slate-900 dark:text-white">ครูประจำชั้นและผู้ช่วยสอน</h2>
              <p class="text-sm text-slate-500 dark:text-slate-400">สมาชิกที่มีสิทธิ์เข้าถึง จัดการคะแนน และเช็คชื่อนักเรียนในห้องเรียนนี้</p>
            </div>
            <button
              @click="openAddMemberModal"
              class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-primary-600 to-sky-500 px-4 py-2 text-sm font-bold text-white hover:opacity-95 shadow-md active:scale-95 transition-all"
            >
              <Icon icon="fluent:add-24-filled" class="h-4 w-4" />
              เพิ่มครู/สมาชิก
            </button>
          </div>

          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <!-- Homeroom teacher main card -->
            <div 
              v-for="member in classroomMembersList" 
              :key="member.id"
              class="relative rounded-2xl border bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-800 flex flex-col justify-between"
              :class="member.role === 'teacher' ? 'border-primary-100 dark:border-primary-950/60 ring-2 ring-primary-500/20' : 'border-slate-100'"
            >
              <span 
                class="absolute right-4 top-4 rounded-full px-2 py-0.5 text-xs font-semibold"
                :class="
                  member.role === 'teacher' ? 'bg-primary-100 text-primary-800 dark:bg-primary-950 dark:text-primary-300' :
                  member.role === 'co_teacher' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300' :
                  'bg-slate-100 text-slate-800 dark:bg-slate-900 dark:text-slate-400'
                "
              >
                {{ 
                  member.role === 'teacher' ? 'ครูประจำชั้นหลัก' : 
                  member.role === 'co_teacher' ? 'ครูผู้ช่วย/ร่วมสอน' : 'ผู้สังเกตการณ์' 
                }}
              </span>
              
              <div class="flex items-center gap-4 mt-2">
                <div class="h-12 w-12 rounded-full bg-slate-100 flex items-center justify-center font-bold text-slate-500 text-lg dark:bg-slate-700 dark:text-slate-300 shadow-inner">
                  {{ member.name ? member.name.charAt(0) : member.user?.name ? member.user.name.charAt(0) : 'T' }}
                </div>
                <div>
                  <h4 class="font-extrabold text-slate-950 dark:text-white text-base">{{ member.name || member.user?.name || '-' }}</h4>
                  <p class="text-xs text-slate-400 font-mono mt-0.5">{{ member.user?.email || member.email || '—' }}</p>
                </div>
              </div>
              
              <div class="mt-6 pt-4 border-t dark:border-slate-700/60 flex justify-between items-center text-xs gap-2">
                <span class="text-slate-400 font-medium truncate">สิทธิ์: จัดการข้อมูลในห้องเรียน</span>
                <div class="flex items-center gap-3 shrink-0">
                  <button
                    v-if="(member.user_id || member.user?.id) !== classroom.homeroom_teacher_id"
                    @click="assignTeacherFromMember(member)"
                    class="text-primary-600 hover:text-primary-700 font-bold flex items-center gap-1 dark:text-primary-400"
                  >
                    <Icon icon="fluent:person-star-24-regular" class="h-4 w-4" />
                    ตั้งเป็นครูประจำชั้น
                  </button>
                  <button
                    v-if="(member.user_id || member.user?.id) === classroom.homeroom_teacher_id"
                    @click="clearHomeroomTeacher"
                    class="text-red-500 hover:text-red-600 font-bold flex items-center gap-1 dark:text-red-400"
                  >
                    <Icon icon="fluent:person-subtract-24-regular" class="h-4 w-4" />
                    ลบออก
                  </button>
                  <button
                    v-if="member.role !== 'teacher'"
                    @click="handleRemoveMember(member)"
                    class="text-red-500 hover:text-red-600 font-bold flex items-center gap-1"
                  >
                    <Icon icon="fluent:delete-16-regular" class="h-4 w-4" />
                    ลบออก
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <AssignHomeroomTeacherModal
          v-if="showAssignHomeroomModal && academy && classroom"
          :academy-id="academy.id"
          :classroom-id="classroom.id"
          :current-teacher-id="classroom.homeroom_teacher_id"
          @close="showAssignHomeroomModal = false"
          @updated="showAssignHomeroomModal = false; loadClassroom()"
        />

        <!-- TAB 4: ATTENDANCE -->
        <div v-else-if="activeTab === 'attendance'" class="space-y-6">
          <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between bg-white dark:bg-slate-800 p-5 rounded-2xl border dark:border-slate-700 shadow-sm">
            <div class="flex flex-wrap items-center gap-3">
              <label class="font-bold text-slate-700 dark:text-slate-300 text-sm">วันที่เช็คชื่อ:</label>
              <input
                v-model="attendanceDate"
                type="date"
                class="border border-slate-300 dark:border-slate-700 rounded-xl px-4 py-2 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500 outline-none font-bold"
              />
            </div>
            
            <div class="flex flex-wrap gap-2" v-if="activeSession">
              <button
                @click="exportAttendanceReport"
                class="inline-flex items-center gap-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2.5 text-sm font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 transition-colors"
              >
                <Icon icon="fluent:document-arrow-down-24-regular" class="h-4.5 w-4.5" />
                ส่งออก CSV
              </button>
              
              <button
                v-if="activeSession.status === 'open'"
                @click="handleCloseAttendanceSession"
                class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-red-700 transition-colors"
              >
                <Icon icon="fluent:lock-closed-24-filled" class="h-4.5 w-4.5" />
                ปิดการเช็คชื่อ
              </button>
            </div>
          </div>

          <!-- Attendance Loading Skeleton -->
          <div v-if="isSessionLoading" class="flex flex-col items-center justify-center py-20 gap-3">
            <div class="h-8 w-8 animate-spin rounded-full border-4 border-primary-500 border-t-transparent"></div>
            <p class="text-slate-500 text-sm animate-pulse">กำลังโหลดเซสชันการเช็คชื่อ...</p>
          </div>

          <!-- Active Session details -->
          <div v-else-if="activeSession" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Roster checklist column -->
            <div class="lg:col-span-2 space-y-4 bg-white dark:bg-slate-800 rounded-2xl border dark:border-slate-700 p-6 shadow-sm">
              <div class="flex items-center justify-between border-b pb-4 dark:border-slate-700">
                <h3 class="font-extrabold text-slate-950 dark:text-white text-base">รายชื่อเช็คชื่อประจำวัน</h3>
                <span class="text-xs text-slate-400">เลือกสถานะของนักเรียนแต่ละคนด้านล่าง</span>
              </div>
              
              <div class="divide-y divide-slate-100 dark:divide-slate-700/60 max-h-[500px] overflow-y-auto pr-1">
                <div v-for="student in students" :key="student.id" class="flex flex-col sm:flex-row sm:items-center justify-between py-3.5 gap-3">
                  <!-- Student identity -->
                  <div class="flex items-center gap-3 min-w-0">
                    <span class="w-8 font-mono text-slate-400 text-xs text-center font-bold">{{ student.student_number || '-' }}</span>
                    <div class="h-9 w-9 rounded-full bg-slate-100 flex items-center justify-center font-bold text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                      {{ studentName(student).charAt(0) }}
                    </div>
                    <div class="min-w-0">
                      <p class="font-semibold text-slate-950 dark:text-white text-sm truncate">{{ studentName(student) }}</p>
                      <p class="text-[10px] text-slate-400 font-mono">{{ student.student_id || '-' }}</p>
                    </div>
                  </div>
                  
                  <!-- Status radio choices -->
                  <div class="flex items-center flex-wrap gap-1.5">
                    <button
                      v-for="(label, statusKey) in { present: 'มา', late: 'สาย', leave: 'ลา', absent: 'ขาด' }"
                      :key="statusKey"
                      @click="attendanceStatuses[student.id || student.user_id].status = statusKey"
                      class="px-3 py-1 rounded-lg text-xs font-semibold transition-all border"
                      :class="
                        attendanceStatuses[student.id || student.user_id]?.status === statusKey
                          ? statusKey === 'present' ? 'bg-green-500 border-green-500 text-white shadow-sm' :
                            statusKey === 'late' ? 'bg-orange-500 border-orange-500 text-white shadow-sm' :
                            statusKey === 'leave' ? 'bg-sky-500 border-sky-500 text-white shadow-sm' :
                            'bg-red-500 border-red-500 text-white shadow-sm'
                          : 'bg-white border-slate-200 text-slate-600 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-400 hover:bg-slate-50'
                      "
                    >
                      {{ label }}
                    </button>
                    
                    <!-- Optional remark -->
                    <input
                      v-model="attendanceStatuses[student.id || student.user_id].remark"
                      type="text"
                      placeholder="หมายเหตุ..."
                      class="w-24 px-2 py-1 text-[11px] border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 rounded-lg text-slate-900 dark:text-white outline-none focus:ring-2 focus:ring-primary-500"
                    />
                  </div>
                </div>
              </div>
              
              <div class="border-t pt-4 flex justify-end dark:border-slate-700">
                <button
                  @click="saveAttendanceRecords"
                  :disabled="isSavingAttendance"
                  class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-primary-600 to-sky-500 px-6 py-2.5 text-sm font-bold text-white shadow-md active:scale-95 transition-all disabled:opacity-50"
                >
                  <Icon v-if="isSavingAttendance" icon="fluent:spinner-ios-20-regular" class="h-4 w-4 animate-spin" />
                  <Icon v-else icon="fluent:save-24-filled" class="h-4 w-4" />
                  บันทึกการเช็คชื่อ
                </button>
              </div>
            </div>

            <!-- Attendance stats & QR column -->
            <div class="space-y-6">
              <!-- Summary breakdown -->
              <div class="bg-white dark:bg-slate-800 rounded-2xl border dark:border-slate-700 p-6 shadow-sm space-y-4">
                <h3 class="font-bold text-slate-900 dark:text-white text-base">สรุปผลเช็คชื่อประจำวัน</h3>
                <div class="grid grid-cols-2 gap-3">
                  <div class="bg-green-50 dark:bg-green-950/20 border border-green-100 dark:border-green-900/40 rounded-xl p-3 text-center">
                    <p class="text-2xl font-black text-green-600 dark:text-green-400">{{ currentSessionSummary.present }}</p>
                    <p class="text-xs text-green-700 dark:text-green-300 font-semibold mt-1">มาเรียน</p>
                  </div>
                  <div class="bg-orange-50 dark:bg-orange-950/20 border border-orange-100 dark:border-orange-900/40 rounded-xl p-3 text-center">
                    <p class="text-2xl font-black text-orange-600 dark:text-orange-400">{{ currentSessionSummary.late }}</p>
                    <p class="text-xs text-orange-700 dark:text-orange-300 font-semibold mt-1">มาสาย</p>
                  </div>
                  <div class="bg-sky-50 dark:bg-sky-950/20 border border-sky-100 dark:border-sky-900/40 rounded-xl p-3 text-center">
                    <p class="text-2xl font-black text-sky-600 dark:text-sky-400">{{ currentSessionSummary.leave }}</p>
                    <p class="text-xs text-sky-700 dark:text-sky-300 font-semibold mt-1">ลา</p>
                  </div>
                  <div class="bg-red-50 dark:bg-red-950/20 border border-red-100 dark:border-red-900/40 rounded-xl p-3 text-center">
                    <p class="text-2xl font-black text-red-600 dark:text-red-400">{{ currentSessionSummary.absent }}</p>
                    <p class="text-xs text-red-700 dark:text-red-300 font-semibold mt-1">ขาดเรียน</p>
                  </div>
                </div>
              </div>

              <!-- Student QR Checkin panel -->
              <div v-if="activeSession.status === 'open' && academy" class="bg-white dark:bg-slate-800 rounded-2xl border dark:border-slate-700 p-6 shadow-sm flex flex-col items-center gap-4 text-center">
                <Icon icon="fluent:qr-code-24-regular" class="h-10 w-10 text-primary-500" />
                <div>
                  <h4 class="font-bold text-slate-900 dark:text-white text-base">สแกนเช็คชื่อด้วย QR</h4>
                  <p class="text-xs text-slate-500 mt-1">นักเรียนสามารถสแกนเพื่อเช็คชื่อตัวเองผ่านแอพได้</p>
                </div>
                <div class="border rounded-xl p-2 bg-white">
                  <!-- Real component or placeholder if component fails -->
                  <SchoolAttendanceQRDisplay 
                    :academy-id="academy.id" 
                    :attendance-id="activeSession.id"
                    class="w-48 h-48"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Empty Date view: prompt to open daily attendance -->
          <div v-else class="rounded-2xl border bg-white dark:bg-slate-800 p-12 text-center shadow-sm">
            <Icon icon="fluent:calendar-empty-24-regular" class="h-16 w-16 mx-auto mb-4 text-slate-300 dark:text-slate-600" />
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">ยังไม่มีเซสชันเช็คชื่อสำหรับวันนี้</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 max-w-sm mx-auto">
              เปิดใช้งานการเข้าเรียนเพื่อเริ่มเช็คชื่อรายวัน พิมพ์รายงาน หรือให้นักเรียนสแกน QR บัตร
            </p>
            <button
              @click="handleCreateAttendanceSession"
              class="mt-6 inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-primary-600 to-sky-500 px-6 py-3 text-sm font-bold text-white shadow-md active:scale-95 transition-all"
            >
              <Icon icon="fluent:play-24-filled" class="h-4.5 w-4.5" />
              เปิดการเช็คชื่อสำหรับวันนี้
            </button>
          </div>
        </div>

        <!-- TAB 5: GRADES / SUBJECTS -->
        <div v-else-if="activeTab === 'grades'" class="space-y-6">
          <div class="flex items-center justify-between border-b pb-4 dark:border-slate-800">
            <div>
              <h2 class="text-lg font-bold text-slate-900 dark:text-white">วิชาเรียนและผลการเรียนในห้องเรียน</h2>
              <p class="text-sm text-slate-500 dark:text-slate-400">ภาพรวมคะแนนดิบ เกรดเฉลี่ยของแต่ละวิชา และการเชื่อมโยงกับระบบสมุดเกรด</p>
            </div>
            
            <NuxtLink
              :to="`/academies/${academyName}/admin/gradebook/classrooms/${classroomId}`"
              class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-primary-600 to-sky-500 px-5 py-2.5 text-sm font-bold text-white shadow-md active:scale-95"
            >
              <Icon icon="fluent:database-link-24-regular" class="h-4.5 w-4.5" />
              เข้าสู่ระบบสมุดเกรด (Gradebook)
            </NuxtLink>
          </div>

          <!-- Performance Roster Table -->
          <div class="bg-white dark:bg-slate-800 rounded-2xl border dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="p-5 bg-slate-50 dark:bg-slate-900/40 border-b dark:border-slate-700 flex justify-between items-center">
              <h3 class="font-bold text-slate-900 dark:text-white text-base">คะแนนเก็บและเกรดห้อง</h3>
              <span class="text-xs text-slate-500">หมายเหตุ: เกรดเฉลี่ยคำนวณจากวิชาเรียนของห้องเท่านั้น</span>
            </div>
            
            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="bg-slate-100/50 dark:bg-slate-900/30 border-b dark:border-slate-700 text-xs font-semibold text-slate-500 dark:text-slate-400">
                    <th class="px-5 py-4 w-16">เลขที่</th>
                    <th class="px-5 py-4 min-w-[180px]">นักเรียน</th>
                    <th v-for="subj in subjectsList" :key="subj.id" class="px-5 py-4 text-center">
                      <p class="font-bold">{{ subj.name }}</p>
                      <p class="text-[10px] font-mono text-slate-400">{{ subj.code }}</p>
                    </th>
                    <th class="px-5 py-4 text-center w-24">GPA (ห้อง)</th>
                    <th class="px-5 py-4 text-center w-24">GPAX (สะสม)</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                  <tr v-if="students.length === 0">
                    <td :colspan="5 + subjectsList.length" class="text-center py-10 text-slate-400 dark:text-slate-500 text-sm">ไม่มีรายชื่อนักเรียน</td>
                  </tr>
                  
                  <tr 
                    v-for="(student, index) in students" 
                    :key="student.id"
                    class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-colors text-sm text-slate-800 dark:text-slate-200"
                  >
                    <td class="px-5 py-3 font-semibold text-center">{{ student.student_number || index + 1 }}</td>
                    <td class="px-5 py-3 font-semibold">{{ studentName(student) }}</td>
                    
                    <!-- Subject score & grade columns -->
                    <td v-for="subj in subjectsList" :key="subj.id" class="px-5 py-3 text-center">
                      <div v-if="studentGrades[student.id]?.[subj.id]" class="inline-flex flex-col items-center">
                        <span class="font-bold text-slate-900 dark:text-white">{{ studentGrades[student.id][subj.id].score }}</span>
                        <span 
                          class="rounded-full px-1.5 py-0.5 text-[10px] font-semibold mt-1"
                          :class="
                            studentGrades[student.id][subj.id].grade.startsWith('A') ? 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300' :
                            studentGrades[student.id][subj.id].grade.startsWith('B') ? 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300' :
                            studentGrades[student.id][subj.id].grade.startsWith('C') ? 'bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300' :
                            'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300'
                          "
                        >
                          เกรด {{ studentGrades[student.id][subj.id].grade }}
                        </span>
                      </div>
                      <span v-else class="text-slate-400">-</span>
                    </td>
                    
                    <!-- GPAs -->
                    <td class="px-5 py-3 text-center font-extrabold text-slate-900 dark:text-white">
                      {{ mockGPAData[student.id]?.gpa || '-' }}
                    </td>
                    <td class="px-5 py-3 text-center font-extrabold text-primary-600 dark:text-primary-400">
                      {{ mockGPAData[student.id]?.gpax || '-' }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- TAB 6: ANNOUNCEMENTS -->
        <div v-else-if="activeTab === 'announcements'" class="space-y-6">
          <div class="flex items-center justify-between border-b pb-4 dark:border-slate-800">
            <div>
              <h2 class="text-lg font-bold text-slate-900 dark:text-white">ประกาศและข่าวสารห้องเรียน</h2>
              <p class="text-sm text-slate-500 dark:text-slate-400">ประชาสัมพันธ์ แจ้งเตือนส่งงาน หรือข้อความสำคัญถึงผู้ปกครองและนักเรียน</p>
            </div>
            
            <button
              @click="showAddAnnouncementModal = true"
              class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-primary-600 to-sky-500 px-4 py-2 text-sm font-bold text-white shadow-md active:scale-95 transition-all"
            >
              <Icon icon="fluent:add-24-filled" class="h-4.5 w-4.5" />
              สร้างประกาศใหม่
            </button>
          </div>

          <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Announcements List -->
            <div class="lg:col-span-2 space-y-4">
              <div v-if="announcements.length === 0" class="rounded-2xl border bg-white dark:bg-slate-800 p-12 text-center text-slate-400">
                ยังไม่มีการลงประกาศสำหรับห้องนี้
              </div>
              
              <div 
                v-for="ann in announcements" 
                :key="ann.id"
                class="bg-white dark:bg-slate-800 rounded-2xl border dark:border-slate-700 p-6 shadow-sm space-y-3"
              >
                <div class="flex justify-between items-start">
                  <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ ann.title }}</h3>
                  <span class="text-xs text-slate-400 font-medium">{{ new Date(ann.date).toLocaleString('th-TH', { dateStyle: 'short', timeStyle: 'short' }) }}</span>
                </div>
                
                <p class="text-slate-600 dark:text-slate-300 text-sm leading-relaxed whitespace-pre-line">
                  {{ ann.content }}
                </p>
                
                <div class="pt-3 border-t dark:border-slate-700 flex items-center justify-between text-xs text-slate-500">
                  <div class="flex items-center gap-1.5">
                    <Icon icon="fluent:person-24-regular" class="h-4 w-4" />
                    <span>เขียนโดย: {{ ann.author }}</span>
                  </div>
                  
                  <span class="rounded bg-sky-50 px-2 py-0.5 font-semibold text-sky-700 dark:bg-sky-950 dark:text-sky-300">แจ้งเตือนแอปพลิเคชัน</span>
                </div>
              </div>
            </div>

            <!-- Calendar & Reminders -->
            <div class="space-y-6">
              <div class="bg-white dark:bg-slate-800 rounded-2xl border dark:border-slate-700 p-6 shadow-sm space-y-4">
                <h3 class="font-bold text-slate-900 dark:text-white text-base border-b pb-3 dark:border-slate-700 flex items-center gap-2">
                  <Icon icon="fluent:clock-24-regular" class="h-5 w-5 text-indigo-500" />
                  ปฏิทินส่งงาน/กิจกรรม
                </h3>
                
                <div class="space-y-4">
                  <div class="border-l-4 border-amber-500 pl-3 py-0.5">
                    <h4 class="font-bold text-slate-800 dark:text-slate-100 text-sm">สอบเก็บคะแนนคณิตศาสตร์</h4>
                    <p class="text-xs text-slate-400 mt-1">20 กรกฎาคม 2026 · คาบเช้า</p>
                  </div>
                  <div class="border-l-4 border-primary-500 pl-3 py-0.5">
                    <h4 class="font-bold text-slate-800 dark:text-slate-100 text-sm">ส่งสมุดงานวิทยาศาสตร์</h4>
                    <p class="text-xs text-slate-400 mt-1">24 กรกฎาคม 2026 · ก่อน 16:30 น.</p>
                  </div>
                  <div class="border-l-4 border-emerald-500 pl-3 py-0.5">
                    <h4 class="font-bold text-slate-800 dark:text-slate-100 text-sm">วันหยุดสถาบันประจำภาคเรียน</h4>
                    <p class="text-xs text-slate-400 mt-1">28 กรกฎาคม 2026 · ทั้งวัน</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 7: REPORTS & EXPORTS -->
        <div v-else-if="activeTab === 'reports'" class="space-y-6">
          <div>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">ระบบรายงานผลและส่งออกข้อมูล (Exports)</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">เลือกประเภทรายงานเพื่อดาวน์โหลดข้อมูลเป็นไฟล์ Excel (.xlsx) สำหรับการทำเอกสารและสถิติภายนอก</p>
          </div>

          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <!-- Report Card 1: Student Roster -->
            <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-800 flex flex-col justify-between hover:shadow-md transition-shadow">
              <div class="space-y-3">
                <div class="h-10 w-10 rounded-xl bg-primary-100 flex items-center justify-center font-bold text-primary-600 dark:bg-primary-950 dark:text-primary-300">
                  <Icon icon="fluent:people-list-24-regular" class="h-6 w-6" />
                </div>
                <h3 class="text-base font-bold text-slate-950 dark:text-white">รายงานรายชื่อนักเรียนประจำห้อง</h3>
                <p class="text-xs text-slate-400 dark:text-slate-500 leading-relaxed">
                  ประกอบด้วยรายชื่อนักเรียน เลขที่ รหัสประจำตัว ข้อมูลเบื้องต้น และข้อมูลผู้ปกครองหลักพร้อมเบอร์ติดต่อสำหรับการสื่อสาร
                </p>
              </div>
              <button
                @click="exportRosterReport"
                class="mt-6 w-full inline-flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-primary-700 transition-colors shadow-sm"
              >
                <Icon icon="fluent:document-arrow-down-24-filled" class="h-4.5 w-4.5" />
                ดาวน์โหลด Excel
              </button>
            </div>

            <!-- Report Card 2: Attendance -->
            <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-800 flex flex-col justify-between hover:shadow-md transition-shadow">
              <div class="space-y-3">
                <div class="h-10 w-10 rounded-xl bg-emerald-100 flex items-center justify-center font-bold text-emerald-600 dark:bg-emerald-950 dark:text-emerald-300">
                  <Icon icon="fluent:calendar-checkmark-24-regular" class="h-6 w-6" />
                </div>
                <h3 class="text-base font-bold text-slate-950 dark:text-white">รายงานข้อมูลการเข้าเรียนประจำวัน</h3>
                <p class="text-xs text-slate-400 dark:text-slate-500 leading-relaxed">
                  ดาวน์โหลดข้อมูลการเข้าเรียนของนักเรียนวันนี้ (มา สาย ลา ขาด) พร้อมข้อมูลหมายเหตุส่งตัวเพื่อนำไปอัพเดทประวัติโรงเรียน
                </p>
              </div>
              <button
                @click="exportAttendanceReport"
                class="mt-6 w-full inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-700 transition-colors shadow-sm"
              >
                <Icon icon="fluent:document-arrow-down-24-filled" class="h-4.5 w-4.5" />
                ดาวน์โหลด Excel
              </button>
            </div>

            <!-- Report Card 3: Academic Grades -->
            <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-800 flex flex-col justify-between hover:shadow-md transition-shadow">
              <div class="space-y-3">
                <div class="h-10 w-10 rounded-xl bg-amber-100 flex items-center justify-center font-bold text-amber-600 dark:bg-amber-950 dark:text-amber-300">
                  <Icon icon="fluent:hat-graduation-24-regular" class="h-6 w-6" />
                </div>
                <h3 class="text-base font-bold text-slate-950 dark:text-white">รายงานผลสัมฤทธิ์ทางการเรียน</h3>
                <p class="text-xs text-slate-400 dark:text-slate-500 leading-relaxed">
                  ส่งออกผลคะแนนวิชาหลัก เกรดเฉลี่ยรายวิชา และ GPA/GPAX ของนักเรียนรายคนสำหรับรายงานต่อผู้บริหาร
                </p>
              </div>
              <button
                @click="exportAcademicReport"
                class="mt-6 w-full inline-flex items-center justify-center gap-2 rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-amber-700 transition-colors shadow-sm"
              >
                <Icon icon="fluent:document-arrow-down-24-filled" class="h-4.5 w-4.5" />
                ดาวน์โหลด Excel
              </button>
            </div>
          </div>
        </div>

        <!-- MODAL DIALOGS -->
        <!-- Add Student Modal -->
        <div v-if="showAddStudentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showAddStudentModal = false"></div>
          
          <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl max-w-lg w-full overflow-hidden border border-slate-100 dark:border-slate-700 transform transition-all flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="px-6 py-5 border-b dark:border-slate-700 flex justify-between items-center">
              <h3 class="font-extrabold text-slate-900 dark:text-white text-lg">เพิ่มนักเรียนเข้าห้อง {{ classroomName }}</h3>
              <button @click="showAddStudentModal = false" class="text-slate-400 hover:text-slate-500">
                <Icon icon="fluent:dismiss-24-regular" class="h-6 w-6" />
              </button>
            </div>

            <!-- Body -->
            <div class="p-6 overflow-y-auto space-y-4 flex-1">
              <div class="relative">
                <input
                  v-model="searchQueryAddStudent"
                  type="text"
                  placeholder="ค้นหาชื่อหรือรหัสนักเรียน..."
                  class="w-full pl-9 pr-4 py-2.5 border border-slate-300 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm outline-none focus:ring-2 focus:ring-primary-500"
                />
                <Icon icon="fluent:search-24-regular" class="absolute left-3 top-3 h-4 w-4 text-slate-400" />
              </div>

              <div v-if="isLoadingAvailableStudents" class="text-center py-10">
                <div class="h-8 w-8 animate-spin rounded-full border-4 border-primary-500 border-t-transparent mx-auto"></div>
                <p class="text-xs text-slate-500 mt-2">กำลังดึงข้อมูลนักเรียน...</p>
              </div>

              <div v-else-if="availableStudentsError" class="rounded-xl border border-rose-200 dark:border-rose-900/50 bg-rose-50 dark:bg-rose-900/20 p-4 text-center">
                <Icon icon="fluent:error-circle-24-regular" class="h-6 w-6 text-rose-500 mx-auto" />
                <p class="text-sm font-bold text-rose-700 dark:text-rose-300 mt-2">ดึงรายชื่อนักเรียนไม่สำเร็จ</p>
                <p class="text-xs text-rose-600 dark:text-rose-400 mt-1">{{ availableStudentsError }}</p>
                <button
                  @click="fetchAvailableStudents()"
                  class="mt-3 rounded-lg border border-rose-300 dark:border-rose-800 px-3 py-1.5 text-xs font-bold text-rose-700 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-900/40 transition-colors"
                >
                  ลองใหม่
                </button>
              </div>

              <div v-else class="space-y-2">
                <div v-if="availableStudents.length === 0" class="text-center py-10 text-slate-400 text-sm">
                  {{ searchQueryAddStudent.trim() ? 'ไม่พบนักเรียนที่ตรงกับคำค้นหา' : 'ไม่พบนักเรียนว่างสะสมในสถาบัน' }}
                </div>

                <p v-else-if="hasMoreAvailableStudents" class="text-xs text-slate-400 text-center pb-1">
                  แสดง {{ availableStudents.length }} จาก {{ availableStudentsTotal }} คน — พิมพ์ชื่อหรือรหัสนักเรียนเพื่อค้นหาให้แคบลง
                </p>

                <div
                  v-for="student in availableStudents"
                  :key="student.id"
                  class="flex items-center justify-between p-3 rounded-xl border dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40 hover:bg-slate-100 transition-colors"
                >
                  <div>
                    <h4 class="font-bold text-slate-900 dark:text-white text-sm">{{ studentName(student) }}</h4>
                    <p class="text-xs text-slate-400 font-mono mt-0.5">รหัส: {{ student.student_id || '-' }}</p>
                    <p v-if="student.currentEnrollment?.classroom" class="text-xs text-amber-600 font-semibold mt-0.5">
                      ห้องปัจจุบัน: {{ student.currentEnrollment.classroom.name }} (แนะนำให้ใช้เมนูย้ายห้อง)
                    </p>
                    <p v-else class="text-xs text-emerald-600 font-semibold mt-0.5">สถานะ: ไม่มีห้องเรียนสังกัด</p>
                  </div>
                  
                  <button
                    @click="handleAddStudent(student)"
                    class="rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-primary-700 transition-colors"
                  >
                    เพิ่มเข้าห้อง
                  </button>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/20 border-t dark:border-slate-700 flex justify-end gap-2">
              <button
                @click="showAddStudentModal = false"
                class="rounded-xl border border-slate-300 dark:border-slate-700 px-4 py-2 text-sm font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors"
              >
                ปิดหน้าต่าง
              </button>
            </div>
          </div>
        </div>

        <!-- Transfer Student Modal -->
        <div v-if="showTransferStudentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showTransferStudentModal = false"></div>
          
          <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl max-w-md w-full overflow-hidden border border-slate-100 dark:border-slate-700 transform transition-all flex flex-col">
            <div class="px-6 py-5 border-b dark:border-slate-700 flex justify-between items-center">
              <h3 class="font-extrabold text-slate-900 dark:text-white text-lg">ย้ายห้องเรียนนักเรียน</h3>
              <button @click="showTransferStudentModal = false" class="text-slate-400 hover:text-slate-500">
                <Icon icon="fluent:dismiss-24-regular" class="h-6 w-6" />
              </button>
            </div>

            <div class="p-6 space-y-4">
              <div v-if="selectedStudentForTransfer" class="rounded-xl bg-primary-50 dark:bg-primary-950/20 p-3 border border-primary-100 text-sm">
                นักเรียน: <span class="font-bold text-primary-700 dark:text-primary-300">{{ studentName(selectedStudentForTransfer) }}</span><br/>
                ห้องปัจจุบัน: <span class="font-semibold">{{ classroomName }}</span>
              </div>

              <div class="space-y-1">
                <label class="text-xs font-bold text-slate-500 dark:text-slate-400">เลือกห้องเรียนปลายทาง</label>
                <select
                  v-model="transferToClassroomId"
                  class="w-full rounded-xl border border-slate-300 dark:border-slate-700 px-3 py-2.5 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-sm outline-none focus:ring-2 focus:ring-primary-500"
                >
                  <option :value="null" disabled>-- เลือกห้องเรียน --</option>
                  <option v-for="c in otherClassrooms" :key="c.id" :value="c.id">
                    {{ c.name }} (ระดับ {{ c.grade_level }} · นักเรียน {{ c.student_count }} คน)
                  </option>
                </select>
              </div>

              <div class="space-y-1">
                <label class="text-xs font-bold text-slate-500 dark:text-slate-400">เหตุผลประกอบการย้าย</label>
                <select
                  v-model="transferReason"
                  class="w-full rounded-xl border border-slate-300 dark:border-slate-700 px-3 py-2.5 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-sm outline-none focus:ring-2 focus:ring-primary-500"
                >
                  <option value="ปรับสมดุลจำนวนนักเรียน">ปรับสมดุลจำนวนนักเรียน</option>
                  <option value="ย้ายตามความต้องการของนักเรียน">ย้ายตามความต้องการของนักเรียน/ผู้ปกครอง</option>
                  <option value="ย้ายเนื่องจากพฤติกรรมหรือความต้องการพิเศษ">ย้ายเนื่องจากความประสงค์สถาบัน</option>
                </select>
              </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/20 border-t dark:border-slate-700 flex justify-end gap-2">
              <button
                @click="showTransferStudentModal = false"
                class="rounded-xl border border-slate-300 dark:border-slate-700 px-4 py-2 text-sm font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors"
              >
                ยกเลิก
              </button>
              
              <button
                @click="handleTransferStudentSubmit"
                :disabled="!transferToClassroomId"
                class="rounded-xl bg-gradient-to-r from-primary-600 to-sky-500 px-5 py-2 text-sm font-bold text-white shadow-md active:scale-95 transition-all disabled:opacity-50"
              >
                ยืนยันการย้ายห้อง
              </button>
            </div>
          </div>
        </div>

        <!-- Add Member Modal -->
        <div v-if="showAddMemberModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showAddMemberModal = false"></div>
          
          <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl max-w-lg w-full overflow-hidden border border-slate-100 dark:border-slate-700 transform transition-all flex flex-col max-h-[90vh]">
            <div class="px-6 py-5 border-b dark:border-slate-700 flex justify-between items-center">
              <h3 class="font-extrabold text-slate-900 dark:text-white text-lg">เพิ่มครูประจำวิชา/ผู้ช่วย</h3>
              <button @click="showAddMemberModal = false" class="text-slate-400 hover:text-slate-500">
                <Icon icon="fluent:dismiss-24-regular" class="h-6 w-6" />
              </button>
            </div>

            <div class="p-6 overflow-y-auto space-y-4 flex-1">
              <div class="flex gap-2">
                <div class="relative flex-1">
                  <input
                    v-model="searchQueryMember"
                    type="text"
                    placeholder="ค้นหาชื่อหรืออีเมลบุคลากร..."
                    class="w-full pl-9 pr-4 py-2.5 border border-slate-300 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm outline-none focus:ring-2 focus:ring-primary-500"
                  />
                  <Icon icon="fluent:search-24-regular" class="absolute left-3 top-3 h-4 w-4 text-slate-400" />
                </div>
                
                <select
                  v-model="selectedMemberRole"
                  class="border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500 outline-none"
                >
                  <option value="co_teacher">ครูผู้ช่วย</option>
                  <option value="teacher">ครูประจำชั้นหลัก</option>
                  <option value="observer">ผู้สังเกตการณ์</option>
                </select>
              </div>

              <div v-if="isLoadingAvailableUsers" class="text-center py-10">
                <div class="h-8 w-8 animate-spin rounded-full border-4 border-primary-500 border-t-transparent mx-auto"></div>
                <p class="text-xs text-slate-500 mt-2">กำลังดึงข้อมูลสมาชิก...</p>
              </div>

              <div v-else class="space-y-2">
                <div v-if="availableUsers.length === 0" class="text-center py-10 text-slate-400 text-sm">
                  ไม่พบสมาชิกอื่นว่างสะสมในสถาบัน
                </div>

                <div 
                  v-for="user in availableUsers" 
                  :key="user.id"
                  class="flex items-center justify-between p-3 rounded-xl border dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40 hover:bg-slate-100 transition-colors"
                >
                  <div class="min-w-0">
                    <h4 class="font-bold text-slate-900 dark:text-white text-sm truncate">{{ user.name }}</h4>
                    <p class="text-xs text-slate-400 font-mono mt-0.5 truncate">{{ user.email }}</p>
                  </div>
                  
                  <button
                    @click="handleAddMember(user)"
                    class="rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-primary-700 transition-colors shrink-0"
                  >
                    แต่งตั้ง
                  </button>
                </div>
              </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/20 border-t dark:border-slate-700 flex justify-end gap-2">
              <button
                @click="showAddMemberModal = false"
                class="rounded-xl border border-slate-300 dark:border-slate-700 px-4 py-2 text-sm font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors"
              >
                ปิด
              </button>
            </div>
          </div>
        </div>

        <!-- Add Announcement Modal -->
        <div v-if="showAddAnnouncementModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showAddAnnouncementModal = false"></div>
          
          <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl max-w-lg w-full overflow-hidden border border-slate-100 dark:border-slate-700 transform transition-all flex flex-col">
            <div class="px-6 py-5 border-b dark:border-slate-700 flex justify-between items-center">
              <h3 class="font-extrabold text-slate-900 dark:text-white text-lg">สร้างประกาศใหม่</h3>
              <button @click="showAddAnnouncementModal = false" class="text-slate-400 hover:text-slate-500">
                <Icon icon="fluent:dismiss-24-regular" class="h-6 w-6" />
              </button>
            </div>

            <div class="p-6 space-y-4">
              <div class="space-y-1">
                <label class="text-xs font-bold text-slate-500 dark:text-slate-400">หัวข้อประกาศ</label>
                <input
                  v-model="newAnnouncementTitle"
                  type="text"
                  placeholder="เช่น กำหนดการสอบ, แจ้งเตือนส่งงาน..."
                  class="w-full rounded-xl border border-slate-300 dark:border-slate-700 px-3 py-2.5 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-sm outline-none focus:ring-2 focus:ring-primary-500"
                />
              </div>

              <div class="space-y-1">
                <label class="text-xs font-bold text-slate-500 dark:text-slate-400">รายละเอียดประกาศ</label>
                <textarea
                  v-model="newAnnouncementContent"
                  rows="5"
                  placeholder="กรอกรายละเอียดข่าวสารที่นี่..."
                  class="w-full rounded-xl border border-slate-300 dark:border-slate-700 px-3 py-2.5 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-sm outline-none focus:ring-2 focus:ring-primary-500"
                ></textarea>
              </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/20 border-t dark:border-slate-700 flex justify-end gap-2">
              <button
                @click="showAddAnnouncementModal = false"
                class="rounded-xl border border-slate-300 dark:border-slate-700 px-4 py-2 text-sm font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors"
              >
                ยกเลิก
              </button>
              
              <button
                @click="handleAddAnnouncement"
                :disabled="!newAnnouncementTitle.trim() || !newAnnouncementContent.trim()"
                class="rounded-xl bg-gradient-to-r from-primary-600 to-sky-500 px-5 py-2 text-sm font-bold text-white shadow-md active:scale-95 transition-all disabled:opacity-50"
              >
                ลงประกาศ
              </button>
            </div>
          </div>
        </div>

        <!-- Renumber Preview Modal -->
        <div v-if="showRenumberModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showRenumberModal = false"></div>
          
          <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl max-w-3xl w-full overflow-hidden border border-slate-100 dark:border-slate-700 transform transition-all flex flex-col max-h-[90vh]">
            <div class="px-6 py-5 border-b dark:border-slate-700 flex justify-between items-center">
              <div>
                <h3 class="font-extrabold text-slate-900 dark:text-white text-lg">จัดเรียงเลขที่ใหม่</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">เรียงตามลำดับเลขประจำตัวนักเรียน จากน้อยไปมาก</p>
              </div>
              <button @click="showRenumberModal = false" class="text-slate-400 hover:text-slate-500">
                <Icon icon="fluent:dismiss-24-regular" class="h-6 w-6" />
              </button>
            </div>

            <div class="p-6 space-y-4 overflow-hidden flex flex-col">
              <div class="rounded-xl bg-amber-50 dark:bg-amber-950/20 p-4 border border-amber-100 dark:border-amber-900/40 text-sm">
                <p class="font-bold text-amber-800 dark:text-amber-400">จะเปลี่ยนเลขที่ {{ renumberChangedCount }} รายการ จากนักเรียนทั้งหมด {{ renumberTotal }} คน</p>
                <p class="text-amber-700 dark:text-amber-500 mt-1">เลขที่ใหม่จะมีผลกับบัตรนักเรียนและใบรายชื่อที่พิมพ์ออกไปแล้ว</p>
              </div>

              <div class="flex-1 overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700 flex flex-col">
                <div class="overflow-y-auto max-h-[60vh]">
                  <table class="w-full text-left border-collapse">
                    <thead class="sticky top-0 bg-slate-50 dark:bg-slate-900 shadow-sm z-10">
                      <tr class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                        <th class="px-4 py-3 w-20">เลขที่เดิม</th>
                        <th class="px-2 py-3 w-10 text-center">→</th>
                        <th class="px-4 py-3 w-20">เลขที่ใหม่</th>
                        <th class="px-4 py-3 w-32">เลขประจำตัว</th>
                        <th class="px-4 py-3 min-w-[200px]">ชื่อ-นามสกุล</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60 text-sm">
                      <tr
                        v-for="row in renumberPreview"
                        :key="row.student_id"
                        :class="row.changed ? 'bg-amber-50/50 dark:bg-amber-900/10' : 'text-slate-500 dark:text-slate-400'"
                      >
                        <td class="px-4 py-2.5 font-mono tabular-nums">{{ row.from === null ? '-' : row.from }}</td>
                        <td class="px-2 py-2.5 text-center text-slate-300 dark:text-slate-600"><Icon icon="fluent:arrow-right-16-regular" class="h-3 w-3 inline-block" /></td>
                        <td class="px-4 py-2.5 font-mono tabular-nums" :class="row.changed ? 'font-bold text-slate-900 dark:text-white' : ''">{{ row.to }}</td>
                        <td class="px-4 py-2.5 font-mono">{{ row.student_code || '-' }}</td>
                        <td class="px-4 py-2.5" :class="row.changed ? 'font-semibold text-slate-900 dark:text-white' : ''">{{ row.full_name }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/20 border-t dark:border-slate-700 flex justify-end gap-2 shrink-0">
              <button
                @click="showRenumberModal = false"
                class="rounded-xl border border-slate-300 dark:border-slate-700 px-4 py-2 text-sm font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors"
              >
                ยกเลิก
              </button>
              
              <button
                @click="applyRenumber"
                :disabled="isApplyingRenumber"
                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-primary-600 to-sky-500 px-5 py-2 text-sm font-bold text-white shadow-md active:scale-95 transition-all disabled:opacity-50"
              >
                <Icon v-if="isApplyingRenumber" icon="fluent:spinner-ios-20-filled" class="h-4 w-4 animate-spin" />
                ยืนยันจัดเรียงใหม่
              </button>
            </div>
          </div>
        </div>

        <!-- Student Profile Slider/Drawer -->
        <div v-if="showProfileDrawer" class="fixed inset-0 z-50 overflow-hidden">
          <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showProfileDrawer = false"></div>
          
          <div class="absolute inset-y-0 right-0 max-w-full flex pl-10">
            <div class="w-screen max-w-md bg-white dark:bg-slate-800 shadow-2xl border-l dark:border-slate-700 transform transition-transform duration-300 flex flex-col h-full">
              <!-- Header -->
              <div class="px-6 py-5 border-b dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-900/40">
                <h3 class="font-extrabold text-slate-900 dark:text-white text-base flex items-center gap-2">
                  <Icon icon="fluent:person-info-24-regular" class="h-5 w-5 text-primary-500" />
                  ข้อมูลโปรไฟล์นักเรียน
                </h3>
                <button @click="showProfileDrawer = false" class="text-slate-400 hover:text-slate-500">
                  <Icon icon="fluent:dismiss-24-regular" class="h-5 w-5" />
                </button>
              </div>

              <!-- Body -->
              <div class="flex-1 overflow-y-auto p-6 space-y-6">
                <div v-if="isLoadingProfile" class="flex flex-col items-center justify-center py-20 gap-3">
                  <div class="h-8 w-8 animate-spin rounded-full border-4 border-primary-500 border-t-transparent mx-auto"></div>
                  <p class="text-xs text-slate-400 animate-pulse">กำลังโหลดข้อมูลเชิงลึก...</p>
                </div>

                <div v-else-if="selectedStudentForProfile" class="space-y-6">
                  <!-- Photo & Name block -->
                  <div class="flex flex-col items-center text-center space-y-3">
                    <img
                      v-if="selectedStudentForProfile.profile_image_url || selectedStudentForProfile.profile_image"
                      :src="selectedStudentForProfile.profile_image_url || selectedStudentForProfile.profile_image"
                      class="h-24 w-24 rounded-full object-cover border-4 border-white shadow-lg dark:border-slate-700"
                      alt="profile image"
                    />
                    <div v-else class="h-24 w-24 rounded-full bg-primary-100 flex items-center justify-center font-black text-primary-600 text-3xl dark:bg-primary-950 dark:text-primary-300 shadow-md">
                      {{ studentName(selectedStudentForProfile).charAt(0) }}
                    </div>
                    <div>
                      <h4 class="font-extrabold text-slate-900 dark:text-white text-lg">{{ studentName(selectedStudentForProfile) }}</h4>
                      <p class="text-xs font-mono text-slate-400 mt-0.5">เลขประจำตัว: {{ selectedStudentForProfile.student_id || '-' }}</p>
                    </div>
                  </div>

                  <!-- Details Section 1: Personal Info -->
                  <div class="space-y-3 rounded-xl bg-slate-50 dark:bg-slate-900/30 p-4 border dark:border-slate-700/60">
                    <h5 class="text-xs font-bold text-slate-400 uppercase tracking-wider">ข้อมูลส่วนตัว</h5>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                      <div>
                        <p class="text-xs text-slate-400">ชื่อเล่น</p>
                        <p class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5">{{ selectedStudentForProfile.nickname || '-' }}</p>
                      </div>
                      <div>
                        <p class="text-xs text-slate-400">เพศ</p>
                        <p class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5">{{ genderLabel(selectedStudentForProfile.gender) }}</p>
                      </div>
                      <div>
                        <p class="text-xs text-slate-400">วันเกิด</p>
                        <p class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5">{{ selectedStudentForProfile.date_of_birth || '-' }}</p>
                      </div>
                      <div>
                        <p class="text-xs text-slate-400">เลขบัตรประชาชน</p>
                        <p class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5 font-mono text-xs">{{ selectedStudentForProfile.citizen_id || '-' }}</p>
                      </div>
                    </div>
                  </div>

                  <!-- Details Section 2: Health Info -->
                  <div class="space-y-3 rounded-xl bg-slate-50 dark:bg-slate-900/30 p-4 border dark:border-slate-700/60">
                    <h5 class="text-xs font-bold text-slate-400 uppercase tracking-wider">ข้อมูลสุขภาพ</h5>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                      <div>
                        <p class="text-xs text-slate-400">หมู่โลหิต</p>
                        <p class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5">{{ selectedStudentForProfile.healthInfo?.blood_type || '-' }}</p>
                      </div>
                      <div>
                        <p class="text-xs text-slate-400">โรคประจำตัว</p>
                        <p class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5">{{ selectedStudentForProfile.healthInfo?.congenital_disease || 'ไม่มี' }}</p>
                      </div>
                      <div class="col-span-2">
                        <p class="text-xs text-slate-400">แพ้อาหาร/แพ้ยา</p>
                        <p class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5">{{ selectedStudentForProfile.healthInfo?.food_allergies || 'ไม่มี' }}</p>
                      </div>
                    </div>
                  </div>

                  <!-- Details Section 3: Guardian Details -->
                  <div class="space-y-3 rounded-xl bg-slate-50 dark:bg-slate-900/30 p-4 border dark:border-slate-700/60">
                    <h5 class="text-xs font-bold text-slate-400 uppercase tracking-wider">ผู้ปกครองของนักเรียน</h5>
                    <div v-if="!selectedStudentForProfile.guardians || selectedStudentForProfile.guardians.length === 0" class="text-xs text-slate-400">
                      ไม่พบข้อมูลผู้ปกครอง
                    </div>
                    
                    <div v-else class="space-y-3">
                      <div v-for="g in selectedStudentForProfile.guardians" :key="g.id" class="border-b last:border-b-0 pb-2 last:pb-0 dark:border-slate-700">
                        <p class="text-xs text-slate-400">{{ g.relationship === 'father' ? 'บิดา' : g.relationship === 'mother' ? 'มารดา' : 'ผู้ปกครองหลัก' }}</p>
                        <p class="font-bold text-slate-800 dark:text-slate-100 mt-0.5 text-sm">{{ g.guardian_name }}</p>
                        <p class="text-xs text-slate-500 font-mono mt-0.5">โทร: {{ g.phone || '-' }}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/20 border-t dark:border-slate-700 flex justify-end gap-2">
                <button
                  @click="showProfileDrawer = false"
                  class="rounded-xl border border-slate-300 dark:border-slate-700 px-5 py-2 text-sm font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors"
                >
                  ปิดหน้าต่าง
                </button>
                <NuxtLink
                  v-if="selectedStudentForProfile?.id"
                  :to="`/academies/${route.params.name}/students/${selectedStudentForProfile.id}/profile`"
                  class="rounded-xl bg-primary-600 px-5 py-2 text-sm font-bold text-white hover:bg-primary-700 transition-colors"
                >
                  เปิดโปรไฟล์เต็ม / แก้ไขข้อมูล
                </NuxtLink>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes scaleLoop {
  0% {
    transform: scale(1);
  }
  100% {
    transform: scale(1.1);
  }
}
</style>
