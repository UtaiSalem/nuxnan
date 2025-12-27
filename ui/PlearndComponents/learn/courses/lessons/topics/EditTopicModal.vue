
<script setup>
import { ref } from 'vue'
import Textarea from 'primevue/textarea';
import { Icon } from '@iconify/vue';
import { useObjectUrl } from '@vueuse/core';

const props = defineProps({
  topic: Object,
});

const emit = defineEmits(['close', 'update-topic']);
const imageInput = ref(null);
const isSubmitting = ref(false);

const imageIndex = ref(null);
const isDeletingImage = ref(false);

const tempImages = ref([]);

const closeModal = () => {
  handleCancle();
  emit('close');
};

const draging = ref(false);
const openImageSelector = () => imageInput.value.click();

// const toggleActive = () => draging.value = !draging.value;
const deleteTempImage = (index) => tempImages.value.splice(index,1);

const form = ref({
  title: props.topic.title,
  content: props.topic.content,
  min_read: props.topic.min_read,
  youtube_url: props.topic.youtube_url,
  images: props.topic.images,
});

async function submitFormHandler(){
  try {
    isSubmitting.value = true;
    const formData = new FormData();
    formData.append('_method', 'put');
    formData.append('title', form.value.title);
    formData.append('content', form.value.content);
    formData.append('min_read', form.value.min_read);
    formData.append('youtube_url', form.value.youtube_url);
    tempImages.value.forEach((image) => {
      formData.append('images[]', image.file);
    });


    const topicResp = await axios.post(`/lessons/${props.topic.lesson_id}/topics/${props.topic.id}`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      }
    });

    if (topicResp.status === 200) {
        emit('update-topic', topicResp.data.topic);
    }

  } catch (error) {
    console.log(error);
  } finally {
    isSubmitting.value = false;
    closeModal();
  }
}


function handleDrop(e){
  draging.value = false;
  Array.from(e.dataTransfer.files).forEach(image => {
    tempImages.value.push({ file: image, url: useObjectUrl(image)});
  });
}

function handleInput(e){
  draging.value = false;
  Array.from(e.target.files).forEach(image => {
    tempImages.value.push({ file: image, url: useObjectUrl(image)});
  });
}

const handleCancle = () => {
  form.value.images = [];
  form.value.title = '';
  form.value.content = '';
}

const removeImage = async (index) => {
    try {
        isDeletingImage.value = true;
        imageIndex.value = index;

        let delResp = await axios.delete(`/topics/${props.topic.id}/images/${index}`);
        if (delResp.status === 200){
            props.topic.images.splice(index, 1);
        }
    } catch (error) {
        console.log(error);        
    } finally {
        isDeletingImage.value = false;
        imageIndex.value = null;
    }

};

</script>
<template>
  <div class="fixed inset-0 z-30 py-14 transition-opacity bg-gray-800 bg-opacity-80 backdrop-blur-lg overflow-y-auto">

    <div class="relative bg-white rounded-lg max-w-xl w-full mx-auto border-t-4 border-blue-500">
      <div class="flex justify-between items-center p-4 bg-blue-200">
        <h3 class="text-lg font-bold text-blue-900">แก้ไขหัวข้อในบทเรียน</h3>
        <button @click="closeModal" class="text-gray-600 hover:text-red-700">
          <Icon icon="heroicons:x-mark-20-solid" class="w-6 h-6" />
        </button>
      </div>

      <div class="p-2">
        <form @submit.prevent="submitFormHandler" class="space-y-4">
            <div>
                <label :for="`topic_title_${topic.id}`" class="block text-md font-semibold text-gray-700">ชื่อหัวข้อ</label>
                <Textarea 
                :id="`topic_title_${topic.id}`" 
                v-model="form.title"
                rows="1"
                autoResize
                class="mt-1 block w-full rounded-md border-gray-300" 
                required 
                ></Textarea>
            </div>

            <div>
                <label :for="`topic_content_${topic.id}`" class="block text-md font-semibold text-gray-700">เนื้อหา</label>
                <Textarea 
                :id="`topic_content_${topic.id}`" 
                v-model="form.content" 
                rows="10"
                autoResize
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" 
                ></Textarea>

            </div>

            <div class="flex items-center w-full space-x-2">
              <label :for="`topic_min_read_${topic.id}`" class=" block text-base font-semibold text-gray-700 ">
                เวลาในการอ่าน(นาที)
              </label>
              <input type="number" min="0" step="0.5" :id="`topic_min_read_${topic.id}`" v-model="form.min_read" aria-describedby="helper-text-explanation"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                placeholder="0">
            </div>

            <div v-if="topic.images.length || tempImages.length" class="grid grid-cols-3 gap-2">
                <div v-for="(image, index) in topic.images" :key="index" class="relative">
                    <img :src="image.image_url" alt="Preview" class="rounded-md border w-full h-32 object-cover" />
                    <button @click.prevent="removeImage(index)" :disabled="isDeletingImage" class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 hover:bg-red-600">
                        <Icon icon="heroicons:x-mark-20-solid" class="w-4 h-4" />
                    </button>
                    <!-- icon loading at the middle of image if isDeleting an image -->
                    <Icon
                        icon="svg-spinners:6-dots-rotate" 
                        v-if="isDeletingImage && imageIndex === index"
                        class="w-10 h-10 absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-white" 
                    />

                </div>
                <div v-for="(image,index) in tempImages" :key="image.url" class="relative">
                    <img :src="image.url" class="rounded-md border w-full h-32 object-cover" alt="Preview" />
                    <button v-if="$page.props.isCourseAdmin" @click.prevent="deleteTempImage(index)"
                        class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 hover:bg-red-600">
                        <Icon icon="heroicons:x-mark-20-solid" class="w-4 h-4 " />
                    </button>
                    <div v-if="isDeletingImage && imageIndex === index"
                        class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                        <Icon icon="heroicons:loader" class="w-4 h-4 text-white animate-spin" />
                    </div>
                </div>
            </div>
            <!-- <div v-if="tempImages" class="">
              <div v-for="(image,index) in tempImages" :key="image.url" class="">
                <div class="relative mb-2 overflow-hidden max-h-fit">
                  <img :src="image.url" class="rounded-md" />
                  <button v-if="$page.props.isCourseAdmin" @click.prevent="deleteTempImage(index)"
                    class="absolute flex items-center justify-around w-8 p-2 bg-gray-200 rounded-full cursor-pointer top-1 right-2">
                    <Icon icon="fa-solid:trash-alt" class="w-4 h-4 text-red-500 " />
                  </button>
                </div>
              </div>
            </div> -->
            <div class="w-full flex">
              <div id="dropzone" 
                  @dragover.prevent="draging = true"
                  @dragleave.prevent="draging = false"
                  @drop.prevent="handleDrop" 
                  :class="draging ? 'bg-blue-200 border-blue-400': 'bg-gray-200 border-gray-400'" 
                  class="relative w-full p-2 border-2 border-dashed rounded-lg">
                  <div class="text-center">
                      <img class="w-8 h-8 mx-auto opacity-70"
                          src="https://www.svgrepo.com/show/357902/image-upload.svg" alt="" />

                      <h3 class="mt-2 text-sm font-medium text-gray-900">
                        <p class="relative">
                            <span>Drag and drop</span>
                            <button class="mx-2 plearnd-btn-primary w-36" @click.prevent="openImageSelector">
                                or browse
                            </button>
                            <span>to upload</span>
                        </p>
                        <input 
                            id="topic_images" 
                            type="file" 
                            multiple accept="image/*"
                            ref="imageInput"
                            class="hidden" 
                            @input="handleInput"
                        />
                            <!-- @change.prevent="handleChange" -->
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
            <label :for="`topic_youtube_url_${topic.id}`" class="block text-sm font-semibold text-gray-600 dark:text-white">ลิงค์ยูทูป</label>
            <Textarea :id="`topic_youtube_url_${topic.id}`" v-model="form.youtube_url" rows="1" autoResize
            class="mt-1 block w-full rounded-md border-gray-300" ></Textarea>
          </div>

        </form>
      </div>

      <div class="flex justify-end space-x-2 p-4 bg-gray-50">
        <button @click="closeModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
          ยกเลิก
        </button>
        <button @click="submitFormHandler" class="px-2 py-2 flex items-center text-sm font-medium text-white bg-cyan-600 rounded-md hover:bg-cyan-700">
          <!-- icon submitting indicator if is submitting -->
          <Icon icon="svg-spinners:6-dots-rotate" class="w-6 h-6" v-if="isSubmitting" />
          <!-- icon save if is not submitting -->
          <Icon icon="prime:save" class="w-6 h-6" v-else />
          บันทึก
        </button>
      </div>
    </div>
  </div>
</template>


