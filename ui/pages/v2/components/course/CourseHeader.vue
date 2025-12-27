<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    courseId: {
        type: Number,
        required: true,
    },
    course: {
        type: Object,
        default: null,
    },
    title: {
        type: String,
        default: '',
    },
});

const page = usePage();

// Computed
const breadcrumbs = computed(() => {
    const crumbs = [
        { name: 'หน้าแรก', href: '/dashboard' },
        { name: 'รายวิชาของฉัน', href: '/courses' },
    ];

    if (props.course) {
        crumbs.push({ 
            name: props.course.name, 
            href: `/course/${props.courseId}` 
        });
    }

    if (props.title) {
        crumbs.push({ 
            name: props.title, 
            href: page.url 
        });
    }

    return crumbs;
});

const currentRouteName = computed(() => page.url);
</script>

<template>
    <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="px-4 py-4 sm:px-6 lg:px-8">
            <!-- Breadcrumbs -->
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2">
                    <li v-for="(crumb, index) in breadcrumbs" :key="index" class="flex items-center">
                        <a 
                            v-if="index < breadcrumbs.length - 1"
                            :href="crumb.href"
                            class="text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors"
                        >
                            {{ crumb.name }}
                        </a>
                        <span 
                            v-else
                            class="text-sm font-medium text-gray-900"
                        >
                            {{ crumb.name }}
                        </span>
                        
                        <!-- Separator -->
                        <svg 
                            v-if="index < breadcrumbs.length - 1"
                            class="flex-shrink-0 h-5 w-5 text-gray-300 ml-2" 
                            fill="currentColor" 
                            viewBox="0 0 20 20"
                        >
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10 10.414l2.293 2.293a1 1 0 001.414 1.414L11.414 10l2.293 2.293a1 1 0 001.414-1.414L10 8.586 7.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </li>
                </ol>
            </nav>

            <!-- Header Content -->
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <h1 class="text-2xl font-semibold text-gray-900">
                        {{ title || course?.name || 'รายวิชา' }}
                    </h1>
                    <span 
                        v-if="course?.code"
                        class="ml-3 px-3 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800"
                    >
                        {{ course.code }}
                    </span>
                </div>

                <!-- Actions -->
                <div class="flex items-center space-x-4">
                    <!-- Search -->
                    <div class="relative">
                        <input 
                            type="text"
                            placeholder="ค้นหา..."
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        >
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Notifications -->
                    <button class="p-1 rounded-full text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <span class="sr-only">View notifications</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.406-1.406A2.992 2.992 0 0118 15a2.993 2.993 0 01-2.828 1.406l-1.406 1.406H5a2 2 0 00-2 2v6a2 2 0 002 2h6l-4 4h6a2 2 0 002-2v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h6l-4 4h6a2 2 0 002-2v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h6L9 19l-4 4z" />
                        </svg>
                    </button>

                    <!-- Settings -->
                    <button class="p-1 rounded-full text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <span class="sr-only">Settings</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.024 1.024 0 01-.112-.017 3.35 0 1.176 2.924 1.756h.822c1.471 0 2.924.842 2.924 2.924 0 1.176-.42-2.175-1.756-2.924-1.756zm1.47 2.924h2.353l-2.353 2.353a1.024 1.024 0 01-.112.017 2.353 0 1.176-.42 2.175-1.756 2.924-1.756zm1.47 2.924h2.353l-2.353 2.353a1.024 1.024 0 01-.112.017 2.353 0 1.176-.42 2.175-1.756 2.924-1.756z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </header>
</template>

