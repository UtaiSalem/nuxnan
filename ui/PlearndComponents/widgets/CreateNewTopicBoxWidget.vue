
<script setup>

import { ref } from 'vue'
import TextInput from '@/Components/TextInput.vue'
import { Icon } from '@iconify/vue';

const  props = defineProps({
  course_id: Number,
  lesson_id: Number,
});

// const emit = defineEmits(['closeCreateTopicBox']);
const showCreateNewTopicForm =ref(false);
// const pageData = usePage();
const form = ref({
  course_id: 1,
  lesson_id: 1,
  title: 'Topic Title',
  content: "New post on " + new Date().toString(),
});

async function submitFormHandler(){
  try {
    let topicResp = await axios.post('/topics', form.value);
    console.log(topicResp.data.newTopic);
    // usePage().props.lesson.data.topics.push(newTopic.data);   
    // emit('closeCreateTopicBox');
  } catch (err) {
    console.error(err);
    // emit('closeCreateTopicBox');
  }
}

</script>
<template>
  <div class="bg-white rounded-lg overflow-hidden shadow-lg">
    <div class="tabs flex flex-col justify-center pt-4">
      <div class="tabs-header px-4 w-full flex items-center justify-between border-b-2 border-blue-400">
        <div class="text-2xl">
          เพิ่มหัวข้อใหม่ในบทเรียน
        </div>
      </div>
      <div class="tabs-content w-full">
        <div class="p-4">
          <form @submit.prevent="submitFormHandler" class="m-4 space-y-2" v-if="showCreateNewTopicForm">
            <!-- <InputLabel for="topic_title" value="ชื่อหัวข้อ" /> -->
            <label for="topic_title" class="block text-lg font-medium text-gray-900 dark:text-white">ชื่อหัวข้อ</label>
            <TextInput id="topic_title" v-model="form.title" type="text" class="mt-0 block w-full text-start" required autofocus autocomplete="topic_title" />

            <label for="topic_content" class="block text-lg font-medium text-gray-900 dark:text-white">เนื้อหา</label>
            <textarea id="topic_content" v-model="form.content" rows="4" class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder=""></textarea>

            <!-- <div class="flex items-center justify-around">
              <button @click="$emit('closeCreateTopicBox')" class="px-6 py-2 text-primary hover:bg-gray-400 hover:text-white border border-primary rounded">
                ยกเลิก
              </button>

              <button type="submit" class="px-6 py-2 text-white border bg-primary hover:bg-primary-focus rounded-lg">
                บันทึก
              </button>

            </div> -->
          </form>
          <div class="pt-2 mt-2  rounded-b flex justify-center items-center">
              <div v-if="showCreateNewTopicForm" class="flex space-x-4">
                  <button @click.prevent="showCreateNewTopicForm=false" class="text-gray-600 bg-red-300 hover:bg-red-400 hover:text-white focus:ring-4 focus:ring-cyan-200 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                      ยกเลิก
                  </button>
                  <button type="submit" @click.prevent="submitFormHandler"  class="text-white bg-cyan-600 hover:bg-cyan-700 focus:ring-4 focus:ring-cyan-200 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                      บันทึก
                  </button>
              </div>
              <div v-else >
                  <button type="button" @click.prevent="showCreateNewTopicForm=true" class="text-white bg-cyan-600 hover:bg-cyan-700 focus:ring-4 focus:ring-cyan-200 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                      เพิ่มหัวข้อใหม่
                  </button>
              </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

