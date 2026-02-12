<script setup>
import { ref } from 'vue';
import { Icon } from '@iconify/vue';

const props = defineProps({
    image: Object,
    edit: Boolean
});

const emit = defineEmits([
    'update:image'
]);

const isLoading = ref(false);

async function deleteImage(id){
    isLoading.value = true;
    const response = await axios.delete(`/asmimages/${id}`);
    if (response.status === 200) {
        emit('update:image');
        isLoading.value = false;
    }
}

</script>
<template>
    <div class="relative mb-2 max-h-fit overflow-hidden">
        <img :src="image.full_url" class="rounded-lg border"/>
        <button v-if="edit" @click.prevent="deleteImage(image.id)"
        class="absolute top-1 right-1 w-8 h-8 flex items-center justify-center rounded-full cursor-pointer bg-gray-100 p-[6px]">
            <Icon icon="fa-solid:trash-alt" class="w-4 h-4 text-red-500" />
        </button>
        <div v-if="isLoading" class="absolute top-0 left-0 w-full h-full bg-gray-600/50 rounded-lg flex items-center justify-center">
            <Icon icon="svg-spinners:bars-rotate-fade" class="z-30 w-24 h-24 text-white" />
        </div>
    </div>
</template>
