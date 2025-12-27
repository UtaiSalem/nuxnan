<script setup>
import { ref, computed, onMounted } from 'vue';
import { useCourseStore } from '@/pages/v2/store/modules/course/courseStore';
import { useGroupStore } from '@/pages/v2/store/modules/group/groupStore';
import { useMemberStore } from '@/pages/v2/store/modules/member/memberStore';
import CourseHeader from '@/pages/v2/components/course/CourseHeader.vue';
import CourseSidebar from '@/pages/v2/components/course/CourseSidebar.vue';

const props = defineProps({
    courseId: {
        type: Number,
        required: true,
    },
});

// Stores
const courseStore = useCourseStore();
const groupStore = useGroupStore();
const memberStore = useMemberStore();

// State
const loading = ref(true);
const error = ref(null);
const selectedGroupId = ref(null);
const attendanceRecords = ref([]);
const showCheckInModal = ref(false);
const selectedDate = ref(new Date().toISOString().split('T')[0]);
const attendanceMode = ref('manual'); // 'manual' or 'auto'
const checkInData = ref({
    date: selectedDate.value,
    group_id: null,
    members: [],
});

// Computed
const course = computed(() => courseStore.currentCourse);
const groups = computed(() => groupStore.groupsArray);
const selectedGroup = computed(() => 
    groups.value.find(g => g.id === selectedGroupId.value)
);
const groupMembers = computed(() => {
    if (!selectedGroupId.value) return [];
    // This would filter members by group
    return memberStore.membersArray.filter(m => 
        m.group_id === selectedGroupId.value
    );
});

const attendanceStats = computed(() => {
    const total = checkInData.value.members.length;
    const present = checkInData.value.members.filter(m => m.status === 'present').length;
    const absent = checkInData.value.members.filter(m => m.status === 'absent').length;
    const late = checkInData.value.members.filter(m => m.status === 'late').length;
    
    return { total, present, absent, late };
});

const attendanceHistory = computed(() => {
    // Group attendance records by date
    const grouped = {};
    attendanceRecords.value.forEach(record => {
        if (!grouped[record.date]) {
            grouped[record.date] = [];
        }
        grouped[record.date].push(record);
    });
    
    return Object.entries(grouped)
        .map(([date, records]) => ({
            date,
            records,
            stats: {
                total: records.length,
                present: records.filter(r => r.status === 'present').length,
                absent: records.filter(r => r.status === 'absent').length,
                late: records.filter(r => r.status === 'late').length,
            }
        }))
        .sort((a, b) => new Date(b.date) - new Date(a.date));
});

// Methods
const loadAttendanceData = async () => {
    try {
        loading.value = true;
        
        // Load groups and members
        await Promise.all([
            groupStore.fetchGroups(props.courseId),
            memberStore.fetchMembers(props.courseId),
        ]);
        
        // Set default group if available
        if (groups.value.length > 0 && !selectedGroupId.value) {
            selectedGroupId.value = groups.value[0].id;
        }
        
        // Load attendance records
        // attendanceRecords.value = await courseStore.fetchAttendanceRecords(props.courseId);
        
        // Mock data for now
        attendanceRecords.value = [
            {
                id: 1,
                date: '2023-12-01',
                member_id: 1,
                member_name: 'นายสมชาย ใจดี',
                group_name: 'กลุ่ม A',
                status: 'present',
                check_in_time: '08:55:00',
                checked_by: 'อาจารย์สมศรี ศึกษา',
            },
            {
                id: 2,
                date: '2023-12-01',
                member_id: 2,
                member_name: 'นางสาวสมหญิง รักเรียน',
                group_name: 'กลุ่ม A',
                status: 'late',
                check_in_time: '09:15:00',
                checked_by: 'อาจารย์สมศรี ศึกษา',
            },
            {
                id: 3,
                date: '2023-12-01',
                member_id: 3,
                member_name: 'นายวิชัย มานะ',
                group_name: 'กลุ่ม B',
                status: 'absent',
                check_in_time: null,
                checked_by: 'อาจารย์สมศรี ศึกษา',
            }
        ];
        
    } catch (err) {
        error.value = err.message;
        console.error('Failed to load attendance data:', err);
    } finally {
        loading.value = false;
    }
};

const openCheckInModal = () => {
    if (!selectedGroupId.value) {
        alert('กรุณาเลือกกลุ่มก่อนเช็คชื่อ');
        return;
    }
    
    checkInData.value = {
        date: selectedDate.value,
        group_id: selectedGroupId.value,
        members: groupMembers.value.map(member => ({
            id: member.id,
            name: member.name,
            status: 'present', // Default to present
            note: '',
        })),
    };
    
    showCheckInModal.value = true;
};

const saveAttendance = async () => {
    try {
        // Validate
        if (!checkInData.value.group_id) {
            alert('กรุณาเลือกกลุ่ม');
            return;
        }
        
        // Save attendance records
        // await courseStore.saveAttendanceRecords(props.courseId, checkInData.value);
        
        // Update local state
        const newRecords = checkInData.value.members.map(member => ({
            id: Date.now() + Math.random(),
            date: checkInData.value.date,
            member_id: member.id,
            member_name: member.name,
            group_name: selectedGroup.value?.name || '',
            status: member.status,
            check_in_time: member.status !== 'absent' ? new Date().toTimeString().split(' ')[0] : null,
            checked_by: 'ผู้ใช้ปัจจุบัน',
        }));
        
        attendanceRecords.value.push(...newRecords);
        
        showCheckInModal.value = false;
        
    } catch (err) {
        console.error('Failed to save attendance:', err);
        alert('เกิดข้อผิดพลาดในการบันทึกการเช็คชื่อ');
    }
};

const exportAttendance = async () => {
    try {
        // This would generate and download CSV/Excel file
        // await courseStore.exportAttendance(props.courseId, { 
        //     group_id: selectedGroupId.value,
        //     date_from: dateRange.value.start,
        //     date_to: dateRange.value.end,
        // });
        
        alert('ระบบจะดาวน์โหลดไฟล์สรุปการเข้าเรียน');
    } catch (err) {
        console.error('Failed to export attendance:', err);
    }
};

const updateMemberStatus = (memberId, status) => {
    const member = checkInData.value.members.find(m => m.id === memberId);
    if (member) {
        member.status = status;
    }
};

// Load data on mount
onMounted(async () => {
    await courseStore.init(props.courseId);
    await loadAttendanceData();
});
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <CourseHeader 
            :course="course"
            :course-id="courseId"
            title="การเข้าเรียน"
        />

        <div class="flex">
            <!-- Sidebar -->
            <CourseSidebar 
                :course-id="courseId"
                :active-tab="'attendance'"
            />

            <!-- Main Content -->
            <main class="flex-1 p-6">
                <!-- Loading State -->
                <div v-if="loading" class="flex justify-center items-center h-64">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="bg-red-50 border-l-4 border-red-400 p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 00016zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-2.293 2.293a1 1 0 101.414 1.414L10 11.414l2.293 2.293a1 1 0 001.414-1.414L11.414 10l2.293-2.293a1 1 0 00-1.414-1.414L10 8.586 7.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                {{ error }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div v-else class="space-y-6">
                    <!-- Page Header -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-semibold text-gray-900">
                                การเข้าเรียน: {{ course?.name }}
                            </h1>
                            <p class="mt-1 text-sm text-gray-600">
                                บันทึกและตรวจสอบการเข้าเรียนของสมาชิก
                            </p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <button
                                @click="exportAttendance"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                ส่งออกรายงาน
                            </button>
                            <button
                                @click="openCheckInModal"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                                เช็คชื่อ
                            </button>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="bg-white shadow rounded-lg p-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="group-select" class="block text-sm font-medium text-gray-700">
                                    กลุ่มเรียน
                                </label>
                                <select
                                    id="group-select"
                                    v-model="selectedGroupId"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                >
                                    <option value="">ทุกกลุ่ม</option>
                                    <option v-for="group in groups" :key="group.id" :value="group.id">
                                        {{ group.name }}
                                    </option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="date-select" class="block text-sm font-medium text-gray-700">
                                    วันที่
                                </label>
                                <input
                                    id="date-select"
                                    v-model="selectedDate"
                                    type="date"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                >
                            </div>
                            
                            <div>
                                <label for="mode-select" class="block text-sm font-medium text-gray-700">
                                    โหมดการเช็คชื่อ
                                </label>
                                <select
                                    id="mode-select"
                                    v-model="attendanceMode"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                >
                                    <option value="manual">เช็คชื่อด้วยตนเอง</option>
                                    <option value="auto">เช็คชื่ออัตโนมัติ</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance History -->
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                ประวัติการเข้าเรียน
                            </h3>
                            
                            <div v-if="attendanceHistory.length === 0" class="text-center py-8 text-gray-500">
                                ยังไม่มีข้อมูลการเข้าเรียน
                            </div>
                            
                            <div v-else class="space-y-6">
                                <div 
                                    v-for="dayRecord in attendanceHistory" 
                                    :key="dayRecord.date"
                                    class="border border-gray-200 rounded-lg p-4"
                                >
                                    <div class="flex items-center justify-between mb-3">
                                        <h4 class="text-md font-medium text-gray-900">
                                            {{ new Date(dayRecord.date).toLocaleDateString('th-TH', { 
                                                weekday: 'long', 
                                                year: 'numeric', 
                                                month: 'long', 
                                                day: 'numeric' 
                                            }) }}
                                        </h4>
                                        <div class="flex items-center space-x-4 text-sm">
                                            <span class="text-green-600">
                                                มา: {{ dayRecord.stats.present }}
                                            </span>
                                            <span class="text-yellow-600">
                                                สาย: {{ dayRecord.stats.late }}
                                            </span>
                                            <span class="text-red-600">
                                                ขาด: {{ dayRecord.stats.absent }}
                                            </span>
                                            <span class="text-gray-600">
                                                รวม: {{ dayRecord.stats.total }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        ชื่อสมาชิก
                                                    </th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        กลุ่ม
                                                    </th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        สถานะ
                                                    </th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        เวลาเช็คชื่อ
                                                    </th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        ผู้บันทึก
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <tr v-for="record in dayRecord.records" :key="record.id">
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                        {{ record.member_name }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {{ record.group_name }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span 
                                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                                            :class="{
                                                                'bg-green-100 text-green-800': record.status === 'present',
                                                                'bg-yellow-100 text-yellow-800': record.status === 'late',
                                                                'bg-red-100 text-red-800': record.status === 'absent'
                                                            }"
                                                        >
                                                            {{ record.status === 'present' ? 'มา' : record.status === 'late' ? 'สาย' : 'ขาด' }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {{ record.check_in_time || '-' }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {{ record.checked_by }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <!-- Check-in Modal -->
        <div v-if="showCheckInModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                เช็คชื่อ - {{ selectedGroup?.name }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                วันที่: {{ new Date(checkInData.date).toLocaleDateString('th-TH') }}
                            </p>
                        </div>

                        <!-- Attendance Stats -->
                        <div class="mb-4 grid grid-cols-4 gap-4">
                            <div class="bg-blue-50 p-3 rounded-lg text-center">
                                <div class="text-2xl font-bold text-blue-600">{{ attendanceStats.total }}</div>
                                <div class="text-sm text-blue-600">ทั้งหมด</div>
                            </div>
                            <div class="bg-green-50 p-3 rounded-lg text-center">
                                <div class="text-2xl font-bold text-green-600">{{ attendanceStats.present }}</div>
                                <div class="text-sm text-green-600">มา</div>
                            </div>
                            <div class="bg-yellow-50 p-3 rounded-lg text-center">
                                <div class="text-2xl font-bold text-yellow-600">{{ attendanceStats.late }}</div>
                                <div class="text-sm text-yellow-600">สาย</div>
                            </div>
                            <div class="bg-red-50 p-3 rounded-lg text-center">
                                <div class="text-2xl font-bold text-red-600">{{ attendanceStats.absent }}</div>
                                <div class="text-sm text-red-600">ขาด</div>
                            </div>
                        </div>

                        <!-- Member List -->
                        <div class="max-h-96 overflow-y-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            ชื่อสมาชิก
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            สถานะ
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            หมายเหตุ
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="member in checkInData.members" :key="member.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ member.name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <select
                                                :value="member.status"
                                                @change="updateMemberStatus(member.id, $event.target.value)"
                                                class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                            >
                                                <option value="present">มา</option>
                                                <option value="late">สาย</option>
                                                <option value="absent">ขาด</option>
                                            </select>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input
                                                v-model="member.note"
                                                type="text"
                                                placeholder="หมายเหตุ"
                                                class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                            >
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            @click="saveAttendance"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            บันทึกการเช็คชื่อ
                        </button>
                        <button
                            @click="showCheckInModal = false"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            ยกเลิก
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

