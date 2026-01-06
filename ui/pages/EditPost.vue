<script setup>
import { ref, onMounted } from 'vue'
import MainLayout from '~/layouts/main.vue'
import PostSkeleton from '~/components/PostSkeleton.vue'
import EditPostForm from '@/components/widgets/EditPostForm.vue'

const props = defineProps({
  post_id: Number,
})

const isLoadingPost = ref(true)
const activity = ref(null)

onMounted(() => {
  getPost()
})

const getPost = async () => {
  const response = await axios.get(`/posts/${props.post_id}/getPostActivity`)
  activity.value = response.data.activity
  isLoadingPost.value = false
}
</script>

<template>
  <div>
    <MainLayout title="Edit Post/แก้ไขโพสต์">
      <template #mainContent>
        <div v-if="isLoadingPost && !activity">
          <PostSkeleton />
        </div>
        <div v-else class="my-4">
          <EditPostForm :activity />
        </div>
      </template>
    </MainLayout>
  </div>
</template>
