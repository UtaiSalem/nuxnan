<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import DotsDropdownMenu from '@/PlearndComponents/accessories/DotsDropdownMenu.vue';

const props = defineProps({
    lesson: Object,
});

const emit = defineEmits([
    'edit-model', 
    'delete-model',
    'link-to-lesson'
]);

function handleEditLesson() {
    emit('edit-model');
}

function onDeleteLesson() {
    emit('delete-model');
}

const linkToSingleLesson = (courseId, lessonId) => {
    isLoadingPage.value = true;
    router.visit(`/courses/${courseId}/lessons/${lessonId}`);
}
</script>

<template>
    <div class="my-4 grid bg-white w-full rounded-lg shadow-lg">
        <div class="flex flex-col overflow-hidden p-2">
            <div class="flex items-center relative border-b">
                <div class="flex  m-0.5">
                    <Link href="#">
                        <img :src="lesson.creater.avatar" class="w-12 h-12 p-[3px] rounded-full ring-1 ring-blue-600 dark:ring-gray-500" 
                    </Link>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900">
                            <a href="#" class="hover:underline">{{ lesson.creater.name }}</a>
                        </p>
                        <div class="flex space-x-1 text-sm text-gray-500">
                            <time datetime="2020-03-16">{{ lesson.created_at }}{{ ' @' + lesson.created_at_for_humans
                                }}</time>
                        </div>
                        <div class="flex space-x-1 text-sm text-indigo-500">
                            <span>{{ 'เวลาอ่าน ' + lesson.min_read + ' นาที' }}</span>
                        </div>

                    </div>
                </div>
                <div class="absolute top-1 right-0 " v-if="$page.props.isCourseAdmin">
                    <DotsDropdownMenu @edit-model="handleEditLesson" @delete-model="onDeleteLesson">
                        <template #editModel>
                            <div>
                                แก้ไขบทเรียน
                            </div>
                        </template>
                        <template #deleteModel>
                            <div>
                                ลบบทเรียน
                            </div>
                        </template>
                    </DotsDropdownMenu>
                </div>
            </div>


            <div class="p-3 mt-2 bg-gray-100 rounded-md">
                <button type="button" @click.prevent="emit('link-to-lesson')" class="underline font-bold text-lg">
                    {{ lesson.title }}
                </button>
            </div>
            <div class="p-3 mt-2 border-2 border-gray-200 rounded-md ">
                <p class="text-sm" v-if="lesson.description">
                    {{ lesson.description.length > 100 ? lesson.description.substring(0, 100) + '...' :
                        lesson.description }}
                </p>
            </div>
            <div class="p-3 mt-2 border-2 border-gray-200 rounded-md ">
                <p class="" v-if="lesson.description">
                    {{ lesson.content.length > 150 ? lesson.content.substring(0, 150) + '...' : lesson.description }}
                </p>
            </div>
            <div v-if="lesson.images.length > 0" class=" overflow-hidden mt-2">
                <button @click.prevent="emit('link-to-lesson')" class="relative">
                    <img :src="lesson.images[0].full_url" alt="" class="rounded-lg border" :class="{ ' brightness-50': lesson.images.length > 1 }">
                    <div v-if="lesson.images.length > 1" class="absolute top-[45%] left-[42%] text-white text-3xl w-24">+
                        {{ lesson.images.length - 1 }}
                    </div>
                </button>
            </div>
            <div class="flex justify-end">
                <button type="button" @click.prevent="emit('link-to-lesson')"
                    class="mr-4 underline">
                    ดูรายละเอียด>>
                </button>
            </div>

        </div>

        <div class="px-6 py-3 flex flex-row items-center justify-between bg-indigo-200">
            <span href="#" class="py-1 text-xs font-regular text-gray-900 mr-1 flex flex-row items-center">
                <svg height="13px" width="13px" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg"
                    xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512"
                    style="enable-background:new 0 0 512 512;" xml:space="preserve">
                    <g>
                        <g>
                            <path
                                d="M256,0C114.837,0,0,114.837,0,256s114.837,256,256,256s256-114.837,256-256S397.163,0,256,0z M277.333,256 c0,11.797-9.536,21.333-21.333,21.333h-85.333c-11.797,0-21.333-9.536-21.333-21.333s9.536-21.333,21.333-21.333h64v-128 c0-11.797,9.536-21.333,21.333-21.333s21.333,9.536,21.333,21.333V256z">
                            </path>
                        </g>
                    </g>
                </svg>
                <span class="ml-1">{{ lesson.point_tuition_fee > 0 ? 'ค่าอ่าน ' + lesson.point_tuition_fee + ' แต้ม' :
                        'ฟรี'
                    }}</span>
            </span>

            <span href="#" class="py-1 text-xs font-regular text-gray-900 mr-1 flex flex-row items-center">
                <svg class="h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z">
                    </path>
                </svg>
                <span class="ml-1">{{ lesson.view_count }} Views</span>
            </span>

            <span href="#" class="py-1 text-xs font-regular text-gray-900 mr-1 flex flex-row items-center">
                <svg class="h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z">
                    </path>
                </svg>
                <span class="ml-1">{{ lesson.like_count }} Likes</span>
            </span>
            <span href="#" class="py-1 text-xs font-regular text-gray-900 mr-1 flex flex-row items-center">
                <svg class="h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z">
                    </path>
                </svg>
                <span class="ml-1">{{ lesson.comment_count }} Comments</span>
            </span>
        </div>
    </div>
</template>
