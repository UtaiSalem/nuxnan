<script setup>
import { ref, onMounted, computed } from 'vue'
import Button from 'primevue/button';
// import InputNumber from 'primevue/inputnumber';
import TopicQuestion from './TopicQuestion.vue'
// import QuestionScoreInput from './QuestionScoreInput.vue'
import { usePage } from '@inertiajs/vue3';
const page = usePage();

const authUser = page.props.auth.user;
const props = defineProps({topicID: Number, topicUserID: Number});

const isCourseOwner = computed(()=>{
    return props.topicUserID === authUser.id;
});

const topicQuestions = ref(null);
const questionInputActive = ref(false);
const questionInput = ref({
    text: '',
    score: 0
});

onMounted(() => { getTopicQuestions() });

function decrement(){
  questionInput.value.score--;
  questionInput.value.score < 0 ? questionInput.value.score=0: questionInput.value.score;
}

async function getTopicQuestions(){
    let qResponse = await axios.get(`/topic/${props.topicID}/questions`);
    topicQuestions.value = qResponse.data;
};

async function addNewQuestion(){
    questionInputActive.value = !questionInputActive.value;
    if(questionInput.value.text.length > 0){
        let newQuestionResponsed = await axios.post(`/topic/${props.topicID}/questions`, { question: questionInput.value});

        if (newQuestionResponsed.status===200) {
            topicQuestions.value.data.push(newQuestionResponsed.data.data);
            questionInput.value.text='';
            questionInput.value.score=0;
        }
    }
}

async function deleteQuestion(qID,qIdx){
    await axios.delete(`/topic/question/${qID}`);
    topicQuestions.value.data.splice(qIdx, 1);
}

</script>

<template>
    <div class="w-full">
        <div v-if="!topicQuestions"><div>ไม่มีคำถามสำหรับหัวข้อนี้ของบทเรียน</div></div>

        <div v-else v-for="(topicQuestion,idx) in topicQuestions.data" :key="topicQuestion.id" class="border rounded-md p-4 my-2">
            <TopicQuestion
                :question="topicQuestion"
                :indexNumber="idx"
                :isCourseOwner="isCourseOwner"
                @delete-question="deleteQuestion(topicQuestion.id, idx)"
            />
        </div>

        <div class="flex flex-col items-between my-2 space-y-2 " v-if="questionInputActive ">
            <div class="flex items-center">
                <!-- <label for="inline-input-label-with-helper-text" class="text-sm font-medium dark:text-white w-[36px]">
                    ข้อ {{ topicQuestions.data.length + 1 }}
                </label> -->
                <span class="w-[48px]">ข้อ {{ topicQuestions.data.length + 1 }}</span>
                <!-- <input type="text" v-model="questionInput.text" id="inline-input-label-with-helper-text" class="py-2 border-gray-200 w-full rounded-full text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400" aria-describedby="hs-inline-input-helper-text"> -->
                <input type="text" v-model="questionInput.text"
                class="py-2 border-gray-200 w-full rounded-full text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400" >
            </div>
            <div class="flex items-center space-x-4">
                <span>คะแนนเมื่อตอบถูก</span>
                <!-- <InputNumber v-model="questionInput.questionScore" inputId="minmax-buttons" mode="decimal" showButtons :min="0" :max="100" /> -->
                <!-- <QuestionScoreInput /> -->
                <div class="flex items-center justify-center">
                    <button type="button" @click="decrement" class="flex items-center justify-center mx-[2px] border-2 border-red-400 hover:bg-red-400 focus:ring-blue-500 focus:ring-offset-blue-200 text-red-600 hover:text-white transition ease-in duration-200 text-center text-base font-semibold shadow-md rounded-full" >
                        <i class="pi pi-minus-circle p-[8px] font-bold"></i>
                    </button>
                    <span class="mx-1 border-2 border-blue-400 px-3 py-1 rounded-lg text-blue-500">{{ questionInput.score }}</span>
                    <button type="button" @click="questionInput.score++" class="flex items-center justify-center mx-[2px] border-2 border-green-400 hover:bg-green-400   text-green-600 hover:text-white transition ease-in duration-200 text-center text-base font-semibold shadow-md rounded-full" >
                        <span><i class="pi pi-plus-circle p-[10px] font-bold"></i></span>
                    </button>
                </div>
            </div>
        </div>

        <div class="w-full flex justify-center items-center" v-if="isCourseOwner">
            <button v-if="!questionInputActive" type="button" @click.prevent="addNewQuestion" class="w-[25%] py-2 flex justify-center items-center  bg-blue-600 hover:bg-blue-700 focus:ring-blue-500 focus:ring-offset-blue-200 text-white transition ease-in duration-200 text-center text-base font-semibold shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 rounded-full ">
                <i class="pi pi-plus mx-2 font-bold"></i>
                เพิ่มคำถาม
            </button>
            <button  v-if="questionInputActive && questionInput.text.trim() !==''" type="button" @click.prevent="addNewQuestion" class="w-[25%] py-2 flex justify-center items-center  bg-green-400 hover:bg-green-500 focus:ring-green-400 focus:ring-offset-green-200 text-white transition ease-in duration-200 text-center text-base font-semibold shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 rounded-full ">
                <span> <i class="pi pi-save mx-2 font-bold"></i> </span>
                บันทึก
            </button>
            <button  v-if="questionInputActive && questionInput.text.trim() ==''" type="button" @click.prevent="addNewQuestion" class="w-[25%] py-2 flex justify-center items-center border-2 border-red-500 hover:bg-red-300 focus:ring-blue-500 focus:ring-offset-blue-200 text-red-500 hover:text-white transition ease-in duration-200 text-center text-base font-semibold shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 rounded-full ">
                <span> <i class="pi pi-times-circle mx-2 font-bold"></i> </span>
                ยกเลิก
            </button>
        </div>
    </div>
</template>
