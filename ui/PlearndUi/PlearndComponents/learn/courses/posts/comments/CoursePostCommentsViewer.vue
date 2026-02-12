<script setup>
import { computed, ref, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';


import InfiniteLoading from "v3-infinite-loading";
import CommentForm from '@/PlearndComponents/learn/courses/posts/comments/CommentForm.vue';
import CoursePostCommentItem from '@/PlearndComponents/learn/courses/posts/comments/CoursePostCommentItem.vue';
import PostLoadingSkeleton from '@/PlearndComponents/accessories/PostLoadingSkeleton.vue';
import CourseFeedShowAllComments from './CourseFeedShowAllComments.vue';

const props = defineProps({
    comments_count: Number,
    comments: Object,
    postId: Number,
    post: Object,
    isPostOwner: Boolean
});
const emit = defineEmits(['new-comment-added']);

const initComments = ref(props.comments);
const commentsCount = ref(props.comments_count);
const postComments = ref([]);
const showAllComments = computed(()=> props.comments_count > 3);

const isLoading = ref(false);
const isLoadingCoursePostComments = ref(false);
const showCommentsModal = ref(false);
const currentPage = ref(1);
const lastPage = ref();
const nextPageUrl = ref();
const lastPageUrl = ref();
const isFinished = ref(false);

const handleAddNewComment = (newComment) => {
    initComments.value.unshift(newComment);
    commentsCount.value++;
    emit('new-comment-added');
}

const showCommentsModalHandler = async () => {
    try {
        showCommentsModal.value = true;
        isLoading.value = false;
    } catch (error) {
        isLoading.value = false;
        showCommentsModal.value = false;
        console.log(error);
    }
}

const clearCommentModal = () => {
    showCommentsModal.value = false;
    postComments.value = null;
    currentPage.value = null;
    lastPage.value = null;
    nextPageUrl.value = null;
    lastPageUrl.value = null;
    isFinished.value = false;
};

const handleDeleteComment = async (idx) => {
    initComments.value.splice(idx, 1);
    commentsCount.value--;
};

const handleDeletePostComment = async (idx) => {
    postComments.value.splice(idx, 1);
    nextPageUrl.value = `/courses/${props.post.course_id}/posts/${props.postId}/comments`;
    fetchMorePostComments();
};

const fetchMorePostComments = async () => {
    if (isLoadingCoursePostComments.value) return;
    try {
        // ตรวจสอบเฉพาะ nextPageUrl เพื่อให้โหลดข้อมูลจนครบ
        if (nextPageUrl.value) {
            isLoadingCoursePostComments.value = true;
            let commentsResp = await axios.get(nextPageUrl.value);
            
            if (commentsResp.data.success) {
                commentsResp.data.comments.forEach(comment => {
                    postComments.value.push(comment);
                });
                
                // อัพเดทข้อมูลการแบ่งหน้า
                currentPage.value = commentsResp.data.post_comments.current_page;
                lastPage.value = commentsResp.data.post_comments.last_page;
                nextPageUrl.value = commentsResp.data.post_comments.next_page_url;

                if (currentPage.value === lastPage.value) {
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

onMounted(() => {
    if(usePage().component == 'Learn/Course/Post/CoursePost'){
        currentPage.value = 0;
        lastPage.value = 2;
        initComments.value = [];
        nextPageUrl.value = `/courses/${props.post.course_id}/posts/${props.postId}/comments`;
        fetchMorePostComments();
    }
});

</script>

<template>
    <div>
        <div>
            <CommentForm 
                :postId="postId"
                :post_url="post.post_url"

                @add-new-comment="(newCom) => handleAddNewComment(newCom)"
                class=" mb-4"
            />

            <CoursePostCommentItem 
                v-for="(comment, index) in initComments" :key="comment.id" 
                :comment="comment" 
                :cIndex="index+1"
                :isPostOwner 
                @delete-post-comment="handleDeleteComment(index)" 
            />

            <div v-if="showAllComments && $page.component === 'Learn/Course/CourseFeeds'" class="flex flex-col w-full">
                <button @click.prevent="showCommentsModalHandler" type="button" class="ml-auto mr-2 text-sm">
                    ...ดูความคิดเห็นเพิ่มเติม...
                </button>
            </div>

        </div>

        <div v-if="$page.component == 'Learn/Course/Post/CoursePost'">
            <PostLoadingSkeleton v-if="isLoading" />
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
            </div>
            <InfiniteLoading @distance="1" @infinite="fetchMorePostComments" :finished="isFinished" /> 
        </div>

        <div v-if="showCommentsModal && $page.component === 'Learn/Course/CourseFeeds'" >
            <CourseFeedShowAllComments
                :courseId="props.post.course_id"
                :postId
                :isPostOwner="props.isPostOwner" 
                @close-comments-modal="clearCommentModal"
            />
        </div>
    </div>
</template>


