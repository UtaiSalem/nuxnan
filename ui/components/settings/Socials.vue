<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import { useAuthStore } from '~/stores/auth'

const config = useRuntimeConfig()
const apiBase = config.public.apiBase
const authStore = useAuthStore()
const isLoading = ref(false)

// Init social links structure
const socials = ref({
    facebook: '',
    twitter: '',
    instagram: '',
    linkedin: '',
    youtube: '',
    github: '',
    tiktok: ''
})

const socialIcons = {
    facebook: 'logos:facebook',
    twitter: 'logos:twitter',
    instagram: 'logos:instagram-icon',
    linkedin: 'logos:linkedin-icon',
    youtube: 'logos:youtube-icon',
    github: 'logos:github-icon',
    tiktok: 'logos:tiktok-icon'
}

onMounted(async () => {
    try {
        const res = await $fetch<any>(`${apiBase}/api/settings`, {
            headers: { Authorization: `Bearer ${authStore.token}` }
        })
        if (res.success && res.data.profile) {
            const links = res.data.profile.social_media_links
            // Parse if it comes as string (should be object)
            const parsed = typeof links === 'string' ? JSON.parse(links) : (links || {})
            socials.value = { ...socials.value, ...parsed }
        }
    } catch (e) {
         // ignore
    }
})

async function saveSocials() {
    isLoading.value = true
    try {
        // We re-use updateProfile endpoint which accepts social_media_links
        const res = await $fetch<any>(`${apiBase}/api/settings/profile`, {
            method: 'POST',
            body: { 
                social_media_links: socials.value 
            },
            headers: { Authorization: `Bearer ${authStore.token}` }
        })
        
        if (res.success) {
            Swal.fire({
                icon: 'success',
                title: 'Saved!',
                text: 'Social links updated.',
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
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
    <div class="p-6 border-b border-gray-100 dark:border-gray-700">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Social Networks</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Connect your social profiles</p>
    </div>

    <div class="p-6 space-y-6">
        <div v-for="(url, platform) in socials" :key="platform" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
            <div class="md:col-span-3 flex items-center gap-2">
                <Icon :icon="socialIcons[platform]" class="w-6 h-6" />
                <span class="capitalize font-medium text-gray-700 dark:text-gray-300">{{ platform }}</span>
            </div>
            <div class="md:col-span-9">
                <input 
                    v-model="socials[platform]" 
                    type="url" 
                    placeholder="Profile URL..." 
                    class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-blue-500 transition-all text-sm"
                />
            </div>
        </div>

        <div class="pt-4 flex justify-end">
            <button 
                @click="saveSocials" 
                :disabled="isLoading"
                class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium shadow-lg hover:shadow-blue-500/30 transition-all flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
            >
                <Icon v-if="isLoading" icon="svg-spinners:ring-resize" />
                Save Social Links
            </button>
        </div>
    </div>
</div>
</template>
