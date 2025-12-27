<script setup>
import { ref, onMounted } from 'vue';
import CourseLayout from '@/Layouts/CourseLayout.vue';
import StaggeredFade from '@/PlearndComponents/accessories/StaggeredFade.vue';
import GroupedMemberList from '@/PlearndComponents/learn/courses/members/GroupedMemberList.vue'
// import NonGroupedMemberList from '@/PlearndComponents/learn/courses/members/NonGroupedMemberList.vue'
const props = defineProps({
    course: Object,
    members: Object, 
    groups: Object,
    isCourseAdmin: Boolean,
    courseMemberOfAuth: Object,
});


</script>

<template>
    <div>
     <CourseLayout
        :isCourseAdmin
        :courseMemberOfAuth
        :course
        :activeTab="4"
     >
        <template #courseContent>
            <div class="mt-4">
                <staggered-fade :duration="50" tag="ul" class="flex flex-col w-full ">
                    <GroupedMemberList :groups="props.groups.data" />
                </staggered-fade>
            </div>
        </template>

     </CourseLayout>
    </div>
</template>

