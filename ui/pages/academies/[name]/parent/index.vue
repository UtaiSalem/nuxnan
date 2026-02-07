<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white">
      <div class="container mx-auto px-4 py-6">
        <div class="flex items-center space-x-4">
          <div class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center">
            <Icon name="mdi:account-child-circle" class="w-8 h-8" />
          </div>
          <div>
            <h1 class="text-2xl font-bold">สวัสดี, ผู้ปกครอง</h1>
            <p class="text-blue-100">ติดตามข้อมูลบุตรหลานของคุณ</p>
          </div>
        </div>
      </div>
    </div>

    <div class="container mx-auto px-4 py-6 space-y-6">
      <!-- Children Cards -->
      <section>
        <h2 class="text-lg font-semibold text-gray-800 mb-4">
          <Icon name="mdi:account-group" class="w-5 h-5 mr-2 inline text-blue-600" />
          บุตรหลานของฉัน
        </h2>

        <div v-if="loadingChildren" class="flex justify-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>

        <div v-else-if="children.length === 0" class="bg-white rounded-xl shadow-sm border p-8 text-center">
          <Icon name="mdi:account-search-outline" class="w-16 h-16 mx-auto text-gray-300 mb-4" />
          <p class="text-gray-500 mb-2">ไม่พบข้อมูลบุตรหลาน</p>
          <p class="text-sm text-gray-400">กรุณาติดต่อโรงเรียนเพื่อเชื่อมโยงข้อมูล</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="child in children"
            :key="child.id"
            class="bg-white rounded-xl shadow-sm border overflow-hidden hover:shadow-md transition-shadow cursor-pointer"
            @click="selectChild(child)"
          >
            <div class="p-4">
              <div class="flex items-center space-x-4">
                <img
                  :src="child.photo || '/images/default-student.png'"
                  :alt="child.name"
                  class="w-16 h-16 rounded-full object-cover border-2 border-gray-200"
                />
                <div class="flex-1 min-w-0">
                  <h3 class="font-semibold text-gray-900 truncate">{{ child.name }}</h3>
                  <p class="text-sm text-gray-500">รหัส: {{ child.student_id }}</p>
                  <p class="text-sm text-blue-600">{{ child.classroom }}</p>
                </div>
                <Icon name="mdi:chevron-right" class="w-6 h-6 text-gray-400" />
              </div>
            </div>
            <div class="bg-gray-50 px-4 py-2 flex items-center justify-between text-xs">
              <span class="text-gray-500">เลขที่: {{ child.student_number }}</span>
              <span class="text-gray-500">{{ child.grade_level }}</span>
            </div>
          </div>
        </div>
      </section>

      <!-- Selected Child Details (if selected) -->
      <div v-if="selectedChild" class="space-y-6">
        <!-- Quick Stats -->
        <section class="bg-white rounded-xl shadow-sm border p-4">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-800">{{ selectedChild.name }}</h3>
            <button @click="selectedChild = null" class="text-gray-400 hover:text-gray-600">
              <Icon name="mdi:close" class="w-5 h-5" />
            </button>
          </div>

          <!-- Tab Navigation -->
          <div class="flex border-b mb-4 overflow-x-auto">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              :class="[
                'px-4 py-2 text-sm font-medium whitespace-nowrap border-b-2 transition-colors',
                activeTab === tab.id
                  ? 'border-blue-600 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700'
              ]"
            >
              <Icon :name="tab.icon" class="w-4 h-4 mr-1 inline" />
              {{ tab.label }}
            </button>
          </div>

          <!-- Tab Content -->
          <div class="min-h-[200px]">
            <!-- Grades Tab -->
            <div v-if="activeTab === 'grades'">
              <div v-if="loadingGrades" class="flex justify-center py-8">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
              </div>
              <div v-else-if="grades.length === 0" class="text-center py-8 text-gray-500">
                ยังไม่มีข้อมูลผลการเรียน
              </div>
              <div v-else class="space-y-2">
                <div
                  v-for="grade in grades"
                  :key="grade.course_id"
                  class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                >
                  <div>
                    <div class="font-medium text-gray-900">{{ grade.course_name }}</div>
                    <div class="text-xs text-gray-500">{{ grade.course_code }}</div>
                  </div>
                  <div class="text-right">
                    <div class="font-bold text-lg" :class="getGradeColor(grade.grade)">
                      {{ grade.grade }}
                    </div>
                    <div v-if="grade.score" class="text-xs text-gray-500">
                      {{ grade.score }} คะแนน
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Attendance Tab -->
            <div v-if="activeTab === 'attendance'">
              <div v-if="loadingAttendance" class="flex justify-center py-8">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
              </div>
              <div v-else>
                <!-- Summary -->
                <div class="grid grid-cols-4 gap-2 mb-4">
                  <div class="text-center p-2 bg-green-50 rounded-lg">
                    <div class="text-xl font-bold text-green-600">{{ attendanceSummary.present }}</div>
                    <div class="text-xs text-green-700">มา</div>
                  </div>
                  <div class="text-center p-2 bg-red-50 rounded-lg">
                    <div class="text-xl font-bold text-red-600">{{ attendanceSummary.absent }}</div>
                    <div class="text-xs text-red-700">ขาด</div>
                  </div>
                  <div class="text-center p-2 bg-yellow-50 rounded-lg">
                    <div class="text-xl font-bold text-yellow-600">{{ attendanceSummary.late }}</div>
                    <div class="text-xs text-yellow-700">สาย</div>
                  </div>
                  <div class="text-center p-2 bg-blue-50 rounded-lg">
                    <div class="text-xl font-bold text-blue-600">{{ attendanceSummary.leave }}</div>
                    <div class="text-xs text-blue-700">ลา</div>
                  </div>
                </div>

                <!-- Recent Attendance -->
                <div class="space-y-2">
                  <div
                    v-for="att in attendance.slice(0, 10)"
                    :key="att.date"
                    class="flex items-center justify-between p-2 bg-gray-50 rounded-lg text-sm"
                  >
                    <div>
                      <span class="font-medium">{{ formatDate(att.date) }}</span>
                      <span class="text-gray-500 ml-2">{{ att.day_name }}</span>
                    </div>
                    <span :class="getAttendanceClass(att.status)">
                      {{ getAttendanceLabel(att.status) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Info Tab -->
            <div v-if="activeTab === 'info'">
              <div class="space-y-3">
                <div class="flex justify-between py-2 border-b">
                  <span class="text-gray-500">รหัสนักเรียน</span>
                  <span class="font-medium">{{ childDetail?.student_id }}</span>
                </div>
                <div class="flex justify-between py-2 border-b">
                  <span class="text-gray-500">ชั้นเรียน</span>
                  <span class="font-medium">{{ childDetail?.classroom?.name }}</span>
                </div>
                <div class="flex justify-between py-2 border-b">
                  <span class="text-gray-500">เลขที่</span>
                  <span class="font-medium">{{ childDetail?.classroom?.student_number }}</span>
                </div>
                <div class="flex justify-between py-2 border-b">
                  <span class="text-gray-500">วันเกิด</span>
                  <span class="font-medium">{{ formatDate(childDetail?.birth_date) }}</span>
                </div>
                <div class="flex justify-between py-2 border-b">
                  <span class="text-gray-500">อายุ</span>
                  <span class="font-medium">{{ childDetail?.age }} ปี</span>
                </div>
                <div class="flex justify-between py-2">
                  <span class="text-gray-500">หมู่เลือด</span>
                  <span class="font-medium">{{ childDetail?.blood_type || '-' }}</span>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>

      <!-- Announcements -->
      <section>
        <h2 class="text-lg font-semibold text-gray-800 mb-4">
          <Icon name="mdi:bullhorn" class="w-5 h-5 mr-2 inline text-orange-500" />
          ประกาศจากโรงเรียน
        </h2>

        <div v-if="loadingAnnouncements" class="flex justify-center py-8">
          <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
        </div>

        <div v-else-if="announcements.length === 0" class="bg-white rounded-xl shadow-sm border p-6 text-center text-gray-500">
          ไม่มีประกาศ
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="ann in announcements.slice(0, 5)"
            :key="ann.id"
            class="bg-white rounded-xl shadow-sm border p-4"
          >
            <div class="flex items-start space-x-3">
              <div v-if="ann.is_pinned" class="flex-shrink-0">
                <Icon name="mdi:pin" class="w-5 h-5 text-red-500" />
              </div>
              <div class="flex-1">
                <h3 class="font-medium text-gray-900">{{ ann.title }}</h3>
                <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ ann.excerpt }}</p>
                <div class="flex items-center mt-2 text-xs text-gray-400">
                  <Icon name="mdi:calendar" class="w-3 h-3 mr-1" />
                  {{ formatDate(ann.published_at) }}
                  <span class="mx-2">·</span>
                  <span>{{ ann.author }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Upcoming Events -->
      <section>
        <h2 class="text-lg font-semibold text-gray-800 mb-4">
          <Icon name="mdi:calendar-star" class="w-5 h-5 mr-2 inline text-purple-500" />
          กิจกรรมที่จะมาถึง
        </h2>

        <div v-if="events.length === 0" class="bg-white rounded-xl shadow-sm border p-6 text-center text-gray-500">
          ไม่มีกิจกรรมที่จะมาถึง
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="event in events.slice(0, 5)"
            :key="event.id"
            class="bg-white rounded-xl shadow-sm border p-4 flex items-center space-x-4"
          >
            <div class="flex-shrink-0 w-14 h-14 bg-purple-100 rounded-lg flex flex-col items-center justify-center">
              <span class="text-lg font-bold text-purple-700">{{ formatDay(event.start_date) }}</span>
              <span class="text-xs text-purple-600">{{ formatMonth(event.start_date) }}</span>
            </div>
            <div class="flex-1">
              <h3 class="font-medium text-gray-900">{{ event.title }}</h3>
              <p class="text-sm text-gray-500">{{ event.location || 'ไม่ระบุสถานที่' }}</p>
            </div>
            <span v-if="event.is_holiday" class="px-2 py-1 bg-red-100 text-red-600 text-xs rounded-full">
              หยุด
            </span>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'

const route = useRoute()
const { $api } = useNuxtApp()

const academyName = route.params.name as string
const academyId = ref<number | null>(null)

// State
const children = ref<any[]>([])
const selectedChild = ref<any>(null)
const childDetail = ref<any>(null)
const grades = ref<any[]>([])
const attendance = ref<any[]>([])
const attendanceSummary = ref({ present: 0, absent: 0, late: 0, leave: 0 })
const announcements = ref<any[]>([])
const events = ref<any[]>([])

// Loading states
const loadingChildren = ref(false)
const loadingGrades = ref(false)
const loadingAttendance = ref(false)
const loadingAnnouncements = ref(false)

const activeTab = ref('grades')
const tabs = [
  { id: 'grades', label: 'ผลการเรียน', icon: 'mdi:school' },
  { id: 'attendance', label: 'การเข้าเรียน', icon: 'mdi:calendar-check' },
  { id: 'info', label: 'ข้อมูลส่วนตัว', icon: 'mdi:account' },
]

// Load academy info
const loadAcademyInfo = async () => {
  try {
    const response = await $api.get(`/academies/${academyName}`)
    if (response.data) {
      academyId.value = response.data.id
    }
  } catch (error) {
    console.error('Error loading academy:', error)
  }
}

// Load children
const loadChildren = async () => {
  if (!academyId.value) return
  loadingChildren.value = true
  try {
    const response = await $api.get(`/academies/${academyId.value}/parent/children`)
    if (response.data.success) {
      children.value = response.data.children
    }
  } catch (error) {
    console.error('Error loading children:', error)
  } finally {
    loadingChildren.value = false
  }
}

// Select child
const selectChild = async (child: any) => {
  selectedChild.value = child
  loadChildDetail(child.id)
  loadGrades(child.id)
  loadAttendance(child.id)
}

// Load child detail
const loadChildDetail = async (studentId: number) => {
  if (!academyId.value) return
  try {
    const response = await $api.get(`/academies/${academyId.value}/parent/children/${studentId}`)
    if (response.data.success) {
      childDetail.value = response.data.student
    }
  } catch (error) {
    console.error('Error loading child detail:', error)
  }
}

// Load grades
const loadGrades = async (studentId: number) => {
  if (!academyId.value) return
  loadingGrades.value = true
  try {
    const response = await $api.get(`/academies/${academyId.value}/parent/children/${studentId}/grades`)
    if (response.data.success) {
      grades.value = response.data.grades
    }
  } catch (error) {
    console.error('Error loading grades:', error)
  } finally {
    loadingGrades.value = false
  }
}

// Load attendance
const loadAttendance = async (studentId: number) => {
  if (!academyId.value) return
  loadingAttendance.value = true
  try {
    const response = await $api.get(`/academies/${academyId.value}/parent/children/${studentId}/attendance`)
    if (response.data.success) {
      attendance.value = response.data.attendance
      attendanceSummary.value = response.data.summary
    }
  } catch (error) {
    console.error('Error loading attendance:', error)
  } finally {
    loadingAttendance.value = false
  }
}

// Load announcements
const loadAnnouncements = async () => {
  if (!academyId.value) return
  loadingAnnouncements.value = true
  try {
    const response = await $api.get(`/academies/${academyId.value}/parent/announcements`)
    if (response.data.success) {
      announcements.value = response.data.announcements
    }
  } catch (error) {
    console.error('Error loading announcements:', error)
  } finally {
    loadingAnnouncements.value = false
  }
}

// Load events
const loadEvents = async () => {
  if (!academyId.value) return
  try {
    const response = await $api.get(`/academies/${academyId.value}/parent/events`)
    if (response.data.success) {
      events.value = response.data.events
    }
  } catch (error) {
    console.error('Error loading events:', error)
  }
}

// Helper functions
const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

const formatDay = (date: string) => {
  return new Date(date).getDate()
}

const formatMonth = (date: string) => {
  return new Date(date).toLocaleDateString('th-TH', { month: 'short' })
}

const getGradeColor = (grade: string) => {
  if (grade === 'A' || grade === '4') return 'text-green-600'
  if (grade === 'B+' || grade === 'B' || grade === '3.5' || grade === '3') return 'text-blue-600'
  if (grade === 'C+' || grade === 'C' || grade === '2.5' || grade === '2') return 'text-yellow-600'
  if (grade === 'D+' || grade === 'D' || grade === '1.5' || grade === '1') return 'text-orange-600'
  if (grade === 'F' || grade === '0') return 'text-red-600'
  return 'text-gray-600'
}

const getAttendanceClass = (status: string) => {
  const classes: Record<string, string> = {
    present: 'text-green-600 bg-green-100 px-2 py-0.5 rounded',
    absent: 'text-red-600 bg-red-100 px-2 py-0.5 rounded',
    late: 'text-yellow-600 bg-yellow-100 px-2 py-0.5 rounded',
    sick: 'text-blue-600 bg-blue-100 px-2 py-0.5 rounded',
    leave: 'text-blue-600 bg-blue-100 px-2 py-0.5 rounded',
  }
  return classes[status] || 'text-gray-600'
}

const getAttendanceLabel = (status: string) => {
  const labels: Record<string, string> = {
    present: 'มาเรียน',
    absent: 'ขาดเรียน',
    late: 'มาสาย',
    sick: 'ลาป่วย',
    leave: 'ลากิจ',
  }
  return labels[status] || status
}

onMounted(async () => {
  await loadAcademyInfo()
  loadChildren()
  loadAnnouncements()
  loadEvents()
})
</script>
