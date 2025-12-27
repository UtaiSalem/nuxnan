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
const summaryData = ref({
    overview: {
        total_students: 0,
        active_students: 0,
        completion_rate: 0,
        average_grade: 0,
        total_lessons: 0,
        completed_lessons: 0,
        total_assignments: 0,
        submitted_assignments: 0,
        total_exams: 0,
        completed_exams: 0,
    },
    attendance: {
        total_sessions: 0,
        average_attendance_rate: 0,
        present_count: 0,
        absent_count: 0,
        late_count: 0,
    },
    grades: {
        grade_distribution: {
            'A': 0,
            'B': 0,
            'C': 0,
            'D': 0,
            'F': 0,
        },
        average_scores: {
            assignments: 0,
            exams: 0,
            overall: 0,
        },
    },
    activity: {
        login_frequency: [],
        content_access: [],
        submission_timeline: [],
    },
});
const selectedGroup = ref('all');
const selectedTimeRange = ref('all'); // 'all', 'month', 'quarter', 'semester'
const exportFormat = ref('pdf'); // 'pdf', 'excel', 'csv'

// Computed
const course = computed(() => courseStore.currentCourse);
const groups = computed(() => groupStore.groupsArray);
const members = computed(() => memberStore.membersArray);

const filteredSummaryData = computed(() => {
    // This would filter data based on selectedGroup and selectedTimeRange
    return summaryData.value;
});

const gradeDistributionData = computed(() => {
    const distribution = filteredSummaryData.value.grades.grade_distribution;
    const total = Object.values(distribution).reduce((sum, count) => sum + count, 0);
    
    return Object.entries(distribution).map(([grade, count]) => ({
        grade,
        count,
        percentage: total > 0 ? Math.round((count / total) * 100) : 0,
    }));
});

const attendanceData = computed(() => {
    const attendance = filteredSummaryData.value.attendance;
    const total = attendance.present_count + attendance.absent_count + attendance.late_count;
    
    return [
        { label: 'มา', value: attendance.present_count, percentage: total > 0 ? Math.round((attendance.present_count / total) * 100) : 0, color: 'bg-green-500' },
        { label: 'สาย', value: attendance.late_count, percentage: total > 0 ? Math.round((attendance.late_count / total) * 100) : 0, color: 'bg-yellow-500' },
        { label: 'ขาด', value: attendance.absent_count, percentage: total > 0 ? Math.round((attendance.absent_count / total) * 100) : 0, color: 'bg-red-500' },
    ];
});

// Methods
const loadSummaryData = async () => {
    try {
        loading.value = true;
        
        // Load groups and members
        await Promise.all([
            groupStore.fetchGroups(props.courseId),
            memberStore.fetchMembers(props.courseId),
        ]);
        
        // This would call API
        // summaryData.value = await courseStore.fetchCourseSummary(props.courseId, {
        //     group_id: selectedGroup.value,
        //     time_range: selectedTimeRange.value,
        // });
        
        // Mock data for now
        summaryData.value = {
            overview: {
                total_students: 45,
                active_students: 42,
                completion_rate: 78,
                average_grade: 82.5,
                total_lessons: 12,
                completed_lessons: 9,
                total_assignments: 8,
                submitted_assignments: 6,
                total_exams: 3,
                completed_exams: 2,
            },
            attendance: {
                total_sessions: 24,
                average_attendance_rate: 85,
                present_count: 918,
                absent_count: 102,
                late_count: 78,
            },
            grades: {
                grade_distribution: {
                    'A': 12,
                    'B': 15,
                    'C': 10,
                    'D': 5,
                    'F': 3,
                },
                average_scores: {
                    assignments: 78.5,
                    exams: 75.2,
                    overall: 77.1,
                },
            },
            activity: {
                login_frequency: [
                    { date: '2023-11-27', count: 35 },
                    { date: '2023-11-28', count: 42 },
                    { date: '2023-11-29', count: 38 },
                    { date: '2023-11-30', count: 45 },
                    { date: '2023-12-01', count: 40 },
                ],
                content_access: [
                    { type: 'บทเรียน', count: 156 },
                    { type: 'การบ้าน', count: 89 },
                    { type: 'ข้อสอบ', count: 34 },
                    { type: 'ประกาศ', count: 23 },
                ],
                submission_timeline: [
                    { date: '2023-11-01', assignments: 5, exams: 0 },
                    { date: '2023-11-15', assignments: 12, exams: 0 },
                    { date: '2023-12-01', assignments: 8, exams: 15 },
                ],
            },
        };
    } catch (err) {
        error.value = err.message;
        console.error('Failed to load summary data:', err);
    } finally {
        loading.value = false;
    }
};

const exportSummary = async () => {
    try {
        // This would call API
        // await courseStore.exportSummary(props.courseId, {
        //     format: exportFormat.value,
        //     group_id: selectedGroup.value,
        //     time_range: selectedTimeRange.value,
        // });
        
        alert(`ระบบจะส่งออกรายงานสรุปผลในรูปแบบ ${exportFormat.value.toUpperCase()}`);
    } catch (err) {
        console.error('Failed to export summary:', err);
        alert('เกิดข้อผิดพลาดในการส่งออกรายงาน');
    }
};

const refreshData = () => {
    loadSummaryData();
};

const getGradeColor = (grade) => {
    const colors = {
        'A': 'text-green-600',
        'B': 'text-blue-600',
        'C': 'text-yellow-600',
        'D': 'text-orange-600',
        'F': 'text-red-600',
    };
    return colors[grade] || 'text-gray-600';
};

// Load data on mount
onMounted(async () => {
    await courseStore.init(props.courseId);
    await loadSummaryData();
});
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <CourseHeader 
            :course="course"
            :course-id="courseId"
            title="สรุปผล"
        />

        <div class="flex">
            <!-- Sidebar -->
            <CourseSidebar 
                :course-id="courseId"
                :active-tab="'summary'"
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
                                สรุปผลรายวิชา: {{ course?.name }}
                            </h1>
                            <p class="mt-1 text-sm text-gray-600">
                                ภาพรวมข้อมูลการเรียนและผลการเรียนของนักเรียน
                            </p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <button
                                @click="refreshData"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                รีเฟรชข้อมูล
                            </button>
                            <button
                                @click="exportSummary"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                ส่งออกรายงาน
                            </button>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="bg-white shadow rounded-lg p-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="group-filter" class="block text-sm font-medium text-gray-700">
                                    กลุ่ม
                                </label>
                                <select
                                    id="group-filter"
                                    v-model="selectedGroup"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                >
                                    <option value="all">ทุกกลุ่ม</option>
                                    <option v-for="group in groups" :key="group.id" :value="group.id">
                                        {{ group.name }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label for="time-range" class="block text-sm font-medium text-gray-700">
                                    ช่วงเวลา
                                </label>
                                <select
                                    id="time-range"
                                    v-model="selectedTimeRange"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                >
                                    <option value="all">ทั้งหมด</option>
                                    <option value="month">เดือนล่าสุด</option>
                                    <option value="quarter">ไตรมาส</option>
                                    <option value="semester">ภาคการศึกษา</option>
                                </select>
                            </div>

                            <div>
                                <label for="export-format" class="block text-sm font-medium text-gray-700">
                                    รูปแบบส่งออก
                                </label>
                                <select
                                    id="export-format"
                                    v-model="exportFormat"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                >
                                    <option value="pdf">PDF</option>
                                    <option value="excel">Excel</option>
                                    <option value="csv">CSV</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Overview Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v-1a6 6 0 00-9 5.197m13 5a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">
                                                จำนวนนักเรียน
                                            </dt>
                                            <dd class="text-lg font-medium text-gray-900">
                                                {{ filteredSummaryData.overview.total_students }}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">
                                                อัตราการสำเร็จ
                                            </dt>
                                            <dd class="text-lg font-medium text-gray-900">
                                                {{ filteredSummaryData.overview.completion_rate }}%
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-6 w-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 01-.95.69h-4.915c-.889 0-1.653-.666-1.953-1.926a2.982 2.982 0 00-.538-.896l-1.966-3.448a1 1 0 01.921-1.404l4.674 1.52c.299.097.598.197.896.297.299.099.598.199.897.298l3.448 1.966a1 1 0 001.404-.921l-1.52-4.674z" />
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">
                                                คะแนนเฉลี่ย
                                            </dt>
                                            <dd class="text-lg font-medium text-gray-900">
                                                {{ filteredSummaryData.overview.average_grade }}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="p-5">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-6 w-6 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt class="text-sm font-medium text-gray-500 truncate">
                                                อัตราการเข้าเรียน
                                            </dt>
                                            <dd class="text-lg font-medium text-gray-900">
                                                {{ filteredSummaryData.attendance.average_attendance_rate }}%
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Overview -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Lessons Progress -->
                        <div class="bg-white shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    ความคืบหน้าในบทเรียน
                                </h3>
                                <div class="space-y-4">
                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm font-medium text-gray-700">บทเรียนที่เสร็จ</span>
                                            <span class="text-sm text-gray-500">
                                                {{ filteredSummaryData.overview.completed_lessons }}/{{ filteredSummaryData.overview.total_lessons }}
                                            </span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div 
                                                class="bg-blue-600 h-2 rounded-full" 
                                                :style="{ width: Math.round((filteredSummaryData.overview.completed_lessons / filteredSummaryData.overview.total_lessons) * 100) + '%' }"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Assignments Progress -->
                        <div class="bg-white shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    การส่งการบ้าน
                                </h3>
                                <div class="space-y-4">
                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm font-medium text-gray-700">การส่งงาน</span>
                                            <span class="text-sm text-gray-500">
                                                {{ filteredSummaryData.overview.submitted_assignments }}/{{ filteredSummaryData.overview.total_assignments }}
                                            </span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div 
                                                class="bg-green-600 h-2 rounded-full" 
                                                :style="{ width: Math.round((filteredSummaryData.overview.submitted_assignments / filteredSummaryData.overview.total_assignments) * 100) + '%' }"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Grade Distribution -->
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                การกระจายคะแนน
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                                <div 
                                    v-for="gradeData in gradeDistributionData" 
                                    :key="gradeData.grade"
                                    class="text-center"
                                >
                                    <div class="text-3xl font-bold" :class="getGradeColor(gradeData.grade)">
                                        {{ gradeData.grade }}
                                    </div>
                                    <div class="text-2xl font-semibold text-gray-900">
                                        {{ gradeData.count }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ gradeData.percentage }}%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Breakdown -->
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                สถิติการเข้าเรียน
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div 
                                    v-for="attendance in attendanceData" 
                                    :key="attendance.label"
                                    class="text-center"
                                >
                                    <div class="flex items-center justify-center mb-2">
                                        <div 
                                            class="w-4 h-4 rounded-full mr-2" 
                                            :class="attendance.color"
                                        ></div>
                                        <span class="text-lg font-semibold text-gray-900">
                                            {{ attendance.value }}
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ attendance.label }} ({{ attendance.percentage }}%)
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Average Scores -->
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                คะแนนเฉลี่ย
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="text-center">
                                    <div class="text-2xl font-semibold text-blue-600">
                                        {{ filteredSummaryData.grades.average_scores.assignments }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        การบ้าน
                                    </div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-semibold text-green-600">
                                        {{ filteredSummaryData.grades.average_scores.exams }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        ข้อสอบ
                                    </div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-semibold text-purple-600">
                                        {{ filteredSummaryData.grades.average_scores.overall }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        รวม
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Activity Timeline -->
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                กิจกรรมล่าสุด
                            </h3>
                            <div class="space-y-4">
                                <div 
                                    v-for="activity in filteredSummaryData.activity.submission_timeline" 
                                    :key="activity.date"
                                    class="flex items-center justify-between p-3 border border-gray-200 rounded-lg"
                                >
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ new Date(activity.date).toLocaleDateString('th-TH') }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            การส่ง: {{ activity.assignments }} งาน, {{ activity.exams }} ข้อสอบ
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>

