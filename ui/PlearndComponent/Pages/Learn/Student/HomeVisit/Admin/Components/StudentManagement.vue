<template>
  <!-- 
    TODO: Student Management Component - Under Development
    This component will handle student CRUD operations, import/export, and visit history
    Features to be implemented:
    - Student list with search and filters
    - Add/Edit student modal
    - Import students from Excel/CSV
    - Export student data
    - View visit history
    - Bulk operations (assign visit, export, delete)
  -->
  <div class="student-management">
    <!-- Student Statistics Cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                <i class="fas fa-users text-white text-sm"></i>
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">
                  นักเรียนทั้งหมด
                </dt>
                <dd class="text-lg font-medium text-gray-900">
                  {{ studentsData?.length || 0 }}
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                <i class="fas fa-check text-white text-sm"></i>
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">
                  กำลังเรียน
                </dt>
                <dd class="text-lg font-medium text-gray-900">
                  {{ activeStudentsCount }}
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center">
                <i class="fas fa-clock text-white text-sm"></i>
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">
                  รอการเยี่ยมบ้าน
                </dt>
                <dd class="text-lg font-medium text-gray-900">
                  {{ pendingVisitCount }}
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center">
                <i class="fas fa-percentage text-white text-sm"></i>
              </div>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">
                  เยี่ยมบ้านแล้ว
                </dt>
                <dd class="text-lg font-medium text-gray-900">
                  {{ visitCompletionRate }}%
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Coming Soon Notice -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
      <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 mb-4">
        <i class="fas fa-tools text-yellow-600 text-xl"></i>
      </div>
      <h3 class="text-lg font-medium text-gray-900 mb-2">
        ฟีเจอร์กำลังพัฒนา
      </h3>
      <p class="text-sm text-gray-600 mb-4">
        ฟีเจอร์การจัดการนักเรียนยังอยู่ระหว่างการพัฒนา<br>
        จะเปิดให้ใช้งานในเร็วๆ นี้
      </p>
      <div class="text-xs text-gray-500">
        <p>ฟีเจอร์ที่กำลังพัฒนา:</p>
        <ul class="mt-2 space-y-1">
          <li>✓ ค้นหาและกรองข้อมูลนักเรียน</li>
          <li>✓ เพิ่ม/แก้ไข/ลบข้อมูลนักเรียน</li>
          <li>✓ นำเข้าข้อมูลจาก Excel/CSV</li>
          <li>✓ ส่งออกข้อมูลนักเรียน</li>
          <li>✓ ดูประวัติการเยี่ยมบ้าน</li>
          <li>✓ การจัดการแบบกลุ่ม (Bulk Operations)</li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue'

export default {
  name: 'StudentManagement',
  
  props: {
    students: {
      type: Array,
      default: () => []
    }
  },

  setup(props) {
    const studentsData = ref(props.students || [])

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

    return {
      studentsData,
      activeStudentsCount,
      pendingVisitCount,
      visitCompletionRate
    }
  }
}
</script>

<style scoped>
.student-management {
  /* Add component-specific styles here */
}
</style>
