<script setup>
    import { ref } from 'vue';
    import { Icon } from '@iconify/vue';
    import { usePage } from '@inertiajs/vue3';
    import Swal from 'sweetalert2';

    const props = defineProps({
        post_url: String,
        desModelId: { type: Number, default: 0},
        isLikedByAuth: { type: Boolean, default: false },
        isDislikedByAuth: { type: Boolean, default: false },
    });

    const emit = defineEmits(['user-dislike-post', 'user-undislike-post']);
    const refIsDislikedByAuth = ref(props.isDislikedByAuth);
    
    const isLoading = ref(false);
    const authUser = usePage().props.auth.user;
    
    const handleSubmit = async() => {
        if(authUser.pp < 24){
            Swal.fire({
                title: 'คะแนนไม่เพียงพอ',
                text: "คุณต้องมีคะแนนมากกว่า 24 เพื่อให้สามารถกดไลค์ความคิดเห็นได้ กรุณาเก็บแต้มสะสมเพิ่มเติม",
                icon: 'error',
            });
            return;
        };
        try {
            isLoading.value = true;
            let dislikeResp = await axios.post(`${props.post_url}/dislike`);
            if(dislikeResp.data.success){
                refIsDislikedByAuth.value = !refIsDislikedByAuth.value;
                if(refIsDislikedByAuth.value){
                    emit('user-dislike-post');
                }else{
                    emit('user-undislike-post');
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
            console.log(error);
        }
    };
</script>

<template>
    <div>
        <button type="submit" @click.prevent="handleSubmit" :disabled="isLoading || isLikedByAuth" 
            :class="[!isLikedByAuth ? 'hover:scale-110 cursor-pointer': '' ]"
            class=" flex justify-center items-center space-x-1 text-sm rounded-full p-2 text-red-500">
            <div v-if="isLoading">
                <Icon icon="eos-icons:bubble-loading" class="w-6 h-6"/>
            </div>
            <div v-else class="flex justify-around items-center space-x-2 ">
                <span v-if="refIsDislikedByAuth">
                    <Icon icon="heroicons-solid:thumb-down" class="w-7 h-7" />
                </span>
                <span v-else>
                    <Icon icon="heroicons-outline:thumb-down" class="w-7 h-7"  />
                </span>
            </div>
        </button>
    </div>
</template>

