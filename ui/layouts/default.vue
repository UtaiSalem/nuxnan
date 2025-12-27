<script setup lang="ts">
import { ref } from 'vue'
import TheNavbar from '~/components/organisms/TheNavbar.vue'
import TheSidebar from '~/components/organisms/TheSidebar.vue'
import FriendListSidebar from '~/components/organisms/FriendListSidebar.vue'
import { useUIStore } from '~/stores/ui'

const uiStore = useUIStore()

const isOpen = ref(true)

const toggleSidebar = () => {
  isOpen.value = !isOpen.value
}

</script>

<template>
  <div>
    <!-- <TheNavbar />
    
    <TheSidebar  :is-open="isOpen" @toggle="toggleSidebar" />
    
    <FriendListSidebar />

    <main 
      class="pt-[76px] pb-8 px-4 transition-all duration-300"
      :class="[
        isOpen ? 'ml-64' : 'ml-20',
        'xl:mr-72'
      ]"
    >
      <div class="container mx-auto max-w-7xl">
        <slot />
      </div>
    </main> -->
  </div>
</template>
