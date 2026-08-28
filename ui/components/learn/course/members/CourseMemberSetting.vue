<template>
  <div class="bg-white p-4 sm:p-6 shadow rounded-lg">
      <h3 class="text-xl font-bold mb-4">Member Settings</h3>
      <p>Member: {{ member_info.member_name }}</p>
      <div class="mt-4">
          <label class="flex items-center space-x-2">
              <input type="checkbox" />
              <span>Allow posting</span>
          </label>
      </div>
  </div>
</template>
<script setup>
defineProps({
    member_info: Object
})
</script>
