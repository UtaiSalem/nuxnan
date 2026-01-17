<script setup>
import { ref, onMounted, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';
import MainLayout from '~/layouts/main.vue';
import AcademyCoverProfile from '@/components/learn/academy/AcademyCoverProfile.vue';
import AcademyNavbarTab from '@/components/learn/academy/AcademyNavbarTab.vue';

const props = defineProps({
    academy: Object,
    courses: Object,
    isAcademyAdmin: Boolean,
    app_url: String,
});

const isDarkMode = ref(false);
const members = ref([]);
const isLoading = ref(true);

const tempLogo = ref(props.academy.data.logo? '/storage/images/academies/logos/' + props.academy.data.logo:'/storage/images/academies/logos/default_logo.png');
const tempCover = ref(props.academy.data.cover? '/storage/images/academies/covers/' + props.academy.data.cover : '/storage/images/academies/covers/default_cover.png');
consttempHeader = ref(props.academy.data.name);
const tempSubheader = ref(props.academy.data.slogan);

async function fetchMembers() {
    isLoading.value = true;
    try {
        const response = await axios.get(`/api/academies/${props.academy.data.id}/members`);
        if (response.data.success) {
            // Handle both paginated and non-paginated responses
            if (response.data.members && response.data.members.data) {
                members.value = response.data.members.data;
            } else {
                members.value = response.data.members || [];
            }
        }
    } catch (error) {
        console.error("Error fetching members:", error);
    } finally {
        isLoading.value = false;
    }
}

onMounted(() => {
    fetchMembers();
});

// Helper to get display name
const getMemberName = (member) => {
    if (member.user) return member.user.name;
    if (member.student) return `${member.student.first_name_th} ${member.student.last_name_th}`;
    return 'Unknown Member';
};

// Helper to get avatar
const getMemberAvatar = (member) => {
    if (member.user && member.user.profile_photo_url) return member.user.profile_photo_url;
    // Fallback/Default
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(getMemberName(member))}&color=7F9CF5&background=EBF4FF`;
};

// Helper to get code/id
const getMemberCode = (member) => {
    if (member.student) return member.student.student_id;
    if (member.member_code) return member.member_code;
    return '-';
};

async function onCoverImageChange(coverFile) {
    const academyCoverUpdate = new FormData();
    academyCoverUpdate.append('cover', coverFile); 
    academyCoverUpdate.append('_method', 'patch');
    await axios.post(`/academies/${props.academy.data.id}/update`, academyCoverUpdate);
}

async function onLogoImageChange(logoFile) {
    const academyLogoUpdate = new FormData();
    academyLogoUpdate.append('logo', logoFile); 
    academyLogoUpdate.append('_method', 'patch');
    await axios.post(`/academies/${props.academy.data.id}/update`, academyLogoUpdate);
}

async function onHeaderChange(academyName) {
    await axios.patch(`/academies/${props.academy.data.id}/update`, { name:academyName });
}

async function onSubheaderChange(academySlogan) {
    await axios.patch(`/academies/${props.academy.data.id}/update`, { slogan:academySlogan });
}

async function onRequestToBeAMember(){
    try {
        let memberResp = await axios.post(`/academies/${props.academy.data.id}/members`);
        if (memberResp.data.success) {
            props.academy.data.isMember=memberResp.data.isMember;
            props.academy.data.total_students = memberResp.data.totalStudents;
            fetchMembers(); // Refresh list
             if (memberResp.data.isMember) {
                Swal.fire('เสร็จสิ้น', 'ขอเป็นสมาชิกเรียบร้อยแล้ว', 'success');
            } else {
                Swal.fire('เสร็จสิ้น', 'ออกจากการเป็นสมาชิกเรียบร้อยแล้ว', 'success');
            }
        }
    } catch (error) {
        // Handle error silently
    }
}
</script>

<template>
    <MainLayout title="Academy Members" :appUrl="props.app_url">
        <template #coverProfileCard>
            <div class="">
                <AcademyCoverProfile :coverImage="tempCover" :logoImage="tempLogo" v-model:coverHeader="tempHeader"
                    v-model:coverSubheader="tempSubheader" :model="'Academy'" :modelTable="'academies'"
                    :modelableId="props.academy.data.id" :modelableType="'app/models/Academy'"
                    :modelableRoute="`/academies/${props.academy.data.id}`" :modelData="props.academy.data"
                    :subModelData="props.courses.data" :subModelNameTh="'รายวิชา'"
                    :isAcademyAdmin="props.isAcademyAdmin" @cover-image-change="(data) => { onCoverImageChange(data); }"
                    @logo-image-change="(data) => { onLogoImageChange(data); }"
                    @header-change="(data)=>{ onHeaderChange(data) }"
                    @subheader-change="(data)=>{ onSubheaderChange(data) }"
                    @request-tobe-member="onRequestToBeAMember()"></AcademyCoverProfile>
            </div>

            <AcademyNavbarTab :academy="props.academy" :activeTab="3" />
        </template>
        
        <template #mainContent>
            <div class="section-header my-4 p-4 bg-white rounded-xl shadow-lg flex justify-between items-center">
                <div class="section-header-info">
                    <h2 class="section-title font-prompt text-xl font-bold">สมาชิกทั้งหมด
                        <span class="text-indigo-600 ml-2">{{ members.length }} คน</span>
                    </h2>
                </div>
            </div>

            <div v-if="isLoading" class="flex justify-center p-8">
                <Icon icon="svg-spinners:3-dots-scale" class="w-10 h-10 text-indigo-500" />
            </div>

            <div v-else-if="members.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <div v-for="member in members" :key="member.id" 
                    class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 flex items-center space-x-4 hover:shadow-md transition-shadow">
                    <img :src="getMemberAvatar(member)" alt="Avatar" class="w-12 h-12 rounded-full object-cover ring-2 ring-indigo-100">
                    <div class="overflow-hidden">
                        <h3 class="font-bold text-gray-800 dark:text-gray-200 truncate">{{ getMemberName(member) }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                            <Icon icon="heroicons:identification" class="w-3 h-3" />
                            {{ getMemberCode(member) }}
                        </p>
                        <span class="inline-block mt-1 px-2 py-0.5 text-[10px] rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-300">
                            {{ member.role || 'Student' }}
                        </span>
                    </div>
                </div>
            </div>

            <div v-else class="flex flex-col items-center justify-center p-12 bg-white dark:bg-gray-800 rounded-xl shadow text-center">
                <Icon icon="heroicons:users" class="w-16 h-16 text-gray-300 mb-4" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">ยังไม่มีสมาชิก</h3>
                <p class="text-gray-500 text-sm mt-1">คลิก "เชิญสมาชิก" เพื่อเชิญผู้ใช้เข้าร่วม (ถ้ามีสิทธิ์)</p>
            </div>
        </template>
    </MainLayout>
</template>

