 <script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useApi } from '~/composables/useApi';
import { Icon } from '@iconify/vue';
import RadialProgress from "vue3-radial-progress";
import AssignmentSubmissionForm from '~/components/learn/course/AssignmentSubmissionForm.vue';
import { inject } from 'vue';


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
                member_name: data.value.member.member_name || data.value.member.user?.name || '',
                member_code: data.value.member.member_code || '',
                order_number: data.value.member.order_number || '',
                group_id: data.value.member.group_id || null,
            };
        }
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

onMounted(fetchData);

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
        grade: d.member?.grade_name || '-',
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
        await api.patch(`/api/courses/${props.courseId}/members/${props.memberId}/update-own-profile`, form.value);
        
        // Update local data without refresh
        if (data.value && data.value.member) {
            Object.assign(data.value.member, form.value);
            // Update group details if needed
            if (form.value.group_id && data.value.groups) {
                const group = data.value.groups.find(g => g.id === form.value.group_id);
                if (group) data.value.member.group = group;
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
    { id: 'lessons', label: 'บทเรียน', icon: 'fluent:book-open-24-filled' },
    { id: 'assignments', label: 'งานที่มอบหมาย', icon: 'fluent:document-text-24-filled' },
    { id: 'quizzes', label: 'แบบทดสอบ', icon: 'fluent:quiz-new-24-filled' },
];
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
                         </h3>
                         <p class="text-sm text-gray-500">แก้ไขข้อมูลพื้นฐานของคุณในรายวิชานี้</p>
                    </div>
                    <div class="px-4 py-2 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded-lg text-sm font-medium">
                        กลุ่มเรียน: {{ stats.groupName }}
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เลขที่ (Order No.)</label>
                        <input v-model="form.order_number" type="number" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="ระบุเลขที่..." />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">รหัสประจำตัว (Student ID)</label>
                        <input v-model="form.member_code" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="ระบุรหัสประจำตัว..." />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อ-นามสกุล (Name)</label>
                        <input v-model="form.member_name" type="text" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="ระบุชื่อ-นามสกุล..." />
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
                        class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-medium transition-colors flex items-center disabled:opacity-50 disabled:cursor-not-allowed">
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
             <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col items-center justify-center">
                    <div class="text-sm text-gray-500 mb-2">เกรดปัจจุบัน</div>
                     <RadialProgress 
                        :diameter="100" 
                        :completed-steps="100" 
                        :total-steps="100"
                        :stroke-width="8"
                        :inner-stroke-width="8"
                        start-color="#3B82F6"
                        stop-color="#2563EB"
                        inner-stroke-color="#E5E7EB"
                     >
                        <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ stats.grade }}</span>
                     </RadialProgress>
                </div>
                <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col items-center justify-center">
                    <div class="text-sm text-gray-500 mb-2">คะแนนรวม</div>
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
                 <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="text-sm text-gray-500 mb-1">งานที่ส่งแล้ว</div>
                    <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                        {{ stats.completedAssignments }} <span class="text-sm text-gray-400 font-normal">/ {{ stats.totalAssignments }}</span>
                    </div>
                </div>
                 <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="text-sm text-gray-500 mb-1">ทดสอบแล้ว</div>
                    <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">
                        {{ stats.completedQuizzes }} <span class="text-sm text-gray-400 font-normal">/ {{ stats.totalQuizzes }}</span>
                    </div>
                </div>
             </div>

             <!-- Tabs Navigation -->
             <div class="flex border-b border-gray-200 dark:border-gray-700 mb-6 overflow-x-auto">
                 <button 
                    v-for="tab in tabs" 
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    class="px-4 py-3 text-sm font-medium flex items-center gap-2 whitespace-nowrap transition-colors border-b-2"
                    :class="activeTab === tab.id 
                        ? 'border-blue-600 text-blue-600 dark:text-blue-400' 
                        : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                 >
                     <Icon :icon="tab.icon" class="w-5 h-5" />
                     {{ tab.label }}
                 </button>
             </div>

             <!-- Tab Content -->
             <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                 
                 <!-- Lessons Tab -->
                 <div v-if="activeTab === 'lessons'">
                     <div class="p-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex justify-between items-center">
                         <h3 class="font-semibold text-gray-800 dark:text-white">รายการบทเรียน</h3>
                         <span class="text-sm text-gray-500">{{ stats.completedLessons }}/{{ stats.totalLessons }}</span>
                     </div>
                     <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div v-if="data.lessons && data.lessons.length === 0" class="p-8 text-center text-gray-500">
                            ไม่มีบทเรียนในรายวิชานี้
                        </div>
                         <div v-for="lesson in data.lessons" :key="lesson.id" class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                              <div class="flex flex-col gap-3">
                                  <div class="flex justify-between items-center">
                                      <div class="flex items-center gap-3 flex-1">
                                          <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center"
                                              :class="lesson.completed ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-400'">
                                              <Icon :icon="lesson.completed ? 'fluent:checkmark-24-filled' : 'fluent:circle-24-regular'" class="w-5 h-5" />
                                          </div>
                                          <div class="font-medium text-gray-900 dark:text-white">{{ lesson.title }}</div>
                                      </div>
                                      <span class="text-xs px-2 py-1 rounded-full flex-shrink-0"
                                          :class="lesson.completed ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'">
                                          {{ lesson.completed ? 'เรียนแล้ว' : 'ยังไม่เรียน' }}
                                      </span>
                                  </div>
                                  <!-- Progress Bar -->
                                  <div v-if="data.lessons.length > 0" class="mt-2">
                                      <div class="flex items-center justify-between mb-1">
                                          <span class="text-xs text-gray-500">ความคืบหน้า</span>
                                          <span class="text-xs font-medium" :class="lesson.completed ? 'text-green-600' : 'text-gray-400'">
                                              {{ lesson.completed ? '100%' : '0%' }}
                                          </span>
                                      </div>
                                      <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                                          <div
                                              class="h-full rounded-full transition-all duration-500 ease-out"
                                              :class="lesson.completed ? 'bg-gradient-to-r from-green-400 to-green-500' : 'bg-gray-400 dark:bg-gray-600'"
                                              :style="{ width: lesson.completed ? '100%' : '0%' }"
                                          ></div>
                                      </div>
                                  </div>
                              </div>
                          </div>
                     </div>
                 </div>

                 <!-- Assignments Tab -->
                 <div v-if="activeTab === 'assignments'">
                     <div class="p-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex justify-between items-center">
                         <h3 class="font-semibold text-gray-800 dark:text-white">รายการงานที่มอบหมาย</h3>
                         <span class="text-sm text-gray-500">{{ stats.completedAssignments }}/{{ stats.totalAssignments }}</span>
                     </div>
                     <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div v-if="data.assignments && data.assignments.length === 0" class="p-8 text-center text-gray-500">
                            ไม่มีงานที่มอบหมายในรายวิชานี้
                        </div>
                         <div v-for="assign in data.assignments" :key="assign.id" class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                              <div class="flex flex-col gap-3">
                                  <div class="flex justify-between items-start">
                                      <div class="flex-1">
                                          <div class="font-medium text-gray-900 dark:text-white">{{ assign.title }}</div>
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
                                                  class="text-xs px-3 py-1.5 rounded-lg transition-colors bg-blue-600 text-white hover:bg-blue-700"
                                              >
                                                  {{ expandedAssignmentId === assign.id ? 'ปิด' : 'ส่งงาน' }}
                                              </button>

                                              <!-- Teacher: Grade Button -->
                                              <button 
                                                  v-if="isCourseAdmin && (assign.submitted || assign.graded)"
                                                  @click="toggleAssignment(assign)"
                                                  class="text-xs px-3 py-1.5 rounded-lg transition-colors border"
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
                                                  class="text-xs px-3 py-1.5 rounded-lg transition-colors bg-blue-600 text-white hover:bg-blue-700"
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
                                                  {{ gradingAnswer.content }}
                                                  <div v-if="gradingAnswer.images?.length" class="mt-3 flex flex-wrap gap-2">
                                                      <img v-for="img in gradingAnswer.images" :key="img.id" :src="img.full_url || img.image_url" class="w-20 h-20 object-cover rounded-lg border cursor-pointer" />
                                                  </div>
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
                                                      class="ml-auto px-4 py-1.5 bg-orange-500 text-white rounded-lg text-sm font-bold hover:bg-orange-600 shadow-sm"
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
                     <div class="p-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex justify-between items-center">
                         <h3 class="font-semibold text-gray-800 dark:text-white">รายการแบบทดสอบ</h3>
                         <span class="text-sm text-gray-500">{{ stats.completedQuizzes }}/{{ stats.totalQuizzes }}</span>
                     </div>
                     <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div v-if="data.quizzes && data.quizzes.length === 0" class="p-8 text-center text-gray-500">
                            ไม่มีแบบทดสอบในรายวิชานี้
                        </div>
                         <div v-for="quiz in data.quizzes" :key="quiz.id" class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                              <div class="flex flex-col gap-3">
                                  <div class="flex justify-between items-start">
                                      <div class="flex-1">
                                          <div class="font-medium text-gray-900 dark:text-white">{{ quiz.title }}</div>
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
                                             <NuxtLink :to="`/courses/${courseId}/quizzes/${quiz.id}`" class="text-xs bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700 transition-colors">
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
    </div>
</template>
