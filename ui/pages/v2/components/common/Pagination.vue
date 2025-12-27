<script setup>
import { computed } from 'vue';

const props = defineProps({
    pagination: {
        type: Object,
        required: true,
        validator: (value) => {
            return ['page', 'perPage', 'total'].every(key => key in value);
        },
    },
});

const emit = defineEmits([
    'page-change',
    'per-page-change',
]);

const currentPage = computed(() => props.pagination.page || 1);
const perPage = computed(() => props.pagination.perPage || 10);
const total = computed(() => props.pagination.total || 0);

const totalPages = computed(() => Math.ceil(total.value / perPage.value));
const from = computed(() => (currentPage.value - 1) * perPage.value + 1);
const to = computed(() => Math.min(currentPage.value * perPage.value, total.value));

const hasPrevious = computed(() => currentPage.value > 1);
const hasNext = computed(() => currentPage.value < totalPages.value);

const visiblePages = computed(() => {
    const pages = [];
    const maxVisible = 5;
    
    if (totalPages.value <= maxVisible) {
        for (let i = 1; i <= totalPages.value; i++) {
            pages.push(i);
        }
    } else {
        const half = Math.floor(maxVisible / 2);
        let start = Math.max(1, currentPage.value - half);
        let end = Math.min(totalPages.value, start + maxVisible - 1);
        
        if (end - start < maxVisible - 1) {
            start = Math.max(1, end - maxVisible + 1);
        }
        
        for (let i = start; i <= end; i++) {
            pages.push(i);
        }
    }
    
    return pages;
});

const handlePageChange = (page) => {
    if (page >= 1 && page <= totalPages.value && page !== currentPage.value) {
        emit('page-change', page);
    }
};

const handlePerPageChange = (newPerPage) => {
    emit('per-page-change', parseInt(newPerPage));
};

const goToFirst = () => handlePageChange(1);
const goToPrevious = () => handlePageChange(currentPage.value - 1);
const goToNext = () => handlePageChange(currentPage.value + 1);
const goToLast = () => handlePageChange(totalPages.value);
</script>

<template>
    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
        <!-- Mobile results info -->
        <div class="flex-1 flex justify-between sm:hidden">
            <button
                v-if="hasPrevious"
                @click="goToPrevious"
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
            >
                ก่อนหน้า
            </button>
            <button
                v-if="hasNext"
                @click="goToNext"
                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
            >
                ถัดไป
            </button>
        </div>

        <!-- Desktop pagination -->
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <!-- Results info -->
            <div>
                <p class="text-sm text-gray-700">
                    แสดง
                    <span class="font-medium">{{ from }}</span>
                    ถึง
                    <span class="font-medium">{{ to }}</span>
                    จากทั้งหมด
                    <span class="font-medium">{{ total }}</span>
                    รายการ
                </p>
            </div>

            <!-- Per page selector -->
            <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-700">แสดงต่อหน้า:</span>
                <select
                    :value="perPage"
                    @change="handlePerPageChange($event.target.value)"
                    class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                >
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>

            <!-- Page navigation -->
            <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <!-- First page -->
                    <button
                        @click="goToFirst"
                        :disabled="!hasPrevious"
                        class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span class="sr-only">First</span>
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" />
                            <path d="M8.707 5.293a1 1 0 010 1.414L5.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" />
                        </svg>
                    </button>

                    <!-- Previous page -->
                    <button
                        @click="goToPrevious"
                        :disabled="!hasPrevious"
                        class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span class="sr-only">Previous</span>
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <!-- Page numbers -->
                    <template v-for="page in visiblePages" :key="page">
                        <button
                            v-if="page !== currentPage"
                            @click="handlePageChange(page)"
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            {{ page }}
                        </button>
                        <span
                            v-else
                            class="relative inline-flex items-center px-4 py-2 border border-blue-500 bg-blue-50 text-sm font-medium text-blue-600"
                        >
                            {{ page }}
                        </span>
                    </template>

                    <!-- Next page -->
                    <button
                        @click="goToNext"
                        :disabled="!hasNext"
                        class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span class="sr-only">Next</span>
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <!-- Last page -->
                    <button
                        @click="goToLast"
                        :disabled="!hasNext"
                        class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span class="sr-only">Last</span>
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" />
                            <path d="M3.293 14.707a1 1 0 010-1.414L6.586 10 3.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" />
                        </svg>
                    </button>
                </nav>
            </div>
        </div>
    </div>
</template>

