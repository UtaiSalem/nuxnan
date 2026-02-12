<script setup>
import { ref } from 'vue';
import { Icon } from '@iconify/vue';
import { usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    assignment: Object,
    answer: Object,
});

const emit = defineEmits(['set-points']);
const oldPoints = ref(props.answer.points);
const unSave = ref(false);
const isLoading = ref(false);

const handleInput = () => {
    unSave.value = props.answer.points !== oldPoints.value;
};

const handleSave = async () => {
    isLoading.value = true;
    try {
        const updateResp = await axios.post(`/assignments/${props.assignment.id}/answers/${props.answer.id}/set-points`, 
                            {
                                points      :   props.answer.points,
                                course_id   :   usePage().props.course.data.id,   
                            });
        if (updateResp.data.success) {
            emit('set-points', props.answer.points);
            unSave.value = false;
            isLoading.value = false;
            Swal.fire({
                icon: 'success',
                title: 'บันทึกสำเร็จ',
                text: 'บันทึกคะแนนเสร็จสมบูรณ์',
                showConfirmButton: false,
                timer: 800
            });
        }
    } catch (error) {
        console.log(error);
        Swal.fire('เกิดข้อผิดพลาด!','กรุณาลองใหม่.','error' );
        isLoading.value = false;
    }
};

</script>

<template>
    <div class="flex justify-end md:justify-between items-center space-x-4">
        <input :id="'minmax-range-' + answer.id" type="range" min="0" :max="assignment.points" 
            v-model="answer.points" @input="handleInput"
            class="hidden sm:flex w-full h-2 bg-indigo-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700">
        <div class="flex items-center">
            <input :id="'assignment-scores-input-' + answer.id" type="number" min="0" :max="assignment.points"
                v-model="answer.points" @input="handleInput"
                class=" w-[64px] h-8 px-2 text-sm placeholder-transparent transition-all border rounded outline-none focus-visible:outline-none peer border-slate-200 text-slate-500 autofill:bg-white invalid:border-pink-500 invalid:text-pink-500 focus:border-violet-500 focus:outline-none invalid:focus:border-pink-500 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400" />
            <button @click.prevent="handleSave" :disabled="isLoading || !unSave"
                :class="!answer.points ? 'bg-slate-400 hover:bg-slate-600': unSave ? 'bg-rose-500 hover:bg-rose-600':'bg-cyan-500 hover:bg-cyan-600'"
                class="flex justify-center space-x-1 items-center w-16 h-8 mx-4 bg-[#23d2e2] py-2 px-4 rounded-lg text-white shadow-lg  hover:shadow-xl hover:bg-[#4bc4cf] border border-gray-400">
                <Icon v-if="isLoading" icon="uiw:loading" 
                    class="animate-spin h-[23px] w-[23px] text-white" 
                />
                <small v-else>บันทึก</small>
            </button>
            <p>คะแนน</p>
        </div>
    </div>
</template>
