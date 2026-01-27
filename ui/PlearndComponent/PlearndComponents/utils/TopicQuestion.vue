<template>
    <div class="hs-accordion-group">
        <div class="hs-accordion active" id="hs-basic-with-title-and-arrow-stretched-heading-one">
            <div class="flex items-center justify-between">
                <button :id="`btn-hs-accordion-group${indexNumber}`"
                    class="bg-indigo-200 p-6 rounded-md hs-accordion-toggle hs-accordion-active:text-blue-600 group py-3 inline-flex items-center justify-between gap-x-3 w-full font-medium text-left text-gray-800 transition hover:text-blue-900 dark:hs-accordion-active:text-blue-500 dark:text-gray-200 dark:hover:text-gray-400"
                    aria-controls="hs-basic-with-title-and-arrow-stretched-collapse-one">
                    ข้อที่ {{ indexNumber + 1 }} {{ props.question.question }}
                    <svg class="hs-accordion-active:hidden hs-accordion-active:text-blue-600 hs-accordion-active:group-hover:text-blue-600 block w-3 h-3 text-gray-600 group-hover:text-gray-500 dark:text-gray-400"
                        width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M2 5L8.16086 10.6869C8.35239 10.8637 8.64761 10.8637 8.83914 10.6869L15 5"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                    <svg class="hs-accordion-active:block hs-accordion-active:text-blue-600 hs-accordion-active:group-hover:text-blue-600 hidden w-3 h-3 text-gray-600 group-hover:text-gray-500 dark:text-gray-400"
                        width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M2 11L8.16086 5.31305C8.35239 5.13625 8.64761 5.13625 8.83914 5.31305L15 11"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                </button>
                <!-- <button class="w-[40px]" v-if="courseOwner" @click.prevent="deleteQuestion(props.question.id)">
                    <i class="pi pi-times text-red-600 font-bold p-3 bg-red-200 rounded-full"></i>
                </button> -->
                <div class="hs-dropdown relative inline-flex">
                    <button :id="`hs-dropdown-custom-icon-trigger${props.question.id}`" v-if="isCourseOwner" type="button" data-dropdown-offset-distance="0" data-dropdown-offset-skidding="0" class="hs-dropdown-toggle py-2 px-1 ml-1 inline-flex justify-center items-center gap-2 rounded-md border font-medium bg-white  shadow-sm align-middle hover:bg-gray-50 focus:outline-none focus:ring-1 focus:ring-offset-1 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:bg-slate-900 dark:hover:bg-slate-800 dark:border-gray-700 dark:text-gray-400 dark:hover:text-white dark:focus:ring-offset-gray-800">
                        <EllipsisVerticalIcon class="h-6 w-6 text-blue-500" />
                    </button>

                    <div class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden bg-white border shadow-lg rounded-lg p-2 mt-2 top-0 left-0 right-[420px] min-w-max dark:bg-gray-800 dark:border dark:border-gray-700" :aria-labelledby="`hs-dropdown-custom-icon-trigger${props.question.id}`">
                        <button @click.prevent="emit('delete-question')" :id="`btn-delete-question-${props.question.id}`" class="flex items-center gap-x-3.5 py-2 px-3 rounded-md text-sm text-gray-800 hover:bg-gray-100 focus:ring-2 focus:ring-blue-500 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300">
                            <i class="pi pi-trash text-red-600 font-bold p-2 bg-red-200 rounded-full"></i>
                            ลบคำถาม
                        </button>
                    </div>
                </div>
            </div>
            <div id="hs-basic-with-title-and-arrow-stretched-collapse-one"
                class="hs-accordion-content w-full overflow-hidden transition-[height] duration-300"
                aria-labelledby="hs-basic-with-title-and-arrow-stretched-heading-one">
                <div class="ml-4 mt-4">
                    <div class="grid space-y-2">
                        <div v-for="(individualOption, idx) in questionOptions" :key="individualOption.id">
                            <div class="flex w-full justify-between items-center">
                                <label :for="`${props.question.id}+${individualOption.id}`"
                                    class="bg-slate-100 hover:bg-teal-300 p-3 rounded-full flex items-center w-full"
                                    :class="{ 'bg-teal-200': selectedOption == individualOption.id }">
                                    <input type="radio" :id="`${props.question.id}+${individualOption.id}`"
                                        :name="`${props.question.id}`" :value="individualOption.id" v-model="selectedOption"
                                        :checked="selectedOption == individualOption.id" />
                                    <p class="text-md text-gray-700 ml-3 dark:text-gray-400">
                                        {{ idx + 1 }} . {{ individualOption.option }}
                                    </p>
                                </label>
                                <button :id="`btn-delete-option-${individualOption.id}`" class="w-[40px]" v-if="isCourseOwner" @click.prevent="deleteOption(individualOption.id,idx)">
                                    <i class="pi pi-trash text-red-600 font-bold p-2 bg-red-200 rounded-full"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-center">
                        <!-- <button class=" m-2 px-4 py-2 bg-blue-400 hover:bg-blue-600 text-md text-gray-700 hover:text-white rounded-full">ยืนยันคำตอบ</button> -->
                        <button type="button" :id="`btn-confirm-answer${props.question.id}`" v-if="isCourseOwner" @click.prevent="handleConfirmAnswer(props.question.id, selectedOption)"
                            class="m-2 py-3 px-4 inline-flex justify-center items-center gap-2 rounded-full bg-blue-200 border border-blue-700 text-blue-700 hover:text-white hover:bg-blue-600 ring-offset-white transition-all text-sm dark:focus:ring-offset-gray-800">
                            ยืนยันคำตอบ
                        </button>
                    </div>

                    <div class="mt-3 flex justify-between" v-if="isCourseOwner">
                        <input type="text" :id="props.question.id"
                            class="py-2 px-3 block w-full border-gray-200 rounded-full text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400"
                            placeholder="เพิ่มตัวเลือก" v-model="optionInputText" />
                        <button type="button" :id="`btn-add-question-options${props.question.id}`" @click.prevent="handleAddOptionButtonClick(props.question.id)"
                            class="flex items-center justify-center mx-[2px] border border-green-400 bg-green-200 hover:bg-green-400 focus:ring-blue-500 focus:ring-offset-blue-200 text-green-600 hover:text-white transition ease-in duration-200 text-center text-base font-semibold shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 rounded-full">
                            <span v-if="optionInputText.length > 0"><i class="pi pi-save p-3 font-bold"></i></span>
                            <span v-else> <i class="pi pi-plus-circle p-3 font-bold"></i></span>
                        </button>
                    </div>
                    <div class="my-2 mr-[50px]" v-if="isCourseOwner">
                        <select :id="`select-question-correct-answer${props.question.id}`"
                            class="py-2 px-4 pr-9 block w-full rounded-full text-md text-gray-700 dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400"
                            name="set-correct-answer-selection"
                            :class="[correctAnswer ? 'border-indigo-400' : 'border-red-500']"
                            @change="setCorrectAnswer(props.question.id, correctAnswer)" v-model="correctAnswer">
                            <option selected :value="false">คำตอบที่ถูกต้องคือ ข้อ</option>
                            <option v-for="(individualOption, i) in questionOptions" :key="individualOption.id"
                                :value="individualOption.id">
                                {{ i + 1 }} {{ individualOption.option }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { EllipsisVerticalIcon } from '@heroicons/vue/24/solid'
import { usePage } from "@inertiajs/vue3";
const props = defineProps({
    question: Object,
    indexNumber: Number,
    isCourseOwner: Boolean,
});

const page = usePage();

const emit = defineEmits(['delete-question']);

const optionInputBoxActive = ref(false);
const optionInputText = ref("");
const selectedOption = ref(null);

const correctAnswer = ref(null);
const questionOptions = computed(() => props.question.options);

onMounted(() => {
    initCorrectAnswer();
});

async function handleAddOptionButtonClick(qid) {
    optionInputBoxActive.value = !optionInputBoxActive.value;
    if (optionInputText.value.length > 0) {
        let newOptionResponsed = await axios.post(`/question/${qid}/options`, {
            option: optionInputText.value,
        });
        props.question.options.push(newOptionResponsed.data);
        optionInputText.value = "";
    }
}

async function handleConfirmAnswer(qID, userAnswer) {
    // if(answer) {
        // console.log(qID, answer);
    // }
    // console.log('กรุณาเลือกคำตอบ');
    let confirmAnswerResponsed = await axios.post(`/question/${qID}/answer/validate`, 
    {
        user_answer: userAnswer,
        courseOwnerPoint: page.props.course_owner.personal_point,
    }
    );
    console.log(confirmAnswerResponsed.data);
    
}

async function deleteOption(id, idx) {
    await axios.delete(`/options/${id}`);
    props.question.options.splice(idx, 1);
}


async function setCorrectAnswer(qid, aid) {
    if (aid) {
        let respAnswer = await axios.post(`/question/${qid}/answers`, { answer: aid });
        correctAnswer.value = respAnswer.data.answer.correct_option_id;
    } else {
        console.log("กรุณาเลือกคำตอบ");
    }
}

async function initCorrectAnswer() {
    if (props.isCourseOwner) {
        if(props.question.options){
            let answerResp = await axios.get(`/question/${props.question.id}/answers`);
            correctAnswer.value = answerResp.data;
        }else {
            correctAnswer.value = 0;
        }
    }
}


</script>
