<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="close"></div>

    <!-- Modal -->
    <div class="flex min-h-full items-center justify-center p-4">
      <div class="relative w-full max-w-lg bg-white dark:bg-gray-800 rounded-xl shadow-xl transform transition-all flex flex-col">
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-4 sm:px-6 border-b border-gray-100 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            <Icon icon="mdi:account-search" class="w-5 h-5 mr-2 inline text-purple-600" />
            ค้นหาผู้ปกครองคนเดิม
          </h3>
          <button @click="close" aria-label="ปิด" class="flex min-h-[44px] min-w-[44px] flex-shrink-0 items-center justify-center text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 sm:min-h-0 sm:min-w-0 sm:p-1">
            <Icon icon="mdi:close" class="w-5 h-5" />
          </button>
        </div>

        <!-- Form/Body -->
        <div class="p-4 sm:p-6 space-y-4 overflow-y-auto max-h-[70vh]">
          <template v-if="mode === 'match'">
            <div class="space-y-3">
              <div>
                <label class="block text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">เลขบัตรประชาชน 13 หลัก</label>
                <input v-model="matchForm.citizen_id" type="text" maxlength="13" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500" placeholder="เลขบัตรประชาชน" />
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">ชื่อจริง</label>
                  <input v-model="matchForm.first_name" type="text" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500" placeholder="ชื่อจริง" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">นามสกุล</label>
                  <input v-model="matchForm.last_name" type="text" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500" placeholder="นามสกุล" />
                </div>
              </div>
              <p class="text-xs text-gray-500 dark:text-gray-400">
                ต้องกรอกเลขบัตรและชื่อ-สกุลให้ตรงกับที่โรงเรียนมีอยู่ จึงจะค้นเจอ
              </p>
              <button @click="doMatch" :disabled="!canMatch || isSearching" class="w-full min-h-[44px] sm:min-h-0 sm:py-2 rounded-xl bg-purple-600 px-4 text-sm font-semibold text-white disabled:opacity-50">
                <Icon v-if="isSearching" icon="mdi:loading" class="w-4 h-4 mr-2 animate-spin inline" />
                ค้นหา
              </button>
            </div>
            
            <div v-if="matchError" class="p-3 bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg text-sm">
              {{ matchError }}
            </div>
          </template>

          <template v-else-if="mode === 'search'">
            <div>
              <input v-model="searchQuery" @input="onSearchInput" type="text" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500" placeholder="ค้นหาชื่อผู้ปกครอง..." />
            </div>
          </template>

          <!-- Results area -->
          <div v-if="hasSearched" class="mt-4">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3 border-b border-gray-100 dark:border-gray-700 pb-2">ผลการค้นหา</h4>
            
            <div v-if="(isSearching || isDebouncing) && mode === 'search'" class="py-4 text-center text-gray-500 dark:text-gray-400">
              <Icon icon="mdi:loading" class="w-6 h-6 animate-spin mx-auto" />
            </div>
            <div v-else-if="searchError" class="py-4 px-3 text-center text-sm text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900 rounded-lg break-words">
              {{ searchError }}
            </div>
            <div v-else-if="results.length === 0" class="py-4 text-center text-sm text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700 rounded-lg">
              <span v-if="mode === 'match'">ไม่พบผู้ปกครองที่ตรงกับข้อมูลนี้ — ถ้าเป็นผู้ปกครองคนใหม่ ให้ใช้ปุ่ม "แก้ไข" เพื่อกรอกข้อมูลแทน</span>
              <span v-else>ไม่พบผู้ปกครองที่ตรงกับคำค้น</span>
            </div>
            <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
              <div v-for="g in results" :key="g.id" class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center">
                <div class="min-w-0 flex-1">
                  <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 break-words">{{ g.full_name }}</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">มีลูกในโรงเรียน {{ g.children_count }} คน</p>
                </div>
                
                <div v-if="appointingId === g.id" class="flex flex-col gap-2 w-full sm:w-auto">
                  <select v-model="selectedGuardianType" class="w-full sm:w-40 px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-lg text-sm">
                    <option :value="null">เลือกความสัมพันธ์</option>
                    <option value="father">บิดา</option>
                    <option value="mother">มารดา</option>
                    <option value="grandfather">ปู่/ตา</option>
                    <option value="grandmother">ย่า/ยาย</option>
                    <option value="uncle">ลุง/อา</option>
                    <option value="aunt">ป้า/น้า</option>
                    <option value="sibling">พี่/น้อง</option>
                    <option value="other">อื่นๆ</option>
                  </select>
                  <div class="flex gap-2">
                    <button @click="appointingId = null" class="flex-1 min-h-[44px] sm:min-h-0 sm:py-2 rounded-xl bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 text-sm font-semibold">ยกเลิก</button>
                    <button @click="confirmAppoint(g)" :disabled="isSubmitting" class="flex-1 min-h-[44px] flex-shrink-0 whitespace-nowrap sm:min-h-0 sm:py-2 rounded-xl bg-purple-600 text-white text-sm font-semibold disabled:opacity-50">ยืนยัน</button>
                  </div>
                </div>
                <template v-else>
                  <div v-if="g.already_linked" class="flex-shrink-0">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">แต่งตั้งไว้แล้ว</span>
                  </div>
                  <button v-else @click="startAppoint(g.id)" class="min-h-[44px] flex-shrink-0 whitespace-nowrap rounded-xl bg-purple-600 px-4 text-sm font-semibold text-white sm:min-h-0 sm:py-2">
                    แต่งตั้ง
                  </button>
                </template>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-4 py-4 sm:px-6 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 rounded-b-xl flex flex-col sm:flex-row justify-end gap-3 mt-auto">
          <button type="button" @click="close" class="w-full sm:w-auto min-h-[44px] sm:min-h-0 sm:py-2 px-4 text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 font-medium transition-colors">
            ปิด
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'
import { useGuardianAppointment, errorMessage, errorStatus } from '~/composables/useGuardianAppointment'
import { useToast } from '#imports'

const props = defineProps<{
  academyId: number | string
  studentId: number | string
  mode: 'search' | 'match'
}>()

const emit = defineEmits(['close', 'appointed'])

const toast = useToast()
const { isSearching, isSubmitting, searchGuardians, matchGuardian, appointGuardian } = useGuardianAppointment(props.academyId, props.studentId)

// Form states
const matchForm = ref({
  citizen_id: '',
  first_name: '',
  last_name: ''
})
const searchQuery = ref('')
const results = ref<any[]>([])
const hasSearched = ref(false)
const matchError = ref('')
const searchError = ref('')
/** True while the debounce timer is pending, so the panel does not flash "not found" before the request even starts. */
const isDebouncing = ref(false)

const canMatch = computed(() => {
  return /^\d{13}$/.test(matchForm.value.citizen_id) && matchForm.value.first_name.trim() !== '' && matchForm.value.last_name.trim() !== ''
})

// Match logic
const doMatch = async () => {
  if (!canMatch.value) return
  matchError.value = ''
  try {
    const res = await matchGuardian(matchForm.value)
    hasSearched.value = true
    if (res.data) {
      results.value = [res.data]
    } else {
      results.value = []
    }
  } catch (err: any) {
    const status = errorStatus(err)
    if (status === 429) {
      matchError.value = 'ค้นหาบ่อยเกินไป กรุณารอสักครู่แล้วลองใหม่'
    } else {
      matchError.value = errorMessage(err) || 'เกิดข้อผิดพลาดในการค้นหา'
    }
  }
}

// Search logic
let searchTimeout: any = null
const onSearchInput = () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  if (searchQuery.value.length < 2) {
    results.value = []
    hasSearched.value = false
    searchError.value = ''
    isDebouncing.value = false
    return
  }
  
  // Flip hasSearched before awaiting, otherwise the results panel — and the spinner inside it —
  // only mount once the request has already finished, so a search never shows it is running.
  hasSearched.value = true
  searchError.value = ''
  isDebouncing.value = true

  searchTimeout = setTimeout(async () => {
    isDebouncing.value = false
    try {
      const res = await searchGuardians(searchQuery.value)
      results.value = res.data || []
    } catch (err) {
      // A toast alone leaves the panel blank, and it is gone in three seconds. Keep the
      // reason on screen next to the empty result the user is staring at.
      results.value = []
      searchError.value = errorStatus(err) === 403
        ? 'ไม่มีสิทธิ์ค้นหาทะเบียนผู้ปกครองของโรงเรียนนี้'
        : (errorMessage(err) || 'เกิดข้อผิดพลาดในการค้นหา กรุณาลองใหม่')
      toast.add({
        severity: 'error',
        summary: 'ข้อผิดพลาด',
        detail: searchError.value,
        life: 3000
      })
    }
  }, 400)
}

// Appoint logic
const appointingId = ref<number | null>(null)
const selectedGuardianType = ref<string | null>(null)

const startAppoint = (id: number) => {
  appointingId.value = id
  selectedGuardianType.value = null
}

const confirmAppoint = async (g: any) => {
  try {
    await appointGuardian({
      guardian_id: g.id,
      guardian_type: selectedGuardianType.value || undefined
    })
    toast.add({ severity: 'success', summary: 'สำเร็จ', detail: 'แต่งตั้งผู้ปกครองสำเร็จ', life: 3000 })
    emit('appointed')
  } catch (err: any) {
    const status = errorStatus(err)
    if (status === 409) {
      toast.add({ severity: 'warn', summary: 'ข้อควรระวัง', detail: 'ผู้ปกครองคนนี้ถูกแต่งตั้งให้นักเรียนคนนี้อยู่แล้ว', life: 3000 })
    } else if (status === 403) {
      toast.add({ severity: 'error', summary: 'ข้อผิดพลาด', detail: 'ไม่มีสิทธิ์แต่งตั้งผู้ปกครองให้นักเรียนคนนี้', life: 3000 })
    } else {
      toast.add({ severity: 'error', summary: 'ข้อผิดพลาด', detail: errorMessage(err) || 'ไม่สามารถแต่งตั้งได้', life: 3000 })
    }
  }
}

const close = () => {
  emit('close')
}
</script>
