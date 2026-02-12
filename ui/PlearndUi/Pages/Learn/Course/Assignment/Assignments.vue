<script setup>
import { provide, ref } from 'vue';
// import { usePage } from "@inertiajs/vue3";
import CourseLayout from '@/Layouts/CourseLayout.vue';
import AssignmentListViewer from '@/PlearndComponents/learn/courses/assignments/AssignmentListViewer.vue'; 
import CreateNewAssignmentCard from '@/PlearndComponents/learn/courses/assignments/CreateNewAssignmentCard.vue';
import LoadingPage from '@/PlearndComponents/accessories/LoadingPage.vue';

const props = defineProps({
    course: Object,
    groups: Object,
    assignments: Object,
    isCourseAdmin: Boolean,
    courseMemberOfAuth: Object,
});

const isLoadingPage = ref(false);
const setLoadingPage = (val) => {
    isLoadingPage.value = val;
}

provide('isLoadingPage', {
    isLoadingPage,
    setLoadingPage,
});

</script>

<template>
    <div class="relative">
        <CourseLayout 
            :course
            :isCourseAdmin
            :courseMemberOfAuth
            :activeTab="2"
        >
            <template #courseContent>
                <LoadingPage v-if="isLoadingPage" />
                <div class=" mt-4">
                    <AssignmentListViewer
                        :assignmentableType="'courses'"
                        :assignmentableId="props.course.data.id"
                        :assignmentNameTh="'รายวิชา'"
                        :assignmentApiRoute="`/courses/${props.course.data.id}`"
                        v-model:assignments="props.assignments.data"
                    />
                    <CreateNewAssignmentCard v-if="props.isCourseAdmin"
                        :assignmentableType="'courses'"
                        :assignmentableId="props.course.data.id"
                        :assignmentNameTh="'รายวิชา'"
                        :assignmentApiRoute="`/courses/${props.course.data.id}`"
                        @add-new-assignment="(newAsm) => props.assignments.data.push(newAsm)"
                    />
                </div>
            </template>
        </CourseLayout>
    </div>
</template>
