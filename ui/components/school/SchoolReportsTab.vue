<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white">รายงานและวิเคราะห์</h2>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="section in sections"
          :key="section.id"
          @click="activeSection = section.id"
          :class="[
            activeSection === section.id
              ? 'bg-primary-500 text-white'
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300',
            'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors'
          ]"
        >
          {{ section.name }}
        </button>
      </div>
    </div>

    <!-- Dashboard Section -->
    <div v-if="activeSection === 'dashboard'" class="space-y-6">
      <!-- KPI Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div
          v-for="kpi in kpiData"
          :key="kpi.id"
          class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4"
        >
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ kpi.name }}</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                {{ kpi.value }}{{ kpi.unit }}
              </p>
            </div>
            <div :class="getKpiTrendClass(kpi.trend)" class="flex items-center gap-1 text-sm">
              <component :is="kpi.trend === 'up' ? ArrowUpIcon : ArrowDownIcon" class="h-4 w-4" />
              {{ kpi.change }}%
            </div>
          </div>
          <div class="mt-2">
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
              <div 
                class="bg-primary-500 h-1.5 rounded-full" 
                :style="{ width: `${Math.min(kpi.progress, 100)}%` }"
              ></div>
            </div>
            <p class="text-xs text-gray-500 mt-1">เป้าหมาย: {{ kpi.target }}{{ kpi.unit }}</p>
          </div>
        </div>
      </div>

      <!-- Widgets Grid -->
      <div v-if="loadingWidgets" class="flex justify-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
      </div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Widget: Student Enrollment -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
          <h4 class="font-semibold text-gray-900 dark:text-white mb-4">จำนวนนักเรียนรายเดือน</h4>
          <div class="h-64 flex items-center justify-center text-gray-400">
            <!-- Chart placeholder -->
            <div class="text-center">
              <Icon icon="heroicons:chart-bar" class="h-16 w-16 mx-auto mb-2 text-gray-300" />
              <p>กราฟแสดงจำนวนนักเรียน</p>
            </div>
          </div>
        </div>

        <!-- Widget: Attendance Rate -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
          <h4 class="font-semibold text-gray-900 dark:text-white mb-4">อัตราการเข้าเรียน</h4>
          <div class="h-64 flex items-center justify-center">
            <div class="relative">
              <svg class="h-40 w-40 transform -rotate-90">
                <circle cx="80" cy="80" r="70" fill="none" stroke="#e5e7eb" stroke-width="12" />
                <circle 
                  cx="80" cy="80" r="70" 
                  fill="none" 
                  stroke="#3b82f6" 
                  stroke-width="12"
                  stroke-dasharray="439.8"
                  :stroke-dashoffset="439.8 * (1 - 0.92)"
                />
              </svg>
              <div class="absolute inset-0 flex items-center justify-center">
                <span class="text-3xl font-bold text-gray-900 dark:text-white">92%</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Widget: Fee Collection -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
          <h4 class="font-semibold text-gray-900 dark:text-white mb-4">การเก็บค่าธรรมเนียม</h4>
          <div class="space-y-4">
            <div>
              <div class="flex justify-between text-sm mb-1">
                <span class="text-gray-600 dark:text-gray-400">ชำระแล้ว</span>
                <span class="font-medium text-gray-900 dark:text-white">฿850,000</span>
              </div>
              <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                <div class="bg-green-500 h-3 rounded-full" style="width: 85%"></div>
              </div>
            </div>
            <div>
              <div class="flex justify-between text-sm mb-1">
                <span class="text-gray-600 dark:text-gray-400">ค้างชำระ</span>
                <span class="font-medium text-gray-900 dark:text-white">฿150,000</span>
              </div>
              <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                <div class="bg-red-500 h-3 rounded-full" style="width: 15%"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Widget: Staff Overview -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
          <h4 class="font-semibold text-gray-900 dark:text-white mb-4">สรุปบุคลากร</h4>
          <div class="grid grid-cols-2 gap-4">
            <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
              <p class="text-2xl font-bold text-blue-600">25</p>
              <p class="text-sm text-gray-600 dark:text-gray-400">ครู</p>
            </div>
            <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
              <p class="text-2xl font-bold text-green-600">10</p>
              <p class="text-sm text-gray-600 dark:text-gray-400">บุคลากรสนับสนุน</p>
            </div>
            <div class="text-center p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
              <p class="text-2xl font-bold text-yellow-600">3</p>
              <p class="text-sm text-gray-600 dark:text-gray-400">ลาวันนี้</p>
            </div>
            <div class="text-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
              <p class="text-2xl font-bold text-purple-600">2</p>
              <p class="text-sm text-gray-600 dark:text-gray-400">ตำแหน่งว่าง</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Reports Section -->
    <div v-if="activeSection === 'reports'" class="space-y-4">
      <div class="flex justify-between items-center">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">รายงาน</h3>
        <button
          @click="showReportModal = true"
          class="inline-flex items-center gap-2 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors"
        >
          <Icon icon="heroicons:plus" class="h-5 w-5" />
          <span class="hidden sm:inline">สร้างรายงาน</span>
        </button>
      </div>

      <div v-if="loadingReports" class="flex justify-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="report in reports"
          :key="report.id"
          class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-shadow cursor-pointer"
          @click="generateReport(report)"
        >
          <div class="flex items-start gap-3">
            <div :class="getReportIconClass(report.report_type)" class="p-3 rounded-lg">
              <component :is="getReportIcon(report.report_type)" class="h-6 w-6" />
            </div>
            <div class="flex-1 min-w-0">
              <h4 class="font-semibold text-gray-900 dark:text-white">{{ report.name }}</h4>
              <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2">{{ report.description }}</p>
            </div>
          </div>
          <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <span class="text-xs text-gray-500">{{ getReportTypeLabel(report.report_type) }}</span>
            <div class="flex items-center gap-2">
              <button 
                @click.stop="downloadReport(report, 'pdf')"
                class="text-xs px-2 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200"
              >
                PDF
              </button>
              <button 
                @click.stop="downloadReport(report, 'excel')"
                class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200"
              >
                Excel
              </button>
            </div>
          </div>
        </div>

        <div v-if="reports.length === 0" class="col-span-full text-center py-12 text-gray-500">
          <Icon icon="heroicons:document-text" class="h-12 w-12 mx-auto mb-3 text-gray-300" />
          <p>ยังไม่มีรายงาน</p>
        </div>
      </div>

      <!-- Quick Reports -->
      <div class="mt-8">
        <h4 class="font-medium text-gray-900 dark:text-white mb-4">รายงานด่วน</h4>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <button
            v-for="quick in quickReports"
            :key="quick.id"
            @click="generateQuickReport(quick.id)"
            class="p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:border-primary-400 hover:shadow-md transition-all text-left"
          >
            <component :is="quick.icon" class="h-8 w-8 text-primary-500 mb-2" />
            <p class="font-medium text-gray-900 dark:text-white text-sm">{{ quick.name }}</p>
          </button>
        </div>
      </div>
    </div>

    <!-- Analytics Section -->
    <div v-if="activeSection === 'analytics'" class="space-y-6">
      <!-- Date Range Filter -->
      <div class="flex flex-wrap gap-4 items-center">
        <div class="flex items-center gap-2">
          <label class="text-sm text-gray-600 dark:text-gray-400">ตั้งแต่:</label>
          <input
            v-model="analyticsDateFrom"
            type="date"
            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          />
        </div>
        <div class="flex items-center gap-2">
          <label class="text-sm text-gray-600 dark:text-gray-400">ถึง:</label>
          <input
            v-model="analyticsDateTo"
            type="date"
            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          />
        </div>
        <button
          @click="loadAnalytics"
          class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors"
        >
          วิเคราะห์
        </button>
      </div>

      <div v-if="loadingAnalytics" class="flex justify-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
      </div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Academic Performance -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
          <h4 class="font-semibold text-gray-900 dark:text-white mb-4">ผลการเรียนตามระดับชั้น</h4>
          <div class="space-y-3">
            <div v-for="grade in gradePerformance" :key="grade.level" class="flex items-center gap-4">
              <span class="w-24 text-sm text-gray-600 dark:text-gray-400">{{ grade.level }}</span>
              <div class="flex-1">
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                  <div 
                    class="h-4 rounded-full transition-all"
                    :class="getGradeClass(grade.average)"
                    :style="{ width: `${grade.average}%` }"
                  ></div>
                </div>
              </div>
              <span class="w-16 text-right font-medium text-gray-900 dark:text-white">{{ grade.average }}%</span>
            </div>
          </div>
        </div>

        <!-- Attendance Trends -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
          <h4 class="font-semibold text-gray-900 dark:text-white mb-4">แนวโน้มการเข้าเรียน</h4>
          <div class="h-64 flex items-center justify-center text-gray-400">
            <div class="text-center">
              <Icon icon="heroicons:presentation-chart-line" class="h-16 w-16 mx-auto mb-2 text-gray-300" />
              <p>กราฟแนวโน้มการเข้าเรียน</p>
            </div>
          </div>
        </div>

        <!-- Revenue Analysis -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
          <h4 class="font-semibold text-gray-900 dark:text-white mb-4">วิเคราะห์รายรับ-รายจ่าย</h4>
          <div class="space-y-4">
            <div class="flex justify-between items-center">
              <span class="text-gray-600 dark:text-gray-400">รายรับรวม</span>
              <span class="text-xl font-bold text-green-600">฿{{ formatMoney(analyticsData.totalRevenue) }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-gray-600 dark:text-gray-400">รายจ่ายรวม</span>
              <span class="text-xl font-bold text-red-600">฿{{ formatMoney(analyticsData.totalExpense) }}</span>
            </div>
            <hr class="border-gray-200 dark:border-gray-700" />
            <div class="flex justify-between items-center">
              <span class="text-gray-600 dark:text-gray-400">กำไร/ขาดทุน</span>
              <span :class="analyticsData.profit >= 0 ? 'text-green-600' : 'text-red-600'" class="text-xl font-bold">
                ฿{{ formatMoney(analyticsData.profit) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Popular Courses -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
          <h4 class="font-semibold text-gray-900 dark:text-white mb-4">รายวิชายอดนิยม</h4>
          <div class="space-y-3">
            <div v-for="(course, index) in popularCourses" :key="course.id" class="flex items-center gap-3">
              <span class="w-6 h-6 flex items-center justify-center bg-primary-100 dark:bg-primary-900/30 text-primary-600 rounded-full text-sm font-medium">
                {{ index + 1 }}
              </span>
              <div class="flex-1 min-w-0">
                <p class="font-medium text-gray-900 dark:text-white truncate">{{ course.name }}</p>
                <p class="text-sm text-gray-500">{{ course.students }} นักเรียน</p>
              </div>
              <span class="text-sm text-gray-500">{{ course.rating }} ⭐</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- KPIs Section -->
    <div v-if="activeSection === 'kpis'" class="space-y-4">
      <div class="flex justify-between items-center">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">ตัวชี้วัด (KPIs)</h3>
        <button
          @click="showKpiModal = true"
          class="inline-flex items-center gap-2 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors"
        >
          <Icon icon="heroicons:plus" class="h-5 w-5" />
          <span class="hidden sm:inline">เพิ่ม KPI</span>
        </button>
      </div>

      <div v-if="loadingKpis" class="flex justify-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
              <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">ตัวชี้วัด</th>
              <th class="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300">เป้าหมาย</th>
              <th class="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300">ปัจจุบัน</th>
              <th class="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300">ความคืบหน้า</th>
              <th class="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300">สถานะ</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="kpi in kpiDefinitions" :key="kpi.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
              <td class="px-4 py-3">
                <div class="font-medium text-gray-900 dark:text-white">{{ kpi.name }}</div>
                <div class="text-xs text-gray-500">{{ kpi.description }}</div>
              </td>
              <td class="px-4 py-3 text-center text-gray-900 dark:text-white">{{ kpi.target_value }}{{ kpi.unit }}</td>
              <td class="px-4 py-3 text-center font-medium text-gray-900 dark:text-white">{{ kpi.current_value }}{{ kpi.unit }}</td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div 
                      class="h-2 rounded-full transition-all"
                      :class="getKpiProgressClass(kpi)"
                      :style="{ width: `${Math.min(getKpiProgress(kpi), 100)}%` }"
                    ></div>
                  </div>
                  <span class="text-xs text-gray-500 w-10 text-right">{{ getKpiProgress(kpi) }}%</span>
                </div>
              </td>
              <td class="px-4 py-3 text-center">
                <span :class="getKpiStatusClass(kpi)" class="px-2 py-1 text-xs rounded-full">
                  {{ getKpiStatusLabel(kpi) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="kpiDefinitions.length === 0" class="text-center py-12 text-gray-500">
          <Icon icon="heroicons:chart-pie" class="h-12 w-12 mx-auto mb-3 text-gray-300" />
          <p>ยังไม่มี KPI</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps<{
  academyId: number
}>()

const schoolApi = useSchoolManagement()

// State
const activeSection = ref('dashboard')
const sections = [
  { id: 'dashboard', name: 'แดชบอร์ด' },
  { id: 'reports', name: 'รายงาน' },
  { id: 'analytics', name: 'วิเคราะห์' },
  { id: 'kpis', name: 'KPIs' },
]

// Dashboard
const loadingWidgets = ref(false)
const kpiData = ref([
  { id: 1, name: 'นักเรียนทั้งหมด', value: 450, unit: ' คน', target: 500, progress: 90, trend: 'up', change: 5 },
  { id: 2, name: 'อัตราเข้าเรียน', value: 92, unit: '%', target: 95, progress: 97, trend: 'up', change: 2 },
  { id: 3, name: 'รายได้รวม', value: 850, unit: 'K', target: 1000, progress: 85, trend: 'up', change: 12 },
  { id: 4, name: 'ความพึงพอใจ', value: 4.2, unit: '/5', target: 4.5, progress: 93, trend: 'down', change: 3 },
])

// Reports
const reports = ref<any[]>([])
const loadingReports = ref(false)
const showReportModal = ref(false)
const quickReports = [
  { id: 'attendance', name: 'รายงานเข้าเรียน', icon: 'heroicons:calendar' },
  { id: 'grades', name: 'รายงานผลการเรียน', icon: 'heroicons:academic-cap' },
  { id: 'finance', name: 'รายงานการเงิน', icon: 'heroicons:currency-dollar' },
  { id: 'staff', name: 'รายงานบุคลากร', icon: 'heroicons:users' },
]

// Analytics
const loadingAnalytics = ref(false)
const analyticsDateFrom = ref('')
const analyticsDateTo = ref('')
const analyticsData = ref({
  totalRevenue: 1250000,
  totalExpense: 890000,
  profit: 360000,
})
const gradePerformance = ref([
  { level: 'ป.1', average: 78 },
  { level: 'ป.2', average: 82 },
  { level: 'ป.3', average: 75 },
  { level: 'ป.4', average: 80 },
  { level: 'ป.5', average: 85 },
  { level: 'ป.6', average: 88 },
])
const popularCourses = ref([
  { id: 1, name: 'คณิตศาสตร์', students: 120, rating: 4.5 },
  { id: 2, name: 'ภาษาอังกฤษ', students: 115, rating: 4.3 },
  { id: 3, name: 'วิทยาศาสตร์', students: 98, rating: 4.4 },
  { id: 4, name: 'ภาษาไทย', students: 95, rating: 4.2 },
  { id: 5, name: 'คอมพิวเตอร์', students: 88, rating: 4.6 },
])

// KPIs
const kpiDefinitions = ref<any[]>([])
const loadingKpis = ref(false)
const showKpiModal = ref(false)

// Helpers
const formatMoney = (amount: number) => new Intl.NumberFormat('th-TH').format(amount || 0)

const getKpiTrendClass = (trend: string) => {
  return trend === 'up' ? 'text-green-600' : 'text-red-600'
}

const getReportIconClass = (type: string) => {
  const classes: Record<string, string> = {
    academic: 'bg-blue-100 text-blue-600',
    financial: 'bg-green-100 text-green-600',
    attendance: 'bg-yellow-100 text-yellow-600',
    staff: 'bg-purple-100 text-purple-600',
  }
  return classes[type] || 'bg-gray-100 text-gray-600'
}

const getReportIcon = (type: string) => {
  const icons: Record<string, string> = {
    academic: 'heroicons:academic-cap',
    financial: 'heroicons:currency-dollar',
    attendance: 'heroicons:calendar',
    staff: 'heroicons:users',
  }
  return icons[type] || 'heroicons:document-text'
}

const getReportTypeLabel = (type: string) => {
  const labels: Record<string, string> = {
    academic: 'วิชาการ',
    financial: 'การเงิน',
    attendance: 'เข้าเรียน',
    staff: 'บุคลากร',
  }
  return labels[type] || type
}

const getGradeClass = (average: number) => {
  if (average >= 80) return 'bg-green-500'
  if (average >= 60) return 'bg-yellow-500'
  return 'bg-red-500'
}

const getKpiProgress = (kpi: any) => {
  if (!kpi.target_value) return 0
  return Math.round((kpi.current_value / kpi.target_value) * 100)
}

const getKpiProgressClass = (kpi: any) => {
  const progress = getKpiProgress(kpi)
  if (progress >= 100) return 'bg-green-500'
  if (progress >= 70) return 'bg-yellow-500'
  return 'bg-red-500'
}

const getKpiStatusClass = (kpi: any) => {
  const progress = getKpiProgress(kpi)
  if (progress >= 100) return 'bg-green-100 text-green-800'
  if (progress >= 70) return 'bg-yellow-100 text-yellow-800'
  return 'bg-red-100 text-red-800'
}

const getKpiStatusLabel = (kpi: any) => {
  const progress = getKpiProgress(kpi)
  if (progress >= 100) return 'บรรลุเป้าหมาย'
  if (progress >= 70) return 'ใกล้เป้าหมาย'
  return 'ต้องปรับปรุง'
}

// Helper to safely extract array data
const extractArray = (response: any): any[] => {
  if (!response) return []
  if (Array.isArray(response)) return response
  if (response.data && Array.isArray(response.data)) return response.data
  return []
}

// Load data
const loadReports = async () => {
  loadingReports.value = true
  try {
    const response = await schoolApi.getReports(props.academyId)
    reports.value = extractArray(response)
  } catch (error) {
    console.error('Failed to load reports:', error)
    reports.value = []
  } finally {
    loadingReports.value = false
  }
}

const loadAnalytics = async () => {
  loadingAnalytics.value = true
  try {
    const response: any = await schoolApi.getAnalyticsOverview(props.academyId)
    if (response.success && response.data) {
      // Map response data to UI state
      analyticsData.value = {
        totalRevenue: response.data.revenue || 0,
        totalExpense: response.data.expense || 0,
        profit: (response.data.revenue || 0) - (response.data.expense || 0),
      }
    }
  } catch (error) {
    console.error('Failed to load analytics:', error)
  } finally {
    loadingAnalytics.value = false
  }
}

const loadKpis = async () => {
  loadingKpis.value = true
  try {
    const response: any = await schoolApi.getKPIs(props.academyId)
    kpiDefinitions.value = extractArray(response)
  } catch (error) {
    console.error('Failed to load KPIs:', error)
    kpiDefinitions.value = []
  } finally {
    loadingKpis.value = false
  }
}

// Actions
const generateReport = async (report: any) => {
  const reportName = prompt('ระบุชื่อรายงานที่ต้องการสร้าง:', report.name)
  if (!reportName) return
  
  try {
    const response: any = await schoolApi.generateReport(props.academyId, report.id, {
      name: reportName,
      parameters: {} // Default params
    })
    
    if (response.success) {
      alert('สร้างรายงานเรียบร้อยแล้ว คุณสามารถดูได้ที่แท็บ "รายงานที่บันทึกไว้"')
    }
  } catch (error) {
    console.error('Failed to generate report:', error)
  }
}

const downloadReport = (_report: any, _format: string) => {
  // placeholder
}

const generateQuickReport = (_type: string) => {
  // placeholder
}

// Watch
watch(activeSection, (section) => {
  if (section === 'reports' && reports.value.length === 0) loadReports()
  if (section === 'kpis' && kpiDefinitions.value.length === 0) loadKpis()
}, { immediate: true })

// Set default date range
onMounted(() => {
  const now = new Date()
  const firstDay = new Date(now.getFullYear(), now.getMonth(), 1)
  analyticsDateFrom.value = firstDay.toISOString().split('T')[0]
  analyticsDateTo.value = now.toISOString().split('T')[0]
})
</script>
