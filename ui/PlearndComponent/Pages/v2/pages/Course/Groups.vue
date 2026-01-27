<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useGroupStore } from '@/Pages/v2/store/modules/group/groupStore';
import { useMemberStore } from '@/Pages/v2/store/modules/member/memberStore';
import CourseHeader from '@/Pages/v2/components/course/CourseHeader.vue';
import CourseSidebar from '@/Pages/v2/components/course/CourseSidebar.vue';
import GroupTable from '@/Pages/v2/components/course/GroupTable.vue';
import GroupFormModal from '@/Pages/v2/components/course/GroupFormModal.vue';
import Pagination from '@/Pages/v2/components/common/Pagination.vue';

const props = defineProps({
    courseId: {
        type: Number,
        required: true,
    },
});

// Stores
const groupStore = useGroupStore();
const memberStore = useMemberStore();

// State
const loading = ref(true);
const error = ref(null);
const showGroupModal = ref(false);
const editingGroup = ref(null);
const selectedGroup = ref(null);
const showMembersPanel = ref(false);

// Computed
const groups = computed(() => groupStore.paginatedGroups);
const pagination = computed(() => groupStore.pagination);
const isLoading = computed(() => groupStore.isLoading('fetch_groups'));

// Load data
onMounted(async () => {
    try {
        loading.value = true;
        
        // Load groups
        await groupStore.fetchGroups(props.courseId);
        
    } catch (err) {
        error.value = err.message;
        console.error('Failed to load groups:', err);
    } finally {
        loading.value = false;
    }
});

// Watch pagination changes
watch(() => pagination.value.page, async (newPage) => {
    await groupStore.fetchGroups(props.courseId, {
        page: newPage,
        perPage: pagination.value.perPage,
    });
});

// Actions
const handleCreateGroup = () => {
    editingGroup.value = null;
    showGroupModal.value = true;
};

const handleEditGroup = (group) => {
    editingGroup.value = { ...group };
    showGroupModal.value = true;
};

const handleDeleteGroup = async (group) => {
    if (confirm(`คุณต้องการลบกลุ่ม "${group.name}" ใช่หรือไม่?`)) {
        try {
            await groupStore.deleteGroup(group.id);
            // Show success message
            alert('ลบกลุ่มเรียบร้อยแล้ว');
        } catch (err) {
            alert('เกิดข้อผิดพลาดในการลบกลุ่ม: ' + err.message);
        }
    }
};

const handleViewMembers = async (group) => {
    selectedGroup.value = group;
    showMembersPanel.value = true;
    
    // Load group members
    try {
        await groupStore.fetchGroupMembers(group.id);
    } catch (err) {
        console.error('Failed to load group members:', err);
    }
};

const handleSaveGroup = async (groupData) => {
    try {
        if (editingGroup.value) {
            // Update existing group
            await groupStore.updateGroup(editingGroup.value.id, groupData);
            alert('อัพเดทข้อมูลกลุ่มเรียบร้อยแล้ว');
        } else {
            // Create new group
            await groupStore.createGroup(props.courseId, groupData);
            alert('สร้างกลุ่มใหม่เรียบร้อยแล้ว');
        }
        
        showGroupModal.value = false;
        editingGroup.value = null;
    } catch (err) {
        alert('เกิดข้อผิดพลาด: ' + err.message);
    }
};

const handlePageChange = (page) => {
    groupStore.updatePagination({ page });
};

const handlePerPageChange = (perPage) => {
    groupStore.updatePagination({ page: 1, perPage });
};
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <CourseHeader 
            :course-id="courseId"
            title="จัดการกลุ่มเรียน"
        />

        <div class="flex">
            <!-- Sidebar -->
            <CourseSidebar 
                :course-id="courseId"
                :active-tab="'groups'"
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

                <!-- Groups Content -->
                <div v-else class="space-y-6">
                    <!-- Header with Create Button -->
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h1 class="text-2xl font-semibold text-gray-900">
                                จัดการกลุ่มเรียน
                            </h1>
                            <p class="mt-1 text-sm text-gray-600">
                                สร้างและจัดการกลุ่มเรียนในรายวิชานี้
                            </p>
                        </div>
                        <button 
                            @click="handleCreateGroup"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            สร้างกลุ่มใหม่
                        </button>
                    </div>

                    <!-- Groups Table -->
                    <GroupTable 
                        :groups="groups"
                        :loading="isLoading"
                        @edit="handleEditGroup"
                        @delete="handleDeleteGroup"
                        @view-members="handleViewMembers"
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

        <!-- Group Form Modal -->
        <GroupFormModal 
            v-if="showGroupModal"
            :group="editingGroup"
            @save="handleSaveGroup"
            @cancel="() => { showGroupModal = false; editingGroup = null; }"
        />

        <!-- Group Members Panel -->
        <div 
            v-if="showMembersPanel && selectedGroup"
            class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
            @click.self="showMembersPanel = false"
        >
            <div class="relative min-h-screen flex items-center justify-center p-4">
                <div class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                สมาชิกในกลุ่ม: {{ selectedGroup.name }}
                            </h3>
                            <button 
                                @click="showMembersPanel = false"
                                class="text-gray-400 hover:text-gray-500 focus:outline-none"
                            >
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        
                        <!-- Members List -->
                        <div class="mt-4">
                            <div v-if="groupStore.isLoading(`get_members_${selectedGroup.id}`)" class="text-center py-4">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                            </div>
                            <div v-else class="grid grid-cols-1 gap-4 max-h-96 overflow-y-auto">
                                <div 
                                    v-for="member in selectedGroup.members || []" 
                                    :key="member.id"
                                    class="flex items-center p-3 border rounded-lg hover:bg-gray-50"
                                >
                                    <img 
                                        :src="member.avatar || '/images/default-avatar.png'" 
                                        :alt="member.name"
                                        class="h-10 w-10 rounded-full mr-3"
                                    >
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ member.name }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ member.email }}
                                        </p>
                                    </div>
                                    <span 
                                        :class="{
                                            'px-2 py-1 text-xs rounded-full': true,
                                            'bg-green-100 text-green-800': member.role === 'leader',
                                            'bg-blue-100 text-blue-800': member.role === 'teacher',
                                            'bg-gray-100 text-gray-800': member.role === 'student',
                                        }"
                                    >
                                        {{ member.role === 'leader' ? 'หัวหน้า' : member.role === 'teacher' ? 'ผู้สอน' : 'นักเรียน' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>