<script setup>
import { ref } from 'vue';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';

const props = defineProps({
    modelID: Number,
    image: Object,
    // edit: Boolean
});

const emit = defineEmits(['image-deleted']);

const isDeletingImage = ref(false);
async function deleteImage(){
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
            isDeletingImage.value = true;
            let delResp = await axios.delete(`/lessons/${props.modelID}/images/${props.image.id}`);
            if (delResp.status===200) {
                emit('image-deleted');
                isDeletingImage.value = false;
            }else{
                Swal.fire({
                    title: 'เกิดข้อผิดพลาด',
                    text: "ไม่สามารถลบรูปภาพได้ในขณะนี้",
                    icon: 'error',
                    confirmButtonColor: '#7c3aed',
                })
            }
        }
    })

    isDeletingImage.value = false;
}

</script>

<template>

    <div class="columns-1">
        <div class="relative mb-2 overflow-hidden max-h-fit">
            <img :src="image.full_url" class="rounded-lg border"/>
            <button v-if="$page.props.isCourseAdmin" @click.prevent="deleteImage"
            class="absolute top-1 right-1 w-8 h-8 flex items-center justify-center rounded-full cursor-pointer bg-gray-100 p-[6px]">
                <Icon icon="fa-solid:trash-alt" class="w-4 h-4 text-red-500" />
            </button>
            <div v-if="isDeletingImage" class=" absolute m-auto left-0 right-0 top-0 bottom-0 w-full h-full z-10 bg-gray-600/75 rounded-lg flex items-center justify-center">
                <Icon  icon="svg-spinners:ring-resize" class=" w-20 h-20 text-white " />
            </div>
        </div>
    </div>

</template>
