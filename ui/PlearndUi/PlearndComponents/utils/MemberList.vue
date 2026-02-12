<template>
  <div class="w-full grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-4">
    <div 
      v-for="member in members" 
      :key="member.id"
      class="member-item"
    >
      <MemberCard :user="member" />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import MemberCard from './MemberCard.vue'

const members = ref([
  {
    id: 1,
    name: 'NekoBebop',
    title: 'Game Developer',
    link: 'WWW.NEKOBEBOP.COM',
    level: 12,
    profileImage: '/storage/images/avatar/05.jpg',
    coverImage: 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=800&q=80',
    educationLevel: 'elementary', // ประถมศึกษา
    gradeLevel: 1, // ชั้นปีที่ 6
    borderLayers: 2, // 1 ชั้น (ประถม)
    completedSides: 1, // เสร็จครบ 6 ด้าน
    stats: {
      posts: 456,
      friends: 89,
      visits: '2.3K'
    },
    badges: [
      { icon: '🎮', color: 'bg-purple-600', count: null },
      { icon: '⚡', color: 'bg-blue-500', count: null },
      { icon: '🔥', color: 'bg-red-500', count: null },
      { icon: '🚀', color: 'bg-gray-400', count: '+5' }
    ]
  },
  {
    id: 2,
    name: 'Destroy Dex',
    title: 'Streamer',
    link: 'WWW.TWITCH.TV/D-DESTROYER',
    level: 13,
    profileImage: '/storage/images/avatar/02.jpg',
    coverImage: 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=800&q=80',
    educationLevel: 'secondary', // มัธยมศึกษา
    gradeLevel: 1, // ชั้น ม.1
    borderLayers: 2, // 2 ชั้น (มัธยม)
    completedSides: 1, // เสร็จ 1 ด้าน
    stats: {
      posts: 890,
      friends: 79,
      visits: '4.6K'
    },
    badges: [
      { icon: '👑', color: 'bg-yellow-500', count: null },
      { icon: '🎮', color: 'bg-purple-600', count: null },
      { icon: '⚡', color: 'bg-blue-500', count: null },
      { icon: '🔥', color: 'bg-red-500', count: null },
      { icon: '🚀', color: 'bg-gray-400', count: '+12' }
    ]
  },
  {
    id: 3,
    name: 'CodeMaster',
    title: 'Full Stack Developer',
    link: 'GITHUB.COM/CODEMASTER',
    level: 25,
    profileImage: '/storage/images/avatar/03.jpg',
    coverImage: 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=800&q=80',
    educationLevel: 'university', // ปริญญาตรี
    gradeLevel: 2, // ปี 2
    borderLayers: 3, // 3 ชั้น (ปริญญา)
    completedSides: 2, // เสร็จ 2 ด้าน
    stats: {
      posts: 1234,
      friends: 156,
      visits: '8.9K'
    },
    badges: [
      { icon: '💻', color: 'bg-green-600', count: null },
      { icon: '🏆', color: 'bg-yellow-500', count: null },
      { icon: '⭐', color: 'bg-blue-500', count: null },
      { icon: '🚀', color: 'bg-purple-600', count: '+20' }
    ]
  },
  {
    id: 4,
    name: 'PixelArtist',
    title: 'UI/UX Designer',
    link: 'DRIBBBLE.COM/PIXELARTIST',
    level: 8,
    profileImage: '/storage/images/avatar/04.jpg',
    coverImage: 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800&q=80',
    educationLevel: 'elementary', // ประถมศึกษา
    gradeLevel: 2, // ชั้น ป.2
    borderLayers: 1, // 1 ชั้น
    completedSides: 2, // เสร็จ 2 ด้าน
    stats: {
      posts: 342,
      friends: 67,
      visits: '1.8K'
    },
    badges: [
      { icon: '🎨', color: 'bg-pink-500', count: null },
      { icon: '✨', color: 'bg-purple-400', count: null },
      { icon: '🌈', color: 'bg-blue-400', count: null },
      { icon: '🚀', color: 'bg-gray-400', count: '+8' }
    ]
  },
  {
    id: 5,
    name: 'DataWizard',
    title: 'Data Scientist',
    link: 'LINKEDIN.COM/IN/DATAWIZARD',
    level: 42,
    profileImage: '/storage/images/avatar/01.jpg',
    coverImage: 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=800&q=80',
    educationLevel: 'university', // ปริญญาตรี
    gradeLevel: 4, // ปี 4
    borderLayers: 3, // 3 ชั้น
    completedSides: 4, // เสร็จ 4 ด้าน
    stats: {
      posts: 2156,
      friends: 234,
      visits: '12.5K'
    },
    badges: [
      { icon: '📊', color: 'bg-blue-600', count: null },
      { icon: '🧠', color: 'bg-purple-600', count: null },
      { icon: '🔬', color: 'bg-green-600', count: null },
      { icon: '🏅', color: 'bg-yellow-500', count: null },
      { icon: '🚀', color: 'bg-gray-400', count: '+25' }
    ]
  },
  {
    id: 6,
    name: 'CloudNinja',
    title: 'DevOps Engineer',
    link: 'CLOUDNINJA.TECH',
    level: 35,
    profileImage: '/storage/images/avatar/06.jpg',
    coverImage: 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=800&q=80',
    educationLevel: 'secondary', // มัธยมศึกษา
    gradeLevel: 6, // ชั้น ม.6
    borderLayers: 2, // 2 ชั้น
    completedSides: 6, // เสร็จครบ 6 ด้าน
    stats: {
      posts: 1567,
      friends: 198,
      visits: '9.7K'
    },
    badges: [
      { icon: '☁️', color: 'bg-sky-500', count: null },
      { icon: '⚙️', color: 'bg-gray-600', count: null },
      { icon: '🔧', color: 'bg-orange-500', count: null },
      { icon: '🏆', color: 'bg-yellow-500', count: null },
      { icon: '🚀', color: 'bg-gray-400', count: '+18' }
    ]
  }
])
</script>
