<script setup>
import { ref, onMounted, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';
import MainLayout from '~/layouts/main.vue';
import AcademyCoverProfile from '@/components/learn/academy/AcademyCoverProfile.vue';
import AcademyNavbarTab from '@/components/learn/academy/AcademyNavbarTab.vue';

const api = useApi();

const props = defineProps({
    academy: {
        type: Object,
        default: () => ({ data: {} })
    },
    courses: {
        type: Object,
        default: () => ({ data: [] })
    },
    isAcademyAdmin: {
        type: Boolean,
        default: false
    },
    app_url: {
        type: String,
        default: ''
    }
});

const isDarkMode = ref(false);
const members = ref([]);
const pendingRequests = ref([]);
const historyLog = ref([]); // For Accepted/Rejected history if we had an endpoint, currently simulating or fetching all
const isLoading = ref(true);
const isLoadingPending = ref(false);

const activeSection = ref('members'); // 'members' or 'history'

const academyData = ref(props.academy?.data || {});

const tempLogo = ref(academyData.value.logo ? '/storage/images/academies/logos/' + academyData.value.logo : '/storage/images/academies/logos/default_logo.png');
const tempCover = ref(academyData.value.cover ? '/storage/images/academies/covers/' + academyData.value.cover : '/storage/images/academies/covers/default_cover.png');
consttempHeader = ref(academyData.value.name || '');
const tempSubheader = ref(academyData.value.slogan || '');

async function fetchMembers() {
    if (!academyData.value.id) return;
    isLoading.value = true;
    try {
        const response = await api.get(`/api/academies/${academyData.value.id}/members`);
        if (response.success) {
            let allMembers = [];
            if (response.members && response.members.data) {
                allMembers = response.members.data;
            } else {
                allMembers = response.members || [];
            }
            
            // Filter logic
            if (props.isAcademyAdmin) {
                // Admin sees everyone (Active, Pending, Invited, Rejected)
                members.value = allMembers;
            } else {
                // Regular users only see Active members
                members.value = allMembers.filter(m => m.status == 2);
            }
        }
    } catch (error) {
        console.error("Error fetching members:", error);
    } finally {
        isLoading.value = false;
    }
}

// ... (fetchPendingRequests is fine)

// Helper for Status Badge
const getMemberStatus = (member) => {
    switch (String(member.status)) {
        case '1': return { text: 'รออนุมัติ', class: 'bg-yellow-100 text-yellow-800' };
        case '2': return { text: 'นักเรียน', class: 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-300' };
        case '3': return { text: 'ถูกปฏิเสธ', class: 'bg-red-100 text-red-600' };
        case '4': return { text: 'ถูกเชิญ', class: 'bg-gray-100 text-gray-600' };
        default: return { text: 'Unknown', class: 'bg-gray-100 text-gray-600' };
    }
};

// ...

// In Template (Members List Card):
/*
<div v-for="member in members" :key="member.id" ...>
    ...
    <span :class="['inline-block mt-1 px-2 py-0.5 text-[10px] rounded-full', getMemberStatus(member).class]">
        {{ getMemberStatus(member).text }}
    </span>
    ...
</div>
*/

async function fetchPendingRequests() {
    if (!props.isAcademyAdmin || !academyData.value.id) return;
    
    isLoadingPending.value = true;
    try {
        // We reuse pending requests logic
        const response = await api.get(`/api/academies/${academyData.value.id}/pending-requests`);
        if (response.success) {
            pendingRequests.value = response.pendingRequests || [];
        }
    } catch (error) {
        console.error("Error fetching pending requests:", error);
    } finally {
        isLoadingPending.value = false;
    }
}

async function handleAccept(memberId) {
    try {
        const result = await Swal.fire({
            title: 'ยืนยันการรับเข้า?',
            text: "คุณต้องการรับผู้ใช้นี้เข้าโรงเรียนหรือไม่",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'รับเข้าเรียน',
            cancelButtonText: 'ยกเลิก'
        });

        if (result.isConfirmed) {
            const response = await api.post(`/api/academies/${academyData.value.id}/members/${memberId}/accept`, {});
            if (response.success) {
                Swal.fire('สำเร็จ!', 'รับเข้าเรียนเรียบร้อยแล้ว', 'success');
                // Refresh lists
                fetchPendingRequests();
                fetchMembers();
                if (response.totalStudents) {
                    academyData.value.total_students = response.totalStudents;
                }
            }
        }
    } catch (error) {
        console.error("Error accepting member:", error);
        Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถดำเนินการได้', 'error');
    }
}

async function handleReject(memberId) {
    try {
        const result = await Swal.fire({
            title: 'ยืนยันการปฏิเสธ?',
            text: "คุณต้องการปฏิเสธคำขอนี้หรือไม่",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ปฏิเสธคำขอ',
            cancelButtonText: 'ยกเลิก'
        });

        if (result.isConfirmed) {
            const response = await api.post(`/api/academies/${academyData.value.id}/members/${memberId}/reject`, {});
            if (response.success) {
                Swal.fire('สำเร็จ!', 'ปฏิเสธคำขอเรียบร้อยแล้ว', 'success');
                fetchPendingRequests();
                // Optionally add to history log if we maintained it locally
            }
        }
    } catch (error) {
        console.error("Error rejecting member:", error);
        Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถดำเนินการได้', 'error');
    }
}

onMounted(() => {
    fetchMembers();
    if (props.isAcademyAdmin) {
        fetchPendingRequests();
    }
});

const getMemberName = (member) => {
    if (member.user) return member.user.name;
    if (member.student) return `${member.student.first_name_th} ${member.student.last_name_th}`;
    return 'Unknown Member';
};

const getMemberAvatar = (member) => {
    if (member.user && member.user.profile_photo_url) return member.user.profile_photo_url;
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(getMemberName(member))}&color=7F9CF5&background=EBF4FF`;
};

const getMemberCode = (member) => {
    if (member.student) return member.student.student_id;
    if (member.member_code) return member.member_code;
    return '-';
};

// ... existing cover/logo functions ...
async function onCoverImageChange(coverFile) {
    const academyCoverUpdate = new FormData();
    academyCoverUpdate.append('cover', coverFile);
    academyCoverUpdate.append('_method', 'patch');
    await api.post(`/api/academies/${academyData.value.id}/update`, academyCoverUpdate);
}

async function onLogoImageChange(logoFile) {
    const academyLogoUpdate = new FormData();
    academyLogoUpdate.append('logo', logoFile);
    academyLogoUpdate.append('_method', 'patch');
    await api.post(`/api/academies/${academyData.value.id}/update`, academyLogoUpdate);
}

async function onHeaderChange(academyName) {
    await api.patch(`/api/academies/${academyData.value.id}/update`, { name:academyName });
}

async function onSubheaderChange(academySlogan) {
    await api.patch(`/api/academies/${academyData.value.id}/update`, { slogan:academySlogan });
}

async function onRequestToBeAMember(){
    try {
        let memberResp = await api.post(`/api/academies/${academyData.value.id}/members`, {});
        if (memberResp.success) {
            academyData.value.isMember=memberResp.isMember;
            academyData.value.total_students = memberResp.totalStudents;
            fetchMembers();
             if (memberResp.isMember) {
                Swal.fire('เสร็จสิ้น', 'ขอเป็นสมาชิกเรียบร้อยแล้ว', 'success');
            } else {
                Swal.fire('เสร็จสิ้น', 'ออกจากการเป็นสมาชิกเรียบร้อยแล้ว', 'success');
            }
        }
    } catch (error) {
    }
}
</script>

<template>
    <MainLayout title="Academy Members" :appUrl="props.app_url">
        <template #coverProfileCard>
            <div class="">
                <AcademyCoverProfile :coverImage="tempCover" :logoImage="tempLogo" v-model:coverHeader="tempHeader"
                    v-model:coverSubheader="tempSubheader" :model="'Academy'" :modelTable="'academies'"
                    :modelableId="academyData.id" :modelableType="'app/models/Academy'"
                    :modelableRoute="`/academies/${academyData.id}`" :modelData="academyData"
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
            <!-- Admin Tabs -->
            <div v-if="props.isAcademyAdmin" class="flex gap-4 mb-6 border-b border-gray-200 dark:border-gray-700">
                <button @click="activeSection = 'members'" 
                    :class="['px-4 py-2 font-medium text-sm transition-colors border-b-2', activeSection === 'members' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
                    สมาชิกปัจจุบัน ({{ members.length }})
                </button>
                <button @click="activeSection = 'history'"
                    :class="['px-4 py-2 font-medium text-sm transition-colors border-b-2', activeSection === 'history' ? 'border-yellow-500 text-yellow-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
                    ประวัติคำขอ ({{ pendingRequests.length }} รออนุมัติ)
                </button>
            </div>

            <!-- Members List View -->
            <div v-if="activeSection === 'members'">
                <div class="section-header my-4 p-4 bg-white dark:bg-gray-800 rounded-xl shadow-lg flex justify-between items-center">
                    <div class="section-header-info">
                        <h2 class="section-title font-prompt text-xl font-bold dark:text-white">ทำเนียบนักเรียน</h2>
                    </div>
                </div>

                <div v-if="isLoading" class="flex justify-center p-8">
                    <Icon icon="svg-spinners:3-dots-scale" class="w-10 h-10 text-indigo-500" />
                </div>

                <div v-else-if="members.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <div v-for="member in members" :key="member.id" 
                        class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 flex items-center space-x-4 hover:shadow-md transition-shadow">
                        <img :src="getMemberAvatar(member)" alt="Avatar" class="w-12 h-12 rounded-full object-cover ring-2 ring-indigo-100 dark:ring-indigo-900">
                        <div class="overflow-hidden">
                            <h3 class="font-bold text-gray-800 dark:text-gray-200 truncate">{{ getMemberName(member) }}</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                <Icon icon="heroicons:identification" class="w-3 h-3" />
                                {{ getMemberCode(member) }}
                            </p>
                            <span :class="['inline-block mt-1 px-2 py-0.5 text-[10px] rounded-full', getMemberStatus(member).class]">
                                {{ getMemberStatus(member).text }}
                            </span>
                        </div>
                    </div>
                </div>
                 <div v-else class="flex flex-col items-center justify-center p-12 bg-white dark:bg-gray-800 rounded-xl shadow text-center">
                    <p class="text-gray-500">ยังไม่มีสมาชิก</p>
                </div>
            </div>

            <!-- History View (Admin Only) -->
            <div v-else-if="activeSection === 'history' && props.isAcademyAdmin">
                <!-- Pending Requests -->
                <div v-if="pendingRequests.length > 0" class="mb-8">
                    <div class="section-header my-4 p-4 bg-yellow-50 dark:bg-yellow-900/10 border-l-4 border-yellow-400 rounded-r-xl shadow-sm">
                        <h2 class="font-bold text-yellow-800 dark:text-yellow-100 flex items-center gap-2">
                            <Icon icon="heroicons:clock" class="w-5 h-5" />
                            รออนุมัติ ({{ pendingRequests.length }})
                        </h2>
                    </div>
                    
                    <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-xl shadow">
                         <table class="w-full text-sm text-left">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th class="px-6 py-3">ผู้ขอเข้าร่วม</th>
                                    <th class="px-6 py-3">วันที่ส่งคำขอ</th>
                                    <th class="px-6 py-3 text-center">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="request in pendingRequests" :key="request.id" class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td class="px-6 py-4 flex items-center gap-3">
                                        <img :src="getMemberAvatar(request)" class="w-8 h-8 rounded-full">
                                        <span class="font-medium text-gray-900 dark:text-white">{{ getMemberName(request) }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ new Date(request.created_at).toLocaleDateString('th-TH') }}
                                    </td>
                                    <td class="px-6 py-4 flex justify-center gap-2">
                                        <button @click="handleAccept(request.id)" class="text-green-600 hover:text-green-800 bg-green-100 hover:bg-green-200 p-1.5 rounded">
                                            <Icon icon="heroicons:check" class="w-5 h-5" />
                                        </button>
                                        <button @click="handleReject(request.id)" class="text-red-600 hover:text-red-800 bg-red-100 hover:bg-red-200 p-1.5 rounded">
                                            <Icon icon="heroicons:x-mark" class="w-5 h-5" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-else class="p-8 text-center text-gray-500 bg-white dark:bg-gray-800 rounded-xl">
                    <Icon icon="heroicons:check-circle" class="w-12 h-12 mx-auto text-green-500 mb-2" />
                    <p>ไม่มีรายการคำขอใหม่</p>
                </div>
            </div>

        </template>
    </MainLayout>
</template>

