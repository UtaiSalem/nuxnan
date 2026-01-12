<script setup lang="ts">
import { ref } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import { useAuthStore } from '~/stores/auth'

const config = useRuntimeConfig()
const apiBase = config.public.apiBase
const authStore = useAuthStore()
const isLoading = ref(false)

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
    <div class="p-6 border-b border-gray-100 dark:border-gray-700 bg-red-50/50 dark:bg-red-900/10">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <Icon icon="fluent:shield-lock-24-filled" class="text-red-500" />
            Security & Password
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Ensure your account is using a strong password</p>
    </div>

    <div class="p-6 space-y-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Current Password</label>
            <input v-model="form.current_password" type="password" class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-red-500" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">New Password</label>
                <input v-model="form.password" type="password" class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-red-500" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Confirm New Password</label>
                <input v-model="form.password_confirmation" type="password" class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-red-500" />
            </div>
        </div>

        <div class="pt-4 flex justify-end">
            <button 
                @click="updatePassword" 
                :disabled="isLoading"
                class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl font-medium shadow-lg hover:shadow-red-500/30 transition-all flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
            >
                <Icon v-if="isLoading" icon="svg-spinners:ring-resize" />
                <span>Update Password</span>
            </button>
        </div>
    </div>
</div>
</template>
