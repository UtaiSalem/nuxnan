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
    <section class="w-full max-w-full min-w-0 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:rounded-2xl">
        <div class="flex min-w-0 flex-col">
            <div class="relative">
                <img
                    :src="coverUrl"
                    alt="Course Cover"
                    class="h-[11rem] w-full object-cover sm:h-[14rem] md:h-[16rem] lg:h-[18rem] xl:h-[20rem]"
                />
                <div class="absolute inset-0 bg-gradient-to-t from-black/35 via-transparent to-black/10"></div>

                <div class="absolute inset-x-0 top-0 flex items-start justify-between gap-3 p-3 sm:p-4">
                    <div v-if="isAdmin">
                        <input type="file" class="hidden" ref="coverInput" accept="image/*" @change="onCoverInputChange">
                        <button
                            type="button"
                            @click.prevent="browseCover"
                            :disabled="isUpdatingCover"
                            class="flex h-10 w-10 items-center justify-center rounded-full border border-white/60 bg-white/90 text-gray-800 shadow-md backdrop-blur transition hover:bg-white active:scale-95 disabled:opacity-50 dark:bg-gray-900/90 dark:text-white"
                            aria-label="เปลี่ยนรูปหน้าปกรายวิชา"
                        >
                            <Icon v-if="isUpdatingCover" icon="svg-spinners:ring-resize" class="h-5 w-5" />
                            <Icon v-else icon="fluent:camera-edit-20-filled" class="h-5 w-5" />
                        </button>
                    </div>
                    <div v-else></div>

                    <div v-if="tuitionFees" class="max-w-[72%] rounded-full border border-white/50 bg-white/95 px-3 py-2 text-gray-900 shadow-md backdrop-blur dark:bg-gray-900/95 dark:text-white">
                        <div class="flex min-w-0 items-center gap-1.5 text-sm font-black leading-none sm:text-base">
                            <Icon icon="ri:bit-coin-fill" class="h-4 w-4 shrink-0 text-amber-500" />
                            <span class="min-w-0 truncate">{{ tuitionFees }}</span>
                            <span class="shrink-0 text-[10px] font-bold text-gray-500 dark:text-gray-400">THB</span>
                        </div>
                    </div>
                </div>
            </div>

                <div class="mx-auto flex w-[90%] min-w-0 flex-col items-center justify-center text-center sm:w-[80%]">
                <div class="relative bottom-[3rem] mx-auto shrink-0 self-center sm:mx-0 sm:self-auto sm:bottom-[4rem] lg:bottom-[5rem]">
                    <div class="h-[7rem] w-[7rem] overflow-hidden rounded-full bg-gray-100 outline outline-2 outline-offset-2 outline-blue-500 dark:bg-gray-800 sm:h-[8rem] sm:w-[8rem] md:h-[10rem] md:w-[10rem] lg:h-[12rem] lg:w-[12rem]">
                        <img :src="logoUrl" alt="Course Logo" class="h-full w-full object-cover">
                    </div>

                    <input type="file" class="hidden" ref="logoInput" accept="image/*" @change="onLogoInputChange" v-if="isAdmin">
                    <button
                        v-if="isAdmin"
                        type="button"
                        @click.prevent="browseLogo"
                        :disabled="isUpdatingLogo"
                        class="absolute -bottom-2 -right-2 flex h-10 w-10 items-center justify-center rounded-full border-4 border-white bg-indigo-600 text-white shadow-md transition active:scale-95 disabled:opacity-50 dark:border-gray-900"
                        aria-label="เปลี่ยนโลโก้รายวิชา"
                    >
                        <Icon v-if="isUpdatingLogo" icon="svg-spinners:ring-resize" class="h-4 w-4" />
                        <Icon v-else icon="fluent:camera-edit-20-filled" class="h-4 w-4" />
                    </button>
                </div>

                    <div class="relative -top-10 w-full min-w-0 px-1 sm:static sm:my-4 sm:mx-4 sm:flex-1 sm:pl-4">
                    <div class="flex min-w-0 items-start justify-center gap-2">
                        <h1 class="min-w-0 break-words text-center text-xl font-black leading-tight text-gray-800 [overflow-wrap:anywhere] dark:text-white sm:text-3xl md:text-3xl lg:text-4xl">
                            {{ courseName || 'ไม่มีชื่อรายวิชา' }}
                        </h1>
                        <button
                            v-if="isAdmin"
                            @click="startEditingName"
                            class="mt-1 shrink-0 rounded-full p-2 text-gray-400 transition hover:bg-gray-100 hover:text-indigo-600 dark:hover:bg-gray-800"
                            aria-label="แก้ไขชื่อรายวิชา"
                        >
                            <Icon icon="fluent:edit-24-filled" class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="mt-2 flex min-w-0 max-w-full flex-wrap items-center justify-center gap-2 text-xs font-semibold text-gray-500 dark:text-gray-400 sm:text-sm">
                        <NuxtLink
                            v-if="courseOwnerName && courseOwnerProfilePath"
                            :to="courseOwnerProfilePath"
                            class="min-w-0 max-w-full break-words text-center hover:text-indigo-600 [overflow-wrap:anywhere]"
                        >
                            {{ courseOwnerName }}
                        </NuxtLink>
                        <span v-else class="min-w-0 max-w-full break-words text-center [overflow-wrap:anywhere]">{{ courseOwnerName || 'ไม่ระบุผู้สอน' }}</span>
                        <span v-if="courseCode" class="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-bold text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-300">
                            #{{ courseCode }}
                        </span>
                        <NuxtLink
                            v-if="academy"
                            :to="`/academies/${academy.id}`"
                            class="min-w-0 max-w-full break-words text-center hover:text-indigo-600 [overflow-wrap:anywhere]"
                        >
                            {{ academy.name }}
                        </NuxtLink>
                    </div>
                </div>
            </div>

            <div class="relative -top-8 mx-auto flex w-[90%] min-w-0 flex-col gap-4 sm:w-[92%] md:-top-6 md:w-[90%] lg:-top-8 lg:w-[90%] xl:w-[80%]">
                <div v-if="!isAdmin" ref="membershipDropdownRef" class="w-full min-w-0 lg:w-auto lg:min-w-[280px] lg:pb-2">
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

                <div class="grid min-w-0 grid-cols-3 divide-x divide-gray-200 rounded-xl border border-gray-200 bg-gray-50 dark:divide-gray-800 dark:border-gray-800 dark:bg-gray-950/60">
                    <div class="flex min-w-0 flex-col items-center gap-1 px-1.5 py-3 text-center sm:flex-row sm:justify-center sm:gap-2 sm:px-2">
                        <Icon icon="heroicons:book-open-solid" class="h-5 w-5 text-blue-600 dark:text-blue-300" />
                        <span class="text-sm font-black text-gray-950 dark:text-white">{{ lessonsCount }}</span>
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400">บทเรียน</span>
                    </div>
                    <div class="flex min-w-0 flex-col items-center gap-1 px-1.5 py-3 text-center sm:flex-row sm:justify-center sm:gap-2 sm:px-2">
                        <Icon icon="heroicons:users-solid" class="h-5 w-5 text-purple-600 dark:text-purple-300" />
                        <span class="text-sm font-black text-gray-950 dark:text-white">{{ enrolledStudents }}</span>
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400">ผู้เรียน</span>
                    </div>
                    <div class="flex min-w-0 flex-col items-center gap-1 px-1.5 py-3 text-center sm:flex-row sm:justify-center sm:gap-2 sm:px-2">
                        <Icon v-if="course?.rating" icon="fluent:star-24-filled" class="h-5 w-5 text-amber-500" />
                        <Icon v-else icon="heroicons:user-group-solid" class="h-5 w-5 text-emerald-600 dark:text-emerald-300" />
                        <span class="text-sm font-black text-gray-950 dark:text-white">
                            {{ course?.rating ? (typeof course.rating === 'number' ? course.rating.toFixed(1) : course.rating) : groupsCount }}
                        </span>
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400">{{ course?.rating ? 'คะแนน' : 'กลุ่ม' }}</span>
                    </div>
                </div>

                <div v-if="courseAcademicMeta.length > 0" class="flex min-w-0 flex-wrap justify-center gap-2 sm:justify-start">
                    <span
                        v-for="(meta, index) in courseAcademicMeta"
                        :key="index"
                        class="max-w-full rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-[11px] font-bold text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                    >
                        {{ meta }}
                    </span>
                </div>
            </div>
        </div>

        <ClientOnly>
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
        </ClientOnly>

        <!-- Edit Course Info Modal -->
        <ClientOnly>
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
        </ClientOnly>
    </section>
</template>
