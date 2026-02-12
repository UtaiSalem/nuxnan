<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';

const props = defineProps({
    model_id: Number,
    images: Object,
    edit: Boolean
});
// const emit = defineEmits(['update:images']);

const isLoading = ref(false);

async function deleteImage(index, id) {
    isLoading.value = true;
    Swal.fire({
        title: 'ลบรูปภาพของบทเรียน',
        text: "ยืนยันการลบรูปภาพของบทเรียนอย่างถาวร",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#7c3aed',
        cancelButtonColor: '#f87171',
        confirmButtonText: 'ยืนยันการลบ'
    }).then(async (result) => {
        if (result.isConfirmed) {
            let delResp = await axios.delete(`/posts/${props.model_id}/images/${id}`);
            if (delResp.status === 200) {
                props.images.splice(index, 1);
            }
        }
    })

    isLoading.value = false;
}

</script>

<template>
    <div class="mt-4">
        <div v-if="(images && images.length <= 4)" class="columns-1">
            <div v-for="(image, index) in images" :key="image.image_url" class="">
                <div class="relative mb-2 max-h-fit overflow-hidden">
                    <Link :href="'/posts/'+ model_id">
                        <img :src="image.full_url" class="rounded-lg border" />
                    </Link>
                    <button v-if="edit && ($page.url === '/posts/'+ model_id)" @click.prevent="deleteImage(index, image.id)"
                        class="absolute top-1 right-1 w-8 h-8 flex items-center justify-center rounded-full cursor-pointer bg-gray-100 p-[6px]">
                        <Icon icon="fa-solid:trash-alt" class="w-4 h-4 text-red-500" />
                    </button>
                </div>
            </div>
        </div>
        <div v-else class="columns-2">
            <div v-for="(i) in 3" :key="i" class="">
                <div class="relative mb-2 max-h-fit overflow-hidden">
                    <Link :href="'/posts/'+ model_id">
                        <img :src="images[i].full_url" class="border rounded-lg hover:scale-110 duration-300" />
                    </Link>
                    <button v-if="edit && ($page.url === '/posts/'+ model_id)" @click.prevent="deleteImage(index, images[i].id)"
                        class="absolute top-1 right-1 w-8 h-8 flex items-center justify-center rounded-full cursor-pointer bg-gray-100 p-[6px]">
                        <Icon icon="fa-solid:trash-alt" class="w-4 h-4 text-red-500" />
                    </button>
                </div>
            </div>
            <div class="flex justify-center items-center">
                <div class="relative overflow-hidden">
                    <img :src="images[4].full_url" alt="" class="border rounded-lg hover:scale-110 brightness-50 duration-300">
                    <Link :href="'/posts/'+ model_id" class="absolute top-[calc(50%-20px)] left-[calc(50%-24px)] text-white text-3xl w-24">
                        + {{ images.length-4 }}
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
