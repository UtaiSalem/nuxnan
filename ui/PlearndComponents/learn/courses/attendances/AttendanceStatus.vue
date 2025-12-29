<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { usePage } from "@inertiajs/vue3";
import { Icon } from '@iconify/vue';
import { useAttendanceStore } from '@/stores/attendance';
import axios from 'axios';

// Click outside directive
const vClickOutside = {
    mounted(el, binding) {
        el.clickOutsideEvent = (event) => {
            if (!(el === event.target || el.contains(event.target))) {
                binding.value(event);
            }
        };
        document.addEventListener('click', el.clickOutsideEvent);
    },
    unmounted(el) {
        document.removeEventListener('click', el.clickOutsideEvent);
    }
};

const props = defineProps({
    status: {
        type: Number,
        default: null
    },
    attendance: {
        type: Object,
        required: true
    },
    memberId: {
        type: Number,
        required: true
    },
    isCourseAdmin: {
        type: Boolean,
        default: null  // null means check from usePage().props
    },
});

const attendanceStore = useAttendanceStore();
const refStatus = ref(props.status);
const isPending = ref(computeIsPending());
const isCheckingStatus = ref(false);
const refreshInterval = ref(null);
const hasError = ref(false);
const errorMessage = ref('');
const showStatusMenu = ref(false);
const isUpdatingStatus = ref(false);

// Helper function to compute pending status
function computeIsPending() {
    if (!props.attendance || !props.attendance.finish_at) return false;
    const now = new Date();
    const finishTime = new Date(props.attendance.finish_at);
    const statusNotSet = props.status === null || props.status === undefined;
    return now < finishTime && statusNotSet;
}

// Watch for props.status changes to update refStatus
watch(() => props.status, (newStatus) => {
    if (newStatus !== null && newStatus !== undefined) {
        refStatus.value = newStatus;
        isPending.value = false;
        hasError.value = false;
        errorMessage.value = '';
    }
}, { immediate: false });

// Watch for attendance changes to recompute pending status
watch(() => props.attendance, () => {
    isPending.value = computeIsPending();
}, { deep: true });

const attendanceStatus = computed(()=>{
    switch (refStatus.value) {
        case 0:
            return {
                icon:'heroicons:x-circle-20-solid',
                color:'text-white',
                label: 'ขาด',
                bgColor: 'bg-red-600 dark:bg-red-700',
                iconBg: 'bg-red-700/40',
                ringColor: 'ring-1 ring-red-700/20 dark:ring-red-500/30',
                glowColor: 'shadow-md'
            };
        case 1:
            return {
                icon:'heroicons:check-circle-20-solid',
                color:'text-white',
                label: 'มา',
                bgColor: 'bg-green-600 dark:bg-green-700',
                iconBg: 'bg-green-700/40',
                ringColor: 'ring-1 ring-green-700/20 dark:ring-green-500/30',
                glowColor: 'shadow-md'
            };
        case 2:
            return {
                icon:'heroicons:clock-20-solid',
                color:'text-white',
                label: 'สาย',
                bgColor: 'bg-orange-500 dark:bg-orange-600',
                iconBg: 'bg-orange-600/40',
                ringColor: 'ring-1 ring-orange-600/20 dark:ring-orange-500/30',
                glowColor: 'shadow-md'
            };
        case 3:
            return {
                icon:'heroicons:document-text-20-solid',
                color:'text-white',
                label: 'ลา',
                bgColor: 'bg-blue-600 dark:bg-blue-700',
                iconBg: 'bg-blue-700/40',
                ringColor: 'ring-1 ring-blue-700/20 dark:ring-blue-500/30',
                glowColor: 'shadow-md'
            };
        default:
            return {
                icon:'heroicons:x-circle-20-solid',
                color:'text-white',
                label: 'ขาด',
                bgColor: 'bg-red-600 dark:bg-red-700',
                iconBg: 'bg-red-700/40',
                ringColor: 'ring-1 ring-red-700/20 dark:ring-red-500/30',
                glowColor: 'shadow-md'
            };
    }
});

// Accessibility text for screen readers
const accessibilityText = computed(() => {
    if (hasError.value) {
        return `สถานะการเช็คชื่อ: ${errorMessage.value}`;
    }
    if (isPending.value) {
        return `รอการประมวลผลสถานะการเช็คชื่อ${isCheckingStatus.value ? ' กำลังตรวจสอบ' : ''}`;
    }
    return `สถานะการเช็คชื่อ: ${attendanceStatus.value.label}`;
});

// Check if the current user is a course admin
const isCourseAdmin = computed(() => {
    return usePage().props.isCourseAdmin || false;
});

// Function to check attendance status from backend
const checkAttendanceStatus = async () => {
    // Only check if attendance is still active and status is not set
    if (!isPending.value || isCheckingStatus.value) return;
    
    isCheckingStatus.value = true;
    hasError.value = false;
    errorMessage.value = '';
    
    try {
        // Use the attendance store to fetch member join status
        const status = await attendanceStore.fetchMemberJoinStatus(props.attendance.id, props.memberId);
        
        // Update status if it has changed
        if (status !== null && status !== undefined && status !== refStatus.value) {
            refStatus.value = status;
            // Update isPending based on new status
            isPending.value = false;
            
            // Also update the status in the store for consistency
            attendanceStore.updateMemberStatusInAttendance(props.attendance.id, props.memberId, status);
        }
    } catch (error) {
        // Handle errors with better user feedback
        if (error.response?.status === 404) {
            // Attendance or member data not found - stop polling to prevent spam
            console.warn('Attendance data not found - stopping auto-refresh');
            errorMessage.value = 'ไม่พบข้อมูลการเช็คชื่อ';
            hasError.value = true;
            isPending.value = false;
            cleanup();
        } else if (error.response?.status === 403) {
            // Permission denied - stop polling
            console.warn('Permission denied - stopping auto-refresh');
            errorMessage.value = 'ไม่มีสิทธิ์เข้าถึงข้อมูล';
            hasError.value = true;
            isPending.value = false;
            cleanup();
        } else if (error.code === 'NETWORK_ERROR' || !navigator.onLine) {
            // Network connectivity issues
            errorMessage.value = 'การเชื่อมต่อขัดข้อง';
            hasError.value = true;
            // Don't stop polling for network errors - might be temporary
        } else {
            // Other unexpected errors
            errorMessage.value = 'เกิดข้อผิดพลาดในการตรวจสอบสถานะ';
            hasError.value = true;
            console.error('Unexpected error in checkAttendanceStatus:', error);
        }
    } finally {
        isCheckingStatus.value = false;
    }
};

// Manual refresh function for course admins
const handlePendingClick = async () => {
    if (isCourseAdmin.value && !isCheckingStatus.value) {
        await checkAttendanceStatus();
    }
};

// Toggle status menu for course admin
const toggleStatusMenu = (event) => {
    if (isCourseAdmin.value) {
        event?.stopPropagation();
        showStatusMenu.value = !showStatusMenu.value;
    }
};

// Close status menu when clicking outside
const closeStatusMenu = () => {
    if (!isUpdatingStatus.value) {
        showStatusMenu.value = false;
    }
};

// Update attendance status by admin
const updateStatusByAdmin = async (newStatus, event) => {
    if (!isCourseAdmin.value || isUpdatingStatus.value) return;
    
    // ป้องกัน event bubbling
    event?.stopPropagation();
    
    // ถ้าเลือกสถานะเดิม ให้ปิด menu เลย
    if (refStatus.value === newStatus) {
        showStatusMenu.value = false;
        return;
    }
    
    // ถ้าเลือก status 0 (ขาด) ให้ลบ record ออก
    // status อื่นๆ (1=มา, 2=สาย, 3=ลา) จะสร้างหรืออัพเดท record
    
    isUpdatingStatus.value = true;
    hasError.value = false;
    errorMessage.value = '';
    
    try {
        // Call API to update status
        await axios.post(`/attendances/${props.attendance.id}/member/${props.memberId}/update-status`, {
            status: newStatus
        });
        
        // Update local status
        refStatus.value = newStatus;
        isPending.value = false;
        
        // Update store
        attendanceStore.updateMemberStatusInAttendance(props.attendance.id, props.memberId, newStatus);
        
        // ปิด menu หลังอัพเดทสำเร็จ
        await nextTick();
        showStatusMenu.value = false;
        
    } catch (error) {
        hasError.value = true;
        
        if (error.response?.status === 403) {
            errorMessage.value = 'ไม่มีสิทธิ์แก้ไขสถานะ';
        } else if (error.response?.status === 404) {
            errorMessage.value = 'ไม่พบข้อมูลการเข้าร่วม';
        } else {
            errorMessage.value = 'เกิดข้อผิดพลาดในการอัพเดทสถานะ';
        }
        
        console.error('Error updating status:', error);
        
        // ถ้า error ก็ปิด menu
        showStatusMenu.value = false;
    } finally {
        isUpdatingStatus.value = false;
    }
};

// Available status options for admin with dynamic colors
const statusOptions = [
    { value: 1, label: 'มา', icon: 'heroicons-outline:check-circle', color: 'text-white', bgColor: 'hover:bg-green-500/20 dark:hover:bg-green-600/20', activeBg: 'bg-green-500/30 dark:bg-green-600/30' },
    { value: 2, label: 'สาย', icon: 'heroicons-outline:clock', color: 'text-white', bgColor: 'hover:bg-orange-400/20 dark:hover:bg-orange-500/20', activeBg: 'bg-orange-400/30 dark:bg-orange-500/30' },
    { value: 3, label: 'ลา', icon: 'heroicons-outline:document-text', color: 'text-white', bgColor: 'hover:bg-blue-500/20 dark:hover:bg-blue-600/20', activeBg: 'bg-blue-500/30 dark:bg-blue-600/30' },
    { value: 0, label: 'ขาด', icon: 'heroicons-outline:x-circle', color: 'text-white', bgColor: 'hover:bg-red-500/20 dark:hover:bg-red-600/20', activeBg: 'bg-red-500/30 dark:bg-red-600/30' },
];

// Set up auto-refresh interval with exponential backoff for errors
const setupAutoRefresh = () => {
    // Only set up auto-refresh for pending attendances
    if (isPending.value) {
        // สุ่มเวลาระหว่าง 1,000 - 6,000 มิลลิวินาที (1-6 วินาที)
        // เพื่อป้องกันการโหลดข้อมูลพร้อมกันทั้งหมด
        let baseInterval = Math.floor(Math.random() * (6000 - 1000 + 1)) + 1000;
        
        // If we had errors, use exponential backoff
        if (hasError.value) {
            baseInterval = Math.min(baseInterval * 2, 30000); // Cap at 30 seconds
        }
        
        refreshInterval.value = setInterval(() => {
            checkAttendanceStatus();
        }, baseInterval);
        
        // console.log(`Auto-refresh ตั้งค่าทุก ${baseInterval / 1000} วินาที`);
    }
};

// Clean up interval when component is unmounted
const cleanup = () => {
    if (refreshInterval.value) {
        clearInterval(refreshInterval.value);
        refreshInterval.value = null;
    }
};

// Set up auto-refresh when component mounts
onMounted(() => {
    setupAutoRefresh();
});

// Clean up when component unmounts
onUnmounted(() => {
    cleanup();
});

// Watch for changes in isPending to start/stop auto-refresh
const stopWatcher = watch(isPending, (newValue) => {
    cleanup();
    if (newValue) {
        setupAutoRefresh();
    }
});

// Clean up watcher when component unmounts
onUnmounted(() => {
    if (stopWatcher) {
        stopWatcher();
    }
});

</script>

<template>
    <!-- Handle case when attendance data is not available -->
    <div v-if="!props.attendance || !props.memberId" class="flex items-center justify-center p-3">
        <div
            class="text-center bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-600"
            role="status"
            aria-label="ไม่มีข้อมูลการเช็คชื่อ"
        >
            <Icon icon="heroicons-outline:question-mark-circle" width="28" height="28" class="text-gray-400 dark:text-gray-500 mx-auto mb-2" aria-hidden="true" />
            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">ไม่มีข้อมูล</p>
        </div>
    </div>
    
    <!-- Normal attendance status display -->
    <div v-else-if="isPending" class="relative inline-flex items-center justify-center gap-1" :aria-label="accessibilityText">
        <!-- Pending status with enhanced animated clock icon -->
        <button
            v-if="isCourseAdmin"
            @click="handlePendingClick"
            :disabled="isCheckingStatus"
            class="relative group p-3 rounded-2xl transition-all duration-500 hover:shadow-2xl hover:scale-110 bg-gradient-to-br from-amber-400 via-orange-400 to-yellow-400 dark:from-amber-600 dark:via-orange-600 dark:to-yellow-600 border-2 border-amber-300/60 dark:border-amber-500/40 focus:outline-none focus:ring-4 focus:ring-amber-400/40 dark:focus:ring-amber-500/30 backdrop-blur-md shadow-lg animate-breathing"
            :title="isCheckingStatus ? 'กำลังตรวจสอบสถานะ...' : 'คลิกเพื่อตรวจสอบสถานะล่าสุด'"
            :aria-label="isCheckingStatus ? 'กำลังตรวจสอบสถานะ' : 'คลิกเพื่อตรวจสอบสถานะล่าสุด'"
            type="button"
        >
            <!-- Rotating glow effect -->
            <div class="absolute inset-0 rounded-2xl bg-gradient-to-br from-yellow-300/40 via-orange-300/40 to-amber-300/40 blur-md group-hover:blur-lg transition-all duration-500 animate-pulse"></div>
            
            <div class="relative">
                <Icon
                    icon="heroicons-outline:clock"
                    width="36"
                    height="36"
                    :class="[
                        'transition-all duration-500 drop-shadow-xl relative z-10',
                        isCheckingStatus ? 'text-white animate-spin scale-110' : 'text-white group-hover:text-yellow-50 group-hover:scale-125 group-hover:rotate-12'
                    ]"
                    aria-hidden="true"
                />
                <!-- Ping effect ring -->
                <div v-if="isCheckingStatus" class="absolute inset-0 rounded-full border-3 border-white/50 animate-ping"></div>
            </div>
            
            <!-- Status indicator dot with pulse -->
            <span v-if="!isCheckingStatus" class="absolute -top-1 -right-1 flex h-4 w-4">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-300 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-4 w-4 bg-amber-400 shadow-lg"></span>
            </span>
            
            <!-- Error indicator with enhanced styling -->
            <div v-if="hasError" class="absolute -bottom-1.5 -right-1.5 w-5 h-5 bg-gradient-to-br from-rose-500 to-red-600 rounded-full shadow-xl ring-2 ring-white dark:ring-gray-800 flex items-center justify-center">
                <Icon icon="heroicons-solid:exclamation" width="12" height="12" class="text-white" :title="errorMessage" />
            </div>
        </button>
        
        <!-- Non-admin users see enhanced clock icon -->
        <div
            v-else
            class="relative p-3 rounded-2xl bg-gradient-to-br from-sky-400 via-blue-400 to-indigo-400 dark:from-sky-600 dark:via-blue-600 dark:to-indigo-600 border-2 border-blue-300/60 dark:border-blue-500/40 backdrop-blur-md animate-breathing shadow-xl"
            :title="isCheckingStatus ? 'กำลังตรวจสอบสถานะ...' : 'รอการประมวลผล'"
            role="status"
            :aria-label="isCheckingStatus ? 'กำลังตรวจสอบสถานะ' : 'รอการประมวลผลสถานะการเช็คชื่อ'"
        >
            <!-- Glow effect -->
            <div class="absolute inset-0 rounded-2xl bg-gradient-to-br from-blue-300/40 via-indigo-300/40 to-sky-300/40 blur-md transition-all duration-500 animate-pulse"></div>
            
            <div class="relative">
                <Icon
                    icon="heroicons-outline:clock"
                    width="36"
                    height="36"
                    :class="[
                        'transition-all duration-500 drop-shadow-xl relative z-10',
                        isCheckingStatus ? 'text-white animate-pulse scale-110' : 'text-white'
                    ]"
                    aria-hidden="true"
                />
                <div v-if="isCheckingStatus" class="absolute inset-0 rounded-full border-3 border-white/50 animate-ping"></div>
            </div>
            
            <span v-if="!isCheckingStatus" class="absolute -top-1 -right-1 flex h-4 w-4">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-300 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-4 w-4 bg-blue-400 shadow-lg"></span>
            </span>
            
            <!-- Error indicator -->
            <div v-if="hasError" class="absolute -bottom-1.5 -right-1.5 w-5 h-5 bg-gradient-to-br from-rose-500 to-red-600 rounded-full shadow-xl ring-2 ring-white dark:ring-gray-800 flex items-center justify-center">
                <Icon icon="heroicons-solid:exclamation" width="12" height="12" class="text-white" :title="errorMessage" />
            </div>
        </div>
        
        <!-- Admin edit button for pending status with enhanced styling -->
        <button
            v-if="isCourseAdmin"
            @click.stop="toggleStatusMenu"
            :disabled="isUpdatingStatus"
            class="p-2.5 rounded-xl transition-all duration-300 hover:bg-gradient-to-br hover:from-blue-100 hover:to-indigo-100 dark:hover:from-blue-900/30 dark:hover:to-indigo-900/30 active:scale-95 focus:outline-none focus:ring-4 focus:ring-blue-400/30 dark:focus:ring-violet-500/20 backdrop-blur-sm border border-transparent hover:border-blue-200 dark:hover:border-blue-700/50 shadow-sm hover:shadow-lg"
            :class="{ 'opacity-50 cursor-not-allowed': isUpdatingStatus }"
            type="button"
            title="กำหนดสถานะการเข้าร่วม"
            aria-label="กำหนดสถานะการเข้าร่วม"
        >
            <Icon
                :icon="isUpdatingStatus ? 'heroicons-outline:arrow-path' : 'heroicons-outline:pencil'"
                width="22"
                height="22"
                :class="[
                    'transition-all duration-300',
                    isUpdatingStatus ? 'text-blue-600 dark:text-violet-400 animate-spin' : 'text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-violet-400 hover:scale-110'
                ]"
                aria-hidden="true"
            />
        </button>
        
        <!-- Status menu dropdown for pending (with status-colored background) -->
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0 scale-90 -translate-y-2"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-90 -translate-y-2"
        >
            <div
                v-if="showStatusMenu && isCourseAdmin"
                v-click-outside="closeStatusMenu"
                class="attendance-dropdown absolute top-full right-0 mt-3 w-56 rounded-2xl shadow-2xl z-50 overflow-hidden backdrop-blur-xl border-2 border-white/20 dark:border-gray-700/50 ring-1 ring-black/5"
                :class="refStatus !== null && refStatus !== undefined ? attendanceStatus.bgColor.replace('bg-', 'bg-').replace('dark:bg-', 'dark:bg-') + '/80' : 'bg-slate-600/80 dark:bg-slate-700/80'"
                role="menu"
                aria-orientation="vertical"
                aria-labelledby="status-menu-button"
                @click.stop
            >
                <div class="py-2">
                    <div class="px-4 py-3 text-xs font-black text-white/90 uppercase tracking-wider border-b-2 border-white/20 bg-black/10">
                        เลือกสถานะ
                    </div>
                    <button
                        v-for="option in statusOptions"
                        :key="option.value"
                        @click.stop="updateStatusByAdmin(option.value, $event)"
                        :disabled="refStatus === option.value || isUpdatingStatus"
                        class="w-full flex items-center gap-3 px-4 py-3.5 text-sm font-bold transition-all duration-300"
                        :class="[
                            option.color,
                            refStatus === option.value 
                                ? option.activeBg + ' cursor-default opacity-80 border-l-4 border-white/40' 
                                : option.bgColor + ' cursor-pointer hover:scale-[1.02] hover:pl-5 border-l-4 border-transparent',
                            isUpdatingStatus ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                        type="button"
                        role="menuitem"
                    >
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-all duration-300 bg-white/20" :class="refStatus === option.value ? 'bg-white/30' : ''">
                            <Icon
                                :icon="option.icon"
                                width="20"
                                height="20"
                                class="drop-shadow-sm"
                                aria-hidden="true"
                            />
                        </div>
                        <span class="flex-1 text-left tracking-wide">{{ option.label }}</span>
                        <Icon
                            v-if="refStatus === option.value"
                            icon="heroicons-solid:check-circle"
                            width="20"
                            height="20"
                            class="text-white/90 animate-pulse"
                            aria-hidden="true"
                        />
                    </button>
                </div>
            </div>
        </Transition>
    </div>
    
    <!-- Confirmed status display with enhanced professional styling -->
    <div v-else class="relative group" :aria-label="accessibilityText">
        <!-- Status display with admin edit button -->
        <div class="relative inline-flex items-center gap-1.5">
            <div
                class="inline-flex items-center gap-2.5 rounded-xl text-base font-bold transition-all duration-300 hover:scale-105 hover:shadow-xl px-4 py-2.5 ring-2 backdrop-blur-sm relative overflow-hidden"
                :class="[attendanceStatus.bgColor, attendanceStatus.ringColor, attendanceStatus.glowColor]"
                role="status"
            >
                <!-- Animated gradient overlay -->
                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/20 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000 pointer-events-none"></div>
                
                <!-- Icon container with pulse effect -->
                <div class="relative flex items-center justify-center w-8 h-8 rounded-lg transition-all duration-300 group-hover:scale-110 group-hover:rotate-6" :class="attendanceStatus.iconBg">
                    <Icon
                        :icon="attendanceStatus.icon"
                        width="20"
                        height="20"
                        :class="attendanceStatus.color"
                        class="drop-shadow-md relative z-10"
                        aria-hidden="true"
                    />
                    <!-- Pulse ring on hover -->
                    <div class="absolute inset-0 rounded-lg bg-white/30 scale-0 group-hover:scale-150 opacity-0 group-hover:opacity-100 transition-all duration-500"></div>
                </div>
                
                <span class="text-white font-bold tracking-wide drop-shadow-sm relative z-10">{{ attendanceStatus.label }}</span>
            </div>
            
            <!-- Admin edit button - enhanced -->
            <button
                v-if="isCourseAdmin"
                @click.stop="toggleStatusMenu"
                :disabled="isUpdatingStatus"
                class="p-2 rounded-lg transition-all duration-200 hover:bg-gray-100 dark:hover:bg-gray-700 active:bg-gray-200 dark:active:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-400 dark:focus:ring-violet-500 focus:ring-opacity-50"
                :class="{ 'opacity-50 cursor-not-allowed': isUpdatingStatus }"
                type="button"
                title="แก้ไขสถานะการเข้าร่วม"
                aria-label="แก้ไขสถานะการเข้าร่วม"
            >
                <Icon
                    :icon="isUpdatingStatus ? 'heroicons-outline:arrow-path' : 'heroicons-outline:pencil'"
                    width="20"
                    height="20"
                    :class="[
                        'transition-all duration-200',
                        isUpdatingStatus ? 'text-blue-500 dark:text-violet-400 animate-spin' : 'text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-violet-400'
                    ]"
                    aria-hidden="true"
                />
            </button>
            
            <!-- Status menu dropdown with status-colored background -->
            <Transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="opacity-0 scale-90 -translate-y-2"
                enter-to-class="opacity-100 scale-100 translate-y-0"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100 scale-100 translate-y-0"
                leave-to-class="opacity-0 scale-90 -translate-y-2"
            >
                <div
                    v-if="showStatusMenu && isCourseAdmin"
                    v-click-outside="closeStatusMenu"
                    class="attendance-dropdown absolute top-full right-0 mt-3 w-56 rounded-2xl shadow-2xl z-50 overflow-hidden backdrop-blur-xl border-2 border-white/20 dark:border-gray-700/50 ring-1 ring-black/5"
                    :class="attendanceStatus.bgColor + '/80'"
                    role="menu"
                    aria-orientation="vertical"
                    aria-labelledby="status-menu-button"
                    @click.stop
                >
                    <div class="py-2">
                        <div class="px-4 py-3 text-xs font-black text-white/90 uppercase tracking-wider border-b-2 border-white/20 bg-black/10">
                            เลือกสถานะ
                        </div>
                        <button
                            v-for="option in statusOptions"
                            :key="option.value"
                            @click.stop="updateStatusByAdmin(option.value, $event)"
                            :disabled="refStatus === option.value || isUpdatingStatus"
                            class="w-full flex items-center gap-3 px-4 py-3.5 text-sm font-bold transition-all duration-300"
                            :class="[
                                option.color,
                                refStatus === option.value 
                                    ? option.activeBg + ' cursor-default opacity-80 border-l-4 border-white/40' 
                                    : option.bgColor + ' cursor-pointer hover:scale-[1.02] hover:pl-5 border-l-4 border-transparent',
                                isUpdatingStatus ? 'opacity-50 cursor-not-allowed' : ''
                            ]"
                            type="button"
                            role="menuitem"
                        >
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-all duration-300 bg-white/20" :class="refStatus === option.value ? 'bg-white/30' : ''">
                                <Icon
                                    :icon="option.icon"
                                    width="20"
                                    height="20"
                                    class="drop-shadow-sm"
                                    aria-hidden="true"
                                />
                            </div>
                            <span class="flex-1 text-left tracking-wide">{{ option.label }}</span>
                            <Icon
                                v-if="refStatus === option.value"
                                icon="heroicons-solid:check-circle"
                                width="20"
                                height="20"
                                class="text-white/90 animate-pulse"
                                aria-hidden="true"
                            />
                        </button>
                    </div>
                </div>
            </Transition>
        </div>
    </div>
</template>

<style scoped>
/* Custom breathing animation for pending status */
@keyframes breathing {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    }
    50% {
        transform: scale(1.05);
        box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.2), 0 4px 6px -4px rgb(0 0 0 / 0.1);
    }
}

.animate-breathing {
    animation: breathing 3s ease-in-out infinite;
}

/* Enhanced dropdown menu styles */
.attendance-dropdown {
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* Smooth hover transitions for menu items */
.attendance-dropdown button {
    position: relative;
    overflow: hidden;
}

.attendance-dropdown button::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    transform: translateX(-100%);
    transition: transform 0.6s ease;
}

.attendance-dropdown button:hover::before {
    transform: translateX(100%);
}

/* Enhanced focus states for accessibility */
button:focus-visible {
    outline: none;
}

/* Smooth color transitions */
* {
    transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Dark mode optimizations */
.dark .attendance-dropdown {
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
}

/* Improve text rendering */
span, p, div {
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}
</style>
