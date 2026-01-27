<script setup>
import { ref } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

import LoadingPage from '@/PlearndComponents/accessories/LoadingPage.vue';
import LessonItem from '@/PlearndComponents/learn/courses/lessons/LessonItem.vue';
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
            let delResp = await axios.delete(`/courses/${props.lessons[index].course_id}/lessons/${lessonId}`);
            if (delResp.status===200) {
                props.lessons.splice(index, 1);
            }
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


const openLessonId = ref(null);

const toggleLesson = (lessonId) => {
  openLessonId.value = openLessonId.value === lessonId ? null : lessonId;
};

</script>
<template>
    <div class="">
        <LoadingPage v-if="isLoadingPage" />
        
        <div class=" border-t-[3px] border-blue-500 p-3 my-4 text-xl shadow-lg bg-white rounded-lg flex justify-between items-center">
            <span>
                บทเรียนในรายวิชา {{ lessons.length }} บทเรียน
            </span>
        </div>

        <div v-for="(lesson, index) in lessons" :key="lesson.id" >
            <LessonItem 
                :lesson="lesson" 
                @edit-model="handleEditLesson(index)"
                @delete-model="onDeleteLesson(lesson.id, index)"
                @link-to-lesson="linkToSingleLesson(lesson.course.id, lesson.id, index)"
            />
        </div>
    </div>
</template>
