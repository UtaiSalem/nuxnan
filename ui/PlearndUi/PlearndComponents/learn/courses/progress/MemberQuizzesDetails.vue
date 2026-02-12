<template>
    <div>
        <div>{{ quizze.title }}</div>
        <div v-for="question in quizze.questions" :key="question.id">
            <MemberQuizeQuestions :question="question" />
        </div>
    </div>
</template>

<script setup lang="ts">
import MemberQuizeQuestions from './MemberQuizeQuestions.vue';
const props = defineProps({
    quizze: Object,
});
</script>
