<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import { useAuthStore } from '~/stores/auth'

const config = useRuntimeConfig()
const apiBase = config.public.apiBase
const authStore = useAuthStore()

const isLoading = ref(false)
const form = ref({
  name: '',
  phone_number: '',
  email: '', // Read-only typically
})

onMounted(() => {
    if (authStore.user) {
        form.value.name = authStore.user.name || ''
        form.value.phone_number = authStore.user.phone_number || ''
        form.value.email = authStore.user.email || ''
    }
})

async function saveAccount() {
    isLoading.value = true
    try {
        const res = await $fetch<any>(`${apiBase}/api/settings/account`, {
            method: 'POST',
            body: { 
                name: form.value.name,
                phone_number: form.value.phone_number
            },
            headers: { Authorization: `Bearer ${authStore.token}` }
        })
        
        if (res.success) {
            authStore.fetchUser() // Refresh user data
            Swal.fire({
                icon: 'success',
                title: 'Saved!',
                text: 'Account information updated.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            })
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
    <div class="p-6 border-b border-gray-100 dark:border-gray-700">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Account Information</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Manage your basic account details</p>
    </div>

    <div class="p-6 space-y-6">
        <!-- Name -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Display Name</label>
            <div class="relative">
                <Icon icon="fluent:person-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
                <input v-model="form.name" type="text" class="pl-10 w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="Your name" />
            </div>
        </div>

        <!-- Email (Read Only) -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email Address</label>
            <div class="relative opacity-75">
                <Icon icon="fluent:mail-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
                <input v-model="form.email" type="email" readonly class="pl-10 w-full rounded-xl border-gray-300 bg-gray-50 dark:bg-gray-700/50 dark:border-gray-600 dark:text-gray-400 cursor-not-allowed" />
            </div>
            <p class="mt-1 text-xs text-amber-500">Contact support to change email address.</p>
        </div>

        <!-- Phone -->
         <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Phone Number</label>
            <div class="relative">
                <Icon icon="fluent:call-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
                <input v-model="form.phone_number" type="tel" class="pl-10 w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="08xxxxxxxx" />
            </div>
        </div>

        <!-- Save Button -->
        <div class="pt-4 flex justify-end">
            <button 
                @click="saveAccount" 
                :disabled="isLoading"
                class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium shadow-lg hover:shadow-blue-500/30 transition-all flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
            >
                <Icon v-if="isLoading" icon="svg-spinners:ring-resize" />
                Save Changes
            </button>
        </div>
    </div>
</div>
</template>
