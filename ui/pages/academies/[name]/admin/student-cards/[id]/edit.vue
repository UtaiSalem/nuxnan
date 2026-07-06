<script setup lang="ts">
/**
 * Academy Admin - Edit Student Card
 * หน้าแก้ไขข้อมูลบัตรนักเรียน
 */
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: false
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)
const studentId = computed(() => route.params.id as string)

// State
const academy = ref<any>(null)
const student = ref<any>(null)
const isLoading = ref(true)
const isSaving = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

// Form Data
const form = ref({
  student_number: '',
  national_id: '',
  title_name: '',
  first_name_thai: '',
  last_name_thai: '',
  first_name_english: '',
  last_name_english: '',
  class_level: '',
  class_section: '',
  birth_date: '',
  card_issue_date: '',
  card_expiry_date: '',
})

// Academy Role
const academyId = ref<number | null>(null)
const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)

// Photo Upload
const photoFile = ref<File | null>(null)
const photoPreview = ref<string | null>(null)

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()
      
      if (!isAdmin.value && !can('students.manage')) {
        navigateTo(`/academies/${academyName.value}`)
        return
      }
      
      await fetchStudent()
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

const fetchStudent = async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/student-cards/profile/${studentId.value}`)
    student.value = response.student
    
    // Populate form
    form.value = {
      student_number: response.student.student_number || '',
      national_id: response.student.national_id || '',
      title_name: response.student.title_name || '',
      first_name_thai: response.student.first_name_thai || '',
      last_name_thai: response.student.last_name_thai || '',
      first_name_english: response.student.first_name_english || '',
      last_name_english: response.student.last_name_english || '',
      class_level: response.student.class_level?.toString() || '',
      class_section: response.student.class_section?.toString() || '',
      birth_date: response.student.birth_date || '',
      card_issue_date: response.student.card_issue_date || '',
      card_expiry_date: response.student.card_expiry_date || '',
    }
  } catch (err) {
    console.error('Failed to fetch student:', err)
  }
}

const handlePhotoChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    photoFile.value = target.files[0]
    photoPreview.value = URL.createObjectURL(target.files[0])
  }
}

const saveStudent = async () => {
  isSaving.value = true
  successMessage.value = ''
  errorMessage.value = ''
  
  try {
    // Update student info
    await api.put(`/api/academies/${academyId.value}/student-cards/${studentId.value}`, form.value)
    
    // Upload photo if selected
    if (photoFile.value) {
      const formData = new FormData()
      formData.append('photo', photoFile.value)
      
      await api.post(`/api/academies/${academyId.value}/student-cards/admin/upload-photo/${studentId.value}`, formData)
    }
    
    successMessage.value = 'บันทึกข้อมูลสำเร็จ'
    
    setTimeout(() => {
      navigateTo(`/academies/${academyName.value}/admin/student-cards/${studentId.value}`)
    }, 1500)
  } catch (err: any) {
    errorMessage.value = err.message || 'เกิดข้อผิดพลาดในการบันทึก'
  } finally {
    isSaving.value = false
  }
}

const getProfileImage = () => {
  if (photoPreview.value) return photoPreview.value
  if (!student.value) return '/images/default-avatar.png'
  return student.value.profile_image_url || '/images/default-avatar.png'
}

const levels = ['1', '2', '3', '4', '5', '6']
const sections = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10']
</script>

<template>
  <div>
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="space-y-6">
      <!-- Header -->
      <div class="flex items-center gap-4">
        <NuxtLink 
          :to="`/academies/${academyName}/admin/student-cards/${studentId}`"
          class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
        >
          <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
        </NuxtLink>
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">แก้ไขข้อมูลนักเรียน</h1>
          <p class="text-gray-600 dark:text-gray-400">{{ student?.first_name_thai }} {{ student?.last_name_thai }}</p>
        </div>
      </div>

      <!-- Messages -->
      <div v-if="successMessage" class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 flex items-center gap-3">
        <Icon icon="fluent:checkmark-circle-24-filled" class="w-5 h-5 text-green-500" />
        <span class="text-green-700 dark:text-green-300">{{ successMessage }}</span>
      </div>
      
      <div v-if="errorMessage" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 flex items-center gap-3">
        <Icon icon="fluent:error-circle-24-filled" class="w-5 h-5 text-red-500" />
        <span class="text-red-700 dark:text-red-300">{{ errorMessage }}</span>
      </div>

      <!-- Form -->
      <form @submit.prevent="saveStudent" class="space-y-6">
        <!-- Photo Upload -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">รูปภาพ</h3>
          
          <div class="flex items-center gap-6">
            <div class="w-32 h-40 bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden">
              <img 
                :src="getProfileImage()"
                class="w-full h-full object-cover"
              />
            </div>
            
            <div>
              <label class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600">
                <Icon icon="fluent:arrow-upload-24-regular" class="w-5 h-5" />
                <span>เลือกรูปภาพ</span>
                <input 
                  type="file" 
                  accept="image/*" 
                  class="hidden" 
                  @change="handlePhotoChange"
                />
              </label>
              <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">รองรับ JPG, PNG ขนาดไม่เกิน 5MB</p>
            </div>
          </div>
        </div>

        <!-- Basic Info -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลพื้นฐาน</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">รหัสนักเรียน</label>
              <input
                v-model="form.student_number"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เลขประจำตัวประชาชน</label>
              <input
                v-model="form.national_id"
                type="text"
                maxlength="13"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำนำหน้า</label>
              <select
                v-model="form.title_name"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              >
                <option value="">เลือกคำนำหน้า</option>
                <option value="เด็กชาย">เด็กชาย</option>
                <option value="เด็กหญิง">เด็กหญิง</option>
                <option value="นาย">นาย</option>
                <option value="นางสาว">นางสาว</option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันเกิด</label>
              <input
                v-model="form.birth_date"
                type="date"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อ (ไทย)</label>
              <input
                v-model="form.first_name_thai"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">นามสกุล (ไทย)</label>
              <input
                v-model="form.last_name_thai"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อ (อังกฤษ)</label>
              <input
                v-model="form.first_name_english"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">นามสกุล (อังกฤษ)</label>
              <input
                v-model="form.last_name_english"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ระดับชั้น</label>
              <select
                v-model="form.class_level"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              >
                <option value="">เลือกระดับชั้น</option>
                <option v-for="level in levels" :key="level" :value="level">ม.{{ level }}</option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ห้อง</label>
              <select
                v-model="form.class_section"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              >
                <option value="">เลือกห้อง</option>
                <option v-for="section in sections" :key="section" :value="section">{{ section }}</option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันที่ออกบัตร</label>
              <input
                v-model="form.card_issue_date"
                type="date"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันหมดอายุบัตร</label>
              <input
                v-model="form.card_expiry_date"
                type="date"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              />
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3">
          <NuxtLink 
            :to="`/academies/${academyName}/admin/student-cards/${studentId}`"
            class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
          >
            ยกเลิก
          </NuxtLink>
          <button
            type="submit"
            :disabled="isSaving"
            class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 flex items-center gap-2"
          >
            <div v-if="isSaving" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
            <span>{{ isSaving ? 'กำลังบันทึก...' : 'บันทึก' }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
