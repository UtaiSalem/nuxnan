<script setup>
import { ref,reactive } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';
import { useObjectUrl } from '@vueuse/core';

import TextInput from '@/Components/TextInput.vue';
import Textarea from 'primevue/textarea';
import CreateNewButton from '@/PlearndComponents/accessories/CreateNewButton.vue'
import BlueVioletButton from '@/PlearndComponents/accessories/BlueVioletButton.vue';

const pageData = usePage().props;
const openCreateNewLessonModal = ref(false);
const lessonsLength = ref(pageData.lessons.data.length + 1);

// const inputLesonImages = ref(null);
const tempImages = reactive([]);
const isDragActive = ref(false);

const emit = defineEmits(['creating-new-lesson', 'add-new-lesson']);

const form = ref({
  courseID: pageData.course.data.id,
  title: '',
  description: '',
  content: '',
  youtube_url: '',
  min_read: 0,
  point_tuition_fee: 0,
  order: lessonsLength.value,
  status: 1,
});

// const browseInputLessonImages = () => inputLessonImages.value.click();
// function onInputImageChange(e){
//     Array.from(e.target.files).forEach(image => {
//         tempImages.push({ file: image, url: useObjectUrl(image)});
//     });
// }

async function handleCreateLessonFormSubmit(){
  try {
      if (form.value.title.trim().length>0 || form.value.description.trim().length>0) {
        
        emit('creating-new-lesson');
        
        const config = { headers: { 'Content-Type': 'multipart/form-data' } };
        let newLessonForm = new FormData();
        newLessonForm.append('courseId', form.value.courseID);
        newLessonForm.append('title', form.value.title);
        newLessonForm.append('description', form.value.description);
        newLessonForm.append('content', form.value.content);
        newLessonForm.append('youtube_url', form.value.youtube_url??'');
        newLessonForm.append('point_tuition_fee', form.value.point_tuition_fee??0);
        newLessonForm.append('min_read', form.value.min_read??0);
        newLessonForm.append('order', form.value.order??0);
        newLessonForm.append('status', form.value.status??1);

        Array.from(tempImages).forEach((image)=>{
          newLessonForm.append('images[]', image.file);
        });

        let newLessonResp = await axios.post(`/courses/${pageData.course.data.id}/lessons`, newLessonForm, config);

        if(newLessonResp.data.success){

          emit('add-new-lesson', newLessonResp.data.newLesson);

          form.value.title       = '';
          form.value.description = '';
          form.value.content     = '';
          form.value.youtube_url = '';
          form.value.status      = 1;
          tempImages.splice(0);
          Swal.fire(
              'เสร็จสมบูรณ์',
              'เพิ่มบทเรียนใหม่เสร็จเรียบร้อย',
              'success'
          )
        }
      }
  } catch (error) {
    Swal.fire(
        'ล้มเหลว',
        'เกิดข้อผิดพลาด, <br />' + 'กรุณาลองใหม่อีกครั้ง',
        'error'
    )
  }
  openCreateNewLessonModal.value = false;
}

const toggleActive = () => isDragActive.value = !isDragActive.value;
const deleteTempImage = (index) => tempImages.splice(index,1);

function handleDrop(e){
  isDragActive.value = false;
  Array.from(e.dataTransfer.files).forEach(image => {
    tempImages.push({ file: image, url: useObjectUrl(image)});
  });
  // console.log(e.dataTransfer.files);
}

function handleChange(e){
  isDragActive.value = false;
  Array.from(e.target.files).forEach(image => {
    tempImages.push({ file: image, url: useObjectUrl(image)});
    // tempImages.push({ file: image, url: URL.createObjectURL(image)});
  });
}

</script>

<template>
    <!-- <div v-if="openCreateNewLessonModal" class="fixed inset-0 z-30 flex items-center justify-center w-full h-screen bg-gray-600 bg-opacity-75"> -->
    <div v-if="openCreateNewLessonModal" class="z-40 w-full bg-white shadow-xl rounded-xl">
      <div class="flex items-center justify-between w-full px-4 py-2 mt-4 border-b-4 border-blue-500">
        <div class="text-2xl">สร้างบทเรียนใหม่</div>
      </div>
      <div class="m-2">
      
        <h5 class="text-base font-semibold text-gray-500 dark:text-white ">
            ชื่อวิชา
        </h5>
        <TextInput id="course_name" type="text" disabled class="w-full p-2 mb-2 bg-gray-200" v-model="$page.props.course.data.name"/>
        <h5 class="text-base font-semibold text-gray-500 dark:text-white ">
            รหัสวิชา
        </h5>
        <TextInput id="course_code" type="text" disabled class="w-full p-2 mb-2 bg-gray-200" v-model="$page.props.course.data.code"/>

        <form @submit.prevent="handleCreateLessonFormSubmit" class="pb-4 my-4 space-y-2" enctype="multipart/form-data">
          <h5 class="text-base font-semibold text-gray-500 dark:text-white ">
              ชื่อบทเรียน
          </h5>
          <TextInput id="lesson_name" type="text" class="block w-full mt-1 text-center" v-model="form.title" required />

          <label for="lesson_description" class="block text-sm font-semibold text-gray-600 dark:text-white">คำอธิบาย</label>
          <Textarea v-model="form.description" id="lesson_description" rows="4" autoResize
            class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
            :placeholder="form.description"></Textarea>

          <label for="lesson_content" class="block text-sm font-semibold text-gray-600 dark:text-white">เนื้อหา-รายละเอียด</label>
          <Textarea v-model="form.content" id="lesson_content" rows="10" autoResize
            class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
            :placeholder="form.content"></Textarea>

          <div class="">
            <h5 class="text-sm font-semibold text-gray-500 dark:text-white ">
                รูปภาพ
            </h5>
            <div v-if="tempImages" class="">
              <div v-for="(image,index) in tempImages" :key="image.url" class="">
                <div class="relative mb-2 overflow-hidden max-h-fit">
                  <img :src="image.url" class="rounded-lg" />
                  <button v-if="$page.props.isCourseAdmin" @click.prevent="deleteTempImage(index)"
                    class="absolute flex items-center justify-around w-8 p-2 bg-gray-200 rounded-full cursor-pointer top-1 right-2">
                    <Icon icon="fa-solid:trash-alt" class="w-4 h-4 text-red-500 " />
                  </button>
                </div>
              </div>
            </div>
            
            <div id="dropzone" class="relative w-full p-6 border-2 border-gray-300 border-dashed rounded-lg"
              :class="{ 'bg-blue-100 border-blue-300 dark:bg-green-700': isDragActive }"
              @dragenter.prevent="toggleActive" 
              @dragleave.prevent="toggleActive" 
              @dragover.prevent=""
              @drop.prevent="handleDrop">

              <div class="text-center">
                <!-- <img class="w-12 h-12 mx-auto" src="https://www.svgrepo.com/show/357902/image-upload.svg" alt=""> -->
                <Icon icon="uil:image-upload" class="w-12 h-12 mx-auto text-gray-600" />
                <h3 class="mt-2 text-sm font-medium text-gray-900">
                  <label for="files-upload" class="relative cursor-pointer">
                    <span>Drag and drop</span>
                    <span class="text-indigo-600"> or browse</span>
                    <span> to upload</span>
                  </label>
                  <input id="files-upload" name="lesson_images" @change="handleChange" accept="image/*" type="file" :max-file-size="300000" multiple class="sr-only">
                </h3>
                <p class="mt-1 text-xs text-gray-500">
                  PNG, JPG, GIF up to 4MB
                </p>
              </div>

            </div>
          </div>

          <!-- <div>
            <label class="block text-base font-semibold text-text-gray-700">
              ลิงค์วีดิโอ
            </label>
            <input type="text" v-model="form.video_url" id="">
          </div> -->
          <div>
            <label for="lesson_youtube_link" class="block text-sm font-semibold text-gray-600 dark:text-white">ลิงค์ยูทูป</label>
            <TextInput id="lesson_youtube_link" type="text" v-model="form.youtube_url" class="block w-full mt-1" />
            <!-- <TextInput id="lesson_name" type="text" class="block w-full mt-1 text-center" v-model="form.title" required
            autocomplete="lesson_name" /> -->
          </div>

          <!-- <div class="flex items-center w-full">
            <label for="assigned_groups_input" class="block text-base font-semibold text-gray-700 w-[50%]">
              เวลาในการอ่าน(นาที)
            </label>
            <input type="number" min="0" step="0.5" id="lesson_min_read_input" v-model="form.min_read" aria-describedby="helper-text-explanation"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-[50%] p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
              placeholder="0">
          </div> -->
          
          <div class="flex items-center w-full">
            <label for="point_tuition_fees_input" class="block text-base font-semibold text-gray-700 w-[50%]">
              แต้มค่าธรรมเนียมการเรียนรู้
            </label>
            <input type="number" min="0" id="point_tuition_fees_input"  v-model="form.point_tuition_fee" aria-describedby="helper-text-explanation"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-[50%] p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
              placeholder="0">

          </div>

          <div class="flex items-center w-full">
            <label for="point_tuition_fees_input" class="block text-base font-semibold text-gray-700 w-[50%]">
              ลำดับที่ของบทเรียน
            </label>
            <input type="number" min="0" id="lesson_order_input" v-model="form.order" aria-describedby="helper-text-explanation"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-[50%] p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
              placeholder="0">
          </div>

          <!-- <div class="flex items-center w-full">
            <label for="assigned_groups_input" class="block text-base font-semibold text-gray-700 w-[50%]">
              ลำดับที่ของบทเรียน
            </label>
            <div class="min-w-f  text-xl font-semibold" id="assigned_groups_input">
              <div class="flex items-center space-x-2 rounded p-2 hover:bg-gray-100 accent-teal-600">
                <input type="checkbox" id="htmlCheckbox" name="languageCheckbox" class="h-4 w-4 rounded border-gray-300 text-teal-600 shadow-sm focus:border-teal-300 focus:ring focus:ring-teal-200 focus:ring-opacity-50 focus:ring-offset-0 disabled:cursor-not-allowed disabled:text-gray-400" />
                <label for="htmlCheckbox" class="flex w-full space-x-2 text-sm"> HTML </label>
              </div>
            </div>
          </div> -->

          <!-- Component: Secondary checkboxes -->
          <div class="flex items-center gap-4">
            <h4 class="text-base font-semibold text-gray-500 dark:text-white ">สถานะ</h4>
            <div class="relative flex items-center">
              <input class="w-4 h-4 transition-colors bg-white border-2 rounded-full appearance-none cursor-pointer focus-visible:outline-none peer border-slate-500 checked:border-violet-500 checked:bg-violet-200 checked:hover:border-violet-600 checked:hover:bg-violet-300 focus:outline-none checked:focus:border-violet-700 checked:focus:bg-violet-400 disabled:cursor-not-allowed disabled:border-slate-100 disabled:bg-slate-50" type="radio" :value="1" id="huey2" v-model="form.status"  />
              <label class="pl-2 cursor-pointer text-slate-500 peer-disabled:cursor-not-allowed peer-disabled:text-slate-400" for="huey2">
                เผยแพร่
              </label>
              <svg class="absolute left-0 w-4 h-4 transition-all duration-300 scale-50 opacity-0 pointer-events-none fill-violet-500 peer-checked:scale-100 peer-checked:opacity-100 peer-hover:fill-violet-600 peer-focus:fill-violet-700 peer-disabled:cursor-not-allowed" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-labelledby="title-01 description-01" role="graphics-symbol">
                <circle cx="8" cy="8" r="4" />
              </svg>
            </div>
            <div class="relative flex items-center">
              <input class="w-4 h-4 transition-colors bg-white border-2 rounded-full appearance-none cursor-pointer focus-visible:outline-none peer border-slate-500 checked:border-violet-500 checked:bg-violet-200 checked:hover:border-violet-600 checked:hover:bg-violet-300 focus:outline-none checked:focus:border-violet-700 checked:focus:bg-violet-400 disabled:cursor-not-allowed disabled:border-slate-100 disabled:bg-slate-50" type="radio" :value="0" id="dewey2" v-model="form.status" />
              <label class="pl-2 cursor-pointer text-slate-500 peer-disabled:cursor-not-allowed peer-disabled:text-slate-400" for="dewey2">
                ร่าง
              </label>
              <svg class="absolute left-0 w-4 h-4 transition-all duration-300 scale-50 opacity-0 pointer-events-none fill-violet-500 peer-checked:scale-100 peer-checked:opacity-100 peer-hover:fill-violet-600 peer-focus:fill-violet-700 peer-disabled:cursor-not-allowed" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-labelledby="title-02 description-02" role="graphics-symbol">
                <circle cx="8" cy="8" r="4" />
              </svg>
            </div>
          </div>
          <!-- End Secondary checkboxes -->

          <div class="flex items-center justify-end space-x-4 ">
            <div class="">
              <button @click.prevent="openCreateNewLessonModal = false"
                class="px-6 py-2 bg-gray-300 rounded-lg  text-primary hover:bg-gray-400 hover:text-white hover:scale-105">
                ยกเลิก
              </button>
            </div>
            <div>
              <BlueVioletButton type="submit" class="hover:scale-105">บันทึก</BlueVioletButton>
            </div>
          </div>
        </form>
      </div>
    </div>


    <div class="w-full plearnd-card" v-if="!openCreateNewLessonModal">
      <div class="flex justify-center">
          <CreateNewButton  @click.prevent="() => openCreateNewLessonModal=true">เพิ่มบทเรียนใหม่</CreateNewButton>
      </div>
    </div>

</template>
