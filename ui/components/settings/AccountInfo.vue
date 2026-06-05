<script setup lang="ts">
import { ref, watch, onMounted, inject, defineAsyncComponent } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

const api = useApi()
const Security = defineAsyncComponent(() => import('~/components/settings/Security.vue'))

const settingsData = inject<any>('settingsData')
const reloadSettings = inject<() => Promise<void>>('reloadSettings', async () => {})
const markDirty = inject<() => void>('markDirty', () => {})
const markClean = inject<() => void>('markClean', () => {})

const isLoadingAccount = ref(false)

const form = ref({
  username: '',
  name: '',
  phone_number: '',
  email: '',
})

let watcherActive = false
watch(form, () => {
  if (watcherActive) markDirty()
}, { deep: true })

function hydrateForm(data: any) {
  if (!data) return
  form.value = {
    username: data.username || '',
    name: data.name || '',
    phone_number: data.phone_number || '',
    email: data.email || '',
  }
  setTimeout(() => {
    watcherActive = true
  }, 100)
}

onMounted(() => {
  if (settingsData?.value) {
    hydrateForm(settingsData.value)
  }
})

watch(() => settingsData?.value, (newData) => {
  if (newData && !watcherActive) {
    hydrateForm(newData)
  }
}, { immediate: true })

async function saveAccount() {
  isLoadingAccount.value = true
  try {
    const res = await api.post<any>('/api/settings/account', {
      name: form.value.name,
      phone_number: form.value.phone_number,
    })

    if (res.success) {
      markClean()
      await reloadSettings()
      Swal.fire({
        icon: 'success',
        title: 'บันทึกสำเร็จ!',
        text: 'ข้อมูลบัญชีถูกอัปเดตแล้ว',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
      })
    }
  } catch (error: any) {
    Swal.fire('Error', error.data?.message || error.message || 'Update failed', 'error')
  } finally {
    isLoadingAccount.value = false
  }
}
</script>

<template>
  <div class="space-y-6">
    <form
      class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden"
      @submit.prevent="saveAccount"
    >
      <div class="p-4 sm:p-6 border-b border-gray-100 dark:border-gray-700 bg-blue-50/30 dark:bg-blue-900/10">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
          <Icon icon="fluent:person-info-24-regular" class="w-5 h-5 text-blue-500" />
          ข้อมูลบัญชี
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">จัดการข้อมูลสำหรับเข้าสู่ระบบและช่องทางติดต่อ</p>
      </div>

      <div class="p-4 sm:p-6 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Username (ชื่อผู้ใช้)</label>
            <div class="relative">
              <Icon icon="fluent:mention-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
              <input
                v-model="form.username"
                type="text"
                class="pl-10 w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-blue-500 focus:border-blue-500 transition-all"
                placeholder="username"
              />
            </div>
            <p class="mt-1 text-xs text-gray-500">นี่คือชื่อที่จะแสดงใน URL โปรไฟล์และใช้ในการค้นหา (ห้ามซ้ำ)</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อแสดงผล (Display Name)</label>
            <div class="relative">
              <Icon icon="fluent:person-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
              <input
                v-model="form.name"
                type="text"
                class="pl-10 w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-blue-500 focus:border-blue-500 transition-all"
                placeholder="ชื่อของคุณ"
              />
            </div>
            <p class="mt-1 text-xs text-gray-500">ชื่อที่จะแสดงให้คนอื่นเห็นในระบบ (ซ้ำได้)</p>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เบอร์โทรศัพท์</label>
          <div class="relative">
            <Icon icon="fluent:call-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
            <input
              v-model="form.phone_number"
              type="tel"
              class="pl-10 w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-blue-500 focus:border-blue-500 transition-all"
              placeholder="08xxxxxxxx"
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">อีเมล</label>
          <div class="relative opacity-75">
            <Icon icon="fluent:mail-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
            <input
              v-model="form.email"
              type="email"
              readonly
              class="pl-10 w-full rounded-xl border-gray-300 bg-gray-50 dark:bg-gray-700/50 dark:border-gray-600 dark:text-gray-400 cursor-not-allowed"
            />
          </div>
          <p class="mt-1 text-xs text-amber-500">ติดต่อทีมงานเพื่อเปลี่ยนอีเมล</p>
        </div>

        <div class="pt-4 flex justify-end">
          <button
            type="submit"
            :disabled="isLoadingAccount"
            class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium shadow-lg hover:shadow-blue-500/30 transition-all flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
          >
            <Icon v-if="isLoadingAccount" icon="svg-spinners:ring-resize" />
            <Icon v-else icon="fluent:save-24-regular" class="w-4 h-4" />
            บันทึกข้อมูลบัญชี
          </button>
        </div>
      </div>
    </form>

    <Security />
  </div>
</template>
