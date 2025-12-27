<template>
  <section class="container mx-auto px-4 py-6">
    <h5 class="text-lg font-bold text-secondary-900 mb-4">Stories</h5>

    <!-- Stories Carousel -->
    <div class="flex space-x-4 overflow-x-auto scrollbar-hide pb-4">
      <div
        v-for="story in stories"
        :key="story.id"
        @click="openStory(story)"
        class="flex-shrink-0 cursor-pointer group"
      >
        <div class="flex flex-col items-center space-y-2">
          <!-- Avatar with Status -->
          <div class="relative">
            <img
              :src="story.avatar"
              :alt="story.name"
              class="w-16 h-16 rounded-full object-cover border-2 border-white shadow-md group-hover:scale-110 transition-transform duration-200"
            />
            <!-- Status Indicator -->
            <span
              class="absolute bottom-0 right-0 w-4 h-4 rounded-full border-2 border-white"
              :class="{
                'bg-primary-500': story.status === 'live',
                'bg-amber-400': story.status === 'away',
                'bg-green-500': story.status === 'online',
                'bg-secondary-300': !story.status,
              }"
            ></span>
          </div>

          <!-- Name -->
          <span class="text-xs text-secondary-700 font-medium text-center max-w-[70px] truncate">
            {{ story.name }}
          </span>
        </div>
      </div>
    </div>

    <!-- Story Viewer Modal -->
    <Transition name="fade">
      <div
        v-if="activeStory"
        class="fixed inset-0 z-[9999] bg-black flex items-center justify-center"
        @click="closeStory"
      >
        <button
          class="absolute top-4 right-4 text-white hover:text-primary-500 transition-colors duration-200"
          @click.stop="closeStory"
        >
          <Icon icon="mdi:close-circle" class="w-10 h-10" />
        </button>

        <div class="relative w-full max-w-md h-[80vh]">
          <!-- Story Content -->
          <img
            :src="activeStory.image"
            :alt="activeStory.name"
            class="w-full h-full object-contain"
          />

          <!-- Progress Bars -->
          <div class="absolute top-4 left-4 right-4 flex space-x-1">
            <div
              v-for="i in 4"
              :key="i"
              class="flex-1 h-1 bg-white/30 rounded-full overflow-hidden"
            >
              <div class="h-full bg-white rounded-full w-1/2"></div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </section>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Story {
  id: number
  name: string
  avatar: string
  status?: 'live' | 'away' | 'online'
  image?: string
}

const stories = ref<Story[]>([
  { id: 1, name: 'Xing chig', avatar: '/images/resources/user2.jpg', status: 'live' },
  { id: 2, name: 'Daniel', avatar: '/images/resources/user1.jpg', status: 'away' },
  { id: 3, name: 'Rita Devi', avatar: '/images/resources/user3.jpg', status: 'online' },
  { id: 4, name: 'Aisha', avatar: '/images/resources/user4.jpg' },
  { id: 5, name: 'Emily', avatar: '/images/resources/user5.jpg', status: 'online' },
  { id: 6, name: 'Turgut', avatar: '/images/resources/user6.jpg', status: 'online' },
])

const activeStory = ref<Story | null>(null)

const openStory = (story: Story) => {
  activeStory.value = {
    ...story,
    image: 'http://placehold.it/856x380/FF6347/FFFFFF&text=Story',
  }
}

const closeStory = () => {
  activeStory.value = null
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
