<script setup>
import { ref, computed } from 'vue';
import { Icon } from '@iconify/vue';
import { usePage } from '@inertiajs/vue3'

import LikeLessonReaction from '@/PlearndComponents/learn/courses/lessons/LessonReactions/LikeLessonReaction.vue';
import DislikeLessonReaction from '@/PlearndComponents/learn/courses/lessons/LessonReactions/DislikeLessonReaction.vue';

const props = defineProps({
    lesson: Object,
});

const refLesson = ref(props.lesson);
const canLikeOrDisLike = computed(() => props.lesson.creater.id !== usePage().props.auth.user.id);

const userLikeLessonHandler = () => {
    refLesson.value.like_count++;
    refLesson.value.is_liked_by_auth = true;
    usePage().props.auth.user.pp -= 12;
    refLesson.value.creater.point += 12;
};

const userUnlikeLessonHandler = () => {
    refLesson.value.like_count--;
    refLesson.value.is_liked_by_auth = false;
    usePage().props.auth.user.pp -= 12;
};

const userDislikeLessonHandler = () => {
    refLesson.value.dislike_count++;
    refLesson.value.is_disliked_by_auth = true;
    usePage().props.auth.user.pp -= 12;
};

const userUndislikeLessonHandler = () => {
    refLesson.value.dislike_count--;
    refLesson.value.is_disliked_by_auth = false;
    usePage().props.auth.user.pp -= 12;
};

</script>

<template>
    <div class="p-2">
        <div class="flex justify-around items-center mb-2">
            <div class="flex items-center text-xs font-regular text-gray-900">
                <Icon icon="tabler:eye-bitcoin" class="w-6 h-6 text-indigo-500" />
                <span class="ml-1 hidden sm:inline">{{ refLesson.point_tuition_fee > 0 ? 'ค่าอ่าน ' + refLesson.point_tuition_fee + ' แต้ม' : 'อ่านฟรี' }}</span>
                <span class="ml-1 sm:hidden">{{ refLesson.point_tuition_fee > 0 ? refLesson.point_tuition_fee : 'ฟรี' }}</span>
            </div>
            
            <div class="flex items-center">
                <Icon icon="heroicons:eye" class="w-5 h-5 text-violet-500" />
                <div class="ml-1 text-gray-400 text-sm">{{ refLesson.view_count }} <span class=" hidden sm:inline">{{ refLesson.view_count < 2 ? 'view' : 'views' }}</span></div>
            </div>

            <div class="flex items-center">
                <Icon icon="icon-park-solid:like" class="w-5 h-5 text-green-500"/>
                <div class="ml-1 text-gray-400 text-sm">{{ refLesson.like_count }} <span class=" hidden sm:inline">{{ refLesson.like_count < 2 ? 'like' : 'likes' }}</span></div>
            </div>

            <div class="flex items-center">
                <Icon icon="icon-park-outline:dislike" class="w-5 h-5 text-red-500" />
                <div class="ml-1 text-gray-400 text-sm">{{ refLesson.dislike_count }} <span class=" hidden sm:inline">{{ refLesson.dislike_count < 2 ? 'dislike' : 'dislikes' }}</span></div>
            </div>
            
            <div class="flex items-center">
                <Icon icon="ant-design:comment-outlined" class="w-5 h-5 text-blue-500" />
                <div class="ml-1 text-gray-400 text-sm">{{ refLesson.comment_count }} <span class=" hidden sm:inline">{{ refLesson.comment_count < 2 ? 'comment' : 'comments' }}</span></div>
            </div>

        </div>

        <hr class="text-gray-400 my-0.5" />

        <div v-if="canLikeOrDisLike" class="flex justify-around items-center" >
            <LikeLessonReaction  
                :lesson="lesson"
                :isLikedByAuth="refLesson.is_liked_by_auth"
                :isDislikedByAuth="refLesson.is_disliked_by_auth"
                @user-like-lesson="userLikeLessonHandler" 
                @user-unlike-lesson="userUnlikeLessonHandler"
            />

            <DislikeLessonReaction 
                :lesson="lesson"
                :isLikedByAuth="refLesson.is_liked_by_auth" 
                :isDislikedByAuth="refLesson.is_disliked_by_auth"  
                @user-dislike-lesson="userDislikeLessonHandler" 
                @user-undislike-lesson="userUndislikeLessonHandler"
            />
        </div>

        <hr v-if="canLikeOrDisLike" class="text-gray-400 mt-1">
    </div>
</template>

