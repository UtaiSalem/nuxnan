<script setup>
import { ref, reactive, computed } from 'vue';
import { useObjectUrl } from '@vueuse/core';
import { Icon } from '@iconify/vue';

const props = defineProps({
    lessonId: Number,
    lessonUrl: String,
});

const emit = defineEmits(['add-new-comment']);

const imageInput = ref(null);
const previewImages = reactive([]);
const isSubmitting = ref(false);

const commentForm = reactive({
    lessonId: props.lessonId,
    content: '',
    images: [],
});

const isFormValid = computed(() => commentForm.content.length > 0 || commentForm.images.length > 0);

const submitLessonComment = async () => {
    if (!isFormValid.value) return;

    try {
        isSubmitting.value = true;
        const formData = new FormData();
        formData.append('content', commentForm.content);
        commentForm.images.forEach(image => formData.append('images[]', image));

        const response = await axios.post(`/lessons/${props.lessonId}/comments`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        if (response.data.success) {
            emit('add-new-comment', response.data.comment);
            resetForm();
        }
    } catch (error) {
        console.error('Error submitting comment:', error);
    } finally {
        isSubmitting.value = false;
    }
};

const resetForm = () => {
    commentForm.content = '';
    commentForm.images = [];
    previewImages.splice(0);
};

const openImageSelector = () => imageInput.value.click();

const handleImageSelection = (event) => {
    Array.from(event.target.files).forEach(image => {
        previewImages.push({ file: image, url: useObjectUrl(image) });
        commentForm.images.push(image);
    });
};

const removePreviewImage = (index) => {
    previewImages.splice(index, 1);
    commentForm.images.splice(index, 1);
};

</script>

<template>
    <div class="mb-4">
        <form @submit.prevent="submitLessonComment" :id="`lesson-comment-form-${lessonId}`" class="relative dark:border-secondary-800 flex items-center">
            <img :src="$page.props.auth.user.profile_photo_url" class="absolute top-0 w-10 h-10 p-[2px] rounded-full ring-1 ring-blue-600 dark:ring-gray-500" alt="">
            <Textarea :id="`lesson-comment-input-${lessonId}`" 
                class="text-sm ml-12 pl-3 pr-12 w-full h-14 dark:text-gray-600 rounded-lg focus:ring-1 focus:ring-blue-500 border-gray-300"
                type="text" 
                rows="1" 
                autoResize 
                v-model="commentForm.content" 
                placeholder="แสดงความคิดเห็น..." 
            >
            </Textarea>
            <div class="flex gap-4 absolute top-[7px] right-[60px]">
                <input ref="imageInput" @change="handleImageSelection" type="file" multiple class="hidden" accept="image/*" />
                <button @click.prevent="openImageSelector">
                    <svg class="icon-20 text-violet-600" width="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21.9999 14.7024V16.0859C21.9999 16.3155 21.9899 16.5471 21.9699 16.7767C21.6893 19.9357 19.4949 22 16.3286 22H7.67126C6.06806 22 4.71535 21.4797 3.74341 20.5363C3.36265 20.1864 3.042 19.7753 2.7915 19.3041C3.12217 18.9021 3.49291 18.462 3.85363 18.0208C4.46485 17.289 5.05603 16.5661 5.42677 16.0959C5.97788 15.4142 7.43078 13.6196 9.44481 14.4617C9.85563 14.6322 10.2164 14.8728 10.547 15.0833C11.3586 15.6247 11.6993 15.7851 12.2705 15.4743C12.9017 15.1335 13.3125 14.4617 13.7434 13.76C13.9739 13.388 14.2043 13.0281 14.4548 12.6972C15.547 11.2736 17.2304 10.8926 18.6332 11.7348C19.3346 12.1559 19.9358 12.6872 20.4969 13.2276C20.6172 13.3479 20.7374 13.4592 20.8476 13.5695C20.9979 13.7198 21.4989 14.2211 21.9999 14.7024Z" fill="currentColor"></path>
                        <path opacity="0.4" d="M16.3387 2H7.67134C4.27455 2 2 4.37607 2 7.91411V16.086C2 17.3181 2.28056 18.4119 2.79158 19.3042C3.12224 18.9022 3.49299 18.4621 3.85371 18.0199C4.46493 17.2891 5.05611 16.5662 5.42685 16.096C5.97796 15.4143 7.43086 13.6197 9.44489 14.4618C9.85571 14.6323 10.2164 14.8729 10.5471 15.0834C11.3587 15.6248 11.6994 15.7852 12.2705 15.4734C12.9018 15.1336 13.3126 14.4618 13.7435 13.759C13.9739 13.3881 14.2044 13.0282 14.4549 12.6973C15.5471 11.2737 17.2305 10.8927 18.6333 11.7349C19.3347 12.1559 19.9359 12.6873 20.497 13.2277C20.6172 13.348 20.7375 13.4593 20.8477 13.5696C20.998 13.7189 21.499 14.2202 22 14.7025V7.91411C22 4.37607 19.7255 2 16.3387 2Z" fill="currentColor"></path>
                        <path d="M11.4543 8.79668C11.4543 10.2053 10.2809 11.3783 8.87313 11.3783C7.46632 11.3783 6.29297 10.2053 6.29297 8.79668C6.29297 7.38909 7.46632 6.21509 8.87313 6.21509C10.2809 6.21509 11.4543 7.38909 11.4543 8.79668Z" fill="currentColor"></path>
                    </svg>
                </button>
            </div>
            <button 
                type="submit" 
                :disabled="!isFormValid || isSubmitting" 
                name="comment-submit-button" 
                :id="`lesson-comment-submit-button-${lessonId}`" 
                :class="isFormValid ? 'hover:scale-110 text-blue-500 border-blue-500': 'cursor-not-allowed text-gray-500 border-gray-500'"
                class="ml-2 p-[6px] rounded-lg border">
                <Icon v-if="!isSubmitting"
                    icon="streamline:mail-send-reply-email-reply-message-actions-action-arrow" 
                    class="h-[23px] w-[23px] rotate-90"
                />
                <Icon v-else
                    icon="eos-icons:bubble-loading"
                    class="h-[23px] w-[23px]"
                />
            </button>
        </form>
        <div class="flex" v-if="previewImages.length">
            <div class="w-10 h-10 p-[2px]"></div>
            <div class="mt-2 ml-4 w-full columns-2 md:columns-3 lg:columns-4 ">
                <div v-for="(image, index) in previewImages" :key="image.url" class="">
                    <div class="relative mb-2 overflow-hidden p-0.5 border rounded-md ">
                        <img :src="image.url" class="rounded-lg" />
                        <button @click.prevent="removePreviewImage(index)"
                            class="absolute w-8 h-8 flex justify-center items-center top-1 right-1 rounded-full cursor-pointer bg-gray-100 p-2">
                            <Icon icon="fa-solid:trash-alt" class="w-4 h-4 text-red-500" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
