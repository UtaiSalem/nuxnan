<script setup>
import { ref } from 'vue';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';

import CoursePostImageReactionViewer from '@/PlearndComponents/learn/courses/posts/PostViewerPartials/PostImages/CoursePostImageReactionViewer.vue';

const props = defineProps({
    model_id: Number,
    post: Object,
    images: Object,
    images_resources: Object,
    edit: Boolean,
});
// const emit = defineEmits(['update:images']);

const isLoading = ref(false);

async function deleteImage(index, id) {
    isLoading.value = true;
    Swal.fire({
        title: 'ลบรูปภาพของโพสต์',
        text: "ยืนยันการลบรูปภาพของโพสต์อย่างถาวร",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#7c3aed',
        cancelButtonColor: '#f87171',
        confirmButtonText: 'ยืนยันการลบ'
    }).then(async (result) => {
        if (result.isConfirmed) {
            let delResp = await axios.delete(`/courses/${props.post.course_id}/posts/${props.model_id}/images/${id}`);
            if (delResp.data.success) {
                props.images.splice(index, 1);
                Swal.fire(
                    'ลบรูปภาพของโพสต์สำเร็จ',
                    'รูปภาพของโพสต์ถูกลบออกแล้ว',
                    'success'
                )
            }
        }
    })

    isLoading.value = false;
}

</script>

<template>
    <div class="mt-4">
        <div class="columns-1">
            <div v-for="(image, index) in images_resources" :key="image.image_url" class="">
                <div class="relative mb-2 max-h-fit overflow-hidden border">
                    <img :src="image.full_url" class="rounded-lg" />
                    <button v-if="edit" @click.prevent="deleteImage(index, image.id)"
                        class="absolute top-1 right-1 w-8 h-8 flex items-center justify-center rounded-full cursor-pointer bg-gray-100 p-[6px]">
                        <Icon icon="fa-solid:trash-alt" class="w-4 h-4 text-red-500" />
                    </button>

                    <CoursePostImageReactionViewer :post :image :model="'coursepost'" />
                </div>
            </div>
        </div>
    </div>
</template>
