<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { useApi } from '~/composables/useApi'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js'
import { Line } from 'vue-chartjs'

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend
)

definePageMeta({
  layout: 'main',
})

const route = useRoute()
const api = useApi()
const academyId = route.params.academyId

const reportData = ref<any>(null)
const loading = ref(true)
const error = ref<string | null>(null)

async function fetchReport() {
  loading.value = true
  error.value = null
  try {
    const res = await api.get(`/typing/classroom/${academyId}/report`)
    if (res.success) {
      reportData.value = res.data
    }
  } catch (e) {
    error.value = 'โหลดข้อมูลไม่สำเร็จ'
    console.error('Failed to fetch report', e)
  } finally {
    loading.value = false
  }
}

onMounted(fetchReport)

const chartData = computed(() => {
  if (!reportData.value?.trend) return null
  return {
    labels: reportData.value.trend.map((t: any) => t.date),
    datasets: [
      {
        label: 'Average WPM',
        backgroundColor: '#3b82f6',
        borderColor: '#3b82f6',
        data: reportData.value.trend.map((t: any) => t.avg_wpm),
        tension: 0.4
      }
    ]
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false }
  },
  scales: {
    y: { beginAtZero: true }
  }
}
</script>

<template>
  <div class="max-w-7xl mx-auto py-10 px-4 space-y-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div class="space-y-1">
        <h1 class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tighter">Typing Class Report</h1>
        <p class="text-slate-500 font-medium">สถิติการฝึกพิมพ์ของนักเรียนในระดับห้องเรียน</p>
      </div>
      <NuxtLink to="/play/games/typing" class="text-sm font-bold text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors flex items-center gap-2">
        <Icon icon="heroicons:arrow-left" />
        กลับหน้าหลัก
      </NuxtLink>
    </div>

    <div v-if="loading" class="flex flex-col items-center justify-center py-40">
      <Icon icon="eos-icons:loading" class="text-5xl text-primary-500" />
    </div>

    <div v-else-if="error" class="flex flex-col items-center justify-center min-h-64 gap-4 text-slate-400">
      <Icon icon="heroicons:exclamation-circle" class="text-4xl text-red-400" />
      <p>{{ error }}</p>
      <button @click="fetchReport()" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors font-bold">
        ลองใหม่
      </button>
    </div>

    <template v-else-if="reportData">
      <!-- Summary Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm">
          <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">นักเรียนทั้งหมด</p>
          <p class="text-3xl font-black text-slate-900 dark:text-white">{{ reportData.summary.total_students }}</p>
        </div>
        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm">
          <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Active (7 วัน)</p>
          <p class="text-3xl font-black text-primary-500">{{ reportData.summary.active_students }}</p>
        </div>
        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm">
          <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Avg WPM ของห้อง</p>
          <p class="text-3xl font-black text-blue-500">{{ reportData.summary.class_avg_wpm }}</p>
        </div>
        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm">
          <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Avg Accuracy</p>
          <p class="text-3xl font-black text-green-500">{{ reportData.summary.class_avg_acc }}%</p>
        </div>
      </div>

      <div class="grid lg:grid-cols-3 gap-8">
        <!-- Trend Chart -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-900 p-8 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm space-y-6">
          <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
            <Icon icon="heroicons:presentation-chart-line" class="text-primary-500" />
            Class WPM Trend
          </h2>
          <div class="h-64">
            <Line v-if="chartData" :data="chartData" :options="chartOptions" />
          </div>
        </div>

        <!-- Needs Help -->
        <div class="bg-red-50 dark:bg-red-950/20 p-8 rounded-3xl border border-red-100 dark:border-red-900/30 space-y-6">
          <h2 class="text-xl font-black text-red-900 dark:text-red-400 uppercase tracking-wider flex items-center gap-2">
            <Icon icon="heroicons:exclamation-triangle" />
            ควรฝึกเพิ่ม
          </h2>
          <div class="space-y-4">
            <div v-for="student in reportData.need_help" :key="student.user_id" class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-red-200 dark:bg-red-900/50 flex items-center justify-center font-bold text-red-700 dark:text-red-300">
                {{ student.user.name.charAt(0) }}
              </div>
              <div>
                <p class="font-bold text-slate-900 dark:text-slate-100 leading-none">{{ student.user.name }}</p>
                <p class="text-xs text-red-600 font-medium">Avg WPM: {{ Math.round(student.avg_wpm) }}</p>
              </div>
            </div>
            <p v-if="reportData.need_help.length === 0" class="text-slate-400 text-sm font-medium">ไม่มีนักเรียนที่ต้องช่วยเหลือเป็นพิเศษ</p>
          </div>
        </div>
      </div>

      <!-- Students Table -->
      <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="p-8 border-b border-slate-50 dark:border-slate-800">
          <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
            <Icon icon="heroicons:users" class="text-slate-400" />
            Student Statistics
          </h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-400 text-[10px] font-black uppercase tracking-widest">
              <tr>
                <th class="px-8 py-4">Student</th>
                <th class="px-8 py-4">Sessions</th>
                <th class="px-8 py-4">Avg WPM</th>
                <th class="px-8 py-4">Max WPM</th>
                <th class="px-8 py-4">Accuracy</th>
                <th class="px-8 py-4">Last Played</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
              <tr v-for="student in reportData.students" :key="student.user_id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                <td class="px-8 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center font-bold text-slate-500 overflow-hidden">
                      <img v-if="student.user.avatar" :src="student.user.avatar" class="w-full h-full object-cover" />
                      <span v-else>{{ student.user.name.charAt(0) }}</span>
                    </div>
                    <span class="font-bold text-slate-900 dark:text-white">{{ student.user.name }}</span>
                  </div>
                </td>
                <td class="px-8 py-4 font-bold text-slate-600 dark:text-slate-400">{{ student.total_sessions }}</td>
                <td class="px-8 py-4 font-black text-primary-500">{{ Math.round(student.avg_wpm) }}</td>
                <td class="px-8 py-4 font-black text-blue-500">{{ student.max_wpm }}</td>
                <td class="px-8 py-4 font-bold" :class="student.avg_accuracy >= 90 ? 'text-green-500' : 'text-yellow-500'">
                  {{ Math.round(student.avg_accuracy) }}%
                </td>
                <td class="px-8 py-4 text-xs text-slate-400 font-medium">
                  {{ student.last_played ? new Date(student.last_played).toLocaleDateString('th-TH') : '-' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>
