<script setup>
import { ref, reactive, onMounted } from 'vue';
import MainLayout from '~/layouts/main.vue';
import SinglePostViewer from '@/PlearndComponents/play/posts/SinglePostViewer.vue';
import PostSkeleton from '@/components/PostSkeleton.vue';

const props = defineProps({
    'post_id': Number,
});

const isLoadingPost = ref(true);
const activity = ref(null);

onMounted(() => {
    getPost();
});

const getPost = async () => {
    const response = await axios.get(`/posts/${props.post_id}/getPostActivity`);

    activity.value = response.data.activity;
    isLoadingPost.value = false;
};

</script>

<template>
    <div>
        <MainLayout  title="Post/โพสต์">
            <template #mainContent>
                <div v-if="isLoadingPost && !activity">
                    <PostSkeleton />
                </div>
                <div v-else>
                    <SinglePostViewer :activity />
                </div>
            </template>
        </MainLayout>
    </div>
</template>

