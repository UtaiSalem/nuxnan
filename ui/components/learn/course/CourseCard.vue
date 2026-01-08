<template>
  <div class="bg-white rounded-lg shadow p-4 flex flex-col h-full">
    <div class="h-40 bg-gray-200 rounded mb-4">
        <img v-if="course.cover" :src="course.cover" class="w-full h-full object-cover rounded" />
    </div>
    <div class="font-bold text-lg mb-2">{{ course.name || 'Course Name' }}</div>
    <p class="text-gray-600 text-sm mb-4">{{ course.code || 'CODE101' }}</p>
    <button @click="$emit('loading-page')" class="mt-auto bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600">
      View Course
    </button>
  </div>
</template>

<script setup>
defineProps({
    course: Object
})
</script>
