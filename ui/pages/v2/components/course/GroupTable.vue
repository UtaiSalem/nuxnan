<script setup>
import { computed } from 'vue';

const props = defineProps({
    groups: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['edit', 'delete', 'view-members']);

// Computed
const sortedGroups = computed(() => {
    return [...props.groups].sort((a, b) => a.name.localeCompare(b.name));
});

// Actions
const handleEdit = (group) => {
    emit('edit', group);
};

const handleDelete = (group) => {
    emit('delete', group);
};

const handleViewMembers = (group) => {
    emit('view-members', group);
};
</script>

<template>
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center items-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>

        <!-- Groups Table -->
        <div v-else-if="sortedGroups.length > 0" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            ชื่อกลุ่ม
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            สมาชิก
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            หัวหน้ากลุ่ม
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            สถานะ
                        </th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="group in sortedGroups" :key="group.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                {{ group.name }}
                            </div>
                            <div v-if="group.description" class="text-sm text-gray-500">
                                {{ group.description }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                {{ group.membersCount || 0 }} คน
                            </div>
                            <div v-if="group.capacity" class="text-sm text-gray-500">
                                ความจุ: {{ group.capacity }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div v-if="group.leader" class="flex items-center">
                                <img 
                                    :src="group.leader.avatar || '/images/default-avatar.png'" 
                                    :alt="group.leader.name"
                                    class="h-8 w-8 rounded-full mr-2"
                                >
                                <div class="text-sm text-gray-900">
                                    {{ group.leader.name }}
                                </div>
                            </div>
                            <div v-else class="text-sm text-gray-500">
                                ยังไม่มีหัวหน้า
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span 
                                :class="{
                                    'px-2 inline-flex text-xs leading-5 font-semibold rounded-full': true,
                                    'bg-green-100 text-green-800': group.status === 'active',
                                    'bg-yellow-100 text-yellow-800': group.status === 'inactive',
                                    'bg-red-100 text-red-800': group.status === 'archived',
                                }"
                            >
                                {{ group.status === 'active' ? 'ใช้งาน' : group.status === 'inactive' ? 'ไม่ใช้งาน' : 'เก็บถาวร' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end space-x-2">
                                <button 
                                    @click="handleViewMembers(group)"
                                    class="text-blue-600 hover:text-blue-900"
                                    title="ดูสมาชิก"
                                >
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v-1a6 6 0 00-9 5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                                    </svg>
                                </button>
                                <button 
                                    @click="handleEdit(group)"
                                    class="text-indigo-600 hover:text-indigo-900"
                                    title="แก้ไข"
                                >
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 0l2.828 2.828a2 2 0 010 2.828l-8.586 8.586a1 1 0 01-.707.293l-3.414-3.414a1 1 0 111.414-1.414l2.586 2.586a1 1 0 00.707 0l6.586-6.586a1 1 0 000-1.414z" />
                                    </svg>
                                </button>
                                <button 
                                    @click="handleDelete(group)"
                                    class="text-red-600 hover:text-red-900"
                                    title="ลบ"
                                >
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">
                ยังไม่มีกลุ่มเรียน
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                สร้างกลุ่มเรียนแรกเพื่อเริ่มจัดการนักเรียน
            </p>
        </div>
    </div>
</template>

