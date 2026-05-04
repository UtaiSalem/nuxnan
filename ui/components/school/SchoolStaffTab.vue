<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white">ระบบบุคลากร</h2>
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

    <!-- Staff Profiles Section -->
    <div v-if="activeSection === 'profiles'" class="space-y-4">
      <div class="flex justify-between items-center">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">รายชื่อบุคลากร</h3>
        <button
          @click="showStaffModal = true"
          class="inline-flex items-center gap-2 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors"
        >
          <Icon icon="heroicons:plus" class="h-5 w-5" />
          <span class="hidden sm:inline">เพิ่มบุคลากร</span>
        </button>
      </div>

      <div v-if="loadingStaff" class="flex justify-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
      </div>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <div
          v-for="staff in staffProfiles"
          :key="staff.id"
          class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4"
        >
          <div class="flex items-center gap-3 mb-3">
            <div class="h-12 w-12 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center overflow-hidden">
              <img v-if="staff.user?.avatar" :src="staff.user.avatar" class="h-full w-full object-cover" />
              <Icon v-else icon="heroicons:user" class="h-6 w-6 text-gray-400" />
            </div>
            <div class="flex-1 min-w-0">
              <h4 class="font-semibold text-gray-900 dark:text-white truncate">{{ staff.user?.name || 'ไม่ระบุ' }}</h4>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ staff.position }}</p>
            </div>
          </div>
          <div class="space-y-1 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-500">แผนก:</span>
              <span class="text-gray-900 dark:text-white">{{ staff.department }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">วันที่เริ่มงาน:</span>
              <span class="text-gray-900 dark:text-white">{{ formatDate(staff.hire_date) }}</span>
            </div>
          </div>
          <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
            <span :class="getEmploymentStatusClass(staff.employment_status)" class="px-2 py-1 text-xs rounded-full">
              {{ getEmploymentStatusLabel(staff.employment_status) }}
            </span>
            <button @click="editStaff(staff)" class="text-primary-600 hover:underline text-sm">แก้ไข</button>
          </div>
        </div>

        <div v-if="staffProfiles.length === 0" class="col-span-full text-center py-12 text-gray-500">
          <Icon icon="heroicons:users" class="h-12 w-12 mx-auto mb-3 text-gray-300" />
          <p>ยังไม่มีบุคลากร</p>
        </div>
      </div>
    </div>

    <!-- Attendance Section -->
    <div v-if="activeSection === 'attendance'" class="space-y-4">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">บันทึกเวลาทำงาน</h3>
        <div class="flex gap-2">
          <input
            v-model="attendanceDate"
            type="date"
            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          />
          <button
            @click="recordCheckIn"
            class="inline-flex items-center gap-2 px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors"
          >
            <Icon icon="heroicons:clock" class="h-5 w-5" />
            <span class="hidden sm:inline">ลงเวลาเข้างาน</span>
          </button>
        </div>
      </div>

      <div v-if="loadingAttendance" class="flex justify-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
              <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">บุคลากร</th>
              <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">วันที่</th>
              <th class="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300">เข้างาน</th>
              <th class="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300">ออกงาน</th>
              <th class="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300">สถานะ</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="record in attendanceRecords" :key="record.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <div class="h-8 w-8 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                    <Icon icon="heroicons:user" class="h-4 w-4 text-gray-400" />
                  </div>
                  <span class="font-medium text-gray-900 dark:text-white">{{ record.staff?.user?.name || 'ไม่ระบุ' }}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ formatDate(record.date) }}</td>
              <td class="px-4 py-3 text-center text-gray-900 dark:text-white">{{ record.check_in_time || '-' }}</td>
              <td class="px-4 py-3 text-center text-gray-900 dark:text-white">{{ record.check_out_time || '-' }}</td>
              <td class="px-4 py-3 text-center">
                <span :class="getAttendanceStatusClass(record.status)" class="px-2 py-1 text-xs rounded-full">
                  {{ getAttendanceStatusLabel(record.status) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="attendanceRecords.length === 0" class="text-center py-12 text-gray-500">
          <Icon icon="heroicons:clock" class="h-12 w-12 mx-auto mb-3 text-gray-300" />
          <p>ยังไม่มีบันทึกเวลาทำงาน</p>
        </div>
      </div>
    </div>

    <!-- Leave Requests Section -->
    <div v-if="activeSection === 'leave'" class="space-y-4">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">คำขอลา</h3>
        <div class="flex gap-2">
          <select v-model="leaveFilter" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
            <option value="">ทั้งหมด</option>
            <option value="pending">รออนุมัติ</option>
            <option value="approved">อนุมัติแล้ว</option>
            <option value="rejected">ปฏิเสธ</option>
          </select>
          <button
            @click="showLeaveModal = true"
            class="inline-flex items-center gap-2 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors"
          >
            <Icon icon="heroicons:plus" class="h-5 w-5" />
            <span class="hidden sm:inline">ยื่นคำขอลา</span>
          </button>
        </div>
      </div>

      <div v-if="loadingLeave" class="flex justify-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="leave in filteredLeaveRequests"
          :key="leave.id"
          class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4"
        >
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
              <div class="h-10 w-10 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                <Icon icon="heroicons:user" class="h-5 w-5 text-gray-400" />
              </div>
              <div>
                <h4 class="font-medium text-gray-900 dark:text-white">{{ leave.staff?.user?.name || 'ไม่ระบุ' }}</h4>
                <p class="text-sm text-gray-500">{{ leave.leave_type?.name }} ({{ leave.days }} วัน)</p>
              </div>
            </div>
            <span :class="getLeaveStatusClass(leave.status)" class="px-2 py-1 text-xs rounded-full">
              {{ getLeaveStatusLabel(leave.status) }}
            </span>
          </div>
          <div class="mt-3 grid grid-cols-2 gap-4 text-sm">
            <div>
              <span class="text-gray-500">วันที่เริ่ม:</span>
              <span class="ml-2 text-gray-900 dark:text-white">{{ formatDate(leave.start_date) }}</span>
            </div>
            <div>
              <span class="text-gray-500">วันที่สิ้นสุด:</span>
              <span class="ml-2 text-gray-900 dark:text-white">{{ formatDate(leave.end_date) }}</span>
            </div>
          </div>
          <p v-if="leave.reason" class="mt-2 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">{{ leave.reason }}</p>
          
          <div v-if="leave.status === 'pending'" class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 flex gap-2">
            <button @click="approveLeaveItem(leave)" class="flex-1 px-3 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 text-sm">
              อนุมัติ
            </button>
            <button @click="rejectLeaveItem(leave)" class="flex-1 px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 text-sm">
              ปฏิเสธ
            </button>
          </div>
        </div>

        <div v-if="filteredLeaveRequests.length === 0" class="text-center py-12 text-gray-500">
          <Icon icon="heroicons:calendar-days" class="h-12 w-12 mx-auto mb-3 text-gray-300" />
          <p>ยังไม่มีคำขอลา</p>
        </div>
      </div>
    </div>

    <!-- Payroll Section -->
    <div v-if="activeSection === 'payroll'" class="space-y-4">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">เงินเดือน</h3>
        <div class="flex gap-2">
          <select v-model="payrollMonth" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
            <option v-for="month in months" :key="month.value" :value="month.value">{{ month.label }}</option>
          </select>
          <button
            @click="processPayroll"
            class="inline-flex items-center gap-2 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors"
          >
            <Icon icon="heroicons:banknotes" class="h-5 w-5" />
            <span class="hidden sm:inline">ประมวลผล</span>
          </button>
        </div>
      </div>

      <div v-if="loadingPayroll" class="flex justify-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
              <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">บุคลากร</th>
              <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-300">เงินเดือน</th>
              <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-300 hidden sm:table-cell">เบี้ยเลี้ยง</th>
              <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-300 hidden sm:table-cell">หักเงิน</th>
              <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-300">สุทธิ</th>
              <th class="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300">สถานะ</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="payroll in payrollRecords" :key="payroll.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <div class="h-8 w-8 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                    <Icon icon="heroicons:user" class="h-4 w-4 text-gray-400" />
                  </div>
                  <span class="font-medium text-gray-900 dark:text-white">{{ payroll.staff?.user?.name || 'ไม่ระบุ' }}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-right text-gray-900 dark:text-white">฿{{ formatMoney(payroll.base_salary) }}</td>
              <td class="px-4 py-3 text-right text-green-600 hidden sm:table-cell">+฿{{ formatMoney(payroll.allowances) }}</td>
              <td class="px-4 py-3 text-right text-red-600 hidden sm:table-cell">-฿{{ formatMoney(payroll.deductions) }}</td>
              <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white">฿{{ formatMoney(payroll.net_salary) }}</td>
              <td class="px-4 py-3 text-center">
                <span :class="getPayrollStatusClass(payroll.status)" class="px-2 py-1 text-xs rounded-full">
                  {{ getPayrollStatusLabel(payroll.status) }}
                </span>
              </td>
            </tr>
          </tbody>
          <tfoot class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
              <td class="px-4 py-3 font-bold text-gray-900 dark:text-white">รวม</td>
              <td class="px-4 py-3 text-right font-bold text-gray-900 dark:text-white">฿{{ formatMoney(totalBaseSalary) }}</td>
              <td class="px-4 py-3 text-right font-bold text-green-600 hidden sm:table-cell">+฿{{ formatMoney(totalAllowances) }}</td>
              <td class="px-4 py-3 text-right font-bold text-red-600 hidden sm:table-cell">-฿{{ formatMoney(totalDeductions) }}</td>
              <td class="px-4 py-3 text-right font-bold text-primary-600">฿{{ formatMoney(totalNetSalary) }}</td>
              <td></td>
            </tr>
          </tfoot>
        </table>

        <div v-if="payrollRecords.length === 0" class="text-center py-12 text-gray-500">
          <Icon icon="heroicons:banknotes" class="h-12 w-12 mx-auto mb-3 text-gray-300" />
          <p>ยังไม่มีข้อมูลเงินเดือน</p>
        </div>
      </div>
    </div>

    <!-- Staff Modal -->
    <SchoolModal v-model="showStaffModal" title="เพิ่มบุคลากร" size="lg">
      <form @submit.prevent="saveStaff" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ผู้ใช้</label>
          <select v-model="staffForm.user_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
            <option value="">เลือกผู้ใช้</option>
            <!-- Options would be populated from API -->
          </select>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ตำแหน่ง</label>
            <input v-model="staffForm.position" type="text" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">แผนก</label>
            <input v-model="staffForm.department" type="text" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันที่เริ่มงาน</label>
            <input v-model="staffForm.hire_date" type="date" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เงินเดือน</label>
            <input v-model.number="staffForm.salary" type="number" min="0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
          </div>
        </div>
        <div class="flex justify-end gap-3 pt-4">
          <button type="button" @click="showStaffModal = false" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
            ยกเลิก
          </button>
          <button type="submit" :disabled="savingStaff" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 disabled:opacity-50">
            {{ savingStaff ? 'กำลังบันทึก...' : 'บันทึก' }}
          </button>
        </div>
      </form>
    </SchoolModal>

    <!-- Leave Request Modal -->
    <SchoolModal v-model="showLeaveModal" title="ยื่นคำขอลา" size="lg">
      <form @submit.prevent="submitLeave" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภทการลา</label>
          <select v-model="leaveForm.leave_type_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
            <option v-for="type in leaveTypes" :key="type.id" :value="type.id">{{ type.name }} (คงเหลือ {{ type.max_days }} วัน)</option>
          </select>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันที่เริ่ม</label>
            <input v-model="leaveForm.start_date" type="date" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันที่สิ้นสุด</label>
            <input v-model="leaveForm.end_date" type="date" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เหตุผล</label>
          <textarea v-model="leaveForm.reason" rows="3" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"></textarea>
        </div>
        <div class="flex justify-end gap-3 pt-4">
          <button type="button" @click="showLeaveModal = false" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
            ยกเลิก
          </button>
          <button type="submit" :disabled="submittingLeave" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 disabled:opacity-50">
            {{ submittingLeave ? 'กำลังส่ง...' : 'ยื่นคำขอ' }}
          </button>
        </div>
      </form>
    </SchoolModal>
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
const activeSection = ref('profiles')
const sections = [
  { id: 'profiles', name: 'บุคลากร' },
  { id: 'attendance', name: 'เวลาทำงาน' },
  { id: 'leave', name: 'การลา' },
  { id: 'payroll', name: 'เงินเดือน' },
]

// Staff Profiles
const staffProfiles = ref<any[]>([])
const loadingStaff = ref(false)
const showStaffModal = ref(false)
const savingStaff = ref(false)
const staffForm = ref({
  user_id: null as number | null,
  position: '',
  department: '',
  hire_date: '',
  salary: 0,
})

// Attendance
const attendanceRecords = ref<any[]>([])
const loadingAttendance = ref(false)
const attendanceDate = ref(new Date().toISOString().split('T')[0])

// Leave
const leaveRequests = ref<any[]>([])
const leaveTypes = ref<any[]>([])
const loadingLeave = ref(false)
const showLeaveModal = ref(false)
const submittingLeave = ref(false)
const leaveFilter = ref('')
const leaveForm = ref({
  leave_type_id: null as number | null,
  start_date: '',
  end_date: '',
  reason: '',
})

// Payroll
const payrollRecords = ref<any[]>([])
const loadingPayroll = ref(false)
const payrollMonth = ref(new Date().getMonth() + 1)
const months = [
  { value: 1, label: 'มกราคม' },
  { value: 2, label: 'กุมภาพันธ์' },
  { value: 3, label: 'มีนาคม' },
  { value: 4, label: 'เมษายน' },
  { value: 5, label: 'พฤษภาคม' },
  { value: 6, label: 'มิถุนายน' },
  { value: 7, label: 'กรกฎาคม' },
  { value: 8, label: 'สิงหาคม' },
  { value: 9, label: 'กันยายน' },
  { value: 10, label: 'ตุลาคม' },
  { value: 11, label: 'พฤศจิกายน' },
  { value: 12, label: 'ธันวาคม' },
]

// Computed
const filteredLeaveRequests = computed(() => {
  if (!leaveFilter.value) return leaveRequests.value
  return leaveRequests.value.filter(l => l.status === leaveFilter.value)
})

const totalBaseSalary = computed(() => payrollRecords.value.reduce((sum, p) => sum + (p.base_salary || 0), 0))
const totalAllowances = computed(() => payrollRecords.value.reduce((sum, p) => sum + (p.allowances || 0), 0))
const totalDeductions = computed(() => payrollRecords.value.reduce((sum, p) => sum + (p.deductions || 0), 0))
const totalNetSalary = computed(() => payrollRecords.value.reduce((sum, p) => sum + (p.net_salary || 0), 0))

// Helpers
const formatMoney = (amount: number) => new Intl.NumberFormat('th-TH').format(amount || 0)
const formatDate = (date: string) => new Date(date).toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: 'numeric' })

const getEmploymentStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    active: 'bg-green-100 text-green-800',
    on_leave: 'bg-yellow-100 text-yellow-800',
    terminated: 'bg-red-100 text-red-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getEmploymentStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    active: 'ทำงาน',
    on_leave: 'ลา',
    terminated: 'พ้นสภาพ',
  }
  return labels[status] || status
}

const getAttendanceStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    present: 'bg-green-100 text-green-800',
    absent: 'bg-red-100 text-red-800',
    late: 'bg-yellow-100 text-yellow-800',
    leave: 'bg-blue-100 text-blue-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getAttendanceStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    present: 'มา',
    absent: 'ขาด',
    late: 'สาย',
    leave: 'ลา',
  }
  return labels[status] || status
}

const getLeaveStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getLeaveStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    pending: 'รออนุมัติ',
    approved: 'อนุมัติ',
    rejected: 'ปฏิเสธ',
  }
  return labels[status] || status
}

const getPayrollStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800',
    pending: 'bg-yellow-100 text-yellow-800',
    paid: 'bg-green-100 text-green-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getPayrollStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    draft: 'ร่าง',
    pending: 'รอจ่าย',
    paid: 'จ่ายแล้ว',
  }
  return labels[status] || status
}

// Helper to safely extract array data
const extractArray = (response: any): any[] => {
  if (!response) return []
  if (Array.isArray(response)) return response
  if (response.data && Array.isArray(response.data)) return response.data
  return []
}

// Load data
const loadStaffProfiles = async () => {
  loadingStaff.value = true
  try {
    const response = await schoolApi.getStaffProfiles(props.academyId)
    staffProfiles.value = extractArray(response)
  } catch (error) {
    console.error('Failed to load staff profiles:', error)
    staffProfiles.value = []
  } finally {
    loadingStaff.value = false
  }
}

const loadAttendance = async () => {
  loadingAttendance.value = true
  try {
    const response = await schoolApi.getStaffAttendance(props.academyId, { date: attendanceDate.value })
    attendanceRecords.value = extractArray(response)
  } catch (error) {
    console.error('Failed to load attendance:', error)
    attendanceRecords.value = []
  } finally {
    loadingAttendance.value = false
  }
}

const loadLeaveRequests = async () => {
  loadingLeave.value = true
  try {
    const [requestsRes, typesRes] = await Promise.all([
      schoolApi.getLeaveRequests(props.academyId),
      schoolApi.getLeaveTypes(props.academyId),
    ])
    leaveRequests.value = extractArray(requestsRes)
    leaveTypes.value = extractArray(typesRes)
    if (leaveTypes.value.length > 0) {
      leaveForm.value.leave_type_id = leaveTypes.value[0].id
    }
  } catch (error) {
    console.error('Failed to load leave requests:', error)
    leaveRequests.value = []
    leaveTypes.value = []
  } finally {
    loadingLeave.value = false
  }
}

const loadPayroll = async () => {
  loadingPayroll.value = true
  try {
    const year = new Date().getFullYear()
    const response = await schoolApi.getPayrolls(props.academyId, { month: payrollMonth.value, year })
    payrollRecords.value = extractArray(response)
  } catch (error) {
    console.error('Failed to load payroll:', error)
    payrollRecords.value = []
  } finally {
    loadingPayroll.value = false
  }
}

// Actions
const editStaff = (staff: any) => {
  staffForm.value = { ...staff }
  showStaffModal.value = true
}

const saveStaff = async () => {
  savingStaff.value = true
  try {
    await schoolApi.createStaffProfile(props.academyId, staffForm.value)
    showStaffModal.value = false
    staffForm.value = { user_id: null, position: '', department: '', hire_date: '', salary: 0 }
    await loadStaffProfiles()
  } catch (error) {
    console.error('Failed to save staff:', error)
  } finally {
    savingStaff.value = false
  }
}

const recordCheckIn = async () => {
  // TODO: Implement check-in
}

const submitLeave = async () => {
  submittingLeave.value = true
  try {
    await schoolApi.createLeaveRequest(props.academyId, leaveForm.value)
    showLeaveModal.value = false
    leaveForm.value = { leave_type_id: leaveTypes.value[0]?.id || null, start_date: '', end_date: '', reason: '' }
    await loadLeaveRequests()
  } catch (error) {
    console.error('Failed to submit leave:', error)
  } finally {
    submittingLeave.value = false
  }
}

const approveLeaveItem = async (leave: any) => {
  if (confirm(`อนุมัติคำขอลาของ ${leave.staff?.user?.name} หรือไม่?`)) {
    try {
      await schoolApi.approveLeaveRequest(props.academyId, leave.id)
      await loadLeaveRequests()
    } catch (error) {
      console.error('Failed to approve leave:', error)
    }
  }
}

const rejectLeaveItem = async (leave: any) => {
  const reason = prompt('เหตุผลในการปฏิเสธ:')
  if (reason) {
    try {
      await schoolApi.rejectLeaveRequest(props.academyId, leave.id, reason)
      await loadLeaveRequests()
    } catch (error) {
      console.error('Failed to reject leave:', error)
    }
  }
}

const processPayroll = async () => {
  if (confirm('ประมวลผลเงินเดือนประจำเดือนนี้?')) {
    try {
      const year = new Date().getFullYear()
      await schoolApi.processPayroll(props.academyId, payrollMonth.value, year)
      await loadPayroll()
    } catch (error) {
      console.error('Failed to process payroll:', error)
    }
  }
}

// Watch
watch(activeSection, (section) => {
  if (section === 'profiles' && staffProfiles.value.length === 0) loadStaffProfiles()
  if (section === 'attendance' && attendanceRecords.value.length === 0) loadAttendance()
  if (section === 'leave' && leaveRequests.value.length === 0) loadLeaveRequests()
  if (section === 'payroll' && payrollRecords.value.length === 0) loadPayroll()
}, { immediate: true })

watch(attendanceDate, () => loadAttendance())
watch(payrollMonth, () => loadPayroll())

onMounted(() => {
  loadStaffProfiles()
})
</script>
