<script setup>
import { ref,computed } from 'vue';
import { usePage, Link } from "@inertiajs/vue3";
import { Icon } from '@iconify/vue';

const props = defineProps({
    cover: { type: String, default: null },
    logo: { type: String, default: null },
    name: { type: String, default: null },
    code: { type: String, default: null },
});

const emit = defineEmits(['update:image']);

const coverInput = ref(null);
const tempLogo = ref(props.logo || usePage().props.imagePath+'storage/images/logos/default_logo.png');
const tempCover = ref(props.cover || usePage().props.imagePath+'storage/images/covers/default_cover.png');

const inputHeaderEditing = ref(false);
const inputSubheaderEditing = ref(false)
const browseCover = () => { coverInput.value.click() };

function onCoverInputChange(event) {
    tempCover.value = URL.createObjectURL(event.target.files[0]);
    // emit('cover-image-change', event.target.files[0]);
}

</script>
<template>
    <div class="bg-white rounded-lg pb-4">
        <div class="relative w-full rounded-t-lg bg-cover bg-white overflow-hidden ">
            <img :src="tempCover" alt="" class="w-full h-36 sm:h-52 md:h-72 lg:h-70 g">
            <div class="flex justify-center -mt-32 lg:-mt-40">
                <img class="bg-cover rounded-full object-center border-4 border-white w-[88px] h-[88px] sm:w-28 sm:h-28 md:w-32 md:h-32 lg:w-40 lg:h-40" 
                    :src="tempLogo" alt='school logo'
                >
            </div>
        </div>
        <div class=" space-y-1 text-center">
                <div class="flex relative justify-center">
                    <Link :href="`/courses/${$page.props.course.data.id}`">
                        <div class="relative ">
                            <p class="bg-white bg-opacity-60 font-semibold text-base sm:text-2xl max-w-fit p-2 rounded-md">{{ props.name }}</p>
                        </div>
                    </Link>
                </div>
                <div class="flex justify-center">
                    <div class="relative ">
                        <p class="min-h-fit bg-white bg-opacity-60 font-normal text-lg p-2 mx-4 sm:mx-0 rounded-md text-center sm:text-start">{{ props.code }}</p>
                    </div>
                </div>
        </div>
        <!-- <div class="my-3 md:flex justify-center gap-4 md:!gap-14">
            <div class="flex justify-center space-x-4 md:space-x-10">
                <div class="flex flex-col items-center justify-center">
                    <h3 class="text-bluePrimary text-2xl font-bold">17</h3>
                    <p class="text-lightSecondary text-sm font-normal">Posts</p>
                </div>
                <div class="flex flex-col items-center justify-center">
                    <h3 class="text-bluePrimary text-2xl font-bold">9.7K</h3>
                    <p class="text-lightSecondary text-sm font-normal">
                        Followers
                    </p>
                </div>
                <div class="flex flex-col items-center justify-center">
                    <h3 class="text-bluePrimary text-2xl font-bold">434</h3>
                    <p class="text-lightSecondary text-sm font-normal">
                        Following
                    </p>
                </div>
            </div>
        </div> -->
        <!-- <div class="space-x-2 flex w-full justify-center">
            <button class="bg-[#8581fa] py-2 px-4 rounded-lg text-white shadow-lg "> + ขอเข้าร่วม</button>
            <button class="bg-[#23d2e2] py-2 px-4 rounded-lg text-white shadow-lg "> + Message</button>
        </div> -->
    </div>
</template>
