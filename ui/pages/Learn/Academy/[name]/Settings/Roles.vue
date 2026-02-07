<script setup>
import { ref, defineProps, onMounted } from 'vue';
import AcademyLayout from '@/layouts/AcademyLayout.vue';
import { Icon } from '@iconify/vue';

const props = defineProps({
    academy: Object,
    isAcademyAdmin: Boolean,
});

const roles = ref([]);
const isLoading = ref(false);

const fetchRoles = async () => {
    isLoading.value = true;
    try {
        // Mock data
        await new Promise(r => setTimeout(r, 600));
        roles.value = [
            { id: 1, name: 'Owner', permissions: ['all'], color: 'purple' },
            { id: 2, name: 'Admin', permissions: ['manage_members', 'manage_content'], color: 'blue' },
            { id: 3, name: 'Moderator', permissions: ['delete_posts', 'ban_users'], color: 'green' },
            { id: 4, name: 'Teacher', permissions: ['create_course', 'grade_work'], color: 'orange' },
        ];
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    fetchRoles();
});
</script>

<template>
    <AcademyLayout :academy="props.academy" :isAcademyAdmin="props.isAcademyAdmin">
        <template #academyContent>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mt-4">
                
                <!-- Breadcrumb / Header -->
                <div class="flex items-center mb-6 border-b border-gray-200 dark:border-gray-700 pb-4 justify-between">
                    <div class="flex items-center">
                        <NuxtLink :to="`/Learn/Academy/${props.academy?.data?.name}/Settings`" class="mr-3 text-gray-500 hover:text-gray-700">
                            <Icon icon="lucide:arrow-left" class="w-6 h-6" />
                        </NuxtLink>
                        <div>
                            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-200">บทบาทและสิทธิ์ (Roles)</h2>
                            <p class="text-sm text-gray-500">จัดการสิทธิ์การเข้าถึงและการใช้งาน</p>
                        </div>
                    </div>
                    <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                        สร้างบทบาทใหม่
                    </button>
                </div>

                <!-- Roles Grid -->
                <div v-if="isLoading" class="text-center py-8 text-gray-500">
                    <Icon icon="lucide:loader-2" class="w-8 h-8 animate-spin mx-auto mb-2" />
                    <p>กำลังโหลดข้อมูล...</p>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="role in roles" :key="role.id" class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-bold text-gray-900 dark:text-white">{{ role.name }}</h3>
                            <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <Icon icon="lucide:more-vertical" class="w-5 h-5" />
                            </button>
                        </div>
                        <div class="flex flex-wrap gap-2 mb-4">
                            <span v-for="perm in role.permissions" :key="perm" class="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded">
                                {{ perm }}
                            </span>
                        </div>
                        <div class="flex items-center text-xs text-gray-500">
                            <Icon icon="lucide:users" class="w-4 h-4 mr-1" />
                            <span>5 users assigned</span>
                        </div>
                    </div>
                </div>

            </div>
        </template>
    </AcademyLayout>
</template>
