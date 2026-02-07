<template>
  <div class="bg-white dark:bg-gray-800 p-4 shadow rounded-lg mb-4">
      <div class="flex gap-4">
          <div class="flex-shrink-0">
               <img :src="user.avatar" class="w-10 h-10 rounded-full" alt="User Avatar">
          </div>
          <div class="flex-grow">
              <textarea 
                  v-model="content"
                  class="w-full border-none focus:ring-0 bg-transparent text-gray-900 dark:text-white placeholder-gray-500 resize-none text-lg" 
                  rows="2"
                  placeholder="เขียนอะไรบางอย่างถึงสถาบัน..."
              ></textarea>

              <!-- Image Previews -->
              <div v-if="imagePreviews.length" class="grid grid-cols-2 gap-2 mt-2 mb-2">
                  <div v-for="(img, index) in imagePreviews" :key="index" class="relative group">
                      <img :src="img" class="w-full h-48 object-cover rounded-lg border dark:border-gray-700">
                      <button 
                          @click="removeImage(index)"
                          class="absolute top-2 right-2 bg-gray-900/50 hover:bg-gray-900/80 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"
                      >
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                          </svg>
                      </button>
                  </div>
              </div>

              <div class="border-t dark:border-gray-700 mt-2 pt-2 flex justify-between items-center">
                  <div class="flex gap-2">
                       <button 
                          @click="$refs.fileInput.click()"
                          class="p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full transition-colors"
                          title="เพิ่มรูปภาพ"
                       >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                       </button>
                       <input 
                          type="file" 
                          ref="fileInput" 
                          multiple 
                          accept="image/*" 
                          class="hidden"
                          @change="handleFileSelect"
                       >
                  </div>
                  <div class="flex items-center gap-2">
                        <span v-if="content.length > 1000" class="text-xs text-red-500">{{ content.length }}/1000</span>
                        <button 
                            @click="createPost" 
                            :disabled="isLoading || (!content.trim() && !images.length) || content.length > 1000"
                            class="bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 text-white px-6 py-1.5 rounded-full font-medium transition-colors flex items-center gap-2"
                        >
                            <span v-if="isLoading">โพสต์...</span>
                            <span v-else>โพสต์</span>
                        </button>
                  </div>
              </div>
          </div>
      </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    academy_id: Number
});

const emit = defineEmits(['post-created']);

const page = usePage();
const user = computed(() => page.props.auth.user);

const content = ref('');
const images = ref([]);
const imagePreviews = ref([]);
const isLoading = ref(false);
const fileInput = ref(null);

const handleFileSelect = (event) => {
    const files = Array.from(event.target.files);
    if (images.value.length + files.length > 4) {
        Swal.fire({
            icon: 'error',
            title: 'จำกัดรูปภาพ',
            text: 'สามารถอัปโหลดรูปภาพได้สูงสุด 4 รูปต่อโพสต์',
            timer: 3000,
            showConfirmButton: false
        });
        return;
    }

    files.forEach(file => {
        if (file.type.match('image.*')) {
            images.value.push(file);
            const reader = new FileReader();
            reader.onload = (e) => imagePreviews.value.push(e.target.result);
            reader.readAsDataURL(file);
        }
    });
    
    // Reset input so same file can be selected again if needed (though we handle multiples)
    event.target.value = '';
};

const removeImage = (index) => {
    images.value.splice(index, 1);
    imagePreviews.value.splice(index, 1);
};

const createPost = async () => {
    if ((!content.value.trim() && !images.value.length) || isLoading.value) return;

    isLoading.value = true;

    try {
        const formData = new FormData();
        formData.append('content', content.value);
        images.value.forEach((image, index) => {
            formData.append(`images[${index}]`, image);
        });

        const response = await axios.post(`/api/academies/${props.academy_id}/posts`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        if (response.data.success) {
            // content.value = '';
            // images.value = [];
            // imagePreviews.value = [];
            resetForm();
            emit('post-created', response.data.post);
            
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
            Toast.fire({
                icon: 'success',
                title: 'โพสต์สำเร็จแล้ว'
            });
        }
    } catch (error) {
        console.error('Error creating post:', error);
        Swal.fire({
            icon: 'error',
            title: 'ผิดพลาด',
            text: error.response?.data?.message || 'ไม่สามารถสร้างโพสต์ได้ กรุณาลองใหม่',
        });
    } finally {
        isLoading.value = false;
    }
};

const resetForm = () => {
    content.value = '';
    images.value = [];
    imagePreviews.value = [];
};
</script>
