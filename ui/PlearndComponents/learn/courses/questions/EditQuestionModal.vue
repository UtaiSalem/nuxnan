<script setup>
import { ref, reactive } from 'vue'
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';
import Textarea from 'primevue/textarea';

const props = defineProps({
    questionApiRoute: String,
    question: Object,
    isVisible: Boolean,
    isCourseOwner: Boolean,
});

const emit = defineEmits([
    'update-question',
    'delete-question-image',
    'close-modal'
]);


const isSaving = ref(false);
const isDeletingImage = ref(false);
const imageDeletedIndex = ref(null);
const inputQuestionImages = ref(null);
const tempImagesFile = reactive([]);
const tempImagesUrl = reactive([]);

const browseInputQuestionImages = () => inputQuestionImages.value.click();

async function updateQuestion() {
    isSaving.value = true;
    if (props.question.text.trim().length > 0 || tempImagesFile.length > 0) {
        const config = { headers: { 'Content-Type': 'multipart/form-data' } };
        let updateQstForm = new FormData();
        updateQstForm.append('_method', 'PUT');
        updateQstForm.append('text', props.question.text);
        updateQstForm.append('points', parseInt(props.question.points));
        updateQstForm.append('pp_fine', parseInt(props.question.pp_fine));
        
        Array.from(tempImagesFile).forEach((image) => {
            updateQstForm.append('images[]', image);
        });

        try {
            let updateQResp = await axios.post(`/questions/${props.question.id}`, updateQstForm, config);
            // let updateQResp = await axios.post(`/${props.questionApiRoute}/questions/${props.question.id}`, updateQstForm, config);
            if (updateQResp.status === 200) {
                emit('update-question', updateQResp.data.question);
                // Swal.fire('สำเร็จ', 'อัปเดตคำถามเรียบร้อยแล้ว', 'success');
                // auto close Swal in 1500 millisec
                Swal.fire({
                    icon: 'success',
                    title: 'Question updated successfully',
                    showConfirmButton: false,
                    timer: 1000 // This will auto close the Swal in 1500 milliseconds (1.5 seconds)
                });
                closeModal();
            }
        } catch (error) {
            console.log('Error details:', error.response ? error.response.data : error);
            Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดในการอัปเดตคำถาม', 'error');
        }
    }
    isSaving.value = false;
}

function onInputImageChange(e) {
    Array.from(e.target.files).forEach(image => {
        tempImagesFile.push(image);
        tempImagesUrl.push(URL.createObjectURL(image));
    });
}

function closeModal() {
    tempImagesFile.splice(0);
    tempImagesUrl.splice(0);
    emit('close-modal');
}

function deleteTempImages(index) {
    tempImagesFile.splice(index, 1);
    tempImagesUrl.splice(index, 1);
}

const deleteQuestionImageHandler = async (questionImageIndex) => {
    try {
        isDeletingImage.value = true;
        imageDeletedIndex.value = questionImageIndex;
        let imageId = props.question.images[questionImageIndex].id;
        const delImgResp = await axios.delete(`/questions/${props.question.id}/images/${imageId}`);
        if (delImgResp.data.success) {
            emit('delete-question-image', questionImageIndex);
        } else {
            Swal.fire('ลบรูปภาพ', 'ลบรูปภาพไม่สำเร็จ', 'error');
        } 
    } finally {
        isDeletingImage.value = false;
        imageDeletedIndex.value = null;
    }
};

</script>

<template>
    <div v-if="isVisible" class="fixed inset-0 bg-gray-600 bg-opacity-80 overflow-y-auto min-h-screen py-16 w-full flex justify-center items-center z-50">
        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
            <h2 class="text-2xl m-4 text-violet-600">แก้ไขคำถาม</h2>
            <hr class="border-b-2 border-blue-500" />
            <form @submit.prevent="updateQuestion" class="space-y-4 p-4 sm:p-6">
                <div class="relative">
                    <Textarea :id="`edit-question-text-form`" v-model="question.text" placeholder="เขียนคำถามของคุณ" rows="3"
                        class="w-full px-4 py-2 text-sm border rounded outline-none focus:border-emerald-500" />
                    <label for="edit-question-text-form" class="absolute left-2 -top-2 z-[1] px-2 text-xs text-slate-400 bg-white">
                        คำถาม
                    </label>
                </div>

                <div v-for="(q_image, qi_index) in question.images" :key="qi_index" class="my-1 relative  max-h-fit overflow-hidden">
                    <img :src="q_image.url" class="rounded-lg" alt="Question image" />
                    <div v-if="isDeletingImage && imageDeletedIndex === qi_index" 
                        class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50">
                        <Icon icon="svg-spinners:6-dots-rotate" class="w-16 h-16 text-white animate-spin" />
                    </div>
                    <button type="button" v-if="isCourseOwner" 
                            @click="deleteQuestionImageHandler(qi_index)" 
                            class="absolute top-2 left-2 rounded-full cursor-pointer bg-gray-100 p-[6px]">
                            <Icon icon="fa-solid:trash-alt" class="w-4 h-4 text-red-500" />
                    </button>
                </div>

                <div v-if="tempImagesUrl.length > 0">
                    <div v-for="(image, index) in tempImagesUrl" :key="index" class="relative mb-2 max-h-fit overflow-hidden">
                        <img :src="image" class="rounded-lg" />
                        <button type="button" @click.prevent="deleteTempImages(index)"
                            class="absolute top-1 left-1 rounded-full cursor-pointer bg-gray-100 p-[6px]">
                            <Icon icon="fa-solid:trash-alt" class="w-4 h-4 text-red-500" />
                        </button>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0 sm:space-x-4">
                    <div class="flex items-center">
                        <label for="edit-assignment-point-input" class="mr-4 text-lg font-medium text-gray-900">คะแนน</label>
                        <button type="button" @click.prevent="question.points <= 0 ? 0 : question.points--;"
                            class="border-violet-500 hover:bg-violet-600 active:bg-violet-700 flex items-center justify-center rounded-l-xl border-2 p-2 text-xl transition duration-200">
                            <Icon icon="heroicons-solid:minus-sm" class="hover:text-white hover:scale-150" />
                        </button>
                        <input type="number" min="0" v-model="question.points" id="edit-assignment-point-input"
                            class="w-20 h-10 text-center border-x-0 border-y-2 border-violet-500">
                        <button type="button" @click.prevent="question.points++"
                            class="border-violet-500 hover:bg-violet-600 active:bg-violet-700 flex items-center justify-center rounded-r-xl border-2 p-2 text-xl transition duration-200">
                            <Icon icon="heroicons-solid:plus-sm" class="hover:text-white hover:scale-150" />
                        </button>
                    </div>

                    <div class="ml-4" data-title="Insert Photo">
                        <input type="file" accept="image/*" multiple ref="inputQuestionImages" class="hidden" @change="onInputImageChange">
                        <Icon icon="heroicons:photo" class="w-9 h-9 hover:scale-110 bg-blue-200 hover:bg-blue-300 rounded-full p-2"
                            @click.prevent="browseInputQuestionImages" />
                    </div>
                </div>

                <div class="flex items-start sm:items-center">
                    <label for="edit-pp-fine-input" class="mr-4 text-lg font-medium text-gray-900">แต้มค่าปรับ</label>
                    <div>
                        <input type="number" min="0" v-model="question.pp_fine" id="edit-pp-fine-input"
                            class="w-20 h-10 text-center border-2 border-violet-500 rounded-lg">
                        <p class="text-red-500 text-sm mt-1">ค่าปรับแต้มสะสมเมื่อมีการแก้ไขคำตอบ</p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-end mt-6 space-y-2 sm:space-y-0 sm:space-x-2">
                    <button @click.prevent="closeModal" type="button"
                        class="w-full sm:w-auto px-4 py-1 border-2 border-red-500 hover:bg-red-200 text-red-400 hover:text-red-600 transition rounded-full">
                        ยกเลิก
                    </button>
                    <button type="submit" :disabled="isSaving || question.text.trim() === ''"
                        class="w-full sm:w-auto flex items-center justify-center px-4 py-1 bg-green-400 hover:bg-green-500 text-white transition rounded-full disabled:opacity-80">
                        <Icon v-if="isSaving" icon="svg-spinners:6-dots-rotate" class="w-5 h-5 text-white animate-spin" />
                        <Icon v-else icon="fluent:save-24-regular" class="w-5 h-5 text-white mr-0.5" />
                        <span>บันทึก</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
