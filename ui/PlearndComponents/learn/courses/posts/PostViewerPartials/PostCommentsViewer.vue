<script setup>
import { computed, ref } from 'vue';
import { Icon } from '@iconify/vue';

import CommentForm from '@/PlearndComponents/widgets/CommentForm.vue';
// import ModalShowAllComment from './ModalShowAllComment.vue';
import PostCommentItem from './PostCommentItem.vue';
const props = defineProps({
    comments_count: Number,
    comments: Object,
    postId: Number,
    isPostOwner: Boolean
});
const emit = defineEmits(['new-comment-added']);
// const initComments = ref(computed(()=> props.comments));

const initComments = ref(props.comments);
const commentsCount = ref(props.comments_count);
const postComments = ref(null);
const showAllComments = computed(()=> props.comments_count > 3);

const isLoading = ref(false);
const showCommentsModal = ref(false);

const handleAddNewComment = (newComment) => {
    initComments.value.unshift(newComment);
    commentsCount.value ++;
    emit('new-comment-added');
    // console.log(newComment.content);
}

const showCommentsModalHandler = async () => {
    try {
        showCommentsModal.value = true;
        isLoading.value = true;
        let commentsResponse = await axios.get(`/posts/${props.postId}/comments`);
        postComments.value = commentsResponse.data.comments;
        isLoading.value = false;
    } catch (error) {
        isLoading.value = false;
        showCommentsModal.value = false;
    }
    // setTimeout( async ()=>{
    // }, 1200);
    // isLoading.value = false;
}

const clearCommentModal = () => {
    showCommentsModal.value = false;
    postComments.value = null;
};

const handleDeletePostComment = async (idx) => {
    initComments.value.splice(idx, 1);
    commentsCount.value --;
    // emit('new-comment-added');
    props.comments_count.splice(idx, 1);
    props.comments_count --;
};

</script>

<template>
    <div>
        <div>
            <CommentForm 
                :postId="postId" 
                @add-new-comment="(newCom) => handleAddNewComment(newCom)"
                class=" mb-4"
            />

            <PostCommentItem v-for="(comment, index) in initComments" :key="comment.id" :comment="comment" :isPostOwner @delete-post-comment="handleDeletePostComment(index)" />

            <div v-if="showAllComments" class="flex flex-col w-full">
                <!-- <PostCommentItem :comment="comment" v-for="comment in initComments.slice(-3)" :key="comment.id" /> -->
                <button @click.prevent="showCommentsModalHandler" type="button" class="ml-auto mr-2 text-sm">
                    ...ดูความคิดเห็นเพิ่มเติม...
                </button>
            </div>
        </div>

        <div v-if="showCommentsModal">
            <div class="flex fixed top-0 left-0 right-0 z-[50] p-6 w-full h-full bg-gray-800 bg-opacity-80 justify-center items-start py-16">
                <div v-if="isLoading" class="flex w-full max-w-xl  bg-white text-green-600 rounded-lg justify-center items-center  py-10">
                    <Icon icon="eos-icons:bubble-loading" class="w-10 h-10 mx-5" />Loading...
                </div>
                <div v-else class="w-full max-w-xl bg-white rounded-lg mx-6">
                    <div class="relative shadow dark:bg-gray-700 rounded-lg flex flex-col max-h-[80vh]">
                        <div class="flex justify-between items-center">
                            <button type="button" @click="clearCommentModal" class="absolute top-3 right-2.5 text-red-500  hover:bg-red-300 hover:text-red-600 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                                <Icon icon="heroicons:x-mark-20-solid" class="w-6 h-6 hover:scale-125" />
                                <span class="sr-only">Close modal</span>
                            </button>
                        </div>
                        <div class="mt-14 px-4 py-2 overflow-y-auto border-t-2 border-gray-400">
                            <div class=" ">
                                <ul>
                                    <li v-for="postComment in postComments" :key="postComment.id" class="">
                                        <PostCommentItem :comment="postComment" :isPostOwner />
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div>
                            <span class="text-white"></span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</template>


