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
import axios from 'axios';

const isLoadingAcademyPosts = ref(false);
const academyActivities = ref(props.activities?.data || []);
const page = ref(1);
const nextPageUrl = ref(props.activities?.links?.next || props.activities?.next_page_url || null);

const fetchActivities = async (url = null) => {
    try {
        isLoadingAcademyPosts.value = true;
        const targetUrl = url || `/api/academies/${props.academy.data.id}/activities?page=${page.value}`;
        const response = await axios.get(targetUrl);
        
        if (response.data.success || response.data.data) { // Handle different response structures if any
            const data = response.data.activities || response.data;
            if (url) {
                // Load more
                academyActivities.value = [...academyActivities.value, ...data.data];
            } else {
                // Refresh
                academyActivities.value = data.data;
            }
            nextPageUrl.value = data.links?.next || data.next_page_url;
            page.value = data.current_page;
        }
    } catch (error) {
        console.error('Failed to fetch activities:', error);
    } finally {
        isLoadingAcademyPosts.value = false;
    }
};

const handlePostCreated = () => {
    // Refresh the feed to get the new post (simplest way to ensure correct data structure)
    page.value = 1;
    fetchActivities(`/api/academies/${props.academy.data.id}/activities?page=1`);
};

const handlePostDeleted = (index) => {
    academyActivities.value.splice(index, 1);
};

const loadMorePosts = () => {
    if (nextPageUrl.value) {
        fetchActivities(nextPageUrl.value);
    }
};

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
                    <CreateAcademyPost 
                        :academy_id="props.academy.data.id" 
                        @post-created="handlePostCreated"
                    />
                    
                    <div v-if="isLoadingAcademyPosts && page === 1" class="mt-4">
                        <PostLoadingSkeleton />
                    </div>

                    <div v-else-if="academyActivities.length">
                        <div v-for="(activity, index) in academyActivities" :key="activity.id || index">
                            <AcademyPostViewer 
                                :activity="activity" 
                                @delete-post="handlePostDeleted(index)"
                            />
                        </div>

                        <!-- Load More -->
                        <div v-if="nextPageUrl" class="text-center mt-4 mb-8">
                            <button 
                                @click="loadMorePosts" 
                                :disabled="isLoadingAcademyPosts"
                                class="bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 px-6 py-2 rounded-full text-sm font-medium transition-colors"
                            >
                                <span v-if="isLoadingAcademyPosts">กำลังโหลด...</span>
                                <span v-else>ดูเพิ่มเติม</span>
                            </button>
                        </div>
                    </div>

                    <div v-else-if="!academyActivities.length && !isLoadingAcademyPosts">
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

