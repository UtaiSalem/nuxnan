<template>
  <div class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-shadow">
    <div class="md:flex">
      <!-- Event Image -->
      <div class="md:w-1/3 h-48 md:h-auto">
        <img :src="event.image" :alt="event.title" class="w-full h-full object-cover" />
      </div>

      <!-- Event Info -->
      <div class="p-4 md:w-2/3 flex flex-col">
        <div class="flex-1">
          <h3 class="text-xl font-bold text-secondary-900 mb-2">{{ event.title }}</h3>
          <p class="text-sm text-secondary-600 mb-4">{{ event.description }}</p>

          <div class="space-y-2 mb-4">
            <div class="flex items-center gap-2 text-sm text-secondary-700">
              <Icon icon="mdi:calendar" class="w-5 h-5 text-primary-500" />
              <span>{{ formatDate(event.date) }} at {{ event.time }}</span>
            </div>

            <div class="flex items-center gap-2 text-sm text-secondary-700">
              <Icon icon="mdi:map-marker" class="w-5 h-5 text-primary-500" />
              <span>{{ event.location }}</span>
            </div>

            <div class="flex items-center gap-2 text-sm text-secondary-700">
              <Icon icon="mdi:account-group" class="w-5 h-5 text-primary-500" />
              <span>{{ event.attendees }} people going</span>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-2">
          <button
            v-if="event.isGoing"
            class="flex-1 px-4 py-2 bg-secondary-200 text-secondary-700 rounded-lg font-medium hover:bg-secondary-300 transition-colors"
          >
            <Icon icon="mdi:check" class="inline w-4 h-4 mr-1" />
            Going
          </button>
          <button
            v-else
            class="flex-1 px-4 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition-colors"
          >
            <Icon icon="mdi:calendar-check" class="inline w-4 h-4 mr-1" />
            Join Event
          </button>

          <button
            class="px-4 py-2 bg-secondary-100 text-secondary-700 rounded-lg hover:bg-secondary-200 transition-colors"
          >
            <Icon icon="mdi:share-variant" class="w-5 h-5" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  event: {
    id: number
    title: string
    description: string
    image: string
    date: string
    time: string
    location: string
    attendees: number
    isGoing: boolean
  }
}

defineProps<Props>()

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}
</script>
