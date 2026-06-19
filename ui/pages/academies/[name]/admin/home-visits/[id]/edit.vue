<script setup lang="ts">
/**
 * Academy Admin - Edit Home Visit
 * หน้าแก้ไขข้อมูลการเยี่ยมบ้าน
 */
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: false
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)
const visitId = computed(() => route.params.id as string)

// State
const academy = ref<any>(null)
const visit = ref<any>(null)
const zones = ref<any[]>([])
const isLoading = ref(true)
const isSaving = ref(false)
const errors = ref<Record<string, string[]>>({})

// Form
const form = ref({
  zone_id: '',
  visit_date: '',
  visit_time: '',
  purpose: '',
  address: '',
  observations: '',
  recommendations: '',
  status: 'pending'
})

// Academy Role
const academyId = ref<number | null>(null)
const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()
      
      if (!isAdmin.value && !can('home_visits.manage')) {
        navigateTo(`/academies/${academyName.value}`)
        return
      }
      
      await Promise.all([fetchVisit(), fetchZones()])
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

const fetchVisit = async () => {
  try {
    const response: any = await api.get(`/api/home-visit/admin/visits/${visitId.value}`)
    visit.value = response.visit || response.data
    
    // Populate form
    form.value = {
      zone_id: visit.value.zone_id || '',
      visit_date: visit.value.visit_date?.split('T')[0] || '',
      visit_time: visit.value.visit_time || '',
      purpose: visit.value.purpose || '',
      address: visit.value.address || '',
      observations: visit.value.observations || '',
      recommendations: visit.value.recommendations || '',
      status: visit.value.status || 'pending'
    }
  } catch (err) {
    console.error('Failed to fetch visit:', err)
  }
}

const fetchZones = async () => {
  try {
    const response: any = await api.get('/api/home-visit/zones')
    zones.value = response.zones || response.data || []
  } catch (err) {
    console.error('Failed to fetch zones:', err)
  }
}

const validateForm = () => {
  errors.value = {}
  
  if (!form.value.visit_date) {
    errors.value.visit_date = ['กรุณาระบุวันที่เยี่ยมบ้าน']
  }
  
  return Object.keys(errors.value).length === 0
}

const saveVisit = async () => {
  if (!validateForm()) return
  
  isSaving.value = true
  errors.value = {}
  
  try {
    await api.put(`/api/home-visit/admin/visits/${visitId.value}`, form.value)
    navigateTo(`/academies/${academyName.value}/admin/home-visits/${visitId.value}`)
  } catch (err: any) {
    if (err.errors) {
      errors.value = err.errors
    }
    console.error('Failed to save:', err)
  } finally {
    isSaving.value = false
  }
}

const purposes = [
  'เยี่ยมบ้านตามกำหนด',
  'ติดตามนักเรียนขาดเรียน',
  'ปัญหาพฤติกรรม',
  'ปัญหาการเรียน',
  'เยี่ยมนักเรียนป่วย',
  'ประสานงานผู้ปกครอง',
  'อื่นๆ'
]

const getStatusBadge = (status: string) => {
  const badges: Record<string, { class: string, label: string }> = {
    'completed': { class: 'bg-green-100 text-green-800', label: 'เสร็จสิ้น' },
    'pending': { class: 'bg-yellow-100 text-yellow-800', label: 'รอดำเนินการ' },
    'cancelled': { class: 'bg-red-100 text-red-800', label: 'ยกเลิก' }
  }
  return badges[status] || badges['pending']
}
</script>

<template>
  <div>
    <div v-if="isLoading" class="flex items-center justify-center py-12">
      <Icon name="fluent:spinner-ios-20-regular" class="w-8 h-8 animate-spin text-primary-600" />
    </div>

    <div v-else-if="visit" class="max-w-4xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex items-center gap-4">
        <NuxtLink 
          :to="`/academies/${academyName}/admin/home-visits/${visitId}`"
          class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
        >
          <Icon name="fluent:arrow-left-24-regular" class="w-5 h-5" />
        </NuxtLink>
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">แก้ไขการเยี่ยมบ้าน</h1>
          <p class="text-gray-600 dark:text-gray-400">
            {{ visit.student?.full_name || visit.student_name }}
          </p>
        </div>
      </div>

      <!-- Student Info (Read-only) -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
          <Icon name="fluent:person-24-regular" class="w-5 h-5 text-primary-600" />
          นักเรียน
        </h2>
        
        <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
          <img 
            :src="visit.student?.avatar || '/images/default-avatar.png'" 
            class="w-14 h-14 rounded-full object-cover" 
          />
          <div class="flex-1">
            <p class="font-medium text-gray-900 dark:text-white">
              {{ visit.student?.full_name || visit.student_name }}
            </p>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              {{ visit.student?.student_number }} • ม.{{ visit.student?.class_level }}/{{ visit.student?.class_section }}
            </p>
          </div>
          <span class="text-sm text-gray-500 dark:text-gray-400">
            (ไม่สามารถเปลี่ยนนักเรียนได้)
          </span>
        </div>
      </div>

      <!-- Form -->
      <form @submit.prevent="saveVisit" class="space-y-6">
        <!-- Visit Details -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <Icon name="fluent:calendar-24-regular" class="w-5 h-5 text-primary-600" />
            รายละเอียดการเยี่ยมบ้าน
          </h2>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                วันที่เยี่ยมบ้าน <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.visit_date"
                type="date"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                :class="errors.visit_date ? 'border-red-500' : ''"
              />
              <p v-if="errors.visit_date" class="text-red-500 text-sm mt-1">{{ errors.visit_date[0] }}</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เวลา</label>
              <input
                v-model="form.visit_time"
                type="time"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">โซน</label>
              <select
                v-model="form.zone_id"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              >
                <option value="">เลือกโซน</option>
                <option v-for="zone in zones" :key="zone.id" :value="zone.id">
                  {{ zone.name }}
                </option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วัตถุประสงค์</label>
              <select
                v-model="form.purpose"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              >
                <option value="">เลือกวัตถุประสงค์</option>
                <option v-for="purpose in purposes" :key="purpose" :value="purpose">
                  {{ purpose }}
                </option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สถานะ</label>
              <select
                v-model="form.status"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              >
                <option value="pending">รอดำเนินการ</option>
                <option value="completed">เสร็จสิ้น</option>
                <option value="cancelled">ยกเลิก</option>
              </select>
            </div>
          </div>
          
          <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ที่อยู่บ้าน</label>
            <textarea
              v-model="form.address"
              rows="2"
              placeholder="ที่อยู่บ้านนักเรียน"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            ></textarea>
          </div>
        </div>

        <!-- Observations -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <Icon name="fluent:notepad-24-regular" class="w-5 h-5 text-primary-600" />
            บันทึกการเยี่ยมบ้าน
          </h2>
          
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                สิ่งที่พบ / ข้อสังเกต
              </label>
              <textarea
                v-model="form.observations"
                rows="4"
                placeholder="บันทึกสิ่งที่พบเห็นจากการเยี่ยมบ้าน..."
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              ></textarea>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                ข้อเสนอแนะ
              </label>
              <textarea
                v-model="form.recommendations"
                rows="3"
                placeholder="ข้อเสนอแนะสำหรับการดูแลนักเรียน..."
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              ></textarea>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3">
          <NuxtLink
            :to="`/academies/${academyName}/admin/home-visits/${visitId}`"
            class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
          >
            ยกเลิก
          </NuxtLink>
          <button
            type="submit"
            :disabled="isSaving"
            class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 flex items-center gap-2"
          >
            <Icon v-if="isSaving" name="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
            <Icon v-else name="fluent:save-24-regular" class="w-5 h-5" />
            <span>{{ isSaving ? 'กำลังบันทึก...' : 'บันทึก' }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
