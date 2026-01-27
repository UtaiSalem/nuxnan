<script setup>

import axios from 'axios';
import { ref, reactive, computed } from 'vue'
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';

const props = defineProps({
    questionableType: String,
    questionableId: Number,
    question_id : Number,
    options: Object
});
const emit = defineEmits(['add-new-option']);
const canSubmit = computed( () => newQstOptionForm.text.length > 0 || tempImagesFile.length > 0 );

const isLoading = ref(false);
const tempImagesFile = reactive([]);
const tempImagesUrl = reactive([]);

const newQstOptionForm = reactive({
    question_id : props.question_id,
    text: ''
});

const qstOptionImagesInput = ref(null);
const browseQstOptionImages = () => qstOptionImagesInput.value.click();
const onQstOptionImagesInputChange = (e) => { 
    Array.from(e.target.files).forEach((image)=> {
        tempImagesFile.push(image);
        tempImagesUrl.push(URL.createObjectURL(image));
    });
};

function onDeleteTempImagesHandler(index) {
    tempImagesFile.splice(index, 1);
    tempImagesUrl.splice(index, 1);
};

const onSubmitQstOption = async () => {
    isLoading.value = true;
    try {
        const config = { headers: { 'Content-Type': 'multipart/form-data' } };
        let newQstOptionFormData = new FormData();
        newQstOptionFormData.append('text', newQstOptionForm.text);

        Array.from(tempImagesFile).forEach((image)=>{
        newQstOptionFormData.append('images[]', image);

        });

        let newOptionResp = await axios.post(`/questions/${props.question_id}/options`, newQstOptionFormData, config);

        if(newOptionResp && newOptionResp.data.success){
            Swal.fire({
                title: 'เพิ่มตัวเลือกสำเร็จ',
                text: 'บันทึกตัวเลือกเสร็จสมบูรณ์',
                icon: 'success',
                timer: 1000,
                showConfirmButton: false
            });
            
            emit('add-new-option', newOptionResp.data.option);
            // props.options.push(newOptionResp.data.option);
        }
        tempImagesFile.splice(0);
        tempImagesUrl.splice(0);
        qstOptionImagesInput.files = [];
        newQstOptionForm.text = '';
        isLoading.value = false;
    } catch (error) {
        console.error(error);
        isLoading.value = false;
    }
};

</script>

<template>
    <div class="w-full">
        <form :id="`add-new-question-option-form-${question_id}`" class="mt-2 relative dark:border-secondary-800 flex items-start">
            <div class="relative w-full">
                <textarea 
                :id="`new-question-option-input-${question_id}`" 
                type="text" name="new-question-option-input" 
                rows="2" 
                v-model="newQstOptionForm.text" 
                placeholder="คำตอบ..." class="relative w-full px-4 py-2 text-sm placeholder-transparent transition-all border rounded-lg outline-none focus-visible:outline-none peer border-slate-200 text-slate-500 autofill:bg-white invalid:border-pink-500 invalid:text-pink-500 focus:border-emerald-500 focus:outline-none invalid:focus:border-pink-500 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400"></textarea>
                <label :for="`new-question-option-input-${question_id}`" class="cursor-text peer-focus:cursor-default absolute left-2 -top-2 z-[1] px-2 text-xs text-slate-400 transition-all before:absolute before:top-0 before:left-0 before:z-[-1] before:block before:h-full before:w-full before:bg-white before:transition-all peer-placeholder-shown:top-2.5 peer-placeholder-shown:text-sm peer-required:after:text-pink-500 peer-required:after:content-['\00a0*'] peer-invalid:text-pink-500 peer-focus:-top-2 peer-focus:text-xs peer-focus:text-emerald-500 peer-invalid:peer-focus:text-pink-500 peer-disabled:cursor-not-allowed peer-disabled:text-slate-400 peer-disabled:before:bg-transparent">
                    เพิ่มตัวเลือก
                </label>

                <input type="file" accept="image/*" multiple ref="qstOptionImagesInput" id="qstOptionImagesInput" class="hidden" @change="onQstOptionImagesInputChange">
                <div class="flex gap-4 absolute bottom-2 right-2">
                    <button @click.prevent="browseQstOptionImages">
                        <svg class="icon-20 text-violet-700" width="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21.9999 14.7024V16.0859C21.9999 16.3155 21.9899 16.5471 21.9699 16.7767C21.6893 19.9357 19.4949 22 16.3286 22H7.67126C6.06806 22 4.71535 21.4797 3.74341 20.5363C3.36265 20.1864 3.042 19.7753 2.7915 19.3041C3.12217 18.9021 3.49291 18.462 3.85363 18.0208C4.46485 17.289 5.05603 16.5661 5.42677 16.0959C5.97788 15.4142 7.43078 13.6196 9.44481 14.4617C9.85563 14.6322 10.2164 14.8728 10.547 15.0833C11.3586 15.6247 11.6993 15.7851 12.2705 15.4743C12.9017 15.1335 13.3125 14.4617 13.7434 13.76C13.9739 13.388 14.2043 13.0281 14.4548 12.6972C15.547 11.2736 17.2304 10.8926 18.6332 11.7348C19.3346 12.1559 19.9358 12.6872 20.4969 13.2276C20.6172 13.3479 20.7374 13.4592 20.8476 13.5695C20.9979 13.7198 21.4989 14.2211 21.9999 14.7024Z" fill="currentColor"></path>
                            <path opacity="0.4" d="M16.3387 2H7.67134C4.27455 2 2 4.37607 2 7.91411V16.086C2 17.3181 2.28056 18.4119 2.79158 19.3042C3.12224 18.9022 3.49299 18.4621 3.85371 18.0199C4.46493 17.2891 5.05611 16.5662 5.42685 16.096C5.97796 15.4143 7.43086 13.6197 9.44489 14.4618C9.85571 14.6323 10.2164 14.8729 10.5471 15.0834C11.3587 15.6248 11.6994 15.7852 12.2705 15.4734C12.9018 15.1336 13.3126 14.4618 13.7435 13.759C13.9739 13.3881 14.2044 13.0282 14.4549 12.6973C15.5471 11.2737 17.2305 10.8927 18.6333 11.7349C19.3347 12.1559 19.9359 12.6873 20.497 13.2277C20.6172 13.348 20.7375 13.4593 20.8477 13.5696C20.998 13.7189 21.499 14.2202 22 14.7025V7.91411C22 4.37607 19.7255 2 16.3387 2Z" fill="currentColor"></path>
                            <path d="M11.4543 8.79668C11.4543 10.2053 10.2809 11.3783 8.87313 11.3783C7.46632 11.3783 6.29297 10.2053 6.29297 8.79668C6.29297 7.38909 7.46632 6.21509 8.87313 6.21509C10.2809 6.21509 11.4543 7.38909 11.4543 8.79668Z" fill="currentColor"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <!-- End Rounded base size textarea with helper text -->
            <button @click.prevent="onSubmitQstOption"
                type="submit" :disabled="!canSubmit" 
                name="comment-form-submit-button" 
                :id="`comment-form-submit-button-${question_id}`" 
                :class="canSubmit ? 'hover:scale-110 bg-violet-500 text-white border-violet-500': 'cursor-not-allowed text-gray-500 border-gray-500 '"
                class="mx-2 p-[6px]  rounded-lg border  ">
                <Icon v-if="isLoading" icon="uiw:loading" 
                    class="animate-spin h-[23px] w-[23px] text-white" 
                />
                <Icon v-else
                    icon="streamline:mail-send-reply-email-reply-message-actions-action-arrow" 
                    class="h-[23px] w-[23px] rotate-90"
                />
            </button>
            
        </form>  

        <div v-if="tempImagesUrl.length>0">
            <div v-for="(image,index) in tempImagesUrl" :key="index" class="">
                <div class="relative mb-2 max-h-fit overflow-hidden">
                    <img :src="image" class="border rounded-lg"/>
                    <button @click.prevent="onDeleteTempImagesHandler(index)"
                    class="absolute top-1 right-1 rounded-full cursor-pointer bg-gray-100 p-[6px]">
                        <Icon icon="fa-solid:trash-alt" class="w-4 h-4 text-red-500" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
