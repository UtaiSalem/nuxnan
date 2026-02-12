<script setup>
import { computed, onMounted } from 'vue';
const props = defineProps({
    member_id: Number,
    quiz: Object,
});

const emit = defineEmits(['handleEmptyPoints']);

const member_score = computed(() => {
    return props.quiz.course_members_score.filter((member) => member.user_id === props.member_id)[0];
})

onMounted(() => {
    if (!member_score.value) {
        emit('handleEmptyPoints');
    }
});

</script>

<template>
    <div>
        <div v-if="member_score" class="py-1 px-2 max-w-fit rounded-md text-md font-medium shadow-sm"
            :class="member_score.score >= quiz.total_score/2 ? 'bg-gradient-to-r from-emerald-50 to-emerald-100 text-emerald-700 border border-emerald-200' : member_score.score > 0 ? 'bg-gradient-to-r from-amber-50 to-amber-100 text-amber-700 border border-amber-200' : 'bg-gradient-to-r from-rose-50 to-rose-100 text-rose-700 border border-rose-200'">
            {{ member_score.score }}
        </div>
    </div>
</template>
