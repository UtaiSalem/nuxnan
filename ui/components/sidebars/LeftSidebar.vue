<script setup>
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'

const route = useRoute()
const authStore = useAuthStore()

const currentUser = computed(() => authStore.user || {
  name: 'Marina Valentine',
  username: 'Marina Valentine',
  avatar: '/images/default-avatar.png',
  points: 0,
  wallet: 0,
  cover: '/images/default-cover.jpg',
  level: 24,
  website: 'WWW.GAMEHUNTRESS.COM'
})

// User stats
const userStats = ref({
  posts: 930,
  friends: 82,
  visits: '5.7K'
})

// User badges - Hexagon badges ตาม Vikinger
const userBadges = ref([
  { id: 1, icon: 'mdi:emoticon-happy', color: '#f8b739', textColor: '#fff' },
  { id: 2, icon: 'mdi:shield-check', color: '#40d04f', textColor: '#fff', badge: '2' },
  { id: 3, icon: 'mdi:hexagon', color: '#f6712f', textColor: '#fff' },
  { id: 4, icon: 'mdi:diamond-stone', color: '#23d2e2', textColor: '#fff' },
])
const extraBadges = ref(9)

// Navigation menu items - ตรงตาม Vikinger
const menuItems = [
  { name: 'Newsfeed', href: '/play/newsfeed', icon: 'mdi:message-outline' },
  { name: 'Overview', href: '/overview', icon: 'mdi:chart-bar' },
  { name: 'Groups', href: '/play/groups', icon: 'mdi:account-group-outline' },
  { name: 'Schools', href: '/academies', icon: 'mdi:school-outline' },
  { name: 'Members', href: '/members', icon: 'mdi:account-outline' },
  { name: 'Badges', href: '/badges', icon: 'mdi:medal-outline' },
  { name: 'Quests', href: '/quests', icon: 'mdi:star-outline' },
  { name: 'Streams', href: '/play/streams', icon: 'mdi:play-box-outline' },
  { name: 'Events', href: '/events', icon: 'mdi:calendar-outline' },
]

const isActive = (href) => route.path === href || route.path.startsWith(href + '/')

const logout = async () => {
  await authStore.logout()
}
</script>

<template>
  <div class="left-sidebar">
    <!-- Profile Card -->
    <div class="profile-card">
      <!-- Cover Image -->
      <div class="cover-image">
        <img 
          :src="currentUser.cover || '/images/default-cover.jpg'" 
          alt="Cover"
          @error="$event.target.style.display='none'"
        />
      </div>

      <!-- Hexagon Avatar with Green Border -->
      <div class="avatar-section">
        <HexagonAvatar
          :src="currentUser.avatar || '/images/default-avatar.png'"
          :alt="currentUser.name"
          size="lg"
          :show-level="true"
          :level="currentUser.level || 24"
          :border-gradient="{ type: 'linear', angle: 180, colors: ['#40d04f', '#1abc9c'] }"
          :level-badge-gradient="{ type: 'linear', angle: 135, colors: ['#23d2e2', '#1abc9c'] }"
          bg-color="#1d2333"
        />
      </div>

      <!-- User Info -->
      <div class="user-info">
        <h3 class="user-name">{{ currentUser.username || currentUser.name }}</h3>
        <p class="user-website">{{ currentUser.website || 'WWW.PLEARND.COM' }}</p>
      </div>

      <!-- Hexagon Badges -->
      <div class="badges-row">
        <div 
          v-for="badge in userBadges" 
          :key="badge.id"
          class="hex-badge"
          :style="{ backgroundColor: badge.color }"
        >
          <Icon :icon="badge.icon" class="badge-icon" />
          <span v-if="badge.badge" class="badge-number">{{ badge.badge }}</span>
        </div>
        <div v-if="extraBadges > 0" class="hex-badge hex-badge-more">
          <span>+{{ extraBadges }}</span>
        </div>
      </div>

      <!-- Stats -->
      <div class="stats-row">
        <div class="stat-item">
          <span class="stat-value">{{ userStats.posts }}</span>
          <span class="stat-label">POSTS</span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
          <span class="stat-value">{{ userStats.friends }}</span>
          <span class="stat-label">FRIENDS</span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
          <span class="stat-value">{{ userStats.visits }}</span>
          <span class="stat-label">VISITS</span>
        </div>
      </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="nav-menu">
      <NuxtLink 
        v-for="item in menuItems" 
        :key="item.name"
        :to="item.href"
        class="nav-item"
        :class="{ 'nav-item-active': isActive(item.href) }"
      >
        <Icon :icon="item.icon" class="nav-icon" />
        <span class="nav-text">{{ item.name }}</span>
      </NuxtLink>
      
      <!-- Logout Button -->
      <button 
        @click="logout"
        class="nav-item logout-button"
      >
        <Icon icon="mdi:logout" class="nav-icon" />
        <span class="nav-text">Logout</span>
      </button>
    </nav>
  </div>
</template>

<style scoped>
/* ==========================================
   LEFT SIDEBAR - VIKINGER STYLE
   ========================================== */

.left-sidebar {
  position: sticky;
  top: 80px;
  width: 100%;
}

/* Profile Card */
.profile-card {
  background: #1d2333;
  border-radius: 12px;
  overflow: hidden;
}

/* Cover Image */
.cover-image {
  height: 100px;
  overflow: hidden;
}

.cover-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* Avatar Section */
.avatar-section {
  display: flex;
  justify-content: center;
  margin-top: -50px;
  position: relative;
}

/* Hexagon Border (Green Gradient) */
.hexagon-border {
  width: 100px;
  height: 100px;
  background: linear-gradient(180deg, #40d04f 0%, #1abc9c 100%);
  clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 4px;
}

/* Hexagon Avatar */
.hexagon-avatar {
  width: 92px;
  height: 92px;
  background: #1d2333;
  clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.hexagon-avatar img {
  width: 88px;
  height: 88px;
  object-fit: cover;
  clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
}

/* Level Badge */
.level-badge {
  position: absolute;
  bottom: -5px;
  left: 50%;
  transform: translateX(-50%);
  background: linear-gradient(135deg, #23d2e2, #1abc9c);
  color: white;
  font-size: 14px;
  font-weight: 700;
  padding: 4px 12px;
  border-radius: 10px;
  box-shadow: 0 3px 12px rgba(35, 210, 226, 0.5);
}

/* User Info */
.user-info {
  text-align: center;
  padding: 20px 16px 0;
}

.user-name {
  color: #fff;
  font-size: 18px;
  font-weight: 700;
  margin: 0;
}

.user-website {
  color: #9aa4bf;
  font-size: 12px;
  margin-top: 4px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Hexagon Badges Row */
.badges-row {
  display: flex;
  justify-content: center;
  gap: 8px;
  padding: 16px;
}

.hex-badge {
  width: 36px;
  height: 36px;
  clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

.badge-icon {
  width: 18px;
  height: 18px;
  color: white;
}

.badge-number {
  position: absolute;
  bottom: 2px;
  right: 2px;
  font-size: 8px;
  font-weight: 700;
  color: white;
}

.hex-badge-more {
  background: #2f3749;
}

.hex-badge-more span {
  font-size: 11px;
  font-weight: 700;
  color: #9aa4bf;
}

/* Stats Row */
.stats-row {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  border-top: 1px solid rgba(255,255,255,0.06);
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 0 20px;
}

.stat-value {
  color: #fff;
  font-size: 18px;
  font-weight: 700;
}

.stat-label {
  color: #9aa4bf;
  font-size: 11px;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-top: 2px;
}

.stat-divider {
  width: 1px;
  height: 40px;
  background: rgba(255,255,255,0.06);
}

/* Navigation Menu */
.nav-menu {
  background: #1d2333;
  border-radius: 12px;
  margin-top: 16px;
  padding: 8px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 18px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  color: #9aa4bf;
  transition: all 0.3s ease;
  text-decoration: none;
}

.nav-item:hover {
  color: #fff;
  background: rgba(255,255,255,0.04);
}

.nav-icon {
  width: 20px;
  height: 20px;
  opacity: 0.8;
}

.nav-text {
  flex: 1;
}

/* Active Menu Item */
.nav-item-active {
  background: linear-gradient(135deg, #40d04f 0%, #1abc9c 100%) !important;
  color: #fff !important;
  box-shadow: 0 4px 16px rgba(64, 208, 79, 0.35);
}

.nav-item-active .nav-icon {
  opacity: 1;
}

/* Logout Button */
.logout-button {
  background: none;
  border: none;
  cursor: pointer;
  margin-top: 8px;
  border-top: 1px solid rgba(255,255,255,0.06);
  padding: 14px 18px;
}

.logout-button:hover {
  background: rgba(255, 85, 85, 0.1) !important;
  color: #ff5555 !important;
}

.logout-button:hover .nav-icon {
  opacity: 1;
  color: #ff5555;
}

/* Light Mode Overrides */
:root:not(.dark) .profile-card,
:root:not(.dark) .nav-menu {
  background: #fff;
  box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}

:root:not(.dark) .hexagon-avatar {
  background: #fff;
}

:root:not(.dark) .user-name {
  color: #3e3f5e;
}

:root:not(.dark) .stat-value {
  color: #3e3f5e;
}

:root:not(.dark) .nav-item {
  color: #8f91ac;
}

:root:not(.dark) .nav-item:hover {
  color: #3e3f5e;
  background: rgba(0,0,0,0.04);
}

:root:not(.dark) .stats-row {
  border-top-color: rgba(0,0,0,0.06);
}

:root:not(.dark) .stat-divider {
  background: rgba(0,0,0,0.06);
}

:root:not(.dark) .hex-badge-more {
  background: #eaeaf5;
}
</style>
