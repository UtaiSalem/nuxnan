<script setup>
import { ref,computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';

import ImageCommentSettingMenu from '../../comments/images/ImageCommentSettingMenu.vue';

const props = defineProps({
    courseId: Number,
    postId: Number,
    comment: Object,
    isPostOwner: Boolean
});

const emit = defineEmits(['delete-image-comment']);

const refComment = ref(props.comment);
const authUser = usePage().props.auth.user;
const isLoading = ref(false);
const isDeleting = ref(false);
const isNotCommentOwner = computed(()=> props.comment.user.id !== authUser.id);
const isCommentOwner = computed(()=> props.comment.user.id === authUser.id);

const handleLikesComment = async () => {

    try {
        isLoading.value = true;
        let likeResp = await axios.post(`/courses/posts/images/comments/${props.comment.id}/like`);
        if(likeResp.data.success){
            refComment.value.isLikedByAuth = !refComment.value.isLikedByAuth;
            if(refComment.value.isLikedByAuth){
                refComment.value.likes++;
                authUser.pp--;
            }else{
                refComment.value.likes--;   
            }    
            isLoading.value = false;
        }else{
            Swal.fire({
                title: 'เกิดข้อผิดพลาด',
                text: likeResp.data.message,
                icon: 'error',
                confirmButtonColor: '#7c3aed',
            });
        }
    } catch (error) {
        isLoading.value = false;
        console.log(error);
    }

};

const handleDislikesComment = async () => {
    try {
        isLoading.value = true;
        let dislikeResp = await axios.post(`/courses/posts/images/comments/${props.comment.id}/dislike`);
        if(dislikeResp.data.success){
            refComment.value.isDislikedByAuth = !refComment.value.isDislikedByAuth;
            if(refComment.value.isDislikedByAuth){
                refComment.value.dislikes++;
                authUser.pp--;
            }else{
                refComment.value.dislikes--;   
            }    
            isLoading.value = false;
        }else{
            Swal.fire({
                title: 'เกิดข้อผิดพลาด',
                text: dislikeResp.data.message,
                icon: 'error',
                confirmButtonColor: '#7c3aed',
            });
        }
    } catch (error) {
        isLoading.value = false;
        console.log(error);
    }
};

const handleDeleteImageComment = () => {
    try {
        isDeleting.value = true;
        Swal.fire({
            title: 'ลบความคิดเห็น',
            text: "คุณแน่ใจหรือไม่ที่จะลบความคิดเห็นนี้ออกจากโพสต์?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#7c3aed',
            cancelButtonColor: '#f87171',
            confirmButtonText: 'ยืนยันการลบ'
        }).then(async (result) => {
            if (result.isConfirmed) {
                let delResp = await axios.delete(`/post_image_comments/${props.comment.id}`);
                if (delResp.data.success) {
                    emit('delete-image-comment');
                    Swal.fire(
                        'ลบความคิดเห็นสำเร็จ',
                        'ความคิดเห็นถูกลบออกแล้ว',
                        'success'
                    )
                    isDeleting.value = false;
                }
            }
        })
    } catch (error) {
        isDeleting.value = false;
        console.log(error);
    }

};

</script>

<template>
    <div class="ml-[2px]">
        <div class="flex gap-4 w-full justify-start">
            <img :src="comment.user.avatar"
                class="w-9 h-9 p-[2px] rounded-full ring-1 ring-blue-600 dark:ring-gray-500" alt="">
            <div class="w-full">
                <div class="flex justify-between items-end">
                    <div class="flex items-center space-x-2">
                        <h6 class="mb-0">{{ comment.user.name }}</h6>
                        <span class="text-gray-600 text-sm">{{ comment.diff_humans_created_at }}</span>
                    </div>
                    <ImageCommentSettingMenu @delete-comment="handleDeleteImageComment" v-if="!isNotCommentOwner || isPostOwner" />
                </div>
                <p class="text-gray-700 text-sm bg-slate-50 border-[1.5px] border-gray-200 p-1 ml-auto rounded-lg my-2">
                    <span v-if="!isDeleting">
                        {{ comment.content }}
                    </span>
                    <span v-else class="w-full flex items-center justify-center">
                        <Icon icon="eos-icons:bubble-loading" class="w-5 h-5 text-blue-500" />
                    </span>
                </p>
                <div class="flex items-center gap-4 text-xs">
                    <button @click.prevent="handleLikesComment" :disabled="refComment.isDislikedByAuth || isCommentOwner"
                        class="flex items-center space-x-1  px-2 py-1 rounded-md text-green-500 disabled:text-gray-500" >
                        <div class="flex justify-around items-center space-x-2" :class="isCommentOwner || refComment.isDislikedByAuth ?'': 'hover:scale-125'">
                            <span v-if="refComment.isLikedByAuth" >
                                <Icon icon="icon-park-solid:like" class="w-5 h-5 " />
                            </span>
                            <span v-else>
                                <Icon icon="icon-park-outline:like" class="w-5 h-5"  />
                            </span>
                            <!-- <Icon v-else icon="heroicons-outline:thumb-up" :class="'w-6 h-6 ' + 'text' + reactionTheme"  /> -->
                        </div>
                        <span>{{ refComment.likes }}</span>
                    </button>
                    <button @click.prevent="handleDislikesComment" :disabled="refComment.isLikedByAuth || isCommentOwner"
                        class="flex space-x-1 px-2 py-1 rounded-md text-red-500 disabled:text-gray-500">
                        <Icon :icon="`heroicons-${ refComment.isDislikedByAuth ? 'solid':'outline'}:thumb-down`" class="w-5 h-5" :class="isCommentOwner || refComment.isDislikedByAuth ?'': 'hover:scale-125'" />
                        <span>{{ refComment.dislikes }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

