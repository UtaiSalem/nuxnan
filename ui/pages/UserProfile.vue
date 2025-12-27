 <script setup>
// import { Head } from "@inertiajs/vue3";
import MainLayout from "~/layouts/main.vue";
import CreatePost from '@/PlearndComponents/widgets/CreatePost.vue';
import NewsFeedPostsViewer from '@/PlearndComponents/play/posts/NewsFeedPostsViewer.vue';

// import ProfileCover from "@/components/partials/ProfileCover.vue";
// import QuickPostBox from '@/PlearndComponents/widgets/QuickPostBox.vue';
// import ActivitiesFeed from '@/components/widgets/ActivitiesFeed.vue';
// import CreateNewSchoolWidget from '@/components/widgets/CreateNewSchoolWidget.vue';
// import { ref } from "vue";
// import { usePage } from '@inertiajs/inertia-vue3';


const props = defineProps({
    user: Object,
    activities: Object,
});

</script>
<template>
    <!-- <Head title="User Profile" /> -->
    <MainLayout title="User Profile">
        <template #header>
            <!-- <ProfileCover 
                :coverImage="$page.props.userProfile.data.profile.cover_image"
                :profileImage="$page.props.userProfile.data.avatar" 
                :coverName="$page.props.userProfile.data.name"
            /> -->
        </template>

        <template #leftSideWidget>
            <div>
                <!-- <CreateNewSchoolWidget /> -->
            </div>
        </template>

        <template #mainContent>
            <div>
                <div>
                    <div class="flex items-center max-w-7xl mx-auto mt-2 mb-4 shadow-lg bg-[url('/storage/images/banner/banner-bg.png')] bg-cover bg-no-repeat rounded-lg">
                        <img class="section-banner-icon " :src="'/storage/images/banner/forums-icon.png'" alt="forums-icon">
                        <p class="text-white font-bold text-4xl">กระดานข่าว {{ props.user.name }}</p>
                    </div>

                    <CreatePost />

                    <div v-for="(activity,index) in props.activities.data" :key="index" >
                        <div v-if="activity.action_to === 'Post'">
                            <NewsFeedPostsViewer :activity @delete-post="props.activities.data.splice(index,1)" />
                        </div>
                        <div v-else-if="activity.action_to === 'Poll'">
                            <!-- <PollViewer :activity="activity" /> -->
                            'Poll Viewer'
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template #rightSideWidget>
            <div>
                <!-- <CreateNewSchoolWidget /> -->
            </div>
        </template>

    </MainLayout>
</template>

