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
                icon:'heroicons-outline:x-circle',
                color:'text-red-500',
                label: 'ขาด',
                bgColor: 'bg-red-50'
            };
        case 1:
            return {
                icon:'heroicons-outline:check-circle',
                color:'text-green-500',
                label: 'มา',
                bgColor: 'bg-green-50'
            };
        case 2:
            return {
                icon:'heroicons-outline:clock',
                color:'text-yellow-500',
                label: 'สาย',
                bgColor: 'bg-yellow-50'
            };
        case 3:
            return {
                icon:'heroicons-outline:document-text',
                color:'text-blue-500',
                label: 'ลา',
                bgColor: 'bg-blue-50'
            };
        default:
            return {
                icon:'heroicons-outline:minus-circle',
                color:'text-gray-500',
                label: 'ไม่มีข้อมูล',
                bgColor: 'bg-gray-50'
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

// Available status options for admin
const statusOptions = [
    { value: 1, label: 'มา', icon: 'heroicons-outline:check-circle', color: 'text-green-600', bgColor: 'hover:bg-green-50' },
    { value: 2, label: 'สาย', icon: 'heroicons-outline:clock', color: 'text-yellow-600', bgColor: 'hover:bg-yellow-50' },
    { value: 3, label: 'ลา', icon: 'heroicons-outline:document-text', color: 'text-blue-600', bgColor: 'hover:bg-blue-50' },
    { value: 0, label: 'ขาด', icon: 'heroicons-outline:x-circle', color: 'text-red-600', bgColor: 'hover:bg-red-50' },
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
            class="text-center bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-4 shadow-sm border border-gray-200"
            role="status"
            aria-label="ไม่มีข้อมูลการเช็คชื่อ"
        >
            <Icon icon="heroicons-outline:question-mark-circle" width="28" height="28" class="text-gray-400 mx-auto mb-2" aria-hidden="true" />
            <p class="text-xs text-gray-500 font-medium">ไม่มีข้อมูล</p>
        </div>
    </div>
    
    <!-- Normal attendance status display -->
    <div v-else-if="isPending" class="relative inline-flex items-center justify-center gap-1" :aria-label="accessibilityText">
        <!-- Pending status with clock icon (enhanced with animation) -->
        <button
            v-if="isCourseAdmin"
            @click="handlePendingClick"
            :disabled="isCheckingStatus"
            class="relative group p-2 rounded-xl transition-all duration-300 hover:shadow-lg hover:scale-105 bg-gradient-to-br from-amber-50 to-orange-50 border border-amber-200 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-opacity-50"
            :title="isCheckingStatus ? 'กำลังตรวจสอบสถานะ...' : 'คลิกเพื่อตรวจสอบสถานะล่าสุด'"
            :aria-label="isCheckingStatus ? 'กำลังตรวจสอบสถานะ' : 'คลิกเพื่อตรวจสอบสถานะล่าสุด'"
            type="button"
        >
            <div class="relative">
                <Icon
                    icon="heroicons-outline:clock"
                    width="36"
                    height="36"
                    :class="[
                        'transition-all duration-300',
                        isCheckingStatus ? 'text-amber-600 animate-pulse' : 'text-amber-500 group-hover:text-amber-600'
                    ]"
                    aria-hidden="true"
                />
                <div v-if="isCheckingStatus" class="absolute inset-0 rounded-full border-2 border-amber-300 animate-ping" aria-hidden="true"></div>
            </div>
            <span class="absolute -top-1 -right-1 w-3 h-3 bg-amber-400 rounded-full animate-pulse" aria-hidden="true"></span>
            <!-- Error indicator -->
            <div v-if="hasError" class="absolute -bottom-1 -right-1 w-3 h-3 bg-red-500 rounded-full" aria-hidden="true" :title="errorMessage"></div>
        </button>
        
        <!-- Non-admin users just see the clock icon -->
        <div
            v-else
            class="relative p-2 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200"
            :title="isCheckingStatus ? 'กำลังตรวจสอบสถานะ...' : 'รอการประมวลผล'"
            role="status"
            :aria-label="isCheckingStatus ? 'กำลังตรวจสอบสถานะ' : 'รอการประมวลผลสถานะการเช็คชื่อ'"
        >
            <div class="relative">
                <Icon
                    icon="heroicons-outline:clock"
                    width="36"
                    height="36"
                    :class="[
                        'transition-colors duration-300',
                        isCheckingStatus ? 'text-blue-600 animate-pulse' : 'text-blue-500'
                    ]"
                    aria-hidden="true"
                />
                <div v-if="isCheckingStatus" class="absolute inset-0 rounded-full border-2 border-blue-300 animate-ping" aria-hidden="true"></div>
            </div>
            <span class="absolute -top-1 -right-1 w-3 h-3 bg-blue-400 rounded-full animate-pulse" aria-hidden="true"></span>
            <!-- Error indicator -->
            <div v-if="hasError" class="absolute -bottom-1 -right-1 w-3 h-3 bg-red-500 rounded-full" aria-hidden="true" :title="errorMessage"></div>
        </div>
        
        <!-- Admin edit button for pending status -->
        <button
            v-if="isCourseAdmin"
            @click.stop="toggleStatusMenu"
            :disabled="isUpdatingStatus"
            class="p-2 rounded-lg transition-all duration-200 hover:bg-gray-100 active:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-opacity-50"
            :class="{ 'opacity-50 cursor-not-allowed': isUpdatingStatus }"
            type="button"
            title="กำหนดสถานะการเข้าร่วม"
            aria-label="กำหนดสถานะการเข้าร่วม"
        >
            <Icon
                :icon="isUpdatingStatus ? 'heroicons-outline:arrow-path' : 'heroicons-outline:pencil'"
                width="20"
                height="20"
                :class="[
                    'transition-all duration-200',
                    isUpdatingStatus ? 'text-blue-500 animate-spin' : 'text-gray-600 hover:text-blue-600'
                ]"
                aria-hidden="true"
            />
        </button>
        
        <!-- Status menu dropdown for pending -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="showStatusMenu && isCourseAdmin"
                v-click-outside="closeStatusMenu"
                class="absolute top-full right-0 mt-2 w-48 bg-white rounded-xl shadow-2xl border border-gray-200 z-50 overflow-hidden"
                role="menu"
                aria-orientation="vertical"
                aria-labelledby="status-menu-button"
                @click.stop
            >
                <div class="py-1">
                    <div class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-100">
                        เลือกสถานะ
                    </div>
                    <button
                        v-for="option in statusOptions"
                        :key="option.value"
                        @click.stop="updateStatusByAdmin(option.value, $event)"
                        :disabled="refStatus === option.value || isUpdatingStatus"
                        class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium transition-colors duration-150"
                        :class="[
                            option.bgColor,
                            option.color,
                            refStatus === option.value 
                                ? 'bg-gray-100 cursor-default opacity-60' 
                                : 'hover:shadow-sm cursor-pointer',
                            isUpdatingStatus ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                        type="button"
                        role="menuitem"
                    >
                        <Icon
                            :icon="option.icon"
                            width="20"
                            height="20"
                            aria-hidden="true"
                        />
                        <span class="flex-1 text-left">{{ option.label }}</span>
                        <Icon
                            v-if="refStatus === option.value"
                            icon="heroicons-outline:check"
                            width="16"
                            height="16"
                            class="text-gray-400"
                            aria-hidden="true"
                        />
                    </button>
                </div>
            </div>
        </Transition>
    </div>
    
    <!-- Confirmed status display with enhanced styling -->
    <div v-else class="relative group" :aria-label="accessibilityText">
        <!-- Status display with admin edit button -->
        <div class="relative inline-flex items-center gap-1">
            <div
                class="inline-flex items-center gap-2 rounded-xl text-sm font-bold shadow-md transition-all duration-300 hover:shadow-lg hover:scale-105 px-3 py-2"
                :class="[
                    attendanceStatus.bgColor,
                    attendanceStatus.color === 'text-green-500' ? 'border border-green-200' :
                    attendanceStatus.color === 'text-red-500' ? 'border border-red-200' :
                    attendanceStatus.color === 'text-yellow-500' ? 'border border-yellow-200' :
                    attendanceStatus.color === 'text-blue-500' ? 'border border-blue-200' :
                    'border border-gray-200'
                ]"
                role="status"
            >
                <Icon
                    :icon="attendanceStatus.icon"
                    width="32"
                    height="32"
                    :class="attendanceStatus.color"
                    aria-hidden="true"
                />
                <span class="sr-only">{{ attendanceStatus.label }}</span>
            </div>
            
            <!-- Admin edit button - แสดงเสมอสำหรับ course admin -->
            <button
                v-if="isCourseAdmin"
                @click.stop="toggleStatusMenu"
                :disabled="isUpdatingStatus"
                class="p-2 rounded-lg transition-all duration-200 hover:bg-gray-100 active:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-opacity-50"
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
                        isUpdatingStatus ? 'text-blue-500 animate-spin' : 'text-gray-600 hover:text-blue-600'
                    ]"
                    aria-hidden="true"
                />
            </button>
            
            <!-- Status menu dropdown -->
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-if="showStatusMenu && isCourseAdmin"
                    v-click-outside="closeStatusMenu"
                    class="absolute top-full right-0 mt-2 w-48 bg-white rounded-xl shadow-2xl border border-gray-200 z-50 overflow-hidden"
                    role="menu"
                    aria-orientation="vertical"
                    aria-labelledby="status-menu-button"
                    @click.stop
                >
                    <div class="py-1">
                        <div class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-100">
                            เลือกสถานะ
                        </div>
                        <button
                            v-for="option in statusOptions"
                            :key="option.value"
                            @click.stop="updateStatusByAdmin(option.value, $event)"
                            :disabled="refStatus === option.value || isUpdatingStatus"
                            class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium transition-colors duration-150"
                            :class="[
                                option.bgColor,
                                option.color,
                                refStatus === option.value 
                                    ? 'bg-gray-100 cursor-default opacity-60' 
                                    : 'hover:shadow-sm cursor-pointer',
                                isUpdatingStatus ? 'opacity-50 cursor-not-allowed' : ''
                            ]"
                            type="button"
                            role="menuitem"
                        >
                            <Icon
                                :icon="option.icon"
                                width="20"
                                height="20"
                                aria-hidden="true"
                            />
                            <span class="flex-1 text-left">{{ option.label }}</span>
                            <Icon
                                v-if="refStatus === option.value"
                                icon="heroicons-outline:check"
                                width="16"
                                height="16"
                                class="text-gray-400"
                                aria-hidden="true"
                            />
                        </button>
                    </div>
                </div>
            </Transition>
        </div>
        
        <!-- Add a subtle glow effect for confirmed status -->
        <div
            class="absolute inset-0 rounded-xl opacity-0 group-hover:opacity-20 transition-opacity duration-300 pointer-events-none"
            :class="[
                attendanceStatus.color === 'text-green-500' ? 'bg-green-400' :
                attendanceStatus.color === 'text-red-500' ? 'bg-red-400' :
                attendanceStatus.color === 'text-yellow-500' ? 'bg-yellow-400' :
                attendanceStatus.color === 'text-blue-500' ? 'bg-blue-400' :
                'bg-gray-400'
            ]"
            aria-hidden="true"
        ></div>
    </div>
</template>
