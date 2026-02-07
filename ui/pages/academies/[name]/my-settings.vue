<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

const route = useRoute()
const router = useRouter()
const api = useApi()
const swal = useSweetAlert()

const academyName = computed(() => route.params.name as string)

// State
const academy = ref<any>(null)
const myMembership = ref<any>(null)
const isLoading = ref(true)
const isSaving = ref(false)
const isLeavingAcademy = ref(false)

// Settings form
const notificationSettings = ref({
  newCourse: true,
  newAnnouncement: true,
  gradeUpdate: true,
  emailNotifications: false
})

// Fetch academy and membership data
const fetchData = async () => {
  isLoading.value = true
  try {
    const response = await api.get(`/api/academies/${academyName.value}`) as any
    academy.value = response.academy || response.data || response
    
    // Use academy data for membership info (simplified - no separate API needed)
    myMembership.value = {
      enrolled_courses: academy.value.enrolled_courses_count || 0,
      completed_courses: academy.value.completed_courses_count || 0,
      certificates: academy.value.certificates_count || 0,
      joined_at: academy.value.joined_at || null
    }
  } catch (err: any) {
    console.error('Failed to fetch data:', err)
    swal.error('ไม่สามารถโหลดข้อมูลได้')
  } finally {
    isLoading.value = false
  }
}

// Save notification settings (placeholder - API not yet implemented)
const saveSettings = async () => {
  isSaving.value = true
  try {
    // TODO: Implement when API is ready
    // await api.put(`/api/academies/${academy.value.id}/my-settings`, {
    //   settings: notificationSettings.value
    // })
    swal.toast('บันทึกการตั้งค่าเรียบร้อย', 'success')
  } catch (err: any) {
    console.error('Failed to save settings:', err)
    swal.error('ไม่สามารถบันทึกการตั้งค่าได้')
  } finally {
    isSaving.value = false
  }
}

// Leave academy
const leaveAcademy = async () => {
  const result = await swal.confirm(
    'คุณแน่ใจหรือไม่ที่จะออกจากโรงเรียนนี้?\n\nการออกจากโรงเรียนจะ:\n• ยกเลิกการเข้าถึงรายวิชาทั้งหมด\n• ลบความคืบหน้าการเรียนของคุณ\n• ต้องสมัครใหม่หากต้องการกลับมา',
    'ยืนยันการออกจากโรงเรียน?'
  )
  
  if (!result) return
  
  isLeavingAcademy.value = true
  try {
    await api.post(`/api/academies/${academy.value.id}/unmembers`, {})
    swal.success('ออกจากโรงเรียนเรียบร้อยแล้ว', 'สำเร็จ')
    router.push(`/academies/${academyName.value}`)
  } catch (err: any) {
    console.error('Failed to leave academy:', err)
    swal.error(err.data?.message || 'ไม่สามารถออกจากโรงเรียนได้')
  } finally {
    isLeavingAcademy.value = false
  }
}

// Get status display
const statusDisplay = computed(() => {
  const status = academy.value?.memberStatus
  switch (status) {
    case 1: return { text: 'รอการอนุมัติ', color: 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900/30', icon: 'fluent:clock-24-regular' }
    case 2: return { text: 'สมาชิก', color: 'text-green-600 bg-green-100 dark:bg-green-900/30', icon: 'fluent:checkmark-circle-24-regular' }
    case 3: return { text: 'ถูกปฏิเสธ', color: 'text-red-600 bg-red-100 dark:bg-red-900/30', icon: 'fluent:dismiss-circle-24-regular' }
    case 4: return { text: 'ได้รับเชิญ', color: 'text-blue-600 bg-blue-100 dark:bg-blue-900/30', icon: 'fluent:mail-24-regular' }
    case 5: return { text: 'ถูกระงับ', color: 'text-gray-600 bg-gray-100 dark:bg-gray-900/30', icon: 'fluent:prohibited-24-regular' }
    default: return null
  }
})

const canLeave = computed(() => {
  return academy.value?.memberStatus === 2 // Only approved members can leave
})

onMounted(() => {
  fetchData()
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
      <div class="max-w-4xl mx-auto px-4 py-6">
        <div class="flex items-center gap-4">
          <NuxtLink 
            :to="`/academies/${academyName}`"
            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
          >
            <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5 text-gray-600 dark:text-gray-400" />
          </NuxtLink>
          <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">ตั้งค่าการเป็นสมาชิก</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ academy?.title || academyName }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="max-w-4xl mx-auto px-4 py-12">
      <div class="flex justify-center">
        <Icon icon="svg-spinners:ring-resize" class="w-8 h-8 text-blue-600" />
      </div>
    </div>

    <!-- Content -->
    <div v-else class="max-w-4xl mx-auto px-4 py-8 space-y-6">
      
      <!-- Membership Status Card -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
            <Icon icon="fluent:person-24-regular" class="w-5 h-5 text-blue-600" />
            สถานะการเป็นสมาชิก
          </h2>
        </div>
        <div class="p-6">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
              <img 
                :src="academy?.logo || '/images/default-academy.png'" 
                :alt="academy?.title"
                class="w-16 h-16 rounded-xl object-cover border border-gray-200 dark:border-gray-700"
              />
              <div>
                <h3 class="font-semibold text-gray-900 dark:text-white">{{ academy?.title }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ academy?.description }}</p>
              </div>
            </div>
            <div v-if="statusDisplay" :class="['px-4 py-2 rounded-full flex items-center gap-2', statusDisplay.color]">
              <Icon :icon="statusDisplay.icon" class="w-4 h-4" />
              <span class="font-medium text-sm">{{ statusDisplay.text }}</span>
            </div>
          </div>
          
          <!-- Membership info -->
          <div v-if="myMembership" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ myMembership.enrolled_courses || 0 }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">รายวิชาที่ลงทะเบียน</p>
            </div>
            <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ myMembership.completed_courses || 0 }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">รายวิชาที่สำเร็จ</p>
            </div>
            <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ myMembership.certificates || 0 }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">ใบประกาศนียบัตร</p>
            </div>
            <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
              <p class="text-sm font-medium text-gray-900 dark:text-white">{{ myMembership.joined_at || '-' }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">วันที่เข้าร่วม</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Notification Settings Card -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
            <Icon icon="fluent:alert-24-regular" class="w-5 h-5 text-blue-600" />
            การแจ้งเตือน
          </h2>
        </div>
        <div class="p-6 space-y-4">
          <!-- New Course -->
          <label class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
            <div class="flex items-center gap-3">
              <Icon icon="fluent:book-add-24-regular" class="w-5 h-5 text-blue-600" />
              <div>
                <p class="font-medium text-gray-900 dark:text-white">รายวิชาใหม่</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">แจ้งเตือนเมื่อมีรายวิชาใหม่เปิดสอน</p>
              </div>
            </div>
            <input type="checkbox" v-model="notificationSettings.newCourse" class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500" />
          </label>

          <!-- Announcements -->
          <label class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
            <div class="flex items-center gap-3">
              <Icon icon="fluent:megaphone-24-regular" class="w-5 h-5 text-orange-600" />
              <div>
                <p class="font-medium text-gray-900 dark:text-white">ประกาศจากโรงเรียน</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">แจ้งเตือนเมื่อมีประกาศใหม่</p>
              </div>
            </div>
            <input type="checkbox" v-model="notificationSettings.newAnnouncement" class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500" />
          </label>

          <!-- Grade Updates -->
          <label class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
            <div class="flex items-center gap-3">
              <Icon icon="fluent:trophy-24-regular" class="w-5 h-5 text-green-600" />
              <div>
                <p class="font-medium text-gray-900 dark:text-white">อัปเดตเกรด</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">แจ้งเตือนเมื่อมีการประกาศเกรด</p>
              </div>
            </div>
            <input type="checkbox" v-model="notificationSettings.gradeUpdate" class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500" />
          </label>

          <!-- Email Notifications -->
          <label class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
            <div class="flex items-center gap-3">
              <Icon icon="fluent:mail-24-regular" class="w-5 h-5 text-purple-600" />
              <div>
                <p class="font-medium text-gray-900 dark:text-white">แจ้งเตือนทางอีเมล</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">รับการแจ้งเตือนทางอีเมลด้วย</p>
              </div>
            </div>
            <input type="checkbox" v-model="notificationSettings.emailNotifications" class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500" />
          </label>

          <!-- Save Button -->
          <div class="pt-4">
            <button
              @click="saveSettings"
              :disabled="isSaving"
              class="w-full py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
            >
              <Icon v-if="isSaving" icon="svg-spinners:ring-resize" class="w-5 h-5" />
              <Icon v-else icon="fluent:save-24-regular" class="w-5 h-5" />
              {{ isSaving ? 'กำลังบันทึก...' : 'บันทึกการตั้งค่า' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Danger Zone -->
      <div v-if="canLeave" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-red-200 dark:border-red-900 overflow-hidden">
        <div class="px-6 py-4 border-b border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-900/20">
          <h2 class="text-lg font-semibold text-red-700 dark:text-red-400 flex items-center gap-2">
            <Icon icon="fluent:warning-24-regular" class="w-5 h-5" />
            โซนอันตราย
          </h2>
        </div>
        <div class="p-6">
          <div class="flex items-start gap-4">
            <div class="flex-1">
              <h3 class="font-semibold text-gray-900 dark:text-white">ออกจากโรงเรียน</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                เมื่อออกจากโรงเรียนแล้ว คุณจะไม่สามารถเข้าถึงรายวิชาและเนื้อหาต่างๆ ได้ 
                หากต้องการกลับมาเป็นสมาชิกอีกครั้ง คุณจะต้องสมัครใหม่และรอการอนุมัติ
              </p>
            </div>
            <button
              @click="leaveAcademy"
              :disabled="isLeavingAcademy"
              class="px-6 py-2.5 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 whitespace-nowrap"
            >
              <Icon v-if="isLeavingAcademy" icon="svg-spinners:ring-resize" class="w-4 h-4" />
              <Icon v-else icon="fluent:person-subtract-24-regular" class="w-4 h-4" />
              {{ isLeavingAcademy ? 'กำลังดำเนินการ...' : 'ออกจากโรงเรียน' }}
            </button>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>
