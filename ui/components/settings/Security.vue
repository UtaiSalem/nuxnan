<script setup lang="ts">
import { ref } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import { useAuthStore } from '~/stores/auth'

const config = useRuntimeConfig()
const apiBase = config.public.apiBase
const authStore = useAuthStore()
const isLoading = ref(false)

const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)

const form = ref({
    current_password: '',
    password: '',
    password_confirmation: ''
})

async function updatePassword() {
    if (form.value.password !== form.value.password_confirmation) {
        Swal.fire('Error', 'New passwords do not match', 'error')
        return
    }

    isLoading.value = true
    try {
        const res = await $fetch<any>(`${apiBase}/api/settings/password`, {
            method: 'POST',
            body: form.value,
            headers: { Authorization: `Bearer ${authStore.token}` }
        })
        
        if (res.success) {
            Swal.fire('Success', 'Password updated successfully', 'success')
            form.value = { current_password: '', password: '', password_confirmation: '' }
        }
    } catch (error: any) {
        Swal.fire('Error', error.response?._data?.message || 'Update failed', 'error')
    } finally {
        isLoading.value = false
    }
}
</script>

<template>
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
    <div class="p-4 sm:p-6 border-b border-gray-100 dark:border-gray-700 bg-red-50/50 dark:bg-red-900/10">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <Icon icon="fluent:shield-lock-24-filled" class="text-red-500" />
            ความปลอดภัย & รหัสผ่าน
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">ตรวจสอบว่าบัญชีของคุณใช้รหัสผ่านที่รัดกุม</p>
    </div>

    <div class="p-4 sm:p-6 space-y-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รหัสผ่านปัจจุบัน</label>
            <div class="relative">
                <input 
                    v-model="form.current_password" 
                    :type="showCurrentPassword ? 'text' : 'password'" 
                    class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-red-500 pr-10" 
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
                        class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-red-500 pr-10" 
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
                        class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-red-500 pr-10" 
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
                class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl font-medium shadow-lg hover:shadow-red-500/30 transition-all flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
            >
                <Icon v-if="isLoading" icon="svg-spinners:ring-resize" />
                <span>เปลี่ยนรหัสผ่าน</span>
            </button>
        </div>
    </div>
</div>
</template>
