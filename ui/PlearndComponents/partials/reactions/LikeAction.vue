<template>
    <div>
    <!-- <form action="" @submit.prevent="" :id="`form-like-post-${activity.id}`"> -->
    <button type="button" @click.prevent="handleSubmit" class="flex items-center space-x-1 text-sm text-gray-800 rounded-md px-2 py-1 hover:bg-blue-300 hover:scale-110 cursor-pointer">
            <!-- <Icon icon="heroicons-solid:thumb-up" /> -->
            <Icon icon="heroicons-outline:thumb-up" class="w-6 h-6 text-gray-600" />
            <span>ถูกใจ</span>
    </button>
    <!-- </form> -->
    </div>
</template>

<script setup>
    import { useForm } from "@inertiajs/vue3";
    import { Icon } from '@iconify/vue';

    const props = defineProps({
        desModel: { type: String, default: 'posts'},
        desModelId: { type: String, default: ''},
        actionType: { type: String, default: 'like'},
    });

    const likePostForm = useForm({
        post:''
    });
    const handleSubmit = () => {
        likePostForm.post(`/${props.desModel}/${props.desModelId}/${props.actionType}`,{ preserveScroll: true });
    };
</script>
