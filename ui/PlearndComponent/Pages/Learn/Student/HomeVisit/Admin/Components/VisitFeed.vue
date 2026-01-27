<template>
  <div class="space-y-6">
    <!-- Header with gradient -->
    <div class="header-card bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500 shadow-xl rounded-2xl p-8 text-white relative overflow-hidden">
      <!-- Animated background pattern -->
      <div class="absolute inset-0 opacity-10">
        <div class="absolute -top-4 -right-4 w-32 h-32 bg-white rounded-full animate-blob"></div>
        <div class="absolute -bottom-8 -left-4 w-40 h-40 bg-white rounded-full animate-blob animation-delay-2000"></div>
        <div class="absolute top-1/2 left-1/2 w-36 h-36 bg-white rounded-full animate-blob animation-delay-4000"></div>
      </div>
      
      <div class="relative z-10 flex items-center justify-between">
        <div class="animate-fade-in-up">
          <h2 class="text-3xl font-bold flex items-center">
            <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center mr-3 animate-pulse-soft">
              <i class="fas fa-stream text-2xl"></i>
            </div>
            ฟีดการเยี่ยมบ้านนักเรียน
          </h2>
          <p class="mt-2 text-white/90 text-sm ml-15">ไทม์ไลน์แสดงความคืบหน้าการเยี่ยมบ้านทั้งหมดเรียงตามเวลาล่าสุด</p>
        </div>
        <div class="flex space-x-3 animate-fade-in">
          <button @click="refreshFeed" 
            class="group px-4 py-2.5 text-sm rounded-xl bg-white/20 backdrop-blur-md hover:bg-white/30 transition-all duration-300 border border-white/30 hover:scale-105 hover:shadow-lg">
            <i class="fas fa-sync-alt mr-2 group-hover:rotate-180 transition-transform duration-500"></i> 
            รีเฟรช
          </button>
        </div>
      </div>
    </div>

    <!-- Filters with modern design -->
    <div class="bg-white shadow-lg rounded-2xl p-6 border border-gray-100 animate-fade-in">
      <div class="flex items-center mb-4">
        <i class="fas fa-filter text-indigo-600 mr-2"></i>
        <h3 class="text-sm font-semibold text-gray-700">ตัวกรองข้อมูล</h3>
      </div>
      <div class="grid md:grid-cols-4 gap-4">
        <div class="filter-item">
          <label class="text-xs font-medium text-gray-700 mb-2 flex items-center">
            <i class="fas fa-tasks text-indigo-500 mr-1.5 text-xs"></i>
            สถานะ
          </label>
          <select v-model="filters.status" 
            class="w-full rounded-lg border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 hover:border-indigo-400">
            <option value="">ทั้งหมด</option>
            <option value="pending">รอดำเนินการ</option>
            <option value="in-progress">กำลังดำเนินการ</option>
            <option value="completed">เสร็จสิ้น</option>
            <option value="cancelled">ยกเลิก</option>
          </select>
        </div>
        <div class="filter-item">
          <label class="text-xs font-medium text-gray-700 mb-2 flex items-center">
            <i class="fas fa-map-marker-alt text-indigo-500 mr-1.5 text-xs"></i>
            โซน
          </label>
          <select v-model="filters.zone" 
            class="w-full rounded-lg border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 hover:border-indigo-400">
            <option value="">ทั้งหมด</option>
            <option v-for="z in zones" :key="z.id" :value="z.id">{{ z.name }}</option>
          </select>
        </div>
        <div class="filter-item">
          <label class="text-xs font-medium text-gray-700 mb-2 flex items-center">
            <i class="fas fa-calendar-alt text-indigo-500 mr-1.5 text-xs"></i>
            วันที่เริ่ม
          </label>
          <input type="date" v-model="filters.startDate" 
            class="w-full rounded-lg border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 hover:border-indigo-400" />
        </div>
        <div class="filter-item">
          <label class="text-xs font-medium text-gray-700 mb-2 flex items-center">
            <i class="fas fa-calendar-check text-indigo-500 mr-1.5 text-xs"></i>
            วันที่สิ้นสุด
          </label>
          <input type="date" v-model="filters.endDate" 
            class="w-full rounded-lg border-gray-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 hover:border-indigo-400" />
        </div>
      </div>
      <div class="mt-5 flex items-center justify-between pt-4 border-t border-gray-100">
        <button @click="clearFilters" 
          class="group text-xs px-4 py-2 rounded-lg bg-gradient-to-r from-gray-100 to-gray-200 text-gray-700 hover:from-gray-200 hover:to-gray-300 transition-all duration-200 font-medium hover:shadow-md">
          <i class="fas fa-redo-alt mr-1.5 group-hover:rotate-180 transition-transform duration-300"></i>
          ล้างตัวกรอง
        </button>
        <div class="flex items-center space-x-2">
          <div class="px-3 py-1.5 rounded-lg bg-indigo-50 border border-indigo-200">
            <span class="text-xs font-semibold text-indigo-700">
              <i class="fas fa-chart-bar mr-1"></i>
              {{ filteredVisits.length }} รายการ
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Timeline Feed -->
    <div class="space-y-8">
      <!-- Empty State with animation -->
      <div v-if="!filteredVisits.length" class="bg-white rounded-2xl shadow-lg p-16 text-center animate-fade-in">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full mb-6 animate-bounce-slow">
          <i class="fas fa-inbox text-3xl text-gray-400"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-700 mb-2">ไม่พบข้อมูล</h3>
        <p class="text-sm text-gray-500">ไม่พบข้อมูลการเยี่ยมบ้านตามเงื่อนไขที่เลือก</p>
      </div>

      <div v-for="(visit, index) in paginatedVisits" :key="visit.id" 
        class="relative timeline-item"
        :style="{ animationDelay: `${index * 100}ms` }">
        <!-- Vertical line with gradient -->
        <div v-if="index !== paginatedVisits.length - 1" 
          class="absolute left-6 top-16 w-0.5 h-full bg-gradient-to-b from-indigo-400 via-purple-400 to-transparent"></div>
        
        <div class="flex items-start">
          <!-- Icon circle with pulse animation -->
          <div class="flex flex-col items-center">
            <div :class="['w-12 h-12 rounded-full flex items-center justify-center shadow-lg text-white relative transition-all duration-300 hover:scale-110', statusColor(visit.visit_status)]">
              <div class="absolute inset-0 rounded-full animate-ping opacity-20" :class="statusColor(visit.visit_status)"></div>
              <i :class="[statusIcon(visit.visit_status), 'text-lg relative z-10']"></i>
            </div>
          </div>
          
          <!-- Card with hover effect -->
          <div class="ml-8 flex-1">
            <div class="visit-card bg-white rounded-2xl shadow-md hover:shadow-2xl border border-gray-100 overflow-hidden transition-all duration-300 hover:-translate-y-1 group">
              <!-- Colored top bar based on status -->
              <div :class="['h-1.5', statusColor(visit.visit_status)]"></div>
              
              <div class="p-6">
                <div class="flex justify-between items-start">
                  <div class="flex-1">
                    <h3 class="text-base font-bold text-gray-900 group-hover:text-indigo-600 transition-colors duration-200 flex items-center">
                      <i class="fas fa-user-graduate text-indigo-500 mr-2"></i>
                      {{ visit.student?.first_name }} {{ visit.student?.last_name }}
                    </h3>
                    <p class="text-xs text-gray-500 mt-1.5 flex items-center">
                      <i class="fas fa-school text-gray-400 mr-1.5"></i>
                      ชั้น {{ visit.student?.classroom || '-' }}
                      <span class="mx-2">•</span>
                      <i class="fas fa-chalkboard-teacher text-gray-400 mr-1.5"></i>
                      ครู: {{ visit.teacher_name || '-' }}
                    </p>
                  </div>
                  <div class="text-right ml-4">
                    <div class="text-xs text-gray-400 flex items-center justify-end mb-2">
                      <i class="far fa-clock mr-1.5"></i>
                      {{ formatDate(visit.visit_date) }}
                    </div>
                    <span :class="['inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold shadow-sm', statusBadge(visit.visit_status)]">
                      <span :class="['w-2 h-2 rounded-full mr-2 animate-pulse', statusDot(visit.visit_status)]"></span>
                      {{ statusText(visit.visit_status) }}
                    </span>
                  </div>
                </div>

                <!-- Summary / Notes with gradient fade -->
                <div v-if="visit.notes || visit.summary" class="mt-4 p-4 bg-gradient-to-br from-gray-50 to-indigo-50/30 rounded-xl border border-gray-100">
                  <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line line-clamp-3">
                    {{ visit.summary || visit.notes }}
                  </p>
                </div>

                <!-- Images Gallery with hover zoom -->
                <div v-if="visit.images && visit.images.length" class="mt-5 grid grid-cols-3 gap-3">
                  <div
                    v-for="(img, i) in visit.images.slice(0,3)"
                    :key="i"
                    class="aspect-square overflow-hidden rounded-xl cursor-pointer group/img relative shadow-md hover:shadow-xl transition-all duration-300"
                    @click="openDetails(visit)"
                  >
                    <img :src="getImageUrl(img)" class="object-cover w-full h-full group-hover/img:scale-110 transition-transform duration-500" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover/img:opacity-100 transition-opacity duration-300"></div>
                    <div v-if="i === 2 && visit.images.length > 3" 
                      class="absolute inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center text-white font-bold text-lg">
                      <div class="text-center">
                        <i class="fas fa-images text-2xl mb-1"></i>
                        <div>+{{ visit.images.length - 3 }}</div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Meta Data with icons -->
                <div class="mt-5 grid sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                  <div class="flex items-center space-x-2 p-2.5 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors duration-200">
                    <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center">
                      <i class="fas fa-map-marker-alt text-indigo-600"></i>
                    </div>
                    <div>
                      <div class="text-[10px] text-indigo-600 font-medium">โซน</div>
                      <div class="text-gray-700 font-semibold">{{ visit.zone?.name || '-' }}</div>
                    </div>
                  </div>
                  <div class="flex items-center space-x-2 p-2.5 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors duration-200">
                    <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                      <i class="fas fa-user-friends text-purple-600"></i>
                    </div>
                    <div>
                      <div class="text-[10px] text-purple-600 font-medium">ผู้เยี่ยม</div>
                      <div class="text-gray-700 font-semibold truncate">{{ visit.visitor_name || visit.teacher_name || 'N/A' }}</div>
                    </div>
                  </div>
                  <div v-if="visit.duration" class="flex items-center space-x-2 p-2.5 bg-pink-50 rounded-lg hover:bg-pink-100 transition-colors duration-200">
                    <div class="w-8 h-8 bg-pink-100 rounded-lg flex items-center justify-center">
                      <i class="fas fa-hourglass-half text-pink-600"></i>
                    </div>
                    <div>
                      <div class="text-[10px] text-pink-600 font-medium">ระยะเวลา</div>
                      <div class="text-gray-700 font-semibold">{{ visit.duration }} นาที</div>
                    </div>
                  </div>
                  <div v-if="visit.next_schedule" class="flex items-center space-x-2 p-2.5 bg-green-50 rounded-lg hover:bg-green-100 transition-colors duration-200">
                    <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                      <i class="fas fa-calendar-plus text-green-600"></i>
                    </div>
                    <div>
                      <div class="text-[10px] text-green-600 font-medium">ครั้งต่อไป</div>
                      <div class="text-gray-700 font-semibold text-[11px]">{{ formatDate(visit.next_schedule) }}</div>
                    </div>
                  </div>
                </div>

                <!-- Actions with hover effects -->
                <div class="mt-5 pt-5 border-t border-gray-100 flex items-center justify-between">
                  <div class="flex space-x-3">
                    <button @click="openDetails(visit)" 
                      class="group/btn flex items-center px-4 py-2 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-600 hover:text-white transition-all duration-200 hover:shadow-md">
                      <i class="fas fa-eye mr-2 group-hover/btn:scale-110 transition-transform"></i> 
                      ดูรายละเอียด
                    </button>
                    <button @click="toggleExpand(visit.id)"
                      class="group/btn flex items-center px-4 py-2 text-xs font-medium text-gray-600 bg-gray-50 rounded-lg hover:bg-gray-600 hover:text-white transition-all duration-200 hover:shadow-md">
                      <i :class="['mr-2 transition-transform', expanded.has(visit.id) ? 'fas fa-chevron-up rotate-180' : 'fas fa-chevron-down']"></i>
                      {{ expanded.has(visit.id) ? 'ย่อ' : 'ขยาย' }}
                    </button>
                  </div>
                  <div class="flex items-center space-x-2">
                    <span class="text-[10px] text-gray-400 bg-gray-50 px-2.5 py-1 rounded-full font-mono">
                      ID: {{ visit.id }}
                    </span>
                  </div>
                </div>

                <!-- Expanded Extra Content with slide animation -->
                <transition name="expand">
                  <div v-if="expanded.has(visit.id)" class="mt-5 pt-5 border-t border-gray-100 space-y-4">
                    <div v-if="visit.risks" class="animate-slide-in">
                      <div class="flex items-center p-3 bg-red-50 rounded-xl mb-3">
                        <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mr-3">
                          <i class="fas fa-exclamation-triangle text-red-600 text-lg"></i>
                        </div>
                        <div class="font-semibold text-red-800 text-sm">ประเด็นที่พบ</div>
                      </div>
                      <ul class="space-y-2 ml-2">
                        <li v-for="(r,i) in (Array.isArray(visit.risks) ? visit.risks : [])" 
                          :key="i" 
                          class="flex items-start text-sm text-gray-700 p-2.5 bg-white rounded-lg border border-red-100 hover:shadow-sm transition-shadow">
                          <i class="fas fa-circle text-red-400 text-[6px] mt-1.5 mr-3"></i>
                          <span>{{ r }}</span>
                        </li>
                      </ul>
                    </div>
                    
                    <div v-if="visit.recommendations" class="animate-slide-in animation-delay-100">
                      <div class="flex items-center p-3 bg-yellow-50 rounded-xl mb-3">
                        <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center mr-3">
                          <i class="fas fa-lightbulb text-yellow-600 text-lg"></i>
                        </div>
                        <div class="font-semibold text-yellow-800 text-sm">ข้อเสนอแนะ</div>
                      </div>
                      <ul class="space-y-2 ml-2">
                        <li v-for="(r,i) in (Array.isArray(visit.recommendations) ? visit.recommendations : [])" 
                          :key="i" 
                          class="flex items-start text-sm text-gray-700 p-2.5 bg-white rounded-lg border border-yellow-100 hover:shadow-sm transition-shadow">
                          <i class="fas fa-circle text-yellow-400 text-[6px] mt-1.5 mr-3"></i>
                          <span>{{ r }}</span>
                        </li>
                      </ul>
                    </div>
                    
                    <div v-if="visit.follow_up_actions" class="animate-slide-in animation-delay-200">
                      <div class="flex items-center p-3 bg-indigo-50 rounded-xl mb-3">
                        <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mr-3">
                          <i class="fas fa-tasks text-indigo-600 text-lg"></i>
                        </div>
                        <div class="font-semibold text-indigo-800 text-sm">การติดตามผล</div>
                      </div>
                      <ul class="space-y-2 ml-2">
                        <li v-for="(a,i) in (Array.isArray(visit.follow_up_actions) ? visit.follow_up_actions : [])" 
                          :key="i" 
                          class="flex items-start text-sm text-gray-700 p-2.5 bg-white rounded-lg border border-indigo-100 hover:shadow-sm transition-shadow">
                          <i class="fas fa-circle text-indigo-400 text-[6px] mt-1.5 mr-3"></i>
                          <span>{{ a }}</span>
                        </li>
                      </ul>
                    </div>
                  </div>
                </transition>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Modern Pagination -->
      <div v-if="filteredVisits.length" class="flex justify-center pt-8 animate-fade-in">
        <nav class="inline-flex items-center bg-white rounded-xl shadow-lg p-2 space-x-1 border border-gray-100" aria-label="Pagination">
          <button
            @click="prevPage"
            :disabled="currentPage === 1"
            class="px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-indigo-50 hover:text-indigo-600 text-gray-600"
          >
            <i class="fas fa-chevron-left mr-2"></i>
            ก่อนหน้า
          </button>
          <div class="px-6 py-2.5 text-sm">
            <span class="font-semibold text-indigo-600">{{ currentPage }}</span>
            <span class="text-gray-400 mx-1">/</span>
            <span class="text-gray-600">{{ totalPages }}</span>
          </div>
          <button
            @click="nextPage"
            :disabled="currentPage === totalPages"
            class="px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-indigo-50 hover:text-indigo-600 text-gray-600"
          >
            ถัดไป
            <i class="fas fa-chevron-right ml-2"></i>
          </button>
        </nav>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  visits: { type: Array, default: () => [] },
  zones: { type: Array, default: () => [] }
})

const emits = defineEmits(['view-details'])

const filters = ref({
  status: '',
  zone: '',
  startDate: '',
  endDate: ''
})

const expanded = ref(new Set())
const currentPage = ref(1)
const perPage = ref(10)

const clearFilters = () => {
  filters.value = { status: '', zone: '', startDate: '', endDate: '' }
}

const formatDate = (d) => {
  if (!d) return '-'
  const dt = new Date(d)
  return dt.toLocaleString('th-TH', { dateStyle: 'medium', timeStyle: 'short' })
}

const statusText = (s) => {
  switch (s) {
    case 'pending': return 'รอดำเนินการ'
    case 'in-progress': return 'กำลังดำเนินการ'
    case 'completed': return 'เสร็จสิ้น'
    case 'cancelled': return 'ยกเลิก'
    default: return 'ไม่ระบุ'
  }
}

const statusColor = (s) => {
  switch (s) {
    case 'pending': return 'bg-gray-400'
    case 'in-progress': return 'bg-yellow-500'
    case 'completed': return 'bg-green-600'
    case 'cancelled': return 'bg-red-500'
    default: return 'bg-indigo-500'
  }
}

const statusIcon = (s) => {
  switch (s) {
    case 'pending': return 'fas fa-clock'
    case 'in-progress': return 'fas fa-spinner'
    case 'completed': return 'fas fa-check'
    case 'cancelled': return 'fas fa-times'
    default: return 'fas fa-info'
  }
}

const statusBadge = (s) => {
  switch (s) {
    case 'pending': return 'bg-gray-100 text-gray-800'
    case 'in-progress': return 'bg-yellow-100 text-yellow-800'
    case 'completed': return 'bg-green-100 text-green-800'
    case 'cancelled': return 'bg-red-100 text-red-800'
    default: return 'bg-indigo-100 text-indigo-800'
  }
}

const statusDot = (s) => {
  switch (s) {
    case 'pending': return 'bg-gray-500'
    case 'in-progress': return 'bg-yellow-500'
    case 'completed': return 'bg-green-500'
    case 'cancelled': return 'bg-red-500'
    default: return 'bg-indigo-500'
  }
}

const filteredVisits = computed(() => {
  return props.visits
    .filter(v => {
      if (filters.value.status && v.visit_status !== filters.value.status) return false
      if (filters.value.zone && String(v.zone_id) !== String(filters.value.zone)) return false
      if (filters.value.startDate) {
        const vs = new Date(v.visit_date)
        const st = new Date(filters.value.startDate)
        if (vs < st) return false
      }
      if (filters.value.endDate) {
        const ve = new Date(v.visit_date)
        const en = new Date(filters.value.endDate)
        if (ve > en) return false
      }
      return true
    })
    .sort((a,b) => new Date(b.visit_date) - new Date(a.visit_date))
})

const totalPages = computed(() => Math.max(1, Math.ceil(filteredVisits.value.length / perPage.value)))

const paginatedVisits = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return filteredVisits.value.slice(start, start + perPage.value)
})

const nextPage = () => { if (currentPage.value < totalPages.value) currentPage.value++ }
const prevPage = () => { if (currentPage.value > 1) currentPage.value-- }

const toggleExpand = (id) => {
  if (expanded.value.has(id)) {
    expanded.value.delete(id)
  } else {
    expanded.value.add(id)
  }
  expanded.value = new Set(expanded.value)
}

const openDetails = (visit) => {
  emits('view-details', visit)
}

const refreshFeed = () => {
  // Placeholder: In real implementation, fetch latest visits via API
  // Could emit an event or call a composable function
}

const getImageUrl = (img) => {
  // Handle different image data structures
  if (typeof img === 'string') {
    return img
  }
  if (img && typeof img === 'object') {
    return img.url || img.path || img.src || ''
  }
  return ''
}
</script>

<style scoped>
/* Animations */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(-10px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes blob {
  0%, 100% { transform: translate(0, 0) scale(1); }
  25% { transform: translate(20px, -20px) scale(1.1); }
  50% { transform: translate(-20px, 20px) scale(0.9); }
  75% { transform: translate(20px, 20px) scale(1.05); }
}

@keyframes pulse-soft {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.8; }
}

@keyframes bounce-slow {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

.animate-blob {
  animation: blob 7s infinite;
}

.animation-delay-2000 {
  animation-delay: 2s;
}

.animation-delay-4000 {
  animation-delay: 4s;
}

.animate-fade-in-up {
  animation: fadeInUp 0.6s ease-out;
}

.animate-fade-in {
  animation: fadeIn 0.5s ease-out;
}

.animate-slide-in {
  animation: slideIn 0.4s ease-out;
}

.animation-delay-100 {
  animation-delay: 0.1s;
  animation-fill-mode: both;
}

.animation-delay-200 {
  animation-delay: 0.2s;
  animation-fill-mode: both;
}

.animate-pulse-soft {
  animation: pulse-soft 3s ease-in-out infinite;
}

.animate-bounce-slow {
  animation: bounce-slow 2s ease-in-out infinite;
}

/* Timeline Items Staggered Animation */
.timeline-item {
  animation: fadeInUp 0.6s ease-out both;
}

/* Expand/Collapse Transition */
.expand-enter-active,
.expand-leave-active {
  transition: all 0.3s ease;
  overflow: hidden;
}

.expand-enter-from,
.expand-leave-to {
  opacity: 0;
  max-height: 0;
  transform: translateY(-10px);
}

.expand-enter-to,
.expand-leave-from {
  opacity: 1;
  max-height: 1000px;
  transform: translateY(0);
}

/* Card Hover Effects */
.visit-card {
  transform-origin: center;
}

.header-card {
  background-size: 200% 200%;
  animation: gradient-shift 8s ease infinite;
}

@keyframes gradient-shift {
  0%, 100% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
}

/* Filter Item Animation */
.filter-item {
  animation: fadeIn 0.5s ease-out;
}

.filter-item:nth-child(1) { animation-delay: 0.1s; animation-fill-mode: both; }
.filter-item:nth-child(2) { animation-delay: 0.2s; animation-fill-mode: both; }
.filter-item:nth-child(3) { animation-delay: 0.3s; animation-fill-mode: both; }
.filter-item:nth-child(4) { animation-delay: 0.4s; animation-fill-mode: both; }

/* Smooth Scroll */
* {
  scroll-behavior: smooth;
}

/* Line Clamp Utility */
.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
