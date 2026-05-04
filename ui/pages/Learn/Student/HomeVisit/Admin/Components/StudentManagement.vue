<script setup>
import { ref, computed } from 'vue'
import StudentTable from './StudentTable.vue'
import StudentForm from './StudentForm.vue'
import StudentImportModal from './StudentImportModal.vue'
import StudentVisitHistory from './StudentVisitHistory.vue'

const props = defineProps({
  students: {
    type: Array,
    default: () => []
  },
  visits: {
    type: Array,
    default: () => []
  }
})

const studentsData = ref(props.students || [])
const allVisits = ref(props.visits || [])

const isFormOpen = ref(false)
const isImportOpen = ref(false)
const isHistoryOpen = ref(false)
const selectedStudent = ref(null)

// Student statistics
const activeStudentsCount = computed(() => {
  return studentsData.value.filter(student => student.status === 'active').length
})

const pendingVisitCount = computed(() => {
  return studentsData.value.filter(student => student.visit_status === 'pending').length
})

const visitCompletionRate = computed(() => {
  const totalActive = studentsData.value.filter(student => student.status === 'active').length
  const completedVisits = studentsData.value.filter(student => student.visit_status === 'completed').length
  return totalActive > 0 ? Math.round((completedVisits / totalActive) * 100) : 0
})

// Handlers
const openAddForm = () => {
  selectedStudent.value = null
  isFormOpen.value = true
}

const openEditForm = (student) => {
  selectedStudent.value = student
  isFormOpen.value = true
}

const openImportModal = () => {
  isImportOpen.value = true
}

const openHistory = (student) => {
  selectedStudent.value = student
  isHistoryOpen.value = true
}

const handleSaveStudent = (formData) => {
  if (selectedStudent.value) {
    // Update existing student
    const index = studentsData.value.findIndex(s => s.id === selectedStudent.value.id)
    if (index !== -1) {
      studentsData.value[index] = { ...studentsData.value[index], ...formData }
    }
  } else {
    // Add new student
    const newStudent = {
      ...formData,
      id: Date.now(), // Temporary ID
      visit_status: 'pending'
    }
    studentsData.value.push(newStudent)
  }
  isFormOpen.value = false
}

const handleImportStudents = (file) => {

  // Here you would typically call an API to process the file
  alert('นำเข้าข้อมูลสำเร็จ (จำลองการทำงาน)')
  isImportOpen.value = false
}
</script>

<template>
  <div class="student-management">
    <!-- Student Statistics Cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
      <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-blue-500">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-users text-blue-600"></i>
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">
                  นักเรียนทั้งหมด
                </dt>
                <dd class="text-2xl font-bold text-gray-900">
                  {{ studentsData.length }}
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-green-500">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-user-check text-green-600"></i>
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">
                  กำลังเรียน
                </dt>
                <dd class="text-2xl font-bold text-gray-900">
                  {{ activeStudentsCount }}
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-yellow-500">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                <i class="fas fa-clock text-yellow-600"></i>
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">
                  รอการเยี่ยมบ้าน
                </dt>
                <dd class="text-2xl font-bold text-gray-900">
                  {{ pendingVisitCount }}
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-purple-500">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                <i class="fas fa-chart-line text-purple-600"></i>
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">
                  ความสำเร็จ
                </dt>
                <dd class="text-2xl font-bold text-gray-900">
                  {{ visitCompletionRate }}%
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Actions Header -->
    <div class="mb-6 flex flex-wrap gap-3 items-center justify-between">
      <h2 class="text-xl font-bold text-gray-800">จัดการข้อมูลนักเรียน</h2>
      <div class="flex gap-2">
        <button 
          @click="openImportModal"
          class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
          <i class="fas fa-file-import mr-2"></i>
          นำเข้าข้อมูล
        </button>
        <button 
          @click="openAddForm"
          class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
          <i class="fas fa-plus mr-2"></i>
          เพิ่มนักเรียน
        </button>
      </div>
    </div>

    <!-- Student Table -->
    <StudentTable 
      :students="studentsData"
      @edit="openEditForm"
      @view-history="openHistory"
    />

    <!-- Modals -->
    <StudentForm 
      v-if="isFormOpen"
      :student="selectedStudent"
      @save="handleSaveStudent"
      @cancel="isFormOpen = false"
    />

    <StudentImportModal
      v-if="isImportOpen"
      @import="handleImportStudents"
      @cancel="isImportOpen = false"
    />

    <StudentVisitHistory
      v-if="isHistoryOpen"
      :student="selectedStudent"
      :visits="allVisits"
      @close="isHistoryOpen = false"
    />
  </div>
</template>

<style scoped>
.student-management {
  /* Add component-specific styles here */
}
</style>

