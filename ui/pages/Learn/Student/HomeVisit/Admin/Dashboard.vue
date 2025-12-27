<template>
  <Head title="ระบบจัดการการเยี่ยมบ้านนักเรียน - แดชบอร์ด" />
  
  <div class="min-h-screen bg-gradient-to-br from-gray-50 via-indigo-50/30 to-purple-50/30">
    <!-- Modern Gradient Header -->
    <nav class="bg-white shadow-lg border-b border-gray-100 sticky top-0 z-50 backdrop-blur-md bg-white/95">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center animate-fade-in">
            <div class="w-10 h-10 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-xl flex items-center justify-center mr-3 shadow-lg">
              <i class="fas fa-home text-white text-lg"></i>
            </div>
            <h1 class="text-xl font-bold bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">
              ระบบจัดการการเยี่ยมบ้านนักเรียน
            </h1>
          </div>
          <div class="flex items-center space-x-3 animate-fade-in">
            <div class="hidden md:flex items-center px-4 py-2 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg">
              <i class="fas fa-user-shield text-indigo-600 mr-2"></i>
              <span class="text-sm font-medium text-gray-700">แอดมิน</span>
            </div>
            <button
              @click="logout"
              class="group flex items-center px-4 py-2 text-sm font-medium text-gray-700 hover:text-red-600 bg-gray-100 hover:bg-red-50 rounded-lg transition-all duration-200 hover:shadow-md"
            >
              <i class="fas fa-sign-out-alt mr-2 group-hover:rotate-12 transition-transform"></i>
              ออกจากระบบ
            </button>
          </div>
        </div>
      </div>
    </nav>

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
      <!-- Modern Tab Navigation -->
      <div class="mb-8">
        <div class="sm:hidden">
          <select
            v-model="activeTab"
            class="block w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 shadow-sm"
          >
            <option value="dashboard">🏠 แดชบอร์ด</option>
            <option value="feed">📊 ฟีดการเยี่ยมบ้าน</option>
            <option value="settings">⚙️ ตั้งค่าระบบ</option>
          </select>
        </div>
        <div class="hidden sm:block">
          <div class="bg-white rounded-2xl shadow-lg p-2 border border-gray-100">
            <nav class="flex space-x-2">
              <button
                v-for="tab in tabs"
                :key="tab.id"
                @click="activeTab = tab.id"
                :class="[
                  activeTab === tab.id
                    ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg scale-105'
                    : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900',
                  'flex items-center px-6 py-3 rounded-xl font-medium text-sm transition-all duration-200'
                ]"
              >
                <i :class="[tab.icon, 'mr-2']"></i>
                {{ tab.name }}
              </button>
            </nav>
          </div>
        </div>
      </div>

      <!-- Dashboard Content -->
      <div v-if="activeTab === 'dashboard'" class="space-y-8">
        <!-- Welcome Banner -->
        <div class="relative bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500 rounded-3xl p-8 md:p-10 overflow-hidden shadow-2xl animate-fade-in">
          <!-- Animated Background Pattern -->
          <div class="absolute inset-0 opacity-10">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-white rounded-full animate-blob"></div>
            <div class="absolute -bottom-10 -left-10 w-48 h-48 bg-white rounded-full animate-blob animation-delay-2000"></div>
            <div class="absolute top-1/2 left-1/2 w-44 h-44 bg-white rounded-full animate-blob animation-delay-4000"></div>
          </div>
          
          <div class="relative z-10">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between">
              <div>
                <h2 class="text-3xl md:text-4xl font-bold text-white mb-2">
                  สวัสดีครับ! 👋
                </h2>
                <p class="text-white/90 text-lg">
                  ยินดีต้อนรับสู่ระบบจัดการการเยี่ยมบ้านนักเรียน
                </p>
                <p class="text-white/75 text-sm mt-2">
                  วันที่ {{ currentDate }}
                </p>
              </div>
              <div class="mt-4 md:mt-0">
                <button class="group flex items-center px-6 py-3 bg-white/20 backdrop-blur-md hover:bg-white/30 rounded-xl text-white font-medium transition-all duration-200 hover:scale-105 border border-white/30">
                  <i class="fas fa-chart-line mr-2 group-hover:scale-110 transition-transform"></i>
                  ดูรายงาน
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Enhanced Statistics Cards -->
        <div class="animate-fade-in animation-delay-200">
          <DashboardStats :stats="stats" />
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 animate-fade-in animation-delay-300">
          <!-- Monthly Visits Chart -->
          <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow duration-300">
            <div class="flex items-center justify-between mb-6">
              <div>
                <h3 class="text-lg font-bold text-gray-900 flex items-center">
                  <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center mr-3">
                    <i class="fas fa-chart-bar text-indigo-600"></i>
                  </div>
                  สถิติการเยี่ยมบ้านรายเดือน
                </h3>
                <p class="text-sm text-gray-500 mt-1 ml-13">แนวโน้มการเยี่ยมบ้าน 6 เดือนล่าสุด</p>
              </div>
            </div>
            <div class="h-64 flex items-center justify-center bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl">
              <div class="text-center">
                <i class="fas fa-chart-area text-5xl text-indigo-300 mb-4"></i>
                <p class="text-gray-500 text-sm">กราฟแสดงข้อมูลการเยี่ยมบ้านรายเดือน</p>
              </div>
            </div>
          </div>

          <!-- Status Distribution Chart -->
          <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow duration-300">
            <div class="flex items-center justify-between mb-6">
              <div>
                <h3 class="text-lg font-bold text-gray-900 flex items-center">
                  <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center mr-3">
                    <i class="fas fa-chart-pie text-purple-600"></i>
                  </div>
                  สัดส่วนสถานะการเยี่ยม
                </h3>
                <p class="text-sm text-gray-500 mt-1 ml-13">การกระจายตามสถานะ</p>
              </div>
            </div>
            <div class="space-y-3">
              <div v-for="status in statusDistribution" :key="status.name" class="group hover:scale-102 transition-transform">
                <div class="flex items-center justify-between mb-1">
                  <span class="text-sm font-medium text-gray-700 flex items-center">
                    <span :class="['w-3 h-3 rounded-full mr-2', status.color]"></span>
                    {{ status.name }}
                  </span>
                  <span class="text-sm font-bold" :class="status.textColor">{{ status.value }}</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                  <div 
                    :class="['h-2.5 rounded-full transition-all duration-700 ease-out', status.bgColor]"
                    :style="{ width: status.percentage + '%' }"
                  ></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Activities & Quick Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-fade-in animation-delay-400">
          <!-- Recent Activities (2/3 width) -->
          <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-lg font-bold text-gray-900 flex items-center">
                <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center mr-3">
                  <i class="fas fa-clock text-green-600"></i>
                </div>
                กิจกรรมล่าสุด
              </h3>
              <button class="text-sm text-indigo-600 hover:text-indigo-700 font-medium hover:underline">
                ดูทั้งหมด
              </button>
            </div>
            <div class="space-y-4 max-h-96 overflow-y-auto custom-scrollbar">
              <div 
                v-for="(activity, index) in recentActivities" 
                :key="index"
                class="flex items-start p-4 bg-gradient-to-r from-gray-50 to-transparent rounded-xl hover:from-indigo-50 hover:shadow-md transition-all duration-200 group"
              >
                <div :class="['w-10 h-10 rounded-full flex items-center justify-center mr-4 flex-shrink-0', activity.iconBg]">
                  <i :class="[activity.icon, activity.iconColor]"></i>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900 group-hover:text-indigo-600 transition-colors">
                    {{ activity.title }}
                  </p>
                  <p class="text-xs text-gray-500 mt-1">{{ activity.description }}</p>
                  <p class="text-xs text-gray-400 mt-1 flex items-center">
                    <i class="far fa-clock mr-1"></i>
                    {{ activity.time }}
                  </p>
                </div>
                <span :class="['px-2.5 py-1 rounded-full text-xs font-medium', activity.badge]">
                  {{ activity.status }}
                </span>
              </div>
            </div>
          </div>

          <!-- Quick Actions (1/3 width) -->
          <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
            <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center">
              <div class="w-10 h-10 bg-pink-100 rounded-xl flex items-center justify-center mr-3">
                <i class="fas fa-bolt text-pink-600"></i>
              </div>
              ทางลัด
            </h3>
            <div class="space-y-3">
              <button 
                v-for="action in quickActions" 
                :key="action.name"
                :class="['w-full group flex items-center p-4 rounded-xl transition-all duration-200 hover:scale-105 hover:shadow-lg border-2', action.class]"
              >
                <div :class="['w-12 h-12 rounded-xl flex items-center justify-center mr-4 transition-transform group-hover:scale-110', action.iconBg]">
                  <i :class="[action.icon, 'text-xl', action.iconColor]"></i>
                </div>
                <div class="text-left">
                  <div :class="['font-semibold text-sm', action.textColor]">{{ action.name }}</div>
                  <div class="text-xs text-gray-500">{{ action.description }}</div>
                </div>
                <i class="fas fa-chevron-right ml-auto opacity-0 group-hover:opacity-100 transition-opacity"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Enhanced Visits List Section -->
        <div class="animate-fade-in animation-delay-500">
          <VisitsListSection 
            :visits="allVisits" 
            :zones="zones" 
            @view-details="viewVisitDetails"
          />
        </div>
      </div>

      <!-- Feed Tab -->
      <div v-if="activeTab === 'feed'" class="space-y-6">
        <VisitFeed 
          :visits="allVisits"
          :zones="zones"
          @view-details="viewVisitDetails"
        />
      </div>

      <!-- Settings Tab -->
      <div v-if="activeTab === 'settings'">
        <AdminSettings :zones="zones" />
      </div>
    </div>

    <!-- Visit Detail Modal -->
    <VisitDetailModal 
      v-if="showDetailModal" 
      :visit="selectedVisit" 
      @close="closeDetailModal"
    />
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import { router, Head } from '@inertiajs/vue3'
import { formatTimeAgo, formatDateThai } from '@/utils/dateUtils'
import DashboardStats from './Components/DashboardStats.vue'
import VisitsListSection from './Components/VisitsListSection.vue'
import VisitFeed from './Components/VisitFeed.vue'
import AdminSettings from './Components/AdminSettings.vue'
import VisitDetailModal from './Components/VisitDetailModal.vue'

export default {
  components: {
    Head,
    DashboardStats,
    VisitsListSection,
    VisitFeed,
    AdminSettings,
    VisitDetailModal
  },

  props: {
    stats: Object,
    recentVisits: Array,
    monthlyVisits: Array,
    students: Array,
    allVisits: {
      type: Array,
      default: () => []
    },
    zones: {
      type: Array,
      default: () => []
    }
  },

  setup(props) {
    const activeTab = ref('dashboard')
    const showDetailModal = ref(false)
    const selectedVisit = ref(null)

    const tabs = [
      { id: 'dashboard', name: 'แดชบอร์ด', icon: 'fas fa-home' },
      { id: 'feed', name: 'ฟีดการเยี่ยมบ้าน', icon: 'fas fa-stream' },
      { id: 'settings', name: 'ตั้งค่าระบบ', icon: 'fas fa-cog' }
    ]

    // Current date
    const currentDate = computed(() => {
      return formatDateThai(new Date().toISOString(), { 
        format: 'full',
        weekday: true 
      })
    })

    // Status distribution for chart
    const statusDistribution = computed(() => {
      const total = props.stats?.total_visits || 1
      return [
        {
          name: 'เสร็จสิ้นแล้ว',
          value: props.stats?.completed_visits || 0,
          percentage: ((props.stats?.completed_visits || 0) / total) * 100,
          color: 'bg-green-500',
          bgColor: 'bg-gradient-to-r from-green-500 to-emerald-500',
          textColor: 'text-green-700'
        },
        {
          name: 'กำลังดำเนินการ',
          value: props.stats?.in_progress_visits || 0,
          percentage: ((props.stats?.in_progress_visits || 0) / total) * 100,
          color: 'bg-yellow-500',
          bgColor: 'bg-gradient-to-r from-yellow-500 to-orange-500',
          textColor: 'text-yellow-700'
        },
        {
          name: 'รอดำเนินการ',
          value: props.stats?.pending_visits || 0,
          percentage: ((props.stats?.pending_visits || 0) / total) * 100,
          color: 'bg-orange-500',
          bgColor: 'bg-gradient-to-r from-orange-500 to-red-500',
          textColor: 'text-orange-700'
        },
        {
          name: 'ยกเลิก',
          value: props.stats?.cancelled_visits || 0,
          percentage: ((props.stats?.cancelled_visits || 0) / total) * 100,
          color: 'bg-red-500',
          bgColor: 'bg-gradient-to-r from-red-500 to-rose-500',
          textColor: 'text-red-700'
        }
      ]
    })

    // Recent activities (mock data - replace with real data)
    const recentActivities = computed(() => {
      const activities = []
      
      // Get recent visits and convert to activities
      if (props.allVisits && props.allVisits.length > 0) {
        props.allVisits.slice(0, 5).forEach(visit => {
          activities.push({
            title: `เยี่ยมบ้านนักเรียน ${visit.student?.first_name || ''} ${visit.student?.last_name || ''}`,
            description: `โซน: ${visit.zone?.name || 'ไม่ระบุ'} | ครู: ${visit.teacher_name || 'ไม่ระบุ'}`,
            time: formatTimeAgo(visit.visit_date),
            status: getStatusText(visit.visit_status),
            badge: getStatusBadgeClass(visit.visit_status),
            icon: getStatusIcon(visit.visit_status),
            iconColor: getStatusIconColor(visit.visit_status),
            iconBg: getStatusIconBg(visit.visit_status)
          })
        })
      }

      // Add some default activities if empty
      if (activities.length === 0) {
        activities.push(
          {
            title: 'ระบบเริ่มต้นใช้งาน',
            description: 'ระบบจัดการการเยี่ยมบ้านพร้อมใช้งาน',
            time: 'เมื่อสักครู่',
            status: 'ใหม่',
            badge: 'bg-blue-100 text-blue-800',
            icon: 'fas fa-rocket',
            iconColor: 'text-blue-600',
            iconBg: 'bg-blue-100'
          }
        )
      }

      return activities
    })

    // Quick actions
    const quickActions = [
      {
        name: 'เพิ่มการเยี่ยมบ้าน',
        description: 'บันทึกการเยี่ยมใหม่',
        icon: 'fas fa-plus-circle',
        iconColor: 'text-indigo-600',
        iconBg: 'bg-indigo-100',
        textColor: 'text-indigo-900',
        class: 'bg-indigo-50 border-indigo-200 hover:bg-indigo-100'
      },
      {
        name: 'ดูรายงาน',
        description: 'สรุปผลการเยี่ยม',
        icon: 'fas fa-chart-line',
        iconColor: 'text-green-600',
        iconBg: 'bg-green-100',
        textColor: 'text-green-900',
        class: 'bg-green-50 border-green-200 hover:bg-green-100'
      },
      {
        name: 'จัดการโซน',
        description: 'แก้ไขพื้นที่เยี่ยม',
        icon: 'fas fa-map-marked-alt',
        iconColor: 'text-purple-600',
        iconBg: 'bg-purple-100',
        textColor: 'text-purple-900',
        class: 'bg-purple-50 border-purple-200 hover:bg-purple-100'
      },
      {
        name: 'ตั้งค่า',
        description: 'จัดการระบบ',
        icon: 'fas fa-cog',
        iconColor: 'text-gray-600',
        iconBg: 'bg-gray-100',
        textColor: 'text-gray-900',
        class: 'bg-gray-50 border-gray-200 hover:bg-gray-100'
      }
    ]

    // Helper functions
    // ใช้ formatTimeAgo จาก date utilities แทน

    const getStatusText = (status) => {
      const statusMap = {
        'pending': 'รอดำเนินการ',
        'in-progress': 'กำลังดำเนินการ',
        'completed': 'เสร็จสิ้น',
        'cancelled': 'ยกเลิก'
      }
      return statusMap[status] || 'ไม่ระบุ'
    }

    const getStatusBadgeClass = (status) => {
      const classMap = {
        'pending': 'bg-orange-100 text-orange-800',
        'in-progress': 'bg-yellow-100 text-yellow-800',
        'completed': 'bg-green-100 text-green-800',
        'cancelled': 'bg-red-100 text-red-800'
      }
      return classMap[status] || 'bg-gray-100 text-gray-800'
    }

    const getStatusIcon = (status) => {
      const iconMap = {
        'pending': 'fas fa-clock',
        'in-progress': 'fas fa-spinner',
        'completed': 'fas fa-check-circle',
        'cancelled': 'fas fa-times-circle'
      }
      return iconMap[status] || 'fas fa-info-circle'
    }

    const getStatusIconColor = (status) => {
      const colorMap = {
        'pending': 'text-orange-600',
        'in-progress': 'text-yellow-600',
        'completed': 'text-green-600',
        'cancelled': 'text-red-600'
      }
      return colorMap[status] || 'text-gray-600'
    }

    const getStatusIconBg = (status) => {
      const bgMap = {
        'pending': 'bg-orange-100',
        'in-progress': 'bg-yellow-100',
        'completed': 'bg-green-100',
        'cancelled': 'bg-red-100'
      }
      return bgMap[status] || 'bg-gray-100'
    }

    const viewVisitDetails = (visit) => {
      selectedVisit.value = visit
      showDetailModal.value = true
    }

    const closeDetailModal = () => {
      showDetailModal.value = false
      selectedVisit.value = null
    }

    const logout = () => {
      router.post('/home-visit/admin/logout')
    }

    return {
      activeTab,
      tabs,
      currentDate,
      statusDistribution,
      recentActivities,
      quickActions,
      showDetailModal,
      selectedVisit,
      viewVisitDetails,
      closeDetailModal,
      logout
    }
  }
}
</script>

<style scoped>
/* Animations */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes blob {
  0%, 100% { transform: translate(0, 0) scale(1); }
  25% { transform: translate(20px, -20px) scale(1.1); }
  50% { transform: translate(-20px, 20px) scale(0.9); }
  75% { transform: translate(20px, 20px) scale(1.05); }
}

.animate-fade-in {
  animation: fadeIn 0.6s ease-out;
}

.animation-delay-200 {
  animation-delay: 0.2s;
  animation-fill-mode: both;
}

.animation-delay-300 {
  animation-delay: 0.3s;
  animation-fill-mode: both;
}

.animation-delay-400 {
  animation-delay: 0.4s;
  animation-fill-mode: both;
}

.animation-delay-500 {
  animation-delay: 0.5s;
  animation-fill-mode: both;
}

.animation-delay-2000 {
  animation-delay: 2s;
}

.animation-delay-4000 {
  animation-delay: 4s;
}

.animate-blob {
  animation: blob 7s infinite;
}

.hover\:scale-102:hover {
  transform: scale(1.02);
}

/* Custom Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: linear-gradient(to bottom, #818cf8, #a78bfa);
  border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(to bottom, #6366f1, #8b5cf6);
}

/* Smooth transitions */
* {
  transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, box-shadow, transform;
}
</style>


