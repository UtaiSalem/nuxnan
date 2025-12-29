<script setup>
import { ref, computed, watch } from 'vue';
// import { Link, router, usePage } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

import LessonReactionViewer from '@/PlearndComponents/learn/courses/lessons/LessonReactions/LessonReactionViewer.vue';
import LessonCommentsViewer from '@/PlearndComponents/learn/courses/lessons/comments/LessonCommentsViewer.vue';
import VerticalDotsDropdownMenu from '@/PlearndComponents/accessories/VerticalDotsDropdownMenu.vue';
import CreateNewTopic from './topics/CreateNewTopic.vue';
import TopicItem from './topics/TopicItem.vue';
import EditTopicModal from './topics/EditTopicModal.vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    lesson: Object,
});

const emit = defineEmits([
  'edit-model', 
  'delete-model',
  'link-to-lesson'
]);

const refLesson = ref(props.lesson);
const isOpenLessonDetail = ref(true);

const showNewTopicForm = ref(false);
const showEditTopicForm = ref(false);
const targetTopicIndex = ref(null);

const showEditImageForm = ref(false);
const targetImageIndex = ref(null);
const isDeletingImage = ref(false);

const displayedDescription = computed(() => {
    if (props.lesson.description.length < 150 || isOpenLessonDetail.value) {
      return props.lesson.description;
    } else {
      return props.lesson.description.substring(0, 150) + '...';
    }
});

const handleEditImage = (imageIndex) => {
  targetImageIndex.value = imageIndex;
  showEditImageForm.value = true;
}

const handleUpdateImage = (updatedImage) => {
  props.lesson.images[targetImageIndex.value] = updatedImage;
  showEditImageForm.value = false;

  Swal.fire({
    icon: 'success',
    title: 'Image updated successfully',
    showConfirmButton: false,
    timer: 1000
  });
}

const deleteImage = (imageId,imageIndex) => {
  // if (confirm('Are you sure you want to delete this image?')) {
  //   axios.delete(`/lessons/${props.lesson.id}/images/${imageId}`)
  //     .then(() => {
  //       props.lesson.images.splice(imageIndex, 1);
  //     })
  //     .catch(error => {
  //       console.error('Error deleting image:', error);
  //     });
  // }
  Swal.fire({
    title: 'ลบรูปภาพ',
    text: "ยืนยันการลบรูปภาพอย่างถาวร",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#7c3aed',
    cancelButtonColor: '#f87171',
    confirmButtonText: 'ยืนยันการลบ'
  }).then( async (result) => {
    if (result.isConfirmed) {
      try {
        isDeletingImage.value = true
        targetImageIndex.value = imageIndex;
        let delResp = await axios.delete(`/lessons/${props.lesson.id}/images/${imageId}`);
        if (delResp.status===200) {
          props.lesson.images.splice(imageIndex, 1);
        }
        else {
          console.error('Error deleting image:', error);
        }
      } catch (error) {
        // Handle error silently
      } finally {
        isDeletingImage.value = true;
        targetImageIndex.value = null;
      }
    }
  });
}

function handleEditLesson() {
    emit('edit-model');
}

function onDeleteLesson() {
    emit('delete-model');
}

const handleExpandLessonDetails = () => {
  isOpenLessonDetail.value = !isOpenLessonDetail.value;
}


const handleAddNewTopic = (newTopic) => {
    props.lesson.topics.push(newTopic);

    Swal.fire({
      icon:'success',
      title: 'เพิ่มหัวข้อใหม่เรียบร้อยแล้ว',
      showConfirmButton: false,
      timer: 1000
    });
}

const handleUpdateTopic = (topic) => {
  props.lesson.topics[targetTopicIndex.value] = topic;
  showEditTopicForm.value = false;
  
  Swal.fire({
    icon:'success',
    title: 'บทเรียนถูกแก้ไขเรียบร้อยแล้ว',
    showConfirmButton: false,
    timer: 1000
  });

}

const handleDeleteTopic = (tid, topicIndex) => {
  Swal.fire({
    title: 'คุณแน่ใจหรือไม่ที่จะลบหัวข้อนี้?',
    text: "คุณจะไม่สามารถกู้คืนหัวข้อนี้ได้อีก!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'ใช่, ยืนยันการลบ!'
  }).then((result) => {
    if (result.isConfirmed) {
      props.lesson.topics.splice(topicIndex, 1);
      deleteTipic(tid);
    }
  })
}

const deleteTipic = async (topic_id) => {
  // console.log(topic_index);
  await axios.delete(`/lessons/${props.lesson.id}/topics/${topic_id}`);
  // if (delResp.status===200) {
  //   props.lesson.topics.splice(topic_index, 1);
  // }
}

const handleEditTopic = async (tIndex) => {
  targetTopicIndex.value = tIndex;
  showEditTopicForm.value = true;
}

</script>

<template>
  <div class="my-4 grid bg-white w-full rounded-lg shadow-lg">
    <div class="flex flex-col overflow-hidden p-2">
      <div class="relative ">
        <div class="absolute top-0 right-0 " v-if="$page.props.isCourseAdmin">
          <VerticalDotsDropdownMenu @edit-model="handleEditLesson" @delete-model="onDeleteLesson">
            <template #editModel>
              <div>
                แก้ไขบทเรียน
              </div>
            </template>
            <template #deleteModel>
              <div>
                ลบบทเรียน
              </div>
            </template>
          </VerticalDotsDropdownMenu>
        </div>
        <div @click="handleExpandLessonDetails" 
          :class="{ 'mr-8' : $page.props.isCourseAdmin }"
          class="flex justify-between items-center px-2 py-3 cursor-pointer bg-violet-200 rounded-md text-gray-700">
          <h3 class="font-semibold">{{ lesson.title }}</h3>
          <svg :class="{ 'transform rotate-180': isOpenLessonDetail }" class="w-5 h-5 transition-transform" fill="none"
            stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
          </svg>
        </div>

        <h3 v-if="lesson.description" class="my-2 font-semibold text-gray-600">คำอธิบาย</h3>
        <Textarea :id="`lesson_description_${lesson.id}`" rows="4" autoResize v-if="lesson.description"
              v-model="displayedDescription" readonly
              class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
        >
        </Textarea>
        <!-- <div v-if="showNewImageForm" class="">
          <CreateNewImage :lesson @close="showNewImageForm = false" />
        </div>

        <div v-if="showEditImageForm" class="">
          <EditImageModal :image="props.lesson.images[targetImageIndex]"
            @update-image="(updatedImage) => handleUpdateImage(updatedImage)" @close="showEditImageForm = false" />
        </div> -->

        <!-- add new section to display lesson's topics with image and all crud instruction with each topic and image -->
        <div class="lesson-topics my-2" v-if="isOpenLessonDetail">

          <h3 v-if="lesson.content" class="my-2 font-semibold text-gray-600">เนื้อหา</h3>
          <Textarea :id="`lesson_content_${lesson.id}`" v-if="lesson.content" 
            rows="4" autoResize
            v-model="lesson.content"
            readonly
            class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
          >
          </Textarea>

          <div class="lesson-images my-2" v-if="isOpenLessonDetail && $page.props.isCourseAdmin">
            <div v-for="(image, imageIndex) in lesson.images" :key="image.id" class="image-item">
              <div class="relative">
                <img :src="image.full_url" :alt="image.alt" class="w-full h-auto border rounded my-1" />
                <button type="button" class="absolute top-2 right-2 bg-red-200 rounded-full p-1.5 flex items-center" @click="deleteImage(image.id, imageIndex)">
                  <Icon icon="uil:image-times" class=" w-5 h-5 text-red-600/80" />
                </button>
                <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50 rounded" v-if="isDeletingImage && targetImageIndex == imageIndex">
                  <Icon icon="svg-spinners:6-dots-rotate" class="w-12 h-12 text-white" />
                </div>
              </div>
            </div>
          </div>
          
        </div>
        <div class="lesson-topics my-2" v-if="lesson.topics.length">
          <h3 v-if="lesson.topics.length" class="my-2 font-semibold text-gray-600">หัวข้อบทเรียน</h3>
          <div v-for="(topic, topicIndex) in lesson.topics" :key="topic.id" class="topic-item">
              <TopicItem 
                :topic="topic" 
                @edit-topic="handleEditTopic(topicIndex)"
                @delete-topic="handleDeleteTopic(topic.id,topicIndex)"
              />
          </div>
        </div>

        <div class="mt-2 flex items-center justify-end rounded-md">
          <button @click="showNewTopicForm = true" class="p-1 bg-blue-500 text-white text-xs rounded-md flex items-center">
            <Icon icon="heroicons:squares-plus" class="w-5 h-5 mr-1" />
            เพิ่มหัวข้อใหม่
          </button>
        </div>

      </div>

      <div v-if="showNewTopicForm" class="">
          <CreateNewTopic 
            :lesson 
            @close="showNewTopicForm = false"
            @add-new-topic="(newTopic)=>handleAddNewTopic(newTopic)"
            
          />
      </div>
      
      <div v-if="showEditTopicForm" class="">
          <EditTopicModal 
            :topic="props.lesson.topics[targetTopicIndex]"
            @update-topic="(newTopic)=>handleUpdateTopic(newTopic)"
            @close="showEditTopicForm = false"/>
      </div>

      <div v-if="lesson.youtube_url" class="mt-4">
          <p>วิดีโอ</p>
      </div>
      <div v-if="lesson.youtube_url" class=" border-2 border-gray-200 rounded-lg ">
          <vue-plyr>
              <div data-plyr-provider="youtube" :data-plyr-embed-id="lesson.youtube_url"></div>
          </vue-plyr>
      </div>
    </div>
    <hr class="text-gray-400 mt-3">

    <LessonReactionViewer :lesson="refLesson" />

    <LessonCommentsViewer :lesson @new-comment-added="() => refLesson.comment_count++" />

  </div>
</template>
