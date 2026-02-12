<script setup>
    import { ref } from 'vue';
    import { Icon } from '@iconify/vue';
    import Swal from 'sweetalert2';

    const props = defineProps({
        desModelId: { type: Number, default: 0},
        isLikedByAuth: { type: Boolean, default: false },
        isDislikedByAuth: { type: Boolean, default: false },
    });

    const emit = defineEmits(['user-dislike-post-image', 'user-undislike-post-image']);
    const refIsDislikedByAuth = ref(props.isDislikedByAuth);
    
    const isLoading = ref(false);
    const handleSubmit = async () => {
        try {
            isLoading.value = true;
            let dislikeResp = await axios.post(`/post_images/${props.desModelId}/dislike`);
            if(dislikeResp.data.success){
                refIsDislikedByAuth.value = !refIsDislikedByAuth.value;
                if(refIsDislikedByAuth.value){
                    emit('user-dislike-post-image');
                }else{
                    emit('user-undislike-post-image');
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

