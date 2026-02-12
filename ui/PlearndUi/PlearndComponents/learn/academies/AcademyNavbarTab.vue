<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue'; 
// const emit = defineEmits(['linkToCourse', 'linkToAcademyCourses', 'linkToAcademyMembers', 'linkToAcademyManages']);


const props = defineProps({
    academy: Object,
    activeTab: Number,
});


//function handle link to course's feeds
const linkToCourse = () =>{
    router.visit(`/academies/${props.academy.data.name}`);
}

//function link to academy's courses
const linkToAcademyCourses = () => {
    router.visit(`/academies/${props.academy.data.name}/courses`);
}

//function link to academy's members
const linkToAcademyMembers = () => {
    router.visit(`/academies/${props.academy.data.id}/members`);
}

//function link to academy's manages
const linkToAcademyManages = () => {
    // router.visit(`/academies/${props.academy.data.name}/manages`);
}
</script>

<template>
    <div class=" bg-white shadow-xl w-full rounded-lg overflow-hidden mt-4">
        <div class="flex flex-row justify-around">
            <button @click.prevent="linkToCourse"
                class="border-b-4 hover:border-cyan-400 rounded-none w-full text-center flex-row justify-center"
                :class="{ 'border-b-4 border-cyan-500': activeTab === 0 }">
                <div class="flex flex-col items-center py-2 justify-center text-slate-700 ">
                    <Icon icon="bi:view-list" class="w-6 h-6" :class="{ 'text-cyan-500': activeTab === 0 }" />
                    <span :class="{ 'text-cyan-500 font-bold': activeTab === 0 }">กระดานข่าว</span>
                </div>
            </button>

            <button @click.prevent="linkToAcademyCourses"
                class="border-b-4 hover:border-cyan-400 rounded-none w-full text-center flex-row justify-center "
                :class="{ 'border-cyan-500': activeTab === 1 }">
                <div class="flex flex-col items-center py-2 justify-center text-slate-700 ">
                    <Icon icon="fluent-mdl2:publish-course" class="w-6 h-6"
                        :class="{ 'text-cyan-500': activeTab === 1 }" />
                    <span :class="{ 'text-cyan-500 font-bold': activeTab === 1 }">รายวิชา</span>
                </div>
            </button>

            <button @click.prevent="linkToAcademyMembers"
                class="border-b-4 hover:border-cyan-400 rounded-none w-full text-center flex-row justify-center "
                :class="{ 'border-b-4 border-cyan-500': activeTab === 2 }">
                <div class="flex flex-col items-center py-2 justify-center text-slate-700 ">
                    <Icon icon="flowbite:users-group-outline" class="w-6 h-6"
                        :class="{ 'text-cyan-500': activeTab === 2 }" />
                    <span :class="{ 'text-cyan-500 font-bold': activeTab === 2 }">สมาชิก</span>
                </div>
            </button>

            <button @click.prevent="linkToAcademyManages"
                class="border-b-4 hover:border-cyan-400 rounded-none w-full text-center flex-row justify-center "
                :class="{ 'border-b-4 border-cyan-500': activeTab === 3 }">
                <div class="flex flex-col items-center py-2 justify-center text-slate-700 ">
                    <Icon icon="lucide:layout-list" class="w-6 h-6" :class="{ 'text-cyan-500': activeTab === 3 }" />
                    <span :class="{ 'text-cyan-500 font-bold': activeTab === 3 }">บริหารจัดการ</span>
                </div>
            </button>

        </div>
    </div>
</template>
