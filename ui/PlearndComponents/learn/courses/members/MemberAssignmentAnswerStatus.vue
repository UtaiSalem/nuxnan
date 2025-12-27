<script setup>
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    member_info: Object,
    assignment: Object,
    answers: Object,
});

const emit = defineEmits(['getErrorMsg']);
// const emit = defineEmits(['handleEmptyPoints']);


const isLoadingMemberAssignmentsAnswer = ref(false);

const memberAnswers = computed(()=>{
    return props.answers.filter((answer)=> answer.student.id === props.member_info.user.id);
});

onMounted(()=>{
    if (!memberAnswers.value.length) {
        emit('getErrorMsg', ' ยังไม่ส่งแบบฝึกหัด');   
    }else if (memberAnswers.value.length>1){
        emit('getErrorMsg', ' มีคำตอบมากกว่า 1 คำตอบ');
    }
    else if (!memberAnswers.value[0].points) {
        emit('getErrorMsg', ' รอตรวจคำตอบ');
    }
    // else if (memberAnswers.value[0].points < props.assignment.points/2) {
    //     emit('getErrorMsg', ' คะแนนน้อยกว่าครึ่งของคะแนนเต็ม');
    // }
});

</script>

<template>
    <div>
        <div v-if="memberAnswers.length">
            <div v-for="(answer) in memberAnswers" :key="answer.id" class="text-center">
                <div v-if="answer.points">
                    <span
                        class=" text-white py-1 px-2 max-w-fit rounded-md text-xs font-semibold"
                        :class="answer.points >= assignment.points/2 ? 'bg-green-400' : answer.points > 0 ? 'bg-orange-400' : 'bg-red-500'"
                    >
                        {{ answer.points + ' / ' + assignment.points }}
                    </span>
                </div>
                <div v-else>
                    <span class="bg-slate-500 text-white py-1 px-2 rounded-md text-xs ">{{ 'รอตรวจ' }}/{{ assignment.points }}</span>
                </div>
            </div>
        </div>
        <div v-else>
            <div class=" text-center">
                <span class="bg-red-500 text-white py-1 px-2 rounded-md text-xs">
                    ยังไม่ส่งงาน
                </span>
            </div>
        </div>
    </div>
</template>
