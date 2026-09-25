<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white">รายงานและวิเคราะห์</h2>
      <div class="flex flex-wrap gap-2">
        <button class="min-h-[44px] sm:min-h-0"
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
          @click="openReportModal"
          class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-2 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors"
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
                :disabled="!!exportingKey"
                class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-1 text-xs px-2 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <Icon v-if="exportingKey === `${report.id}:pdf`" icon="heroicons:arrow-path" class="h-3.5 w-3.5 animate-spin" />
                PDF
              </button>
              <button
                @click.stop="downloadReport(report, 'excel')"
                :disabled="!!exportingKey"
                class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-1 text-xs px-2 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <Icon v-if="exportingKey === `${report.id}:excel`" icon="heroicons:arrow-path" class="h-3.5 w-3.5 animate-spin" />
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

    <!-- Saved Reports Section -->
    <div v-if="activeSection === 'saved'" class="space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">รายงานที่บันทึกไว้</h3>
        <button
          @click="loadSavedReports"
          class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-600 dark:text-gray-300 hover:text-gray-900"
        >
          <Icon icon="heroicons:arrow-path" class="h-5 w-5" />
          <span class="hidden sm:inline">โหลดใหม่</span>
        </button>
      </div>

      <div v-if="loadingSaved" class="flex justify-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
      </div>

      <div v-else-if="savedReports.length === 0" class="text-center py-12 text-gray-500">
        <Icon icon="heroicons:document-check" class="h-12 w-12 mx-auto mb-3 text-gray-300" />
        <p>ยังไม่มีรายงานที่บันทึกไว้</p>
        <p class="text-sm mt-1">สร้างรายงานจากแท็บ "รายงาน" แล้วกดสร้าง เพื่อบันทึกสแนปช็อตข้อมูล</p>
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="report in savedReports"
          :key="report.id"
          class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4"
        >
          <div class="flex items-start gap-3">
            <button
              @click="toggleFavorite(report)"
              class="min-h-[44px] min-w-[44px] flex items-center justify-center flex-shrink-0 -m-2 sm:m-0 sm:min-h-0 sm:min-w-0"
              :title="report.is_favorite ? 'เอาออกจากรายการโปรด' : 'เพิ่มในรายการโปรด'"
            >
              <Icon
                :icon="report.is_favorite ? 'heroicons:star-solid' : 'heroicons:star'"
                class="h-5 w-5"
                :class="report.is_favorite ? 'text-amber-400' : 'text-gray-400'"
              />
            </button>
            <div class="flex-1 min-w-0">
              <h4 class="font-semibold text-gray-900 dark:text-white break-words">{{ report.name }}</h4>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                <span v-if="report.definition">{{ report.definition.name }} · </span>
                <span>{{ report.generated_at ? new Date(report.generated_at).toLocaleString('th-TH') : '-' }}</span>
              </p>
            </div>
          </div>

          <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 flex flex-wrap items-center gap-2">
            <button
              @click="viewReport(report)"
              class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-1 text-xs px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded hover:bg-gray-200"
            >
              <Icon icon="heroicons:eye" class="h-4 w-4" /> ดู
            </button>
            <button
              @click="refreshOne(report)"
              :disabled="refreshingId === report.id"
              class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-1 text-xs px-3 py-1.5 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 disabled:opacity-50"
            >
              <Icon icon="heroicons:arrow-path" class="h-4 w-4" :class="{ 'animate-spin': refreshingId === report.id }" /> รีเฟรช
            </button>
            <button
              @click="exportSaved(report, 'pdf')"
              :disabled="!!exportingKey"
              class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-1 text-xs px-3 py-1.5 bg-red-100 text-red-700 rounded hover:bg-red-200 disabled:opacity-50"
            >
              <Icon v-if="exportingKey === `sr:${report.id}:pdf`" icon="heroicons:arrow-path" class="h-3.5 w-3.5 animate-spin" /> PDF
            </button>
            <button
              @click="exportSaved(report, 'excel')"
              :disabled="!!exportingKey"
              class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-1 text-xs px-3 py-1.5 bg-green-100 text-green-700 rounded hover:bg-green-200 disabled:opacity-50"
            >
              <Icon v-if="exportingKey === `sr:${report.id}:excel`" icon="heroicons:arrow-path" class="h-3.5 w-3.5 animate-spin" /> Excel
            </button>
            <button
              @click="exportSaved(report, 'csv')"
              :disabled="!!exportingKey"
              class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-1 text-xs px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded hover:bg-gray-200 disabled:opacity-50"
            >
              <Icon v-if="exportingKey === `sr:${report.id}:csv`" icon="heroicons:arrow-path" class="h-3.5 w-3.5 animate-spin" /> CSV
            </button>
            <button
              @click="deleteReport(report)"
              class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-1 text-xs px-3 py-1.5 bg-red-50 text-red-600 rounded hover:bg-red-100 sm:ml-auto"
            >
              <Icon icon="heroicons:trash" class="h-4 w-4" /> ลบ
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- View Saved Report Modal -->
    <div
      v-if="viewingReport"
      class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-0 sm:p-4"
      @click.self="viewingReport = null"
    >
      <div class="w-full sm:max-w-3xl bg-white dark:bg-gray-800 rounded-t-2xl sm:rounded-2xl shadow-xl max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white min-w-0 break-words">{{ viewingReport.name }}</h3>
          <button @click="viewingReport = null" class="min-h-[44px] min-w-[44px] flex items-center justify-center text-gray-400 hover:text-gray-600 flex-shrink-0">
            <Icon icon="heroicons:x-mark" class="h-6 w-6" />
          </button>
        </div>

        <div class="p-4 overflow-auto">
          <div v-if="loadingView" class="flex justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
          </div>
          <div v-else-if="viewRows.length === 0" class="text-center py-10 text-gray-500">
            <Icon icon="heroicons:inbox" class="h-10 w-10 mx-auto mb-2 text-gray-300" />
            <p>ไม่มีข้อมูลในรายงานนี้</p>
            <p class="text-sm mt-1">ลองกด "รีเฟรช" เพื่อดึงข้อมูลล่าสุด</p>
          </div>
          <div v-else class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                  <th v-for="col in viewColumns" :key="col" class="text-left font-medium text-gray-600 dark:text-gray-300 px-3 py-2 whitespace-nowrap">{{ col }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, i) in viewRows" :key="i" class="border-b border-gray-100 dark:border-gray-700/50">
                  <td v-for="col in viewColumns" :key="col" class="px-3 py-2 text-gray-800 dark:text-gray-200 whitespace-nowrap">{{ cellValue(row, col) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Report Definition Modal -->
    <div
      v-if="showReportModal"
      class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-0 sm:p-4"
      @click.self="showReportModal = false"
    >
      <div class="w-full sm:max-w-lg bg-white dark:bg-gray-800 rounded-t-2xl sm:rounded-2xl shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white">สร้างรายงานใหม่</h3>
          <button @click="showReportModal = false" class="min-h-[44px] min-w-[44px] flex items-center justify-center text-gray-400 hover:text-gray-600">
            <Icon icon="heroicons:x-mark" class="h-6 w-6" />
          </button>
        </div>

        <div class="p-4 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อรายงาน <span class="text-red-500">*</span></label>
            <input
              v-model="reportForm.name"
              type="text"
              placeholder="เช่น สรุปการเข้าเรียนรายเดือน"
              class="w-full min-h-[44px] px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภทข้อมูล <span class="text-red-500">*</span></label>
            <select
              v-model="reportForm.source"
              class="w-full min-h-[44px] px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            >
              <option v-for="s in REPORT_SOURCES" :key="s.key" :value="s.key">{{ s.label }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
            <textarea
              v-model="reportForm.description"
              rows="3"
              placeholder="รายละเอียดของรายงาน (ไม่บังคับ)"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent break-words"
            ></textarea>
          </div>
        </div>

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 p-4 border-t border-gray-200 dark:border-gray-700">
          <button
            @click="showReportModal = false"
            class="min-h-[44px] px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700"
          >
            ยกเลิก
          </button>
          <button
            @click="createReport"
            :disabled="creatingReport"
            class="min-h-[44px] inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-primary-500 text-white hover:bg-primary-600 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <Icon v-if="creatingReport" icon="heroicons:arrow-path" class="h-5 w-5 animate-spin" />
            สร้างรายงาน
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
          class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors"
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
          class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-2 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors"
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
const swal = useSweetAlert()

// State
const activeSection = ref('dashboard')
const sections = [
  { id: 'dashboard', name: 'แดชบอร์ด' },
  { id: 'reports', name: 'รายงาน' },
  { id: 'saved', name: 'รายงานที่บันทึก' },
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

// Data sources that the backend generateReport() can actually populate.
// Each preset carries the fields createDefinition requires so the created
// definition generates real data + exports cleanly.
const REPORT_SOURCES = [
  {
    key: 'school_attendances',
    label: 'รายงานการเข้าเรียน',
    category: 'attendance',
    columns: ['date', 'title', 'present_count', 'absent_count', 'late_count', 'total_count'],
  },
  {
    key: 'tuition_fees',
    label: 'รายงานค่าเทอม/การเงิน',
    category: 'financial',
    columns: ['student_name', 'classroom_name', 'total_amount', 'paid_amount', 'remaining_amount', 'due_date'],
  },
  {
    key: 'at_risk_students',
    label: 'รายงานนักเรียนกลุ่มเสี่ยง',
    category: 'academic',
    columns: ['name', 'risk_level', 'reason'],
  },
]

// Create-definition form
const creatingReport = ref(false)
const reportForm = ref({ name: '', description: '', source: REPORT_SOURCES[0].key })
const resetReportForm = () => {
  reportForm.value = { name: '', description: '', source: REPORT_SOURCES[0].key }
}
const openReportModal = () => {
  resetReportForm()
  showReportModal.value = true
}

// Per-card export progress: `${reportId}:${format}` (definitions) / `sr:${id}:${format}` (saved)
const exportingKey = ref<string | null>(null)

// Saved reports
const savedReports = ref<any[]>([])
const loadingSaved = ref(false)
const refreshingId = ref<number | null>(null)
const viewingReport = ref<any>(null) // saved report currently open in the view modal
const loadingView = ref(false)
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
// Create a new report definition from the chosen preset source
const createReport = async () => {
  const name = reportForm.value.name.trim()
  if (!name) {
    swal.error('กรุณากรอกชื่อรายงาน')
    return
  }
  const preset = REPORT_SOURCES.find(s => s.key === reportForm.value.source) || REPORT_SOURCES[0]

  creatingReport.value = true
  try {
    const response: any = await schoolApi.createReportDefinition(props.academyId, {
      name,
      description: reportForm.value.description.trim() || null,
      category: preset.category,
      report_type: 'table',
      data_source: preset.key,
      columns: preset.columns,
      filters: [],
      default_params: {},
    })
    if (response.success) {
      showReportModal.value = false
      resetReportForm()
      await loadReports()
      swal.toast('สร้างรายงานเรียบร้อยแล้ว')
    }
  } catch (error: any) {
    console.error('Failed to create report definition:', error)
    swal.error(error?.data?.message || 'ไม่สามารถสร้างรายงานได้')
  } finally {
    creatingReport.value = false
  }
}

// Generate a saved report from a definition; returns the saved report id (or null)
const generateSavedReport = async (definition: any): Promise<number | null> => {
  const stamp = new Date().toLocaleString('th-TH', { dateStyle: 'medium', timeStyle: 'short' })
  const response: any = await schoolApi.generateReport(props.academyId, definition.id, {
    name: `${definition.name} — ${stamp}`,
    parameters: {},
  })
  return response?.success ? (response.data?.id ?? response.data?.report?.id ?? null) : null
}

const generateReport = async (report: any) => {
  try {
    const id = await generateSavedReport(report)
    if (id) swal.toast('สร้างรายงานเรียบร้อยแล้ว')
    else swal.error('ไม่สามารถสร้างรายงานได้')
  } catch (error) {
    console.error('Failed to generate report:', error)
    swal.error('ไม่สามารถสร้างรายงานได้')
  }
}

// Generate → export → download in one click from a definition card
const downloadReport = async (report: any, format: string) => {
  const apiFormat = format === 'excel' ? 'xlsx' : format
  const key = `${report.id}:${format}`
  if (exportingKey.value) return
  exportingKey.value = key
  try {
    const reportId = await generateSavedReport(report)
    if (!reportId) {
      swal.error('ไม่สามารถสร้างรายงานได้')
      return
    }
    const res: any = await schoolApi.exportReport(props.academyId, reportId, apiFormat)
    const url = res?.data?.download_url
    if (res?.success && url) {
      // public storage url → open to download
      window.open(url, '_blank')
      swal.toast('ส่งออกไฟล์เรียบร้อยแล้ว')
    } else {
      swal.error('ส่งออกไฟล์ไม่สำเร็จ')
    }
  } catch (error: any) {
    console.error('Failed to export report:', error)
    swal.error(error?.data?.message || 'ส่งออกไฟล์ไม่สำเร็จ')
  } finally {
    exportingKey.value = null
  }
}

const generateQuickReport = (_type: string) => {
  // ยังไม่รองรับใน export slice นี้
  swal.toast('ฟีเจอร์รายงานด่วนยังไม่พร้อมใช้งาน', 'info')
}

// ── Saved Reports ────────────────────────────────────────────────
const loadSavedReports = async () => {
  loadingSaved.value = true
  try {
    const response: any = await schoolApi.getSavedReports(props.academyId)
    // listSavedReports → { data: paginator{ data: [...] } }
    savedReports.value = response?.data?.data ?? (Array.isArray(response?.data) ? response.data : [])
  } catch (error) {
    console.error('Failed to load saved reports:', error)
    savedReports.value = []
  } finally {
    loadingSaved.value = false
  }
}

const viewReport = async (report: any) => {
  loadingView.value = true
  viewingReport.value = report // show immediately with what we have
  try {
    const res: any = await schoolApi.getSavedReport(props.academyId, report.id)
    if (res?.success && res.data) viewingReport.value = res.data
  } catch (error) {
    console.error('Failed to load report:', error)
  } finally {
    loadingView.value = false
  }
}

// Table view of a saved report's cached_data
const viewColumns = computed<string[]>(() => {
  const rows = viewingReport.value?.cached_data
  const first = Array.isArray(rows) ? rows[0] : null
  return first ? Object.keys(first) : []
})
const viewRows = computed<any[]>(() => {
  const rows = viewingReport.value?.cached_data
  return Array.isArray(rows) ? rows : []
})
const cellValue = (row: any, col: string) => {
  const v = (row && typeof row === 'object') ? row[col] : row
  return v === null || v === undefined ? '-' : v
}

const deleteReport = async (report: any) => {
  const confirmed = await swal.confirmDelete(`รายงาน "${report.name}"`)
  if (!confirmed) return
  try {
    const res: any = await schoolApi.deleteSavedReport(props.academyId, report.id)
    if (res?.success) {
      savedReports.value = savedReports.value.filter(r => r.id !== report.id)
      swal.toast('ลบรายงานแล้ว')
    }
  } catch (error: any) {
    console.error('Failed to delete report:', error)
    swal.error(error?.data?.message || 'ลบรายงานไม่สำเร็จ')
  }
}

const toggleFavorite = async (report: any) => {
  try {
    const res: any = await schoolApi.toggleReportFavorite(props.academyId, report.id)
    if (res?.success) report.is_favorite = res.data?.is_favorite ?? !report.is_favorite
  } catch (error) {
    console.error('Failed to toggle favorite:', error)
    swal.error('ไม่สามารถอัปเดตรายการโปรดได้')
  }
}

const refreshOne = async (report: any) => {
  if (refreshingId.value) return
  refreshingId.value = report.id
  try {
    const res: any = await schoolApi.refreshSavedReport(props.academyId, report.id)
    if (res?.success && res.data) {
      Object.assign(report, res.data) // updates generated_at + cached_data in place
      if (viewingReport.value?.id === report.id) viewingReport.value = res.data
      swal.toast('รีเฟรชข้อมูลแล้ว')
    }
  } catch (error: any) {
    console.error('Failed to refresh report:', error)
    swal.error(error?.data?.message || 'รีเฟรชไม่สำเร็จ')
  } finally {
    refreshingId.value = null
  }
}

// Export an already-saved report (no generate step needed)
const exportSaved = async (report: any, format: string) => {
  const apiFormat = format === 'excel' ? 'xlsx' : format
  const key = `sr:${report.id}:${format}`
  if (exportingKey.value) return
  exportingKey.value = key
  try {
    const res: any = await schoolApi.exportReport(props.academyId, report.id, apiFormat)
    const url = res?.data?.download_url
    if (res?.success && url) {
      window.open(url, '_blank')
      swal.toast('ส่งออกไฟล์เรียบร้อยแล้ว')
    } else {
      swal.error('ส่งออกไฟล์ไม่สำเร็จ')
    }
  } catch (error: any) {
    console.error('Failed to export saved report:', error)
    swal.error(error?.data?.message || 'ส่งออกไฟล์ไม่สำเร็จ')
  } finally {
    exportingKey.value = null
  }
}

// Watch
watch(activeSection, (section) => {
  if (section === 'reports' && reports.value.length === 0) loadReports()
  if (section === 'saved' && savedReports.value.length === 0) loadSavedReports()
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
