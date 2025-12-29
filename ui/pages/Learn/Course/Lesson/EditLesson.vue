<script setup>
import {  usePage } from "@inertiajs/vue3";

import CourseLayout from '@/Layouts/CourseLayout.vue';
// import SingleCourseLesson from '@/PlearndComponents/learn/courses/lessons/SingleCourseLesson.vue';
import UpdateCourseLesson from "@/PlearndComponents/learn/courses/lessons/UpdateCourseLesson.vue";

const props = defineProps({
    course: Object,
    lesson: Object,
    isCourseAdmin: Boolean,
    courseMemberOfAuth: Object,
});

// const { $page } = usePage();
// const { course, lesson, isCourseAdmin, courseMemberOfAuth } = $page.props;

const handleLessonUpdated = (lesson) => {
    // Handle lesson update
}

</script>

<template>
    <div>
     <CourseLayout
        :course="props.course"
        :isCourseAdmin="props.isCourseAdmin"
        :activeTab="1"
     >
        <template #courseContent>
            <div>
                <UpdateCourseLesson 
                    :lesson 

                    @lesson-updated="(lesson) => handleLessonUpdated(lesson)"
                />
            </div>
        </template>
     </CourseLayout>
    </div>
</template>

