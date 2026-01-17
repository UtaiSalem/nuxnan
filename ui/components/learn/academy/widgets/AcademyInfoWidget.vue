<script setup>
import { computed } from 'vue';
import { Icon } from '@iconify/vue';

const props = defineProps({
    academy: {
        type: Object,
        required: true
    }
});

const academyData = computed(() => props.academy?.data || props.academy || {});

const stats = computed(() => [
    { label: 'สมาชิก', value: academyData.value.total_students || 0, icon: 'heroicons:users' },
    { label: 'รายวิชา', value: academyData.value.courses_offered || 0, icon: 'heroicons:academic-cap' },
    // You can add more stats here if available in the academy data
]);
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="p-5 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50">
            <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <Icon icon="heroicons:information-circle" class="w-5 h-5 text-indigo-500" />
                ข้อมูลโรงเรียน
            </h3>
        </div>
        
        <div class="p-5 space-y-4">
            <div>
                <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">เกี่ยวกับโรงเรียน</h4>
                <p class="mt-2 text-gray-700 dark:text-gray-300 text-sm leading-relaxed">
                    {{ academyData.description || 'ไม่มีคำอธิบายเพิ่มเติม' }}
                </p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div v-for="stat in stats" :key="stat.label" class="bg-indigo-50 dark:bg-indigo-900/20 p-3 rounded-xl border border-indigo-100 dark:border-indigo-800/30">
                    <div class="flex items-center gap-2 mb-1">
                        <Icon :icon="stat.icon" class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                        <span class="text-xs font-medium text-indigo-700 dark:text-indigo-300">{{ stat.label }}</span>
                    </div>
                    <div class="text-xl font-bold text-indigo-900 dark:text-indigo-100">{{ stat.value }}</div>
                </div>
            </div>

            <div v-if="academyData.address" class="pt-2">
                <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">ที่ตั้งพระเอก</h4>
                <div class="flex gap-2 text-sm text-gray-600 dark:text-gray-400 leading-snug">
                    <Icon icon="heroicons:map-pin" class="w-4 h-4 shrink-0 text-gray-400 mt-0.5" />
                    <span>{{ academyData.address }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
