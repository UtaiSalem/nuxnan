<script setup>
import { ref, computed } from 'vue';
import { Icon } from '@iconify/vue';
import { usePage } from '@inertiajs/vue3'

import AvatarGroupLikesWindUI from '@/PlearndComponents/learn/courses/posts/PostViewerPartials/AvatarGroupLikesWindUI.vue'
import PostImageLikeReaction from '@/PlearndComponents/learn/courses/posts/PostViewerPartials/PostReactions/PostImageLikeReaction.vue';
import PostImageDislikeReaction from '@/PlearndComponents/learn/courses/posts/PostViewerPartials/PostReactions/PostImageDislikeReaction.vue';
import PostImageCommentsViewer from '@/PlearndComponents/learn/courses/posts/PostViewerPartials/PostImages/PostImageCommentsViewer.vue';

const pageData = usePage();

const props = defineProps({
    post: Object,
    image: Object,
    model: String,
});

const refImage = ref(props.image);
const canLikeOrDisLike = computed( () => props.post.author.id !== pageData.props.auth.user.id );
const isPostOwner = computed(() => props.post.author.id === usePage().props.auth.user.id);

const userLikePostImageHandler = () => {
    refImage.value.likes++;
    refImage.value.isLikedByAuth = true;
    pageData.props.auth.user.pp--;
};

const userUnlikePostImageHandler = () => {
    refImage.value.likes--;
    refImage.value.isLikedByAuth = false;
    pageData.props.auth.user.pp--;
};

const userDislikePostImageHandler = () => {
    refImage.value.dislikes++;
    refImage.value.isDislikedByAuth = true;
    pageData.props.auth.user.pp--;
};

const userUndislikePostImageHandler = () => {
    refImage.value.dislikes--;
    refImage.value.isDislikedByAuth = false;
    pageData.props.auth.user.pp--;
};

</script>

<template>
    <div class="p-2">
        <div class="flex w-full justify-between items-start lg:items-center flex-col lg:flex-row">
            <div class="flex w-full gap-3 items-center justify-between">
                <div class="">
                    <AvatarGroupLikesWindUI 
                        :likes="refImage.likes"
                        :showLikeIcon="canLikeOrDisLike"
                    />
                </div>

                <div class="flex">
                    <Icon icon="heroicons-solid:thumb-down" class="w-5 h-5 text-red-500 mx-2" v-if="canLikeOrDisLike"/>
                    <span class="text-gray-400 text-sm">{{ refImage.dislikes }} {{ refImage.dislikes < 2 ? 'dislike' : 'dislikes' }}</span>
                </div>
                

                <div class="flex gap-2 items-center" >
                    <Icon icon="ant-design:comment-outlined" class="w-5 h-5 text-blue-500" />
                    <span class="text-gray-400 text-sm">{{ image.comments }} {{ image.comments < 2 ? 'comment' : 'comments' }}</span>
                </div>


            </div>
        </div>

        <hr class="text-gray-400 mt-3">

        <div v-if="canLikeOrDisLike" class="flex justify-around items-center" >

            <PostImageLikeReaction
                :courseId="post.course_id"
                :postId="post.id"
                :desModelId="image.id" 
                :isLikedByAuth="image.isLikedByAuth" 
                :isDislikedByAuth="image.isDislikedByAuth" 
                @user-like-post_image="userLikePostImageHandler" 
                @user-unlike-post_image="userUnlikePostImageHandler"
            /> 

            <PostImageDislikeReaction
                :courseId="post.course_id"
                :postId="post.id"
                :desModelId="refImage.id" 
                :isLikedByAuth="refImage.isLikedByAuth" 
                :isDislikedByAuth="refImage.isDislikedByAuth"  
                @user-dislike-post-image="userDislikePostImageHandler" 
                @user-undislike-post-image="userUndislikePostImageHandler"
            />

        </div>

        <hr v-if="canLikeOrDisLike" class="text-gray-400 mb-2">

        <div>
            <PostImageCommentsViewer 
                :courseId="post.course_id"
                :postId="post.id"
                :postImageID="image.id"
                :comments="image.image_comments"
                :commentsCount="image.comments"
                :isPostOwner
                @new-comment-added="image.comments++"
                @comment-deleted="image.comments--"
            />
        </div>

    </div>
</template>


