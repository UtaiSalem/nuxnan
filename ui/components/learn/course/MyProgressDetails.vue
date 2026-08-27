 <script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useApi } from '~/composables/useApi';
import { Icon } from '@iconify/vue';
import RadialProgress from "vue3-radial-progress";
import AssignmentSubmissionForm from '~/components/learn/course/AssignmentSubmissionForm.vue';
import ImageGalleryModal from '~/components/ImageGalleryModal.vue';
import ReadingUnlockPanel from '~/components/learn/course/ReadingUnlockPanel.vue';
import { inject, onUnmounted } from 'vue';
import { stripHtml } from '~/utils/textUtils';
import AnswerAttachmentList from '~/components/learn/course/assignments/AnswerAttachmentList.vue';

const { $echo } = useNuxtApp();
const authStore = useAuthStore();

// Image Gallery State
const showGallery = ref(false);
const galleryImages = ref([]);
const galleryStartIndex = ref(0);
const galleryTitle = ref('');

const openGallery = (images, index = 0, title = '') => {
    galleryImages.value = images;
    galleryStartIndex.value = index;
    galleryTitle.value = title;
    showGallery.value = true;
};

const closeGallery = () => {
    showGallery.value = false;
};


const props = defineProps({
    courseId: { type: [String, Number], required: true },
    memberId: { type: [String, Number], required: true },
});

const api = useApi();
const swal = useSweetAlert();
const isCourseAdmin = inject('isCourseAdmin', ref(false));

const loading = ref(true);
const data = ref(null);

// Grading State
const expandedAssignmentId = ref(null);
const answerLoading = ref(false);
const gradingAnswer = ref(null);

const fetchAnswer = async (assignmentId) => {
    answerLoading.value = true;
    gradingAnswer.value = null;
    try {
        const userId = data.value.user_id || data.value.member?.user_id || data.value.member?.user?.id;
        const res = await api.get(`/api/assignments/${assignmentId}/answers`, {
            params: { user_id: userId }
        });
        
        if (res.data && res.data.length > 0) {
            gradingAnswer.value = res.data[0];
            gradingAnswer.value.newPoints = res.data[0].points;
        }
    } catch (e) {
        console.error(e);
        swal.toast('ไม่สามารถโหลดคำตอบได้', 'error');
    } finally {
        answerLoading.value = false;
    }
};

const toggleAssignment = (assign) => {
    if (expandedAssignmentId.value === assign.id) {
        expandedAssignmentId.value = null;
        gradingAnswer.value = null;
    } else {
        expandedAssignmentId.value = assign.id;
        if (isCourseAdmin.value && (assign.submitted || assign.graded)) {
            fetchAnswer(assign.id);
        }
    }
};

const saveGrade = async (assignmentId) => {
    if (!gradingAnswer.value) return;
    try {
        await api.post(`/api/assignments/${assignmentId}/answers/${gradingAnswer.value.id}/set-points`, {
            points: gradingAnswer.value.newPoints,
            course_id: props.courseId
        });
        
        gradingAnswer.value.points = gradingAnswer.value.newPoints;
        swal.toast('บันทึกคะแนนเรียบร้อย', 'success');
        await fetchData(); // Refresh data using existing method
    } catch (e) {
        console.error(e);
        swal.toast('บันทึกคะแนนไม่สำเร็จ', 'error');
    }
};

const onSubmitted = async () => {
    swal.toast('ส่งงานเรียบร้อยแล้ว', 'success');
    expandedAssignmentId.value = null;
    await fetchData();
};

const fetchData = async () => {
    loading.value = true;
    try {
        const res = await api.get(`/api/courses/${props.courseId}/members/${props.memberId}/progress`);
        data.value = {
            assignments: [],
            quizzes: [],
            lessons: [],
            ...res
        };
        
        // Populate form
        if (data.value.member) {
            form.value = {
                member_name: data.value.member.member_name ?? '',
                member_code: data.value.member.member_code != null ? String(data.value.member.member_code) : '',
                order_number: data.value.member.order_number != null ? data.value.member.order_number : '',
                group_id: data.value.member.group_id ?? null,
            };
        }
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchData();
    fetchEligibilityStatus();
    
    // Listen for score resets via Echo
    if ($echo && authStore.user) {
        $echo.private(`user.${authStore.user.id}`)
            .listen('.lesson.score.reset', (e) => {
                if (Number(e.courseId) === Number(props.courseId)) {
                    swal.toast('ข้อมูลคะแนนมีการเปลี่ยนแปลง (รีเซ็ตโดยผู้สอน)', 'info');
                    fetchData();
                    fetchEligibilityStatus();
                }
            });
    }
});

onUnmounted(() => {
    if ($echo && authStore.user) {
        $echo.leave(`user.${authStore.user.id}`);
    }
});

const sourceBadge = computed(() => {
    const s = data.value?.member?.identity_source;
    if (s === 'classroom') return { label: 'ใช้ข้อมูลห้องเรียน', class: 'bg-green-100 text-green-700 border-green-200' };
    if (s === 'academy') return { label: 'ใช้ข้อมูลสถาบัน', class: 'bg-blue-100 text-blue-700 border-blue-200' };
    if (s === 'user') return { label: 'ใช้ข้อมูลทั่วไป', class: 'bg-gray-100 text-gray-700 border-gray-200' };
    if (s === 'course_override') return { label: 'ระบุเฉพาะวิชานี้', class: 'bg-orange-100 text-orange-700 border-orange-200' };
    return null;
});

const stats = computed(() => {
    if (!data.value) return {};
    const d = data.value;
    
    // Calculate total score
    const totalScore = (d.assignments?.reduce((sum, a) => sum + (a.score || 0), 0) || 0) +
                       (d.quizzes?.reduce((sum, q) => sum + (q.score || 0), 0) || 0);
    
    const maxScore = (d.assignments?.reduce((sum, a) => sum + (a.max_score || 0), 0) || 0) +
                     (d.quizzes?.reduce((sum, q) => sum + (q.max_score || 0), 0) || 0);

    return {
        totalScore,
        maxScore,
        grade: d.member?.final_grade || d.member?.draft_grade || d.member?.grade_name || '-',
        gradeProgress: d.member?.grade_progress ?? 0,
        completedLessons: d.lessons?.filter(l => l.completed).length || 0,
        totalLessons: d.lessons?.length || 0,
        completedAssignments: d.assignments?.filter(a => a.submitted).length || 0,
        totalAssignments: d.assignments?.length || 0,
        completedQuizzes: d.quizzes?.filter(q => q.completed).length || 0,
        totalQuizzes: d.quizzes?.length || 0,
        groupName: d.member?.group?.name || 'ไม่มีกลุ่ม',
        scorePercent: maxScore > 0 ? (totalScore / maxScore) * 100 : 0,
    };
});

// Profile Editing
const form = ref({
    member_name: '',
    member_code: '',
    order_number: '',
    group_id: null,
});
const isSaving = ref(false);
const saveSuccess = ref(false);

const saveProfile = async () => {
    isSaving.value = true;
    saveSuccess.value = false;
    try {
        const payload = {
            member_name: form.value.member_name || null,
            member_code: form.value.member_code != null ? String(form.value.member_code) : null,
            order_number: form.value.order_number !== '' ? Number(form.value.order_number) : null,
            group_id: form.value.group_id ?? null,
        };
        await api.patch(`/api/courses/${props.courseId}/members/${props.memberId}/update-own-profile`, payload);
        
        // Update local data without refresh
        if (data.value && data.value.member) {
            Object.assign(data.value.member, payload);
            // Update group details if needed
            if (data.value.groups) {
                if (payload.group_id) {
                    const group = data.value.groups.find(g => g.id === payload.group_id);
                    if (group) data.value.member.group = group;
                } else {
                    data.value.member.group = null;
                }
            }
        }

        swal.success('บันทึกข้อมูลเรียบร้อย');
        saveSuccess.value = true;
        setTimeout(() => saveSuccess.value = false, 3000);
    } catch (e) {
        console.error(e);
        const status = e.response?.status || e.status;
        if (status === 403) {
            swal.error('คุณไม่มีสิทธิ์แก้ไขข้อมูลนี้ (Forbidden)', 'Access Denied');
        } else {
            swal.error(e.message || 'บันทึกข้อมูลไม่สำเร็จ', 'Error');
        }
    } finally {
        isSaving.value = false;
    }
};

const getScoreColor = (score, max) => {
    if (!max) return 'text-gray-500';
    const pct = (score / max) * 100;
    if (pct >= 80) return 'text-green-600';
    if (pct >= 50) return 'text-blue-600';
    return 'text-red-600';
};

const getProgressBarColor = (score, max) => {
    if (!max) return 'bg-gray-400 dark:bg-gray-600';
    const pct = (score / max) * 100;
    if (pct >= 80) return 'bg-gradient-to-r from-green-400 to-green-500';
    if (pct >= 50) return 'bg-gradient-to-r from-blue-400 to-blue-500';
    return 'bg-gradient-to-r from-red-400 to-red-500';
};

const getActivityLabel = (activity, type) => {
    if (activity?.status === 'none') return type === 'assignment' ? 'ไม่มีแบบฝึกหัด' : 'ไม่มีแบบทดสอบ';
    const labels = {
        not_attempted: 'ยังไม่ส่ง',
        submitted: 'รอตรวจ',
        awaiting_grading: 'รอตรวจ',
        scored: 'ตรวจแล้ว',
        passed: 'ผ่าน',
        failed: 'ไม่ผ่าน',
    };
    return labels[activity?.status] || 'ยังไม่ทำ';
};

const getActivityClass = (activity) => {
    if (activity?.status === 'passed' || activity?.status === 'scored') return 'text-green-600';
    if (activity?.status === 'failed') return 'text-red-600';
    if (activity?.status === 'submitted' || activity?.status === 'awaiting_grading') return 'text-amber-600';
    return 'text-gray-500';
};

// Grade color helper
const getGradeColor = (grade) => {
    if (!grade || grade === '-') return { text: 'text-gray-500 dark:text-gray-400', ring: '#9CA3AF', ringStop: '#6B7280' };
    const g = grade.toUpperCase();
    if (g === 'A' || g === 'A+') return { text: 'text-green-600 dark:text-green-400', ring: '#10B981', ringStop: '#059669' };
    if (g === 'B+' || g === 'B') return { text: 'text-blue-600 dark:text-blue-400', ring: '#3B82F6', ringStop: '#2563EB' };
    if (g === 'C+' || g === 'C') return { text: 'text-yellow-600 dark:text-yellow-400', ring: '#F59E0B', ringStop: '#D97706' };
    if (g === 'D+' || g === 'D') return { text: 'text-orange-600 dark:text-orange-400', ring: '#F97316', ringStop: '#EA580C' };
    if (g === 'F') return { text: 'text-red-600 dark:text-red-400', ring: '#EF4444', ringStop: '#DC2626' };
    return { text: 'text-gray-600 dark:text-gray-400', ring: '#9CA3AF', ringStop: '#6B7280' };
};

const gradeColors = computed(() => getGradeColor(stats.value.grade));

// Check if score should be shown
const canShowScore = computed(() => {
    // Admin can always see
    if (isCourseAdmin.value) return true;
    
    // Student must have order_number
    if (data.value?.member?.order_number) return true;
    
    return false;
});

// Tabs
const activeTab = ref('lessons');
const tabs = [
    { id: 'lessons', label: 'คะแนนจากบทเรียน', shortLabel: 'บทเรียน', icon: 'fluent:book-open-24-filled' },
    { id: 'assignments', label: 'คะแนนจากงานที่มอบหมาย', shortLabel: 'งาน', icon: 'fluent:document-text-24-filled' },
    { id: 'quizzes', label: 'คะแนนจากแบบทดสอบ', shortLabel: 'ทดสอบ', icon: 'fluent:quiz-new-24-filled' },
];

const getTabCount = (tabId) => {
    if (!data.value) return { completed: 0, total: 0 };
    switch (tabId) {
        case 'lessons': return { completed: stats.value.completedLessons, total: stats.value.totalLessons };
        case 'assignments': return { completed: stats.value.completedAssignments, total: stats.value.totalAssignments };
        case 'quizzes': return { completed: stats.value.completedQuizzes, total: stats.value.totalQuizzes };
        default: return { completed: 0, total: 0 };
    }
};

// ── Eligibility Status ────────────────────────────────────────────────
const eligibilityStatus = ref(null);
const isLoadingEligibility = ref(false);
const appealReason = ref('');
const isSubmittingAppeal = ref(false);
const showAppealForm = ref(false);
const showReadingPanel = ref(false);
const readingProgress = ref(null);

const fetchEligibilityStatus = async () => {
    isLoadingEligibility.value = true;
    try {
        const res = await api.get(`/api/courses/${props.courseId}/eligibility/my-status`);
        // handle both { data: {...} } and direct response shapes
        eligibilityStatus.value = res?.data || res || null;
    } catch (e) {
        console.error('Failed to fetch eligibility status:', e);
    } finally {
        isLoadingEligibility.value = false;
    }
};

const fetchReadingProgress = async () => {
    try {
        const res = await api.get(`/api/courses/${props.courseId}/eligibility/reading-progress`);
        readingProgress.value = res?.data || res || null;
    } catch (e) {
        console.error('Failed to fetch reading progress:', e);
    }
};

const handleUnlockOption = async (option) => {
    if (option.method === 'appeal') return; // handled inline via form

    // lesson-based reading → toggle panel with progress
    if (option.method === 'reading' && option.lesson_mode) {
        if (!showReadingPanel.value) {
            await fetchReadingProgress();
        }
        showReadingPanel.value = !showReadingPanel.value;
        return;
    }

    const endpointMap = {
        self: `/api/courses/${props.courseId}/eligibility/unlock/self`,
        points: `/api/courses/${props.courseId}/eligibility/unlock/points`,
        reading: `/api/courses/${props.courseId}/eligibility/unlock/reading`,
    };
    const endpoint = endpointMap[option.method];
    if (!endpoint) return;

    try {
        await api.post(endpoint, { method: option.method });

        const immediateUnlock = ['self', 'points'];
        const msg = immediateUnlock.includes(option.method)
            ? 'ปลดล็อคสิทธิ์สอบสำเร็จ!'
            : 'ส่งคำร้องแล้ว รอการพิจารณา';
        swal.toast(msg, 'success');

        await fetchEligibilityStatus();
    } catch (e) {
        console.error('Failed to request unlock:', e);
        swal.toast('ไม่สามารถส่งคำร้องได้', 'error');
    }
};

const handleReadingUnlock = async () => {
    try {
        const res = await api.post(`/api/courses/${props.courseId}/eligibility/unlock/reading`);
        if (res?.unlocked || res?.data?.unlocked) {
            swal.toast('ปลดล็อคสิทธิ์สอบสำเร็จ!', 'success');
            showReadingPanel.value = false;
            await fetchEligibilityStatus();
        } else {
            swal.toast('บทเรียนยังไม่ครบ ไม่สามารถปลดล็อคได้', 'error');
        }
    } catch (e) {
        console.error('Failed to unlock by reading:', e);
        swal.toast('ไม่สามารถปลดล็อคได้', 'error');
    }
};

const submitAppeal = async () => {
    if (!appealReason.value.trim()) return;
    isSubmittingAppeal.value = true;
    try {
        await api.post(`/api/courses/${props.courseId}/eligibility/unlock/appeal`, { reason: appealReason.value });
        swal.toast('ส่งคำอุทธรณ์แล้ว รอการพิจารณา', 'success');
        appealReason.value = '';
        await fetchEligibilityStatus();
    } catch (e) {
        console.error('Failed to submit appeal:', e);
        swal.toast('ไม่สามารถส่งคำอุทธรณ์ได้', 'error');
    } finally {
        isSubmittingAppeal.value = false;
    }
};

// Fetch eligibility on mount alongside the main data
onMounted(() => {
    fetchEligibilityStatus();
});

// ── Grade Acceptance ──────────────────────────────────────────────────
const isAcceptingGrade = ref(false);
const acceptGrade = async () => {
    const confirm = await swal.confirm(
        'ยืนยันผลการเรียน?',
        `คุณยอมรับเกรด ${data.value.member.draft_grade} สำหรับรายวิชานี้ใช่หรือไม่? หากยืนยันแล้วจะไม่สามารถแก้ไขได้`,
        'info',
        'ยืนยันรับเกรด',
        'ยกเลิก'
    );

    if (!confirm.isConfirmed) return;

    isAcceptingGrade.value = true;
    try {
        await api.post(`/api/courses/${props.courseId}/completion/accept-grade`);
        swal.toast('ยืนยันผลการเรียนเรียบร้อยแล้ว', 'success');
        await fetchData(); // Refresh to show final grade
    } catch (e) {
        console.error('Failed to accept grade:', e);
        swal.toast('ยืนยันผลการเรียนไม่สำเร็จ', 'error');
    } finally {
        isAcceptingGrade.value = false;
    }
};

const showGradeAcceptance = computed(() => {
    if (!data.value || !data.value.course || !data.value.member) return false;
    
    // Only show if course is published and student needs to accept
    return data.value.course.finalization_status === 'published' && 
           data.value.member.completion_status === 'pending_acceptance';
});
// ─────────────────────────────────────────────────────────────────────
</script>

<template>
    <div class="space-y-6">
        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-12">
            <Icon icon="eos-icons:loading" class="w-10 h-10 text-blue-600" />
        </div>

        <div v-else-if="data" class="animate-fade-in">
             
             <!-- Profile Settings Card -->
             <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 mb-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                    <div>
                         <h3 class="text-lg font-bold text-gray-800 dark:text-white flex items-center gap-2">
                             <Icon icon="fluent:person-edit-24-filled" class="text-blue-500" />
                             ข้อมูลส่วนตัว
                             <span v-if="data.member?.role === 4" class="px-2 py-0.5 text-[10px] font-bold bg-purple-100 text-purple-700 rounded-full border border-purple-200">
                                 ผู้ดูแลระบบ (Admin)
                             </span>
                             <span v-else class="px-2 py-0.5 text-[10px] font-bold bg-blue-100 text-blue-700 rounded-full border border-blue-200">
                                 นักเรียน (Student)
                             </span>
                             <span v-if="sourceBadge" :class="['px-2 py-0.5 text-[10px] font-bold rounded-full border', sourceBadge.class]">
                                 {{ sourceBadge.label }}
                             </span>
                         </h3>
                         <p class="text-sm text-gray-500">แก้ไขข้อมูลพื้นฐานของคุณในรายวิชานี้ (หากเว้นว่างจะใช้ข้อมูลกลาง)</p>
                    </div>
                    <div class="px-4 py-2 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded-lg text-sm font-medium">
                        กลุ่มเรียน: {{ stats.groupName }}
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เลขที่ (Order No.)</label>
                        <input v-model="form.order_number" type="number" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-blue-500 focus:border-blue-500" :placeholder="data.member?.effective_order_number || 'ระบุเลขที่...'" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">รหัสประจำตัว (Student ID)</label>
                        <input v-model="form.member_code" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-blue-500 focus:border-blue-500" :placeholder="data.member?.effective_member_code || 'ระบุรหัสประจำตัว...'" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อ-นามสกุล (Name)</label>
                        <input v-model="form.member_name" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-blue-500 focus:border-blue-500" :placeholder="data.member?.effective_member_name || 'ระบุชื่อ-นามสกุล...'" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">กลุ่มเรียน (Group)</label>
                        <select v-model="form.group_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option :value="null">-- ไม่ระบุกลุ่ม --</option>
                            <option v-for="group in data.groups" :key="group.id" :value="group.id">
                                {{ group.name }}
                            </option>
                        </select>
                    </div>
                </div>
                
                <div class="mt-6 flex items-center justify-end gap-3">
                     <span v-if="saveSuccess" class="text-green-600 text-sm flex items-center animate-fade-in">
                         <Icon icon="fluent:checkmark-circle-24-filled" class="mr-1" /> บันทึกเรียบร้อย
                     </span>
                     <button 
                        @click="saveProfile" 
                        :disabled="isSaving"
                        class="min-h-[44px] sm:min-h-0 bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-medium transition-colors flex items-center disabled:opacity-50 disabled:cursor-not-allowed">
                        <Icon v-if="isSaving" icon="eos-icons:loading" class="mr-2 animate-spin" />
                        {{ isSaving ? 'กำลังบันทึก...' : 'บันทึกการเปลี่ยนแปลง' }}
                     </button>
                </div>
             </div>

             <!-- Warning for No Order Number -->
             <div v-if="!canShowScore" class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-6 text-center mb-8">
                 <Icon icon="fluent:warning-24-filled" class="w-12 h-12 text-yellow-500 mx-auto mb-2" />
                 <h3 class="text-lg font-bold text-yellow-800 dark:text-yellow-200">ยังไม่มีเลขที่ (Order Number)</h3>
                 <p class="text-yellow-700 dark:text-yellow-300 mt-1">
                     กรุณาระบุเลขที่ของคุณ หรือติดต่อผู้สอนเพื่อตรวจสอบข้อมูล <br>
                     (ระบบแสดงคะแนนสำหรับนักเรียนที่มีเลขที่แล้วเท่านั้น)
                 </p>
             </div>

             <!-- Header Stats (Only Show if canShowScore) -->
             <div v-else class="grid grid-cols-2 gap-3 sm:gap-4 mb-6 sm:mb-8">
                <div class="bg-white dark:bg-gray-800 p-3 sm:p-5 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col items-center justify-center">
                    <div class="text-xs sm:text-sm text-gray-500 mb-1.5 sm:mb-2">เกรดปัจจุบัน</div>
                     <RadialProgress 
                        :diameter="100" 
                        :completed-steps="Math.round(stats.scorePercent)" 
                        :total-steps="100"
                        :stroke-width="8"
                        :inner-stroke-width="8"
                        :start-color="gradeColors.ring"
                        :stop-color="gradeColors.ringStop"
                        inner-stroke-color="#E5E7EB"
                     >
                        <div class="text-center">
                            <span class="text-2xl font-bold" :class="gradeColors.text">{{ stats.gradeProgress }}</span>
                            <div class="text-xs font-semibold" :class="gradeColors.text">({{ stats.grade }})</div>
                        </div>
                     </RadialProgress>
                </div>
                <div class="bg-white dark:bg-gray-800 p-3 sm:p-5 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col items-center justify-center">
                    <div class="text-xs sm:text-sm text-gray-500 mb-1.5 sm:mb-2">คะแนนรวม</div>
                    <RadialProgress 
                        :diameter="100" 
                        :completed-steps="Math.round(stats.scorePercent)" 
                        :total-steps="100"
                        :stroke-width="8"
                        :inner-stroke-width="8"
                         start-color="#10B981"
                         stop-color="#059669"
                         inner-stroke-color="#E5E7EB"
                     >
                        <div class="text-center">
                             <div class="text-xl font-bold text-gray-900 dark:text-white">{{ stats.totalScore }}</div>
                             <div class="text-xs text-gray-400">/ {{ stats.maxScore }}</div>
                        </div>
                     </RadialProgress>
                </div>
             </div>

             <!-- Grade Acceptance Prompt -->
             <div v-if="showGradeAcceptance" class="mb-6 animate-bounce-subtle">
                 <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl p-6 text-white shadow-lg border border-blue-400/30 overflow-hidden relative">
                     <!-- Decoration -->
                     <Icon icon="fluent:ribbon-star-24-filled" class="absolute -right-4 -bottom-4 w-32 h-32 text-white/10 rotate-12" />
                     
                     <div class="relative z-10">
                         <div class="flex items-center gap-3 mb-4">
                             <div class="w-12 h-12 bg-white/20 backdrop-blur-md rounded-xl flex items-center justify-center border border-white/30">
                                 <Icon icon="fluent:mortarboard-24-filled" class="w-7 h-7" />
                             </div>
                             <div>
                                 <h3 class="text-xl font-bold">ประกาศผลการเรียนเบื้องต้น</h3>
                                 <p class="text-blue-100 text-sm">ผู้สอนได้ประกาศผลการเรียนแล้ว กรุณาตรวจสอบและยืนยัน</p>
                             </div>
                         </div>

                         <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 flex flex-wrap items-center justify-between gap-4 mb-6">
                             <div class="flex gap-6">
                                 <div>
                                     <div class="text-xs text-blue-100 mb-1">คะแนนรวม</div>
                                     <div class="text-2xl font-black">{{ data.member.draft_total_score }}</div>
                                 </div>
                                 <div>
                                     <div class="text-xs text-blue-100 mb-1">เกรดที่ได้</div>
                                     <div class="text-2xl font-black text-yellow-300">{{ data.member.draft_grade }}</div>
                                 </div>
                             </div>
                             
                             <div class="flex-1 min-w-[200px] sm:text-right">
                                 <p class="text-xs text-blue-100 italic mb-2">* เมื่อกดยืนยันแล้ว ผลการเรียนจะถูกบันทึกลงในระเบียนประวัติถาวร</p>
                             </div>
                         </div>

                         <div class="flex flex-col sm:flex-row gap-3">
                             <button 
                                @click="acceptGrade"
                                :disabled="isAcceptingGrade"
                                class="flex-1 bg-white text-blue-600 hover:bg-blue-50 px-6 py-3 rounded-xl font-bold transition-all shadow-md flex items-center justify-center gap-2 disabled:opacity-50"
                             >
                                 <Icon v-if="isAcceptingGrade" icon="eos-icons:loading" class="animate-spin" />
                                 <Icon v-else icon="fluent:checkmark-circle-24-filled" />
                                 ยืนยันรับผลการเรียน
                             </button>
                             
                             <button 
                                class="flex-1 bg-white/10 hover:bg-white/20 text-white px-6 py-3 rounded-xl font-bold transition-all border border-white/30 flex items-center justify-center gap-2"
                             >
                                 <Icon icon="fluent:chat-help-24-filled" />
                                 สอบถาม / อุทธรณ์เกรด
                             </button>
                         </div>
                     </div>
                 </div>
             </div>

             <!-- Eligibility Status Section -->
             <div v-if="!isLoadingEligibility && eligibilityStatus" class="mb-6">
                 <!-- Eligible -->
                 <div
                     v-if="eligibilityStatus.can_take_exam || eligibilityStatus.status === 'eligible'"
                     class="flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl"
                 >
                     <Icon icon="heroicons:check-circle" class="w-6 h-6 text-green-600 dark:text-green-400 flex-shrink-0" />
                     <div>
                         <p class="font-semibold text-green-800 dark:text-green-200">มีสิทธิ์สอบ</p>
                         <p class="text-xs text-green-600 dark:text-green-400">คุณมีสิทธิ์เข้าสอบในรายวิชานี้</p>
                     </div>
                 </div>

                 <!-- At Risk -->
                 <div
                     v-else-if="eligibilityStatus.status === 'at_risk'"
                     class="flex items-center gap-3 p-4 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-xl"
                 >
                     <Icon icon="heroicons:exclamation-triangle" class="w-6 h-6 text-orange-600 dark:text-orange-400 flex-shrink-0" />
                     <div>
                         <p class="font-semibold text-orange-800 dark:text-orange-200">กลุ่มเสี่ยง</p>
                         <p class="text-xs text-orange-600 dark:text-orange-400">
                             อัตราขาดเรียน {{ eligibilityStatus.absence_percent ?? '-' }}% — ระวังอาจหมดสิทธิ์สอบ
                         </p>
                     </div>
                 </div>

                 <!-- Unlocked -->
                 <div
                     v-else-if="eligibilityStatus.status === 'unlocked'"
                     class="flex items-center gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl"
                 >
                     <Icon icon="heroicons:lock-open" class="w-6 h-6 text-blue-600 dark:text-blue-400 flex-shrink-0" />
                     <div>
                         <p class="font-semibold text-blue-800 dark:text-blue-200">ปลดล็อคแล้ว</p>
                         <p class="text-xs text-blue-600 dark:text-blue-400">สิทธิ์สอบของคุณถูกปลดล็อคโดยผู้สอน</p>
                     </div>
                 </div>

                 <!-- Ineligible -->
                 <div
                     v-else-if="!eligibilityStatus.can_take_exam && eligibilityStatus.status === 'ineligible'"
                     class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl overflow-hidden"
                 >
                     <div class="flex items-center gap-3 p-4">
                         <Icon icon="heroicons:x-circle" class="w-6 h-6 text-red-600 dark:text-red-400 flex-shrink-0" />
                         <div>
                             <p class="font-semibold text-red-800 dark:text-red-200">หมดสิทธิ์สอบ</p>
                             <p class="text-xs text-red-600 dark:text-red-400">
                                 อัตราขาดเรียน {{ eligibilityStatus.absence_percent ?? '-' }}% — เกินเกณฑ์ที่กำหนด
                             </p>
                         </div>
                     </div>

                     <!-- Unlock Options -->
                         <div class="space-y-2">
                             <template v-for="option in eligibilityStatus.unlock_options" :key="option.method">
                                 <!-- Appeal: inline form (Secondary/Hidden) -->
                                 <div v-if="option.method === 'appeal'" class="mt-4">
                                     <button 
                                        @click="showAppealForm = !showAppealForm"
                                        class="min-h-[44px] sm:min-h-0 w-full flex items-center justify-center gap-2 px-4 py-2 text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                                     >
                                         <Icon :icon="showAppealForm ? 'heroicons:chevron-up' : 'heroicons:chevron-down'" class="w-4 h-4" />
                                         อุทธรณ์สิทธิ์สอบ (สำหรับกรณีจำเป็น)
                                     </button>

                                     <div v-if="showAppealForm" class="mt-2 p-3 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg shadow-sm animate-fade-in">
                                         <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">ระบุเหตุผลในการอุทธรณ์:</p>
                                         <textarea
                                             v-model="appealReason"
                                             rows="3"
                                             placeholder="เช่น มีใบรับรองแพทย์, ติดภารกิจทางบ้าน ฯลฯ"
                                             class="w-full text-xs px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-400 resize-none"
                                         ></textarea>
                                         <button
                                             @click="submitAppeal"
                                             :disabled="!appealReason.trim() || isSubmittingAppeal"
                                             class="min-h-[44px] sm:min-h-0 mt-2 w-full inline-flex items-center justify-center gap-2 px-3 py-2 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors shadow-sm"
                                         >
                                             <Icon v-if="isSubmittingAppeal" icon="heroicons:arrow-path" class="w-3.5 h-3.5 animate-spin" />
                                             ส่งคำอุทธรณ์
                                         </button>
                                     </div>
                                 </div>

                                 <!-- Other options: button -->
                                 <button
                                     v-else
                                     @click="handleUnlockOption(option)"
                                     class="w-full flex items-center justify-between px-4 py-3 bg-white dark:bg-gray-800 border border-red-200 dark:border-red-700 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-all text-left shadow-sm group"
                                 >
                                     <div class="flex items-center gap-3">
                                         <div class="w-8 h-8 rounded-full flex items-center justify-center bg-red-50 dark:bg-red-900/40 text-red-600 dark:text-red-400 group-hover:scale-110 transition-transform">
                                             <Icon :icon="option.method === 'points' ? 'fluent:star-24-filled' : (option.method === 'reading' ? 'fluent:book-open-24-filled' : 'fluent:flash-24-filled')" class="w-5 h-5" />
                                         </div>
                                         <div>
                                             <div class="flex items-center gap-2">
                                                <p class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ option.label }}</p>
                                             </div>
                                             <p v-if="option.cost" class="text-[10px] text-gray-500 dark:text-gray-400">หักคะแนนสะสมทันที</p>
                                             <p v-if="option.method === 'self'" class="text-[10px] text-gray-500 dark:text-gray-400">ใช้สิทธิ์ปลดล็อคด้วยตนเอง</p>
                                             <p v-if="option.minutes" class="text-[10px] text-gray-500 dark:text-gray-400">ต้องสะสมเวลาการอ่านให้ครบ</p>
                                             <p v-if="option.lesson_mode" class="text-[10px] text-gray-500 dark:text-gray-400">ต้องอ่านบทเรียนที่กำหนดให้ครบ</p>
                                         </div>
                                     </div>
                                     <Icon :icon="showReadingPanel && option.method === 'reading' ? 'heroicons:chevron-up' : 'heroicons:arrow-right'" class="w-4 h-4 text-gray-300 group-hover:text-red-500 transition-colors" />
                                 </button>

                                 <!-- Reading Progress Panel (Lesson-based) -->
                                 <ReadingUnlockPanel 
                                     v-if="showReadingPanel && readingProgress && option.method === 'reading'"
                                     :progress="readingProgress"
                                     :courseId="courseId"
                                     @unlock="handleReadingUnlock"
                                     class="mt-2 animate-fade-in"
                                 />
                             </template>
                         </div>
                 </div>
             </div>
             <!-- /Eligibility Status Section -->

             <!-- Tabs Navigation -->
             <div class="bg-gray-100 dark:bg-gray-800/80 rounded-xl sm:rounded-2xl p-1 sm:p-1.5 mb-4 sm:mb-6 flex gap-1 overflow-x-auto">
                 <button 
                    v-for="tab in tabs" 
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    class="min-h-[44px] sm:min-h-0 relative flex-1 min-w-0 flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-2 px-2 sm:px-4 py-2.5 sm:py-3 rounded-lg sm:rounded-xl text-xs sm:text-sm font-semibold transition-all duration-200 cursor-pointer select-none"
                    :class="activeTab === tab.id 
                        ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-md ring-1 ring-black/5 dark:ring-white/10' 
                        : 'text-gray-500 dark:text-gray-400 hover:bg-white/60 dark:hover:bg-gray-700/50 hover:text-gray-700 dark:hover:text-gray-200 active:scale-[0.97]'"
                 >
                     <Icon :icon="tab.icon" class="w-5 h-5 sm:w-5 sm:h-5 flex-shrink-0" />
                     <span class="truncate hidden sm:inline">{{ tab.label }}</span>
                     <span class="truncate sm:hidden text-[11px] leading-tight text-center">{{ tab.shortLabel }}</span>
                     <span 
                        v-if="data"
                        class="text-[10px] sm:text-xs font-bold px-1.5 py-0.5 rounded-full flex-shrink-0 tabular-nums"
                        :class="activeTab === tab.id 
                            ? 'bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400' 
                            : 'bg-gray-200/80 dark:bg-gray-600 text-gray-500 dark:text-gray-400'"
                     >
                        {{ getTabCount(tab.id).completed }}/{{ getTabCount(tab.id).total }}
                     </span>
                 </button>
             </div>

             <!-- Tab Content -->
             <div class="bg-white dark:bg-gray-800 rounded-xl sm:rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                 
                 <!-- Lessons Tab -->
                 <div v-if="activeTab === 'lessons'">
                     <div class="px-3 sm:px-4 py-3 sm:py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex justify-between items-center">
                         <h3 class="text-sm sm:text-base font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                             <Icon icon="fluent:book-open-24-filled" class="w-4 h-4 sm:w-5 sm:h-5 text-blue-500" />
                             รายการบทเรียน
                         </h3>
                         <span class="text-xs sm:text-sm text-gray-500 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full">{{ stats.completedLessons }}/{{ stats.totalLessons }}</span>
                     </div>
                     <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div v-if="data.lessons && data.lessons.length === 0" class="p-8 text-center text-gray-500">
                            ไม่มีบทเรียนในรายวิชานี้
                        </div>
                         <div v-for="lesson in data.lessons" :key="lesson.id" class="px-3 sm:px-4 py-3 sm:py-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                              <div class="flex flex-col gap-2 sm:gap-3">
                                  <div class="flex justify-between items-center gap-2">
                                      <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
                                          <div class="flex-shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-full flex items-center justify-center"
                                              :class="lesson.completed ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-400'">
                                              <Icon :icon="lesson.completed ? 'fluent:checkmark-24-filled' : 'fluent:circle-24-regular'" class="w-4 h-4 sm:w-5 sm:h-5" />
                                          </div>
                                          <div class="font-medium text-sm sm:text-base text-gray-900 dark:text-white truncate">{{ lesson.title }}</div>
                                      </div>
                                      <span class="text-[10px] sm:text-xs px-2 py-1 rounded-full flex-shrink-0 font-medium"
                                          :class="lesson.completed ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'">
                                          {{ lesson.completed ? 'เรียนแล้ว' : 'ยังไม่เรียน' }}
                                      </span>
                                  </div>
                                  <!-- Reading + Activity progress -->
                                  <div class="mt-1 space-y-2.5">
                                      <!-- Reading progress (topic-based) -->
                                      <div v-if="lesson.reading_progress?.total_topics > 0">
                                          <div class="flex items-center justify-between mb-1 text-xs">
                                              <span class="text-gray-500">การอ่านบทเรียน</span>
                                              <span class="font-medium" :class="lesson.reading_progress.progress_percentage >= 100 ? 'text-green-600' : 'text-blue-600'">
                                                  {{ lesson.reading_progress.progress_percentage }}% ({{ lesson.reading_progress.completed_topics }}/{{ lesson.reading_progress.total_topics }} หัวข้อ)
                                              </span>
                                          </div>
                                          <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                                              <div
                                                  class="h-full rounded-full transition-all duration-500 ease-out"
                                                  :class="lesson.reading_progress.progress_percentage >= 100 ? 'bg-gradient-to-r from-green-400 to-green-500' : 'bg-gradient-to-r from-blue-400 to-blue-500'"
                                                  :style="{ width: `${lesson.reading_progress.progress_percentage}%` }"
                                              ></div>
                                          </div>
                                      </div>

                                      <!-- Activity scores (assignments / quizzes) -->
                                      <template v-for="activity in [
                                          { key: 'assignments', label: 'คะแนนแบบฝึกหัด', color: 'bg-purple-500', type: 'assignment' },
                                          { key: 'quizzes', label: 'คะแนนแบบทดสอบ', color: 'bg-amber-500', type: 'quiz' }
                                      ]" :key="activity.key">
                                          <div v-if="lesson.activity_progress?.[activity.key] && lesson.activity_progress[activity.key].status !== 'none'">
                                              <div class="flex items-center justify-between mb-1 text-xs">
                                                  <span class="text-gray-500">{{ activity.label }}</span>
                                                  <span v-if="canShowScore && lesson.activity_progress[activity.key].score !== null" class="font-bold" :class="getActivityClass(lesson.activity_progress[activity.key])">
                                                      {{ lesson.activity_progress[activity.key].score }}/{{ lesson.activity_progress[activity.key].max_score }} ({{ lesson.activity_progress[activity.key].score_percentage }}%)
                                                  </span>
                                                  <span v-else-if="canShowScore" class="font-medium" :class="getActivityClass(lesson.activity_progress[activity.key])">
                                                      {{ getActivityLabel(lesson.activity_progress[activity.key], activity.type) }}
                                                  </span>
                                                  <span v-else class="text-gray-400 italic">ซ่อนคะแนน</span>
                                              </div>
                                              <div class="w-full h-2.5 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                                                  <div
                                                      class="h-full rounded-full transition-all duration-500"
                                                      :class="canShowScore && lesson.activity_progress[activity.key].score !== null ? activity.color : 'bg-gray-400 dark:bg-gray-600'"
                                                      :style="{ width: `${canShowScore && lesson.activity_progress[activity.key].score !== null ? lesson.activity_progress[activity.key].score_percentage : 0}%` }"
                                                  ></div>
                                              </div>
                                          </div>
                                      </template>

                                      <div v-if="!lesson.reading_progress?.total_topics && !lesson.has_graded_activity" class="text-xs text-gray-400">
                                          ไม่มีหัวข้ออ่านหรือกิจกรรมในบทเรียนนี้
                                      </div>
                                  </div>
                              </div>
                          </div>
                     </div>
                 </div>

                 <!-- Assignments Tab -->
                 <div v-if="activeTab === 'assignments'">
                     <div class="px-3 sm:px-4 py-3 sm:py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex justify-between items-center">
                         <h3 class="text-sm sm:text-base font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                             <Icon icon="fluent:document-text-24-filled" class="w-4 h-4 sm:w-5 sm:h-5 text-orange-500" />
                             รายการงานที่มอบหมาย
                         </h3>
                         <span class="text-xs sm:text-sm text-gray-500 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full">{{ stats.completedAssignments }}/{{ stats.totalAssignments }}</span>
                     </div>
                     <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div v-if="data.assignments && data.assignments.length === 0" class="p-8 text-center text-gray-500">
                            ไม่มีงานที่มอบหมายในรายวิชานี้
                        </div>
                         <div v-for="assign in data.assignments" :key="assign.id" class="px-3 sm:px-4 py-3 sm:py-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                              <div class="flex flex-col gap-2 sm:gap-3">
                                  <div class="flex justify-between items-start gap-2">
                                      <div class="flex-1 min-w-0">
                                          <div class="font-medium text-sm sm:text-base text-gray-900 dark:text-white">{{ assign.title }}</div>
                                          <div class="text-xs mt-1" :class="{
                                              'text-green-600': assign.submitted,
                                              'text-yellow-600': !assign.submitted
                                          }">
                                              {{ assign.submitted ? (assign.graded ? 'ตรวจแล้ว' : 'ส่งแล้ว') : 'ยังไม่ส่ง' }}
                                          </div>
                                          <div class="text-xs text-gray-400 mt-1" v-if="assign.submitted_at">
                                              ส่งเมื่อ: {{ new Date(assign.submitted_at).toLocaleDateString('th-TH') }}
                                          </div>
                                      </div>
                                      <div class="text-right flex-shrink-0" v-if="canShowScore">
                                          <div class="font-bold text-lg" :class="getScoreColor(assign.score, assign.max_score)">
                                              {{ assign.score !== null ? assign.score : '-' }}
                                          </div>
                                          <div class="text-xs text-gray-400">เต็ม {{ assign.max_score }}</div>
                                          
                                          <!-- Buttons -->
                                          <div class="mt-2 text-right">
                                              <!-- Student: Submit Button -->
                                              <button 
                                                  v-if="!isCourseAdmin && !assign.submitted"
                                                  @click="toggleAssignment(assign)"
                                                  class="min-h-[44px] sm:min-h-0 text-xs px-3 py-1.5 rounded-lg transition-colors bg-blue-600 text-white hover:bg-blue-700"
                                              >
                                                  {{ expandedAssignmentId === assign.id ? 'ปิด' : 'ส่งงาน' }}
                                              </button>

                                              <!-- Teacher: Grade Button -->
                                              <button 
                                                  v-if="isCourseAdmin && (assign.submitted || assign.graded)"
                                                  @click="toggleAssignment(assign)"
                                                  class="min-h-[44px] sm:min-h-0 text-xs px-3 py-1.5 rounded-lg transition-colors border"
                                                  :class="expandedAssignmentId === assign.id ? 'bg-orange-50 text-orange-600 border-orange-200' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                              >
                                                  {{ expandedAssignmentId === assign.id ? 'ปิดการตรวจ' : (assign.graded ? 'แก้ไขคะแนน' : 'ตรวจให้คะแนน') }}
                                              </button>
                                          </div>
                                      </div>
                                      <div class="text-right flex-shrink-0" v-else>
                                          <div class="text-xs text-gray-400 italic">ซ่อนคะแนน</div>
                                          
                                          <!-- Buttons (Still allow submit if enabled) -->
                                          <div class="mt-2 text-right">
                                              <button 
                                                  v-if="!isCourseAdmin && !assign.submitted"
                                                  @click="toggleAssignment(assign)"
                                                  class="min-h-[44px] sm:min-h-0 text-xs px-3 py-1.5 rounded-lg transition-colors bg-blue-600 text-white hover:bg-blue-700"
                                              >
                                                  {{ expandedAssignmentId === assign.id ? 'ปิด' : 'ส่งงาน' }}
                                              </button>
                                          </div>
                                      </div>
                                  </div>
                                  <!-- Progress Bar -->
                                  <div v-if="assign.max_score > 0 && canShowScore" class="mt-2">
                                      <div class="flex items-center justify-between mb-1">
                                          <span class="text-xs text-gray-500">ความคืบหน้า</span>
                                          <span class="text-xs font-medium" :class="getScoreColor(assign.score, assign.max_score)">
                                              {{ assign.score !== null ? Math.round((assign.score / assign.max_score) * 100) + '%' : '0%' }}
                                          </span>
                                      </div>
                                      <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                                          <div
                                              class="h-full rounded-full transition-all duration-500 ease-out"
                                              :class="assign.score !== null && assign.max_score > 0 ? getProgressBarColor(assign.score, assign.max_score) : 'bg-gray-400 dark:bg-gray-600'"
                                              :style="{ width: assign.score !== null && assign.max_score > 0 ? `${Math.min((assign.score / assign.max_score) * 100, 100)}%` : '0%' }"
                                          ></div>
                                      </div>
                                  </div>

                                  <!-- Inline Submission / Grading Area -->
                                  <div v-if="expandedAssignmentId === assign.id" class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                      <!-- Student View -->
                                      <div v-if="!isCourseAdmin">
                                          <AssignmentSubmissionForm 
                                              :assignment="assign"
                                              :courseId="courseId"
                                              @submitted="onSubmitted"
                                              @cancel="expandedAssignmentId = null"
                                          />
                                      </div>

                                      <!-- Teacher View -->
                                      <div v-else>
                                          <div v-if="answerLoading" class="flex justify-center py-4">
                                              <Icon icon="eos-icons:loading" class="w-6 h-6 text-orange-500" />
                                          </div>
                                          <div v-else-if="gradingAnswer">
                                              <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl mb-4 text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap">
                                                  {{ stripHtml(gradingAnswer.content) }}
                                                  <div v-if="gradingAnswer.images?.length" class="mt-3 flex flex-wrap gap-2">
                                                      <img 
                                                          v-for="(img, index) in gradingAnswer.images" 
                                                          :key="img.id" 
                                                          :src="img.full_url || img.image_url" 
                                                          class="w-20 h-20 object-cover rounded-lg border cursor-pointer hover:opacity-80 hover:ring-2 hover:ring-blue-500 transition-all" 
                                                          @click="openGallery(gradingAnswer.images, index, 'รูปภาพจากงานที่ส่ง')"
                                                      />
                                                  </div>
                                                  <AnswerAttachmentList :attachments="gradingAnswer.attachments" title="ไฟล์แนบ" class="mt-3" />
                                              </div>
                                              
                                              <div class="flex items-center gap-3">
                                                  <div class="font-bold text-sm">คะแนน:</div>
                                                  <input 
                                                      type="number" 
                                                      v-model.number="gradingAnswer.newPoints"
                                                      :max="assign.max_score"
                                                      min="0"
                                                      class="w-20 px-2 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 focus:ring-2 focus:ring-orange-500 outline-none text-center font-bold"
                                                  />
                                                  <span class="text-sm text-gray-500">/ {{ assign.max_score }}</span>
                                                  <button 
                                                      @click="saveGrade(assign.id)"
                                                      class="min-h-[44px] sm:min-h-0 ml-auto px-4 py-1.5 bg-orange-500 text-white rounded-lg text-sm font-bold hover:bg-orange-600 shadow-sm"
                                                  >
                                                      บันทึก
                                                  </button>
                                              </div>
                                          </div>
                                          <div v-else class="text-center py-4 text-gray-500 text-sm">
                                              ไม่พบคำตอบ หรือ ยังไม่ได้ส่งงาน
                                          </div>
                                      </div>
                                  </div>
                              </div>
                          </div>
                     </div>
                 </div>

                 <!-- Quizzes Tab -->
                 <div v-if="activeTab === 'quizzes'">
                     <div class="px-3 sm:px-4 py-3 sm:py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex justify-between items-center">
                         <h3 class="text-sm sm:text-base font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                             <Icon icon="fluent:quiz-new-24-filled" class="w-4 h-4 sm:w-5 sm:h-5 text-purple-500" />
                             รายการแบบทดสอบ
                         </h3>
                         <span class="text-xs sm:text-sm text-gray-500 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full">{{ stats.completedQuizzes }}/{{ stats.totalQuizzes }}</span>
                     </div>
                     <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div v-if="data.quizzes && data.quizzes.length === 0" class="p-8 text-center text-gray-500">
                            ไม่มีแบบทดสอบในรายวิชานี้
                        </div>
                         <div v-for="quiz in data.quizzes" :key="quiz.id" class="px-3 sm:px-4 py-3 sm:py-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                              <div class="flex flex-col gap-2 sm:gap-3">
                                  <div class="flex justify-between items-start gap-2">
                                      <div class="flex-1 min-w-0">
                                          <div class="font-medium text-sm sm:text-base text-gray-900 dark:text-white">{{ quiz.title }}</div>
                                          <div class="text-xs mt-1 flex items-center gap-2" :class="{
                                              'text-green-600': quiz.completed,
                                              'text-gray-500': !quiz.completed
                                          }">
                                              <span>{{ quiz.completed ? `ทำแล้ว (${quiz.attempt_count} ครั้ง)` : 'ยังไม่ทำ' }}</span>
                                              <span v-if="quiz.completed && quiz.passed" class="text-green-600 bg-green-100 px-1.5 py-0.5 rounded text-[10px]">ผ่าน</span>
                                              <span v-if="quiz.completed && !quiz.passed" class="text-red-600 bg-red-100 px-1.5 py-0.5 rounded text-[10px]">ไม่ผ่าน</span>
                                          </div>
                                          <div class="text-xs text-gray-400 mt-1" v-if="quiz.completed_at">
                                              ล่าสุด: {{ new Date(quiz.completed_at).toLocaleDateString('th-TH') }}
                                          </div>
                                      </div>
                                      <div class="text-right flex-shrink-0">
                                          <template v-if="canShowScore">
                                              <div class="font-bold text-lg" :class="getScoreColor(quiz.score, quiz.max_score)">
                                                  {{ quiz.score !== null ? quiz.score : '-' }}
                                              </div>
                                              <div class="text-xs text-gray-400">เต็ม {{ quiz.max_score }}</div>
                                          </template>
                                          <div v-else class="text-xs text-gray-400 italic mb-2">ซ่อนคะแนน</div>
                                          
                                          <div v-if="!quiz.passed">
                                             <NuxtLink :to="`/Learn/Courses/${courseId}/quizzes/${quiz.id}`" class="text-xs bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700 transition-colors">
                                                 ทำแบบทดสอบ
                                             </NuxtLink>
                                          </div>
                                      </div>
                                  </div>
                                  <!-- Progress Bar -->
                                  <div v-if="quiz.completed && quiz.max_score > 0 && canShowScore" class="mt-2">
                                      <div class="flex items-center justify-between mb-1">
                                          <span class="text-xs text-gray-500">ความคืบหน้า</span>
                                          <span class="text-xs font-medium" :class="getScoreColor(quiz.score, quiz.max_score)">
                                              {{ Math.round((quiz.score / quiz.max_score) * 100) }}%
                                          </span>
                                      </div>
                                      <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                                          <div
                                              class="h-full rounded-full transition-all duration-500 ease-out"
                                              :class="getProgressBarColor(quiz.score, quiz.max_score)"
                                              :style="{ width: `${Math.min((quiz.score / quiz.max_score) * 100, 100)}%` }"
                                          ></div>
                                      </div>
                                  </div>
                              </div>
                          </div>
                     </div>
                 </div>

             </div>
        </div>
        <div v-else class="flex flex-col items-center justify-center py-12 text-gray-500">
            <Icon icon="fluent:error-circle-24-filled" class="w-12 h-12 text-red-400 mb-2" />
            <p>ไม่สามารถโหลดข้อมูลได้</p>
            <button @click="fetchData" class="mt-2 text-blue-600 hover:underline">ลองใหม่</button>
        </div>

        <!-- Image Gallery Modal -->
        <ImageGalleryModal 
            :show="showGallery"
            :images="galleryImages"
            :start-index="galleryStartIndex"
            :title="galleryTitle"
            @close="closeGallery"
        />
    </div>
</template>
