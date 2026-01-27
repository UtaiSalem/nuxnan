<script setup>
import PersonItem from '@/PlearndComponents/widgets/friends/PersonItem.vue';

const props = defineProps({
    peopleMayKnow: Object,
});

</script>

<template>
    <div class="bg-white p-4 rounded-lg shadow-lg border-t-4 border-blue-500">
        <p class="text-sm md:text-md font-bold mb-2">คนที่คุณอาจจะรู้จัก</p>
        <hr>
        <div v-for="person in peopleMayKnow.data" :key="person.id">
            <PersonItem :person />
            <hr>
        </div>
    </div>
</template>
