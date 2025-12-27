<script setup>
import { ref } from 'vue'
const props = defineProps({
    type:String
});
</script>
<template>
    <button :type="type" class="bg-[#8581fa] w-full py-2 px-4 rounded-lg text-white shadow-lg  hover:shadow-xl hover:bg-[#615dfa] border border-gray-400">
        <slot></slot>
    </button>
</template>

