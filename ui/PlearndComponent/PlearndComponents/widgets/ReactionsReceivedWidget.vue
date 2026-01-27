<script setup>
import { ref, onMounted } from 'vue';
import { Icon } from '@iconify/vue';

const isLoading = ref(true);
const statistics = ref({
  likes: 0,
  loves: 0,
  dislikes: 0,
  happy: 0,
  total: 0,
});

const fetchReactionsStatistics = async () => {
  try {
    isLoading.value = true;
    const response = await axios.get('/api/user/reactions-received');
    if (response.data.success) {
      statistics.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to fetch reactions statistics:', error);
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  fetchReactionsStatistics();
});

// Helper function to format large numbers
const formatNumber = (num) => {
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + 'M';
  } else if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K';
  }
  return num.toLocaleString();
};
</script>

<template>
    <div class="bg-white rounded-xl shadow-md p-4">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Reactions Received</h3>
            <button class="text-gray-400 hover:text-gray-600">
                <Icon icon="heroicons:ellipsis-horizontal" class="w-5 h-5" />
            </button>
        </div>

        <!-- Loading Skeleton -->
        <div v-if="isLoading" class="grid grid-cols-2 gap-4">
            <div v-for="i in 4" :key="i" class="flex flex-col items-center animate-pulse">
                <div class="w-16 h-16 bg-gray-200 rounded-full mb-2"></div>
                <div class="h-6 w-16 bg-gray-200 rounded mb-1"></div>
                <div class="h-4 w-12 bg-gray-200 rounded"></div>
            </div>
        </div>

        <!-- Statistics Content -->
        <div v-else class="grid grid-cols-2 gap-4">
            <!-- Likes -->
            <div class="flex flex-col items-center p-3 rounded-lg hover:bg-blue-50 transition-colors cursor-pointer">
                <div class="w-14 h-14 flex items-center justify-center bg-blue-100 rounded-full mb-2">
                    <Icon icon="heroicons-solid:thumb-up" class="w-8 h-8 text-blue-500" />
                </div>
                <span class="text-xl font-bold text-gray-800">{{ formatNumber(statistics.likes) }}</span>
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Likes</span>
            </div>

            <!-- Loves -->
            <div class="flex flex-col items-center p-3 rounded-lg hover:bg-red-50 transition-colors cursor-pointer">
                <div class="w-14 h-14 flex items-center justify-center bg-red-100 rounded-full mb-2">
                    <Icon icon="heroicons-solid:heart" class="w-8 h-8 text-red-500" />
                </div>
                <span class="text-xl font-bold text-gray-800">{{ formatNumber(statistics.loves) }}</span>
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Loves</span>
            </div>

            <!-- Dislikes -->
            <div class="flex flex-col items-center p-3 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                <div class="w-14 h-14 flex items-center justify-center bg-gray-200 rounded-full mb-2">
                    <Icon icon="heroicons-solid:thumb-down" class="w-8 h-8 text-gray-600" />
                </div>
                <span class="text-xl font-bold text-gray-800">{{ formatNumber(statistics.dislikes) }}</span>
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Dislikes</span>
            </div>

            <!-- Happy -->
            <div class="flex flex-col items-center p-3 rounded-lg hover:bg-yellow-50 transition-colors cursor-pointer">
                <div class="w-14 h-14 flex items-center justify-center bg-yellow-100 rounded-full mb-2">
                    <Icon icon="heroicons-solid:face-smile" class="w-8 h-8 text-yellow-500" />
                </div>
                <span class="text-xl font-bold text-gray-800">{{ formatNumber(statistics.happy) }}</span>
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Happy</span>
            </div>
        </div>

        <!-- Total Reactions Footer -->
        <div class="mt-4 pt-3 border-t border-gray-100">
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500">Total Reactions</span>
                <span class="font-semibold text-gray-800">{{ formatNumber(statistics.total) }}</span>
            </div>
        </div>
    </div>
</template>
