<script setup>
import { ref, onMounted, computed } from 'vue';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';

import AcademyLayout from '@/layouts/AcademyLayout.vue';

const props = defineProps({
    academy: Object,
    isAcademyAdmin: Boolean,
    groupId: [String, Number]
});

const group = ref(null);
const members = ref([]);
const isLoading = ref(true);
const showAddMemberModal = ref(false);
const searchQuery = ref('');
const searchResults = ref([]);
const isSearching = ref(false);
const selectedUser = ref(null);
const selectedRole = ref('student');

const { $api } = useNuxtApp();

// Fetch group details
const fetchGroup = async () => {
    try {
        isLoading.value = true;
        const response = await $api(`/academies/groups/${props.groupId}`);
        if (response.success) {
            group.value = response.group;
        }
    } catch (error) {
        console.error('Error fetching group:', error);
    }
};

// Fetch group members
const fetchMembers = async () => {
    try {
        const response = await $api(`/academies/groups/${props.groupId}/members`);
        if (response.success) {
            members.value = response.members;
        }
    } catch (error) {
        console.error('Error fetching members:', error);
    } finally {
        isLoading.value = false;
    }
};

// Search users to add
const searchUsers = async () => {
    if (!searchQuery.value || searchQuery.value.length < 2) {
        searchResults.value = [];
        return;
    }
    
    try {
        isSearching.value = true;
        const response = await $api(`/api/users/search?q=${searchQuery.value}`);
        if (response.success) {
            // Filter out existing members
            const memberIds = members.value.map(m => m.id);
            searchResults.value = response.users.filter(u => !memberIds.includes(u.id));
        }
    } catch (error) {
        console.error('Error searching users:', error);
    } finally {
        isSearching.value = false;
    }
};

// Add member to group
const addMember = async () => {
    if (!selectedUser.value) return;
    
    try {
        const response = await $api(`/academies/groups/${props.groupId}/members`, {
            method: 'POST',
            body: {
                user_id: selectedUser.value.id,
                role: selectedRole.value
            }
        });
        
        if (response.success) {
            members.value.push({
                ...selectedUser.value,
                pivot: { role: selectedRole.value }
            });
            showAddMemberModal.value = false;
            selectedUser.value = null;
            searchQuery.value = '';
            searchResults.value = [];
            
            Swal.fire({
                icon: 'success',
                title: 'เพิ่มสมาชิกสำเร็จ',
                timer: 2000,
                showConfirmButton: false
            });
        }
    } catch (error) {
        console.error('Error adding member:', error);
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: error.message || 'ไม่สามารถเพิ่มสมาชิกได้'
        });
    }
};

// Remove member from group
const removeMember = async (userId) => {
    const result = await Swal.fire({
        icon: 'warning',
        title: 'ยืนยันการลบ',
        text: 'คุณต้องการลบสมาชิกคนนี้ออกจากกลุ่มหรือไม่?',
        showCancelButton: true,
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#dc2626'
    });
    
    if (result.isConfirmed) {
        try {
            const response = await $api(`/academies/groups/${props.groupId}/members`, {
                method: 'DELETE',
                body: { user_id: userId }
            });
            
            if (response.success) {
                members.value = members.value.filter(m => m.id !== userId);
                Swal.fire({
                    icon: 'success',
                    title: 'ลบสมาชิกสำเร็จ',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        } catch (error) {
            console.error('Error removing member:', error);
        }
    }
};

// Update member role
const updateRole = async (userId, newRole) => {
    try {
        const response = await $api(`/academies/groups/${props.groupId}/members/role`, {
            method: 'PATCH',
            body: {
                user_id: userId,
                role: newRole
            }
        });
        
        if (response.success) {
            const member = members.value.find(m => m.id === userId);
            if (member) {
                member.pivot.role = newRole;
            }
            Swal.fire({
                icon: 'success',
                title: 'อัปเดตบทบาทสำเร็จ',
                timer: 2000,
                showConfirmButton: false
            });
        }
    } catch (error) {
        console.error('Error updating role:', error);
    }
};

// Group type info
const typeInfo = computed(() => {
    const types = {
        department: { label: 'ฝ่ายงาน', icon: 'heroicons:briefcase-solid', color: 'indigo' },
        classroom: { label: 'ห้องเรียน', icon: 'heroicons:academic-cap-solid', color: 'cyan' },
        club: { label: 'ชมรม', icon: 'heroicons:star-solid', color: 'green' }
    };
    return types[group.value?.type] || types.classroom;
});

// Role info
const roleInfo = {
    student: { label: 'นักเรียน', color: 'blue' },
    teacher: { label: 'ครู', color: 'purple' },
    admin: { label: 'ผู้ดูแล', color: 'red' }
};

onMounted(() => {
    fetchGroup();
    fetchMembers();
});
</script>

<template>
    <AcademyLayout 
        :academy
        :isAcademyAdmin
    >
        <template #academyContent>
            <div class="mt-4">
                <!-- Loading -->
                <div v-if="isLoading" class="flex justify-center items-center h-[40vh]">
                    <Icon icon="svg-spinners:ring-resize" class="w-12 h-12 text-purple-500" />
                </div>

                <template v-else-if="group">
                    <!-- Group Header -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden mb-6">
                        <div 
                            class="h-32 bg-gradient-to-br flex items-center justify-center"
                            :class="`from-${typeInfo.color}-400 to-${typeInfo.color}-600`"
                        >
                            <div class="text-center text-white">
                                <Icon :icon="typeInfo.icon" class="w-12 h-12 mx-auto mb-2" />
                                <span class="text-sm font-medium bg-white/20 px-3 py-1 rounded-full">{{ typeInfo.label }}</span>
                            </div>
                        </div>
                        <div class="p-6">
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ group.name }}</h1>
                            <p v-if="group.description" class="text-gray-500 dark:text-gray-400">{{ group.description }}</p>
                            
                            <div class="flex items-center gap-4 mt-4">
                                <div class="flex items-center gap-2 text-gray-600 dark:text-gray-300">
                                    <Icon icon="heroicons:users-solid" class="w-5 h-5" />
                                    <span>{{ members.length }} สมาชิก</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Members Section -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <Icon icon="heroicons:users-solid" class="w-6 h-6 text-purple-500" />
                                สมาชิกในกลุ่ม
                            </h2>
                            
                            <button
                                v-if="isAcademyAdmin"
                                @click="showAddMemberModal = true"
                                class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-purple-500 to-blue-500 hover:from-purple-600 hover:to-blue-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all"
                            >
                                <Icon icon="heroicons:user-plus-solid" class="w-5 h-5" />
                                เพิ่มสมาชิก
                            </button>
                        </div>

                        <!-- Members List -->
                        <div v-if="members.length > 0" class="space-y-3">
                            <div 
                                v-for="member in members" 
                                :key="member.id"
                                class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            >
                                <div class="flex items-center gap-4">
                                    <img 
                                        :src="member.profile_photo_url || '/images/default-avatar.png'" 
                                        :alt="member.name"
                                        class="w-12 h-12 rounded-full object-cover"
                                    />
                                    <div>
                                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ member.name }}</h3>
                                        <span 
                                            class="text-xs px-2 py-0.5 rounded-full"
                                            :class="`bg-${roleInfo[member.pivot?.role || 'student'].color}-100 text-${roleInfo[member.pivot?.role || 'student'].color}-700 dark:bg-${roleInfo[member.pivot?.role || 'student'].color}-900/30 dark:text-${roleInfo[member.pivot?.role || 'student'].color}-300`"
                                        >
                                            {{ roleInfo[member.pivot?.role || 'student'].label }}
                                        </span>
                                    </div>
                                </div>

                                <div v-if="isAcademyAdmin" class="flex items-center gap-2">
                                    <select 
                                        :value="member.pivot?.role || 'student'"
                                        @change="updateRole(member.id, $event.target.value)"
                                        class="text-sm border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-1.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    >
                                        <option value="student">นักเรียน</option>
                                        <option value="teacher">ครู</option>
                                        <option value="admin">ผู้ดูแล</option>
                                    </select>
                                    
                                    <button
                                        @click="removeMember(member.id)"
                                        class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                                        title="ลบสมาชิก"
                                    >
                                        <Icon icon="heroicons:trash-solid" class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-12">
                            <Icon icon="heroicons:users" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                            <p class="text-gray-500 dark:text-gray-400">ยังไม่มีสมาชิกในกลุ่มนี้</p>
                        </div>
                    </div>
                </template>

                <!-- Add Member Modal -->
                <Teleport to="body">
                    <div 
                        v-if="showAddMemberModal" 
                        class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50"
                        @click.self="showAddMemberModal = false"
                    >
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
                            <div class="bg-gradient-to-r from-purple-500 to-blue-500 px-6 py-4">
                                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                                    <Icon icon="heroicons:user-plus-solid" class="w-6 h-6" />
                                    เพิ่มสมาชิก
                                </h3>
                            </div>

                            <div class="p-6 space-y-4">
                                <!-- Search Input -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        ค้นหาผู้ใช้
                                    </label>
                                    <div class="relative">
                                        <input
                                            v-model="searchQuery"
                                            @input="searchUsers"
                                            type="text"
                                            class="w-full px-4 py-3 pl-10 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                            placeholder="พิมพ์ชื่อผู้ใช้..."
                                        />
                                        <Icon icon="heroicons:magnifying-glass-solid" class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                                    </div>
                                    
                                    <!-- Search Results -->
                                    <div v-if="searchResults.length > 0" class="mt-2 max-h-48 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-xl">
                                        <button
                                            v-for="user in searchResults"
                                            :key="user.id"
                                            @click="selectedUser = user; searchQuery = user.name; searchResults = []"
                                            class="w-full flex items-center gap-3 p-3 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                        >
                                            <img 
                                                :src="user.profile_photo_url || '/images/default-avatar.png'" 
                                                :alt="user.name"
                                                class="w-10 h-10 rounded-full object-cover"
                                            />
                                            <span class="font-medium text-gray-900 dark:text-white">{{ user.name }}</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Selected User -->
                                <div v-if="selectedUser" class="flex items-center gap-3 p-3 bg-purple-50 dark:bg-purple-900/30 rounded-xl">
                                    <img 
                                        :src="selectedUser.profile_photo_url || '/images/default-avatar.png'" 
                                        :alt="selectedUser.name"
                                        class="w-10 h-10 rounded-full object-cover"
                                    />
                                    <span class="font-medium text-gray-900 dark:text-white">{{ selectedUser.name }}</span>
                                    <button @click="selectedUser = null; searchQuery = ''" class="ml-auto p-1 text-gray-400 hover:text-gray-600">
                                        <Icon icon="heroicons:x-mark-solid" class="w-5 h-5" />
                                    </button>
                                </div>

                                <!-- Role Selection -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        บทบาท
                                    </label>
                                    <div class="grid grid-cols-3 gap-2">
                                        <button
                                            v-for="(info, role) in roleInfo"
                                            :key="role"
                                            @click="selectedRole = role"
                                            :class="[
                                                'flex flex-col items-center p-3 rounded-xl border-2 transition-all',
                                                selectedRole === role 
                                                    ? `border-${info.color}-500 bg-${info.color}-50 dark:bg-${info.color}-900/30` 
                                                    : 'border-gray-200 dark:border-gray-600 hover:border-gray-300'
                                            ]"
                                        >
                                            <span class="text-sm font-medium">{{ info.label }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 flex justify-end gap-3">
                                <button
                                    @click="showAddMemberModal = false"
                                    class="px-4 py-2 text-gray-600 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                >
                                    ยกเลิก
                                </button>
                                <button
                                    @click="addMember"
                                    :disabled="!selectedUser"
                                    class="px-6 py-2 bg-gradient-to-r from-purple-500 to-blue-500 hover:from-purple-600 hover:to-blue-600 text-white font-semibold rounded-lg shadow disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                                >
                                    เพิ่มสมาชิก
                                </button>
                            </div>
                        </div>
                    </div>
                </Teleport>
            </div>
        </template>
    </AcademyLayout>
</template>
