<script setup>
import { ref } from 'vue';

import VerticalDotsDropdownMenu from '@/PlearndComponents/accessories/VerticalDotsDropdownMenu.vue';

const props = defineProps({
  topic: Object,
});

const emit = defineEmits(['edit-topic', 'delete-topic']);

const expandTopicDetails = ref(false);

const handleExpandTopicDetails = () => {
  expandTopicDetails.value = !expandTopicDetails.value;
}

const handleEditTopic = () => {
  emit('edit-topic');
}

const handleDeleteTopic = ()=> {
  emit('delete-topic');
}

</script>

<template>
  <div class="lesson-topics my-2 relative border rounded-md border-gray-200">
    <div @click="handleExpandTopicDetails"
      :class="{ 'mr-9' : $page.props.isCourseAdmin }"
      class="flex justify-between items-center px-2 py-3 cursor-pointer bg-blue-200 text-gray-700">
      <div class="">
        <h3 class="font-semibold">{{ topic.title }}</h3>
        <!-- <p class="text-sm text-gray-500">{{ topic.videoCount }} videos</p> -->
      </div>
      <svg :class="{ 'transform rotate-180': expandTopicDetails }" class="w-5 h-5 transition-transform" fill="none"
        stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
      </svg>
    </div>
    <div class="absolute top-0 right-1 z-30" v-if="$page.props.isCourseAdmin">
      <VerticalDotsDropdownMenu @edit-model="handleEditTopic" @delete-model="handleDeleteTopic">
        <template #editModel>
          <div>
            แก้ไขหัวข้อ
          </div>
        </template>
        <template #deleteModel>
          <div>
            ลบหัวข้อ
          </div>
        </template>
      </VerticalDotsDropdownMenu>
    </div>
    <div v-if="expandTopicDetails">
      <div class="p-2 mt-2 rounded-md">
        <Textarea :id="`topic_content_${topic.id}`" rows="4" autoResize
            v-model="topic.content" v-if="topic.content" readonly
            class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
          >
          </Textarea>
      </div>

      <!-- topic's images viewer -->
      <div class="flex flex-wrap justify-center">
        <div v-for="image in topic.images" :key="image.id" class=" mt-2">
          <img :src="image.image_url" class="border" />
        </div>
      </div>

      <div v-if="topic.youtube_url" class="mt-4">
          <p>วิดีโอ</p>
      </div>
      <div v-if="topic.youtube_url" class=" border-2 border-gray-200 rounded-lg ">
          <vue-plyr>
              <div data-plyr-provider="youtube" :data-plyr-embed-id="topic.youtube_url"></div>
          </vue-plyr>
      </div>

    </div>
  </div>
  <!-- <button @click="addNewTopic">Add New Topic</button> -->
</template>
