<script setup>
const props = defineProps({
    active: {
        type: Boolean,
        default: false
    },
});
</script>
<template>
    <button :disabled="props.active" class="bg-[#23d2e2] py-2 px-4 rounded-lg text-white shadow-lg  hover:shadow-xl hover:bg-[#4bc4cf] border border-gray-400">
        <slot></slot>
    </button>
</template>

