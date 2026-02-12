<script setup>
import { computed, onMounted, watch } from 'vue';

const props = defineProps({
    member_info: Object,
    assignment: Object,
    answers: Object,
});

const emit = defineEmits(['getErrorMsg']);

// Pre-filter answers for this member to avoid repeated filtering
const memberAnswers = computed(() => {
    if (!props.answers || !props.answers.length) return [];
    return props.answers.filter(answer => answer.student.id === props.member_info.user.id);
});

// Check for empty answers and emit error
const hasAnswers = computed(() => {
    return memberAnswers.value.length > 0;
});

watch(hasAnswers, (newValue) => {
    if (!newValue) {
        emit('getErrorMsg', ' ไม่มีคะแนน');
    }
}, { immediate: true });

</script>

<template>
    <div v-if="memberAnswers.length">
        <div v-for="(answer) in memberAnswers" :key="answer.id" class="text-center">
            <div v-if="answer.points" class="text-gray-900 py-1 px-2 rounded-md text-sm font-semibold border-2 shadow-sm"
            :class="answer.points >= assignment.points/2 ? 'bg-teal-200 text-teal-800 border-teal-400' : answer.points > 0 ? 'bg-teal-100 text-teal-700 border-teal-300' : 'bg-teal-50 text-teal-600 border-teal-200'">
                {{ answer.points }}
            </div>
            <div v-else class="bg-slate-200 text-slate-700 py-1 px-2 rounded-md text-sm font-medium border-2 border-slate-400 shadow-sm">
                {{ 'รต' }}
            </div>
        </div>
    </div>
</template>