<script setup lang="ts">
/**
 * EventFormModal — สร้าง/แก้ไขกิจกรรมโรงเรียน (SchoolEvent)
 * - เลือก event_type จาก dropdown ที่จัดกลุ่มด้วย schoolEventTypesByGroup()
 * - auto-suggest attendance_pattern จาก DEFAULT_PATTERN_BY_TYPE (ผู้ใช้แก้ไขได้)
 * - ซ่อน fields เฉพาะ one_time (max_participants, requires_registration, registration_deadline)
 *   เมื่อ attendance_pattern เป็น semester/recurring
 */
import { Icon } from '@iconify/vue'
import { computed, onMounted, ref, watch } from 'vue'
import {
  ATTENDANCE_PATTERNS,
  DEFAULT_PATTERN_BY_TYPE,
  schoolEventTypesByGroup,
} from '~/constants/schoolEventTypes'

const props = defineProps<{
  academyId: number
  event?: any | null
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'saved', event: any): void
}>()

const api = useApi()

const isEdit = computed(() => !!props.event)
const saving = ref(false)
const errorMessage = ref('')

const typeGroups = schoolEventTypesByGroup()
const patterns = ATTENDANCE_PATTERNS

// Groups for the academy (text input fallback if API has no results / load fails)
const groups = ref<any[]>([])
const loadingGroups = ref(false)

const form = ref({
  title: '',
  description: '',
  event_type: 'activity',
  attendance_pattern: 'one_time' as 'one_time' | 'semester' | 'recurring',
  group_id: null as number | null,
  location_type: 'onsite' as 'onsite' | 'online' | 'hybrid',
  location: '',
  meeting_url: '',
  start_datetime: '',
  end_datetime: '',
  max_participants: null as number | null,
  requires_registration: false,
  registration_deadline: '',
})

// Track whether the user manually changed attendance_pattern after picking a type,
// so we only auto-suggest when it hasn't been touched yet for the current type.
let userTouchedPattern = false

const showAdvanced = ref(false)
const showPatternOverride = ref(false)

const isOneTime = computed(() => form.value.attendance_pattern === 'one_time')

watch(
  () => form.value.event_type,
  (newType) => {
    if (userTouchedPattern) return
    const suggested = DEFAULT_PATTERN_BY_TYPE[newType]
    if (suggested) {
      form.value.attendance_pattern = suggested
    }
  },
)

const onPatternChange = () => {
  userTouchedPattern = true
}

onMounted(async () => {
  await loadGroups()

  if (props.event) {
    const e = props.event
    form.value = {
      title: e.title || '',
      description: e.description || '',
      event_type: e.event_type || 'activity',
      attendance_pattern: e.attendance_pattern || 'one_time',
      group_id: e.group_id || null,
      location_type: e.location_type || 'onsite',
      location: e.location || '',
      meeting_url: e.meeting_url || '',
      start_datetime: toDatetimeLocal(e.start_datetime),
      end_datetime: toDatetimeLocal(e.end_datetime),
      max_participants: e.max_participants || null,
      requires_registration: !!e.requires_registration,
      registration_deadline: toDatetimeLocal(e.registration_deadline),
    }
    userTouchedPattern = true

    // ถ้า event เดิมมีค่าใน advanced fields อยู่แล้ว ให้ขยายให้เห็นทันที
    // ไม่งั้นผู้ใช้แก้ไข event เก่าจะเห็นแค่ 3 field essential แล้วงงว่าข้อมูลหายไปไหน
    showAdvanced.value = !!(
      e.description ||
      e.group_id ||
      e.location ||
      e.meeting_url ||
      e.end_datetime ||
      e.location_type !== 'onsite' ||
      e.max_participants ||
      e.requires_registration ||
      e.registration_deadline
    )
  }
})

const toDatetimeLocal = (value: string | null | undefined) => {
  if (!value) return ''
  // value may come as "YYYY-MM-DD HH:mm:ss" or ISO — normalize to "YYYY-MM-DDTHH:mm"
  const d = new Date(value)
  if (isNaN(d.getTime())) return ''
  const pad = (n: number) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

const loadGroups = async () => {
  loadingGroups.value = true
  try {
    const res: any = await api.get(`/api/academies/${props.academyId}/groups`)
    if (res?.success) {
      groups.value = res.data?.data || res.data || res.groups || []
    } else if (Array.isArray(res)) {
      groups.value = res
    }
  } catch {
    groups.value = []
  } finally {
    loadingGroups.value = false
  }
}

const handleSubmit = async () => {
  errorMessage.value = ''

  if (!form.value.title || !form.value.start_datetime) {
    errorMessage.value = 'กรุณากรอกชื่อกิจกรรมและวันเวลาเริ่ม'
    return
  }

  saving.value = true
  try {
    const payload: Record<string, any> = {
      title: form.value.title,
      description: form.value.description || null,
      event_type: form.value.event_type,
      attendance_pattern: form.value.attendance_pattern,
      group_id: form.value.group_id || null,
      location_type: form.value.location_type,
      location: form.value.location || null,
      meeting_url: form.value.meeting_url || null,
      start_datetime: form.value.start_datetime,
      end_datetime: form.value.end_datetime || null,
    }

    if (isOneTime.value) {
      payload.max_participants = form.value.max_participants || null
      payload.requires_registration = form.value.requires_registration
      payload.registration_deadline = form.value.registration_deadline || null
    }

    let res: any
    if (isEdit.value) {
      res = await api.patch(`/api/academies/${props.academyId}/events/${props.event.id}`, payload)
    } else {
      res = await api.post(`/api/academies/${props.academyId}/events`, payload)
    }

    if (res?.success !== false) {
      emit('saved', res?.data ?? res?.event ?? res)
    } else {
      errorMessage.value = res?.message || 'ไม่สามารถบันทึกได้'
    }
  } catch (error: any) {
    errorMessage.value = error?.data?.message || error?.message || 'เกิดข้อผิดพลาดในการบันทึก'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <Teleport to="body">
    <div
      class="fixed inset-0 z-[60] flex items-start sm:items-center justify-center p-4 pt-20 sm:pt-4"
      @click.self="$emit('close')"
    >
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="$emit('close')" />

      <div
        class="relative w-full max-w-2xl max-h-[calc(100vh-6rem)] sm:max-h-[85vh] overflow-y-auto bg-white dark:bg-slate-800 rounded-vikinger shadow-xl border border-slate-200 dark:border-slate-700 z-10"
      >
        <!-- Header -->
        <div
          class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-700 sticky top-0 bg-white dark:bg-slate-800 z-10"
        >
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-gradient-vikinger flex items-center justify-center">
              <Icon icon="fluent:calendar-add-24-filled" class="w-5 h-5 text-white" />
            </div>
            <h2 class="font-heading font-bold text-slate-900 dark:text-white">
              {{ isEdit ? 'แก้ไขกิจกรรมโรงเรียน' : 'สร้างกิจกรรมโรงเรียน' }}
            </h2>
          </div>
          <button
            class="p-2 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-all"
            aria-label="ปิด"
            @click="$emit('close')"
          >
            <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5" />
          </button>
        </div>

        <!-- Body -->
        <form class="px-6 py-5 space-y-4" @submit.prevent="handleSubmit">
          <div
            v-if="errorMessage"
            class="px-4 py-3 rounded-lg bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-300 text-sm"
          >
            {{ errorMessage }}
          </div>

          <!-- Title -->
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
              ชื่อกิจกรรม <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.title"
              type="text"
              required
              placeholder="เช่น ทัศนศึกษาประจำปี"
              class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
            />
          </div>

          <!-- Event type -->
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
              ประเภทกิจกรรม <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.event_type"
              required
              class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
            >
              <optgroup v-for="g in typeGroups" :key="g.group" :label="g.group">
                <option v-for="t in g.items" :key="t.key" :value="t.key">{{ t.label }}</option>
              </optgroup>
            </select>
          </div>

          <!-- Attendance pattern summary (auto-suggested) -->
          <div class="flex items-center justify-between gap-3 px-4 py-2.5 rounded-lg bg-slate-50 dark:bg-slate-700/50">
            <span class="text-sm text-slate-600 dark:text-slate-300">
              รูปแบบการเช็คชื่อ:
              <strong class="text-slate-900 dark:text-white">
                {{ patterns.find((p) => p.key === form.attendance_pattern)?.label }}
              </strong>
            </span>
            <button
              type="button"
              class="text-xs font-semibold text-purple-600 dark:text-purple-400 shrink-0"
              @click="showPatternOverride = !showPatternOverride"
            >
              {{ showPatternOverride ? 'ซ่อน' : 'เปลี่ยน' }}
            </button>
          </div>
          <div v-if="showPatternOverride">
            <select
              v-model="form.attendance_pattern"
              class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
              @change="onPatternChange"
            >
              <option v-for="p in patterns" :key="p.key" :value="p.key">{{ p.label }}</option>
            </select>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
              {{ patterns.find((p) => p.key === form.attendance_pattern)?.description }}
            </p>
          </div>

          <!-- Start datetime -->
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
              วันเวลาเริ่ม <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.start_datetime"
              type="datetime-local"
              required
              class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
            />
          </div>

          <!-- Advanced settings toggle -->
          <button
            type="button"
            class="flex items-center gap-2 text-sm font-medium text-purple-600 dark:text-purple-400"
            @click="showAdvanced = !showAdvanced"
          >
            <Icon
              :icon="showAdvanced ? 'fluent:chevron-up-24-regular' : 'fluent:chevron-down-24-regular'"
              class="w-4 h-4"
            />
            ตั้งค่าเพิ่มเติม (รายละเอียด, สถานที่, กลุ่ม, การลงทะเบียน)
          </button>

          <div v-if="showAdvanced" class="space-y-4 pt-2 border-t border-dashed border-slate-200 dark:border-slate-700">
            <!-- Description -->
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">รายละเอียด</label>
              <textarea
                v-model="form.description"
                rows="3"
                placeholder="รายละเอียดกิจกรรม (ถ้ามี)"
                class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none resize-none"
              />
            </div>

            <!-- Group -->
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                กลุ่ม/หน่วยงานเจ้าของกิจกรรม
              </label>
              <select
                v-if="groups.length > 0"
                v-model="form.group_id"
                class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
              >
                <option :value="null">ทั้งโรงเรียน (ไม่ระบุกลุ่ม)</option>
                <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
              </select>
              <p v-else class="text-xs text-slate-500 dark:text-slate-400">
                {{ loadingGroups ? 'กำลังโหลดกลุ่ม...' : 'ไม่พบกลุ่มในโรงเรียนนี้ — กิจกรรมจะถือเป็นกิจกรรมทั้งโรงเรียน' }}
              </p>
            </div>

            <!-- Location -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">รูปแบบสถานที่</label>
                <select
                  v-model="form.location_type"
                  class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
                >
                  <option value="onsite">ในสถานที่ (Onsite)</option>
                  <option value="online">ออนไลน์ (Online)</option>
                  <option value="hybrid">ผสม (Hybrid)</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">สถานที่</label>
                <input
                  v-model="form.location"
                  type="text"
                  placeholder="เช่น หอประชุมโรงเรียน"
                  class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
                />
              </div>
            </div>

            <div v-if="form.location_type !== 'onsite'">
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">ลิงก์ประชุมออนไลน์</label>
              <input
                v-model="form.meeting_url"
                type="url"
                placeholder="https://meet.example.com/..."
                class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
              />
            </div>

            <!-- End datetime -->
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">วันเวลาสิ้นสุด</label>
              <input
                v-model="form.end_datetime"
                type="datetime-local"
                class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
              />
            </div>

            <!-- One-time-only fields -->
            <div v-if="isOneTime" class="space-y-4 pt-2 border-t border-dashed border-slate-200 dark:border-slate-700">
              <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">
                ตั้งค่าการลงทะเบียน (เฉพาะกิจกรรมครั้งเดียว)
              </p>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">จำนวนที่รับสูงสุด</label>
                  <input
                    v-model.number="form.max_participants"
                    type="number"
                    min="0"
                    placeholder="ไม่จำกัด"
                    class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">ปิดรับลงทะเบียนวันที่</label>
                  <input
                    v-model="form.registration_deadline"
                    type="datetime-local"
                    class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
                  />
                </div>
              </div>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  v-model="form.requires_registration"
                  type="checkbox"
                  class="w-4 h-4 text-purple-600 border-slate-300 rounded focus:ring-purple-500"
                />
                <span class="text-sm text-slate-700 dark:text-slate-300">ต้องลงทะเบียนก่อนเข้าร่วม</span>
              </label>
            </div>
          </div>
        </form>

        <!-- Footer -->
        <div
          class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-200 dark:border-slate-700 sticky bottom-0 bg-white dark:bg-slate-800"
        >
          <button
            type="button"
            class="px-4 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-all font-medium"
            @click="$emit('close')"
          >
            ยกเลิก
          </button>
          <button
            type="button"
            class="px-5 py-2 bg-gradient-vikinger text-white font-semibold rounded-lg shadow-vikinger hover:shadow-vikinger-lg hover:scale-105 transition-all disabled:opacity-50 disabled:pointer-events-none flex items-center gap-2"
            :disabled="saving"
            @click="handleSubmit"
          >
            <Icon v-if="saving" icon="fluent:spinner-ios-20-regular" class="w-4 h-4 animate-spin" />
            <Icon v-else icon="fluent:checkmark-24-filled" class="w-4 h-4" />
            {{ isEdit ? 'บันทึกการแก้ไข' : 'สร้างกิจกรรม' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
