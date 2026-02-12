<template>
    <div class="my-4">
        <div class="bg-white shadow-lg rounded-lg  dark:bg-dark-card" v-if="activity">
            <div class="">
                <div class="px-4 pt-4 flex justify-between">
                    <div>
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
                                <p class="text-gray-500 text-xs">{{ activity.created_at }}</p>
                            </div>
                        </div>
                    </div>
                    <div x-data="dropdown" class="inline-block">
                        <a class="inline-block px-4 py-1 text-center text-gray-400  cursor-pointer" type="button" @click="toggle">
                            <svg width="18" class="inline-block w-5 h-5"  aria-expanded="false"
                                            role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentcolor">
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M10 20.6788C10 21.9595 11.0378 23 12.3113 23C13.5868 23 14.6265 21.9595 14.6265 20.6788C14.6265 19.3981 13.5868 18.3576 12.3113 18.3576C11.0378 18.3576 10 19.3981 10 20.6788ZM10 12.0005C10 13.2812 11.0378 14.3217 12.3113 14.3217C13.5868 14.3217 14.6265 13.2812 14.6265 12.0005C14.6265 10.7198 13.5868 9.67929 12.3113 9.67929C11.0378 9.67929 10 10.7198 10 12.0005ZM12.3113 5.64239C11.0378 5.64239 10 4.60192 10 3.3212C10 2.04047 11.0378 1 12.3113 1C13.5868 1 14.6265 2.04047 14.6265 3.3212C14.6265 4.60192 13.5868 5.64239 12.3113 5.64239Z"
                                                fill="#8A92A6" />
                                        </svg>
                        </a>
                        <ul x-show.transition="open"  x-bind="dropdownTransition" class="z-10 py-2  absolute text-left text-secondary-500 bg-white  rounded min-w-36 dark:bg-dark-bg "   style="display: none;">
                            <li><a class=" block text-left pr-[1.5rem] pl-[1rem] py-1 ml-[0.5rem] mr-[0.5rem] rtl:text-right rtl:pl-[1.5rem] rtl:pr-4 whitespace-nowrap hover:text-white hover:bg-primary-500 hover:rounded" href="#">
                            <svg class="icon-18" width="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.4" d="M19.9927 18.9534H14.2984C13.7429 18.9534 13.291 19.4124 13.291 19.9767C13.291 20.5422 13.7429 21.0001 14.2984 21.0001H19.9927C20.5483 21.0001 21.0001 20.5422 21.0001 19.9767C21.0001 19.4124 20.5483 18.9534 19.9927 18.9534Z" fill="currentColor"></path>
                                    <path d="M10.309 6.90385L15.7049 11.2639C15.835 11.3682 15.8573 11.5596 15.7557 11.6929L9.35874 20.0282C8.95662 20.5431 8.36402 20.8344 7.72908 20.8452L4.23696 20.8882C4.05071 20.8903 3.88775 20.7613 3.84542 20.5764L3.05175 17.1258C2.91419 16.4915 3.05175 15.8358 3.45388 15.3306L9.88256 6.95545C9.98627 6.82108 10.1778 6.79743 10.309 6.90385Z" fill="currentColor"></path>
                                    <path opacity="0.4" d="M18.1208 8.66544L17.0806 9.96401C16.9758 10.0962 16.7874 10.1177 16.6573 10.0124C15.3927 8.98901 12.1545 6.36285 11.2561 5.63509C11.1249 5.52759 11.1069 5.33625 11.2127 5.20295L12.2159 3.95706C13.126 2.78534 14.7133 2.67784 15.9938 3.69906L17.4647 4.87078C18.0679 5.34377 18.47 5.96726 18.6076 6.62299C18.7663 7.3443 18.597 8.0527 18.1208 8.66544Z" fill="currentColor"></path>
                                </svg>
                            <span class="ml-2">Edit Post</span>
                            </a></li>
                            <li><a class="block text-left pr-[1.5rem] pl-[1rem] py-1 ml-[0.5rem] mr-[0.5rem] rtl:text-right rtl:pl-[1.5rem] rtl:pr-4 whitespace-nowrap hover:text-white hover:bg-primary-500 hover:rounded" href="#">
                                <svg class="icon-18" width="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M7.7688 8.71387H16.2312C18.5886 8.71387 20.5 10.5831 20.5 12.8885V17.8254C20.5 20.1308 18.5886 22 16.2312 22H7.7688C5.41136 22 3.5 20.1308 3.5 17.8254V12.8885C3.5 10.5831 5.41136 8.71387 7.7688 8.71387ZM11.9949 17.3295C12.4928 17.3295 12.8891 16.9419 12.8891 16.455V14.2489C12.8891 13.772 12.4928 13.3844 11.9949 13.3844C11.5072 13.3844 11.1109 13.772 11.1109 14.2489V16.455C11.1109 16.9419 11.5072 17.3295 11.9949 17.3295Z" fill="currentColor"></path>
                                    <path opacity="0.4" d="M17.523 7.39595V8.86667C17.1673 8.7673 16.7913 8.71761 16.4052 8.71761H15.7447V7.39595C15.7447 5.37868 14.0681 3.73903 12.0053 3.73903C9.94257 3.73903 8.26594 5.36874 8.25578 7.37608V8.71761H7.60545C7.20916 8.71761 6.83319 8.7673 6.47754 8.87661V7.39595C6.4877 4.41476 8.95692 2 11.985 2C15.0537 2 17.523 4.41476 17.523 7.39595Z" fill="currentColor"></path>
                                </svg>
                                <span class="ml-2">Security</span>
                            </a></li>
                            <li><a class="block text-left pr-[1.5rem] pl-[1rem] py-1 ml-[0.5rem] mr-[0.5rem] rtl:text-right rtl:pl-[1.5rem] rtl:pr-4 whitespace-nowrap hover:text-white hover:bg-primary-500 hover:rounded" href="#">
                                <svg class="icon-18" width="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.4" d="M22 12C22 17.524 17.523 22 12 22C6.477 22 2 17.524 2 12C2 6.478 6.477 2 12 2C17.523 2 22 6.478 22 12Z" fill="currentColor"></path>
                                    <path d="M15.5739 15.8145C15.4429 15.8145 15.3109 15.7805 15.1899 15.7095L11.2639 13.3675C11.0379 13.2315 10.8989 12.9865 10.8989 12.7225V7.67554C10.8989 7.26154 11.2349 6.92554 11.6489 6.92554C12.0629 6.92554 12.3989 7.26154 12.3989 7.67554V12.2965L15.9589 14.4195C16.3139 14.6325 16.4309 15.0925 16.2189 15.4485C16.0779 15.6835 15.8289 15.8145 15.5739 15.8145Z" fill="currentColor"></path>
                                </svg>
                                <span class="ml-2">Timer</span>
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="p-4">
                <p class="text-gray-700 text-base bg-slate-100 border-[1.5px] border-gray-200 p-2 rounded-lg mb-4" >
                    {{ activity.target_resource.title }}
                </p>

                <!-- <div class="grid grid-cols-2 gap-3">
                    <a href="#" class="col-span-2">
                        <img src="storage/assets/images/newsfeed/03.png" class="rounded-lg w-full" alt="">
                    </a>
                    <a href="#">
                        <img src="storage/assets/images/newsfeed/08.png" class="rounded-lg w-full" alt="">
                    </a>
                    <a href="#">
                        <img src="storage/assets/images/newsfeed/09.png" class="rounded-lg w-full" alt="">
                    </a>
                    <a href="#">
                        <img src="storage/assets/images/newsfeed/10.png" class="rounded-lg w-full" alt="">
                    </a>
                    <a href="#">
                        <img src="storage/assets/images/newsfeed/11.png" class="rounded-lg w-full" alt="">
                    </a>

                    <img src="/storage/assets/images/app/01.png" class="rounded-lg" alt="">
                </div> -->
                <div class="pt-3">
                    <div class="flex w-full justify-between items-start lg:items-center flex-col lg:flex-row">
                        <div class="flex w-full gap-3 items-center justify-between">
                            <div class="">
                                <AvatarGroupLikesWindUI />
                            </div>
                    
                            <div class="">
                                <a type="button" class="flex gap-2 items-center cursor-pointer" @click="toggle">
                                    <svg class="icon-20 text-gray-400" width="20" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path opacity="0.4"
                                            d="M12.02 2C6.21 2 2 6.74 2 12C2 13.68 2.49 15.41 3.35 16.99C3.51 17.25 3.53 17.58 3.42 17.89L2.75 20.13C2.6 20.67 3.06 21.07 3.57 20.91L5.59 20.31C6.14 20.13 6.57 20.36 7.081 20.67C8.541 21.53 10.36 21.97 12 21.97C16.96 21.97 22 18.14 22 11.97C22 6.65 17.7 2 12.02 2Z"
                                            fill="currentColor"></path>
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M11.9807 13.2901C11.2707 13.2801 10.7007 12.7101 10.7007 12.0001C10.7007 11.3001 11.2807 10.7201 11.9807 10.7301C12.6907 10.7301 13.2607 11.3001 13.2607 12.0101C13.2607 12.7101 12.6907 13.2901 11.9807 13.2901ZM7.37033 13.2901C6.67033 13.2901 6.09033 12.7101 6.09033 12.0101C6.09033 11.3001 6.66033 10.7301 7.37033 10.7301C8.08033 10.7301 8.65033 11.3001 8.65033 12.0101C8.65033 12.7101 8.08033 13.2801 7.37033 13.2901ZM15.3105 12.0101C15.3105 12.7101 15.8805 13.2901 16.5905 13.2901C17.3005 13.2901 17.8705 12.7101 17.8705 12.0101C17.8705 11.3001 17.3005 10.7301 16.5905 10.7301C15.8805 10.7301 15.3105 11.3001 15.3105 12.0101Z"
                                            fill="currentColor"></path>
                                    </svg>
                                    <span class="text-gray-400 text-sm">02 Comments</span>
                                </a>
                                <ul x-show.transition="open" x-bind="dropdownTransition"
                                    class="z-10 py-2 absolute right-130 text-left text-secondary-500 bg-white rounded min-w-36 shadow-dropdown"
                                     style="display: none;">
                                    <li><a class="block w-full px-4 py-1 text-gray-500 bg-transparent whitespace-nowrap hover:text-primary-500 focus:text-white focus:bg-primary-500"
                                            href="#">Max Emum</a></li>
                                    <li><a class="block w-full px-4 py-1 text-gray-500 bg-transparent whitespace-nowrap hover:text-primary-500 focus:text-white focus:bg-primary-500"
                                            href="#">Bill Yerds</a></li>
                                    <li><a class="block w-full px-4 py-1 text-gray-500 bg-transparent whitespace-nowrap hover:text-primary-500 focus:text-white focus:bg-primary-500"
                                            href="#">Hap E. Birthday</a></li>
                                    <li><a class="block w-full px-4 py-1 text-gray-500 bg-transparent whitespace-nowrap hover:text-primary-500 focus:text-white focus:bg-primary-500"
                                            href="#">Tara Misu</a></li>
                                    <li><a class="block w-full px-4 py-1 text-gray-500 bg-transparent whitespace-nowrap hover:text-primary-500 focus:text-white focus:bg-primary-500"
                                            href="#">Midge Itz</a></li>
                                    <li><a class="block w-full px-4 py-1 text-gray-500 bg-transparent whitespace-nowrap hover:text-primary-500 focus:text-white focus:bg-primary-500"
                                            href="#">Sal Vidge</a></li>
                                    <li><a class="block w-full px-4 py-1 text-gray-500 bg-transparent whitespace-nowrap hover:text-primary-500 focus:text-white focus:bg-primary-500"
                                            href="#">Other</a></li>
                                </ul>
                            </div>
                            
                            <div>
                                <a href="#" class="flex gap-2 items-center">
                                    <svg class="icon-20 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="20" viewBox="0 0 24 24"
                                        fill="none">
                                        <path
                                            d="M5.50052 15C6.37518 14.9974 7.21675 14.6653 7.85752 14.07L14.1175 17.647C13.9078 18.4666 14.0002 19.3343 14.378 20.0913C14.7557 20.8483 15.3935 21.4439 16.1745 21.7692C16.9555 22.0944 17.8275 22.1274 18.6309 21.8623C19.4343 21.5971 20.1153 21.0515 20.5493 20.3252C20.9832 19.599 21.1411 18.7408 20.994 17.9076C20.8469 17.0745 20.4047 16.3222 19.7483 15.7885C19.0918 15.2548 18.2652 14.9753 17.4195 15.0013C16.5739 15.0273 15.7659 15.357 15.1435 15.93L8.88352 12.353C8.94952 12.103 8.98552 11.844 8.99152 11.585L15.1415 8.06996C15.7337 8.60874 16.4932 8.92747 17.2925 8.97268C18.0918 9.01789 18.8823 8.78684 19.5315 8.31828C20.1806 7.84972 20.6489 7.17217 20.8577 6.39929C21.0666 5.6264 21.0032 4.80522 20.6784 4.0735C20.3535 3.34178 19.7869 2.74404 19.0735 2.38056C18.3602 2.01708 17.5436 1.90998 16.7607 2.07723C15.9777 2.24447 15.2761 2.67588 14.7736 3.29909C14.271 3.92229 13.9981 4.69937 14.0005 5.49996C14.0045 5.78796 14.0435 6.07496 14.1175 6.35296L8.43352 9.59997C8.1039 9.09003 7.64729 8.67461 7.10854 8.39454C6.5698 8.11446 5.96746 7.97937 5.3607 8.00251C4.75395 8.02566 4.16365 8.20627 3.64781 8.52658C3.13197 8.84689 2.70834 9.29589 2.41853 9.82946C2.12872 10.363 1.98271 10.9628 1.99484 11.5699C2.00697 12.177 2.17683 12.7704 2.48772 13.292C2.79861 13.8136 3.23984 14.2453 3.76807 14.5447C4.29629 14.8442 4.89333 15.0011 5.50052 15Z"
                                            fill="currentColor"></path>
                                    </svg>
                                    <span class="text-gray-400 text-sm">15 Share</span>
                                </a>
                            </div>
                    
                        </div>
                        <!-- <div>
                            <a href="#" class="flex gap-2 items-center">
                                <svg class="icon-20 text-gray-400" width="20" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.4"
                                        d="M11.9912 18.6215L5.49945 21.864C5.00921 22.1302 4.39768 21.9525 4.12348 21.4643C4.0434 21.3108 4.00106 21.1402 4 20.9668V13.7087C4 14.4283 4.40573 14.8725 5.47299 15.37L11.9912 18.6215Z"
                                        fill="currentColor"></path>
                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                        d="M8.89526 2H15.0695C17.7773 2 19.9735 3.06605 20 5.79337V20.9668C19.9989 21.1374 19.9565 21.3051 19.8765 21.4554C19.7479 21.7007 19.5259 21.8827 19.2615 21.9598C18.997 22.0368 18.7128 22.0023 18.4741 21.8641L11.9912 18.6215L5.47299 15.3701C4.40573 14.8726 4 14.4284 4 13.7088V5.79337C4 3.06605 6.19625 2 8.89526 2ZM8.22492 9.62227H15.7486C16.1822 9.62227 16.5336 9.26828 16.5336 8.83162C16.5336 8.39495 16.1822 8.04096 15.7486 8.04096H8.22492C7.79137 8.04096 7.43991 8.39495 7.43991 8.83162C7.43991 9.26828 7.79137 9.62227 8.22492 9.62227Z"
                                        fill="currentColor"></path>
                                </svg>
                                <span class="text-gray-400">Save</span>
                            </a>
                        </div> -->
                    
                    </div>

                    <hr class="text-gray-400 my-3">

                    <div class="flex justify-around items-center">

                        <!-- <LikeAction :desModelId="activity.action_to.activityable.id"></LikeAction> -->

                        <form action="" :id="`form-dislike-post-${activity.id}`">
                            <button class="flex items-center space-x-1 text-sm text-gray-800 rounded-md px-2 py-1 hover:bg-blue-300 hover:scale-110 cursor-pointer">
                                <Icon icon="heroicons-outline:thumb-down" class="w-6 h-6 text-gray-600" />
                                <span>ไม่ถูกใจ</span>
                            </button>
                        </form>
                        <button class="flex items-center space-x-1 text-sm text-gray-800 rounded-md px-2 py-1 hover:bg-blue-300 hover:scale-110 cursor-pointer">
                            <Icon icon="heroicons-outline:share" class="w-6 h-6 text-gray-600" />
                            <span>แบ่งปัน</span>
                        </button>

                        <!-- <div class="flex items-center space-x-1 text-sm text-gray-800 rounded-md px-2 py-1 hover:bg-blue-300"> -->
                        <button @click.prevent="showComments = !showComments" class="flex items-center space-x-1 text-sm text-gray-800 rounded-md px-2 py-1 hover:bg-blue-200 hover:scale-110">
                            <Icon icon="heroicons-outline:chat-alt" class="w-6 h-6 text-gray-600" />
                            <span>ดูความคิดเห็น</span>
                        </button>
                        <!-- </div> -->

                    </div>

                    <hr class="text-gray-400 my-2">

                    <div v-if="showComments">
                        <div class="flex gap-4 w-full">
                            <img src="/storage/assets/images/avatar/11.png" class="w-10 h-10 p-[2px] rounded-full ring-1 ring-blue-600 dark:ring-gray-500" alt="">
                            <div class="w-full">
                                <div class="flex space-x-2 items-end">
                                    <h6 class="mb-0">Larry Robbins</h6>
                                    <span class="text-gray-600 text-sm">2 Min Ago</span>
                                </div>
                                <p class="text-gray-700 text-base bg-slate-100 border-[1.5px] border-gray-200 p-2 rounded-lg my-2">So True!!</p>
                                <div class="flex items-center gap-4 py-1">
                                    <a href="#" class="text-primary-500 flex space-x-1 text-sm hover:bg-blue-300 hover:scale-110 px-2 py-1 rounded-md">
                                        <Icon icon="heroicons-outline:thumb-up" class="w-5 h-5 text-gray-600" />
                                        <span>ถูกใจ</span> 
                                    </a>
                                    <a href="#" class="text-primary-500 flex space-x-1 text-sm hover:bg-blue-300 hover:scale-110 px-2 py-1 rounded-md">
                                        <Icon icon="heroicons-outline:thumb-down" class="w-5 h-5 text-gray-600" />
                                        <span>ไม่ถูกใจ</span> 
                                    </a>
                                    <a href="#" class="text-primary-500 flex space-x-1 text-sm hover:bg-blue-300 hover:scale-110 px-2 py-1 rounded-md">
                                        <Icon icon="heroicons-outline:reply" class="w-5 h-5 text-gray-600" />
                                        <span>ตอบกลับ</span>
                                    </a>
                                </div>
                            </div>
                        </div>                   
                    </div>

                    <hr v-if="showComments" class="text-gray-400 my-2">

                    <CommentForm />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref,computed } from 'vue';
import { Icon } from '@iconify/vue';
import { useForm,usePage } from "@inertiajs/vue3";

import AvatarGroupLikesWindUI from '@/HopeuiComponents/partials/PostViewerPartials/AvatarGroupLikesWindUI.vue'
import CommentForm from '@/HopeuiComponents/widgets/CommentForm.vue';
import LikeAction from '@/Components/LikeReaction.vue';

const pageData = usePage();

const props = defineProps({
    activity: Object
});

const likePostForm = useForm({
    post:''
});

const showComments = ref(false);
// const isActivityOwner = computed(() => props.activity.action_by.id === pageData.props.auth.user.id );
const toggleLikePost = (post) => {
    likePostForm.post(`/posts/${post}/like`,{ preserveScroll: true });
};
</script>

<style lang="scss" scoped>

</style>
