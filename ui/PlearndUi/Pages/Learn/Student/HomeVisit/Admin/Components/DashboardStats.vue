<template>
  <div class="space-y-4">
    <!-- Statistics Cards with Modern Design -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-5">
      <!-- Total Students Card -->
      <div class="stat-card group bg-white overflow-hidden shadow-lg hover:shadow-2xl rounded-2xl border border-gray-100 transition-all duration-300 hover:-translate-y-2">
        <div class="p-6">
          <div class="flex items-center justify-between">
            <div class="flex-1">
              <div class="flex items-center mb-4">
                <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                  <i class="fas fa-users text-white text-2xl"></i>
                </div>
              </div>
              <dt class="text-sm font-medium text-gray-500 mb-2">
                จำนวนนักเรียนทั้งหมด
              </dt>
              <dd class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">
                {{ stats.total_students?.toLocaleString() || 0 }}
              </dd>
              <div class="mt-2 flex items-center text-xs text-green-600">
                <i class="fas fa-arrow-up mr-1"></i>
                <span>คน</span>
              </div>
            </div>
            <div class="absolute top-0 right-0 w-20 h-20 bg-blue-100 rounded-full -mr-10 -mt-10 opacity-20"></div>
          </div>
        </div>
        <div class="h-1 bg-gradient-to-r from-blue-500 to-cyan-500"></div>
      </div>

      <!-- Total Visits Card -->
      <div class="stat-card group bg-white overflow-hidden shadow-lg hover:shadow-2xl rounded-2xl border border-gray-100 transition-all duration-300 hover:-translate-y-2">
        <div class="p-6">
          <div class="flex items-center justify-between">
            <div class="flex-1">
              <div class="flex items-center mb-4">
                <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-emerald-500 rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                  <i class="fas fa-home text-white text-2xl"></i>
                </div>
              </div>
              <dt class="text-sm font-medium text-gray-500 mb-2">
                การเยี่ยมบ้านทั้งหมด
              </dt>
              <dd class="text-3xl font-bold bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent">
                {{ stats.total_visits?.toLocaleString() || 0 }}
              </dd>
              <div class="mt-2 flex items-center text-xs text-green-600">
                <i class="fas fa-check-circle mr-1"></i>
                <span>ครั้ง</span>
              </div>
            </div>
            <div class="absolute top-0 right-0 w-20 h-20 bg-green-100 rounded-full -mr-10 -mt-10 opacity-20"></div>
          </div>
        </div>
        <div class="h-1 bg-gradient-to-r from-green-500 to-emerald-500"></div>
      </div>

      <!-- This Month Card -->
      <div class="stat-card group bg-white overflow-hidden shadow-lg hover:shadow-2xl rounded-2xl border border-gray-100 transition-all duration-300 hover:-translate-y-2">
        <div class="p-6">
          <div class="flex items-center justify-between">
            <div class="flex-1">
              <div class="flex items-center mb-4">
                <div class="w-14 h-14 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                  <i class="fas fa-calendar text-white text-2xl"></i>
                </div>
              </div>
              <dt class="text-sm font-medium text-gray-500 mb-2">
                เยี่ยมบ้านเดือนนี้
              </dt>
              <dd class="text-3xl font-bold bg-gradient-to-r from-yellow-600 to-orange-600 bg-clip-text text-transparent">
                {{ stats.visits_this_month?.toLocaleString() || 0 }}
              </dd>
              <div class="mt-2 flex items-center text-xs text-yellow-600">
                <i class="fas fa-chart-line mr-1"></i>
                <span>ครั้ง</span>
              </div>
            </div>
            <div class="absolute top-0 right-0 w-20 h-20 bg-yellow-100 rounded-full -mr-10 -mt-10 opacity-20"></div>
          </div>
        </div>
        <div class="h-1 bg-gradient-to-r from-yellow-500 to-orange-500"></div>
      </div>

      <!-- Pending Card -->
      <div class="stat-card group bg-white overflow-hidden shadow-lg hover:shadow-2xl rounded-2xl border border-gray-100 transition-all duration-300 hover:-translate-y-2">
        <div class="p-6">
          <div class="flex items-center justify-between">
            <div class="flex-1">
              <div class="flex items-center mb-4">
                <div class="w-14 h-14 bg-gradient-to-br from-orange-500 to-red-500 rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                  <i class="fas fa-clock text-white text-2xl"></i>
                </div>
              </div>
              <dt class="text-sm font-medium text-gray-500 mb-2">
                รอดำเนินการ
              </dt>
              <dd class="text-3xl font-bold bg-gradient-to-r from-orange-600 to-red-600 bg-clip-text text-transparent">
                {{ stats.pending_visits?.toLocaleString() || 0 }}
              </dd>
              <div class="mt-2 flex items-center text-xs text-orange-600">
                <i class="fas fa-hourglass-half mr-1"></i>
                <span>รายการ</span>
              </div>
            </div>
            <div class="absolute top-0 right-0 w-20 h-20 bg-orange-100 rounded-full -mr-10 -mt-10 opacity-20"></div>
          </div>
        </div>
        <div class="h-1 bg-gradient-to-r from-orange-500 to-red-500"></div>
      </div>

      <!-- Completed Card -->
      <div class="stat-card group bg-white overflow-hidden shadow-lg hover:shadow-2xl rounded-2xl border border-gray-100 transition-all duration-300 hover:-translate-y-2">
        <div class="p-6">
          <div class="flex items-center justify-between">
            <div class="flex-1">
              <div class="flex items-center mb-4">
                <div class="w-14 h-14 bg-gradient-to-br from-emerald-500 to-teal-500 rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                  <i class="fas fa-check text-white text-2xl"></i>
                </div>
              </div>
              <dt class="text-sm font-medium text-gray-500 mb-2">
                เสร็จสิ้นแล้ว
              </dt>
              <dd class="text-3xl font-bold bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent">
                {{ stats.completed_visits?.toLocaleString() || 0 }}
              </dd>
              <div class="mt-2 flex items-center text-xs text-emerald-600">
                <i class="fas fa-trophy mr-1"></i>
                <span>รายการ</span>
              </div>
            </div>
            <div class="absolute top-0 right-0 w-20 h-20 bg-emerald-100 rounded-full -mr-10 -mt-10 opacity-20"></div>
          </div>
        </div>
        <div class="h-1 bg-gradient-to-r from-emerald-500 to-teal-500"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  stats: {
    type: Object,
    required: true
  }
})
</script>

<style scoped>
.stat-card {
  position: relative;
  animation: fadeInUp 0.6s ease-out;
}

.stat-card:nth-child(1) { animation-delay: 0.1s; }
.stat-card:nth-child(2) { animation-delay: 0.2s; }
.stat-card:nth-child(3) { animation-delay: 0.3s; }
.stat-card:nth-child(4) { animation-delay: 0.4s; }
.stat-card:nth-child(5) { animation-delay: 0.5s; }

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
