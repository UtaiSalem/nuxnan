<script setup>
import { ref, computed } from 'vue';
import { Icon } from '@iconify/vue';
import { usePage } from '@inertiajs/vue3'
import { useAuthStore } from '@/stores/auth';

import AvatarGroupLikesWindUI from '@/PlearndComponents/play/posts/PostViewerPartials/AvatarGroupLikesWindUI.vue'
import LikePostReaction from '@/PlearndComponents/partials/reactions/LikePostReaction.vue';
import DislikePostReaction from '@/PlearndComponents/partials/reactions/DislikePostReaction.vue';
import PostCommentsViewer from './PostCommentsViewer.vue';

const pageData = usePage();
const authStore = useAuthStore();

const props = defineProps({
    post: Object,
    model: String
});

const refPost = ref(props.post);
const canLikeOrDisLike = computed( () => props.post.author.id !== pageData.props.auth.user.id );

const userLikePostHandler = () => {
    refPost.value.likes++;
    refPost.value.isLikedByAuth = true;
    authStore.deductPoints(12);
    refPost.value.author.point++;
};

const userUnlikePostHandler = () => {
    refPost.value.likes--;
    refPost.value.isLikedByAuth = false;
    authStore.addPoints(12);
    refPost.value.author.point--;
};

const userDislikePostHandler = () => {
    refPost.value.dislikes++;
    refPost.value.isDislikedByAuth = true;
    authStore.deductPoints(12);
    refPost.value.author.point++;
};

const userUndislikePostHandler = () => {
    refPost.value.dislikes--;
    refPost.value.isDislikedByAuth = false;
    authStore.addPoints(12);
    refPost.value.author.point--;
};

</script>

<template>
    <div class="pt-2">
        <div class="flex w-full justify-between items-start lg:items-center flex-col lg:flex-row">
            <div class="flex w-full gap-3 items-center justify-between">
                <div class="flex">
                    <Icon icon="heroicons:eye" class="w-5 h-5 text-violet-500 mx-2" />
                    <span class="text-gray-400 text-sm">{{ refPost.views }} {{ refPost.views < 2 ? 'view' : 'views' }}</span>
                </div>

                <div class="">
                    <AvatarGroupLikesWindUI 
                        :likes="refPost.likes"
                        :showLikeIcon="true"
                    />
                </div>

                <div class="flex">
                    <Icon icon="heroicons-solid:thumb-down" class="w-5 h-5 text-red-500 mx-2" />
                    <span class="text-gray-400 text-sm">{{ refPost.dislikes }} {{ refPost.dislikes < 2 ? 'dislike' : 'dislikes' }}</span>
                </div>
                
                <!-- <div>
                    <a href="#" class="flex gap-2 items-center">
                        <svg class="icon-20 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="20" viewBox="0 0 24 24"
                            fill="none">
                            <path
                                d="M5.50052 15C6.37518 14.9974 7.21675 14.6653 7.85752 14.07L14.1175 17.647C13.9078 18.4666 14.0002 19.3343 14.378 20.0913C14.7557 20.8483 15.3935 21.4439 16.1745 21.7692C16.9555 22.0944 17.8275 22.1274 18.6309 21.8623C19.4343 21.5971 20.1153 21.0515 20.5493 20.3252C20.9832 19.599 21.1411 18.7408 20.994 17.9076C20.8469 17.0745 20.4047 16.3222 19.7483 15.7885C19.0918 15.2548 18.2652 14.9753 17.4195 15.0013C16.5739 15.0273 15.7659 15.357 15.1435 15.93L8.88352 12.353C8.94952 12.103 8.98552 11.844 8.99152 11.585L15.1415 8.06996C15.7337 8.60874 16.4932 8.92747 17.2925 8.97268C18.0918 9.01789 18.8823 8.78684 19.5315 8.31828C20.1806 7.84972 20.6489 7.17217 20.8577 6.39929C21.0666 5.6264 21.0032 4.80522 20.6784 4.0735C20.3535 3.34178 19.7869 2.74404 19.0735 2.38056C18.3602 2.01708 17.5436 1.90998 16.7607 2.07723C15.9777 2.24447 15.2761 2.67588 14.7736 3.29909C14.271 3.92229 13.9981 4.69937 14.0005 5.49996C14.0045 5.78796 14.0435 6.07496 14.1175 6.35296L8.43352 9.59997C8.1039 9.09003 7.64729 8.67461 7.10854 8.39454C6.5698 8.11446 5.96746 7.97937 5.3607 8.00251C4.75395 8.02566 4.16365 8.20627 3.64781 8.52658C3.13197 8.84689 2.70834 9.29589 2.41853 9.82946C2.12872 10.363 1.98271 10.9628 1.99484 11.5699C2.00697 12.177 2.17683 12.7704 2.48772 13.292C2.79861 13.8136 3.23984 14.2453 3.76807 14.5447C4.29629 14.8442 4.89333 15.0011 5.50052 15Z"
                                fill="currentColor"></path>
                        </svg>
                        <span class="text-gray-400 text-sm">15 Share</span>
                    </a>
                </div> -->



                <div class="flex gap-2 items-center" >
                    <Icon icon="ant-design:comment-outlined" class="w-5 h-5 text-blue-500" />
                    <span class="text-gray-400 text-sm">{{ refPost.comments }} {{ refPost.comments < 2 ? 'comment' : 'comments' }}</span>
                </div>

                <!-- <ul x-show.transition="open" x-bind="dropdownTransition"
                    class="z-10 py-2 absolute right-130 text-left text-secondary-500 bg-white rounded min-w-36 shadow-dropdown"
                    style="display: none;">
                    <li><a class="block w-full px-4 py-1 text-gray-500 bg-transparent whitespace-nowrap hover:text-primary-500 focus:text-white focus:bg-primary-500"
                            href="#">Max Emum</a></li>
                    <li><a class="block w-full px-4 py-1 text-gray-500 bg-transparent whitespace-nowrap hover:text-primary-500 focus:text-white focus:bg-primary-500"
                            href="#">Bill Yerds</a></li>
                </ul> -->

            </div>
            <!-- <div>
                            <a href="#" class="flex gap-2 items-center">
                                <svg class="icon-20 text-gray-400" width="20" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.4"
                                        d="M11.9912 18.6215L5.49945 21.864C5.00921 22.1302 4.39768 21.9525 4.12348 21.4643C4.0434 21.3108 4.00106 21.1402 4 20.9668V13.7087C4 14.4283 4.40573 14.8725 5.47299 15.37L11.9912 18.6215Z"
                                        fill="currentColor"></path>
                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                        d="M8.89526 2H15.0695C17.7773 2 19.9735 3.06605 20 5.79337V20.9668C19.9989 21.1374 19.9565 21.3051 19.8765 21.4554C19.7479 21.7007 19.5259 21.8827 19.2615 21.9598C18.997 22.0368 18.7128 22.0023 18.4741 21.8641L11.9912 18.6215L5.47299 15.3701C4.40573 14.8726 4 14.4284 4 13.7088V5.79337C4 3.06605 6.19625 2 8.89526 2ZM8.22492 9.62227H15.7486C16.1822 9.62227 16.5336 9.26828 16.5336 8.83162C16.5336 8.39495 16.1822 8.04096 15.7486 8.04096H8.22492C7.79137 8.04096 7.43991 8.39495 7.43991 8.83162C7.43991 9.26828 7.79137 9.62227 8.22492 9.62227Z"
                                        fill="currentColor"></path>
                                </svg>
                                <span class="text-gray-400">Save</span>
                            </a>
                        </div> -->

        </div>

        <hr class="text-gray-400 mt-3">

        <div v-if="canLikeOrDisLike" class="flex justify-around items-center" >

            <LikePostReaction 
                :desModelId="refPost.id" 
                :isLikedByAuth="refPost.isLikedByAuth" 
                :isDislikedByAuth="refPost.isDislikedByAuth" 
                @user-like-post="userLikePostHandler" 
                @user-unlike-post="userUnlikePostHandler"
            /> 

            <DislikePostReaction 
                :desModelId="refPost.id" 
                :isLikedByAuth="refPost.isLikedByAuth" 
                :isDislikedByAuth="refPost.isDislikedByAuth"  
                @user-dislike-post="userDislikePostHandler" 
                @user-undislike-post="userUndislikePostHandler"
            />

            <!-- <form action="" :id="`form-dislike-post-${post.id}`">
                <button :class="{}"
                    class="flex items-center space-x-1 text-sm text-gray-800 rounded-md px-2 py-1 hover:bg-blue-300 hover:scale-110 cursor-pointer">
                    <Icon icon="heroicons-outline:thumb-down" class="w-6 h-6 " />
                    <span>ไม่ถูกใจ</span>
                </button>
            </form> -->
            <!-- <button
                class="flex items-center space-x-1 text-sm text-gray-800 rounded-md px-2 py-1 hover:bg-blue-300 hover:scale-110 cursor-pointer">
                <Icon icon="heroicons-outline:share" class="w-6 h-6 text-gray-600" />
                <span>แบ่งปัน</span>
            </button> -->

            <!-- <div class="flex items-center space-x-1 text-sm text-gray-800 rounded-md px-2 py-1 hover:bg-blue-300"> -->
            <!-- <button 
                class="flex items-center space-x-1 text-sm text-gray-800 rounded-md px-2 py-1 hover:bg-blue-200 hover:scale-110">
                <Icon icon="heroicons-outline:chat-alt" class="w-6 h-6 text-gray-600" />
                <span>ดูความคิดเห็น</span>
            </button> -->
            <!-- </div> -->

        </div>

        <hr v-if="canLikeOrDisLike" class="text-gray-400 mb-2">

        <div>
            <PostCommentsViewer 
                :postId="post.id"
                :isPostOwner="!canLikeOrDisLike"
                :comments="post.post_comments" 
                :comments_count="post.comments_count" 
                @new-comment-added="refPost.comments++"
            />
        </div>

    </div>
</template>


