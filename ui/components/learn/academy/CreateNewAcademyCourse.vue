<template>
  <div class="bg-white p-4 sm:p-6 shadow rounded-lg">
      <h2 class="text-xl font-bold mb-4">Create New Course</h2>
      <form class="space-y-4">
          <input type="text" placeholder="Course Name" class="w-full border rounded p-2" />
          <textarea placeholder="Description" class="w-full border rounded p-2"></textarea>
          <button type="submit" class="min-h-[44px] sm:min-h-0 bg-blue-600 text-white px-4 py-2 rounded">Create</button>
      </form>
  </div>
</template>
<script setup>
defineProps({
    academy: Object
})
</script>
