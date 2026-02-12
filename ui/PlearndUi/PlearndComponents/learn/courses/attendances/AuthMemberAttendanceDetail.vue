<script setup>
import { ref, reactive, computed } from 'vue';
import { usePage } from "@inertiajs/vue3";
import { Icon } from '@iconify/vue';

const props = defineProps({
    attendance: Object,
    aIndx: Number,
    aDate: String,
    startAt: String,
    finishAt: String,
    lateTime: Number,
    aDescription: String,
    authMemberAttendance: Object,
});

const refAuthMemberAttendanceStatus = ref(props.authMemberAttendance ? props.authMemberAttendance.status : null);
const isLoadingMemberAttendanceDetail = ref(false);

// Status configuration object with enhanced styling
const STATUS_CONFIG = {
    0: {
        text: 'ขาด',
        icon: 'heroicons:x-circle',
        badgeClass: 'bg-gradient-to-r from-red-100 to-pink-100 text-red-700 border border-red-200 shadow-sm',
        iconClass: 'text-red-600'
    },
    1: {
        text: 'มา',
        icon: 'heroicons:check-circle',
        badgeClass: 'bg-gradient-to-r from-green-100 to-emerald-100 text-green-700 border border-green-200 shadow-sm',
        iconClass: 'text-green-600'
    },
    2: {
        text: 'สาย',
        icon: 'heroicons:clock',
        badgeClass: 'bg-gradient-to-r from-yellow-100 to-amber-100 text-yellow-700 border border-yellow-200 shadow-sm',
        iconClass: 'text-yellow-600'
    },
    3: {
        text: 'ลา',
        icon: 'heroicons:document-text',
        badgeClass: 'bg-gradient-to-r from-blue-100 to-indigo-100 text-blue-700 border border-blue-200 shadow-sm',
        iconClass: 'text-blue-600'
    },
};

const form = reactive({
    course_attendance_id: props.attendance.id,
    course_member_id: usePage().props.courseMemberOfAuth.id,
    status: 1,
    comments: null,
});

// Computed properties
const isExpired = computed(() => {
    if (!props.finishAt) return false;
    const finishTime = new Date(props.finishAt);
    const currentTime = new Date();
    return currentTime > finishTime;
});

const hasStatus = computed(() => {
    return refAuthMemberAttendanceStatus.value !== null && refAuthMemberAttendanceStatus.value !== undefined;
});

const canRegister = computed(() => {
    return !isExpired.value && !hasStatus.value;
});

const isLate = computed(() => {
    if (!props.startAt || !props.lateTime) return false;
    const startTime = new Date(props.startAt);
    const currentTime = new Date();
    const lateThreshold = new Date(startTime.getTime() + (props.lateTime * 60 * 1000));
    return currentTime > lateThreshold;
});

const currentStatus = computed(() => {
    // ถ้าไม่มีสถานะ แปลว่าขาดเรียน (status = 0)
    if (!hasStatus.value) {
        return STATUS_CONFIG[0];
    }
    // ถ้ามีสถานะ ให้แสดงสถานะนั้น
    return STATUS_CONFIG[refAuthMemberAttendanceStatus.value] || STATUS_CONFIG[0];
});

const shouldShowStatus = computed(() => {
    // แสดง status badge เมื่อ: มีสถานะแล้ว หรือ เลยเวลาแล้ว (ถือว่าขาด)
    return hasStatus.value || isExpired.value;
});

const load = async () => {
    isLoadingMemberAttendanceDetail.value = true;
    try {
        const response = await axios.post(`/attendances/${props.attendance.id}/details`, {
            course_attendance_id: props.attendance.id,
            course_member_id: usePage().props.courseMemberOfAuth.id,
            status: form.status,
            comments: form.comments
        });
        
        if (response.data && response.data.success) {
            refAuthMemberAttendanceStatus.value = response.data.attendance_detail.status;
        }
    } catch (error) {
        console.error('Failed to submit attendance:', error);
    } finally {
        isLoadingMemberAttendanceDetail.value = false;
    }
};

</script>

<template>
    <!-- Enhanced Status Badge -->
    <div
        v-if="shouldShowStatus"
        class="relative group"
    >
        <div
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 hover:shadow-md hover:scale-105"
            :class="currentStatus.badgeClass"
        >
            <div class="relative">
                <Icon :icon="currentStatus.icon" class="h-5 w-5" :class="currentStatus.iconClass" />
                <div class="absolute inset-0 rounded-full opacity-0 group-hover:opacity-20 transition-opacity duration-300"
                     :class="[
                         currentStatus.iconClass === 'text-green-600' ? 'bg-green-400' :
                         currentStatus.iconClass === 'text-red-600' ? 'bg-red-400' :
                         currentStatus.iconClass === 'text-yellow-600' ? 'bg-yellow-400' :
                         'bg-blue-400'
                     ]">
                </div>
            </div>
            <span class="font-bold">{{ currentStatus.text }}</span>
            <!-- Add status indicator dot -->
            <div class="w-2 h-2 rounded-full animate-pulse"
                 :class="[
                     currentStatus.iconClass === 'text-green-600' ? 'bg-green-500' :
                     currentStatus.iconClass === 'text-red-600' ? 'bg-red-500' :
                     currentStatus.iconClass === 'text-yellow-600' ? 'bg-yellow-500' :
                     'bg-blue-500'
                 ]">
            </div>
        </div>
        <!-- Add subtle glow effect -->
        <div class="absolute inset-0 rounded-xl opacity-0 group-hover:opacity-10 transition-opacity duration-300 -z-10"
             :class="[
                 currentStatus.iconClass === 'text-green-600' ? 'bg-green-500' :
                 currentStatus.iconClass === 'text-red-600' ? 'bg-red-500' :
                 currentStatus.iconClass === 'text-yellow-600' ? 'bg-yellow-500' :
                 'bg-blue-500'
             ]">
        </div>
    </div>
    
    <!-- Enhanced Register Button -->
    <button
        v-else-if="canRegister"
        type="button"
        @click="load"
        :disabled="isLoadingMemberAttendanceDetail"
        class="relative w-full px-4 py-3 rounded-xl font-bold text-white shadow-lg transform transition-all duration-300 text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 flex items-center justify-center gap-2 group overflow-hidden"
        :class="[
            isLate
                ? 'bg-gradient-to-r from-yellow-500 to-amber-500 hover:from-yellow-600 hover:to-amber-600 focus:ring-yellow-500 hover:shadow-xl hover:scale-105'
                : 'bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 focus:ring-green-500 hover:shadow-xl hover:scale-105',
            isLoadingMemberAttendanceDetail ? 'cursor-not-allowed opacity-75' : ''
        ]"
    >
        <!-- Add animated background effect -->
        <div v-if="!isLoadingMemberAttendanceDetail" class="absolute inset-0 bg-white opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
        
        <!-- Button content with enhanced icon -->
        <div class="relative flex items-center gap-2">
            <Icon
                :icon="isLoadingMemberAttendanceDetail ? 'svg-spinners:270-ring-with-bg' : 'heroicons:check-circle'"
                class="h-5 w-5"
                :class="{
                    'group-hover:animate-bounce': !isLoadingMemberAttendanceDetail,
                    'animate-spin': isLoadingMemberAttendanceDetail
                }"
            />
            <span class="font-bold">
                {{ isLoadingMemberAttendanceDetail ? 'กำลังบันทึก...' : (isLate ? 'มาสาย' : 'มาเรียน') }}
            </span>
        </div>
        
        <!-- Add ripple effect on hover -->
        <div v-if="!isLoadingMemberAttendanceDetail" class="absolute inset-0 rounded-xl overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white to-transparent opacity-0 group-hover:opacity-20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-all duration-700"></div>
        </div>
    </button>
</template>
