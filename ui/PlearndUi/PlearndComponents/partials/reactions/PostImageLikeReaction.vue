<script setup>
    import { ref } from 'vue';
    import { Icon } from '@iconify/vue';
    import Swal from 'sweetalert2';

    const props = defineProps({
        desModelId: { type: Number, default: 0},
        isLikedByAuth: { type: Boolean, default: false },
        isDislikedByAuth: { type: Boolean, default: false },
    });
    
    const emit = defineEmits(['user-like-post_image', 'user-unlike-post_image']);
    const refIsLikedByAuth = ref(props.isLikedByAuth);
    
    const isLoading = ref(false);
    // const reactionForm = useForm({});
    const handleSubmit = async () => {
        try {
            isLoading.value = true;
            
            let likeResp = await axios.post(`/post_images/${props.desModelId}/like`);

            if(likeResp.data.success){
                refIsLikedByAuth.value = !refIsLikedByAuth.value;
                if(refIsLikedByAuth.value){
                    emit('user-like-post_image');
                }else{
                    emit('user-unlike-post_image');
                }
            }else{
                Swal.fire({
                    title: 'เกิดข้อผิดพลาด',
                    text: likeResp.data.message,
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
        <button  @click.prevent="handleSubmit" :disabled="isLoading || isDislikedByAuth" 
            :class="[!isDislikedByAuth ? 'hover:scale-110 cursor-pointer':'']"
            class=" flex justify-center items-center space-x-1 text-sm rounded-full p-2 text-green-500">
            <div v-if="isLoading">
                <Icon icon="eos-icons:bubble-loading" class="w-6 h-6 "/>
            </div>
            <div v-else class="flex justify-around items-center space-x-2">
                <span v-if="refIsLikedByAuth" >
                    <Icon icon="icon-park-solid:like" class="w-7 h-7" />
                </span>
                <span v-else>
                    <Icon icon="icon-park-outline:like" class="w-7 h-7" />
                </span>
            </div>
        </button>
    </div>
</template>

