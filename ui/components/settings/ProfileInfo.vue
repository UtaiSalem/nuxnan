<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import { useAuthStore } from '~/stores/auth'

const config = useRuntimeConfig()
const apiBase = config.public.apiBase
const authStore = useAuthStore()

const isLoading = ref(false)
const isUploadingAvatar = ref(false)
const isUploadingCover = ref(false)
const activeSection = ref('basic') // basic, personal, professional

// Basic Profile Info
const basicForm = ref({
  first_name: '',
  last_name: '',
  bio: '',
  location: '',
  website: '',
  birthdate: '',
  gender: 'male',
})

// Personal Information
const personalForm = ref({
  phone_number: '',
  address: '',
  city: '',
  country: '',
  postal_code: '',
})

// Professional Information
const professionalForm = ref({
  job_title: '',
  company: '',
  industry: '',
  skills: '',
  experience_years: '',
})

const avatarPreview = ref('/images/default-avatar.png')
const coverPreview = ref('')
const inputAvatar = ref<HTMLInputElement|null>(null)
const inputCover = ref<HTMLInputElement|null>(null)

const sections = [
  { id: 'basic', label: 'ข้อมูลพื้นฐาน', icon: 'fluent:person-24-regular', description: 'ข้อมูลโปรไฟล์หลัก' },
  { id: 'personal', label: 'ข้อมูลส่วนตัว', icon: 'fluent:contact-card-24-regular', description: 'ข้อมูลติดต่อและที่อยู่' },
  { id: 'professional', label: 'ข้อมูลอาชีพ', icon: 'fluent:briefcase-24-regular', description: 'ข้อมูลการทำงานและทักษะ' },
]

const activeSectionData = computed(() => sections.find(s => s.id === activeSection.value))

// Display name computed
const displayName = computed(() => {
  const firstName = basicForm.value.first_name
  const lastName = basicForm.value.last_name
  if (firstName || lastName) {
    return `${firstName} ${lastName}`.trim()
  }
  return authStore.user?.name || authStore.user?.username || 'ผู้ใช้งาน'
})

onMounted(async () => {
    try {
        const res = await $fetch<any>(`${apiBase}/api/settings`, {
            headers: { Authorization: `Bearer ${authStore.token}` }
        })
        if (res.success) {
            const p = res.data.profile || {}
            const u = res.data || {}
            
            // Basic Profile
            basicForm.value = {
                first_name: p.first_name || '',
                last_name: p.last_name || '',
                bio: p.bio || '',
                location: p.location || '',
                website: p.website || '',
                birthdate: p.birthdate ? p.birthdate.split('T')[0] : '',
                gender: p.gender || 'male',
            }
            
            // Personal Information
            personalForm.value = {
                phone_number: u.phone_number || p.phone_number || '',
                address: p.address || '',
                city: p.city || '',
                country: p.country || '',
                postal_code: p.postal_code || '',
            }
            
            // Professional Information
            professionalForm.value = {
                job_title: p.job_title || '',
                company: p.company || '',
                industry: p.industry || '',
                skills: Array.isArray(p.skills) ? p.skills.join(', ') : (p.skills || ''),
                experience_years: p.experience_years || '',
            }
            
            // Handle Avatar URL - check multiple possible fields
            const avatarUrl = res.data.profile_photo_url || res.data.profile_photo_path || p.profile_picture || p.avatar
            if (avatarUrl) {
                if (avatarUrl.startsWith('/storage')) {
                    avatarPreview.value = `${apiBase}${avatarUrl}`
                } else if (avatarUrl.startsWith('http')) {
                    avatarPreview.value = avatarUrl
                } else {
                    avatarPreview.value = `${apiBase}/storage/${avatarUrl}`
                }
            } else {
                avatarPreview.value = '/images/default-avatar.png'
            }

            // Handle Cover URL
            const coverUrl = p.cover_image || p.cover_image_url
            if (coverUrl && coverUrl.startsWith('/storage')) {
                coverPreview.value = `${apiBase}${coverUrl}`
            } else if (coverUrl) {
                coverPreview.value = coverUrl
            } else {
                coverPreview.value = '/storage/images/banner/banner-bg.png'
                if (coverPreview.value.startsWith('/storage')) {
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
            avatarPreview.value = res.url.startsWith('/storage') ? `${apiBase}${res.url}` : res.url
            authStore.fetchUser()
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'อัปโหลดรูปโปรไฟล์สำเร็จ', timer: 2000, showConfirmButton: false })
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
            coverPreview.value = res.url.startsWith('/storage') ? `${apiBase}${res.url}` : res.url
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'อัปโหลดภาพปกสำเร็จ', timer: 2000, showConfirmButton: false })
        }
    } catch (e) {
        Swal.fire('Error', 'Cover upload failed', 'error')
    } finally {
        isUploadingCover.value = false
    }
}

async function saveBasicProfile() {
    isLoading.value = true
    try {
        const res = await $fetch<any>(`${apiBase}/api/settings/profile`, {
            method: 'POST',
            body: basicForm.value,
            headers: { Authorization: `Bearer ${authStore.token}` }
        })
        
        if (res.success) {
            Swal.fire({
                icon: 'success',
                title: 'บันทึกสำเร็จ!',
                text: 'ข้อมูลโปรไฟล์พื้นฐานถูกอัปเดตแล้ว',
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

async function savePersonalInfo() {
    isLoading.value = true
    try {
        const res = await $fetch<any>(`${apiBase}/api/settings/profile`, {
            method: 'POST',
            body: {
                ...personalForm.value,
                phone_number: personalForm.value.phone_number
            },
            headers: { Authorization: `Bearer ${authStore.token}` }
        })
        
        if (res.success) {
            Swal.fire({
                icon: 'success',
                title: 'บันทึกสำเร็จ!',
                text: 'ข้อมูลส่วนตัวถูกอัปเดตแล้ว',
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

async function saveProfessionalInfo() {
    isLoading.value = true
    try {
        const skillsArray = professionalForm.value.skills
            .split(',')
            .map(s => s.trim())
            .filter(s => s.length > 0)
        
        const res = await $fetch<any>(`${apiBase}/api/settings/profile`, {
            method: 'POST',
            body: {
                ...professionalForm.value,
                skills: skillsArray
            },
            headers: { Authorization: `Bearer ${authStore.token}` }
        })
        
        if (res.success) {
            Swal.fire({
                icon: 'success',
                title: 'บันทึกสำเร็จ!',
                text: 'ข้อมูลอาชีพถูกอัปเดตแล้ว',
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

function handleSave() {
    switch(activeSection.value) {
        case 'basic':
            saveBasicProfile()
            break
        case 'personal':
            savePersonalInfo()
            break
        case 'professional':
            saveProfessionalInfo()
            break
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
                    เปลี่ยนภาพปก
                </button>
            </div>
            <input type="file" ref="inputCover" @change="onCoverChange" class="hidden" accept="image/*" />
        </div>

        <div class="px-6 pb-6 relative">
            <!-- Avatar -->
            <div class="absolute -top-12 left-6 w-24 h-24 rounded-full border-4 border-white dark:border-gray-800 overflow-hidden group shadow-md bg-gray-100 dark:bg-gray-700">
                <img 
                    :src="avatarPreview || '/images/default-avatar.png'" 
                    class="w-full h-full object-cover" 
                    @error="($event.target as HTMLImageElement).src = '/images/default-avatar.png'"
                />
                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-black/40 cursor-pointer" @click="triggerAvatar">
                    <Icon v-if="isUploadingAvatar" icon="svg-spinners:ring-resize" class="text-white w-6 h-6" />
                    <Icon v-else icon="fluent:camera-24-regular" class="text-white w-6 h-6" />
                </div>
                <input type="file" ref="inputAvatar" @change="onAvatarChange" class="hidden" accept="image/*" />
            </div>

            <div class="ml-28 pt-2">
                <!-- User Name -->
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                    {{ displayName }}
                </h3>
                <!-- Email -->
                <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1 mt-0.5">
                    <Icon icon="fluent:mail-24-regular" class="w-4 h-4" />
                    {{ authStore.user?.email || '-' }}
                </p>
                <!-- Reference Code -->
                <p class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1 mt-1">
                    <Icon icon="fluent:key-24-regular" class="w-3 h-3" />
                    {{ authStore.user?.reference_code || '-' }}
                </p>
            </div>
        </div>
    </div>

    <!-- Section Tabs -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="border-b border-gray-100 dark:border-gray-700">
            <nav class="flex overflow-x-auto">
                <button
                    v-for="section in sections"
                    :key="section.id"
                    @click="activeSection = section.id"
                    class="flex-1 min-w-max px-6 py-4 text-sm font-medium transition-colors border-b-2"
                    :class="activeSection === section.id
                        ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                        : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                >
                    <div class="flex items-center gap-2">
                        <Icon :icon="section.icon" class="w-5 h-5" />
                        <span>{{ section.label }}</span>
                    </div>
                </button>
            </nav>
        </div>

        <!-- Section Content -->
        <div class="p-6">
            <div v-if="activeSection === 'basic'" class="space-y-6">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">ข้อมูลพื้นฐาน</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ข้อมูลที่แสดงให้ผู้อื่นเห็น</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ชื่อจริง</label>
                        <input v-model="basicForm.first_name" type="text" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="ชื่อจริง" />
                    </div>
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">นามสกุล</label>
                        <input v-model="basicForm.last_name" type="text" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="นามสกุล" />
                    </div>

                    <div class="md:col-span-2 space-y-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">เกี่ยวกับฉัน (Bio)</label>
                        <textarea v-model="basicForm.bio" rows="4" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all resize-none" placeholder="บอกเล่าเกี่ยวกับตัวคุณ..."></textarea>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ที่อยู่</label>
                        <div class="relative">
                            <Icon icon="fluent:location-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
                            <input v-model="basicForm.location" type="text" class="pl-10 w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="กรุงเทพมหานคร, ประเทศไทย" />
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">เว็บไซต์</label>
                        <div class="relative">
                            <Icon icon="fluent:globe-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
                            <input v-model="basicForm.website" type="url" class="pl-10 w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="https://yourwebsite.com" />
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">วันเกิด</label>
                        <input v-model="basicForm.birthdate" type="date" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" />
                    </div>

                    <div class="space-y-1">
                         <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">เพศ</label>
                         <select v-model="basicForm.gender" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                             <option value="male">ชาย</option>
                             <option value="female">หญิง</option>
                             <option value="other">อื่นๆ</option>
                         </select>
                    </div>
                </div>
            </div>

            <div v-if="activeSection === 'personal'" class="space-y-6">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">ข้อมูลส่วนตัว</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ข้อมูลติดต่อและที่อยู่ของคุณ</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เบอร์โทรศัพท์</label>
                        <div class="relative">
                            <Icon icon="fluent:call-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
                            <input v-model="personalForm.phone_number" type="tel" class="pl-10 w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="08xxxxxxxx" />
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ที่อยู่</label>
                        <div class="relative">
                            <Icon icon="fluent:home-24-regular" class="absolute left-3 top-3 text-gray-400 w-5 h-5" />
                            <textarea v-model="personalForm.address" rows="2" class="pl-10 w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="บ้านเลขที่, ถนน, แขวง/ตำบล"></textarea>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เมือง/จังหวัด</label>
                        <input v-model="personalForm.city" type="text" class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="เมือง/จังหวัด" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเทศ</label>
                        <input v-model="personalForm.country" type="text" class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="ประเทศ" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รหัสไปรษณีย์</label>
                        <input v-model="personalForm.postal_code" type="text" class="w-full rounded-xl border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="รหัสไปรษณีย์" />
                    </div>
                </div>
            </div>

            <div v-if="activeSection === 'professional'" class="space-y-6">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">ข้อมูลอาชีพ</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ข้อมูลการทำงานและทักษะของคุณ</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ตำแหน่งงาน</label>
                        <div class="relative">
                            <Icon icon="fluent:briefcase-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
                            <input v-model="professionalForm.job_title" type="text" class="pl-10 w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="ตำแหน่งงาน" />
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">บริษัท/องค์กร</label>
                        <div class="relative">
                            <Icon icon="fluent:building-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
                            <input v-model="professionalForm.company" type="text" class="pl-10 w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="ชื่อบริษัท/องค์กร" />
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">อุตสาหกรรม</label>
                        <input v-model="professionalForm.industry" type="text" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="อุตสาหกรรม" />
                    </div>

                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ประสบการณ์ (ปี)</label>
                        <input v-model="professionalForm.experience_years" type="text" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="จำนวนปี" />
                    </div>

                    <div class="md:col-span-2 space-y-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ทักษะ (คั่นด้วยจุลภาค)</label>
                        <div class="relative">
                            <Icon icon="fluent:lightbulb-24-regular" class="absolute left-3 top-3 text-gray-400 w-5 h-5" />
                            <textarea v-model="professionalForm.skills" rows="3" class="pl-10 w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all resize-none" placeholder="ทักษะ1, ทักษะ2, ทักษะ3"></textarea>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">คั่นทักษะแต่ละอย่างด้วยจุลภาค (,)</p>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="pt-6 border-t border-gray-100 dark:border-gray-700 flex justify-end">
                <button 
                    @click="handleSave" 
                    :disabled="isLoading"
                    class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium shadow-lg hover:shadow-blue-500/30 transition-all flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
                >
                    <Icon v-if="isLoading" icon="svg-spinners:ring-resize" />
                    <span v-if="activeSection === 'basic'">บันทึกข้อมูลพื้นฐาน</span>
                    <span v-else-if="activeSection === 'personal'">บันทึกข้อมูลส่วนตัว</span>
                    <span v-else-if="activeSection === 'professional'">บันทึกข้อมูลอาชีพ</span>
                </button>
            </div>
        </div>
    </div>
</div>
</template>
