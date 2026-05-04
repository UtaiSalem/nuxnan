<template>
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Monthly Scores Trend (Line Chart) -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
      <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">Monthly Performance Trend</h3>
      <div class="h-64">
        <Line :data="trendData" :options="lineOptions" />
      </div>
    </div>

    <!-- Student vs Class Average (Radar Chart) -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
      <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">Subject Comparison</h3>
      <div class="h-64 flex justify-center">
        <Radar :data="comparisonData" :options="radarOptions" />
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  LineElement,
  LinearScale,
  PointElement,
  CategoryScale,
  RadialLinearScale,
  Filler,
  RadarController,
  LineController,
} from 'chart.js'
import { Line, Radar } from 'vue-chartjs'

ChartJS.register(
  Title,
  Tooltip,
  Legend,
  LineElement,
  LinearScale,
  PointElement,
  CategoryScale,
  RadialLinearScale,
  Filler,
  RadarController,
  LineController
)

const props = defineProps({
  trendData: {
    type: Object,
    required: true,
  },
  comparisonData: {
    type: Object,
    required: true,
  },
})

const lineOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: true,
      position: 'bottom',
    },
  },
  scales: {
    y: {
      beginAtZero: true,
      max: 100,
      grid: {
        color: 'rgba(156, 163, 175, 0.1)',
      },
    },
    x: {
      grid: {
        display: false,
      },
    },
  },
}

const radarOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: true,
      position: 'bottom',
    },
  },
  scales: {
    r: {
      angleLines: {
        color: 'rgba(156, 163, 175, 0.2)',
      },
      grid: {
        color: 'rgba(156, 163, 175, 0.2)',
      },
      pointLabels: {
        font: {
          size: 12,
        },
      },
      suggestedMin: 0,
      suggestedMax: 100,
      ticks: {
        display: false,
      },
    },
  },
}
</script>
