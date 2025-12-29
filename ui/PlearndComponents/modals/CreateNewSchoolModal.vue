<template>
  <div>
    <div class="flex items-center justify-between w-full px-4 py-2">
      <h3 class="text-2xl">สร้างแหล่งเรียนรู้ใหม่</h3>
      <button class="text-red-600 cursor-pointer hover:bg-red-200 rounded-lg" @click.prevent="emit('close-modal')">
        <Icon icon="heroicons:x-mark-20-solid" class="w-7 h-7" />
      </button>
    </div>

    <form id="create-new-academy-form" @submit.prevent="formSubmit" class="relative flex flex-col justify-center space-y-3" enctype="multipart/form-data">

      <div class="relative max-h-40 overflow-hidden bg-cover m-0 p-0">
        <img class="object-cover object-top w-full m-0 p-0"
          :src="tempCover || pageData.props.profilePath + 'storage/images/covers/default_cover.png'" alt='school cover'>
        <div class="absolute top-2 left-2 flex flex-col">
          <input type="file" class="hidden" accept="image/*" ref="coverInput" @change="onCoverInputChange">
          <button type="button" @click.prevent="browseCover"
            class="text-white hover:bg-white hover:bg-opacity-50 hover:text-gray-600 transition duration-200 rounded-full p-2">
            <Icon icon="heroicons:camera" class="w-6 h-6" />
          </button>
        </div>
      </div>

      <div class=" absolute top-4 right-[calc(50%-56px)]">
        <div class="relative  w-28 h-28 flex justify-center items-center border-white border-4 rounded-full overflow-hidden">
          <img class="bg-cover object-center h-full"
            :src="tempLogo || pageData.props.profilePath + 'storage/images/logos/default_logo.png'" alt='school logo'>
          <input type="file" class="hidden" accept="image/*" ref="logoInput" @change="onLogoInputChange">
          <button type="button" @click.prevent="browseLogo"
            class="absolute bottom-0 text-white bg-gray-400 hover:bg-opacity-80 hover:text-gray-600 transition duration-200 rounded-full p-1">
            <Icon icon="heroicons:camera" class="w-4 h-4 text-white" />
          </button>
        </div>
      </div>
      
      <div class="m-4 space-y-2">
          <TextInput id="school_name" type="text" class="mt-1 block w-full text-center" v-model="form.name" required 
            autocomplete="school_name" placeholder="ชื่อสถานศึกษา" />

          <TextInput id="slogan_name" type="text" class="mt-1 block w-full text-center" v-model="form.slogan" required 
            autocomplete="slogan_name" placeholder="คำขวัญ, ปณิธาน" />
          <textarea
              class="w-full py-3 border border-slate-200 rounded-lg px-3 focus:outline-none focus:border-slate-500 hover:shadow dark:bg-gray-600 dark:text-gray-100"
              name="bio" placeholder="สถานที่ตั้ง" v-model="form.address">
          </textarea>
      </div>
      <div class="flex items-center justify-around pb-4">
        <button type="button"  @click.prevent="emit('close-modal')"  class="px-6 py-2 bg-slate-200 hover:bg-red-300 hover:text-gray-800 border rounded-full">
          ยกเลิก
        </button>
        <button type="submit" class="inline-flex items-center justify-center w-28 h-10 gap-2 px-5 text-sm font-medium tracking-wide text-white transition duration-300 rounded-full focus-visible:outline-none whitespace-nowrap bg-violet-500 hover:bg-violet-600 focus:bg-violet-700 disabled:cursor-not-allowed disabled:border-violet-300 disabled:bg-violet-300 disabled:shadow-none">
          <span>บันทึก</span><Icon icon="heroicons-outline:plus-circle" class="w-7 h-7"/> 
        </button>
      </div>
    </form>

  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { Icon } from '@iconify/vue';
import { usePage } from '@inertiajs/vue3';
const pageData = usePage();
import TextInput from '@/Components/TextInput.vue';

const emit = defineEmits(['close-modal','add-new-academy']);

const coverInput =ref(null);
const logoInput =ref(null);
const tempCover = ref(null);
const tempLogo = ref(null);
const isLoading = ref(false);

const form = ref({
  name:'', 
  slogan:'', 
  address:'', 
  cover:null, 
  logo:null,  
});

const browseCover = () => { coverInput.value.click() };
const browseLogo = () => { logoInput.value.click() };

function onCoverInputChange(event){
  form.value.cover = event.target.files[0];
  tempCover.value = URL.createObjectURL(event.target.files[0]);
}
function onLogoInputChange(event){
  form.value.logo = event.target.files[0];
  tempLogo.value = URL.createObjectURL(event.target.files[0]);
}

async function formSubmit(e) {
  e.preventDefault();
  isLoading.value = true;

  const config = { headers: { 'content-type': 'multipart/form-data' } }

  const data = new FormData();
  
  data.append('name', form.value.name);
  data.append('slogan', form.value.slogan);
  data.append('address', form.value.address);

  form.value.logo ? data.append('logo', form.value.logo) : null;
  form.value.cover ? data.append('cover', form.value.cover): null;

  await axios.post('/academies/upload', data, config)
      .then(function (res) {
          emit('add-new-academy', res.data.academy[0]);
      })
      .catch(function (err) {
          // Handle error silently
      });

  isLoading.value = false;
  emit('close-modal');

}
      
</script>
