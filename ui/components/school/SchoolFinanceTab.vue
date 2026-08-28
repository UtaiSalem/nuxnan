<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white">ระบบการเงิน</h2>
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

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">รายรับ</p>
            <p class="text-xl font-bold text-green-600">฿{{ formatMoney(summary.totalIncome) }}</p>
          </div>
          <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-full">
            <Icon icon="heroicons:arrow-trending-up" class="h-6 w-6 text-green-600" />
          </div>
        </div>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">รายจ่าย</p>
            <p class="text-xl font-bold text-red-600">฿{{ formatMoney(summary.totalExpense) }}</p>
          </div>
          <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-full">
            <Icon icon="heroicons:arrow-trending-down" class="h-6 w-6 text-red-600" />
          </div>
        </div>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">งบประมาณ</p>
            <p class="text-xl font-bold text-blue-600">฿{{ formatMoney(summary.totalBudget) }}</p>
          </div>
          <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-full">
            <Icon icon="heroicons:banknotes" class="h-6 w-6 text-blue-600" />
          </div>
        </div>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">คงเหลือ</p>
            <p class="text-xl font-bold text-gray-900 dark:text-white">฿{{ formatMoney(summary.remaining) }}</p>
          </div>
          <div class="p-3 bg-gray-100 dark:bg-gray-700 rounded-full">
            <Icon icon="heroicons:wallet" class="h-6 w-6 text-gray-600 dark:text-gray-300" />
          </div>
        </div>
      </div>
    </div>

    <!-- Fee Structures Section -->
    <div v-if="activeSection === 'fees'" class="space-y-4">
      <div class="flex justify-between items-center">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">โครงสร้างค่าธรรมเนียม</h3>
        <div class="flex gap-2">
          <button
            @click="openBulkModal()"
            class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            <Icon icon="heroicons:document-duplicate" class="h-5 w-5" />
            <span class="hidden sm:inline">ออกใบแจ้งหนี้รายกลุ่ม</span>
          </button>
          <button
            @click="showFeeModal = true"
            class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-2 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors"
          >
            <Icon icon="heroicons:plus" class="h-5 w-5" />
            <span class="hidden sm:inline">เพิ่มโครงสร้าง</span>
          </button>
        </div>
      </div>

      <div v-if="loadingFees" class="flex justify-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div
          v-for="fee in feeStructures"
          :key="fee.id"
          class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4"
        >
          <div class="flex items-start justify-between mb-3">
            <div>
              <h4 class="font-semibold text-gray-900 dark:text-white">{{ fee.name }}</h4>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ fee.description }}</p>
              <div class="mt-1 flex flex-wrap gap-1">
                <span v-for="level in fee.grade_levels" :key="level" class="px-1.5 py-0.5 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-[10px] rounded-md font-bold">
                  {{ level }}
                </span>
              </div>
            </div>
            <span :class="fee.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'" class="px-2 py-1 text-xs rounded-full">
              {{ fee.is_active ? 'ใช้งาน' : 'ปิด' }}
            </span>
          </div>
          <div class="flex items-center justify-between border-t border-gray-200 dark:border-gray-700 pt-3">
            <div class="text-2xl font-bold text-primary-600">฿{{ formatMoney(fee.total_amount) }}</div>
            <div class="flex gap-3">
              <button @click="openBulkModal(fee)" class="text-sm text-blue-600 hover:underline font-medium">
                ออกใบแจ้งหนี้
              </button>
              <button @click="viewFeeDetails(fee)" class="text-sm text-primary-600 hover:underline font-medium">
                รายละเอียด
              </button>
            </div>
          </div>
        </div>

        <div v-if="feeStructures.length === 0" class="col-span-full text-center py-12 text-gray-500">
          <Icon icon="heroicons:currency-dollar" class="h-12 w-12 mx-auto mb-3 text-gray-300" />
          <p>ยังไม่มีโครงสร้างค่าธรรมเนียม</p>
        </div>
      </div>
    </div>

    <!-- Bulk Invoice Modal -->
    <SchoolModal v-model="showBulkModal" title="ออกใบแจ้งหนี้ค่าเทอมรายกลุ่ม" size="lg">
      <form @submit.prevent="generateBulkInvoices" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">โครงสร้างค่าธรรมเนียม</label>
            <select v-model="bulkForm.fee_structure_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
              <option v-for="fee in feeStructures" :key="fee.id" :value="fee.id">{{ fee.name }} (฿{{ formatMoney(fee.total_amount) }})</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">ปีการศึกษา</label>
            <select v-model="bulkForm.academic_year_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
              <option v-for="year in academicYears" :key="year.id" :value="year.id">{{ year.name }}</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">ภาคเรียน</label>
            <select v-model="bulkForm.semester_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
              <option v-for="sem in semesters" :key="sem.id" :value="sem.id">{{ sem.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">วันครบกำหนดชำระ</label>
            <input v-model="bulkForm.due_date" type="date" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
          </div>
        </div>

        <div>
          <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">ชั้นเรียน / ห้องเรียน</label>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-48 overflow-y-auto p-2 border border-gray-200 dark:border-gray-700 rounded-lg">
            <div v-for="classroom in classrooms" :key="classroom.id" class="flex items-center gap-2">
              <input 
                type="radio" 
                :id="'room-' + classroom.id" 
                v-model="bulkForm.classroom_id" 
                :value="classroom.id"
                class="rounded text-primary-600 focus:ring-primary-500"
              />
              <label :for="'room-' + classroom.id" class="text-sm text-gray-700 dark:text-gray-300 cursor-pointer">{{ classroom.name }}</label>
            </div>
          </div>
        </div>

        <div class="bg-amber-50 dark:bg-amber-900/20 p-4 rounded-xl flex items-start gap-3">
          <Icon icon="heroicons:information-circle" class="h-6 w-6 text-amber-600 flex-shrink-0" />
          <p class="text-xs text-amber-700 dark:text-amber-400 font-medium">
            ระบบจะสร้างใบแจ้งหนี้ให้กับนักเรียนทุกคนในห้องเรียนที่เลือก หากนักเรียนคนใดมีใบแจ้งหนี้สำหรับปีการศึกษาและภาคเรียนนี้อยู่แล้ว ระบบจะข้ามไปโดยอัตโนมัติเพื่อป้องกันข้อมูลซ้ำซ้อน
          </p>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
          <button type="button" @click="showBulkModal = false" class="min-h-[44px] sm:min-h-0 px-4 sm:px-6 py-2.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl font-bold">
            ยกเลิก
          </button>
          <button type="submit" :disabled="generatingInvoices" class="min-h-[44px] sm:min-h-0 px-8 py-2.5 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 disabled:opacity-50 flex items-center gap-2">
            <Icon v-if="generatingInvoices" icon="svg-spinners:180-ring-with-bg" class="h-5 w-5" />
            {{ generatingInvoices ? 'กำลังดำเนินการ...' : 'ยืนยันการสร้างใบแจ้งหนี้' }}
          </button>
        </div>
      </form>
    </SchoolModal>

    <!-- Expenses Section -->
    <div v-if="activeSection === 'expenses'" class="space-y-4">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">รายจ่าย</h3>
        <div class="flex gap-2">
          <select v-model="expenseFilter" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
            <option value="">ทั้งหมด</option>
            <option value="pending">รออนุมัติ</option>
            <option value="approved">อนุมัติแล้ว</option>
            <option value="rejected">ปฏิเสธ</option>
          </select>
          <button
            @click="showExpenseModal = true"
            class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-2 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors"
          >
            <Icon icon="heroicons:plus" class="h-5 w-5" />
            <span class="hidden sm:inline">เพิ่มรายจ่าย</span>
          </button>
        </div>
      </div>

      <div v-if="loadingExpenses" class="flex justify-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
              <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">วันที่</th>
              <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">รายการ</th>
              <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300 hidden sm:table-cell">ผู้จำหน่าย</th>
              <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-300">จำนวนเงิน</th>
              <th class="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300">สถานะ</th>
              <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-300">จัดการ</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="expense in filteredExpenses" :key="expense.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
              <td class="px-4 py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ formatDate(expense.expense_date) }}</td>
              <td class="px-4 py-3">
                <div class="font-medium text-gray-900 dark:text-white">{{ expense.title }}</div>
                <div class="text-xs text-gray-500 line-clamp-1">{{ expense.description }}</div>
              </td>
              <td class="px-4 py-3 text-gray-600 dark:text-gray-400 hidden sm:table-cell">{{ expense.vendor || '-' }}</td>
              <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">฿{{ formatMoney(expense.amount) }}</td>
              <td class="px-4 py-3 text-center">
                <span :class="getStatusClass(expense.status)" class="px-2 py-1 text-xs rounded-full">
                  {{ getStatusLabel(expense.status) }}
                </span>
              </td>
              <td class="px-4 py-3 text-right">
                <div v-if="expense.status === 'pending'" class="flex justify-end gap-1">
                  <button @click="approveExpenseItem(expense)" class="p-1 text-green-600 hover:bg-green-50 rounded" title="อนุมัติ">
                    <Icon icon="heroicons:check" class="h-5 w-5" />
                  </button>
                  <button @click="rejectExpenseItem(expense)" class="p-1 text-red-600 hover:bg-red-50 rounded" title="ปฏิเสธ">
                    <Icon icon="heroicons:x-mark" class="h-5 w-5" />
                  </button>
                </div>
                <span v-else class="text-gray-400">-</span>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="filteredExpenses.length === 0" class="text-center py-12 text-gray-500">
          <Icon icon="heroicons:receipt-percent" class="h-12 w-12 mx-auto mb-3 text-gray-300" />
          <p>ยังไม่มีรายจ่าย</p>
        </div>
      </div>
    </div>

    <!-- Budgets Section -->
    <div v-if="activeSection === 'budgets'" class="space-y-4">
      <div class="flex justify-between items-center">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">งบประมาณ</h3>
        <button
          @click="showBudgetModal = true"
          class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-2 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors"
        >
          <Icon icon="heroicons:plus" class="h-5 w-5" />
          <span class="hidden sm:inline">เพิ่มงบประมาณ</span>
        </button>
      </div>

      <div v-if="loadingBudgets" class="flex justify-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
      </div>

      <div v-else class="space-y-4">
        <div
          v-for="budget in budgets"
          :key="budget.id"
          class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4"
        >
          <div class="flex items-start justify-between mb-4">
            <div>
              <h4 class="font-semibold text-gray-900 dark:text-white">{{ budget.name }}</h4>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ budget.description }}</p>
            </div>
            <span :class="budget.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'" class="px-2 py-1 text-xs rounded-full">
              {{ budget.is_active ? 'ใช้งาน' : 'ปิด' }}
            </span>
          </div>
          
          <!-- Budget Progress -->
          <div class="space-y-2">
            <div class="flex justify-between text-sm">
              <span class="text-gray-600 dark:text-gray-400">ใช้ไป: ฿{{ formatMoney(budget.spent_amount) }}</span>
              <span class="text-gray-600 dark:text-gray-400">คงเหลือ: ฿{{ formatMoney(budget.remaining_amount) }}</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
              <div
                class="h-3 rounded-full transition-all"
                :class="getBudgetProgressClass(budget)"
                :style="{ width: `${getBudgetPercentage(budget)}%` }"
              ></div>
            </div>
            <div class="flex justify-between text-sm">
              <span class="font-medium text-gray-900 dark:text-white">งบประมาณ: ฿{{ formatMoney(budget.allocated_amount) }}</span>
              <span class="text-gray-500">{{ getBudgetPercentage(budget) }}% ใช้ไปแล้ว</span>
            </div>
          </div>
        </div>

        <div v-if="budgets.length === 0" class="text-center py-12 text-gray-500">
          <Icon icon="heroicons:chart-pie" class="h-12 w-12 mx-auto mb-3 text-gray-300" />
          <p>ยังไม่มีงบประมาณ</p>
        </div>
      </div>
    </div>

    <!-- Expense Modal -->
    <SchoolModal v-model="showExpenseModal" title="เพิ่มรายจ่าย" size="lg">
      <form @submit.prevent="saveExpense" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">หัวข้อ</label>
          <input v-model="expenseForm.title" type="text" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">รายละเอียด</label>
          <textarea v-model="expenseForm.description" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"></textarea>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">จำนวนเงิน</label>
            <input v-model.number="expenseForm.amount" type="number" min="0" step="0.01" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันที่</label>
            <input v-model="expenseForm.expense_date" type="date" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ผู้จำหน่าย/ร้านค้า</label>
          <input v-model="expenseForm.vendor" type="text" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">หมวดหมู่</label>
          <select v-model="expenseForm.expense_category_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
            <option v-for="cat in expenseCategories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
          </select>
        </div>
        <div class="flex justify-end gap-3 pt-4">
          <button type="button" @click="showExpenseModal = false" class="min-h-[44px] sm:min-h-0 px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
            ยกเลิก
          </button>
          <button type="submit" :disabled="savingExpense" class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 disabled:opacity-50">
            {{ savingExpense ? 'กำลังบันทึก...' : 'บันทึก' }}
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
const activeSection = ref('fees')
const sections = [
  { id: 'fees', name: 'ค่าธรรมเนียม' },
  { id: 'expenses', name: 'รายจ่าย' },
  { id: 'budgets', name: 'งบประมาณ' },
]

// Summary
const summary = ref({
  totalIncome: 0,
  totalExpense: 0,
  totalBudget: 0,
  remaining: 0,
})

// Fee Structures
const feeStructures = ref<any[]>([])
const loadingFees = ref(false)
const showFeeModal = ref(false)

// Expenses
const expenses = ref<any[]>([])
const loadingExpenses = ref(false)
const showExpenseModal = ref(false)
const savingExpense = ref(false)
const expenseFilter = ref('')
const expenseCategories = ref<any[]>([])
const expenseForm = ref({
  title: '',
  description: '',
  amount: 0,
  expense_date: new Date().toISOString().split('T')[0],
  vendor: '',
  expense_category_id: null as number | null,
})

// Budgets
const budgets = ref<any[]>([])
const loadingBudgets = ref(false)
const showBudgetModal = ref(false)

// Bulk Invoices
const showBulkModal = ref(false)
const generatingInvoices = ref(false)
const classrooms = ref<any[]>([])
const academicYears = ref<any[]>([])
const semesters = ref<any[]>([])
const bulkForm = ref({
  fee_structure_id: null as number | null,
  academic_year_id: null as number | null,
  semester_id: null as number | null,
  classroom_id: null as number | null,
  due_date: new Date(new Date().setDate(new Date().getDate() + 30)).toISOString().split('T')[0],
})

// Computed
const filteredExpenses = computed(() => {
  if (!expenseFilter.value) return expenses.value
  return expenses.value.filter(e => e.status === expenseFilter.value)
})

// Helpers
const formatMoney = (amount: number) => {
  return new Intl.NumberFormat('th-TH').format(amount || 0)
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: 'numeric' })
}

const getStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    pending: 'รออนุมัติ',
    approved: 'อนุมัติ',
    rejected: 'ปฏิเสธ',
  }
  return labels[status] || status
}

const getBudgetPercentage = (budget: any) => {
  if (!budget.allocated_amount) return 0
  return Math.round((budget.spent_amount / budget.allocated_amount) * 100)
}

const getBudgetProgressClass = (budget: any) => {
  const percentage = getBudgetPercentage(budget)
  if (percentage >= 90) return 'bg-red-500'
  if (percentage >= 70) return 'bg-yellow-500'
  return 'bg-green-500'
}

// Helper to safely extract array data
const extractArray = (response: any): any[] => {
  if (!response) return []
  if (Array.isArray(response)) return response
  if (response.data && Array.isArray(response.data)) return response.data
  return []
}

// Load data
const loadFeeStructures = async () => {
  loadingFees.value = true
  try {
    const response = await schoolApi.getFeeStructures(props.academyId)
    feeStructures.value = extractArray(response)
    
    // Calculate total income from fees
    summary.value.totalIncome = feeStructures.value.reduce((sum: number, fee: any) => sum + (fee.total_amount || 0), 0)
  } catch (error) {
    console.error('Failed to load fee structures:', error)
    feeStructures.value = []
  } finally {
    loadingFees.value = false
  }
}

const loadExpenses = async () => {
  loadingExpenses.value = true
  try {
    const response = await schoolApi.getExpenses(props.academyId)
    expenses.value = extractArray(response)
    
    // Calculate total expenses
    summary.value.totalExpense = expenses.value
      .filter((e: any) => e.status === 'approved')
      .reduce((sum: number, e: any) => sum + (e.amount || 0), 0)
  } catch (error) {
    console.error('Failed to load expenses:', error)
    expenses.value = []
  } finally {
    loadingExpenses.value = false
  }
}

const loadExpenseCategories = async () => {
  try {
    const response = await schoolApi.getExpenseCategories(props.academyId)
    expenseCategories.value = extractArray(response)
    if (expenseCategories.value.length > 0) {
      expenseForm.value.expense_category_id = expenseCategories.value[0].id
    }
  } catch (error) {
    console.error('Failed to load expense categories:', error)
    expenseCategories.value = []
  }
}

const loadBudgets = async () => {
  loadingBudgets.value = true
  try {
    const response = await schoolApi.getBudgets(props.academyId)
    budgets.value = extractArray(response)
    
    // Calculate total budget
    summary.value.totalBudget = budgets.value.reduce((sum: number, b: any) => sum + (b.allocated_amount || 0), 0)
    summary.value.remaining = budgets.value.reduce((sum: number, b: any) => sum + (b.remaining_amount || 0), 0)
  } catch (error) {
    console.error('Failed to load budgets:', error)
    budgets.value = []
  } finally {
    loadingBudgets.value = false
  }
}

// Actions
const openBulkModal = async (fee?: any) => {
  if (fee) {
    bulkForm.value.fee_structure_id = fee.id
  }
  showBulkModal.value = true
  await loadBulkData()
}

const loadBulkData = async () => {
  try {
    const [rooms, years] = await Promise.all([
      schoolApi.getClassrooms(props.academyId),
      schoolApi.getAcademicYears(props.academyId)
    ])
    classrooms.value = extractArray(rooms)
    academicYears.value = extractArray(years)
    
    // Auto-select current year if available
    const currentYear = academicYears.value.find((y: any) => y.is_current)
    if (currentYear) {
      bulkForm.value.academic_year_id = currentYear.id
      await loadSemesters(currentYear.id)
    }
  } catch (error) {
    console.error('Failed to load bulk data:', error)
  }
}

const loadSemesters = async (yearId: number) => {
  try {
    const response = await schoolApi.getSemesters(props.academyId, yearId)
    semesters.value = extractArray(response)
    if (semesters.value.length > 0) {
      bulkForm.value.semester_id = semesters.value[0].id
    }
  } catch (error) {
    console.error('Failed to load semesters:', error)
    semesters.value = []
  }
}

watch(() => bulkForm.value.academic_year_id, (newYearId) => {
  if (newYearId) loadSemesters(newYearId)
})

const generateBulkInvoices = async () => {
  if (!bulkForm.value.fee_structure_id || !bulkForm.value.classroom_id) {
    alert('กรุณาเลือกข้อมูลให้ครบถ้วน')
    return
  }
  
  generatingInvoices.value = true
  try {
    const response: any = await schoolApi.bulkGenerateTuitionFees(props.academyId, bulkForm.value)
    if (response.success) {
      showBulkModal.value = false
      alert(response.message || 'สร้างใบแจ้งหนี้เรียบร้อยแล้ว')
    }
  } catch (error: any) {
    alert(error.response?._data?.message || 'เกิดข้อผิดพลาดในการสร้างใบแจ้งหนี้')
  } finally {
    generatingInvoices.value = false
  }
}

const viewFeeDetails = (_fee: any) => {
  // TODO: Show fee details modal
}

const saveExpense = async () => {
  savingExpense.value = true
  try {
    await schoolApi.createExpense(props.academyId, expenseForm.value)
    showExpenseModal.value = false
    expenseForm.value = {
      title: '',
      description: '',
      amount: 0,
      expense_date: new Date().toISOString().split('T')[0],
      vendor: '',
      expense_category_id: expenseCategories.value[0]?.id || null,
    }
    await loadExpenses()
  } catch (error) {
    console.error('Failed to save expense:', error)
  } finally {
    savingExpense.value = false
  }
}

const approveExpenseItem = async (expense: any) => {
  if (confirm(`อนุมัติรายจ่าย "${expense.title}" จำนวน ฿${formatMoney(expense.amount)} หรือไม่?`)) {
    try {
      await schoolApi.approveExpense(props.academyId, expense.id)
      await loadExpenses()
    } catch (error) {
      console.error('Failed to approve expense:', error)
    }
  }
}

const rejectExpenseItem = async (expense: any) => {
  const reason = prompt('เหตุผลในการปฏิเสธ:')
  if (reason) {
    try {
      await schoolApi.rejectExpense(props.academyId, expense.id, reason)
      await loadExpenses()
    } catch (error) {
      console.error('Failed to reject expense:', error)
    }
  }
}

// Watch section change
watch(activeSection, (section) => {
  if (section === 'fees' && feeStructures.value.length === 0) loadFeeStructures()
  if (section === 'expenses' && expenses.value.length === 0) {
    loadExpenses()
    loadExpenseCategories()
  }
  if (section === 'budgets' && budgets.value.length === 0) loadBudgets()
}, { immediate: true })

onMounted(() => {
  loadFeeStructures()
  loadExpenses()
  loadBudgets()
})
</script>
