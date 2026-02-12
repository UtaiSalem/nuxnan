<script setup>
import { usePage } from "@inertiajs/vue3";
import CourseLayout from '@/Layouts/CourseLayout.vue';
import CourseMemberSetting from '@/PlearndComponents/learn/courses/members/CourseMemberSetting.vue';

const props = defineProps({
    course: Object,
    lessons: Object,
    groups: Object,
    isCourseAdmin: Boolean,
    courseMemberOfAuth: Object,
    member: Object,
});

</script>

<template>
    <div>
        <CourseLayout 
            :course 
            :lessons
            :groups
            :isCourseAdmin
            :activeTab="9"
            :courseMemberOfAuth
        >
            <template #courseContent>
                <div class=" mt-4">
                    <CourseMemberSetting :member_info="props.member.data" />
                </div>
            </template>
        </CourseLayout>
    </div>
</template>
