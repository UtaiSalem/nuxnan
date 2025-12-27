<template>
  <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-4 space-y-3 border border-gray-200">
    <div class="grid grid-cols-2 gap-3">
      <!-- Student ID -->
      <div class="bg-white rounded-lg p-3 shadow-sm">
        <div class="flex items-center space-x-2">
          <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
            <i class="fas fa-id-card text-blue-600 text-sm"></i>
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-xs text-gray-500 font-medium">รหัสนักเรียน</p>
            <p class="text-sm font-bold text-gray-900 break-all">{{ student.student_id }}</p>
          </div>
        </div>
      </div>
      
      <!-- Class Level -->
      <div v-if="student.class_level" class="bg-white rounded-lg p-3 shadow-sm">
        <div class="flex items-center space-x-2">
          <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0">
            <i class="fas fa-layer-group text-emerald-600 text-sm"></i>
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-xs text-gray-500 font-medium">ระดับชั้น</p>
            <p class="text-sm font-bold text-gray-900">ม.{{ student.class_level }}</p>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-3">
      <!-- Class Section -->
      <div v-if="student.class_section" class="bg-white rounded-lg p-3 shadow-sm">
        <div class="flex items-center space-x-2">
          <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
            <i class="fas fa-graduation-cap text-green-600 text-sm"></i>
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-xs text-gray-500 font-medium">ห้อง</p>
            <p class="text-sm font-bold text-gray-900">{{ student.class_section }}</p>
          </div>
        </div>
      </div>
      
      <!-- Current Class (from academic_info) -->
      <div v-if="student.academic_info?.current_class" class="bg-white rounded-lg p-3 shadow-sm">
        <div class="flex items-center space-x-2">
          <div class="w-8 h-8 bg-teal-100 rounded-lg flex items-center justify-center flex-shrink-0">
            <i class="fas fa-chalkboard-teacher text-teal-600 text-sm"></i>
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-xs text-gray-500 font-medium">ชั้นปัจจุบัน</p>
            <p class="text-sm font-bold text-gray-900 break-words">{{ student.academic_info.current_class }}</p>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-3" v-if="student.citizen_id || (student.contacts && student.contacts.length > 0)">
      <!-- Citizen ID -->
      <div v-if="student.citizen_id" class="bg-white rounded-lg p-3 shadow-sm">
        <div class="flex items-center space-x-2">
          <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
            <i class="fas fa-credit-card text-purple-600 text-sm"></i>
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-xs text-gray-500 font-medium">เลขบัตร</p>
            <p class="text-xs font-bold text-gray-900 break-all">{{ student.citizen_id }}</p>
          </div>
        </div>
      </div>
      
      <!-- Phone -->
      <div v-if="student.contacts && student.contacts.length > 0" class="bg-white rounded-lg p-3 shadow-sm">
        <div class="flex items-center space-x-2">
          <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
            <i class="fas fa-phone text-yellow-600 text-sm"></i>
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-xs text-gray-500 font-medium">เบอร์โทร</p>
            <p class="text-xs font-bold text-gray-900 break-all">{{ student.contacts[0].contact_value || 'ไม่ระบุ' }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Last Visit Info -->
    <div v-if="student.home_visits?.length > 0" class="bg-white rounded-lg p-3 shadow-sm">
      <div class="flex items-center space-x-2">
        <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
          <i class="fas fa-calendar text-indigo-600 text-sm"></i>
        </div>
        <div class="min-w-0 flex-1">
          <p class="text-xs text-gray-500 font-medium">เยี่ยมล่าสุด</p>
          <p class="text-sm font-bold text-gray-900 break-words">{{ formatDate(student.home_visits[0].visit_date) }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'StudentInfoGrid',
  
  props: {
    student: {
      type: Object,
      required: true
    }
  },

  methods: {
    formatDate(dateString) {
      if (!dateString) return 'ไม่ระบุ'
      const date = new Date(dateString)
      return date.toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }
  }
}
</script>

