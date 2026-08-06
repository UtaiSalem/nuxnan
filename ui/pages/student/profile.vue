<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useStudentMasterStore } from '~/stores/studentMaster'

definePageMeta({
  layout: 'default',
  middleware: ['auth']
})

const config = useRuntimeConfig()
const studentStore = useStudentMasterStore()

const activeTab = ref('general')
const isEditMode = ref(false)
const updateSuccess = ref(false)
const updateMessage = ref('')

// Form state
const form = ref({
  title_prefix_th: '',
  first_name_th: '',
  last_name_th: '',
  title_prefix_en: '',
  first_name_en: '',
  last_name_en: '',
  nickname: '',
  gender: '' as number | '',
  date_of_birth: '',
  nationality: '',
  religion: ''
})

const tabs = [
  { id: 'general', label: 'ข้อมูลพื้นฐาน', icon: 'fas fa-user' },
  { id: 'academic', label: 'ประวัติการศึกษา', icon: 'fas fa-graduation-cap' },
  { id: 'contact', label: 'ข้อมูลติดต่อและที่อยู่', icon: 'fas fa-address-book' },
  { id: 'family', label: 'ข้อมูลครอบครัว', icon: 'fas fa-users' },
  { id: 'health', label: 'ข้อมูลสุขภาพ', icon: 'fas fa-heartbeat' },
]

onMounted(async () => {
  await studentStore.fetchMyProfile()
  initForm()
})

const initForm = () => {
  if (studentStore.currentStudent) {
    const s = studentStore.currentStudent
    form.value = {
      title_prefix_th: s.title_prefix_th || '',
      first_name_th: s.first_name_th || '',
      last_name_th: s.last_name_th || '',
      title_prefix_en: s.title_prefix_en || '',
      first_name_en: s.first_name_en || '',
      last_name_en: s.last_name_en || '',
      nickname: s.nickname || '',
      gender: s.gender ?? '',
      date_of_birth: s.date_of_birth ? s.date_of_birth.split('T')[0] : '',
      nationality: s.nationality || '',
      religion: s.religion || ''
    }
  }
}

const submitUpdate = async () => {
  try {
    updateSuccess.value = false
    const res = await studentStore.updateProfile(form.value)
    updateSuccess.value = true
    updateMessage.value = res.message || 'อัปเดตข้อมูลสำเร็จ'
    isEditMode.value = false
    initForm() // Reset form with potential new/pending state
  } catch (e: any) {
    alert(e.message || 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล')
  }
}

const formatDate = (dateString: string) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const student = computed(() => studentStore.currentStudent)
</script>

<template>
  <div class="student-profile p-4 sm:p-6 max-w-5xl mx-auto">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">โปรไฟล์ของฉัน (My Profile)</h1>
      <p class="text-gray-500">จัดการข้อมูลประวัติส่วนตัวและข้อมูลที่เกี่ยวข้อง</p>
    </div>

    <div v-if="studentStore.isLoading && !student" class="p-12 text-center bg-white dark:bg-gray-800 rounded-lg shadow">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto mb-4"></div>
      <p class="text-gray-500">กำลังโหลดข้อมูล...</p>
    </div>

    <div v-else-if="studentStore.error || !student" class="p-12 text-center bg-white dark:bg-gray-800 rounded-lg shadow text-red-500">
      <i class="fas fa-exclamation-circle text-4xl mb-4"></i>
      <p class="text-lg">{{ studentStore.error || 'ไม่พบข้อมูลนักเรียน' }}</p>
    </div>

    <div v-else>
      <!-- Success Banner -->
      <div v-if="updateSuccess" class="mb-6 bg-green-50 border-l-4 border-green-400 p-4 rounded-md">
        <div class="flex">
          <div class="flex-shrink-0">
            <i class="fas fa-check-circle text-green-400"></i>
          </div>
          <div class="ml-3">
            <p class="text-sm text-green-700 font-medium">{{ updateMessage }}</p>
          </div>
          <div class="ml-auto pl-3">
            <div class="-mx-1.5 -my-1.5">
              <button @click="updateSuccess = false" class="inline-flex rounded-md p-1.5 text-green-500 hover:bg-green-100 focus:outline-none">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <!-- Header Profile -->
        <div class="bg-gradient-to-r from-primary-600 to-primary-800 px-6 py-8 sm:px-10 sm:py-12 relative">
          <div class="flex flex-col sm:flex-row items-center sm:items-end">
            <div class="relative mb-4 sm:mb-0 sm:mr-6">
              <img 
                class="h-32 w-32 rounded-full object-cover border-4 border-white shadow-lg" 
                :src="student.profile_image ? (config.public.apiBase + '/storage/' + student.profile_image) : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(student.first_name_th || 'S') + '&background=random&size=128'" 
                alt="Profile Profile" 
              />
            </div>
            <div class="text-center sm:text-left text-white">
              <h2 class="text-2xl sm:text-3xl font-bold">{{ student.full_name_th }}</h2>
              <p class="text-primary-100 text-lg">{{ student.student_id || '-' }}</p>
              <div class="mt-2 flex flex-wrap justify-center sm:justify-start gap-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-white bg-opacity-20 text-white">
                  ชั้น {{ student.class_level || '-' }}{{ student.class_section ? '/' + student.class_section : '' }}
                </span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-white bg-opacity-20 text-white">
                  {{ student.status === 'active' || !student.status ? 'กำลังศึกษา' : 'จบการศึกษา' }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-850">
          <nav class="flex overflow-x-auto" aria-label="Tabs">
            <button 
              v-for="tab in tabs" 
              :key="tab.id"
              @click="activeTab = tab.id; isEditMode = false;"
              class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors"
              :class="activeTab === tab.id ? 'border-primary-500 text-primary-600 dark:text-primary-400 bg-white dark:bg-gray-800' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:bg-gray-750'"
            >
              <i :class="tab.icon" class="mr-2"></i> {{ tab.label }}
            </button>
          </nav>
        </div>

        <!-- Content Area -->
        <div class="p-6 sm:p-8">
          
          <!-- General Info Tab -->
          <div v-if="activeTab === 'general'">
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-lg font-medium text-gray-900 dark:text-white">ข้อมูลพื้นฐานส่วนตัว</h3>
              <button v-if="!isEditMode" @click="isEditMode = true" class="text-sm px-3 py-1.5 border border-primary-500 text-primary-600 rounded-md hover:bg-primary-50 transition">
                <i class="fas fa-edit mr-1"></i> ขอแก้ไขข้อมูล
              </button>
            </div>

            <!-- View Mode -->
            <div v-if="!isEditMode" class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">เลขประจำตัวประชาชน</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ student.citizen_id || '-' }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ชื่อเล่น</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ student.nickname || '-' }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ชื่อภาษาอังกฤษ</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ student.full_name_en || '-' }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">วัน/เดือน/ปีเกิด</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatDate(student.date_of_birth) }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">เพศ</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ student.gender_text || (student.gender === 1 ? 'ชาย' : (student.gender === 0 ? 'หญิง' : '-')) }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">สัญชาติ</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ student.nationality || '-' }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ศาสนา</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ student.religion || '-' }}</dd>
              </div>
            </div>

            <!-- Edit Mode -->
            <form v-else @submit.prevent="submitUpdate" class="space-y-6">
              <div class="bg-yellow-50 dark:bg-yellow-900/30 p-4 rounded-md mb-6 border border-yellow-200 dark:border-yellow-800">
                <p class="text-sm text-yellow-800 dark:text-yellow-200">
                  <i class="fas fa-info-circle mr-2"></i> 
                  การแก้ไขข้อมูลบางส่วนอาจต้องรอการอนุมัติจากทางโรงเรียนก่อนจึงจะมีผลในระบบ
                </p>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">คำนำหน้า (ไทย)</label>
                  <input v-model="form.title_prefix_th" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ชื่อ (ไทย)</label>
                  <input v-model="form.first_name_th" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">นามสกุล (ไทย)</label>
                  <input v-model="form.last_name_th" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600">
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">คำนำหน้า (Eng)</label>
                  <input v-model="form.title_prefix_en" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ชื่อ (Eng)</label>
                  <input v-model="form.first_name_en" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">นามสกุล (Eng)</label>
                  <input v-model="form.last_name_en" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600">
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ชื่อเล่น</label>
                  <input v-model="form.nickname" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">วันเกิด</label>
                  <input v-model="form.date_of_birth" type="date" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">เพศ</label>
                  <select v-model="form.gender" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600">
                    <option value="">ไม่ระบุ</option>
                    <option :value="1">ชาย</option>
                    <option :value="0">หญิง</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">สัญชาติ</label>
                  <input v-model="form.nationality" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ศาสนา</label>
                  <input v-model="form.religion" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600">
                </div>
              </div>

              <div class="flex justify-end space-x-3 mt-6">
                <button type="button" @click="isEditMode = false; initForm()" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
                  ยกเลิก
                </button>
                <button type="submit" :disabled="studentStore.isLoading" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none flex items-center">
                  <span v-if="studentStore.isLoading" class="animate-spin mr-2 h-4 w-4 border-2 border-white border-t-transparent rounded-full"></span>
                  บันทึกการแก้ไข
                </button>
              </div>
            </form>
          </div>

          <!-- Other tabs placeholder logic (reuse from admin detail view) -->
          <div v-else class="text-center py-12 text-gray-500">
            <i class="fas fa-tools text-4xl mb-4 text-gray-300"></i>
            <p>อยู่ระหว่างการปรับปรุงให้รองรับโครงสร้างข้อมูลใหม่</p>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>
