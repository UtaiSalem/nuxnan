<template>
  <div class="school-management">
    <!-- Tab Navigation -->
    <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
      <nav class="-mb-px flex flex-wrap gap-2 sm:gap-4" aria-label="Tabs">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            activeTab === tab.id
              ? 'border-primary-500 text-primary-600 dark:text-primary-400'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
            'flex items-center gap-2 py-3 px-1 border-b-2 font-medium text-sm whitespace-nowrap transition-colors'
          ]"
        >
          <Icon :icon="tab.icon" class="h-5 w-5" />
          <span class="hidden sm:inline">{{ tab.name }}</span>
        </button>
      </nav>
    </div>

    <!-- Tab Content -->
    <div class="tab-content">
      <!-- Academic Tab -->
      <div v-if="activeTab === 'academic'">
        <SchoolAcademicTab :academy-id="academyId" />
      </div>

      <!-- Finance Tab -->
      <div v-else-if="activeTab === 'finance'">
        <SchoolFinanceTab :academy-id="academyId" />
      </div>

      <!-- Staff Tab -->
      <div v-else-if="activeTab === 'staff'">
        <SchoolStaffTab :academy-id="academyId" />
      </div>

      <!-- Communication Tab -->
      <div v-else-if="activeTab === 'communication'">
        <SchoolCommunicationTab :academy-id="academyId" />
      </div>

      <!-- Reports Tab -->
      <div v-else-if="activeTab === 'reports'">
        <SchoolReportsTab :academy-id="academyId" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps<{
  academyId: number
}>()

const activeTab = ref('academic')

const tabs = [
  { id: 'academic', name: 'วิชาการ', icon: 'heroicons:academic-cap' },
  { id: 'finance', name: 'การเงิน', icon: 'heroicons:banknotes' },
  { id: 'staff', name: 'บุคลากร', icon: 'heroicons:user-group' },
  { id: 'communication', name: 'สื่อสาร', icon: 'heroicons:megaphone' },
  { id: 'reports', name: 'รายงาน', icon: 'heroicons:chart-bar' },
]
</script>
