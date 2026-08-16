<script setup lang="ts">
/**
 * Parent-Teacher Meeting Booking Page
 * สำหรับผู้ปกครองจองเวลานัดพบครู
 */

import { ref, onMounted, computed } from 'vue'
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'main',
  middleware: ['auth']
})

const route = useRoute()
const api = useApi()
const schoolApi = useSchoolManagement()

const academyName = computed(() => route.params.name as string)
const academyId = ref<number | null>(null)
const academy = ref<any>(null)

// State
const children = ref<any[]>([])
const teachers = ref<any[]>([])
const selectedTeacherId = ref<number | null>(null)
const availableSlots = ref<any[]>([])
const myBookings = ref<any[]>([])
const isLoading = ref(true)
const isBooking = ref(false)

// Booking Form
const bookingForm = ref({
  student_id: null as number | null,
  purpose: '',
  notes: '',
})

// Modal State
const showBookingModal = ref(false)
const selectedSlot = ref<any>(null)

// Load initial data
onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      
      await Promise.all([
        loadChildren(),
        loadTeachers(),
        loadMyBookings()
      ])
    }
  } catch (err) {
    console.error('Failed to load data:', err)
  } finally {
    isLoading.value = false
  }
})

const loadChildren = async () => {
  if (!academyId.value) return
  const response: any = await api.get(`/api/academies/${academyId.value}/parent/children`)
  if (response.success) {
    children.value = response.children || []
    if (children.value.length > 0) {
      bookingForm.value.student_id = children.value[0].id
    }
  }
}

const loadTeachers = async () => {
  if (!academyId.value) return
  // Fetch staff with teacher role
  const response: any = await schoolApi.getStaffList(academyId.value, { role: 'teacher' })
  if (response.success) {
    teachers.value = response.data || []
  }
}

const loadMyBookings = async () => {
  if (!academyId.value) return
  const response: any = await schoolApi.getMyMeetingBookings(academyId.value)
  if (response.success) {
    myBookings.value = response.data || []
  }
}

const fetchSlots = async () => {
  if (!academyId.value || !selectedTeacherId.value) return
  isLoading.value = true
  try {
    const response: any = await schoolApi.getAvailableSlots(academyId.value, { 
      teacher_id: selectedTeacherId.value,
      available_only: 1
    })
    if (response.success) {
      availableSlots.value = response.data || []
    }
  } catch (err) {
    console.error('Failed to fetch slots:', err)
  } finally {
    isLoading.value = false
  }
}

const openBookingModal = (slot: any) => {
  selectedSlot.value = slot
  showBookingModal.value = true
}

const submitBooking = async () => {
  if (!academyId.value || !selectedSlot.value || !bookingForm.value.student_id) return
  
  isBooking.value = true
  try {
    const response: any = await schoolApi.bookMeeting(
      academyId.value, 
      selectedSlot.value.id, 
      bookingForm.value
    )
    
    if (response.success) {
      showBookingModal.value = false
      selectedSlot.value = null
      bookingForm.value.purpose = ''
      bookingForm.value.notes = ''
      
      // Refresh data
      await Promise.all([
        fetchSlots(),
        loadMyBookings()
      ])
      
      // Show success notification (logic to be added if toast system exists)
      alert('จองเวลานัดพบเรียบร้อยแล้ว กรุณารอครูยืนยันการนัดหมาย')
    }
  } catch (err: any) {
    alert(err.response?._data?.message || 'เกิดข้อผิดพลาดในการจอง')
  } finally {
    isBooking.value = false
  }
}

const cancelBooking = async (bookingId: number) => {
  if (!confirm('คุณต้องการยกเลิกการนัดหมายนี้ใช่หรือไม่?')) return
  
  try {
    const response: any = await schoolApi.cancelBooking(academyId.value!, bookingId)
    if (response.success) {
      await loadMyBookings()
    }
  } catch (err) {
    console.error('Failed to cancel booking:', err)
  }
}

// Helpers
const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('th-TH', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

const getStatusClass = (status: string) => {
  switch (status) {
    case 'pending': return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
    case 'confirmed': return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
    case 'cancelled': return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
    case 'completed': return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
    default: return 'bg-gray-100 text-gray-700'
  }
}

const getStatusLabel = (status: string) => {
  switch (status) {
    case 'pending': return 'รอการยืนยัน'
    case 'confirmed': return 'ยืนยันแล้ว'
    case 'cancelled': return 'ยกเลิกแล้ว'
    case 'completed': return 'เสร็จสิ้นแล้ว'
    default: return status
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 pb-20">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 sticky top-0 z-10 shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <NuxtLink 
            :to="`/academies/${academyName}/parent`"
            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full transition-colors"
          >
            <Icon icon="fluent:arrow-left-24-regular" class="w-6 h-6 text-gray-500" />
          </NuxtLink>
          <h1 class="text-xl font-bold text-gray-900 dark:text-white">นัดพบครูผู้สอน</h1>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left: Booking Section -->
        <div class="lg:col-span-2 space-y-8">
          <!-- Step 1: Select Teacher -->
          <section class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
              <span class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm">1</span>
              เลือกครูที่คุณต้องการนัดพบ
            </h2>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div 
                v-for="teacher in teachers" 
                :key="teacher.id"
                @click="selectedTeacherId = teacher.user_id; fetchSlots()"
                :class="[
                  'p-4 rounded-2xl border-2 transition-all cursor-pointer flex items-center gap-4',
                  selectedTeacherId === teacher.user_id 
                    ? 'border-blue-600 bg-blue-50 dark:bg-blue-900/20' 
                    : 'border-gray-100 dark:border-gray-700 hover:border-blue-200'
                ]"
              >
                <CircleAvatar :src="teacher.user?.profile_photo_path" :name="teacher.user?.name" size="md" />
                <div class="flex-1 min-w-0">
                  <p class="font-bold text-gray-900 dark:text-white truncate">{{ teacher.user?.name }}</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ teacher.position || 'ครูผู้สอน' }}</p>
                </div>
                <div v-if="selectedTeacherId === teacher.user_id" class="text-blue-600">
                  <Icon icon="fluent:checkmark-circle-24-filled" class="w-6 h-6" />
                </div>
              </div>
            </div>
            
            <div v-if="teachers.length === 0" class="text-center py-10 text-gray-400">
              <Icon icon="fluent:person-prohibited-24-regular" class="w-12 h-12 mx-auto mb-2 opacity-20" />
              <p>ยังไม่มีข้อมูลครูในระบบ</p>
            </div>
          </section>

          <!-- Step 2: Select Slot -->
          <section v-if="selectedTeacherId" class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
              <span class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm">2</span>
              เลือกช่วงเวลาที่ว่าง
            </h2>

            <div v-if="isLoading" class="flex justify-center py-10">
              <div class="animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent"></div>
            </div>

            <div v-else-if="availableSlots.length === 0" class="text-center py-12 bg-gray-50 dark:bg-gray-900/30 rounded-2xl">
              <Icon icon="fluent:calendar-cancel-24-regular" class="w-16 h-16 mx-auto text-gray-300 mb-2 opacity-50" />
              <p class="text-gray-500">ครูยังไม่ได้เปิดตารางนัดหมายในช่วงนี้</p>
            </div>

            <div v-else class="space-y-6">
              <!-- Group by Date would be nice, but for now simple list -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div 
                  v-for="slot in availableSlots" 
                  :key="slot.id"
                  class="p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl hover:shadow-md transition-all group"
                >
                  <div class="flex justify-between items-start mb-3">
                    <div>
                      <div class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-1">
                        {{ new Date(slot.meeting_date).toLocaleDateString('th-TH', { weekday: 'short', day: 'numeric', month: 'short' }) }}
                      </div>
                      <div class="text-xl font-black text-gray-900 dark:text-white">
                        {{ slot.start_time.substring(0, 5) }} - {{ slot.end_time.substring(0, 5) }}
                      </div>
                    </div>
                    <div class="p-2 bg-gray-50 dark:bg-gray-900 rounded-xl group-hover:bg-blue-600 group-hover:text-white transition-colors">
                      <Icon :icon="slot.meeting_type === 'video_call' ? 'fluent:video-24-regular' : 'fluent:location-24-regular'" class="w-6 h-6" />
                    </div>
                  </div>
                  
                  <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-50 dark:border-gray-700">
                    <div class="text-[10px] font-black uppercase text-gray-400 tracking-tighter">
                      {{ slot.available_spots }} ที่ว่าง
                    </div>
                    <button 
                      @click="openBookingModal(slot)"
                      class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-bold shadow-lg shadow-blue-200 dark:shadow-none hover:bg-blue-700 active:scale-95 transition-all"
                    >
                      จองเวลานี้
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </section>
        </div>

        <!-- Right: My Bookings -->
        <div class="space-y-6">
          <h2 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
            <Icon icon="fluent:calendar-clock-24-regular" class="w-6 h-6 text-purple-600" />
            คิวที่นัดไว้แล้ว
          </h2>

          <div v-if="myBookings.length === 0" class="bg-white dark:bg-gray-800 rounded-3xl p-8 border border-gray-100 dark:border-gray-700 text-center text-gray-400 shadow-sm">
            <p>ยังไม่มีรายการนัดหมาย</p>
          </div>

          <div v-else class="space-y-4">
            <div 
              v-for="booking in myBookings" 
              :key="booking.id"
              class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 overflow-hidden shadow-sm hover:shadow-md transition-shadow"
            >
              <div class="p-5">
                <div class="flex items-center justify-between mb-4">
                  <span :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider', getStatusClass(booking.status)]">
                    {{ getStatusLabel(booking.status) }}
                  </span>
                  <button 
                    v-if="['pending', 'confirmed'].includes(booking.status)"
                    @click="cancelBooking(booking.id)"
                    class="p-1.5 hover:bg-red-50 text-gray-300 hover:text-red-500 rounded-full transition-colors"
                  >
                    <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
                  </button>
                </div>
                
                <div class="flex items-center gap-3 mb-4">
                  <CircleAvatar :src="booking.slot?.teacher?.profile_photo_path" :name="booking.slot?.teacher?.name" size="sm" />
                  <div>
                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ booking.slot?.teacher?.name }}</p>
                    <p class="text-[10px] text-gray-500">นัดพบเรื่อง: {{ booking.student?.name }}</p>
                  </div>
                </div>

                <div class="space-y-3 bg-gray-50 dark:bg-gray-900/50 p-4 rounded-2xl">
                  <div class="flex items-center gap-3 text-sm">
                    <Icon icon="fluent:calendar-ltr-24-regular" class="w-5 h-5 text-blue-600" />
                    <span class="font-bold text-gray-700 dark:text-gray-300">{{ new Date(booking.slot?.meeting_date).toLocaleDateString('th-TH') }}</span>
                  </div>
                  <div class="flex items-center gap-3 text-sm">
                    <Icon icon="fluent:clock-24-regular" class="w-5 h-5 text-blue-600" />
                    <span class="font-bold text-gray-700 dark:text-gray-300">{{ booking.slot?.start_time.substring(0, 5) }} น.</span>
                  </div>
                  <div class="flex items-center gap-3 text-sm">
                    <Icon :icon="booking.slot?.meeting_type === 'video_call' ? 'fluent:video-24-regular' : 'fluent:location-24-regular'" class="w-5 h-5 text-blue-600" />
                    <span class="text-gray-600 dark:text-gray-400">{{ booking.slot?.location || 'ออนไลน์' }}</span>
                  </div>
                </div>

                <div v-if="booking.purpose" class="mt-4">
                  <p class="text-[10px] font-black uppercase text-gray-400 mb-1 tracking-widest">หัวข้อสนทนา</p>
                  <p class="text-sm text-gray-600 dark:text-gray-300 italic">"{{ booking.purpose }}"</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Booking Modal -->
    <div v-if="showBookingModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-in fade-in duration-300">
      <div class="bg-white dark:bg-gray-800 rounded-[2rem] shadow-2xl w-full max-w-lg overflow-hidden animate-in zoom-in-95 duration-300">
        <div class="p-8">
          <div class="flex items-center justify-between mb-8">
            <h3 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">ยืนยันการนัดพบ</h3>
            <button @click="showBookingModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full transition-colors">
              <Icon icon="fluent:dismiss-24-regular" class="w-6 h-6 text-gray-400" />
            </button>
          </div>

          <div class="bg-blue-50 dark:bg-blue-900/20 p-6 rounded-3xl mb-8">
            <div class="flex items-center gap-4 mb-4">
              <CircleAvatar :src="selectedSlot?.teacher?.profile_photo_path" :name="selectedSlot?.teacher?.name" size="sm" />
              <div>
                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ selectedSlot?.teacher?.name }}</p>
                <p class="text-xs text-blue-600 dark:text-blue-400">จะดำเนินการนัดหมายในเวลานี้</p>
              </div>
            </div>
            <div class="flex items-center gap-6">
              <div>
                <p class="text-[10px] font-black uppercase text-gray-400 tracking-widest mb-1">วันที่</p>
                <p class="font-bold text-gray-800 dark:text-gray-200">{{ new Date(selectedSlot?.meeting_date).toLocaleDateString('th-TH', { day: 'numeric', month: 'short' }) }}</p>
              </div>
              <div class="w-px h-8 bg-blue-100 dark:bg-blue-800"></div>
              <div>
                <p class="text-[10px] font-black uppercase text-gray-400 tracking-widest mb-1">เวลา</p>
                <p class="font-bold text-gray-800 dark:text-gray-200">{{ selectedSlot?.start_time.substring(0, 5) }} - {{ selectedSlot?.end_time.substring(0, 5) }}</p>
              </div>
            </div>
          </div>

          <form @submit.prevent="submitBooking" class="space-y-6">
            <div>
              <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">เรื่องเกี่ยวกับบุตรหลาน</label>
              <select 
                v-model="bookingForm.student_id"
                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-700 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none dark:text-white"
                required
              >
                <option v-for="child in children" :key="child.id" :value="child.id">{{ child.name }}</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">หัวข้อที่ต้องการปรึกษา</label>
              <input 
                v-model="bookingForm.purpose"
                type="text"
                placeholder="เช่น การเรียน, พฤติกรรม, กิจกรรมโรงเรียน"
                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-700 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none dark:text-white"
                required
              />
            </div>

            <div>
              <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">หมายเหตุเพิ่มเติม (ถ้ามี)</label>
              <textarea 
                v-model="bookingForm.notes"
                rows="3"
                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-700 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none dark:text-white resize-none"
              ></textarea>
            </div>

            <div class="pt-4 flex gap-4">
              <button 
                type="button"
                @click="showBookingModal = false"
                class="flex-1 px-6 py-4 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-2xl font-bold hover:bg-gray-200 transition-all"
              >
                ยกเลิก
              </button>
              <button 
                type="submit"
                :disabled="isBooking"
                class="flex-[2] px-6 py-4 bg-blue-600 text-white rounded-2xl font-bold shadow-xl shadow-blue-200 dark:shadow-none hover:bg-blue-700 active:scale-95 transition-all disabled:opacity-50 flex items-center justify-center gap-2"
              >
                <Icon v-if="isBooking" icon="fluent:spinner-24-regular" class="w-5 h-5 animate-spin" />
                <span>{{ isBooking ? 'กำลังจอง...' : 'ยืนยันการจอง' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
