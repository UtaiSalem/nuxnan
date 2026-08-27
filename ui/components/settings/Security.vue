<script setup lang="ts">
import { ref, watch, inject } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

const api = useApi()

const markDirty = inject<() => void>('markDirty', () => {})
const markClean = inject<() => void>('markClean', () => {})

const isLoading = ref(false)

const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)

const form = ref({
    current_password: '',
    password: '',
    password_confirmation: ''
})

let watcherActive = true
watch(form, () => {
  if (watcherActive && (form.value.current_password || form.value.password || form.value.password_confirmation)) {
    markDirty()
  }
}, { deep: true })

async function updatePassword() {
    if (!form.value.current_password || !form.value.password) {
        Swal.fire('Error', 'กรุณากรอกข้อมูลให้ครบถ้วน', 'error')
        return
    }

    if (form.value.password !== form.value.password_confirmation) {
        Swal.fire('Error', 'รหัสผ่านใหม่ไม่ตรงกัน', 'error')
        return
    }

    isLoading.value = true
    try {
        const res = await api.post<any>('/api/settings/password', form.value)
        
        if (res.success) {
            markClean()
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ!',
                text: 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            })
            form.value = { current_password: '', password: '', password_confirmation: '' }
        }
    } catch (error: any) {
        Swal.fire('Error', error.data?.message || 'Update failed', 'error')
    } finally {
        isLoading.value = false
    }
}
</script>

<template>
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
    <div class="p-4 sm:p-6 border-b border-gray-100 dark:border-gray-700 bg-orange-50/50 dark:bg-orange-900/10">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <Icon icon="fluent:shield-lock-24-filled" class="text-orange-500" />
            ความปลอดภัย & รหัสผ่าน
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">ตรวจสอบว่าบัญชีของคุณใช้รหัสผ่านที่รัดกุม</p>
    </div>

    <div class="p-4 sm:p-6 space-y-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รหัสผ่านปัจจุบัน</label>
            <div class="relative">
                <input 
                    v-model="form.current_password" 
                    :type="showCurrentPassword ? 'text' : 'password'" 
                    class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-orange-500 pr-10" 
                    placeholder="••••••••"
                />
                <button 
                    @click="showCurrentPassword = !showCurrentPassword" 
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none"
                    type="button"
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
                        v-model="form.password" 
                        :type="showNewPassword ? 'text' : 'password'" 
                        class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-orange-500 pr-10" 
                        placeholder="••••••••"
                    />
                    <button 
                        @click="showNewPassword = !showNewPassword" 
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none"
                        type="button"
                    >
                        <Icon :icon="showNewPassword ? 'fluent:eye-24-regular' : 'fluent:eye-off-24-regular'" class="w-5 h-5" />
                    </button>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ยืนยันรหัสผ่านใหม่</label>
                <div class="relative">
                    <input 
                        v-model="form.password_confirmation" 
                        :type="showConfirmPassword ? 'text' : 'password'" 
                        class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-orange-500 pr-10" 
                        placeholder="••••••••"
                    />
                    <button 
                        @click="showConfirmPassword = !showConfirmPassword" 
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none"
                        type="button"
                    >
                        <Icon :icon="showConfirmPassword ? 'fluent:eye-24-regular' : 'fluent:eye-off-24-regular'" class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </div>

        <div class="pt-4 flex justify-end">
            <button 
                @click="updatePassword" 
                :disabled="isLoading"
                class="min-h-[44px] sm:min-h-0 px-6 py-2.5 bg-orange-600 hover:bg-orange-700 text-white rounded-xl font-medium shadow-lg hover:shadow-orange-500/30 transition-all flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
            >
                <Icon v-if="isLoading" icon="svg-spinners:ring-resize" />
                <Icon v-else icon="fluent:save-24-regular" class="w-4 h-4" />
                <span>เปลี่ยนรหัสผ่าน</span>
            </button>
        </div>
    </div>
</div>
</template>
