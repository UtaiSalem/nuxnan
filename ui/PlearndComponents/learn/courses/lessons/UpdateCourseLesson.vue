<script setup>
import { ref,reactive } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';
import { useObjectUrl } from '@vueuse/core';

import Textarea from 'primevue/textarea';

import TextInput from '@/Components/TextInput.vue';
// import CreateNewButton from '@/PlearndComponents/accessories/CreateNewButton.vue'
import BlueVioletButton from '@/PlearndComponents/accessories/BlueVioletButton.vue';
import LoadingPage from '@/PlearndComponents/accessories/LoadingPage.vue';
import IndividualLessonImageViewer from '@/PlearndComponents/learn/courses/lessons/images/IndividualLessonImageViewer.vue';

const props = defineProps({
  // course: Object,
  lesson: Object,
  // isCourseAdmin: Boolean,
  // courseMemberOfAuth: Object,
});

const pageData = usePage().props;
// const openCreateNewLessonModal = ref(true);
// const lessonsLength = ref(pageData.lessons.data.length + 1);

// const inputLesonImages = ref(null);
const inputLessonContent = ref(null);

const tempImages = reactive([]);
const isDragActive = ref(false);
const isLoadingPage = ref(false);

const emit = defineEmits(['lesson-updated']);

// const form = ref({
//   userID: pageData.auth.user.id,
//   courseID: pageData.course.data.id,
//   title: '',
//   description: '',
//   content: '',
//   youtube_url: '',
//   status: 1
// });
const form = ref({
  userID: pageData.auth.user.id,
  courseID: props.lesson.data.course_id,
  title: props.lesson.data.title,
  description: props.lesson.data.description,
  content: props.lesson.data.content,
  youtube_url: props.lesson.data.youtube_url,
  min_read: props.lesson.data.min_read??0,
  point_tuition_fee: props.lesson.data.point_tuition_fee??0,
  order: props.lesson.data.order??0,
  status: parseInt(props.lesson.data.status),
});

async function handleUpdateLessonFormSubmit(){
  try {
      if (form.value.title.trim().length>0 || form.value.description.trim().length>0) {
        
        isLoadingPage.value = true;

        const config = { headers: { 'Content-Type': 'multipart/form-data' } };
        let updateLessonForm = new FormData();
        updateLessonForm.append('courseId', form.value.courseID);
        updateLessonForm.append('title', form.value.title);
        updateLessonForm.append('description', form.value.description);
        updateLessonForm.append('content', form.value.content);
        // updateLessonForm.append('video_url', form.value.video_url);
        updateLessonForm.append('youtube_url', form.value.youtube_url??'');
        updateLessonForm.append('min_read', form.value.min_read??0);
        updateLessonForm.append('point_tuition_fee', form.value.point_tuition_fee??0);
        updateLessonForm.append('order', form.value.order??0);
        updateLessonForm.append('status', form.value.status);

        Array.from(tempImages).forEach((image)=>{
          updateLessonForm.append('images[]', image.file);
        });

        updateLessonForm.append('_method', 'PUT');

        let updateResp = await axios.post(`/courses/${pageData.course.data.id}/lessons/${props.lesson.data.id}`, updateLessonForm, config);

        if(updateResp.data.success){
          // console.log(updateResp.data.lesson);
          props.lesson.data.images = updateResp.data.lesson.images;
          tempImages.splice(0);
          
          Swal.fire(
              'เสร็จสมบูรณ์',
              'แก้ไขข้อมูลเสร็จเรียบร้อย',
              'success'
          )
        } else {
            Swal.fire(
                'ล้มเหลว',
                updateResp.data.message,
                'error'
            )
        }
      }
      isLoadingPage.value = false;
  } catch (error) {
    console.log(error);
    Swal.fire(
        'ล้มเหลว',
        'เกิดข้อผิดพลาด, <br />' + 'กรุณาลองใหม่อีกครั้ง',
        'error'
    );
    isLoadingPage.value = false;
  }
  // openCreateNewLessonModal.value = false;
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

async function deleteImage(index){
  props.lesson.data.images.splice(index, 1);
  Swal.fire({
    title: "ลบรูปภาพสำเร็จ",
    text: "รูปภาพนี้ของบทเรียนถูกลบออกจากระบบแล้ว",
    icon: 'success',
  });
}

function onCancleButtonClicked(){
  isLoadingPage.value = true;
  // route('course.lessons.show', [props.lesson.data.course_id,props.lesson.data.id] );
  router.visit(`/courses/${props.lesson.data.course_id}/lessons/${props.lesson.data.id}`);
}

function handleBackLink(){
    isLoadingPage.value = true;
    window.history.back();
}
</script>

<template>
    <!-- <div v-if="openCreateNewLessonModal" class="fixed inset-0 z-30 flex items-center justify-center w-full h-screen bg-gray-600 bg-opacity-75"> -->
    <LoadingPage v-if="isLoadingPage" />
    <button @click="handleBackLink" class="inline-flex items-center border bg-white border-indigo-300 px-3 py-1.5 rounded-md text-indigo-500 hover:bg-indigo-50">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18">
            </path>
        </svg>
        <span class="ml-1 text-lg font-bold">Back</span>
    </button>

    <div class="z-40 w-full bg-white shadow-xl rounded-xl">
      <div class="flex items-center justify-between w-full px-4 py-2 mt-4 border-b-4 border-blue-500">
        <div class="text-2xl">แก้ไขบทเรียน</div>
      </div>
      <div class="m-4">
      
        <h5 class="text-base font-semibold text-gray-500 dark:text-white ">
            ชื่อวิชา
        </h5>
        <TextInput id="course_name" type="text" disabled class="w-full p-2 mb-2 bg-gray-200" v-model="$page.props.course.data.name"/>
        <h5 class="text-base font-semibold text-gray-500 dark:text-white ">
            รหัสวิชา
        </h5>
        <TextInput id="course_code" type="text" disabled class="w-full p-2 mb-2 bg-gray-200" v-model="$page.props.course.data.code"/>

        <form @submit.prevent="handleUpdateLessonFormSubmit" class="pb-4 my-4 space-y-2" enctype="multipart/form-data">
          <h5 class="text-base font-semibold text-gray-500 dark:text-white "> ชื่อบทเรียน </h5>
          <TextInput id="lesson_name" type="text" class="block w-full mt-1 text-center" v-model="form.title" required />

          <label for="lesson_description" class="block text-sm font-semibold text-gray-600 dark:text-white">คำอธิบาย</label>
          <Textarea v-model="form.description" id="lesson_description" rows="4" autoResize
            class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
            :placeholder="form.description"></Textarea>

          <label for="lesson_content" class="block text-sm font-semibold text-gray-600 dark:text-white">เนื้อหา-รายละเอียด</label>
          <Textarea v-model="form.content" id="lesson_content" autoResize rows="10" ref="inputLessonContent"
            class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
            :placeholder="form.content"></Textarea>

          <div class="">
            <h5 class="text-sm font-semibold text-gray-500 dark:text-white ">
                รูปภาพ
            </h5>
          
            <div v-if="props.lesson.data.images" class="columns-1">
                <div v-for="(image, index) in props.lesson.data.images" :key="image.id" class="mt-4">
                    <IndividualLessonImageViewer :modelID="props.lesson.data.id" :image="image" @image-deleted="deleteImage(index)" />
                </div>
            </div>

            <div v-if="tempImages" class="">
              <div v-for="(image,index) in tempImages" :key="image.url" class="">
                <div class="relative mb-2 overflow-hidden max-h-fit">
                  <img :src="image.url" class="rounded-lg border" />
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
          </div>

          <div class="flex items-center w-full">
            <label for="assigned_groups_input" class="block text-base font-semibold text-gray-700 w-[50%]">
              เวลาในการอ่าน(นาที)
            </label>
            <input type="number" min="0" step="0.5" id="lesson_min_read_input" v-model="form.min_read" aria-describedby="helper-text-explanation"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-[50%] p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
              :placeholder="form.min_read">
          </div>

          <div class="flex items-center w-full">
            <label for="point_tuition_fees_input" class="block text-base font-semibold text-gray-700 w-[50%]">
              แต้มค่าธรรมเนียมการอ่าน(ต่อครั้ง)
            </label>
            <input type="number" min="0" id="point_tuition_fees_input" v-model="form.point_tuition_fee" aria-describedby="helper-text-explanation"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-[50%] p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
              :placeholder="form.point_tuition_fee??0">
          </div>

          <div class="flex items-center w-full">
            <label for="point_tuition_fees_input" class="block text-base font-semibold text-gray-700 w-[50%]">
              ลำดับที่ของบทเรียน
            </label>
            <input type="number" min="0" id="lesson_order_input" v-model="form.order" aria-describedby="helper-text-explanation"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-[50%] p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
              :placeholder="form.order??0">
          </div>

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


          <div class="flex justify-between">
            <button @click="handleBackLink" type="button" class="inline-flex items-center border bg-white border-indigo-300 px-3 py-1.5 rounded-md text-indigo-500 hover:bg-indigo-50">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18">
                    </path>
                </svg>
                <span class="ml-1 text-lg font-bold">Back</span>
            </button>

            <div class="flex items-center justify-end space-x-4 ">
                <button @click.prevent="onCancleButtonClicked" type="button"
                  class="px-6 py-2 bg-gray-300 rounded-lg text-primary hover:bg-gray-400 hover:text-white hover:scale-105">
                  ยกเลิก
                </button>

                <BlueVioletButton type="submit" class="hover:scale-105">บันทึก</BlueVioletButton>
            </div>
            
          </div>

        </form>
      </div>
    </div>
</template>
