<script setup>
import { ref, onMounted } from 'vue';
import { Icon } from '@iconify/vue';
import AcademyPostViewer from '@/components/learn/academy/posts/AcademyPostViewer.vue';

const props = defineProps({
    academyId: {
        type: [Number, String],
        required: true
    }
});

const activities = ref([]);
const isLoading = ref(true);

const fetchActivities = async () => {
    try {
        isLoading.value = true;
        const response = await axios.get(`/api/academies/${props.academyId}/activities`);
        if (response.data.success) {
            // Take only first 3 activities for the widget
            activities.value = response.data.activities?.data?.slice(0, 3) || [];
        }
    } catch (error) {
        console.error("Error fetching academy activities:", error);
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    fetchActivities();
});
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
            <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2 text-sm">
                <Icon icon="heroicons:bolt" class="w-4 h-4 text-amber-500" />
                ความเคลื่อนไหวล่าสุด
            </h3>
            <Link :href="`/academies/${props.academyId}`" class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-medium">
                ดูทั้งหมด
            </Link>
        </div>
        
        <div class="p-3">
            <div v-if="isLoading" class="flex justify-center py-6">
                <Icon icon="svg-spinners:3-dots-scale" class="w-8 h-8 text-indigo-500" />
            </div>
            
            <div v-else-if="activities.length" class="space-y-4">
                <!-- We use a very simplified list for the widget instead of the full PostViewer -->
                <div v-for="activity in activities" :key="activity.id" class="flex gap-3 pb-3 border-b border-gray-50 dark:border-gray-700 last:border-0 last:pb-0">
                    <img :src="activity.user?.profile_photo_url || '/images/default-avatar.png'" class="w-8 h-8 rounded-full border border-gray-100 transition-transform hover:scale-105" />
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-gray-900 dark:text-gray-100 line-clamp-2 leading-snug">
                            <span class="font-bold">{{ activity.user?.name }}</span> 
                            {{ activity.activity_type_label || 'ได้โพสต์ข้อความใหม่' }}
                        </p>
                        <p class="text-[10px] text-gray-400 mt-1 flex items-center gap-1">
                             <Icon icon="heroicons:clock" class="w-3 h-3" />
                             {{ activity.created_at_for_humans }}
                        </p>
                    </div>
                </div>
            </div>
            
            <div v-else class="py-10 text-center">
                <Icon icon="heroicons:chat-bubble-left" class="w-8 h-8 text-gray-300 mx-auto mb-2" />
                <p class="text-xs text-gray-500 dark:text-gray-400">ไม่มีความเคลื่อนไหว</p>
            </div>
        </div>
    </div>
</template>
