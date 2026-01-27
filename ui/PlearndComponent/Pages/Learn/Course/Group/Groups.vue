<script setup>

import CourseLayout from '@/Layouts/CourseLayout.vue';
import CourseGroupList from '@/PlearndComponents/learn/courses/groups/CourseGroupList.vue';

const props = defineProps({
    course: Object,
    groups: Object,
    isCourseAdmin: Boolean,
    courseMemberOfAuth: Object,
});


</script>

<template>
    <div>
        <CourseLayout 
            :course 
            :groups
            :isCourseAdmin
            :activeTab="5"
            :courseMemberOfAuth
        >
            <template #courseContent>
                <div>
                    <CourseGroupList 
                        :course="props.course.data" 
                        :groups='props.groups.data' 
                        :courseMemberOfAuth="props.courseMemberOfAuth"

                        @add-new-group="(group)=> props.groups.data.push(group)"
                        @delete-group="(index) => props.groups.data.splice(index,1)"
                    />
                </div>
            </template>
        </CourseLayout>
    </div>
</template>
