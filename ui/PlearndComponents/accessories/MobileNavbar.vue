<script setup>
import { ref } from 'vue';
import { router } from "@inertiajs/vue3";
import { Icon } from '@iconify/vue';


const props = defineProps({
    navigation: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['go-to-page', 'set-current-active-column']);

const isLoadingPage = ref(false);

const handleLinkToPage = (href) => {
    // isLoadingPage.value = true;
    emit('go-to-page', href);
    // emit('set-current-active-column', href);
};

</script>

<template>
    <div class="bg-[#34465d] p-1 mt-14 shadow-xl mx-auto w-full md:hidden">
        <div class="mx-auto flex w-full flex-row items-center justify-around h-11">
            <!-- <Link href="/">
                <SVGHome class="h-6 w-6 " />
            </Link>
            <Link href="#">
                <svg xmlns="http://www.w3.org/2000/svg" height="44" width="44">
                    <path d="M12 40q-3.3 0-5.65-2.35Q4 35.3 4 32V16q0-3.3 2.35-5.65Q8.7 8 12 8h24q3.3 0 5.65 2.35Q44 12.7 44 16v16q0 3.3-2.35 5.65Q39.3 40 36 40Zm0-23.5h24q1.45 0 2.725.45Q40 17.4 41 18.25V16q0-2.1-1.45-3.55Q38.1 11 36 11H12q-2.1 0-3.55 1.45Q7 13.9 7 16v2.25q1-.85 2.275-1.3Q10.55 16.5 12 16.5Zm-4.85 6.8L31 29.05q.35.1.725.025.375-.075.625-.325l8-6.7q-.65-1.15-1.8-1.85-1.15-.7-2.55-.7H12q-1.75 0-3.1 1.075T7.15 23.3Z" />
                </svg>
            </Link>
            <Link href="#">
                <svg xmlns="http://www.w3.org/2000/svg" height="44" width="44">
                    <path d="M12 28.05h15.65v-3H12Zm0-6.5h24v-3H12Zm0-6.5h24v-3H12ZM4 44V7q0-1.15.9-2.075Q5.8 4 7 4h34q1.15 0 2.075.925Q44 5.85 44 7v26q0 1.15-.925 2.075Q42.15 36 41 36H12Zm3-7.25L10.75 33H41V7H7ZM7 7v29.75Z" />
                </svg>
            </Link>
            <Link href="#">
                <svg xmlns="http://www.w3.org/2000/svg" height="44" width="44">
                    <path d="M24 23.95q-3.3 0-5.4-2.1-2.1-2.1-2.1-5.4 0-3.3 2.1-5.4 2.1-2.1 5.4-2.1 3.3 0 5.4 2.1 2.1 2.1 2.1 5.4 0 3.3-2.1 5.4-2.1 2.1-5.4 2.1ZM8 40v-4.7q0-1.9.95-3.25T11.4 30q3.35-1.5 6.425-2.25Q20.9 27 24 27q3.1 0 6.15.775 3.05.775 6.4 2.225 1.55.7 2.5 2.05.95 1.35.95 3.25V40Zm3-3h26v-1.7q0-.8-.475-1.525-.475-.725-1.175-1.075-3.2-1.55-5.85-2.125Q26.85 30 24 30t-5.55.575q-2.7.575-5.85 2.125-.7.35-1.15 1.075Q11 34.5 11 35.3Zm13-16.05q1.95 0 3.225-1.275Q28.5 18.4 28.5 16.45q0-1.95-1.275-3.225Q25.95 11.95 24 11.95q-1.95 0-3.225 1.275Q19.5 14.5 19.5 16.45q0 1.95 1.275 3.225Q22.05 20.95 24 20.95Zm0-4.5ZM24 37Z" />
                </svg>
            </Link> -->
            <button v-for="(navbarMenu, index) in navigation" :key="navbarMenu.name" @click.prevent="handleLinkToPage(navbarMenu.href)"
                :class="[$page.url.startsWith(navbarMenu.href) ? 'bg-violet-500 text-white' : 'text-gray-300 hover:bg-violet-700 hover:text-white', 'flex items-center justify-center rounded-md px-3 py-2 text-sm font-medium']"
                :aria-current="navbarMenu.current ? 'page' : undefined">
                <Icon :icon="navbarMenu.icon" class="w-6 h-6 mr-2" />
                <span class="hidden lg:block">
                    {{ navbarMenu.name }}
                </span>
            </button>
        </div>
    </div>
</template>
