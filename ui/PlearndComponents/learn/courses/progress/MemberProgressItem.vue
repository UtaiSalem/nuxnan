<script setup>
import { ref, computed } from 'vue';
// import { useRouter } from 'vue-router';
import { Link, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { Icon } from '@iconify/vue';

import MemberAssignmentsAnswerProgress from '@/PlearndComponents/learn/courses/progress/MemberAssignmentAnswerProgress.vue';
import MemberQuizzesAnswerProgress from '@/PlearndComponents/learn/courses/progress/MemberQuizzesAnswerProgress.vue';
import MemberGradeProgress from './MemberGradeProgress.vue';
import MemberProgressComments from './MemberProgressComments.vue';

const props = defineProps({
    member: Object,
    isCourseAdmin: Boolean,
});

// const router = useRouter();
const isLoading = ref(false);
const isAllAnswersCompleted = ref(true);
const errorMessages = ref([]);
const refBonusPoints = ref(props.member.bonus_points??0);
const isLinkingToMemberSettingPage = ref(false);
const refOrderNumber = ref(props.member.order_number);
const isOrderLoading = ref(false);
const refMemberCode = ref(props.member.member_code);
const isMemberCodeLoading = ref(false);
const totalMemberScore = computed(() => {
    return props.member.achieved_score + refBonusPoints.value ;
});

const gradeProgress = (score) => {
    if (score >= 80) {
        return {
            grade: 4,
            color: "emerald",
            bgColor: "bg-gradient-to-r from-emerald-50 to-emerald-100",
            textColor: "text-emerald-800",
            borderColor: "border-emerald-300",
            iconColor: "text-emerald-500",
            gradient: "bg-gradient-to-r from-emerald-400 to-emerald-600",
            lightGradient: "bg-gradient-to-r from-emerald-100 to-emerald-200"
        };
    } else if (score >= 75) {
        return {
            grade: 3.5,
            color: "teal",
            bgColor: "bg-gradient-to-r from-teal-50 to-teal-100",
            textColor: "text-teal-800",
            borderColor: "border-teal-300",
            iconColor: "text-teal-500",
            gradient: "bg-gradient-to-r from-teal-400 to-teal-600",
            lightGradient: "bg-gradient-to-r from-teal-100 to-teal-200"
        };
    } else if (score >= 70) {
        return {
            grade: 3,
            color: "blue",
            bgColor: "bg-gradient-to-r from-blue-50 to-blue-100",
            textColor: "text-blue-800",
            borderColor: "border-blue-300",
            iconColor: "text-blue-500",
            gradient: "bg-gradient-to-r from-blue-400 to-blue-600",
            lightGradient: "bg-gradient-to-r from-blue-100 to-blue-200"
        };
    } else if (score >= 65) {
        return {
            grade: 2.5,
            color: "indigo",
            bgColor: "bg-gradient-to-r from-indigo-50 to-indigo-100",
            textColor: "text-indigo-800",
            borderColor: "border-indigo-300",
            iconColor: "text-indigo-500",
            gradient: "bg-gradient-to-r from-indigo-400 to-indigo-600",
            lightGradient: "bg-gradient-to-r from-indigo-100 to-indigo-200"
        };
    } else if (score >= 60) {
        return {
            grade: 2,
            color: "amber",
            bgColor: "bg-gradient-to-r from-amber-50 to-amber-100",
            textColor: "text-amber-800",
            borderColor: "border-amber-300",
            iconColor: "text-amber-500",
            gradient: "bg-gradient-to-r from-amber-400 to-amber-600",
            lightGradient: "bg-gradient-to-r from-amber-100 to-amber-200"
        };
    } else if (score >= 55) {
        return {
            grade: 1.5,
            color: "orange",
            bgColor: "bg-gradient-to-r from-orange-50 to-orange-100",
            textColor: "text-orange-800",
            borderColor: "border-orange-300",
            iconColor: "text-orange-500",
            gradient: "bg-gradient-to-r from-orange-400 to-orange-600",
            lightGradient: "bg-gradient-to-r from-orange-100 to-orange-200"
        };
    } else if (score >= 50) {
        return {
            grade: 1,
            color: "rose",
            bgColor: "bg-gradient-to-r from-rose-50 to-rose-100",
            textColor: "text-rose-800",
            borderColor: "border-rose-300",
            iconColor: "text-rose-500",
            gradient: "bg-gradient-to-r from-rose-400 to-rose-600",
            lightGradient: "bg-gradient-to-r from-rose-100 to-rose-200"
        };
    } else {
        return {
            grade: 0,
            color: "slate",
            bgColor: "bg-gradient-to-r from-slate-50 to-slate-100",
            textColor: "text-slate-800",
            borderColor: "border-slate-300",
            iconColor: "text-slate-500",
            gradient: "bg-gradient-to-r from-slate-400 to-slate-600",
            lightGradient: "bg-gradient-to-r from-slate-100 to-slate-200"
        };
    }
};

const gradeStatus = computed(()=>{
    if(!props.member.order_number || 
        !props.member.achieved_score || 
        !props.member.member_code ||
        !isAllAnswersCompleted.value ||
        props.member.grade_progress === 5 ||
        (props.member.notes_comments && props.member.notes_comments.length) ||
        errorMessages.value.length
    ){
        return false;
    }else{
        return true;
    }
});

const gradePercentage = computed(()=>{
    if(props.member.achieved_score){
        return Math.round(((props.member.achieved_score + refBonusPoints.value) / usePage().props.course.data.total_score) * 100);
    }else{
        return 0;
    }
});

const bonusPointsForm = ref({
    bonusPoints: 0,
    gradeProgress: gradeProgress(totalMemberScore).grade,
});

const orderNumberForm = ref({
    orderNumber: props.member.order_number || 0,
});

const memberCodeForm = ref({
    memberCode: props.member.member_code || '',
});

const handleBonusPointsInputSubmit = async () => {
    isLoading.value = true;
    try {
        let response = await axios.patch(`/courses/${props.member.course_id}/members/${props.member.id}/bonus-points`, {
            bonus_points: bonusPointsForm.value.bonusPoints,
        });

        if (response.data.success) {
            refBonusPoints.value += bonusPointsForm.value.bonusPoints;
            bonusPointsForm.value.bonusPoints = 0;
            Swal.fire({
                icon: 'success',
                title: 'บันทึกคะแนนโบนัสสำเร็จ',
                showConfirmButton: false,
                timer: 1500,
            });
            isLoading.value = false;
        }
        
    } catch (error) {
        console.log(error);
        isLoading.value = false;
        Swal.fire({
            icon: 'error',
            title: 'บันทึกคะแนนโบนัสไม่สำเร็จ',
            showConfirmButton: false,
            timer: 1500,
        });
    }
    isLoading.value = false;
}

const handleOrderNumberInputSubmit = async () => {
    isOrderLoading.value = true;
    try {
        let response = await axios.patch(`/courses/${props.member.course_id}/members/${props.member.id}/order-number`, {
            order_number: orderNumberForm.value.orderNumber,
        });

        if (response.data.success) {
            refOrderNumber.value = orderNumberForm.value.orderNumber;
            props.member.order_number = orderNumberForm.value.orderNumber;
            Swal.fire({
                icon: 'success',
                title: 'บันทึกเลขที่สำเร็จ',
                showConfirmButton: false,
                timer: 1500,
            });
            isOrderLoading.value = false;
        }
        
    } catch (error) {
        console.log(error);
        isOrderLoading.value = false;
        Swal.fire({
            icon: 'error',
            title: 'บันทึกเลขที่ไม่สำเร็จ',
            showConfirmButton: false,
            timer: 1500,
        });
    }
    isOrderLoading.value = false;
}

const handleMemberCodeInputSubmit = async () => {
    isMemberCodeLoading.value = true;
    try {
        let response = await axios.patch(`/courses/${props.member.course_id}/members/${props.member.id}/member-code`, {
            member_code: memberCodeForm.value.memberCode,
        });

        if (response.data.success) {
            refMemberCode.value = memberCodeForm.value.memberCode;
            props.member.member_code = memberCodeForm.value.memberCode;
            Swal.fire({
                icon: 'success',
                title: 'บันทึกรหัสนักเรียนสำเร็จ',
                showConfirmButton: false,
                timer: 1500,
            });
            isMemberCodeLoading.value = false;
        }
        
    } catch (error) {
        console.log(error);
        isMemberCodeLoading.value = false;
        Swal.fire({
            icon: 'error',
            title: 'บันทึกรหัสนักเรียนไม่สำเร็จ',
            showConfirmButton: false,
            timer: 1500,
        });
    }
    isMemberCodeLoading.value = false;
}

function setGradeStatus(errMsg){
    errorMessages.value.push(errMsg);
}

const navigateToMemberSettings = (courseId, memberId) => {
    isLinkingToMemberSettingPage.value = true;
    try {
        // route('course.member.settings.page.show', { course: courseId, course_member: memberId });
        window.location.href = `/courses/${courseId}/members/${memberId}/member-settings`;
    } catch (error) {
        console.log(error);
    }
}

</script>

<template>
    <td scope="row" :class="gradeStatus ? gradeProgress(gradePercentage).lightGradient : 'bg-gradient-to-r from-red-50 to-red-100'"
        class="border border-slate-300 font-medium text-gray-900 whitespace-nowrap dark:text-white text-center shadow-sm">
        <div v-if="!isCourseAdmin" class="w-12 min-w-fit">
            {{ member.order_number }}
        </div>
        <form v-else @submit.prevent="handleOrderNumberInputSubmit" class="flex items-center justify-center">
            <input type="number" name="orderNumber" v-model="orderNumberForm.orderNumber" :id="`member-${member.id}-order-number`" autocomplete="order-number" class="block w-16 rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
            <button type="submit" class="rounded-md px-1 py-0 ml-1 text-sm font-base shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
                :class="gradeStatus ? gradeProgress(gradePercentage).textColor : 'text-red-600'">
                <Icon icon="icomoon-free:spinner" class="w-5 h-5 animate-spin" v-if="isOrderLoading" />
                <Icon icon="fluent:save-24-regular" class="w-5 h-5" v-else />
            </button>
        </form>
    </td>
    <td :class="gradeStatus ? gradeProgress(gradePercentage).lightGradient : 'bg-gradient-to-r from-red-50 to-red-100'"
        class="px-1 py-1 border border-slate-300 text-center shadow-sm">
        <div v-if="!isCourseAdmin" class="flex justify-center items-center">
            <a 
                :href="`/courses/${member.course_id}/members/${member.id}/member-grade-progress`"
                target="_blank" 
                rel="noopener noreferrer"
                class="font-medium hover:text-indigo-600 transition-colors duration-200 cursor-pointer inline-flex items-center group/link">
                <span>{{ member.member_code?? '' }}</span>
                <Icon  icon="heroicons:arrow-top-right-on-square" class="w-4 h-4 ml-1.5 opacity-0 group-hover/link:opacity-100 transition-opacity duration-200" />
            </a>
        </div>
        <form v-else @submit.prevent="handleMemberCodeInputSubmit" class="flex items-center justify-center">
            <input type="text" name="memberCode" v-model="memberCodeForm.memberCode" :id="`member-${member.id}-member-code`" autocomplete="member-code" class="block w-24 rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
            <button type="submit" class="rounded-md px-1 py-0 ml-1 text-sm font-base shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
                :class="gradeStatus ? gradeProgress(gradePercentage).textColor : 'text-red-600'">
                <Icon icon="icomoon-free:spinner" class="w-5 h-5 animate-spin" v-if="isMemberCodeLoading" />
                <Icon icon="fluent:save-24-regular" class="w-5 h-5" v-else />
            </button>
        </form>
    </td>

    <td :class="gradeStatus ? gradeProgress(gradePercentage).lightGradient : 'bg-gradient-to-r from-red-50 to-red-100'"
        class="pl-2 py-1 border border-slate-300 text-start w-48 shadow-sm">
        <a 
            :href="`/courses/${member.course_id}/members/${member.id}/member-grade-progress`"
            target="_blank" 
            rel="noopener noreferrer"
            class="font-medium hover:text-indigo-600 transition-colors duration-200 cursor-pointer inline-flex items-center group/link">
            <span>{{ member.member_name?? member.user.name }}</span>
            <Icon icon="heroicons:arrow-top-right-on-square" class="w-4 h-4 ml-1.5 opacity-0 group-hover/link:opacity-100 transition-opacity duration-200" />
        </a>
    </td>

    <td :class="gradeStatus ? 'bg-gradient-to-r from-purple-50 to-purple-100' : 'bg-gradient-to-r from-red-50 to-red-100'"
        v-for="(assignment, asmIndx) in $page.props.assignments.data" :key="assignment.id"
        class="px-2 py-1 border border-slate-300 text-center w-8 shadow-sm"
    >
        <MemberAssignmentsAnswerProgress
            :member_info="member"
            :assignment="assignment"
            :answers="assignment.answers?.filter((answer) => answer.student?.id === props.member.user.id) || []"

            @handleEmptyPoints="setGradeStatus(' ภาระงานที่ ' + (asmIndx+1) + ' ไม่มีคะแนน')"
        />
    </td>

    <td :class="gradeStatus ? 'bg-gradient-to-r from-cyan-50 to-cyan-100' : 'bg-gradient-to-r from-red-50 to-red-100'"
        v-for="(quiz, qzIndx) in $page.props.quizzes.data" :key="quiz.id"
        class="px-2 py-1 border border-slate-300 text-center w-8 shadow-sm">
        <MemberQuizzesAnswerProgress
            :quiz="quiz"
            :member_id="member.user.id"
            @handleEmptyPoints="setGradeStatus(' แบบทดสอบที่ ' + (qzIndx+1) + ' ไม่มีคะแนน')"
        />
    </td>

    <td :class="gradeStatus ? 'bg-gradient-to-r from-yellow-50 to-yellow-100' : 'bg-gradient-to-r from-red-50 to-red-100'"
        class="px-2 py-0 border border-slate-300 text-center shadow-sm">
        <span class="font-semibold">{{ member.achieved_score }}</span>
    </td>

    <td :class="gradeStatus ? 'bg-gradient-to-r from-green-50 to-green-100' : 'bg-gradient-to-r from-red-50 to-red-100'"
        class="px-2 py-0 border border-slate-300 text-center shadow-sm">
        <form @submit.prevent="handleBonusPointsInputSubmit" class="flex items-center justify-center">
            <span class="mr-2 font-semibold">{{ refBonusPoints }}</span>
            <input type="number" name="bonusPoints" v-model="bonusPointsForm.bonusPoints" :id="`member-${member.id}-bonus-points`" autocomplete="bonus-points" class="block w-16 rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
            <button type="submit" class="rounded-md px-1 py-0 ml-1 text-sm font-base shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
                :class="gradeStatus ? 'text-green-600' : 'text-red-600'">
                <Icon icon="icomoon-free:spinner" class="w-5 h-5 animate-spin" v-if="isLoading" />
                <Icon icon="fluent:save-24-regular" class="w-5 h-5" v-else />
            </button>
        </form>
    </td>

    <td :class="gradeStatus ? 'bg-gradient-to-r from-indigo-50 to-indigo-100' : 'bg-gradient-to-r from-red-50 to-red-100'"
        class="px-2 py-0 border border-slate-300 text-center shadow-sm">
        <span class="font-semibold">{{ totalMemberScore + '/' + $page.props.course.data.total_score }}</span>
    </td>

    <td :class="gradeStatus ? 'bg-gradient-to-r from-pink-50 to-pink-100' : 'bg-gradient-to-r from-red-50 to-red-100'"
        class="px-2 py-0 border border-slate-300 text-center shadow-sm">
        <span class="font-semibold">{{ gradePercentage }}</span>
    </td>

    <td :class="gradeStatus ? gradeProgress(gradePercentage).lightGradient : 'bg-gradient-to-r from-red-50 to-red-100'"
        class="px-2 py-0 border border-slate-300 text-center text-lg font-bold shadow-sm">
        <span class="rounded-md px-3 py-1 shadow-md" v-if="gradeStatus"
            :class="gradeProgress(gradePercentage).gradient + ' text-white'">
            {{  gradeProgress(gradePercentage).grade }}
        </span>
        <span class="rounded-md px-3 py-1 shadow-md" v-else
            :class="gradeProgress(gradePercentage).gradient + ' text-white'">
            ร
        </span>
    </td>

    <td class="p-1 flex justify-center bg-gradient-to-r from-gray-50 to-gray-100 border border-slate-300 shadow-sm">
        <MemberGradeProgress
            :grade="gradeProgress(totalMemberScore).grade"
            :gradeStatus="gradeStatus"
            :member_id="member.id"
            :course_id="member.course_id"

            v-model:grade-progress="props.member.grade_progress"
        />
    </td>
    <td class="p-1 border border-slate-300 bg-gradient-to-r from-gray-50 to-gray-100 shadow-sm">
        <MemberProgressComments
            :member_id="props.member.id"
            :course_id="props.member.course_id"
            :notes_comments="props.member.notes_comments"

            v-model:notes-comments="props.member.notes_comments"
        />
    </td>

    <div v-if="isLinkingToMemberSettingPage" class="fixed top-0 left-0 z-20 flex items-center justify-center w-full h-full modal">
        <div class="absolute w-full h-full bg-gray-900 opacity-75 modal-overlay"></div>
        <div class="flex items-center justify-center h-64">
            <Icon icon="svg-spinners:bars-rotate-fade" class="z-30 w-32 h-32 text-white" />
        </div>
    </div>
</template>
