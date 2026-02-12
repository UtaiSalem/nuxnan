<script setup>
import { ref } from 'vue';
import { Icon } from '@iconify/vue';
import InfiniteLoading from "v3-infinite-loading";
import CoursePostCommentItem from '@/PlearndComponents/learn/courses/posts/comments/CoursePostCommentItem.vue';
import PostLoadingSkeleton from '@/PlearndComponents/accessories/PostLoadingSkeleton.vue';

const props = defineProps({
    postId: Number,
    courseId: Number,
    isPostOwner: Boolean
});

const emit = defineEmits(['close-comments-modal']);

const postComments = ref([]);

const isLoadingCoursePostComments = ref(false);
const nextPageUrl = ref(`/courses/${props.courseId}/posts/${props.postId}/comments`);
const total = ref(1);
const isFinished = ref(false);

const fetchMorePostComments = async () => {
    if (isLoadingCoursePostComments.value) return;
    try {
        if (nextPageUrl.value) {

            isLoadingCoursePostComments.value = true;

            let commentsResp = await axios.get(nextPageUrl.value);
            
            if (commentsResp.data.success) {
                total.value = commentsResp.data.post_comments.total;
                nextPageUrl.value = commentsResp.data.post_comments.next_page_url;

                if (postComments.value.length < total.value) {
                    postComments.value = [...postComments.value, ...commentsResp.data.comments];
                } else {
                    isFinished.value = true;
                }
            }
        }

    } catch (error) {
        console.log(error);
    } finally {
        isLoadingCoursePostComments.value = false;
    }
};

const handleDeletePostComment = async (idx) => {
    postComments.value.splice(idx, 1);
    nextPageUrl.value = `/courses/${props.post.course_id}/posts/${props.postId}/comments`;
    fetchMorePostComments();
};


</script>

<template>
    <div class="flex fixed top-0 left-0 right-0 z-[80] p-2 w-full h-full bg-gray-800 bg-opacity-90 justify-center items-start py-16">
        <div class="w-full max-w-2xl bg-white rounded-lg mx-2">
            <div class="relative shadow dark:bg-gray-700 rounded-lg flex flex-col max-h-[90vh]">
                <div class="flex justify-between items-center">
                    <button type="button" @click="emit('close-comments-modal')"
                        class="absolute top-3 right-2.5 text-red-500  hover:bg-red-300 hover:text-red-600 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                        <Icon icon="heroicons:x-mark-20-solid" class="w-6 h-6 hover:scale-125" />
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <div class="mt-14 p-2 overflow-y-auto border-t-2 border-blue-400">
                    <!-- <PostLoadingSkeleton v-if="isLoading" /> -->
                    <div class=" ">
                        <ul>
                            <li v-for="(postComment, index) in postComments" :key="postComment.id" class="">
                                <CoursePostCommentItem 
                                    :cIndex="index + 1" 
                                    :comment="postComment" 
                                    :isPostOwner
                                    @delete-post-comment="handleDeletePostComment(index)" 
                                />
                            </li>
                        </ul>
                        <PostLoadingSkeleton v-if="isLoadingCoursePostComments" />
                        <InfiniteLoading @distance="1" @infinite="fetchMorePostComments" :finished="isFinished" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
