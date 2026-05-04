<script setup>
import { ref, computed } from 'vue'
import { useVisitReports } from '~/composables/useVisitReports.js'

const props = defineProps({
  student: {
    type: Object,
    required: true
  },
  visits: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close'])

const allVisits = ref(props.visits)
const zones = ref([]) // We can pass empty zones if not needed for this view

const { 
  filteredVisits, 
  getStudentFullName, 
  viewVisitDetails,
  selectedVisit,
  showDetailModal,
  closeDetailModal
} = useVisitReports(allVisits, zones)

// Further filter visits for this specific student if allVisits contains more than just this student's visits
const studentVisits = computed(() => {
  return filteredVisits.value.filter(visit => visit.student_id === props.student.id || visit.student?.student_id === props.student.student_id)
})
</script>

<template>
  <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="emit('close')"></div>

      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
              ประวัติการเยี่ยมบ้าน: {{ student.first_name }} {{ student.last_name }}
            </h3>
            <button @click="emit('close')" class="text-gray-400 hover:text-gray-500">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่เยี่ยม</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ผู้เยี่ยม</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">หมายเหตุ</th>
                  <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="visit in studentVisits" :key="visit.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ new Date(visit.visit_date).toLocaleDateString('th-TH') }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ visit.visitor_name || 'ไม่ระบุ' }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span 
                      class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                      :class="{
                        'bg-green-100 text-green-800': visit.visit_status === 'completed',
                        'bg-yellow-100 text-yellow-800': visit.visit_status === 'pending',
                        'bg-red-100 text-red-800': visit.visit_status === 'failed'
                      }"
                    >
                      {{ visit.visit_status === 'completed' ? 'สำเร็จ' : visit.visit_status === 'failed' ? 'ไม่พบนักเรียน' : 'รอดำเนินการ' }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-500 truncate max-w-xs">
                    {{ visit.notes || '-' }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button 
                      @click="viewVisitDetails(visit)"
                      class="text-blue-600 hover:text-blue-900"
                    >
                      ดูรายละเอียด
                    </button>
                  </td>
                </tr>
                <tr v-if="studentVisits.length === 0">
                  <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                    ไม่พบประวัติการเยี่ยมบ้าน
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button
            @click="emit('close')"
            type="button"
            class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto sm:text-sm"
          >
            ปิด
          </button>
        </div>
      </div>
    </div>

    <!-- Nested Visit Detail Modal could go here if needed, but assuming viewVisitDetails might trigger something else or we can just show details -->
  </div>
</template>
