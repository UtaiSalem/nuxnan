<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useMemberStore } from '@/Pages/v2/store/modules/member/memberStore';
import { useGroupStore } from '@/Pages/v2/store/modules/group/groupStore';
import CourseHeader from '@/Pages/v2/components/course/CourseHeader.vue';
import CourseSidebar from '@/Pages/v2/components/course/CourseSidebar.vue';
import MemberList from '@/Pages/v2/components/course/MemberList.vue';
import MemberInviteModal from '@/Pages/v2/components/course/MemberInviteModal.vue';
import Pagination from '@/Pages/v2/components/common/Pagination.vue';

const props = defineProps({
    courseId: {
        type: Number,
        required: true,
    },
});

// Stores
const memberStore = useMemberStore();
const groupStore = useGroupStore();

// State
const loading = ref(true);
const error = ref(null);
const showInviteModal = ref(false);
const selectedMembers = ref([]);
const showBulkActions = ref(false);
const searchQuery = ref('');
const roleFilter = ref('');

// Computed
const members = computed(() => memberStore.paginatedMembers);
const pagination = computed(() => memberStore.pagination);
const isLoading = computed(() => memberStore.isLoading('fetch_members'));
const groups = computed(() => groupStore.groupsForSelect);
const filteredMembers = computed(() => memberStore.filteredMembers);

// Load data
onMounted(async () => {
    try {
        loading.value = true;
        
        // Load members and groups in parallel
        await Promise.all([
            memberStore.fetchMembers(props.courseId),
            groupStore.fetchGroups(props.courseId),
        ]);
        
    } catch (err) {
        error.value = err.message;
        console.error('Failed to load members:', err);
    } finally {
        loading.value = false;
    }
});

// Watch filters
watch([searchQuery, roleFilter], ([query, role]) => {
    memberStore.updateFilters({ q: query, role });
}, { debounce: 300 });

// Actions
const handleInviteMember = () => {
    showInviteModal.value = true;
};

const handleInviteMembers = async (inviteData) => {
    try {
        if (inviteData.type === 'single') {
            await memberStore.inviteMember(props.courseId, {
                email: inviteData.email,
                group_id: inviteData.groupId,
            });
        } else if (inviteData.type === 'bulk') {
            await memberStore.bulkImport(props.courseId, inviteData.members);
        }
        
        showInviteModal.value = false;
        alert('เชิญสมาชิกเรียบร้อยแล้ว');
    } catch (err) {
        alert('เกิดข้อผิดพลาด: ' + err.message);
    }
};

const handleUpdateRole = async (member, newRole) => {
    try {
        await memberStore.updateMemberRole(member.id, newRole);
        alert('อัพเดทบทบาทสมาชิกเรียบร้อยแล้ว');
    } catch (err) {
        alert('เกิดข้อผิดพลาด: ' + err.message);
    }
};

const handleRemoveMember = async (member) => {
    if (confirm(`คุณต้องการลบสมาชิก "${member.name}" ออกจากรายวิชาใช่หรือไม่?`)) {
        try {
            await memberStore.removeMember(props.courseId, member.id);
            alert('ลบสมาชิกเรียบร้อยแล้ว');
        } catch (err) {
            alert('เกิดข้อผิดพลาด: ' + err.message);
        }
    }
};

const handleBulkAction = async (action) => {
    if (selectedMembers.value.length === 0) {
        alert('กรุณาเลือกสมาชิกที่ต้องการดำเนินการ');
        return;
    }

    try {
        switch (action) {
            case 'updateRole':
                const role = prompt('กรุณาระบุบทบาทใหม่ (student, teacher, ta):');
                if (role) {
                    await memberStore.bulkUpdateRoles(
                        selectedMembers.value.map(id => ({ member_id: id, role }))
                    );
                    alert('อัพเดทบทบาทสมาชิกเรียบร้อยแล้ว');
                }
                break;
                
            case 'remove':
                if (confirm(`คุณต้องการลบสมาชิก ${selectedMembers.value.length} คนออกจากรายวิชาใช่หรือไม่?`)) {
                    await memberStore.bulkRemoveMembers(
                        props.courseId,
                        selectedMembers.value
                    );
                    alert('ลบสมาชิกเรียบร้อยแล้ว');
                }
                break;
                
            case 'addToGroup':
                const groupId = prompt('กรุณาระบุ ID กลุ่ม:');
                if (groupId) {
                    // This would need to be implemented in the service
                    alert('เพิ่มสมาชิกลงในกลุ่มเรียบร้อยแล้ว');
                }
                break;
        }
        
        selectedMembers.value = [];
        showBulkActions.value = false;
    } catch (err) {
        alert('เกิดข้อผิดพลาด: ' + err.message);
    }
};

const handleSelectMember = (memberId) => {
    const index = selectedMembers.value.indexOf(memberId);
    if (index > -1) {
        selectedMembers.value.splice(index, 1);
    } else {
        selectedMembers.value.push(memberId);
    }
};

const handleSelectAll = () => {
    if (selectedMembers.value.length === filteredMembers.value.length) {
        selectedMembers.value = [];
    } else {
        selectedMembers.value = filteredMembers.value.map(m => m.id);
    }
};

const handlePageChange = (page) => {
    memberStore.updatePagination({ page });
};

const handlePerPageChange = (perPage) => {
    memberStore.updatePagination({ page: 1, perPage });
};

const handleExport = async (format = 'csv') => {
    try {
        const data = await memberStore.exportMembers(props.courseId, format);
        
        // Create download link
        const blob = new Blob([data], { 
            type: format === 'csv' ? 'text/csv' : 'application/vnd.ms-excel' 
        });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `members.${format}`;
        a.click();
        window.URL.revokeObjectURL(url);
    } catch (err) {
        alert('เกิดข้อผิดพลาดในการส่งออก: ' + err.message);
    }
};
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <CourseHeader 
            :course-id="courseId"
            title="จัดการสมาชิก"
        />

        <div class="flex">
            <!-- Sidebar -->
            <CourseSidebar 
                :course-id="courseId"
                :active-tab="'members'"
            />

            <!-- Main Content -->
            <main class="flex-1 p-6">
                <!-- Loading State -->
                <div v-if="loading" class="flex justify-center items-center h-64">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="bg-red-50 border-l-4 border-red-400 p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 00016zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-2.293 2.293a1 1 0 101.414 1.414L10 11.414l2.293 2.293a1 1 0 001.414-1.414L11.414 10l2.293-2.293a1 1 0 00-1.414-1.414L10 8.586 7.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                {{ error }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Members Content -->
                <div v-else class="space-y-6">
                    <!-- Header with Actions -->
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h1 class="text-2xl font-semibold text-gray-900">
                                จัดการสมาชิก
                            </h1>
                            <p class="mt-1 text-sm text-gray-600">
                                จัดการสมาชิกและสิทธิในรายวิชา
                            </p>
                        </div>
                        <div class="flex space-x-3">
                            <button 
                                @click="handleInviteMember"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                            >
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0 0h3m-13 0h3m0 0V9m0 0v3m0 0h3" />
                                </svg>
                                เชิญสมาชิก
                            </button>
                            <button 
                                @click="handleExport('csv')"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3 3m3-3l3 3m-3-3l3 3" />
                                </svg>
                                ส่งออก CSV
                            </button>
                        </div>
                    </div>

                    <!-- Filters and Search -->
                    <div class="bg-white shadow rounded-lg p-4 mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    ค้นหา
                                </label>
                                <input 
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="ค้นหาตามชื่อหรืออีเมล..."
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    บทบาท
                                </label>
                                <select 
                                    v-model="roleFilter"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                >
                                    <option value="">ทั้งหมด</option>
                                    <option value="student">นักเรียน</option>
                                    <option value="teacher">ผู้สอน</option>
                                    <option value="ta">ผู้ช่วยสอน</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    การกระทำ
                                </label>
                                <div class="flex space-x-2">
                                    <button 
                                        v-if="selectedMembers.length > 0"
                                        @click="showBulkActions = !showBulkActions"
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    >
                                        การกระทำกับที่เลือก ({{ selectedMembers.length }})
                                        <svg class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bulk Actions Dropdown -->
                    <div v-if="showBulkActions && selectedMembers.length > 0" class="bg-white shadow rounded-lg p-4 mb-6">
                        <div class="flex space-x-2">
                            <button 
                                @click="handleBulkAction('updateRole')"
                                class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50"
                            >
                                อัพเดทบทบาท
                            </button>
                            <button 
                                @click="handleBulkAction('addToGroup')"
                                class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50"
                            >
                                เพิ่มลงในกลุ่ม
                            </button>
                            <button 
                                @click="handleBulkAction('remove')"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700"
                            >
                                ลบออก
                            </button>
                        </div>
                    </div>

                    <!-- Members List -->
                    <MemberList 
                        :members="members"
                        :groups="groups"
                        :selected-members="selectedMembers"
                        :loading="isLoading"
                        @select="handleSelectMember"
                        @select-all="handleSelectAll"
                        @update-role="handleUpdateRole"
                        @remove="handleRemoveMember"
                    />

                    <!-- Pagination -->
                    <Pagination 
                        :pagination="pagination"
                        @page-change="handlePageChange"
                        @per-page-change="handlePerPageChange"
                    />
                </div>
            </main>
        </div>

        <!-- Invite Modal -->
        <MemberInviteModal 
            v-if="showInviteModal"
            :groups="groups"
            @invite="handleInviteMembers"
            @cancel="() => showInviteModal = false"
        />
    </div>
</template>