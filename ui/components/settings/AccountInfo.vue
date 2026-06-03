<script setup lang="ts">
import { ref, watch, onMounted, inject } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

const api = useApi()
const authStore = useAuthStore()

const markDirty = inject<() => void>('markDirty', () => {})
const markClean = inject<() => void>('markClean', () => {})

const isLoadingAccount = ref(false)
const isLoadingPassword = ref(false)
const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)

const form = ref({
  phone_number: '',
  email: '',
})

const passwordForm = ref({
  current_password: '',
  password: '',
  password_confirmation: '',
})

let watcherActive = false
watch([form, passwordForm], () => {
  if (watcherActive) markDirty()
}, { deep: true })

onMounted(() => {
  if (authStore.user) {
    form.value.phone_number = authStore.user.phone_number || ''
    form.value.email = authStore.user.email || ''
  }
  watcherActive = true
})

async function saveAccount() {
  isLoadingAccount.value = true
  try {
    const res = await api.post<any>('/api/settings/account', {
      phone_number: form.value.phone_number,
    })

    if (res.success) {
      markClean()
      await authStore.fetchUser()
      Swal.fire({
        icon: 'success',
        title: 'Saved!',
        text: 'Account information updated.',
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

async function updatePassword() {
  if (passwordForm.value.password !== passwordForm.value.password_confirmation) {
    Swal.fire('Error', 'รหัสผ่านใหม่ไม่ตรงกัน', 'error')
    return
  }

  isLoadingPassword.value = true
  try {
    const res = await api.post<any>('/api/settings/password', passwordForm.value)

    if (res.success) {
      markClean()
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'เปลี่ยนรหัสผ่านสำเร็จ',
        timer: 3000,
        showConfirmButton: false,
      })
      passwordForm.value = { current_password: '', password: '', password_confirmation: '' }
    }
  } catch (error: any) {
    Swal.fire('Error', error.data?.message || error.message || 'เปลี่ยนรหัสผ่านไม่สำเร็จ', 'error')
  } finally {
    isLoadingPassword.value = false
  }
}
</script>

<template>
  <div class="space-y-6">
    <form
      class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden"
      @submit.prevent="saveAccount"
    >
      <div class="p-4 sm:p-6 border-b border-gray-100 dark:border-gray-700">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
          <Icon icon="fluent:person-info-24-regular" class="w-5 h-5 text-blue-500" />
          ข้อมูลบัญชี
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">จัดการข้อมูลสำหรับเข้าสู่ระบบและช่องทางติดต่อ</p>
      </div>

      <div class="p-4 sm:p-6 space-y-6">
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

        <div class="pt-4 flex justify-end">
          <button
            type="submit"
            :disabled="isLoadingAccount"
            class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium shadow-lg hover:shadow-blue-500/30 transition-all flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
          >
            <Icon v-if="isLoadingAccount" icon="svg-spinners:ring-resize" />
            บันทึกข้อมูล
          </button>
        </div>
      </div>
    </form>

    <form
      class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden"
      @submit.prevent="updatePassword"
    >
      <div class="p-4 sm:p-6 border-b border-gray-100 dark:border-gray-700">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
          <Icon icon="fluent:shield-lock-24-regular" class="w-5 h-5 text-orange-500" />
          เปลี่ยนรหัสผ่าน
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">ตรวจสอบว่าบัญชีของคุณใช้รหัสผ่านที่รัดกุม</p>
      </div>

      <div class="p-4 sm:p-6 space-y-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รหัสผ่านปัจจุบัน</label>
          <div class="relative">
            <input
              v-model="passwordForm.current_password"
              :type="showCurrentPassword ? 'text' : 'password'"
              class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-orange-500 focus:border-orange-500 pr-10"
            />
            <button
              type="button"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none"
              @click="showCurrentPassword = !showCurrentPassword"
            >
              <Icon :icon="showCurrentPassword ? 'fluent:eye-24-regular' : 'fluent:eye-off-24-regular'" class="w-5 h-5" />
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รหัสผ่านใหม่</label>
            <div class="relative">
              <input
                v-model="passwordForm.password"
                :type="showNewPassword ? 'text' : 'password'"
                class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-orange-500 focus:border-orange-500 pr-10"
              />
              <button
                type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none"
                @click="showNewPassword = !showNewPassword"
              >
                <Icon :icon="showNewPassword ? 'fluent:eye-24-regular' : 'fluent:eye-off-24-regular'" class="w-5 h-5" />
              </button>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ยืนยันรหัสผ่านใหม่</label>
            <div class="relative">
              <input
                v-model="passwordForm.password_confirmation"
                :type="showConfirmPassword ? 'text' : 'password'"
                class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-orange-500 focus:border-orange-500 pr-10"
              />
              <button
                type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none"
                @click="showConfirmPassword = !showConfirmPassword"
              >
                <Icon :icon="showConfirmPassword ? 'fluent:eye-24-regular' : 'fluent:eye-off-24-regular'" class="w-5 h-5" />
              </button>
            </div>
          </div>
        </div>

        <div class="pt-4 flex justify-end">
          <button
            type="submit"
            :disabled="isLoadingPassword"
            class="px-6 py-2.5 bg-orange-600 hover:bg-orange-700 text-white rounded-xl font-medium shadow-lg hover:shadow-orange-500/30 transition-all flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
          >
            <Icon v-if="isLoadingPassword" icon="svg-spinners:ring-resize" />
            <span>เปลี่ยนรหัสผ่าน</span>
          </button>
        </div>
      </div>
    </form>
  </div>
</template>
