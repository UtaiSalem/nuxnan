<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    images: Array,
    model_id: Number,
    model: Object,
    edit: Boolean,
    isLoading: Boolean,
    imagesCount: Number,
});

// Intersection Observer for lazy loading images
let imageObserver = null;

const loadImage = (index) => {
    if (loadedImages.value.includes(index)) return;
    
    const img = new Image();
    img.onload = () => {
        loadedImages.value.push(index);
    };
    img.src = props.images[index].url;
};

const setupImageObserver = () => {
    if (!('IntersectionObserver' in window)) {
        // Fallback for browsers that don't support IntersectionObserver
        props.images.forEach((_, index) => {
            loadImage(index);
        });
        return;
    }

    imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const index = parseInt(entry.target.dataset.index);
                loadImage(index);
                imageObserver.unobserve(entry.target);
            }
        });
    }, { 
        threshold: 0.1,
        rootMargin: '50px' // Start loading 50px before the image is in view
    });

    // Observe all image placeholders
    imageRefs.value.forEach((ref) => {
        if (ref) {
            imageObserver.observe(ref);
        }
    });
};

const loadedImages = ref([]);
const imageRefs = ref([]);

onMounted(() => {
    setupImageObserver();
});

onUnmounted(() => {
    if (imageObserver) {
        imageObserver.disconnect();
    }
});
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 mt-4">
        <!-- Loading skeleton -->
        <div v-if="isLoading && images.length === 0" class="col-span-full flex justify-center py-4">
            <div class="animate-pulse space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                    <div v-for="i in 3" :key="i" class="bg-gray-300 rounded-lg h-48"></div>
                </div>
            </div>
        </div>
        
        <!-- Show placeholder if there are no images but we know there should be -->
        <div v-if="!isLoading && images.length === 0 && imagesCount > 0" class="col-span-full flex justify-center py-4">
            <div class="text-gray-500 text-sm">
                มีรูปภาพ {{ imagesCount }} รูป
            </div>
        </div>
        
        <!-- Image placeholders -->
        <div 
            v-for="(image, index) in images" 
            :key="index"
            ref="imageRefs"
            :data-index="index"
            class="relative group"
        >
            <!-- Loading placeholder -->
            <div v-if="!loadedImages.includes(index)" class="bg-gray-200 rounded-lg h-48 animate-pulse flex items-center justify-center">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
            
            <!-- Actual image -->
            <img 
                v-show="loadedImages.includes(index)"
                :src="image.url"
                :alt="`Course post image ${index + 1}`"
                class="rounded-lg w-full h-48 object-cover transition-opacity duration-300"
                :class="{ 'opacity-0': !loadedImages.includes(index), 'opacity-100': loadedImages.includes(index) }"
            >
            
            <!-- Edit overlay -->
            <div v-if="edit" class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all duration-200 rounded-lg flex items-center justify-center">
                <button v-show="loadedImages.includes(index)" class="opacity-0 group-hover:opacity-100 bg-white text-gray-800 px-3 py-1 rounded-md text-sm transition-opacity duration-200">
                    แก้ไข
                </button>
            </div>
        </div>
    </div>
</template>