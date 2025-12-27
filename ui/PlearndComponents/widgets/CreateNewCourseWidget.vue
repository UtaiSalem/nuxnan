<script setup>
import { ref, onMounted } from 'vue';
import { Icon } from '@iconify/vue';
import axios from 'axios';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';

import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue';

const authStore = useAuthStore();
const router = useRouter();

const openModal = ref(false);

const userCourses = ref(null);
const defaultCourseCode = ref('');
const defaultCourseName = ref('');
const defaultCourseDescription = ref('');
const defaultCoursePrice = ref(0);

const form = ref({
  code: defaultCourseCode.value,
  name: defaultCourseName.value,
  description: defaultCourseDescription.value,
  price: defaultCoursePrice.value,
});

async function testBtn(e){
  try {
    await axios.post('/courses', form.value);
    openModal.value = false;
    router.push('/courses');
  } catch (error) {
    console.error('Error creating course:', error);
  }
}

onMounted( async () => {
  if (authStore.user) {
    let userCourseResp = await axios.get(`users/${authStore.user.id}/courses`);
    userCourses.value = userCourseResp.data.user_courses;
  }
})

</script>

<template>
  <div class=" sm:max-w-sm md:max-w-sm lg:max-w-sm xl:max-w-sm sm:mx-auto md:mx-auto lg:mx-auto xl:mx-auto bg-white shadow-xl rounded-lg text-gray-900">
    <!-- <div class="rounded-t-lg h-32 overflow-hidden">
      <img class="object-cover object-top w-full" :src="defaultSchoolProfileCover" alt='school cover'>
    </div> -->
    <!-- <div class="mx-auto w-full h-full relative -mt-10 flex justify-center   overflow-hidden bg-cover object-center">
      <div class="avatar ">
        <div class="w-[75px] rounded-full border-primary border-4 bg-white">
          <img :src="defaultSchoolProfileImage" alt='school logo' />
        </div>
      </div>
      <img class="bg-cover object-center h-16" :src="defaultSchoolProfileImage" alt='school logo'>
    </div> -->

    <div class="p-4 border-t mt-2 flex flex-col justify-center items-center">
      <ul class="my-2" >
        <li v-for="(userCourse, index) in userCourses" :key="index">
          <Link :href="`/courses/${userCourse.id}`" class="py-1 my-1">{{ userCourse.name }}</Link>
        </li>
      </ul>
    </div>

    <div class="p-4 border-t mt-2 flex flex-col justify-center items-center">
      <button  @click.prevent="openModal = true"  class="inline-flex items-center justify-center py-1.5 gap-1 px-3 text-sm font-medium tracking-wide text-white transition duration-300 rounded-full focus-visible:outline-none whitespace-nowrap bg-violet-500 hover:bg-violet-600 focus:bg-violet-700 disabled:cursor-not-allowed disabled:border-violet-300 disabled:bg-violet-300 disabled:shadow-none">
        <span class="order-2">เพิ่มรายวิชา</span>
        <span class="relative only:-mx-3">
          <Icon icon="heroicons-outline:plus-circle" class="w-6 h-6"/>
        </span>
      </button>
    </div>

    <div v-if="openModal"
      class="fixed inset-0 w-full h-screen flex items-center justify-center bg-semi-75 z-30 bg-gray-600 bg-opacity-75">
      <div class=" w-2/5 mx-4 bg-white rounded-xl shadow-xl z-40">
        <div class="flex items-center justify-between w-full border-b-4 border-blue-500 mt-4 px-4 py-2">
          <h3 class="text-2xl">สร้างรายวิชาใหม่</h3>
          <button class=" text-red-400 relative -top-3 -right-2 cursor-pointer" @click.prevent="openModal = false">
            <Icon icon="heroicons-outline:x-circle" class="w-8 h-8" />
          </button>
        </div>
        <!-- <div class="rounded-t-lg h-32 overflow-hidden">
          <img class="object-cover object-top w-full" :src="defaultSchoolProfileCover" alt='school cover'>
        </div> -->
        <!-- <div class="mx-auto w-28 h-28 relative -mt-16 flex justify-center border-primary border-4 rounded-full  overflow-hidden">
          <img class="bg-cover object-center h-full" :src="defaultSchoolProfileImage" alt='school logo'>
        </div> -->

        <div class="m-4">
          <form @submit.prevent="testBtn" class="mt-6 space-y-2" id="create-new-course-form">

            <InputLabel for="course_code_input_form" value="รหัสวิชา" />
            <TextInput id="course_code_input_form" type="text" class="mt-0 w-full text-start" v-model="form.code" required />
            
            <InputLabel for="course_name_input_form" value="ชื่อวิชา" />
            <TextInput id="course_name_input_form" type="text" class="mt-0 w-full text-start" v-model="form.name" required />
            
            <InputLabel for="course_description_input_form" value="คำอธิบายรายวิชา" />
            <!-- <TextInput id="course_description" type="text" class="mt-0 w-full text-start" v-model="form.description" required autofocus autocomplete="course_code" /> -->
            <textarea id="course_description_input_form" v-model="form.description" rows="4" class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Write your thoughts here..."></textarea>

            <!-- <TextInput id="slogan_name" type="text" class="mt-1 block w-full text-center" v-model="form.schoolSlogan" required autofocus
              autocomplete="slogan_name" /> -->

            <div class="flex items-center justify-around">
              <button @click.prevent="openModal = false" class="px-6 py-2 text-white bg-red-400 hover:bg-red-500 hover:text-white  rounded">
                ยกเลิก
              </button>

              <button type="submit" class="px-6 py-2 text-gray-700 border bg-blue-500 hover:bg-blue-500 rounded-lg">
                บันทึก
              </button>

            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- ##################################################### -->

  </div>
</template>


