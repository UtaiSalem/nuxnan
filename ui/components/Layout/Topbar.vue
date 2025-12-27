<template>
  <header class="bg-slate-700 text-white shadow-lg">
    <div class="max-w-full px-4 py-3">
      <div class="flex items-center justify-between">
        
        <!-- Left Section - Logo & Search -->
        <div class="flex items-center gap-4 flex-1">
          <!-- Logo -->
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-gradient-to-br from-orange-400 via-pink-400 to-cyan-400 rounded-lg flex items-center justify-center">
              <div class="w-6 h-6 bg-white rounded opacity-30"></div>
            </div>
            <span class="text-xl font-bold">pitnik</span>
          </div>
          
          <!-- Search Bar -->
          <div class="relative max-w-md w-full">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search People, Pages, Groups etc"
              class="w-full bg-slate-600 text-white placeholder-slate-400 rounded-full py-2 px-4 pr-10 focus:outline-none focus:ring-2 focus:ring-slate-500"
              @keyup.enter="handleSearch"
            />
            <svg 
              class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <circle cx="11" cy="11" r="8"></circle>
              <path d="m21 21-4.35-4.35"></path>
            </svg>
          </div>
        </div>

        <!-- Center Section - Newsfeed -->
        <div class="flex-1 flex justify-center">
          <button 
            @click="goToNewsfeed"
            class="text-white font-bold text-sm tracking-wider hover:text-slate-300 transition"
          >
            NEWSFEED
          </button>
        </div>

        <!-- Right Section - Icons & Profile -->
        <div class="flex items-center gap-3 flex-1 justify-end">
          <!-- Home Icon -->
          <button 
            @click="goToHome"
            class="p-2 hover:bg-slate-600 rounded-lg transition relative"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
              <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
          </button>
          
          <!-- Friends Icon with Badge -->
          <button 
            @click="goToFriends"
            class="p-2 hover:bg-slate-600 rounded-lg transition relative"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            <span 
              v-if="friendRequests > 0"
              class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold"
            >
              {{ friendRequests }}
            </span>
          </button>
          
          <!-- Messages Icon -->
          <button 
            @click="goToMessages"
            class="p-2 hover:bg-slate-600 rounded-lg transition relative"
          >
            <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
          </button>
          
          <!-- Notifications Icon with Badge -->
          <button 
            @click="goToNotifications"
            class="p-2 hover:bg-slate-600 rounded-lg transition relative"
          >
            <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
              <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
            <span 
              v-if="notifications >= 0"
              class="absolute -top-1 -right-1 bg-cyan-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold"
            >
              {{ notifications }}
            </span>
          </button>
          
          <!-- Settings Icon -->
          <button 
            @click="openSettings"
            class="p-2 hover:bg-slate-600 rounded-lg transition"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="3"></circle>
              <path d="M12 1v6m0 6v6m5.66-13l-3 5.2M9.34 17.8l-3 5.2m11.32-15.4-5.2 3M6.8 14.66l-5.2 3m15.4-11.32-5.2 3M14.66 17.8l3 5.2"></path>
            </svg>
          </button>
          
          <!-- Help Icon -->
          <button 
            @click="openHelp"
            class="p-2 hover:bg-slate-600 rounded-lg transition"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="10"></circle>
              <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
              <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
          </button>

          <!-- Language Selector -->
          <div class="text-xs bg-slate-600 px-2 py-1 rounded cursor-pointer hover:bg-slate-500 transition">
            {{ currentLanguage }}
          </div>

          <!-- Divider -->
          <div class="h-8 w-px bg-slate-600"></div>

          <!-- QR Code Button -->
          <button 
            @click="openQRCode"
            class="p-2 hover:bg-slate-600 rounded-lg transition"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <rect x="3" y="3" width="8" height="8" rx="1"></rect>
              <rect x="13" y="3" width="8" height="8" rx="1"></rect>
              <rect x="3" y="13" width="8" height="8" rx="1"></rect>
              <rect x="13" y="13" width="8" height="8" rx="1"></rect>
            </svg>
          </button>

          <!-- Profile -->
          <button 
            @click="openProfile"
            class="flex items-center gap-2 hover:bg-slate-600 rounded-full pl-1 pr-3 py-1 transition"
          >
            <div class="w-8 h-8 bg-slate-500 rounded-full flex items-center justify-center overflow-hidden">
              <img 
                :src="userAvatar" 
                :alt="userName"
                class="w-full h-full object-cover"
              />
            </div>
            <span class="text-sm font-medium">{{ userName }}</span>
          </button>

          <!-- Settings Icon (Right) -->
          <button 
            @click="openSettings"
            class="p-2 hover:bg-slate-600 rounded-lg transition"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="3"></circle>
              <path d="M12 1v6m0 6v6m5.66-13l-3 5.2M9.34 17.8l-3 5.2m11.32-15.4-5.2 3M6.8 14.66l-5.2 3m15.4-11.32-5.2 3M14.66 17.8l3 5.2"></path>
            </svg>
          </button>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref } from 'vue';

// State
const searchQuery = ref('');
const friendRequests = ref(3);
const notifications = ref(0);
const currentLanguage = ref('EN');
const userName = ref('Jack Carter');
const userAvatar = ref('https://api.dicebear.com/7.x/avataaars/svg?seed=Jack');

// Methods
const handleSearch = () => {
  console.log('Searching for:', searchQuery.value);
  // Add your search logic here
};

const goToNewsfeed = () => {
  console.log('Navigate to Newsfeed');
  // Add navigation logic
};

const goToHome = () => {
  console.log('Navigate to Home');
};

const goToFriends = () => {
  console.log('Navigate to Friends');
};

const goToMessages = () => {
  console.log('Navigate to Messages');
};

const goToNotifications = () => {
  console.log('Navigate to Notifications');
};

const openSettings = () => {
  console.log('Open Settings');
};

const openHelp = () => {
  console.log('Open Help');
};

const openQRCode = () => {
  console.log('Open QR Code');
};

const openProfile = () => {
  console.log('Open Profile');
};
</script>

<style scoped>
/* Optional: Add any additional custom styles here */
</style>