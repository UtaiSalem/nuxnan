<template>
  <div
    class=" bg-white shadow-xl rounded-lg text-gray-900 overflow-hidden">

    <!-- <div class=" relative w-full h-full grid justify-center content-center bg-cover py-2" :style="`background-image: url(${'/storage/assets/images/pages/01-page.png'})`">
        <div class="w-fit rounded-[25px]  ">
            <div class="  ">
              <Icon icon="emojione:school" class="w-20 h-20 border-4 border-white rounded-full"/>
            </div>
        </div>
    </div> -->
    <div class="rounded-t-lg h-20 overflow-hidden">
        <img class="object-cover object-top w-full" :src="defaultSchoolProfileCover" alt='Mountain'>
    </div>
    <!-- <div class="mx-auto w-16 h-16 relative -mt-8 border-4 border-white rounded-full overflow-hidden "> -->
        <!-- <img class="object-cover object-center h-16" src='https://images.unsplash.com/photo-1438761681033-6461ffad8d80?ixlib=rb-1.2.1&q=80&fm=jpg&crop=entropy&cs=tinysrgb&w=400&fit=max&ixid=eyJhcHBfaWQiOjE0NTg5fQ' alt='Woman looking front'> -->
    <Icon icon="emojione:school" class="mx-auto w-16 h-16 relative -mt-8 border-4 border-white rounded-full overflow-hidden"/>
    <!-- </div> -->

    <div class="pt-4 mx-auto flex flex-col justify-center items-center">
      <ul class=" list-none">
        <li v-for="academy in authAcademies" :key="academy.name" class="text-sm">
          <a :href="`/academies/${academy.id}`" class=" underline">{{ academy.name }}</a>
        </li>
      </ul>
    </div>

    <div class="p-4 mx-4 flex flex-col justify-center items-center space-y-2">
      <!-- Component: Base primary button with trailing icon  -->
      <button type="button" @click.prevent="openModal = true"
        class="inline-flex items-center justify-center py-1.5 gap-1 px-3 text-sm font-medium tracking-wide text-white transition duration-300 rounded-full focus-visible:outline-none whitespace-nowrap bg-violet-500 hover:bg-violet-600 focus:bg-violet-700 disabled:cursor-not-allowed disabled:border-violet-300 disabled:bg-violet-300 disabled:shadow-none">
        <span class="relative only:-mx-3">
          <Icon icon="heroicons-outline:plus-circle" class="w-6 h-6"/>
        </span>
        <span>เพิ่มแหล่งเรียนรู้</span>
      </button>
      <!-- End Base primary button with trailing icon  -->
      <!-- <Link href="/courses/create" class="inline-flex items-center justify-center py-1.5 gap-1 px-3 text-sm font-medium tracking-wide text-white transition duration-300 rounded-full focus-visible:outline-none whitespace-nowrap bg-violet-500 hover:bg-violet-600 focus:bg-violet-700 disabled:cursor-not-allowed disabled:border-violet-300 disabled:bg-violet-300 disabled:shadow-none">
        <span class="relative only:-mx-3">
          <Icon icon="heroicons-outline:plus-circle" class="w-6 h-6"/>
        </span>
        <span>เพิ่มรายวิชาใหม่</span>
      </Link> -->
    </div>

    <!-- ##################################################### -->

    <div v-if="openModal" class="fixed inset-0 w-full h-screen flex items-center justify-center bg-semi-75 z-50 bg-gray-600 bg-opacity-75">
      <div class=" w-2/5 mx-4 bg-white rounded-xl shadow-xl z-40">
        <CreateNewSchoolModal 
          @close-modal="openModal=false" 
          @add-new-academy="(newAcademy)=>{ authAcademies.push(newAcademy) }"
        />
      </div>
    </div>

    <!-- ##################################################### -->

  </div>
</template>

<script setup>
import { ref,onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { Icon } from '@iconify/vue';
import CreateNewSchoolModal from '../modals/CreateNewSchoolModal.vue';
const openModal = ref(false);
const authStore = useAuthStore();

const defaultSchoolProfileCover = '/storage/images/covers/default_cover.png';
const authAcademies = ref([]);
onMounted(() => {
  getAuthUserAcademy();
})

const getAuthUserAcademy = async () => {
  try {
    let authAcdResp = await axios.get(`/user/${pageData.props.auth.user.id}/academies`);
    authAcademies.value = authAcdResp.data.academies;
  } catch (error) {
    console.error(error);
  }
};


</script>
