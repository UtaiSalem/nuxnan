<script setup>
import { ref } from 'vue';
import AcademyLayout from '@/Layouts/AcademyLayout.vue';
import PostLoadingSkeleton from '@/PlearndComponents/accessories/PostLoadingSkeleton.vue';

import CreateAcademyPost from '@/PlearndComponents/learn/academies/posts/CreateAcademyPost.vue';
import AcademyPostViewer from '@/PlearndComponents/learn/academies/posts/AcademyPostViewer.vue';

const props = defineProps({
    academy: Object,
    isAcademyAdmin: Boolean,
    activities: Array,
});

const isLoadingAcademyPosts = ref(false);
const academyActivities = ref(props.activities.data);

</script>

<template>
        <AcademyLayout 
            :academy
            :isAcademyAdmin
        >

            <template #academyContent>
                <div class="mt-4">
                    <CreateAcademyPost :academy_id="props.academy.data.id" />
                    
                    <div v-if="isLoadingAcademyPosts" class="mt-4">
                        <PostLoadingSkeleton />
                    </div>

                    <div v-else-if="academyActivities.length">
                        <div v-for="(activity, index) in academyActivities" :key="index">
                            <AcademyPostViewer :activity="activity" />
                        </div>
                    </div>

                    <div v-else-if="!academyActivities.length">
                        <div class="flex justify-center items-center h-[50vh]">
                            <p class="text-gray-500 text-lg">ยังไม่มีกิจกรรมใดๆ ในขณะนี้</p>
                        </div>
                    </div>

                </div>
            </template>
        </AcademyLayout>
</template>
