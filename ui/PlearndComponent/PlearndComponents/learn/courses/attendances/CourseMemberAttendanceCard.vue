<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage } from "@inertiajs/vue3";
import { Icon } from '@iconify/vue';
import { useAttendanceStore } from '@/stores/attendance.js';
import AuthMemberAttendanceDetail  from './AuthMemberAttendanceDetail.vue';

const props = defineProps({
    courseMemberOfAuth: {
        type: Object,
        required: true,
    },
});

const attendanceStore = useAttendanceStore();
const page = usePage();

// Pagination and loading states
const currentPage = ref(1);
const isLoading = ref(false);
const isLoadingMore = ref(false);
const attendanceError = ref(null);

// Get course and group information
const course = computed(() => page.props.course);
const groupId = computed(() => props.courseMemberOfAuth?.group_id);

// Computed properties using store
const groupAttendances = computed(() => {
    if (!groupId.value) return [];
    return attendanceStore.getGroupAttendances(groupId.value) || [];
});

const paginatedAttendances = computed(() => {
    if (!groupAttendances.value.length) return [];
    // เรียงลำดับจากวันที่ล่าสุดก่อน
    return [...groupAttendances.value].sort((a, b) => {
        return new Date(b.date) - new Date(a.date);
    });
});

const totalCount = computed(() => {
    return paginatedAttendances.value.length;
});

const displayedCount = computed(() => {
    return paginatedAttendances.value.length;
});

const canLoadMore = computed(() => {
    return displayedCount.value < totalCount.value;
});

const isLoadingAttendances = computed(() => {
    return groupId.value ? attendanceStore.isLoading(`group_${groupId.value}`) : false;
});

const attendanceErrorFromStore = computed(() => {
    return groupId.value ? attendanceStore.getError(`group_${groupId.value}`) : null;
});

// Methods
const loadAttendances = async () => {
    if (!groupId.value || !course.value) return;
    
    try {
        await attendanceStore.fetchGroupAttendances(
            course.value.data.id,
            groupId.value,
            page.props.isCourseAdmin
        );
    } catch (error) {
        console.error('Failed to load attendances:', error);
    }
};

const refreshAttendances = async () => {
    if (!groupId.value || !course.value) return;
    
    try {
        await attendanceStore.fetchGroupAttendances(
            course.value.data.id,
            groupId.value,
            page.props.isCourseAdmin,
            true // force refresh
        );
    } catch (error) {
        console.error('Failed to refresh attendances:', error);
    }
};

const loadMoreAttendances = async () => {
    if (isLoadingMore.value || !canLoadMore.value) return;
    
    isLoadingMore.value = true;
    
    try {
        // This would typically trigger a parent component method or emit an event
        // For now, we'll just simulate loading more
        await new Promise(resolve => setTimeout(resolve, 1000));
        currentPage.value++;
    } catch (error) {
        attendanceError.value = 'Failed to load more attendances';
    } finally {
        isLoadingMore.value = false;
    }
};

// Load attendances when component is mounted
onMounted(async () => {
    await loadAttendances();
});

</script>

<template>
    <!-- Enhanced Loading State -->
    <div v-if="isLoadingAttendances && currentPage === 1" class="flex justify-center items-center py-20">
        <div class="text-center">
            <div class="relative">
                <div class="w-20 h-20 border-4 border-violet-200 border-t-violet-600 rounded-full animate-spin"></div>
                <div class="absolute inset-0 flex items-center justify-center">
                    <Icon icon="heroicons:academic-cap" class="h-10 w-10 text-violet-600 animate-pulse" />
                </div>
                <!-- Add rotating ring effect -->
                <div class="absolute inset-0 rounded-full border-2 border-violet-300 animate-ping opacity-20"></div>
            </div>
            <p class="mt-8 text-xl font-bold text-gray-700 animate-pulse">กำลังโหลดข้อมูลการเข้าเรียน...</p>
            <p class="mt-2 text-sm text-gray-500">โปรดรอสักครู่</p>
        </div>
    </div>

    <!-- Enhanced Error State -->
    <div v-if="attendanceErrorFromStore && !paginatedAttendances.length" class="bg-gradient-to-r from-red-50 via-pink-50 to-rose-50 border-l-4 border-red-500 rounded-xl p-8 mb-6 shadow-lg">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0 w-14 h-14 bg-red-100 rounded-full flex items-center justify-center animate-pulse">
                    <Icon icon="heroicons:exclamation-triangle" class="h-8 w-8 text-red-600" />
                </div>
                <div>
                    <p class="text-xl font-bold text-red-800">เกิดข้อผิดพลาด</p>
                    <p class="text-red-600 mt-1">{{ attendanceErrorFromStore }}</p>
                </div>
            </div>
            <button @click="refreshAttendances"
                class="px-6 py-3 bg-gradient-to-r from-red-600 to-pink-600 text-white rounded-xl hover:from-red-700 hover:to-pink-700 transition-all duration-300 text-sm font-bold flex items-center gap-2 shadow-md hover:shadow-lg transform hover:scale-105">
                <Icon icon="heroicons:arrow-path" class="h-5 w-5" />
                ลองใหม่
            </button>
        </div>
    </div>

    <!-- Enhanced Empty State -->
    <div v-if="!isLoadingAttendances && !attendanceErrorFromStore && !paginatedAttendances.length" class="text-center py-20">
        <div class="inline-flex items-center justify-center w-32 h-32 bg-gradient-to-br from-gray-100 to-slate-100 rounded-full mb-8 shadow-lg">
            <Icon icon="heroicons:calendar-days" class="h-16 w-16 text-gray-400" />
        </div>
        <h3 class="text-2xl font-bold text-gray-700 mb-3">ไม่มีข้อมูลการเข้าเรียน</h3>
        <p class="text-gray-500 text-lg">ยังไม่มีการสร้างรายการเช็คชื่อในขณะนี้</p>
    </div>

    <!-- Enhanced Attendance Table -->
    <div v-if="paginatedAttendances.length > 0" class="space-y-8">
        <!-- Enhanced Summary Header -->
        <div class="bg-gradient-to-r from-violet-50 via-purple-50 to-indigo-50 border border-violet-200 rounded-2xl p-8 shadow-xl">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-violet-600 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                        <Icon icon="heroicons:calendar-days" class="h-8 w-8 text-white" />
                    </div>
                    <div>
                        <p class="text-sm font-bold text-violet-900 uppercase tracking-wide">ข้อมูลการเข้าเรียน</p>
                        <p class="text-2xl font-black text-violet-700">
                            แสดง {{ displayedCount }} จาก {{ totalCount }} รายการ
                        </p>
                    </div>
                </div>
                <div v-if="canLoadMore" class="flex items-center gap-3 text-sm font-bold text-violet-600 bg-gradient-to-r from-violet-100 to-purple-100 px-4 py-2 rounded-full border border-violet-200 shadow-md">
                    <Icon icon="heroicons:chevron-double-down" class="h-5 w-5 animate-bounce" />
                    <span>มีข้อมูลเพิ่มเติม</span>
                </div>
            </div>
        </div>

        <!-- Enhanced Table -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <!-- Enhanced Table Header -->
                    <thead class="bg-gradient-to-r from-gray-50 via-gray-100 to-slate-100 border-b-2 border-gray-200">
                        <tr>
                            <th class="px-6 py-5 text-left">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-violet-100 to-purple-100 rounded-xl flex items-center justify-center shadow-sm">
                                        <Icon icon="heroicons:hashtag" class="h-5 w-5 text-violet-600" />
                                    </div>
                                    <span class="text-sm font-black text-gray-700 uppercase tracking-wide">ลำดับ</span>
                                </div>
                            </th>
                            <th class="px-6 py-5 text-left">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-100 to-indigo-100 rounded-xl flex items-center justify-center shadow-sm">
                                        <Icon icon="heroicons:calendar" class="h-5 w-5 text-blue-600" />
                                    </div>
                                    <span class="text-sm font-black text-gray-700 uppercase tracking-wide">วันที่</span>
                                </div>
                            </th>
                            <th class="px-6 py-5 text-left">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-green-100 to-emerald-100 rounded-xl flex items-center justify-center shadow-sm">
                                        <Icon icon="heroicons:clock" class="h-5 w-5 text-green-600" />
                                    </div>
                                    <span class="text-sm font-black text-gray-700 uppercase tracking-wide">เวลาเริ่ม</span>
                                </div>
                            </th>
                            <th class="px-6 py-5 text-left">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-red-100 to-pink-100 rounded-xl flex items-center justify-center shadow-sm">
                                        <Icon icon="heroicons:clock" class="h-5 w-5 text-red-600" />
                                    </div>
                                    <span class="text-sm font-black text-gray-700 uppercase tracking-wide">เวลาสิ้นสุด</span>
                                </div>
                            </th>
                            <th class="px-6 py-5 text-left">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-100 to-blue-100 rounded-xl flex items-center justify-center shadow-sm">
                                        <Icon icon="heroicons:document-text" class="h-5 w-5 text-indigo-600" />
                                    </div>
                                    <span class="text-sm font-black text-gray-700 uppercase tracking-wide">คำอธิบาย</span>
                                </div>
                            </th>
                            <th class="px-6 py-5 text-center">
                                <div class="flex items-center justify-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-purple-100 to-pink-100 rounded-xl flex items-center justify-center shadow-sm">
                                        <Icon icon="heroicons:check-badge" class="h-5 w-5 text-purple-600" />
                                    </div>
                                    <span class="text-sm font-black text-gray-700 uppercase tracking-wide">สถานะการเข้าเรียน</span>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    
                    <!-- Enhanced Table Body -->
                    <tbody class="divide-y divide-gray-100">
                        <tr
                            v-for="(attendance, aIndx) in paginatedAttendances"
                            :key="attendance.id"
                            class="hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 transition-all duration-300 transform hover:scale-[1.01]"
                        >
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-violet-100 to-purple-100 rounded-xl flex items-center justify-center shadow-sm">
                                        <span class="text-violet-600 font-black text-lg">{{ aIndx + 1 }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="space-y-2">
                                    <p class="text-sm font-bold text-gray-900">
                                        {{ attendance.date ? new Date(attendance.date).toLocaleDateString('th-TH') : '-' }}
                                    </p>
                                    <p class="text-sm text-gray-600 font-medium">
                                        {{ attendance.date ? new Date(attendance.date).toLocaleDateString('th-TH', { weekday: 'long' }) : '-' }}
                                    </p>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-gradient-to-br from-green-100 to-emerald-100 rounded-lg flex items-center justify-center">
                                        <Icon icon="heroicons:play-circle" class="h-5 w-5 text-green-600" />
                                    </div>
                                    <span class="text-sm font-bold text-gray-900">
                                        {{ new Date(attendance.start_at).toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' }) }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-gradient-to-br from-red-100 to-pink-100 rounded-lg flex items-center justify-center">
                                        <Icon icon="heroicons:stop-circle" class="h-5 w-5 text-red-600" />
                                    </div>
                                    <span class="text-sm font-bold text-gray-900">
                                        {{ new Date(attendance.finish_at).toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' }) }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <div v-if="attendance.description" class="max-w-xs">
                                    <p class="text-sm text-gray-700 font-medium line-clamp-2 bg-gray-50 rounded-lg p-2">{{ attendance.description }}</p>
                                </div>
                                <div v-else class="text-sm text-gray-400 font-medium">
                                    <span class="inline-flex items-center px-2 py-1 bg-gray-100 rounded-lg">-</span>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex justify-center">
                                    <AuthMemberAttendanceDetail
                                        :attendance="attendance"
                                        :aIndx="aIndx"
                                        :aDate="attendance.date"
                                        :startAt="attendance.start_at"
                                        :finishAt="attendance.finish_at"
                                        :lateTime="attendance.late_time"
                                        :aDescription="attendance.description"
                                        :authMemberAttendance="attendance.authAttendanceStatus"
                                    />
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Enhanced Load More Button -->
    <div v-if="canLoadMore && paginatedAttendances.length > 0" class="flex justify-center mt-10">
        <button
            @click="loadMoreAttendances"
            :disabled="isLoadingMore"
            class="group px-10 py-4 bg-gradient-to-r from-violet-600 via-purple-600 to-indigo-600 text-white rounded-2xl hover:from-violet-700 hover:via-purple-700 hover:to-indigo-700 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-3 shadow-xl hover:shadow-2xl transform hover:-translate-y-1">
            <Icon v-if="isLoadingMore" icon="svg-spinners:270-ring-with-bg" class="h-6 w-6" />
            <Icon v-else icon="heroicons:arrow-down-circle" class="h-6 w-6 group-hover:animate-bounce" />
            <span class="font-black text-lg">{{ isLoadingMore ? 'กำลังโหลด...' : 'โหลดเพิ่มเติม' }}</span>
        </button>
    </div>

    <!-- Enhanced End of Data Indicator -->
    <div v-if="!canLoadMore && paginatedAttendances.length > 0" class="text-center mt-10">
        <div class="inline-flex items-center gap-4 px-8 py-4 bg-gradient-to-r from-green-50 via-emerald-50 to-teal-50 border-2 border-green-200 rounded-2xl shadow-lg">
            <Icon icon="heroicons:check-circle" class="h-8 w-8 text-green-600" />
            <span class="text-lg font-black text-green-700">แสดงข้อมูลทั้งหมดแล้ว</span>
        </div>
    </div>
</template>
