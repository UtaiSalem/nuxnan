<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow cursor-pointer" @click="$emit('click', advert)">
      
      <!-- Header: Profile -->
      <div class="p-3 flex items-center gap-3">
          <img 
            :src="advert.advertiser?.avatar || advert.user?.avatar || '/images/default-avatar.png'" 
            class="w-10 h-10 rounded-full object-cover bg-gray-100" 
            alt="Profile" 
          />
          <div>
              <h4 class="font-bold text-sm text-blue-600 leading-tight">
                  {{ advert.advertiser?.name || advert.user?.name || 'Unknown User' }}
              </h4>
              <p class="text-xs text-gray-800 font-semibold">
                  {{ advert.advertiser?.personal_code || advert.user?.personal_code || advert.advertiser?.id || 'Code' }}
              </p>
          </div>
          <div class="ml-auto">
             <span class="text-xs text-gray-500">ประชาสัมพันธ์</span>
          </div>
      </div>

      <!-- Main Image -->
      <div class="w-full aspect-square bg-gray-100 flex items-center justify-center overflow-hidden">
          <video 
            v-if="isVideo(advert.media_image) || (advert.media_type && advert.media_type.startsWith('video/'))"
            :src="advert.media_image" 
            class="w-full h-full object-cover" 
            autoplay 
            muted 
            loop 
            playsinline
          ></video>
          <img 
            v-else
            :src="advert.media_image" 
            class="w-full h-full object-cover" 
            alt="Ad Image" 
            loading="lazy"
          />
      </div>

      <!-- Footer: Stats -->
      <div class="p-3 text-center border-t border-gray-100">
          <div class="flex items-center justify-center gap-1 text-sm font-bold">
              <span class="text-amber-400 text-lg">{{ advert.total_views || 0 }}</span> 
              <span class="text-teal-500/80 text-xs">views</span>
              <span class="text-gray-300 mx-1">/</span>
              <span class="text-blue-500 text-xs">ใช้ได้</span>
              <span class="text-blue-500 text-lg">{{ advert.remaining_views || 0 }}</span>
              <span class="text-blue-500 text-xs">views</span>
          </div>
      </div>
  </div>
</template>
<script setup>
import { Icon } from '@iconify/vue'
defineProps({
    advert: Object,
    index: Number
})
defineEmits(['click'])

const isVideo = (url) => {
    if (!url) return false
    return /\.(mp4|webm|ogg)$/i.test(url)
}
</script>
