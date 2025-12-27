<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Icon } from '@iconify/vue';

// Props remain the same
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

// Emits remain the same
const emit = defineEmits([
    'update:coverHeader',
    'update:coverSubheader',
    'cover-image-change', 
    'logo-image-change',
    'header-change',
    'subheader-change',
    'request-tobe-member',
    'request-tobe-unmember'
]);

// ===== LOCAL STATE MANAGEMENT (Replacing Pinia Store) =====

// Mock course groups data (replace with props or local data as needed)
const mockCourseGroups = ref([
    { id: 1, name: 'กลุ่มเรียน A', image: null, members_count: 25 },
    { id: 2, name: 'กลุ่มเรียน B', image: null, members_count: 30 },
    { id: 3, name: 'กลุ่มเรียน C', image: null, members_count: 15 }
]);

// Get course groups from props or use mock data
const courseGroups = computed(() => {
    // Try to get from window.page props if available, otherwise use mock data
    if (typeof window !== 'undefined' && window.page?.props?.courseGroups) {
        return window.page.props.courseGroups;
    }
    return mockCourseGroups.value;
});

// Check if user is course admin
const isCourseAdmin = computed(() => {
    // Try to get from window.page props if available
    if (typeof window !== 'undefined' && window.page?.props?.isCourseAdmin !== undefined) {
        return window.page.props.isCourseAdmin;
    }
    // Default to true for testing if not available
    return true;
});

// Check if groups are available and has content
const hasGroups = computed(() => courseGroups.value && courseGroups.value.length > 0);

// Get course ID from props
const courseId = computed(() => props.modelableId || props.modelData?.id);

// Refs for file inputs and dropdown
const logoInput = ref(null);
const coverInput = ref(null);
const dropdownRef = ref(null);
const membershipDropdownRef = ref(null);

// ===== LOCAL UI STATE MANAGEMENT =====

// UI States (replacing store state)
const showOptionGroups = ref(false);
const showAcceptMemberOption = ref(false);
const inputHeaderEditing = ref(false);
const inputSubheaderEditing = ref(false);

// Temporary images for preview
const tempImages = ref({});

// Loading states
const loadingStates = ref({
    update_cover: false,
    update_logo: false,
    update_header: false,
    update_subheader: false,
    request_member: false,
    request_unmember: false
});

// Error states
const errorStates = ref({});

// ===== COMPUTED PROPERTIES (Local Implementation) =====

// Temporary images using local state
const tempLogo = computed(() => {
    const localTempLogo = tempImages.value[courseId.value]?.['logo'];
    return localTempLogo || props.logoImage || '/images/default-logo.png';
});

const tempCover = computed(() => {
    const localTempCover = tempImages.value[courseId.value]?.['cover'];
    return localTempCover || props.coverImage || '/images/default-cover.png';
});

// Loading states computed properties
const isUpdatingCover = computed(() => loadingStates.value.update_cover);
const isUpdatingLogo = computed(() => loadingStates.value.update_logo);
const isUpdatingHeader = computed(() => loadingStates.value.update_header);
const isUpdatingSubheader = computed(() => loadingStates.value.update_subheader);
const isRequestingMember = computed(() => loadingStates.value.request_member);
const isRequestingUnmember = computed(() => loadingStates.value.request_unmember);

// ===== UTILITY FUNCTIONS =====

// Mock notification system (replacing Swal and $toast)
const showNotification = (title, message, type = 'info') => {
    console.log(`[${type.toUpperCase()}] ${title}: ${message}`);
    
    // Create simple notification if DOM is available
    if (typeof document !== 'undefined') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
            type === 'success' ? 'bg-green-500 text-white' :
            type === 'error' ? 'bg-red-500 text-white' :
            type === 'warning' ? 'bg-yellow-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        notification.innerHTML = `
            <div class="font-semibold">${title}</div>
            <div class="text-sm">${message}</div>
        `;
        document.body.appendChild(notification);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 3000);
    }
};

// Mock confirmation dialog
const showConfirmDialog = (title, message) => {
    return confirm(`${title}\n${message}`);
};

// ===== FILE INPUT HANDLERS =====

const browseCover = () => { coverInput.value.click() };
const browseLogo = () => { logoInput.value.click() };

// ===== IMAGE CHANGE HANDLERS (Mock Implementation) =====

async function onCoverInputChange(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    // Create temporary preview URL
    const tempUrl = URL.createObjectURL(file);
    
    // Set temporary image for preview
    if (!tempImages.value[courseId.value]) {
        tempImages.value[courseId.value] = {};
    }
    tempImages.value[courseId.value]['cover'] = tempUrl;
    
    // Emit for backward compatibility
    emit('cover-image-change', file);
    
    try {
        // Mock API call - simulate upload delay
        loadingStates.value.update_cover = true;
        await new Promise(resolve => setTimeout(resolve, 1500));
        
        // Mock success
        showNotification('สำเร็จ', 'อัปเดตรูปปกเรียบร้อยแล้ว', 'success');
        
        // Clear temporary image after successful upload
        if (tempImages.value[courseId.value]) {
            delete tempImages.value[courseId.value]['cover'];
        }
        URL.revokeObjectURL(tempUrl);
        
    } catch (error) {
        console.error('Failed to update cover:', error);
        showNotification('ข้อผิดพลาด', 'ไม่สามารถอัปเดตรูปปกได้', 'error');
        
        // Revert temporary image on error
        if (tempImages.value[courseId.value]) {
            delete tempImages.value[courseId.value]['cover'];
        }
        URL.revokeObjectURL(tempUrl);
    } finally {
        loadingStates.value.update_cover = false;
    }
}

async function onLogoInputChange(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    // Create temporary preview URL
    const tempUrl = URL.createObjectURL(file);
    
    // Set temporary image for preview
    if (!tempImages.value[courseId.value]) {
        tempImages.value[courseId.value] = {};
    }
    tempImages.value[courseId.value]['logo'] = tempUrl;
    
    // Emit for backward compatibility
    emit('logo-image-change', file);
    
    try {
        // Mock API call - simulate upload delay
        loadingStates.value.update_logo = true;
        await new Promise(resolve => setTimeout(resolve, 1500));
        
        // Mock success
        showNotification('สำเร็จ', 'อัปเดตโลโก้เรียบร้อยแล้ว', 'success');
        
        // Clear temporary image after successful upload
        if (tempImages.value[courseId.value]) {
            delete tempImages.value[courseId.value]['logo'];
        }
        URL.revokeObjectURL(tempUrl);
        
    } catch (error) {
        console.error('Failed to update logo:', error);
        showNotification('ข้อผิดพลาด', 'ไม่สามารถอัปเดตโลโก้ได้', 'error');
        
        // Revert temporary image on error
        if (tempImages.value[courseId.value]) {
            delete tempImages.value[courseId.value]['logo'];
        }
        URL.revokeObjectURL(tempUrl);
    } finally {
        loadingStates.value.update_logo = false;
    }
}

// ===== HEADER EDITING HANDLERS (Mock Implementation) =====

async function onHeaderSave(){
    try {
        loadingStates.value.update_header = true;
        
        // Mock API call - simulate update delay
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        // Mock success
        inputHeaderEditing.value = false;
        emit('header-change', props.coverHeader);
        showNotification('สำเร็จ', 'อัปเดตหัวข้อเรียบร้อยแล้ว', 'success');
        
    } catch (error) {
        console.error('Failed to update header:', error);
        showNotification('ข้อผิดพลาด', 'ไม่สามารถอัปเดตหัวข้อได้', 'error');
    } finally {
        loadingStates.value.update_header = false;
    }
}

async function onSubheaderSave(e){
    e.preventDefault();
    try {
        loadingStates.value.update_subheader = true;
        
        // Mock API call - simulate update delay
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        // Mock success
        inputSubheaderEditing.value = false;
        emit('subheader-change', props.coverSubheader);
        showNotification('สำเร็จ', 'อัปเดตหัวข้อรองเรียบร้อยแล้ว', 'success');
        
    } catch (error) {
        console.error('Failed to update subheader:', error);
        showNotification('ข้อผิดพลาด', 'ไม่สามารถอัปเดตหัวข้อรองได้', 'error');
    } finally {
        loadingStates.value.update_subheader = false;
    }
}

// ===== MEMBERSHIP HANDLERS (Mock Implementation) =====

async function onRequestToBeMember(groupId, groupIndx){
    // Prevent multiple requests
    if (isRequestingMember.value) return;
    
    try {
        loadingStates.value.request_member = true;
        
        // Mock API call - simulate processing delay
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        // Mock success - simulate checking points and processing
        const mockUserPoints = 1000; // Mock user points
        const mockTuitionFees = props.modelData?.tuition_fees || 500;
        
        if (mockUserPoints < mockTuitionFees) {
            showNotification('ขออภัย', 'คุณมีแต้มสะสมไม่เพียงพอที่จะสมัครเข้าร่วมกลุ่มรายวิชานี้ , กรุณาเติมแต้มสะสมก่อน', 'warning');
            return;
        }
        
        // Mock successful membership request
        showOptionGroups.value = false;
        emit('request-tobe-member', groupId, groupIndx);
        showNotification('เสร็จสิ้น', 'ขอเป็นสมาชิกเรียบร้อยแล้ว', 'success');
        
    } catch (error) {
        console.error('Failed to request membership:', error);
        showNotification('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการขอเข้าร่วมกลุ่ม', 'error');
    } finally {
        loadingStates.value.request_member = false;
    }
}

async function onRequestToBeUnMember(){
    if (!props.courseMemberOfAuth?.id) return;
    
    // Prevent multiple requests
    if (isRequestingUnmember.value) return;
    
    // Show confirmation dialog for active members
    if (props.modelData.member_status === '1') {
        const confirmed = showConfirmDialog('ยืนยันการออกจากรายวิชา', 'คุณต้องการออกจากรายวิชานี้ใช่หรือไม่?');
        if (!confirmed) return;
    }
    
    try {
        loadingStates.value.request_unmember = true;
        
        // Mock API call - simulate processing delay
        await new Promise(resolve => setTimeout(resolve, 1500));
        
        // Mock successful unmembership
        showAcceptMemberOption.value = false;
        emit('request-tobe-unmember', props.courseMemberOfAuth.id);
        showNotification('เสร็จสิ้น', 'ออกจากรายวิชาเรียบร้อยแล้ว', 'success');
        
    } catch (error) {
        console.error('Failed to cancel membership:', error);
        showNotification('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการออกจากรายวิชา', 'error');
    } finally {
        loadingStates.value.request_unmember = false;
    }
}

// ===== UI STATE HANDLERS =====

function toggleOptionGroups() {
    showOptionGroups.value = !showOptionGroups.value;
}

function toggleAcceptMemberOption() {
    showAcceptMemberOption.value = !showAcceptMemberOption.value;
}

function startHeaderEditing() {
    inputHeaderEditing.value = true;
}

function cancelHeaderEditing() {
    inputHeaderEditing.value = false;
}

function startSubheaderEditing() {
    inputSubheaderEditing.value = true;
}

function cancelSubheaderEditing() {
    inputSubheaderEditing.value = false;
}

// ===== EVENT LISTENERS =====

// Close dropdowns when clicking outside
function handleClickOutside(event) {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        showOptionGroups.value = false;
    }
    if (membershipDropdownRef.value && !membershipDropdownRef.value.contains(event.target)) {
        showAcceptMemberOption.value = false;
    }
}

// Close dropdowns when pressing Escape key
function handleEscapeKey(event) {
    if (event.key === 'Escape' || event.keyCode === 27) {
        showOptionGroups.value = false;
        showAcceptMemberOption.value = false;
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
    if (tempImages.value[courseId.value]) {
        Object.values(tempImages.value[courseId.value]).forEach(url => {
            if (url) URL.revokeObjectURL(url);
        });
        delete tempImages.value[courseId.value];
    }
    
    // Remove event listeners
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleEscapeKey);
});

// Watch for membership request success to close dropdown
watch(isRequestingMember, (newVal, oldVal) => {
    if (oldVal && !newVal) {
        // Request completed, close dropdown
        showOptionGroups.value = false;
    }
});
</script>

<template>
    <div class="relative w-full bg-white bg-cover rounded-lg ">
        <div class="">
            <img :src="tempCover" alt="" class="w-full rounded-t-lg h-36 sm:h-52 md:h-72 lg:h-70">
            <div id="cover-input-label" class="absolute top-2 left-2 md:h-28 lg:h-60" v-if="isCourseAdmin">
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
                    <input type="file" class="hidden" accept="image/*" ref="logoInput" @change="onLogoInputChange" v-if="isCourseAdmin" >
                    <button type="button" @click.prevent="browseLogo" v-if="isCourseAdmin" :disabled="isUpdatingLogo"
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
                                    <button  @click="startHeaderEditing" class="" v-if="isCourseAdmin">
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
                            <p v-else-if="isCourseAdmin" class="p-2 mx-4 text-lg font-normal text-center rounded-md min-h-fit bg-slate-300 bg-opacity-80 sm:mx-0 sm:text-start">{{ 'แก้ไขรหัสวิชา' }}</p>
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
                                    <button  @click="startSubheaderEditing" class="" v-if="isCourseAdmin">
                                        <Icon icon="heroicons:pencil-square"
                                            class="w-6 h-6 p-1 text-gray-700 bg-gray-200 rounded-full bg-opacity-60" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div  v-if="!isCourseAdmin" class="hidden md:block space-x-2 mx-4 w-[31%] justify-end">
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
                                    <span class="ml-1 text-xs opacity-75">({{ courseGroups.value?.length || 0 }})</span>
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
                            
                            <!-- ตัวเลือก: ไม่เข้ากลุ่ม -->
                            <button
                                @click.prevent="onRequestToBeMember(null, null)" :disabled="isRequestingMember"
                                class="py-2.5 px-3 font-semibold text-gray-500 bg-gray-50 border-b hover:bg-gray-100 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-150">
                                <span class="flex items-center justify-start">
                                    // <Icon v-if="isRequestingMember" icon="eos-icons:loading" class="w-5 h-5 animate-spin mr-2" />
                                    <Icon icon="heroicons-outline:minus-circle" class="w-5 h-5 mr-2" />
                                    ไม่สังกัดกลุ่ม
                                </span>
                            </button>
                            
                            <!-- ตัวเลือก: เข้ากลุ่มต่างๆ -->
                            <button
                                v-for="(group, index) in courseGroups" :key="group.id"
                                @click.prevent="onRequestToBeMember(group.id, index)" :disabled="isRequestingMember"
                                class="py-2.5 px-3 font-semibold text-gray-600 bg-white border-b hover:bg-cyan-50 hover:text-cyan-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-150"
                                :class="{'rounded-b-md border-b-0': index === courseGroups.length - 1}">
                                <span class="flex items-center justify-between">
                                    <span class="flex items-center flex-1 min-w-0">
                                        // <Icon v-if="isRequestingMember" icon="eos-icons:loading" class="w-5 h-5 animate-spin mr-2 flex-shrink-0" />
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
                <div class="flex flex-col items-center justify-center">
                    <h3 class="text-2xl font-bold text-bluePrimary">{{ props.modelData.lessons_count }}</h3>
                    <p class="text-sm font-normal text-lightSecondary">{{ props.subModelNameTh }}</p>
                </div>
                <div class="flex flex-col items-center justify-center" v-if="props.modelData.enrolled_students">
                    <h3 class="text-2xl font-bold text-bluePrimary">{{ props.modelData.enrolled_students }}</h3>
                    <p class="text-sm font-normal text-lightSecondary">ผู้เรียน</p>
                </div>
            </div>

            <!-- Mobile version - ระบบสมาชิก -->
            <div v-if="!isCourseAdmin" class="justify-center m-2 space-x-4 md:hidden">
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
                            // <Icon v-if="isRequestingMember" icon="eos-icons:loading" class="w-5 h-5 animate-spin mr-2" />
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
                                    <Icon icon="heroicons-outline:user-add" class="w-5 h-5 mr-2" />
                                    ขอเป็นสมาชิก
                                    <span class="ml-1 text-xs opacity-75">({{ courseGroups.value?.length || 0 }})</span>
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
                            
                            <!-- ตัวเลือก: ไม่เข้ากลุ่ม -->
                            <button
                                @click.prevent="onRequestToBeMember(null, null)" :disabled="isRequestingMember"
                                class="py-2.5 px-3 font-semibold text-gray-500 bg-gray-50 border-b active:bg-gray-200 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-150">
                                <span class="flex items-center justify-start">
                                    <Icon v-if="isRequestingMember" icon="eos-icons:loading" class="w-5 h-5 animate-spin mr-2" />
                                    <Icon v-else icon="heroicons-outline:minus-circle" class="w-5 h-5 mr-2" />
                                    ไม่สังกัดกลุ่ม
                                </span>
                            </button>
                            
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