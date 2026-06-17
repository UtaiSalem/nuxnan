<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useStudentMasterStore } from '~/stores/studentMaster'
import { useAuthStore } from '~/stores/auth'

definePageMeta({
  layout: 'admin',
  middleware: ['auth']
})

const studentStore = useStudentMasterStore()
const authStore = useAuthStore()

// State
const searchQuery = ref('')
const selectedClassLevel = ref('')
const selectedClassSection = ref('')
const selectedStatus = ref('')
const currentPage = ref(1)
const perPage = ref(15)

// Options (Mock or derived from API)
const classLevels = ref(['ม.1', 'ม.2', 'ม.3', 'ม.4', 'ม.5', 'ม.6'])
const classSections = ref(['1', '2', '3', '4', '5'])
const statusOptions = ref([
  { value: 'active', label: 'กำลังศึกษา' },
  { value: 'graduated', label: 'สำเร็จการศึกษา' },
  { value: 'dropped_out', label: 'ลาออก/พ้นสภาพ' }
])

// Fetch Data
const fetchStudents = async () => {
  await studentStore.fetchMasterStudents({
    page: currentPage.value,
    per_page: perPage.value,
    search: searchQuery.value,
    class_level: selectedClassLevel.value,
    class_section: selectedClassSection.value,
    status: selectedStatus.value,
    academy_id: authStore.user?.academy_id || ''
  })
}

// Watchers for filters
watch([searchQuery, selectedClassLevel, selectedClassSection, selectedStatus], () => {
  currentPage.value = 1
  fetchStudents()
})

const changePage = (page: number) => {
  currentPage.value = page
  fetchStudents()
}

onMounted(() => {
  fetchStudents()
})
</script>

<template>
  <div class="admin-students-page p-6 max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">ทะเบียนประวัตินักเรียน (Master Profile)</h1>
      <div class="flex space-x-2">
        <NuxtLink to="/admin/students/requests" class="px-4 py-2 bg-yellow-500 text-white rounded-md shadow hover:bg-yellow-600 transition flex items-center">
          <i class="fas fa-bell mr-2"></i> คำขอแก้ไขข้อมูล
        </NuxtLink>
        <button class="px-4 py-2 bg-primary-600 text-white rounded-md shadow hover:bg-primary-700 transition flex items-center">
          <i class="fas fa-plus mr-2"></i> เพิ่มนักเรียนใหม่
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ค้นหา</label>
        <input 
          v-model="searchQuery" 
          type="text" 
          placeholder="รหัส, ชื่อ-สกุล..."
          class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600"
          @keyup.enter="fetchStudents"
        >
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ระดับชั้น</label>
        <select v-model="selectedClassLevel" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600">
          <option value="">ทั้งหมด</option>
          <option v-for="level in classLevels" :key="level" :value="level">{{ level }}</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ห้อง</label>
        <select v-model="selectedClassSection" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600">
          <option value="">ทั้งหมด</option>
          <option v-for="section in classSections" :key="section" :value="section">{{ section }}</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สถานะ</label>
        <select v-model="selectedStatus" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600">
          <option value="">ทั้งหมด</option>
          <option v-for="status in statusOptions" :key="status.value" :value="status.value">{{ status.label }}</option>
        </select>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
      <div v-if="studentStore.isLoading" class="p-8 text-center">
        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-primary-600 mx-auto"></div>
        <p class="mt-2 text-gray-500">กำลังโหลดข้อมูล...</p>
      </div>

      <div v-else-if="studentStore.error" class="p-8 text-center text-red-500">
        <i class="fas fa-exclamation-triangle text-3xl mb-2"></i>
        <p>{{ studentStore.error }}</p>
        <button @click="fetchStudents" class="mt-4 px-4 py-2 border border-red-500 rounded text-red-500 hover:bg-red-50">ลองใหม่</button>
      </div>

      <div v-else-if="studentStore.students.length === 0" class="p-8 text-center text-gray-500">
        <i class="fas fa-search text-3xl mb-2 text-gray-400"></i>
        <p>ไม่พบข้อมูลนักเรียนที่ตรงกับเงื่อนไข</p>
      </div>

      <table v-else class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700">
          <tr>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">รหัส นร.</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ชื่อ - สกุล</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ชั้นเรียน</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">จัดการ</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
          <tr v-for="student in studentStore.students" :key="student.id" class="hover:bg-gray-50 dark:hover:bg-gray-750 transition">
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
              {{ student.student_id || '-' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center">
                <div class="flex-shrink-0 h-10 w-10">
                  <img class="h-10 w-10 rounded-full object-cover border border-gray-200" :src="student.profile_image ? (config.public.apiBase + '/storage/' + student.profile_image) : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(student.first_name_th || 'S') + '&background=random'" alt="" />
                </div>
                <div class="ml-4">
                  <div class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ student.full_name_th }}
                  </div>
                  <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ student.citizen_id || 'ไม่มีข้อมูลบัตร ปชช.' }}
                  </div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
              <span v-if="student.class_level">{{ student.class_level }}{{ student.class_section ? '/' + student.class_section : '' }}</span>
              <span v-else class="text-gray-400 italic">ไม่ได้ระบุ</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" 
                    :class="{
                      'bg-green-100 text-green-800': student.status === 'active' || !student.status,
                      'bg-gray-100 text-gray-800': student.status === 'graduated',
                      'bg-red-100 text-red-800': student.status === 'dropped_out'
                    }">
                {{ student.status === 'active' || !student.status ? 'กำลังศึกษา' : (student.status === 'graduated' ? 'จบการศึกษา' : 'ลาออก') }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <NuxtLink :to="`/admin/students/${student.id}`" class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300 mr-3">
                <i class="fas fa-eye mr-1"></i> รายละเอียด
              </NuxtLink>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="studentStore.studentsPagination && studentStore.studentsPagination.last_page > 1" class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6 flex items-center justify-between">
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
          <div>
            <p class="text-sm text-gray-700 dark:text-gray-300">
              แสดง <span class="font-medium">{{ ((currentPage - 1) * perPage) + 1 }}</span> ถึง 
              <span class="font-medium">{{ Math.min(currentPage * perPage, studentStore.studentsPagination.total) }}</span> 
              จากทั้งหมด <span class="font-medium">{{ studentStore.studentsPagination.total }}</span> รายการ
            </p>
          </div>
          <div>
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
              <button 
                @click="changePage(currentPage - 1)" 
                :disabled="currentPage === 1"
                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                <span class="sr-only">Previous</span>
                <i class="fas fa-chevron-left"></i>
              </button>
              
              <button 
                v-for="page in studentStore.studentsPagination.last_page" 
                :key="page"
                @click="changePage(page)"
                :class="[
                  page === currentPage ? 'z-10 bg-primary-50 border-primary-500 text-primary-600 dark:bg-primary-900 dark:border-primary-500 dark:text-primary-200' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300',
                  'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                ]">
                {{ page }}
              </button>
              
              <button 
                @click="changePage(currentPage + 1)" 
                :disabled="currentPage === studentStore.studentsPagination.last_page"
                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">
                <span class="sr-only">Next</span>
                <i class="fas fa-chevron-right"></i>
              </button>
            </nav>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
