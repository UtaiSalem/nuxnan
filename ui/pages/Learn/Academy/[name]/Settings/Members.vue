<script setup>
import { ref, defineProps, onMounted } from 'vue';
import AcademyLayout from '@/layouts/AcademyLayout.vue';
import { Icon } from '@iconify/vue';

const props = defineProps({
    academy: Object,
    isAcademyAdmin: Boolean,
});

const members = ref([]);
const isLoading = ref(false);

const fetchMembers = async () => {
    isLoading.value = true;
    try {
        // TODO: Replace with actual API call
        // const response = await axios.get(`/api/academies/${props.academy.data.id}/members`);
        // members.value = response.data;
        
        // Mock data
        await new Promise(r => setTimeout(r, 800));
        members.value = [
            { id: 1, name: 'Student A', role: 'Student', joined_at: '2023-01-01' },
            { id: 2, name: 'Teacher B', role: 'Teacher', joined_at: '2023-01-15' },
            { id: 3, name: 'Admin C', role: 'Admin', joined_at: '2022-12-01' },
        ];
    } catch (error) {
        console.error('Error fetching members:', error);
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    fetchMembers();
});

const removeMember = (id) => {
    if(confirm('ต้องการลบสมาชิกรายนี้หรือไม่?')) {
        members.value = members.value.filter(m => m.id !== id);
    }
};
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
                            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-200">จัดการสมาชิก (Members)</h2>
                            <p class="text-sm text-gray-500">อนุมัติคำขอ, จัดการรายชื่อสมาชิก</p>
                        </div>
                    </div>
                    <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                        เชิญสมาชิก
                    </button>
                </div>

                <!-- Content -->
                <div v-if="isLoading" class="text-center py-8 text-gray-500">
                    <Icon icon="lucide:loader-2" class="w-8 h-8 animate-spin mx-auto mb-2" />
                    <p>กำลังโหลดข้อมูล...</p>
                </div>

                <div v-else>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase text-gray-500 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3 rounded-l-lg">ชื่อ-นามสกุล</th>
                                    <th class="px-4 py-3">บทบาท</th>
                                    <th class="px-4 py-3">วันที่เข้าร่วม</th>
                                    <th class="px-4 py-3 rounded-r-lg text-right">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr v-for="member in members" :key="member.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-3 flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-600"></div>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ member.name }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            {{ member.role }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ member.joined_at }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <button 
                                            @click="removeMember(member.id)"
                                            class="text-red-500 hover:text-red-700 p-1 rounded-full hover:bg-red-50 dark:hover:bg-red-900/20"
                                        >
                                            <Icon icon="lucide:trash-2" class="w-4 h-4" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </template>
    </AcademyLayout>
</template>
