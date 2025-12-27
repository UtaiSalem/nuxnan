<script setup>
import { ref, computed } from 'vue';
import { Icon } from '@iconify/vue';
import { usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    lesson: Object,
    isLikedByAuth: { type: Boolean, default: false },
    isDislikedByAuth: { type: Boolean, default: false },
});

const emit = defineEmits(['user-like-lesson', 'user-unlike-lesson']);
const refIsLikedByAuth = ref(props.isLikedByAuth);

const isLoading = ref(false);
const authUser = usePage().props.auth.user;

const buttonClass = computed(() => ({
  'hover:scale-110 cursor-pointer': !props.isDislikedByAuth,
  'cursor-not-allowed': props.isDislikedByAuth
}));

const handleSubmit = async () => {
    if(authUser.pp < 24){
        Swal.fire({
            title: 'คะแนนไม่เพียงพอ',
            text: "คุณต้องมีคะแนนมากกว่า 24 เพื่อให้สามารถกดไลค์บทเรียนได้ กรุณาเก็บแต้มสะสมเพิ่มเติม",
            icon: 'error',
        });
        return;
    }

    try {
        isLoading.value = true;
        const likeResp = await axios.post(`/courses/${props.lesson.course_id}/lessons/${props.lesson.id}/likes`);
        if(likeResp.data.success){
            refIsLikedByAuth.value = !refIsLikedByAuth.value;
            emit(refIsLikedByAuth.value ? 'user-like-lesson' : 'user-unlike-lesson');
        } else {
            console.log(likeResp.data);
            Swal.fire({
                title: 'เกิดข้อผิดพลาด',
                text: likeResp.data.message,
                icon: 'error',
                confirmButtonColor: '#7c3aed',
            });
        }
    } catch (error) {
        console.log(error);
        Swal.fire({
            title: 'เกิดข้อผิดพลาด',
            text: error,
            icon: 'error',
            confirmButtonColor: '#7c3aed',
        });
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <div>
        <button @click.prevent="handleSubmit" :disabled="isLoading || isDislikedByAuth" 
            class="flex justify-center items-center space-x-1 text-sm rounded-full p-2 text-green-500"
            :class="buttonClass">
        
            <Icon v-if="isLoading" icon="eos-icons:bubble-loading" class="w-6 h-6" />
            <Icon v-else :icon="refIsLikedByAuth ? 'icon-park-solid:like' : 'icon-park-outline:like'" class="w-7 h-7" />
        </button>
    </div>
</template>
