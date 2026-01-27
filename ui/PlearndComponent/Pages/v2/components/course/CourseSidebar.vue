<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { useCourseStore } from '@/Pages/v2/store/modules/course/courseStore';
import { useGroupStore } from '@/Pages/v2/store/modules/group/groupStore';

const props = defineProps({
    courseId: {
        type: Number,
        required: true,
    },
    course: {
        type: Object,
        default: null,
    },
    isAdmin: {
        type: Boolean,
        default: false,
    },
    activeTab: {
        type: String,
        default: 'dashboard',
    },
});

const page = usePage();
const courseStore = useCourseStore();
const groupStore = useGroupStore();

// State
const isCollapsed = ref(false);
const isGroupsExpanded = ref(true);

// Computed
const groups = computed(() => groupStore.groupsArray);
const currentRouteName = computed(() => page.url);

// Navigation items
const navigationItems = computed(() => [
    {
        name: 'แดชบอร์ด',
        key: 'dashboard',
        icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        route: { name: 'Course.Dashboard', params: { courseId: props.courseId } },
    },
    {
        name: 'ประกาศ',
        key: 'announcements',
        icon: 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z',
        route: { name: 'Course.Announcements', params: { courseId: props.courseId } },
    },
    {
        name: 'เช็กชื่อ',
        key: 'attendance',
        icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
        route: { name: 'Course.Attendance', params: { courseId: props.courseId } },
    },
    {
        name: 'บทเรียน',
        key: 'lessons',
        icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
        route: { name: 'Course.Lessons', params: { courseId: props.courseId } },
    },
    {
        name: 'การบ้าน',
        key: 'assignments',
        icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        route: { name: 'Course.Assignments', params: { courseId: props.courseId } },
    },
    {
        name: 'ข้อสอบ',
        key: 'exams',
        icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
        route: { name: 'Course.Exams', params: { courseId: props.courseId } },
    },
    {
        name: 'กลุ่มเรียน',
        key: 'groups',
        icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
        route: { name: 'Course.Groups', params: { courseId: props.courseId } },
        badge: groups.value.length,
    },
    {
        name: 'สมาชิก',
        key: 'members',
        icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v-1a6 6 0 00-9 5.197m13 5a4 4 0 11-8 0 4 4 0 018 0z',
        route: { name: 'Course.Members', params: { courseId: props.courseId } },
    },
    {
        name: 'สรุปผล',
        key: 'summary',
        icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        route: { name: 'Course.Summary', params: { courseId: props.courseId } },
    },
    {
        name: 'ตั้งค่า',
        key: 'settings',
        icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
        route: { name: 'Course.Settings', params: { courseId: props.courseId } },
        adminOnly: true,
    },
]);

// Methods
const navigateTo = (item) => {
    // Build URL from route object
    let url = `/courses/${props.courseId}`;
    
    // Map route names to URL paths
    const routeMap = {
        'Course.Dashboard': '',
        'Course.Announcements': '/announcements',
        'Course.Attendance': '/attendances',
        'Course.Lessons': '/lessons',
        'Course.Assignments': '/assignments',
        'Course.Exams': '/quizzes',
        'Course.Groups': '/groups',
        'Course.Members': '/members',
        'Course.Summary': '/summary',
        'Course.Settings': '/settings',
    };
    
    if (item.route && routeMap[item.route.name]) {
        url += routeMap[item.route.name];
    }
    
    // Add query parameters if present
    if (item.route && item.route.query) {
        const queryString = new URLSearchParams(item.route.query).toString();
        url += `?${queryString}`;
    }
    
    router.visit(url);
};

const toggleSidebar = () => {
    isCollapsed.value = !isCollapsed.value;
};

const toggleGroups = () => {
    isGroupsExpanded.value = !isGroupsExpanded.value;
};

const isActive = (itemKey) => {
    return props.activeTab === itemKey || currentRouteName.value?.includes(itemKey);
};

// Load groups on mount
onMounted(async () => {
    if (props.courseId) {
        try {
            await groupStore.fetchGroups(props.courseId);
        } catch (error) {
            console.error('Failed to fetch groups:', error);
        }
    }
});
</script>

<template>
    <div class="h-full flex flex-col bg-white border-r border-gray-200 transition-all duration-300" :class="{ 'w-16': isCollapsed, 'w-64': !isCollapsed }">
        <!-- Header -->
        <div class="p-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div v-if="!isCollapsed" class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                        <span class="text-white font-semibold text-sm">
                            {{ course?.code?.substring(0, 2) || 'CS' }}
                        </span>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 truncate">
                            {{ course?.name || 'รายวิชา' }}
                        </h3>
                        <p class="text-xs text-gray-500">
                            {{ course?.code || 'COURSE001' }}
                        </p>
                    </div>
                </div>
                
                <button 
                    @click="toggleSidebar"
                    class="p-1 rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path v-if="!isCollapsed" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
            <!-- Main Navigation -->
            <div class="space-y-1">
                <template v-for="item in navigationItems" :key="item.key">
                    <!-- Skip admin-only items if not admin -->
                    <div v-if="!item.adminOnly || isAdmin">
                        <button
                            @click="navigateTo(item)"
                            class="w-full flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors"
                            :class="[
                                isActive(item.key)
                                    ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-700'
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                            ]"
                            :title="isCollapsed ? item.name : ''"
                        >
                            <svg class="flex-shrink-0 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                            </svg>
                            
                            <span v-if="!isCollapsed" class="ml-3 truncate">{{ item.name }}</span>
                            
                            <!-- Badge -->
                            <span 
                                v-if="!isCollapsed && item.badge" 
                                class="ml-auto inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
                            >
                                {{ item.badge }}
                            </span>
                        </button>
                    </div>
                </template>
            </div>

            <!-- Groups Section (only show if not collapsed) -->
            <div v-if="!isCollapsed && groups.length > 0" class="pt-4 mt-4 border-t border-gray-200">
                <button
                    @click="toggleGroups"
                    class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900"
                >
                    <span>กลุ่มเรียน</span>
                    <svg 
                        class="w-4 h-4 transition-transform" 
                        :class="{ 'rotate-90': isGroupsExpanded }"
                        fill="none" 
                        viewBox="0 0 24 24" 
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
                
                <div v-if="isGroupsExpanded" class="mt-2 space-y-1">
                    <button
                        v-for="group in groups.slice(0, 5)"
                        :key="group.id"
                        @click="navigateTo({
                            name: 'Course.Groups',
                            params: { courseId: props.courseId },
                            query: { groupId: group.id }
                        })"
                        class="w-full flex items-center px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 rounded-md"
                    >
                        <div class="w-2 h-2 bg-green-400 rounded-full mr-3"></div>
                        <span class="truncate">{{ group.name }}</span>
                        <span class="ml-auto text-xs text-gray-400">
                            {{ group.members_count || 0 }}
                        </span>
                    </button>
                    
                    <button
                        v-if="groups.length > 5"
                        @click="navigateTo({
                            name: 'Course.Groups',
                            params: { courseId: props.courseId }
                        })"
                        class="w-full flex items-center px-3 py-2 text-sm text-gray-500 hover:text-gray-700 rounded-md"
                    >
                        <span class="ml-5">ดูทั้งหมด...</span>
                    </button>
                </div>
            </div>
        </nav>

        <!-- Footer -->
        <div class="p-4 border-t border-gray-200">
            <div v-if="!isCollapsed" class="text-xs text-gray-500 text-center">
                <p>ระบบจัดการรายวิชา v2.0</p>
            </div>
        </div>
    </div>
</template>