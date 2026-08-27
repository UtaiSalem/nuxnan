<template>
  <div class="bg-white p-4 shadow rounded mb-4 border-l-4 border-orange-500">
      <h4 class="font-bold">Advert Approval</h4>
      <p>Title: {{ advert.title }}</p>
      <div class="mt-2 text-sm text-gray-500">
          <button class="min-h-[44px] sm:min-h-0 bg-green-500 text-white px-2 py-1 rounded mr-2">Approve</button>
          <button class="min-h-[44px] sm:min-h-0 bg-red-500 text-white px-2 py-1 rounded">Reject</button>
      </div>
  </div>
</template>
<script setup>
defineProps({
    advert: Object
})
</script>
