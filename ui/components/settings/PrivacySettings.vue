<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import { useAuthStore } from '~/stores/auth'

const config = useRuntimeConfig()
const apiBase = config.public.apiBase
const authStore = useAuthStore()

const isLoading = ref(false)

const privacyForm = ref({
    profile_visibility: 'public', // public, friends, private
    show_email: false,
    show_phone: false,
    show_birthdate: false,
    show_location: false,
    allow_friend_requests: true,
    allow_messages: true,
    show_online_status: true,
})

const visibilityOptions = [
    { 
        value: 'public', 
        label: 'สาธารณะ', 
        description: 'ทุกคนสามารถดูโปรไฟล์ของคุณได้',
        icon: 'fluent:earth-24-regular',
        color: 'green'
    },
    { 
        value: 'friends', 
        label: 'เฉพาะเพื่อน', 
        description: 'เฉพาะเพื่อนที่เท่านั้นที่สามารถดูโปรไฟล์ของคุณได้',
        icon: 'fluent:people-24-regular',
        color: 'blue'
    },
    { 
        value: 'private', 
        label: 'ส่วนตัว', 
        description: 'เฉพาะคุณเท่านั้นที่สามารถดูโปรไฟล์ของคุณได้',
        icon: 'fluent:lock-closed-24-regular',
        color: 'red'
    },
]

onMounted(async () => {
    try {
        const res = await $fetch<any>(`${apiBase}/api/settings`, {
            headers: { Authorization: `Bearer ${authStore.token}` }
        })
        if (res.success && res.data.profile) {
            const p = res.data.profile
            privacyForm.value = {
                profile_visibility: p.privacy_settings || 'public',
                show_email: p.show_email ?? false,
                show_phone: p.show_phone ?? false,
                show_birthdate: p.show_birthdate ?? false,
                show_location: p.show_location ?? false,
                allow_friend_requests: p.allow_friend_requests ?? true,
                allow_messages: p.allow_messages ?? true,
                show_online_status: p.show_online_status ?? true,
            }
        }
    } catch (e) {
        console.error("Fetch privacy settings error", e)
    }
})

async function savePrivacySettings() {
    isLoading.value = true
    try {
        const res = await $fetch<any>(`${apiBase}/api/settings/profile`, {
            method: 'POST',
            body: {
                privacy_settings: privacyForm.value.profile_visibility,
                show_email: privacyForm.value.show_email,
                show_phone: privacyForm.value.show_phone,
                show_birthdate: privacyForm.value.show_birthdate,
                show_location: privacyForm.value.show_location,
                allow_friend_requests: privacyForm.value.allow_friend_requests,
                allow_messages: privacyForm.value.allow_messages,
                show_online_status: privacyForm.value.show_online_status,
            },
            headers: { Authorization: `Bearer ${authStore.token}` }
        })
        
        if (res.success) {
            Swal.fire({
                icon: 'success',
                title: 'บันทึกสำเร็จ!',
                text: 'การตั้งค่าความเป็นส่วนตัวถูกอัปเดตแล้ว',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            })
        }
    } catch (error: any) {
        Swal.fire('Error', 'Update failed', 'error')
    } finally {
        isLoading.value = false
    }
}
</script>

<template>
<div class="space-y-6">
    <!-- Profile Visibility -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <Icon icon="fluent:eye-24-regular" class="text-blue-500" />
                การมองเห็นโปรไฟล์
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">เลือกว่าใครสามารถดูโปรไฟล์ของคุณได้บ้าง</p>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div
                    v-for="option in visibilityOptions"
                    :key="option.value"
                    @click="privacyForm.profile_visibility = option.value"
                    class="relative p-4 rounded-xl border-2 cursor-pointer transition-all duration-200"
                    :class="[
                        privacyForm.profile_visibility === option.value
                            ? `border-${option.color}-500 bg-${option.color}-50 dark:bg-${option.color}-900/20`
                            : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                    ]"
                >
                    <div class="flex items-center gap-3 mb-2">
                        <Icon :icon="option.icon" class="w-6 h-6" :class="`text-${option.color}-500`" />
                        <span class="font-semibold text-gray-900 dark:text-white">{{ option.label }}</span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ option.description }}</p>
                    <div v-if="privacyForm.profile_visibility === option.value" class="absolute top-3 right-3">
                        <Icon icon="fluent:checkmark-circle-24-filled" :class="`text-${option.color}-500`" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Personal Information Visibility -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <Icon icon="fluent:person-info-24-regular" class="text-purple-500" />
                การแสดงข้อมูลส่วนตัว
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ควบคุมว่าจะแสดงข้อมูลใดให้ผู้อื่นเห็น</p>
        </div>

        <div class="p-6 space-y-4">
            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                <div class="flex items-center gap-3">
                    <Icon icon="fluent:mail-24-regular" class="text-gray-500 w-5 h-5" />
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">แสดงอีเมล</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">อนุญาตให้ผู้อื่นเห็นอีเมลของคุณ</p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" v-model="privacyForm.show_email" class="sr-only peer" />
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                </label>
            </div>

            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                <div class="flex items-center gap-3">
                    <Icon icon="fluent:call-24-regular" class="text-gray-500 w-5 h-5" />
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">แสดงเบอร์โทรศัพท์</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">อนุญาตให้ผู้อื่นเห็นเบอร์โทรศัพท์ของคุณ</p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" v-model="privacyForm.show_phone" class="sr-only peer" />
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                </label>
            </div>

            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                <div class="flex items-center gap-3">
                    <Icon icon="fluent:calendar-24-regular" class="text-gray-500 w-5 h-5" />
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">แสดงวันเกิด</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">อนุญาตให้ผู้อื่นเห็นวันเกิดของคุณ</p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" v-model="privacyForm.show_birthdate" class="sr-only peer" />
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                </label>
            </div>

            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                <div class="flex items-center gap-3">
                    <Icon icon="fluent:location-24-regular" class="text-gray-500 w-5 h-5" />
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">แสดงที่อยู่</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">อนุญาตให้ผู้อื่นเห็นที่อยู่ของคุณ</p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" v-model="privacyForm.show_location" class="sr-only peer" />
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                </label>
            </div>
        </div>
    </div>

    <!-- Interaction Settings -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <Icon icon="fluent:chat-24-regular" class="text-green-500" />
                การตั้งค่าการโต้ตอบ
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ควบคุมว่าผู้อื่นสามารถโต้ตอบกับคุณได้อย่างไร</p>
        </div>

        <div class="p-6 space-y-4">
            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                <div class="flex items-center gap-3">
                    <Icon icon="fluent:person-add-24-regular" class="text-gray-500 w-5 h-5" />
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">อนุญาตคำขอเป็นเพื่อน</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">อนุญาตให้ผู้อื่นส่งคำขอเป็นเพื่อน</p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" v-model="privacyForm.allow_friend_requests" class="sr-only peer" />
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                </label>
            </div>

            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                <div class="flex items-center gap-3">
                    <Icon icon="fluent:chat-bubble-24-regular" class="text-gray-500 w-5 h-5" />
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">อนุญาตข้อความส่วนตัว</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">อนุญาตให้ผู้อื่นส่งข้อความส่วนตัว</p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" v-model="privacyForm.allow_messages" class="sr-only peer" />
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                </label>
            </div>

            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                <div class="flex items-center gap-3">
                    <Icon icon="fluent:presence-available-24-regular" class="text-gray-500 w-5 h-5" />
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">แสดงสถานะออนไลน์</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">แสดงสถานะออนไลน์ของคุณให้ผู้อื่นเห็น</p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" v-model="privacyForm.show_online_status" class="sr-only peer" />
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                </label>
            </div>
        </div>
    </div>

    <!-- Save Button -->
    <div class="flex justify-end">
        <button 
            @click="savePrivacySettings" 
            :disabled="isLoading"
            class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium shadow-lg hover:shadow-blue-500/30 transition-all flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
        >
            <Icon v-if="isLoading" icon="svg-spinners:ring-resize" />
            <span>บันทึกการตั้งค่าความเป็นส่วนตัว</span>
        </button>
    </div>
</div>
</template>
