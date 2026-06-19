<script setup lang="ts">
/**
 * Student — School Attendance Check-In
 * นักเรียนเช็คชื่อผ่าน QR Code หรือรหัส token
 */
import { Icon } from '@iconify/vue'

definePageMeta({ layout: 'main', middleware: ['auth'] })

const route = useRoute()
const schoolApi = useSchoolManagement()
const academyName = computed(() => route.params.name as string)

const academyId = ref<number | null>(Number(route.query.aid) || null)
const sessionId = ref<number | null>(Number(route.query.sid) || null)
const prefillToken = computed(() => (route.query.token as string) || '')

const tokenInput = ref(prefillToken.value)
const manualSessionId = ref<number | null>(sessionId.value)
const isChecking = ref(false)
const checkInResult = ref<'success' | 'late' | 'error' | 'already' | null>(null)
const errorMessage = ref('')

onMounted(async () => {
  if (!academyId.value) {
    const res: any = await useApi().get(`/api/academies/${academyName.value}`)
    if (res?.success) academyId.value = res.academy.id
  }
  if (prefillToken.value && sessionId.value && academyId.value) {
    await doCheckIn()
  }
})

const doCheckIn = async () => {
  if (!academyId.value || !manualSessionId.value || !tokenInput.value || isChecking.value) return
  isChecking.value = true
  checkInResult.value = null
  errorMessage.value = ''
  try {
    const res: any = await schoolApi.schoolAttendanceCheckIn(
      academyId.value,
      manualSessionId.value,
      tokenInput.value,
    )
    if (res?.success) {
      checkInResult.value = res.status === 'late' ? 'late' : 'success'
    } else {
      checkInResult.value = res?.message?.includes('already') ? 'already' : 'error'
      errorMessage.value = res?.message || 'เกิดข้อผิดพลาด'
    }
  } catch (e: any) {
    checkInResult.value = 'error'
    errorMessage.value = e?.data?.message || 'เกิดข้อผิดพลาด'
  } finally {
    isChecking.value = false
  }
}

const retry = () => {
  checkInResult.value = null
  errorMessage.value = ''
  tokenInput.value = ''
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900 flex items-center justify-center p-4">
    <div
      class="w-full max-w-sm bg-white dark:bg-slate-800 rounded-vikinger shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden"
    >
      <!-- Top gradient bar -->
      <div class="h-1.5 bg-gradient-vikinger" />

      <div class="p-8">
        <!-- State: success -->
        <div v-if="checkInResult === 'success'" class="flex flex-col items-center gap-4 text-center animate-bounce-in">
          <div class="w-20 h-20 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
            <Icon icon="mdi:check-circle" class="w-12 h-12 text-green-500" />
          </div>
          <div>
            <h2 class="font-heading text-2xl font-bold text-slate-900 dark:text-white">เช็คชื่อสำเร็จ!</h2>
            <p class="text-slate-500 dark:text-slate-400 mt-1">บันทึกการมาโรงเรียนเรียบร้อย</p>
          </div>
          <span class="px-4 py-2 bg-gradient-vikinger text-white text-sm font-semibold rounded-full shadow-vikinger">
            <Icon icon="fluent:star-24-filled" class="w-4 h-4 inline mr-1" />
            ได้รับแต้ม Play Learn Earn
          </span>
          <NuxtLink
            :to="`/academies/${academyName}`"
            class="mt-2 w-full py-3 bg-gradient-vikinger text-white font-semibold rounded-vikinger shadow-vikinger hover:shadow-vikinger-lg hover:scale-105 transition-all text-center"
          >
            กลับหน้าหลัก
          </NuxtLink>
        </div>

        <!-- State: late -->
        <div v-else-if="checkInResult === 'late'" class="flex flex-col items-center gap-4 text-center">
          <div class="w-20 h-20 rounded-full bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
            <Icon icon="fluent:clock-alarm-24-filled" class="w-12 h-12 text-orange-500" />
          </div>
          <div>
            <h2 class="font-heading text-2xl font-bold text-slate-900 dark:text-white">มาสาย</h2>
            <p class="text-slate-500 dark:text-slate-400 mt-1">บันทึกการมาสายเรียบร้อย</p>
          </div>
          <NuxtLink
            :to="`/academies/${academyName}`"
            class="mt-2 w-full py-3 bg-orange-500 hover:bg-orange-600 text-white font-semibold rounded-vikinger transition-all text-center"
          >
            กลับหน้าหลัก
          </NuxtLink>
        </div>

        <!-- State: already -->
        <div v-else-if="checkInResult === 'already'" class="flex flex-col items-center gap-4 text-center">
          <div class="w-20 h-20 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
            <Icon icon="fluent:checkmark-circle-24-filled" class="w-12 h-12 text-blue-500" />
          </div>
          <div>
            <h2 class="font-heading text-xl font-bold text-slate-900 dark:text-white">เช็คชื่อแล้ว</h2>
            <p class="text-slate-500 dark:text-slate-400 mt-1">คุณได้เช็คชื่อไปแล้วในวันนี้</p>
          </div>
          <NuxtLink
            :to="`/academies/${academyName}`"
            class="mt-2 w-full py-3 bg-gradient-vikinger text-white font-semibold rounded-vikinger shadow-vikinger hover:shadow-vikinger-lg hover:scale-105 transition-all text-center"
          >
            กลับหน้าหลัก
          </NuxtLink>
        </div>

        <!-- State: error -->
        <div v-else-if="checkInResult === 'error'" class="flex flex-col items-center gap-4 text-center">
          <div class="w-20 h-20 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
            <Icon icon="fluent:error-circle-24-regular" class="w-12 h-12 text-red-500" />
          </div>
          <div>
            <h2 class="font-heading text-2xl font-bold text-slate-900 dark:text-white">เช็คชื่อไม่สำเร็จ</h2>
            <p class="text-red-500 dark:text-red-400 mt-1 text-sm">{{ errorMessage }}</p>
          </div>
          <button
            class="mt-2 w-full py-3 bg-gradient-vikinger text-white font-semibold rounded-vikinger shadow-vikinger hover:shadow-vikinger-lg hover:scale-105 transition-all"
            @click="retry"
          >
            ลองใหม่
          </button>
        </div>

        <!-- State: form (default) -->
        <div v-else class="space-y-6">
          <!-- Icon + title -->
          <div class="flex flex-col items-center gap-3 text-center">
            <div class="w-16 h-16 rounded-vikinger bg-gradient-vikinger flex items-center justify-center shadow-vikinger">
              <Icon icon="fluent:qr-code-24-regular" class="w-9 h-9 text-white" />
            </div>
            <div>
              <h1 class="font-heading text-2xl font-bold text-slate-900 dark:text-white">เช็คชื่อการมาโรงเรียน</h1>
              <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">กรอกรหัสที่ครูแสดงบนกระดาน</p>
            </div>
          </div>

          <div class="space-y-4">
            <!-- Session ID (ถ้าไม่มีใน URL) -->
            <div v-if="!sessionId">
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                หมายเลข Session
              </label>
              <input
                v-model.number="manualSessionId"
                type="number"
                placeholder="กรอกหมายเลข session"
                class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none text-center text-lg"
              />
            </div>

            <!-- Token input -->
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                รหัสเช็คชื่อ
              </label>
              <input
                v-model="tokenInput"
                type="text"
                placeholder="กรอกรหัส เช่น ABC12345"
                class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none text-center font-mono text-xl tracking-widest uppercase"
                @keyup.enter="doCheckIn"
              />
            </div>

            <!-- Submit -->
            <button
              class="w-full py-3.5 bg-gradient-vikinger text-white font-bold rounded-vikinger shadow-vikinger hover:shadow-vikinger-lg hover:scale-105 transition-all disabled:opacity-50 disabled:pointer-events-none flex items-center justify-center gap-2 text-lg"
              :disabled="isChecking || !tokenInput || !manualSessionId"
              @click="doCheckIn"
            >
              <Icon
                v-if="isChecking"
                icon="fluent:spinner-ios-20-regular"
                class="w-5 h-5 animate-spin"
              />
              <Icon v-else icon="fluent:person-clock-24-filled" class="w-5 h-5" />
              {{ isChecking ? 'กำลังบันทึก...' : 'เช็คชื่อ' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
