<script setup>
import { ref } from 'vue';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';

const props = defineProps({
    model_id: Number,
    images: Object,
    edit: Boolean
});

const emit = defineEmits(['update:images']);

const isLoading = ref(false);

async function deleteImage(index, id){
    isLoading.value = true;
    Swal.fire({
        title: 'ลบรูปภาพของบทเรียน',
        text: "ยืนยันการลบรูปภาพของบทเรียนอย่างถาวร",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#7c3aed',
        cancelButtonColor: '#f87171',
        confirmButtonText: 'ยืนยันการลบ'
    }).then( async (result) => {
        if (result.isConfirmed) {
            let delResp = await axios.delete(`/lessons/${props.model_id}/images/${id}`);
            if (delResp.status===200) {
                props.images.splice(index, 1);
            }
        }
    })

    isLoading.value = false;
}

</script>

<template>
    <div class="mt-4">
        <!-- <div class="flex flex-wrap columns-2 md:columns-3 lg:columns-4"> -->
        <div class="">
            <div v-if="images && images.length<=4" class="columns-1">
                <div v-for="(image,index) in images" :key="image.image_url" class="">
                    <div class="relative mb-2 overflow-hidden max-h-fit">
                        <img :src="'/storage/images/courses/lessons/'+image.image_url" class="rounded-lg"/>
                        <button v-if="edit" @click.prevent="deleteImage(index,image.id)"
                        class="absolute top-1 right-1 w-8 h-8 flex items-center justify-center rounded-full cursor-pointer bg-gray-100 p-[6px]">
                            <Icon icon="fa-solid:trash-alt" class="w-4 h-4 text-red-500" />
                        </button>
                    </div>
                </div>
            </div>
            <div v-else class="columns-2">
                <div v-for="(i) in 3" :key="i" class="">
                    <div class="relative mb-2 overflow-hidden max-h-fit">
                        <a href="">
                            <img :src="'/storage/images/courses/lessons/'+images[i].image_url" class="duration-300 rounded-lg hover:scale-110" />
                        </a>
                        <button v-if="edit" @click.prevent="deleteImage(index,images[i].id)"
                        class="absolute top-1 right-1 w-8 h-8 flex items-center justify-center rounded-full cursor-pointer bg-gray-100 p-[6px]">
                            <Icon icon="fa-solid:trash-alt" class="w-4 h-4 text-red-500" />
                        </button>
                    </div>
                </div>
                <div class="flex items-center justify-center"> 
                    <div class="relative overflow-hidden">
                        <img :src="'/storage/images/courses/lessons/'+images[4].image_url" alt="" class="duration-300 rounded-lg hover:scale-110 brightness-50">
                        <a href="" class="absolute top-[calc(50%-20px)] left-[calc(50%-24px)] text-white text-3xl w-24">+ {{ images.length-4 }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
