<script setup>
import CoursesCardWidget from '@/PlearndComponents/widgets/CourseCardWidget.vue';

defineProps({
    courses: Object,
});
</script>
<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="" v-for="course,index in courses" :key="index">
            <CoursesCardWidget :course="course"/>
        </div>
    </div>
</template>
