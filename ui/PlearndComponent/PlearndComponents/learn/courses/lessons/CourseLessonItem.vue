<template>
    <div class="max-w-4xl mx-auto p-4 bg-white">
        <!-- Course Header -->
        <!-- <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="flex text-yellow-400 mr-2">
                        <svg v-for="_ in 5" :key="_" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                            </path>
                        </svg>
                    </div>
                    <span class="text-yellow-600 font-bold">4.5</span>
                </div>
                <button class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">Follow</button>
            </div>
            <h1 class="text-3xl font-bold mb-2">{{ course.title }}</h1>
            <p class="text-gray-600 mb-4">{{ course.description }}</p>
            <div class="flex items-center mb-4">
                <img :src="course.instructorAvatar" :alt="course.instructorName" class="w-12 h-12 rounded-full mr-4">
                <div>
                    <p class="font-semibold">{{ course.instructorName }}</p>
                    <p class="text-sm text-gray-500">Last Update: {{ course.lastUpdate }}</p>
                </div>
            </div>
            <div class="flex items-center text-gray-600 text-sm">
                <span class="mr-4"><i class="far fa-eye mr-1"></i>{{ course.views }}</span>
                <span class="mr-4"><i class="far fa-thumbs-up mr-1"></i>{{ course.likes }}</span>
                <span class="mr-4"><i class="far fa-comment mr-1"></i>{{ course.comments }}</span>
                <span><i class="fas fa-share mr-1"></i>Share</span>
            </div>
        </div> -->

        <!-- Course Video -->
        <!-- <div class="bg-gray-200 h-64 flex items-center justify-center mb-6 rounded-lg relative">
            <span class="text-2xl text-gray-600">{{ course.videoSize }}</span>
            <button class="absolute inset-0 flex items-center justify-center">
                <svg class="w-16 h-16 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                    <path
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"
                        clip-rule="evenodd" fill-rule="evenodd"></path>
                </svg>
            </button>
        </div> -->

        <!-- Course Actions -->
        <!-- <div class="flex justify-between items-center mb-6">
            <div class="text-2xl font-bold text-blue-600">${{ course.price }} <span
                    class="text-gray-400 line-through text-lg">${{ course.originalPrice }}</span></div>
            <div>
                <button
                    class="bg-blue-500 text-white px-6 py-2 rounded-lg mr-2 hover:bg-blue-600 transition">Start</button>
                <button class="bg-yellow-500 text-white px-6 py-2 rounded-lg hover:bg-yellow-600 transition">Add to
                    Wishlist</button>
            </div>
        </div> -->
        <!-- <p class="text-center text-sm text-gray-600 mb-6">{{ course.guarantee }}</p> -->

        <!-- Course Description -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold mb-4">Description:</h2>
            <p class="text-gray-600 mb-4">{{ course.fullDescription }}</p>
        </div>

        <!-- Curriculum -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold mb-4">Curriculum</h2>
            <div v-for="(lesson, index) in course.curriculum" :key="index" class="bg-white shadow-md rounded-lg mb-2">
                <div @click="toggleLesson(index)"
                    class="flex justify-between items-center p-4 cursor-pointer hover:bg-gray-50">
                    <div>
                        <h3 class="font-semibold">{{ lesson.title }}</h3>
                        <p class="text-sm text-gray-500">{{ lesson.videoCount }} videos</p>
                    </div>
                    <svg :class="{ 'transform rotate-180': lesson.isOpen }" class="w-5 h-5 transition-transform"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
                <div v-if="lesson.isOpen" class="p-4 border-t">
                    <ul>
                        <li v-for="(topic, topicIndex) in lesson.topics" :key="topicIndex" class="mb-2">
                            <div class="flex justify-between items-center">
                                <span>{{ topic.title }}</span>
                                <div v-if="isOwner">
                                    <button @click="editTopic(index, topicIndex)"
                                        class="text-blue-500 mr-2">Edit</button>
                                    <button @click="deleteTopic(index, topicIndex)" class="text-red-500">Delete</button>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <button v-if="isOwner" @click="openAddTopicModal(index)"
                        class="mt-4 bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Add Topic</button>
                </div>
            </div>
        </div>

        <!-- Course Includes -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold mb-4">This Course Includes:</h2>
            <ul class="grid grid-cols-2 gap-4">
                <li v-for="(item, index) in course.includes" :key="index" class="flex items-center">
                    <i :class="item.icon" class="mr-2 text-blue-500"></i>
                    <span>{{ item.text }}</span>
                </li>
            </ul>
        </div>

        <!-- Add/Edit Topic Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
            <div class="bg-white p-6 rounded-lg w-full max-w-md">
                <h3 class="text-xl font-bold mb-4">{{ isEditing ? 'Edit Topic' : 'Add New Topic' }}</h3>
                <form @submit.prevent="submitTopic">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Title</label>
                        <input v-model="topicForm.title" type="text"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    </div>
                    <div class="flex justify-end">
                        <button type="button" @click="closeModal"
                            class="mr-2 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Cancel</button>
                        <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">{{
                            isEditing ? 'Update' : 'Add' }} Topic</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';

const course = reactive({
    title: 'Learn Basic Java Scripts',
    description: 'Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, eget dui.',
    fullDescription: 'Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero.\n\nNam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero.',
    instructorName: 'Kim Carter',
    instructorAvatar: 'path/to/avatar.jpg',
    lastUpdate: 'Aug, 27 2021',
    views: 1450,
    likes: 200,
    comments: 80,
    videoSize: '480px x 270px',
    price: 19.99,
    originalPrice: 29.99,
    guarantee: '30 days money back guarantee',
    curriculum: [
        {
            title: '1- Basic Html Introduction',
            videoCount: '03 videos',
            isOpen: false,
            topics: [
                { title: 'HTML Structure' },
                { title: 'HTML Tags' },
                { title: 'HTML Forms' },
            ]
        },
        {
            title: 'Css3 Advanced Lectures',
            videoCount: '10 videos',
            isOpen: false,
            topics: [
                { title: 'CSS Selectors' },
                { title: 'CSS Box Model' },
                { title: 'CSS Flexbox' },
            ]
        },
        {
            title: 'JQuery Advance Lectures',
            videoCount: '20 videos',
            isOpen: false,
            topics: [
                { title: 'jQuery Selectors' },
                { title: 'jQuery Events' },
                { title: 'jQuery AJAX' },
            ]
        },
    ],
    includes: [
        { icon: 'far fa-clock', text: '28 Hours Video' },
        { icon: 'fas fa-certificate', text: 'Certificate' },
        { icon: 'far fa-file-alt', text: '12 Article' },
        { icon: 'fas fa-tv', text: 'Watch Offline' },
        { icon: 'fas fa-infinity', text: 'Life Time Access' },
        { icon: 'fas fa-dollar-sign', text: 'Paid' },
    ]
});

const isOwner = ref(true); // This should be set based on user authentication
const showModal = ref(false);
const isEditing = ref(false);
const currentLessonIndex = ref(null);
const currentTopicIndex = ref(null);

const topicForm = reactive({
    title: '',
});

const toggleLesson = (index) => {
    course.curriculum[index].isOpen = !course.curriculum[index].isOpen;
};

const openAddTopicModal = (lessonIndex) => {
    currentLessonIndex.value = lessonIndex;
    isEditing.value = false;
    topicForm.title = '';
    showModal.value = true;
};

const editTopic = (lessonIndex, topicIndex) => {
    currentLessonIndex.value = lessonIndex;
    currentTopicIndex.value = topicIndex;
    const topic = course.curriculum[lessonIndex].topics[topicIndex];
    topicForm.title = topic.title;
    isEditing.value = true;
    showModal.value = true;
};

const deleteTopic = (lessonIndex, topicIndex) => {
    if (confirm('Are you sure you want to delete this topic?')) {
        course.curriculum[lessonIndex].topics.splice(topicIndex, 1);
    }
};

const submitTopic = () => {
    if (isEditing.value) {
        course.curriculum[currentLessonIndex.value].topics[currentTopicIndex.value] = { ...topicForm };
    } else {
        course.curriculum[currentLessonIndex.value].topics.push({ ...topicForm });
    }
    closeModal();
};

const closeModal = () => {
    showModal.value = false;
    topicForm.title = '';
};
</script>
