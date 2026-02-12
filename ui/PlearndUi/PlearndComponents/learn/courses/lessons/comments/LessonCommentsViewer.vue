<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import InfiniteLoading from "v3-infinite-loading";
import LessonCommentForm from '@/PlearndComponents/learn/courses/lessons/comments/LessonCommentForm.vue';
import LessonCommentItem from '@/PlearndComponents/learn/courses/lessons/comments/LessonCommentItem.vue';
import PostLoadingSkeleton from '@/PlearndComponents/accessories/PostLoadingSkeleton.vue';

const props = defineProps({
    lesson: {
        type: Object,
        required: true
    },
});

const initComments = ref(props.lesson.comments);
const lessonComments = ref([]);
const showAllComments = computed(() => props.lesson.comment_count > 3);
const showCommentsModal = ref(false);
const isLoading = ref(false);
const nextPageUrl = ref(null);

const currentPage = computed(() => usePage().component);

const displayComments = computed(() => {
    return currentPage.value === 'Learn/Course/Lesson/Lesson' ? lessonComments.value : initComments.value;
});

const showCommentsModalHandler = async () => {
    showCommentsModal.value = true;
    if (lessonComments.value.length === 0) {
        nextPageUrl.value = `/lessons/${props.lesson.id}/comments`;
        await fetchMoreComments();
    }
}

const clearCommentsModal = () => {
    showCommentsModal.value = false;
    lessonComments.value = [];
    nextPageUrl.value = null;
};

const fetchMoreComments = async () => {
    if (isLoading.value || !nextPageUrl.value) return;
    try {
        isLoading.value = true;
        const commentsResp = await axios.get(nextPageUrl.value);
        if (commentsResp.data) {
            lessonComments.value.push(...commentsResp.data.data);
            nextPageUrl.value = commentsResp.data.links.next;
        }
    } catch (error) {
        console.error('Error fetching comments:', error);
    } finally {
        isLoading.value = false;
    }
};

const handleDeleteComment = (index) => {
    if (currentPage.value === 'Learn/Course/Lesson/Lesson') {
        lessonComments.value.splice(index, 1);
    } else {
        initComments.value.splice(index, 1);
    }
    props.lesson.comment_count--;
}

const handleNewComment = (comment) => {
    if (currentPage.value === 'Learn/Course/Lesson/Lesson') {
        lessonComments.value.unshift(comment);
    } else {
        initComments.value.unshift(comment);
    }
    props.lesson.comment_count++;
}

onMounted(() => {
    if (currentPage.value === 'Learn/Course/Lesson/Lesson') {
        initComments.value = [];
        nextPageUrl.value = `/lessons/${props.lesson.id}/comments`;
        fetchMoreComments();
    }
});
</script>

<template>
    <div class="m-2">
        <LessonCommentForm 
            :lessonId="props.lesson.id"
            :lessonUrl="props.lesson.url"
            @add-new-comment="handleNewComment"
        />

        <LessonCommentItem 
            v-for="(comment, index) in displayComments" 
            :key="comment.id"
            :comment="comment"
            :lessonId="props.lesson.id"
            @delete-lesson-comment="handleDeleteComment(index)"
        />

        <div v-if="showAllComments && currentPage === 'Learn/Course/Lesson/Lessons'" class="flex flex-col w-full my-2">
            <button @click.prevent="showCommentsModalHandler" type="button" class="ml-auto mr-2 text-sm">
                ...ดูความคิดเห็นเพิ่มเติม...
            </button>
        </div>

        <div v-if="currentPage === 'Learn/Course/Lesson/Lesson'">
            <PostLoadingSkeleton v-if="isLoading" />
            <InfiniteLoading v-if="nextPageUrl" @infinite="fetchMoreComments">
                <template #complete>
                    <p>ไม่มีความคิดเห็นเพิ่มเติม</p>
                </template>
                <template #error>
                    <p>เกิดข้อผิดพลาดในการโหลดความคิดเห็น</p>
                </template>
            </InfiniteLoading>
        </div>

        <div v-if="showCommentsModal && currentPage === 'Learn/Course/Lesson/Lessons'" class="flex flex-col w-full">
            <div class="flex fixed top-0 left-0 right-0 z-[50] p-2 w-full h-full bg-gray-800 justify-center py-16">
                <div class="w-full max-w-xl bg-white rounded-lg mx-2">
                    <div class="relative dark:bg-gray-700 rounded-lg flex flex-col max-h-[90vh]">
                        <div class="flex justify-between items-center">
                            <button type="button" @click="clearCommentsModal" class="absolute top-3 right-2.5 text-red-500  hover:bg-red-300 hover:text-red-600 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                                <Icon icon="heroicons:x-mark-20-solid" class="w-6 h-6 hover:scale-125" />
                                <span class="sr-only">Close modal</span>
                            </button>
                        </div>
                        <div class="my-14 p-2 overflow-y-auto border-t-2 border-blue-400">
                            <LessonCommentItem 
                                v-for="(comment, index) in lessonComments" 
                                :key="comment.id"
                                :comment="comment"
                                :lessonId="props.lesson.id"
                                @delete-lesson-comment="handleDeleteComment(index)"
                            />
                            <PostLoadingSkeleton v-if="isLoading" />
                            <InfiniteLoading v-if="nextPageUrl" @infinite="fetchMoreComments">
                                <template #complete>
                                    <p>ไม่มีความคิดเห็นเพิ่มเติม</p>
                                </template>
                                <template #error>
                                    <p>เกิดข้อผิดพลาดในการโหลดความคิดเห็น</p>
                                </template>
                            </InfiniteLoading>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
