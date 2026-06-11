<template>
    <div v-if="images?.length" class="mt-4">
        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
            <img
                v-for="(img, index) in images"
                :key="img.id"
                :src="img.full_url"
                class="w-full h-40 object-cover rounded cursor-pointer hover:scale-105 transition-transform"
                @click="openImage(index)"
            />
        </div>
        <ImageGalleryModal
            :show="showGallery"
            :images="images"
            :start-index="startIndex"
            title="รูปภาพประกอบบทเรียน"
            @close="showGallery = false"
        />
    </div>
</template>

<script setup>
import { ref } from 'vue'
import ImageGalleryModal from '~/components/ImageGalleryModal.vue'

defineProps({
    model_id: Number,
    images: Array,
    edit: Boolean
})

const showGallery = ref(false)
const startIndex = ref(0)
const openImage = (index) => {
    startIndex.value = index
    showGallery.value = true
}
</script>
