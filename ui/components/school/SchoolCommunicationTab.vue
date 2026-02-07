<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white">ระบบสื่อสาร</h2>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="section in sections"
          :key="section.id"
          @click="activeSection = section.id"
          :class="[
            activeSection === section.id
              ? 'bg-primary-500 text-white'
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300',
            'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors'
          ]"
        >
          {{ section.name }}
        </button>
      </div>
    </div>

    <!-- Announcements Section -->
    <div v-if="activeSection === 'announcements'" class="space-y-4">
      <div class="flex justify-between items-center">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">ประกาศ</h3>
        <button
          @click="showAnnouncementModal = true"
          class="inline-flex items-center gap-2 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors"
        >
          <Icon icon="heroicons:plus" class="h-5 w-5" />
          <span class="hidden sm:inline">เพิ่มประกาศ</span>
        </button>
      </div>

      <div v-if="loadingAnnouncements" class="flex justify-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
      </div>

      <div v-else class="space-y-4">
        <div
          v-for="announcement in announcements"
          :key="announcement.id"
          class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4"
        >
          <div class="flex items-start justify-between mb-3">
            <div class="flex items-start gap-3">
              <div :class="getPriorityIconClass(announcement.priority)" class="p-2 rounded-full">
                <Icon icon="heroicons:megaphone" class="h-5 w-5" />
              </div>
              <div>
                <h4 class="font-semibold text-gray-900 dark:text-white">{{ announcement.title }}</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  โดย {{ announcement.author?.name || 'ไม่ระบุ' }} • {{ formatDate(announcement.publish_date) }}
                </p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <span :class="getStatusBadgeClass(announcement.status)" class="px-2 py-1 text-xs rounded-full">
                {{ getStatusLabel(announcement.status) }}
              </span>
              <button @click="editAnnouncement(announcement)" class="text-gray-400 hover:text-gray-600">
                <Icon icon="heroicons:pencil" class="h-5 w-5" />
              </button>
            </div>
          </div>
          <div class="prose dark:prose-invert max-w-none text-sm text-gray-600 dark:text-gray-400 line-clamp-3" v-html="announcement.content"></div>
          <div v-if="announcement.target_audience" class="mt-3 flex items-center gap-2 text-sm text-gray-500">
            <Icon icon="heroicons:user-group" class="h-4 w-4" />
            <span>กลุ่มเป้าหมาย: {{ getAudienceLabel(announcement.target_audience) }}</span>
          </div>
        </div>

        <div v-if="announcements.length === 0" class="text-center py-12 text-gray-500">
          <Icon icon="heroicons:megaphone" class="h-12 w-12 mx-auto mb-3 text-gray-300" />
          <p>ยังไม่มีประกาศ</p>
        </div>
      </div>
    </div>

    <!-- Events Section -->
    <div v-if="activeSection === 'events'" class="space-y-4">
      <div class="flex justify-between items-center">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">กิจกรรม</h3>
        <button
          @click="showEventModal = true"
          class="inline-flex items-center gap-2 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors"
        >
          <Icon icon="heroicons:plus" class="h-5 w-5" />
          <span class="hidden sm:inline">เพิ่มกิจกรรม</span>
        </button>
      </div>

      <div v-if="loadingEvents" class="flex justify-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="event in events"
          :key="event.id"
          class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
        >
          <!-- Event Date Header -->
          <div class="bg-primary-500 text-white p-3 text-center">
            <div class="text-3xl font-bold">{{ getEventDay(event.start_date) }}</div>
            <div class="text-sm">{{ getEventMonth(event.start_date) }}</div>
          </div>
          
          <div class="p-4">
            <h4 class="font-semibold text-gray-900 dark:text-white mb-2">{{ event.title }}</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-3">{{ event.description }}</p>
            
            <div class="space-y-2 text-sm">
              <div class="flex items-center gap-2 text-gray-500">
                <Icon icon="heroicons:clock" class="h-4 w-4" />
                <span>{{ formatTime(event.start_date) }} - {{ formatTime(event.end_date) }}</span>
              </div>
              <div v-if="event.location" class="flex items-center gap-2 text-gray-500">
                <Icon icon="heroicons:map-pin" class="h-4 w-4" />
                <span>{{ event.location }}</span>
              </div>
            </div>

            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
              <span :class="getEventStatusClass(event.status)" class="px-2 py-1 text-xs rounded-full">
                {{ getEventStatusLabel(event.status) }}
              </span>
              <button @click="viewEventDetails(event)" class="text-primary-600 hover:underline text-sm">
                ดูรายละเอียด
              </button>
            </div>
          </div>
        </div>

        <div v-if="events.length === 0" class="col-span-full text-center py-12 text-gray-500">
          <Icon icon="heroicons:calendar" class="h-12 w-12 mx-auto mb-3 text-gray-300" />
          <p>ยังไม่มีกิจกรรม</p>
        </div>
      </div>
    </div>

    <!-- Meeting Slots Section -->
    <div v-if="activeSection === 'meetings'" class="space-y-4">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">ช่วงเวลานัดพบ</h3>
        <div class="flex gap-2">
          <input
            v-model="meetingDateFilter"
            type="date"
            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          />
          <button
            @click="showMeetingModal = true"
            class="inline-flex items-center gap-2 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors"
          >
            <Icon icon="heroicons:plus" class="h-5 w-5" />
            <span class="hidden sm:inline">เพิ่มช่วงเวลา</span>
          </button>
        </div>
      </div>

      <div v-if="loadingMeetings" class="flex justify-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
      </div>

      <div v-else>
        <!-- Group by date -->
        <div v-for="(slots, date) in groupedMeetingSlots" :key="date" class="mb-6">
          <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-3">{{ formatDateFull(date) }}</h4>
          <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
            <div
              v-for="slot in slots"
              :key="slot.id"
              :class="[
                slot.is_booked 
                  ? 'bg-gray-100 dark:bg-gray-700 border-gray-300' 
                  : 'bg-white dark:bg-gray-800 border-primary-200 hover:border-primary-400',
                'border rounded-lg p-3 text-center transition-colors cursor-pointer'
              ]"
              @click="!slot.is_booked && bookMeetingSlot(slot)"
            >
              <div class="font-medium text-gray-900 dark:text-white">{{ slot.start_time }} - {{ slot.end_time }}</div>
              <div v-if="slot.is_booked" class="text-xs text-gray-500 mt-1">
                <span class="flex items-center justify-center gap-1">
                  <Icon icon="heroicons:user" class="h-3 w-3" />
                  จองแล้ว
                </span>
              </div>
              <div v-else class="text-xs text-green-600 mt-1">ว่าง</div>
            </div>
          </div>
        </div>

        <div v-if="Object.keys(groupedMeetingSlots).length === 0" class="text-center py-12 text-gray-500">
          <Icon icon="heroicons:clock" class="h-12 w-12 mx-auto mb-3 text-gray-300" />
          <p>ยังไม่มีช่วงเวลานัดพบ</p>
        </div>
      </div>
    </div>

    <!-- Announcement Modal -->
    <SchoolModal v-model="showAnnouncementModal" title="เพิ่มประกาศ" size="lg">
      <form @submit.prevent="saveAnnouncement" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">หัวข้อ</label>
          <input v-model="announcementForm.title" type="text" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เนื้อหา</label>
          <textarea v-model="announcementForm.content" rows="5" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"></textarea>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ความสำคัญ</label>
            <select v-model="announcementForm.priority" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
              <option value="low">ต่ำ</option>
              <option value="normal">ปกติ</option>
              <option value="high">สูง</option>
              <option value="urgent">ด่วน</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">กลุ่มเป้าหมาย</label>
            <select v-model="announcementForm.target_audience" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
              <option value="all">ทุกคน</option>
              <option value="students">นักเรียน</option>
              <option value="parents">ผู้ปกครอง</option>
              <option value="teachers">ครู</option>
              <option value="staff">บุคลากร</option>
            </select>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันที่เผยแพร่</label>
            <input v-model="announcementForm.publish_date" type="date" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันหมดอายุ</label>
            <input v-model="announcementForm.expiry_date" type="date" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
          </div>
        </div>
        <div class="flex items-center gap-2">
          <input v-model="announcementForm.is_pinned" type="checkbox" id="is_pinned" class="rounded border-gray-300" />
          <label for="is_pinned" class="text-sm text-gray-700 dark:text-gray-300">ปักหมุดประกาศ</label>
        </div>
        <div class="flex justify-end gap-3 pt-4">
          <button type="button" @click="showAnnouncementModal = false" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
            ยกเลิก
          </button>
          <button type="submit" :disabled="savingAnnouncement" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 disabled:opacity-50">
            {{ savingAnnouncement ? 'กำลังบันทึก...' : 'บันทึก' }}
          </button>
        </div>
      </form>
    </SchoolModal>

    <!-- Event Modal -->
    <SchoolModal v-model="showEventModal" title="เพิ่มกิจกรรม" size="lg">
      <form @submit.prevent="saveEvent" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อกิจกรรม</label>
          <input v-model="eventForm.title" type="text" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">รายละเอียด</label>
          <textarea v-model="eventForm.description" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"></textarea>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันที่เริ่ม</label>
            <input v-model="eventForm.start_date" type="datetime-local" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันที่สิ้นสุด</label>
            <input v-model="eventForm.end_date" type="datetime-local" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สถานที่</label>
            <input v-model="eventForm.location" type="text" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภท</label>
            <select v-model="eventForm.event_type" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
              <option value="academic">วิชาการ</option>
              <option value="sports">กีฬา</option>
              <option value="cultural">วัฒนธรรม</option>
              <option value="holiday">วันหยุด</option>
              <option value="meeting">ประชุม</option>
              <option value="other">อื่นๆ</option>
            </select>
          </div>
        </div>
        <div class="flex justify-end gap-3 pt-4">
          <button type="button" @click="showEventModal = false" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
            ยกเลิก
          </button>
          <button type="submit" :disabled="savingEvent" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 disabled:opacity-50">
            {{ savingEvent ? 'กำลังบันทึก...' : 'บันทึก' }}
          </button>
        </div>
      </form>
    </SchoolModal>

    <!-- Meeting Slot Modal -->
    <SchoolModal v-model="showMeetingModal" title="เพิ่มช่วงเวลานัดพบ" size="md">
      <form @submit.prevent="saveMeetingSlot" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันที่</label>
          <input v-model="meetingForm.date" type="date" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เวลาเริ่ม</label>
            <input v-model="meetingForm.start_time" type="time" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เวลาสิ้นสุด</label>
            <input v-model="meetingForm.end_time" type="time" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">จำนวนช่วงเวลา</label>
          <input v-model.number="meetingForm.slots_count" type="number" min="1" max="20" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
          <p class="text-xs text-gray-500 mt-1">สร้างช่วงเวลาหลายช่วงติดต่อกัน</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ระยะเวลาต่อช่วง (นาที)</label>
          <select v-model.number="meetingForm.duration" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
            <option :value="15">15 นาที</option>
            <option :value="30">30 นาที</option>
            <option :value="45">45 นาที</option>
            <option :value="60">60 นาที</option>
          </select>
        </div>
        <div class="flex justify-end gap-3 pt-4">
          <button type="button" @click="showMeetingModal = false" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
            ยกเลิก
          </button>
          <button type="submit" :disabled="savingMeeting" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 disabled:opacity-50">
            {{ savingMeeting ? 'กำลังสร้าง...' : 'สร้าง' }}
          </button>
        </div>
      </form>
    </SchoolModal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps<{
  academyId: number
}>()

const schoolApi = useSchoolManagement()

// State
const activeSection = ref('announcements')
const sections = [
  { id: 'announcements', name: 'ประกาศ' },
  { id: 'events', name: 'กิจกรรม' },
  { id: 'meetings', name: 'นัดพบ' },
]

// Announcements
const announcements = ref<any[]>([])
const loadingAnnouncements = ref(false)
const showAnnouncementModal = ref(false)
const savingAnnouncement = ref(false)
const announcementForm = ref({
  title: '',
  content: '',
  priority: 'normal',
  target_audience: 'all',
  publish_date: new Date().toISOString().split('T')[0],
  expiry_date: '',
  is_pinned: false,
})

// Events
const events = ref<any[]>([])
const loadingEvents = ref(false)
const showEventModal = ref(false)
const savingEvent = ref(false)
const eventForm = ref({
  title: '',
  description: '',
  start_date: '',
  end_date: '',
  location: '',
  event_type: 'academic',
})

// Meeting Slots
const meetingSlots = ref<any[]>([])
const loadingMeetings = ref(false)
const showMeetingModal = ref(false)
const savingMeeting = ref(false)
const meetingDateFilter = ref(new Date().toISOString().split('T')[0])
const meetingForm = ref({
  date: new Date().toISOString().split('T')[0],
  start_time: '08:00',
  end_time: '08:30',
  slots_count: 1,
  duration: 30,
})

// Computed
const groupedMeetingSlots = computed(() => {
  const grouped: Record<string, any[]> = {}
  meetingSlots.value.forEach(slot => {
    const date = slot.date
    if (!grouped[date]) grouped[date] = []
    grouped[date].push(slot)
  })
  // Sort slots by time within each date
  Object.keys(grouped).forEach(date => {
    grouped[date].sort((a, b) => a.start_time.localeCompare(b.start_time))
  })
  return grouped
})

// Helpers
const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: 'numeric' })
}

const formatDateFull = (date: string) => {
  return new Date(date).toLocaleDateString('th-TH', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
}

const formatTime = (datetime: string) => {
  return new Date(datetime).toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' })
}

const getEventDay = (date: string) => {
  return new Date(date).getDate()
}

const getEventMonth = (date: string) => {
  return new Date(date).toLocaleDateString('th-TH', { month: 'short' })
}

const getPriorityIconClass = (priority: string) => {
  const classes: Record<string, string> = {
    low: 'bg-gray-100 text-gray-600',
    normal: 'bg-blue-100 text-blue-600',
    high: 'bg-orange-100 text-orange-600',
    urgent: 'bg-red-100 text-red-600',
  }
  return classes[priority] || 'bg-gray-100 text-gray-600'
}

const getStatusBadgeClass = (status: string) => {
  const classes: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800',
    published: 'bg-green-100 text-green-800',
    archived: 'bg-yellow-100 text-yellow-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    draft: 'ร่าง',
    published: 'เผยแพร่',
    archived: 'เก็บถาวร',
  }
  return labels[status] || status
}

const getAudienceLabel = (audience: string) => {
  const labels: Record<string, string> = {
    all: 'ทุกคน',
    students: 'นักเรียน',
    parents: 'ผู้ปกครอง',
    teachers: 'ครู',
    staff: 'บุคลากร',
  }
  return labels[audience] || audience
}

const getEventStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    upcoming: 'bg-blue-100 text-blue-800',
    ongoing: 'bg-green-100 text-green-800',
    completed: 'bg-gray-100 text-gray-800',
    cancelled: 'bg-red-100 text-red-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getEventStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    upcoming: 'กำลังจะมาถึง',
    ongoing: 'กำลังดำเนินการ',
    completed: 'เสร็จสิ้น',
    cancelled: 'ยกเลิก',
  }
  return labels[status] || status
}

// Helper to safely extract array data
const extractArray = (response: any): any[] => {
  if (!response) return []
  if (Array.isArray(response)) return response
  if (response.data && Array.isArray(response.data)) return response.data
  return []
}

// Load data
const loadAnnouncements = async () => {
  loadingAnnouncements.value = true
  try {
    const response = await schoolApi.getAnnouncements(props.academyId)
    announcements.value = extractArray(response)
  } catch (error) {
    console.error('Failed to load announcements:', error)
    announcements.value = []
  } finally {
    loadingAnnouncements.value = false
  }
}

const loadEvents = async () => {
  loadingEvents.value = true
  try {
    const response = await schoolApi.getEvents(props.academyId)
    events.value = extractArray(response)
  } catch (error) {
    console.error('Failed to load events:', error)
    events.value = []
  } finally {
    loadingEvents.value = false
  }
}

const loadMeetingSlots = async () => {
  loadingMeetings.value = true
  try {
    const response = await schoolApi.getMeetingSlots(props.academyId, { date: meetingDateFilter.value })
    meetingSlots.value = extractArray(response)
  } catch (error) {
    console.error('Failed to load meeting slots:', error)
    meetingSlots.value = []
  } finally {
    loadingMeetings.value = false
  }
}

// Actions
const editAnnouncement = (announcement: any) => {
  announcementForm.value = { ...announcement }
  showAnnouncementModal.value = true
}

const saveAnnouncement = async () => {
  savingAnnouncement.value = true
  try {
    await schoolApi.createAnnouncement(props.academyId, announcementForm.value)
    showAnnouncementModal.value = false
    announcementForm.value = {
      title: '',
      content: '',
      priority: 'normal',
      target_audience: 'all',
      publish_date: new Date().toISOString().split('T')[0],
      expiry_date: '',
      is_pinned: false,
    }
    await loadAnnouncements()
  } catch (error) {
    console.error('Failed to save announcement:', error)
  } finally {
    savingAnnouncement.value = false
  }
}

const viewEventDetails = (event: any) => {
  console.log('View event:', event)
}

const saveEvent = async () => {
  savingEvent.value = true
  try {
    await schoolApi.createEvent(props.academyId, eventForm.value)
    showEventModal.value = false
    eventForm.value = {
      title: '',
      description: '',
      start_date: '',
      end_date: '',
      location: '',
      event_type: 'academic',
    }
    await loadEvents()
  } catch (error) {
    console.error('Failed to save event:', error)
  } finally {
    savingEvent.value = false
  }
}

const saveMeetingSlot = async () => {
  savingMeeting.value = true
  try {
    await schoolApi.createMeetingSlot(props.academyId, meetingForm.value)
    showMeetingModal.value = false
    meetingForm.value = {
      date: new Date().toISOString().split('T')[0],
      start_time: '08:00',
      end_time: '08:30',
      slots_count: 1,
      duration: 30,
    }
    await loadMeetingSlots()
  } catch (error) {
    console.error('Failed to save meeting slot:', error)
  } finally {
    savingMeeting.value = false
  }
}

const bookMeetingSlot = async (slot: any) => {
  if (confirm(`จองช่วงเวลา ${slot.start_time} - ${slot.end_time} หรือไม่?`)) {
    try {
      await schoolApi.bookMeetingSlot(props.academyId, slot.id)
      await loadMeetingSlots()
    } catch (error) {
      console.error('Failed to book meeting slot:', error)
    }
  }
}

// Watch
watch(activeSection, (section) => {
  if (section === 'announcements' && announcements.value.length === 0) loadAnnouncements()
  if (section === 'events' && events.value.length === 0) loadEvents()
  if (section === 'meetings' && meetingSlots.value.length === 0) loadMeetingSlots()
}, { immediate: true })

watch(meetingDateFilter, () => loadMeetingSlots())

onMounted(() => {
  loadAnnouncements()
})
</script>
