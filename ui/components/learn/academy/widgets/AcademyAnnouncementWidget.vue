<script setup>
import { ref, onMounted } from 'vue';
import { Icon } from '@iconify/vue';

const props = defineProps({
    academyId: {
        type: [Number, String],
        required: true
    }
});

// Since we don't have a specific Announcement model, we'll use pinned AcademyPosts or a dummy for now
// In a real scenario, this would fetch from a specific announcements endpoint
const announcements = ref([]);
const isLoading = ref(true);

const fetchAnnouncements = async () => {
    try {
        isLoading.value = true;
        // Mocking announcements or fetching a specific tag/pinned posts
        const response = await axios.get(`/api/academies/${props.academyId}/activities`);
        if (response.data.success) {
            // Filter posts that might be announcements (e.g., if we had a type or pinned status)
            // For now, just take the first 2 as 'latest news'
            announcements.value = response.data.activities?.data?.slice(0, 2) || [];
        }
    } catch (error) {
        console.error("Error fetching academy announcements:", error);
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    fetchAnnouncements();
});
</script>

<template>
    <div class="bg-gradient-to-br from-indigo-600 to-violet-700 rounded-2xl shadow-lg border border-indigo-500/20 overflow-hidden text-white">
        <div class="p-4 border-b border-white/10 flex justify-between items-center">
            <h3 class="font-bold flex items-center gap-2 text-sm uppercase tracking-wider">
                <Icon icon="heroicons:megaphone" class="w-4 h-4 text-amber-300" />
                ข่าวประชาสัมพันธ์
            </h3>
            <span class="flex h-2 w-2 rounded-full bg-red-400 animate-pulse"></span>
        </div>
        
        <div class="p-4">
            <div v-if="isLoading" class="flex justify-center py-4">
                <Icon icon="svg-spinners:ring-resize" class="w-6 h-6 text-white/50" />
            </div>
            
            <div v-else-if="announcements.length" class="space-y-4">
                <div v-for="announcement in announcements" :key="announcement.id" class="group cursor-pointer">
                    <p class="text-[11px] text-indigo-100 font-medium mb-1 flex items-center gap-1 opacity-80">
                         <Icon icon="heroicons:calendar-days" class="w-3 h-3" />
                         {{ announcement.created_at_for_humans }}
                    </p>
                    <p class="text-xs font-semibold leading-relaxed group-hover:text-amber-200 transition-colors line-clamp-2">
                        {{ announcement.activityable?.description || 'มีการอัปเดตข้อมูลใหม่ภายในโรงเรียน' }}
                    </p>
                </div>
            </div>
            
            <div v-else class="py-6 text-center">
                <Icon icon="heroicons:inbox" class="w-8 h-8 text-white/30 mx-auto mb-2" />
                <p class="text-xs text-white/60">ไม่มีข่าวสารในขณะนี้</p>
            </div>
            
            <button class="w-full mt-4 py-2 bg-white/10 hover:bg-white/20 border border-white/20 rounded-xl text-xs font-bold transition-all text-center">
                ดูประกาศทั้งหมด
            </button>
        </div>
    </div>
</template>
