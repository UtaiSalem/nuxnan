<template>
  <div class="bg-white p-4 rounded-xl shadow-lg border border-gray-100">
      <h3 class="font-bold mb-4 ml-4 text-gray-700">ลำดับความคืบหน้าของสมาชิก - {{ groupName }}</h3>
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead>
            <tr class="bg-gray-50 text-gray-600 text-sm">
              <th class="p-3 font-semibold">ชื่อ-นามสกุล</th>
              <th class="p-3 font-semibold text-center">การเข้าเรียน</th>
              <th class="p-3 font-semibold text-center">บทเรียน</th>
              <th class="p-3 font-semibold text-center">งาน</th>
              <th class="p-3 font-semibold text-center">แบบทดสอบ</th>
              <th class="p-3 font-semibold text-center">คะแนนรวม</th>
              <th class="p-3 font-semibold text-center">เกรด</th>
              <th class="p-3 font-semibold text-center">จัดการ</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="member in members" :key="member.id" class="border-t hover:bg-blue-50/50 transition-colors">
              <td class="p-3">
                <div class="font-medium text-gray-800">{{ member.member_name }}</div>
                <div class="text-xs text-gray-500">{{ member.member_code || '-' }}</div>
              </td>
              <td class="p-3 text-center">
                <div class="text-sm font-bold" :class="getAttendanceColor(member.attendance_rate)">
                  {{ member.attendance_rate || 0 }}%
                </div>
              </td>
              <td class="p-3 text-center">
                <div class="w-full bg-gray-200 rounded-full h-2 mb-1">
                  <div class="bg-blue-600 h-2 rounded-full" :style="`width: ${member.lessons_progress || 0}%`"></div>
                </div>
                <span class="text-xs text-gray-500">{{ member.lessons_progress || 0 }}%</span>
              </td>
              <td class="p-3 text-center">
                <div class="w-full bg-gray-200 rounded-full h-2 mb-1">
                  <div class="bg-green-600 h-2 rounded-full" :style="`width: ${member.assignments_progress || 0}%`"></div>
                </div>
                <span class="text-xs text-gray-500">{{ member.assignments_progress || 0 }}%</span>
              </td>
              <td class="p-3 text-center">
                <div class="w-full bg-gray-200 rounded-full h-2 mb-1">
                  <div class="bg-purple-600 h-2 rounded-full" :style="`width: ${member.quizzes_progress || 0}%`"></div>
                </div>
                <span class="text-xs text-gray-500">{{ member.quizzes_progress || 0 }}%</span>
              </td>
              <td class="p-3 text-center">
                <span class="font-bold text-gray-700">{{ member.scores?.total_score || 0 }}</span>
              </td>
              <td class="p-3 text-center">
                <span class="px-2 py-1 rounded text-xs font-bold bg-blue-100 text-blue-700">
                  {{ member.scores?.grade_name || '-' }}
                </span>
              </td>
              <td class="p-3 text-center">
                <button 
                  @click="$emit('view', member)" 
                  class="bg-blue-50 text-blue-600 hover:bg-blue-100 px-3 py-1 rounded-lg text-sm transition-colors font-medium"
                >
                  รายละเอียด
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
  </div>
</template>

<script setup>
defineProps({
    groupName: String,
    members: Array,
    isCourseAdmin: Boolean
});

defineEmits(['view']);

const getAttendanceColor = (rate) => {
  if (!rate) return 'text-gray-400';
  if (rate >= 80) return 'text-green-600';
  if (rate >= 50) return 'text-orange-600';
  return 'text-red-600';
};
</script>
