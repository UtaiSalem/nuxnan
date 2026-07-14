<script setup lang="ts">
/**
 * Academy Admin — School Events (กิจกรรมโรงเรียน)
 * รายการกิจกรรม + filter + สร้าง/แก้ไขผ่าน EventFormModal
 */
import { Icon } from '@iconify/vue'
import EventFormModal from '~/components/academy/events/EventFormModal.vue'
import EventTypeBadge from '~/components/academy/events/EventTypeBadge.vue'
import { schoolEventTypesByGroup } from '~/constants/schoolEventTypes'

definePageMeta({ layout: 'main' })

const route = useRoute()
const api = useApi()
const eventsApi = useSchoolEvents()
const academyName = computed(() => route.params.name as string)

const academy = ref<any>(null)
const academyId = ref<number | null>(null)
const { isAdmin, can, fetchMyRole } = useAcademyRole(academyId)

const events = ref<any[]>([])
const groups = ref<any[]>([])
const isLoading = ref(true)
const showFormModal = ref(false)
const editingEvent = ref<any | null>(null)

const typeGroups = schoolEventTypesByGroup()

const filters = ref({
  event_type: '',
  group_id: '',
  attendance_pattern: '',
  start_date: '',
  end_date: '',
})

const stats = computed(() => {
  const now = new Date()
  return {
    total: events.value.length,
    upcoming: events.value.filter((e) => e.start_datetime && new Date(e.start_datetime) > now).length,
    published: events.value.filter((e) => e.status === 'published').length,
    draft: events.value.filter((e) => e.status === 'draft').length,
  }
})

onMounted(async () => {
  try {
    const res: any = await api.get(`/api/academies/${academyName.value}`)
    if (res.success) {
      academy.value = res.academy
      academyId.value = res.academy.id
      await fetchMyRole()
      if (!isAdmin.value && !can('events.manage')) {
        navigateTo(`/academies/${academyName.value}`)
        return
      }
      await Promise.all([loadEvents(), loadGroups()])
    }
  } finally {
    isLoading.value = false
  }
})

const loadEvents = async () => {
  if (!academyId.value) return
  const params: Record<string, any> = {}
  if (filters.value.event_type) params.event_type = filters.value.event_type
  if (filters.value.group_id) params.group_id = filters.value.group_id
  if (filters.value.attendance_pattern) params.attendance_pattern = filters.value.attendance_pattern
  if (filters.value.start_date) params.start_date = filters.value.start_date
  if (filters.value.end_date) params.end_date = filters.value.end_date

  const res: any = await eventsApi.getEvents(academyId.value, params)
  if (res?.success !== false) {
    events.value = res?.data?.data || res?.data || res || []
  }
}

const loadGroups = async () => {
  if (!academyId.value) return
  try {
    const res: any = await api.get(`/api/academies/${academyId.value}/groups`)
    if (res?.success) {
      groups.value = res.data?.data || res.data || res.groups || []
    } else if (Array.isArray(res)) {
      groups.value = res
    }
  } catch {
    groups.value = []
  }
}

const openCreateModal = () => {
  editingEvent.value = null
  showFormModal.value = true
}

const openEditModal = (event: any) => {
  editingEvent.value = event
  showFormModal.value = true
}

const onSaved = async () => {
  showFormModal.value = false
  editingEvent.value = null
  await loadEvents()
}

const groupName = (groupId: number | null) => {
  if (!groupId) return null
  return groups.value.find((g) => g.id === groupId)?.name || null
}

const formatDateRange = (start: string, end: string | null) => {
  if (!start) return '-'
  const startDate = new Date(start)
  const opts: Intl.DateTimeFormatOptions = { year: 'numeric', month: 'short', day: 'numeric', weekday: 'short' }
  const timeOpts: Intl.DateTimeFormatOptions = { hour: '2-digit', minute: '2-digit' }
  let result = `${startDate.toLocaleDateString('th-TH', opts)} ${startDate.toLocaleTimeString('th-TH', timeOpts)}`
  if (end) {
    const endDate = new Date(end)
    result += ` - ${endDate.toLocaleTimeString('th-TH', timeOpts)}`
  }
  return result
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900">
    <!-- Skeleton -->
    <div v-if="isLoading" class="space-y-6 p-6">
      <div class="h-40 rounded-vikinger bg-slate-200 dark:bg-slate-700 animate-pulse" />
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div v-for="i in 4" :key="i" class="h-24 rounded-vikinger bg-slate-200 dark:bg-slate-700 animate-pulse" />
      </div>
      <div v-for="i in 3" :key="'sk-' + i" class="h-20 rounded-vikinger bg-slate-200 dark:bg-slate-700 animate-pulse" />
    </div>

    <div v-else class="space-y-6">
      <!-- Header gradient -->
      <div class="bg-gradient-to-br from-violet-600 via-purple-600 to-indigo-700 px-6 py-8">
        <div class="max-w-6xl mx-auto flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-vikinger bg-white/20 flex items-center justify-center shadow-vikinger">
              <Icon icon="fluent:calendar-star-24-filled" class="w-8 h-8 text-white" />
            </div>
            <div>
              <h1 class="font-heading text-2xl font-bold text-white">กิจกรรมโรงเรียน</h1>
              <p class="text-purple-200 text-sm mt-0.5">จัดการกิจกรรม/กำหนดการ — {{ academy?.name }}</p>
            </div>
          </div>
          <button
            class="flex items-center gap-2 px-5 py-2.5 bg-white text-purple-700 font-semibold rounded-vikinger hover:bg-purple-50 transition-all shadow"
            @click="openCreateModal"
          >
            <Icon icon="fluent:add-24-filled" class="w-5 h-5" />
            สร้างกิจกรรม
          </button>
        </div>
      </div>

      <div class="max-w-6xl mx-auto px-6 space-y-6 pb-10">
        <!-- Stats row -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <div
            class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 p-5 flex items-center gap-4"
          >
            <div class="w-11 h-11 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
              <Icon icon="fluent:calendar-multiple-24-regular" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
              <p class="text-2xl font-heading font-bold text-slate-900 dark:text-white">{{ stats.total }}</p>
              <p class="text-sm text-slate-500 dark:text-slate-400">ทั้งหมด</p>
            </div>
          </div>

          <div
            class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 p-5 flex items-center gap-4"
          >
            <div class="w-11 h-11 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
              <Icon icon="fluent:calendar-clock-24-regular" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <p class="text-2xl font-heading font-bold text-slate-900 dark:text-white">{{ stats.upcoming }}</p>
              <p class="text-sm text-slate-500 dark:text-slate-400">กำลังจะถึง</p>
            </div>
          </div>

          <div
            class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 p-5 flex items-center gap-4"
          >
            <div class="w-11 h-11 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
              <Icon icon="fluent:checkmark-circle-24-filled" class="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
            <div>
              <p class="text-2xl font-heading font-bold text-slate-900 dark:text-white">{{ stats.published }}</p>
              <p class="text-sm text-slate-500 dark:text-slate-400">เผยแพร่แล้ว</p>
            </div>
          </div>

          <div
            class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 p-5 flex items-center gap-4"
          >
            <div class="w-11 h-11 rounded-xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
              <Icon icon="fluent:document-24-regular" class="w-6 h-6 text-slate-600 dark:text-slate-400" />
            </div>
            <div>
              <p class="text-2xl font-heading font-bold text-slate-900 dark:text-white">{{ stats.draft }}</p>
              <p class="text-sm text-slate-500 dark:text-slate-400">ร่าง</p>
            </div>
          </div>
        </div>

        <!-- Filter bar -->
        <div
          class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 p-4"
        >
          <div class="flex flex-col sm:flex-row sm:flex-wrap gap-3">
            <select
              v-model="filters.event_type"
              class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
            >
              <option value="">ทุกประเภท</option>
              <optgroup v-for="g in typeGroups" :key="g.group" :label="g.group">
                <option v-for="t in g.items" :key="t.key" :value="t.key">{{ t.label }}</option>
              </optgroup>
            </select>

            <select
              v-model="filters.group_id"
              class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
            >
              <option value="">ทุกกลุ่ม</option>
              <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
            </select>

            <select
              v-model="filters.attendance_pattern"
              class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
            >
              <option value="">ทุกรูปแบบเช็คชื่อ</option>
              <option value="one_time">ครั้งเดียว</option>
              <option value="semester">รายภาคเรียน</option>
              <option value="recurring">ต่อเนื่อง</option>
            </select>

            <input
              v-model="filters.start_date"
              type="date"
              class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
            />
            <input
              v-model="filters.end_date"
              type="date"
              class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
            />

            <button
              class="px-5 py-2 bg-gradient-vikinger text-white font-semibold rounded-lg shadow-vikinger hover:shadow-vikinger-lg hover:scale-105 transition-all"
              @click="loadEvents"
            >
              <Icon icon="fluent:search-24-regular" class="w-4 h-4 inline mr-1" />
              ค้นหา
            </button>
          </div>
        </div>

        <!-- Empty state -->
        <div
          v-if="events.length === 0"
          class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 py-20 flex flex-col items-center gap-4"
        >
          <Icon icon="fluent:calendar-empty-24-regular" class="w-16 h-16 text-slate-300 dark:text-slate-600" />
          <p class="font-heading text-lg font-semibold text-slate-500 dark:text-slate-400">ยังไม่มีกิจกรรม</p>
          <button
            class="px-5 py-2.5 bg-gradient-vikinger text-white font-semibold rounded-vikinger shadow-vikinger hover:shadow-vikinger-lg hover:scale-105 transition-all"
            @click="openCreateModal"
          >
            <Icon icon="fluent:add-24-filled" class="w-4 h-4 inline mr-1" />
            สร้างกิจกรรมแรก
          </button>
        </div>

        <!-- Events list -->
        <div v-else class="space-y-3">
          <div
            v-for="event in events"
            :key="event.id"
            class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 transition-all hover:shadow-lg"
          >
            <div class="p-5 flex flex-col sm:flex-row sm:items-center gap-4">
              <div class="flex items-start gap-4 flex-1 min-w-0">
                <div class="min-w-0">
                  <div class="flex flex-wrap items-center gap-2">
                    <h3 class="font-heading font-bold text-slate-900 dark:text-white truncate">
                      {{ event.title }}
                    </h3>
                    <EventTypeBadge :event_type="event.event_type" />
                    <span
                      v-if="event.status === 'published'"
                      class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300"
                    >
                      เผยแพร่แล้ว
                    </span>
                    <span
                      v-else-if="event.status === 'cancelled'"
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300"
                    >
                      ยกเลิก
                    </span>
                    <span
                      v-else
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400"
                    >
                      ร่าง
                    </span>
                  </div>
                  <div class="flex flex-wrap gap-3 mt-1.5">
                    <span class="text-sm text-slate-500 dark:text-slate-400">
                      <Icon icon="fluent:calendar-24-regular" class="w-4 h-4 inline mr-1" />
                      {{ formatDateRange(event.start_datetime, event.end_datetime) }}
                    </span>
                    <span v-if="event.location" class="text-sm text-slate-500 dark:text-slate-400">
                      <Icon icon="fluent:location-24-regular" class="w-4 h-4 inline mr-1" />
                      {{ event.location }}
                    </span>
                    <span v-if="groupName(event.group_id)" class="text-sm text-slate-500 dark:text-slate-400">
                      <Icon icon="fluent:people-team-24-regular" class="w-4 h-4 inline mr-1" />
                      {{ groupName(event.group_id) }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Action button -->
              <div class="flex gap-2 flex-shrink-0">
                <button
                  class="px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition-all"
                  @click="openEditModal(event)"
                >
                  <Icon icon="fluent:edit-24-regular" class="w-4 h-4 inline mr-1" />
                  แก้ไข
                </button>
                <button
                  class="px-4 py-2 bg-gradient-vikinger text-white text-sm font-semibold rounded-lg shadow-vikinger hover:shadow-vikinger-lg hover:scale-105 transition-all"
                  @click="navigateTo(`/academies/${academyName}/admin/events/${event.id}`)"
                >
                  <Icon icon="fluent:arrow-right-24-regular" class="w-4 h-4 inline mr-1" />
                  จัดการ
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <EventFormModal
      v-if="showFormModal && academyId"
      :academy-id="academyId"
      :event="editingEvent"
      @close="showFormModal = false; editingEvent = null"
      @saved="onSaved"
    />
  </div>
</template>
