<script setup lang="ts">
const props = defineProps<{
  academyName: string
  heading: string
  subheading: string
  viewType: 'classroom' | 'teacher'
  gradeLevel: string | null
  periodSets: any[]
  timetable: any[]
}>()

const { gridDays, gridRows, getScheduleCell } = useScheduleGrid({
  periodSets: computed(() => props.periodSets),
  timetable: computed(() => props.timetable),
  gradeLevel: computed(() => props.gradeLevel),
})

// คิดเวลาพิมพ์ครั้งเดียวฝั่งเบราว์เซอร์ — ถ้าเรียก new Date() ในเทมเพลต
// ค่าที่ SSR เรนเดอร์กับที่ไคลเอนต์เรนเดอร์จะไม่ตรงกัน แล้วได้ hydration mismatch
const printedAt = ref('')

onMounted(() => {
  printedAt.value = new Date().toLocaleString('th-TH')
})
</script>

<template>
  <div class="print-sheet bg-white text-black p-4 sm:p-8">
    <div class="text-center mb-6">
      <h1 class="text-2xl font-bold uppercase mb-1">{{ academyName }}</h1>
      <h2 class="text-xl font-semibold mb-1">{{ heading }}</h2>
      <p class="text-sm">{{ subheading }}</p>
    </div>

    <table class="w-full table-fixed border-collapse border border-black">
      <thead>
        <tr>
          <th class="border border-black px-1 py-3 bg-gray-100 text-center w-[86px]">เวลา</th>
          <th v-for="day in gridDays" :key="day.value" class="border border-black px-1 py-3 bg-gray-100 text-center whitespace-nowrap">
            {{ day.label }}
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in gridRows" :key="row.key">
          <td class="border border-black px-2 py-2 text-center align-top whitespace-nowrap">
            <div class="font-medium text-sm">{{ row.label }}</div>
            <div class="text-[11px]">{{ row.start }} - {{ row.end }}</div>
          </td>
          
          <template v-if="row.type === 'break' || row.type === 'lunch'">
            <td :colspan="gridDays.length" class="border border-black px-2 py-2 bg-gray-200 text-center align-middle font-medium">
              {{ row.label }}
            </td>
          </template>
          <template v-else>
            <td v-for="day in gridDays" :key="day.value" class="border border-black px-2 py-2 align-top h-24">
              <div v-for="schedule in getScheduleCell(day.value, row)" :key="schedule.id" class="mb-2 last:mb-0">
                <div class="font-semibold text-[13px] leading-tight break-words">
                  {{ schedule.display_title || schedule.title || schedule.course?.name }}
                </div>
                <div v-if="schedule.course?.code" class="text-[11px] mt-0.5">
                  {{ schedule.course.code }}
                </div>
                <div class="text-[11px] mt-0.5">
                  {{ viewType === 'classroom' ? schedule.teacher?.name : schedule.classroom?.name }}
                </div>
                <div v-if="schedule.room" class="text-[11px] mt-0.5">
                  {{ schedule.room }}
                </div>
              </div>
            </td>
          </template>
        </tr>
      </tbody>
    </table>

    <div class="mt-4 text-right text-xs">
      <span v-if="printedAt">พิมพ์เมื่อ {{ printedAt }}</span>
    </div>
  </div>
</template>
