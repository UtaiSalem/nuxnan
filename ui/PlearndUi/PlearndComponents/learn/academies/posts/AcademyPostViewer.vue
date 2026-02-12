<script setup>
import { ref, reactive, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

import AcademiesPagePostSettingMenuItem from '@/PlearndComponents/learn/academies/posts/AcademiesPagePostSettingMenuItem.vue';
import AcademyPostContentViewer from '@/PlearndComponents/learn/academies/posts/AcademyPostContentViewer.vue';
import AcademyPostImagesViewer from '@/PlearndComponents/learn/academies/posts/AcademyPostImagesViewer.vue';
import AcademyPostReactionViewer from '@/PlearndComponents/learn/academies/posts/AcademyPostReactionViewer.vue';

const props = defineProps({
    activity: Object,
});

const emit = defineEmits(['delete-post']);

const isPostCreation = computed(() => props.activity.action === "createpost");
const isPostOwner = computed(() => props.activity.action_by.id === usePage().props.auth.user.id);

const isDeleting = ref(false);

const postSettingMenus = reactive([
    {   
        name: 'ดูโพสต์', 
        icon: 'heroicons-outline:book-open', 
        action: 'view', 
        url: props.activity.target_resource.post_url, 
        isAuthor: isPostOwner, 
        color: 'text-blue-500', 
        showMenu: isPostOwner
    },
    { name: 'แก้ไขโพสต์', icon: 'bi:pencil-square', action: 'edit', url: props.activity.target_resource.post_url+'/edit', isAuthor: isPostOwner, color: 'yellow-400'},
    { name: 'ลบโพสต์', icon: 'fa-solid:trash-alt', action: 'delete', url: props.activity.target_resource.post_url, isAuthor: isPostOwner, color: 'red-500'},
    { name: 'แจ้งลบโพสต์', icon: 'bi:exclamation-triangle', action: 'report', url: props.activity.target_resource.post_url, isAuthor: isPostOwner, color: 'red-500'},
]);

const handleDeletePostRequest = () => {
    Swal.fire({
        title: 'คุณแน่ใจหรือไม่ที่จะลบโพสต์นี้และทุกอย่างที่เกี่ยวข้องกับโพสต์นี้?',
        text: "คุณจะไม่สามารถกู้คืนข้อมูลที่ลบไปแล้วได้!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'ใช่, ยืนยันการลบ!',
        cancelButtonText: 'ยกเลิก'
    }).then( async (result) => {
        if (result.isConfirmed) {
            isDeleting.value = true;
            let delResp = await axios.delete(`/posts/${props.activity.target_resource.id}`);
            if (delResp.data.success) {
                Swal.fire(
                    'ลบโพสต์สำเร็จ!',
                    'โพสต์นี้ของคุณและทุกอย่างที่เกี่ยวข้องถูกลบออกแล้ว',
                    'success'
                );
                emit('delete-post');
                isDeleting.value = false;
            }
        }
    });
}

</script>

<template>
    <div class="my-4">
        <div class="bg-white shadow-lg rounded-lg dark:bg-dark-card py-2 relative " v-if="activity">
            
            <div class="absolute flex w-full justify-end pr-4 pt-2" >
                <AcademiesPagePostSettingMenuItem 
                    :activity 
                    :postSettingMenus 

                    @delete-post="handleDeletePostRequest" 
                />
            </div>

            <div class="absolute top-[50%] left-[50%] z-10" v-if="isDeleting">
                <!-- spinner -->
                <div>
                    <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V4a10 10 0 00-10 10h2zm2 8a8 8 0 018-8h2a10 10 0 00-10-10v2zm8 2a8 8 0 01-8-8h-2a10 10 0 0010 10v-2zm-8-2a8 8 0 01-8 8V20a10 10 0 0010-10h-2z"></path>
                    </svg>
                </div>
            </div>

            <div v-if="!isPostCreation">
                <div  class="px-4 pt-4 flex justify-between">
                    <div class="flex justify-center gap-4">
                        <img :src="activity.action_by.avatar" class="w-12 h-12 p-[3px] rounded-full ring-1 ring-blue-600 dark:ring-gray-500" alt="">
                        <div>
                            <div class="flex items-center gap-4 mb-2">
                                <h6 class="">{{ activity.action_by.name }}</h6>
                                <small class="text-gray-800 mt-1 dark:text-secondary-600">
                                    {{ activity.action }}
                                </small>
                                <small class="text-gray-800 mt-1 dark:text-secondary-600">
                                    {{ activity.action_to }}
                                </small>
                            </div>
                            <!-- <p class="text-gray-500 text-xs">{{ activity.created_at }}</p> -->
                            <p class="text-gray-500 text-xs">{{ activity.diff_humans_created_at }}</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div :class="!isPostCreation ? 'm-2 border-[1.5px] border-gray-200 rounded-lg':''" class="px-4">

                <div class="text-gray-700 text-base rounded-lg">
                    <AcademyPostContentViewer 
                        :post="activity.target_resource" 
                        :action_to_id="activity.action_to_id"
                        :actorId="activity.action_by.id"
                        :acting="activity.action" 
                    />
                </div>
                
                <div>
                    <AcademyPostImagesViewer
                        :images="activity.target_resource.images" 
                        :model_id="activity.target_resource.id" 
                        :edit="activity.target_resource.author.id === $page.props.auth.user.id"
                    />
                </div>

                <div>
                    <AcademyPostReactionViewer 
                        :post="activity.target_resource" 
                        :model="activity.action_to.toLowerCase()"                
                    />
                </div>

            </div>
        </div>
    </div>
</template>

