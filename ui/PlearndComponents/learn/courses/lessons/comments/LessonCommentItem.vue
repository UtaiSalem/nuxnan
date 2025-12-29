<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';

import CommentSettingMenu from './CommentSettingMenu.vue';
import Textarea from 'primevue/textarea';

const props = defineProps({
    comment: Object,
    lessonId: Number,
});

const emit = defineEmits(['delete-lesson-comment']);

const refComment = ref(props.comment);
const authUser = usePage().props.auth.user;
const isLoading = ref(false);
const isDislikeLoading = ref(false);
const isDeleting = ref(false);
const isCommentOwner = computed(() => props.comment.user.id === authUser.id);

const handleLikesComment = async () => {
    if (isCommentOwner.value) return;
    if (refComment.value.isDislikedByAuth) return;
    if (isLoading.value) return;
    if (isDislikeLoading.value) return;
    if (isDeleting.value) return;
    if (authUser.pp < 24) {
        Swal.fire({
            title: 'คะแนนไม่เพียงพอ',
            text: "คุณต้องมีคะแนนมากกว่า 24 เพื่อให้สามารถกดไลค์ความคิดเห็นได้ กรุณาเก็บแต้มสะสมเพิ่มเติม",
            icon: 'danger',
        });
        return;
    };
    try {
        isLoading.value = true;
        let likeResp = await axios.post(`/lessons/${props.lessonId}/comments/${props.comment.id}/like`);
        if (likeResp.data.success) {
            refComment.value.isLikedByAuth = !refComment.value.isLikedByAuth;
            if (refComment.value.isLikedByAuth) {
                refComment.value.likes++;
                authUser.pp--;
                refComment.value.user.point++;
            } else {
                refComment.value.likes--;
            }
            isLoading.value = false;
        }
    } catch (error) {
        isLoading.value = false;
    }
};

const handleDislikesComment = async () => {
    if (isCommentOwner.value) return;
    if (refComment.value.isLikedByAuth) return;
    if (isLoading.value) return;
    if (isDislikeLoading.value) return;
    if (isDeleting.value) return;
    if (authUser.pp < 24) {
        Swal.fire({
            title: 'คะแนนไม่เพียงพอ',
            text: "คุณต้องมีคะแนนมากกว่า 24 เพื่อให้สามารถกดไม่ชอบความคิดเห็นได้ กรุณาเก็บแต้มสะสมเพิ่มเติม",
            icon: 'danger',
        });
        return;
    };
    try {
        isDislikeLoading.value = true;
        let dislikeResp = await axios.post(`/lessons/${props.lessonId}/comments/${props.comment.id}/dislike`);
        if (dislikeResp.data.success) {
            refComment.value.isDislikedByAuth = !refComment.value.isDislikedByAuth;
            if (refComment.value.isDislikedByAuth) {
                refComment.value.dislikes++;
                authUser.pp--;
            } else {
                refComment.value.dislikes--;
            }
            isDislikeLoading.value = false;
        }
    } catch (error) {
        isDislikeLoading.value = false;
    }
};

const handleDeleteComment = () => {
    try {
        isDeleting.value = true;
        Swal.fire({
            title: 'ลบความคิดเห็น',
            text: "คุณแน่ใจหรือไม่ที่จะลบความคิดเห็นนี้ออกจากบทเรียน?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#7c3aed',
            cancelButtonColor: '#f87171',
            confirmButtonText: 'ยืนยันการลบ'
        }).then(async (result) => {
            if (result.isConfirmed) {
                let delResp = await axios.delete(`/lessons/${props.comment.lesson_id}/comments/${props.comment.id}`);
                if (delResp.data.success) {
                    emit('delete-lesson-comment');
                    isDeleting.value = false;
                    Swal.fire(
                        'ลบความคิดเห็นสำเร็จ',
                        'ความคิดเห็นถูกลบออกแล้ว',
                        'success'
                    )
                }
            } else {
                isDeleting.value = false;
            }
        });
    } catch (error) {
        isDeleting.value = false;
    }
};
</script>

<template>
    <div class="flex gap-3 w-full justify-start border p-2 my-2 rounded-md">
        <img :src="comment.user.avatar" class="w-9 h-9 p-[2px] rounded-full ring-1 ring-blue-600 dark:ring-gray-500"
            alt="">
        <div class="w-full">
            <div class="flex justify-between items-end">
                <div class="flex items-center space-x-2">
                    <h6 class="mb-0">{{ comment.user.name }}</h6>
                    <span class="text-gray-600 text-sm">{{ comment.create_at }}</span>
                </div>
                <CommentSettingMenu @delete-comment="handleDeleteComment" v-if="isCommentOwner || $page.props.isCourseAdmin" />
            </div>

            <div v-if="comment.content" class="mt-2">
                <Textarea :id="`comment-form-comment-item-${comment.id}${comment.lesson_id}`" type="text"
                    v-model="comment.content" autoResize rows="1" readonly
                    class="text-sm bg-gray-200 w-full text-gray-700 rounded-lg focus:ring-0 border-gray-200"></Textarea>
            </div>

            <div v-if="comment.images.length > 0" class="flex flex-wrap gap-2">
                <div v-for="image in comment.images" :key="image.id" class="relative">
                    <img :src="image.url" class="w-24 h-24 object-cover rounded-lg border" alt="">
                </div>
            </div>

            <div class="flex items-center gap-4 text-xs">
                <button @click.prevent="handleLikesComment" :disabled="refComment.isDislikedByAuth || isCommentOwner"
                    class="flex items-center space-x-1  px-2 py-1 rounded-md text-green-500 disabled:text-gray-600">
                    <div class="flex justify-around items-center space-x-2"
                        :class="isCommentOwner || refComment.isDislikedByAuth ? '' : 'hover:scale-125'">
                        <span v-if="isLoading">
                            <Icon icon="eos-icons:bubble-loading" class="h-5 w-5" />
                        </span>
                        <div v-else>
                            <span v-if="isCommentOwner">
                                <Icon icon="icon-park-solid:like" class="w-5 h-5 " />
                            </span>
                            <span v-else-if="refComment.isLikedByAuth">
                                <Icon icon="icon-park-solid:like" class="w-5 h-5 " />
                            </span>
                            <span v-else-if="!refComment.isLikedByAuth">
                                <Icon icon="icon-park-outline:like" class="w-5 h-5" />
                            </span>
                        </div>
                    </div>
                    <span>{{ refComment.likes }}</span>
                </button>

                <button @click.prevent="handleDislikesComment" :disabled="refComment.isLikedByAuth || isCommentOwner"
                    class="flex text-center space-x-1 px-2 py-1 rounded-md text-red-500 disabled:text-gray-600">
                    <div class="flex justify-around items-center space-x-2 "
                        :class="[isCommentOwner || refComment.isLikedByAuth ? '' : 'hover:scale-125']">
                        <span v-if="isDislikeLoading">
                            <Icon icon="eos-icons:bubble-loading" class="h-5 w-5" />
                        </span>
                        <div v-else>
                            <span v-if="isCommentOwner">
                                <Icon icon="heroicons-solid:thumb-down" class="w-5 h-5 " />
                            </span>
                            <span v-else-if="refComment.isDislikedByAuth">
                                <Icon icon="heroicons-solid:thumb-down" class="w-5 h-5 " />
                            </span>
                            <span v-else-if="!refComment.isDislikedByAuth">
                                <Icon icon="heroicons-outline:thumb-down" class="w-5 h-5" />
                            </span>
                        </div>
                    </div>
                    <span>{{ refComment.dislikes }}</span>
                </button>
            </div>
        </div>
    </div>
</template>
