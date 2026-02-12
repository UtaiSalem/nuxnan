<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Icon } from '@iconify/vue';
import { usePage } from '@inertiajs/vue3';
import { useCourseProfileStore } from '@/stores/courseProfile.js';

const props = defineProps({
    coverImage: { type: String, default: null },
    logoImage: { type: String, default: null },
    coverHeader: { type: String, default: null },
    coverSubheader: { type: String, default: null },
    model: String,
    modelTable: String,
    modelableId: Number,
    modelableType: String,
    modelableRoute: String,
    modelData: Object,

    subModelNameTh: String,

    courseMemberOfAuth: Object,
});

const emit = defineEmits([
    'update:coverHeader',
    'update:coverSubheader',
    'update:coverImage',
    'update:logoImage',
    'header-change',
    'subheader-change',
    'request-tobe-member',
    'request-tobe-unmember'
]);

// Initialize store
const courseProfileStore = useCourseProfileStore();

// Get course groups from Inertia props with ref for proper reactivity
const courseGroups = ref(usePage().props.courseGroups || []);

// Check if groups are available and has content
const hasGroups = computed(() => courseGroups.value && courseGroups.value.length > 0);

// Get course ID from props
const courseId = computed(() => props.modelableId || props.modelData?.id);

// Refs for file inputs and dropdown
const logoInput = ref(null);
const coverInput = ref(null);
const dropdownRef = ref(null);
const membershipDropdownRef = ref(null);

// Computed properties using store
// const showOptionGroups = computed(() => courseProfileStore.getShowOptionGroups());
const showOptionGroups = ref(courseProfileStore.getShowOptionGroups());
// const showOptionGroups = ref(false);
const showAcceptMemberOption = computed(() => courseProfileStore.getShowAcceptMemberOption(courseId.value));
const inputHeaderEditing = computed(() => courseProfileStore.getInputHeaderEditing(courseId.value));
const inputSubheaderEditing = computed(() => courseProfileStore.getInputSubheaderEditing(courseId.value));

// Temporary images using store
const tempLogo = computed(() => {
    const storeTempLogo = courseProfileStore.getTempImage(courseId.value, 'logo');
    
    // If there's a temporary image (blob URL), use it for immediate preview
    if (storeTempLogo) {
        return storeTempLogo;
    }
    
    // Otherwise, use the prop image
    if (props.logoImage) {
        // Return as-is - the parent already provides the correct path
        return props.logoImage;
    }
    
    return '/images/default-logo.png';
});

const tempCover = computed(() => {
    const storeTempCover = courseProfileStore.getTempImage(courseId.value, 'cover');
    
    // If there's a temporary image (blob URL), use it for immediate preview
    if (storeTempCover) {
        return storeTempCover;
    }
    
    // Otherwise, use the prop image
    if (props.coverImage) {
        // Return as-is - the parent already provides the correct path
        return props.coverImage;
    }
    
    return '/images/default-cover.png';
});

// Loading states
const isUpdatingCover = computed(() => courseProfileStore.isLoading(`update_cover_${courseId.value}`));
const isUpdatingLogo = computed(() => courseProfileStore.isLoading(`update_logo_${courseId.value}`));
const isUpdatingHeader = computed(() => courseProfileStore.isLoading(`update_header_${courseId.value}`));
const isUpdatingSubheader = computed(() => courseProfileStore.isLoading(`update_subheader_${courseId.value}`));
const isRequestingMember = computed(() => courseProfileStore.isLoading(`request_member_${courseId.value}`));
const isRequestingUnmember = computed(() => courseProfileStore.isLoading(`request_unmember_${courseId.value}`));

// File input handlers
const browseCover = () => { coverInput.value.click() };
const browseLogo = () => { logoInput.value.click() };

// Image change handlers
async function onCoverInputChange(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    // Create temporary preview URL
    const tempUrl = URL.createObjectURL(file);
    courseProfileStore.setTempImage(courseId.value, 'cover', tempUrl);
    
    try {
        // Update via store and get the new image path
        const newImagePath = await courseProfileStore.updateCourseCover(courseId.value, file);
        
        // Emit the new path to parent component
        emit('update:coverImage', newImagePath);
        
        // Clear temporary image after a delay to ensure smooth transition
        setTimeout(() => {
            courseProfileStore.clearTempImage(courseId.value, 'cover');
            URL.revokeObjectURL(tempUrl);
        }, 1000);
    } catch (error) {
        console.error('Failed to update cover:', error);
        // Revert temporary image on error
        courseProfileStore.clearTempImage(courseId.value, 'cover');
        URL.revokeObjectURL(tempUrl);
    }
}

async function onLogoInputChange(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    // Create temporary preview URL
    const tempUrl = URL.createObjectURL(file);
    courseProfileStore.setTempImage(courseId.value, 'logo', tempUrl);
    
    try {
        // Update via store and get the new image path
        const newImagePath = await courseProfileStore.updateCourseLogo(courseId.value, file);
        
        // Emit the new path to parent component
        emit('update:logoImage', newImagePath);
        
        // Clear temporary image after a delay to ensure smooth transition
        setTimeout(() => {
            courseProfileStore.clearTempImage(courseId.value, 'logo');
            URL.revokeObjectURL(tempUrl);
        }, 1000);
    } catch (error) {
        console.error('Failed to update logo:', error);
        // Revert temporary image on error
        courseProfileStore.clearTempImage(courseId.value, 'logo');
        URL.revokeObjectURL(tempUrl);
    }
}

// Header editing handlers
async function onHeaderSave(){
    try {
        await courseProfileStore.updateCourseHeader(courseId.value, props.coverHeader);
        courseProfileStore.setInputHeaderEditing(courseId.value, false);
        emit('header-change', props.coverHeader);
    } catch (error) {
        console.error('Failed to update header:', error);
    }
}

async function onSubheaderSave(e){
    e.preventDefault();
    try {
        await courseProfileStore.updateCourseSubheader(courseId.value, props.coverSubheader);
        courseProfileStore.setInputSubheaderEditing(courseId.value, false);
        emit('subheader-change', props.coverSubheader);
    } catch (error) {
        console.error('Failed to update subheader:', error);
    }
}

// Membership handlers
async function onRequestToBeMember(groupId, groupIndx){
    // Prevent multiple requests
    if (isRequestingMember.value) return;
    
    try {
        await courseProfileStore.requestToBeMember(courseId.value, groupId);
        courseProfileStore.setShowOptionGroups(false);
        emit('request-tobe-member', groupId, groupIndx);
    } catch (error) {
        console.error('Failed to request membership:', error);
    }
}

async function onRequestToBeUnMember(){
    if (!props.courseMemberOfAuth?.id) return;
    
    // Prevent multiple requests
    if (isRequestingUnmember.value) return;
    
    // Show confirmation dialog for active members
    if (props.modelData.member_status === '1') {
        const confirmed = confirm('คุณต้องการออกจากรายวิชานี้ใช่หรือไม่?');
        if (!confirmed) return;
    }
    
    try {
        await courseProfileStore.requestToBeUnmember(courseId.value, props.courseMemberOfAuth.id);
        courseProfileStore.setShowAcceptMemberOption(courseId.value, false);
        emit('request-tobe-unmember', props.courseMemberOfAuth.id);
    } catch (error) {
        console.error('Failed to cancel membership:', error);
    }
}

// UI state handlers
function toggleOptionGroups() {
    showOptionGroups.value = !showOptionGroups.value;
    courseProfileStore.setShowOptionGroups(showOptionGroups.value);
}

function toggleAcceptMemberOption() {
    courseProfileStore.setShowAcceptMemberOption(courseId.value, !showAcceptMemberOption.value);
}

function startHeaderEditing() {
    courseProfileStore.setInputHeaderEditing(courseId.value, true);
}

function cancelHeaderEditing() {
    courseProfileStore.setInputHeaderEditing(courseId.value, false);
}

function startSubheaderEditing() {
    courseProfileStore.setInputSubheaderEditing(courseId.value, true);
}

function cancelSubheaderEditing() {
    courseProfileStore.setInputSubheaderEditing(courseId.value, false);
}

// Close dropdowns when clicking outside
function handleClickOutside(event) {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        courseProfileStore.setShowOptionGroups(courseId.value, false);
    }
    if (membershipDropdownRef.value && !membershipDropdownRef.value.contains(event.target)) {
        courseProfileStore.setShowAcceptMemberOption(courseId.value, false);
    }
}

// Close dropdowns when pressing Escape key
function handleEscapeKey(event) {
    if (event.key === 'Escape' || event.keyCode === 27) {
        courseProfileStore.setShowOptionGroups(courseId.value, false);
        courseProfileStore.setShowAcceptMemberOption(courseId.value, false);
    }
}

// Setup click outside and escape key listeners
onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleEscapeKey);
});

// Cleanup on unmount
onUnmounted(() => {
    // Clear temporary images when component is unmounted
    courseProfileStore.clearAllTempImages(courseId.value);
    // Remove event listeners
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleEscapeKey);
});

// Watch for membership request success to close dropdown
watch(isRequestingMember, (newVal, oldVal) => {
    if (oldVal && !newVal) {
        // Request completed, close dropdown
        courseProfileStore.setShowOptionGroups(courseId.value, false);
    }
});
</script>

<template>
    <div class="relative w-full bg-white bg-cover rounded-lg ">
        <div class="">
            <img :src="tempCover" alt="" class="w-full rounded-t-lg h-36 sm:h-52 md:h-72 lg:h-70">
            <div id="cover-input-label" class="absolute top-2 left-2 md:h-28 lg:h-60" v-if="$page.props.isCourseAdmin">
                <input type="file" class="hidden" ref="coverInput" accept="image/*" @change="onCoverInputChange">
                <button type="button" @click.prevent="browseCover" :disabled="isUpdatingCover"
                    class="p-1 text-gray-600 bg-white rounded-full bg-opacity-60 disabled:opacity-50">
                    <Icon v-if="isUpdatingCover" icon="eos-icons:loading" class="w-6 h-6 animate-spin" />
                    <Icon v-else icon="heroicons-outline:photograph" class="w-6 h-6" />
                </button>
            </div>
            <div id="cover-tuition-fees" class="absolute top-0 right-0 md:h-28 lg:h-60">
                <p class="flex items-center px-3 py-1 space-x-1 font-semibold text-white rounded-tr-lg rounded-bl-lg  bg-yellow-300/75">
                    <Icon icon="ri:bit-coin-line" class="w-6 h-6 " />
                    <span class="text-lg">
                        {{ props.modelData.tuition_fees }} 
                    </span>
                </p>
            </div>

        </div>
        <div class="flex flex-col sm:flex-row items-center justify-center sm:justify-between w-full px-4 -mt-[52px] sm:-mt[-54-px] md:-mt-[64px] lg:-mt-[84px]">
            <div class="flex flex-col sm:flex-row items-center w-[69%]">
                <!-- <div class="relative max-w-fit w-[88px] h-[88px] sm:w-28 sm:h-28 md:w-32 md:h-32 lg:w-40 lg:h-40 flex justify-center items-center border-gray-400 border-2 rounded-full overflow-hidden"> -->
                <div class="relative min-w-fit flex justify-center items-center border-white border-[4px] rounded-full overflow-hidden ">
                    <img class="bg-cover object-center w-[88px] h-[88px] sm:w-28 sm:h-28 md:w-32 md:h-32 lg:w-40 lg:h-40" :src="tempLogo" alt='school logo'>
                    <input type="file" class="hidden" accept="image/*" ref="logoInput" @change="onLogoInputChange" v-if="$page.props.isCourseAdmin" >
                    <button type="button" @click.prevent="browseLogo" v-if="$page.props.isCourseAdmin" :disabled="isUpdatingLogo"
                        class="absolute bottom-0 bg-white bg-opacity-60 text-gray-600 border-[1px] border-gray-300 transition duration-200 rounded-full md:p-1 disabled:opacity-50">
                        <Icon v-if="isUpdatingLogo" icon="eos-icons:loading" class="w-4 h-4 animate-spin" />
                        <Icon v-else icon="heroicons:camera" class="w-4 h-4 " />
                    </button>
                </div>
                <div class="w-full space-y-4 text-center sm:ml-4 md:text-start">
                    <div class="relative flex justify-center w-full sm:justify-start ">
                        <div class="relative " :class="{'w-full':inputHeaderEditing}">
                            <input type="text" name="" :value="coverHeader" @input="$emit('update:coverHeader', $event.target.value)" v-if="inputHeaderEditing"
                                    id="coverHeaderNameInput"
                                    class="bg-white bg-opacity-60 !w-full rounded-md bg-transparent text-center sm:text-start font-semibold text-base sm:text-xl focus:outline-0"
                            >
                            <p v-else class="p-2 text-base font-semibold rounded-md bg-slate-300 bg-opacity-80 sm:text-xl max-w-fit">{{ coverHeader }}</p>
                            <!-- <div class=""> -->
                                <div v-if="inputHeaderEditing" class="absolute top-0 flex flex-col-reverse -right-6 ">
                                    <button  @click.prevent="onHeaderSave" :disabled="isUpdatingHeader" class="">
                                        <Icon v-if="isUpdatingHeader" icon="eos-icons:loading" class="w-6 h-6 p-1 text-gray-700 bg-green-400 border border-white rounded-full bg-opacity-60 animate-spin" />
                                        <Icon v-else icon="fluent:save-24-regular"
                                            class="w-6 h-6 p-1 text-gray-700 bg-green-400 border border-white rounded-full bg-opacity-60" />
                                    </button>
                                    <button  @click="cancelHeaderEditing" class="">
                                        <Icon icon="heroicons-outline:x"
                                            class="w-6 h-6 p-1 text-gray-700 bg-red-400 border border-white rounded-full bg-opacity-60" />
                                    </button>
                                </div>
                                <div v-else class="absolute -top-4 -right-2 sm:-right-6 sm:top-0">
                                    <button  @click="startHeaderEditing" class="" v-if=" $page.props.isCourseAdmin">
                                        <Icon icon="heroicons:pencil-square"
                                            class="w-6 h-6 p-1 text-gray-700 bg-white rounded-full bg-opacity-60" />
                                    </button>
                                </div>
                            <!-- </div> -->
                        </div>
                    </div>
                    <div class="flex justify-center sm:justify-start">
                        <div class="relative" :class="{'w-full': inputSubheaderEditing}">
                            <textarea rows="1"
                            v-if="inputSubheaderEditing" 
                            :value="coverSubheader"
                            @input="emit('update:coverSubheader', $event.target.value)"
                            class="w-full text-lg font-normal text-center rounded-lg sm:text-start"
                            ></textarea>
                            <p v-else-if="coverSubheader" class="p-2 mx-4 text-lg font-normal text-center rounded-md min-h-fit bg-slate-300 bg-opacity-80 sm:mx-0 sm:text-start">{{ coverSubheader }}</p>
                            <p v-else-if="$page.props.isCourseAdmin" class="p-2 mx-4 text-lg font-normal text-center rounded-md min-h-fit bg-slate-300 bg-opacity-80 sm:mx-0 sm:text-start">{{ 'แก้ไขรหัสวิชา' }}</p>
                            <div class="absolute top-0 -right-6">
                            <!-- <div class="absolute top-0 -right-1 sm:-right-6"> -->
                                <div v-if="inputSubheaderEditing" class="flex flex-col-reverse">
                                    <button  @click="onSubheaderSave" :disabled="isUpdatingSubheader" class="">
                                        <Icon v-if="isUpdatingSubheader" icon="eos-icons:loading" class="w-6 h-6 p-1 text-gray-700 bg-green-400 rounded-full bg-opacity-60 animate-spin" />
                                        <Icon v-else icon="fluent:save-24-regular"
                                            class="w-6 h-6 p-1 text-gray-700 bg-green-400 rounded-full bg-opacity-60" />
                                    </button>
                                    <button  @click="cancelSubheaderEditing" class="">
                                        <Icon icon="heroicons-outline:x"
                                            class="w-6 h-6 p-1 text-gray-700 bg-red-400 rounded-full bg-opacity-60 " />
                                    </button>
                                </div>
                                <div v-else class="">
                                    <button  @click="startSubheaderEditing" class="" v-if="$page.props.isCourseAdmin">
                                        <Icon icon="heroicons:pencil-square"
                                            class="w-6 h-6 p-1 text-gray-700 bg-gray-200 rounded-full bg-opacity-60" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div  v-if="!$page.props.isCourseAdmin" class="hidden md:block space-x-2 mx-4 w-[31%] justify-end">
                <!-- สถานะรอการตอบรับ -->
                <div v-if="props.courseMemberOfAuth && props.modelData.member_status==='0'" ref="membershipDropdownRef" class="relative w-56 py-2 ml-auto cursor-pointer group">
                    <div class="flex justify-center w-full bg-yellow-500 rounded-md shadow-md hover:shadow-lg transition-all duration-200">
                        <button class="flex items-center justify-between w-full py-2 px-3 text-base font-medium text-white hover:bg-yellow-600 transition-colors duration-200" 
                            @click.prevent="toggleAcceptMemberOption">
                            <span class="flex items-center">
                                <Icon icon="heroicons:clock" class="w-5 h-5 mr-2" />
                                รอการตอบรับ
                            </span>
                            <Icon 
                                icon="heroicons:chevron-down" 
                                class="w-5 h-5 transition-transform duration-300" 
                                :class="{'rotate-180': showAcceptMemberOption}" 
                            />
                        </button>
                    </div>
                    <div :class="showAcceptMemberOption ? 'visible opacity-100 translate-y-0': 'invisible opacity-0 -translate-y-2'"
                        class="absolute z-[80] flex w-full flex-col mt-1 text-gray-800 shadow-xl bg-white rounded-md transition-all duration-300 border border-red-200">
                        <button @click.prevent="onRequestToBeUnMember" :disabled="isRequestingUnmember"
                            class="py-2.5 px-3 font-semibold text-red-600 bg-red-50 hover:bg-red-100 rounded-md hover:text-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-150">
                            <span class="flex items-center justify-center">
                                <Icon v-if="isRequestingUnmember" icon="eos-icons:loading" class="w-5 h-5 animate-spin mr-2" />
                                <Icon v-else icon="heroicons:x-circle" class="w-5 h-5 mr-2" />
                                ยกเลิกคำขอ
                            </span>
                        </button>                       
                    </div>
                </div>

                <!-- ปุ่มขอเป็นสมาชิก -->
                <div v-else-if="!props.courseMemberOfAuth" ref="dropdownRef" class="relative w-56 py-2 ml-auto cursor-pointer group">
                    <!-- กรณีไม่มีกลุ่มเลย -->
                    <button v-if="!hasGroups"
                        @click.prevent="onRequestToBeMember(null, null)" :disabled="isRequestingMember"
                        class="w-full py-2 text-base font-medium text-white rounded-lg bg-cyan-500 hover:bg-cyan-600 disabled:opacity-50 transition-all duration-200 shadow-md hover:shadow-lg">
                        <span class="flex items-center justify-center">
                            <Icon v-if="isRequestingMember" icon="eos-icons:loading" class="w-5 h-5 animate-spin mr-2" />
                            <Icon v-else icon="heroicons-outline:user-add" class="w-5 h-5 mr-2" />
                            ขอเป็นสมาชิก
                        </span>
                    </button>

                    <!-- กรณีมีกลุ่ม -->
                    <div v-else>
                        <div class="flex justify-center w-full rounded-md bg-cyan-500 shadow-md hover:shadow-lg transition-all duration-200">
                            <button class="flex items-center justify-between w-full py-2 px-3 text-base font-medium text-white hover:bg-cyan-600 transition-colors duration-200"
                                @click.prevent="toggleOptionGroups">
                                <span class="flex items-center">
                                    <Icon v-if="isRequestingMember" icon="eos-icons:loading" class="w-5 h-5 animate-spin mr-2" />
                                    <Icon v-else icon="heroicons-outline:user-add" class="w-5 h-5 mr-2" />
                                    ขอเป็นสมาชิก
                                </span>
                                <Icon
                                    icon="heroicons:chevron-down"
                                    class="w-5 h-5 transition-transform duration-300"
                                    :class="{'rotate-180': showOptionGroups}"
                                />
                            </button>
                        </div>

                        <!-- Dropdown เลือกกลุ่ม -->
                        <div
                            :class="showOptionGroups ? 'visible opacity-100 translate-y-0': 'invisible opacity-0 -translate-y-2'"
                            class="absolute z-[80] flex w-full flex-col mt-1 text-gray-800 shadow-xl bg-white rounded-md transition-all duration-300 border border-gray-200 max-h-64 overflow-y-auto">
                            
                            <!-- Header ของ Dropdown -->
                            <div class="sticky top-0 bg-gradient-to-b from-gray-50 to-gray-100 px-3 py-2 border-b border-gray-200 z-10">
                                <p class="text-xs font-medium text-gray-600">เลือกกลุ่มที่ต้องการเข้าร่วม</p>
                            </div>
                            
                            <!-- ตัวเลือก: ไม่เข้ากลุ่ม
                            <button
                                @click.prevent="onRequestToBeMember(null, null)" :disabled="isRequestingMember"
                                class="py-2.5 px-3 font-semibold text-gray-500 bg-gray-50 border-b hover:bg-gray-100 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-150">
                                <span class="flex items-center justify-start">
                                    <Icon icon="heroicons-outline:minus-circle" class="w-5 h-5 mr-2" />
                                    ไม่สังกัดกลุ่ม
                                </span>
                            </button> -->
                            
                            <!-- ตัวเลือก: เข้ากลุ่มต่างๆ -->
                            <button
                                v-for="(group, index) in courseGroups" :key="group.id"
                                @click.prevent="onRequestToBeMember(group.id, index)" :disabled="isRequestingMember"
                                class="py-2.5 px-3 font-semibold text-gray-600 bg-white border-b hover:bg-cyan-50 hover:text-cyan-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-150"
                                :class="{'rounded-b-md border-b-0': index === courseGroups.length - 1}">
                                <span class="flex items-center justify-between">
                                    <span class="flex items-center flex-1 min-w-0">
                                        <img v-if="group.image" :src="group.image" :alt="group.name" class="w-6 h-6 mr-2 rounded-full object-cover border border-gray-200 flex-shrink-0" />
                                        <Icon v-else icon="heroicons-outline:user-group" class="w-5 h-5 mr-2 flex-shrink-0" />
                                        <span class="truncate">{{ group.name }}</span>
                                    </span>
                                    <span v-if="group.members_count" class="ml-2 text-xs text-gray-400 flex-shrink-0">
                                        ({{ group.members_count }})
                                    </span>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="my-3 md:flex justify-center gap-4 md:!gap-14">
            <div class="flex justify-center space-x-4 md:space-x-10">
                <div class="flex flex-col items-center justify-center p-4 rounded-lg bg-gradient-to-br from-blue-50 to-cyan-50 shadow-sm hover:shadow-md transition-all duration-300 min-w-[100px]">
                    <div class="flex items-center justify-center w-12 h-12 mb-2 bg-blue-500 rounded-full">
                        <Icon icon="heroicons:book-open" class="w-6 h-6 text-white" />
                    </div>
                    <h3 class="text-2xl font-bold text-blue-600">{{ props.modelData.lessons_count }}</h3>
                    <p class="text-sm font-medium text-gray-600">{{ props.subModelNameTh }}</p>
                </div>
                <div class="flex flex-col items-center justify-center p-4 rounded-lg bg-gradient-to-br from-purple-50 to-pink-50 shadow-sm hover:shadow-md transition-all duration-300 min-w-[100px]" v-if="props.modelData.enrolled_students">
                    <div class="flex items-center justify-center w-12 h-12 mb-2 bg-purple-500 rounded-full">
                        <Icon icon="heroicons:users" class="w-6 h-6 text-white" />
                    </div>
                    <h3 class="text-2xl font-bold text-purple-600">{{ props.modelData.enrolled_students }}</h3>
                    <p class="text-sm font-medium text-gray-600">ผู้เรียน</p>
                </div>
                <div class="flex flex-col items-center justify-center p-4 rounded-lg bg-gradient-to-br from-green-50 to-emerald-50 shadow-sm hover:shadow-md transition-all duration-300 min-w-[100px]" v-if="props.modelData.groups">
                    <div class="flex items-center justify-center w-12 h-12 mb-2 bg-green-500 rounded-full">
                        <Icon icon="heroicons:user-group" class="w-6 h-6 text-white" />
                    </div>
                    <h3 class="text-2xl font-bold text-green-600">{{ props.modelData.groups }}</h3>
                    <p class="text-sm font-medium text-gray-600">กลุ่ม/ห้อง</p>
                </div>
            </div>

            <!-- Mobile version - ระบบสมาชิก -->
            <div v-if="!$page.props.isCourseAdmin" class="justify-center m-2 space-x-4 md:hidden">
                <!-- สถานะรอการตอบรับ (Mobile) -->
                <div v-if="props.courseMemberOfAuth && props.modelData.member_status==='0'" class="relative w-56 py-2 mx-auto cursor-pointer group">
                    <div class="flex justify-center w-full bg-yellow-500 rounded-md shadow-md transition-all duration-200">
                        <button class="flex items-center justify-between w-full py-2 px-3 text-base font-medium text-white active:bg-yellow-600 transition-colors duration-200" 
                            @click.prevent="toggleAcceptMemberOption">
                            <span class="flex items-center">
                                <Icon icon="heroicons:clock" class="w-5 h-5 mr-2" />
                                รอการตอบรับ
                            </span>
                            <Icon 
                                icon="heroicons:chevron-down" 
                                class="w-5 h-5 transition-transform duration-300" 
                                :class="{'rotate-180': showAcceptMemberOption}" 
                            />
                        </button>
                    </div>
                    <div :class="showAcceptMemberOption ? 'visible opacity-100 translate-y-0': 'invisible opacity-0 -translate-y-2'"
                        class="absolute z-[80] flex w-full flex-col mt-1 text-gray-800 shadow-xl bg-white rounded-md transition-all duration-300 border border-red-200">
                        <button @click.prevent="onRequestToBeUnMember" :disabled="isRequestingUnmember"
                            class="py-2.5 px-3 font-semibold text-red-600 bg-red-50 active:bg-red-200 rounded-md hover:text-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-150">
                            <span class="flex items-center justify-center">
                                <Icon v-if="isRequestingUnmember" icon="eos-icons:loading" class="w-5 h-5 animate-spin mr-2" />
                                <Icon v-else icon="heroicons:x-circle" class="w-5 h-5 mr-2" />
                                ยกเลิกคำขอ
                            </span>
                        </button>                       
                    </div>
                </div>

                <!-- ปุ่มออกจากสมาชิก (Mobile) -->
                <button v-else-if="props.courseMemberOfAuth && props.modelData.member_status==='1'" 
                    @click.prevent="onRequestToBeUnMember" :disabled="isRequestingUnmember"
                    class="w-56 mx-auto py-2 px-3 text-gray-700 bg-yellow-300 rounded-md active:bg-red-500 active:text-white disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-md active:shadow-sm">
                    <span class="flex items-center justify-center">
                        <Icon v-if="isRequestingUnmember" icon="eos-icons:loading" class="w-5 h-5 animate-spin mr-2" />
                        <Icon v-else icon="majesticons:door-exit-line" class="w-5 h-5 mr-2" /> 
                        <span class="text-base font-medium">
                            ออกจากสมาชิก
                        </span>
                    </span>
                </button>

                <!-- ปุ่มขอเป็นสมาชิก (Mobile) -->
                <div v-else-if="!props.courseMemberOfAuth" class="relative w-56 py-2 mx-auto cursor-pointer group">
                    <!-- กรณีไม่มีกลุ่มเลย (Mobile) -->
                    <button v-if="!hasGroups"
                        @click.prevent="onRequestToBeMember(null, null)" :disabled="isRequestingMember"
                        class="w-full py-2 text-base font-medium text-white rounded-lg bg-cyan-500 hover:bg-cyan-600 active:bg-cyan-700 disabled:opacity-50 transition-all duration-200 shadow-md active:shadow-sm">
                        <span class="flex items-center justify-center">
                            <Icon v-if="isRequestingMember" icon="eos-icons:loading" class="w-5 h-5 animate-spin mr-2" />
                            <Icon icon="heroicons-outline:user-add" class="w-5 h-5 mr-2" />
                            ขอเป็นสมาชิก
                        </span>
                    </button>

                    <!-- กรณีมีกลุ่ม (Mobile) -->
                    <div v-else>
                        <div class="flex justify-center w-full rounded-md bg-cyan-500 shadow-md transition-all duration-200">
                            <button class="flex items-center justify-between w-full py-2 px-3 text-base font-medium text-white active:bg-cyan-600 transition-colors duration-200"
                                @click.prevent="toggleOptionGroups">
                                <span class="flex items-center">
                                    <Icon v-if="isRequestingMember" icon="eos-icons:loading" class="w-5 h-5 animate-spin mr-2" />
                                    <Icon v-else icon="heroicons-outline:user-add" class="w-5 h-5 mr-2" />
                                    ขอเป็นสมาชิก
                                </span>
                                <Icon
                                    icon="heroicons:chevron-down"
                                    class="w-5 h-5 transition-transform duration-300"
                                    :class="{'rotate-180': showOptionGroups}"
                                />
                            </button>
                        </div>

                        <!-- Dropdown เลือกกลุ่ม (Mobile) -->
                        <div
                            :class="showOptionGroups ? 'visible opacity-100 translate-y-0': 'invisible opacity-0 -translate-y-2'"
                            class="absolute z-[80] flex w-full flex-col mt-1 text-gray-800 shadow-xl bg-white rounded-md transition-all duration-300 border border-gray-200 max-h-64 overflow-y-auto">
                            
                            <!-- Header ของ Dropdown -->
                            <div class="sticky top-0 bg-gradient-to-b from-gray-50 to-gray-100 px-3 py-2 border-b border-gray-200 z-10">
                                <p class="text-xs font-medium text-gray-600">เลือกกลุ่มที่ต้องการเข้าร่วม</p>
                            </div>
                            
                            <!-- ตัวเลือก: ไม่เข้ากลุ่ม 
                            <button @click.prevent="onRequestToBeMember(null, null)" :disabled="isRequestingMember" class="py-2.5 px-3 font-semibold text-gray-500 bg-gray-50 border-b active:bg-gray-200 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-150">
                                <span class="flex items-center justify-start">
                                    <Icon icon="heroicons-outline:minus-circle" class="w-5 h-5 mr-2" />
                                    ไม่สังกัดกลุ่ม
                                </span>
                            </button> 
                            -->
                            
                            <!-- ตัวเลือก: เข้ากลุ่มต่างๆ -->
                            <button
                                v-for="(group, index) in courseGroups" :key="group.id"
                                @click.prevent="onRequestToBeMember(group.id, index)" :disabled="isRequestingMember"
                                class="py-2.5 px-3 font-semibold text-gray-600 bg-white border-b active:bg-cyan-100 hover:text-cyan-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-150"
                                :class="{'rounded-b-md border-b-0': index === courseGroups.length - 1}">
                                <span class="flex items-center justify-between">
                                    <span class="flex items-center flex-1 min-w-0">
                                        <img v-if="group.image" :src="group.image" :alt="group.name" class="w-6 h-6 mr-2 rounded-full object-cover border border-gray-200 flex-shrink-0" />
                                        <Icon v-else icon="heroicons-outline:user-group" class="w-5 h-5 mr-2 flex-shrink-0" />
                                        <span class="truncate">{{ group.name }}</span>
                                    </span>
                                    <span v-if="group.members_count" class="ml-2 text-xs text-gray-400 flex-shrink-0">
                                        ({{ group.members_count }})
                                    </span>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
