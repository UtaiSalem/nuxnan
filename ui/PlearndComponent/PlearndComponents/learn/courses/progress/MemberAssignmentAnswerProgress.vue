<script setup>
import { onMounted } from 'vue';

const props = defineProps({
    member_info: Object,
    assignment: Object,
    answers: Object,
});

const emit = defineEmits(['handleEmptyPoints']);

onMounted(() => {
    if (!props.answers.length) {
        emit('handleEmptyPoints');
    }
});

</script>

<template>
    <div v-if="answers.length">
        <div v-for="(answer) in answers" :key="answer.id" class="text-center">
            <div v-if="answer.points" class="py-1 px-2 rounded-md text-md font-medium shadow-sm"
                :class="answer.points >= assignment.points/2 ? 'bg-gradient-to-r from-emerald-50 to-emerald-100 text-emerald-700 border border-emerald-200' : answer.points > 0 ? 'bg-gradient-to-r from-amber-50 to-amber-100 text-amber-700 border border-amber-200' : 'bg-gradient-to-r from-rose-50 to-rose-100 text-rose-700 border border-rose-200'">
                {{ answer.points }}
            </div>
            <div v-else class="bg-gradient-to-r from-slate-50 to-slate-100 text-slate-600 py-1 px-2 rounded-md text-xs border border-slate-200 shadow-sm">
                {{ 'รต' }}
            </div>
        </div>
    </div>
</template>
