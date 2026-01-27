<template>
  <li class="px-3 py-4 hover:bg-gray-50 border-l-4 border-transparent hover:border-indigo-400 transition-all duration-200">
    <!-- Mobile Layout (sm and below) -->
    <div class="block sm:hidden">
      <div class="space-y-3">
        <!-- Header Row -->
        <div class="flex items-start space-x-4">
          <div class="flex-shrink-0">
            <div v-if="student.profile_image" class="w-24 h-32 rounded-md overflow-hidden border-2 border-gray-200 shadow-lg cursor-pointer hover:shadow-xl transition-shadow" @click="openPhotoModal">
              <img :src="getProfileImagePath(student)" :alt="student.first_name_th" class="w-full h-full object-cover">
            </div>
            <div v-else class="w-24 h-32 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-md flex items-center justify-center border-2 border-gray-200 shadow-lg">
              <span class="text-white font-bold text-xl">
                {{ student.first_name_th?.charAt(0) || 'N' }}
              </span>
            </div>
          </div>
          
          <div class="flex-1 min-w-0 flex flex-col justify-start pt-2">
            <!-- Name and Nickname -->
            <div class="flex flex-col space-y-3">
              <!-- Name in column format -->
              <div class="space-y-2">
                <!-- Title if exists -->
                <div v-if="student.title_prefix_th" class="text-sm font-medium text-gray-600">
                  {{ student.title_prefix_th }}
                </div>
                
                <!-- Name columns -->
                <div class="grid grid-cols-1 gap-2 text-sm">
                  <div class="flex">
                    <span class="text-gray-500 font-medium w-12 flex-shrink-0">ชื่อ:</span>
                    <span class="font-bold text-gray-900 break-words flex-1">{{ student.first_name_th }}</span>
                  </div>
                  <div class="flex">
                    <span class="text-gray-500 font-medium w-16 flex-shrink-0">นามสกุล:</span>
                    <span class="font-bold text-gray-900 break-words flex-1">{{ student.last_name_th }}</span>
                  </div>
                </div>
              </div>
              
              <!-- Badges with flexible wrapping -->
              <div class="flex flex-wrap items-center gap-2">
                <!-- Nickname Badge -->
                <div v-if="student.nickname" class="flex-shrink-0">
                  <span class="inline-flex items-center px-2.5 py-1 bg-purple-100 text-purple-700 text-xs font-medium rounded-full">
                    <i class="fas fa-star mr-1"></i>
                    <span class="truncate max-w-20">{{ student.nickname }}</span>
                  </span>
                </div>
                
                <!-- Visit Status Badge -->
                <div class="flex-shrink-0">
                  <div v-if="student.home_visits?.length > 0" class="inline-flex items-center px-2.5 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">
                    <i class="fas fa-check-circle mr-1"></i>
                    <span class="whitespace-nowrap">เยี่ยม {{ student.home_visits.length }} ครั้ง</span>
                  </div>
                  <div v-else class="inline-flex items-center px-2.5 py-1 bg-orange-100 text-orange-700 text-xs font-medium rounded-full">
                    <i class="fas fa-clock mr-1"></i>
                    <span class="whitespace-nowrap">ยังไม่เคยเยี่ยม</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Information Grid -->
        <StudentInfoGrid :student="student" />

        <!-- Action Buttons -->
        <div class="flex space-x-2">
          <button
            @click="$emit('view-student', student)"
            class="flex-1 inline-flex items-center justify-center px-4 py-2 border border-indigo-300 text-sm font-medium rounded-lg text-indigo-700 bg-indigo-50 hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
          >
            <i class="fas fa-user-graduate mr-2"></i>
            ดูข้อมูล
          </button>
          <button
            @click="$emit('create-visit', student)"
            class="flex-1 inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 transition-colors"
          >
            <i class="fas fa-home mr-2"></i>
            เยี่ยมบ้าน
          </button>
        </div>
      </div>
    </div>

    <!-- Desktop Layout (sm and above) -->
    <div class="hidden sm:flex items-center justify-between">
      <div class="flex items-center flex-1">
        <div class="flex-shrink-0">
          <div v-if="student.profile_image" class="w-20 h-24 rounded-md overflow-hidden border border-gray-200 shadow-lg cursor-pointer hover:shadow-xl transition-shadow" @click="openPhotoModal">
            <img :src="getProfileImagePath(student)" :alt="student.first_name_th" class="w-full h-full object-cover">
          </div>
          <div v-else class="w-20 h-24 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-md flex items-center justify-center border border-gray-200 shadow-lg">
            <span class="text-white font-semibold text-lg">
              {{ student.first_name_th?.charAt(0) || 'N' }}
            </span>
          </div>
        </div>
        <div class="ml-4 sm:ml-5 flex-1 min-w-0">
          <div class="flex flex-col gap-2">
            <!-- Title if exists -->
            <div v-if="student.title_prefix_th" class="text-xs font-medium text-gray-600">
              {{ student.title_prefix_th }}
            </div>
            
            <!-- Name columns for desktop -->
            <div class="flex flex-col gap-1">
              <div class="flex items-center">
                <span class="text-gray-500 font-medium text-xs w-12 flex-shrink-0">ชื่อ:</span>
                <span class="font-semibold text-gray-900 text-sm break-words flex-1">{{ student.first_name_th }}</span>
                <div v-if="student.nickname" class="ml-2 flex-shrink-0">
                  <span class="inline-flex items-center text-xs text-purple-700 bg-purple-100 px-2 py-1 rounded-full font-medium">
                    <i class="fas fa-star mr-1"></i>
                    <span class="truncate max-w-16">{{ student.nickname }}</span>
                  </span>
                </div>
              </div>
              <div class="flex items-center">
                <span class="text-gray-500 font-medium text-xs w-12 flex-shrink-0">สกุล:</span>
                <span class="font-semibold text-gray-900 text-sm break-words flex-1">{{ student.last_name_th }}</span>
              </div>
            </div>
          </div>
          
          <div class="mt-1 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-1 text-sm text-gray-500">
            <div class="flex items-center">
              <i class="fas fa-id-card w-4 mr-2"></i>
              รหัส: {{ student.student_id }}
            </div>
            <div v-if="student.class_level" class="flex items-center">
              <i class="fas fa-layer-group w-4 mr-2"></i>
              ระดับ: ม.{{ student.class_level }}
            </div>
            <div v-if="student.class_section" class="flex items-center">
              <i class="fas fa-door-open w-4 mr-2"></i>
              ห้อง: {{ student.class_section }}
            </div>
            <div v-if="student.academic_info?.current_class" class="flex items-center">
              <i class="fas fa-graduation-cap w-4 mr-2"></i>
              ชั้นปัจจุบัน: {{ student.academic_info.current_class }}
            </div>
            <div v-if="student.citizen_id" class="flex items-center">
              <i class="fas fa-credit-card w-4 mr-2"></i>
              เลขบัตร: {{ student.citizen_id }}
            </div>
            <div v-if="student.contacts?.length > 0" class="flex items-center">
              <i class="fas fa-phone w-4 mr-2"></i>
              เบอร์: {{ student.contacts[0].contact_value || 'ไม่ระบุ' }}
            </div>
          </div>

          <!-- Academic Info -->
          <div class="mt-2 text-xs text-gray-400 space-x-3">
            <span v-if="student.academic_info?.school_name">
              <i class="fas fa-school mr-1"></i>
              {{ student.academic_info.school_name }}
            </span>
            <span v-if="student.academic_info?.education_level">
              <i class="fas fa-graduation-cap mr-1"></i>
              การศึกษา: {{ getEducationLevelText(student.academic_info.education_level) }}
            </span>
            <span v-if="student.class_level && student.class_section">
              <i class="fas fa-users mr-1"></i>
              ม.{{ student.class_level }}/{{ student.class_section }}
            </span>
          </div>

          <!-- Home Visit Status -->
          <div v-if="student.home_visits?.length > 0" class="mt-2 text-xs">
            <div class="inline-flex items-center px-2 py-1 bg-green-100 text-green-700 rounded">
              <i class="fas fa-home mr-1"></i>
              เยี่ยมล่าสุด: {{ formatDate(student.home_visits[0].visit_date) }}
            </div>
          </div>
          <div v-else class="mt-2 text-xs">
            <div class="inline-flex items-center px-2 py-1 bg-yellow-100 text-yellow-700 rounded">
              <i class="fas fa-exclamation-circle mr-1"></i>
              ยังไม่เคยเยี่ยมบ้าน
            </div>
          </div>
        </div>
      </div>
      
      <div class="flex flex-col items-end space-y-2 ml-4">
        <!-- Visit Count Badge -->
        <div v-if="student.home_visits?.length > 0" class="text-xs text-center">
          <span class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-700 rounded-full">
            <i class="fas fa-chart-line mr-1"></i>
            เยี่ยมแล้ว {{ student.home_visits.length }} ครั้ง
          </span>
        </div>
        
        <div class="flex flex-col space-y-1">
          <button
            @click="$emit('view-student', student)"
            class="inline-flex items-center px-3 py-2 border border-indigo-300 shadow-sm text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-50 hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
          >
            <i class="fas fa-user-graduate mr-2"></i>
            ดูข้อมูล
          </button>
          <button
            @click="$emit('create-visit', student)"
            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors"
          >
            <i class="fas fa-home mr-2"></i>
            เยี่ยมบ้าน
          </button>
        </div>
      </div>
    </div>
  </li>

  <!-- Photo Modal -->
  <div v-if="showPhotoModal" class="fixed inset-0 z-50 overflow-y-auto" @click="closePhotoModal">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 transition-opacity bg-black bg-opacity-75" @click="closePhotoModal"></div>

      <!-- Modal content -->
      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full" @click.stop>
        <!-- Header -->
        <div class="bg-gray-50 px-4 py-3 sm:px-6 flex items-center justify-between border-b">
          <h3 class="text-lg leading-6 font-medium text-gray-900">
            รูปภาพ {{ student.title_prefix_th }} {{ student.first_name_th }} {{ student.last_name_th }}
          </h3>
          <button @click="closePhotoModal" class="text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600 transition ease-in-out duration-150">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Photo content -->
        <div class="bg-white px-4 py-6 sm:px-6">
          <div class="flex justify-center">
            <img
              :src="getProfileImagePath(student)"
              :alt="`${student.first_name_th} ${student.last_name_th}`"
              class="max-w-full max-h-96 object-contain rounded-lg shadow-lg"
            />
          </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t">
          <button
            @click="closePhotoModal"
            type="button"
            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-gray-600 text-base font-medium text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors"
          >
            ปิด
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import StudentInfoGrid from './StudentInfoGrid.vue'

export default {
  name: 'StudentCard',
  
  components: {
    StudentInfoGrid
  },

  props: {
    student: {
      type: Object,
      required: true
    }
  },

  emits: ['view-student', 'create-visit'],

  data() {
    return {
      showPhotoModal: false
    }
  },

  methods: {
    getProfileImagePath(student) {
      if (!student.profile_image) return null
      return `../../storage/images/students/${student.class_level}/${student.class_section}/${student.profile_image}`
    },

    formatDate(dateString) {
      if (!dateString) return 'ไม่ระบุ'
      const date = new Date(dateString)
      return date.toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    },

    getEducationLevelText(level) {
      const levelMap = {
        1: 'ประถมศึกษา',
        2: 'มัธยมศึกษาตอนต้น',
        3: 'มัธยมศึกษาตอนปลาย',
        4: 'อุดมศึกษา'
      }
      return levelMap[level] || `ระดับ ${level}`
    },

    openPhotoModal() {
      if (this.student.profile_image) {
        this.showPhotoModal = true
      }
    },

    closePhotoModal() {
      this.showPhotoModal = false
    }
  }
}
</script>
