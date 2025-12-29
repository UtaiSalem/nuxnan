<script setup>
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AssignmentAnswerItem from './AssignmentAnswerItem.vue';

const props =  defineProps({
    assignment: Object,
    answers: Object,
});

const emit = defineEmits(['delete-answers']);

const isDeleting = ref(false);

async function onSetPoints(points,i){
    try {
        props.answers[i].points = points;
    } catch (error) {
        // Handle error silently
    }
}

function onDeleteAsmAnswer(answerId,index) {
    Swal.fire({
        title: 'แน่ใจหรือไม่?',
        text: "ที่จะลบคำตอบอย่างถาวร!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: 'ยกเลิก',
        confirmButtonText: 'ใช่, ยืนยัน!'
        }).then(async (result) => {
        if (result.isConfirmed) {
            isDeleting.value = true;
            try {
                await axios.delete(`/assignments/${props.assignment.id}/answers/${answerId}`, 
                    {
                        data: {
                            course_id: usePage().props.course.data.id,   
                        }
                    }
                );
                emit('delete-answers', index);
                Swal.fire('ลบเสร็จสิ้น!','ลบคำตอบเรียบร้อยแล้ว.','success');
            } catch (error) {
                console.error(error);
                Swal.fire('เกิดข้อผิดพลาด!', 'ไม่สามารถลบคำตอบได้', 'error');
            } finally {
                isDeleting.value = false;
            }
        }
    });
};

</script>
<template>
    <div>
        <div v-for="(answer,i) in props.answers" :key="answer.id" class="m-2" >
            <AssignmentAnswerItem 
                :assignment="props.assignment" 
                :answer
                :index="i"
                :isDeleting

                @update-answer="(points)=>onSetPoints(points, i)"
                @delete-answer="onDeleteAsmAnswer(answer.id, i)"
            />
        </div>
    </div>
</template>


