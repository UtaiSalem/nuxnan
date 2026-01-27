<script setup>
import { ref } from 'vue';
import CourseProfileCoverStandalone from '@/PlearndComponents/learn/courses/CourseProfileCoverStandalone.vue';
import MemberList from '@/PlearndComponents/utils/MemberList.vue';
import NavigationWidgets from '@/PlearndComponents/utils/NavigationWidgets.vue';
// Mock data for testing
const mockCourseData = ref({
    id: 1,
    name: 'วิชาคณิตศาสตร์พื้นฐาน',
    code: 'MATH101',
    cover_header: 'วิชาคณิตศาสตร์พื้นฐาน',
    cover_subheader: 'MATH101 - ปริญญาตรี ชั้นปีที่ 1',
    tuition_fees: 500,
    lessons_count: 12,
    enrolled_students: 45,
    member_status: null, // null = not member, '0' = pending, '1' = active
    isMember: false
});

// Mock course member data
const mockCourseMemberOfAuth = ref(null);

// Mock member data
const mockMemberData = ref({
    id: 1,
    name: 'John Doe',
    title: 'Student',
    avatar: 'https://via.placeholder.com/150'
});

// Mock page props (simulating Inertia.js)
const pageProps = ref({
    isCourseAdmin: true, // Test as admin first
    courseGroups: [
        { id: 1, name: 'กลุ่มเรียน A', image: null, members_count: 25 },
        { id: 2, name: 'กลุ่มเรียน B', image: null, members_count: 30 },
        { id: 3, name: 'กลุ่มเรียน C', image: null, members_count: 15 }
    ],
    auth: {
        user: {
            pp: 1000 // Mock user points
        }
    }
});

// Set up window.page for compatibility with other components
if (typeof window !== 'undefined') {
    window.page = {
        props: pageProps.value
    };
}

// Event handlers for testing
function onCoverImageChange(file) {
    console.log('Cover image changed:', file);
}

function onLogoImageChange(file) {
    console.log('Logo image changed:', file);
}

function onHeaderChange(header) {
    console.log('Header changed:', header);
    mockCourseData.value.cover_header = header;
}

function onSubheaderChange(subheader) {
    console.log('Subheader changed:', subheader);
    mockCourseData.value.cover_subheader = subheader;
}

function onRequestToBeMember(groupId, groupIndex) {
    console.log('Request to be member:', { groupId, groupIndex });
    // Simulate membership request
    mockCourseMemberOfAuth.value = {
        id: 1,
        course_member_status: 0 // pending
    };
    mockCourseData.value.member_status = '0';
    mockCourseData.value.isMember = false;
}

function onRequestToBeUnmember(memberId) {
    console.log('Request to unmember:', memberId);
    // Simulate unmembership
    mockCourseMemberOfAuth.value = null;
    mockCourseData.value.member_status = null;
    mockCourseData.value.isMember = false;
}

// Toggle between admin and student view for testing
function toggleUserRole() {
    if (pageProps.value.isCourseAdmin) {
        pageProps.value.isCourseAdmin = false;
        // Set as student with no membership
        mockCourseMemberOfAuth.value = null;
        mockCourseData.value.member_status = null;
        mockCourseData.value.isMember = false;
    } else {
        pageProps.value.isCourseAdmin = true;
        mockCourseMemberOfAuth.value = null;
        mockCourseData.value.member_status = null;
        mockCourseData.value.isMember = false;
    }
    
    // Update window.page if available
    if (typeof window !== 'undefined') {
        window.page.props.isCourseAdmin = pageProps.value.isCourseAdmin;
    }
}

// Toggle membership status for testing
function toggleMembershipStatus() {
    if (!mockCourseMemberOfAuth.value) {
        // Create pending membership
        mockCourseMemberOfAuth.value = {
            id: 1,
            course_member_status: 0
        };
        mockCourseData.value.member_status = '0';
        mockCourseData.value.isMember = false;
    } else if (mockCourseData.value.member_status === '0') {
        // Change to active membership
        mockCourseMemberOfAuth.value.course_member_status = 1;
        mockCourseData.value.member_status = '1';
        mockCourseData.value.isMember = true;
    } else {
        // Remove membership
        mockCourseMemberOfAuth.value = null;
        mockCourseData.value.member_status = null;
        mockCourseData.value.isMember = false;
    }
}
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-purple-50 flex">
        <!-- Navigation Sidebar - positioned on the left side -->
        <div class="fixed lg:relative top-0 left-0 h-full w-80 z-40">
            <NavigationWidgets />
        </div>
        
        <!-- Main Content Area - positioned to the right of the sidebar -->
        <main class="flex-1 lg:ml-0 ml-0 p-6 relative z-10">
            <div class="max-w-7xl mx-auto">
                <!-- Page Header -->
                <div class="mb-8 text-center">
                    <div class="inline-flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <h1 class="text-4xl font-bold bg-gradient-to-r from-cyan-600 via-blue-600 to-purple-600 bg-clip-text text-transparent">
                            Course Profile Cover Test
                        </h1>
                    </div>
                    <p class="text-gray-600 text-lg">ทดสอบการทำงานของคอมโพเนนต์ Course Profile Cover</p>
                </div>

                <!-- Test Controls Panel -->
                <div class="bg-white/80 backdrop-blur-xl rounded-2xl shadow-2xl p-6 mb-8 border border-white/20">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-8 h-8 bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800">แผงควบคุมการทดสอบ</h2>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Control Buttons -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-700 mb-3">การควบคุม</h3>
                            <div class="flex flex-wrap gap-3">
                                <button 
                                    @click="toggleUserRole"
                                    class="px-6 py-3 bg-gradient-to-r from-blue-500 to-cyan-500 text-white rounded-xl hover:from-blue-600 hover:to-cyan-600 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    {{ pageProps.isCourseAdmin ? 'Admin' : 'Student' }}
                                </button>
                                <button 
                                    @click="toggleMembershipStatus"
                                    class="px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-xl hover:from-green-600 hover:to-emerald-600 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                    </svg>
                                    {{ 
                                        mockCourseData.member_status === null ? 'Not Member' : 
                                        mockCourseData.member_status === '0' ? 'Pending' : 'Active' 
                                    }}
                                </button>
                            </div>
                        </div>

                        <!-- Status Display -->
                        <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-4">
                            <h3 class="text-lg font-semibold text-gray-700 mb-3">สถานะปัจจุบัน</h3>
                            <div class="space-y-2">
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-gray-600">บทบาทผู้ใช้:</span>
                                    <span class="font-semibold px-3 py-1 rounded-full text-sm" 
                                          :class="pageProps.isCourseAdmin ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'">
                                        {{ pageProps.isCourseAdmin ? 'Admin' : 'Student' }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-gray-600">สถานะสมาชิก:</span>
                                    <span class="font-semibold px-3 py-1 rounded-full text-sm"
                                          :class="{
                                              'bg-red-100 text-red-800': mockCourseData.member_status === null,
                                              'bg-yellow-100 text-yellow-800': mockCourseData.member_status === '0',
                                              'bg-green-100 text-green-800': mockCourseData.member_status === '1'
                                          }">
                                        {{ 
                                            mockCourseData.member_status === null ? 'Not Member' : 
                                            mockCourseData.member_status === '0' ? 'Pending Member' : 'Active Member' 
                                        }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-gray-600">หลักสูตร:</span>
                                    <span class="font-medium text-gray-800">{{ mockCourseData.name }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2">
                                    <span class="text-gray-600">ค่าธรรมเนียม:</span>
                                    <span class="font-medium text-gray-800">{{ mockCourseData.tuition_fees }} points</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Component Test Section -->
                <div class="bg-white/80 backdrop-blur-xl rounded-2xl shadow-2xl p-6 mb-8 border border-white/20">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-8 h-8 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800">การทดสอบคอมโพเนนต์</h2>
                    </div>
                    
                    <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-4">
                        <CourseProfileCoverStandalone
                            :cover-image="'/storage/images/banner/banner-profile-stats.jpg'"
                            :logo-image="'/storage/images/plearnd-logo.png'"
                            :cover-header="mockCourseData.cover_header"
                            :cover-subheader="mockCourseData.cover_subheader"
                            model="course"
                            model-table="courses"
                            :modelable-id="mockCourseData.id"
                            modelable-type="course"
                            modelable-route="/courses/1"
                            :model-data="mockCourseData"
                            sub-model-name-th="บทเรียน"
                            :course-member-of-auth="mockCourseMemberOfAuth"
                            @cover-image-change="onCoverImageChange"
                            @logo-image-change="onLogoImageChange"
                            @header-change="onHeaderChange"
                            @subheader-change="onSubheaderChange"
                            @request-tobe-member="onRequestToBeMember"
                            @request-tobe-unmember="onRequestToBeUnmember"
                        />
                    </div>
                </div>

                <!-- Member List Section -->
                <div class="bg-white/80 backdrop-blur-xl rounded-2xl shadow-2xl p-6 border border-white/20">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-8 h-8 bg-gradient-to-r from-emerald-500 to-teal-500 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800">รายการสมาชิก</h2>
                    </div>
                    
                    <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-4">
                        <MemberList />
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
