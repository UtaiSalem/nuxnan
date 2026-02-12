<script setup>
import { ref } from 'vue';
import { Icon } from '@iconify/vue';

const props = defineProps({
  images: {
    type: Array,
    required: true
  },
  modelId: {
    type: [Number, String],
    required: true
  },
  // edit: {
  //   type: Boolean,
  //   default: false
  // },
  isCourseOwner: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['delete-image']);

const isDeleteLoading = ref(false);
const deletingIndex = ref(null);

const handleDeleteImage = async (index) => {
  try {
    isDeleteLoading.value = true;
    deletingIndex.value = index;
    let imageId = props.images[index].id;
    const delImgResp = await axios.delete(`/questions/${props.modelId}/images/${imageId}`);
    if (delImgResp.data.success) {
        emit('delete-image', index);
    } else {
        Swal.fire('ลบรูปภาพ', 'ลบรูปภาพไม่สำเร็จ', 'error');
    } 
  } finally {
    isDeleteLoading.value = false;
    deletingIndex.value = null;
  }
};

</script>

<template>
    <div class="flex flex-wrap justify-center mt-2">
      <div v-for="(image, qi_index) in images" :key="qi_index" class="my-1 relative  max-h-fit overflow-hidden">
        <img :src="image.url" class="rounded-lg" alt="Question image" />
        <div v-if="isDeleteLoading && deletingIndex === qi_index" 
             class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50">
          <Icon icon="eos-icons:loading" class="w-16 h-16 text-white animate-spin" />
        </div>
        <button v-if="isCourseOwner" 
                @click="handleDeleteImage(qi_index)" 
                class="absolute top-2 left-2 rounded-full cursor-pointer bg-gray-100 p-[6px]">
          <Icon icon="fa-solid:trash-alt" class="w-4 h-4 text-red-500" />
        </button>
      </div>
    </div>
</template>  
