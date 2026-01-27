<template>
  <div>   
    <div class="flex items-center justify-between w-full px-4 py-2">
      <h3 class="text-2xl">สร้างแหล่งเรียนรู้ใหม่</h3>
      <button class="text-red-600 cursor-pointer hover:bg-red-200 rounded-lg" @click.prevent="emit('close-modal')">
        <Icon icon="heroicons:x-mark-20-solid" class="w-7 h-7" />
      </button>
    </div>

    <form id="create-new-academy-form" class="relative flex flex-col justify-center space-y-6" enctype="multipart/form-data">

      <div class="relative max-h-40 overflow-hidden bg-cover m-0 p-0">
        <img class="object-cover object-top w-full m-0 p-0" :src="defaultSchoolProfileCover || pageData.props.profilePath + 'storage/assets/images/pages/01-page.png'" alt='school cover'>
        <div class="absolute top-2 left-2 flex flex-col">
          <input type="file" class="hidden" accept="image/*" ref="coverInput" @change="coverChange">
          <button type="button" @click.prevent="browse" class="text-white hover:bg-white hover:bg-opacity-50 hover:text-gray-600 transition duration-200 rounded-full p-2">
            <Icon icon="heroicons:camera" class="w-6 h-6" />
          </button>
        </div>
      </div>

      <div class=" absolute top-4 right-[calc(50%-56px)]">
        <div class="relative  w-28 h-28 flex justify-center items-center border-white border-4 rounded-full overflow-hidden">
          <img class="bg-cover object-center h-full" :src="defaultSchoolLogoImage || pageData.props.profilePath + 'storage/assets/images/school2.png'" alt='school logo'>
          <!-- <Icon icon="emojione:school" class="mx-auto w-28 h-28 -mt-28 border-4 border-white rounded-full overflow-hidden"/> -->
          <input type="file" class="hidden" accept="image/*" ref="logoInput" @change="logoChange">
          <button type="button" @click.prevent="browseLogo" class="absolute bottom-0 text-white bg-gray-400 hover:bg-opacity-80 hover:text-gray-600 transition duration-200 rounded-full p-1">
              <Icon icon="heroicons:camera" class="w-4 h-4 text-white" />
          </button>
        </div>
      </div>

      <div class="m-4 space-y-2">
          <!-- <InputLabel for="name" value="Name" /> -->

          <!-- <TextInput id="school_name" type="text" class="mt-1 block w-full text-center" v-model="form.name" required autofocus
            autocomplete="school_name" />

          <TextInput id="slogan_name" type="text" class="mt-1 block w-full text-center" v-model="form.slogan" required autofocus
            autocomplete="slogan_name" /> -->

            <!-- <div> -->
                <!-- <label class="text-gray-600 dark:text-gray-400">Bio</label> -->
          <!-- <textarea
              class="w-full py-3 border border-slate-200 rounded-lg px-3 focus:outline-none focus:border-slate-500 hover:shadow dark:bg-gray-600 dark:text-gray-100"
              name="bio" placeholder="ที่ตั้ง" v-model="form.address">
          </textarea> -->
            <!-- </div> -->

          <div class="flex items-center justify-around pb-4">
            <button type="button"  @click.prevent="emit('close-modal')"  class="px-6 py-2 bg-slate-200 hover:bg-red-300 hover:text-gray-800 border rounded-full">
              ยกเลิก
            </button>

            <!-- <button type="submit" class="px-6 py-2 text-white border bg-primary hover:bg-primary-focus rounded-lg">
              บันทึก
            </button> -->
            <button @click.prevent="formSubmitHandler" class="inline-flex items-center justify-center w-28 h-10 gap-2 px-5 text-sm font-medium tracking-wide text-white transition duration-300 rounded-full focus-visible:outline-none whitespace-nowrap bg-violet-500 hover:bg-violet-600 focus:bg-violet-700 disabled:cursor-not-allowed disabled:border-violet-300 disabled:bg-violet-300 disabled:shadow-none">
              <span>บันทึก</span><Icon icon="heroicons-outline:plus-circle" class="w-7 h-7"/> 
            </button>

          </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { Icon } from '@iconify/vue';
import { usePage, useForm } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';
const pageData = usePage();

const emit = defineEmits(['close-modal','add-new-academy']);

const defaultSchoolName = 'ตั้งชื่อแหล่งเรียนรู้';
const defaultSchoolSlogan = 'คำขวัญ, ปรัชญา, คติพจน์';
const defaultSchoolAddress = 'อ.จะนะ จ.สงขลา'
const defaultSchoolProfileCover = ref(null);
const defaultSchoolLogoImage = ref(null);

const form = ref({
  name: defaultSchoolName,
  slogan: defaultSchoolSlogan,
  address: defaultSchoolAddress,
  cover: null,
  logo: null
});

// const form = useForm({
//   name: defaultSchoolName,
//   slogan: defaultSchoolSlogan,
//   address: defaultSchoolAddress,
//   logo: null,
//   cover: null
// })

const isLoading = ref(false);
const coverInput = ref(null);
const logoInput = ref(null);

const browse = () => { coverInput.value.click() };
const browseLogo = () => { logoInput.value.click() };

const formSubmitHandler = async () => {
  isLoading.value = true;
  try {
    const config = { headers: { 'content-type': 'multipart/form-data' } }

    let data = new FormData();
    data.append('logo', form.value.logo);

    let resp = await axios.post('/academies', data, config);
    // emit('add-new-academy', resp.data.academy[0]);
    console.log(resp.data);
    // console.log(form);
    // console.log(data);

    // form.post('/academies');


  } catch (error) {
    console.error(error);
  }
  isLoading.value = false;
  emit('close-modal');
};



function coverChange(event){
  defaultSchoolProfileCover.value = URL.createObjectURL(event.target.files[0]);
  form.value.cover = event.target.files[0];
  // form.cover = event.target.files[0];
};

function logoChange(event) {
  defaultSchoolLogoImage.value = URL.createObjectURL(event.target.files[0]);
  form.value.logo = event.target.files[0];
  // form.logo = event.target.files[0];
};

</script>
