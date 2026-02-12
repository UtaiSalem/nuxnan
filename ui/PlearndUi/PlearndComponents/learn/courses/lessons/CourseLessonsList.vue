<script setup>
import { ref } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

import LoadingPage from '@/PlearndComponents/accessories/LoadingPage.vue';
import LessonPost from '@/PlearndComponents/learn/courses/lessons/LessonPost.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    lessons: Object,
});

const emit = defineEmits(['create-new-lesson']);

const isLoadingPage = ref(false);

function handleEditLesson(index){
    isLoadingPage.value = true;
    router.visit(`/courses/${props.lessons[index].course.id}/lessons/${props.lessons[index].id}/edit`);
}

function onDeleteLesson(lessonId, index){
    Swal.fire({
        title: 'ลบบทเรียน',
        text: "ยืนยันการลบบทเรียนอย่างถาวร",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#7c3aed',
        cancelButtonColor: '#f87171',
        confirmButtonText: 'ยืนยันการลบ'
    }).then( async (result) => {
        if (result.isConfirmed) {
            isLoadingPage.value = true;
            let delResp = await axios.delete(`/courses/${props.lessons[index].course.id}/lessons/${lessonId}`);
            if (delResp.status===200) {
                props.lessons.splice(index, 1);
            }
            props.lessons.splice(index, 1);
        }
        isLoadingPage.value = false;
    })
}

const linkToSingleLesson = (courseId, lessonId, idx) => {
    if(!usePage().props.isCourseAdmin && (usePage().props.auth.user.pp < props.lessons[idx].point_tuition_fee)){
        Swal.fire({
            title: 'ขออภัย',
            text: "คุณมีแต้มสะสมไม่เพียงพอที่จะอ่านเรียนบทเรียนนี้",
            icon: 'warning',
            confirmButtonColor: '#7c3aed',
        });
        return;
    }
    isLoadingPage.value = true;
    router.visit(`/courses/${courseId}/lessons/${lessonId}`);
}

</script>
<template>
    <div class="">
    
        <LoadingPage v-if="isLoadingPage" />
        
        <div class=" border-t-[3px] border-blue-500 p-3 my-4 text-xl shadow-lg bg-white rounded-lg flex justify-between items-center">
            <span>
                บทเรียนในรายวิชา {{ props.lessons.length }} บทเรียน
            </span>
            <!-- <span v-if="usePage().props.isCourseAdmin">
                <CreateNewLesson @add-new-lesson="(newLesson)=> props.lessons.push(newLesson)" />
            </span> -->
        </div>

        <div v-for="(lesson, index) in props.lessons" :key="lesson.id" >
            <LessonPost :lesson="lesson" 
                @edit-model="handleEditLesson(index)"
                @delete-model="onDeleteLesson(lesson.id, index)"
                @link-to-lesson="linkToSingleLesson(lesson.course.id, lesson.id, index)"
            />
        </div>
    </div>
</template>
