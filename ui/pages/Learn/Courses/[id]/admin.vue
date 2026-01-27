<script setup lang="ts">
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import { useAuthStore } from '~/stores/auth'

const route = useRoute()
const api = useApi()
const authStore = useAuthStore()
const courseId = route.params.id

const isCourseAdmin = inject<Ref<boolean>>('isCourseAdmin')

const admins = ref<any[]>([])
const isLoading = ref(true)
const showInviteModal = ref(false)
const searchQuery = ref('')
const searchResults = ref<any[]>([])
const isSearching = ref(false)
const selectedRole = ref(3) // Default to TA (Teacher/3)

// Fetch Admins
const fetchAdmins = async () => {
    isLoading.value = true
    try {
        const res: any = await api.get(`/api/courses/${courseId}/admins`)
        if (res.success) {
            const realAdmins = res.admins.map((a: any) => ({ ...a, status: 1 }))
            const pendingInvites = (res.invitations || []).map((i: any) => ({
                id: `invite-${i.id}`, // Avoid key collision
                user: i.invitee,
                role: i.role,
                status: 2, // 2 = Pending
                is_invitation: true,
                invitation_id: i.id // Keep original ID for API calls
            }))
            admins.value = [...realAdmins, ...pendingInvites]
        }
    } catch (e) {
        console.error('Failed to fetch admins', e)
    } finally {
        isLoading.value = false
    }
}

onMounted(() => {
    fetchAdmins()
})

// Search
const handleSearch = async () => {
    if (searchQuery.value.length < 2) return
    isSearching.value = true
    try {
        const res: any = await api.post(`/api/courses/${courseId}/admins/search`, { query: searchQuery.value })
        if (res.success) {
            searchResults.value = res.users
        }
    } catch (e) {
        console.error('Search failed', e)
    } finally {
        isSearching.value = false
    }
}

// Invite
const inviteUser = async (user: any) => {
    try {
        isLoading.value = true
        const res: any = await api.post(`/api/courses/${courseId}/admins/invite`, {
            user_id: user.id,
            role: selectedRole.value
        })
        if (res.success) {
            Swal.fire('สำเร็จ', 'ส่งคำเชิญเรียบร้อยแล้ว', 'success')
            showInviteModal.value = false
            searchQuery.value = ''
            searchResults.value = []
            fetchAdmins()
        }
    } catch (e: any) {
        Swal.fire('ข้อผิดพลาด', e.response?.data?.message || 'ไม่สามารถเชิญได้', 'error')
    } finally {
        isLoading.value = false
    }
}

// Remove
const removeAdmin = async (member: any) => {
    const isInvite = member.is_invitation
    const title = isInvite ? 'ยกเลิกคำเชิญ?' : 'ยืนยันการลบ?'
    const text = isInvite 
        ? `คุณต้องการยกเลิกคำเชิญ "${member.user?.name}"?` 
        : `คุณต้องการลบ "${member.user?.name}" ออกจากผู้ดูแล?`
        
    const result = await Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: isInvite ? 'ยกเลิกคำเชิญ' : 'ลบเลย',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#d33'
    })

    if (result.isConfirmed) {
        try {
            if (isInvite) {
                await api.delete(`/api/courses/${courseId}/admins/invitations/${member.invitation_id}`)
            } else {
                await api.delete(`/api/courses/${courseId}/admins/${member.id}`)
            }
            Swal.fire(isInvite ? 'ยกเลิกสำเร็จ' : 'ลบสำเร็จ', '', 'success')
            fetchAdmins()
        } catch (e) {
            Swal.fire('ผิดพลาด', 'ไม่สามารถดำเนินการได้', 'error')
        }
    }
}

const course = inject<Ref<any>>('course')

const getRoleName = (admin: any) => {
    if (course?.value?.user_id === admin.user_id) {
        return 'แอดมิน (Admin)'
    }
    return admin.role === 4 ? 'ผู้ดูแล (Admin)' : 'ผู้ช่วยสอน (TA)'
}

const getAdminAvatar = (admin: any) => {
    if (admin.user?.avatar) return admin.user.avatar
    if (authStore.user?.id === admin.user?.id) return authStore.user?.avatar
    return null
}

import BaseAvatar from '~/components/atoms/BaseAvatar.vue'
</script>

<template>
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold flex items-center gap-2">
                    <Icon icon="eos-icons:admin-outlined" class="text-blue-600" />
                    จัดการผู้ดูแลรายวิชา
                </h1>
                <p class="text-gray-500 text-sm mt-1">เพิ่มหรือลบผู้ดูแลและผู้ช่วยสอน</p>
            </div>
            <button @click="showInviteModal = true" 
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 shadow transition-all">
                <Icon icon="mdi:plus" /> เพิ่มผู้ดูแล
            </button>
        </div>

        <!-- Loading -->
        <div v-if="isLoading && !showInviteModal" class="text-center py-10">
            <Icon icon="svg-spinners:ring-resize" class="w-10 h-10 mx-auto text-blue-500" />
        </div>

        <!-- List -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="admin in admins" :key="admin.id" 
                class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 flex items-center gap-4 relative overflow-hidden">
                
                <!-- Status Badge -->
                <div v-if="admin.status === 2" class="absolute top-0 right-0 bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-bl-lg font-medium">
                    รอการตอบรับ
                </div>
                <div v-else class="absolute top-0 right-0 bg-green-100 text-green-800 text-xs px-2 py-1 rounded-bl-lg font-medium">
                    {{ getRoleName(admin) }}
                </div>

                <BaseAvatar :src="getAdminAvatar(admin)" :name="admin.user?.name" size="xl" />
                
                <div class="flex-1 min-w-0">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 truncate">{{ admin.user?.name || 'Unknown' }}</h3>
                    <p class="text-sm text-gray-500 truncate">{{ admin.user?.email }}</p>
                    <div class="flex items-center gap-2 mt-2">
                        <button @click="removeAdmin(admin)" class="text-red-500 hover:text-red-700 text-xs flex items-center gap-1 bg-red-50 px-2 py-1 rounded-md transition-colors">
                            <Icon icon="mdi:trash-can-outline" /> ลบ/ยกเลิก
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Empty State -->
            <div v-if="admins.length === 0" class="col-span-full text-center py-10 text-gray-500">
                ยังไม่มีผู้ดูแล
            </div>
        </div>

        <!-- Invite Modal -->
        <div v-if="showInviteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh]">
                <div class="p-4 border-b dark:border-gray-700 flex justify-between items-center">
                    <h3 class="font-bold text-lg">เชิญผู้ดูแล / TA</h3>
                    <button @click="showInviteModal = false" class="text-gray-500 hover:text-gray-700">
                        <Icon icon="mdi:close" class="w-6 h-6" />
                    </button>
                </div>
                
                <div class="p-4 space-y-4">
                    <!-- Role Selection -->
                    <div>
                        <label class="block text-sm font-medium mb-1">ตำแหน่ง</label>
                        <select v-model="selectedRole" class="w-full border rounded-lg p-2 dark:bg-gray-900 dark:border-gray-700">
                            <option :value="3">ผู้ช่วยสอน (TA) - จัดการเนื้อหา/การบ้านได้</option>
                            <option :value="4">ผู้ดูแลระบบ (Admin) - จัดการทุกอย่างได้</option>
                        </select>
                    </div>

                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium mb-1">ค้นหาผู้ใช้งาน (ชื่อ, อีเมล, รหัสอ้างอิง)</label>
                        <div class="flex gap-2">
                            <input v-model="searchQuery" @keyup.enter="handleSearch" type="text" class="flex-1 border rounded-lg p-2 dark:bg-gray-900 dark:border-gray-700" placeholder="พิมพ์ชื่อเพื่อค้นหา..." />
                            <button @click="handleSearch" class="bg-blue-600 text-white px-4 rounded-lg hover:bg-blue-700">
                                <Icon icon="heroicons:magnifying-glass" />
                            </button>
                        </div>
                    </div>

                    <!-- Results -->
                    <div class="flex-1 overflow-y-auto min-h-[200px] border rounded-lg p-2 bg-gray-50 dark:bg-gray-900/50">
                        <div v-if="isSearching" class="text-center py-4">
                            <Icon icon="svg-spinners:ring-resize" class="w-6 h-6 mx-auto text-blue-500" />
                        </div>
                        <div v-else-if="searchResults.length > 0" class="space-y-2">
                            <div v-for="user in searchResults" :key="user.id" class="flex items-center justify-between p-2 bg-white dark:bg-gray-800 rounded shadow-sm hover:shadow-md transition-shadow">
                                <div class="flex items-center gap-3">
                                    <BaseAvatar :src="user.avatar" :name="user.name" size="md" />
                                    <div>
                                        <p class="font-medium text-sm">{{ user.name }}</p>
                                        <p class="text-xs text-gray-500">{{ user.email }}</p>
                                    </div>
                                </div>
                                <button @click="inviteUser(user)" class="text-blue-600 hover:text-blue-800 text-sm font-medium px-3 py-1 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                                    เชิญ
                                </button>
                            </div>
                        </div>
                        <div v-else-if="searchQuery.length > 1" class="text-center py-4 text-gray-500">
                            ไม่พบผู้ใช้งาน
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
