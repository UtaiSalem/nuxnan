<template>
  <div>
    <!-- Page Header -->
    <section class="bg-gradient-to-r from-primary-500 to-primary-600 text-white py-6 mb-4">
      <div class="container mx-auto px-4">
        <h1 class="text-2xl font-bold mb-1">Market Place</h1>
        <div class="flex items-center gap-2 text-sm text-primary-100">
          <NuxtLink to="/" class="hover:text-white transition-colors">Home</NuxtLink>
          <Icon icon="mdi:chevron-right" class="w-4 h-4" />
          <span>Market Place</span>
        </div>
      </div>
    </section>

    <!-- Categories -->
    <div class="container mx-auto px-4 mb-6">
      <div class="flex gap-2 overflow-x-auto pb-2">
        <button
          v-for="category in categories"
          :key="category"
          @click="activeCategory = category"
          class="px-4 py-2 rounded-full font-medium whitespace-nowrap transition-colors"
          :class="
            activeCategory === category
              ? 'bg-primary-600 text-white'
              : 'bg-white text-secondary-700 hover:bg-gray-100'
          "
        >
          {{ category }}
        </button>
      </div>
    </div>

    <!-- Products Grid -->
    <div class="container mx-auto px-4 pb-6">
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <ProductCard v-for="product in products" :key="product.id" :product="product" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'

const activeCategory = ref('All')
const categories = ['All', 'Electronics', 'Fashion', 'Home', 'Sports', 'Books']

const products = ref([
  {
    id: 1,
    name: 'Wireless Headphones',
    price: 129.99,
    image: 'https://picsum.photos/400/400?random=60',
    seller: 'Tech Store',
    condition: 'New',
  },
  {
    id: 2,
    name: 'Vintage Camera',
    price: 299.99,
    image: 'https://picsum.photos/400/400?random=61',
    seller: 'Camera Shop',
    condition: 'Used',
  },
  {
    id: 3,
    name: 'Running Shoes',
    price: 89.99,
    image: 'https://picsum.photos/400/400?random=62',
    seller: 'Sports Gear',
    condition: 'New',
  },
  {
    id: 4,
    name: 'Designer Watch',
    price: 499.99,
    image: 'https://picsum.photos/400/400?random=63',
    seller: 'Luxury Store',
    condition: 'New',
  },
  {
    id: 5,
    name: 'Gaming Keyboard',
    price: 159.99,
    image: 'https://picsum.photos/400/400?random=64',
    seller: 'Gaming Hub',
    condition: 'New',
  },
  {
    id: 6,
    name: 'Leather Backpack',
    price: 79.99,
    image: 'https://picsum.photos/400/400?random=65',
    seller: 'Fashion Store',
    condition: 'Used',
  },
])

useHead({
  title: 'Market Place',
})
</script>
