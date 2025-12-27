<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    members: {
        type: Array,
        required: true,
    },
    groups: {
        type: Array,
        default: () => [],
    },
    selectedMembers: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits([
    'select',
    'select-all',
    'update-role',
    'remove',
]);

const selectAll = ref(false);

const allSelected = computed(() => {
    return props.members.length > 0 && props.selectedMembers.length === props.members.length;
});

const handleSelectAll = () => {
    if (allSelected.value) {
        emit('select-all', false);
    } else {
        emit('select-all', true);
    }
};

const handleSelectMember = (memberId) => {
    emit('select', memberId);
};

const handleUpdateRole = (member, newRole) => {
    emit('update-role', member, newRole);
};

const handleRemoveMember = (member) => {
    emit('remove', member);
};

const getRoleLabel = (role) => {
    const roleLabels = {
        student: 'นักเรียน',
        teacher: 'ผู้สอน',
        ta: 'ผู้ช่วยสอน',
        instructor: 'ผู้สอน',
        teaching_assistant: 'ผู้ช่วยสอน',
    };
    return roleLabels[role] || role;
};

const getStatusBadge = (status) => {
    const statusClasses = {
        active: 'bg-green-100 text-green-800',
        pending: 'bg-yellow-100 text-yellow-800',
        suspended: 'bg-red-100 text-red-800',
        inactive: 'bg-gray-100 text-gray-800',
    };
    
    const statusLabels = {
        active: 'ใช้งานอยู่',
        pending: 'รออนุมัติ',
        suspended: 'ระงับ',
        inactive: 'ไม่ใช้งาน',
    };
    
    return {
        class: statusClasses[status] || 'bg-gray-100 text-gray-800',
        label: statusLabels[status] || status,
    };
};
</script>

<template>
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <!-- Table Header -->
        <div class="px-6 py-3 border-b border-gray-200 bg-gray-50">
            <div class="flex items-center">
                <input
                    type="checkbox"
                    :checked="allSelected"
                    @change="handleSelectAll"
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                >
                <span class="ml-3 text-sm font-medium text-gray-900">
                    สมาชิกทั้งหมด ({{ members.length }})
                </span>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center items-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>

        <!-- Empty State -->
        <div v-else-if="members.length === 0" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">ไม่มีสมาชิก</h3>
            <p class="mt-1 text-sm text-gray-500">ยังไม่มีสมาชิกในรายวิชานี้</p>
        </div>

        <!-- Members List -->
        <div v-else class="divide-y divide-gray-200">
            <div
                v-for="member in members"
                :key="member.id"
                class="px-6 py-4 hover:bg-gray-50"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <!-- Checkbox -->
                        <input
                            type="checkbox"
                            :checked="selectedMembers.includes(member.id)"
                            @change="handleSelectMember(member.id)"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >

                        <!-- Avatar -->
                        <div class="ml-4 flex-shrink-0">
                            <img
                                v-if="member.avatar"
                                :src="member.avatar"
                                :alt="member.name"
                                class="h-10 w-10 rounded-full object-cover"
                            >
                            <div
                                v-else
                                class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center"
                            >
                                <span class="text-sm font-medium text-gray-600">
                                    {{ member.name.charAt(0).toUpperCase() }}
                                </span>
                            </div>
                        </div>

                        <!-- Member Info -->
                        <div class="ml-4">
                            <div class="flex items-center">
                                <h4 class="text-sm font-medium text-gray-900">
                                    {{ member.name }}
                                </h4>
                                <span
                                    :class="getStatusBadge(member.status).class"
                                    class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                >
                                    {{ getStatusBadge(member.status).label }}
                                </span>
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ member.email }}
                                <span v-if="member.student_id" class="ml-2">
                                    ({{ member.student_id }})
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center space-x-2">
                        <!-- Role Dropdown -->
                        <select
                            :value="member.role"
                            @change="handleUpdateRole(member, $event.target.value)"
                            class="text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="student">นักเรียน</option>
                            <option value="teacher">ผู้สอน</option>
                            <option value="ta">ผู้ช่วยสอน</option>
                        </select>

                        <!-- Group Badge -->
                        <span
                            v-if="member.group_name"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                        >
                            {{ member.group_name }}
                        </span>

                        <!-- More Actions -->
                        <div class="relative">
                            <button
                                @click="handleRemoveMember(member)"
                                class="text-red-600 hover:text-red-900 text-sm"
                            >
                                ลบ
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

