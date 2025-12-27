<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Icon } from '@iconify/vue';
import axios from 'axios';
const props = defineProps({
    member_id: Number,
    course_id: Number,
    notes_comments: String,
})

const emit = defineEmits(['update:notesComments']);

// const localNotesComments = ref(props.notesComments);
const isLoading = ref(false);
const isShowNotesComments = ref(false);
const modalRef = ref(null);

const form = ref({
    notes_comments: props.notes_comments,
})

const handleNotesCommentsInput = () => {
    // console.log(form.value.notes_comments);
    emit('update:notesComments', form.value.notes_comments);
}

async function handleNotesCommentsFormSubmit () {
    try {
        isLoading.value = true;
        const response = await axios.patch(`/courses/${props.course_id}/members/${props.member_id}/notes-comments`, form.value);
        if (response.data &&response.data.success) {
            emit('update:notesComments', form.value.notes_comments);
            // console.log(response.data.success);
            // console.log(response.data.notes_comments);
            isLoading.value = false;
        }
        
    } catch (error) {
        console.log(error);
        isLoading.value = false;
    }
}

const handleClickOutside = (event) => {
    if (modalRef.value && !modalRef.value.contains(event.target)) {
        isShowNotesComments.value = false;
    }
}

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
});

</script>

<template>
    <div>
        <!-- Component: Modal with title, text and an action button -->
        <button 
            @click.prevent="isShowNotesComments = true"
            class="block focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
            :class="{ 'text-white bg-blue-600 hover:bg-blue-700 focus:ring-blue-800': props.notes_comments }"
            type="button"
            >
            <Icon icon="fa-regular:edit" class="w-4 h-4"></Icon>
        </button>


        <!-- Modal backdrop -->
        <div v-if="isShowNotesComments" 
            class="fixed top-0 left-0 z-20 flex items-center justify-center w-screen h-screen bg-slate-800/80 backdrop-blur-sm"
            aria-labelledby="header-2a content-2a" aria-modal="true" tabindex="-1" role="dialog">
            <!-- Modal -->
            <div ref="modalRef"
                class="flex max-h-[90vh] w-11/12 max-w-md flex-col overflow-hidden rounded bg-white p-4 text-slate-500 shadow-xl shadow-slate-700/10">
                <!-- Modal header -->
                <header :id="`header-${props.member_id}-notes-comments`" class="flex items-center gap-2">
                    <h3 class="flex-1 text-lg font-semibold text-gray-900 dark:text-white">
                            บันทึกย่อ
                        </h3>
                    <button @click="isShowNotesComments = false"
                        class="inline-flex items-center justify-center h-10 gap-2 px-5 text-sm font-medium tracking-wide transition duration-300 rounded-full focus-visible:outline-none justify-self-center whitespace-nowrap text-red-500 hover:bg-red-100 hover:text-red-600 focus:bg-red-200 focus:text-red-700 disabled:cursor-not-allowed disabled:text-red-300 disabled:shadow-none disabled:hover:bg-transparent"
                        :id="`modal-${props.member_id}-notes-comments`" role="document" aria-label="close dialog">
                        <span class="relative only:-mx-5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="1.5" role="graphics-symbol"
                                aria-labelledby="title-79 desc-79">
                                <title id="title-79">Icon title</title>
                                <desc id="desc-79">A more detailed description of the icon</desc>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </span>
                    </button>
                </header>
                <!-- Modal body -->
                <form class="mt-4" @submit.prevent="handleNotesCommentsFormSubmit">
                    <div class="grid gap-4 mb-4 grid-cols-2">
                        <div class="col-span-2">
                            <textarea :id="`notes-comments-form-input-${props.member_id}`" rows="3" v-model="form.notes_comments" @input="handleNotesCommentsInput"
                                class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            ></textarea>
                        </div>
                    </div>
                    <button type="submit" :disabled="isLoading"
                        class="text-white inline-flex items-center bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-3 py-2 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                        :data-modal-toggle="`crud-modal-${props.member_id}}-notes-comments`"
                        >
                        <Icon icon="icomoon-free:spinner" class="w-5 h-5 animate-spin" v-if="isLoading" />
                        <Icon icon="fa-regular:save" class="w-4 h-4 mx-1" v-else />
                        บันทึก
                    </button>
                </form>
            </div>
        </div>
        <!-- End Modal with title, text and an action button -->
    </div>
</template>
