<template>
  <div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-900">
    <div class="max-w-7xl mx-auto">
      <!-- Page Header -->
      <div class="text-center mb-8">
        <div class="w-20 h-20 mx-auto mb-4 bg-gradient-to-br from-purple-400 to-purple-600 rounded-full flex items-center justify-center shadow-lg">
          <Icon icon="eos-icons:product-subscriptions-outlined" class="w-10 h-10 text-white" />
        </div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
          ดูสินค้า
        </h1>
        <p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
          ชมโฆษณาเพื่อรับรางวัลและสนับสนุนผู้ขาย
        </p>
      </div>

      <!-- Actions -->
      <div class="flex justify-center mb-6">
      <div class="flex justify-center gap-4 mb-6">
          <NuxtLink to="/earn/advertise/create" class="flex items-center px-4 py-2 bg-teal-500 text-white rounded-lg hover:bg-teal-600 shadow-md transition-colors">
              <Icon icon="mdi:plus-circle" class="mr-2 w-5 h-5"/>
              ลงโฆษณาสินค้า
          </NuxtLink>
          <NuxtLink to="/earn/donates/create" class="flex items-center px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 shadow-md transition-colors">
              <Icon icon="flat-color-icons:donate" class="mr-2 w-5 h-5"/>
              ให้การสนับสนุนทุนการเรียนรู้
          </NuxtLink>
      </div>
      </div>

      <!-- Ad Listing -->
      <!-- Skeleton Loader -->
      <div v-if="isLoading" class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 animate-pulse">
           <div v-for="i in 6" :key="i" class="bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-sm border border-gray-100 dark:border-gray-700">
               <div class="h-48 bg-gray-200 dark:bg-gray-700 w-full"></div>
               <div class="p-4 space-y-3">
                   <div class="h-6 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                   <div class="flex justify-between items-end">
                       <div class="w-1/3">
                           <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-full mb-1"></div>
                           <div class="h-5 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
                       </div>
                       <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-1/3"></div>
                   </div>
               </div>
           </div>
      </div>

      <div v-else-if="adverts.length > 0" class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div v-for="(advert, index) in adverts" :key="advert.id" class="transform hover:-translate-y-1 transition-transform duration-200">
              <AdvertiseItemCard :advert="advert" :index="index" @click="handleAdClick" />
          </div>
      </div>

      <div v-else class="text-center py-12 bg-white rounded-lg shadow">
          <Icon icon="mdi:inbox-outline" class="w-16 h-16 mx-auto text-gray-300 mb-4" />
          <p class="text-gray-500 text-lg">ไม่มีโฆษณาในขณะนี้</p>
      </div>

      <!-- Load More -->
      <div v-if="currentPage < lastPage" class="flex justify-center mt-8">
          <button 
            @click="loadMore" 
            :disabled="isLoadingMore"
            class="px-6 py-2 bg-gray-200 text-gray-700 rounded-full hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed flex items-center transition-colors"
          >
              <Icon v-if="isLoadingMore" icon="svg-spinners:3-dots-fade" class="w-5 h-5 mr-2" />
              <span v-else>โหลดเพิ่มเติม</span>
          </button>
      </div>

    </div>

    <!-- Ad Viewer Modal -->
    <AdViewerModal 
        :isOpen="isAdModalOpen" 
        :advert="selectedAdvert" 
        @close="handleAdClose" 
        @completed="handleAdCompleted" 
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { Icon } from '@iconify/vue'
import AdvertiseItemCard from '@/components/widgets/advertises/AdvertiseItemCard.vue'
import AdViewerModal from '@/components/widgets/advertises/AdViewerModal.vue'
import Swal from 'sweetalert2'
import { useAuthStore } from '~/stores/auth'

definePageMeta({
  layout: 'main'
})

useHead({
  title: 'ดูสินค้า - Nuxni'
})

const adverts = ref([])
const isLoading = ref(true)

// Ad Viewer Logic
const authStore = useAuthStore()
const config = useRuntimeConfig()
const apiBase = config.public.apiBase

// Ad Viewer Logic
const selectedAdvert = ref(null)
const isAdModalOpen = ref(false)

const currentPage = ref(1)
const lastPage = ref(1)
const isLoadingMore = ref(false)

async function fetchAdverts(page = 1) {
    if (page === 1) {
        isLoading.value = true;
    } else {
        isLoadingMore.value = true;
    }
    
    try {
        const response = await $fetch<any>(`${apiBase}/api/advertises?page=${page}`);
        if(response && response.adverts) {
            const newAdverts = response.adverts.data || response.adverts;
            if (page === 1) {
                adverts.value = newAdverts;
            } else {
                adverts.value = [...adverts.value, ...newAdverts];
            }
            
            // Update pagination meta
            const meta = response.adverts.meta || {};
            currentPage.value = meta.current_page || page;
            lastPage.value = meta.last_page || 1;
        }
    } catch (error) {
        console.error("Failed to fetch adverts", error);
    } finally {
        isLoading.value = false;
        isLoadingMore.value = false;
    }
}

function loadMore() {
    if (currentPage.value < lastPage.value) {
        fetchAdverts(currentPage.value + 1);
    }
}

function handleAdClick(advert) {
    if(!authStore.isAuthenticated) {
         Swal.fire({
            icon: 'warning',
            title: 'เข้าสู่ระบบ',
            text: 'กรุณาเข้าสู่ระบบเพื่อรับชมโฆษณา'
        });
        return;
    }

    const pointsRequired = advert.duration * 20;
    // user.value.points logic, authStore has `points` computed
    if (authStore.points < pointsRequired) {
        Swal.fire({
            icon: 'error',
            title: 'คะแนนไม่เพียงพอ',
            text: `คุณต้องมีอย่างน้อย ${pointsRequired} PP เพื่อรับชมโฆษณานี้ (คุณมี ${authStore.points} PP)`
        });
        return;
    }

    selectedAdvert.value = advert;
    isAdModalOpen.value = true;
}

function handleAdClose() {
    isAdModalOpen.value = false;
    selectedAdvert.value = null;
}

function handleAdCompleted(advert) {
    // Refresh user data (if possible) and Update UI
    const index = adverts.value.findIndex(a => a.id === advert.id);
    if (index !== -1) {
        adverts.value[index].remaining_views--;
    }
}

onMounted(() => {
    fetchAdverts();
});
</script>
