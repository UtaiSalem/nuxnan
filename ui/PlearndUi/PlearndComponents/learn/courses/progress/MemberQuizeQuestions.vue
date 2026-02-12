
<template>
    <div class="quiz-questions-container">
        <div class="question-text">{{ question.text }}</div>
        <div class="question-options">
            <div v-for="option in question.options" :key="option.id" class="option-item">
                <input
                    type="radio"
                    :name="'question-' + question.id"
                    :value="option.id"
                    :checked="option.is_selected"
                    disabled
                />
                <label>{{ option.text }}</label>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    question: Object,
});
</script>
