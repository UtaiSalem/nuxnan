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
            ผูกบัญชีให้ผู้ปกครอง: {{ guardianName }}
          </h3>
          <button @click="close" aria-label="ปิด" class="flex min-h-[44px] min-w-[44px] flex-shrink-0 items-center justify-center text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 sm:min-h-0 sm:min-w-0 sm:p-1">
            <Icon icon="mdi:close" class="w-5 h-5" />
          </button>
        </div>

        <!-- Form/Body -->
        <div class="p-4 sm:p-6 space-y-4 overflow-y-auto max-h-[70vh]">
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">
              กรอกชื่อผู้ใช้ (username) รหัสส่วนตัว หรือเบอร์โทรศัพท์ ให้ตรงตัว
            </p>
            <div class="flex flex-col sm:flex-row gap-2">
              <input v-model="searchQuery" type="text" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500" placeholder="ชื่อผู้ใช้, รหัสส่วนตัว, เบอร์โทร..." @keyup.enter="doSearch" />
              <button @click="doSearch" :disabled="!canSearch || isSearching" class="min-h-[44px] flex-shrink-0 whitespace-nowrap sm:min-h-0 sm:py-2 rounded-xl bg-purple-600 px-4 text-sm font-semibold text-white disabled:opacity-50">
                <Icon v-if="isSearching" icon="mdi:loading" class="w-4 h-4 mr-1 animate-spin inline" />
                ค้นหาบัญชี
              </button>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
              หมายเหตุ: ค้นด้วยชื่อ-นามสกุลไม่ได้ (โดยตั้งใจ เพื่อความเป็นส่วนตัว)
            </p>
          </div>

          <!-- Results area -->
          <div v-if="hasSearched" class="mt-4">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3 border-b border-gray-100 dark:border-gray-700 pb-2">ผลการค้นหา</h4>
            
            <div v-if="isSearching" class="py-4 text-center text-gray-500 dark:text-gray-400">
              <Icon icon="mdi:loading" class="w-6 h-6 animate-spin mx-auto" />
            </div>
            <div v-else-if="searchError" class="py-4 px-3 text-center text-sm text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900 rounded-lg break-words">
              {{ searchError }}
            </div>
            <div v-else-if="!result" class="py-4 text-center text-sm text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700 rounded-lg">
              ไม่พบบัญชีนี้ ลองตรวจตัวสะกดอีกครั้ง
            </div>
            <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
              <div class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                  <img v-if="result.avatar" :src="result.avatar" class="flex-shrink-0 w-10 h-10 rounded-full object-cover bg-gray-100" />
                  <div v-else class="flex-shrink-0 w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-purple-600 dark:text-purple-400 font-bold">
                    {{ result.name?.charAt(0) || 'U' }}
                  </div>
                  <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 break-words">{{ result.name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">@{{ result.username }}</p>
                  </div>
                </div>
                
                <div v-if="result.already_linked" class="flex-shrink-0 w-full sm:w-auto">
                  <div class="text-xs text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 px-3 py-2 rounded-lg text-center sm:text-left">
                    บัญชีนี้ถูกผูกเป็นผู้ปกครองในโรงเรียนนี้แล้ว
                  </div>
                </div>
                <div v-else class="flex-shrink-0 w-full sm:w-auto">
                  <button @click="sendRequest" :disabled="isSubmitting" class="w-full sm:w-auto min-h-[44px] flex-shrink-0 whitespace-nowrap sm:min-h-0 sm:py-2 rounded-xl bg-purple-600 px-4 text-sm font-semibold text-white disabled:opacity-50">
                    <Icon v-if="isSubmitting" icon="mdi:loading" class="w-4 h-4 mr-1 animate-spin inline" />
                    ส่งคำขอผูกบัญชี
                  </button>
                </div>
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
import { useGuardianAccount } from '~/composables/useGuardianAccount'
import { errorStatus, errorMessage } from '~/composables/useGuardianAppointment'
import { useToast } from '#imports'

const props = defineProps<{
  academyId: number | string
  studentId: number | string
  guardianId: number | null
  guardianName: string
}>()

const emit = defineEmits(['close', 'requested'])

const toast = useToast()
const { isSearching, isSubmitting, searchAccount, createAccountRequest } = useGuardianAccount(props.academyId)

const searchQuery = ref('')
const result = ref<any>(null)
const hasSearched = ref(false)
const searchError = ref('')

const canSearch = computed(() => searchQuery.value.trim().length > 0)

const doSearch = async () => {
  if (!canSearch.value) return
  hasSearched.value = true
  searchError.value = ''
  result.value = null

  try {
    const res: any = await searchAccount(searchQuery.value.trim())
    result.value = res.data
  } catch (err: any) {
    const status = errorStatus(err)
    if (status === 403) {
      searchError.value = 'ไม่มีสิทธิ์ค้นหาบัญชี'
    } else if (status === 429) {
      searchError.value = 'ค้นหาบ่อยเกินไป กรุณารอสักครู่แล้วลองใหม่'
    } else {
      searchError.value = errorMessage(err) || 'เกิดข้อผิดพลาดในการค้นหา'
    }
    toast.add({
      severity: 'error',
      summary: 'ข้อผิดพลาด',
      detail: searchError.value,
      life: 3000
    })
  }
}

const sendRequest = async () => {
  if (!result.value) return
  
  try {
    const payload: any = { user_id: result.value.id }
    if (props.guardianId) {
      payload.guardian_id = props.guardianId
    }
    
    await createAccountRequest(Number(props.studentId), payload)
    
    toast.add({ severity: 'success', summary: 'สำเร็จ', detail: 'ส่งคำขอผูกบัญชีแล้ว รอการตอบรับ', life: 3000 })
    emit('requested')
  } catch (err: any) {
    const status = errorStatus(err)
    let msg = errorMessage(err) || 'ไม่สามารถส่งคำขอได้'
    if (status === 409) {
      msg = 'บัญชีนี้ถูกผูกหรือมีคำขอที่ค้างอยู่แล้ว'
      toast.add({ severity: 'warn', summary: 'ข้อควรระวัง', detail: msg, life: 3000 })
    } else if (status === 403) {
      msg = 'ไม่มีสิทธิ์ดำเนินการ'
      toast.add({ severity: 'error', summary: 'ข้อผิดพลาด', detail: msg, life: 3000 })
    } else {
      toast.add({ severity: 'error', summary: 'ข้อผิดพลาด', detail: msg, life: 3000 })
    }
  }
}

const close = () => {
  emit('close')
}
</script>
