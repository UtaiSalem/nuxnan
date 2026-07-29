<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { computed, ref, watch } from 'vue'
import Swal from 'sweetalert2'

interface CourseOption {
  id: number
  name: string
  cover?: string | null
  education_level?: string | null
  education_year?: string | number | null
}

interface Props {
  courses: CourseOption[]
  available: number
  submitting?: boolean
  loadingCourses?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  submitting: false,
  loadingCourses: false,
})

const emit = defineEmits<{
  submit: [payload: { course_id: number; amount: number; purpose?: string }]
  'update:amount': [value: number]
}>()

const fmt = (n: number) => new Intl.NumberFormat('th-TH').format(Math.max(0, Math.round(n || 0)))

const selectedCourseId = ref<number | null>(null)
// เก็บเป็น string เพราะ input[type=number] คืนค่าว่างได้ระหว่างพิมพ์
const amount = ref<string>('')
const purpose = ref('')

/**
 * คอร์สที่เลือกไว้ต้องแสดงได้แม้ถูกตัวกรองซ่อนไป จึงเก็บสำเนาไว้ตอนกดเลือก
 * มิฉะนั้นสรุปรายการและ modal ยืนยันจะว่างเปล่าหลังผู้ใช้เปลี่ยนตัวกรอง
 */
const selectedCourseSnapshot = ref<CourseOption | null>(null)

const selectedCourse = computed(() =>
  props.courses.find(c => c.id === selectedCourseId.value) || selectedCourseSnapshot.value,
)

const selectCourse = (course: CourseOption) => {
  selectedCourseId.value = course.id
  selectedCourseSnapshot.value = course
}

/** ปุ่มลัดจำนวนแต้ม — ตัดตัวเลือกที่เกินยอดที่มีออก */
const amountPresets = computed(() => {
  return [100, 500, 1000, 5000].filter(v => v <= props.available)
})

const purposePresets = [
  'ทุนสนับสนุนการเรียน',
  'รางวัลนักเรียน',
  'กิจกรรมในคอร์ส',
  'อุปกรณ์การเรียน',
]

const normalizedAmount = computed(() => {
  const n = Number(amount.value)
  return Number.isFinite(n) && n > 0 ? Math.floor(n) : 0
})

// แจ้งยอดที่กำลังกรอกกลับไปให้การ์ดยอดคงเหลือแสดงผลแบบสด
watch(normalizedAmount, (v) => emit('update:amount', v))

const amountError = computed(() => {
  if (String(amount.value).trim() === '') return null
  if (normalizedAmount.value < 1) return 'จำนวนแต้มต้องเป็นจำนวนเต็มอย่างน้อย 1 แต้ม'
  if (normalizedAmount.value > props.available) return `แต้มไม่พอ — โอนได้สูงสุด ${fmt(props.available)} แต้ม`
  return null
})

const canSubmit = computed(() =>
  !!selectedCourseId.value
  && normalizedAmount.value >= 1
  && normalizedAmount.value <= props.available
  && !amountError.value
  && !props.submitting,
)

const setAmount = (v: number) => { amount.value = String(v) }
const useAllAvailable = () => { amount.value = String(props.available) }

const togglePurpose = (text: string) => {
  purpose.value = purpose.value.trim() === text ? '' : text
}

const reset = () => {
  selectedCourseId.value = null
  selectedCourseSnapshot.value = null
  amount.value = ''
  purpose.value = ''
}

const confirmAndSubmit = async () => {
  if (!canSubmit.value || !selectedCourse.value) return

  const result = await Swal.fire({
    icon: 'question',
    title: 'ยืนยันการโอนแต้ม?',
    html: `
      <div style="text-align:left;line-height:1.9;color:#4b5563;font-size:15px;">
        <div><b>คอร์สปลายทาง:</b> ${escapeHtml(selectedCourse.value.name)}</div>
        <div><b>จำนวน:</b> ${fmt(normalizedAmount.value)} แต้ม</div>
        <div><b>วัตถุประสงค์:</b> ${purpose.value.trim() ? escapeHtml(purpose.value.trim()) : '—'}</div>
        <div style="margin-top:12px;color:#b45309;font-size:13px;">
          แต้มจะถูกหักจากกระเป๋าโรงเรียนทันที และไม่สามารถยกเลิกได้เอง
        </div>
      </div>
    `,
    showCancelButton: true,
    confirmButtonText: 'ยืนยันโอนแต้ม',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#4f46e5',
    reverseButtons: true,
  })

  if (!result.isConfirmed) return

  emit('submit', {
    course_id: selectedCourseId.value!,
    amount: normalizedAmount.value,
    purpose: purpose.value.trim() || undefined,
  })
}

function escapeHtml(value: string) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')
}

defineExpose({ reset })
</script>

<template>
  <div class="space-y-6">
    <!-- ขั้นที่ 1: เลือกคอร์ส -->
    <section>
      <div class="mb-3 flex items-center gap-2.5">
        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-300">1</span>
        <h3 class="text-base font-bold text-gray-900 dark:text-white">เลือกคอร์สปลายทาง</h3>
      </div>

      <!-- ตัวกรองรายวิชา (ส่งเข้ามาจากหน้าเพจ) -->
      <slot name="filters" />

      <!-- โหลดคอร์ส -->
      <div v-if="loadingCourses" class="grid gap-2 sm:grid-cols-2">
        <div v-for="i in 4" :key="i" class="h-16 animate-pulse rounded-xl bg-gray-100 dark:bg-gray-800" />
      </div>

      <!-- ไม่พบคอร์สตามตัวกรอง -->
      <div
        v-else-if="!courses.length"
        class="rounded-xl border border-dashed border-gray-300 p-6 text-center dark:border-gray-700"
      >
        <Icon icon="fluent:book-24-regular" class="mx-auto h-8 w-8 text-gray-400" />
        <p class="mt-2 text-sm font-medium text-gray-600 dark:text-gray-300">ไม่พบคอร์สตามตัวกรองที่เลือก</p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
          ลองล้างตัวกรอง หรือเลือก "ดูคอร์สทุกปีการศึกษา"
        </p>
      </div>

      <!-- รายการคอร์สแบบการ์ดเลือกได้ -->
      <div v-else class="grid max-h-72 gap-2 overflow-y-auto pr-1 sm:grid-cols-2">
        <button
          v-for="c in courses"
          :key="c.id"
          type="button"
          class="flex items-center gap-3 rounded-xl border-2 p-3 text-left transition"
          :class="selectedCourseId === c.id
            ? 'border-indigo-500 bg-indigo-50 shadow-sm dark:border-indigo-400 dark:bg-indigo-950/40'
            : 'border-gray-200 bg-white hover:border-indigo-300 hover:bg-indigo-50/40 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-indigo-700'"
          @click="selectCourse(c)"
        >
          <span
            class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg"
            :class="selectedCourseId === c.id
              ? 'bg-indigo-500 text-white'
              : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400'"
          >
            <Icon :icon="selectedCourseId === c.id ? 'fluent:checkmark-24-filled' : 'fluent:book-24-regular'" class="h-5 w-5" />
          </span>
          <span class="min-w-0 flex-1">
            <span class="block truncate text-sm font-semibold text-gray-900 dark:text-white">{{ c.name }}</span>
            <span v-if="c.education_level" class="block truncate text-xs text-gray-500 dark:text-gray-400">
              {{ c.education_level }}<template v-if="c.education_year"> · ปี {{ c.education_year }}</template>
            </span>
          </span>
        </button>
      </div>

      <!-- คอร์สที่เลือกไว้ถูกตัวกรองซ่อนอยู่ -->
      <div
        v-if="selectedCourse && !courses.some(c => c.id === selectedCourseId)"
        class="mt-2 flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2.5 text-xs dark:border-indigo-900/50 dark:bg-indigo-950/30"
      >
        <Icon icon="fluent:info-24-regular" class="h-4 w-4 flex-shrink-0 text-indigo-500" />
        <span class="min-w-0 flex-1 text-indigo-700 dark:text-indigo-300">
          เลือกไว้: <b class="font-semibold">{{ selectedCourse.name }}</b> (ไม่อยู่ในตัวกรองปัจจุบัน)
        </span>
        <button
          type="button"
          class="flex-shrink-0 rounded-lg px-2 py-1 font-semibold text-indigo-600 transition hover:bg-indigo-100 dark:text-indigo-300 dark:hover:bg-indigo-900/50"
          @click="selectedCourseId = null; selectedCourseSnapshot = null"
        >
          ยกเลิก
        </button>
      </div>
    </section>

    <!-- ขั้นที่ 2: จำนวนแต้ม -->
    <section>
      <div class="mb-3 flex items-center gap-2.5">
        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-300">2</span>
        <h3 class="text-base font-bold text-gray-900 dark:text-white">ระบุจำนวนแต้ม</h3>
      </div>

      <div class="relative">
        <input
          v-model="amount"
          type="number"
          min="1"
          step="1"
          inputmode="numeric"
          placeholder="0"
          class="w-full rounded-xl border-2 bg-white py-3.5 pl-4 pr-16 text-2xl font-bold outline-none transition dark:bg-gray-900 dark:text-white"
          :class="amountError
            ? 'border-rose-300 focus:border-rose-500 dark:border-rose-800'
            : 'border-gray-200 focus:border-indigo-500 dark:border-gray-700'"
        />
        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-semibold text-gray-400">แต้ม</span>
      </div>

      <p v-if="amountError" class="mt-2 flex items-center gap-1.5 text-sm text-rose-600 dark:text-rose-400">
        <Icon icon="fluent:error-circle-24-regular" class="h-4 w-4 flex-shrink-0" />
        {{ amountError }}
      </p>
      <p v-else class="mt-2 text-xs text-gray-500 dark:text-gray-400">
        กรอกเป็นจำนวนเต็ม โอนได้สูงสุด {{ fmt(available) }} แต้ม
      </p>

      <!-- ปุ่มลัด -->
      <div class="mt-3 flex flex-wrap gap-2">
        <button
          v-for="preset in amountPresets"
          :key="preset"
          type="button"
          class="rounded-lg border px-3 py-1.5 text-sm font-semibold transition"
          :class="normalizedAmount === preset
            ? 'border-indigo-500 bg-indigo-500 text-white'
            : 'border-gray-200 bg-white text-gray-600 hover:border-indigo-300 hover:text-indigo-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300'"
          @click="setAmount(preset)"
        >
          {{ fmt(preset) }}
        </button>
        <button
          v-if="available > 0"
          type="button"
          class="rounded-lg border px-3 py-1.5 text-sm font-semibold transition"
          :class="normalizedAmount === available
            ? 'border-violet-500 bg-violet-500 text-white'
            : 'border-violet-200 bg-violet-50 text-violet-600 hover:border-violet-400 dark:border-violet-900 dark:bg-violet-950/40 dark:text-violet-300'"
          @click="useAllAvailable"
        >
          ทั้งหมด ({{ fmt(available) }})
        </button>
      </div>
    </section>

    <!-- ขั้นที่ 3: วัตถุประสงค์ -->
    <section>
      <div class="mb-3 flex items-center gap-2.5">
        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-300">3</span>
        <h3 class="text-base font-bold text-gray-900 dark:text-white">
          วัตถุประสงค์
          <span class="ml-1 text-xs font-normal text-gray-400">(ไม่บังคับ)</span>
        </h3>
      </div>

      <div class="mb-2 flex flex-wrap gap-2">
        <button
          v-for="p in purposePresets"
          :key="p"
          type="button"
          class="rounded-full border px-3 py-1.5 text-xs font-medium transition"
          :class="purpose.trim() === p
            ? 'border-indigo-500 bg-indigo-500 text-white'
            : 'border-gray-200 bg-white text-gray-600 hover:border-indigo-300 hover:text-indigo-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300'"
          @click="togglePurpose(p)"
        >
          {{ p }}
        </button>
      </div>

      <textarea
        v-model="purpose"
        rows="3"
        maxlength="500"
        placeholder="เช่น จัดสรรทุนสำหรับกิจกรรมกลุ่มในคอร์สนี้"
        class="w-full resize-none rounded-xl border border-gray-200 bg-white p-3 text-sm outline-none transition focus:border-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
      />
      <p class="mt-1 text-right text-xs text-gray-400">{{ purpose.length }}/500</p>
    </section>

    <!-- สรุปก่อนยืนยัน -->
    <div
      v-if="selectedCourse && normalizedAmount > 0 && !amountError"
      class="rounded-xl border border-indigo-200 bg-indigo-50/60 p-4 dark:border-indigo-900/50 dark:bg-indigo-950/30"
    >
      <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-indigo-500 dark:text-indigo-300">สรุปรายการ</p>
      <div class="flex items-center gap-3">
        <div class="min-w-0 flex-1 text-right">
          <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">กระเป๋าโรงเรียน</p>
          <p class="text-xs text-gray-500 dark:text-gray-400">−{{ fmt(normalizedAmount) }} แต้ม</p>
        </div>
        <Icon icon="fluent:arrow-right-24-filled" class="h-5 w-5 flex-shrink-0 text-indigo-500" />
        <div class="min-w-0 flex-1">
          <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ selectedCourse.name }}</p>
          <p class="text-xs text-emerald-600 dark:text-emerald-400">+{{ fmt(normalizedAmount) }} แต้ม</p>
        </div>
      </div>
    </div>

    <!-- ปุ่มยืนยัน -->
    <button
      type="button"
      :disabled="!canSubmit"
      class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-3.5 text-base font-bold text-white shadow-sm transition hover:from-indigo-700 hover:to-violet-700 disabled:cursor-not-allowed disabled:from-gray-300 disabled:to-gray-300 disabled:text-gray-500 dark:disabled:from-gray-700 dark:disabled:to-gray-700"
      @click="confirmAndSubmit"
    >
      <Icon v-if="submitting" icon="svg-spinners:ring-resize" class="h-5 w-5" />
      <Icon v-else icon="fluent:arrow-swap-24-regular" class="h-5 w-5" />
      {{ submitting ? 'กำลังโอน...' : 'โอนแต้มให้คอร์ส' }}
    </button>

    <p v-if="!selectedCourseId" class="-mt-3 text-center text-xs text-gray-400">
      เลือกคอร์สและระบุจำนวนแต้มก่อนจึงจะโอนได้
    </p>
  </div>
</template>
