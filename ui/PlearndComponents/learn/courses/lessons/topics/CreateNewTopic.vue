<script setup>
import { ref } from 'vue'
import Textarea from 'primevue/textarea';

import { Icon } from '@iconify/vue';
import { useObjectUrl } from '@vueuse/core';

const props = defineProps({
  lesson: Object,
});

const emit = defineEmits(['close', 'add-new-topic']);
const imageInput = ref(null);
const tempImages = ref([]);
const isSubmitting = ref(false);

const closeModal = () => {
  handleCancle();
  emit('close');
};

const form = ref({
  title: '',
  content: '',
  youtube_url: '',
  min_read: 1,
  images: []
});

async function submitFormHandler() {
  try {
    isSubmitting.value = true;

    const formData = new FormData();
    formData.append('title', form.value.title);
    formData.append('content', form.value.content);
    formData.append('min_read', form.value.min_read);
    formData.append('youtube_url', form.value.youtube_url);
    tempImages.value.forEach((image) => {
      formData.append('images[]', image.file);
    });

    const topicResp = await axios.post(`/lessons/${props.lesson.id}/topics`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    if (topicResp.status === 200) {
      emit('add-new-topic', topicResp.data.newTopic);
    }

  } catch (error) {
    // Handle error silently
  } finally {
    isSubmitting.value = false;
    handleCancle();
    closeModal();
  }
}

const draging = ref(false);
const openImageSelector = () => imageInput.value.click();

const handleImageUpload = (event) => {
  // const files = event.target.files;
  // for (let i = 0; i < files.length; i++) {
  //   const reader = new FileReader();
  //   reader.onload = (e) => {
  //     form.value.images.push(e.target.result);
  //   };
  //   reader.readAsDataURL(files[i]);
  // }
  Array.from(event.target.files).forEach(image => {
    tempImages.value.push({ file: image, url: useObjectUrl(image) });
  });
};

function onDropFile(e) {
  e.preventDefault();
  // const files = e.dataTransfer.files;
  // for (let i = 0; i < files.length; i++) {
  //   if (files[i].type.startsWith('image/')) {
  //     const reader = new FileReader();
  //     reader.onload = (event) => {
  //       form.value.images.push(event.target.result);
  //     };
  //     reader.readAsDataURL(files[i]);
  //   }
  // }

  Array.from(e.dataTransfer.files).forEach(image => {
    tempImages.value.push({ file: image, url: useObjectUrl(image) });
  });

  e.dataTransfer.clearData();
  draging.value = false;
}

const handleCancle = () => {
  form.value.images = [];
  form.value.title = '';
  form.value.content = '';
  tempImages.value = [];
}

const removeImage = (index) => {
  tempImages.value.splice(index, 1);
};

</script>
<template>
    <div class="fixed inset-0 z-30 py-14 transition-opacity bg-gray-800 bg-opacity-80 backdrop-blur-lg overflow-y-auto">
      <div class="relative bg-white rounded-lg shadow-xl overflow-hidden max-w-xl w-full mx-auto border-t-4 border-blue-500">
        <div class="flex justify-between items-center p-4 bg-blue-200">
          <h3 class="text-lg font-bold text-blue-900">เพิ่มหัวข้อใหม่ในบทเรียน</h3>
          <button @click="closeModal" class="text-gray-600 hover:text-red-700">
            <Icon icon="heroicons:x-mark-20-solid" class="w-6 h-6" />
          </button>
        </div>

        <div class="p-2">
          <form @submit.prevent="submitFormHandler" class="space-y-4">
            <div>
              <label :for="`topic_title_${lesson.id}`"
                class="block text-md font-semibold text-gray-700">ชื่อหัวข้อ</label>
              <Textarea :id="`topic_title_${lesson.id}`" v-model="form.title" rows="1" autoResize
                class="mt-1 block w-full rounded-md border-gray-300" required></Textarea>

            </div>

            <div>
              <label :for="`topic_content_${lesson.id}`" class="block text-md font-semibold text-gray-700">เนื้อหา</label>
              <Textarea :id="`topic_content_${lesson.id}`" v-model="form.content" rows="6" autoResize
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></Textarea>
            </div>

            <div class="flex items-center w-full space-x-2">
              <label :for="`topic_min_read_${lesson.id}`" class=" block text-base font-semibold text-gray-700 ">
                เวลาในการอ่าน(นาที)
              </label>
              <input type="number" min="0" step="0.5" :id="`topic_min_read_${lesson.id}`" v-model="form.min_read" aria-describedby="helper-text-explanation"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                placeholder="0">
            </div>

            <div v-if="tempImages.length" class="grid grid-cols-3 gap-4">
              <div v-for="(image, index) in tempImages" :key="index" class="relative">
                <img :src="image.url" alt="Preview" class="w-full h-32 object-cover rounded-md border" />
                <button @click.prevent="removeImage(index)"
                  class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 hover:bg-red-600">
                  <Icon icon="heroicons:x-mark-20-solid" class="w-4 h-4" />
                </button>
              </div>
            </div>

            <div class="w-full flex">
              <div id="dropzone" @dragover.prevent="draging = true" @dragleave.prevent="draging = false"
                @drop.prevent="onDropFile" :class="draging ? 'bg-blue-200 border-blue-400' : 'bg-gray-200 border-gray-400'"
                class="relative w-full p-2 border-2 border-dashed rounded-lg">
                <div class="text-center">
                  <img class="w-8 h-8 mx-auto opacity-70" src="https://www.svgrepo.com/show/357902/image-upload.svg"
                    alt="" />

                  <h3 class="mt-2 text-sm font-medium text-gray-900">
                    <p class="relative">
                      <span>Drag and drop</span>
                      <button class="mx-2 plearnd-btn-primary w-36" @click.prevent="openImageSelector">
                        or browse
                      </button>
                      <span>to upload</span>
                    </p>
                    <input id="topic_images" type="file" multiple accept="image/*" ref="imageInput" class="hidden"
                      @change.prevent="handleImageUpload" />
                  </h3>
                  <p class="mt-1 text-xs text-gray-500">
                    PNG, JPG, GIF up to 4MB
                  </p>
                </div>
              </div>
            </div>
            <!-- <div>
              <label for="topic_images" class="block text-sm font-medium text-gray-700">รูปภาพ</label>
              <input type="file" id="topic_images" @change="handleImageUpload" multiple accept="image/*" class="hidden mt-1 w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
            </div> -->
            <div>
              <label :for="`topic_youtube_url_${lesson.id}`" class="block text-sm font-semibold text-gray-600 dark:text-white">ลิงค์ยูทูป</label>
              <Textarea :id="`topic_youtube_url_${lesson.id}`" v-model="form.youtube_url" rows="1" autoResize
              class="mt-1 block w-full rounded-md border-gray-300" ></Textarea>
            </div>
          </form>
        </div>

        <div class="flex justify-end space-x-2 p-4 bg-gray-50">
          <button @click="closeModal"
            class="px-4 py-2 text-sm font-medium text-red-700 bg-red-300 border border-gray-300 rounded-md hover:bg-red-400 hover:text-white">
            ยกเลิก
          </button>
          <button @click="submitFormHandler"
            class="px-2 py-2 flex items-center text-sm font-medium text-cyan-900 hover:text-white bg-cyan-500 rounded-md hover:bg-cyan-700">
            <Icon icon="svg-spinners:6-dots-rotate" class="w-6 h-6" v-if="isSubmitting" />
            <Icon icon="prime:save" class="w-6 h-6" v-else />
            บันทึก
          </button>
        </div>
      </div>
  </div>
  <!-- </div> -->
</template>
