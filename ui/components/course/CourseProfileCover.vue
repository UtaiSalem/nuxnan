<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Icon } from '@iconify/vue';

const props = defineProps({
    courseMemberOfAuth: { type: Object, default: null },
});

const emit = defineEmits([
    'request-member',
    'request-unmember',
    'refresh'
]);

// Use API composable and stores
const api = useApi();
const config = useRuntimeConfig();
const courseStore = useCourseStore();

// Get data from stores
const course = computed(() => courseStore.currentCourse);
const academy = computed(() => courseStore.academy);
const isAdmin = computed(() => courseStore.isCourseAdmin);
const courseGroups = computed(() => course.value?.groups || []);

// Computed course data
const courseId = computed(() => course.value?.id);
const courseName = computed(() => course.value?.name || '');
const courseCode = computed(() => course.value?.code || '');
const tuitionFees = computed(() => course.value?.tuition_fees);
const lessonsCount = computed(() => course.value?.lessons_count ?? 0);
const enrolledStudents = computed(() => course.value?.enrolled_students ?? 0);
const groupsCount = computed(() => course.value?.groups ?? 0);
const memberStatus = computed(() => props.courseMemberOfAuth?.status || course.value?.member_status);

// Check if groups are available
const hasGroups = computed(() => (course.value?.groups?.length ?? 0) > 0);

// Refs for file inputs and dropdown
const logoInput = ref(null);
const coverInput = ref(null);
const dropdownRef = ref(null);
const membershipDropdownRef = ref(null);

// UI States
const showOptionGroups = ref(false);
const showAcceptMemberOption = ref(false);
const showEditModal = ref(false);
const tempName = ref('');
const tempCode = ref('');

// Loading states
const isUpdatingCover = ref(false);
const isUpdatingLogo = ref(false);
const isUpdatingName = ref(false);
const isUpdatingCode = ref(false);
const isRequestingMember = ref(false);
const isRequestingUnmember = ref(false);

// Temp images for preview
const coverPreview = ref(null);
const logoPreview = ref(null);

// Image URLs
const coverUrl = computed(() => {
    if (coverPreview.value) return coverPreview.value;
    if (course.value?.cover) {
        return `${config.public.apiBase}/storage/images/courses/covers/${course.value.cover}`;
    }
    return '/images/default-cover.jpg';
});

const logoUrl = computed(() => {
    if (logoPreview.value) return logoPreview.value;
    if (course.value?.logo) {
        return `${config.public.apiBase}/storage/images/courses/logos/${course.value.logo}`;
    }
    if (course.value?.user?.avatar) {
        return course.value.user.avatar;
    }
    return '/images/default-logo.png';
});

// File input handlers
const browseCover = () => coverInput.value?.click();
const browseLogo = () => logoInput.value?.click();

// Modal handlers
function openEditModal() {
    tempName.value = courseName.value;
    tempCode.value = courseCode.value;
    showEditModal.value = true;
}

function closeEditModal() {
    showEditModal.value = false;
    tempName.value = '';
    tempCode.value = '';
    isUpdatingName.value = false;
    isUpdatingCode.value = false;
}

async function saveCourseInfo() {
    if (!tempName.value.trim()) {
        alert('กรุณากรอกชื่อรายวิชา');
        return;
    }
    
    isUpdatingName.value = true;
    try {
        const data = {
            name: tempName.value.trim(),
            code: tempCode.value.trim()
        };
        
        await api.put(`/api/courses/${courseId.value}`, data);
        
        // Update store
        courseStore.updateCourse(data);
        
        emit('refresh');
        closeEditModal();
    } catch (error) {
        console.error('Failed to update course info:', error);
        alert('เกิดข้อผิดพลาดในการบันทึก กรุณาลองใหม่อีกครั้ง');
    } finally {
        isUpdatingName.value = false;
    }
}

// Cover upload
async function onCoverInputChange(event) {
    const file = event.target.files?.[0];
    if (!file) return;
    
    coverPreview.value = URL.createObjectURL(file);
    isUpdatingCover.value = true;
    
    try {
        const formData = new FormData();
        formData.append('cover', file);
        
        const response = await api.post(`/api/courses/${courseId.value}/cover`, formData);
        
        // Update store with new cover
        if (response.cover) {
            courseStore.updateCourse({ cover: response.cover });
        }
        
        emit('refresh');
    } catch (error) {
        console.error('Failed to update cover:', error);
        coverPreview.value = null;
    } finally {
        isUpdatingCover.value = false;
    }
}

// Logo upload
async function onLogoInputChange(event) {
    const file = event.target.files?.[0];
    if (!file) return;
    
    logoPreview.value = URL.createObjectURL(file);
    isUpdatingLogo.value = true;
    
    try {
        const formData = new FormData();
        formData.append('logo', file);
        
        const response = await api.post(`/api/courses/${courseId.value}/logo`, formData);
        
        // Update store with new logo
        if (response.logo) {
            courseStore.updateCourse({ logo: response.logo });
        }
        
        emit('refresh');
    } catch (error) {
        console.error('Failed to update logo:', error);
        logoPreview.value = null;
    } finally {
        isUpdatingLogo.value = false;
    }
}

// Name editing
function startEditingName() {
    openEditModal();
}

// Code editing
function startEditingCode() {
    openEditModal();
}

// Membership handlers
async function onRequestToBeMember(groupId = null) {
    if (isRequestingMember.value) return;
    
    isRequestingMember.value = true;
    try {
        const data = groupId ? { group_id: groupId } : {};
        const response = await api.post(`/api/courses/${courseId.value}/members`, data);
        
        // Update store with membership status
        courseStore.updateCourse({ 
            isMember: true,
            member_status: response.memberStatus || 'pending'
        });
        
        showOptionGroups.value = false;
        emit('request-member', groupId);
        emit('refresh');
    } catch (error) {
        console.error('Failed to request membership:', error);
    } finally {
        isRequestingMember.value = false;
    }
}

async function onRequestToBeUnMember() {
    if (!props.courseMemberOfAuth?.id) return;
    if (isRequestingUnmember.value) return;
    
    // Confirm for active members
    if (memberStatus.value === '1' || memberStatus.value === 'active') {
        const confirmed = confirm('คุณต้องการออกจากรายวิชานี้ใช่หรือไม่?');
        if (!confirmed) return;
    }
    
    isRequestingUnmember.value = true;
    try {
        await api.delete(`/api/courses/${courseId.value}/members/${props.courseMemberOfAuth.id}`);
        
        // Update store
        courseStore.updateCourse({ 
            isMember: false,
            member_status: null
        });
        
        showAcceptMemberOption.value = false;
        emit('request-unmember', props.courseMemberOfAuth.id);
        emit('refresh');
    } catch (error) {
        console.error('Failed to cancel membership:', error);
    } finally {
        isRequestingUnmember.value = false;
    }
}

// Toggle handlers
function toggleOptionGroups() {
    showOptionGroups.value = !showOptionGroups.value;
    showAcceptMemberOption.value = false;
}

function toggleAcceptMemberOption() {
    showAcceptMemberOption.value = !showAcceptMemberOption.value;
    showOptionGroups.value = false;
}

// Close dropdowns when clicking outside
function handleClickOutside(event) {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        showOptionGroups.value = false;
    }
    if (membershipDropdownRef.value && !membershipDropdownRef.value.contains(event.target)) {
        showAcceptMemberOption.value = false;
    }
}

function handleEscapeKey(event) {
    if (event.key === 'Escape') {
        showOptionGroups.value = false;
        showAcceptMemberOption.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleEscapeKey);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleEscapeKey);
    if (coverPreview.value) URL.revokeObjectURL(coverPreview.value);
    if (logoPreview.value) URL.revokeObjectURL(logoPreview.value);
});
</script>

<style scoped>
@keyframes spin-slow {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.animate-spin-slow {
    animation: spin-slow 3s linear infinite;
}

.shadow-3xl {
    box-shadow: 0 35px 60px -15px rgba(0, 0, 0, 0.3);
}

/* Glassmorphism effect */
.backdrop-blur-md {
    backdrop-filter: blur(12px);
}

/* Smooth transitions for all interactive elements */
* {
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}
</style>

<template>
    <div class="relative w-full bg-white dark:bg-gray-900 rounded-2xl overflow-hidden shadow-2xl border border-gray-100 dark:border-gray-800 transition-all duration-300">
        <!-- Cover Photo Section -->
        <div 
            class="relative h-48 sm:h-64 md:h-72 lg:h-80 bg-cover bg-center bg-no-repeat transition-all duration-500"
            :style="{ backgroundImage: `url(${coverUrl})` }"
        >
            <!-- Enhanced Overlay gradient for better visual depth -->
            <div class="absolute inset-0 bg-gradient-to-br from-blue-600/20 via-purple-600/10 to-pink-600/20 dark:from-blue-900/30 dark:via-purple-900/20 dark:to-pink-900/30"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent"></div>
            
            <!-- Animated Background Pattern -->
            <div class="absolute inset-0 opacity-10 dark:opacity-5" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 40px 40px;"></div>
            
            <!-- Edit Cover Button (Admin Only) -->
            <div class="absolute top-4 left-4 z-10" v-if="isAdmin">
                <input type="file" class="hidden" ref="coverInput" accept="image/*" @change="onCoverInputChange">
                <button type="button" @click.prevent="browseCover" :disabled="isUpdatingCover"
                    class="group relative p-3 text-gray-700 dark:text-gray-200 bg-white/80 dark:bg-gray-800/80 backdrop-blur-md rounded-xl hover:bg-white dark:hover:bg-gray-700 transition-all duration-300 disabled:opacity-50 shadow-lg hover:shadow-xl hover:scale-110 border border-white/20">
                    <Icon v-if="isUpdatingCover" icon="svg-spinners:ring-resize" class="w-5 h-5" />
                    <Icon v-else icon="fluent:camera-edit-20-filled" class="w-5 h-5 group-hover:scale-110 transition-transform" />
                    <div class="absolute -bottom-8 left-1/2 transform -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                        แก้ไขปก
                    </div>
                </button>
            </div>
            
            <!-- Tuition Fees Badge with enhanced styling -->
            <div v-if="tuitionFees" class="absolute top-4 right-4 z-10 animate-pulse">
                <div class="relative group">
                    <div class="absolute inset-0 bg-gradient-to-r from-yellow-400 to-orange-500 rounded-xl blur-md opacity-75 group-hover:opacity-100 transition-opacity"></div>
                    <div class="relative flex items-center px-4 py-2.5 space-x-2 font-bold text-white rounded-xl bg-gradient-to-r from-yellow-400 to-orange-500 shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105 border border-yellow-300/50">
                        <Icon icon="ri:bit-coin-fill" class="w-6 h-6 animate-spin-slow" />
                        <span class="text-lg">{{ tuitionFees }}</span>
                        <span class="text-sm opacity-90">บาท</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Avatar + Name Section (Overlapping Cover) -->
        <div class="flex flex-col sm:flex-row items-center justify-between w-full px-4 sm:px-6 -mt-[70px] sm:-mt-[80px] md:-mt-[90px] transition-all duration-300">
            <div class="flex flex-col sm:flex-row items-center gap-4 w-full sm:w-auto">
                <!-- Avatar with enhanced styling -->
                <div class="relative flex-shrink-0 group">
                    <!-- Glow effect -->
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 rounded-full blur-xl opacity-0 group-hover:opacity-60 transition-opacity duration-500 animate-pulse pointer-events-none -z-10"></div>
                    
                    <div class="relative w-24 h-24 sm:w-28 sm:h-28 md:w-32 md:h-32 lg:w-36 lg:h-36 rounded-full border-4 border-white dark:border-gray-800 overflow-hidden bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 shadow-2xl transition-all duration-300 group-hover:scale-105 group-hover:shadow-3xl z-10">
                        <img :src="logoUrl" alt="Course Logo" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                        
                        <!-- Overlay effect on hover -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    </div>
                    
                    <input type="file" class="hidden" ref="logoInput" accept="image/*" @change="onLogoInputChange" v-if="isAdmin">
                    <button v-if="isAdmin" type="button" @click.prevent="browseLogo" :disabled="isUpdatingLogo"
                        class="absolute bottom-2 right-2 p-2.5 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-full hover:from-blue-600 hover:to-purple-700 transition-all duration-300 disabled:opacity-50 shadow-lg hover:shadow-xl hover:scale-110 border-2 border-white dark:border-gray-800 z-10">
                        <Icon v-if="isUpdatingLogo" icon="svg-spinners:ring-resize" class="w-4 h-4" />
                        <Icon v-else icon="fluent:camera-edit-20-filled" class="w-4 h-4" />
                    </button>
                </div>

                <!-- Course Name & Code -->
                <div class="space-y-2 text-center sm:text-left mt-3 sm:mt-6">
                    <!-- Course Name -->
                    <div class="flex items-center gap-2 flex-wrap justify-center sm:justify-start">
                        <div class="relative group">
                            <!-- Glow effect -->
                            <div class="absolute inset-0 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 rounded-2xl blur-xl opacity-0 group-hover:opacity-30 transition-opacity duration-500 pointer-events-none -z-10"></div>
                            
                            <h1 class="relative z-10 px-5 py-3 text-lg sm:text-xl md:text-2xl font-black text-white bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 dark:from-gray-800 dark:via-gray-700 dark:to-gray-800 rounded-2xl shadow-2xl border border-slate-700/50 dark:border-gray-600/50 backdrop-blur-sm transition-all duration-300 group-hover:shadow-3xl group-hover:scale-105">
                                <span class="bg-gradient-to-r from-blue-400 via-purple-400 to-pink-400 bg-clip-text text-transparent">
                                    {{ courseName || 'ไม่มีชื่อรายวิชา' }}
                                </span>
                            </h1>
                        </div>
                        <button v-if="isAdmin" @click="startEditingName" 
                            class="group relative z-20 p-2.5 bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700 rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl border border-gray-200 dark:border-gray-600 hover:scale-110">
                            <Icon icon="fluent:edit-24-filled" class="w-5 h-5 text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform" />
                            <div class="absolute -bottom-8 left-1/2 transform -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-50">
                                แก้ไขข้อมูล
                            </div>
                        </button>
                    </div>

                    <!-- Course Code -->
                    <div class="flex items-center gap-2 justify-center sm:justify-start">
                        <span v-if="courseCode" class="group relative px-5 py-2.5 text-sm font-bold text-white bg-gradient-to-r from-cyan-500 via-blue-600 to-purple-600 rounded-full shadow-xl border-2 border-cyan-400/30 transition-all duration-300 hover:shadow-2xl hover:scale-105 cursor-default">
                            <Icon icon="fluent:number-symbol-square-24-filled" class="w-4 h-4 inline-block mr-1.5" />
                            <span class="tracking-wider">{{ courseCode }}</span>
                            
                            <!-- Shine effect -->
                            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent transform -skew-x-12 translate-x-full group-hover:translate-x-[-200%] transition-transform duration-1000"></div>
                        </span>
                        <span v-else-if="isAdmin" @click="startEditingCode" class="px-4 py-2 text-sm font-medium text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-800 rounded-full border-2 border-dashed border-gray-300 dark:border-gray-600 hover:border-cyan-400 dark:hover:border-cyan-600 transition-colors cursor-pointer">
                            <Icon icon="fluent:add-circle-24-regular" class="w-4 h-4 inline-block mr-1" />
                            เพิ่มรหัสวิชา
                        </span>
                    </div>
                </div>
            </div>

            <!-- Desktop: Join/Member Button -->
            <div v-if="!isAdmin" class="hidden md:block mt-4 sm:mt-0">
                <!-- Pending Status -->
                <div v-if="courseMemberOfAuth && (memberStatus === '0' || memberStatus === 'pending')" ref="membershipDropdownRef" class="relative">
                    <button @click.prevent="toggleAcceptMemberOption"
                        class="flex items-center gap-2 px-5 py-2.5 bg-yellow-500 hover:bg-yellow-600 text-white font-semibold rounded-lg transition-colors shadow-md">
                        <Icon icon="heroicons:clock" class="w-5 h-5" />
                        <span>รอการตอบรับ</span>
                        <Icon icon="heroicons:chevron-down" class="w-4 h-4 transition-transform" :class="{'rotate-180': showAcceptMemberOption}" />
                    </button>
                    <div v-if="showAcceptMemberOption" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border z-50">
                        <button @click.prevent="onRequestToBeUnMember" :disabled="isRequestingUnmember"
                            class="w-full px-4 py-3 text-red-600 hover:bg-red-50 rounded-lg font-medium flex items-center gap-2 disabled:opacity-50">
                            <Icon v-if="isRequestingUnmember" icon="svg-spinners:ring-resize" class="w-5 h-5" />
                            <Icon v-else icon="heroicons:x-circle" class="w-5 h-5" />
                            <span>ยกเลิกคำขอ</span>
                        </button>
                    </div>
                </div>

                <!-- Active Member -->
                <button v-else-if="courseMemberOfAuth && (memberStatus === '1' || memberStatus === 'active')"
                    @click.prevent="onRequestToBeUnMember" :disabled="isRequestingUnmember"
                    class="flex items-center gap-2 px-5 py-2.5 bg-emerald-500 hover:bg-red-500 text-white font-semibold rounded-lg transition-colors shadow-md disabled:opacity-50">
                    <Icon v-if="isRequestingUnmember" icon="svg-spinners:ring-resize" class="w-5 h-5" />
                    <Icon v-else icon="fluent:checkmark-circle-24-filled" class="w-5 h-5" />
                    <span>เป็นสมาชิก</span>
                </button>

                <!-- Join Button -->
                <div v-else-if="!courseMemberOfAuth" ref="dropdownRef" class="relative">
                    <button v-if="!hasGroups" @click.prevent="onRequestToBeMember(null)" :disabled="isRequestingMember"
                        class="flex items-center gap-2 px-5 py-2.5 bg-cyan-500 hover:bg-cyan-600 text-white font-semibold rounded-lg transition-colors shadow-md disabled:opacity-50">
                        <Icon v-if="isRequestingMember" icon="svg-spinners:ring-resize" class="w-5 h-5" />
                        <Icon v-else icon="heroicons:user-plus" class="w-5 h-5" />
                        <span>สมัครเรียน</span>
                    </button>
                    <template v-else>
                        <button @click.prevent="toggleOptionGroups"
                            class="flex items-center gap-2 px-5 py-2.5 bg-cyan-500 hover:bg-cyan-600 text-white font-semibold rounded-lg transition-colors shadow-md">
                            <Icon v-if="isRequestingMember" icon="svg-spinners:ring-resize" class="w-5 h-5" />
                            <Icon v-else icon="heroicons:user-plus" class="w-5 h-5" />
                            <span>สมัครเรียน</span>
                            <Icon icon="heroicons:chevron-down" class="w-4 h-4 transition-transform" :class="{'rotate-180': showOptionGroups}" />
                        </button>
                        <div v-if="showOptionGroups" class="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-xl border z-50 max-h-64 overflow-y-auto">
                            <div class="sticky top-0 bg-gray-50 px-4 py-2 border-b">
                                <p class="text-xs font-medium text-gray-600">เลือกกลุ่มที่ต้องการเข้าร่วม</p>
                            </div>
                            <!-- Empty state -->
                            <div v-if="!courseGroups || courseGroups.length === 0" class="px-4 py-6 text-center text-gray-500">
                                <Icon icon="heroicons:user-group" class="w-12 h-12 mx-auto mb-2 opacity-30" />
                                <p class="text-sm">ไม่มีกลุ่มในรายวิชานี้</p>
                            </div>
                            <!-- Groups list -->
                            <button v-for="group in courseGroups" :key="group.id"
                                @click.prevent="onRequestToBeMember(group.id)" :disabled="isRequestingMember"
                                class="w-full px-4 py-3 text-left hover:bg-cyan-50 flex items-center gap-3 disabled:opacity-50">
                                <div class="w-8 h-8 rounded-full bg-cyan-100 flex items-center justify-center flex-shrink-0">
                                    <Icon icon="heroicons:user-group" class="w-4 h-4 text-cyan-600" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-900 truncate">{{ group.name }}</p>
                                    <p class="text-xs text-gray-500">{{ group.members_count || 0 }} สมาชิก</p>
                                </div>
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Stats Section -->
        <div class="py-6 px-4 sm:px-6">
            <div class="flex flex-wrap justify-center gap-4 md:gap-6">
                <!-- Lessons Count -->
                <div class="group relative flex items-center gap-3 px-5 py-3 rounded-2xl bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 border-2 border-blue-200 dark:border-blue-700 hover:shadow-xl transition-all duration-300 hover:scale-105 cursor-pointer overflow-hidden">
                    <!-- Animated background -->
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-500/0 via-blue-500/10 to-blue-500/0 transform translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                    
                    <div class="relative flex items-center justify-center w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg group-hover:shadow-xl group-hover:scale-110 transition-all duration-300">
                        <Icon icon="heroicons:book-open-solid" class="w-6 h-6 text-white" />
                    </div>
                    <div class="relative">
                        <div class="text-2xl font-black text-blue-600 dark:text-blue-400 leading-none">{{ lessonsCount }}</div>
                        <div class="text-xs font-semibold text-blue-700/70 dark:text-blue-300/70 mt-0.5">บทเรียน</div>
                    </div>
                </div>

                <!-- Enrolled Students -->
                <div class="group relative flex items-center gap-3 px-5 py-3 rounded-2xl bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/30 dark:to-purple-800/30 border-2 border-purple-200 dark:border-purple-700 hover:shadow-xl transition-all duration-300 hover:scale-105 cursor-pointer overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-r from-purple-500/0 via-purple-500/10 to-purple-500/0 transform translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                    
                    <div class="relative flex items-center justify-center w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg group-hover:shadow-xl group-hover:scale-110 transition-all duration-300">
                        <Icon icon="heroicons:users-solid" class="w-6 h-6 text-white" />
                    </div>
                    <div class="relative">
                        <div class="text-2xl font-black text-purple-600 dark:text-purple-400 leading-none">{{ enrolledStudents || 0 }}</div>
                        <div class="text-xs font-semibold text-purple-700/70 dark:text-purple-300/70 mt-0.5">ผู้เรียน</div>
                    </div>
                </div>

                <!-- Groups Count -->
                <div class="group relative flex items-center gap-3 px-5 py-3 rounded-2xl bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-800/30 border-2 border-green-200 dark:border-green-700 hover:shadow-xl transition-all duration-300 hover:scale-105 cursor-pointer overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-r from-green-500/0 via-green-500/10 to-green-500/0 transform translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                    
                    <div class="relative flex items-center justify-center w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg group-hover:shadow-xl group-hover:scale-110 transition-all duration-300">
                        <Icon icon="heroicons:user-group-solid" class="w-6 h-6 text-white" />
                    </div>
                    <div class="relative">
                        <div class="text-2xl font-black text-green-600 dark:text-green-400 leading-none">{{ groupsCount || 0 }}</div>
                        <div class="text-xs font-semibold text-green-700/70 dark:text-green-300/70 mt-0.5">กลุ่ม</div>
                    </div>
                </div>

                <!-- Academy Info -->
                <NuxtLink v-if="academy" :to="`/academies/${academy.id}`" 
                    class="group relative flex items-center gap-3 px-5 py-3 rounded-2xl bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/30 dark:to-orange-800/30 border-2 border-orange-200 dark:border-orange-700 hover:shadow-xl transition-all duration-300 hover:scale-105 overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-r from-orange-500/0 via-orange-500/10 to-orange-500/0 transform translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>
                    
                    <div class="relative flex items-center justify-center w-12 h-12 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg overflow-hidden group-hover:shadow-xl group-hover:scale-110 transition-all duration-300">
                        <img v-if="academy.logo" :src="`${$config.public.apiBase}/storage/images/academies/logos/${academy.logo}`" class="w-full h-full object-cover" />
                        <Icon v-else icon="heroicons:academic-cap-solid" class="w-6 h-6 text-white" />
                    </div>
                    <div class="relative text-left">
                        <div class="text-sm font-black text-orange-700 dark:text-orange-400 truncate max-w-[140px] leading-tight">{{ academy.name }}</div>
                        <div class="text-xs font-semibold text-orange-600/70 dark:text-orange-300/70">สถาบัน</div>
                    </div>
                </NuxtLink>
            </div>

            <!-- Mobile: Join/Member Button -->
            <div v-if="!isAdmin" class="flex justify-center mt-4 md:hidden">
                <!-- Pending Status (Mobile) -->
                <div v-if="courseMemberOfAuth && (memberStatus === '0' || memberStatus === 'pending')" class="relative w-56">
                    <button @click.prevent="toggleAcceptMemberOption"
                        class="w-full flex items-center justify-between gap-2 px-5 py-2.5 bg-yellow-500 active:bg-yellow-600 text-white font-semibold rounded-lg transition-colors shadow-md">
                        <span class="flex items-center gap-2">
                            <Icon icon="heroicons:clock" class="w-5 h-5" />
                            <span>รอการตอบรับ</span>
                        </span>
                        <Icon icon="heroicons:chevron-down" class="w-4 h-4 transition-transform" :class="{'rotate-180': showAcceptMemberOption}" />
                    </button>
                    <div v-if="showAcceptMemberOption" class="absolute left-0 right-0 mt-2 bg-white rounded-lg shadow-xl border z-50">
                        <button @click.prevent="onRequestToBeUnMember" :disabled="isRequestingUnmember"
                            class="w-full px-4 py-3 text-red-600 active:bg-red-50 rounded-lg font-medium flex items-center justify-center gap-2 disabled:opacity-50">
                            <Icon v-if="isRequestingUnmember" icon="svg-spinners:ring-resize" class="w-5 h-5" />
                            <Icon v-else icon="heroicons:x-circle" class="w-5 h-5" />
                            <span>ยกเลิกคำขอ</span>
                        </button>
                    </div>
                </div>

                <!-- Active Member (Mobile) -->
                <button v-else-if="courseMemberOfAuth && (memberStatus === '1' || memberStatus === 'active')"
                    @click.prevent="onRequestToBeUnMember" :disabled="isRequestingUnmember"
                    class="w-56 flex items-center justify-center gap-2 px-5 py-2.5 bg-yellow-300 active:bg-red-500 active:text-white text-gray-700 font-semibold rounded-lg transition-all shadow-md disabled:opacity-50">
                    <Icon v-if="isRequestingUnmember" icon="svg-spinners:ring-resize" class="w-5 h-5" />
                    <Icon v-else icon="majesticons:door-exit-line" class="w-5 h-5" />
                    <span>ออกจากสมาชิก</span>
                </button>

                <!-- Join Button (Mobile) -->
                <div v-else-if="!courseMemberOfAuth" class="relative w-56">
                    <button v-if="!hasGroups" @click.prevent="onRequestToBeMember(null)" :disabled="isRequestingMember"
                        class="w-full flex items-center justify-center gap-2 px-5 py-2.5 bg-cyan-500 active:bg-cyan-600 text-white font-semibold rounded-lg transition-colors shadow-md disabled:opacity-50">
                        <Icon v-if="isRequestingMember" icon="svg-spinners:ring-resize" class="w-5 h-5" />
                        <Icon v-else icon="heroicons:user-plus" class="w-5 h-5" />
                        <span>สมัครเรียน</span>
                    </button>
                    <template v-else>
                        <button @click.prevent="toggleOptionGroups"
                            class="w-full flex items-center justify-between gap-2 px-5 py-2.5 bg-cyan-500 active:bg-cyan-600 text-white font-semibold rounded-lg transition-colors shadow-md">
                            <span class="flex items-center gap-2">
                                <Icon v-if="isRequestingMember" icon="svg-spinners:ring-resize" class="w-5 h-5" />
                                <Icon v-else icon="heroicons:user-plus" class="w-5 h-5" />
                                <span>สมัครเรียน</span>
                            </span>
                            <Icon icon="heroicons:chevron-down" class="w-4 h-4 transition-transform" :class="{'rotate-180': showOptionGroups}" />
                        </button>
                        <div v-if="showOptionGroups" class="absolute left-0 right-0 mt-2 bg-white rounded-xl shadow-xl border z-50 max-h-64 overflow-y-auto">
                            <div class="sticky top-0 bg-gray-50 px-4 py-2 border-b">
                                <p class="text-xs font-medium text-gray-600">เลือกกลุ่มที่ต้องการเข้าร่วม</p>
                            </div>
                            <!-- Empty state -->
                            <div v-if="!courseGroups || courseGroups.length === 0" class="px-4 py-6 text-center text-gray-500">
                                <Icon icon="heroicons:user-group" class="w-12 h-12 mx-auto mb-2 opacity-30" />
                                <p class="text-sm">ไม่มีกลุ่มในรายวิชานี้</p>
                            </div>
                            <!-- Groups list -->
                            <button v-for="group in courseGroups" :key="group.id"
                                @click.prevent="onRequestToBeMember(group.id)" :disabled="isRequestingMember"
                                class="w-full px-4 py-3 text-left active:bg-cyan-50 flex items-center gap-3 border-b last:border-b-0 disabled:opacity-50">
                                <div class="w-8 h-8 rounded-full bg-cyan-100 flex items-center justify-center flex-shrink-0">
                                    <Icon icon="heroicons:user-group" class="w-4 h-4 text-cyan-600" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-900 truncate">{{ group.name }}</p>
                                    <p class="text-xs text-gray-500">{{ group.members_count || 0 }} สมาชิก</p>
                                </div>
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Course Info Modal -->
    <DialogModal :show="showEditModal" @close="closeEditModal" max-width="2xl">
        <template #title>
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl">
                    <Icon icon="fluent:edit-24-filled" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">แก้ไขข้อมูลรายวิชา</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">อัพเดทชื่อและรหัสวิชา</p>
                </div>
            </div>
        </template>

        <template #content>
            <div class="space-y-6">
                <!-- Course Name -->
                <div class="space-y-2">
                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
                        <Icon icon="heroicons:book-open-solid" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                        <span>ชื่อรายวิชา</span>
                        <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        v-model="tempName"
                        rows="3"
                        placeholder="กรอกชื่อรายวิชา (สามารถใส่ชื่อยาวได้)"
                        class="w-full px-4 py-3 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-xl font-medium text-base focus:outline-none focus:ring-4 focus:ring-blue-500/50 border-2 border-gray-200 dark:border-gray-700 hover:border-blue-400 dark:hover:border-blue-600 transition-all resize-none"
                        :class="{ 'border-red-500 focus:ring-red-500/50': !tempName.trim() }"
                    ></textarea>
                    <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                        <Icon icon="heroicons:information-circle" class="w-4 h-4" />
                        <span>ตัวอักษร: {{ tempName.length }} / 500</span>
                    </p>
                </div>

                <!-- Course Code -->
                <div class="space-y-2">
                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
                        <Icon icon="fluent:number-symbol-square-24-filled" class="w-5 h-5 text-cyan-600 dark:text-cyan-400" />
                        <span>รหัสวิชา</span>
                        <span class="text-gray-400 text-xs">(ไม่บังคับ)</span>
                    </label>
                    <input
                        v-model="tempCode"
                        type="text"
                        placeholder="เช่น CS101, MATH201"
                        maxlength="50"
                        class="w-full px-4 py-3 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-xl font-medium text-base focus:outline-none focus:ring-4 focus:ring-cyan-500/50 border-2 border-gray-200 dark:border-gray-700 hover:border-cyan-400 dark:hover:border-cyan-600 transition-all"
                    />
                    <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                        <Icon icon="heroicons:light-bulb" class="w-4 h-4" />
                        <span>รหัสวิชาจะแสดงเป็น badge ที่สวยงาม</span>
                    </p>
                </div>

                <!-- Preview Section -->
                <div class="p-4 bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-xl border-2 border-blue-200 dark:border-blue-800">
                    <p class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2 flex items-center gap-1">
                        <Icon icon="heroicons:eye" class="w-4 h-4" />
                        <span>ตัวอย่างการแสดงผล:</span>
                    </p>
                    <div class="flex items-center gap-2 flex-wrap">
                        <div class="px-4 py-2 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 dark:from-gray-800 dark:via-gray-700 dark:to-gray-800 rounded-xl shadow-lg border border-slate-700/50">
                            <span class="text-sm font-bold bg-gradient-to-r from-blue-400 via-purple-400 to-pink-400 bg-clip-text text-transparent">
                                {{ tempName || 'ชื่อรายวิชา' }}
                            </span>
                        </div>
                        <div v-if="tempCode.trim()" class="px-3 py-1.5 bg-gradient-to-r from-cyan-500 via-blue-600 to-purple-600 rounded-full shadow-lg">
                            <span class="text-xs font-bold text-white tracking-wider">{{ tempCode }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <div class="flex items-center justify-end gap-3">
                <button
                    @click="closeEditModal"
                    :disabled="isUpdatingName"
                    class="px-6 py-2.5 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-all duration-300 hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    ยกเลิก
                </button>
                <button
                    @click="saveCourseInfo"
                    :disabled="!tempName.trim() || isUpdatingName"
                    class="flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-bold rounded-xl transition-all duration-300 hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl"
                >
                    <Icon v-if="isUpdatingName" icon="svg-spinners:ring-resize" class="w-5 h-5" />
                    <Icon v-else icon="fluent:save-24-filled" class="w-5 h-5" />
                    <span>{{ isUpdatingName ? 'กำลังบันทึก...' : 'บันทึกการแก้ไข' }}</span>
                </button>
            </div>
        </template>
    </DialogModal>
</template>
