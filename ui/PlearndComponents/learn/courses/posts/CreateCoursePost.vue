<script setup>
import { ref, reactive, computed } from 'vue';
import { usePage } from "@inertiajs/vue3";
import { useObjectUrl } from '@vueuse/core';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';
import LoadingPage from '@/PlearndComponents/accessories/LoadingPage.vue';

const props = defineProps({
    course_id: Number,
});

const emit = defineEmits(['add-new-post']);

const waiting = ref(false);
const inputPostImages = ref(null);
const tempPostImages = reactive([]);
const selectedPrivacy = ref(3);

const authUser = usePage().props.auth.user;

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

const defaultPostForm = reactive({
    content: '',
    'course_id': props.course_id, // 'course_id': 'required|integer',
    point:1,
    privacy_settings: 3,
    images: [],
});

const postForm = reactive({
    content: '',
    'course_id': props.course_id,
    point:1,
    privacy_settings: 3,
    images: [],
});

async function onSubmitCreatePost(){
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
    }else if(authUser.pp < 180){
        Swal.fire({
            icon: 'error',
            title: 'แต้มใช้งานสะสมไม่เพียงพอ',
            text: 'คุณต้องมีแต้มใช้งานสะสมมากกว่า 180 คะแนน กรุณาสะสมแต้มเพิ่มเติม',
        });
        return;
    };

    waiting.value = true;

    try {

        const config = {
            headers: {
                'Content-Type': 'multipart/form-data',
            }
        };

        const formData = new FormData();
        formData.append('content', postForm.content);
        formData.append('course_id', postForm.course_id);
        formData.append('point', postForm.point);
        formData.append('privacy_settings', selectedPrivacy.value);
        postForm.images.forEach((image, index) => {
            formData.append(`images[${index}]`, image);
        });

        let response = await axios.post(`/courses/${props.course_id}/posts`, formData, config);

        if(response.data.success){
            // console.log(response.data.activity);
            postForm.content = '';
            postForm.images = [];
            tempPostImages.splice(0);
            waiting.value = false;

            Swal.fire({
                icon: 'success',
                title: 'โพสต์สำเร็จ',
                text: response.data.message,
            });
            emit('add-new-post', response.data.activity);
        }else{
            waiting.value = false;
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: response.data.message,
            });
        }
        
    } catch (error) {
        // Handle error silently
    }
}

function handleCancleCreatePost(){
    postForm.value = defaultPostForm.value;
};

</script>

<template>
    <div class="relative px-4 py-2 bg-white rounded-lg shadow-lg hs-accordion-group">
        <div class="hs-accordion active" id="hs-basic-with-title-and-arrow-stretched-heading-one">
            <button class="inline-flex items-center justify-between w-full py-2 text-left text-gray-800 transition hs-accordion-toggle hs-accordion-active:text-blue-600 group gap-x-3 font-base hover:text-gray-500 dark:hs-accordion-active:text-blue-500 dark:text-gray-200 dark:hover:text-gray-400" aria-controls="hs-basic-with-title-and-arrow-stretched-collapse-one">
                <div class="flex items-center space-x-6">
                    <img :src="$page.props.auth.user.profile_photo_url" class="w-12 h-12 p-[3px] rounded-full ring-1 ring-blue-600 dark:ring-gray-500" alt="">
                    <span>เขียนโพสต์ใหม่</span>
                </div>

            </button>
            <div id="hs-basic-with-title-and-arrow-stretched-collapse-one" class="hs-accordion-content w-full overflow-hidden transition-[height] duration-300" aria-labelledby="hs-basic-with-title-and-arrow-stretched-heading-one">
                <div class="px-1 py-2">
                    <form @submit.prevent="onSubmitCreatePost" id="newPostForm">
                        <div class="border-b border-secondary-200 dark:border-secondary-800">
                            <Textarea 
                                :id="`course-post-message`" 
                                name="postcontent" 
                                v-model="postForm.content" 
                                rows="2"
                                autoResize
                                class="block p-2.5 mb-2 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                placeholder="เขียนสิ่งที่อยากบอกเล่า..." >
                            </textarea>
                        </div>

                        <div v-if="tempPostImages" class="w-full mt-2 columns-2 md:columns-3 lg:columns-4 ">
                            <div v-for="(image, index) in tempPostImages" :key="image.url" class="">
                                <div class="relative mb-2 overflow-hidden p-0.5 border rounded-md ">
                                    <img :src="image.url" class="rounded-lg" />
                                    <button @click.prevent="deleteTempPostImage(index)"
                                        class="absolute flex items-center justify-center w-8 h-8 p-2 bg-gray-100 rounded-full cursor-pointer top-1 right-1">
                                        <Icon icon="fa-solid:trash-alt" class="w-4 h-4 text-red-500" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between mx-1 mt-4">
                            <div class="flex items-center">
                                <input type="file" accept="image/*" multiple ref="inputPostImages" class="hidden" @change="onInputImageInput">
                                <button @click.prevent="browseInputPostImages" class="flex gap-2 p-1.5 shadow-sm rounded-full bg-violet-200 text-violet-600 text-lg dark:bg-dark-strip dark:text-white">
                                    <!-- <span class="text-sm">Photo</span> -->
                                    <svg class="w-6" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
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
                                <!-- <div class="flex gap-2 px-2 py-1 mx-4 text-sm text-gray-400 bg-gray-100 rounded dark:bg-dark-strip dark:text-white">
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
                                <!-- <div class="px-2 py-1 mx-4 text-sm bg-gray-100 rounded text-secondary-600 dark:bg-dark-strip dark:text-white">
                                    More...
                                </div> -->
                                <!-- <div class="relative flex items-center mx-4">
                                    <Icon :icon="privacyOptions[selectedPrivacy-1].icon" class="w-6 h-6"/>
                                    <select @input="handleSelectPrivacySetting" v-model="selectedPrivacy" id="privacy" class="absolute inline-flex items-center w-16 text-sm font-medium text-left text-white border rounded-lg opacity-50 -right-8 border-violet-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                        <option v-for="(item, index) in privacyOptions" :key="index" 
                                            class="py-2 text-sm text-gray-700 dark:text-gray-200" 
                                            :value="item.id"
                                            >
                                            {{ item.label }}
                                        </option>
                                    </select>
                                </div> -->
                            </div>
                            <div class="flex space-x-2">
                                <button type="button" @click.prevent="handleCancleCreatePost" class="px-2 py-1 text-sm text-red-500 bg-gray-100 border border-red-500 rounded-lg dark:text-white hover:scale-110">ยกเลิก</button>
                                <button type="submit" :disabled="waiting || !canBePosted" class="px-2 py-1 text-sm text-blue-500 bg-blue-100 border border-blue-500 rounded-lg dark:text-white disabled:cursor-not-allowed" :class="canBePosted ? 'hover:scale-110' : 'cursor-not-allowed'">เขียนกระดาน</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- <div v-if="waiting" class="absolute z-50 -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2">
            <Icon icon="fa-solid:spinner" class="w-20 h-20 mr-2 text-indigo-600 animate-spin dark:text-gray-600 fill-blue-600" />
        </div> -->
        <LoadingPage v-if="waiting" />

    </div>
</template>

