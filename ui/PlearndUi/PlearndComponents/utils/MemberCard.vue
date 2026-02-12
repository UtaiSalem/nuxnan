<template>
  <div class="flex items-center justify-center min-h-screen bg-gradient-to-br from-gray-100 to-gray-200 p-4">
    <div class="w-full max-w-sm bg-white rounded-3xl shadow-2xl overflow-hidden">
      <!-- Cover Image -->
      <div class="relative h-40">
        <img 
          :src="userData.coverImage" 
          alt="Cover" 
          class="w-full h-full object-cover"
        />
        <div class="absolute inset-0 bg-gradient-to-b from-transparent to-black/20"></div>
      </div>

      <!-- Profile Picture Section with Hexagon -->
      <div class="relative px-6 pb-6">
        <div class="flex justify-center -mt-20 mb-4">
          <div class="relative">
            <!-- Hexagon Profile Picture Container -->
            <HexagonAvatar 
              :image-src="userData.profileImage"
              :level="userData.level"
              :width="132"
              :height="136"
              :line-width="8"
              :show-badge="true"
              :badge-size="48"
              :education-level="userData.educationLevel"
              :border-layers="1"
              
              :responsive="false"
              @loaded="onAvatarLoaded"
            />
          </div>
        </div>

        <!-- User Info -->
        <div class="text-center mb-4">
          <h2 class="text-2xl font-bold text-gray-800 mb-1">{{ userData.name }}</h2>
          <p class="text-sm text-purple-600 font-medium">{{ userData.link }}</p>
        </div>

        <!-- Badges Row -->
        <div class="flex justify-center gap-2 mb-6">
          <div 
            v-for="(badge, index) in userData.badges" 
            :key="index" 
            class="relative"
          >
            <div 
              :class="`w-12 h-12 ${badge.color} rounded-xl flex items-center justify-center text-xl shadow-md transform hover:scale-110 transition-transform`"
            >
              {{ badge.icon }}
            </div>
            <div 
              v-if="badge.count" 
              class="absolute -top-1 -right-1 bg-gray-700 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold"
            >
              {{ badge.count }}
            </div>
          </div>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-3 gap-4 mb-6">
          <div class="text-center">
            <div class="text-2xl font-bold text-gray-800">{{ userData.stats.posts }}</div>
            <div class="text-xs text-gray-500 uppercase tracking-wide">Posts</div>
          </div>
          <div class="text-center border-x border-gray-200">
            <div class="text-2xl font-bold text-gray-800">{{ userData.stats.friends }}</div>
            <div class="text-xs text-gray-500 uppercase tracking-wide">Friends</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-gray-800">{{ userData.stats.visits }}</div>
            <div class="text-xs text-gray-500 uppercase tracking-wide">Visits</div>
          </div>
        </div>

        <!-- Social Links -->
        <div class="flex justify-center gap-3 mb-6">
          <button
            v-for="(social, index) in socialLinks"
            :key="index"
            :class="`${social.color} w-10 h-10 rounded-lg flex items-center justify-center text-white shadow-md hover:shadow-lg transform hover:-translate-y-1 transition-all`"
            :aria-label="social.name"
          >
            <Icon :icon="social.icon" width="18" height="18" />
          </button>
        </div>

        <!-- Action Buttons -->
        <div class="grid grid-cols-2 gap-3">
          <button class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-semibold py-3 rounded-xl hover:shadow-lg transform hover:-translate-y-0.5 transition-all">
            Add Friend +
          </button>
          <button class="bg-gradient-to-r from-cyan-500 to-teal-500 text-white font-semibold py-3 rounded-xl hover:shadow-lg transform hover:-translate-y-0.5 transition-all">
            Send Message
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Icon } from '@iconify/vue';
import HexagonAvatar from './HexagonAvatar.vue';

// Define props for customization
const props = defineProps({
  // Allow passing custom user data
  user: {
    type: Object,
    default: () => ({})
  }
});

// Define emits
const emit = defineEmits(['avatar-loaded']);

// User data from props only
const userData = props.user;

const socialLinks = ref([
  { icon: 'lucide:facebook', color: 'bg-blue-600', name: 'Facebook' },
  { icon: 'lucide:twitter', color: 'bg-sky-500', name: 'Twitter' },
  { icon: 'lucide:instagram', color: 'bg-pink-500', name: 'Instagram' },
  { icon: 'lucide:twitch', color: 'bg-purple-600', name: 'Twitch' },
  { icon: 'lucide:user', color: 'bg-teal-500', name: 'Profile' }
]);

// Event handler for avatar loaded
function onAvatarLoaded() {
  // console.log('Avatar loaded successfully');
  emit('avatar-loaded');
}
</script>

<style scoped>
/* Tailwind CSS classes are used - make sure Tailwind is configured in your Vue project */
</style>
