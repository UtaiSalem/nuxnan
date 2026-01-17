<script setup>
import { ref } from 'vue';
import AcademyLayout from '@/layouts/AcademyLayout.vue';
import PostLoadingSkeleton from '@/components/accessories/PostLoadingSkeleton.vue';

import CreateAcademyPost from '@/components/learn/academy/posts/CreateAcademyPost.vue';
import AcademyPostViewer from '@/components/learn/academy/posts/AcademyPostViewer.vue';

const props = defineProps({
    academy: Object,
    isAcademyAdmin: Boolean,
    activities: Object,
});

import AcademyInfoWidget from '@/components/learn/academy/widgets/AcademyInfoWidget.vue';
import AcademyActivityWidget from '@/components/learn/academy/widgets/AcademyActivityWidget.vue';
import AcademyAnnouncementWidget from '@/components/learn/academy/widgets/AcademyAnnouncementWidget.vue';

const isLoadingAcademyPosts = ref(false);
const academyActivities = ref(props.activities?.data || []);

</script>

<template>
        <AcademyLayout 
            :academy
            :isAcademyAdmin
        >
            <template #leftSideWidget>
                <div class="space-y-4">
                    <AcademyInfoWidget :academy="props.academy" />
                    <AcademyAnnouncementWidget :academyId="props.academy.data.id" />
                </div>
            </template>

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

            <template #rightSideWidget>
                <div class="space-y-4">
                    <AcademyActivityWidget :academyId="props.academy.data.id" />
                </div>
            </template>
        </AcademyLayout>
</template>

