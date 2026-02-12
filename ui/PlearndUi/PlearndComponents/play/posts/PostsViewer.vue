<script setup>
import { ref, computed, onMounted } from 'vue';
import PostContentViewer from './PostViewerPartials/PostContentViewer.vue';
import PostImagesViewer from './PostViewerPartials/PostImagesViewer.vue';
import IndividualPostImageViewer from './PostViewerPartials/IndividualPostImageViewer.vue';
import PostReactionViewer from './PostViewerPartials/PostReactionViewer.vue';
import NewsFeedPagePostSettingMenuItem from './PostViewerPartials/PostMenuItems/NewsFeedPagePostSettingMenuItem.vue';

import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    activity: Object,
    postSettingMenus: Array
});

// const isPostCreation = ref(props.activity.action == "createpost");
const isPostCreation = computed(() => props.activity.action === "createpost");
const isPostOwner = computed(() => props.activity.action_by.id === usePage().props.auth.user.id);
const setPostSettingMenus = ref(null);

onMounted(() => {
    setPostSettingMenus.value = props.postSettingMenus.map((item) => {
            item.url = props.activity.target_resource.post_url;
            item.isAuthor = isPostOwner;
            item.showMenu = isPostOwner;
            return item;
    });
    // console.log(props.activity.id);
});

</script>

<template>
    <div class="my-4">
        <div class="bg-white shadow-lg rounded-lg dark:bg-dark-card py-2 relative " v-if="activity">
            
            <div class="absolute flex w-full justify-end pr-4 pt-2" >
                <NewsFeedPagePostSettingMenuItem :activity :postSettingMenus="setPostSettingMenus" />
            </div>

            <div v-if="!isPostCreation">
                <div  class="px-4 pt-4 flex justify-between">
                    <div>
                        <div class="flex justify-center gap-4">
                            <img :src="activity.action_by.avatar" class="w-12 h-12 p-[3px] rounded-full ring-1 ring-blue-600 dark:ring-gray-500" alt="">
                            <div>
                                <div class="flex items-center gap-4 mb-2">
                                    <h6 class="">{{ activity.action_by.name }}</h6>
                                    <small class="text-gray-800 mt-1 dark:text-secondary-600">
                                        {{ activity.action }}
                                    </small>
                                    <small class="text-gray-800 mt-1 dark:text-secondary-600">
                                        {{ activity.action_to }}
                                    </small>
                                </div>
                                <!-- <p class="text-gray-500 text-xs">{{ activity.created_at }}</p> -->
                                <p class="text-gray-500 text-xs">{{ activity.diff_humans_created_at }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div :class="!isPostCreation ? 'm-2 border-[1.5px] border-gray-200 rounded-lg':''" class="px-4">

                <div class="text-gray-700 text-base rounded-lg" >
                    <PostContentViewer 
                        :post="activity.target_resource" 
                        :action_to_id="activity.action_to_id"
                        :actorId="activity.action_by.id"
                        :acting="activity.action" 
                    />
                </div>
                
                <div v-if="$page.component === 'Newsfeed'">
                    <PostImagesViewer 
                        :images="activity.target_resource.images" 
                        :model_id="activity.target_resource.id" 
                        :edit="activity.target_resource.author.id === $page.props.auth.user.id"
                    />
                </div>

                <div v-if="$page.component === 'Post'">
                    <IndividualPostImageViewer 
                        :images="activity.target_resource.images" 
                        :model_id="activity.target_resource.id" 
                        :edit="activity.target_resource.author.id === $page.props.auth.user.id"
                    />
                </div>

                <div>
                    <PostReactionViewer 
                        :post="activity.target_resource" 
                        :model="activity.action_to.toLowerCase()"                
                    />
                </div>

            </div>
        </div>
    </div>
</template>

