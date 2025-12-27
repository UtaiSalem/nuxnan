<script setup>
import { ref, reactive} from 'vue'
import { Icon } from '@iconify/vue';
import { useDebounceFn } from '@vueuse/core';
import Textarea from 'primevue/textarea';

const props = defineProps({
    questionApiRoute: String,
    quizId: Number,
});

const emit = defineEmits(['add-new-question']);

const showCreateNewQuestionForm = ref(false);
const questionInputActive = ref(false);

const questionInput = ref({
    text: '',
    points: 1,
    pp_fine: 0,
});

const isLoading = ref(false);
const isCopyingQuestion = ref(false);
const isLoadingQuestion = ref(null);
const inputQuestionImages =ref(null);
const oldQuestions = ref([]);
// const tempQuestion = ref(null);
const tempImagesFile = reactive([]);
const tempImagesUrl = reactive([]);

const browseInputQuestionImages = () => inputQuestionImages.value.click();

const handleQuestionInput = () => {
    try {
        isLoadingQuestion.value = true;
        getQuestions();
    } catch (error) {
        console.log(error);
    }
}
const getQuestions = useDebounceFn(async () => {
    let qstResp = await axios.get(`/user/questions`, {text: questionInput.value.text});
    if (qstResp.data) {
        oldQuestions.value = qstResp.data.questions_resource ?? null;
        isLoadingQuestion.value = false;
    }
}, 1000);

const handleClickOldQuestion = (question) => {
    questionInput.value.text = question.text;
    questionInput.value.points = question.points;
    questionInput.value.pp_fine = question.pp_fine;
    // questionInput.value.question_id = question.id;

    // tempQuestion.value = question;

    if (question.images && question.images.length > 0) {
        question.images.forEach(image => {
            tempImagesUrl.push(image.url);
        });
    }

    oldQuestions.value = [];
}

const handleCopyOldQuestions = async (question) => {
    isCopyingQuestion.value = true;
    try {
        const resp = await axios.post(`/questions/${question.id}/duplicate`, { quiz_id: props.quizId });
        if (resp.data && resp.data.success) {

            emit('add-new-question', resp.data.question);

        }else{
            console.log(resp.data);
        }
    } catch (error) {
        console.log(error);
    } finally {
        isCopyingQuestion.value = false;
    }
}

function handleDeleteOldQuestion(old_q_index){
    oldQuestions.value.splice(old_q_index, 1);
}

async function addNewQuestion(){
    isLoading.value = true;
    questionInputActive.value = !questionInputActive.value;
    if(questionInput.value.text.trim().length > 0 || tempImagesFile.length>0){

        const config = { headers: { 'Content-Type': 'multipart/form-data' } };
        let newQstForm = new FormData();
        newQstForm.append('text', questionInput.value.text);
        newQstForm.append('points', questionInput.value.points);
        newQstForm.append('pp_fine', questionInput.value.pp_fine);
        newQstForm.append('question_id', questionInput.value.question_id);

        Array.from(tempImagesFile).forEach((image)=>{
            newQstForm.append('images[]', image);
        });

        let newQResp = await axios.post(`${props.questionApiRoute}/questions`, newQstForm, config);
        if (newQResp.status===200) {
            emit('add-new-question', newQResp.data.question);
        }
        questionInput.value.text='';
        questionInput.value.points=0;
        // questionInput.value.pp_fine=0;
        // questionInput.value.question_id=null;
        tempImagesFile.splice(0);
        tempImagesUrl.splice(0);
        showCreateNewQuestionForm.value = false;

        // tempQuestion.value = [];
    }
    isLoading.value = false;
}

function onInputImageChange(e){
    Array.from(e.target.files).forEach(image => {
        tempImagesFile.push(image);
        tempImagesUrl.push(URL.createObjectURL(image));
    });
}

function onCancleHandler(e){
    inputQuestionImages.files = [];
    questionInput.value.text = '';
    questionInput.value.points = 0;
    questionInput.value.pp_fine = 0;
    tempImagesFile.splice(0);
    tempImagesUrl.splice(0);
    questionInputActive.value = false;
    showCreateNewQuestionForm.value = false;

    oldQuestions.value = [];
}

function deleteTempImages(index){
    tempImagesFile.splice(index,1);
    tempImagesUrl.splice(index,1);
}



</script>

<template>
    <div class="w-full ">
        <div v-if="isCopyingQuestion" class="fixed inset-0 bg-white bg-opacity-80 z-20 h-full w-full flex items-center justify-center">
            <div class="flex items-center">
                <svg class="animate-spin h-20 w-20 text-gray-800" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
            </div>
        </div>
        <!-- <hr class="border-b-2 border-blue-500" /> -->
        <div class="bg-white overflow-hidden mx-4 " v-if="$page.props.isCourseAdmin">

            <div class="" v-if="$page.props.isCourseAdmin">
                <form class="" v-if="showCreateNewQuestionForm">
                    <!-- Component: Rounded base size basic textarea -->
                    <div class="relative mt-4">
                        <Textarea :id="`${questionApiRoute}-input-question-text-form`" type="text" name="id-01" v-model="questionInput.text"
                            placeholder="Write your message" rows="3" @input="handleQuestionInput"
                            class="relative w-full px-4 pt-2 text-sm placeholder-transparent transition-all border rounded outline-none focus-visible:outline-none peer border-slate-200 text-slate-500 autofill:bg-white invalid:border-pink-500 invalid:text-pink-500 focus:border-emerald-500 focus:outline-none invalid:focus:border-pink-500 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400">
                        </Textarea>
                        <label :for="`${questionApiRoute}-input-question-text-form`"
                            class="cursor-text peer-focus:cursor-default absolute left-2 -top-2 z-[1] px-2 text-xs text-slate-400 transition-all before:absolute before:top-0 before:left-0 before:z-[-1] before:block before:h-full before:w-full before:bg-white before:transition-all peer-placeholder-shown:top-2.5 peer-placeholder-shown:text-sm peer-required:after:text-pink-500 peer-required:after:content-['\00a0*'] peer-invalid:text-pink-500 peer-focus:-top-2 peer-focus:text-xs peer-focus:text-emerald-500 peer-invalid:peer-focus:text-pink-500 peer-disabled:cursor-not-allowed peer-disabled:text-slate-400 peer-disabled:before:bg-transparent">
                            คำถาม
                        </label>
                    </div>
                    <!-- End Rounded base size basic textarea -->
                    <div  class="mb-2  max-h-40 overflow-y-auto">
                        <!-- <div > -->
                            <Icon icon="svg-spinners:6-dots-rotate" v-if="isLoadingQuestion" class="w-full flex items-center justify-center" />
                        <!-- </div> -->
                        <div v-for="(question, old_q_index) in oldQuestions" :key="question.id">
                            <div class="w-full flex items-center justify-between">
                                <button type="button" @click.prevent="handleClickOldQuestion(question)"
                                    class="flex items-center w-full p-3 text-sm text-gray-600 capitalize transition-colors duration-300 transform dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 dark:hover:text-white">
                                    <Icon icon="solar:card-send-broken" class="w-5 h-5 mx-1" />
                                    <span class="mx-1">{{ question.text }}</span>
                                </button>
                                <button type="button" @click.prevent="handleCopyOldQuestions(question)" class="h-full p-3 text-sm text-gray-600 capitalize transition-colors duration-300 transform dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 dark:hover:text-white">
                                    <Icon icon="prime:copy" class="w-5 h-5 mx-1 text-green-800 rounded-full" />
                                </button>
                                <button type="button" @click.prevent="handleDeleteOldQuestion(old_q_index)">
                                    <Icon icon="bx:bxs-trash" class="w-5 h-5 mx-1 text-red-500" />
                                </button>
                            </div>
                            <div v-if="question.images && question.images.length > 0" class="flex flex-wrap">
                                <img v-for="image in question.images" :key="image.id" :src="image.url" class="w-16 h-16 object-cover m-1 rounded" />
                            </div>
                        </div>
                    </div>

                    <div v-if="tempImagesUrl.length>0">
                        <div v-for="(image,index) in tempImagesUrl" :key="index" class="">
                            <div class="relative mb-2 max-h-fit overflow-hidden">
                                <img :src="image" class="rounded-lg" />
                                <button @click.prevent="deleteTempImages(index)"
                                    v-if="$page.props.isCourseAdmin"
                                    class="absolute top-1 right-1 rounded-full cursor-pointer bg-gray-100 p-[6px]">
                                    <Icon icon="fa-solid:trash-alt" class="w-4 h-4 text-red-500" />
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-between h-10 w-full">
                        <div class="flex justify-start items-center">
                            <label for="assignment-point-input"
                                class=" mr-4 mt-1 block text-lg font-medium text-gray-900 dark:text-white">คะแนน</label>
                            <button @click.prevent="questionInput.points<=0? 0: questionInput.points--;"
                                class="border-violet-500 hover:bg-violet-600 active:bg-violet-700 dark:border-violet-400 flex items-center justify-center rounded-l-xl border-2 p-2 text-xl  transition duration-200 hover:cursor-pointer dark:bg-white/5 dark:text-white dark:hover:bg-white/10 dark:active:bg-white/20">
                                <Icon icon="heroicons-solid:minus-sm"
                                    class="hover:text-white hover:scale-150 dark:text-white" />
                            </button>
                            <input type="number" min="0" name="" v-model="questionInput.points"
                                id="assignment-point-input"
                                class="w-20 h-10 text-center border-x-0 border-y-2 border-violet-500">
                            <button @click.prevent="questionInput.points++"
                                class="border-violet-500 hover:bg-violet-600 active:bg-violet-700 dark:border-violet-400 flex items-center justify-center rounded-r-xl border-2 p-2 text-xl  transition duration-200 hover:cursor-pointer dark:bg-white/5 dark:text-white dark:hover:bg-white/10 dark:active:bg-white/20">
                                <Icon icon="heroicons-solid:plus-sm"
                                    class=" hover:text-white hover:scale-150 dark:text-white" />
                            </button>
                        </div>

                        <div class=" ml-4" data-title="Insert Photo">
                            <input type="file" accept="image/*" multiple ref="inputQuestionImages"
                                class="hidden" @change="onInputImageChange">
                            <Icon icon="heroicons:photo"
                                class="w-9 h-9 hover:scale-110 bg-blue-200 hover:bg-blue-300 rounded-full p-2"
                                @click.prevent="browseInputQuestionImages" />
                        </div>
                    </div>
                    <div class="flex justify-start items-start mt-2">
                        <label for="pp-fine-input" class="mr-4 block text-lg font-medium text-gray-900 dark:text-white">แต้มค่าปรับ</label>
                        <div>
                        <input type="number" min="0" v-model="questionInput.pp_fine" id="pp-fine-input"
                                class="w-20 h-10 text-center border-2 border-violet-500 rounded-lg">
                        <p class="text-red-500 text-sm mt-1">ค่าปรับแต้มสะสมเมื่อมีการแก้ไขคำตอบ</p>
                        </div>
                    </div>
                </form>
            </div>

            <div class="w-full md:w-1/2 md:mx-auto flex justify-center items-center py-4 "
                v-if="$page.props.isCourseAdmin">
                <button v-if="!showCreateNewQuestionForm" type="button" @click.prevent="showCreateNewQuestionForm = true"
                    class="flex justify-center items-center text-white bg-green-500 hover:bg-green-600 focus:ring-4 focus:ring-violet-200 font-medium rounded-lg text-sm px-5 p-2 text-center">
                    <!-- <i class="pi pi-plus-circle mx-2 text-lg font-semibold"></i> -->
                    เพิ่มคำถาม
                </button>
                <button v-if="showCreateNewQuestionForm" type="button" @click.prevent="onCancleHandler"
                    class="px-4 py-1.5 mr-2 flex justify-center items-center border-2 border-red-500 hover:bg-red-300 focus:ring-blue-500 focus:ring-offset-blue-200 text-red-500 hover:text-white transition ease-in duration-200 text-center text-base font-semibold shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 rounded-full ">
                    <!-- <span> <i class="pi pi-times-circle mx-2 font-semibold"></i> </span> -->
                    ยกเลิก
                </button>
                <button v-if="showCreateNewQuestionForm && (questionInput.text.trim() !=='')" type="button"
                    @click.prevent="addNewQuestion"
                    class="px-4 py-2 flex justify-center items-center  bg-green-400 hover:bg-green-500 focus:ring-green-400 focus:ring-offset-green-200 text-white transition ease-in duration-200 text-center text-base font-semibold shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 rounded-full ">
                    <!-- <span> <i class="pi pi-save mx-2 font-semibold"></i> </span> -->
                    <svg v-if="isLoading" width="20" height="20" fill="currentColor" class="mr-2 animate-spin" viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg">
                            <path d="M526 1394q0 53-37.5 90.5t-90.5 37.5q-52 0-90-38t-38-90q0-53 37.5-90.5t90.5-37.5 90.5 37.5 37.5 90.5zm498 206q0 53-37.5 90.5t-90.5 37.5-90.5-37.5-37.5-90.5 37.5-90.5 90.5-37.5 90.5 37.5 37.5 90.5zm-704-704q0 53-37.5 90.5t-90.5 37.5-90.5-37.5-37.5-90.5 37.5-90.5 90.5-37.5 90.5 37.5 37.5 90.5zm1202 498q0 52-38 90t-90 38q-53 0-90.5-37.5t-37.5-90.5 37.5-90.5 90.5-37.5 90.5 37.5 37.5 90.5zm-964-996q0 66-47 113t-113 47-113-47-47-113 47-113 113-47 113 47 47 113zm1170 498q0 53-37.5 90.5t-90.5 37.5-90.5-37.5-37.5-90.5 37.5-90.5 90.5-37.5 90.5 37.5 37.5 90.5zm-640-704q0 80-56 136t-136 56-136-56-56-136 56-136 136-56 136 56 56 136zm530 206q0 93-66 158.5t-158 65.5q-93 0-158.5-65.5t-65.5-158.5q0-92 65.5-158t158.5-66q92 0 158 66t66 158z">
                            </path>
                        </svg>
                    บันทึก
                </button>
            </div>

        </div>
    </div>
</template>
