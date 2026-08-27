<script setup lang="ts">
import { Icon } from '@iconify/vue'
import MissingSourcesModal from './MissingSourcesModal.vue'

type SortField = 'order_number' | 'name' | 'percentage' | 'grade' | 'last_synced_at'

const props = defineProps<{
  members: any[]
  courseId: string | number
  sortBy?: SortField
  sortOrder?: 'asc' | 'desc'
}>()

const emit = defineEmits<{
  (e: 'sort', field: SortField): void
}>()

const showMissingModal = ref(false)
const selectedMissingSources = ref({})
const expandedMember = ref<number | null>(null)

const openMissingModal = (sources: any) => {
  selectedMissingSources.value = sources
  showMissingModal.value = true
}

const toggleExpand = (id: number) => {
  expandedMember.value = expandedMember.value === id ? null : id
}

const getUser = (item: any) => item.user ?? { avatar: null, name: 'Unknown user', username: '' }

const formatDate = (dateString: string) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleString('th-TH', {
    year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
  })
}

const getGradeColor = (grade: any) => {
  const g = parseFloat(grade)
  if (isNaN(g)) return 'text-gray-500'
  if (g >= 3.5) return 'text-green-600 dark:text-green-400 font-bold'
  if (g >= 2.0) return 'text-blue-600 dark:text-blue-400 font-medium'
  if (g >= 1.0) return 'text-yellow-600 dark:text-yellow-400'
  return 'text-red-600 dark:text-red-400 font-bold'
}

const hasMissing = (item: any) => !!item.has_missing_sources

const sortIcon = (field: SortField) => {
  if (props.sortBy !== field) return 'heroicons:chevron-up-down'
  return props.sortOrder === 'asc' ? 'heroicons:chevron-up' : 'heroicons:chevron-down'
}

const sortClass = (field: SortField) =>
  props.sortBy === field ? 'text-primary-600 dark:text-primary-300' : 'text-gray-500 dark:text-gray-400'

// Dual scrollbar: top scroller mirrors the table container's horizontal scroll.
const topScroller = ref<HTMLDivElement | null>(null)
const tableScroller = ref<HTMLDivElement | null>(null)
const tableWidth = ref(0)
let syncing = false

const measureTable = () => {
  const inner = tableScroller.value?.querySelector('table')
  if (inner) tableWidth.value = inner.scrollWidth
}

const onTopScroll = () => {
  if (syncing || !topScroller.value || !tableScroller.value) return
  syncing = true
  tableScroller.value.scrollLeft = topScroller.value.scrollLeft
  requestAnimationFrame(() => { syncing = false })
}

const onTableScroll = () => {
  if (syncing || !topScroller.value || !tableScroller.value) return
  syncing = true
  topScroller.value.scrollLeft = tableScroller.value.scrollLeft
  requestAnimationFrame(() => { syncing = false })
}

onMounted(() => {
  measureTable()
  window.addEventListener('resize', measureTable)
})
onBeforeUnmount(() => window.removeEventListener('resize', measureTable))
watch(() => props.members, () => nextTick(measureTable), { deep: false })
</script>

<template>
  <!-- Wide screens: full table -->
  <div class="hidden xl:block">
    <!-- Top mirror scrollbar — synced with the table below for easier teacher navigation -->
    <div
      ref="topScroller"
      class="overflow-x-auto overflow-y-hidden sticky top-0 z-30 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600 rounded-t-2xl"
      style="height: 14px;"
      @scroll="onTopScroll"
    >
      <div :style="{ width: tableWidth + 'px', height: '1px' }"></div>
    </div>
    <div
      ref="tableScroller"
      class="overflow-x-auto overscroll-x-contain"
      @scroll="onTableScroll"
    >
    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400" style="min-width: var(--gradebook-table-min-width, 1360px); /* 1360px accommodates 12 columns without squishing content or wrapping text */">
      <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 sticky top-0 z-20 shadow-sm">
        <tr>
          <th class="px-4 py-3 sticky left-0 bg-gray-50 dark:bg-gray-700 z-10 whitespace-nowrap border-r-2 border-gray-200 dark:border-gray-600 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]" style="width: 240px; min-width: 240px;">
            <button class="flex items-center gap-1" :class="sortClass('name')" @click="emit('sort', 'name')">
              นักเรียน
              <Icon :icon="sortIcon('name')" class="w-3 h-3" />
            </button>
          </th>
          <th class="px-4 py-3 text-center whitespace-nowrap">Quiz</th>
          <th class="px-4 py-3 text-center whitespace-nowrap">Lesson Q</th>
          <th class="px-4 py-3 text-center whitespace-nowrap">Course Asg</th>
          <th class="px-4 py-3 text-center whitespace-nowrap">Lesson Asg</th>
          <th class="px-4 py-3 text-center whitespace-nowrap">External</th>
          <th class="px-4 py-3 text-center whitespace-nowrap">Bonus</th>
          <th class="px-4 py-3 text-center font-bold text-gray-900 dark:text-white">รวม</th>
          <th class="px-4 py-3 text-center whitespace-nowrap">
            <button class="inline-flex items-center gap-1" :class="sortClass('percentage')" @click="emit('sort', 'percentage')">
              %
              <Icon :icon="sortIcon('percentage')" class="w-3 h-3" />
            </button>
          </th>
          <th class="px-4 py-3 text-center whitespace-nowrap">
            <button class="inline-flex items-center gap-1" :class="sortClass('grade')" @click="emit('sort', 'grade')">
              เกรด
              <Icon :icon="sortIcon('grade')" class="w-3 h-3" />
            </button>
          </th>
          <th class="px-4 py-3 text-center">สถานะ</th>
          <th class="px-4 py-3 whitespace-nowrap">
            <button class="inline-flex items-center gap-1" :class="sortClass('last_synced_at')" @click="emit('sort', 'last_synced_at')">
              ซิงค์ล่าสุด
              <Icon :icon="sortIcon('last_synced_at')" class="w-3 h-3" />
            </button>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in members" :key="item.member_id" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
          <td class="px-4 py-4 sticky left-0 bg-white dark:bg-gray-800 z-10 font-medium text-gray-900 dark:text-white border-r-2 border-gray-100 dark:border-gray-700 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]" style="width: 240px; min-width: 240px;">
            <div class="flex items-center gap-2">
              <CircleAvatar :src="getUser(item).avatar" :name="getUser(item).name" size="xs" />
              <div class="flex flex-col min-w-0">
                <span class="truncate flex items-center gap-1">
                  <span v-if="item.order_number" class="text-[10px] text-gray-500">#{{ item.order_number }}</span>
                  <span class="truncate">{{ getUser(item).name }}</span>
                </span>
                <span v-if="getUser(item).username" class="text-[10px] text-gray-500 truncate">{{ getUser(item).username }}</span>
              </div>
            </div>
          </td>
          <td class="px-4 py-4 text-center whitespace-nowrap">{{ item.breakdown.course_quiz.earned }}/{{ item.breakdown.course_quiz.max }}</td>
          <td class="px-4 py-4 text-center whitespace-nowrap">{{ item.breakdown.lesson_question.earned }}/{{ item.breakdown.lesson_question.max }}</td>
          <td class="px-4 py-4 text-center whitespace-nowrap">{{ item.breakdown.course_assignment.earned }}/{{ item.breakdown.course_assignment.max }}</td>
          <td class="px-4 py-4 text-center whitespace-nowrap">{{ item.breakdown.lesson_assignment.earned }}/{{ item.breakdown.lesson_assignment.max }}</td>
          <td class="px-4 py-4 text-center whitespace-nowrap">{{ item.breakdown.external.earned }}/{{ item.breakdown.external.max }}</td>
          <td class="px-4 py-4 text-center whitespace-nowrap" :class="{ 'text-green-500': item.breakdown.bonus > 0, 'text-red-500': item.breakdown.bonus < 0 }">
            {{ item.breakdown.bonus > 0 ? '+' : '' }}{{ item.breakdown.bonus }}
          </td>
          <td class="px-4 py-4 text-center font-bold text-gray-900 dark:text-white whitespace-nowrap">
            {{ item.breakdown.total_earned }}/{{ item.breakdown.total_max }}
          </td>
          <td class="px-4 py-4 text-center whitespace-nowrap">
            <span class="px-2 py-1 rounded text-xs font-semibold" :class="item.breakdown.percentage >= 50 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
              {{ item.breakdown.percentage }}%
            </span>
          </td>
          <td class="px-4 py-4 text-center whitespace-nowrap" :class="getGradeColor(item.grade_progress)">{{ item.grade_progress }}</td>
          <td class="px-4 py-4 text-center whitespace-nowrap">
            <button
              v-if="hasMissing(item)"
              @click="openMissingModal(item.breakdown.missing_sources)"
              class="text-xs text-amber-600 hover:text-amber-700 underline inline-flex items-center justify-center gap-1 mx-auto whitespace-nowrap"
            >
              <Icon icon="heroicons:exclamation-triangle" class="w-3 h-3" />
              <span class="hidden sm:inline">ยังไม่ครบ</span>
            </button>
            <span v-else class="text-xs text-green-600 inline-flex items-center justify-center gap-1 whitespace-nowrap">
              <Icon icon="heroicons:check-circle" class="w-3 h-3" />
              <span class="hidden sm:inline">ครบถ้วน</span>
            </span>
          </td>
          <td class="px-4 py-4 text-xs whitespace-nowrap">{{ formatDate(item.last_synced_at) }}</td>
        </tr>
      </tbody>
    </table>
    </div>
  </div>

  <!-- Small / medium screens: card list -->
  <div class="xl:hidden divide-y divide-gray-100 dark:divide-gray-700">
    <!-- Sort controls for mobile -->
    <div class="p-3 bg-gray-50 dark:bg-gray-700/30 flex items-center justify-between gap-2 text-sm border-b border-gray-200 dark:border-gray-700 rounded-t-2xl">
      <span class="text-gray-500 font-medium whitespace-nowrap">จัดเรียง:</span>
      <div class="flex items-center gap-2 flex-1 max-w-[240px]">
        <select 
          :value="sortBy" 
          @change="e => emit('sort', (e.target as HTMLSelectElement).value as SortField)"
          class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg px-2 py-1.5 flex-1 text-sm focus:ring-primary-500 focus:border-primary-500"
        >
          <option value="order_number">รหัสนักเรียน</option>
          <option value="name">ชื่อ-นามสกุล</option>
          <option value="percentage">เปอร์เซ็นต์รวม</option>
          <option value="grade">เกรด</option>
          <option value="last_synced_at">ซิงค์ล่าสุด</option>
        </select>
        <button
          @click="emit('sort', sortBy as SortField)"
          class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex-shrink-0"
        >
          <Icon :icon="sortOrder === 'asc' ? 'heroicons:arrow-up' : 'heroicons:arrow-down'" class="w-4 h-4 text-gray-600 dark:text-gray-300" />
        </button>
      </div>
    </div>

    <div v-for="item in members" :key="item.member_id" class="p-4">
      <div class="flex items-center gap-3">
        <CircleAvatar :src="getUser(item).avatar" :name="getUser(item).name" size="sm" />
        <div class="flex-1 min-w-0">
          <div class="font-medium text-gray-900 dark:text-white truncate flex items-center gap-1.5">
            <span v-if="item.order_number" class="text-xs font-semibold px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded">
              #{{ item.order_number }}
            </span>
            <span class="truncate">{{ getUser(item).name }}</span>
          </div>
          <div v-if="getUser(item).username" class="text-[11px] text-gray-500 truncate mt-0.5">{{ getUser(item).username }}</div>
        </div>
        <div class="text-right flex-shrink-0">
          <div class="text-base font-bold" :class="getGradeColor(item.grade_progress)">{{ item.grade_progress }}</div>
          <div class="text-[11px] font-medium text-gray-500" :class="item.breakdown.percentage >= 50 ? 'text-green-600' : 'text-red-600'">
            {{ item.breakdown.percentage }}%
          </div>
        </div>
      </div>

      <div class="mt-3 flex items-center justify-between text-xs bg-gray-50 dark:bg-gray-800/50 p-2 rounded-lg border border-gray-100 dark:border-gray-700">
        <span class="text-gray-600 dark:text-gray-400">
          รวม <b class="text-gray-900 dark:text-white text-sm">{{ item.breakdown.total_earned }}</b>/{{ item.breakdown.total_max }}
        </span>
        <button
          v-if="hasMissing(item)"
          @click="openMissingModal(item.breakdown.missing_sources)"
          class="min-h-[44px] sm:min-h-0 text-amber-600 hover:text-amber-700 underline flex items-center gap-1 px-2 py-1 rounded bg-amber-50 dark:bg-amber-900/20"
        >
          <Icon icon="heroicons:exclamation-triangle" class="w-4 h-4" />
          <span class="font-medium hidden sm:inline">ยังไม่ครบ</span>
        </button>
        <span v-else class="text-green-600 flex items-center gap-1 px-2 py-1">
          <Icon icon="heroicons:check-circle" class="w-4 h-4" />
          <span class="font-medium hidden sm:inline">ครบถ้วน</span>
        </span>
      </div>

      <button
        class="min-h-[44px] sm:min-h-0 mt-3 w-full text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 flex items-center justify-center gap-1 py-1.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 transition-colors"
        @click="toggleExpand(item.member_id)"
      >
        {{ expandedMember === item.member_id ? 'ซ่อน breakdown' : 'ดู breakdown' }}
        <Icon :icon="expandedMember === item.member_id ? 'heroicons:chevron-up' : 'heroicons:chevron-down'" class="w-3 h-3" />
      </button>

      <div v-if="expandedMember === item.member_id" class="mt-2 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300 p-3 bg-gray-50 dark:bg-gray-800/80 rounded-lg border border-gray-100 dark:border-gray-700">
        <div class="flex justify-between"><span>Quiz:</span> <b>{{ item.breakdown.course_quiz.earned }}/{{ item.breakdown.course_quiz.max }}</b></div>
        <div class="flex justify-between"><span>Lesson Q:</span> <b>{{ item.breakdown.lesson_question.earned }}/{{ item.breakdown.lesson_question.max }}</b></div>
        <div class="flex justify-between"><span>Course Asg:</span> <b>{{ item.breakdown.course_assignment.earned }}/{{ item.breakdown.course_assignment.max }}</b></div>
        <div class="flex justify-between"><span>Lesson Asg:</span> <b>{{ item.breakdown.lesson_assignment.earned }}/{{ item.breakdown.lesson_assignment.max }}</b></div>
        <div class="flex justify-between"><span>External:</span> <b>{{ item.breakdown.external.earned }}/{{ item.breakdown.external.max }}</b></div>
        <div class="flex justify-between"><span>Bonus:</span> <b :class="item.breakdown.bonus > 0 ? 'text-green-500' : item.breakdown.bonus < 0 ? 'text-red-500' : ''">{{ item.breakdown.bonus > 0 ? '+' : '' }}{{ item.breakdown.bonus }}</b></div>
        <div class="col-span-2 text-[10px] text-gray-400 pt-2 mt-1 border-t border-gray-200 dark:border-gray-600 text-center">
          ซิงค์ล่าสุด {{ formatDate(item.last_synced_at) }}
        </div>
      </div>
    </div>
  </div>

  <MissingSourcesModal
    :show="showMissingModal"
    :missing-sources="selectedMissingSources"
    :course-id="courseId"
    @close="showMissingModal = false"
  />
</template>
