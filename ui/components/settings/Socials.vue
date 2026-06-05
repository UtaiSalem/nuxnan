<script setup lang="ts">
import { ref, onMounted, inject, watch } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

const api = useApi()

const settingsData = inject<any>('settingsData')
const reloadSettings = inject<() => Promise<void>>('reloadSettings', async () => {})
const markDirty = inject<() => void>('markDirty', () => {})
const markClean = inject<() => void>('markClean', () => {})

const isLoading = ref(false)

// Init social links structure
const socials = ref<any>({
    facebook: '',
    twitter: '',
    instagram: '',
    linkedin: '',
    youtube: '',
    github: '',
    tiktok: ''
})

const socialIcons: Record<string, string> = {
    facebook: 'logos:facebook',
    twitter: 'logos:twitter',
    instagram: 'logos:instagram-icon',
    linkedin: 'logos:linkedin-icon',
    youtube: 'logos:youtube-icon',
    github: 'logos:github-icon',
    tiktok: 'logos:tiktok-icon'
}

let watcherActive = false
watch(socials, () => {
  if (watcherActive) markDirty()
}, { deep: true })

function hydrateForm(data: any) {
  if (!data || !data.profile) return
  const links = data.profile.social_media_links
  // Parse if it comes as string (should be object)
  const parsed = typeof links === 'string' ? JSON.parse(links) : (links || {})
  socials.value = { 
    facebook: parsed.facebook || '',
    twitter: parsed.twitter || '',
    instagram: parsed.instagram || '',
    linkedin: parsed.linkedin || '',
    youtube: parsed.youtube || '',
    github: parsed.github || '',
    tiktok: parsed.tiktok || ''
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

async function saveSocials() {
    isLoading.value = true
    try {
        const res = await api.post<any>('/api/settings/profile', { 
            social_media_links: socials.value 
        })
        
        if (res.success) {
            markClean()
            await reloadSettings()
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ!',
                text: 'บันทึกข้อมูลโซเชียลเรียบร้อยแล้ว',
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
    <div class="p-4 sm:p-6 border-b border-gray-100 dark:border-gray-700 bg-blue-50/30 dark:bg-blue-900/10">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <Icon icon="fluent:share-24-regular" class="text-blue-500" />
            โซเชียลมีเดีย
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">เชื่อมต่อบัญชีโซเชียลมีเดียของคุณ</p>
    </div>

    <div class="p-4 sm:p-6 space-y-6">
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
                <Icon v-else icon="fluent:save-24-regular" class="w-4 h-4" />
                บันทึกข้อมูลโซเชียล
            </button>
        </div>
    </div>
</div>
</template>
