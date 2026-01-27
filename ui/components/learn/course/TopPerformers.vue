<script setup lang="ts">
import { computed, type PropType } from 'vue';
import { Icon } from '@iconify/vue';

const props = defineProps({
  members: {
    type: Array as PropType<any[]>,
    required: true,
    default: () => []
  }
});

const topPerformers = computed(() => {
  // Filter out invalid members if necessary, though we likely trust the passed list
  // Sort by achieved_score descending
  const sorted = [...props.members].sort((a, b) => {
    const scoreA = Number(a.achieved_score || 0);
    const scoreB = Number(b.achieved_score || 0);
    return scoreB - scoreA;
  });
  
  // Take top 5
  return sorted.slice(0, 5);
});

const getRankColor = (index: number) => {
  switch (index) {
    case 0: return 'bg-yellow-400 text-yellow-900 ring-yellow-400'; // Gold
    case 1: return 'bg-gray-300 text-gray-800 ring-gray-300'; // Silver
    case 2: return 'bg-orange-400 text-orange-900 ring-orange-400'; // Bronze
    default: return 'bg-gray-700 text-white ring-gray-600';
  }
};
</script>

<template>
  <div class="bg-slate-900 rounded-xl p-5 shadow-lg border border-slate-800 text-white">
    <div class="flex items-center gap-2 mb-6">
      <Icon icon="solar:cup-star-bold" class="w-6 h-6 text-yellow-400" />
      <h2 class="text-lg font-bold">Top Performers</h2>
    </div>

    <div class="flex flex-col gap-4">
      <div 
        v-for="(member, index) in topPerformers" 
        :key="member.id"
        class="flex items-center justify-between group"
      >
        <div class="flex items-center gap-3">
            <div class="relative">
                 <!-- Rank Badge -->
                <div 
                    class="absolute -top-1 -right-1 w-5 h-5 flex items-center justify-center rounded-full text-[10px] font-bold shadow-md z-10 border-2 border-slate-900"
                    :class="getRankColor(index)"
                >
                    {{ index + 1 }}
                </div>

                <!-- Avatar -->
                <img 
                    :src="member.avatar || member.user?.avatar || member.user?.profile_photo_url || '/images/default-avatar.png'" 
                    :alt="member.name"
                    class="w-10 h-10 rounded-full object-cover ring-2 ring-slate-800 group-hover:ring-slate-700 transition-all"
                >
            </div>
         
          <div class="flex flex-col">
            <span class="text-sm font-semibold truncate max-w-[120px] md:max-w-[150px]">
              {{ member.member_name || member.user?.name || member.student?.name || 'Unknown' }}
            </span>
            <span class="text-xs text-slate-400">
               F • {{ member.percentage || 0 }}%
            </span>
          </div>
        </div>

        <div class="text-green-500 font-bold font-mono text-lg">
          {{ member.achieved_score || 0 }}
        </div>
      </div>
      
      <!-- Empty State -->
      <div v-if="topPerformers.length === 0" class="text-center py-4 text-slate-500 text-sm">
        No ranking data yet
      </div>
    </div>
  </div>
</template>
