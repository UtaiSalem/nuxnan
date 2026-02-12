<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import PostContentViewer from './PostViewerPartials/PostContentViewer.vue';
import PostImagesViewer from './PostViewerPartials/PostImagesViewer.vue';
import IndividualPostImageViewer from './PostViewerPartials/IndividualPostImageViewer.vue';
import PostReactionViewer from './PostViewerPartials/PostReactionViewer.vue';
import PostPagePostSettingMenuItem from './PostViewerPartials/PostMenuItems/PostPagePostSettingMenuItem.vue';

import { Link, usePage, router } from '@inertiajs/vue3';

const props = defineProps({
    activity: Object,
});

// const router = useRouter();
// const isPostCreation = ref(props.activity.action == "createpost");
const isPostCreation = computed(() => props.activity.action === "createpost");
const isPostOwner = computed(() => props.activity.action_by.id === usePage().props.auth.user.id);

const postSettingMenus = reactive([
    { name: 'แก้ไขโพสต์', icon: 'bi:pencil-square', action: 'edit', url: props.activity.target_resource.post_url+'/edit' , isAuthor: isPostOwner, color: 'yellow-400'},
    { name: 'ลบโพสต์', icon: 'fa-solid:trash-alt', action: 'delete', url: props.activity.target_resource.post_url, isAuthor: isPostOwner, color: 'red-500'},
    { name: 'แจ้งลบโพสต์', icon: 'bi:exclamation-triangle', action: 'report', url: '', isAuthor: isPostOwner, color: 'red-500'},
]);

const handleBackLink = () => {
    // return window.history.back();
    // console.log(window.history.back());
    window.history.back();
};


</script>

<template>
    <div class="my-4">
        <div class="flex justify-between gap-2 mb-2">
            <button @click="handleBackLink" class="inline-flex items-center border border-indigo-300 px-3 py-1.5 rounded-md text-indigo-500 hover:bg-indigo-50">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="h-6 w-6">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18">
                    </path>
                </svg>
                <span class="ml-1 font-bold text-lg">Back</span>
            </button>
            <!-- <a href="#"
                class="inline-flex items-center border border-indigo-300 px-3 py-1.5 rounded-md text-indigo-500 hover:bg-indigo-50">
                <span class="mr-1 font-bold text-lg">Next</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="h-6 w-6">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3">
                    </path>
                </svg>
            </a> -->
        </div>

        <div class="bg-white shadow-lg rounded-lg dark:bg-dark-card py-2 relative " v-if="activity">
            
            <div class="absolute flex w-full justify-end pr-4 pt-2" >
                <PostPagePostSettingMenuItem :activity :postSettingMenus />
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
                                <!-- <div>{{ activity.action_by.name }}</div> -->
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
                
                <div>
                    <IndividualPostImageViewer 
                        :images="activity.target_resource.images"
                        :images_resources="activity.target_resource.imagesResources"
                        :model_id="activity.target_resource.id" 
                        :post="activity.target_resource"
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

