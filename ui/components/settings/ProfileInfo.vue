<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import { useAuthStore } from '~/stores/auth'

const config = useRuntimeConfig()
const apiBase = config.public.apiBase
const authStore = useAuthStore()

const isLoading = ref(false)
const isUploadingAvatar = ref(false)
const isUploadingCover = ref(false)

const form = ref({
  first_name: '',
  last_name: '',
  bio: '',
  location: '',
  website: '',
  birthdate: '',
  gender: 'male',
})

const avatarPreview = ref('')
const coverPreview = ref('')
const inputAvatar = ref<HTMLInputElement|null>(null)
const inputCover = ref<HTMLInputElement|null>(null)

onMounted(async () => {
    // Ideally fetch full profile from API incase authStore is partial
    try {
        const res = await $fetch<any>(`${apiBase}/api/settings`, {
            headers: { Authorization: `Bearer ${authStore.token}` }
        })
        if (res.success) {
            const p = res.data.profile || {}
            form.value = {
                first_name: p.first_name || '',
                last_name: p.last_name || '',
                bio: p.bio || '',
                location: p.location || '',
                website: p.website || '',
                birthdate: p.birthdate ? p.birthdate.split('T')[0] : '', // YYYY-MM-DD
                gender: p.gender || 'male',
            }
            
            // Handle Avatar URL
            const avatarUrl = res.data.profile_photo_url || res.data.profile_photo_path
            if (avatarUrl && avatarUrl.startsWith('/storage')) {
                avatarPreview.value = `${apiBase}${avatarUrl}`
            } else if (avatarUrl) {
                avatarPreview.value = avatarUrl
            } else {
                avatarPreview.value = '/images/default-avatar.png'
            }

            // Handle Cover URL
            // Check cover_image first, then cover_image_url as fallback
            const coverUrl = p.cover_image || p.cover_image_url
            if (coverUrl && coverUrl.startsWith('/storage')) {
                coverPreview.value = `${apiBase}${coverUrl}`
            } else if (coverUrl) {
                coverPreview.value = coverUrl
            } else {
                coverPreview.value = '/storage/images/banner/banner-bg.png' 
                // Note: banner-bg.png seems to be in basic storage, might need apiBase too if it's not in public/
                // If it is in public/, usage dependent on deployment. 
                // But previously it was used as literal string. Assuming it's a fallback locally OR global asset.
                // Reverting fallback to original relative path, assuming it works or is handled by Nginx/etc? 
                // Wait, previous code: 'url(/storage/images/banner/banner-bg.png)' in UserProfile.vue
                // Here: '/storage/images/banner/banner-bg.png'
                // If this is a static asset on frontend, it's fine. If on backend, needs apiBase.
                // I will add apiBase check to be safe IF it starts with /storage.
                if (coverPreview.value.startsWith('/storage')) {
                     // Check if it's a known static asset? 
                     // Let's just prepend apiBase to be consistent with other storage assets.
                     coverPreview.value = `${apiBase}${coverPreview.value}`
                }
            }
        }
    } catch (e) {
        console.error("Fetch profile error", e)
    }
})

// Avatar Upload
function triggerAvatar() { inputAvatar.value?.click() }
async function onAvatarChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0]
    if (!file) return
    
    isUploadingAvatar.value = true
    const fd = new FormData()
    fd.append('avatar', file)
    
    try {
        const res = await $fetch<any>(`${apiBase}/api/settings/avatar`, {
            method: 'POST',
            body: fd,
            headers: { Authorization: `Bearer ${authStore.token}` }
        })
        if (res.success) {
            // Prepend apiBase if needed
            avatarPreview.value = res.url.startsWith('/storage') ? `${apiBase}${res.url}` : res.url
            authStore.fetchUser()
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Avatar updated', timer: 2000, showConfirmButton: false })
        }
    } catch (e) {
        Swal.fire('Error', 'Avatar upload failed', 'error')
    } finally {
        isUploadingAvatar.value = false
    }
}

// Cover Upload
function triggerCover() { inputCover.value?.click() }
async function onCoverChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0]
    if (!file) return

    isUploadingCover.value = true
    const fd = new FormData()
    fd.append('cover', file)

    try {
        const res = await $fetch<any>(`${apiBase}/api/settings/cover`, {
            method: 'POST',
            body: fd,
            headers: { Authorization: `Bearer ${authStore.token}` }
        })
        if (res.success) {
            // Prepend apiBase if needed
            coverPreview.value = res.url.startsWith('/storage') ? `${apiBase}${res.url}` : res.url
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Cover updated', timer: 2000, showConfirmButton: false })
        }
    } catch (e) {
        Swal.fire('Error', 'Cover upload failed', 'error')
    } finally {
        isUploadingCover.value = false
    }
}

async function saveProfile() {
    isLoading.value = true
    try {
        const res = await $fetch<any>(`${apiBase}/api/settings/profile`, {
            method: 'POST',
            body: form.value,
            headers: { Authorization: `Bearer ${authStore.token}` }
        })
        
        if (res.success) {
            Swal.fire({
                icon: 'success',
                title: 'Saved!',
                text: 'Profile details updated.',
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
    <!-- Visuals Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        
        <!-- Cover Area -->
        <div class="relative h-48 bg-gray-200 group">
            <img :src="coverPreview" class="w-full h-full object-cover transition-opacity group-hover:opacity-90" />
            
            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-black/30">
                <button @click="triggerCover" class="bg-white/90 text-gray-800 px-4 py-2 rounded-full font-medium shadow-lg hover:bg-white flex items-center gap-2">
                    <Icon v-if="isUploadingCover" icon="svg-spinners:ring-resize" />
                    <Icon v-else icon="fluent:camera-24-regular" />
                    Change Cover
                </button>
            </div>
            <input type="file" ref="inputCover" @change="onCoverChange" class="hidden" accept="image/*" />
        </div>

        <div class="px-6 pb-6 relative">
            <!-- Avatar -->
            <div class="absolute -top-12 left-6 w-24 h-24 rounded-full border-4 border-white dark:border-gray-800 overflow-hidden group shadow-md bg-white">
                <img :src="avatarPreview" class="w-full h-full object-cover" />
                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-black/40 cursor-pointer" @click="triggerAvatar">
                    <Icon v-if="isUploadingAvatar" icon="svg-spinners:ring-resize" class="text-white w-6 h-6" />
                    <Icon v-else icon="fluent:camera-24-regular" class="text-white w-6 h-6" />
                </div>
                <input type="file" ref="inputAvatar" @change="onAvatarChange" class="hidden" accept="image/*" />
            </div>

            <div class="ml-24 pl-4 pt-2">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Profile Visuals</h3>
                <p class="text-xs text-gray-500">Update your avatar and profile cover</p>
            </div>
        </div>
    </div>

    <!-- Info Form -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Public Profile</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Information visible to other users</p>
        </div>

        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">First Name</label>
                <input v-model="form.first_name" type="text" class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-blue-500" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Last Name</label>
                <input v-model="form.last_name" type="text" class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-blue-500" />
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">About Me (Bio)</label>
                <textarea v-model="form.bio" rows="4" class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-blue-500" placeholder="Tell something about yourself..."></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Location</label>
                <div class="relative">
                    <Icon icon="fluent:location-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
                    <input v-model="form.location" type="text" class="pl-10 w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Bangkok, Thailand" />
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Website</label>
                <div class="relative">
                    <Icon icon="fluent:globe-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
                    <input v-model="form.website" type="url" class="pl-10 w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="https://yourwebsite.com" />
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Birthdate</label>
                <input v-model="form.birthdate" type="date" class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
            </div>

            <div>
                 <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Gender</label>
                 <select v-model="form.gender" class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                     <option value="male">Male</option>
                     <option value="female">Female</option>
                     <option value="other">Other</option>
                 </select>
            </div>
            
            <div class="md:col-span-2 pt-4 flex justify-end">
                <button 
                    @click="saveProfile" 
                    :disabled="isLoading"
                    class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium shadow-lg hover:shadow-blue-500/30 transition-all flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
                >
                    <Icon v-if="isLoading" icon="svg-spinners:ring-resize" />
                    Save Profile Changes
                </button>
            </div>
        </div>
    </div>
</div>
</template>
