<script setup>
import { ref, onMounted, computed } from 'vue';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';
import { useApi } from '~/composables/useApi';

import AcademyLayout from '@/layouts/AcademyLayout.vue';
import AcademyGroupCard from '@/components/learn/academy/AcademyGroupCard.vue';

const route = useRoute();
const api = useApi();

// Academy data
const academy = ref(null);
const isAcademyAdmin = ref(false);
const isLoadingAcademy = ref(true);

const groups = ref([]);
const isLoading = ref(false);
const showCreateModal = ref(false);

// Form for creating new group
const newGroup = ref({
    name: '',
    description: '',
    type: 'classroom'
});

// Get academy ID from route query or path
const academyId = computed(() => route.query.academyId || route.params.academyId);
const academyName = computed(() => route.params.name);

// Fetch academy data
const fetchAcademy = async () => {
    try {
        isLoadingAcademy.value = true;
        let url = '';
        if (academyName.value) {
            url = `/api/academies/${academyName.value}`;
        } else if (academyId.value) {
            url = `/api/academies/by-id/${academyId.value}`;
        } else {
            console.error('No academy identifier provided');
            return;
        }
        
        const response = await api.get(url);
        if (response) {
            academy.value = response.academy || response;
            isAcademyAdmin.value = response.isAcademyAdmin || false;
        }
    } catch (error) {
        console.error('Error fetching academy:', error);
    } finally {
        isLoadingAcademy.value = false;
    }
};

const fetchGroups = async () => {
    if (!academy.value?.id && !academy.value?.data?.id) return;
    
    const id = academy.value?.id || academy.value?.data?.id;
    try {
        isLoading.value = true;
        const response = await api.get(`/api/academies/${id}/groups`);
        if (response.success) {
            groups.value = response.groups;
        }
    } catch (error) {
        console.error('Error fetching groups:', error);
    } finally {
        isLoading.value = false;
    }
};

const createGroup = async () => {
    const id = academy.value?.id || academy.value?.data?.id;
    if (!id) return;
    
    try {
        const response = await api.post(`/api/academies/${id}/groups`, newGroup.value);
        
        if (response.success) {
            groups.value.push(response.group);
            showCreateModal.value = false;
            newGroup.value = { name: '', description: '', type: 'classroom' };
            
            Swal.fire({
                icon: 'success',
                title: 'สร้างกลุ่มสำเร็จ',
                text: 'กลุ่มใหม่ถูกสร้างเรียบร้อยแล้ว',
                timer: 2000,
                showConfirmButton: false
            });
        }
    } catch (error) {
        console.error('Error creating group:', error);
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่สามารถสร้างกลุ่มได้ กรุณาลองใหม่อีกครั้ง'
        });
    }
};

const deleteGroup = async (groupId) => {
    const result = await Swal.fire({
        icon: 'warning',
        title: 'ยืนยันการลบ',
        text: 'คุณต้องการลบกลุ่มนี้หรือไม่? การดำเนินการนี้ไม่สามารถยกเลิกได้',
        showCancelButton: true,
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#dc2626'
    });
    
    if (result.isConfirmed) {
        try {
            const response = await api.delete(`/api/academies/groups/${groupId}`);
            
            if (response.success) {
                groups.value = groups.value.filter(g => g.id !== groupId);
                Swal.fire({
                    icon: 'success',
                    title: 'ลบกลุ่มสำเร็จ',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        } catch (error) {
            console.error('Error deleting group:', error);
        }
    }
};

const handleGroupClick = (group) => {
    // Navigate to group detail page
    navigateTo(`/Learn/Academy/GroupDetail?groupId=${group.id}`);
};

onMounted(async () => {
    await fetchAcademy();
    await fetchGroups();
});
</script>

<template>
    <!-- Show loading spinner when loading academy data -->
    <div v-if="isLoadingAcademy" class="flex justify-center items-center h-screen">
        <Icon icon="svg-spinners:ring-resize" class="w-12 h-12 text-purple-500" />
    </div>
    
    <!-- Show error if no academy data -->
    <div v-else-if="!academy" class="flex flex-col justify-center items-center h-screen gap-4">
        <Icon icon="heroicons:exclamation-circle" class="w-16 h-16 text-red-400" />
        <p class="text-gray-600 dark:text-gray-300 text-lg">ไม่สามารถโหลดข้อมูลโรงเรียนได้</p>
        <button 
            @click="fetchAcademy" 
            class="px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg"
        >
            ลองใหม่อีกครั้ง
        </button>
    </div>
    
    <!-- Main content when academy is loaded -->
    <AcademyLayout 
        v-else
        :academy
        :isAcademyAdmin
    >
        <template #academyContent>
            <div class="mt-4">
                <!-- Header with Create Button -->
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <Icon icon="heroicons:user-group-solid" class="w-7 h-7 text-purple-500" />
                        กลุ่มในโรงเรียน
                    </h2>
                    
                    <button
                        v-if="isAcademyAdmin"
                        @click="showCreateModal = true"
                        class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-purple-500 to-blue-500 hover:from-purple-600 hover:to-blue-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300"
                    >
                        <Icon icon="heroicons:plus-circle-solid" class="w-5 h-5" />
                        สร้างกลุ่มใหม่
                    </button>
                </div>

                <!-- Loading State -->
                <div v-if="isLoading" class="flex justify-center items-center h-[40vh]">
                    <Icon icon="svg-spinners:ring-resize" class="w-12 h-12 text-purple-500" />
                </div>

                <!-- Groups Grid -->
                <div v-else-if="groups.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <AcademyGroupCard
                        v-for="group in groups"
                        :key="group.id"
                        :group="group"
                        :isAcademyAdmin="isAcademyAdmin"
                        @click="handleGroupClick"
                        @delete="deleteGroup"
                    />
                </div>

                <!-- Empty State -->
                <div v-else class="flex flex-col justify-center items-center h-[40vh] text-center">
                    <Icon icon="heroicons:user-group" class="w-20 h-20 text-gray-300 dark:text-gray-600 mb-4" />
                    <p class="text-gray-500 dark:text-gray-400 text-lg mb-2">ยังไม่มีกลุ่มในโรงเรียนนี้</p>
                    <p v-if="isAcademyAdmin" class="text-gray-400 dark:text-gray-500 text-sm">กดปุ่ม "สร้างกลุ่มใหม่" เพื่อเริ่มต้น</p>
                </div>

                <!-- Create Group Modal -->
                <Teleport to="body">
                    <div 
                        v-if="showCreateModal" 
                        class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50"
                        @click.self="showCreateModal = false"
                    >
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
                            <!-- Modal Header -->
                            <div class="bg-gradient-to-r from-purple-500 to-blue-500 px-6 py-4">
                                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                                    <Icon icon="heroicons:plus-circle-solid" class="w-6 h-6" />
                                    สร้างกลุ่มใหม่
                                </h3>
                            </div>

                            <!-- Modal Body -->
                            <div class="p-6 space-y-4">
                                <!-- Group Name -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        ชื่อกลุ่ม
                                    </label>
                                    <input
                                        v-model="newGroup.name"
                                        type="text"
                                        class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                                        placeholder="เช่น ฝ่ายวิชาการ, ห้อง ม.4/1"
                                    />
                                </div>

                                <!-- Group Type -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        ประเภทกลุ่ม
                                    </label>
                                    <div class="grid grid-cols-3 gap-2">
                                        <button
                                            @click="newGroup.type = 'department'"
                                            :class="[
                                                'flex flex-col items-center p-3 rounded-xl border-2 transition-all',
                                                newGroup.type === 'department' 
                                                    ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30' 
                                                    : 'border-gray-200 dark:border-gray-600 hover:border-gray-300'
                                            ]"
                                        >
                                            <Icon icon="heroicons:briefcase-solid" class="w-6 h-6 text-indigo-500 mb-1" />
                                            <span class="text-xs font-medium">ฝ่ายงาน</span>
                                        </button>
                                        <button
                                            @click="newGroup.type = 'classroom'"
                                            :class="[
                                                'flex flex-col items-center p-3 rounded-xl border-2 transition-all',
                                                newGroup.type === 'classroom' 
                                                    ? 'border-cyan-500 bg-cyan-50 dark:bg-cyan-900/30' 
                                                    : 'border-gray-200 dark:border-gray-600 hover:border-gray-300'
                                            ]"
                                        >
                                            <Icon icon="heroicons:academic-cap-solid" class="w-6 h-6 text-cyan-500 mb-1" />
                                            <span class="text-xs font-medium">ห้องเรียน</span>
                                        </button>
                                        <button
                                            @click="newGroup.type = 'club'"
                                            :class="[
                                                'flex flex-col items-center p-3 rounded-xl border-2 transition-all',
                                                newGroup.type === 'club' 
                                                    ? 'border-green-500 bg-green-50 dark:bg-green-900/30' 
                                                    : 'border-gray-200 dark:border-gray-600 hover:border-gray-300'
                                            ]"
                                        >
                                            <Icon icon="heroicons:star-solid" class="w-6 h-6 text-green-500 mb-1" />
                                            <span class="text-xs font-medium">ชมรม</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Description -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        คำอธิบาย (ไม่บังคับ)
                                    </label>
                                    <textarea
                                        v-model="newGroup.description"
                                        rows="3"
                                        class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all resize-none"
                                        placeholder="อธิบายเกี่ยวกับกลุ่มนี้..."
                                    ></textarea>
                                </div>
                            </div>

                            <!-- Modal Footer -->
                            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 flex justify-end gap-3">
                                <button
                                    @click="showCreateModal = false"
                                    class="px-4 py-2 text-gray-600 dark:text-gray-300 font-medium rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                >
                                    ยกเลิก
                                </button>
                                <button
                                    @click="createGroup"
                                    :disabled="!newGroup.name"
                                    class="px-6 py-2 bg-gradient-to-r from-purple-500 to-blue-500 hover:from-purple-600 hover:to-blue-600 text-white font-semibold rounded-lg shadow disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                                >
                                    สร้างกลุ่ม
                                </button>
                            </div>
                        </div>
                    </div>
                </Teleport>
            </div>
        </template>
    </AcademyLayout>
</template>
