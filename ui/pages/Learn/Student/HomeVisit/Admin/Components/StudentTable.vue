<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  students: {
    type: Array,
    required: true
  }
})

const emit = defineEmits(['edit', 'view-history'])

const searchQuery = ref('')
const selectedGrade = ref('')
const selectedRoom = ref('')
const currentPage = ref(1)
const itemsPerPage = 10

// Get unique grades and rooms for filters
const grades = computed(() => [...new Set(props.students.map(s => s.grade))].sort())
const rooms = computed(() => [...new Set(props.students.map(s => s.room))].sort())

const filteredStudents = computed(() => {
  return props.students.filter(student => {
    const matchesSearch = !searchQuery.value || 
      student.first_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      student.last_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      student.student_id?.toString().includes(searchQuery.value)
    
    const matchesGrade = !selectedGrade.value || student.grade === selectedGrade.value
    const matchesRoom = !selectedRoom.value || student.room === selectedRoom.value
    
    return matchesSearch && matchesGrade && matchesRoom
  })
})

const totalPages = computed(() => Math.ceil(filteredStudents.value.length / itemsPerPage))

const paginatedStudents = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return filteredStudents.value.slice(start, end)
})

const handlePageChange = (page) => {
  currentPage.value = page
}
</script>

<template>
  <div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="p-4 border-b border-gray-200 flex flex-wrap gap-4 items-center justify-between">
      <div class="flex flex-wrap gap-4 items-center flex-1">
        <div class="relative min-w-[250px]">
          <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
            <i class="fas fa-search"></i>
          </span>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="ค้นหาชื่อหรือรหัสนักเรียน..."
            class="pl-10 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          />
        </div>
        
        <select
          v-model="selectedGrade"
          class="block border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
        >
          <option value="">ชั้นปีทั้งหมด</option>
          <option v-for="grade in grades" :key="grade" :value="grade">{{ grade }}</option>
        </select>

        <select
          v-model="selectedRoom"
          class="block border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
        >
          <option value="">ห้องทั้งหมด</option>
          <option v-for="room in rooms" :key="room" :value="room">ห้อง {{ room }}</option>
        </select>
      </div>
      
      <div class="text-sm text-gray-500">
        พบ {{ filteredStudents.length }} รายการ
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">รหัสนักเรียน</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ชื่อ-นามสกุล</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ชั้น/ห้อง</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะการเยี่ยมบ้าน</th>
            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">จัดการ</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="student in paginatedStudents" :key="student.id">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ student.student_id }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ student.first_name }} {{ student.last_name }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ student.grade }}/{{ student.room }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span 
                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                :class="{
                  'bg-green-100 text-green-800': student.visit_status === 'completed',
                  'bg-yellow-100 text-yellow-800': student.visit_status === 'pending',
                  'bg-gray-100 text-gray-800': !student.visit_status
                }"
              >
                {{ student.visit_status === 'completed' ? 'เรียบร้อย' : 'รอดำเนินการ' }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
              <button 
                @click="emit('edit', student)"
                class="text-blue-600 hover:text-blue-900"
              >
                แก้ไข
              </button>
              <button 
                @click="emit('view-history', student)"
                class="text-green-600 hover:text-green-900"
              >
                ประวัติการเยี่ยม
              </button>
            </td>
          </tr>
          <tr v-if="paginatedStudents.length === 0">
            <td colspan="5" class="px-6 py-10 text-center text-gray-500">
              ไม่พบข้อมูลนักเรียน
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="px-4 py-3 border-t border-gray-200 flex items-center justify-between sm:px-6">
      <div class="flex-1 flex justify-between sm:hidden">
        <button
          @click="handlePageChange(currentPage - 1)"
          :disabled="currentPage === 1"
          class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
        >
          ก่อนหน้า
        </button>
        <button
          @click="handlePageChange(currentPage + 1)"
          :disabled="currentPage === totalPages"
          class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
        >
          ถัดไป
        </button>
      </div>
      <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
        <div>
          <p class="text-sm text-gray-700">
            แสดงจาก <span class="font-medium">{{ ((currentPage - 1) * itemsPerPage) + 1 }}</span> ถึง <span class="font-medium">{{ Math.min(currentPage * itemsPerPage, filteredStudents.length) }}</span> จากทั้งหมด <span class="font-medium">{{ filteredStudents.length }}</span> รายการ
          </p>
        </div>
        <div>
          <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
            <button
              @click="handlePageChange(currentPage - 1)"
              :disabled="currentPage === 1"
              class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
            >
              <span class="sr-only">Previous</span>
              <i class="fas fa-chevron-left"></i>
            </button>
            
            <button
              v-for="page in totalPages"
              :key="page"
              @click="handlePageChange(page)"
              :class="[
                page === currentPage ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
              ]"
            >
              {{ page }}
            </button>

            <button
              @click="handlePageChange(currentPage + 1)"
              :disabled="currentPage === totalPages"
              class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
            >
              <span class="sr-only">Next</span>
              <i class="fas fa-chevron-right"></i>
            </button>
          </nav>
        </div>
      </div>
    </div>
  </div>
</template>
