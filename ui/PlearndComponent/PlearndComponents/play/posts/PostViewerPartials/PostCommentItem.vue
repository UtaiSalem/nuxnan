<script setup>
import { ref,computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';

import CommentSettingMenu from './../comments/CommentSettingMenu.vue';

const props = defineProps({
    comment: Object,
    isPostOwner: Boolean
});

const emit = defineEmits(['delete-post-comment']);

const refComment = ref(props.comment);
const authUser = usePage().props.auth.user;
const isLoading = ref(false);
const isDislikeLoading = ref(false);
const isDeleting = ref(false);
const isCommentOwner = computed(()=> props.comment.user.id === authUser.id);
const isNotCommentOwner = computed(()=> props.comment.user.id !== authUser.id); 

const handleLikesComment = async () => {
 
    try {
        isLoading.value = true;
        let likeResp = await axios.post(`/post_comments/${props.comment.id}/like`);
        if(likeResp.data.success){
            refComment.value.isLikedByAuth = !refComment.value.isLikedByAuth;
            if(refComment.value.isLikedByAuth){
                refComment.value.likes++;
                authUser.pp--;
                refComment.value.user.point++;
            }else{
                refComment.value.likes--;  
            }    
            isLoading.value = false;  
        }
    } catch (error) {
        console.log(error);
        isLoading.value = false;
    }
};

const handleDislikesComment = async () => {

    try {
        isDislikeLoading.value = true;
        let dislikeResp = await axios.post(`/post_comments/${props.comment.id}/dislike`);
        if(dislikeResp.data.success){
            refComment.value.isDislikedByAuth = !refComment.value.isDislikedByAuth;
            if(refComment.value.isDislikedByAuth){
                refComment.value.dislikes++;
                authUser.pp--;
            }else{
                refComment.value.dislikes--;   
            }    
            isDislikeLoading.value = false;  
        }
    } catch (error) {
        console.log(error);
        isDislikeLoading.value = false;
    }
};

const handleDeleteComment = () => {
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
                let delResp =  await axios.delete(`/posts/${props.comment.post_id}/comments/${props.comment.id}`);
                if(delResp.data.success){
                    emit('delete-post-comment');
                    isDeleting.value = false;
                    Swal.fire(
                        'ลบความคิดเห็นสำเร็จ',
                        'ความคิดเห็นถูกลบออกแล้ว'+ delResp.data.message,
                        'success'
                    )
                }

            } else {
                isDeleting.value = false;
            }
        });
    } catch (error) {
        console.log(error);
        isDeleting.value = false;
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
                        <span class="text-gray-600 text-sm">{{ comment.create_at }}</span>
                    </div>
                    <CommentSettingMenu @delete-comment="handleDeleteComment" v-if="!isNotCommentOwner || isPostOwner" />
                </div>
                <p v-if="comment.content" class="text-gray-700 text-sm bg-slate-50 border-[1.5px] border-gray-200 p-1 ml-auto rounded-lg my-2">
                    {{ comment.content }}
                </p>
                <div v-if="comment.images.length > 0" class="flex flex-wrap gap-2">
                    <div v-for="image in comment.images" :key="image.id" class="relative">
                        <img :src="image.url" class="w-24 h-24 object-cover rounded-lg border" alt="">
                    </div>
                </div>
                <div class="flex items-center gap-4 text-xs">
                    <button @click.prevent="handleLikesComment" :disabled="refComment.isDislikedByAuth || isCommentOwner"
                        class="flex items-center space-x-1  px-2 py-1 rounded-md text-green-500 disabled:text-gray-600">
                        <div class="flex justify-around items-center space-x-2" :class="isCommentOwner || refComment.isDislikedByAuth ?'': 'hover:scale-125'">
                            <span v-if="isLoading">
                                <Icon icon="eos-icons:bubble-loading" class="h-5 w-5" />
                            </span>
                            <div v-else>
                                <span v-if="isCommentOwner">
                                    <Icon icon="icon-park-solid:like" class="w-5 h-5 " />
                                </span>
                                <span v-else-if="refComment.isLikedByAuth" >
                                    <Icon icon="icon-park-solid:like" class="w-5 h-5 " />
                                </span>
                                <span v-else-if="!refComment.isLikedByAuth">
                                    <Icon icon="icon-park-outline:like" class="w-5 h-5"  />
                                </span>
                            </div>
                            <!-- <Icon v-else icon="heroicons-outline:thumb-up" :class="'w-6 h-6 ' + 'text' + reactionTheme"  /> -->
                        </div>
                        <span>{{ refComment.likes }}</span>
                    </button>

                    <button @click.prevent="handleDislikesComment" :disabled="refComment.isLikedByAuth || isCommentOwner"
                        class="flex text-center space-x-1 px-2 py-1 rounded-md text-red-500 disabled:text-gray-600">

                        <div class="flex justify-around items-center space-x-2 " :class="[isCommentOwner || refComment.isLikedByAuth ? '': 'hover:scale-125']">
                            <span v-if="isDislikeLoading">
                                <Icon icon="eos-icons:bubble-loading" class="h-5 w-5" />
                            </span>
                            <div v-else>
                                <span v-if="isCommentOwner">
                                    <Icon icon="heroicons-solid:thumb-down" class="w-5 h-5 " />
                                </span>
                                <span v-else-if="refComment.isDislikedByAuth" >
                                    <Icon icon="heroicons-solid:thumb-down" class="w-5 h-5 " />
                                </span>
                                <span v-else-if="!refComment.isDislikedByAuth">
                                    <Icon icon="heroicons-outline:thumb-down" class="w-5 h-5"  />
                                </span>
                            </div>
                            <!-- <Icon v-else icon="heroicons-outline:thumb-up" :class="'w-6 h-6 ' + 'text' + reactionTheme"  /> -->
                        </div>
                        <span>{{ refComment.dislikes }}</span>
                    </button>

                    <!-- <button 
                        class="text-primary-500 flex space-x-1 hover:bg-blue-300 hover:scale-110 px-2 py-1 rounded-md">
                        <Icon icon="heroicons-outline:reply" class="w-5 h-5 text-gray-600" />
                        <span class="">ตอบกลับ</span>
                    </button> -->
                </div>
            </div>
        </div>
    </div>
</template>
