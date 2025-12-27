<template>
  <Head title="ระบบจัดการการเยี่ยมบ้านนักเรียน - ครู" />
  
  <div class="min-h-screen bg-gray-50">
    <!-- Navigation Header -->
    <nav class="bg-white shadow-sm border-b">
      <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">
        <!-- Mobile Layout (sm and below) -->
        <div class="flex justify-between items-center h-16 sm:hidden">
          <div class="flex items-center min-w-0 flex-1">
            <div class="flex-shrink-0">
              <span class="text-xl">🏠</span>
            </div>
            <div class="ml-2 min-w-0 flex-1">
              <h1 class="text-sm font-semibold text-gray-900 truncate">
                ระบบเยี่ยมบ้านนักเรียน
              </h1>
              <p class="text-xs text-gray-500 truncate">
                ครู
              </p>
            </div>
          </div>
          <div class="flex items-center ml-2">
            <button
              @click="logout"
              class="text-gray-500 hover:text-gray-700 p-2 rounded-md"
              title="ออกจากระบบ"
            >
              <i class="fas fa-sign-out-alt text-lg"></i>
            </button>
          </div>
        </div>

        <!-- Desktop Layout (sm and above) -->
        <div class="hidden sm:flex justify-between h-16">
          <div class="flex items-center">
            <div class="flex-shrink-0 flex items-center">
              <span class="text-2xl">🏠</span>
              <h1 class="ml-3 text-xl font-semibold text-gray-900">
                ระบบเยี่ยมบ้านนักเรียน - ครู
              </h1>
            </div>
          </div>
          <div class="flex items-center space-x-4">
            <span class="text-sm text-gray-600">
              สวัสดี, ครู
            </span>
            <button
              @click="logout"
              class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium transition-colors"
            >
              ออกจากระบบ
            </button>
          </div>
        </div>
      </div>
    </nav>

    <div class="max-w-7xl mx-auto py-4 px-4 sm:py-6 sm:px-6 lg:px-8">

      <!-- Search Students -->
      <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <!-- Mobile Header -->
        <div class="px-4 py-4 sm:hidden">
          <h3 class="text-base font-semibold text-gray-900 flex items-center">
            <i class="fas fa-search mr-2 text-indigo-600"></i>
            ค้นหานักเรียน
          </h3>
          <p class="mt-1 text-xs text-gray-500">
            ค้นหาด้วยรหัสประจำตัวนักเรียน
          </p>
        </div>

        <!-- Desktop Header -->
        <div class="hidden sm:block px-4 py-5 sm:px-6">
          <h3 class="text-lg leading-6 font-medium text-gray-900">
            ค้นหานักเรียน
          </h3>
          <p class="mt-1 max-w-2xl text-sm text-gray-500">
            ค้นหานักเรียนด้วยรหัสประจำตัวเพื่อดูข้อมูลและจัดการการเยี่ยมบ้าน
          </p>
        </div>
        <div class="px-4 pb-4 pt-2 sm:p-6">
          <div class="space-y-4">
            <!-- Mobile Layout -->
            <div class="block sm:hidden space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ค้นหานักเรียน</label>
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="กรอกรหัสประจำตัวนักเรียน..."
                  :disabled="isLoading"
                  class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-base disabled:bg-gray-100 disabled:cursor-not-allowed"
                  @keyup.enter="searchStudents"
                >
              </div>
              <!-- <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ชั้นเรียน</label>
                <select
                  v-model="selectedClassroom"
                  class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-base"
                  @change="searchStudents"
                >
                  <option value="">ทุกชั้นเรียน</option>
                  <option v-for="classroom in classrooms" :key="classroom" :value="classroom">
                    ชั้น {{ classroom }}
                  </option>
                </select>
              </div> -->
              <button
                @click="searchStudents"
                :disabled="isLoading"
                class="w-full inline-flex justify-center items-center py-3 px-4 border border-transparent shadow-sm text-base font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <svg v-if="isLoading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <i v-else class="fas fa-search mr-2"></i>
                {{ isLoading ? 'กำลังค้นหา...' : 'ค้นหานักเรียน' }}
              </button>
            </div>

            <!-- Desktop Layout -->
            <div class="hidden sm:flex flex-col sm:flex-row gap-4">
              <div class="flex-1">
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="กรอกรหัสประจำตัวนักเรียน"
                  :disabled="isLoading"
                  class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm disabled:bg-gray-100 disabled:cursor-not-allowed"
                  @keyup.enter="searchStudents"
                >
              </div>
              <!-- <div class="flex-shrink-0">
                <select
                  v-model="selectedClassroom"
                  class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                  @change="searchStudents"
                >
                  <option value="">ทุกชั้นเรียน</option>
                  <option v-for="classroom in classrooms" :key="classroom" :value="classroom">
                    ชั้น {{ classroom }}
                  </option>
                </select>
              </div> -->
              <button
                @click="searchStudents"
                :disabled="isLoading"
                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <svg v-if="isLoading" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <i v-else class="fas fa-search mr-2"></i>
                {{ isLoading ? 'กำลังค้นหา...' : 'ค้นหา' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Student Detail (when single student found) -->
      <div v-if="selectedStudent" class="space-y-6">
        <!-- Back to Search Button -->
        <div class="flex items-center space-x-3">
          <button
            @click="clearSelectedStudent"
            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
          >
            <i class="fas fa-arrow-left mr-2"></i>
            ค้นหาใหม่
          </button>
          <div class="text-sm text-gray-500">
            กำลังดูข้อมูลของ: <span class="font-semibold">{{ selectedStudent.first_name_th }} {{ selectedStudent.last_name_th }}</span>
          </div>
        </div>

        <!-- Student Information Cards -->
        <div class="space-y-6">
          <!-- Student Basic Info Card -->
          <StudentsCard 
            :student="selectedStudent" 
            :student-card="selectedStudent"
            context="teacher"
            @save="handleStudentSave"
            @update="handleStudentUpdate"
          />

          <!-- Academic Information Card -->
          <AcademicInfoCard 
            :student="selectedStudent"
            context="teacher"
            @save="handleAcademicSave"
            @update="handleAcademicUpdate"
          />

          <!-- Address Information Card -->
          <AddressCard 
            :student="selectedStudent"
            context="teacher"
            @save="handleAddressSave"
            @update="handleAddressUpdate"
          />

          <!-- Contact Information Card -->
          <ContactCard 
            :student="selectedStudent"
            context="teacher"
            @save="handleContactSave"
            @update="handleContactUpdate"
          />

          <!-- Health Information Card -->
          <HealthInfoCard 
            :student="selectedStudent"
            context="teacher"
            @save="handleHealthSave"
            @update="handleHealthUpdate"
          />

          <!-- Guardian Information Card -->
          <GuardianCard 
            :student="selectedStudent"
            context="teacher"
            @save="handleGuardianSave"
            @update="handleGuardianUpdate"
          />
        </div>

        <!-- Home Visit Section -->
        <HomeVisitCard
          :home-visits="homeVisits" 
          :visit-form="visitForm"
          :zones="zones"
          :create-new-home-visit="createNewHomeVisit"
          :handle-image-upload="handleImageUpload"
          :remove-image="removeImage"
          @visit-updated="onVisitUpdated"  
        />
        
      </div>

      <!-- Students List (when multiple students found) - Only show if more than one student -->
      <div v-else-if="students.data?.length > 1 && !selectedStudent" class="bg-white shadow overflow-hidden sm:rounded-md">
        <!-- Mobile Header -->
        <div class="px-4 py-3 sm:hidden border-b border-gray-200">
          <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-900 flex items-center">
              <i class="fas fa-users mr-2 text-green-600"></i>
              รายชื่อนักเรียน
            </h3>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
              {{ students.data.length }} คน
            </span>
          </div>
        </div>

        <!-- Desktop Header -->
        <div class="hidden sm:block px-4 py-5 sm:px-6">
          <h3 class="text-lg leading-6 font-medium text-gray-900">
            รายชื่อนักเรียน
          </h3>
          <p class="mt-1 max-w-2xl text-sm text-gray-500">
            พบนักเรียนหลายคน {{ students.data.length }} คน กรุณาเลือกนักเรียนที่ต้องการดูข้อมูล
          </p>
        </div>
        <ul class="divide-y divide-gray-200">
          <StudentCard
            v-for="student in students.data"
            :key="student.id"
            :student="student"
            @view-student="selectStudent"
            @create-home-visit="createHomeVisit"
          />

        </ul>

        <!-- Pagination -->
        <div v-if="students.links" class="bg-gray-50 px-4 py-3 border-t border-gray-200 sm:px-6">
          <!-- Mobile Pagination -->
          <div class="flex items-center justify-between sm:hidden">
            <div class="flex-1 flex justify-between">
              <a
                v-if="students.prev_page_url"
                :href="students.prev_page_url"
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 shadow-sm transition-colors"
              >
                <i class="fas fa-chevron-left mr-2"></i>
                ก่อนหน้า
              </a>
              <div v-else class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-400">
                <i class="fas fa-chevron-left mr-2"></i>
                ก่อนหน้า
              </div>
              
              <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700">
                หน้า {{ students.current_page }} จาก {{ students.last_page }}
              </span>
              
              <a
                v-if="students.next_page_url"
                :href="students.next_page_url"
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 shadow-sm transition-colors"
              >
                ถัดไป
                <i class="fas fa-chevron-right ml-2"></i>
              </a>
              <div v-else class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-400">
                ถัดไป
                <i class="fas fa-chevron-right ml-2"></i>
              </div>
            </div>
          </div>

          <!-- Desktop Pagination -->
          <div class="hidden sm:flex items-center justify-between">
            <div class="flex items-center">
              <p class="text-sm text-gray-700">
                แสดง <span class="font-medium">{{ students.from }}</span> ถึง <span class="font-medium">{{ students.to }}</span> 
                จาก <span class="font-medium">{{ students.total }}</span> ผลลัพธ์
              </p>
            </div>
            <div class="flex items-center space-x-2">
              <a
                v-if="students.prev_page_url"
                :href="students.prev_page_url"
                class="relative inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors"
              >
                <i class="fas fa-chevron-left mr-1"></i>
                ก่อนหน้า
              </a>
              <a
                v-if="students.next_page_url"
                :href="students.next_page_url"
                class="relative inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors"
              >
                ถัดไป
                <i class="fas fa-chevron-right ml-1"></i>
              </a>
            </div>
          </div>   
        </div>  

      </div>
      
      <!-- No Results -->
      <div v-else-if="searchQuery || selectedClassroom" class="bg-white rounded-lg shadow-sm border border-gray-200 text-center py-8 sm:py-12 mx-4 sm:mx-0">
        <i class="fas fa-search text-3xl sm:text-4xl text-gray-400 mb-3 sm:mb-4"></i>
        <h3 class="text-base sm:text-lg font-medium text-gray-900 mb-2">ไม่พบนักเรียน</h3>
        <p class="text-sm sm:text-base text-gray-500 px-4">ลองค้นหาด้วยรหัสประจำตัวนักเรียนอื่นหรือเปลี่ยนตัวกรองชั้นเรียน</p>
      </div>

      <!-- Initial State -->
      <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200 text-center py-8 sm:py-12 mx-4 sm:mx-0">
        <i class="fas fa-users text-3xl sm:text-4xl text-gray-400 mb-3 sm:mb-4"></i>
        <h3 class="text-base sm:text-lg font-medium text-gray-900 mb-2">ค้นหานักเรียน</h3>
        <p class="text-sm sm:text-base text-gray-500 px-4">กรอกรหัสประจำตัวนักเรียนเพื่อเริ่มค้นหา</p>
      </div>
    </div>

    <!-- Create Home Visit Modal -->
    <div v-if="showCreateVisitModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" @click="closeCreateVisitModal">
          <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
              สร้างการเยี่ยมบ้าน
            </h3>
            <div class="mb-4 p-3 bg-gray-50 rounded-lg">
              <div class="text-sm space-y-1">
                <div class="flex">
                  <span class="text-gray-500 font-medium w-16">ชื่อ:</span>
                  <span class="font-semibold text-gray-900">{{ selectedStudent?.first_name_th }}</span>
                </div>
                <div class="flex">
                  <span class="text-gray-500 font-medium w-16">นามสกุล:</span>
                  <span class="font-semibold text-gray-900">{{ selectedStudent?.last_name_th }}</span>
                </div>
              </div>
            </div>
            
            <form @submit.prevent="submitCreateVisit" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">
                  วันที่เยี่ยม
                </label>
                <input
                  v-model="visitForm.visit_date"
                  type="date"
                  required
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                >
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">
                  โซน <span class="text-gray-500 text-xs">(ไม่บังคับ)</span>
                </label>
                <select
                  v-model="visitForm.zone_id"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                >
                  <option value="">ไม่ระบุโซน</option>
                  <option 
                    v-for="zone in zones" 
                    :key="zone.id" 
                    :value="zone.id"
                  >
                    {{ zone.zone_name }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">
                  หัวข้อการเยี่ยม
                </label>
                <input
                  v-model="visitForm.visit_purpose"
                  type="text"
                  required
                  placeholder="เช่น ติดตามผลการเรียน, เยี่ยมบ้าน"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                >
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">
                  รายละเอียด
                </label>
                <textarea
                  v-model="visitForm.visit_notes"
                  rows="3"
                  placeholder="รายละเอียดเพิ่มเติม"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                ></textarea>
              </div>

              <div class="flex justify-end space-x-3 pt-4">
                <button
                  type="button"
                  @click="closeCreateVisitModal"
                  class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                >
                  ยกเลิก
                </button>
                <button
                  type="submit"
                  :disabled="visitForm.processing"
                  class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 disabled:opacity-50"
                >
                  <span v-if="visitForm.processing">กำลังบันทึก...</span>
                  <span v-else>บันทึกการเยี่ยม</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { formatTimeAgo, formatDateThai, getCurrentDate } from '@/utils/dateUtils'
import StudentCard from './Components/StudentCard.vue'
// Import Shared HomeVisit Components
import {
  StudentsCard,
  AcademicInfoCard,
  AddressCard,
  ContactCard,
  HealthInfoCard,
  GuardianCard
} from '../Components'
import HomeVisitCard from './Components/HomeVisitCard.vue'

const props = defineProps({
  stats: Object,
  students: Object,
  classrooms: Array,
  zones: {
    type: Array,
    default: () => []
  },
  filters: Object
})

// === Reactive State ===
const searchQuery = ref(props.filters?.search || '')
const selectedClassroom = ref(props.filters?.classroom || '')
const showCreateVisitModal = ref(false)
const selectedStudent = ref(null)
const isLoading = ref(false)

const homeVisits = ref([])

// === Forms ===
const visitForm = useForm({
  visit_date: '',
  visit_time: '',
  notes: '',
  zone_id: '',
  images: []
})

const editForm = useForm({
  title_prefix_th: '',
  first_name_th: '',
  last_name_th: '',
  nickname: '',
  citizen_id: '',
  class_level: '',
  class_section: '',
  academic_info: null,
  contacts: []
})

// === Search & Navigation Functions ===
const searchStudents = () => {
  isLoading.value = true
  router.get(route('homevisit.teacher.search.students'), {
    search: searchQuery.value,
    classroom: selectedClassroom.value
  }, {
    preserveState: true,
    onStart: () => {
      isLoading.value = true
    },
    onSuccess: (page) => {
      // Auto-select student if only one result
      if (page.props.students.data?.length === 1) {
        selectStudent(page.props.students.data[0])
      }
      isLoading.value = false
    },
    onError: () => {
      isLoading.value = false
    },
    onFinish: () => {
      isLoading.value = false
    }
  })
}

const selectStudent = (student) => {
  selectedStudent.value = student
  // Populate edit form
  editForm.title_prefix_th = student.title_prefix_th || ''
  editForm.first_name_th = student.first_name_th || ''
  editForm.last_name_th = student.last_name_th || ''
  editForm.nickname = student.nickname || ''
  editForm.citizen_id = student.citizen_id || ''
  editForm.class_level = student.class_level || ''
  editForm.class_section = student.class_section || ''
  editForm.academic_info = student.academic_info || {}
  editForm.contacts = student.contacts || []
  // Set homeVisits for this student
  if (student.home_visits) {
    homeVisits.value = student.home_visits
  } else {
    homeVisits.value = []
  }
}

    const viewStudent = (student) => {
      selectStudent(student)
    }

    const createHomeVisit = (student) => {
      selectedStudent.value = student
      visitForm.visit_date = getCurrentDate()
      showCreateVisitModal.value = true
    }

    const closeCreateVisitModal = () => {
      showCreateVisitModal.value = false
      selectedStudent.value = null
      visitForm.reset()
    }

    const submitCreateVisit = () => {
      if (!selectedStudent.value) return

      visitForm.post(route('homevisit.teacher.create.home.visit', selectedStudent.value.id), {
        onSuccess: () => {
          closeCreateVisitModal()
          searchStudents() // Refresh the list
        }
      })
    }

    const updateStudent = () => {
      if (!selectedStudent.value) return

      editForm.put(route('homevisit.teacher.update.student', selectedStudent.value.id), {
        onSuccess: () => {
          // Refresh selected student data
          searchStudents()
        }
      })
    }

    const createNewHomeVisit = () => {
      if (!selectedStudent.value) return

      const formData = new FormData()
      formData.append('visit_date', visitForm.visit_date)
      formData.append('visit_time', visitForm.visit_time)
      formData.append('observations', visitForm.observations || '')
      formData.append('notes', visitForm.notes || '')
      
      // Add zone_id if selected
      if (visitForm.zone_id) {
        formData.append('zone_id', visitForm.zone_id)
      }
      
      // Add participants
      if (visitForm.participants && visitForm.participants.length > 0) {
        visitForm.participants.forEach((participant, index) => {
          formData.append(`participants[${index}][name]`, participant.name)
        })
      }
      
      // Add images
      if (visitForm.images) {
        visitForm.images.forEach((image, index) => {
          formData.append(`images[${index}]`, image.file)
        })
      }

      router.post(route('homevisit.teacher.create.home.visit', selectedStudent.value.id), formData, {
        forceFormData: true,
        onSuccess: () => {
          visitForm.reset()
          visitForm.images = []
          visitForm.participants = []
          searchStudents() // Refresh data
        }
      })
    }

    const handleImageUpload = async (event) => {
      const files = Array.from(event.target.files)
      
      for (const file of files) {
        // Compress image before adding to form
        const compressedFile = await compressImage(file)
        
        const reader = new FileReader()
        reader.onload = (e) => {
          visitForm.images.push({
            file: compressedFile,
            preview: e.target.result
          })
        }
        reader.readAsDataURL(compressedFile)
      }
    }
    
    // Compress image to reduce file size
    const compressImage = (file, maxWidth = 1200, quality = 0.8) => {
      return new Promise((resolve) => {
        const reader = new FileReader()
        reader.onload = (e) => {
          const img = new Image()
          img.onload = () => {
            const canvas = document.createElement('canvas')
            let width = img.width
            let height = img.height
            
            // Resize if image is too large
            if (width > maxWidth) {
              height = (height * maxWidth) / width
              width = maxWidth
            }
            
            canvas.width = width
            canvas.height = height
            
            const ctx = canvas.getContext('2d')
            ctx.drawImage(img, 0, 0, width, height)
            
            // Convert to blob with compression
            canvas.toBlob(
              (blob) => {
                const compressedFile = new File([blob], file.name, {
                  type: 'image/jpeg',
                  lastModified: Date.now()
                })
                resolve(compressedFile)
              },
              'image/jpeg',
              quality
            )
          }
          img.src = e.target.result
        }
        reader.readAsDataURL(file)
      })
    }

    const removeImage = (index) => {
      visitForm.images.splice(index, 1)
    }

    const resetForm = () => {
      selectedStudent.value = null
      editForm.reset()
      visitForm.reset()
      visitForm.images = []
    }

    const clearSelectedStudent = () => {
      selectedStudent.value = null
      editForm.reset()
      searchQuery.value = ''
      selectedClassroom.value = ''
      isLoading.value = false
      // Navigate back to main dashboard view
      router.get(route('homevisit.teacher.dashboard'), {}, {
        preserveState: false
      })
    }

    const viewVisitImages = (visit) => {
      // TODO: Implement image gallery modal
      alert(`ดูรูปภาพของการเยี่ยม ID: ${visit.id}`)
    }

    // ใช้ formatTimeAgo จาก date utilities แทน

    // Event handlers for student component saves
    const handleStudentSave = (message) => {
      console.log('Student saved:', message)
      // Refresh data if needed
      searchStudents()
    }

    const handleStudentUpdate = (data) => {
      console.log('Student updated:', data)
      // Update local selectedStudent data
      if (selectedStudent.value) {
        Object.assign(selectedStudent.value, data)
      }
    }

    const handleAcademicSave = (message) => {
      console.log('Academic info saved:', message)
      searchStudents()
    }

    const handleAcademicUpdate = (data) => {
      console.log('Academic info updated:', data)
      if (selectedStudent.value) {
        selectedStudent.value.academic_info = data
      }
    }

    const handleAddressSave = (message) => {
      console.log('Address saved:', message)
    }

    const handleAddressUpdate = (data) => {
      console.log('Address updated:', data)
      if (selectedStudent.value) {
        selectedStudent.value.addresses = data
      }
    }

    const handleContactSave = (message) => {
      console.log('Contact saved:', message)
    }

    const handleContactUpdate = (data) => {
      console.log('Contact updated:', data)
      if (selectedStudent.value) {
        selectedStudent.value.contacts = data
      }
    }

    const handleHealthSave = (message) => {
      console.log('Health info saved:', message)
    }

    const handleHealthUpdate = (data) => {
      console.log('Health info updated:', data)
      if (selectedStudent.value) {
        selectedStudent.value.health_info = data
      }
    }

    const handleGuardianSave = (message) => {
      console.log('Guardian saved:', message)
    }

    const handleGuardianUpdate = (data) => {
      console.log('Guardian updated:', data)
      if (selectedStudent.value) {
        selectedStudent.value.guardians = data
      }
    }

    const logout = () => {
      router.post(route('homevisit.general.logout'))
    }

    // ใช้ formatDateThai จาก date utilities แทน
    const formatDate = (dateString) => {
      if (!dateString) return 'ไม่ระบุ'
      return formatDateThai(dateString, { format: 'short' })
    }

    const getEducationLevelText = (level) => {
      const levelMap = {
        1: 'ประถมศึกษา',
        2: 'มัธยมศึกษาตอนต้น',
        3: 'มัธยมศึกษาตอนปลาย',
        4: 'อุดมศึกษا'
      }
      return levelMap[level] || `ระดับ ${level}`
    }

    const getFullName = (student) => {
      return `${student.title_prefix_th || ''} ${student.first_name_th} ${student.last_name_th}`.trim()
    }


    function onVisitUpdated(updatedVisit) {
      // หา index ของการเยี่ยมบ้านที่ถูกอัปเดต
      const index = homeVisits.value.findIndex(visit => visit.id === updatedVisit.id);
      
      // ถ้าเจอ ให้อัปเดตข้อมูลใน array
      if (index !== -1) {
        // ใช้ spread operator เพื่อคง reactive ไว้และอัปเดตข้อมูล
        homeVisits.value[index] = { ...homeVisits.value[index], ...updatedVisit };
      }
    }
</script>

