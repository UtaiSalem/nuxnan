<script setup>
import { ref } from 'vue';
import { Icon } from '@iconify/vue';
import { usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    lesson: Object,
    isLikedByAuth: { type: Boolean, default: false },
    isDislikedByAuth: { type: Boolean, default: false },
});

const emit = defineEmits(['user-dislike-lesson', 'user-undislike-lesson']);
const refIsDislikedByAuth = ref(props.isDislikedByAuth);

const isLoading = ref(false);
const authUser = usePage().props.auth.user;

const handleSubmit = async () => {
    if(authUser.pp < 24){
        Swal.fire({
            title: 'คะแนนไม่เพียงพอ',
            text: "คุณต้องมีคะแนนมากกว่า 24 เพื่อให้สามารถกดดิสไลค์บทเรียนได้ กรุณาเก็บแต้มสะสมเพิ่มเติม",
            icon: 'error',
        });
        return;
    };

    try {
        isLoading.value = true;
        const dislikeResp = await axios.post(`/courses/${props.lesson.course_id}/lessons/${props.lesson.id}/dislikes`);
        if(dislikeResp.data.success){
            refIsDislikedByAuth.value = !refIsDislikedByAuth.value;
            if(refIsDislikedByAuth.value){
                emit('user-dislike-lesson');
            }else{
                emit('user-undislike-lesson');
            }
        }else{
            Swal.fire({
                title: 'เกิดข้อผิดพลาด',
                text: dislikeResp.data.message,
                icon: 'error',
                confirmButtonColor: '#7c3aed',
            });
        }
        isLoading.value = false;
    } catch (error) {
        isLoading.value = false;
        Swal.fire({
                title: 'เกิดข้อผิดพลาด',
                text: error,
                icon: 'error',
                confirmButtonColor: '#7c3aed',
            });
    } 
};
</script>

<template>
    <div>
        <button @click.prevent="handleSubmit" :disabled="isLoading || isLikedByAuth" 
            class="flex justify-center items-center space-x-1 text-sm rounded-full p-2"
            :class="{ 'hover:scale-110 cursor-pointer': !isLikedByAuth, 'cursor-not-allowed': isLikedByAuth }">
            <div v-if="isLoading">
                <Icon icon="eos-icons:bubble-loading" class="w-6 h-6 "/>
            </div>
            <div v-else class="flex justify-around items-center space-x-2">
                <span v-if="refIsDislikedByAuth">
                    <Icon icon="icon-park:dislike" class="w-7 h-7 text-red-500 " />
                </span>
                <span v-else>
                    <Icon icon="icon-park-outline:dislike" class="w-7 h-7 text-red-500" />
                </span>
            </div>
        </button>
    </div>
</template>

