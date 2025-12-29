<template>
  <article
    class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 p-6 mb-4"
  >
    <!-- Post Header -->
    <div class="flex items-start justify-between mb-4">
      <div class="flex items-center space-x-3">
        <!-- Avatar -->
        <img
          :src="post.author.avatar"
          :alt="post.author.name"
          class="w-12 h-12 rounded-full object-cover"
        />

        <!-- Author Info -->
        <div>
          <h5 class="font-semibold text-secondary-900 hover:text-primary-600 cursor-pointer">
            {{ post.author.name }}
          </h5>
          <span class="text-xs text-secondary-500">{{ post.timestamp }}</span>
        </div>
      </div>

      <!-- More Options -->
      <div class="relative">
        <button
          @click="showOptions = !showOptions"
          class="p-2 hover:bg-secondary-100 rounded-full transition-colors duration-200"
        >
          <i class="pi pi-ellipsis-h text-xl text-secondary-600"></i>
        </button>

        <Transition name="scale">
          <div
            v-if="showOptions"
            class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-secondary-200 py-2 z-10"
          >
            <button
              class="flex items-center space-x-2 px-4 py-2 hover:bg-secondary-50 w-full text-left text-sm"
            >
              <i class="pi pi-trash text-secondary-600"></i>
              <span>Delete Post</span>
            </button>
            <button
              class="flex items-center space-x-2 px-4 py-2 hover:bg-secondary-50 w-full text-left text-sm"
            >
              <i class="pi pi-times text-secondary-600"></i>
              <span>Hide Post</span>
            </button>
            <button
              class="flex items-center space-x-2 px-4 py-2 hover:bg-secondary-50 w-full text-left text-sm"
            >
              <i class="pi pi-pencil text-secondary-600"></i>
              <span>Edit Post</span>
            </button>
          </div>
        </Transition>
      </div>
    </div>

    <!-- Post Content -->
    <div class="mb-4">
      <p class="text-secondary-700 text-sm leading-relaxed whitespace-pre-line">
        {{ post.content }}
      </p>
    </div>

    <!-- Post Media -->
    <div v-if="post.image" class="mb-4 rounded-lg overflow-hidden">
      <img
        :src="post.image"
        alt="Post image"
        class="w-full h-auto object-cover hover:scale-105 transition-transform duration-300 cursor-pointer"
        @click="$emit('view-image', post.image)"
      />
    </div>

    <div v-if="post.video" class="mb-4 rounded-lg overflow-hidden aspect-video">
      <iframe :src="post.video" allowfullscreen class="w-full h-full"></iframe>
    </div>

    <div v-if="post.album" class="mb-4 grid grid-cols-2 gap-2">
      <img
        v-for="(img, index) in post.album.slice(0, 2)"
        :key="index"
        :src="img"
        alt="Album"
        class="w-full h-48 object-cover rounded-lg hover:scale-105 transition-transform duration-300 cursor-pointer"
      />
      <div v-if="post.album.length > 2" class="relative">
        <img :src="post.album[1]" alt="More photos" class="w-full h-48 object-cover rounded-lg" />
        <div class="absolute inset-0 bg-black/60 flex items-center justify-center rounded-lg">
          <span class="text-white text-2xl font-bold">+{{ post.album.length - 2 }}</span>
        </div>
      </div>
    </div>

    <!-- Reactions Summary -->
    <div class="flex items-center justify-between py-3 border-t border-b border-secondary-100">
      <!-- Emoji Reactions -->
      <div class="flex items-center space-x-1">
        <img src="/images/svg/thumb.png" alt="Like" class="w-5 h-5" />
        <img src="/images/svg/heart.png" alt="Love" class="w-5 h-5" />
        <img src="/images/svg/smile.png" alt="Smile" class="w-5 h-5" />
        <img src="/images/svg/weep.png" alt="Sad" class="w-5 h-5" />
        <p class="text-sm font-semibold text-secondary-700 ml-1">{{ post.reactions }}</p>
      </div>

      <!-- Comments & Shares Count -->
      <div class="flex items-center space-x-4 text-sm text-secondary-600">
        <span class="flex items-center space-x-1">
          <i class="pi pi-comment"></i>
          <span>{{ post.commentsCount }} comments</span>
        </span>
        <span class="flex items-center space-x-1">
          <i class="pi pi-share-alt"></i>
          <span>{{ post.sharesCount }} shares</span>
        </span>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center justify-around py-3 border-b border-secondary-100">
      <!-- Like Button with Emoji Reactions -->
      <div class="relative group">
        <button
          @click="toggleLike"
          class="flex items-center space-x-2 px-4 py-2 hover:bg-secondary-50 rounded-lg transition-colors duration-200"
          :class="{ 'text-primary-600': isLiked }"
        >
          <i class="pi pi-heart text-lg"></i>
          <span class="font-medium text-sm">Like</span>
        </button>

        <!-- Emoji Reactions Popup -->
        <div
          class="absolute bottom-full left-0 mb-2 hidden group-hover:flex items-center space-x-2 bg-white rounded-full shadow-xl px-3 py-2 border border-secondary-200"
        >
          <button
            v-for="emoji in emojiReactions"
            :key="emoji.name"
            @click.stop="reactWithEmoji(emoji.name)"
            class="emoji-reaction hover:scale-125 transition-transform duration-200"
            :title="emoji.name"
          >
            <img :src="emoji.icon" :alt="emoji.name" class="w-8 h-8" />
          </button>
        </div>
      </div>

      <!-- Comment Button -->
      <button
        @click="toggleComments"
        class="flex items-center space-x-2 px-4 py-2 hover:bg-secondary-50 rounded-lg transition-colors duration-200"
      >
        <i class="pi pi-comment text-lg"></i>
        <span class="font-medium text-sm">Comment</span>
      </button>

      <!-- Share Button -->
      <button
        @click="$emit('share', post)"
        class="flex items-center space-x-2 px-4 py-2 hover:bg-secondary-50 rounded-lg transition-colors duration-200"
      >
        <i class="pi pi-share-alt text-lg"></i>
        <span class="font-medium text-sm">Share</span>
      </button>
    </div>

    <!-- New Comment Input -->
    <Transition name="slide-down">
      <div v-if="showCommentInput" class="py-4 border-b border-secondary-100">
        <form @submit.prevent="submitComment" class="flex items-center space-x-3">
          <input
            v-model="newComment"
            type="text"
            placeholder="Write a comment..."
            class="flex-1 px-4 py-2 border border-secondary-300 rounded-full focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm"
          />
          <button
            type="submit"
            class="p-2 bg-primary-500 text-white rounded-full hover:bg-primary-600 transition-colors duration-200"
          >
            <i class="pi pi-send text-lg"></i>
          </button>
        </form>
      </div>
    </Transition>

    <!-- Comments List -->
    <Transition name="slide-down">
      <div v-if="showComments && post.comments && post.comments.length > 0" class="py-4 space-y-4">
        <div v-for="comment in post.comments" :key="comment.id" class="flex items-start space-x-3">
          <img
            :src="comment.author.avatar"
            :alt="comment.author.name"
            class="w-8 h-8 rounded-full object-cover flex-shrink-0"
          />

          <div class="flex-1">
            <div class="bg-secondary-100 rounded-lg px-4 py-2">
              <h6 class="font-semibold text-sm text-secondary-900">
                {{ comment.author.name }}
              </h6>
              <span class="text-xs text-secondary-500">{{ comment.timestamp }}</span>
              <p class="text-sm text-secondary-700 mt-1">{{ comment.content }}</p>
            </div>

            <div class="flex items-center space-x-4 mt-2 text-xs">
              <button class="text-primary-600 hover:text-primary-700 font-medium">
                <i class="pi pi-heart"></i> Like
              </button>
              <button class="text-secondary-600 hover:text-secondary-700 font-medium">
                <i class="pi pi-reply"></i> Reply
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </article>
</template>

<script setup lang="ts">
interface Author {
  name: string
  avatar: string
}

interface Comment {
  id: number
  author: Author
  content: string
  timestamp: string
}

interface Post {
  id: number
  author: Author
  content: string
  timestamp: string
  image?: string
  video?: string
  album?: string[]
  reactions: number
  commentsCount: number
  sharesCount: number
  comments?: Comment[]
}

defineProps<{
  post: Post
}>()

defineEmits<{
  'view-image': [url: string]
  share: [post: Post]
}>()

const showOptions = ref(false)
const showComments = ref(false)
const showCommentInput = ref(false)
const isLiked = ref(false)
const newComment = ref('')

const emojiReactions = [
  { name: 'like', icon: '/images/svg/thumb.png' },
  { name: 'love', icon: '/images/svg/heart.png' },
  { name: 'haha', icon: '/images/svg/smile.png' },
  { name: 'sad', icon: '/images/svg/weep.png' },
  { name: 'angry', icon: '/images/svg/angry.png' },
]

const toggleLike = () => {
  isLiked.value = !isLiked.value
}

const reactWithEmoji = (emojiName: string) => {
  isLiked.value = true
}

const toggleComments = () => {
  showComments.value = !showComments.value
  showCommentInput.value = !showCommentInput.value
}

const submitComment = () => {
  if (newComment.value.trim()) {
    newComment.value = ''
  }
}
</script>

<style scoped>
.scale-enter-active,
.scale-leave-active {
  transition: all 0.2s ease;
}

.scale-enter-from,
.scale-leave-to {
  transform: scale(0.95);
  opacity: 0;
}

.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease;
}

.slide-down-enter-from,
.slide-down-leave-to {
  transform: translateY(-10px);
  opacity: 0;
}
</style>
