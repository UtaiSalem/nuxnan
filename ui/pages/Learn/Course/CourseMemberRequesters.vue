<script setup>
import { ref, onMounted } from 'vue';
import CourseLayout from '@/Layouts/CourseLayout.vue';
import StaggeredFade from '@/PlearndComponents/accessories/StaggeredFade.vue';
// import GroupedMemberList from '@/PlearndComponents/learn/courses/members/GroupedMemberList.vue'
import NonGroupedMemberList from '@/PlearndComponents/learn/courses/members/NonGroupedMemberList.vue'
const props = defineProps({
    isCourseAdmin: Boolean,
    course: Object,
    lessons: Object,
    groups: Object,
    members: Object,
    courseMemberOfAuth: Object,
});

const isLoading = ref(true);

onMounted(() => {
    setTimeout(() => {
        isLoading.value = false;
    }, 1000);
});
</script>

<template>
    <div>
     <CourseLayout
        :isCourseAdmin
        :course
        :lessons
        :groups
        :courseMemberOfAuth
        :activeTab="6"
     >
        <template #courseContent>
            <div>
                <!-- <div v-if="isLoading">
                    <div class="flex justify-center items-center h-64">
                        <div class="flex items-center space-x-2">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V2.5"></path>
                            </svg>
                            <span>Loading...</span>
                        </div>
                    </div>
                </div> -->
                <div class=" mt-4">
                    <staggered-fade :duration="50" tag="ul" class="flex flex-col w-full ">
                        <NonGroupedMemberList
                            :members="props.members.data"
                            :groups="props.groups.data"
                        />
                    </staggered-fade>
                </div>

            </div>
        </template>
     </CourseLayout>
    </div>
</template>

