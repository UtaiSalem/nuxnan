<script setup>
import { ref, reactive } from 'vue';
import { Icon } from '@iconify/vue';

import PostImageCommentForm from '@/PlearndComponents/learn/courses/posts/comments/images/PostImageCommentForm.vue';
// import ModalShowAllComment from './ModalShowAllComment.vue';
import PostImageCommentItem from './PostImageCommentItem.vue';
// import Course from '@/Pages/Learn/Course/Course.vue';

const props = defineProps({
    courseId: Number,
    postId: Number,
    comments: Object,
    commentsCount: Number,
    postImageID: Number,
    isPostOwner: Boolean
});
const emit = defineEmits(['new-comment-added', 'comment-deleted']);

const showAllComments = ref(props.commentsCount > 3 ? true:false);
const isLoading = ref(false);

const tempComments = ref(props.comments);

const handleAddNewComment = (newComment) => {
    tempComments.value.unshift(newComment);
    emit('new-comment-added', newComment);
}

const handleDeleteComment = async (idx) => {
    tempComments.value.splice(idx, 1);
    emit('comment-deleted');
};

const showCommentsModalHandler = async () => {
    try {
        isLoading.value = true;
        let imageCommentsResponse = await axios.get(`/courses/${props.courseId}/posts/${props.postId}/images/${props.postImageID}/comments`);
        if (imageCommentsResponse.data) {
            // showAllComments.value = true;
            tempComments.value = imageCommentsResponse.data.comments;
            showAllComments.value = false;
        }
        isLoading.value = false;
    } catch (error) {
        isLoading.value = false;
    }
}

</script>

<template>
    <div>
        <div>
            <PostImageCommentForm
                :courseId
                :postId
                :postImageID
                @add-new-comment="(newCom) => handleAddNewComment(newCom)"
                class=" mb-4"
            />

            <!-- <div v-if="!showAllComments">
                <PostImageCommentItem 
                    v-for="(comment,index) in tempComments" 
                    :key="comment.id" 
                    :comment 
                    @delete-image-comment="handleDeleteComment(comment.id,index)" 
                />
            </div> -->

            <div class="flex flex-col w-full relative">

                <div v-if="isLoading" class="flex w-full rounded-lg justify-center items-center  py-10">
                    <Icon icon="eos-icons:bubble-loading" class="w-8 h-8 mx-5 text-violet-600" />Loading...
                </div>

                <PostImageCommentItem  v-for="(comment,index) in tempComments" :key="comment.id" 
                    :courseId
                    :postId
                    :comment
                    :isPostOwner
                    @delete-image-comment="handleDeleteComment(index)"
                />
                
                <button @click.prevent="showCommentsModalHandler" v-if="showAllComments"  type="button" class="ml-auto mr-2 text-sm">
                    ...ดูความคิดเห็นเพิ่มเติม...
                </button>

            </div>
        </div>
    </div>
</template>


