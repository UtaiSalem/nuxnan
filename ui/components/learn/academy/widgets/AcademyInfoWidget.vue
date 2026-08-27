<script setup>
import { ref, computed } from 'vue';
import { Icon } from '@iconify/vue';
import { useNuxtApp } from '#app';
// import { useToast } from 'vue-toastification'; // Assuming toast usage, or use alert for now if unsure

const { $api } = useNuxtApp();
// const toast = useToast();

const props = defineProps({
    academy: {
        type: Object,
        required: true
    }
});

const academyData = computed(() => props.academy?.data || props.academy || {});
const isLoading = ref(false);

const memberStatus = computed(() => academyData.value.memberStatus);

const stats = computed(() => [
    { label: 'สมาชิก', value: academyData.value.total_students || 0, icon: 'heroicons:users' },
    { label: 'รายวิชา', value: academyData.value.courses_offered || 0, icon: 'heroicons:academic-cap' },
]);

const handleJoinRequest = async () => {
    if (!confirm('คุณต้องการขอเข้าร่วมเป็นนักเรียนในโรงเรียนนี้ใช่หรือไม่?')) return;

    isLoading.value = true;
    try {
        const response = await $api.post(`/academies/${academyData.value.id}/members`);
        
        if (response.success) {
            // Update local state directly to reflect change immediately
            // Assuming response returns new status or we infer it
            // memberStatus is computed from props, so we technically should emit up or mutate prop (bad practice)
            // Ideally, we force a refresh or have academyData be a ref.
            // For now, let's alert and reload or emit.
            
            // Actually, we can't mutate props. 
            // Better pattern: emit 'update:academy' or similar. 
            // But for MVP quick fix as requested:
            alert('ส่งคำขอสำเร็จ');
            location.reload(); 
        }
    } catch (error) {
        console.error('Join error:', error);
        alert(error.response?.data?.msg || 'เกิดข้อผิดพลาดในการเข้าร่วม');
    } finally {
        isLoading.value = false;
    }
};

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

            <!-- Join Button Area -->
            <div class="pt-2 border-t border-gray-100 dark:border-gray-700">
                <div v-if="memberStatus == 1">
                    <button disabled class="min-h-[44px] sm:min-h-0 w-full py-2 px-4 bg-yellow-100 text-yellow-700 rounded-lg flex items-center justify-center gap-2 cursor-not-allowed font-medium">
                        <Icon icon="heroicons:clock" class="w-5 h-5" />
                        รอการอนุมัติ
                    </button>
                </div>
                <div v-else-if="memberStatus == 2">
                    <div class="w-full py-2 px-4 bg-green-100 text-green-700 rounded-lg flex items-center justify-center gap-2 font-medium">
                        <Icon icon="heroicons:check-circle" class="w-5 h-5" />
                        เป็นสมาชิกแล้ว
                    </div>
                </div>
                <div v-else>
                    <button 
                        @click="handleJoinRequest" 
                        :disabled="isLoading"
                        class="min-h-[44px] sm:min-h-0 w-full py-2 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg flex items-center justify-center gap-2 transition-colors shadow-sm font-medium"
                    >
                        <Icon v-if="isLoading" icon="svg-spinners:180-ring-with-bg" class="w-5 h-5" />
                        <Icon v-else icon="heroicons:user-plus" class="w-5 h-5" />
                        <span>ขอเข้าเป็นนักเรียน</span>
                    </button>
                </div>
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
                <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">ที่ตั้ง</h4>
                <div class="flex gap-2 text-sm text-gray-600 dark:text-gray-400 leading-snug">
                    <Icon icon="heroicons:map-pin" class="w-4 h-4 shrink-0 text-gray-400 mt-0.5" />
                    <span>{{ academyData.address }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
