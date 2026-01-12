<script setup>
import { computed, ref } from 'vue';
import CourseLayout from '@/Layouts/CourseLayout.vue';
import MemberAssignmentsAnswersDetails from '@/components/learn/course/assignments/MemberAssignmentsAnswersDetails.vue';
import MemberQuizzesDetails from '@/components/learn/course/progress/MemberQuizzesDetails.vue';
import Textarea from 'primevue/textarea';

const props = defineProps({
    isCourseAdmin: Boolean,
    course: Object,
    member: Object,
    course_assignments: Object,
    course_quizzes: Object,
    member_quizes_results: Object,
    member_assignments_answers: Object,
});

// Calculate total achieved score from assignments and quizzes
const totalAchievedScore = computed(() => {
    let assignmentScore = 0;
    let quizScore = 0;
    
    // Calculate assignment scores
    props.course_assignments.forEach(assignment => {
        const answer = props.member_assignments_answers.data.find(answer => answer.assignment_id === assignment.id);
        if (answer) {
            assignmentScore += answer.points || 0;
        }
    });
    
    // Calculate quiz scores
    props.course_quizzes.forEach(quiz => {
        const result = props.member_quizes_results.data.find(result => result.quiz_id === quiz.id);
        if (result) {
            quizScore += result.score || 0;
        }
    });
    
    return assignmentScore + quizScore;
});

// Calculate percentage of achieved score
const scorePercentage = computed(() => {
    if (!props.course.data.total_score || props.course.data.total_score === 0) return 0;
    return (totalAchievedScore.value / props.course.data.total_score) * 100;
});

// Check if all assignments are submitted
const allAssignmentsSubmitted = computed(() => {
    return props.course_assignments.every(assignment => {
        return props.member_assignments_answers.data.some(answer => answer.assignment_id === assignment.id);
    });
});

// Calculate grade based on percentage
const calculatedGrade = computed(() => {
    // Check if all assignments are submitted
    if (!allAssignmentsSubmitted.value) {
        return { grade: 'ร.', label: 'รอการตัดเกรด', status: 'pending' };
    }
    
    // Check if score is below passing threshold (50% of total score)
    const passingThreshold = props.course.data.total_score / 2;
    if (totalAchievedScore.value <= passingThreshold) {
        return { grade: '0', label: '0 (ไม่ผ่านเกณฑ์)', status: 'fail' };
    }
    
    // Calculate grade based on percentage
    const percentage = scorePercentage.value;
    
    if (percentage >= 90) return { grade: '4.0', label: '4.0 (ดีเยี่ยม)', status: 'excellent' };
    if (percentage >= 85) return { grade: '3.5', label: '3.5 (ดีมาก)', status: 'very-good' };
    if (percentage >= 80) return { grade: '3.0', label: '3.0 (ดี)', status: 'good' };
    if (percentage >= 75) return { grade: '2.5', label: '2.5 (ค่อนข้างดี)', status: 'fairly-good' };
    if (percentage >= 70) return { grade: '2.0', label: '2.0 (พอใช้)', status: 'fair' };
    if (percentage >= 60) return { grade: '1.5', label: '1.5 (ผ่านขั้นต่ำ)', status: 'minimum-pass' };
    if (percentage > passingThreshold / props.course.data.total_score * 100) return { grade: '1.0', label: '1.0 (ผ่าน)', status: 'pass' };
    
    return { grade: '0', label: '0 (ไม่ผ่าน)', status: 'fail' };
});

// Determine pass/fail status
const passFailStatus = computed(() => {
    const passingThreshold = props.course.data.total_score / 2;
    return totalAchievedScore.value > passingThreshold ? 'ผ่าน' : 'ไม่ผ่าน';
});

// Get grade color based on status
const getGradeColor = (status) => {
    switch (status) {
        case 'excellent': return 'text-emerald-700 bg-gradient-to-br from-emerald-50 to-emerald-100 border-emerald-300 shadow-lg shadow-emerald-100';
        case 'very-good': return 'text-green-700 bg-gradient-to-br from-green-50 to-green-100 border-green-300 shadow-lg shadow-green-100';
        case 'good': return 'text-teal-700 bg-gradient-to-br from-teal-50 to-teal-100 border-teal-300 shadow-lg shadow-teal-100';
        case 'fairly-good': return 'text-blue-700 bg-gradient-to-br from-blue-50 to-blue-100 border-blue-300 shadow-lg shadow-blue-100';
        case 'fair': return 'text-indigo-700 bg-gradient-to-br from-indigo-50 to-indigo-100 border-indigo-300 shadow-lg shadow-indigo-100';
        case 'minimum-pass': return 'text-yellow-700 bg-gradient-to-br from-yellow-50 to-yellow-100 border-yellow-300 shadow-lg shadow-yellow-100';
        case 'pass': return 'text-orange-700 bg-gradient-to-br from-orange-50 to-orange-100 border-orange-300 shadow-lg shadow-orange-100';
        case 'fail': return 'text-red-700 bg-gradient-to-br from-red-50 to-red-100 border-red-300 shadow-lg shadow-red-100';
        case 'pending': return 'text-gray-700 bg-gradient-to-br from-gray-50 to-gray-100 border-gray-300 shadow-lg shadow-gray-100';
        default: return 'text-gray-700 bg-gradient-to-br from-gray-50 to-gray-100 border-gray-300 shadow-lg shadow-gray-100';
    }
};

// Get progress bar color based on percentage
const getProgressColor = (percentage) => {
    if (percentage >= 90) return 'text-emerald-500';
    if (percentage >= 80) return 'text-green-500';
    if (percentage >= 70) return 'text-teal-500';
    if (percentage >= 60) return 'text-blue-500';
    if (percentage >= 50) return 'text-indigo-500';
    if (percentage >= 40) return 'text-yellow-500';
    if (percentage >= 30) return 'text-orange-500';
    return 'text-red-500';
};

// Get stroke color for radial progress
const getStrokeColor = (percentage) => {
    if (percentage >= 90) return '#10b981'; // emerald-500
    if (percentage >= 80) return '#22c55e'; // green-500
    if (percentage >= 70) return '#14b8a6'; // teal-500
    if (percentage >= 60) return '#3b82f6'; // blue-500
    if (percentage >= 50) return '#6366f1'; // indigo-500
    if (percentage >= 40) return '#eab308'; // yellow-500
    if (percentage >= 30) return '#f97316'; // orange-500
    return '#ef4444'; // red-500
};

// Get gradient colors for radial progress
const getProgressGradient = (percentage) => {
    if (percentage >= 90) return 'url(#gradient-emerald)';
    if (percentage >= 80) return 'url(#gradient-green)';
    if (percentage >= 70) return 'url(#gradient-teal)';
    if (percentage >= 60) return 'url(#gradient-blue)';
    if (percentage >= 50) return 'url(#gradient-indigo)';
    if (percentage >= 40) return 'url(#gradient-yellow)';
    if (percentage >= 30) return 'url(#gradient-orange)';
    return 'url(#gradient-red)';
};

// Calculate stroke dasharray for radial progress
const getStrokeDasharray = (percentage) => {
    const radius = 60;
    const circumference = 2 * Math.PI * radius;
    const offset = circumference - (percentage / 100) * circumference;
    return {
        strokeDasharray: circumference,
        strokeDashoffset: offset
    };
};

// Get progress bar color for individual items
const getItemProgressColor = (percentage) => {
    if (percentage >= 80) return 'bg-gradient-to-r from-emerald-400 to-emerald-600';
    if (percentage >= 60) return 'bg-gradient-to-r from-blue-400 to-blue-600';
    if (percentage >= 40) return 'bg-gradient-to-r from-yellow-400 to-yellow-600';
    return 'bg-gradient-to-r from-red-400 to-red-600';
};

// Get status badge styling
const getStatusBadgeStyle = (status, type) => {
    if (type === 'assignment') {
        return status ? 'bg-gradient-to-r from-green-100 to-emerald-100 text-green-800 border border-green-300' : 'bg-gradient-to-r from-red-100 to-pink-100 text-red-800 border border-red-300';
    } else {
        return status ? 'bg-gradient-to-r from-green-100 to-emerald-100 text-green-800 border border-green-300' : 'bg-gradient-to-r from-yellow-100 to-orange-100 text-yellow-800 border border-yellow-300';
    }
};

// Format score display
const formatScore = (score, total) => {
    return `${score}/${total}`;
};

// Truncate text and add read more functionality
const truncateText = (text, maxLength = 50) => {
    if (text.length <= maxLength) return { text, isTruncated: false };
    return {
        text: text.substring(0, maxLength) + '...',
        isTruncated: true,
        fullText: text
    };
};

// State for expanded assignments
const expandedAssignments = ref({});
const expandedQuizzes = ref({});


// Toggle expanded state
const toggleExpanded = (id, type) => {
    if (type === 'assignment') {
        expandedAssignments.value[id] = !expandedAssignments.value[id];
    } else {
        expandedQuizzes.value[id] = !expandedQuizzes.value[id];
    }
};

const handlePrint = () => {
    window.print();
};

/* Grade Calculator Logic */
const isCalculatorOpen = ref(false);
const hypotheticalScores = ref({});

const toggleCalculator = () => {
    isCalculatorOpen.value = !isCalculatorOpen.value;
    if (isCalculatorOpen.value && Object.keys(hypotheticalScores.value).length === 0) {
        // Initialize with current scores or 0 for unsubmitted
        props.course_assignments.forEach(assignment => {
            const answer = props.member_assignments_answers.data.find(a => a.assignment_id === assignment.id);
            if (!answer) {
                hypotheticalScores.value[`assignment_${assignment.id}`] = assignment.points; // Default to max points for optimism
            }
        });
        props.course_quizzes.forEach(quiz => {
            const result = props.member_quizes_results.data.find(r => r.quiz_id === quiz.id);
            if (!result) {
                hypotheticalScores.value[`quiz_${quiz.id}`] = quiz.total_score;
            }
        });
    }
};

const projectedTotalScore = computed(() => {
    let score = 0;
    
    props.course_assignments.forEach(assignment => {
        const key = `assignment_${assignment.id}`;
        if (hypotheticalScores.value[key] !== undefined) {
             // Use hypothetical if defined (meaning it was unsubmitted/target for calc)
            score += Number(hypotheticalScores.value[key]) || 0;
        } else {
             // Submitted/Actual
             const answer = props.member_assignments_answers.data.find(a => a.assignment_id === assignment.id);
             score += answer ? (answer.points || 0) : 0;
        }
    });
    
    props.course_quizzes.forEach(quiz => {
        const key = `quiz_${quiz.id}`;
        if (hypotheticalScores.value[key] !== undefined) {
            score += Number(hypotheticalScores.value[key]) || 0;
        } else {
            const result = props.member_quizes_results.data.find(r => r.quiz_id === quiz.id);
            score += result ? (result.score || 0) : 0;
        }
    });
    
    return score;
});

const projectedScorePercentage = computed(() => {
    if (!props.course.data.total_score || props.course.data.total_score === 0) return 0;
    return (projectedTotalScore.value / props.course.data.total_score) * 100;
});

const projectedGrade = computed(() => {
     const score = projectedTotalScore.value;
     const passingThreshold = props.course.data.total_score / 2;
     
     if (score <= passingThreshold) return { grade: '0', label: '0 (ไม่ผ่าน)', status: 'fail' };
     
     const percentage = projectedScorePercentage.value;
     
    if (percentage >= 90) return { grade: '4.0', label: '4.0 (ดีเยี่ยม)', status: 'excellent' };
    if (percentage >= 85) return { grade: '3.5', label: '3.5 (ดีมาก)', status: 'very-good' };
    if (percentage >= 80) return { grade: '3.0', label: '3.0 (ดี)', status: 'good' };
    if (percentage >= 75) return { grade: '2.5', label: '2.5 (ค่อนข้างดี)', status: 'fairly-good' };
    if (percentage >= 70) return { grade: '2.0', label: '2.0 (พอใช้)', status: 'fair' };
    if (percentage >= 60) return { grade: '1.5', label: '1.5 (ผ่านขั้นต่ำ)', status: 'minimum-pass' };
    if (percentage > passingThreshold / props.course.data.total_score * 100) return { grade: '1.0', label: '1.0 (ผ่าน)', status: 'pass' };
    
    return { grade: '0', label: '0 (ไม่ผ่าน)', status: 'fail' };
});
</script>

<template>
    <div>
        <CourseLayout
            :isCourseAdmin="props.isCourseAdmin"
            :course="props.course"
            :lessons="props.lessons"
            :groups="props.groups"
            :activeTab="4"
         >
            <template #courseContent>
                <!-- Header Section with Member Info -->
                <div class="bg-gradient-to-br from-purple-600 via-blue-600 to-indigo-700 rounded-2xl shadow-2xl p-8 mb-8 text-white relative overflow-hidden">
                    <!-- Static background elements -->
                    <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
                    <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/10 rounded-full blur-2xl"></div>
                    
                    <div class="relative z-10">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                            <div class="mb-6 md:mb-0">
                                <div class="flex items-center gap-4 mb-3">
                                    <button @click="$router.back()" class="p-2 rounded-full bg-white/20 hover:bg-white/30 text-white transition-colors print:hidden">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                        </svg>
                                    </button>
                                    <h1 class="text-4xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-white to-blue-100">
                                        รายละเอียดความก้าวหน้าเกรด
                                    </h1>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                                    <div class="bg-white/20 backdrop-blur-md rounded-xl p-4 transition-all duration-200 hover:bg-white/25">
                                        <p class="text-sm opacity-90 mb-1">เลขที่</p>
                                        <p class="text-2xl font-bold">{{ props.member.data.order_number }}</p>
                                    </div>
                                    <div class="bg-white/20 backdrop-blur-md rounded-xl p-4 transition-all duration-200 hover:bg-white/25">
                                        <p class="text-sm opacity-90 mb-1">เลขประจำตัว</p>
                                        <p class="text-2xl font-bold">{{ props.member.data.member_code }}</p>
                                    </div>
                                    <div class="bg-white/20 backdrop-blur-md rounded-xl p-4 transition-all duration-200 hover:bg-white/25">
                                        <p class="text-sm opacity-90 mb-1">ชื่อสมาชิก</p>
                                        <p class="text-2xl font-bold">{{ props.member.data.member_name }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-3 print:hidden">
                                <button @click="toggleCalculator" :class="`flex items-center gap-2 px-4 py-2 ${isCalculatorOpen ? 'bg-white text-indigo-600 shadow-lg' : 'bg-white/20 text-white hover:bg-white/30'} rounded-xl backdrop-blur-md transition-all font-medium border border-transparent`">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                    {{ isCalculatorOpen ? 'ปิดคำนวณเกรด' : 'คำนวณเกรด' }}
                                </button>
                                <button @click="handlePrint" class="flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-xl backdrop-blur-md transition-all font-medium">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                    พิมพ์ / ส่งออก PDF
                                </button>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Grade Calculator Section -->
                <div v-if="isCalculatorOpen" class="bg-gradient-to-br from-indigo-50 to-blue-50 rounded-2xl shadow-xl p-8 mb-8 border border-indigo-100 print:hidden">
                    <div class="flex justify-between items-start mb-6">
                        <h2 class="text-2xl font-bold text-indigo-800 flex items-center">
                            <span class="p-2 bg-indigo-200 rounded-lg mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </span>
                            จำลองการคำนวณเกรด (Grade Calculator)
                        </h2>
                        <button @click="toggleCalculator" class="text-gray-500 hover:text-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Input Section -->
                        <div class="lg:col-span-2 space-y-6">
                            <!-- Assignments -->
                            <div class="bg-white/50 rounded-xl p-4">
                                <h3 class="font-bold text-gray-700 mb-3 flex items-center">
                                    <span class="w-2 h-2 bg-blue-500 rounded-full mr-2"></span>
                                    งานที่ยังไม่ได้ส่ง / ยังไม่ได้ตรวจ
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div v-for="assign in props.course_assignments" :key="assign.id"
                                         v-if="!props.member_assignments_answers.data.find(a => a.assignment_id === assign.id) || !props.member_assignments_answers.data.find(a => a.assignment_id === assign.id).points"
                                         class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                            <div class="flex justify-between text-sm mb-2">
                                                <span class="font-medium text-gray-700 truncate pr-2" :title="assign.title">{{ truncateText(assign.title, 30).text }}</span>
                                                <span class="text-gray-500 text-xs bg-gray-100 px-2 py-0.5 rounded-full">Max: {{ assign.points }}</span>
                                            </div>
                                            <div class="relative">
                                                <Textarea
                                                    v-model="hypotheticalScores[`assignment_${assign.id}`]"
                                                    :max="assign.points"
                                                    rows="1"
                                                    class="w-full"
                                                    placeholder="คะแนนที่คาดหวัง" />
                                            </div>
                                        </div>
                                </div>
                            </div>

                            <!-- Quizzes -->
                            <div class="bg-white/50 rounded-xl p-4">
                                <h3 class="font-bold text-gray-700 mb-3 flex items-center">
                                    <span class="w-2 h-2 bg-purple-500 rounded-full mr-2"></span>
                                    แบบทดสอบที่ยังไม่ได้ทำ
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div v-for="quiz in props.course_quizzes" :key="quiz.id"
                                         v-if="!props.member_quizes_results.data.find(r => r.quiz_id === quiz.id)"
                                         class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                            <div class="flex justify-between text-sm mb-2">
                                                <span class="font-medium text-gray-700 truncate pr-2" :title="quiz.title">{{ truncateText(quiz.title, 30).text }}</span>
                                                <span class="text-gray-500 text-xs bg-gray-100 px-2 py-0.5 rounded-full">Max: {{ quiz.total_score }}</span>
                                            </div>
                                            <Textarea
                                                   v-model="hypotheticalScores[`quiz_${quiz.id}`]"
                                                   :max="quiz.total_score"
                                                   rows="1"
                                                   class="w-full"
                                                   placeholder="คะแนนที่คาดหวัง" />
                                        </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Result Section -->
                        <div class="relative">
                            <div class="sticky top-4 bg-white rounded-2xl p-6 shadow-lg border border-indigo-100 text-center ring-4 ring-indigo-50/50">
                                 <p class="text-gray-500 font-medium mb-1 tracking-wide uppercase text-xs">เกรดที่คาดว่าจะได้</p>
                                 <div class="my-4">
                                    <div :class="`inline-block px-8 py-6 rounded-2xl border-2 transition-all duration-300 transform hover:scale-105 ${getGradeColor(projectedGrade.status)}`">
                                        <p class="text-6xl font-black mb-1 leading-none">{{ projectedGrade.grade }}</p>
                                        <p class="text-sm font-bold opacity-90">{{ projectedGrade.label }}</p>
                                    </div>
                                 </div>
                                 
                                 <div class="bg-gray-50 rounded-xl p-4 mb-4">
                                     <div class="flex justify-between items-center mb-2">
                                         <span class="text-sm text-gray-600">คะแนนปัจจุบัน</span>
                                         <span class="font-bold text-gray-800">{{ totalAchievedScore }}</span>
                                     </div>
                                     <div class="flex justify-between items-center mb-2">
                                         <span class="text-sm text-gray-600">+ คะแนนจำลอง</span>
                                         <span class="font-bold text-indigo-600">+{{ (projectedTotalScore - totalAchievedScore).toFixed(0) }}</span>
                                     </div>
                                     <div class="border-t border-gray-200 my-2 pt-2 flex justify-between items-end">
                                         <span class="text-sm font-bold text-gray-700">รวมทั้งหมด</span>
                                         <div class="text-right">
                                             <span class="text-lg font-black text-indigo-700 block">{{ projectedTotalScore }} / {{ props.course.data.total_score }}</span>
                                             <span class="text-xs text-gray-500 font-medium">({{ projectedScorePercentage.toFixed(1) }}%)</span>
                                         </div>
                                     </div>
                                 </div>

                                 <button @click="toggleCalculator" class="w-full py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-sm font-medium transition-colors">
                                     ปิดเครื่องคิดเลข
                                 </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grade Summary Card -->
                <div class="bg-white rounded-2xl shadow-2xl p-8 mb-8 border border-gray-100">
                    <h2 class="text-3xl font-bold text-gray-800 mb-8 flex items-center">
                        <div class="p-3 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl mr-4">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        สรุปผลการเรียน
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Grade Display -->
                        <div class="text-center">
                            <div :class="`inline-block px-10 py-8 rounded-2xl border-2 transition-all duration-300 ${getGradeColor(calculatedGrade.status)}`">
                                <p class="text-sm font-bold mb-3 uppercase tracking-wider">เกรด</p>
                                <p class="text-5xl font-bold mb-2">{{ calculatedGrade.grade }}</p>
                                <p class="text-sm font-medium">{{ calculatedGrade.label }}</p>
                            </div>
                        </div>
                        
                        <!-- Score Progress - Radial -->
                        <div class="flex flex-col justify-center items-center">
                            <div class="relative group">
                                <!-- Subtle glow effect -->
                                <div class="absolute inset-0 rounded-full blur-xl opacity-20 group-hover:opacity-30 transition-opacity duration-200"
                                     :style="`background: ${getStrokeColor(scorePercentage)}`"></div>
                                
                                <svg class="w-40 h-40 transform -rotate-90 relative z-10">
                                    <!-- Define gradients -->
                                    <defs>
                                        <linearGradient id="gradient-emerald" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" style="stop-color:#10b981;stop-opacity:1" />
                                            <stop offset="100%" style="stop-color:#34d399;stop-opacity:1" />
                                        </linearGradient>
                                        <linearGradient id="gradient-green" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" style="stop-color:#22c55e;stop-opacity:1" />
                                            <stop offset="100%" style="stop-color:#4ade80;stop-opacity:1" />
                                        </linearGradient>
                                        <linearGradient id="gradient-teal" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" style="stop-color:#14b8a6;stop-opacity:1" />
                                            <stop offset="100%" style="stop-color:#2dd4bf;stop-opacity:1" />
                                        </linearGradient>
                                        <linearGradient id="gradient-blue" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" style="stop-color:#3b82f6;stop-opacity:1" />
                                            <stop offset="100%" style="stop-color:#60a5fa;stop-opacity:1" />
                                        </linearGradient>
                                        <linearGradient id="gradient-indigo" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" style="stop-color:#6366f1;stop-opacity:1" />
                                            <stop offset="100%" style="stop-color:#818cf8;stop-opacity:1" />
                                        </linearGradient>
                                        <linearGradient id="gradient-yellow" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" style="stop-color:#eab308;stop-opacity:1" />
                                            <stop offset="100%" style="stop-color:#facc15;stop-opacity:1" />
                                        </linearGradient>
                                        <linearGradient id="gradient-orange" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" style="stop-color:#f97316;stop-opacity:1" />
                                            <stop offset="100%" style="stop-color:#fb923c;stop-opacity:1" />
                                        </linearGradient>
                                        <linearGradient id="gradient-red" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" style="stop-color:#ef4444;stop-opacity:1" />
                                            <stop offset="100%" style="stop-color:#f87171;stop-opacity:1" />
                                        </linearGradient>
                                    </defs>
                                    
                                    <!-- Background circle -->
                                    <circle
                                        cx="80"
                                        cy="80"
                                        r="60"
                                        stroke="#f3f4f6"
                                        stroke-width="12"
                                        fill="none"
                                    />
                                    <!-- Progress circle -->
                                    <circle
                                        cx="80"
                                        cy="80"
                                        r="60"
                                        :stroke="getProgressGradient(scorePercentage)"
                                        stroke-width="12"
                                        fill="none"
                                        :stroke-dasharray="getStrokeDasharray(scorePercentage).strokeDasharray"
                                        :stroke-dashoffset="getStrokeDasharray(scorePercentage).strokeDashoffset"
                                        stroke-linecap="round"
                                        class="transition-all duration-500 ease-out"
                                    />
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span :class="`text-3xl font-bold ${getProgressColor(scorePercentage)}`">
                                        {{ scorePercentage.toFixed(1) }}%
                                    </span>
                                    <span class="text-sm text-gray-600 mt-1 font-medium">คะแนนรวม</span>
                                </div>
                            </div>
                            <div class="mt-4 text-center bg-gray-50 rounded-xl px-4 py-2">
                                <p class="text-sm font-semibold text-gray-700">{{ formatScore(totalAchievedScore, props.course.data.total_score) }}</p>
                            </div>
                        </div>
                        
                        <!-- Pass/Fail Status -->
                        <div class="flex flex-col justify-center items-center">
                            <div :class="`px-8 py-6 rounded-2xl transition-all duration-300 ${passFailStatus === 'ผ่าน' ? 'bg-gradient-to-br from-green-50 to-emerald-100 border-2 border-green-300 shadow-lg shadow-green-100' : 'bg-gradient-to-br from-red-50 to-pink-100 border-2 border-red-300 shadow-lg shadow-red-100'}`">
                                <p class="text-sm font-bold mb-2 uppercase tracking-wider">สถานะ</p>
                                <p :class="`text-3xl font-bold ${passFailStatus === 'ผ่าน' ? 'text-green-600' : 'text-red-600'}`">
                                    {{ passFailStatus }}
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Comments Section -->
                    <div v-if="props.member?.data.notes_comments" class="mt-8 p-6 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl border border-blue-200 transition-all duration-200">
                        <h3 class="text-lg font-bold text-gray-800 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                            </svg>
                            หมายเหตุ
                        </h3>
                        <p class="text-gray-700 leading-relaxed">{{ props.member.data.notes_comments }}</p>
                    </div>
                </div>

                <!-- Assignments Section -->
                <div class="bg-white rounded-2xl shadow-2xl p-8 mb-8 border border-gray-100 transition-all duration-200">
                    <h2 class="text-3xl font-bold text-gray-800 mb-8 flex items-center">
                        <div class="p-3 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl mr-4">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        งานที่ได้รับมอบหมาย
                    </h2>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b-2 border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100">
                                    <th class="text-left py-4 px-4 font-bold text-gray-700">ลำดับ</th>
                                    <th class="text-left py-4 px-4 font-bold text-gray-700">หัวข้อ</th>
                                    <th class="text-center py-4 px-4 font-bold text-gray-700">คะแนน</th>
                                    <th class="text-center py-4 px-4 font-bold text-gray-700">เปอร์เซ็นต์</th>
                                    <th class="text-center py-4 px-4 font-bold text-gray-700">สถานะ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(assignment, index) in props.course_assignments" :key="assignment.id"
                                    class="border-b border-gray-100 hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 transition-all duration-200">
                                    <td class="py-4 px-4">
                                        <span class="inline-flex items-center justify-center w-10 h-10 bg-gradient-to-br from-indigo-400 to-purple-500 text-white rounded-full font-bold shadow-lg">
                                            {{ index + 1 }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="max-w-md">
                                            <p class="font-semibold text-gray-800 text-lg">
                                                {{ expandedAssignments[assignment.id] ? assignment.title : truncateText(assignment.title, 50).text }}
                                            </p>
                                            <button
                                                v-if="truncateText(assignment.title, 50).isTruncated"
                                                @click="toggleExpanded(assignment.id, 'assignment')"
                                                class="text-indigo-600 hover:text-indigo-800 text-sm font-medium mt-1 transition-colors duration-200"
                                            >
                                                {{ expandedAssignments[assignment.id] ? 'แสดงน้อยลง' : 'อ่านเพิ่มเติม' }}
                                            </button>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="font-bold text-gray-700 text-lg">
                                            {{ props.member_assignments_answers.data.find(answer => answer.assignment_id === assignment.id)?.points || 0 }}/{{ assignment.points }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <div class="flex items-center justify-center">
                                            <div class="w-20 bg-gray-200 rounded-full h-3 mr-3 shadow-inner">
                                                <div :class="`h-3 rounded-full shadow-sm transition-all duration-300 ${getItemProgressColor(((props.member_assignments_answers.data.find(answer => answer.assignment_id === assignment.id)?.points || 0) / assignment.points) * 100)}`"
                                                     :style="`width: ${Math.min(((props.member_assignments_answers.data.find(answer => answer.assignment_id === assignment.id)?.points || 0) / assignment.points) * 100, 100)}%`"></div>
                                            </div>
                                            <span class="text-sm font-semibold text-gray-700">
                                                {{ (((props.member_assignments_answers.data.find(answer => answer.assignment_id === assignment.id)?.points || 0) / assignment.points) * 100).toFixed(1) }}%
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span v-if="props.member_assignments_answers.data.find(answer => answer.assignment_id === assignment.id)"
                                              :class="`px-4 py-2 rounded-full text-sm font-bold shadow-md transition-all duration-200 ${getStatusBadgeStyle(true, 'assignment')}`">
                                            ส่งแล้ว
                                        </span>
                                        <span v-else :class="`px-4 py-2 rounded-full text-sm font-bold shadow-md transition-all duration-200 ${getStatusBadgeStyle(false, 'assignment')}`">
                                            ยังไม่ส่ง
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Quizzes Section -->
                <div class="bg-white rounded-2xl shadow-2xl p-8 mb-8 border border-gray-100 transition-all duration-200">
                    <h2 class="text-3xl font-bold text-gray-800 mb-8 flex items-center">
                        <div class="p-3 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl mr-4">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        แบบทดสอบในหลักสูตร
                    </h2>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b-2 border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100">
                                    <th class="text-left py-4 px-4 font-bold text-gray-700">ลำดับ</th>
                                    <th class="text-left py-4 px-4 font-bold text-gray-700">หัวข้อแบบทดสอบ</th>
                                    <th class="text-center py-4 px-4 font-bold text-gray-700">คะแนน</th>
                                    <th class="text-center py-4 px-4 font-bold text-gray-700">เปอร์เซ็นต์</th>
                                    <th class="text-center py-4 px-4 font-bold text-gray-700">สถานะ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(quizze, index) in props.course_quizzes" :key="quizze.id"
                                    class="border-b border-gray-100 hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 transition-all duration-200">
                                    <td class="py-4 px-4">
                                        <span class="inline-flex items-center justify-center w-10 h-10 bg-gradient-to-br from-indigo-400 to-purple-500 text-white rounded-full font-bold shadow-lg">
                                            {{ index + 1 }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="max-w-md">
                                            <p class="font-semibold text-gray-800 text-lg">
                                                {{ expandedQuizzes[quizze.id] ? quizze.title : truncateText(quizze.title, 50).text }}
                                            </p>
                                            <button
                                                v-if="truncateText(quizze.title, 50).isTruncated"
                                                @click="toggleExpanded(quizze.id, 'quiz')"
                                                class="text-indigo-600 hover:text-indigo-800 text-sm font-medium mt-1 transition-colors duration-200"
                                            >
                                                {{ expandedQuizzes[quizze.id] ? 'แสดงน้อยลง' : 'อ่านเพิ่มเติม' }}
                                            </button>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="font-bold text-gray-700 text-lg">
                                            {{ props.member_quizes_results.data.find(result => result.quiz_id === quizze.id)?.score || 0 }}/{{ quizze.total_score }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <div class="flex items-center justify-center">
                                            <div class="w-20 bg-gray-200 rounded-full h-3 mr-3 shadow-inner">
                                                <div :class="`h-3 rounded-full shadow-sm transition-all duration-300 ${getItemProgressColor(((props.member_quizes_results.data.find(result => result.quiz_id === quizze.id)?.score || 0) / quizze.total_score) * 100)}`"
                                                     :style="`width: ${Math.min(((props.member_quizes_results.data.find(result => result.quiz_id === quizze.id)?.score || 0) / quizze.total_score) * 100, 100)}%`"></div>
                                            </div>
                                            <span class="text-sm font-semibold text-gray-700">
                                                {{ (((props.member_quizes_results.data.find(result => result.quiz_id === quizze.id)?.score || 0) / quizze.total_score) * 100).toFixed(1) }}%
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span v-if="props.member_quizes_results.data.find(result => result.quiz_id === quizze.id)"
                                              :class="`px-4 py-2 rounded-full text-sm font-bold shadow-md transition-all duration-200 ${getStatusBadgeStyle(true, 'quiz')}`">
                                            ทำแล้ว
                                        </span>
                                        <span v-else :class="`px-4 py-2 rounded-full text-sm font-bold shadow-md transition-all duration-200 ${getStatusBadgeStyle(false, 'quiz')}`">
                                            ยังไม่ทำ
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Grade Reference -->
                <div class="bg-gradient-to-br from-indigo-50 via-blue-50 to-purple-50 rounded-2xl shadow-2xl p-8 border border-indigo-200 transition-all duration-200">
                    <h3 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                        <div class="p-3 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl mr-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        มาตรฐานการให้เกรด
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4">
                        <div class="text-center p-4 bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200 border border-emerald-200">
                            <p class="font-bold text-emerald-600 text-lg">4.0</p>
                            <p class="text-xs text-gray-600 mt-1">90-100%</p>
                        </div>
                        <div class="text-center p-4 bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200 border border-green-200">
                            <p class="font-bold text-green-600 text-lg">3.5</p>
                            <p class="text-xs text-gray-600 mt-1">85-89%</p>
                        </div>
                        <div class="text-center p-4 bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200 border border-teal-200">
                            <p class="font-bold text-teal-600 text-lg">3.0</p>
                            <p class="text-xs text-gray-600 mt-1">80-84%</p>
                        </div>
                        <div class="text-center p-4 bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200 border border-blue-200">
                            <p class="font-bold text-blue-600 text-lg">2.5</p>
                            <p class="text-xs text-gray-600 mt-1">75-79%</p>
                        </div>
                        <div class="text-center p-4 bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200 border border-indigo-200">
                            <p class="font-bold text-indigo-600 text-lg">2.0</p>
                            <p class="text-xs text-gray-600 mt-1">70-74%</p>
                        </div>
                        <div class="text-center p-4 bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200 border border-yellow-200">
                            <p class="font-bold text-yellow-600 text-lg">1.5</p>
                            <p class="text-xs text-gray-600 mt-1">60-69%</p>
                        </div>
                        <div class="text-center p-4 bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200 border border-red-200">
                            <p class="font-bold text-red-600 text-lg">0</p>
                            <p class="text-xs text-gray-600 mt-1"><50%></p>
                        </div>
                        <div class="text-center p-4 bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200 border border-gray-200">
                            <p class="font-bold text-gray-600 text-lg">ร.</p>
                            <p class="text-xs text-gray-600 mt-1">ขาดงาน</p>
                        </div>
                    </div>
                </div>
            </template>
         </CourseLayout>
    </div>
</template>


