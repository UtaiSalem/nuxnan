<script setup>

import CourseLayout from '@/Layouts/CourseLayout.vue';
import CourseInfomation from '@/PlearndComponents/learn/courses/info/CourseInfomation.vue';

const props = defineProps({
    course: Object,
    // lessons: Object,
    // groups: Object,
    isCourseAdmin: Boolean,
    courseMemberOfAuth: Object,
});

</script>

<template>
    <div>
        <CourseLayout 
            :course 
            :isCourseAdmin
            :activeTab="12"
            :courseMemberOfAuth
        >
            <template #courseContent>
                <div class=" mt-4">
                    <CourseInfomation
                        :course="props.course.data" 
                        :isCourseAdmin="props.isCourseAdmin"
                    />
                </div>
            </template>
        </CourseLayout>
    </div>
</template>
