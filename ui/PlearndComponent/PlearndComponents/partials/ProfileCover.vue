<script setup>
import { ref } from 'vue';
import { Link,usePage } from "@inertiajs/vue3";
import { Icon } from '@iconify/vue';
import BlueVioletButton from '../accessories/BlueVioletButton.vue';
import CyanSeaButton from '../accessories/CyanSeaButton.vue'


const props = defineProps({
    coverImage: { type: String, default: null },
    logoImage: { type: String, default: null },
    coverHeader: { type: String, default: null },
    coverSubheader: { type: String, default: null },
    model: String,
    modelTable: String,
    modelableId: Number,
    modelableType: String,
    modelableRoute: String,
    modelData: Object,
    subModelData: Object,
    subModelNameTh: String
});

const emit = defineEmits([
    'update:coverHeader',
    'update:coverSubheader',
    'cover-image-change', 
    'logo-image-change',
    'header-change',
    'subheader-change',
    'request-tobe-member'
]);

const logoInput = ref(null);
const coverInput = ref(null);
const tempLogo = ref(props.logoImage);
const tempCover = ref(props.coverImage);
const tempHeader = ref(props.coverHeader);
const tempSubheader = ref(props.coverSubheader);

const inputHeaderEditing = ref(false);
const inputSubheaderEditing = ref(false)
const browseCover = () => { coverInput.value.click() };
const browseLogo = () => { logoInput.value.click() };

function onCoverInputChange(event) {
    tempCover.value = URL.createObjectURL(event.target.files[0]);
    emit('cover-image-change', event.target.files[0]);
}
function onLogoInputChange(event) {
    tempLogo.value = URL.createObjectURL(event.target.files[0]);
    emit('logo-image-change', event.target.files[0]);
}
function onHeaderSave(){
    emit('header-change', tempHeader.value);
    inputHeaderEditing.value = false;
}
function onSubheaderSave(e){
    e.preventDefault();
    // console.log(tempSubheader);
    emit('subheader-change', tempSubheader.value);
    inputSubheaderEditing.value = false;
}

function onRequestToBeMember(){
    emit('request-tobe-member');
}
</script>
<template>
    <div>
        <div class="relative w-full rounded-lg bg-cover bg-white overflow-hidden ">
            <div class="">
                <img :src="tempCover" alt="" class="w-full h-36 sm:h-52 md:h-72 lg:h-70">
                <div id="cover" class="absolute top-2 left-2 md:h-28 lg:h-60">
                    <input type="file" class="hidden" ref="coverInput" accept="image/*" @change="onCoverInputChange">
                    <button type="button" @click.prevent="browseCover"
                        class="rounded-full bg-white bg-opacity-60 p-1 text-gray-600">
                        <Icon icon="heroicons-outline:photograph" class="w-6 h-6" />
                    </button>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row items-center justify-center sm:justify-between w-full px-4 -mt-[52px] sm:-mt[-54-px] md:-mt-[64px] lg:-mt-[84px]">
                <div class="flex flex-col sm:flex-row items-center w-[69%]">
                    <!-- <div class="relative max-w-fit w-[88px] h-[88px] sm:w-28 sm:h-28 md:w-32 md:h-32 lg:w-40 lg:h-40 flex justify-center items-center border-gray-400 border-2 rounded-full overflow-hidden"> -->
                    <div class="relative min-w-fit flex justify-center items-center border-gray-400 border-2 rounded-full overflow-hidden ">
                        <img class="bg-cover object-center w-[88px] h-[88px] sm:w-28 sm:h-28 md:w-32 md:h-32 lg:w-40 lg:h-40" :src="tempLogo" alt='school logo'>
                        <input type="file" class="hidden" accept="image/*" ref="logoInput" @change="onLogoInputChange">
                        <button type="button" @click.prevent="browseLogo"
                            class="absolute bottom-0 bg-white bg-opacity-60 text-gray-600 border-[1px] border-gray-300 transition duration-200 rounded-full md:p-1">
                            <Icon icon="heroicons:camera" class="w-4 h-4 " />
                        </button>
                    </div>
                    <div class="sm:ml-4 space-y-1 text-center">
                        <div class="flex relative justify-center sm:justify-start ">
                            <div class="relative ">
                                <input type="text" name="" :value="coverHeader" @input="$emit('update:coverHeader', $event.target.value)" v-if="inputHeaderEditing"
                                        id="coverHeaderNameInput"
                                        class="bg-white bg-opacity-60  sm-mt-2rounded-md bg-transparent border-none text-center sm:text-start font-semibold text-base sm:text-2xl focus:outline-0"
                                >
                                <p v-else class="bg-white bg-opacity-60 font-semibold text-base sm:text-2xl max-w-fit p-2 rounded-md">{{ coverHeader }}</p>
                                <div class="">
                                    <div v-if="inputHeaderEditing" class="">
                                        <button  @click.prevent="onHeaderSave" class="absolute -top-1 right-0 sm:-right-2">
                                            <Icon icon="fluent:save-24-regular"
                                                class="w-6 h-6 bg-green-400 bg-opacity-60 rounded-full text-gray-700 p-1 border border-white" />
                                        </button>
                                        <button  @click="inputHeaderEditing = false" class="absolute -bottom-1 right-0 sm:-right-2 ">
                                            <Icon icon="heroicons-outline:x"
                                                class="w-6 h-6 bg-red-400 bg-opacity-60 rounded-full text-gray-700 p-1 border border-white" />
                                        </button>
                                    </div>
                                    <div v-else class="">
                                        <button  @click="inputHeaderEditing = true" class="absolute top-0 -right-4 sm:-right-6 ">
                                            <Icon icon="heroicons:pencil-square"
                                                class="w-6 h-6 bg-white bg-opacity-60 rounded-full text-gray-700 p-1" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-center sm:justify-start">
                            <div class="relative ">
                                <textarea v-if="inputSubheaderEditing" :value="coverSubheader" @input="emit('update:coverSubheader', $event.target.value)"
                                class="rounded-lg bg-transparent border-none text-center sm:text-start font-normal text-lg "
                                ></textarea>
                                <p v-else class="min-h-fit bg-white bg-opacity-60 font-normal text-lg p-2 mx-4 sm:mx-0 rounded-md text-center sm:text-start">{{ coverSubheader }}</p>
                                <div class="">
                                    <div v-if="inputSubheaderEditing">
                                        <button  @click="onSubheaderSave" class="absolute top-0 right-0 sm:-right-6 ">
                                            <Icon icon="fluent:save-24-regular"
                                                class="w-6 h-6 bg-gray-200 bg-opacity-60 rounded-full text-gray-700 p-1" />
                                        </button>
                                        <button  @click="inputSubheaderEditing = false" class="absolute top-8 right-0 sm:-right-6">
                                            <Icon icon="heroicons-outline:x"
                                                class="w-6 h-6 bg-red-400 bg-opacity-60 rounded-full text-gray-700 p-1 " />
                                        </button>
                                    </div>
                                    <div v-else>
                                        <button  @click="inputSubheaderEditing = true" class="absolute top-0 right-0 sm:-right-6 ">
                                            <Icon icon="heroicons:pencil-square"
                                                class="w-6 h-6 bg-gray-200 bg-opacity-60 rounded-full text-gray-700 p-1" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="space-x-2 hidden md:flex  mx-4 w-[31%] justify-end">
                    <BlueVioletButton @click.prevent="onRequestToBeMember" >
                        <span v-if="!props.modelData.isMember">
                            + เข้าร่วม
                        </span>
                        <span v-else class="flex items-center">
                            - <Icon icon="majesticons:door-exit-line" class="ml-2" />
                        </span>
                    </BlueVioletButton>
                    <!-- <CyanSeaButton>+ message</CyanSeaButton> -->
                </div>
            </div>
            <div class="my-3 md:flex justify-center gap-4 md:!gap-14">
                <div class="flex justify-center space-x-4 md:space-x-10">
                    <div class="flex flex-col items-center justify-center">
                        <h3 class="text-bluePrimary text-2xl font-bold">{{ props.subModelData.length }}</h3>
                        <p class="text-lightSecondary text-sm font-normal">{{ props.subModelNameTh }}</p>
                    </div>
                    <div class="flex flex-col items-center justify-center" v-if="props.modelData.total_students">
                        <h3 class="text-bluePrimary text-2xl font-bold">{{ props.modelData.total_students }}</h3>
                        <p class="text-lightSecondary text-sm font-normal">สมาชิก</p>
                    </div>
                    <div class="flex flex-col items-center justify-center" v-if="props.modelData.enrolled_students">
                        <h3 class="text-bluePrimary text-2xl font-bold">{{ props.modelData.enrolled_students }}</h3>
                        <p class="text-lightSecondary text-sm font-normal">ผู้เรียน</p>
                    </div>
                    <!-- <div class="flex flex-col items-center justify-center">
                        <h3 class="text-bluePrimary text-2xl font-bold">434</h3>
                        <p class="text-lightSecondary text-sm font-normal">
                            Following
                        </p>
                    </div> -->
                </div>
                <div class="flex justify-center md:hidden space-x-4 m-2">
                    <BlueVioletButton @click.prevent="onRequestToBeMember()">
                        <span v-if="!props.modelData.isMember">
                            + เข้าร่วม
                        </span>
                        <span v-else class="flex items-center space-x-3">
                            - <Icon icon="majesticons:door-exit-line" class="ml-2" />
                        </span>
                    </BlueVioletButton>
                    <!-- <CyanSeaButton>+ message</CyanSeaButton> -->
                </div>
            </div>

    </div>
</div>
</template>
