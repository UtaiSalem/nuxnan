<script setup>
import { ref } from 'vue';
// import axios from 'axios';
import { useAuthStore } from '@/stores/auth';
import { Icon } from '@iconify/vue';
import TextInput from '@/Components/TextInput.vue';
import BlueVioletButton from '../accessories/BlueVioletButton.vue';
import CreateNewButton from '../accessories/CreateNewButton.vue';
import Swal from 'sweetalert2';

const authStore = useAuthStore();
const emit = defineEmits(['add-new-lesson']);

const props = defineProps({
  course: Object,
  lessons: Object,
});

const lessonsLength = ref(props.lessons.data.length + 1);
const openCreateNewLessonModal = ref(false);

const form = ref({
  userID: authStore.user?.id,
  courseID: props.course.data.id,
  title: 'บทเรียนที่ ' + lessonsLength.value,
  description: 'คำอธิบายบทเรียน เนื้อหา รายละอียด',
});

async function handleCreateLessonFormSubmit(){
  try {
      if (form.value.title.trim().length>0 || form.value.description.trim().length>0) {
      let newLessonResp = await axios.post(`/courses/${pageData.course.data.id}/lessons`, form.value);
      // pageData.lessons.data.push(newLesson.data);
      if(newLessonResp.data.success){
        emit('add-new-lesson', newLessonResp.data.newLesson);
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
        'เกิดข้อผิดพลาด,<br /> '+ error.response.data.message + '<br /> กรุณาลองใหม่อีกครั้ง',
        'error'
    )
  }
  openCreateNewLessonModal.value = false;
}

</script>
<template>
  <div>
    <div class="w-full">
      <div class="flex justify-center">
          <CreateNewButton @click.prevent="openCreateNewLessonModal = true">เพิ่มบทเรียนใหม่</CreateNewButton>
      </div>
    </div>

    <div v-if="openCreateNewLessonModal"
      class="fixed inset-0 w-full h-screen flex items-center justify-center z-30 bg-gray-600 bg-opacity-75">
      <div class="w-full md:w-3/5 mx-4 bg-white rounded-xl shadow-xl z-40">
        <div class="flex items-center justify-between w-full border-b-4 border-blue-500 mt-4 px-4 py-2">
          <h3 class="text-2xl">สร้างบทเรียนใหม่</h3>
          <button class="text-red-600 relative -top-3 -right-2 cursor-pointer hover:scale-105" @click.prevent="openCreateNewLessonModal = false">
            <Icon icon="heroicons:x-circle" class="w-8 h-8" />
          </button>
        </div>

        <div class="m-4">

          <TextInput type="text" disabled class="w-full p-2 mb-2" v-model="$page.props.course.data.name"/>
          <TextInput type="text" disabled class="w-full p-2 mb-2" v-model="$page.props.course.data.code"/>

          <form @submit.prevent="handleCreateLessonFormSubmit" class="mt-6 space-y-6">

            <TextInput id="lesson_name" type="text" class="mt-1 block w-full text-center" v-model="form.title" required autofocus
              autocomplete="lesson_name" />

            <!-- <label for="message" class="block  text-sm font-medium text-gray-900 dark:text-white">Your message</label> -->
            <textarea v-model="form.description" id="message" rows="4" class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" :placeholder="form.lessonDescription"></textarea>

            <div class="flex items-center justify-around">
              <button @click.prevent="openCreateNewLessonModal = false" class="px-6 py-2 text-primary bg-gray-300 hover:bg-gray-400 hover:text-white hover:scale-105 border border-primary rounded-lg">
                ยกเลิก
              </button>

              <!-- <button type="submit" class="px-6 py-2 text-white border bg-blue-400 hover:bg-blue-400-focus rounded-lg">
                บันทึก
              </button> -->
              <BlueVioletButton type="submit">บันทึก</BlueVioletButton>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

