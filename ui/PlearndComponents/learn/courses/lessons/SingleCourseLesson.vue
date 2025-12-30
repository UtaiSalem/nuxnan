<script setup>
import { ref, onMounted } from 'vue';
import { Icon } from '@iconify/vue';
import { Link } from '@inertiajs/vue3';

import Swal from 'sweetalert2';
import DotsDropdownMenu from '@/PlearndComponents/accessories/DotsDropdownMenu.vue';
import LoadingPage from '@/PlearndComponents/accessories/LoadingPage.vue';
import RichTextViewer from '~/components/RichTextViewer.vue';

import LessonReactionViewer from '@/PlearndComponents/learn/courses/lessons/LessonReactions/LessonReactionViewer.vue';
import LessonCommentsViewer from '@/PlearndComponents/learn/courses/lessons/comments/LessonCommentsViewer.vue';
import TopicItem from '@/PlearndComponents/learn/courses/lessons/topics/TopicItem.vue';
import QuestionItem from '@/PlearndComponents/learn/courses/questions/QuestionItem.vue';
import ShareModal from '~/components/share/ShareModal.vue';


const props = defineProps({
    lesson: Object,
    isCourseAdmin: Boolean,
});

const isLoadingPage = ref(false);
const isDeletingImage = ref(false);
const refLesson = ref(props.lesson);

// Topics and Questions state
const questions = ref([]);
const isLoadingQuestions = ref(false);

// Bookmark state
const isBookmarked = ref(props.lesson.is_bookmarked_by_auth || false);
const bookmarksCount = ref(props.lesson.bookmarks_count || 0);
const isBookmarking = ref(false);

// Share state
const showShareModal = ref(false);


function handleBackLink() {
    isLoadingPage.value = true;
    // usePage().$inertia.visit('/courses/'+usePage().props.course.data.id);
    window.history.back();
    // route('course.feeds', 1);
}

const handleEditLesson = () => {
    isLoadingPage.value = true;
    window.location.href = '/courses/' + props.lesson.course.id + '/lessons/' + props.lesson.id + '/edit';
}

function onDeleteLesson() {
    Swal.fire({
        title: 'ลบบทเรียน',
        text: "ยืนยันการลบบทเรียนอย่างถาวร",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#7c3aed',
        cancelButtonColor: '#f87171',
        confirmButtonText: 'ยืนยันการลบ'
    }).then(async (result) => {
        if (result.isConfirmed) {
            let delResp = await axios.delete(`/courses/${props.lesson.course.id}/lessons/${props.lesson.id}`);
            if (delResp.status === 200) {
                // props.lessons.splice(index, 1);
                // usePage().$inertia.visit('/courses/'+props.lesson.course.id+'/lessons');
                // window.history.back();
                window.location.href = '/courses/' + props.lesson.course.id + '/lessons';
            }
        }
    })
}

async function deleteImage(index, imgId) {
    Swal.fire({
        title: 'ลบรูปภาพของบทเรียน',
        text: "ยืนยันการลบรูปภาพของบทเรียนอย่างถาวร",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#7c3aed',
        cancelButtonColor: '#f87171',
        confirmButtonText: 'ยืนยันการลบ'
    }).then(async (result) => {
        if (result.isConfirmed) {
            isDeletingImage.value = true;
            let delResp = await axios.delete(`/lessons/${props.lesson.id}/images/${imgId}`);
            if (delResp.status === 200) {
                props.lesson.images.splice(index, 1);
            }
            isDeletingImage.value = false;
        }
    })
}

// Fetch questions for this lesson
async function fetchQuestions() {
    try {
        isLoadingQuestions.value = true;
        const response = await axios.get(`/lessons/${props.lesson.id}/questions`);
        questions.value = response.data.questions || response.data || [];
    } catch (error) {
        console.error('Failed to fetch questions:', error);
        questions.value = [];
    } finally {
        isLoadingQuestions.value = false;
    }
}

// Handle topic edit
function handleEditTopic(topic) {
    console.log('Edit topic:', topic);
    // TODO: Implement topic editing
}

// Handle topic delete
function handleDeleteTopic(topicId) {
    console.log('Delete topic:', topicId);
    // TODO: Implement topic deletion
}

// Toggle bookmark
async function toggleBookmark() {
    if (isBookmarking.value) return;

    isBookmarking.value = true;
    const wasBookmarked = isBookmarked.value;

    // Optimistic update
    isBookmarked.value = !wasBookmarked;
    bookmarksCount.value += wasBookmarked ? -1 : 1;

    try {
        const response = await axios.post(`/courses/${props.lesson.course.id}/lessons/${props.lesson.id}/bookmark`);

        if (!response.data.success) {
            // Revert
            isBookmarked.value = wasBookmarked;
            bookmarksCount.value += wasBookmarked ? 1 : -1;
            console.error('Failed to toggle bookmark');
        }
    } catch (error) {
        // Revert
        isBookmarked.value = wasBookmarked;
        bookmarksCount.value += wasBookmarked ? 1 : -1;
        console.error('Failed to toggle bookmark:', error);
    } finally {
        isBookmarking.value = false;
    }
}

// Handle share
const handleShare = () => {
    showShareModal.value = true;
}

const handleShareSuccess = () => {
    showShareModal.value = false;
}

// Initialize
onMounted(() => {
    fetchQuestions();
});

</script>
<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <LoadingPage v-if="isLoadingPage" />

        <!-- Breadcrumb Navigation -->
        <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <nav class="flex items-center space-x-2 text-sm">
                    <button @click="handleBackLink" class="flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                        <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
                        <span class="font-medium">กลับ</span>
                    </button>
                    <Icon icon="fluent:chevron-right-24-regular" class="w-4 h-4 text-gray-400" />
                    <span class="text-gray-900 dark:text-white font-medium">{{ lesson.title }}</span>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
                <!-- Lesson Header -->
                <div class="relative">
                    <!-- Cover Image or Gradient -->
                    <div class="h-64 bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 relative">
                        <img v-if="lesson.images && lesson.images[0]" 
                             :src="lesson.images[0].full_url" 
                             class="w-full h-full object-cover"
                             alt="Cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                    </div>

                    <!-- Admin Menu -->
                    <div class="absolute top-4 right-4" v-if="isCourseAdmin">
                        <DotsDropdownMenu @delete-model="onDeleteLesson" @edit-model="handleEditLesson">
                            <template #editModel>
                                <div class="flex items-center gap-2">
                                    <Icon icon="fluent:edit-24-regular" class="w-4 h-4" />
                                    แก้ไข
                                </div>
                            </template>
                            <template #deleteModel>
                                <div class="flex items-center gap-2">
                                    <Icon icon="fluent:delete-24-regular" class="w-4 h-4" />
                                    ลบบทเรียน
                                </div>
                            </template>
                        </DotsDropdownMenu>
                    </div>
                </div>

                <!-- Lesson Info -->
                <div class="p-8">
                    <!-- Title -->
                    <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-6">
                        {{ lesson.title }}
                    </h1>

                    <!-- Meta Info -->
                    <div class="flex items-center gap-6 mb-8 pb-6 border-b border-gray-200 dark:border-gray-700">
                        <!-- Creator -->
                        <div class="flex items-center gap-3">
                            <img :src="lesson.creater.avatar"
                                class="w-12 h-12 rounded-full ring-2 ring-blue-500" />
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ lesson.creater.name }}
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ lesson.created_at_for_humans }}
                                </p>
                            </div>
                        </div>

                        <!-- Stats -->
                        <div class="flex items-center gap-6 text-sm text-gray-600 dark:text-gray-400">
                            <div class="flex items-center gap-2">
                                <Icon icon="fluent:clock-24-regular" class="w-5 h-5" />
                                <span>{{ lesson.min_read }} นาที</span>
                            </div>
                            <div v-if="lesson.view_count" class="flex items-center gap-2">
                                <Icon icon="fluent:eye-24-regular" class="w-5 h-5" />
                                <span>{{ lesson.view_count }} ครั้ง</span>
                            </div>
                            <div v-if="lesson.point_tuition_fee > 0" class="flex items-center gap-2">
                                <Icon icon="fluent:diamond-24-filled" class="w-5 h-5 text-amber-500" />
                                <span>{{ lesson.point_tuition_fee }} พอยต์</span>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div v-if="lesson.description" class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">คำอธิบาย</h3>
                        <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-700">
                            <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                                {{ lesson.description }}
                            </p>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">เนื้อหาบทเรียน</h3>
                        <RichTextViewer :content="lesson.content" class="bg-gray-50 dark:bg-gray-900/50 p-6 rounded-xl border border-gray-200 dark:border-gray-700" />
                    </div>

                <div v-if="lesson.images.length > 0" class="mt-4">
                    <p>รูปภาพ</p>
                </div>

                <div v-if="lesson.images.length > 0" class="mt-2 columns-1">
                    <div v-for="(image, index) in lesson.images" :key="image.id" class="">
                        <div class="relative mb-2 overflow-hidden max-h-fit">
                            <img :src="image.full_url" class="rounded-lg border-2" />
                            <button @click.prevent="deleteImage(index, image.id)" v-if="isCourseAdmin" title="ลบรูปภาพ"
                                class="absolute top-1 right-1 w-8 h-8 flex items-center justify-center rounded-full cursor-pointer bg-gray-100 p-[6px]">
                                <Icon icon="fa-solid:trash-alt" class="w-4 h-4 text-red-500" />
                            </button>
                            <div v-if="isDeletingImage"
                                class=" absolute m-auto left-0 right-0 top-0 bottom-0 w-full h-full z-10 bg-gray-600/75 rounded-lg flex items-center justify-center">
                                <Icon icon="svg-spinners:ring-resize" class=" w-20 h-20 text-white " />
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="lesson.youtube_url" class="mt-4">
                    <p>วิดีโอ</p>
                </div>
                <div v-if="lesson.youtube_url" class=" border-2 border-gray-200 rounded-lg ">
                    <vue-plyr>
                        <div data-plyr-provider="youtube" :data-plyr-embed-id="lesson.youtube_url"></div>
                    </vue-plyr>
                </div>

                <!-- Topics Section -->
                <div v-if="lesson.topics && lesson.topics.length > 0" class="mt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">หัวข้อย่อย</h3>
                    <div class="space-y-2">
                        <TopicItem
                            v-for="topic in lesson.topics"
                            :key="topic.id"
                            :topic="topic"
                            @edit-topic="handleEditTopic"
                            @delete-topic="handleDeleteTopic"
                        />
                    </div>
                </div>

                <!-- Questions/Exercises Section -->
                <div v-if="questions.length > 0" class="mt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">แบบฝึกหัด</h3>
                    <div class="space-y-4">
                        <QuestionItem
                            v-for="(question, index) in questions"
                            :key="question.id"
                            :question="question"
                            :index-number="index"
                            :is-course-owner="isCourseAdmin"
                            :question-api-route="`/lessons/${lesson.id}/questions`"
                            :quiz-id="null"
                            :start-quiz-at="new Date()"
                        />
                    </div>
                </div>

                <div v-else-if="isLoadingQuestions" class="mt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">แบบฝึกหัด</h3>
                    <div class="text-center py-4">
                        <Icon icon="fluent:spinner-ios-20-regular" class="w-6 h-6 animate-spin text-gray-400" />
                        <p class="text-sm text-gray-500 mt-2">กำลังโหลดแบบฝึกหัด...</p>
                    </div>
                </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <hr class="text-gray-400 mt-3" />

            <LessonReactionViewer :lesson="refLesson" />

            <hr class="text-gray-400 mt-3" />

            <!-- Actions Section -->
            <div class="flex items-center justify-center gap-4 mt-4 px-4">
                <!-- Bookmark Button -->
                <button
                    @click="toggleBookmark"
                    :disabled="isBookmarking"
                    :class="[
                        'flex items-center gap-2 px-4 py-2 rounded-lg transition-colors font-medium',
                        isBookmarked
                            ? 'bg-yellow-500 text-white hover:bg-yellow-600'
                            : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                    ]"
                >
                    <Icon
                        :icon="isBookmarked ? 'fluent:bookmark-24-filled' : 'fluent:bookmark-24-regular'"
                        class="w-4 h-4"
                    />
                    <span class="text-sm">{{ isBookmarked ? 'บุ๊กมาร์กแล้ว' : 'บุ๊กมาร์ก' }}</span>
                </button>

                <!-- Share Button -->
                <button
                    @click="handleShare"
                    class="flex items-center gap-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors font-medium"
                >
                    <Icon icon="fluent:share-24-regular" class="w-4 h-4" />
                    <span class="text-sm">แชร์</span>
                </button>
            </div>

            <hr class="text-gray-400 mt-3" />

            <LessonCommentsViewer
                :lesson="lesson"

                @new-comment-added="() => refLesson.comment_count++"
            />

        </div>

        <!-- Share Modal -->
        <ShareModal
            v-if="showShareModal"
            :show="showShareModal"
            :shareable-type="'Lesson'"
            :shareable-id="lesson.id"
            :post="lesson"
            @close="showShareModal = false"
            @shared="handleShareSuccess"
        />
    </div>
</template>
<style scoped>
textarea {
    white-space: pre-wrap;
    word-wrap: break-word;
    overflow-wrap: break-word;
}
</style>
