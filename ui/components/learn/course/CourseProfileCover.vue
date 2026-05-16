<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Icon } from '@iconify/vue';
import CourseCoverActionGroup from './CourseCoverActionGroup.vue';
import AcademyCoursePurchaseModal from '~/components/academy/CoursePurchaseModal.vue';

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
const courseGroupStore = useCourseGroupStore();

// Get data from stores
const course = computed(() => courseStore.currentCourse);
const academy = computed(() => courseStore.academy);
const isAdmin = computed(() => courseStore.isCourseAdmin);

// Computed course data
const courseId = computed(() => course.value?.id);
const courseName = computed(() => course.value?.name || '');
const courseCode = computed(() => course.value?.code || '');
const courseOwner = computed(() => course.value?.user || course.value?.owner || null);
const courseOwnerName = computed(() => courseOwner.value?.name || courseOwner.value?.username || '');
const courseOwnerProfilePath = computed(() => {
    if (!courseOwner.value) return null;
    return `/profile/${courseOwner.value.reference_code || courseOwner.value.id}`;
});
const tuitionFees = computed(() => course.value?.tuition_fees);
const lessonsCount = computed(() => course.value?.course_lessons_count ?? course.value?.lessons_count ?? course.value?.lessons ?? 0);
const enrolledStudents = computed(() => course.value?.enrolled_students ?? 0);
const groupsCount = computed(() => course.value?.groups ?? 0);
const memberStatus = computed(() => props.courseMemberOfAuth?.status || course.value?.member_status);

// Academic Meta
const educationLevelLabel = computed(() => course.value?.education_level);
const educationYearLabel = computed(() => course.value?.education_year ? `ปีที่ ${course.value.education_year}` : null);
const semesterLabel = computed(() => course.value?.semester ? `ภาคเรียนที่ ${course.value.semester}` : null);
const academicYearLabel = computed(() => course.value?.academic_year ? `ปีการศึกษา ${course.value.academic_year}` : null);

const courseAcademicMeta = computed(() => {
    const meta = [];
    if (educationLevelLabel.value) {
        if (educationYearLabel.value) {
            meta.push(`ระดับชั้น: ${educationLevelLabel.value} ${educationYearLabel.value}`);
        } else {
            meta.push(`ระดับชั้น: ${educationLevelLabel.value}`);
        }
    }
    if (semesterLabel.value) meta.push(semesterLabel.value);
    if (academicYearLabel.value) meta.push(academicYearLabel.value);
    return meta;
});

// Refs for file inputs and dropdown
const logoInput = ref(null);
const coverInput = ref(null);
const membershipDropdownRef = ref(null);

// UI States
const showAcceptMemberOption = ref(false);
const showEditModal = ref(false);
const showGroupSelector = ref(false);
const showCopyPurchaseModal = ref(false);
const selectedGroupId = ref(null);
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
        if (course.value.cover.startsWith('http')) return course.value.cover;
        return `${config.public.apiBase}/storage/images/courses/covers/${course.value.cover}`;
    }
    return `${config.public.apiBase}/storage/images/courses/covers/default_cover.jpg`;
});

const logoUrl = computed(() => {
    if (logoPreview.value) return logoPreview.value;
    if (course.value?.logo) {
        if (course.value.logo.startsWith('http')) return course.value.logo;
        return `${config.public.apiBase}/storage/images/courses/logos/${course.value.logo}`;
    }
    if (course.value?.user?.avatar) return course.value.user.avatar;
    return '/images/default-avatar.png';
});

const courseGroups = computed(() => courseGroupStore.groups || []);
const hasMultipleGroups = computed(() => courseGroups.value.length > 1);
const courseJoinPrice = computed(() => Number(course.value?.tuition_fees ?? course.value?.price ?? 0));
const canPurchaseCopy = computed(() => Boolean(course.value?.is_for_marketplace));

function formatMoney(value) {
    return new Intl.NumberFormat('th-TH', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    }).format(Number(value) || 0);
}

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

function startEditingName() {
    openEditModal();
}

async function saveCourseInfo() {
    if (!tempName.value.trim()) return;
    isUpdatingName.value = true;
    try {
        const data = { name: tempName.value.trim(), code: tempCode.value.trim() };
        await api.put(`/api/courses/${courseId.value}`, data);
        courseStore.updateCourse(data);
        emit('refresh');
        closeEditModal();
    } catch (error) {
        console.error('Failed to update course info:', error);
    } finally {
        isUpdatingName.value = false;
    }
}

function onCopyPurchaseSuccess() {
    showCopyPurchaseModal.value = false;
    emit('refresh', true);
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
        if (response.cover) courseStore.updateCourse({ cover: response.cover });
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
        if (response.logo) courseStore.updateCourse({ logo: response.logo });
        emit('refresh');
    } catch (error) {
        console.error('Failed to update logo:', error);
        logoPreview.value = null;
    } finally {
        isUpdatingLogo.value = false;
    }
}

// Membership handlers
function openMembershipRequest() {
    if (isRequestingMember.value) return;
    if (hasMultipleGroups.value) {
        selectedGroupId.value = courseGroups.value[0]?.id ?? null;
        showGroupSelector.value = true;
        return;
    }
    const groupId = courseGroups.value.length === 1 ? courseGroups.value[0].id : null;
    requestToBeMember(groupId);
}

async function requestToBeMember(groupId = null) {
    if (!courseId.value || isRequestingMember.value) return;
    if (courseJoinPrice.value > 0) {
        const confirmed = confirm(`ยืนยันสมัครสมาชิกในรายวิชานี้ ค่าเรียน ฿${formatMoney(courseJoinPrice.value)}?`);
        if (!confirmed) return;
    }
    isRequestingMember.value = true;
    try {
        const payload = groupId ? { group_id: groupId } : {};
        const response = await api.post(`/api/courses/${courseId.value}/members`, payload);
        courseStore.updateCourse({
            isMember: true,
            member_status: response.memberStatus ?? response.newCourseMember?.status ?? null
        });
        showGroupSelector.value = false;
        emit('request-member', groupId);
        emit('refresh', true);
    } catch (error) {
        console.error('Failed to request membership:', error);
    } finally {
        isRequestingMember.value = false;
    }
}

function confirmGroupMembership() {
    requestToBeMember(selectedGroupId.value);
}

async function onRequestToBeUnMember() {
    if (!props.courseMemberOfAuth?.id) return;
    if (isRequestingUnmember.value) return;
    if (memberStatus.value === '1' || memberStatus.value === 'active') {
        const confirmed = confirm('คุณต้องการออกจากรายวิชานี้ใช่หรือไม่?');
        if (!confirmed) return;
    }
    isRequestingUnmember.value = true;
    try {
        await api.delete(`/api/courses/${courseId.value}/members/${props.courseMemberOfAuth.id}`);
        courseStore.updateCourse({ isMember: false, member_status: null });
        showAcceptMemberOption.value = false;
        emit('request-unmember', props.courseMemberOfAuth.id);
        emit('refresh');
    } catch (error) {
        console.error('Failed to cancel membership:', error);
    } finally {
        isRequestingUnmember.value = false;
    }
}

function toggleAcceptMemberOption() {
    showAcceptMemberOption.value = !showAcceptMemberOption.value;
}

function handleClickOutside(event) {
    if (membershipDropdownRef.value && !membershipDropdownRef.value.contains(event.target)) {
        showAcceptMemberOption.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    if (coverPreview.value) URL.revokeObjectURL(coverPreview.value);
    if (logoPreview.value) URL.revokeObjectURL(logoPreview.value);
});
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
* { transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); }
</style>

<template>
    <div class="relative w-full bg-white dark:bg-gray-900 rounded-xl sm:rounded-2xl overflow-visible shadow-xl sm:shadow-2xl border border-gray-100 dark:border-gray-800 transition-all duration-300">
        <!-- 1. Cover Photo Section (overflow-hidden) -->
        <div 
            class="relative h-48 sm:h-64 md:h-80 lg:h-[320px] bg-cover bg-center bg-no-repeat transition-all duration-500 overflow-hidden rounded-t-xl sm:rounded-t-2xl z-0"
            :style="{ backgroundImage: `url(${coverUrl})` }"
        >
            <!-- Enhanced Overlay gradient -->
            <div class="absolute inset-0 bg-gradient-to-br from-blue-600/10 via-purple-600/5 to-pink-600/10 dark:from-blue-900/20 dark:via-purple-900/10 dark:to-pink-900/20 z-0"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent z-0"></div>
            
            <!-- Edit Cover Button (Admin Only) -->
            <div class="absolute top-4 left-4 z-10" v-if="isAdmin">
                <input type="file" class="hidden" ref="coverInput" accept="image/*" @change="onCoverInputChange">
                <button type="button" @click.prevent="browseCover" :disabled="isUpdatingCover"
                    class="group relative p-2 text-gray-700 dark:text-gray-200 bg-white/90 dark:bg-gray-800/90 backdrop-blur-md rounded-lg active:scale-95 transition-all duration-300 disabled:opacity-50 shadow-lg border border-white/20 min-w-[40px] min-h-[40px] flex items-center justify-center">
                    <Icon v-if="isUpdatingCover" icon="svg-spinners:ring-resize" class="w-5 h-5" />
                    <Icon v-else icon="fluent:camera-edit-20-filled" class="w-5 h-5" />
                </button>
            </div>
            
            <!-- Tuition Fees Badge -->
            <div v-if="tuitionFees" class="absolute top-4 right-4 z-10">
                <div class="relative flex items-center px-4 py-2 space-x-2 font-black text-white rounded-xl bg-gradient-to-r from-yellow-400 to-orange-500 shadow-xl border border-yellow-300/30">
                    <Icon icon="ri:bit-coin-fill" class="w-5 h-5" />
                    <span class="text-base sm:text-xl">{{ tuitionFees }}</span>
                    <span class="text-xs opacity-90 uppercase">THB</span>
                </div>
            </div>
        </div>

        <!-- 2. Profile Main Section (Avatar + Actions) -->
        <div class="relative px-4 sm:px-8 pb-4">
            <div class="flex flex-col lg:flex-row items-center lg:items-end gap-6 relative">
                <!-- Avatar Block (Centered/Bottom-aligned) -->
                <div class="relative -mt-16 sm:-mt-24 lg:-mt-28 flex-shrink-0 z-30 order-1">
                    <div class="relative w-32 h-32 sm:w-44 sm:h-44 lg:w-48 lg:h-48 rounded-3xl border-[6px] border-white dark:border-gray-900 overflow-hidden bg-white dark:bg-gray-800 shadow-2xl transition-all duration-300 group">
                        <img :src="logoUrl" alt="Course Logo" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-black/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    </div>
                    
                    <input type="file" class="hidden" ref="logoInput" accept="image/*" @change="onLogoInputChange" v-if="isAdmin">
                    <button v-if="isAdmin" type="button" @click.prevent="browseLogo" :disabled="isUpdatingLogo"
                        class="absolute bottom-2 right-2 p-2.5 bg-indigo-600 text-white rounded-2xl active:scale-95 transition-all shadow-xl border-4 border-white dark:border-gray-900 z-10">
                        <Icon v-if="isUpdatingLogo" icon="svg-spinners:ring-resize" class="w-5 h-5" />
                        <Icon v-else icon="fluent:camera-edit-20-filled" class="w-5 h-5" />
                    </button>
                </div>

                <!-- Owner Info (Beside Logo on Right) -->
                <div v-if="courseOwnerName" class="flex flex-col items-center lg:items-start lg:mb-5 lg:text-left order-2 lg:min-w-[180px] max-w-full">
                    <NuxtLink v-if="courseOwnerProfilePath" :to="courseOwnerProfilePath" class="max-w-[280px] text-center lg:text-left text-2xl sm:text-3xl lg:text-4xl leading-tight font-black text-gray-900 dark:text-white hover:text-indigo-600 transition-colors break-words">
                        <span>{{ courseOwnerName }}</span>
                    </NuxtLink>
                    <span v-else class="max-w-[280px] text-center lg:text-left text-2xl sm:text-3xl lg:text-4xl leading-tight font-black text-gray-900 dark:text-white break-words">{{ courseOwnerName }}</span>
                </div>

                <!-- Floating Actions (Desktop right-aligned) -->
                <div v-if="!isAdmin" ref="membershipDropdownRef" class="lg:absolute lg:right-0 lg:bottom-0 w-full lg:w-auto z-30 mt-4 lg:mt-0 order-3">
                    <CourseCoverActionGroup
                        :course="course"
                        :courseMemberOfAuth="courseMemberOfAuth"
                        :memberStatus="memberStatus"
                        :isRequestingMember="isRequestingMember"
                        :isRequestingUnmember="isRequestingUnmember"
                        :canPurchaseCopy="canPurchaseCopy"
                        :showAcceptMemberOption="showAcceptMemberOption"
                        @request-member="openMembershipRequest"
                        @purchase-course="showCopyPurchaseModal = true"
                        @cancel-member="onRequestToBeUnMember"
                        @toggle-pending-menu="toggleAcceptMemberOption"
                    />
                </div>
            </div>
        </div>

        <!-- 3. Profile Info row (Stats + Title) -->
        <div class="px-4 sm:px-8 py-6 border-t border-gray-50 dark:border-gray-800/50">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <!-- Left/Center: Stats & Course Info -->
                <div class="flex flex-col gap-4 flex-1">
                    <!-- Stats Bar -->
                    <div class="flex items-center gap-4 text-[11px] sm:text-sm font-bold text-gray-500 dark:text-gray-400 overflow-x-auto no-scrollbar pb-1">
                        <div class="flex items-center gap-1.5 whitespace-nowrap bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 px-3 py-1.5 rounded-full">
                            <Icon icon="heroicons:book-open-solid" class="w-4 h-4" />
                            <span>{{ lessonsCount }} บทเรียน</span>
                        </div>
                        <div class="flex items-center gap-1.5 whitespace-nowrap bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 px-3 py-1.5 rounded-full">
                            <Icon icon="heroicons:users-solid" class="w-4 h-4" />
                            <span>{{ enrolledStudents }} ผู้เรียน</span>
                        </div>
                        <div class="flex items-center gap-1.5 whitespace-nowrap bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 px-3 py-1.5 rounded-full">
                            <Icon icon="heroicons:user-group-solid" class="w-4 h-4" />
                            <span>{{ groupsCount }} กลุ่ม</span>
                        </div>
                        <div v-if="course?.rating" class="flex items-center gap-1.5 whitespace-nowrap bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 px-3 py-1.5 rounded-full">
                            <Icon icon="fluent:star-24-filled" class="w-4 h-4" />
                            <span>{{ typeof course.rating === 'number' ? course.rating.toFixed(1) : course.rating }} ({{ course.reviews_count || 0 }})</span>
                        </div>
                    </div>

                    <!-- Course Title & Code -->
                    <div class="space-y-1">
                        <div class="flex items-center gap-3 flex-wrap">
                            <h1 class="text-xl sm:text-3xl font-black text-gray-900 dark:text-white tracking-tight">
                                {{ courseName || 'ไม่มีชื่อรายวิชา' }}
                            </h1>
                            <button v-if="isAdmin" @click="startEditingName"
                                class="p-1.5 text-gray-400 hover:text-indigo-600 transition-colors">
                                <Icon icon="fluent:edit-24-filled" class="w-5 h-5" />
                            </button>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span v-if="courseCode" class="text-xs sm:text-sm font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 px-2 py-0.5 rounded-md">
                                #{{ courseCode }}
                            </span>
                            <NuxtLink v-if="academy" :to="`/academies/${academy.id}`" class="text-xs sm:text-sm font-semibold text-gray-500 hover:text-indigo-600 transition-colors">
                                <span v-if="courseCode" class="mr-1">•</span>
                                {{ academy.name }}
                            </NuxtLink>
                        </div>

                        <!-- Academic Meta Badges -->
                        <div v-if="courseAcademicMeta.length > 0" class="flex flex-wrap items-center gap-2 pt-2">
                            <span v-for="(meta, index) in courseAcademicMeta" :key="index"
                                class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] sm:text-xs font-semibold bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-700">
                                {{ meta }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div
                v-if="showGroupSelector"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                @click.self="showGroupSelector = false"
            >
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
                <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-gray-800 shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-black text-gray-900 dark:text-white">เลือกกลุ่มเรียน</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">เลือกกลุ่มที่ต้องการสมัครเข้าร่วม</p>
                            </div>
                            <button
                                type="button"
                                @click="showGroupSelector = false"
                                class="p-2 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-gray-200 transition-colors"
                            >
                                <Icon icon="heroicons:x-mark" class="w-5 h-5" />
                            </button>
                        </div>
                    </div>

                    <div class="p-5 space-y-3 max-h-[50vh] overflow-y-auto">
                        <label
                            v-for="group in courseGroups"
                            :key="group.id"
                            class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-colors"
                            :class="selectedGroupId === group.id ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600'"
                        >
                            <input
                                v-model="selectedGroupId"
                                type="radio"
                                :value="group.id"
                                class="text-blue-600 focus:ring-blue-500"
                            >
                            <div class="flex-1 min-w-0">
                                <div class="font-bold text-gray-900 dark:text-white truncate">{{ group.name || `กลุ่ม ${group.id}` }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ group.members_count || group.members?.length || 0 }} สมาชิก
                                </div>
                            </div>
                        </label>
                    </div>

                    <div class="p-5 bg-gray-50 dark:bg-gray-900/50 flex gap-3">
                        <button
                            type="button"
                            @click="showGroupSelector = false"
                            class="flex-1 px-4 py-3 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-bold hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                        >
                            ยกเลิก
                        </button>
                        <button
                            type="button"
                            @click="confirmGroupMembership"
                            :disabled="!selectedGroupId || isRequestingMember"
                            class="flex-1 px-4 py-3 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-700 transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
                        >
                            <Icon v-if="isRequestingMember" icon="svg-spinners:ring-resize" class="w-5 h-5" />
                            <span>สมัครกลุ่มนี้</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <AcademyCoursePurchaseModal
            v-if="course"
            :course="course"
            :visible="showCopyPurchaseModal"
            @close="showCopyPurchaseModal = false"
            @success="onCopyPurchaseSuccess"
        />

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
    </div>
</template>
