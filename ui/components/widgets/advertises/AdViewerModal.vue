<template>
  <Transition name="fade">
      <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-md">
        
        <!-- Modal Content -->
        <div class="relative bg-white dark:bg-gray-900 w-screen h-screen flex flex-col md:flex-row overflow-hidden">
          
          <!-- Close Button -->
           <button 
            v-if="canClose" 
            @click="closeModal" 
            class="absolute top-4 right-4 z-10 text-white bg-black/50 hover:bg-black/70 rounded-full p-2 transition-colors"
          >
            <Icon icon="mdi:close" class="w-6 h-6" />
          </button>

          <!-- Left Side: Image/Video -->
          <div class="w-full md:w-2/3 h-1/2 md:h-full bg-black flex items-center justify-center relative">
              <video 
                v-if="isVideo"
                :src="advert?.media_image" 
                class="w-full h-full object-contain" 
                autoplay 
                loop 
                muted 
                playsinline 
                controls
              ></video>
              <img 
                v-else
                :src="advert?.media_image" 
                class="w-full h-full object-contain" 
                alt="Ad Content" 
              />
          </div>

          <!-- Right Side: Interaction & Timer -->
          <div class="w-full md:w-1/3 h-1/2 md:h-full bg-white dark:bg-gray-800 p-8 flex flex-col items-center justify-center text-center relative">
               
               <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 line-clamp-2">
                   {{ advert?.title || 'Product Advertisement' }}
               </h3>
               
               <p v-if="advert?.description" class="text-sm text-gray-500 dark:text-gray-400 mb-4 line-clamp-3">
                   {{ advert.description }}
               </p>

               <a 
                v-if="advert?.media_link" 
                :href="advert.media_link" 
                target="_blank"
                class="mb-6 inline-flex items-center text-teal-600 hover:text-teal-700 font-medium text-sm"
               >
                 <Icon icon="mdi:link" class="mr-1 w-4 h-4" />
                 เยี่ยมชมเว็บไซต์
               </a>
               <div v-else class="mb-6"></div>

               <!-- Circular Progress -->
               <div v-if="timeLeft > 0" class="relative mb-8">
                   <svg class="w-32 h-32 transform -rotate-90">
                       <circle
                           cx="64"
                           cy="64"
                           r="60"
                           stroke="currentColor"
                           stroke-width="8"
                           fill="transparent"
                           class="text-gray-200 dark:text-gray-700"
                       />
                       <circle
                           cx="64"
                           cy="64"
                           r="60"
                           stroke="currentColor"
                           stroke-width="8"
                           fill="transparent"
                           :stroke-dasharray="circumference"
                           :stroke-dashoffset="dashOffset"
                           class="text-teal-500 transition-all duration-1000 ease-linear"
                           stroke-linecap="round"
                       />
                   </svg>
                   <div class="absolute inset-0 flex items-center justify-center flex-col">
                       <span class="text-3xl font-bold text-gray-800 dark:text-white">{{ timeLeft }}</span>
                       <span class="text-xs text-gray-500 uppercase">Seconds</span>
                   </div>
               </div>

               <!-- Success State -->
               <div v-else-if="rewardClaimed" class="mb-8 flex flex-col items-center animate-bounce-in">
                   <div class="w-24 h-24 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-4">
                       <Icon icon="mdi:check-bold" class="w-12 h-12 text-green-600 dark:text-green-400" />
                   </div>
                   <h4 class="text-2xl font-bold text-green-600 dark:text-green-400 mb-1">สำเร็จ!</h4>
                   <p class="text-gray-600 dark:text-gray-400">ได้รับรางวัลเรียบร้อยแล้ว</p>
               </div>
               
               <!-- Processing State (Center) -->
               <div v-else-if="processing" class="mb-8 flex flex-col items-center">
                   <Icon icon="svg-spinners:3-dots-fade" class="w-24 h-24 text-teal-500" />
                   <h4 class="text-xl font-bold text-gray-600 dark:text-gray-300 mb-1">กำลังประมวลผล...</h4>
               </div>

               <!-- Max Views Reached State -->
               <div v-else-if="maxViewsReached" class="mb-8 flex flex-col items-center">
                   <div class="w-24 h-24 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                       <Icon icon="mdi:eye-off-outline" class="w-12 h-12 text-gray-500 dark:text-gray-400" />
                   </div>
                   <h4 class="text-2xl font-bold text-gray-600 dark:text-gray-300 mb-1">โฆษณานี้แสดงผลครบแล้ว</h4>
                   <p class="text-gray-500 dark:text-gray-400 text-sm max-w-xs">
                       โฆษณานี้มียอดการรับชมครบตามจำนวนที่กำหนดแล้ว ไม่สามารถรับรางวัลได้
                   </p>
               </div>

               <!-- Failed State -->
               <div v-else class="mb-8 flex flex-col items-center">
                    <Icon icon="mdi:alert-circle" class="w-24 h-24 text-red-500" />
                    <h4 class="text-xl font-bold text-red-500 mb-1">เสร็จสิ้น</h4>
                    <p class="text-sm text-gray-500">กรุณารอผลสักครู่...</p>
               </div>

               <div v-if="timeLeft > 0" class="space-y-2">
                   <p class="text-gray-600 dark:text-gray-300">รับชมเพื่อรับรางวัล</p>
                   <p class="text-2xl font-bold text-amber-500">{{ (advert?.duration * 0.07).toFixed(2) }} ฿</p>
               </div>

               <div v-if="processing" class="absolute bottom-4 left-0 right-0">
                    <div class="flex items-center justify-center text-teal-500 gap-2">
                        <Icon icon="svg-spinners:3-dots-fade" class="w-6 h-6" />
                        <span class="text-sm">Processing Reward...</span>
                    </div>
               </div>

          </div>

        </div>
      </div>
  </Transition>
</template>

<script setup>
import { ref, watch, onUnmounted, computed } from 'vue';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';
import { useRuntimeConfig } from '#app';
import { useAuthStore } from '~/stores/auth'

const props = defineProps({
  isOpen: Boolean,
  advert: Object,
});

const emit = defineEmits(['close', 'completed']);

const config = useRuntimeConfig();
const apiBase = config.public.apiBase;
const authStore = useAuthStore();

const timeLeft = ref(0);
const timer = ref(null);
const canClose = ref(false);
const processing = ref(false);
const totalDuration = ref(0);
const rewardClaimed = ref(false);
const maxViewsReached = ref(false);

// Circular Progress Logic
const radius = 60;
const circumference = 2 * Math.PI * radius;
const dashOffset = computed(() => {
    if (totalDuration.value === 0) return 0;
    const progress = timeLeft.value / totalDuration.value;
    return circumference * (1 - progress); // Inverted logic for countdown filling or emptying
});

const isVideo = computed(() => {
    if (!props.advert?.media_image) return false;
    const ext = props.advert.media_image.split('.').pop().toLowerCase();
    return ['mp4', 'webm', 'ogg'].includes(ext);
});


watch(() => props.isOpen, (newVal) => {
    if (newVal && props.advert) {
        startAd();
    } else {
        resetAd();
    }
});

function startAd() {
    timeLeft.value = props.advert.duration;
    totalDuration.value = props.advert.duration;
    canClose.value = false;
    processing.value = false;
    rewardClaimed.value = false;
    maxViewsReached.value = false;
    
    // Check remaining views
    if (props.advert.remaining_views <= 0) {
        maxViewsReached.value = true;
        canClose.value = true;
        timeLeft.value = 0; // Ensure timer UI doesn't show
        return;
    }
    
    // Clear any existing timer
    if (timer.value) clearInterval(timer.value);

    timer.value = setInterval(() => {
        timeLeft.value--;
        if (timeLeft.value <= 0) {
            clearInterval(timer.value);
            claimReward();
        }
    }, 1000);
}

async function claimReward() {
    processing.value = true;
    try {
        const response = await $fetch(`${apiBase}/api/advertises/${props.advert.id}/view`, {
            method: 'POST',
             headers: {
                Authorization: `Bearer ${authStore.token}`
            }
        });
        
        if (response.success) {
            // Update auth store if possible
            if(authStore.fetchUser) authStore.fetchUser();

             // Play sound effect? (Optional)
             rewardClaimed.value = true;
             emit('completed', props.advert);
        }
    } catch (error) {
        console.error(error);
        const errorData = error.data || {};
        const statusCode = error.statusCode || error.response?.status;

        if (statusCode === 402) {
             Swal.fire({
                 icon: 'warning',
                 title: 'คะแนนไม่เพียงพอ',
                 text: errorData.message || 'Points not enough',
                 customClass: { popup: 'rounded-xl' }
             });
        } else if (statusCode === 404) {
             maxViewsReached.value = true;
             Swal.fire({
                 icon: 'info',
                 title: 'ครบจำนวนแล้ว',
                 text: 'โฆษณานี้มีผู้รับชมครบตามจำนวนแล้ว',
                 customClass: { popup: 'rounded-xl' }
             });
        } else {
             Swal.fire('เกิดข้อผิดพลาด', errorData.message || 'Failed to claim reward', 'error');
        }
    } finally {
        processing.value = false;
        canClose.value = true; 
    }
}

function resetAd() {
    if (timer.value) clearInterval(timer.value);
    timeLeft.value = 0;
}

function closeModal() {
    resetAd();
    emit('close');
}

onUnmounted(() => {
    if (timer.value) clearInterval(timer.value);
});
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

.animate-bounce-in {
    animation: bounceIn 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

@keyframes bounceIn {
    0% { transform: scale(0); opacity: 0;}
    100% { transform: scale(1); opacity: 1;}
}
</style>
