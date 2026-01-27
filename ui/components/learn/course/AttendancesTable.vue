<script setup lang="ts">
import { Icon } from '@iconify/vue'
import AttendanceStatusBadge from '~/components/learn/course/AttendanceStatusBadge.vue'

const { getAvatarUrl } = useAvatar()

interface Props {
  attendances: any[]
  groupMembers: any[]
  isCourseAdmin?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isCourseAdmin: false
})

const emit = defineEmits(['create', 'view-details', 'edit', 'delete', 'update-status'])

// Format date
const formatDate = (dateString: string) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('th-TH', { day: 'numeric', month: 'short' })
}

// Sort attendances by date (oldest first - latest on the right)
const sortedAttendances = computed(() => {
  return [...props.attendances].sort((a, b) => {
    const dateA = new Date(a.date || a.start_at || a.created_at).getTime()
    const dateB = new Date(b.date || b.start_at || b.created_at).getTime()
    return dateA - dateB // เรียงจากเก่าไปใหม่ (ล่าสุดอยู่ขวาสุด)
  })
})

// Find member's attendance status
const getMemberStatus = (attendance: any, memberId: number) => {
  const detail = attendance.details?.find((d: any) => d.course_member_id === memberId)
  return detail?.status
}

// Get avatar for member using useAvatar composable
const getMemberAvatar = (member: any) => {
  return getAvatarUrl(member.user || member)
}

// Handle image error
const handleImageError = (event: Event) => {
  const img = event.target as HTMLImageElement
  const name = img.alt || 'User'
  // Fallback to UI Avatars
  img.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&color=7F9CF5&background=EBF4FF`
}

// Scroll to end (latest attendances)
const tableContainer = ref<HTMLElement | null>(null)

const scrollToEnd = async () => {
  await nextTick()
  if (tableContainer.value) {
    tableContainer.value.scrollLeft = tableContainer.value.scrollWidth
  }
}

onMounted(() => {
  scrollToEnd()
})

watch(() => props.attendances, () => {
  scrollToEnd()
}, { deep: true })
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div ref="tableContainer" class="relative overflow-x-auto">
      <table class="w-full">
        <!-- Table Header -->
        <thead class="bg-gradient-to-r from-gray-50 via-gray-100 to-slate-100 dark:from-gray-700 dark:via-gray-800 dark:to-gray-700 border-b-2 border-gray-200 dark:border-gray-600">
          <tr class="text-center">
            <!-- Member column -->
            <th scope="col" class="px-6 py-4 border border-slate-300 dark:border-gray-600 font-black text-gray-800 dark:text-gray-200 bg-white dark:bg-gray-800 min-w-[280px] sticky left-0 z-20">
              <div class="flex items-center justify-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-violet-100 to-purple-100 dark:from-violet-900 dark:to-purple-900 rounded-xl flex items-center justify-center shadow-sm">
                  <Icon icon="fluent:people-24-filled" class="w-5 h-5 text-violet-600 dark:text-violet-400" />
                </div>
                <span class="uppercase tracking-wide">สมาชิก</span>
              </div>
            </th>

            <!-- Attendance columns -->
            <th 
              scope="col" 
              v-for="(attendance, index) in sortedAttendances" 
              :key="attendance.id"
              class="px-3 py-4 border border-slate-300 dark:border-gray-600 min-w-[100px]"
            >
              <button
                @click="emit('view-details', attendance)"
                class="flex flex-col justify-center items-center mx-auto text-sm font-bold text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-xl px-3 py-2 transition-all duration-300 transform hover:scale-105 shadow-sm hover:shadow-md"
              >
                <span class="text-xs text-gray-500 dark:text-gray-400">#{{ index + 1 }}</span>
                <span class="mt-1">{{ formatDate(attendance.date) }}</span>
                <Icon icon="fluent:eye-24-regular" width="16" height="16" class="mt-1" />
              </button>
            </th>

            <!-- Add new column -->
            <th v-if="isCourseAdmin" scope="col" class="px-3 py-4 border bg-white dark:bg-gray-800 sticky right-0 z-20">
              <button
                @click="emit('create')"
                class="flex justify-center items-center mx-auto text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-xl p-3 transition-all duration-300 transform hover:scale-105 shadow-sm hover:shadow-md"
                title="เพิ่มการเช็คชื่อใหม่"
              >
                <Icon icon="fluent:add-circle-24-filled" width="24" height="24" />
              </button>
            </th>
          </tr>
        </thead>
        
        <!-- Table Body -->
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
          <tr 
            v-for="member in groupMembers" 
            :key="member.id"
            class="hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 dark:hover:from-blue-900/20 dark:hover:to-indigo-900/20 transition-all duration-200"
          >
            <!-- Member info -->
            <td class="p-2 border border-slate-300 dark:border-gray-600 whitespace-nowrap sticky left-0 z-10 bg-white dark:bg-gray-800">
              <div class="flex items-center min-w-fit group">
                <div class="relative">
                  <img
                    class="w-14 h-14 rounded-full border-2 border-white dark:border-gray-700 shadow-md transition-all duration-300 group-hover:scale-105 group-hover:shadow-lg object-cover"
                    :src="getMemberAvatar(member)"
                    :alt="member.user?.name || member.name || 'Avatar'"
                    @error="handleImageError"
                  >
                  <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 rounded-full border-2 border-white dark:border-gray-800"></div>
                </div>
                
                <div class="ml-3 flex-1">
                  <p class="text-gray-900 dark:text-gray-100 font-bold text-md tracking-wide group-hover:text-blue-700 dark:group-hover:text-blue-400 transition-colors duration-200">
                    {{ member.name || member.member_name || member.user?.name || 'ไม่มีชื่อ' }}
                  </p>
                  <div class="flex items-center mt-1 space-x-2 text-xs">
                    <!-- Order number -->
                    <span v-if="member.order_number" class="inline-flex items-center gap-1 px-2 py-1 bg-gradient-to-r from-violet-100 to-purple-100 dark:from-violet-900/50 dark:to-purple-900/50 text-violet-700 dark:text-violet-300 rounded-full font-medium border border-violet-200 dark:border-violet-700">
                      <Icon icon="fluent:number-symbol-square-20-regular" width="14" height="14" />
                      <span>{{ member.order_number }}</span>
                    </span>
                    
                    <!-- Group badge -->
                    <span v-if="member.group" class="inline-flex items-center gap-1 px-2 py-1 bg-gradient-to-r from-blue-100 to-indigo-100 dark:from-blue-900/50 dark:to-indigo-900/50 text-blue-700 dark:text-blue-300 rounded-full font-medium border border-blue-200 dark:border-blue-700">
                      <Icon icon="fluent:people-team-20-regular" width="14" height="14" />
                      <span>{{ member.group.name }}</span>
                    </span>
                  </div>
                </div>
              </div>
            </td>

            <!-- Attendance status cells -->
            <td 
              v-for="attendance in sortedAttendances" 
              :key="attendance.id" 
              class="p-3 whitespace-nowrap border border-slate-300 dark:border-gray-600 text-center"
            >
              <div class="flex justify-center items-center">
                <AttendanceStatusBadge
                  :status="getMemberStatus(attendance, member.id)"
                  :attendanceId="attendance.id"
                  :memberId="member.id"
                  :isCourseAdmin="isCourseAdmin"
                  @update-status="emit('update-status', $event)"
                />
              </div>
            </td>
          </tr>

          <!-- Empty state -->
          <tr v-if="groupMembers.length === 0">
            <td :colspan="attendances.length + (isCourseAdmin ? 2 : 1)" class="p-12 text-center">
              <div class="flex flex-col items-center justify-center">
                <Icon icon="fluent:people-24-regular" class="w-16 h-16 text-gray-400 dark:text-gray-600 mb-4" />
                <p class="text-gray-500 dark:text-gray-400 font-medium">ไม่มีสมาชิกในกลุ่มนี้</p>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
