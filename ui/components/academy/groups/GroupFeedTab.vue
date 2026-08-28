<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import { useAcademyGroups } from '~/composables/useAcademyGroups'
import PlayFeedCreatePostBox from '~/components/play/feed/CreatePostBox.vue'
import FeedPost from '~/components/play/feed/FeedPost.vue'

interface Props {
  group: any
  /** Can the current user post in this group? */
  canPost: boolean
  isAcademyAdmin?: boolean
}
const props = defineProps<Props>()

const { listGroupPosts } = useAcademyGroups()

const posts = ref<any[]>([])
const isLoading = ref(true)
const hasMore = ref(false)
const currentPage = ref(1)

const load = async (page = 1) => {
  if (page === 1) isLoading.value = true
  try {
    const res: any = await listGroupPosts(props.group.id, { per_page: 10, page })
    const data = res?.data
    if (page === 1) {
      posts.value = data?.data ?? []
    } else {
      posts.value.push(...(data?.data ?? []))
    }
    currentPage.value = data?.current_page ?? page
    hasMore.value = (data?.current_page ?? 0) < (data?.last_page ?? 0)
  } catch (e) {
    console.error('Failed to load group posts:', e)
  } finally {
    isLoading.value = false
  }
}

const loadMore = () => load(currentPage.value + 1)
const onPostCreated = (activity: any) => {
  // If the activity has a target_resource, prep it or prepend directly
  posts.value.unshift(activity)
}
const onPostDeleted = (id: number) => {
  posts.value = posts.value.filter((p) => p.id !== id && (p.target_resource?.id !== id))
}

onMounted(() => load(1))
</script>

<template>
  <div class="space-y-4">
    <!-- Composer (only if canPost) -->
    <PlayFeedCreatePostBox
      v-if="canPost"
      context="academy"
      :context-id="group.academy_id"
      :context-name="group.name"
      :locked-group-id="group.id"
      :is-academy-admin="isAcademyAdmin"
      @post-created="onPostCreated"
    />

    <!-- Info panel for non-members -->
    <div
      v-else
      class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/50 rounded-xl p-4 text-sm text-blue-700 dark:text-blue-300 flex items-center gap-2"
    >
      <Icon icon="heroicons:information-circle" class="w-5 h-5 flex-shrink-0" />
      <span class="font-medium">เฉพาะสมาชิกของส่วนงานนี้ที่มีสิทธิ์โพสต์เท่านั้นที่สามารถเขียนข่าวสารในนามส่วนงานได้</span>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="py-10 text-center">
      <Icon icon="svg-spinners:ring-resize" class="w-8 h-8 text-vikinger-purple mx-auto" />
    </div>

    <!-- Empty -->
    <div
      v-else-if="posts.length === 0"
      class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 sm:p-8 text-center border border-gray-200 dark:border-gray-700 shadow-sm"
    >
      <Icon icon="heroicons:document-text" class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
      <p class="text-gray-500 dark:text-gray-400 font-bold">ยังไม่มีโพสต์ในส่วนงานนี้</p>
      <p v-if="canPost" class="text-sm text-gray-400 dark:text-gray-500 mt-1 font-medium">เริ่มเขียนโพสต์แรกเพื่อแบ่งปันข้อมูลข่าวสารในส่วนงานของคุณ</p>
    </div>

    <!-- Feed -->
    <FeedPost
      v-for="post in posts"
      :key="post.id"
      :post="post"
      @delete-success="onPostDeleted"
    />

    <!-- Load more -->
    <div v-if="hasMore" class="text-center py-4">
      <button
        type="button"
        class="min-h-[44px] sm:min-h-0 px-4 sm:px-6 py-2.5 bg-white dark:bg-vikinger-dark-200 text-gray-700 dark:text-gray-300 rounded-lg font-bold shadow-sm border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-colors"
        @click="loadMore"
      >
        โหลดเพิ่มเติม
      </button>
    </div>
  </div>
</template>
