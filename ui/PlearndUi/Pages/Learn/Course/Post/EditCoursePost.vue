<script setup>
import { ref, reactive, onMounted } from 'vue';
import MainLayout from '@/Layouts/MainLayout.vue';
// import SingleCoursePostViewer from '@/PlearndComponents/learn/courses/posts/SingleCoursePostViewer.vue';
import PostSkeleton from '@/Components/PostSkeleton.vue';
import EditCoursePostForm from '@/PlearndComponents/learn/courses/posts/EditCoursePostForm.vue';

const props = defineProps({
    'activity': Object,
});

const isLoadingPost = ref(true);

</script>

<template>
    <div>
        <MainLayout  title="Post/โพสต์">
            <template #mainContent>
                <div v-if="isLoadingPost && !activity">
                    <PostSkeleton />
                </div>
                <div v-else>
                    <EditCoursePostForm :activity="props.activity.data" />
                </div>
            </template>
        </MainLayout>
    </div>
</template>
