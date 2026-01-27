<script setup>
import { reactive } from "vue";
import { Link } from "@inertiajs/vue3";
import { Icon } from '@iconify/vue';

import Textarea from "primevue/textarea";

const props = defineProps({
    post: Object,
    action_to_id: Number,
    actorId: Number,
    acting: String
});

// const showPostHeader = computed( () => props.acting !== 'createpost' && props.actorId !== props.post.author.id );
const privacyOptions = reactive([
        { id: 1, label: 'ส่วนตัว', icon: 'bx:user-pin'},
        { id: 2, label: 'เพื่อน' , icon: 'oui:users'},
        { id: 3, label: 'สาธารณะ', icon: 'uiw:global'},
    ]);

</script>

<style lang="scss" scoped>

</style>

<template>
    <div>
        <div class="flex flex-wrap w-full gap-4 border-b">
            <div class="flex items-center gap-2 min-w-fit mb-2">
                <img :src="post.author.avatar" class="w-12 h-12 p-[3px] rounded-full ring-1 ring-blue-600 dark:ring-gray-500" alt="">
                <h6 class="">{{ post.author.name }}</h6>
                <div class="flex justify-between">
                    <div class="flex flex-wrap items-center gap-1">
                        <small class="text-gray-800 dark:text-secondary-600">
                            {{ props.acting == 'createpost' ? 'เขียนโพสต์' : props.acting }}
                        </small>
                        <!-- <small class="text-gray-800 dark:text-secondary-600">
                            ใน {{ props.post.academy.name }}
                        </small> -->
                        <small class="text-gray-800 dark:text-secondary-600">
                            เมื่อ
                        </small>
                        <small class="text-gray-800 dark:text-secondary-600">
                            {{ post.diff_humans_created_at }}
                        </small>
                        <span class="mx-2"><Icon :icon="privacyOptions[post.privacy_settings-1].icon" class="w-5 h-5"/></span>
                        <!-- <span class="mx-2"><Icon :icon="props.privacy_settings" class="w-5 h-5"/></span> -->
                        <!-- <span class="mx-2">{{ post.privacy_settings }}</span> -->
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-gray-100 mt-2 rounded-lg" v-if="post.content">

            <Link v-if="$page.component==='Learn/Course/CourseFeeds'" :href="post.post_url">
                <div class="line-clamp-6">
                    <!-- {{ post.content }} -->
                    <Textarea 
                        :id="`course-post-content-${post.id}`" 
                        name="postcontent" 
                        v-model="post.content" 
                        
                        autoResize
                        readonly
                        class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        :class="{ 'cursor-pointer': $page.component==='Learn/Course/CourseFeeds' }"
                    ></textarea>
                </div>
                <div v-if="post.content.length > 500" class="flex justify-end mt-2">
                    <Link :href="post.post_url" class="text-blue-500 dark:text-blue-400">อ่านต่อ...</Link>
                </div>
            </Link>

            <div v-else class="bg-gray-100 mt-2 rounded-lg">
                <Textarea 
                    :id="`course-post-content-${post.id}`" 
                    name="postcontent" 
                    v-model="post.content" 
                    
                    autoResize
                    readonly
                    class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                ></textarea>
            </div>

        </div>
    </div>
</template>

