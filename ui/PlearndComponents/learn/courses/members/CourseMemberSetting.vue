<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { Icon } from '@iconify/vue';
import RadialProgress from "vue3-radial-progress";
import MemberAssignmentStatusViewer from '@/PlearndComponents/learn/courses/members/MemberAssignmentStatusViewer.vue';
import MemberQuestionStatusViewer from '@/PlearndComponents/learn/courses/members/MemberQuestionStatusViewer.vue';
import ScoresSummaryTable from '@/PlearndComponents/learn/courses/progress/ScoresSummaryTable.vue';

const props = defineProps({
    member_info: Object,
});

const isLoading = ref(false);
const errorMessages = ref([]);

const form = ref({
    member_name:    props.member_info.member_name || usePage().props.auth.user.name,
    order_number:   props.member_info.order_number,
    member_code:    props.member_info.member_code,
});

// grade calculator return object of grade and color
const grade = computed(() => {
    // let score = props.member_info.achieved_score;
    let score = props.member_info.achieved_score * 100 / usePage().props.course.data.total_score;
    if (score >= 80) {
        return {    grade: 4,   color: 'text-green-500' };
    } else if (score >= 75) {
        return {    grade: 3.5, color: 'text-blue-500' };
    } else if (score >= 70) {
        return {     grade: 3,  color: 'text-blue-500' };
    } else if (score >= 65) {
        return {    grade: 2.5, color: 'text-blue-500' };
    } else if (score >= 60) {
        return {    grade: 2,   color: 'text-orange-500' };
    } else if (score >= 55) {
        return {    grade: 1.5, color: 'text-orange-500' };
    } else if (score >= 50) {
        return {    grade: 1,   color: 'text-orange-500' };
    } else {
        return {    grade: 0,   color: 'text-red-500' };
    } 
});

//percentage math.floor
const percentage = computed(() => {
    let percent = props.member_info.achieved_score * 100 / usePage().props.course.data.total_score;
    return `${Math.floor(percent)}%`;
});

const gradeStatus = computed(()=>{
    if(!props.member_info.order_number || 
        !props.member_info.achieved_score || 
        !props.member_info.member_code ||
        props.member_info.grade_progress === 5 ||
        (props.member_info.notes_comments && props.member_info.notes_comments.length) ||
        // (props.member_info.achieved_score + props.member_info.bonus_points) < 50 ||
        errorMessages.value.length
    ){
        return false;
    }else{
        return true;
    }
});

async function handleUpdateMemberInfo() {
    try {
        isLoading.value = true;
        let resp = await axios.patch(`/courses/${props.member_info.course_id}/members/${props.member_info.id}/update`, form.value);
        if (resp.data.success) {
            props.member_info.member_name = form.value.member_name;
            props.member_info.order_number = form.value.order_number;
            props.member_info.member_code = form.value.member_code;

            props.member_info.notes_comments = resp.data.notes_comments;
            
            isLoading.value = false;
            Swal.fire({
                icon: 'success',
                title: 'แก้ไขข้อมูลสำเร็จ',
                text: 'แก้ไขข้อมูลเรียบร้อยแล้ว',
                showConfirmButton: false,
                timer: 1000
            });
        }
    } catch (error) {
        console.log(error);
        isLoading.value = false;
    }
}

function setGradeStatus(errMsg){
    errorMessages.value.push(errMsg);
}

onMounted(()=>{
    if (!form.value.order_number) {
        errorMessages.value.push('ยังไม่กรอกเลขที่');
    }else if (!form.value.member_code) {
        errorMessages.value.push('ยังไม่กรอกเลขประจำตัว');
    }
});

</script>

<template>
    <div class="w-full mx-auto">
        <!-- Header Section with Gradient Background -->
        <div class="bg-gradient-to-r from-purple-600 via-blue-600 to-indigo-700 rounded-xl p-6 mb-6 shadow-xl transform transition-all duration-300 hover:scale-[1.02]">
            <div class="flex items-center space-x-3">
                <div class="bg-white/20 backdrop-blur-sm rounded-full p-3">
                    <Icon icon="mdi:account-settings" class="w-8 h-8 text-white" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">การตั้งค่าสมาชิก</h1>
                    <p class="text-blue-100 text-sm">จัดการข้อมูลส่วนตัวและผลการเรียน</p>
                </div>
            </div>
        </div>

        <!-- Member Information Section -->
        <div class="bg-gradient-to-br from-white to-blue-50 rounded-xl p-6 shadow-lg border border-blue-100 mb-6 transform transition-all duration-300 hover:shadow-xl">
            <div class="flex items-center space-x-2 mb-4">
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg p-2">
                    <Icon icon="mdi:account-details" class="w-5 h-5 text-white" />
                </div>
                <h2 class="text-xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">ข้อมูลสมาชิก</h2>
            </div>
            <div class="bg-white/70 backdrop-blur-sm rounded-lg p-4">
            <div class="grid grid-cols-1 w-full mb-4">
                <label :for="`username-${member_info.id}`" class="flex text-sm font-semibold leading-6 text-gray-800 mb-2">
                    <div class="flex items-center space-x-2">
                        <Icon icon="mdi:account" class="w-4 h-4 text-blue-600" />
                        <span>ชื่อ-นามสกุล</span>
                        <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full">(ภาษาไทย ตรงตามบัตรประชาชน)</span>
                    </div>
                </label>
                <div class="relative">
                    <div class="flex rounded-lg shadow-sm ring-2 ring-inset ring-blue-200 focus-within:ring-2 focus-within:ring-inset focus-within:ring-blue-500 bg-gradient-to-r from-blue-50 to-indigo-50 transition-all duration-200">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <Icon icon="mdi:account-edit" class="h-5 w-5 text-blue-400" />
                        </div>
                        <input type="text" v-model="form.member_name" name="username" :id="`username-${member_info.id}`" autocomplete="username"
                            class="block flex-1 border-0 bg-transparent py-3 pl-10 text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm sm:leading-6 rounded-lg"
                            placeholder="กรุณากรอกชื่อ-นามสกุล" />
                    </div>
                </div>
            </div>
                <div class="flex flex-wrap items-center w-full gap-4">
                    <div class="flex-1 min-w-[120px]">
                        <label :for="`order-number-${member_info.id}`" class="flex text-sm font-semibold leading-6 text-gray-800 mb-2">
                            <div class="flex items-center space-x-2">
                                <Icon icon="mdi:numeric" class="w-4 h-4 text-green-600" />
                                <span>เลขที่</span>
                            </div>
                        </label>
                        <div class="relative">
                            <div class="flex rounded-lg shadow-sm ring-2 ring-inset ring-green-200 focus-within:ring-2 focus-within:ring-inset focus-within:ring-green-500 bg-gradient-to-r from-green-50 to-emerald-50 transition-all duration-200">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <Icon icon="mdi:hash" class="h-5 w-5 text-green-400" />
                                </div>
                                <input type="number" :id="`order-number-${member_info.id}`" v-model="form.order_number" min="1"
                                    class="block w-full border-0 bg-transparent py-3 pl-10 text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm sm:leading-6 rounded-lg"
                                    placeholder="เลขที่" />
                            </div>
                        </div>
                    </div>
                    <div class="flex-1 min-w-[150px]">
                        <label :for="`member-code-${member_info.id}`" class="flex text-sm font-semibold leading-6 text-gray-800 mb-2">
                            <div class="flex items-center space-x-2">
                                <Icon icon="mdi:id-card" class="w-4 h-4 text-purple-600" />
                                <span>เลขประจำตัว</span>
                            </div>
                        </label>
                        <div class="relative">
                            <div class="flex rounded-lg shadow-sm ring-2 ring-inset ring-purple-200 focus-within:ring-2 focus-within:ring-inset focus-within:ring-purple-500 bg-gradient-to-r from-purple-50 to-pink-50 transition-all duration-200">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <Icon icon="mdi:badge-account" class="h-5 w-5 text-purple-400" />
                                </div>
                                <input type="number" :id="`member-code-${member_info.id}`" v-model="form.member_code" min="1"
                                    class="block w-full border-0 bg-transparent py-3 pl-10 text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm sm:leading-6 rounded-lg"
                                    placeholder="เลขประจำตัว" />
                            </div>
                        </div>
                    </div>
                    <div class="flex-1 min-w-[150px]">
                        <label :for="`member-group-${member_info.id}`" class="flex text-sm font-semibold leading-6 text-gray-800 mb-2">
                            <div class="flex items-center space-x-2">
                                <Icon icon="mdi:account-group" class="w-4 h-4 text-orange-600" />
                                <span>กลุ่ม/ห้อง</span>
                            </div>
                        </label>
                        <div class="relative">
                            <div v-if="props.member_info.group" class="flex rounded-lg shadow-sm ring-2 ring-inset ring-orange-200 bg-gradient-to-r from-orange-50 to-amber-50">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <Icon icon="mdi:home-group" class="h-5 w-5 text-orange-400" />
                                </div>
                                <input type="text" :id="`member-group-${member_info.id}`" disabled v-model="props.member_info.group.name"
                                    class="block w-full border-0 bg-transparent py-3 pl-10 text-gray-700 sm:text-sm sm:leading-6 rounded-lg cursor-not-allowed" />
                            </div>
                            <div v-else class="flex rounded-lg shadow-sm ring-2 ring-inset ring-gray-200 bg-gradient-to-r from-gray-50 to-slate-50">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <Icon icon="mdi:help-circle" class="h-5 w-5 text-gray-400" />
                                </div>
                                <div class="block w-full py-3 pl-10 text-gray-500 sm:text-sm sm:leading-6 rounded-lg">ไม่มีกลุ่ม</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-end gap-x-4">
                    <button type="button" @click.prevent="handleUpdateMemberInfo" :disabled="isLoading"
                        :class="{
                            'cursor-not-allowed opacity-60 bg-gradient-to-r from-gray-400 to-gray-500': isLoading,
                            'bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 transform hover:scale-105 shadow-lg hover:shadow-xl': !isLoading
                        }"
                        class="rounded-lg flex items-center justify-center px-6 py-3 text-sm font-semibold text-white transition-all duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        <span v-if="isLoading" class="animate-spin mr-2"><Icon icon="mdi:loading" class="w-5 h-5"></Icon></span>
                        <Icon v-else icon="mdi:content-save" class="w-5 h-5 mr-2"></Icon>
                        <span>{{ isLoading ? 'กำลังบันทึก...' : 'บันทึก' }}</span>
                    </button>
                </div>
            </div>
        </div>
        <!-- Learning Information Section -->
        <div class="bg-gradient-to-br from-white to-green-50 rounded-xl p-6 shadow-lg border border-green-100 mb-6 transform transition-all duration-300 hover:shadow-xl">
            <div class="flex items-center space-x-2 mb-4">
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg p-2">
                    <Icon icon="mdi:school" class="w-5 h-5 text-white" />
                </div>
                <h2 class="text-xl font-bold bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent">ข้อมูลการเรียน</h2>
            </div>
        </div>

        <!-- Assignment Section -->
        <div class="bg-gradient-to-br from-white to-orange-50 rounded-xl p-6 shadow-lg border border-orange-100 mb-6 transform transition-all duration-300 hover:shadow-xl">
            <div class="flex items-center space-x-2 mb-4">
                <div class="bg-gradient-to-r from-orange-500 to-amber-600 rounded-lg p-2">
                    <Icon icon="mdi:clipboard-text" class="w-5 h-5 text-white" />
                </div>
                <h2 class="text-lg font-bold bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent">แบบฝึกหัด</h2>
            </div>
            <div class="bg-white/70 backdrop-blur-sm rounded-lg p-4">
                <MemberAssignmentStatusViewer
                    :member_info="props.member_info"
                    @handleEmptyPoints="(msg)=>setGradeStatus(msg)"
                />
            </div>
        </div>

        <!-- Quiz Section -->
        <div class="bg-gradient-to-br from-white to-purple-50 rounded-xl p-6 shadow-lg border border-purple-100 mb-6 transform transition-all duration-300 hover:shadow-xl">
            <div class="flex items-center space-x-2 mb-4">
                <div class="bg-gradient-to-r from-purple-500 to-pink-600 rounded-lg p-2">
                    <Icon icon="mdi:clipboard-check" class="w-5 h-5 text-white" />
                </div>
                <h2 class="text-lg font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">แบบทดสอบ</h2>
            </div>
            <div class="bg-white/70 backdrop-blur-sm rounded-lg p-4">
                <MemberQuestionStatusViewer
                    @sendErrorsMsg="(msg)=>setGradeStatus(msg)"
                />
            </div>
        </div>

        <!-- <AssignmentsScoresViwer /> -->

        <!-- Grade Display Section -->
        <div class="bg-gradient-to-br from-white via-blue-50 to-indigo-50 rounded-xl p-6 shadow-lg border border-blue-100 transform transition-all duration-300 hover:shadow-xl">
            <div class="flex items-center space-x-2 mb-4">
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg p-2">
                    <Icon icon="mdi:star-circle" class="w-5 h-5 text-white" />
                </div>
                <h2 class="text-lg font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">คะแนนรวม/เกรด</h2>
            </div>

            <!-- <div>
                <ScoresSummaryTable 
                    :memberId="props.member_info.id"
                    :courseId="props.member_info.course_id"
                    @sendErrorsMsg="(msg)=>setGradeStatus(msg)"
                />
            </div> -->

            <div class="bg-white/70 backdrop-blur-sm rounded-lg p-6">
                <!-- Error Messages Section -->
                <div v-if="errorMessages.length > 0 || (props.member_info.notes_comments && props.member_info.notes_comments.length > 0)" class="mb-6">
                    <div class="bg-gradient-to-r from-red-50 to-pink-50 border-l-4 border-red-500 rounded-lg p-4">
                        <div class="flex items-center mb-2">
                            <Icon icon="mdi:alert-circle" class="w-5 h-5 text-red-600 mr-2" />
                            <span class="font-semibold text-red-800">ข้อความแจ้งเตือน</span>
                        </div>
                        <ul class="space-y-2">
                            <li v-for="(msg, index) in errorMessages" :key="index" class="text-red-700 text-sm flex items-start space-x-2 bg-white/50 rounded p-2">
                                <Icon icon="mdi:chevron-right" class="w-4 h-4 text-red-500 mt-0.5 flex-shrink-0" />
                                <span>{{ msg }}</span>
                            </li>
                            <li v-if="props.member_info.notes_comments && props.member_info.notes_comments.length > 0" class="text-red-700 text-sm flex items-start space-x-2 bg-white/50 rounded p-2">
                                <Icon icon="mdi:chevron-right" class="w-4 h-4 text-red-500 mt-0.5 flex-shrink-0" />
                                <span>{{ props.member_info.notes_comments }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
                <!-- Grade Progress Circle -->
                <div class="flex flex-col items-center">
                    <div class="relative">
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-400 to-indigo-600 rounded-full blur-xl opacity-30 animate-pulse"></div>
                        <div class="relative bg-white rounded-full p-2 shadow-xl">
                            <RadialProgress
                                :diameter="140"
                                :completed-steps="member_info.achieved_score"
                                :total-steps="$page.props.course.data.total_score"
                                :stroke-width="8"
                                :inner-stroke-width="8"
                                start-color="#3B82F6"
                                stop-color="#6366F1"
                                inner-stroke-color="#E5E7EB">
                                <div class="flex flex-col items-center justify-center">
                                    <span class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">{{ percentage }}</span>
                                    <div v-if="$page.props.course.data.status === 3" class="flex flex-col items-center">
                                        <span class="text-3xl font-bold mt-1" :class="grade.color">
                                            {{ gradeStatus ? grade.grade : 'ร'}}
                                        </span>
                                        <span class="text-xs font-semibold mt-1 px-2 py-1 rounded-full" :class="{
                                            'bg-green-100 text-green-800': grade.color === 'text-green-500',
                                            'bg-blue-100 text-blue-800': grade.color === 'text-blue-500',
                                            'bg-orange-100 text-orange-800': grade.color === 'text-orange-500',
                                            'bg-red-100 text-red-800': grade.color === 'text-red-500'
                                        }">เกรด</span>
                                    </div>
                                    <div v-else class="flex flex-col items-center mt-2">
                                        <div class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-xs font-semibold flex items-center">
                                            <Icon icon="mdi:clock" class="w-3 h-3 mr-1" />
                                            รอประมวลผล
                                        </div>
                                    </div>
                                </div>
                            </RadialProgress>
                        </div>
                    </div>
                    
                    <!-- Score Display -->
                    <div class="mt-6 text-center">
                        <div class="inline-flex items-center space-x-2 bg-gradient-to-r from-blue-50 to-indigo-50 px-4 py-2 rounded-full border border-blue-200">
                            <Icon icon="mdi:trophy" class="w-5 h-5 text-yellow-500" />
                            <span class="text-2xl font-bold" :class="grade.color">{{ member_info.achieved_score }}</span>
                            <span class="text-gray-500">/</span>
                            <span class="text-sm font-semibold text-gray-700">{{ $page.props.course.data.total_score }}</span>
                            <span class="text-sm text-gray-500">คะแนน</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
