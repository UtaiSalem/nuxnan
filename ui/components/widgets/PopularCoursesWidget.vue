<script setup>
import { Icon } from '@iconify/vue'

const courses = ref([
  {
    id: 1,
    title: 'WordPress Online Video Course',
    image: '/images/course-1.jpg',
    price: '$19/M',
    tag: 'Free',
    link: '/courses/wordpress'
  },
  {
    id: 2,
    title: 'Node JS Online Video Course',
    image: '/images/course-2.jpg',
    price: '$29/M',
    tag: 'Premium',
    link: '/courses/nodejs'
  }
])
</script>

<template>
  <div class="vikinger-card">
    <h4 class="text-sm font-semibold mb-4 flex items-center gap-2 text-gray-700 dark:text-gray-300">
      <Icon icon="fluent:fire-24-filled" class="w-4 h-4 text-orange-500" />
      Popular Courses
    </h4>
    <ul class="space-y-3">
      <li v-for="course in courses" :key="course.id" class="flex gap-3">
        <figure class="relative w-20 h-16 flex-shrink-0 rounded-lg overflow-hidden">
          <img :src="course.image" :alt="course.title" class="w-full h-full object-cover" />
          <span class="absolute top-1 right-1 px-2 py-0.5 text-xs font-bold rounded" 
                :class="course.tag === 'Free' ? 'bg-green-500 text-white' : 'bg-vikinger-purple text-white'">
            {{ course.tag }}
          </span>
        </figure>
        <div class="flex-1 min-w-0">
          <h5 class="text-sm font-semibold text-gray-800 dark:text-white mb-1 line-clamp-2">
            <NuxtLink :to="course.link" class="hover:text-vikinger-purple transition-colors">
              {{ course.title }}
            </NuxtLink>
          </h5>
          <span class="text-sm font-bold text-vikinger-purple">{{ course.price }}</span>
        </div>
      </li>
    </ul>
  </div>
</template>
