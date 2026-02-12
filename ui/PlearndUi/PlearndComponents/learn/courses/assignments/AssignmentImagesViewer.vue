<script setup>
import { ref } from 'vue';
import { Icon } from '@iconify/vue';
import { Link } from '@inertiajs/vue3';

import SingleAssignmentImageViewer from '@/PlearndComponents/learn/courses/assignments/SingleAssignmentImageViewer.vue';

const props = defineProps({
    model_id: Number,
    images: Object,
    edit: Boolean
});

const emit = defineEmits(['update:images']);

// const isLoading = ref(false);


async function deleteImage(index){
    // isLoading.value = true;
    // const response = await axios.delete(`/topics/${props.model_id}/images/${id}`);
    // const response = await axios.delete(`/asmimages/${id}`);
    // if (response.status === 200) {
        props.images.splice(index,1);
        // isLoading.value = false;
    // }
    // emit('update:images');
}

</script>
<template>
    <div class="mt-4">
        <!-- <div class="flex flex-wrap columns-2 md:columns-3 lg:columns-4"> -->
        <div class="">
            <!-- <div v-if="images && images.length<5" class="columns-1"> -->
            <div class="columns-1">
                <div v-for="(image,index) in images" :key="image.full_url" class="">
                    <SingleAssignmentImageViewer
                        :image="image"
                        :edit="edit"
                        @update:image="deleteImage(index)"
                    />
                </div>
            </div>
            <!-- </div> -->
            <!-- <div v-else class="columns-2">
                <div v-for="(i) in 4" :key="i" class="">
                    <div class="relative mb-2 max-h-fit overflow-hidden">
                        <a href="">
                            <img :src="images[i].full_url" class="rounded-lg hover:scale-110 duration-300" />
                        </a>
                        <button v-if="edit" @click.prevent="deleteImage(index,images[i].id)"
                        class="absolute top-1 right-1 w-8 h-8 flex items-center justify-center rounded-full cursor-pointer bg-gray-100 p-[6px]">
                            <Icon icon="fa-solid:trash-alt" class="w-4 h-4 text-red-500" />
                        </button>
                    </div>
                </div>
                <div class="flex justify-center items-center"> 
                    <div class="relative overflow-hidden">
                        <img :src="images[5].full_url" alt="" class="rounded-lg hover:scale-110 brightness-50 duration-300">
                        <a href="" class="absolute top-[calc(50%-20px)] left-[calc(50%-24px)] text-white text-3xl w-24">+ {{ images.length-5 }}</a>
                    </div>
                </div>
            </div> -->
        </div>
    </div>
</template>
