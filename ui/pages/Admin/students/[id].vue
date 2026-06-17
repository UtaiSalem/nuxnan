<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useStudentMasterStore } from '~/stores/studentMaster'

definePageMeta({
  layout: 'admin',
  middleware: ['auth']
})

const route = useRoute()
const config = useRuntimeConfig()
const studentStore = useStudentMasterStore()

const studentId = route.params.id as string
const activeTab = ref('general')

const tabs = [
  { id: 'general', label: 'ข้อมูลพื้นฐาน', icon: 'fas fa-user' },
  { id: 'academic', label: 'ประวัติการศึกษา', icon: 'fas fa-graduation-cap' },
  { id: 'contact', label: 'ข้อมูลติดต่อและที่อยู่', icon: 'fas fa-address-book' },
  { id: 'family', label: 'ข้อมูลครอบครัว', icon: 'fas fa-users' },
  { id: 'health', label: 'ข้อมูลสุขภาพ', icon: 'fas fa-heartbeat' },
  { id: 'documents', label: 'เอกสารแนบ', icon: 'fas fa-file-alt' }
]

onMounted(async () => {
  if (studentId) {
    await studentStore.fetchMasterStudent(studentId)
  }
})

const formatDate = (dateString: string) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}
</script>

<template>
  <div class="admin-student-detail p-6 max-w-7xl mx-auto">
    <div class="flex items-center mb-6">
      <NuxtLink to="/admin/students" class="text-gray-500 hover:text-gray-700 mr-4">
        <i class="fas fa-arrow-left text-xl"></i>
      </NuxtLink>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">รายละเอียดนักเรียน</h1>
    </div>

    <div v-if="studentStore.isLoading" class="p-12 text-center bg-white dark:bg-gray-800 rounded-lg shadow">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto mb-4"></div>
      <p class="text-gray-500">กำลังโหลดข้อมูล...</p>
    </div>

    <div v-else-if="studentStore.error || !studentStore.currentStudent" class="p-12 text-center bg-white dark:bg-gray-800 rounded-lg shadow text-red-500">
      <i class="fas fa-exclamation-circle text-4xl mb-4"></i>
      <p class="text-lg">{{ studentStore.error || 'ไม่พบข้อมูลนักเรียน' }}</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- Left Column: Profile Summary -->
      <div class="md:col-span-1">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
          <div class="p-6 text-center border-b border-gray-200 dark:border-gray-700">
            <div class="relative inline-block mb-4">
              <img 
                class="h-32 w-32 rounded-full object-cover border-4 border-white shadow-lg mx-auto" 
                :src="studentStore.currentStudent.profile_image ? (config.public.apiBase + '/storage/' + studentStore.currentStudent.profile_image) : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(studentStore.currentStudent.first_name_th || 'S') + '&background=random&size=128'" 
                alt="Profile Profile" 
              />
              <span class="absolute bottom-0 right-0 block h-6 w-6 rounded-full ring-2 ring-white" :class="studentStore.currentStudent.status === 'active' || !studentStore.currentStudent.status ? 'bg-green-400' : 'bg-gray-400'"></span>
            </div>
            
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
              {{ studentStore.currentStudent.full_name_th }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
              {{ studentStore.currentStudent.full_name_en || 'No English Name' }}
            </p>
            <p class="text-sm font-medium text-primary-600 dark:text-primary-400 mt-2">
              รหัสนักเรียน: {{ studentStore.currentStudent.student_id || '-' }}
            </p>
            <p class="text-sm text-gray-500 mt-1">
              ชั้น {{ studentStore.currentStudent.class_level || '-' }}{{ studentStore.currentStudent.class_section ? '/' + studentStore.currentStudent.class_section : '' }}
            </p>
          </div>
          
          <div class="p-6">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">ข้อมูลเบื้องต้น</h3>
            <ul class="space-y-3">
              <li class="flex justify-between">
                <span class="text-sm text-gray-500">เลขบัตรประชาชน</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ studentStore.currentStudent.citizen_id || '-' }}</span>
              </li>
              <li class="flex justify-between">
                <span class="text-sm text-gray-500">ชื่อเล่น</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ studentStore.currentStudent.nickname || '-' }}</span>
              </li>
              <li class="flex justify-between">
                <span class="text-sm text-gray-500">วันเกิด</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ formatDate(studentStore.currentStudent.date_of_birth) }}</span>
              </li>
              <li class="flex justify-between">
                <span class="text-sm text-gray-500">เพศ</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ studentStore.currentStudent.gender_text || (studentStore.currentStudent.gender === 1 ? 'ชาย' : (studentStore.currentStudent.gender === 2 ? 'หญิง' : '-')) }}</span>
              </li>
              <li class="flex justify-between">
                <span class="text-sm text-gray-500">สัญชาติ / ศาสนา</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ studentStore.currentStudent.nationality || '-' }} / {{ studentStore.currentStudent.religion || '-' }}</span>
              </li>
            </ul>
            
            <div class="mt-6">
              <button class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                แก้ไขข้อมูลพื้นฐาน
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Tabbed Content -->
      <div class="md:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
          <!-- Tabs -->
          <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex -mb-px overflow-x-auto">
              <button 
                v-for="tab in tabs" 
                :key="tab.id"
                @click="activeTab = tab.id"
                class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors duration-200"
                :class="activeTab === tab.id ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
              >
                <i :class="tab.icon" class="mr-2"></i> {{ tab.label }}
              </button>
            </nav>
          </div>

          <!-- Tab Content -->
          <div class="p-6">
            
            <!-- General Tab -->
            <div v-if="activeTab === 'general'">
              <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">ข้อมูลการลงทะเบียน</h3>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-6">
                <div>
                  <dt class="text-sm font-medium text-gray-500">วันที่เข้าเรียน</dt>
                  <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatDate(studentStore.currentStudent.enrollment_date) }}</dd>
                </div>
                <div>
                  <dt class="text-sm font-medium text-gray-500">สถานะนักเรียน</dt>
                  <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full" 
                      :class="{
                        'bg-green-100 text-green-800': studentStore.currentStudent.status === 'active' || !studentStore.currentStudent.status,
                        'bg-gray-100 text-gray-800': studentStore.currentStudent.status === 'graduated',
                        'bg-red-100 text-red-800': studentStore.currentStudent.status === 'dropped_out'
                      }">
                      {{ studentStore.currentStudent.status === 'active' || !studentStore.currentStudent.status ? 'กำลังศึกษา' : (studentStore.currentStudent.status === 'graduated' ? 'จบการศึกษา' : 'ลาออก') }}
                    </span>
                  </dd>
                </div>
              </div>
              
              <div v-if="studentStore.currentStudent.card" class="mt-8">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">ข้อมูลบัตรนักเรียน (Legacy)</h3>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 grid grid-cols-2 gap-4">
                  <div>
                    <span class="block text-xs text-gray-500">รหัสจากบัตร</span>
                    <span class="block text-sm font-medium">{{ studentStore.currentStudent.card.student_number || '-' }}</span>
                  </div>
                  <div>
                    <span class="block text-xs text-gray-500">วันออกบัตร</span>
                    <span class="block text-sm font-medium">{{ formatDate(studentStore.currentStudent.card.card_issue_date) }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Academic Tab -->
            <div v-if="activeTab === 'academic'">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">ประวัติการศึกษา</h3>
                <button class="text-sm text-primary-600 hover:text-primary-700">
                  <i class="fas fa-plus"></i> เพิ่มประวัติ
                </button>
              </div>
              
              <div v-if="studentStore.currentStudent.academicInfos && studentStore.currentStudent.academicInfos.length > 0" class="space-y-4">
                <div v-for="info in studentStore.currentStudent.academicInfos" :key="info.id" class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 relative">
                  <span v-if="info.is_current" class="absolute top-4 right-4 bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full font-semibold">ปัจจุบัน</span>
                  <h4 class="font-medium text-gray-900 dark:text-white">ปีการศึกษา {{ info.academic_year }} เทอม {{ info.semester || '-' }}</h4>
                  <div class="mt-2 grid grid-cols-2 gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <p><strong>ระดับชั้น:</strong> {{ info.class_level }}{{ info.class_section ? '/' + info.class_section : '' }}</p>
                    <p><strong>แผนการเรียน:</strong> {{ info.study_program || '-' }}</p>
                    <p><strong>เกรดเฉลี่ย:</strong> {{ info.gpa || '-' }}</p>
                  </div>
                </div>
              </div>
              <p v-else class="text-gray-500 text-center py-4">ไม่มีข้อมูลประวัติการศึกษา</p>
            </div>

            <!-- Contact & Address Tab -->
            <div v-if="activeTab === 'contact'">
              <!-- Contacts -->
              <div class="mb-8">
                <div class="flex justify-between items-center mb-4">
                  <h3 class="text-lg font-medium text-gray-900 dark:text-white">ช่องทางการติดต่อ</h3>
                  <button class="text-sm text-primary-600 hover:text-primary-700"><i class="fas fa-plus"></i> เพิ่ม</button>
                </div>
                
                <ul v-if="studentStore.currentStudent.contacts && studentStore.currentStudent.contacts.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg">
                  <li v-for="contact in studentStore.currentStudent.contacts" :key="contact.id" class="p-4 flex items-center justify-between">
                    <div class="flex items-center">
                      <div class="flex-shrink-0 h-10 w-10 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                        <i :class="contact.contact_type === 'phone' ? 'fas fa-phone' : (contact.contact_type === 'email' ? 'fas fa-envelope' : 'fas fa-link')" class="text-gray-500"></i>
                      </div>
                      <div class="ml-4">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ contact.contact_value }}</p>
                        <p class="text-xs text-gray-500 uppercase">{{ contact.contact_type }}</p>
                      </div>
                    </div>
                    <span v-if="contact.is_primary" class="bg-primary-100 text-primary-800 text-xs px-2 py-1 rounded-full font-semibold">หลัก</span>
                  </li>
                </ul>
                <p v-else class="text-gray-500 text-center py-4 border border-dashed border-gray-300 rounded-lg">ไม่มีข้อมูลการติดต่อ</p>
              </div>

              <!-- Addresses -->
              <div>
                <div class="flex justify-between items-center mb-4">
                  <h3 class="text-lg font-medium text-gray-900 dark:text-white">ที่อยู่</h3>
                  <button class="text-sm text-primary-600 hover:text-primary-700"><i class="fas fa-plus"></i> เพิ่ม</button>
                </div>
                
                <div v-if="studentStore.currentStudent.addresses && studentStore.currentStudent.addresses.length > 0" class="space-y-4">
                  <div v-for="address in studentStore.currentStudent.addresses" :key="address.id" class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 relative">
                    <span v-if="address.is_current" class="absolute top-4 right-4 bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full font-semibold">ที่อยู่ปัจจุบัน</span>
                    <h4 class="font-medium text-gray-900 dark:text-white uppercase text-sm mb-2">{{ address.address_type === 'current' ? 'ที่อยู่ปัจจุบัน' : (address.address_type === 'permanent' ? 'ตามทะเบียนบ้าน' : address.address_type) }}</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                      {{ address.house_number }} 
                      <span v-if="address.village_number">หมู่ {{ address.village_number }}</span>
                      <span v-if="address.village_name">{{ address.village_name }}</span>
                      <span v-if="address.alley">ซอย {{ address.alley }}</span>
                      <span v-if="address.road">ถนน {{ address.road }}</span>
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                      ต.{{ address.subdistrict }} อ.{{ address.district }} จ.{{ address.province }} {{ address.postal_code }}
                    </p>
                  </div>
                </div>
                <p v-else class="text-gray-500 text-center py-4 border border-dashed border-gray-300 rounded-lg">ไม่มีข้อมูลที่อยู่</p>
              </div>
            </div>

            <!-- Family/Guardian Tab -->
            <div v-if="activeTab === 'family'">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">ผู้ปกครอง</h3>
                <button class="text-sm text-primary-600 hover:text-primary-700"><i class="fas fa-plus"></i> เพิ่ม</button>
              </div>
              
              <div v-if="studentStore.currentStudent.guardians && studentStore.currentStudent.guardians.length > 0" class="space-y-4">
                <div v-for="guardian in studentStore.currentStudent.guardians" :key="guardian.id" class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                  <div class="flex justify-between">
                    <div>
                      <h4 class="font-medium text-gray-900 dark:text-white">{{ guardian.title_prefix }}{{ guardian.first_name }} {{ guardian.last_name }}</h4>
                      <p class="text-sm text-gray-500">{{ guardian.relationship }}</p>
                    </div>
                    <div class="flex space-x-2">
                      <span v-if="guardian.is_primary_contact" class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full font-semibold">ผู้ติดต่อหลัก</span>
                      <span v-if="guardian.is_emergency_contact" class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-semibold">ติดต่อฉุกเฉิน</span>
                    </div>
                  </div>
                  <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <p><strong>อาชีพ:</strong> {{ guardian.occupation || '-' }}</p>
                    <p><strong>สถานที่ทำงาน:</strong> {{ guardian.workplace || '-' }}</p>
                  </div>
                  
                  <div v-if="guardian.contacts && guardian.contacts.length > 0" class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <h5 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">ข้อมูลติดต่อ</h5>
                    <ul class="space-y-1 text-sm">
                      <li v-for="contact in guardian.contacts" :key="contact.id">
                        <i :class="contact.contact_type === 'phone' ? 'fas fa-phone' : 'fas fa-envelope'" class="mr-2 text-gray-400"></i>
                        {{ contact.contact_value }}
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
              <p v-else class="text-gray-500 text-center py-4 border border-dashed border-gray-300 rounded-lg">ไม่มีข้อมูลผู้ปกครอง</p>
            </div>

            <!-- Health Tab -->
            <div v-if="activeTab === 'health'">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">ข้อมูลสุขภาพ</h3>
                <button class="text-sm text-primary-600 hover:text-primary-700"><i class="fas fa-edit"></i> แก้ไข</button>
              </div>
              
              <div v-if="studentStore.currentStudent.healthInfo" class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                <div class="grid grid-cols-2 gap-6 mb-6">
                  <div>
                    <span class="block text-sm font-medium text-gray-500">ส่วนสูง</span>
                    <span class="block text-lg font-semibold text-gray-900 dark:text-white">{{ studentStore.currentStudent.healthInfo.height || '-' }} <span class="text-sm font-normal text-gray-500">ซม.</span></span>
                  </div>
                  <div>
                    <span class="block text-sm font-medium text-gray-500">น้ำหนัก</span>
                    <span class="block text-lg font-semibold text-gray-900 dark:text-white">{{ studentStore.currentStudent.healthInfo.weight || '-' }} <span class="text-sm font-normal text-gray-500">กก.</span></span>
                  </div>
                  <div>
                    <span class="block text-sm font-medium text-gray-500">หมู่เลือด</span>
                    <span class="block text-lg font-semibold text-red-600">{{ studentStore.currentStudent.healthInfo.blood_type || '-' }}</span>
                  </div>
                </div>
                
                <div class="space-y-4 border-t border-gray-200 dark:border-gray-600 pt-4">
                  <div>
                    <span class="block text-sm font-medium text-gray-500">ประวัติการแพ้</span>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ studentStore.currentStudent.healthInfo.allergies || 'ไม่มี' }}</p>
                  </div>
                  <div>
                    <span class="block text-sm font-medium text-gray-500">โรคประจำตัว</span>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ studentStore.currentStudent.healthInfo.chronic_diseases || 'ไม่มี' }}</p>
                  </div>
                  <div>
                    <span class="block text-sm font-medium text-gray-500">หมายเหตุ</span>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ studentStore.currentStudent.healthInfo.health_notes || '-' }}</p>
                  </div>
                </div>
              </div>
              <p v-else class="text-gray-500 text-center py-4 border border-dashed border-gray-300 rounded-lg">ไม่มีข้อมูลสุขภาพ</p>
            </div>

            <!-- Documents Tab -->
            <div v-if="activeTab === 'documents'">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">เอกสารแนบ</h3>
                <button class="text-sm text-primary-600 hover:text-primary-700"><i class="fas fa-upload"></i> อัปโหลดเอกสาร</button>
              </div>
              
              <div v-if="studentStore.currentStudent.documents && studentStore.currentStudent.documents.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="doc in studentStore.currentStudent.documents" :key="doc.id" class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 flex flex-col items-center text-center hover:shadow-md transition cursor-pointer">
                  <i class="fas fa-file-pdf text-4xl text-red-500 mb-3"></i>
                  <span class="text-sm font-medium text-gray-900 dark:text-white truncate w-full">{{ doc.document_name || doc.document_type }}</span>
                  <span class="text-xs text-gray-500 mt-1">{{ formatDate(doc.created_at) }}</span>
                </div>
              </div>
              <p v-else class="text-gray-500 text-center py-12 border border-dashed border-gray-300 rounded-lg flex flex-col items-center">
                <i class="fas fa-folder-open text-4xl text-gray-300 mb-3"></i>
                ยังไม่มีเอกสารแนบ
              </p>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>
</template>
