<script setup>
import { ref, computed } from 'vue';
import { Icon } from '@iconify/vue';
import { usePage } from '@inertiajs/vue3'
import { useAuthStore } from '@/stores/auth';

import AvatarGroupLikesWindUI from '@/PlearndComponents/learn/courses/posts/PostViewerPartials/AvatarGroupLikesWindUI.vue'
import LikeCoursePostReaction from '@/PlearndComponents/learn/courses/posts/PostViewerPartials/PostReactions/LikePostReaction.vue';
import DislikeCoursePostReaction from '@/PlearndComponents/learn/courses/posts/PostViewerPartials/PostReactions/DislikePostReaction.vue';
import CoursePostCommentsViewer from '@/PlearndComponents/learn/courses/posts/comments/CoursePostCommentsViewer.vue';

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
    refPost.value.author.point += 12;
};

const userUnlikePostHandler = () => {
    refPost.value.likes--;
    refPost.value.isLikedByAuth = false;
    authStore.addPoints(12);
};

const userDislikePostHandler = () => {
    refPost.value.dislikes++;
    refPost.value.isDislikedByAuth = true;
    authStore.deductPoints(12);
};

const userUndislikePostHandler = () => {
    refPost.value.dislikes--;
    refPost.value.isDislikedByAuth = false;
    authStore.addPoints(12);
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
                
                <div class="flex gap-2 items-center" >
                    <Icon icon="ant-design:comment-outlined" class="w-5 h-5 text-blue-500" />
                    <span class="text-gray-400 text-sm">{{ refPost.comments }} {{ refPost.comments < 2 ? 'comment' : 'comments' }}</span>
                </div>

            </div>

        </div>

        <hr class="text-gray-400 mt-3">

        <div v-if="canLikeOrDisLike" class="flex justify-around items-center" >

            <LikeCoursePostReaction 
                :post_url="props.post.post_url"
                :desModelId="refPost.id" 
                :isLikedByAuth="refPost.isLikedByAuth" 
                :isDislikedByAuth="refPost.isDislikedByAuth" 
                @user-like-post="userLikePostHandler" 
                @user-unlike-post="userUnlikePostHandler"
            /> 

            <DislikeCoursePostReaction 
                :post_url="props.post.post_url"
                :desModelId="refPost.id" 
                :isLikedByAuth="refPost.isLikedByAuth" 
                :isDislikedByAuth="refPost.isDislikedByAuth"  
                @user-dislike-post="userDislikePostHandler" 
                @user-undislike-post="userUndislikePostHandler"
            />

        </div>

        <hr v-if="canLikeOrDisLike" class="text-gray-400 mb-2">

        <div>
            <CoursePostCommentsViewer
                :postId="post.id"
                :post="post"
                :isPostOwner="!canLikeOrDisLike"
                :comments="post.post_comments" 
                :comments_count="post.comments_count" 
                @new-comment-added="refPost.comments++"
            />
        </div>
    </div>
</template>


