<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Navigation Header -->
    <nav class="bg-white shadow-sm border-b">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <div class="flex-shrink-0 flex items-center">
              <button 
                @click="goBack"
                class="text-gray-500 hover:text-gray-700 mr-4"
              >
                <i class="fas fa-arrow-left"></i>
              </button>
              <span class="text-2xl">👨‍🎓</span>
              <h1 class="ml-2 text-xl font-semibold text-gray-900">
                จัดการข้อมูลนักเรียน
              </h1>
            </div>
          </div>
          <div class="flex items-center space-x-4">
            <span class="text-sm text-gray-600">
              สวัสดี, ครู
            </span>
            <button
              @click="logout"
              class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium"
            >
              ออกจากระบบ
            </button>
          </div>
        </div>
      </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <!-- Student Profile Header -->
      <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <div class="px-4 py-5 sm:px-6 bg-gradient-to-r from-indigo-500 to-purple-600">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div v-if="student.profile_image" class="w-20 h-20 rounded-full overflow-hidden border-4 border-white">
                <img :src="getProfileImagePath(student)" :alt="student.first_name_th" class="w-full h-full object-cover">
              </div>
              <div v-else class="w-20 h-20 bg-white rounded-full flex items-center justify-center border-4 border-white">
                <span class="text-indigo-600 font-bold text-2xl">
                  {{ student.first_name_th?.charAt(0) || 'N' }}
                </span>
              </div>
            </div>
            <div class="ml-6">
              <h3 class="text-2xl leading-8 font-bold text-white">
                {{ student.title_prefix_th || '' }} {{ student.first_name_th }} {{ student.last_name_th }}
              </h3>
              <div v-if="student.nickname" class="mt-1">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white/20 text-white">
                  <i class="fas fa-star mr-1"></i>
                  {{ student.nickname }}
                </span>
              </div>
              <p class="mt-2 text-indigo-100">
                รหัสนักเรียน: {{ student.student_id }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Student Information -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
          <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
              <i class="fas fa-user mr-2"></i>
              ข้อมูลส่วนตัว
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
              ข้อมูลพื้นฐานของนักเรียน
            </p>
          </div>
          <div class="border-t border-gray-200">
            <dl>
              <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">ชื่อเต็ม</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  {{ student.title_prefix_th }} {{ student.first_name_th }} {{ student.last_name_th }}
                </dd>
              </div>
              <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">ชื่อเล่น</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  {{ student.nickname || 'ไม่ระบุ' }}
                </dd>
              </div>
              <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">รหัสนักเรียน</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  {{ student.student_id }}
                </dd>
              </div>
              <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">เลขบัตรประชาชน</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  {{ student.citizen_id || 'ไม่ระบุ' }}
                </dd>
              </div>
              <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">วันเกิด</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  {{ formatDate(student.date_of_birth) }}
                </dd>
              </div>
              <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">เพศ</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  {{ getGenderText(student.gender) }}
                </dd>
              </div>
            </dl>
          </div>
        </div>

        <!-- Academic Information -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
          <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
              <i class="fas fa-graduation-cap mr-2"></i>
              ข้อมูลการศึกษา
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
              ข้อมูลด้านการศึกษาและโรงเรียน
            </p>
          </div>
          <div class="border-t border-gray-200" v-if="student.academic_info">
            <dl>
              <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">ชั้นเรียนปัจจุบัน</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  {{ student.academic_info.current_class || 'ไม่ระบุ' }}
                </dd>
              </div>
              <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">โรงเรียน</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  {{ student.academic_info.school_name || 'ไม่ระบุ' }}
                </dd>
              </div>
              <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">ระดับการศึกษา</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  {{ getEducationLevelText(student.academic_info.education_level) }}
                </dd>
              </div>
              <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">วันที่เข้าเรียน</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  {{ formatDate(student.enrollment_date) }}
                </dd>
              </div>
            </dl>
          </div>
        </div>
      </div>

      <!-- Contact Information -->
      <div class="mt-6 bg-white shadow overflow-hidden sm:rounded-lg" v-if="student.contacts && student.contacts.length > 0">
        <div class="px-4 py-5 sm:px-6">
          <h3 class="text-lg leading-6 font-medium text-gray-900">
            <i class="fas fa-phone mr-2"></i>
            ข้อมูลติดต่อ
          </h3>
          <p class="mt-1 max-w-2xl text-sm text-gray-500">
            ช่องทางติดต่อของนักเรียน
          </p>
        </div>
        <div class="border-t border-gray-200">
          <ul class="divide-y divide-gray-200">
            <li v-for="contact in student.contacts" :key="contact.id" class="px-4 py-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <i :class="getContactIcon(contact.contact_type)" class="text-gray-400"></i>
                  </div>
                  <div class="ml-4">
                    <p class="text-sm font-medium text-gray-900">
                      {{ getContactTypeText(contact.contact_type) }}
                    </p>
                    <p class="text-sm text-gray-500">
                      {{ contact.contact_value }}
                    </p>
                  </div>
                </div>
              </div>
            </li>
          </ul>
        </div>
      </div>

      <!-- Home Visit History -->
      <div class="mt-6 bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg leading-6 font-medium text-gray-900">
                <i class="fas fa-home mr-2"></i>
                ประวัติการเยี่ยมบ้าน
              </h3>
              <p class="mt-1 max-w-2xl text-sm text-gray-500">
                รายการการเยี่ยมบ้านทั้งหมด
              </p>
            </div>
            <button
              @click="createHomeVisit"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
            >
              <i class="fas fa-plus mr-2"></i>
              เพิ่มการเยี่ยมบ้าน
            </button>
          </div>
        </div>
        <div class="border-t border-gray-200">
          <div v-if="student.home_visits && student.home_visits.length > 0">
            <ul class="divide-y divide-gray-200">
              <li v-for="visit in student.home_visits" :key="visit.id" class="px-4 py-4 hover:bg-gray-50">
                <div class="flex items-center justify-between">
                  <div class="flex-1">
                    <div class="flex items-center justify-between">
                      <p class="text-sm font-medium text-gray-900">
                        {{ visit.visit_purpose || 'การเยี่ยมบ้าน' }}
                      </p>
                      <p class="text-sm text-gray-500">
                        {{ formatDate(visit.visit_date) }}
                      </p>
                    </div>
                    <p class="mt-1 text-sm text-gray-600">
                      {{ visit.visit_notes || 'ไม่มีรายละเอียด' }}
                    </p>
                    <p class="mt-1 text-xs text-gray-400">
                      โดย: {{ visit.teacher_name || 'ครู' }}
                    </p>
                  </div>
                </div>
              </li>
            </ul>
          </div>
          <div v-else class="text-center py-12">
            <i class="fas fa-home text-4xl text-gray-400 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">ยังไม่มีการเยี่ยมบ้าน</h3>
            <p class="text-gray-500">เริ่มสร้างการเยี่ยมบ้านครั้งแรกสำหรับนักเรียนคนนี้</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

export default {
  props: {
    student: Object
  },

  setup(props) {
    const goBack = () => {
      router.visit(route('homevisit.teacher.dashboard'))
    }

    const logout = () => {
      router.post(route('homevisit.logout'))
    }

    const createHomeVisit = () => {
      // This could open a modal or navigate to a form
      router.visit(route('homevisit.teacher.create.home.visit', props.student.id))
    }

    const formatDate = (dateString) => {
      if (!dateString) return 'ไม่ระบุ'
      const date = new Date(dateString)
      return date.toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    }

    const getGenderText = (gender) => {
      const genderMap = {
        1: 'ชาย',
        2: 'หญิง',
        'male': 'ชาย',
        'female': 'หญิง'
      }
      return genderMap[gender] || 'ไม่ระบุ'
    }

    const getEducationLevelText = (level) => {
      const levelMap = {
        1: 'ประถมศึกษา',
        2: 'มัธยมศึกษาตอนต้น',
        3: 'มัธยมศึกษาตอนปลาย',
        4: 'อุดมศึกษา'
      }
      return levelMap[level] || 'ไม่ระบุ'
    }

    const getContactTypeText = (type) => {
      const typeMap = {
        'phone': 'เบอร์โทรศัพท์',
        'mobile': 'มือถือ',
        'email': 'อีเมล',
        'line': 'LINE ID',
        'facebook': 'Facebook'
      }
      return typeMap[type] || type
    }

    const getContactIcon = (type) => {
      const iconMap = {
        'phone': 'fas fa-phone',
        'mobile': 'fas fa-mobile-alt',
        'email': 'fas fa-envelope',
        'line': 'fab fa-line',
        'facebook': 'fab fa-facebook'
      }
      return iconMap[type] || 'fas fa-address-book'
    }

    const getProfileImagePath = (student) => {
      return student.profile_image_url || null
    }

    return {
      goBack,
      logout,
      createHomeVisit,
      formatDate,
      getGenderText,
      getEducationLevelText,
      getContactTypeText,
      getContactIcon,
      getProfileImagePath
    }
  }
}
</script>

