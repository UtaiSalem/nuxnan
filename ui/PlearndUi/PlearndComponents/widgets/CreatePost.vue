<script setup>
import { ref, reactive, computed } from 'vue';
import { useForm, usePage } from "@inertiajs/vue3";
import { useObjectUrl } from '@vueuse/core';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';

const waiting = ref(false);
const inputPostImages = ref(null);
const tempPostImages = reactive([]);
const selectedPrivacy = ref(3);

const authUser = ref(usePage().props.auth.user);

const emit = defineEmits(['add-new-post-activity']);

const privacyOptions = reactive([
        { id: 1, label: 'ส่วนตัว', icon: 'bx:user-pin'},
        { id: 2, label: 'เพื่อน' , icon: 'oui:users'},
        { id: 3, label: 'สาธารณะ', icon: 'uiw:global'},
    ]);
    
    
    const browseInputPostImages = () => inputPostImages.value.click();
    function onInputImageInput(e) {
        Array.from(e.target.files).forEach(image => {
            tempPostImages.push({ file: image, url: useObjectUrl(image) });
            postForm.images.push(image);
        });
    }
    
    function deleteTempPostImage(index) { 
        tempPostImages.splice(index, 1);
        postForm.images.splice(index, 1);
    }
    
    const canBePosted = computed(() => postForm.content.length>0 || postForm.images.length>0);
    
    
    function handleSelectPrivacySetting(e){
        selectedPrivacy.value = parseInt(e.target.value);
    }
    
    // const postForm = useForm({
    //     content: '',
    //     point:1,
    //     privacy_settings: 3,
    //     images: [],
    // });

    const postForm = reactive({
        content: '',
        point:1,
        privacy_settings: 3,
        images: [],
    });

    async function onSubmitCreatePost(){
        // console.log(postForm);
        if(!canBePosted.value ) {
            Swal.fire({
                icon: 'error',
                title: 'กรุณากรอกข้อมูลให้ครบถ้วน',
                text: 'โพสต์ต้องมีเนื้อหาหรือรูปภาพอย่างน้อย 1 รายการ',
            });
            return;
        }else if(postForm.content.length>5000){
            Swal.fire({
                icon: 'error',
                title: 'เนื้อหาโพสต์ยาวเกินไป',
                text: 'เนื้อหาโพสต์ต้องมีความยาวไม่เกิน 5000 ตัวอักษร'
            });
            return
        }else if(postForm.images.length>20){
            Swal.fire({
                icon: 'error',
                title: 'รูปภาพโพสต์เกินจำนวนที่กำหนด',
                text: 'โพสต์ได้ไม่เกินครั้งละ 20 รูปภาพ',
            });
            return;
        }else if(authUser.value.pp < 180){
            Swal.fire({
                icon: 'error',
                title: 'แต้มใช้งานสะสมไม่เพียงพอ',
                text: 'คุณต้องมีแต้มใช้งานสะสมมากกว่า 180 คะแนน กรุณาสะสมแต้มเพิ่มเติม',
            });
            return;
        };
        waiting.value = true;
        // try {
        //     postForm.post('/posts',
        //         {
        //             preserveScroll: true,
        //             onSuccess: ()=>{ 
        //                 postForm.reset();
        //                 tempPostImages.splice(0);
        //                 waiting.value = false;
        //                 usePage().props.auth.user.pp -= 180;
        //             },
        //             onError: handleErrorCreatePost,
        //         }
        //     );
        // } catch (error) {
        //     console.log(error);
        // }

        try {

            const config = {
                headers: {
                    'Content-Type': 'multipart/form-data',
                }
            };

            const formData = new FormData();
            formData.append('content', postForm.content);
            formData.append('point', postForm.point);
            formData.append('privacy_settings', selectedPrivacy.value);
            postForm.images.forEach((image, index) => {
                formData.append(`images[${index}]`, image);
            });

            let response = await axios.post(`/posts`, formData, config);

            if(response.data.success){
                postForm.content = '';
                postForm.images = [];
                tempPostImages.splice(0);
                waiting.value = false;
                Swal.fire({
                    icon: 'success',
                    title: 'โพสต์สำเร็จ',
                    text: response.data.message,
                });
                emit('add-new-post-activity', response.data.activity);
                authUser.value.pp -= 180;
            }else{
                waiting.value = false;
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: response.data.message,  
                });
            }

        } catch (error) {
            waiting.value = false;
            console.log(error);
        }

    }
     
    function handleCancleCreatePost(){
        // postForm.reset();
        postForm.content = '';
        postForm.images = [];
        tempPostImages.splice(0);

    };

    function handleErrorCreatePost(errors){
        waiting.value = false;
        console.log(errors);
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'กรุณาตรวจสอบข้อมูลที่กรอกใหม่อีกครั้ง',
        });
    }

</script>

<template>
    <div class="relative hs-accordion-group bg-white rounded-lg mt-4 md:mt-0 shadow-lg p-2  border-t-4 border-blue-500">
        <div class="hs-accordion active" id="hs-basic-with-title-and-arrow-stretched-heading-one">
            <button class="hs-accordion-toggle hs-accordion-active:text-blue-600 group py-2 inline-flex items-center justify-between gap-x-3 w-full font-base text-left text-gray-800 transition hover:text-gray-500 dark:hs-accordion-active:text-blue-500 dark:text-gray-200 dark:hover:text-gray-400" aria-controls="hs-basic-with-title-and-arrow-stretched-collapse-one">
                <div class="flex items-center space-x-6">
                    <img :src="$page.props.auth.user.profile_photo_url" class="w-12 h-12 p-[3px] rounded-full ring-1 ring-blue-600 dark:ring-gray-500" alt="">
                    <span>เขียนโพสต์ใหม่</span>
                </div>

            </button>
            <div id="hs-basic-with-title-and-arrow-stretched-collapse-one" class="hs-accordion-content w-full overflow-hidden transition-[height] duration-300" aria-labelledby="hs-basic-with-title-and-arrow-stretched-heading-one">
                <div class="py-2 px-1">
                    <form @submit.prevent="onSubmitCreatePost" id="newPostForm">
                        <div class="border-b border-secondary-200 dark:border-secondary-800">
                            <textarea id="post-message" name="postcontent" v-model="postForm.content" rows="4"
                                class="block p-2.5 mb-2 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                placeholder="เขียนสิ่งที่อยากบอกเล่า..." >
                            </textarea>
                        </div>

                        <div v-if="tempPostImages" class="mt-2 w-full columns-2 md:columns-3 lg:columns-4 ">
                            <div v-for="(image, index) in tempPostImages" :key="image.url" class="">
                                <div class="relative mb-2 overflow-hidden p-0.5 border rounded-md ">
                                    <img :src="image.url" class="rounded-lg" />
                                    <button @click.prevent="deleteTempPostImage(index)"
                                        class="absolute w-8 h-8 flex justify-center items-center top-1 right-1 rounded-full cursor-pointer bg-gray-100 p-2">
                                        <Icon icon="fa-solid:trash-alt" class="w-4 h-4 text-red-500" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 mx-1 flex items-center justify-between">
                            <div class="flex items-center">
                                <input type="file" accept="image/*" multiple ref="inputPostImages" class="hidden" @change="onInputImageInput">
                                <button @click.prevent="browseInputPostImages" class="flex gap-2 p-1.5 shadow-sm rounded-full bg-violet-200 text-violet-600 text-sm md:text-lg dark:bg-dark-strip dark:text-white">
                                    <!-- <span class="text-sm">Photo</span> -->
                                    <svg class="w-4 md:w-6" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M21.9999 14.7024V16.0859C21.9999 16.3155 21.9899 16.5471 21.9699 16.7767C21.6893 19.9357 19.4949 22 16.3286 22H7.67126C6.06806 22 4.71535 21.4797 3.74341 20.5363C3.36265 20.1864 3.042 19.7753 2.7915 19.3041C3.12217 18.9021 3.49291 18.462 3.85363 18.0208C4.46485 17.289 5.05603 16.5661 5.42677 16.0959C5.97788 15.4142 7.43078 13.6196 9.44481 14.4617C9.85563 14.6322 10.2164 14.8728 10.547 15.0833C11.3586 15.6247 11.6993 15.7851 12.2705 15.4743C12.9017 15.1335 13.3125 14.4617 13.7434 13.76C13.9739 13.388 14.2043 13.0281 14.4548 12.6972C15.547 11.2736 17.2304 10.8926 18.6332 11.7348C19.3346 12.1559 19.9358 12.6872 20.4969 13.2276C20.6172 13.3479 20.7374 13.4592 20.8476 13.5695C20.9979 13.7198 21.4989 14.2211 21.9999 14.7024Z"
                                            fill="currentColor"></path>
                                        <path opacity="0.4"
                                            d="M16.3387 2H7.67134C4.27455 2 2 4.37607 2 7.91411V16.086C2 17.3181 2.28056 18.4119 2.79158 19.3042C3.12224 18.9022 3.49299 18.4621 3.85371 18.0199C4.46493 17.2891 5.05611 16.5662 5.42685 16.096C5.97796 15.4143 7.43086 13.6197 9.44489 14.4618C9.85571 14.6323 10.2164 14.8729 10.5471 15.0834C11.3587 15.6248 11.6994 15.7852 12.2705 15.4734C12.9018 15.1336 13.3126 14.4618 13.7435 13.759C13.9739 13.3881 14.2044 13.0282 14.4549 12.6973C15.5471 11.2737 17.2305 10.8927 18.6333 11.7349C19.3347 12.1559 19.9359 12.6873 20.497 13.2277C20.6172 13.348 20.7375 13.4593 20.8477 13.5696C20.998 13.7189 21.499 14.2202 22 14.7025V7.91411C22 4.37607 19.7255 2 16.3387 2Z"
                                            fill="currentColor"></path>
                                        <path
                                            d="M11.4543 8.79668C11.4543 10.2053 10.2809 11.3783 8.87313 11.3783C7.46632 11.3783 6.29297 10.2053 6.29297 8.79668C6.29297 7.38909 7.46632 6.21509 8.87313 6.21509C10.2809 6.21509 11.4543 7.38909 11.4543 8.79668Z"
                                            fill="currentColor"></path>
                                    </svg>
                                </button>
                                <!-- <div class="flex gap-2 px-2 py-1 mx-4 rounded bg-gray-100 text-gray-400 text-sm dark:bg-dark-strip dark:text-white">
                                    <span class="text-sm ">Tag Friend</span>
                                    <svg class="w-[1rem]" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M11.997 15.1746C7.684 15.1746 4 15.8546 4 18.5746C4 21.2956 7.661 21.9996 11.997 21.9996C16.31 21.9996 19.994 21.3206 19.994 18.5996C19.994 15.8786 16.334 15.1746 11.997 15.1746Z"
                                            fill="currentColor"></path>
                                        <path opacity="0.4"
                                            d="M11.9971 12.5838C14.9351 12.5838 17.2891 10.2288 17.2891 7.29176C17.2891 4.35476 14.9351 1.99976 11.9971 1.99976C9.06008 1.99976 6.70508 4.35476 6.70508 7.29176C6.70508 10.2288 9.06008 12.5838 11.9971 12.5838Z"
                                            fill="currentColor"></path>
                                    </svg>
                                </div> -->
                                <!-- <div class="px-2 py-1 mx-4 rounded bg-gray-100 text-sm text-secondary-600 dark:bg-dark-strip dark:text-white">
                                    More...
                                </div> -->
                                <div class="mx-4 flex items-center relative">
                                    <Icon :icon="privacyOptions[selectedPrivacy-1].icon" class="w-4 md:w-6 h-4 md:h-6"/>
                                    <select @input="handleSelectPrivacySetting" v-model="selectedPrivacy" id="privacy" class="absolute -right-8 opacity-50 w-10 md:w-16 h-7 md:h-10 text-white border border-violet-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text[11px] md:text-sm text-left inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                        <option v-for="(item, index) in privacyOptions" :key="index" 
                                            class="py-0 md:py-2 text-[11px] md:text-sm text-gray-700 dark:text-gray-200" 
                                            :value="item.id"
                                            >
                                            {{ item.label }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <button type="button" @click.prevent="handleCancleCreatePost" class="px-2 py-1 rounded-lg bg-gray-100 border border-red-500 text-[11px] md:text-sm text-red-500 dark:text-white hover:scale-110">ยกเลิก</button>
                                <button type="submit" :disabled="waiting || !canBePosted" class="px-2 py-1 rounded-lg bg-blue-100 border border-blue-500 text-[11px] md:text-sm text-blue-500 dark:text-white disabled:cursor-not-allowed" :class="canBePosted ? 'hover:scale-110' : 'cursor-not-allowed'">เขียนกระดาน</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div v-if="waiting" class="absolute -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2 z-50">
            <svg aria-hidden="true" class="w-8 h-8 mr-2 text-gray-200 animate-spin dark:text-gray-600 fill-blue-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/><path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/></svg>
            <span class="sr-only">Loading...</span>
        </div>
    </div>
</template>

